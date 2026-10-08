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

namespace format_dari;

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari::class)]
/**
 * Every language string the plugin asks for must exist.
 *
 * Note A missing string is not a cosmetic fault: Moodle's settings page calls
 * get_string() while building the form, so one absent key fills the page with debugging output and
 * can stop an administrator reaching the settings at all.
 *
 * It is also the easiest kind of mistake to make. Strings are added by hand alongside the code that
 * uses them, and a rename or a bad merge separates the two silently -- nothing fails until someone
 * opens the page.
 *
 * This walks the plugin's own source for `get_string('x', 'format_dari')` and asserts each key
 * is defined, which turns "someone will notice eventually" into a test failure.
 *
 * @package    format_dari
 * @covers     \format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lang_strings_test extends \advanced_testcase {
    /**
     * Every string referenced in the plugin's PHP is defined in the language file.
     *
     * @return void
     */
    public function test_referenced_strings_exist(): void {
        global $CFG;
        $root = $CFG->dirroot . '/course/format/dari';

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $referenced = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // The tests themselves are excluded: a test may reference a deliberately absent key to
            // check the failure path, and that is not a fault in the plugin.
            if (strpos($file->getPathname(), '/tests/') !== false) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $pattern = '/get_string\(\s*[\'"]([a-zA-Z0-9_:.\-]+)[\'"]\s*,\s*[\'"]format_dari[\'"]/';
            if (preg_match_all($pattern, $source, $matches)) {
                foreach ($matches[1] as $key) {
                    $referenced[$key] = $file->getFilename();
                }
            }
        }

        $this->assertNotEmpty($referenced, 'No strings found to check - the scan itself is broken.');

        $manager = get_string_manager();
        $missing = [];
        foreach ($referenced as $key => $where) {
            if (!$manager->string_exists($key, 'format_dari')) {
                $missing[] = $key . ' (used in ' . $where . ')';
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Language strings are referenced but not defined:\n  " . implode("\n  ", $missing)
        );
    }

    /**
     * Every error code the plugin throws, and every string it names in JavaScript or templates,
     * is defined.
     *
     * An exception's error code is a string key too: a missing one reaches the user as
     * "[[error_x]]". get_string() calls are covered above; this covers moodle_exception error
     * codes, the "_detail" variants \format_dari\local\ai builds, {{#str}} in templates and
     * string requests in the AMD sources.
     *
     * @return void
     */
    public function test_error_codes_and_client_strings_exist(): void {
        global $CFG;
        $root = $CFG->dirroot . '/course/format/dari';
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $patterns = [
            'php' => [
                '/moodle_exception\(\s*[\'"]([a-zA-Z0-9_:.\-]+)[\'"]\s*,\s*[\'"]format_dari[\'"]/',
                '/lang_string\(\s*[\'"]([a-zA-Z0-9_:.\-]+)[\'"]\s*,\s*[\'"]format_dari[\'"]/',
            ],
            'mustache' => ['/\{\{#(?:str|cleanstr)\}\}\s*([a-zA-Z0-9_:.\-]+)\s*,\s*format_dari\b/'],
            'js' => [
                '/key:\s*[\'"]([a-zA-Z0-9_:.\-]+)[\'"]\s*,\s*component:\s*[\'"]format_dari[\'"]/',
                '/getString\(\s*[\'"]([a-zA-Z0-9_:.\-]+)[\'"]\s*,\s*[\'"]format_dari[\'"]/',
            ],
        ];

        $referenced = [];
        foreach ($files as $file) {
            $path = $file->getPathname();
            $ext = $file->getExtension();
            if (!isset($patterns[$ext]) || strpos($path, '/tests/') !== false || strpos($path, '/amd/build/') !== false) {
                continue;
            }
            $source = file_get_contents($path);
            foreach ($patterns[$ext] as $pattern) {
                if (preg_match_all($pattern, $source, $matches)) {
                    foreach ($matches[1] as $key) {
                        $referenced[$key] = $file->getFilename();
                    }
                }
            }
        }
        // Built from a fallback key in \format_dari\local\ai::throw_failure().
        $referenced['error_ai_textfailed_detail'] = 'ai.php';
        $referenced['error_ai_imagefailed_detail'] = 'ai.php';

        $this->assertNotEmpty($referenced);
        $manager = get_string_manager();
        $missing = [];
        foreach ($referenced as $key => $where) {
            if (!$manager->string_exists($key, 'format_dari')) {
                $missing[] = $key . ' (used in ' . $where . ')';
            }
        }
        $this->assertSame([], $missing, "Strings are referenced but not defined:\n  " . implode("\n  ", $missing));
    }

    /**
     * Strings for the removed vendor API are gone, and nothing still refers to them.
     *
     * @return void
     */
    public function test_removed_vendor_strings_are_not_referenced(): void {
        global $CFG;
        $root = $CFG->dirroot . '/course/format/dari';
        $removed = ['apikey', 'siteid', 'error_apinocredits', 'error_bannerhttp',
            'error_bannerunreachable', 'error_bannernoreason'];

        $manager = get_string_manager();
        foreach ($removed as $key) {
            $this->assertFalse($manager->string_exists($key, 'format_dari'), $key . ' should have been removed');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        $found = [];
        foreach ($files as $file) {
            $path = $file->getPathname();
            if (
                !in_array($file->getExtension(), ['php', 'js', 'mustache'], true)
                    || strpos($path, '/tests/') !== false || strpos($path, '/amd/build/') !== false
            ) {
                continue;
            }
            $source = file_get_contents($path);
            foreach ($removed as $key) {
                if (preg_match('/[\'"]' . preg_quote($key, '/') . '[\'"]/', $source)) {
                    $found[] = $key . ' in ' . substr($path, strlen($root) + 1);
                }
            }
            foreach (['format_dari/apikey', 'format_dari/siteid'] as $needle) {
                if (strpos($source, $needle) !== false) {
                    $found[] = $needle . ' in ' . substr($path, strlen($root) + 1);
                }
            }
        }
        $this->assertSame([], $found);
        $this->assertFileDoesNotExist($root . '/deprecatedlib.php');
        $this->assertFileDoesNotExist($root . '/ajax.php');
        $this->assertFileDoesNotExist($root . '/classes/external/credentials.php');
    }

    /**
     * Every setting shown on the settings page has both a name and an explanation.
     *
     * A setting with a label and no description is not fatal, but it reaches an administrator as a
     * control with no indication of what it does -- and it is the same omission that produces a
     * missing-string error one line over.
     *
     * @return void
     */
    public function test_course_settings_are_described(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/course/format/dari/lib.php');

        // The course settings form declares each option with 'help' => 'name'.
        preg_match_all("/'help'\s*=>\s*'([a-zA-Z0-9_]+)'/", $source, $matches);
        $this->assertNotEmpty($matches[1], 'No course settings found to check.');

        $manager = get_string_manager();
        $undescribed = [];
        foreach (array_unique($matches[1]) as $name) {
            if (!$manager->string_exists($name . '_help', 'format_dari')) {
                $undescribed[] = $name . '_help';
            }
        }

        $this->assertSame(
            [],
            $undescribed,
            "Course settings declare help text that does not exist:\n  " . implode("\n  ", $undescribed)
        );
    }
}
