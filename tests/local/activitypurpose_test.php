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

/**
 * Tests for activity purpose classification.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\activitypurpose
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\activitypurpose::class)]
final class activitypurpose_test extends \advanced_testcase {

    /**
     * Titles and descriptions outrank the module type.
     *
     * @return array
     */
    public static function cases(): array {
        return [
            'exam instructions page' => ['page', 'CPA Practice Exam Instructions', '', null, 'instructions', 'title'],
            'full practice exam quiz' => ['quiz', 'AUD – Full CPA Practice Examination', '', true, 'assessment', 'title'],
            'mock exam' => ['quiz', 'FAR mock exam', '', true, 'assessment', 'title'],
            'content page' => ['page', 'Understanding Audit Evidence', '', null, 'content', 'type'],
            'risk assessment is a topic' => ['page', 'Risk assessment in the workplace', '', null, 'content', 'type'],
            'knowledge check' => ['quiz', 'Knowledge check', '', true, 'practice', 'title'],
            'practice test' => ['quiz', 'Practice test 2', '', true, 'practice', 'title'],
            'knowledge test' => ['quiz', 'Knowledge test', '', true, 'practice', 'title'],
            'final test' => ['quiz', 'Final test', '', true, 'assessment', 'title'],
            'ungraded quiz by type' => ['quiz', 'Leases', '', false, 'practice', 'type'],
            'graded quiz by type' => ['quiz', 'Leases', '', true, 'assessment', 'type'],
            'written questions assignment' => ['assign', 'Assessment 1 - Written questions', '', null, 'submission', 'title'],
            'assignment by type' => ['assign', 'Your kitchen plan', '', null, 'submission', 'type'],
            'forum' => ['forum', 'Introduce yourself', '', null, 'discussion', 'title'],
            'forum by type' => ['forum', 'Week 3', '', null, 'discussion', 'type'],
            'resource' => ['resource', 'AICPA Blueprint', '', null, 'resources', 'type'],
            'glossary by title' => ['page', 'Glossary of audit terms', '', null, 'resources', 'title'],
            'feedback' => ['feedback', 'Course evaluation', '', null, 'feedback', 'title'],
            'certificate' => ['customcert', 'Your certificate', '', null, 'completion', 'title'],
            'description decides' => ['page', 'Before the exam', 'Read these instructions before you start.', null,
                'instructions', 'title'],
            'description when title is neutral' => ['page', 'Part B', 'Upload your completed workbook here.', null,
                'submission', 'description'],
            'unknown module' => ['mystery', 'Something', '', null, 'other', 'type'],
        ];
    }

    /**
     * Each case is classified with the expected purpose and basis.
     *
     * @dataProvider cases
     * @param string $modname Module.
     * @param string $title Title.
     * @param string $text Description.
     * @param bool|null $graded Quiz graded.
     * @param string $purpose Expected purpose.
     * @param string $basis Expected basis.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function test_classify(string $modname, string $title, string $text, ?bool $graded, string $purpose,
            string $basis): void {
        $this->assertSame(['purpose' => $purpose, 'basis' => $basis],
            activitypurpose::classify($modname, $title, $text, $graded));
    }

    /**
     * Every purpose has guidance for the planner.
     */
    public function test_guidance(): void {
        foreach ([activitypurpose::CONTENT, activitypurpose::INSTRUCTIONS, activitypurpose::ASSESSMENT,
                activitypurpose::PRACTICE, activitypurpose::DISCUSSION, activitypurpose::RESOURCES,
                activitypurpose::SUBMISSION, activitypurpose::FEEDBACK, activitypurpose::COMPLETION,
                activitypurpose::OTHER] as $purpose) {
            $this->assertNotEmpty(activitypurpose::GUIDANCE[$purpose] ?? '', $purpose);
        }
    }

    /**
     * Real activities: a page's own content is read when it has no description, and a quiz's
     * marks decide between practice and assessment when its title says nothing.
     */
    public function test_real_activities(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari', 'numsections' => 1],
            ['createsections' => true]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1,
            'name' => 'Part B', 'intro' => '<p></p>', 'content' => '<p>Before you start the exam, check your ID.</p>']);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'section' => 1,
            'name' => 'Leases', 'intro' => '<p></p>', 'grade' => 0]);
        $modinfo = get_fast_modinfo($course);

        $this->assertStringContainsString('Before you start the exam', activitypurpose::content_text($modinfo->get_cm($page->cmid)));
        $facts = imageplanner::item_facts(get_course($course->id), 'cm:' . $page->cmid);
        $this->assertSame('instructions', $facts['purpose']);

        $this->assertFalse(activitypurpose::quiz_graded($modinfo->get_cm($quiz->cmid)));
        $facts = imageplanner::item_facts(get_course($course->id), 'cm:' . $quiz->cmid);
        $this->assertSame('practice', $facts['purpose']);
        $this->assertSame('type', $facts['purposebasis']);
    }
}
