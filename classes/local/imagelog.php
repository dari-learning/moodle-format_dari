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
 * Diagnostics log for AI image generation and browser errors.
 *
 * Every image job records each stage with its duration: queued, started (time waiting for cron),
 * course plan, item plan, prompt, image request, save, done or failed. Browser errors on Dari
 * course pages (JavaScript errors, unhandled promise rejections, console errors and failed Dari
 * web-service calls) are recorded for editing teachers as stage "browser". The Image plan preview
 * page shows the log and downloads it as CSV, so slow stages and failures can be seen, not guessed.
 *
 * Kept for 30 days.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imagelog {
    /** @var string Table. */
    public const TABLE = 'format_dari_imagelog';

    /** @var int Seconds an entry is kept. */
    public const KEEP = 30 * DAYSECS;

    /** @var string|null The request being processed in this PHP process, so nested code can log to it. */
    public static ?string $requestid = null;

    /** @var string The item being processed. */
    public static string $itemkey = '';

    /** @var int The course being processed. */
    public static int $courseid = 0;

    /** @var int Inserts this request, to purge old rows now and then. */
    protected static int $inserts = 0;

    /**
     * Start logging for one job in this process.
     *
     * @param int $courseid The course.
     * @param string $itemkey section:{id}, cm:{id}, banner or section banner key.
     * @param string $requestid The job's request id.
     * @return void
     */
    public static function begin(int $courseid, string $itemkey, string $requestid): void {
        self::$courseid = $courseid;
        self::$itemkey = $itemkey;
        self::$requestid = $requestid;
    }

    /**
     * Stop logging for the current job.
     *
     * @return void
     */
    public static function end(): void {
        self::$requestid = null;
        self::$itemkey = '';
        self::$courseid = 0;
    }

    /**
     * Record one stage.
     *
     * @param string $stage Stage name.
     * @param string $status ok, fail or info.
     * @param int $durationms Duration in milliseconds, or 0.
     * @param string $message Detail.
     * @param int|null $courseid Course, or null for the current job's.
     * @param string|null $itemkey Item, or null for the current job's.
     * @param string|null $requestid Request id, or null for the current job's.
     * @return void
     */
    public static function add(string $stage, string $status = 'ok', int $durationms = 0, string $message = '',
            ?int $courseid = null, ?string $itemkey = null, ?string $requestid = null): void {
        global $DB, $USER;
        if (get_config('format_dari', 'diagnostics') === '0') {
            return;
        }
        $courseid = $courseid ?? self::$courseid;
        if ($courseid <= 0) {
            return;
        }
        try {
            $DB->insert_record(self::TABLE, (object) [
                'courseid' => $courseid,
                'itemkey' => \core_text::substr($itemkey ?? self::$itemkey, 0, 40),
                'requestid' => \core_text::substr($requestid ?? (string) self::$requestid, 0, 36),
                'stage' => \core_text::substr($stage, 0, 30),
                'status' => \core_text::substr($status, 0, 10),
                'durationms' => max(0, $durationms),
                'message' => \core_text::substr($message, 0, 4000),
                'userid' => isset($USER->id) ? (int) $USER->id : 0,
                'timecreated' => time(),
            ]);
            if (++self::$inserts % 50 === 1) {
                $DB->delete_records_select(self::TABLE, 'timecreated < ?', [time() - self::KEEP]);
            }
        } catch (\Throwable $e) {
            // Diagnostics must never break generation.
            debugging('format_dari image log write failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Run a callable and record its duration as a stage; failures are recorded and rethrown.
     *
     * @param string $stage Stage name.
     * @param callable $fn The work.
     * @param string $message Detail recorded on success.
     * @return mixed The callable's result.
     */
    public static function time(string $stage, callable $fn, string $message = '') {
        $start = microtime(true);
        try {
            $result = $fn();
            self::add($stage, 'ok', (int) round((microtime(true) - $start) * 1000), $message);
            return $result;
        } catch (\Throwable $e) {
            self::add($stage, 'fail', (int) round((microtime(true) - $start) * 1000), $e->getMessage());
            throw $e;
        }
    }

    /**
     * The newest entries for a course.
     *
     * @param int $courseid The course.
     * @param int $limit Most rows.
     * @return \stdClass[]
     */
    public static function recent(int $courseid, int $limit = 300): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['courseid' => $courseid], 'timecreated DESC, id DESC', '*', 0, $limit);
    }

    /**
     * Group entries into jobs: request id => summary with stages and total time.
     *
     * @param \stdClass[] $rows Log rows, newest first.
     * @return array
     */
    public static function jobs(array $rows): array {
        $jobs = [];
        foreach (array_reverse($rows) as $row) {
            if ($row->stage === 'browser' || $row->requestid === '') {
                continue;
            }
            $id = $row->requestid;
            if (!isset($jobs[$id])) {
                $jobs[$id] = ['requestid' => $id, 'itemkey' => $row->itemkey, 'start' => (int) $row->timecreated,
                    'end' => (int) $row->timecreated, 'stages' => [], 'result' => ''];
            }
            $jobs[$id]['end'] = max($jobs[$id]['end'], (int) $row->timecreated);
            $jobs[$id]['stages'][] = $row;
            if (in_array($row->stage, ['done', 'failed'], true)) {
                $jobs[$id]['result'] = $row->stage;
            }
        }
        return array_reverse($jobs, true);
    }

    /**
     * Forget a course's log.
     *
     * @param int $courseid The course.
     * @return void
     */
    public static function forget(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }
}
