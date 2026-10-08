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
use format_dari\local\cardimage;

/**
 * The prompt last sent to the image model for a card or banner, so a teacher can see it.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_image_prompt extends external_api {
    /**
     * Parameter description.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'targettype' => new external_value(PARAM_ALPHA, 'section, cm or banner'),
            'targetid' => new external_value(PARAM_INT, 'course_sections.id, course_modules.id, or section id (0 = course) for a banner'),
        ]);
    }

    /**
     * Return the last prompt.
     *
     * @param int $courseid Course id.
     * @param string $targettype section, cm or banner.
     * @param int $targetid Target id.
     * @return array
     */
    public static function execute(int $courseid, string $targettype, int $targetid): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'targettype' => $targettype,
            'targetid' => $targetid,
        ]);
        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        if (!in_array($params['targettype'], [cardimage::TYPE_SECTION, cardimage::TYPE_CM, cardimage::TYPE_BANNER], true)) {
            throw new \moodle_exception('error_cardimagetype', 'format_dari');
        }
        $stored = cardimage::get_prompt((int) $course->id, $params['targettype'], (int) $params['targetid']);
        $teacher = in_array($params['targettype'], [cardimage::TYPE_SECTION, cardimage::TYPE_CM], true)
            ? \format_dari\local\imageplanner::teacher_text((int) $course->id,
                $params['targettype'] . ':' . (int) $params['targetid'])
            : '';
        return [
            'teacherprompt' => $teacher,
            'prompt' => $stored['prompt'],
            'source' => $stored['source'],
            'time' => $stored['time'],
        ];
    }

    /**
     * Return value description.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'teacherprompt' => new external_value(PARAM_TEXT, 'The teacher\'s own description kept with the card, or empty'),
            'prompt' => new external_value(PARAM_TEXT, 'The prompt, or empty when none has been generated'),
            'source' => new external_value(PARAM_ALPHA, 'artdirector or template'),
            'time' => new external_value(PARAM_INT, 'When it was used'),
        ]);
    }
}
