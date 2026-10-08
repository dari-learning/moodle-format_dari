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
 * Tests for the gateway to Moodle's AI subsystem.
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
 * Tests for \format_dari\local\ai.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\ai
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\ai::class)]
final class ai_test extends \advanced_testcase {
    use \format_dari\tests\ai_stub;

    /** @var \stdClass A course in the Dari format. */
    private $course;

    /** @var \context_course Its context. */
    private $context;

    /** @var \stdClass A teacher in it. */
    private $teacher;

    /**
     * Fixture: a course and a teacher; the memo starts empty.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        ai::reset_memo();
        $this->course = $this->getDataGenerator()->create_course(['format' => 'dari']);
        $this->context = \context_course::instance($this->course->id);
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($this->teacher);
    }

    /**
     * With Moodle's real manager and no provider configured, both features say so.
     */
    public function test_unavailable_with_no_provider(): void {
        $this->assertSame('error_ai_notextprovider', ai::unavailable_reason(ai::FEATURE_TEXT));
        $this->assertSame('error_ai_noimageprovider', ai::unavailable_reason(ai::FEATURE_IMAGE, $this->context));
        $this->assertFalse(ai::is_available(ai::FEATURE_TEXT, $this->context));
        $this->assertFalse(permissions::is_tutor_enabled($this->context));
        // The administrator's own switch is a separate question.
        $this->assertTrue(permissions::is_tutor_switched_on());

        try {
            ai::require_available(ai::FEATURE_IMAGE, $this->context);
            $this->fail('Expected an exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_ai_noimageprovider', $e->errorcode);
        }
    }

    /**
     * Each feature is judged on its own action.
     */
    public function test_reason_per_feature(): void {
        $this->stub_ai(true, false);
        $this->assertNull(ai::unavailable_reason(ai::FEATURE_TEXT, $this->context));
        $this->assertSame('error_ai_noimageprovider', ai::unavailable_reason(ai::FEATURE_IMAGE, $this->context));
        $this->assertTrue(permissions::is_tutor_enabled($this->context));

        $this->stub_ai(false, true);
        $this->assertSame('error_ai_notextprovider', ai::unavailable_reason(ai::FEATURE_TEXT, $this->context));
        $this->assertNull(ai::unavailable_reason(ai::FEATURE_IMAGE, $this->context));

        set_config('enabletutor', 0, 'format_dari');
        $this->stub_ai(true, true);
        $this->assertFalse(permissions::is_tutor_enabled($this->context));
        $this->assertFalse(permissions::is_tutor_switched_on());
    }

    /**
     * The answer is memoised per request until reset_memo().
     */
    public function test_memo(): void {
        $this->stub_ai(false, false);
        $this->assertSame('error_ai_notextprovider', ai::unavailable_reason(ai::FEATURE_TEXT, $this->context));

        // Swap the manager without clearing the memo: the cached answer stands.
        $manager = $this->getMockBuilder(\core_ai\manager::class)->disableOriginalConstructor()
            ->onlyMethods(['is_action_available'])->getMock();
        $manager->method('is_action_available')->willReturn(true);
        \core\di::set(\core_ai\manager::class, $manager);
        $this->assertSame('error_ai_notextprovider', ai::unavailable_reason(ai::FEATURE_TEXT, $this->context));

        ai::reset_memo();
        $this->assertNull(ai::unavailable_reason(ai::FEATURE_TEXT, $this->context));
    }

    /**
     * Core's AI policy must be accepted.
     */
    public function test_policy(): void {
        $userid = (int) $this->teacher->id;
        $this->assertFalse(ai::policy_accepted($userid));
        try {
            ai::require_policy($userid);
            $this->fail('Expected an exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_ai_policynotaccepted', $e->errorcode);
        }

        $this->accept_ai_policy($userid);
        $this->assertTrue(ai::policy_accepted($userid));
        ai::require_policy($userid);

        $state = ai::client_state(ai::FEATURE_TEXT, $this->context, $userid);
        $this->assertSame(['available' => false, 'policyaccepted' => true], $state);
    }

    /**
     * generate_text() returns the text, the finish reason and the model, minus inline reasoning.
     */
    public function test_generate_text(): void {
        $this->stub_ai();
        $this->queue_text_reply("<thinking>step one</thinking>\n  The answer.  ", 'LENGTH');

        $result = ai::generate_text($this->context, (int) $this->teacher->id, 'Prompt here');

        $this->assertSame(['text' => 'The answer.', 'finishreason' => 'length', 'model' => 'test-model'], $result);
        $this->assertSame('Prompt here', $this->aiactions[0]->get_configuration('prompttext'));
        $this->assertSame($this->context->id, $this->aiactions[0]->get_configuration('contextid'));
    }

    /**
     * Provider failures become translated exceptions; nothing is sent when unavailable.
     */
    public function test_generate_text_failures(): void {
        $this->stub_ai();
        foreach ([[401, 'bad key', 'error_apiunauthorized'], [429, 'slow down', 'error_apiratelimited'],
                [500, 'boom', 'error_ai_textfailed_detail']] as [$code, $message, $expected]) {
            $this->queue_failure($code, $message);
            try {
                ai::generate_text($this->context, (int) $this->teacher->id, 'x');
                $this->fail('Expected an exception');
            } catch (\moodle_exception $e) {
                $this->assertSame($expected, $e->errorcode);
            }
        }
        $this->resetDebugging();

        $this->stub_ai(false, false);
        try {
            ai::generate_text($this->context, (int) $this->teacher->id, 'x');
            $this->fail('Expected an exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_ai_notextprovider', $e->errorcode);
        }
        $this->assertCount(0, $this->aiactions);
    }

    /**
     * generate_image() returns the image bytes and removes core's draft file.
     */
    public function test_generate_image(): void {
        $this->stub_ai();
        set_config('imagequality', 'hd', 'format_dari');
        set_config('imagestyle', 'vivid', 'format_dari');
        $png = self::make_png(40, 20);
        $this->queue_image_reply($png);

        $bytes = ai::generate_image($this->context, (int) $this->teacher->id, 'A kitchen', 'square');

        $this->assertSame($png, $bytes, 'A small image is returned untouched');
        $this->assertSame(0, $this->count_draft_files((int) $this->teacher->id));
        $action = $this->aiactions[0];
        $this->assertSame('square', $action->get_configuration('aspectratio'));
        $this->assertSame('hd', $action->get_configuration('quality'));
        $this->assertSame('vivid', $action->get_configuration('style'));
        $this->assertSame(1, $action->get_configuration('numimages'));

        // An unknown aspect ratio falls back to landscape.
        ai::generate_image($this->context, (int) $this->teacher->id, 'A kitchen', 'panorama');
        $this->assertSame('landscape', $this->aiactions[1]->get_configuration('aspectratio'));
    }

    /**
     * A provider that claims success without a file is an error.
     */
    public function test_generate_image_without_a_file(): void {
        $this->stub_ai();
        $this->aiimagereplies[] = function () {
            $response = new \core_ai\aiactions\responses\response_generate_image(true);
            $response->set_response_data(['draftfile' => null]);
            return $response;
        };
        try {
            ai::generate_image($this->context, (int) $this->teacher->id, 'x');
            $this->fail('Expected an exception');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_bannernoimage', $e->errorcode);
        }
    }

    /**
     * image_prompt() appends the Avoid line, and only when there is something to avoid.
     */
    public function test_image_prompt_appends_avoid_line(): void {
        $this->stub_ai();
        $userid = (int) $this->teacher->id;

        $prompt = ai::image_prompt(['prompt' => 'A chef at work.', 'negativePrompt' => 'text, logos'], $this->context, $userid);
        $this->assertSame("A chef at work.\nAvoid: text, logos.", $prompt);

        $prompt = ai::image_prompt(['prompt' => 'A chef at work.', 'negativePrompt' => ''], $this->context, $userid);
        $this->assertSame('A chef at work.', $prompt);
        // No brief, so the art director has nothing to work from and is not asked.
        $this->assertCount(0, $this->aiactions);
    }

    /**
     * image_prompt() hands the recipe to the art director and appends the Avoid line; without a
     * text provider, or with the art director switched off, the template is used.
     */
    public function test_image_prompt_uses_the_art_director(): void {
        $this->stub_ai();
        $userid = (int) $this->teacher->id;
        $composed = [
            'prompt' => 'Template scene. STYLE TAIL',
            'promptTail' => 'STYLE TAIL',
            'negativePrompt' => 'text',
            'brief' => ['topic' => 'Food safety', 'courseName' => 'Kitchen operations', 'style' => 'photo'],
        ];

        $prompt = ai::image_prompt($composed, $this->context, $userid);
        $this->assertStringStartsWith('An apprentice chef in crisp whites', $prompt);
        $this->assertStringContainsString("\n\nSTYLE TAIL\n\n", $prompt);
        $this->assertStringEndsWith("Keep out: Bare hands on raw food.\nAvoid: text.", $prompt);
        $this->assertCount(2, $this->aiactions);

        set_config('aiscenewriter', 0, 'format_dari');
        $this->assertSame("Template scene. STYLE TAIL\nAvoid: text.", ai::image_prompt($composed, $this->context, $userid));

        set_config('aiscenewriter', 1, 'format_dari');
        $this->stub_ai(false, true);
        $this->assertSame("Template scene. STYLE TAIL\nAvoid: text.", ai::image_prompt($composed, $this->context, $userid));
        $this->assertCount(0, $this->aiactions);
    }

    /**
     * optimise_image() shrinks a large image to a 1920 px wide JPEG and leaves others alone.
     */
    public function test_optimise_image(): void {
        $img = imagecreatetruecolor(2000, 1000);
        mt_srand(42);
        for ($y = 0; $y < 1000; $y++) {
            for ($x = 0; $x < 2000; $x++) {
                imagesetpixel($img, $x, $y, mt_rand(0, 0xFFFFFF));
            }
        }
        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        $out = ai::optimise_image($png);
        $this->assertLessThan(strlen($png), strlen($out));
        $info = getimagesizefromstring($out);
        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(1920, $info[0]);
        $this->assertSame(960, $info[1]);

        $small = self::make_png(100, 50);
        $this->assertSame($small, ai::optimise_image($small));
        $this->assertSame('not an image', ai::optimise_image('not an image'));
    }
}
