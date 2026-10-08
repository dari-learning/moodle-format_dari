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
 * The typeface Dari uses: bundled DM Sans, the theme's own font, or a Google Font.
 *
 * DM Sans ships inside the plugin and is served by Moodle. A Google Font is loaded from
 * fonts.googleapis.com only when an administrator picks one, and only on pages Dari draws.
 * Each font is listed with the exact weights Google serves for it: asking Google for a weight a
 * family does not have makes the whole stylesheet request fail.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fonts {
    /** @var string The bundled default. */
    public const DEFAULT = 'dmsans';

    /** @var string Use whatever font the theme sets. */
    public const THEME = 'theme';

    /**
     * Google Fonts on offer: setting key => [family name, weight spec, generic fallback].
     *
     * Weight specs use Google's CSS2 API syntax: a range for variable fonts, a list otherwise.
     */
    public const GOOGLE = [
        'inter' => ['Inter', '400..700', 'sans-serif'],
        'roboto' => ['Roboto', '400..700', 'sans-serif'],
        'opensans' => ['Open Sans', '400..700', 'sans-serif'],
        'lato' => ['Lato', '400;700', 'sans-serif'],
        'montserrat' => ['Montserrat', '400..700', 'sans-serif'],
        'poppins' => ['Poppins', '400;500;600;700', 'sans-serif'],
        'nunito' => ['Nunito', '400..700', 'sans-serif'],
        'nunitosans' => ['Nunito Sans', '400..700', 'sans-serif'],
        'raleway' => ['Raleway', '400..700', 'sans-serif'],
        'sourcesans3' => ['Source Sans 3', '400..700', 'sans-serif'],
        'worksans' => ['Work Sans', '400..700', 'sans-serif'],
        'plusjakartasans' => ['Plus Jakarta Sans', '400..700', 'sans-serif'],
        'manrope' => ['Manrope', '400..700', 'sans-serif'],
        'outfit' => ['Outfit', '400..700', 'sans-serif'],
        'figtree' => ['Figtree', '400..700', 'sans-serif'],
        'lexend' => ['Lexend', '400..700', 'sans-serif'],
        'rubik' => ['Rubik', '400..700', 'sans-serif'],
        'mulish' => ['Mulish', '400..700', 'sans-serif'],
        'karla' => ['Karla', '400..700', 'sans-serif'],
        'ibmplexsans' => ['IBM Plex Sans', '400;500;600;700', 'sans-serif'],
        'firasans' => ['Fira Sans', '400;500;600;700', 'sans-serif'],
        'notosans' => ['Noto Sans', '400..700', 'sans-serif'],
        'ptsans' => ['PT Sans', '400;700', 'sans-serif'],
        'ubuntu' => ['Ubuntu', '400;500;700', 'sans-serif'],
        'quicksand' => ['Quicksand', '400..700', 'sans-serif'],
        'librefranklin' => ['Libre Franklin', '400..700', 'sans-serif'],
        'atkinson' => ['Atkinson Hyperlegible', '400;700', 'sans-serif'],
        'merriweather' => ['Merriweather', '400;700', 'serif'],
        'lora' => ['Lora', '400..700', 'serif'],
        'playfair' => ['Playfair Display', '400..700', 'serif'],
        'sourceserif4' => ['Source Serif 4', '400..700', 'serif'],
    ];

    /**
     * Normalise a stored value to DEFAULT, THEME or a GOOGLE key; '' when it is not one.
     *
     * @param string $value Stored value.
     * @return string
     */
    protected static function valid(string $value): string {
        if ($value === self::DEFAULT || $value === self::THEME || isset(self::GOOGLE[$value])) {
            return $value;
        }
        return '';
    }

    /**
     * The site-wide choice from the plugin settings.
     *
     * @return string
     */
    public static function site_choice(): string {
        return self::valid((string) get_config('format_dari', 'fontfamily')) ?: self::DEFAULT;
    }

    /**
     * The choice for a course: its own "Font" course setting, else the site setting.
     *
     * @param \stdClass|null $course The course, or null for the site choice.
     * @return string
     */
    public static function choice(?\stdClass $course = null): string {
        if ($course && !empty($course->id) && $course->id != SITEID) {
            try {
                $options = course_get_format($course)->get_format_options();
                $own = self::valid((string) ($options['fontfamily'] ?? ''));
                if ($own !== '') {
                    return $own;
                }
            } catch (\Throwable $e) {
                // Fall back to the site choice.
                debugging('format_dari font lookup: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return self::site_choice();
    }

    /**
     * Human label for a choice.
     *
     * @param string $choice DEFAULT, THEME or a GOOGLE key.
     * @return string
     */
    public static function label(string $choice): string {
        return self::options()[$choice] ?? self::options()[self::DEFAULT];
    }

    /**
     * Options for the admin setting.
     *
     * @return array
     */
    public static function options(): array {
        $options = [
            self::DEFAULT => get_string('fontfamily_dmsans', 'format_dari'),
            self::THEME => get_string('fontfamily_theme', 'format_dari'),
        ];
        $google = [];
        foreach (self::GOOGLE as $key => [$name]) {
            $google[$key] = get_string('fontfamily_google', 'format_dari', $name);
        }
        asort($google, SORT_NATURAL | SORT_FLAG_CASE);
        return $options + $google;
    }

    /**
     * The Google Fonts stylesheet URL for a key.
     *
     * @param string $key A GOOGLE key.
     * @return string
     */
    public static function google_url(string $key): string {
        [$name, $weights] = self::GOOGLE[$key];
        return 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $name) . ':wght@' . $weights
            . '&display=swap';
    }

    /**
     * Apply the font to a page: a body class, and the Google stylesheet when one is chosen.
     *
     * @param \moodle_page $page The page being set up.
     * @return void
     */
    public static function apply(\moodle_page $page): void {
        $choice = self::choice($page->course ?? null);
        if ($choice === self::DEFAULT) {
            return;
        }
        if ($choice === self::THEME) {
            $page->add_body_class('dari-font-theme');
            return;
        }
        $page->add_body_class('dari-font-google');
        $page->add_body_class('dari-font-g-' . $choice);
        // Only while the page head can still be written, and never for CLI or AJAX requests.
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) || (defined('AJAX_SCRIPT') && AJAX_SCRIPT)
                || $page->state >= \moodle_page::STATE_PRINTING_HEADER) {
            return;
        }
        try {
            $page->requires->css(new \moodle_url(self::google_url($choice)));
        } catch (\Throwable $e) {
            debugging('format_dari could not add the Google font stylesheet: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * CSS giving each Google font its family, for styles.css (generated once, pasted in).
     *
     * @return string
     */
    public static function css(): string {
        $css = '';
        foreach (self::GOOGLE as $key => [$name, , $generic]) {
            $css .= "body.format-dari.dari-font-g-{$key},\nbody.format-dari.dari-font-g-{$key} .dariadmin-wrap {\n"
                . "    --drf-font-family: \"{$name}\", {$generic};\n}\n";
        }
        return $css;
    }
}
