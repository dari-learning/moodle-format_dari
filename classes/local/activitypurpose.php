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
 * What a Moodle activity asks the learner to do, for its card image.
 *
 * A section card shows what learners study. An activity card shows what the learner is asked to
 * do. "CPA Practice Exam Instructions" (a Page) is about getting ready for an exam, not about
 * auditing, even though it sits in the auditing section.
 *
 * The purpose comes from the first of these that says something: the activity's title, then its
 * description or content, then its Moodle module type. The title and description outrank the
 * module type, so a Page called "Exam instructions" is instructions, not reading, and a Quiz
 * called "Knowledge check" is practice, not an exam.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitypurpose {
    /** @var string Learning content: the activity teaches the subject. */
    public const CONTENT = 'content';
    /** @var string Instructions and orientation. */
    public const INSTRUCTIONS = 'instructions';
    /** @var string Assessment and examinations. */
    public const ASSESSMENT = 'assessment';
    /** @var string Practice and knowledge checks. */
    public const PRACTICE = 'practice';
    /** @var string Discussion and collaboration. */
    public const DISCUSSION = 'discussion';
    /** @var string Resources and reference materials. */
    public const RESOURCES = 'resources';
    /** @var string Submission and assignments. */
    public const SUBMISSION = 'submission';
    /** @var string Feedback and surveys. */
    public const FEEDBACK = 'feedback';
    /** @var string Completion and certification. */
    public const COMPLETION = 'completion';
    /** @var string Anything else, judged from the content. */
    public const OTHER = 'other';

    /**
     * @var array Title and description patterns, checked in order: the first match wins. Instructions
     * come first, so "Practice Exam Instructions" is instructions, not an exam.
     */
    private const RULES = [
        self::INSTRUCTIONS => '~\b(instructions?|how to (use|complete|sit|prepare|navigate|submit|access)|read (this|me) first|'
            . 'before you (start|begin)|before (the|your) (exam|test|assessment)|exam day|preparing for (the|your) exam|orientation|start here|getting started|welcome|what to expect|exam (rules|conditions)|'
            . 'assessment (rules|conditions|guide)|candidate (guide|information))\b~iu',
        self::COMPLETION => '~\b(certificates?|completion|congratulations|graduat\w*|badges?)\b~iu',
        self::DISCUSSION => '~\b(forums?|discussions?|introduce yourself|q ?& ?a|chat|community|networking|debate|peer)\b~iu',
        self::FEEDBACK => '~\b(feedback|surveys?|evaluation|questionnaires?|have your say|reflections? survey)\b~iu',
        self::SUBMISSION => '~\b(assignments?|submit|submission|portfolio|upload your|written (task|questions)|'
            . 'task \d+|project brief|case study submission)\b~iu',
        // Bare "assessment" is left out on purpose: "Risk assessment" is a topic, not an exam.
        self::ASSESSMENT => '~\b(exam|examination|exams|final assessment|summative assessment|assessment (task|event|\d+)|'
            . '(?<!practice )(?<!knowledge )(?<!self-)(?<!self )tests?|mid-?term|mock exam|final quiz|graded quiz)\b~iu',
        self::PRACTICE => '~\b(practice (questions?|quiz(zes)?|tests?|sets?|activit(y|ies)|round)|knowledge (check|test)|'
            . 'self[- ]?(check|test|assessment)|check your understanding|revision|review questions|quiz(zes)?|'
            . 'flash ?cards|drills?)\b~iu',
        self::RESOURCES => '~\b(resources?|readings?|reference (material|list|guide|documents?)|glossary|handouts?|'
            . 'downloads?|toolkit|templates?|study guide|library|further reading|useful links)\b~iu',
    ];

    /** @var string[] Purpose by module type, used only when the title and description say nothing. */
    private const MODULES = [
        'assign' => self::SUBMISSION, 'workshop' => self::SUBMISSION,
        'forum' => self::DISCUSSION, 'hsuforum' => self::DISCUSSION, 'chat' => self::DISCUSSION,
        'wiki' => self::DISCUSSION,
        'resource' => self::RESOURCES, 'folder' => self::RESOURCES, 'url' => self::RESOURCES,
        'glossary' => self::RESOURCES, 'data' => self::RESOURCES,
        'feedback' => self::FEEDBACK, 'questionnaire' => self::FEEDBACK, 'survey' => self::FEEDBACK,
        'choice' => self::FEEDBACK,
        'customcert' => self::COMPLETION, 'certificate' => self::COMPLETION, 'coursecertificate' => self::COMPLETION,
        'page' => self::CONTENT, 'book' => self::CONTENT, 'lesson' => self::CONTENT, 'scorm' => self::CONTENT,
        'h5pactivity' => self::CONTENT, 'hvp' => self::CONTENT, 'lti' => self::CONTENT, 'label' => self::CONTENT,
        'zoom' => self::OTHER, 'bigbluebuttonbn' => self::OTHER, 'googlemeet' => self::OTHER,
    ];

    /** @var string[] What the image should show for each purpose, for the planner. */
    public const GUIDANCE = [
        self::CONTENT => 'learning content: show the specific topic of THIS activity (not the section\'s general subject), '
            . 'the way a section card shows its subject',
        self::INSTRUCTIONS => 'instructions or orientation: show the learner getting ready for the task the instructions '
            . 'are about (for exam instructions: preparing for that exam), in a setting real for this qualification',
        self::ASSESSMENT => 'an assessment or examination: show the realistic assessment setting for this qualification '
            . 'and a candidate undertaking it (for example a computer-based professional exam, a practical observation, '
            . 'a written test), chosen from the title and content',
        self::PRACTICE => 'practice or a knowledge check: show a learner actively practising or checking their '
            . 'understanding of this topic, tied to the subject, without exam pressure',
        self::DISCUSSION => 'discussion or collaboration: show learners exchanging ideas about the subject, in person or '
            . 'online, as a genuine conversation',
        self::RESOURCES => 'resources or reference material: show the actual reference materials or tools of the subject '
            . 'in use',
        self::SUBMISSION => 'a submission or assignment: show the learner producing or finalising the work the task asks '
            . 'for, as the real work product of this field',
        self::FEEDBACK => 'feedback or a survey: show a learner giving considered feedback on their learning experience',
        self::COMPLETION => 'completion or certification: show achievement and recognition in this field',
        self::OTHER => 'judge from the title and content what the learner does here, and show that',
    ];

    /**
     * Classify an activity.
     *
     * @param string $modname Module name without mod_ (quiz, page...).
     * @param string $title The activity's name.
     * @param string $text Its description or content, plain text; may be empty.
     * @param bool|null $graded For a quiz: whether it carries marks (null when unknown).
     * @return array{purpose: string, basis: string} basis is title, description or type.
     */
    public static function classify(string $modname, string $title, string $text = '', ?bool $graded = null): array {
        foreach (['title' => $title, 'description' => \core_text::substr($text, 0, 400)] as $basis => $words) {
            if (trim($words) === '') {
                continue;
            }
            foreach (self::RULES as $purpose => $pattern) {
                if (preg_match($pattern, $words) === 1) {
                    return ['purpose' => $purpose, 'basis' => $basis];
                }
            }
        }
        if ($modname === 'quiz') {
            return ['purpose' => $graded === false ? self::PRACTICE : self::ASSESSMENT, 'basis' => 'type'];
        }
        return ['purpose' => self::MODULES[$modname] ?? self::OTHER, 'basis' => 'type'];
    }

    /**
     * Whether a quiz carries marks, or null when it cannot be told.
     *
     * @param \cm_info $cm The activity.
     * @return bool|null
     */
    public static function quiz_graded(\cm_info $cm): ?bool {
        global $DB;
        if ($cm->modname !== 'quiz') {
            return null;
        }
        try {
            $grade = $DB->get_field('quiz', 'grade', ['id' => $cm->instance]);
            return $grade === false ? null : ((float) $grade > 0);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The activity's own content when it has no description: a page's text, a book's first chapter.
     *
     * @param \cm_info $cm The activity.
     * @return string Plain text, or ''.
     */
    public static function content_text(\cm_info $cm): string {
        global $DB;
        try {
            if ($cm->modname === 'page') {
                $html = (string) $DB->get_field('page', 'content', ['id' => $cm->instance]);
            } else if ($cm->modname === 'book') {
                $chapters = $DB->get_records('book_chapters', ['bookid' => $cm->instance, 'hidden' => 0], 'pagenum', 'content',
                    0, 1);
                $html = $chapters ? (string) reset($chapters)->content : '';
            } else {
                return '';
            }
        } catch (\Throwable $e) {
            return '';
        }
        return cardprompt::excerpt(cardprompt::html_plain($html));
    }
}
