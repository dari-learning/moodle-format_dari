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
 * Where Dari's documentation lives, and the elephant icon that links to it.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class docs {
    /** @var string The documentation home on darilearning.com. */
    public const URL = 'https://darilearning.com/docs';

    /**
     * URL of the elephant icon (pix/elephant.svg or pix/elephant.png).
     *
     * @return string
     */
    public static function icon_url(): string {
        global $OUTPUT;
        return $OUTPUT->image_url('elephant', 'format_dari')->out(false);
    }

    /**
     * The elephant icon and a "Documentation" link, as HTML, for the admin settings page.
     *
     * @return string
     */
    public static function link_html(): string {
        $img = \html_writer::empty_tag('img', [
            'src' => self::icon_url(),
            'alt' => '',
            'width' => 40,
            'height' => 40,
            'class' => 'dari-docs-elephant',
        ]);
        $text = \html_writer::span(get_string('docslink_text', 'format_dari'), 'dari-docs-text');
        return \html_writer::link(self::URL, $img . $text, [
            'class' => 'dari-docs-link',
            'target' => '_blank',
            'rel' => 'noopener',
            'aria-label' => get_string('docslink_label', 'format_dari'),
        ]);
    }
}
