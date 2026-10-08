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
        imagelog::add('preview', 'ok', (int) round((microtime(true) - $start) * 1000), count($rows) . ' images planned',
            (int) $course->id, '', '');
    } catch (\moodle_exception $e) {
        $error = $e->getMessage();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('imageplan', 'format_dari'));
echo html_writer::tag('p', get_string('imageplan_intro', 'format_dari', \format_dari\local\imagemodel::string_params()));

// Plan buttons.
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::div(html_writer::checkbox('activities', 1, (bool) $withactivities,
    get_string('imageplan_withactivities', 'format_dari')), 'mb-2');
echo html_writer::tag('button', get_string('imageplan_run', 'format_dari'),
    ['type' => 'submit', 'name' => 'action', 'value' => 'plan', 'class' => 'btn btn-primary me-2']);
echo html_writer::tag('button', get_string('imageplan_replan', 'format_dari'),
    ['type' => 'submit', 'name' => 'action', 'value' => 'replan', 'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

if ($error !== '') {
    echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
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

if ($rows === null) {
    $rows = [];
    foreach (imageplanner::get_rows((int) $course->id) as $row) {
        if ($row->itemkey === imageplanner::COURSE_KEY) {
            continue;
        }
        $facts = imageplanner::item_facts($course, (string) $row->itemkey);
        $rows[] = ['title' => ($facts['title'] ?? '') !== '' ? $facts['title'] : $row->itemkey, 'key' => $row->itemkey, 'entry' => (array) json_decode((string) $row->plan, true),
            'prompt' => (string) ($row->prompt ?? ''), 'row' => $row];
    }
    if ($rows) {
        echo html_writer::tag('p', get_string('imageplan_stored', 'format_dari'), ['class' => 'text-muted']);
    }
}

foreach ($rows as $item) {
    $entry = $item['entry'];
    $key = (string) $item['key'];
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-sm';
    $table->caption = s($item['title']) . ' [' . s($key) . ']';
    $table->captionhide = false;
    if (str_starts_with($key, 'cm:')) {
        $facts = imageplanner::item_facts($course, $key);
        $table->data[] = [html_writer::tag('strong', get_string('imageplan_purpose', 'format_dari')),
            s(($facts['purpose'] ?? '') . ' (' . ($facts['purposebasis'] ?? '') . ')')];
    }
    foreach ($labels as $field => $label) {
        $table->data[] = [html_writer::tag('strong', get_string($label, 'format_dari')), s((string) ($entry[$field] ?? ''))];
    }
    if (($entry['teacher'] ?? '') !== '') {
        $table->data[] = [html_writer::tag('strong', get_string('imageplan_teacher', 'format_dari')), s($entry['teacher'])];
    }
    $table->data[] = [html_writer::tag('strong', get_string('imageplan_categories', 'format_dari')),
        s(implode(' · ', array_filter([$entry['env_category'] ?? '', $entry['composition'] ?? '',
            $entry['people_arrangement'] ?? '', $entry['light_category'] ?? ''])))];
    $table->data[] = [html_writer::tag('strong', get_string('imageplan_prompt', 'format_dari')),
        html_writer::tag('div', nl2br(s((string) $item['prompt'])))];

    // The card's current image, at full width and at the size a card shows it.
    [$type, $id] = array_pad(explode(':', $key, 2), 2, 0);
    if (in_array($type, [cardimage::TYPE_SECTION, cardimage::TYPE_CM], true)) {
        $imageurl = cardimage::get_url((int) $course->id, $type, (int) $id);
        if ($imageurl) {
            $table->data[] = [html_writer::tag('strong', get_string('imageplan_current', 'format_dari')),
                html_writer::empty_tag('img', ['src' => (string) $imageurl, 'alt' => '', 'style' => 'width: 320px; height: 180px; '
                    . 'object-fit: cover; border-radius: 8px; margin-right: 12px; vertical-align: top'])
                . html_writer::empty_tag('img', ['src' => (string) $imageurl, 'alt' => '', 'style' => 'max-width: 640px; '
                    . 'width: 100%; border-radius: 8px; vertical-align: top'])];
        }
    }
    if (isset($item['row'])) {
        $r = $item['row'];
        $table->data[] = [html_writer::tag('strong', get_string('imageplan_counts', 'format_dari')),
            s(get_string('imageplan_counts_value', 'format_dari', (object) ['attempts' => (int) $r->attempts,
                'successes' => (int) $r->successes, 'failures' => (int) $r->failures]))];
    }
    echo html_writer::table($table);
}

// The diagnostics log.
echo $OUTPUT->heading(get_string('imagelog', 'format_dari'), 3);
echo html_writer::tag('p', get_string('imagelog_intro', 'format_dari'));
echo html_writer::link(new moodle_url($url, ['download' => 'log', 'sesskey' => sesskey()]),
    get_string('imagelog_download', 'format_dari'), ['class' => 'btn btn-secondary mb-3']);

$logrows = imagelog::recent((int) $course->id, 400);
$jobs = imagelog::jobs($logrows);
if ($jobs) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-sm';
    $table->head = [get_string('imagelog_when', 'format_dari'), get_string('imagelog_item', 'format_dari'),
        get_string('imagelog_stages', 'format_dari'), get_string('imagelog_total', 'format_dari'),
        get_string('imagelog_result', 'format_dari')];
    foreach (array_slice($jobs, 0, 60, true) as $job) {
        $stages = [];
        foreach ($job['stages'] as $stage) {
            $stages[] = s($stage->stage) . ($stage->durationms ? ' ' . format_float($stage->durationms / 1000, 1) . ' s' : '')
                . ($stage->status === 'fail' ? ' ✖ ' . s(core_text::substr((string) $stage->message, 0, 200)) : '');
        }
        $table->data[] = [userdate($job['start'], get_string('strftimedatetimeshort', 'core_langconfig')), s($job['itemkey']),
            implode('<br>', $stages), ($job['end'] - $job['start']) . ' s', s($job['result'])];
    }
    echo html_writer::table($table);
}
$browser = array_filter($logrows, fn($r) => $r->stage === 'browser');
if ($browser) {
    echo $OUTPUT->heading(get_string('imagelog_browser', 'format_dari'), 4);
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-sm';
    foreach (array_slice($browser, 0, 50) as $r) {
        $table->data[] = [userdate((int) $r->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
            html_writer::tag('code', s((string) $r->message))];
    }
    echo html_writer::table($table);
}
if (!$jobs && !$browser) {
    echo html_writer::tag('p', get_string('imagelog_empty', 'format_dari'), ['class' => 'text-muted']);
}

echo $OUTPUT->footer();
