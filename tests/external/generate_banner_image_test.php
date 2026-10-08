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
 * Tests for the format_dari_generate_banner_image external function.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/dari/tests/external/external_testcase.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\external\generate_banner_image::class)]
/**
 * Tests for the format_dari_generate_banner_image external function.
 *
 * Moodle's AI manager is a mock (see external_testcase), so the generation itself is exercised too.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\external\generate_banner_image
 */
final class generate_banner_image_test extends external_testcase {
    /**
     * A student cannot generate a banner.
     */
    public function test_execute_requires_capability(): void {
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        generate_banner_image::execute($this->course->id);
    }

    /**
     * A site with no image provider says so before anything is queued.
     */
    public function test_execute_requires_an_image_provider(): void {
        $this->stub_ai(true, false);
        $this->setUser($this->teacher);

        $this->assert_throws_errorcode($this->no_provider_error(true), function (): void {
            generate_banner_image::execute($this->course->id);
        });
        $this->assertCount(0, \core\task\manager::get_adhoc_tasks('\\format_dari\\task\\generate_banner'));
    }

    /**
     * A teacher who has not accepted the AI policy is refused on the server.
     */
    public function test_execute_requires_the_ai_policy(): void {
        $teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->setUser($teacher);

        if (!\format_dari\local\ai::subsystem_present()) {
            // No core policy API exists on 4.4; the permitted request must still queue normally.
            $this->assertTrue(\format_dari\local\ai::policy_accepted((int) $teacher->id));
            $this->assertSame('queued', generate_banner_image::execute($this->course->id)['status']);
            $this->assertCount(1, $this->pending_tasks('\format_dari\task\generate_banner'));
            return;
        }
        $this->assert_throws_errorcode('error_ai_policynotaccepted', function (): void {
            generate_banner_image::execute($this->course->id);
        });
    }

    /**
     * Generation is queued as an adhoc task that runs as the teacher who asked.
     */
    public function test_execute_queues_a_task_as_the_teacher(): void {
        $this->setUser($this->teacher);
        $result = generate_banner_image::execute($this->course->id, str_repeat('b', 500));
        $this->assertSame('queued', $result['status']);

        $tasks = \core\task\manager::get_adhoc_tasks('\\format_dari\\task\\generate_banner');
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertEquals($this->teacher->id, $task->get_userid());
        $this->assertSame(300, \core_text::strlen($task->get_custom_data()->extraprompt));
        $this->assertSame('queued', generate_banner_image::get_status((int) $this->course->id)['state']);
        $this->assertCount(0, $this->aiactions, 'Nothing is generated inside the web request');
    }

    /**
     * generate_and_store() asks core for a landscape image, stores it in the banner area and
     * leaves nothing behind in the teacher's draft files.
     */
    public function test_generate_and_store(): void {
        $this->setUser($this->teacher);
        $this->queue_image_reply();

        $url = generate_banner_image::generate_and_store(
            get_course($this->course->id),
            'warm light',
            0,
            (int) $this->teacher->id
        );

        $this->assertStringContainsString('/format_dari/bannerimage/', $url);
        $files = get_file_storage()->get_area_files($this->context->id, 'format_dari', 'bannerimage', false, 'id', false);
        $this->assertCount(1, $files);
        $this->assertSame(0, $this->count_draft_files((int) $this->teacher->id));

        $images = $this->actions_of(\core_ai\aiactions\generate_image::class);
        $this->assertCount(1, $images);
        $action = $images[0];
        $this->assertSame('landscape', $action->get_configuration('aspectratio'));
        if (\format_dari\local\ai::subsystem_present()) {
            $this->assertSame($this->context->id, $action->get_configuration('contextid'));
        }
        $prompt = $action->get_configuration('prompttext');
        // The banner was planned with the teacher's direction first, its prompt written from that
        // plan, and the banner's fixed tail follows it unchanged.
        $this->assertStringStartsWith('An apprentice chef in crisp whites', $prompt);
        $this->assertStringContainsString('left third calm and simple', $prompt);
        $this->assertStringContainsString('minimal, clean lettering; no captions, logos or watermarks', $prompt);
        // The teacher's direction reached the planning request as its top priority, and the prompt
        // writer was told to honour it.
        $texts = array_map(fn($a) => (string) $a->get_configuration('prompttext'),
            $this->actions_of(\core_ai\aiactions\generate_text::class));
        $plan = array_values(array_filter($texts, fn($t) => str_starts_with($t, \format_dari\local\imageplanner::ITEM_OPENING)));
        $this->assertCount(1, $plan);
        $this->assertStringContainsString('THE TEACHER ASKS FOR (highest priority', $plan[0]);
        $this->assertStringContainsString('warm light', $plan[0]);
        $this->assertStringContainsString('the left third stays calm for the title overlay', $plan[0]);
        $this->assertStringContainsString('- The teacher asks for (must be honoured): warm light', $this->last_prompt());
    }

    /**
     * The task stores the banner and reports done; a provider failure is reported, not retried.
     */
    public function test_task_success_and_failure(): void {
        $this->setUser($this->teacher);
        generate_banner_image::execute($this->course->id);
        $this->expectOutputRegex('/.*/');
        $this->runAdhocTasks('\\format_dari\\task\\generate_banner');

        $status = generate_banner_image::get_status((int) $this->course->id);
        $this->assertSame('done', $status['state']);
        $this->assertStringContainsString('/bannerimage/', $status['detail']);
        $this->assertSame(0, $this->count_draft_files((int) $this->teacher->id));

        $this->setUser($this->teacher);
        generate_banner_image::execute($this->course->id);
        $this->queue_failure(400, 'Your request was rejected by the safety system', true);
        $this->runAdhocTasks('\\format_dari\\task\\generate_banner');
        $this->assertDebuggingCalled();
        $status = generate_banner_image::get_status((int) $this->course->id);
        $this->assertSame('failed', $status['state']);
        $this->assertStringContainsString('rejected by the safety system', $status['detail']);
        $this->assertCount(0, \core\task\manager::get_adhoc_tasks('\\format_dari\\task\\generate_banner'));
    }

    /**
     * Generation stays rate limited: the fourth generation in the window is refused.
     */
    public function test_execute_is_rate_limited(): void {
        $this->setUser($this->teacher);
        // No image provider, so every allowed call stops at the availability check; the throttle
        // runs before it.
        $this->stub_ai(true, false);

        for ($i = 0; $i < throttle::BANNER_MAX; $i++) {
            try {
                generate_banner_image::execute($this->course->id);
                $this->fail('Expected the availability check to reject this call.');
            } catch (\moodle_exception $e) {
                $this->assertSame($this->no_provider_error(true), $e->errorcode);
            }
        }

        $this->assert_throws_errorcode('error_toomanyrequests', function (): void {
            generate_banner_image::execute($this->course->id);
        });
    }

    /**
     * A courseid that is not an integer is rejected before any code runs.
     */
    public function test_execute_rejects_invalid_parameters(): void {
        $this->setUser($this->teacher);

        $this->assert_call_fails('invalidparameter', 'format_dari_generate_banner_image', [
            'courseid' => 'not-a-number',
        ]);
    }
}
