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
 * Restore support for the Dari course format.
 *
 * Counterpart to backup_format_dari_plugin. Its only job is to put the course banner image
 * back into the restored course's file area; the format's settings are restored by core from
 * {course_format_options}.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore plugin class for the Dari course format.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_dari_plugin extends restore_format_plugin {
    /**
     * Define the paths this plugin handles inside the course element.
     *
     * The element carries no data worth restoring, but registering a path is not optional:
     * restore_plugin::define_plugin_structure() only records a processing object when the
     * plugin returns at least one path, and restore_structure_step::launch_after_restore_methods()
     * walks those recorded objects. Return nothing here and after_restore_course() below is
     * never called, so the banner is never restored.
     *
     * @return array Array of restore_path_element.
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element('dari_banner', $this->get_pathfor('/banner')),
        ];
    }

    /**
     * Process the banner element.
     *
     * Intentionally a no-op. See define_course_plugin_structure() for why the element exists.
     *
     * @param array|stdClass $data The parsed element.
     * @return void
     */
    public function process_dari_banner($data) {
        return;
    }

    /**
     * Restore the banner image once the course itself has been restored.
     *
     * The file area is added with a null item id mapping, which makes
     * restore_dbops::send_files_to_pool() reuse the stored item id verbatim. That is correct
     * because the banner is always filed under format_dari\local\banner::BANNER_ITEMID (0),
     * which does not change between courses.
     *
     * @return void
     */
    public function after_restore_course() {
        $this->add_related_files('format_dari', 'bannerimage', null);
        // Activity card images, whose item id is a course module id. This runs after the
        // whole restore, when every restored activity has registered its 'course_module' mapping.
        //
        // add_related_files() cannot be used here. It matches each file against its mapping on
        // BOTH the item id and the mapping's parentitemid, which core only fills with a context
        // when the mapping was created with "restore files" -- and course_module mappings are not.
        // Every image would silently fail to match (a test caught exactly that). Calling the
        // restore helper directly with $skipparentitemidctxmatch = true matches on the item id
        // alone, which is right: the files are in the course context, whose old id is known.
        restore_dbops::send_files_to_pool(
            $this->task->get_basepath(),
            $this->get_restoreid(),
            'format_dari',
            'cmcardimage',
            $this->task->get_old_contextid(),
            $this->task->get_userid(),
            'course_module',
            null,
            null,
            true
        );
    }

    /**
     * Define the paths this plugin handles inside each section element.
     *
     * As with the course element, the data carries nothing worth restoring and the element
     * exists only so that after_restore_section() below is called at all.
     *
     * @return array Array of restore_path_element.
     */
    protected function define_section_plugin_structure() {
        return [
            new restore_path_element('dari_sectionbanner', $this->get_pathfor('/sectionbanner')),
            new restore_path_element('dari_sectioncard', $this->get_pathfor('/sectioncard')),
        ];
    }

    /**
     * Process the section banner element.
     *
     * Intentionally a no-op. See define_section_plugin_structure() for why the element exists.
     *
     * @param array|stdClass $data The parsed element.
     * @return void
     */
    public function process_dari_sectionbanner($data) {
        return;
    }

    /**
     * Restore this section's banner image once the section itself has been restored.
     *
     * The mapping argument is 'course_section' rather than the null the course banner
     * uses, and that difference is the entire point: a section banner's item id names a section,
     * and a restored course has new section ids. Core registers the old-to-new section mapping
     * under that name in restore_section_structure_step, so restore_dbops::send_files_to_pool()
     * translates each file's item id as it goes. Passing null here instead would copy the old
     * ids verbatim and file every banner against a section that does not exist in this course:
     * present in the file pool, reachable by nothing, and silent about it.
     *
     * @return void
     */
    public function after_restore_section() {
        $this->add_related_files('format_dari', 'sectionbannerimage', 'course_section');
        $this->add_related_files('format_dari', 'sectioncardimage', 'course_section');
    }

    /**
     * Restore a section card's colour against the restored section.
     *
     * @param array|stdClass $data The parsed element.
     * @return void
     */
    public function process_dari_sectioncard($data) {
        $this->restore_card_colour('section', (int) $this->task->get_sectionid(), (object) $data);
    }

    /**
     * Define the paths this plugin handles inside each activity.
     *
     * @return array Array of restore_path_element.
     */
    protected function define_module_plugin_structure() {
        return [
            new restore_path_element('dari_cmcard', $this->get_pathfor('/cmcard')),
        ];
    }

    /**
     * Restore an activity card's colour against the restored activity.
     *
     * @param array|stdClass $data The parsed element.
     * @return void
     */
    public function process_dari_cmcard($data) {
        $this->restore_card_colour('cm', (int) $this->task->get_moduleid(), (object) $data);
    }

    /**
     * Write one restored card colour, replacing any the target already has.
     *
     * @param string $type section or cm.
     * @param int $targetid The NEW section or course module id.
     * @param stdClass $data The parsed element.
     * @return void
     */
    protected function restore_card_colour(string $type, int $targetid, stdClass $data): void {
        global $DB;

        $colour = \format_dari\local\cardimage::clean_colour((string) ($data->colour ?? ''));
        if ($targetid <= 0 || $colour === '') {
            return;
        }
        $DB->delete_records('format_dari_cardstyle', ['targettype' => $type, 'targetid' => $targetid]);
        $DB->insert_record('format_dari_cardstyle', (object) [
            'courseid' => (int) $this->task->get_courseid(),
            'targettype' => $type,
            'targetid' => $targetid,
            'colour' => $colour,
            'usermodified' => 0,
            'timemodified' => time(),
        ]);
    }
}
