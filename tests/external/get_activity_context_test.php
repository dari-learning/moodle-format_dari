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
 * Tests for the format_dari_get_activity_context external function.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\external;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/dari/tests/external/external_testcase.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\external\get_activity_context::class)]
/**
 * Tests for the format_dari_get_activity_context external function.
 *
 * SECURITY REGRESSION COVER. The action this function replaces had no capability check and no
 * visibility check, and it shipped answer options annotated ' [CORRECT]', per-answer explanations
 * and marking criteria straight to the browser as JSON. The tests below assert that a hidden
 * activity is refused and that no correctness marker can ever appear in the payload.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\external\get_activity_context
 */
final class get_activity_context_test extends external_testcase {
    /**
     * Build a quiz in the fixture course with one multiple choice question.
     *
     * @param array $options Extra module options, e.g. ['visible' => 0].
     * @param \stdClass|null $question Created question, returned by reference.
     * @return \stdClass The quiz activity record, with cmid.
     */
    protected function create_quiz(array $options = [], ?\stdClass &$question = null): \stdClass {
        $quiz = $this->getDataGenerator()->create_module('quiz', array_merge([
            'course' => $this->course->id,
            'name' => 'Hazard identification quiz',
            'intro' => '<p>Answer every question.</p>',
            'introformat' => FORMAT_HTML,
        ], $options));

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category([
            'contextid' => \context_module::instance($quiz->cmid)->id,
        ]);
        $question = $questiongenerator->create_question('multichoice', 'one_of_four', [
            'category' => $category->id,
            'name' => 'Hazard question',
            'questiontext' => ['text' => '<p>Which of these is a hazard?</p>', 'format' => FORMAT_HTML],
        ]);
        quiz_add_quiz_question($question->id, $quiz);

        return $quiz;
    }

    /**
     * A learner gets the activity intro and the bare question prompts.
     */
    public function test_execute_returns_the_public_context(): void {
        $quiz = $this->create_quiz();
        $this->setUser($this->student);

        $result = get_activity_context::execute($this->course->id, $quiz->cmid, 1);
        $result = external_api::clean_returnvalue(get_activity_context::execute_returns(), $result);

        $this->assertSame('quiz', $result['context']['type']);
        $this->assertSame('Hazard identification quiz', $result['context']['name']);
        $this->assertStringContainsString('Answer every question.', $result['context']['intro']);
        $this->assertCount(1, $result['context']['questions']);
        $this->assertSame(1, $result['context']['questions'][0]['slot']);
        $this->assertStringContainsString(
            'Which of these is a hazard?',
            $result['context']['questions'][0]['text']
        );
        $this->assertSame(1, $result['context']['currentquestion']['slot']);
    }

    /**
     * SECURITY REGRESSION: no correctness marker, explanation or feedback may reach the browser.
     */
    public function test_execute_never_returns_a_correct_marker(): void {
        $quiz = $this->create_quiz();
        $this->setUser($this->student);

        $result = get_activity_context::execute($this->course->id, $quiz->cmid, 1);
        $result = external_api::clean_returnvalue(get_activity_context::execute_returns(), $result);
        $payload = json_encode($result);

        $this->assertStringNotContainsString('[CORRECT]', $payload);
        $this->assertStringNotContainsString('[CORRECT:', $payload);
        $this->assertStringNotContainsString('[EXPLANATION:', $payload);
        $this->assertStringNotContainsString('[ANSWER:', $payload);
        // The generated question's correct answer is 'One'; it must not be in the payload either.
        $this->assertArrayNotHasKey('answers', $result['context']['questions'][0]);
        $this->assertSame(['slot', 'text', 'type'], array_keys($result['context']['questions'][0]));
    }

    /**
     * SECURITY REGRESSION: a hidden activity must not leak its intro or questions.
     */
    public function test_execute_refuses_an_activity_the_student_cannot_see(): void {
        $quiz = $this->create_quiz(['visible' => 0]);

        // The teacher can still see it.
        $this->setUser($this->teacher);
        $teacherresult = get_activity_context::execute($this->course->id, $quiz->cmid, 0);
        $this->assertSame('quiz', $teacherresult['context']['type']);

        // The student must not.
        $this->setUser($this->student);
        $this->assert_throws_errorcode('error_activitynotvisible', function () use ($quiz): void {
            get_activity_context::execute($this->course->id, $quiz->cmid, 0);
        });
    }

    /**
     * An activity id that is not in this course is not found.
     */
    public function test_execute_refuses_an_unknown_activity(): void {
        $this->setUser($this->student);

        $this->assert_throws_errorcode('error_activitynotfound', function (): void {
            get_activity_context::execute($this->course->id, 999999, 0);
        });
    }

    /**
     * When a question has been edited, the learner's context shows the version the quiz uses --
     * the latest, unless the slot pins one -- and nothing raises a duplicate-key warning.
     */
    public function test_execute_uses_the_quiz_s_question_version(): void {
        global $DB;
        $question = null;
        $quiz = $this->create_quiz([], $question);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->update_question($question, null, [
            'questiontext' => ['text' => '<p>Which of these is a RISK?</p>', 'format' => FORMAT_HTML],
        ]);
        $this->setUser($this->student);

        $result = get_activity_context::execute($this->course->id, $quiz->cmid, 1);
        $this->assertCount(1, $result['context']['questions']);
        $this->assertStringContainsString('Which of these is a RISK?', $result['context']['questions'][0]['text']);

        // Pin the slot to version 1.
        $slotid = $DB->get_field('quiz_slots', 'id', ['quizid' => $quiz->id, 'slot' => 1], MUST_EXIST);
        $DB->set_field(
            'question_references',
            'version',
            1,
            ['component' => 'mod_quiz', 'questionarea' => 'slot', 'itemid' => $slotid]
        );
        $result = get_activity_context::execute($this->course->id, $quiz->cmid, 1);
        $this->assertCount(1, $result['context']['questions']);
        $this->assertStringContainsString('Which of these is a hazard?', $result['context']['questions'][0]['text']);
    }

    /**
     * The administrator's switch, and the absence of a text provider, are reported precisely.
     */
    public function test_execute_requires_the_switch_and_a_provider(): void {
        $quiz = $this->create_quiz();
        $this->setUser($this->student);

        $this->stub_ai(false, false);
        $this->assert_throws_errorcode($this->no_provider_error(), function () use ($quiz): void {
            get_activity_context::execute($this->course->id, $quiz->cmid, 0);
        });

        $this->stub_ai();
        set_config('enabletutor', 0, 'format_dari');
        $this->assert_throws_errorcode('error_tutordisabled', function () use ($quiz): void {
            get_activity_context::execute($this->course->id, $quiz->cmid, 0);
        });
    }

    /**
     * A user without format/dari:useaitutor is refused.
     */
    public function test_execute_requires_capability(): void {
        $quiz = $this->create_quiz();
        $this->prohibit_capability('format/dari:useaitutor', 'student');
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        get_activity_context::execute($this->course->id, $quiz->cmid, 0);
    }

    /**
     * An activityid that is not an integer is rejected before any code runs.
     */
    public function test_execute_rejects_invalid_parameters(): void {
        $this->setUser($this->student);

        $this->assert_call_fails('invalidparameter', 'format_dari_get_activity_context', [
            'courseid' => $this->course->id,
            'activityid' => 'not-a-number',
        ]);
    }
}
