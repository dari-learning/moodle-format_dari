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

/**
 * Adhoc task that generates a card image in the background.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\task;

use format_dari\external\generate_card_image as generator;
use format_dari\local\cardimage;

/**
 * Generate one section or activity card image away from the web request.
 *
 * Same reasoning as {@see generate_banner}: the service takes around two minutes, longer than
 * the shortest proxy timeout in front of many sites. Failures are recorded, not rethrown, so
 * Moodle never retries on its own and spends credits the teacher is no longer watching.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_card_image extends \core\task\adhoc_task {
    /**
     * Descriptive name for the admin task screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskgeneratecardimage', 'format_dari');
    }

    /**
     * Generate the image and record the outcome.
     *
     * @return void
     */
    public function execute(): void {
        global $CFG;
        // Cron does not load course/lib.php, and the prompt composer reads the course format options.
        require_once($CFG->dirroot . '/course/lib.php');
        $data = $this->get_custom_data();
        $courseid = (int) ($data->courseid ?? 0);
        $type = (string) ($data->targettype ?? '');
        $id = (int) ($data->targetid ?? 0);
        if ($courseid <= 0 || $id <= 0 || !in_array($type, [cardimage::TYPE_SECTION, cardimage::TYPE_CM], true)) {
            return;
        }

        try {
            $course = get_course($courseid);
        } catch (\moodle_exception $e) {
            return;
        }

        $requestid = (string) ($data->requestid ?? '');
        $key = $type . ':' . $id;
        // A newer request for this card replaced this job: it must not touch the card.
        if (!cardimage::is_current($courseid, $type, $id, $requestid)) {
            \format_dari\local\imagelog::add(
                'superseded',
                'info',
                0,
                'A newer request replaced this job before it started.',
                $courseid,
                $key,
                $requestid
            );
            return;
        }

        // One cron process runs many jobs; a colour a teacher changed since the last one must count.
        cardimage::reset_cache();
        \format_dari\local\imagelog::begin($courseid, $key, $requestid);
        $queued = (int) ($data->queued ?? 0);
        \format_dari\local\imagelog::add(
            'started',
            'ok',
            $queued > 0 ? max(0, time() - $queued) * 1000 : 0,
            'Time waiting for Moodle\'s task runner (cron) before starting.'
        );
        $start = microtime(true);
        cardimage::set_stage($courseid, $type, $id, $requestid, 'planning');
        try {
            $url = generator::generate_card(
                $course,
                $type,
                $id,
                (string) ($data->prompt ?? ''),
                $requestid,
                (int) ($data->userid ?? 0),
                (string) ($data->mode ?? 'auto'),
                !empty($data->replaceprompt)
            );
            if ($url !== '' && cardimage::is_current($courseid, $type, $id, $requestid)) {
                cardimage::set_status($courseid, $type, $id, 'done', $url, ['requestid' => $requestid, 'stage' => 'done']);
                cardimage::set_prompt(
                    $courseid,
                    $type,
                    $id,
                    \format_dari\local\ai::$lastprompt['prompt'],
                    \format_dari\local\ai::$lastprompt['source']
                );
            }
            \format_dari\local\imagelog::add(
                'done',
                'ok',
                (int) round((microtime(true) - $start) * 1000),
                'Prompt source: ' . \format_dari\local\ai::$lastprompt['source']
                . (\format_dari\local\ai::$lastprompt['reason'] !== ''
                    ? ' (' . \format_dari\local\ai::$lastprompt['reason'] . ')' : '')
            );
        } catch (\format_dari\local\planning_busy_exception $e) {
            // Another worker is planning this course. Nothing was requested yet, so try again shortly
            // as the same job (same request id); nothing is charged twice.
            $retry = new self();
            $retry->set_custom_data($data);
            $retry->set_component('format_dari');
            $retry->set_userid((int) ($data->userid ?? 0));
            $retry->set_next_run_time(time() + 30);
            \core\task\manager::queue_adhoc_task($retry);
            cardimage::set_stage($courseid, $type, $id, $requestid, 'waiting');
            \format_dari\local\imagelog::add(
                'requeued',
                'info',
                0,
                'The course plan is being written by another worker; retry in 30 s.'
            );
        } catch (\Throwable $e) {
            if (cardimage::is_current($courseid, $type, $id, $requestid)) {
                cardimage::set_status($courseid, $type, $id, 'failed', $e->getMessage(), ['requestid' => $requestid,
                    'stage' => 'failed']);
            }
            \format_dari\local\imagelog::add(
                'failed',
                'fail',
                (int) round((microtime(true) - $start) * 1000),
                $e->getMessage()
            );
        } finally {
            \format_dari\local\imagelog::end();
        }
    }
}
