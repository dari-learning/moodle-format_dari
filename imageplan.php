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
 * Image plan preview and diagnostics.
 *
 * - Plan: the visual plan and final prompt for every section card, optionally every activity card,
 *   and the course banner, without generating any image (text requests only).
 * - Log: every image job's stages with their durations (cron wait, course plan, item plan, prompt,
 *   image request, save), failures and fallbacks, and browser errors, with the current image of
 *   each card at full size and at card-thumbnail size. Downloadable as CSV.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use format_dari\local\ai;
use format_dari\local\cardimage;
use format_dari\local\imagelog;
use format_dari\local\imageplanner;
use format_dari\local\promptwriter;

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$withactivities = optional_param('activities', 0, PARAM_BOOL);

$course = get_course($courseid);
$context = context_course::instance($course->id);
require_login($course);
require_capability('moodle/course:update', $context);

if ($download === 'log') {
    require_sesskey();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dari-image-log-' . $course->id . '-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['time', 'requestid', 'item', 'stage', 'status', 'duration_ms', 'message', 'userid']);
    foreach (array_reverse(imagelog::recent((int) $course->id, 5000)) as $row) {
        fputcsv($out, [date('Y-m-d H:i:s', (int) $row->timecreated), $row->requestid, $row->itemkey, $row->stage,
            $row->status, $row->durationms, (string) $row->message, $row->userid]);
    }
    fclose($out);
    exit;
}

$url = new moodle_url('/course/format/dari/imageplan.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('imageplan', 'format_dari'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$rows = null;
$error = '';
if ($action !== '' && confirm_sesskey()) {
    try {
        ai::require_available(ai::FEATURE_TEXT, $context);
        ai::require_policy((int) $USER->id);
        $start = microtime(true);
        $rows = promptwriter::preview($course, $context, (int) $USER->id, $action === 'replan', (bool) $withactivities);
        imagelog::add(
            'preview',
            'ok',
            (int) round((microtime(true) - $start) * 1000),
            get_string('imagelog_msg_planned', 'format_dari', count($rows)),
            (int) $course->id,
            '',
            ''
        );
    } catch (\moodle_exception $e) {
        $error = $e->getMessage();
    }
}

$labels = [
    'interpretation' => 'imageplan_interpretation',
    'concept' => 'imageplan_concept',
    'signature_element' => 'imageplan_signature',
    'perspective' => 'imageplan_perspective',
    'environment' => 'imageplan_environment',
    'people' => 'imageplan_people',
    'lighting' => 'imageplan_lighting',
];

$stored = false;
if ($rows === null) {
    $rows = [];
    foreach (imageplanner::get_rows((int) $course->id) as $row) {
        if ($row->itemkey === imageplanner::COURSE_KEY) {
            continue;
        }
        $facts = imageplanner::item_facts($course, (string) $row->itemkey);
        $rows[] = [
            'title' => ($facts['title'] ?? '') !== '' ? $facts['title'] : $row->itemkey,
            'key' => $row->itemkey,
            'entry' => (array) json_decode((string) $row->plan, true),
            'prompt' => (string) ($row->prompt ?? ''), 'row' => $row];
    }
    $stored = !empty($rows);
}

$items = [];
foreach ($rows as $item) {
    $entry = $item['entry'];
    $key = (string) $item['key'];
    $lines = [];
    if (str_starts_with($key, 'cm:')) {
        $facts = imageplanner::item_facts($course, $key);
        $lines[] = ['label' => get_string('imageplan_purpose', 'format_dari'),
            'value' => get_string('imageplan_purposevalue', 'format_dari', (object) [
                'purpose' => $facts['purpose'] ?? '',
                'basis' => $facts['purposebasis'] ?? '',
            ])];
    }
    foreach ($labels as $field => $label) {
        $lines[] = ['label' => get_string($label, 'format_dari'), 'value' => (string) ($entry[$field] ?? '')];
    }
    if (($entry['teacher'] ?? '') !== '') {
        $lines[] = ['label' => get_string('imageplan_teacher', 'format_dari'), 'value' => (string) $entry['teacher']];
    }
    $lines[] = ['label' => get_string('imageplan_categories', 'format_dari'),
        'value' => implode(' · ', array_filter([$entry['env_category'] ?? '', $entry['composition'] ?? '',
            $entry['people_arrangement'] ?? '', $entry['light_category'] ?? '']))];
    $lines[] = ['label' => get_string('imageplan_prompt', 'format_dari'), 'value' => (string) $item['prompt'],
        'isprompt' => true];

    // The card's current image, at the size a card shows it and at full width.
    [$type, $id] = array_pad(explode(':', $key, 2), 2, 0);
    if (in_array($type, [cardimage::TYPE_SECTION, cardimage::TYPE_CM], true)) {
        $imageurl = cardimage::get_url((int) $course->id, $type, (int) $id);
        if ($imageurl) {
            $lines[] = ['label' => get_string('imageplan_current', 'format_dari'), 'imageurl' => (string) $imageurl];
        }
    }
    if (isset($item['row'])) {
        $r = $item['row'];
        $lines[] = ['label' => get_string('imageplan_counts', 'format_dari'),
            'value' => get_string('imageplan_counts_value', 'format_dari', (object) ['attempts' => (int) $r->attempts,
                'successes' => (int) $r->successes, 'failures' => (int) $r->failures])];
    }
    $items[] = [
        'caption' => get_string('imageplan_caption', 'format_dari', (object) ['title' => $item['title'], 'key' => $key]),
        'rows' => $lines,
    ];
}

// The diagnostics log.
$logrows = imagelog::recent((int) $course->id, 400);
$jobs = [];
foreach (array_slice(imagelog::jobs($logrows), 0, 60, true) as $job) {
    $stages = [];
    foreach ($job['stages'] as $stage) {
        $text = (string) $stage->stage;
        if ($stage->durationms) {
            $text .= ' ' . get_string('imagelog_seconds', 'format_dari', format_float($stage->durationms / 1000, 1));
        }
        if ($stage->status === 'fail') {
            $text .= ' ' . get_string('imagelog_failedwith', 'format_dari', core_text::substr((string) $stage->message, 0, 200));
        }
        $stages[] = ['text' => $text];
    }
    $jobs[] = [
        'when' => userdate($job['start'], get_string('strftimedatetimeshort', 'core_langconfig')),
        'item' => (string) $job['itemkey'],
        'stages' => $stages,
        'total' => get_string('imagelog_seconds', 'format_dari', $job['end'] - $job['start']),
        'result' => (string) $job['result'],
    ];
}
$browser = [];
foreach (array_slice(array_filter($logrows, fn($r) => $r->stage === 'browser'), 0, 50) as $r) {
    $browser[] = [
        'when' => userdate((int) $r->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
        'message' => (string) $r->message,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('imageplan', 'format_dari'));
echo $OUTPUT->render_from_template('format_dari/image_plan', [
    'intro' => get_string('imageplan_intro', 'format_dari', \format_dari\local\imagemodel::string_params()),
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'withactivities' => (bool) $withactivities,
    'error' => $error,
    'stored' => $stored,
    'items' => $items,
    'downloadurl' => (new moodle_url($url, ['download' => 'log', 'sesskey' => sesskey()]))->out(false),
    'hasjobs' => !empty($jobs),
    'jobs' => $jobs,
    'hasbrowser' => !empty($browser),
    'browser' => $browser,
    'empty' => !$jobs && !$browser,
]);
echo $OUTPUT->footer();
