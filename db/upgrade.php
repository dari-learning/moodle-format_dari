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
    if ($oldversion < 2026100901) {
        global $DB;
        // Images are now painted with one model only (GPT Image 2.5 Sunburst) at high quality, and
        // the art director always writes the prompt when the site has a text model. The settings
        // that chose another image model, a lower quality, a DALL-E style or no art director are gone.
        // On Moodle 4.4 an image model left empty meant "no AI images"; keep that choice.
        $directmodel = get_config('format_dari', 'directimagemodel');
        if ($directmodel !== false && trim((string) $directmodel) === '') {
            set_config('directimages', 0, 'format_dari');
        }
        foreach (['directimagemodel', 'imagequality', 'imagestyle', 'aiscenewriter'] as $name) {
            unset_config($name, 'format_dari');
        }
        // Forget remembered scenes and art direction written by the old prompt, which steered every
        // card towards a person at a desk with screens.
        $DB->delete_records_select('config_plugins', "plugin = :plugin AND (" . $DB->sql_like('name', ':a') . ' OR '
            . $DB->sql_like('name', ':b') . ')', ['plugin' => 'format_dari', 'a' => 'artscenes\\_%', 'b' => 'artdirection\\_%']);
        \cache::make('core', 'config')->delete('format_dari');
        upgrade_plugin_savepoint(true, 2026100901, 'format', 'dari');
    }
    if ($oldversion < 2026100902) {
        // Images now come from one image engine, Google (Nano Banana 2.1) by default. A site that
        // already chose an engine keeps it.
        if (get_config('format_dari', 'imageengine') === false) {
            set_config('imageengine', \format_dari\local\imagemodel::DEFAULT_ENGINE, 'format_dari');
        }
        upgrade_plugin_savepoint(true, 2026100902, 'format', 'dari');
    }
    if ($oldversion < 2026100903) {
        global $DB;
        $dbman = $DB->get_manager();
        // Images are now planned per course before any prompt is written: the plan and the prompt
        // for each card are kept here.
        $table = new xmldb_table('format_dari_imageplan');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemkey', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('factshash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('plan', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('generations', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_index('courseid_itemkey', XMLDB_INDEX_UNIQUE, ['courseid', 'itemkey']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // The single-call art direction and scene memory of earlier versions are no longer used.
        $DB->delete_records_select('config_plugins', "plugin = :plugin AND (" . $DB->sql_like('name', ':a') . ' OR '
            . $DB->sql_like('name', ':b') . ')', ['plugin' => 'format_dari', 'a' => 'artscenes\\_%', 'b' => 'artdirection\\_%']);
        \cache::make('core', 'config')->delete('format_dari');
        upgrade_plugin_savepoint(true, 2026100903, 'format', 'dari');
    }
    if ($oldversion < 2026100904) {
        global $DB;
        $dbman = $DB->get_manager();
        // Plans written by 2.0.3 (no activity purpose, no categories) are planned again. Emptied
        // first, so the new NOT NULL columns are added to an empty table.
        $DB->delete_records('format_dari_imageplan');
        $table = new xmldb_table('format_dari_imageplan');
        // Attempts, successes and failures are counted apart, so a failed image never turns the next
        // attempt into a new concept.
        $old = new xmldb_field('generations', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $old)) {
            $dbman->rename_field($table, $old, 'attempts');
        }
        $fields = [
            new xmldb_field('teacherhash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'factshash'),
            new xmldb_field('successes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'attempts'),
            new xmldb_field('failures', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'successes'),
            new xmldb_field('lastresult', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, 'failures'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $log = new xmldb_table('format_dari_imagelog');
        $log->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $log->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $log->add_field('itemkey', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $log->add_field('requestid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $log->add_field('stage', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null);
        $log->add_field('status', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'ok');
        $log->add_field('durationms', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $log->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $log->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $log->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $log->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $log->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $log->add_index('courseid_time', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'timecreated']);
        $log->add_index('requestid', XMLDB_INDEX_NOTUNIQUE, ['requestid']);
        $log->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        if (!$dbman->table_exists($log)) {
            $dbman->create_table($log);
        }
        upgrade_plugin_savepoint(true, 2026100904, 'format', 'dari');
    }
    if ($oldversion < 2026100905) {
        // Image prompts are always written by the text model from the plan (highest quality); the
        // 2.0.4 choice between that and a faster assembled prompt is removed.
        unset_config('promptmode', 'format_dari');
        upgrade_plugin_savepoint(true, 2026100905, 'format', 'dari');
    }
    return true;
}
