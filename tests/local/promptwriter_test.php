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
 * Tests for Dari's image art director.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\local;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/ai_stub.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\promptwriter::class)]
/**
 * Tests for \format_dari\local\promptwriter.
 *
 * The site's AI manager is a mock (see \format_dari\tests\ai_stub): by default it answers the
 * art-direction request with a JSON object and the scene request with a usable paragraph.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\promptwriter
 */
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
     * Clean JSON is read field by field; unknown keys are ignored and missing ones are empty.
     */
    public function test_parse_art_clean_json(): void {
        $art = promptwriter::parse_art('{"world": "A busy kitchen", "people": "Chefs", "light": "Morning", '
            . '"extra": "ignored"}');
        $this->assertSame(['world', 'people', 'places', 'props', 'palette', 'light', 'mood', 'avoid'], array_keys($art));
        $this->assertSame('A busy kitchen', $art['world']);
        $this->assertSame('Chefs', $art['people']);
        $this->assertSame('Morning', $art['light']);
        $this->assertSame('', $art['places']);
        $this->assertArrayNotHasKey('extra', $art);
    }

    /**
     * JSON wrapped in chatter or a code fence is still found.
     */
    public function test_parse_art_tolerates_prose_and_fences(): void {
        $fence = str_repeat(chr(96), 3);
        $reply = "Sure! Here is the art direction you asked for:\n{$fence}json\n"
            . "{\"world\": \"A **hospital** ward\", \"mood\": \"Reassuring\"}\n{$fence}\nLet me know if you need more.";
        $art = promptwriter::parse_art($reply);
        $this->assertSame('A hospital ward', $art['world']);
        $this->assertSame('Reassuring', $art['mood']);
    }

    /**
     * A reply that is not JSON becomes the description of the world.
     */
    public function test_parse_art_non_json_fallback(): void {
        $art = promptwriter::parse_art("  A rural farm at dawn,\n with  tractors and sheep.  ");
        $this->assertSame('A rural farm at dawn, with tractors and sheep.', $art['world']);
        $this->assertSame('', $art['people']);
        $this->assertSame('', $art['avoid']);
    }

    /**
     * List values are joined into one phrase.
     */
    public function test_parse_art_joins_array_values(): void {
        $art = promptwriter::parse_art('{"places": ["Cool room", "Prep bench", "Loading dock"], "props": ["Probe"]}');
        $this->assertSame('Cool room, Prep bench, Loading dock', $art['places']);
        $this->assertSame('Probe', $art['props']);
    }

    /**
     * The art director is on unless switched off, and needs a text provider.
     */
    public function test_enabled(): void {
        unset_config('aiscenewriter', 'format_dari');
        $this->assertTrue(promptwriter::enabled($this->context), 'Unset counts as on');

        set_config('aiscenewriter', '', 'format_dari');
        $this->assertTrue(promptwriter::enabled($this->context));

        set_config('aiscenewriter', '0', 'format_dari');
        $this->assertFalse(promptwriter::enabled($this->context));

        set_config('aiscenewriter', '1', 'format_dari');
        $this->assertTrue(promptwriter::enabled($this->context));

        $this->stub_ai(false, true);
        $this->assertFalse(promptwriter::enabled($this->context), 'No text provider');
    }

    /**
     * Switched off, or with no brief, the template is returned and nothing is asked.
     */
    public function test_write_returns_the_template_when_not_enabled(): void {
        $composed = $this->composed();
        set_config('aiscenewriter', 0, 'format_dari');
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));

        set_config('aiscenewriter', 1, 'format_dari');
        unset($composed['brief']);
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertCount(0, $this->aiactions);
    }

    /**
     * The art direction is written once per course, cached, and rewritten when the course changes.
     */
    public function test_art_direction_is_cached_and_refreshed(): void {
        global $DB;
        $first = promptwriter::write($this->composed(1), $this->context, $this->userid);
        $this->assertStringStartsWith('An apprentice chef in crisp whites', $first);
        $this->assertCount(1, $this->prompts_starting('You are the art director'));
        $this->assertStringContainsString('- Course: Kitchen operations', $this->prompts_starting('You are the art director')[0]);

        $cached = json_decode(get_config('format_dari', 'artdirection_' . $this->course->id), true);
        $this->assertSame('A busy, well-run commercial kitchen', $cached['art']['world']);
        $this->assertNotEmpty($cached['hash']);

        // A second image in the same course reuses it: one more scene, no more art direction.
        promptwriter::write($this->composed(2), $this->context, $this->userid);
        $this->assertCount(1, $this->prompts_starting('You are the art director'));
        $this->assertCount(2, $this->prompts_starting('You write prompts for an AI image generator'));

        // The course summary changes: the art direction is written again, from the new facts.
        $DB->set_field('course', 'summary', 'Run a bakery production line.', ['id' => $this->course->id]);
        rebuild_course_cache($this->course->id, true);
        promptwriter::write($this->composed(1), $this->context, $this->userid);
        $art = $this->prompts_starting('You are the art director');
        $this->assertCount(2, $art);
        $this->assertStringContainsString('Run a bakery production line.', $art[1]);
    }

    /**
     * A reply that is not JSON is used for this image but not cached, so the next image asks again.
     */
    public function test_non_json_art_direction_is_not_cached(): void {
        $this->queue_text_reply("I'm sorry, I can't help with that request.");
        $prompt = promptwriter::write($this->composed(1), $this->context, $this->userid);
        $this->assertStringStartsWith('An apprentice chef in crisp whites', $prompt);
        $this->assertFalse(get_config('format_dari', 'artdirection_' . $this->course->id));
        $this->assertStringContainsString(
            "- World: I'm sorry, I can't help with that request.",
            $this->prompts_starting('You write prompts for an AI image generator')[0]
        );

        promptwriter::write($this->composed(2), $this->context, $this->userid);
        $this->assertCount(2, $this->prompts_starting('You are the art director'));
        $this->assertNotFalse(get_config('format_dari', 'artdirection_' . $this->course->id));
    }

    /**
     * After the first image, the scene request lists the scenes the course already shows.
     */
    public function test_scene_request_lists_previous_scenes(): void {
        $this->queue_text_reply('{"world": "A kitchen"}');
        $this->queue_text_reply('A pastry cook pipes cream onto a tray of eclairs. Ovens glow behind her as an '
            . 'apprentice weighs flour.');
        promptwriter::write($this->composed(1), $this->context, $this->userid);
        $scenes = $this->prompts_starting('You write prompts for an AI image generator');
        $this->assertStringNotContainsString('ALREADY SHOW', $scenes[0]);
        $this->assertStringContainsString('- World: A kitchen', $scenes[0]);
        $this->assertStringContainsString('the image for one section', $scenes[0]);

        $this->assertSame(
            ['A pastry cook pipes cream onto a tray of eclairs.'],
            promptwriter::recent_scenes((int) $this->course->id)
        );

        promptwriter::write($this->composed(2), $this->context, $this->userid);
        $scenes = $this->prompts_starting('You write prompts for an AI image generator');
        $this->assertCount(2, $scenes);
        $this->assertStringContainsString("ALREADY SHOW (choose a clearly different moment, place or angle):\n"
            . '- A pastry cook pipes cream onto a tray of eclairs.', $scenes[1]);
        $this->assertCount(2, promptwriter::recent_scenes((int) $this->course->id));
    }

    /**
     * assemble(): scene, then the look, then the medium, then the people line, then the tail,
     * then "Keep out".
     */
    public function test_assemble_order_and_contents(): void {
        $art = promptwriter::parse_art('{"light": "Soft morning light.", "palette": "Steel and green", '
            . '"mood": "Calm", "avoid": "Bare hands on raw food"}');
        $prompt = promptwriter::assemble(
            'A chef plates a dish at the pass.',
            $art,
            ['style' => 'illustration'],
            "Style: tail.\nNo visible text."
        );

        $parts = explode("\n\n", $prompt);
        $this->assertCount(6, $parts);
        $this->assertSame('A chef plates a dish at the pass.', $parts[0]);
        $this->assertSame('Lighting: Soft morning light. Palette: Steel and green. Mood: Calm.', $parts[1]);
        $this->assertStringContainsString('modern editorial illustration', $parts[2]);
        $this->assertStringStartsWith('People look natural and unposed', $parts[3]);
        $this->assertSame("Style: tail.\nNo visible text.", $parts[4]);
        $this->assertSame('Keep out: Bare hands on raw food.', $parts[5]);

        // Empty fields add nothing; an unknown style uses the photographic medium.
        $prompt = promptwriter::assemble(
            'A chef plates a dish at the pass.',
            promptwriter::parse_art('{}'),
            ['style' => 'unknown'],
            ''
        );
        $parts = explode("\n\n", $prompt);
        $this->assertCount(3, $parts);
        $this->assertStringContainsString('editorial photograph', $parts[1]);
        $this->assertStringNotContainsString('Keep out', $prompt);
        $this->assertStringNotContainsString('Lighting:', $prompt);
    }

    /**
     * However long the model's writing, the plugin's own fixed rules survive whole.
     */
    public function test_assemble_keeps_the_tail_within_the_limit(): void {
        $composed = $this->composed();
        $long = str_repeat('word ', 300);
        $art = array_fill_keys(
            ['world', 'people', 'places', 'props', 'palette', 'light', 'mood', 'avoid'],
            trim(substr($long, 0, 240))
        );
        $prompt = promptwriter::assemble(
            trim(substr(str_repeat($long, 2), 0, 1200)),
            $art,
            $composed['brief'],
            $composed['promptTail']
        );

        $this->assertLessThanOrEqual(3600, \core_text::strlen($prompt));
        $this->assertStringContainsString(trim($composed['promptTail']), $prompt);
    }

    /**
     * A text failure falls back to the template prompt and tells developers.
     */
    public function test_text_failure_falls_back_to_the_template(): void {
        $composed = $this->composed();
        $this->queue_failure(500, 'Model overloaded');
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertDebuggingCalledCount(2); // The core_ai failure, then the art director's fallback.
        $this->assertFalse(get_config('format_dari', 'artdirection_' . $this->course->id), 'A failure is not cached');

        // The scene request failing falls back too.
        $this->queue_text_reply('{"world": "A kitchen"}');
        $this->queue_failure(500, 'Model overloaded');
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertDebuggingCalledCount(2);
        $this->assertSame([], promptwriter::recent_scenes((int) $this->course->id));
    }

    /**
     * A scene reply too short to be a scene falls back to the template, quietly.
     */
    public function test_short_scene_falls_back_to_the_template(): void {
        $composed = $this->composed();
        $this->queue_text_reply('{"world": "A kitchen"}');
        $this->queue_text_reply('Scene: A chef cooking.');
        $this->assertSame($composed['prompt'], promptwriter::write($composed, $this->context, $this->userid));
        $this->assertSame([], promptwriter::recent_scenes((int) $this->course->id));
    }

    /**
     * forget() clears the art direction and the scene memory; deleting the course does too.
     */
    public function test_forget_clears_both_keys(): void {
        $courseid = (int) $this->course->id;
        promptwriter::write($this->composed(), $this->context, $this->userid);
        $this->assertNotFalse(get_config('format_dari', 'artdirection_' . $courseid));
        $this->assertNotFalse(get_config('format_dari', 'artscenes_' . $courseid));

        promptwriter::forget($courseid);
        $this->assertFalse(get_config('format_dari', 'artdirection_' . $courseid));
        $this->assertFalse(get_config('format_dari', 'artscenes_' . $courseid));

        promptwriter::write($this->composed(), $this->context, $this->userid);
        $this->assertNotFalse(get_config('format_dari', 'artscenes_' . $courseid));
        delete_course($courseid, false);
        $this->assertFalse(get_config('format_dari', 'artdirection_' . $courseid));
        $this->assertFalse(get_config('format_dari', 'artscenes_' . $courseid));
    }

    /**
     * The 2026100805 upgrade step switches the art director on where it was off, and leaves
     * any other value alone.
     */
    public function test_upgrade_step_switches_the_art_director_on(): void {
        global $CFG;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/course/format/dari/db/upgrade.php');

        foreach (['0' => '1', '1' => '1'] as $before => $after) {
            set_config('aiscenewriter', $before, 'format_dari');
            set_config('version', 2026100800, 'format_dari');
            $this->assertTrue(xmldb_format_dari_upgrade(2026100800));
            $this->assertSame($after, get_config('format_dari', 'aiscenewriter'));
            $this->assertEquals(2026100805, get_config('format_dari', 'version'));
        }

        // Unset stays unset (which already counts as on).
        unset_config('aiscenewriter', 'format_dari');
        set_config('version', 2026100800, 'format_dari');
        xmldb_format_dari_upgrade(2026100800);
        $this->assertFalse(get_config('format_dari', 'aiscenewriter'));

        // A site already past the step is not touched.
        set_config('aiscenewriter', '0', 'format_dari');
        xmldb_format_dari_upgrade(2026100805);
        $this->assertSame('0', get_config('format_dari', 'aiscenewriter'));
    }
}
