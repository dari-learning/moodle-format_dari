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
 * Helpers for the Dari course format's upgrade steps.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Remove every format_dari setting whose name starts with one of the given prefixes.
 *
 * Uses the configuration API only: get_config() lists the plugin's settings and unset_config()
 * removes each one, which also keeps the configuration cache correct.
 *
 * @param string[] $prefixes Name prefixes, for example ['artscenes_'].
 * @return string[] The names removed.
 */
function format_dari_unset_config_prefixed(array $prefixes): array {
    $removed = [];
    foreach (array_keys((array) get_config('format_dari')) as $name) {
        foreach ($prefixes as $prefix) {
            if (strpos((string) $name, $prefix) === 0) {
                unset_config((string) $name, 'format_dari');
                $removed[] = (string) $name;
                break;
            }
        }
    }
    return $removed;
}

/**
 * Move the card and banner prompts that 2.1.1 and earlier kept in plugin settings into the job
 * state table, then remove those settings and the old per-card and per-banner job states.
 *
 * Old job states are not moved: a job still running during the upgrade finishes on its own and
 * writes its result to the new table.
 *
 * @return void
 */
function format_dari_move_job_state_out_of_config(): void {
    global $DB;
    foreach ((array) get_config('format_dari') as $name => $value) {
        if (!preg_match('/^imgprompt_(\d+)_([scb])(\d+)$/', (string) $name, $m)) {
            continue;
        }
        $data = json_decode((string) $value, true);
        $courseid = (int) $m[1];
        if (is_array($data) && ($data['prompt'] ?? '') !== '' && $DB->record_exists('course', ['id' => $courseid])) {
            $type = ['s' => 'section', 'c' => 'cm', 'b' => 'banner'][$m[2]];
            $conditions = ['courseid' => $courseid, 'targettype' => $type, 'targetid' => (int) $m[3]];
            if (!$DB->record_exists('format_dari_jobstate', $conditions)) {
                $DB->insert_record('format_dari_jobstate', (object) ($conditions + [
                    'state' => 'idle',
                    'detail' => '',
                    'requestid' => '',
                    'stage' => '',
                    'timequeued' => 0,
                    'prompt' => (string) $data['prompt'],
                    'promptsource' => \core_text::substr((string) ($data['source'] ?? ''), 0, 20),
                    'prompttime' => (int) ($data['time'] ?? 0),
                    'timemodified' => time(),
                ]));
            }
        }
    }
    format_dari_unset_config_prefixed(['imgprompt_', 'cardstatus_', 'bannerstatus_']);
}
