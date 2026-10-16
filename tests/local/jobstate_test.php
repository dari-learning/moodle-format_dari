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

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\jobstate::class)]
#[\PHPUnit\Framework\Attributes\CoversFunction('format_dari_unset_config_prefixed')]
#[\PHPUnit\Framework\Attributes\CoversFunction('format_dari_move_job_state_out_of_config')]
/**
 * Tests for the AI image job state table.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\jobstate
 */
final class jobstate_test extends \advanced_testcase {
    /**
     * A row is created on first save, updated on the next, and never touches plugin config.
     */
    public function test_save_and_get(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);
        $configbefore = (array) get_config('format_dari');

        $this->assertNull(jobstate::get($course->id, cardimage::TYPE_SECTION, 7));
        jobstate::save($course->id, cardimage::TYPE_SECTION, 7, ['state' => 'queued', 'requestid' => 'abc',
            'timequeued' => 100, 'notafield' => 'ignored']);
        $row = jobstate::get($course->id, cardimage::TYPE_SECTION, 7);
        $this->assertSame('queued', $row->state);
        $this->assertSame('abc', $row->requestid);
        $this->assertEquals(100, $row->timequeued);
        $this->assertObjectNotHasProperty('notafield', $row);

        jobstate::save($course->id, cardimage::TYPE_SECTION, 7, ['state' => 'running', 'stage' => 'image']);
        $row = jobstate::get($course->id, cardimage::TYPE_SECTION, 7);
        $this->assertSame('running', $row->state);
        $this->assertSame('image', $row->stage);
        $this->assertSame('abc', $row->requestid, 'Fields not passed are kept.');

        global $DB;
        $this->assertSame(1, $DB->count_records(jobstate::TABLE, ['courseid' => $course->id]));
        $this->assertEquals($configbefore, (array) get_config('format_dari'));
    }

    /**
     * The status API of cardimage reads and writes the table.
     */
    public function test_cardimage_status_round_trip(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);

        $this->assertSame('idle', cardimage::get_status($course->id, cardimage::TYPE_CM, 3)['state']);
        cardimage::set_status($course->id, cardimage::TYPE_CM, 3, 'running', '', ['requestid' => 'req1', 'stage' => 'planning']);
        $status = cardimage::get_status($course->id, cardimage::TYPE_CM, 3);
        $this->assertSame('running', $status['state']);
        $this->assertNotNull(jobstate::get($course->id, cardimage::TYPE_CM, 3));

        cardimage::set_prompt($course->id, cardimage::TYPE_CM, 3, 'A prompt', 'plan');
        $this->assertSame('A prompt', cardimage::get_prompt($course->id, cardimage::TYPE_CM, 3)['prompt']);

        cardimage::clear_status($course->id, cardimage::TYPE_CM, 3);
        $this->assertSame('idle', cardimage::get_status($course->id, cardimage::TYPE_CM, 3)['state']);
    }

    /**
     * Only queued or running card jobs of the course, changed since the cut-off, count as running.
     */
    public function test_running_cards(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);
        $other = $this->getDataGenerator()->create_course(['format' => 'dari']);

        jobstate::save($course->id, cardimage::TYPE_SECTION, 1, ['state' => 'queued']);
        jobstate::save($course->id, cardimage::TYPE_CM, 2, ['state' => 'running']);
        jobstate::save($course->id, cardimage::TYPE_CM, 3, ['state' => 'done']);
        jobstate::save($course->id, jobstate::TYPE_BANNER, 0, ['state' => 'running']);
        jobstate::save($course->id, cardimage::TYPE_CM, 4, ['state' => 'running']);
        $DB->set_field(jobstate::TABLE, 'timemodified', time() - 3600, ['courseid' => $course->id, 'targetid' => 4]);
        jobstate::save($other->id, cardimage::TYPE_CM, 5, ['state' => 'running']);

        $running = jobstate::running_cards($course->id, time() - 600);
        usort($running, fn($a, $b) => $a['id'] <=> $b['id']);
        $this->assertSame([['type' => 'section', 'id' => 1], ['type' => 'cm', 'id' => 2]], $running);
    }

    /**
     * Deleting a target or a course removes its rows and nothing else.
     */
    public function test_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);
        $other = $this->getDataGenerator()->create_course(['format' => 'dari']);
        jobstate::save($course->id, cardimage::TYPE_CM, 1, ['state' => 'done']);
        jobstate::save($course->id, cardimage::TYPE_CM, 2, ['state' => 'done']);
        jobstate::save($other->id, cardimage::TYPE_CM, 1, ['state' => 'done']);

        jobstate::delete($course->id, cardimage::TYPE_CM, 1);
        $this->assertNull(jobstate::get($course->id, cardimage::TYPE_CM, 1));
        $this->assertNotNull(jobstate::get($course->id, cardimage::TYPE_CM, 2));

        jobstate::delete_course($course->id);
        $this->assertSame(0, $DB->count_records(jobstate::TABLE, ['courseid' => $course->id]));
        $this->assertSame(1, $DB->count_records(jobstate::TABLE, ['courseid' => $other->id]));
    }

    /**
     * The 2.1.2 upgrade moves stored prompts out of plugin config and forgets the old job state
     * values, through the config API only.
     */
    public function test_upgrade_moves_job_state_out_of_config(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/format/dari/db/upgradelib.php');
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);

        set_config('imgprompt_' . $course->id . '_s12', json_encode(['prompt' => 'Section prompt',
            'source' => 'plan', 'time' => 50]), 'format_dari');
        set_config('imgprompt_' . $course->id . '_b0', json_encode(['prompt' => 'Banner prompt',
            'source' => 'template', 'time' => 60]), 'format_dari');
        set_config('imgprompt_999999_c1', json_encode(['prompt' => 'Gone course']), 'format_dari');
        set_config('cardstatus_' . $course->id . '_cm_4', json_encode(['state' => 'running']), 'format_dari');
        set_config('bannerstatus_' . $course->id . '_0', 'running', 'format_dari');
        set_config('maxcontextchars', '1234', 'format_dari');

        format_dari_move_job_state_out_of_config();

        $section = jobstate::get($course->id, cardimage::TYPE_SECTION, 12);
        $this->assertSame('Section prompt', $section->prompt);
        $this->assertSame('plan', $section->promptsource);
        $this->assertEquals(50, $section->prompttime);
        $this->assertSame('Banner prompt', jobstate::get($course->id, jobstate::TYPE_BANNER, 0)->prompt);
        $this->assertSame(0, $DB->count_records(jobstate::TABLE, ['courseid' => 999999]));

        $config = (array) get_config('format_dari');
        foreach (array_keys($config) as $name) {
            $this->assertDoesNotMatchRegularExpression('/^(imgprompt|cardstatus|bannerstatus)_/', $name);
        }
        $this->assertSame('1234', $config['maxcontextchars'], 'Other settings are untouched.');
    }

    /**
     * The prefix helper unsets only the named prefixes.
     */
    public function test_unset_config_prefixed(): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/format/dari/db/upgradelib.php');
        $this->resetAfterTest();
        set_config('artscenes_5', 'x', 'format_dari');
        set_config('artdirection_5', 'y', 'format_dari');
        set_config('artistic', 'z', 'format_dari');

        $removed = format_dari_unset_config_prefixed(['artscenes_', 'artdirection_']);
        sort($removed);
        $this->assertSame(['artdirection_5', 'artscenes_5'], $removed);
        $this->assertFalse(get_config('format_dari', 'artscenes_5'));
        $this->assertSame('z', get_config('format_dari', 'artistic'));
    }
}
