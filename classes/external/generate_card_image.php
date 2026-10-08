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

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use format_dari\local\cardimage;

/**
 * Web service queueing an AI image for a section card or an activity card.
 *
 * Everything that protects the banner generator protects this one, because it uses the same
 * AI provider: moodle/course:update, no guests, the site's AI provider and the AI policy checked
 * before queueing, the work done in an adhoc task so no proxy timeout can cut it off, and the
 * result validated before it is stored.
 *
 * It extends generate_banner_image only to share its status helpers; its parameters, target and
 * storage are its own.
 *
 * The throttle bucket is the BANNER's, deliberately. Its limit exists because every call costs
 * credits; a second bucket for cards would double the ceiling, and a bucket per card would remove
 * it. Three generations a minute still lets a teacher work steadily down a course.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_card_image extends generate_banner_image {
    /** @var int Kept for compatibility: images now use the school's own provider, so there is no credit cost. */
    public const CREDIT_COST = 0;

    /** @var int Longest teacher prompt accepted, in characters. */
    public const PROMPT_MAX = 400;

    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'targettype' => new external_value(PARAM_ALPHA, 'section or cm'),
            'targetid' => new external_value(PARAM_INT, 'course_sections.id or course_modules.id'),
            'prompt' => new external_value(
                PARAM_TEXT,
                'The teacher\'s own description of the image; optional',
                VALUE_DEFAULT,
                ''
            ),
            'mode' => new external_value(
                PARAM_ALPHA,
                'auto, retry (use the stored prompt again) or new (plan a different concept)',
                VALUE_DEFAULT,
                'auto'
            ),
            'replaceprompt' => new external_value(
                PARAM_BOOL,
                'True when prompt replaces the card\'s stored description, even when empty; false keeps the stored one '
                    . 'when prompt is empty',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Queue the generation.
     *
     * The signature differs from the parent's on purpose; external functions are called through
     * db/services.php by name, never polymorphically.
     *
     * @param int $courseid Course id.
     * @param string $targettype section or cm.
     * @param int $targetid course_sections.id or course_modules.id.
     * @param string $prompt Teacher's description.
     * @param string $mode auto, retry or new.
     * @param bool $replaceprompt Whether $prompt replaces the card's stored description even when empty.
     * @return array
     */
    public static function execute(
        $courseid,
        $targettype = '',
        $targetid = 0,
        $prompt = '',
        $mode = 'auto',
        $replaceprompt = false
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'targettype' => $targettype,
            'targetid' => $targetid,
            'prompt' => $prompt,
            'mode' => $mode,
            'replaceprompt' => $replaceprompt,
        ]);
        $mode = in_array($params['mode'], ['retry', 'new'], true) ? $params['mode'] : 'auto';

        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        $target = cardimage::require_target($course, $params['targettype'], (int) $params['targetid']);

        if (isguestuser()) {
            throw new \moodle_exception('error_guestnotallowed', 'format_dari');
        }

        throttle::check('bannerimage', (int) $course->id, (int) $USER->id, throttle::BANNER_MAX, throttle::BANNER_WINDOW);

        \format_dari\local\ai::require_available(\format_dari\local\ai::FEATURE_IMAGE, $context);
        \format_dari\local\ai::require_policy((int) $USER->id);

        // The authoritative cap: the textarea's maxlength is a convenience and is not trusted.
        $prompttext = \core_text::substr(trim($params['prompt']), 0, self::PROMPT_MAX);

        self::queue(
            (int) $course->id,
            $params['targettype'],
            (int) $target->id,
            $prompttext,
            $mode,
            (int) $USER->id,
            (bool) $params['replaceprompt']
        );

        return ['status' => 'queued', 'imageurl' => '', 'message' => ''];
    }

    /**
     * Build the request sent to the image service for one card.
     *
     * The service already knows how to make a course banner from courseName and extraDetail.
     * The card fields are additions it can use when it has been updated for them:
     *
     *  - imageKind 'card' and aspectRatio '16:9' ask for a card-shaped picture. A service that
     *    ignores them returns its usual wide banner, which the card crops to the centre.
     *  - imageStyle is the course's chosen art direction: photo, illustration, render3d or flat.
     *  - sectionName / activityName / activityType say what the card is about.
     *  - extraDetail carries the teacher's own words, exactly as the banner dialogue does, so a
     *    service that knows nothing of cards still receives the teacher's direction.
     *  - prompt, negativePrompt and promptVersion (3.0.0) are the complete prompt the plugin wrote,
     *    see {@see \format_dari\local\cardprompt}. An updated service uses it verbatim; an
     *    older one ignores the fields and composes from the ones above, as before.
     *  - brief and promptTail (3.1.0) let a service with a scene writer rewrite the scene part of
     *    the prompt from the facts in brief, then append promptTail (style, colour, composition and
     *    the no-text rule) unchanged.
     *
     * Everything is plain text. Names go through format_string() with filters applied and are
     * then flattened, so multilang markup reaches the service as the text a reader would see.
     *
     * @param \stdClass $course The course.
     * @param string $type section or cm.
     * @param \section_info|\cm_info $target The card's section or activity.
     * @param string $prompt Teacher's description.
     * @return array
     */
    public static function build_payload(\stdClass $course, string $type, $target, string $prompt): array {
        $context = \context_course::instance($course->id);
        $payload = [
            'courseName' => \format_dari\local\text::plain($course->fullname, $context),
            'courseShortname' => $course->shortname,
            'courseId' => (int) $course->id,
            'imageKind' => 'card',
            'aspectRatio' => '16:9',
            // The course's art direction, so every card in it is generated as one set.
            'imageStyle' => cardimage::clean_style(course_get_format($course)->get_format_options()['cardimagestyle'] ?? ''),
        ];

        if ($type === cardimage::TYPE_SECTION) {
            $payload['sectionId'] = (int) $target->id;
            $payload['sectionNumber'] = (int) $target->section;
            $name = trim((string) $target->name);
            if ($name !== '') {
                $payload['sectionName'] = \format_dari\local\text::plain($name, $context);
            }
            // Html_plain(), not html_to_text(), which writes bold text in UPPERCASE.
            $summary = \core_text::substr(\format_dari\local\cardprompt::html_plain((string) $target->summary), 0, 600);
            if ($summary !== '') {
                $payload['sectionSummary'] = $summary;
            }
        } else {
            $payload['cmId'] = (int) $target->id;
            $payload['activityName'] = \format_dari\local\text::plain((string) $target->name, $context);
            $payload['activityType'] = (string) $target->modname;
            $section = $target->get_section_info();
            if ($section && trim((string) $section->name) !== '') {
                $payload['sectionName'] = \format_dari\local\text::plain((string) $section->name, $context);
            }
        }

        $prompt = trim($prompt);
        if ($prompt !== '') {
            $payload['extraDetail'] = \core_text::substr($prompt, 0, self::PROMPT_MAX);
        }

        // The complete prompt, written by the plugin, for the service to use verbatim. The
        // fields above stay for services not yet updated, for their logs and for moderation.
        $payload += \format_dari\local\cardprompt::compose($course, $type, $target, $prompt);

        return $payload;
    }

    /**
     * Queue one card's generation as an adhoc task with a fresh request id.
     *
     * The request id makes this the card's current job: an older job still running for the same
     * card can no longer change its status or replace its image.
     *
     * @param int $courseid The course.
     * @param string $type section or cm.
     * @param int $id Target id.
     * @param string $prompt Teacher's description.
     * @param string $mode auto, retry or new.
     * @param int $userid The teacher.
     * @param bool $replace Whether $prompt replaces the card's stored description even when empty.
     * @return string The request id.
     */
    public static function queue(
        int $courseid,
        string $type,
        int $id,
        string $prompt,
        string $mode,
        int $userid,
        bool $replace = false
    ): string {
        $requestid = \core\uuid::generate();
        $task = new \format_dari\task\generate_card_image();
        $task->set_custom_data([
            'courseid' => $courseid,
            'targettype' => $type,
            'targetid' => $id,
            'prompt' => $prompt,
            'mode' => $mode,
            'replaceprompt' => $replace,
            'requestid' => $requestid,
            'userid' => $userid,
            'queued' => time(),
        ]);
        $task->set_component('format_dari');
        $task->set_userid($userid);
        cardimage::set_status($courseid, $type, $id, 'queued', '', ['requestid' => $requestid, 'stage' => 'waiting',
            'queued' => time()]);
        \core\task\manager::queue_adhoc_task($task);
        \format_dari\local\imagelog::add(
            'queued',
            'info',
            0,
            'mode ' . $mode . ($prompt !== '' ? '; teacher description given' : ''),
            $courseid,
            $type . ':' . $id,
            $requestid
        );
        return $requestid;
    }

    /**
     * Generate one card image with the site's AI provider and store it.
     *
     * Each stage is written to the card's status (for the browser) and timed in the image log.
     * The image is stored only if this job is still the card's current one.
     *
     * @param \stdClass $course The course.
     * @param string $type section or cm.
     * @param int $id course_sections.id or course_modules.id.
     * @param string $prompt Teacher's description.
     * @param string $requestid The job's request id.
     * @param int $userid The teacher the request is made for; 0 for the current user.
     * @param string $mode auto, retry or new.
     * @param bool $replace Whether $prompt replaces the card's stored description even when empty.
     * @return string URL of the stored image, or '' when a newer request replaced this job.
     * @throws \format_dari\local\planning_busy_exception When another worker is planning the course.
     */
    public static function generate_card(
        \stdClass $course,
        string $type,
        int $id,
        string $prompt,
        string $requestid = '',
        int $userid = 0,
        string $mode = 'auto',
        bool $replace = false
    ): string {
        global $USER;

        // Re-checked here: the section or activity may have been deleted since queueing.
        $target = cardimage::require_target($course, $type, $id);
        $context = \context_course::instance($course->id);
        $userid = $userid > 0 ? $userid : (int) $USER->id;
        $key = $type . ':' . (int) $target->id;

        cardimage::set_stage((int) $course->id, $type, (int) $target->id, $requestid, 'planning');
        $composed = \format_dari\local\cardprompt::compose($course, $type, $target, trim($prompt));
        $composed['brief']['mode'] = $mode;
        $composed['brief']['replaceTeacher'] = $replace;
        $imageprompt = \format_dari\local\ai::image_prompt($composed, $context, $userid);

        if (!cardimage::set_stage((int) $course->id, $type, (int) $target->id, $requestid, 'generating')) {
            \format_dari\local\imagelog::add(
                'superseded',
                'info',
                0,
                'A newer request replaced this job before the image request.'
            );
            return '';
        }
        try {
            $bytes = \format_dari\local\ai::generate_image($context, $userid, $imageprompt, 'landscape');
        } catch (\Throwable $e) {
            \format_dari\local\imageplanner::record_result((int) $course->id, $key, false);
            throw $e;
        }

        if (!cardimage::set_stage((int) $course->id, $type, (int) $target->id, $requestid, 'saving')) {
            \format_dari\local\imagelog::add(
                'superseded',
                'info',
                0,
                'A newer request replaced this job; its image was not stored.'
            );
            return '';
        }
        $url = \format_dari\local\imagelog::time('save', fn() =>
            cardimage::store((int) $course->id, $type, (int) $target->id, $bytes, 'ai'));
        \format_dari\local\imageplanner::record_result((int) $course->id, $key, true);
        return $url;
    }
}
