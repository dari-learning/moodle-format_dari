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

namespace format_dari\local;

/**
 * The state of AI image jobs, and the last prompt used, for each card and banner.
 *
 * One row per course, target type and target id. Card targets are section or cm; a banner's target
 * id is its section id, or 0 for the course banner. The rows are written several times while a job
 * runs and read by the browser's status poll, so they live in their own table rather than in
 * plugin config, whose cache every write would purge.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jobstate {
    /** @var string The table. */
    public const TABLE = 'format_dari_jobstate';

    /** @var string Target type of a course or section banner. */
    public const TYPE_BANNER = 'banner';

    /**
     * The stored row for a target, or null.
     *
     * @param int $courseid The course.
     * @param string $type section, cm or banner.
     * @param int $id The target id.
     * @return \stdClass|null
     */
    public static function get(int $courseid, string $type, int $id): ?\stdClass {
        global $DB;
        $row = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'targettype' => $type, 'targetid' => $id]);
        return $row ?: null;
    }

    /**
     * Create or update the row for a target with the given fields.
     *
     * @param int $courseid The course.
     * @param string $type section, cm or banner.
     * @param int $id The target id.
     * @param array $fields Field => value: state, detail, requestid, stage, timequeued, prompt,
     *                      promptsource, prompttime.
     * @return void
     */
    public static function save(int $courseid, string $type, int $id, array $fields): void {
        global $DB;
        $allowed = ['state', 'detail', 'requestid', 'stage', 'timequeued', 'prompt', 'promptsource', 'prompttime'];
        $fields = array_intersect_key($fields, array_flip($allowed));
        $existing = self::get($courseid, $type, $id);
        if ($existing) {
            $record = (object) (['id' => $existing->id, 'timemodified' => time()] + $fields);
            $DB->update_record(self::TABLE, $record);
            return;
        }
        $record = (object) ($fields + [
            'courseid' => $courseid,
            'targettype' => $type,
            'targetid' => $id,
            'state' => 'idle',
            'detail' => '',
            'requestid' => '',
            'stage' => '',
            'timequeued' => 0,
            'prompt' => null,
            'promptsource' => '',
            'prompttime' => 0,
            'timemodified' => time(),
        ]);
        try {
            $DB->insert_record(self::TABLE, $record);
        } catch (\dml_write_exception $e) {
            // Another request created the row a moment ago: update it instead.
            $existing = self::get($courseid, $type, $id);
            if (!$existing) {
                throw $e;
            }
            $DB->update_record(self::TABLE, (object) (['id' => $existing->id, 'timemodified' => time()] + $fields));
        }
    }

    /**
     * Forget a target's job state and stored prompt.
     *
     * @param int $courseid The course.
     * @param string $type section, cm or banner.
     * @param int $id The target id.
     * @return void
     */
    public static function delete(int $courseid, string $type, int $id): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid, 'targettype' => $type, 'targetid' => $id]);
    }

    /**
     * Forget every row of a course.
     *
     * @param int $courseid The course.
     * @return void
     */
    public static function delete_course(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }

    /**
     * Card jobs of a course still queued or running and touched since a time.
     *
     * @param int $courseid The course.
     * @param int $since Earliest last change, as a timestamp.
     * @return array[] Each ['type' => section|cm, 'id' => int].
     */
    public static function running_cards(int $courseid, int $since): array {
        global $DB;
        [$typesql, $typeparams] = $DB->get_in_or_equal([cardimage::TYPE_SECTION, cardimage::TYPE_CM], SQL_PARAMS_NAMED, 'type');
        [$statesql, $stateparams] = $DB->get_in_or_equal(['queued', 'running'], SQL_PARAMS_NAMED, 'state');
        $rows = $DB->get_records_select(
            self::TABLE,
            "courseid = :courseid AND targettype $typesql AND state $statesql AND timemodified >= :since",
            ['courseid' => $courseid, 'since' => $since] + $typeparams + $stateparams,
            'id',
            'id, targettype, targetid'
        );
        return array_values(array_map(fn($row) => ['type' => (string) $row->targettype, 'id' => (int) $row->targetid], $rows));
    }
}
