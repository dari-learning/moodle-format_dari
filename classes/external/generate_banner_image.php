<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace format_dari\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use format_dari\local\banner;

/**
 * Web service generating a course banner image with the site's own AI provider (core_ai).
 *
 * SECURITY:
 *  - moodle/course:update is required;
 *  - guests are refused outright, because the call is billed to the school's AI provider;
 *  - the call is rate limited per user per course (see \format_dari\external\throttle);
 *  - the returned bytes are strictly base64 decoded, size capped and confirmed to be an image of
 *    an allowed type before they are stored, and the file extension comes from the detected type.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_banner_image extends external_api {
    /** @var int Maximum accepted size in bytes for a generated banner image. */
    protected const MAX_BANNER_BYTES = 12 * 1024 * 1024;

    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Id of the course to generate a banner for'),
            // The teacher's own extra direction for the image, e.g. "warm evening light,
            // no people". Optional and defaulted, so an older cached courseformat.js that does not
            // send it still calls this function successfully rather than failing validation.
            'extraprompt' => new external_value(
                PARAM_TEXT,
                'Optional extra detail from the teacher, added to the image prompt',
                VALUE_DEFAULT,
                ''
            ),
            // Which banner to generate. 0, the default, means the course banner, so a
            // browser still running an older bundle keeps calling this function successfully and
            // keeps generating course banners, which is what it thinks it is doing.
            'sectionid' => new external_value(
                PARAM_INT,
                'course_sections.id to generate a section banner for, or 0 for the course banner',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Generate and store a banner image for the course.
     *
     * @param int $courseid Id of the course.
     * @return array The URL of the stored image and the credits it cost.
     */
    public static function execute(int $courseid, string $extraprompt = '', int $sectionid = 0): array {
        global $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'extraprompt' => $extraprompt,
            'sectionid' => $sectionid,
        ]);

        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // Refuses a section belonging to some other course: the capability above was checked
        // against THIS course's context and would otherwise authorise writing into that one.
        $sectioninfo = banner::require_section_in_course($course, (int) $params['sectionid']);
        $targetsectionid = $sectioninfo ? (int) $sectioninfo->id : 0;

        // Guests must never use the school's AI provider.
        if (isguestuser()) {
            throw new \moodle_exception('error_guestnotallowed', 'format_dari');
        }

        // Note: Banner generation is a 90 second call that spends purchased credits.
        //
        // Section banners deliberately share the COURSE's bucket rather than getting one
        // each. The limit exists because each call spends real credits, and a per-section bucket
        // would multiply the ceiling by the number of sections -- which is the opposite of a
        // limit. Three a minute still allows a teacher to work through a course's sections
        // steadily; it only stops a stuck retry loop or a scripted burst.
        throttle::check(
            'bannerimage',
            $course->id,
            (int) $USER->id,
            throttle::BANNER_MAX,
            throttle::BANNER_WINDOW
        );

        // Check the site's AI provider HERE, before queueing, so a site with no image provider
        // enabled tells the teacher at once rather than minutes later from cron. The check runs
        // again in the task because configuration can change between the two.
        \format_dari\local\ai::require_available(\format_dari\local\ai::FEATURE_IMAGE, $context);
        \format_dari\local\ai::require_policy((int) $USER->id);

        // Note: queue the work, do not do it here.
        //
        // Generation takes about 110 seconds. Done inline it had to outlive every intermediary
        // between the browser and PHP, and the shortest timeout won: a reverse proxy at 60s,
        // Cloudflare's fixed 100s on its lower tiers, PHP-FPM at 30s. Sites behind one saw a 504
        // and no banner. Raising this plugin's cURL timeout could never have helped -- the
        // connection was already severed upstream of PHP.
        //
        // The browser now gets an answer immediately and polls get_banner_status for the result.
        // The extra prompt travels with the task, not in a session or a cache. The task
        // may run minutes later in cron, in a different process, for a user who has since logged
        // out -- custom data is the only thing that survives that journey. Capped at 300
        // characters here, which is the authoritative cap; the textarea's maxlength and the JS
        // slice are conveniences, and neither is trusted.
        $extra = \core_text::substr(trim($params['extraprompt']), 0, 300);

        $task = new \format_dari\task\generate_banner();
        $task->set_custom_data([
            'courseid' => (int) $course->id,
            'extraprompt' => $extra,
            'sectionid' => $targetsectionid,
            'userid' => (int) $USER->id,
        ]);
        $task->set_component('format_dari');
        // The task runs as the teacher who asked: core_ai logs the request against them, applies
        // their rate limit and writes the image into their draft area.
        $task->set_userid((int) $USER->id);
        self::set_status((int) $course->id, 'queued', '', $targetsectionid);
        \core\task\manager::queue_adhoc_task($task);

        return [
            'status' => 'queued',
            'imageurl' => '',
            'message' => '',
        ];
    }

    /**
     * Record the state of a course's banner generation.
     *
     * Stored in plugin config rather than a new table: it is one short string per course that is
     * read a few times a minute while a teacher watches a spinner and is meaningless afterwards.
     * A table would need an install step, an upgrade step, a backup decision and a privacy
     * declaration for data with a lifetime of about two minutes.
     *
     * @param int $courseid The course being generated for.
     * @param string $state One of queued, running, done, failed.
     * @param string $detail The image URL when done, the failure reason when failed.
     * @param int $sectionid course_sections.id for a section banner, 0 for the course banner.
     * @return void
     */
    public static function set_status(int $courseid, string $state, string $detail, int $sectionid = 0): void {
        set_config(
            self::status_key($courseid, $sectionid),
            json_encode(['state' => $state, 'detail' => $detail, 'time' => time()]),
            'format_dari'
        );
    }

    /**
     * Name of the config setting holding one generation's state.
     *
     * Section banners get their own key. Sharing the course's would mean two generations started
     * a few seconds apart -- which is exactly how a teacher works through a course's sections --
     * overwriting each other's state, so the browser polling for one would be told about the
     * other and could apply the wrong image to the wrong section.
     *
     * The course banner's key is left in its original shape so a generation already queued when
     * a site upgrades is still found by the poll that follows it.
     *
     * @param int $courseid The course.
     * @param int $sectionid course_sections.id, or 0 for the course banner.
     * @return string
     */
    protected static function status_key(int $courseid, int $sectionid): string {
        if ($sectionid > 0) {
            return 'bannerstatus_' . $courseid . '_s' . $sectionid;
        }
        return 'bannerstatus_' . $courseid;
    }

    /**
     * Read back the state of a course's banner generation.
     *
     * @param int $courseid The course to report on.
     * @param int $sectionid course_sections.id for a section banner, 0 for the course banner.
     * @return array{state: string, detail: string, time: int}
     */
    public static function get_status(int $courseid, int $sectionid = 0): array {
        $raw = get_config('format_dari', self::status_key($courseid, $sectionid));
        if ($raw === false || $raw === '') {
            return ['state' => 'idle', 'detail' => '', 'time' => 0];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['state'])) {
            return ['state' => 'idle', 'detail' => '', 'time' => 0];
        }
        return [
            'state' => (string) $decoded['state'],
            'detail' => (string) ($decoded['detail'] ?? ''),
            'time' => (int) ($decoded['time'] ?? 0),
        ];
    }

    /**
     * Generate a banner with the site's AI provider and store it against the course or section.
     *
     * The prompt is written by \format_dari\local\cardprompt with the same composer as the card
     * images, so a banner and its course's cards look like one set.
     *
     * @param \stdClass $course The course to generate for.
     * @param string $extraprompt The teacher's own extra direction for the image.
     * @param int $sectionid course_sections.id for a section banner, 0 for the course banner.
     * @param int $userid The teacher the request is made for; 0 for the current user.
     * @return string The moodle_url of the stored image.
     */
    public static function generate_and_store(\stdClass $course, string $extraprompt = '', int $sectionid = 0,
            int $userid = 0): string {
        global $USER;

        $context = \context_course::instance($course->id);
        $userid = $userid > 0 ? $userid : (int) $USER->id;

        $sectioninfo = banner::require_section_in_course($course, $sectionid);
        $composed = \format_dari\local\cardprompt::compose_banner($course, $sectioninfo, trim($extraprompt));

        $imagedata = \format_dari\local\ai::generate_image(
            $context,
            $userid,
            \format_dari\local\ai::image_prompt($composed, $context, $userid),
            'landscape'
        );

        // The bytes come from the site's own provider through core, but they are still checked
        // before they are stored: a size ceiling, a real image of an allowed type, and the file
        // extension taken from the detected type.
        if (strlen($imagedata) > self::MAX_BANNER_BYTES) {
            debugging('format_dari rejected banner of ' . strlen($imagedata) . ' bytes.', DEBUG_DEVELOPER);
            throw new \moodle_exception('error_bannertoolarge', 'format_dari');
        }

        $imageinfo = getimagesizefromstring($imagedata);
        if ($imageinfo === false || empty($imageinfo['mime'])) {
            throw new \moodle_exception('error_bannerinvalidimage', 'format_dari');
        }

        $allowedmimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedmimes[$imageinfo['mime']])) {
            debugging('format_dari rejected banner mimetype ' . $imageinfo['mime'], DEBUG_DEVELOPER);
            throw new \moodle_exception('error_bannerinvalidimage', 'format_dari');
        }

        $fs = get_file_storage();

        // Where this image belongs: the course area, or this section's slot in the section area.
        [$filearea, $itemid] = banner::target($sectionid);

        // Remove any existing banner image for this target. Scoped by item id, so generating a
        // section banner cannot clear the course banner or another section's.
        $fs->delete_area_files($context->id, 'format_dari', $filearea, $itemid);

        $fileinfo = [
            'component' => 'format_dari',
            'filearea' => $filearea,
            'itemid' => $itemid,
            'contextid' => $context->id,
            'filepath' => '/',
            'filename' => 'ai_banner_' . time() . '.' . $allowedmimes[$imageinfo['mime']],
        ];

        try {
            $file = $fs->create_file_from_string($fileinfo, $imagedata);
        } catch (\Exception $e) {
            debugging('format_dari banner save failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('error_bannersavefailed', 'format_dari');
        }

        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            'format_dari',
            $filearea,
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );

        return $fileurl->out(false);
    }

    /**
     * Return value description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            // Note: the call queues an image rather than producing one. imageurl and
            // creditsused are kept in the structure, defaulted, so a browser still running the
            // previous AMD bundle reads an empty string and a zero instead of failing on a
            // missing key.
            'status' => new external_value(PARAM_ALPHA, 'queued, running, done or failed'),
            'imageurl' => new external_value(
                // Note: declared as a URL rather than an untyped string. The value is a
                // pluginfile URL this plugin builds itself, so the URL type both documents the
                // contract and has Moodle validate it on the way out.
                PARAM_URL,
                'Empty when queued; the URL once done',
                VALUE_DEFAULT,
                ''
            ),
            'message' => new external_value(
                PARAM_TEXT,
                'Failure reason when status is failed',
                VALUE_DEFAULT,
                ''
            ),
            'creditsused' => new external_value(
                PARAM_INT,
                'Retained for compatibility; always 0 here',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }
}
