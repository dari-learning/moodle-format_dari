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

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\cardprompt::class)]
/**
 * Tests for the plugin-written card image prompt.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\cardprompt
 */
final class cardprompt_test extends \advanced_testcase {
    /**
     * Card images and colours are cached per request; each test starts clean.
     */
    protected function setUp(): void {
        parent::setUp();
        cardimage::reset_cache();
    }

    /**
     * An activity card's prompt is a described scene, the teacher's words, then the shared tail.
     */
    public function test_activity_prompt_is_a_scene_with_tail(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'dari',
            'fullname' => 'BSB50420 Diploma of Leadership and Management',
            'numsections' => 1,
        ]);
        course_get_format($course)->update_course_format_options([
            'id' => $course->id,
            'cardimagestyle' => 'photo',
            'accentcolour' => '#0F766E',
        ]);
        $page = $this->getDataGenerator()->create_module(
            'page',
            [
                'course' => $course->id,
                'section' => 1,
                'name' => 'Leading change',
                'intro' => '<p>How leaders guide <b>teams</b> through&nbsp;change.</p>'
                    . '<p>Includes <a href="https://x.test">links</a>.</p>',
            ]
        );
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);

        $out = cardprompt::compose(get_course($course->id), cardimage::TYPE_CM, $cm, 'sunlight through windows');
        $prompt = $out['prompt'];

        $this->assertSame('card-5', $out['promptVersion']);
        $this->assertSame('page', $out['brief']['sceneKey']);
        $this->assertStringStartsWith('An adult learner reading a printed guide in a comfortable armchair', $prompt);
        $this->assertStringContainsString('Sunlight through windows.', $prompt);
        $this->assertStringContainsString(
            'a lesson on Leading change in a Diploma of Leadership and Management course',
            $prompt
        );
        $this->assertStringEndsWith("\n\n" . $out['promptTail'], $prompt);

        // The tail: medium, accent colour in words, 16:9 composition, no text.
        $this->assertStringStartsWith('Photorealistic, high-end documentary and editorial photography', $out['promptTail']);
        $this->assertStringContainsString('deep teal appears as a subtle accent', $out['promptTail']);
        $this->assertStringContainsString('16:9', $out['promptTail']);
        $this->assertStringContainsString('minimal, clean lettering; no captions, logos or watermarks', $out['promptTail']);
        // Cards carry no title over the image, so no room is asked for one.
        $this->assertStringNotContainsString('title overlay', $prompt);

        // Nothing that made the 2.0.0 images generic.
        $this->assertStringNotContainsString('laptop', $prompt);
        $this->assertStringNotContainsString('Include subtle visual references', $prompt);
        $this->assertStringNotContainsString('Create a premium', $prompt);
        $this->assertStringNotContainsString('BSB50420', $prompt);
        $this->assertStringNotContainsString('TEAMS', $prompt);
        $this->assertStringNotContainsString('"', $prompt);
        $this->assertStringNotContainsString('<', $prompt);
        $this->assertStringContainsString('cartoon', $out['negativePrompt']);

        $this->assertSame('Leading change', $out['brief']['topic']);
        $this->assertSame('page', $out['brief']['activityType']);
        $this->assertSame('Diploma of Leadership and Management', $out['brief']['courseTopic']);
        $this->assertSame('business and leadership', $out['brief']['field']);
        $this->assertSame('adult learners', $out['brief']['audience']);
        $this->assertSame('#0F766E', $out['brief']['colourHex']);
        $this->assertSame('sunlight through windows', $out['brief']['teacherDirection']);
        $this->assertIsArray($out['brief']['courseSections']);
    }

    /**
     * A common title gets its own scene, and none of the common scenes is someone at a laptop.
     */
    public function test_common_title_gets_its_scene(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari', 'numsections' => 1]);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        course_update_section($course, $section, ['name' => 'Student instructions']);
        $section = get_fast_modinfo($course->id)->get_section_info(1);

        $out = cardprompt::compose(get_course($course->id), cardimage::TYPE_SECTION, $section, '');
        $this->assertSame('instructions', $out['brief']['sceneKey']);
        $this->assertStringContainsString('wall planner', $out['prompt']);
        $this->assertStringContainsString('The image introduces part of a', $out['prompt']);

        foreach (
            ['Welcome', 'Student instructions', 'Quiz', 'Assessment 1', 'Forum', 'Resources', 'Key dates',
                'Live sessions', 'Videos', 'Feedback', 'Glossary', 'Reflection', 'Student support'] as $title
        ) {
            [$key, $scene] = cardprompt::scene($title, '');
            $this->assertNotSame('general', $key, $title);
            $this->assertStringNotContainsString('laptop', $scene, $title);
            $this->assertStringNotContainsString('{', $scene, $title);
        }
    }

    /**
     * Scene matching by title, then by activity type.
     */
    public function test_scene_matching(): void {
        $this->assertSame('welcome', cardprompt::scene('Welcome to the course', '')[0]);
        $this->assertSame('quiz', cardprompt::scene('Knowledge check', 'page')[0]);
        $this->assertSame('assessment', cardprompt::scene('Assessment 2 - Written questions', '')[0]);
        $this->assertSame('forum', cardprompt::scene('Introduce yourself', '')[0]);
        $this->assertSame('certificate', cardprompt::scene('Certificate of completion', '')[0]);
        $this->assertSame('resources', cardprompt::scene('Learning materials', '')[0]);

        $this->assertSame('quiz', cardprompt::scene('Leading change', 'quiz')[0]);
        $this->assertSame('live', cardprompt::scene('Leading change', 'zoom')[0]);

        $this->assertSame('general', cardprompt::scene('Construction materials', '')[0]);
        $this->assertSame('general', cardprompt::scene('Providing client support', '')[0]);
        $this->assertSame('general', cardprompt::scene('Working in community services', '')[0]);
        $this->assertSame('general', cardprompt::scene('Testing and tagging', '')[0]);
        $this->assertSame('general', cardprompt::scene('Leading effective workplace relationships', '')[0]);
        $this->assertSame('general', cardprompt::scene('Practical first aid', '')[0]);
        $this->assertSame('workplace', cardprompt::scene('Workplace assessment', '')[0]);
        $this->assertSame('workplace', cardprompt::scene('Practical tasks', '')[0]);

        $this->assertStringContainsString('a secondary school student', cardprompt::scene('Reflection', '', true)[1]);
    }

    /**
     * The card's own colour wins over the accent; a section named only by number uses its summary.
     */
    public function test_section_prompt_uses_card_colour_and_summary(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'dari',
            'fullname' => 'Year 10 Biology',
            'numsections' => 1,
        ]);
        course_get_format($course)->update_course_format_options([
            'id' => $course->id,
            'cardimagestyle' => 'flat',
            'accentcolour' => '#0F766E',
        ]);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $DB->set_field(
            'course_sections',
            'summary',
            '<p>Managing risk on a building site. More here.</p>',
            ['id' => $section->id]
        );
        $DB->set_field('course_sections', 'name', 'Week 3', ['id' => $section->id]);
        cardimage::set_colour((int) $course->id, cardimage::TYPE_SECTION, (int) $section->id, '#B91C1C');
        rebuild_course_cache($course->id, true);
        cardimage::reset_cache();
        $section = get_fast_modinfo($course->id)->get_section_info(1);

        $out = cardprompt::compose(get_course($course->id), cardimage::TYPE_SECTION, $section, '');
        $prompt = $out['prompt'];

        $this->assertStringStartsWith('A secondary school student', $prompt);
        $this->assertStringContainsString('a lesson on Managing risk on a building site', $prompt);
        $this->assertStringNotContainsString('Week 3', $prompt);
        $this->assertStringStartsWith('Flat vector illustration', $out['promptTail']);
        $this->assertStringContainsString('red appears as a subtle accent', $out['promptTail']);
        $this->assertSame('#B91C1C', $out['brief']['colourHex']);
        $this->assertStringNotContainsString('cartoon', $out['negativePrompt']);
        $this->assertSame('school students', $out['brief']['audience']);
    }

    /**
     * A section with only a number and no summary falls back to the course, without treating the
     * course name as a housekeeping title.
     */
    public function test_untitled_section_does_not_match_course_words(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'dari',
            'fullname' => 'US CPA Exam Preparation',
            'numsections' => 1,
        ]);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $DB->set_field('course_sections', 'name', 'Week 3', ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        $section = get_fast_modinfo($course->id)->get_section_info(1);

        $out = cardprompt::compose(get_course($course->id), cardimage::TYPE_SECTION, $section, '');
        $this->assertNotSame('quiz', $out['brief']['sceneKey'], '"Exam" in the course name is not a quiz');
        $this->assertSame('field:accounting', $out['brief']['sceneKey']);
    }

    /**
     * Banners: the course banner is about the course, a section banner about its section.
     */
    public function test_banner_prompts(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'dari',
            'fullname' => 'CPC30220 - Certificate III in Carpentry',
            'summary' => '<p>Build <strong>framing</strong>, stairs and formwork.</p>',
            'numsections' => 1,
        ]);
        course_get_format($course)->update_course_format_options([
            'id' => $course->id,
            'cardimagestyle' => 'render3d',
            'accentcolour' => '#2563EB',
        ]);

        $out = cardprompt::compose_banner(get_course($course->id), null, 'golden hour');
        $this->assertSame('banner-4', $out['promptVersion']);
        $this->assertSame('banner', $out['brief']['imageKind']);
        $this->assertSame('field:construction', $out['brief']['sceneKey']);
        $this->assertStringStartsWith('A construction crew at work', $out['prompt']);
        $this->assertStringContainsString('Golden hour.', $out['prompt']);
        $this->assertStringContainsString('a construction and trades course', $out['prompt']);
        $this->assertStringNotContainsString('CPC30220', $out['prompt']);
        $this->assertStringStartsWith('Polished 3D render', $out['promptTail']);
        $this->assertStringContainsString('left third calm and simple', $out['promptTail']);
        $this->assertStringContainsString('blue appears as a subtle accent', $out['promptTail']);

        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $DB->set_field('course_sections', 'name', 'Welcome', ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $out = cardprompt::compose_banner(get_course($course->id), $section, '');
        $this->assertSame('welcome', $out['brief']['sceneKey']);
        $this->assertStringContainsString('greeting', $out['prompt']);
    }

    /**
     * Long names and descriptions never push the tail out.
     */
    public function test_prompt_stays_within_limit(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari', 'numsections' => 1]);
        $page = $this->getDataGenerator()->create_module(
            'page',
            [
                'course' => $course->id,
                'section' => 1,
                'name' => str_repeat('Long name ', 25),
                'intro' => str_repeat('Detail sentence here. ', 200),
            ]
        );
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);
        $out = cardprompt::compose(get_course($course->id), cardimage::TYPE_CM, $cm, str_repeat('x ', 1200));

        $this->assertLessThanOrEqual(
            cardprompt::PROMPT_MAX + 2 + \core_text::strlen($out['promptTail']),
            \core_text::strlen($out['prompt'])
        );
        $this->assertStringEndsWith($out['promptTail'], $out['prompt']);
    }

    /**
     * Title helpers.
     */
    public function test_title_helpers(): void {
        $this->assertTrue(cardprompt::is_numbered_only('Week 3'));
        $this->assertTrue(cardprompt::is_numbered_only('Topic 12:'));
        $this->assertFalse(cardprompt::is_numbered_only('Week 3: Safety'));
        $this->assertSame('Risk management', cardprompt::strip_number_prefix('Module 3: Risk management'));
        $this->assertSame('Week 3', cardprompt::strip_number_prefix('Week 3'));
        $this->assertSame('Diploma of Leadership', cardprompt::course_topic('BSB50420 Diploma of Leadership'));
        $this->assertSame('Year 10 Biology', cardprompt::course_topic('Year 10 Biology'));
        $this->assertSame('Bold and plain', cardprompt::html_plain('<p><b>Bold</b>&nbsp;and</p><p>plain</p>'));
    }

    /**
     * Colour words.
     */
    public function test_colour_name(): void {
        $this->assertSame('deep teal', cardprompt::colour_name('#0F766E'));
        $this->assertSame('teal', cardprompt::colour_name('#14B8A6'));
        $this->assertSame('red', cardprompt::colour_name('#DC2626'));
        $this->assertSame('blue', cardprompt::colour_name('#2563EB'));
        $this->assertSame('deep blue', cardprompt::colour_name('#172554'));
        $this->assertSame('pale golden yellow', cardprompt::colour_name('#FEF08A'));
        $this->assertSame('slate grey', cardprompt::colour_name('#64748B'));
        $this->assertSame('charcoal', cardprompt::colour_name('#111'));
    }
}
