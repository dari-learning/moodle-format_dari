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
use format_dari\local\imagelog;

/**
 * Record a browser error from a Dari course page in the diagnostics log.
 *
 * Only editing teachers' browsers report (format_dari/diagnostics is loaded for them only), and at
 * most 30 events a minute per user are kept.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_client_event extends external_api {
    /** @var int Events kept per user per minute. */
    public const PER_MINUTE = 30;

    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'error, rejection, console, ajax or timeout'),
            // Raw: stack traces hold text such as <anonymous>, which PARAM_TEXT would refuse. Stored as
            // data only and escaped wherever it is shown.
            'message' => new external_value(PARAM_RAW, 'What happened'),
            'source' => new external_value(PARAM_RAW, 'Script, line and column, or web service name', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_RAW, 'Page path', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Record the event.
     *
     * @param int $courseid Course id.
     * @param string $type Event type.
     * @param string $message Message.
     * @param string $source Source.
     * @param string $page Page.
     * @return array
     */
    public static function execute(int $courseid, string $type, string $message, string $source = '',
            string $page = ''): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid, 'type' => $type, 'message' => $message, 'source' => $source, 'page' => $page,
        ]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        $recent = $DB->count_records_select(imagelog::TABLE, 'userid = ? AND stage = ? AND timecreated > ?',
            [(int) $USER->id, 'browser', time() - 60]);
        if ($recent >= self::PER_MINUTE) {
            return ['logged' => false];
        }
        $detail = json_encode([
            'type' => \core_text::substr($params['type'], 0, 20),
            'message' => \core_text::substr($params['message'], 0, 1500),
            'source' => \core_text::substr($params['source'], 0, 300),
            'page' => \core_text::substr($params['page'], 0, 300),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        imagelog::add('browser', 'fail', 0, (string) $detail, (int) $params['courseid'], '', '');
        return ['logged' => true];
    }

    /**
     * Return value description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'logged' => new external_value(PARAM_BOOL, 'Whether the event was kept'),
        ]);
    }
}
