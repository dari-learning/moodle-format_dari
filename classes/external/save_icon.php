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
use format_dari\local\icons;

/**
 * Web service storing the decorative icon shown on a section card.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_icon extends external_api {
    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Id of the course the section belongs to'),
            'sectionid' => new external_value(PARAM_INT, 'Id (not number) of the course section'),
            'icon' => new external_value(
                PARAM_ALPHANUMEXT,
                'Icon key from the icon library, or the '
                    . 'empty string to clear the icon',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Store the icon for a section.
     *
     * @param int $courseid Id of the course.
     * @param int $sectionid Id of the section.
     * @param string $icon Icon key, or '' to clear.
     * @return array Status report.
     */
    public static function execute(int $courseid, int $sectionid, string $icon = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'icon' => $icon,
        ]);

        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        // Note: The section id arrives from the browser and was never checked against the
        // course, so a teacher in course A could rewrite the icon of a section in course B.
        if (!$DB->record_exists('course_sections', ['id' => $params['sectionid'], 'course' => $course->id])) {
            throw new \moodle_exception('error_invalidsection', 'format_dari');
        }

        // An empty string is valid and clears the icon; anything else must be in the library.
        if ($params['icon'] !== '') {
            $library = icons::get_library();
            if (!isset($library[$params['icon']])) {
                throw new \moodle_exception('error_invalidicon', 'format_dari');
            }
        }

        icons::set_section_icon($course->id, $params['sectionid'], $params['icon']);

        return ['status' => true];
    }

    /**
     * Return value description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True when the icon was stored'),
        ]);
    }
}
