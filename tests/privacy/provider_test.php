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
 * Privacy provider tests for format_dari.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\privacy;

use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\privacy\provider::class)]
/**
 * Privacy provider test for format_dari.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var stdClass A course using the Dari course format. */
    protected $course1;

    /** @var stdClass A second course using the Dari course format. */
    protected $course2;

    /** @var stdClass A student with chat data in both courses. */
    protected $student1;

    /** @var stdClass A student with chat data in course1 only. */
    protected $student2;

    /** @var stdClass A teacher who has corrected one of student1's responses in course1. */
    protected $teacher;

    /** @var stdClass A user with no plugin data at all. */
    protected $outsider;

    /**
     * Create the courses, users and chat data used by every test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $this->course1 = $generator->create_course(['format' => 'dari']);
        $this->course2 = $generator->create_course(['format' => 'dari']);

        $this->student1 = $generator->create_user();
        $this->student2 = $generator->create_user();
        $this->teacher = $generator->create_user();
        $this->outsider = $generator->create_user();

        $generator->enrol_user($this->student1->id, $this->course1->id, 'student');
        $generator->enrol_user($this->student1->id, $this->course2->id, 'student');
        $generator->enrol_user($this->student2->id, $this->course1->id, 'student');
        $generator->enrol_user($this->teacher->id, $this->course1->id, 'editingteacher');

        // Student1 asks two questions in course1 and one in course2.
        $this->create_chat($this->course1->id, $this->student1->id, 'What is a hazard?', 'A hazard is...');
        $corrected = $this->create_chat(
            $this->course1->id,
            $this->student1->id,
            'How do I structure the report?',
            'Start with...'
        );
        $this->create_chat($this->course2->id, $this->student1->id, 'Explain the cycle.', 'The cycle is...');

        // Student2 asks one question in course1.
        $this->create_chat($this->course1->id, $this->student2->id, 'When is this due?', 'Check the...');

        // The teacher corrects one of student1's responses. The teacher never asked a question.
        $this->correct_chat($corrected, $this->teacher->id, 'The correct structure is...');

        // Tutoring memory for both students.
        $this->create_memory($this->course1->id, $this->student1->id, 'Asked about hazards');
        $this->create_memory($this->course2->id, $this->student1->id, 'Asked about the cycle');
        $this->create_memory($this->course1->id, $this->student2->id, 'Asked about deadlines');
    }

    /**
     * Insert a chat record.
     *
     * @param int $courseid The course.
     * @param int $userid The user asking.
     * @param string $question The question text.
     * @param string $response The AI response text.
     * @return int The new record id.
     */
    protected function create_chat(int $courseid, int $userid, string $question, string $response): int {
        global $DB;

        $record = (object) [
            'courseid' => $courseid,
            'userid' => $userid,
            'activityid' => 0,
            'questionslot' => null,
            'question' => $question,
            'response' => $response,
            'rating' => 0,
            'refused' => 0,
            'locked' => 0,
            'timecreated' => time(),
        ];

        return $DB->insert_record('format_dari_chats', $record);
    }

    /**
     * Apply a teacher correction to an existing chat record.
     *
     * @param int $chatid The chat record.
     * @param int $teacherid The correcting teacher.
     * @param string $correction The correction text.
     */
    protected function correct_chat(int $chatid, int $teacherid, string $correction): void {
        global $DB;

        $DB->update_record('format_dari_chats', (object) [
            'id' => $chatid,
            'correction' => $correction,
            'correctedby' => $teacherid,
            'timecorrected' => time(),
        ]);
    }

    /**
     * Insert a tutoring memory record.
     *
     * @param int $courseid The course.
     * @param int $userid The user.
     * @param string $memory The memory text.
     * @return int The new record id.
     */
    protected function create_memory(int $courseid, int $userid, string $memory): int {
        global $DB;

        return $DB->insert_record('format_dari_ai_memory', (object) [
            'courseid' => $courseid,
            'activityid' => 1,
            'userid' => $userid,
            'memory' => $memory,
            'timeupdated' => time(),
        ]);
    }

    /**
     * The metadata declares the plugin's tables and links to core_ai.
     */
    public function test_get_metadata(): void {
        $collection = new collection('format_dari');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        $names = [];
        foreach ($items as $item) {
            $names[] = $item->get_name();
        }

        $this->assertContains('format_dari_chats', $names);
        $this->assertContains('format_dari_ai_memory', $names);
        $this->assertContains('format_dari_actminutes', $names);
        $this->assertContains('format_dari_cardstyle', $names);
        // Moodle 4.4 uses the configured external endpoint; newer versions use core AI.
        $coreai = \format_dari\local\ai::subsystem_present();
        $providername = $coreai ? 'core_ai' : 'dari_direct_ai';
        $this->assertContains($providername, $names);
        foreach ($items as $item) {
            if ($item->get_name() === $providername) {
                $providertype = $coreai ? \core_privacy\local\metadata\types\subsystem_link::class
                    : \core_privacy\local\metadata\types\external_location::class;
                $this->assertInstanceOf($providertype, $item);
                $this->assertSame('privacy:metadata:' . $providername, $item->get_summary());
            } else {
                $this->assertNotInstanceOf(\core_privacy\local\metadata\types\external_location::class, $item);
            }
            $this->assertTrue(
                get_string_manager()->string_exists($item->get_summary(), 'format_dari'),
                $item->get_summary()
            );
        }
    }

    /**
     * A user's contexts must be the course contexts they have data in, and nothing else.
     */
    public function test_get_contexts_for_userid(): void {
        $course1context = context_course::instance($this->course1->id);
        $course2context = context_course::instance($this->course2->id);

        // Student1 has data in both courses.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();
        sort($contextids);
        $expected = [$course1context->id, $course2context->id];
        sort($expected);
        $this->assertEquals($expected, $contextids);

        // Student2 has data in course1 only.
        $contextids = provider::get_contexts_for_userid($this->student2->id)->get_contextids();
        $this->assertEquals([$course1context->id], $contextids);

        // The teacher never asked a question, but wrote a correction in course1.
        $contextids = provider::get_contexts_for_userid($this->teacher->id)->get_contextids();
        $this->assertEquals([$course1context->id], $contextids);

        // The outsider has nothing.
        $this->assertEmpty(provider::get_contexts_for_userid($this->outsider->id)->get_contextids());
    }

    /**
     * The userlist for a course context must include askers and correctors, but no one else.
     */
    public function test_get_users_in_context(): void {
        $context = context_course::instance($this->course1->id);

        $userlist = new userlist($context, 'format_dari');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        sort($userids);
        $expected = [$this->student1->id, $this->student2->id, $this->teacher->id];
        sort($expected);

        $this->assertEquals($expected, $userids);
        $this->assertNotContains($this->outsider->id, $userids);
    }

    /**
     * Nothing must be returned for a context level the plugin does not use.
     */
    public function test_get_users_in_context_wrong_context_level(): void {
        $userlist = new userlist(\context_system::instance(), 'format_dari');
        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Export must write the user's own conversations and memory into each course context.
     */
    public function test_export_user_data(): void {
        $course1context = context_course::instance($this->course1->id);
        $course2context = context_course::instance($this->course2->id);

        $this->export_context_data_for_user($this->student1->id, $course1context, 'format_dari');

        $writer = writer::with_context($course1context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('privacy:path:chats', 'format_dari')]);
        $this->assertCount(2, $data->chats);
        $questions = array_map(function ($chat) {
            return $chat->question;
        }, $data->chats);
        $this->assertContains('What is a hazard?', $questions);
        $this->assertContains('How do I structure the report?', $questions);

        $memory = $writer->get_data([get_string('privacy:path:memory', 'format_dari')]);
        $this->assertCount(1, $memory->memory);
        $this->assertEquals('Asked about hazards', $memory->memory[0]->memory);

        // The second course must be exported separately and hold only its own data.
        writer::reset();
        $this->export_context_data_for_user($this->student1->id, $course2context, 'format_dari');

        $data = writer::with_context($course2context)->get_data([
            get_string('privacy:path:chats', 'format_dari'),
        ]);
        $this->assertCount(1, $data->chats);
        $this->assertEquals('Explain the cycle.', $data->chats[0]->question);
    }

    /**
     * A user who only wrote corrections must have those corrections exported.
     */
    public function test_export_user_data_for_corrector(): void {
        $context = context_course::instance($this->course1->id);

        $this->export_context_data_for_user($this->teacher->id, $context, 'format_dari');

        $data = writer::with_context($context)->get_data([
            get_string('privacy:path:corrections', 'format_dari'),
        ]);

        $this->assertCount(1, $data->corrections);
        $this->assertEquals('The correct structure is...', $data->corrections[0]->correction);

        // The teacher asked no questions, so there must be no conversation export.
        $chats = writer::with_context($context)->get_data([
            get_string('privacy:path:chats', 'format_dari'),
        ]);
        $this->assertEmpty((array) $chats);
    }

    /**
     * A user with no data must produce no export.
     */
    public function test_export_user_data_no_data(): void {
        $context = context_course::instance($this->course1->id);

        $this->export_context_data_for_user($this->outsider->id, $context, 'format_dari');

        $this->assertFalse(writer::with_context($context)->has_any_data());
    }

    /**
     * Deleting a course context must remove every user's data in that course, and only there.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $context = context_course::instance($this->course1->id);
        provider::delete_data_for_all_users_in_context($context);

        $this->assertEquals(0, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id]
        ));
        $this->assertEquals(0, $DB->count_records(
            'format_dari_ai_memory',
            ['courseid' => $this->course1->id]
        ));

        // Course 2 must be untouched.
        $this->assertEquals(1, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course2->id]
        ));
        $this->assertEquals(1, $DB->count_records(
            'format_dari_ai_memory',
            ['courseid' => $this->course2->id]
        ));
    }

    /**
     * A context level the plugin does not use must be ignored.
     */
    public function test_delete_data_for_all_users_in_context_wrong_context_level(): void {
        global $DB;

        $before = $DB->count_records('format_dari_chats');
        provider::delete_data_for_all_users_in_context(\context_system::instance());

        $this->assertEquals($before, $DB->count_records('format_dari_chats'));
    }

    /**
     * Deleting one user must remove their rows in the approved courses only.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $course1context = context_course::instance($this->course1->id);

        $contextlist = new approved_contextlist(
            $this->student1,
            'format_dari',
            [$course1context->id]
        );
        provider::delete_data_for_user($contextlist);

        // Student1's course1 data is gone.
        $this->assertEquals(0, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id, 'userid' => $this->student1->id]
        ));
        $this->assertEquals(0, $DB->count_records(
            'format_dari_ai_memory',
            ['courseid' => $this->course1->id, 'userid' => $this->student1->id]
        ));

        // Student1's course2 data was not approved for deletion and must remain.
        $this->assertEquals(1, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course2->id, 'userid' => $this->student1->id]
        ));

        // Student2 is unaffected.
        $this->assertEquals(1, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id, 'userid' => $this->student2->id]
        ));
    }

    /**
     * Deleting the correcting teacher must keep the student's row and drop only the attribution.
     */
    public function test_delete_data_for_user_removes_correction_attribution_only(): void {
        global $DB;

        $course1context = context_course::instance($this->course1->id);

        $before = $DB->count_records('format_dari_chats', ['courseid' => $this->course1->id]);

        $contextlist = new approved_contextlist(
            $this->teacher,
            'format_dari',
            [$course1context->id]
        );
        provider::delete_data_for_user($contextlist);

        // No row was destroyed: the corrected row belongs to student1.
        $this->assertEquals($before, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id]
        ));

        // No row still points at the teacher.
        $this->assertEquals(0, $DB->count_records(
            'format_dari_chats',
            ['correctedby' => $this->teacher->id]
        ));

        // The student's question and the correction text survive; the attribution does not.
        // `question` is a TEXT column, so it may not appear in a plain equality condition on.
        // Every supported database — sql_compare_text() is required.
        $select = 'courseid = :courseid AND userid = :userid AND '
            . $DB->sql_compare_text('question') . ' = ' . $DB->sql_compare_text(':question');
        $record = $DB->get_record_select('format_dari_chats', $select, [
            'courseid' => $this->course1->id,
            'userid' => $this->student1->id,
            'question' => 'How do I structure the report?',
        ]);
        $this->assertNotEmpty($record);
        $this->assertEquals('The correct structure is...', $record->correction);
        $this->assertNull($record->correctedby);
        $this->assertNull($record->timecorrected);
    }

    /**
     * Deleting several users at once in one context must behave like the single user path.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $context = context_course::instance($this->course1->id);

        $userlist = new approved_userlist(
            $context,
            'format_dari',
            [$this->student1->id, $this->teacher->id]
        );
        provider::delete_data_for_users($userlist);

        // Student1 is gone from course1 but not course2.
        $this->assertEquals(0, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id, 'userid' => $this->student1->id]
        ));
        $this->assertEquals(1, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course2->id, 'userid' => $this->student1->id]
        ));
        $this->assertEquals(0, $DB->count_records(
            'format_dari_ai_memory',
            ['courseid' => $this->course1->id, 'userid' => $this->student1->id]
        ));

        // The teacher's attribution is gone.
        $this->assertEquals(0, $DB->count_records(
            'format_dari_chats',
            ['correctedby' => $this->teacher->id]
        ));

        // Student2 is untouched.
        $this->assertEquals(1, $DB->count_records(
            'format_dari_chats',
            ['courseid' => $this->course1->id, 'userid' => $this->student2->id]
        ));
        $this->assertEquals(1, $DB->count_records(
            'format_dari_ai_memory',
            ['courseid' => $this->course1->id, 'userid' => $this->student2->id]
        ));
    }

    /**
     * A context level the plugin does not use must be ignored by the bulk delete.
     */
    public function test_delete_data_for_users_wrong_context_level(): void {
        global $DB;

        $before = $DB->count_records('format_dari_chats');

        $userlist = new approved_userlist(
            \context_system::instance(),
            'format_dari',
            [$this->student1->id]
        );
        provider::delete_data_for_users($userlist);

        $this->assertEquals($before, $DB->count_records('format_dari_chats'));
    }
}
