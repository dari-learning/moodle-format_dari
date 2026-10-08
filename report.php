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
 * Per-course Ask Dari report — what the AI has indexed, and every Q&A exchange in the course.
 *
 * Access: format/dari:viewreport in the course context.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use format_dari\output\report\chatfilter;
use format_dari\output\report\coursereport;

$filter = chatfilter::from_request();

$course = get_course($filter->courseid);
$context = context_course::instance($course->id);

require_login($course);
// Note: moodle/course:viewparticipants is CAP_ALLOW for the student archetype (see
// /lib/db/access.php), so the previous check let any enrolled student read every other student's
// Ask Dari questions, AI answers, ratings and teacher corrections, with per-user filtering.
// format/dari:viewreport is declared in this plugin's db/access.php for teacher,
// editingteacher and manager only, which is the correct gate for this page.
require_capability('format/dari:viewreport', $context);

$PAGE->set_url($filter->get_url());
$PAGE->set_context($context);
$PAGE->set_title(get_string('aireport', 'format_dari'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Note: Removed $PAGE->requires->css() for this plugin's own styles.css. Moodle already
// aggregates a course format's styles.css into the theme stylesheet, so requiring it here made the
// browser download the same file a second time on every report page view.

$report = new coursereport($course, $context, $filter);

echo $OUTPUT->header();
echo $OUTPUT->render($report);
echo $OUTPUT->footer();
