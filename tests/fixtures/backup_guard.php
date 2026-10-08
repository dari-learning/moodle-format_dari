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
 * Guard for backup tests on sites where another course format shadows this one in backups.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\tests;

/**
 * Keeps another course format from claiming Dari's backup data during a round-trip test.
 *
 * Core offers every installed format's backup plugin the same, non-multiple optigroup for every
 * course; the first element whose condition matches wins. A format plugin that sorts before
 * "dari" and requests its element WITHOUT the format condition matches for every course, so it
 * takes the section and activity data of Dari courses too -- format_aicourse, from which this
 * plugin was forked, does exactly that. Dari cannot prevent it from its own side.
 *
 * So that a round-trip test exercises Dari's own backup and restore code on such a site, the
 * offending plugins are removed from core_component's plugin list for the duration of the test,
 * in the same way core's advanced_testcase::add_mocked_plugin() adds one. core_component is reset
 * after every test by advanced_testcase, which restores the real list.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait backup_guard {
    /**
     * Hide format plugins, sorted before Dari, whose backup element carries no format condition.
     *
     * @return string[] Names of the hidden plugins.
     */
    protected function hide_formats_that_shadow_dari_backups(): array {
        $formats = \core_component::get_plugin_list('format');
        $hidden = [];
        foreach ($formats as $name => $dir) {
            if (strcmp($name, 'dari') >= 0) {
                continue;
            }
            $file = $dir . '/backup/moodle2/backup_format_' . $name . '_plugin.class.php';
            if (is_readable($file) && preg_match('/get_plugin_element\(\s*\)/', (string) file_get_contents($file))) {
                $hidden[] = $name;
            }
        }
        if ($hidden) {
            $component = new \ReflectionClass(\core_component::class);
            $plugins = $component->getStaticPropertyValue('plugins');
            foreach ($hidden as $name) {
                unset($plugins['format'][$name]);
            }
            $component->setStaticPropertyValue('plugins', $plugins);
        }
        return $hidden;
    }
}
