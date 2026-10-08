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
 * Site-wide admin report — all Ask Dari Q&A across every course.
 *
 * Access:  Site administrators only (moodle/site:config).
 * URL:     /course/format/dari/admin_report.php
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use format_dari\output\report\adminfilter;
use format_dari\output\report\adminreport;
use format_dari\output\report\csvexporter;

$filter = adminfilter::from_request();

require_login();
$syscontext = context_system::instance();
require_capability('moodle/site:config', $syscontext);

// The CSV download must be streamed before any page output is sent.
if ($filter->export) {
    (new csvexporter($filter))->download();
}

$PAGE->set_url($filter->get_url());
$PAGE->set_context($syscontext);
$PAGE->set_title(get_string('admin_report_title', 'format_dari'));
$PAGE->set_heading(get_string('admin_report_title', 'format_dari'));
$PAGE->set_pagelayout('admin');

// Note: Removed $PAGE->requires->css() for this plugin's own styles.css — Moodle already
// Aggregates a course format's styles.css into the theme stylesheet, so this downloaded it twice.

$report = new adminreport($filter);

// Renders the full tutor answers behind each "Show full answer", as the learner saw them.
$PAGE->requires->js_call_amd('format_dari/report', 'renderAnswers');

echo $OUTPUT->header();
echo $OUTPUT->render($report);
echo $OUTPUT->footer();
