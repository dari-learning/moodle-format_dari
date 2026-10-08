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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/ai_stub.php');

/**
 * Tests for the visual planning stage.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\imageplanner
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\imageplanner::class)]
final class imageplanner_test extends \advanced_testcase {
    use \format_dari\tests\ai_stub;

    /** @var array Course facts used by the request tests. */
    private const FACTS = ['course' => 'CPA Exams for USA', 'category' => 'Accounting', 'summary' => '',
        'audience' => 'adult learners', 'style' => 'photo'];

    /**
     * The course request lists every section card by key, asks for the structured attributes and
     * the banner, allows charts and figures, and holds no fixed industry scenes or scores.
     */
    public function test_course_request(): void {
        $request = imageplanner::course_request(self::FACTS, [
            'section:11' => ['kind' => 'section', 'number' => 1, 'title' => 'AUD – Auditing & Attestation',
                'untitled' => false, 'summary' => '', 'activities' => ['CPA Practice Exam']],
            'section:12' => ['kind' => 'section', 'number' => 2, 'title' => 'Week 2', 'untitled' => true,
                'summary' => 'Leases and revenue recognition.', 'activities' => []],
        ]);
        $this->assertStringStartsWith(imageplanner::COURSE_OPENING, $request);
        $this->assertStringContainsString(
            '- [section:11] SECTION card: "AUD – Auditing & Attestation" | activities: CPA Practice Exam', $request);
        $this->assertStringContainsString('- [section:12] SECTION card: section 2 (no descriptive title', $request);
        $this->assertStringContainsString('description: Leases and revenue recognition.', $request);
        $this->assertStringContainsString('"env_category": "one of: office,', $request);
        $this->assertStringContainsString('"banner":', $request);
        $this->assertStringContainsString('Charts, tables, figures', $request);
        $this->assertStringContainsString('320 x 180', $request);
        $this->assertStringNotContainsString('scores', $request);
        $this->assertStringNotContainsString('candidates', $request);
        $this->assertStringNotContainsString('hard hat on', $request);
    }

    /**
     * An activity's request says what the learner does, names its section's image to avoid, and
     * puts the teacher's own description first.
     */
    public function test_item_request_for_activity(): void {
        $facts = ['kind' => 'activity', 'type' => 'Page', 'title' => 'CPA Practice Exam Instructions',
            'section' => 'AUD – Auditing & Attestation', 'sectionkey' => 'section:11', 'summary' => '',
            'content' => 'Read these rules before you start the timed practice exam.',
            'purpose' => activitypurpose::INSTRUCTIONS, 'purposebasis' => 'title'];
        $section = ['concept' => 'An auditor tracing an invoice to a ledger entry', 'environment' => 'an audit room'];
        $request = imageplanner::item_request(self::FACTS, [], 'cm:5', $facts, ['section:11' => $section],
            'Old concept to drop', 'A checklist on a clipboard next to a stopwatch');
        $this->assertStringStartsWith(imageplanner::ITEM_OPENING, $request);
        $this->assertStringContainsString('ACTIVITY card: Page "CPA Practice Exam Instructions" in section '
            . '"AUD – Auditing & Attestation" | purpose: instructions (judged from its title)', $request);
        $this->assertStringContainsString(activitypurpose::GUIDANCE[activitypurpose::INSTRUCTIONS], $request);
        $this->assertStringContainsString('content: Read these rules', $request);
        $this->assertStringContainsString('Its section card already shows: An auditor tracing an invoice', $request);
        $this->assertStringContainsString('Do not repeat that image.', $request);
        $this->assertStringContainsString('THE TEACHER ASKS FOR (highest priority', $request);
        $this->assertStringContainsString('A checklist on a clipboard next to a stopwatch', $request);
        $this->assertStringContainsString('this one is rejected: Old concept to drop', $request);
        $this->assertStringNotContainsString('OTHER IMAGES IN THIS COURSE', $request, 'The section image is listed once');
    }

    /**
     * Likeness is scored from the structured attributes; the course check flags the later of a
     * repeated pair and an overused environment category.
     */
    public function test_likeness_and_diversity(): void {
        $item = fn(string $env, string $comp, string $people, string $light, string $object, string $concept) => [
            'env_category' => $env, 'composition' => $comp, 'people_arrangement' => $people, 'light_category' => $light,
            'object' => $object, 'subject' => '', 'action' => '', 'concept' => $concept];
        $a = $item('office', 'medium', 'two people', 'daylight', 'audit binder', 'Two auditors review a binder of invoices');
        $b = $item('office', 'medium', 'two people', 'warm interior', 'audit binder', 'Two auditors discuss findings at a desk');
        $c = $item('outdoor', 'wide', 'one person', 'dusk or night', 'inventory tag', 'An auditor counts pallets in a yard');
        $this->assertSame(4, imageplanner::likeness($a, $b));
        $this->assertSame(0, imageplanner::likeness($a, $c));
        $this->assertSame(['a'], imageplanner::similar_to($b, ['a' => $a, 'c' => $c]));

        $issues = imageplanner::diversity_issues(['a' => $a, 'b' => $b, 'c' => $c]);
        $this->assertSame(['b'], array_keys($issues));
        $this->assertStringContainsString('too similar to [a]', $issues['b']);

        // Five of six in an office: the fifth and later are flagged (limit is half, rounded up).
        $offices = [];
        $comps = imageplanner::CATEGORIES['composition'];
        $people = imageplanner::CATEGORIES['people_arrangement'];
        $concepts = ['A clerk stamps invoices', 'Hands sort receipts into trays', 'A partner signs the opinion letter',
            'An analyst compares two spreadsheets', 'A manager reads a variance report', 'Inventory pallets counted outside'];
        foreach (range(0, 5) as $n) {
            $offices['k' . $n] = $item($n === 5 ? 'outdoor' : 'office', $comps[$n], $people[$n % 5], 'daylight',
                'object' . $n, $concepts[$n]);
        }
        $issues = imageplanner::diversity_issues($offices);
        $this->assertSame(['k3', 'k4'], array_keys($issues));
        $this->assertStringContainsString('too many images are set in a office', $issues['k3']);
    }

    /**
     * parse_json() finds the object in chatter and fences; normalise_entry() keeps only known
     * fields and only allowed category values; a concept needs four words.
     */
    public function test_parse_and_normalise(): void {
        $fence = str_repeat(chr(96), 3);
        $data = imageplanner::parse_json("Here you go:\n{$fence}json\n{\"concept\": \"A clerk files leases\", "
            . "\"env_category\": \"Office\", \"composition\": \"fish-eye\", \"scores\": {\"relevance\": 99}}\n{$fence}");
        $entry = imageplanner::normalise_entry($data);
        $this->assertSame('A clerk files leases', $entry['concept']);
        $this->assertSame('office', $entry['env_category']);
        $this->assertSame('', $entry['composition'], 'Not an allowed value');
        $this->assertArrayNotHasKey('scores', $entry);
        $this->assertSame('', $entry['lighting']);
        $this->assertTrue(imageplanner::valid_entry($entry));
        $this->assertFalse(imageplanner::valid_entry(['concept' => 'Too short']));
        $this->assertNull(imageplanner::parse_json('no json here'));
    }

    /**
     * A course plan is made in one request and reused; a changed section is planned again on its
     * own and nothing else is replanned.
     */
    public function test_plan_storage(): void {
        global $DB;
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];

        $plan = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $this->assertSame('A distinct kitchen scene for section:' . $section->id, $plan['entry']['concept']);
        $this->assertCount(4, imageplanner::get_rows((int) $course->id), 'Course row, two sections, banner');
        $this->assertCount(1, $this->aiactions, 'One request plans the whole course');

        $again = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $this->assertCount(1, $this->aiactions, 'Reused');
        $this->assertFalse($again['planned']);

        $other = imageplanner::get_row((int) $course->id, 'section:' . get_fast_modinfo($course)->get_section_info(2)->id);
        $DB->set_field('course_sections', 'summary', 'Knife skills and safe cutting.', ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        $course = get_course($course->id);
        $replanned = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $this->assertTrue($replanned['planned']);
        $this->assertCount(2, $this->aiactions, 'The changed section is planned on its own');
        $this->assertStringStartsWith(imageplanner::ITEM_OPENING,
            (string) $this->aiactions[1]->get_configuration('prompttext'));
        $this->assertSame($other->plan, imageplanner::get_row((int) $course->id, $other->itemkey)->plan,
            'The other section is untouched');
    }

    /**
     * An unusable course plan reply throws and leaves the stored plan exactly as it was.
     */
    public function test_invalid_replan_keeps_stored_plan(): void {
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];
        imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $before = imageplanner::get_rows((int) $course->id);

        $this->queue_text_reply('Sorry, I cannot help with that.');
        try {
            imageplanner::course_plan($course, $brief, $context, (int) $teacher->id, true);
            $this->fail('Expected an exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_imageplan_invalid', $e->errorcode);
        }
        $this->assertEquals($before, imageplanner::get_rows((int) $course->id));
    }

    /**
     * While another worker holds the course's planning lock, planning throws a busy exception
     * without any request and without touching stored data.
     */
    public function test_planning_lock_busy(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->stub_ai();
        // The file lock factory blocks a second lock in the same process, as another worker would see it.
        $CFG->lock_factory = '\core\lock\file_lock_factory';
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];

        $lock = \core\lock\lock_config::get_lock_factory('format_dari_imageplan')->get_lock('course' . $course->id, 0);
        $this->assertNotFalse($lock);
        try {
            imageplanner::course_plan($course, $brief, $context, (int) $teacher->id);
            $this->fail('Expected planning_busy_exception');
        } catch (planning_busy_exception $e) {
            $this->assertSame('error_imageplan_busy', $e->errorcode);
        } finally {
            $lock->release();
        }
        $this->assertCount(0, $this->aiactions);
        $this->assertCount(0, imageplanner::get_rows((int) $course->id));

        imageplanner::course_plan($course, $brief, $context, (int) $teacher->id);
        $this->assertCount(4, imageplanner::get_rows((int) $course->id), 'Plans once the lock is free');
    }

    /**
     * Retry reuses the stored plan with no request; New concept replans just that item, telling
     * the model which concept was rejected; the teacher's description replans just that item.
     */
    public function test_retry_new_and_teacher(): void {
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];
        $first = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $rows = imageplanner::get_rows((int) $course->id);

        imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_RETRY);
        $this->assertCount(1, $this->aiactions, 'Retry makes no planning request');

        $new = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_NEW);
        $this->assertCount(2, $this->aiactions);
        $this->assertStringContainsString('this one is rejected: ' . $first['entry']['concept'], $this->last_prompt());
        $this->assertNotSame($first['entry']['concept'], $new['entry']['concept']);

        $teacherbrief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, 'A sharpening steel on a board')['brief'];
        imageplanner::plan_for($course, $teacherbrief, $context, (int) $teacher->id, imageplanner::MODE_AUTO,
            'A sharpening steel on a board');
        $this->assertCount(3, $this->aiactions);
        $this->assertStringContainsString('THE TEACHER ASKS FOR (highest priority', $this->last_prompt());
        $this->assertStringContainsString('A sharpening steel on a board', $this->last_prompt());
        $row = imageplanner::get_row((int) $course->id, 'section:' . $section->id);
        $this->assertSame(sha1('A sharpening steel on a board'), $row->teacherhash);
        $this->assertSame('A sharpening steel on a board', imageplanner::teacher_text((int) $course->id,
            'section:' . $section->id));

        // A later request without a description keeps it (no new request); Retry keeps it too.
        $kept = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $this->assertFalse($kept['planned']);
        $this->assertSame('A sharpening steel on a board', $kept['teacher']);
        imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_RETRY);
        $this->assertCount(3, $this->aiactions);

        // Only this card was replanned.
        foreach (imageplanner::get_rows((int) $course->id) as $id => $after) {
            if ($after->itemkey !== 'section:' . $section->id) {
                $this->assertSame($rows[$id]->plan, $after->plan, $after->itemkey);
            }
        }

        // The card dialog replaces it, and can clear it.
        $cleared = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_AUTO, '', true);
        $this->assertTrue($cleared['planned']);
        $this->assertSame('', $cleared['teacher']);
        $this->assertSame('', imageplanner::teacher_text((int) $course->id, 'section:' . $section->id));
    }

    /**
     * New concept that comes back with the rejected concept is planned once more, told so.
     */
    public function test_new_concept_must_differ(): void {
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];
        $first = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);

        $this->queue_text_reply(json_encode(['concept' => $first['entry']['concept']] + self::default_single_item()));
        $new = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_NEW);
        $this->assertCount(3, $this->aiactions, 'Course plan, the repeated plan, the replacement');
        $this->assertStringContainsString('Your first plan repeated the rejected concept', $this->last_prompt());
        $this->assertSame('An apprentice chef plating a dessert at the pass', $new['entry']['concept']);
    }

    /**
     * An activity card is planned with its purpose and its section's image to avoid.
     */
    public function test_activity_plan_uses_purpose_and_section(): void {
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1,
            'name' => 'CPA Practice Exam Instructions', 'intro' => '', 'content' => '<p>Rules for the exam.</p>']);
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        $brief = cardprompt::compose($course, cardimage::TYPE_CM, $cm, '')['brief'];

        $plan = imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $this->assertTrue($plan['planned']);
        $this->assertCount(2, $this->aiactions, 'Course plan, then this activity');
        $request = $this->last_prompt();
        $this->assertStringContainsString('purpose: instructions (judged from its title)', $request);
        $this->assertStringContainsString('Its section card already shows: A distinct kitchen scene for section:', $request);
        $this->assertNotNull(imageplanner::get_row((int) $course->id, 'cm:' . $cm->id));
    }

    /**
     * Attempts, successes and failures are counted separately and survive a replan of the item.
     */
    public function test_counters(): void {
        $this->resetAfterTest();
        $this->stub_ai();
        [$course, $context, $teacher] = $this->course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        $brief = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')['brief'];
        imageplanner::plan_for($course, $brief, $context, (int) $teacher->id);
        $key = 'section:' . $section->id;

        imageplanner::record_attempt((int) $course->id, $key);
        imageplanner::record_result((int) $course->id, $key, false);
        imageplanner::record_attempt((int) $course->id, $key);
        imageplanner::record_result((int) $course->id, $key, true);
        imageplanner::plan_for($course, $brief, $context, (int) $teacher->id, imageplanner::MODE_NEW);

        $row = imageplanner::get_row((int) $course->id, $key);
        $this->assertSame(2, (int) $row->attempts);
        $this->assertSame(1, (int) $row->successes);
        $this->assertSame(1, (int) $row->failures);
        $this->assertSame('success', $row->lastresult);
    }

    /**
     * A Dari course with two sections and an editing teacher.
     *
     * @return array [course, context, teacher]
     */
    private function course(): array {
        $course = $this->getDataGenerator()->create_course(['format' => 'dari', 'numsections' => 2,
            'fullname' => 'Commercial Cookery'], ['createsections' => true]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        return [get_course($course->id), \context_course::instance($course->id), $teacher];
    }
}
