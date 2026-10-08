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
 * Tests for Dari's image prompt writer (stage 2) working from the visual plan (stage 1).
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\local;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/ai_stub.php');

/**
 * Tests for \format_dari\local\promptwriter.
 *
 * The site's AI manager is a mock (see \format_dari\tests\ai_stub): by default it answers the
 * planning request with one distinct item per section and the prompt request with a paragraph.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\promptwriter
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\promptwriter::class)]
final class promptwriter_test extends \advanced_testcase {
    use \format_dari\tests\ai_stub;

    /** @var \stdClass A course in the Dari format. */
    private $course;

    /** @var \context_course Its context. */
    private $context;

    /** @var int A teacher's user id. */
    private $userid;

    /**
     * Fixture: a course, a teacher and a stubbed AI manager with text and images.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course([
            'format' => 'dari',
            'fullname' => 'Kitchen operations',
            'summary' => 'Work safely in a commercial kitchen.',
            'numsections' => 2,
        ], ['createsections' => true]);
        $this->context = \context_course::instance($this->course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->userid = (int) $teacher->id;
        $this->setUser($teacher);
        $this->stub_ai();
    }

    /**
     * A composed recipe for one of the fixture course's sections, as the card generator builds it.
     *
     * @param int $sectionnum Section number.
     * @return array
     */
    private function composed(int $sectionnum = 1): array {
        $course = get_course($this->course->id);
        $section = get_fast_modinfo($course)->get_section_info($sectionnum);
        return cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '');
    }

    /**
     * The text prompts sent so far that start with the given words.
     *
     * @param string $start Opening words.
     * @return string[]
     */
    private function prompts_starting(string $start): array {
        $prompts = [];
        foreach ($this->actions_of(\core_ai\aiactions\generate_text::class) as $action) {
            $prompt = (string) $action->get_configuration('prompttext');
            if (strpos($prompt, $start) === 0) {
                $prompts[] = $prompt;
            }
        }
        return $prompts;
    }

    /**
     * Stage 2 runs only with a text provider and a brief; otherwise the template is used, unasked.
     */
    public function test_write_returns_the_template_without_a_text_model(): void {
        $composed = $this->composed();
        $this->stub_ai(false, true);
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));

        $this->stub_ai();
        unset($composed['brief']);
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertCount(0, $this->aiactions);
    }

    /**
     * The first image: one course plan request, then one request that writes the prompt from the
     * plan; the prompt is stored and an attempt is counted.
     */
    public function test_first_image_plans_the_course_then_writes_the_prompt(): void {
        $composed = $this->composed(1);
        $prompt = promptwriter::write($composed, $this->context, $this->userid);

        $key = 'section:' . get_fast_modinfo($this->course->id)->get_section_info(1)->id;
        $this->assertStringStartsWith('An apprentice chef in crisp whites', $prompt);
        $this->assertStringEndsWith("\n\n" . $composed['promptTail'], $prompt);
        $this->assertCount(1, $this->prompts_starting(imageplanner::COURSE_OPENING));
        $requests = $this->prompts_starting(promptwriter::SCENE_OPENING);
        $this->assertCount(1, $requests);
        $this->assertStringContainsString('- Concept: A distinct kitchen scene for ' . $key, $requests[0]);
        $this->assertStringContainsString('- Colour treatment: Steel and fresh greens', $requests[0]);
        $this->assertStringContainsString('60 to 130 words', $requests[0]);
        $this->assertSame('written', promptwriter::$last['source']);

        $row = imageplanner::get_row((int) $this->course->id, $key);
        $this->assertSame(1, (int) $row->attempts);
        $this->assertSame(0, (int) $row->successes);
        $this->assertStringStartsWith('An apprentice chef in crisp whites', (string) $row->prompt);
    }

    /**
     * When the prompt cannot be written, the card's own plan is still used, built into a prompt,
     * and the fallback is recorded.
     */
    public function test_prompt_writing_failure_uses_the_plan(): void {
        $composed = $this->composed(1);
        imageplanner::plan_for(get_course($this->course->id), $composed['brief'], $this->context, $this->userid);
        $this->queue_text_reply('Too short.');
        $this->queue_text_reply('Still too short.');
        $prompt = promptwriter::write($composed, $this->context, $this->userid);
        $this->assertStringStartsWith('A distinct kitchen scene for section:', $prompt);
        $this->assertSame('plan', promptwriter::$last['source']);
        $this->assertStringContainsString('prompt writing failed', promptwriter::$last['reason']);
    }

    /**
     * A second card in the same course reuses the plan: only its own prompt is written.
     */
    public function test_second_card_reuses_the_course_plan(): void {
        promptwriter::write($this->composed(1), $this->context, $this->userid);
        promptwriter::write($this->composed(2), $this->context, $this->userid);
        $this->assertCount(1, $this->prompts_starting(imageplanner::COURSE_OPENING));
        $this->assertCount(2, $this->prompts_starting(promptwriter::SCENE_OPENING));
    }

    /**
     * The preview plans and writes prompts without counting attempts; the first real image then
     * uses the previewed prompt without another request.
     */
    public function test_preview_prompt_is_the_painted_prompt(): void {
        $rows = promptwriter::preview(get_course($this->course->id), $this->context, $this->userid);
        $this->assertCount(3, $rows, 'Two sections and the course banner');
        $this->assertSame('banner', $rows[2]['key']);
        foreach ($rows as $row) {
            $this->assertNotSame('', $row['entry']['concept']);
            $this->assertStringStartsWith('An apprentice chef in crisp whites', $row['prompt']);
        }
        $key = $rows[0]['key'];
        $this->assertSame(0, (int) imageplanner::get_row((int) $this->course->id, $key)->attempts);
        $before = count($this->aiactions);

        $prompt = promptwriter::write($this->composed(1), $this->context, $this->userid);
        $this->assertCount($before, $this->aiactions, 'No new request: the previewed prompt is used');
        $this->assertSame($rows[0]['prompt'], $prompt);
    }

    /**
     * The preview can plan every activity card too, each with its purpose.
     */
    public function test_preview_with_activities(): void {
        $this->getDataGenerator()->create_module('quiz', ['course' => $this->course->id, 'section' => 1,
            'name' => 'Knowledge check']);
        $rows = promptwriter::preview(get_course($this->course->id), $this->context, $this->userid, false, true);
        $this->assertCount(4, $rows, 'Two sections, one activity and the course banner');
        $this->assertStringStartsWith('cm:', $rows[1]['key']);
        $this->assertStringContainsString('purpose: practice (judged from its title)',
            $this->prompts_starting(imageplanner::ITEM_OPENING)[0]);
    }

    /**
     * Retry reuses the stored prompt with no request; a failed image does not change the concept;
     * New concept replans only that item, with the earlier concept rejected.
     */
    public function test_retry_and_new_concept(): void {
        $first = promptwriter::write($this->composed(1), $this->context, $this->userid);
        $key = 'section:' . get_fast_modinfo($this->course->id)->get_section_info(1)->id;
        imageplanner::record_result((int) $this->course->id, $key, false);

        $retry = $this->composed(1);
        $retry['brief']['mode'] = imageplanner::MODE_RETRY;
        $this->assertSame($first, promptwriter::write($retry, $this->context, $this->userid));
        $this->assertSame($first, promptwriter::write($this->composed(1), $this->context, $this->userid),
            'After a failure the next automatic attempt keeps the same concept');
        $this->assertCount(2, $this->aiactions, 'Course plan and one prompt; retries made no request');

        $new = $this->composed(1);
        $new['brief']['mode'] = imageplanner::MODE_NEW;
        $second = promptwriter::write($new, $this->context, $this->userid);
        $items = $this->prompts_starting(imageplanner::ITEM_OPENING);
        $this->assertCount(1, $items);
        $this->assertStringContainsString('this one is rejected: A distinct kitchen scene for ' . $key, $items[0]);
        $this->assertStringContainsString('OTHER IMAGES IN THIS COURSE', $items[0]);
        $this->assertSame($first, $second, 'The stub writes the same paragraph; the plan is what changed');
        $row = imageplanner::get_row((int) $this->course->id, $key);
        $this->assertSame(4, (int) $row->attempts);
        $this->assertSame(1, (int) $row->failures);
    }

    /**
     * A teacher's description replans that card with the description as the top priority and
     * appears in the prompt; the regenerated card keeps it.
     */
    public function test_teacher_direction(): void {
        $course = get_course($this->course->id);
        $section = get_fast_modinfo($course)->get_section_info(1);
        promptwriter::write($this->composed(1), $this->context, $this->userid);
        $composed = cardprompt::compose($course, cardimage::TYPE_SECTION, $section, 'golden hour, no people');
        $prompt = promptwriter::write($composed, $this->context, $this->userid);
        $items = $this->prompts_starting(imageplanner::ITEM_OPENING);
        $this->assertCount(1, $items);
        $this->assertStringContainsString('THE TEACHER ASKS FOR (highest priority', $items[0]);
        $this->assertStringContainsString('golden hour, no people', $items[0]);
        $this->assertStringContainsString('- The teacher asks for (must be honoured): golden hour, no people',
            $this->last_prompt());
        $this->assertNotEmpty($prompt);

        // The same description again: no new request.
        promptwriter::write($composed, $this->context, $this->userid);
        $this->assertCount(1, $this->prompts_starting(imageplanner::ITEM_OPENING));

        // Regenerating without a description (Generate all, New concept) keeps it in the plan and prompt.
        $new = $this->composed(1);
        $new['brief']['mode'] = imageplanner::MODE_NEW;
        $regenerated = promptwriter::write($new, $this->context, $this->userid);
        // (The stub plans the same concept again, so the planner asks once more for a different one.)
        $items = $this->prompts_starting(imageplanner::ITEM_OPENING);
        $this->assertCount(3, $items);
        $this->assertStringContainsString('golden hour, no people', $items[1]);
        $this->assertStringContainsString('Your first plan repeated the rejected concept', $items[2]);
        $this->assertStringContainsString('- The teacher asks for (must be honoured): golden hour, no people',
            $this->last_prompt());
        $this->assertNotEmpty($regenerated);
    }

    /**
     * parse_prompt(): labels, quotes and the old house opening are removed; short replies are refused.
     */
    public function test_parse_prompt(): void {
        $this->assertSame(self::$defaultscene, promptwriter::parse_prompt(self::$defaultscene));
        $this->assertSame(self::$defaultscene, promptwriter::parse_prompt("**PROMPT:**\n\"" . self::$defaultscene . '"'));
        $this->assertSame(self::$defaultscene, promptwriter::parse_prompt('Create an image of '
            . lcfirst(self::$defaultscene)));
        $this->assertSame('', promptwriter::parse_prompt('A chef cooking.'));
    }

    /**
     * However long the paragraph, the tail survives whole and the prompt stays within the limit.
     */
    public function test_assemble_keeps_the_tail_within_the_limit(): void {
        $composed = $this->composed();
        $prompt = promptwriter::assemble(str_repeat('word ', 1000), $composed['promptTail']);
        $this->assertLessThanOrEqual(3600, \core_text::strlen($prompt));
        $this->assertStringEndsWith("\n\n" . $composed['promptTail'], $prompt);
    }

    /**
     * A planning failure falls back to the template; the plan is not stored.
     */
    public function test_planning_failure_uses_the_template(): void {
        $composed = $this->composed();
        $this->queue_text_reply('I cannot help with that.');
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertDebuggingCalled();
        $this->assertSame([], imageplanner::get_rows((int) $this->course->id));
    }

    /**
     * forget() clears the plan; deleting the course does too.
     */
    public function test_forget(): void {
        $courseid = (int) $this->course->id;
        promptwriter::write($this->composed(), $this->context, $this->userid);
        $this->assertNotEmpty(imageplanner::get_rows($courseid));
        promptwriter::forget($courseid);
        $this->assertEmpty(imageplanner::get_rows($courseid));

        promptwriter::write($this->composed(), $this->context, $this->userid);
        delete_course($courseid, false);
        $this->assertEmpty(imageplanner::get_rows($courseid));
    }

    /**
     * The 2026100901 to 2026100904 upgrade steps remove the image model, quality, style and art-director settings,
     * keep a 4.4 site's "no AI images" choice, forget old scenes, set the Google image engine and
     * create the image plan table.
     */
    public function test_upgrade_step_single_image_model(): void {
        global $CFG;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/course/format/dari/db/upgrade.php');

        set_config('directimagemodel', '', 'format_dari');
        set_config('imagequality', 'standard', 'format_dari');
        set_config('imagestyle', 'vivid', 'format_dari');
        set_config('aiscenewriter', '0', 'format_dari');
        set_config('artscenes_' . $this->course->id, '["A person at a desk"]', 'format_dari');
        // 2026100903 creates the plan table; drop it so the step can run again here.
        $dbman = $GLOBALS['DB']->get_manager();
        $dbman->drop_table(new \xmldb_table('format_dari_imageplan'));
        $dbman->drop_table(new \xmldb_table('format_dari_imagelog'));
        set_config('artdirection_' . $this->course->id, '{}', 'format_dari');
        set_config('promptmode', 'assembled', 'format_dari');
        set_config('version', 2026100900, 'format_dari');

        $this->assertTrue(xmldb_format_dari_upgrade(2026100900));
        foreach (['directimagemodel', 'imagequality', 'imagestyle', 'aiscenewriter',
                'artscenes_' . $this->course->id, 'artdirection_' . $this->course->id] as $name) {
            $this->assertFalse(get_config('format_dari', $name), $name);
        }
        $this->assertSame('0', get_config('format_dari', 'directimages'));
        $this->assertEquals(2026100905, get_config('format_dari', 'version'));
        $this->assertFalse(get_config('format_dari', 'promptmode'), 'The 2.0.4 prompt choice is removed');
        $this->assertSame('google', get_config('format_dari', 'imageengine'));
        $this->assertTrue($dbman->table_exists('format_dari_imageplan'));
        $this->assertTrue($dbman->table_exists('format_dari_imagelog'));
        $columns = $GLOBALS['DB']->get_columns('format_dari_imageplan', false);
        foreach (['teacherhash', 'attempts', 'successes', 'failures', 'lastresult'] as $field) {
            $this->assertArrayHasKey($field, $columns, $field);
        }
        $this->assertArrayNotHasKey('generations', $columns);
    }
}
