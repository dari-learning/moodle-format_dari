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
 * Database upgrade steps for the Dari course format.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the Dari course format database schema.
 *
 * 1.0.0 is the first release; the full schema is created from install.xml. Future schema
 * changes are added here, each guarded by its version number and followed by a savepoint.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_format_dari_upgrade($oldversion) {
    if ($oldversion < 2026100805) {
        // The AI art director for images is now on by default. Earlier releases shipped it off,
        // so switch it on for sites that still have that original default.
        if ((string) get_config('format_dari', 'aiscenewriter') === '0') {
            set_config('aiscenewriter', 1, 'format_dari');
        }
        upgrade_plugin_savepoint(true, 2026100805, 'format', 'dari');
    }
    if ($oldversion < 2026100807) {
        // Stronger defaults for images and for the model that writes image prompts. Only sites
        // still on the earlier shipped defaults are moved; a model an admin chose is left alone.
        $moves = [
            'directtextmodel' => ['gpt-4o-mini', 'gpt-6-astra'],
            'directimagemodel' => ['gpt-image-1', 'gpt-image-2.5-sunburst'],
            'imagequality' => ['standard', 'hd'],
        ];
        foreach ($moves as $name => [$old, $new]) {
            if ((string) get_config('format_dari', $name) === $old) {
                set_config($name, $new, 'format_dari');
            }
        }
        upgrade_plugin_savepoint(true, 2026100807, 'format', 'dari');
    }
    return true;
}
