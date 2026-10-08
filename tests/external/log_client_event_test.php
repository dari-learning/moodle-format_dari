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

namespace format_dari\external;

use format_dari\local\imagelog;

/**
 * Tests for recording browser errors in the diagnostics log.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\external\log_client_event
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\external\log_client_event::class)]
final class log_client_event_test extends \advanced_testcase {
    /**
     * A real stack trace (with <anonymous> frames) is stored as sent; learners cannot write to the
     * log; and the per-minute limit holds.
     */
    public function test_log_client_event(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'dari']);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($teacher);

        $stack = "boom\nError: boom\n    at <anonymous>:1:16\n    at UtilityScript.evaluate (<anonymous>:292:16)";
        $this->assertTrue(log_client_event::execute((int) $course->id, 'rejection', $stack)['logged']);
        $rows = array_values(imagelog::recent((int) $course->id));
        $this->assertCount(1, $rows);
        $this->assertSame('browser', $rows[0]->stage);
        $this->assertSame($stack, json_decode($rows[0]->message, true)['message']);
        $this->assertEquals($teacher->id, $rows[0]->userid);

        for ($i = 1; $i < log_client_event::PER_MINUTE; $i++) {
            log_client_event::execute((int) $course->id, 'console', 'message ' . $i);
        }
        $this->assertFalse(log_client_event::execute((int) $course->id, 'console', 'one too many')['logged']);

        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        log_client_event::execute((int) $course->id, 'error', 'from a learner');
    }
}
