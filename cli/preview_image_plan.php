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
 * CLI test mode: print the visual plan and final image prompt for every section card and the course
 * banner of one course, without generating any image.
 *
 * Usage: php course/format/dari/cli/preview_image_plan.php --courseid=12 [--replan] [--json]
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    ['courseid' => 0, 'replan' => false, 'activities' => false, 'json' => false, 'help' => false],
    ['c' => 'courseid', 'r' => 'replan', 'a' => 'activities', 'h' => 'help']
);

if ($options['help'] || empty($options['courseid'])) {
    echo "Print Dari's image plan and prompts for a course, without generating images.\n\n"
        . "Options:\n"
        . "  -c, --courseid=ID  The course (required)\n"
        . "  -r, --replan       Discard the stored plan and plan again\n"
        . "  -a, --activities   Also plan every activity card\n"
        . "      --json         Output JSON\n"
        . "  -h, --help         This help\n";
    exit(empty($options['courseid']) && !$options['help'] ? 1 : 0);
}

$course = get_course((int) $options['courseid']);
$context = context_course::instance($course->id);
$admin = get_admin();
\core\session\manager::set_user($admin);

$rows = \format_dari\local\promptwriter::preview(
    $course,
    $context,
    (int) $admin->id,
    (bool) $options['replan'],
    (bool) $options['activities']
);

if ($options['json']) {
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

foreach ($rows as $row) {
    $e = $row['entry'];
    echo str_repeat('=', 78) . "\n" . $row['title'] . "  [" . $row['key'] . "]\n" . str_repeat('-', 78) . "\n";
    if (str_starts_with((string) $row['key'], 'cm:')) {
        $facts = \format_dari\local\imageplanner::item_facts($course, (string) $row['key']);
        echo 'Activity purpose:  ' . ($facts['purpose'] ?? '') . ' (judged from its ' . ($facts['purposebasis'] ?? '') . ")\n";
    }
    echo 'Interpretation:    ' . ($e['interpretation'] ?? '') . "\n";
    echo 'Visual concept:    ' . ($e['concept'] ?? '') . "\n";
    echo 'Signature element: ' . ($e['signature_element'] ?? '') . "\n";
    echo 'Camera perspective: ' . ($e['perspective'] ?? '') . "\n";
    echo 'Environment:       ' . ($e['environment'] ?? '') . "\n";
    echo 'People:            ' . ($e['people'] ?? '') . "\n";
    echo 'Lighting:          ' . ($e['lighting'] ?? '') . "\n";
    echo 'Categories:        ' . implode(' / ', [$e['env_category'] ?? '', $e['composition'] ?? '',
        $e['people_arrangement'] ?? '', $e['light_category'] ?? '']) . "\n";
    echo 'Final prompt (' . str_word_count((string) $row['prompt']) . " words):\n"
        . wordwrap((string) $row['prompt'], 100) . "\n\n";
}
exit(0);
