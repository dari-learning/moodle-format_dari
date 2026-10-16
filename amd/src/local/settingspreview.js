const captionOr = (caption, fallback) => caption || fallback;
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
 * Setting previews for the plugin's settings page.
 *
 * Every setting gets a small, accurate drawing of the course page it changes: real breadcrumbs,
 * real tabs, a banner with a title and a ring, a course index with ticks and time chips, cards
 * with chips and a progress bar. The part the setting changes is ringed in the theme primary and
 * named in a callout. Settings that switch something on or off draw both states side by side, and
 * the panel matching the current value is marked as the setting changes.
 *
 * Everything is inline SVG built here, with no images. Colours come from CSS custom properties
 * (see the settings page section of styles.css), so the drawings follow light and dark admin themes.
 * The drawings are decorative (aria-hidden); a visually hidden caption beside each one says in
 * words what it shows.
 *
 * @module     format_dari/local/settingspreview
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

/** @type {String[]} Every language string the drawings use. */
const STRING_KEYS = [
    'pv_12_questions_2_min',
    'pv_1_hr_10_min',
    'pv_3_modules_5_activities',
    'pv_3_modules_5_activities_1_hr_10_min',
    'pv_3d_render',
    'pv_a_12_question_quiz_at_2_minutes_a_qu',
    'pv_a_banner_photograph_darkened_lightly',
    'pv_a_banner_without_an_image_untinted_o',
    'pv_a_course_opened_with_the_index_colla',
    'pv_a_one_minute_look_at_how_this',
    'pv_a_sample_of_the_course_page_text_in',
    'pv_a_section_card_listing_its_first_thr',
    'pv_a_section_card_with_its_total_time_c',
    'pv_a_section_card_without_its_activity',
    'pv_a_section_page_as_moodles_standard_a',
    'pv_a_tour_step_read_aloud_in_the_chosen',
    'pv_above_the_tabs',
    'pv_accent_colour',
    'pv_activity_icons',
    'pv_activity_tiles',
    'pv_after_scrolling_down_on_the_left_the',
    'pv_all_courses',
    'pv_an_activity_page_banner_without_then',
    'pv_an_activity_tile_with_its_time_chip',
    'pv_an_activity_type_with_no_time_of_its',
    'pv_anything_not_listed',
    'pv_api_key',
    'pv_ask_about_this_course',
    'pv_ask_dari',
    'pv_ask_dari_answering_a_learner_who_is',
    'pv_ask_dari_explaining_a_quiz_result_wi',
    'pv_ask_dari_greeting_a_learner_without',
    'pv_assessment',
    'pv_assignment',
    'pv_assumed_time_per_activity_type',
    'pv_back',
    'pv_below_the_banner',
    'pv_below_the_tabs',
    'pv_book',
    'pv_card_background',
    'pv_consultation',
    'pv_contact_site_support',
    'pv_continue',
    'pv_controls',
    'pv_course',
    'pv_course_content',
    'pv_course_content_up_to_your_limit',
    'pv_course_home_sections_activities',
    'pv_course_home_sections_activities2',
    'pv_course_index',
    'pv_course_index_rows_with_their_estimat',
    'pv_course_works_skip_any_time',
    'pv_current',
    'pv_dari_academy',
    'pv_dari_sends_the_question_and_course_c',
    'pv_dari_uses_your_sites_own_ai_provider',
    'pv_dark',
    'pv_dashboard',
    'pv_data_retention_summary',
    'pv_done_view',
    'pv_duty_of_care',
    'pv_each_activity_type_with_its_assumed',
    'pv_eliminate_substitute_isolate',
    'pv_end_tour',
    'pv_endpoint',
    'pv_engineer_administrate_ppe',
    'pv_explain_the_concept_simply',
    'pv_find_what_could_hurt_someone',
    'pv_flat',
    'pv_follow_the_theme',
    'pv_forum',
    'pv_from_google_fonts',
    'pv_general',
    'pv_generated_card_images',
    'pv_get_the_mobile_app',
    'pv_give_me_some_practice_questions',
    'pv_grades',
    'pv_grid',
    'pv_hazard_quiz',
    'pv_hazards',
    'pv_hi_sam_what_are_we_working',
    'pv_hi_there_what_are_we_working',
    'pv_hide',
    'pv_hierarchy_of_control',
    'pv_hierarchy_starts_with_removing',
    'pv_home',
    'pv_i_cant_see_your_answers_tell_me',
    'pv_i_feel_really_stressed',
    'pv_illustration',
    'pv_image_model',
    'pv_in_progress',
    'pv_in_the_site_header',
    'pv_index_background',
    'pv_index_header_band',
    'pv_large',
    'pv_leading_safe_workplaces',
    'pv_learner_first_name',
    'pv_learning_portal',
    'pv_lesson',
    'pv_lesson_no_time_set',
    'pv_lifeline_13_11_14',
    'pv_light',
    'pv_list',
    'pv_medium',
    'pv_mins',
    'pv_module_1',
    'pv_module_2',
    'pv_module_3',
    'pv_moodle_4_4_banners_and_card_images_d',
    'pv_moodle_4_4_dari_calls_your_provider',
    'pv_moodle_4_4_dari_sends_requests_strai',
    'pv_moodle_4_4_the_api_key_dari_uses_wit',
    'pv_moodle_4_4_the_model_that_writes_ask',
    'pv_moodles_plain_course_index_on_the_le',
    'pv_my_courses',
    'pv_narration_english_australia',
    'pv_never_share',
    'pv_next',
    'pv_no_tint',
    'pv_off',
    'pv_off_long_page',
    'pv_on',
    'pv_on_cards',
    'pv_on_today',
    'pv_openai_compatible',
    'pv_opens_ask_dari',
    'pv_page',
    'pv_participants',
    'pv_photo_shows_most',
    'pv_photographic',
    'pv_pick_a_tool_or_type_your_own_questio',
    'pv_plain_course_index',
    'pv_player_sidebar',
    'pv_powered_by_moodle',
    'pv_previous_next',
    'pv_question',
    'pv_questions_are_counted_when',
    'pv_recommended',
    'pv_reports',
    'pv_risk_assessment',
    'pv_sample_in_a_similar_font_courses_loa',
    'pv_scrolls_away',
    'pv_section_cards_side_by_side_in_a_grid',
    'pv_section_headings',
    'pv_set_in_site_admin',
    'pv_settings',
    'pv_share',
    'pv_show',
    'pv_show_me_a_real_world_example',
    'pv_shown_as_time_chips',
    'pv_shown_in',
    'pv_shows_3_then_2_more',
    'pv_site_walk',
    'pv_spotting_hazards',
    'pv_spotting_hazards2',
    'pv_spotting_hazards_shown_on_the_left_a',
    'pv_standard_list',
    'pv_start_collapsed',
    'pv_start_open',
    'pv_stays_at_top',
    'pv_strong',
    'pv_student_wellbeing_ext_2040',
    'pv_text_model',
    'pv_text_stands_out_most',
    'pv_that_sounds_hard_you_can_talk_to',
    'pv_the_accent_colour_on_the_course_page',
    'pv_the_activity_icons_in_the_course_ind',
    'pv_the_ask_dari_panel_a_greeting_a_lear',
    'pv_the_background_of_each_section_card',
    'pv_the_band_at_the_top_of_the_course_in',
    'pv_the_banner_under_the_course_tabs_on',
    'pv_the_breadcrumb_trail_dashboard_my_co',
    'pv_the_card_background_colour_at_a_weak',
    'pv_the_course_home_as_moodles_long_page',
    'pv_the_course_index_background_tinted_w',
    'pv_the_course_index_beside_the_course_p',
    'pv_the_course_index_colour_at_a_weak_st',
    'pv_the_course_index_with_the_general_se',
    'pv_the_course_page_without_the_banner_o',
    'pv_the_course_tabs_course_settings_part',
    'pv_the_course_tabs_under_the_page_title',
    'pv_the_four_looks_for_ai_card_images_ph',
    'pv_the_hazard_a_elimination',
    'pv_the_same_course_page_in_light_mode_o',
    'pv_the_section_card_title_at_a_small_si',
    'pv_the_section_heading_bands_in_the_cou',
    'pv_the_site_footer_with_its_links_at_th',
    'pv_the_site_logo_band_above_the_navbar',
    'pv_the_time_is_worked_out',
    'pv_the_total_course_time_under_the_cour',
    'pv_the_tour_step_with_narration_muted_o',
    'pv_then_decide_what_to_do_about_it',
    'pv_through_it_together',
    'pv_tinted',
    'pv_to_do_receive_a_grade',
    'pv_to_do_submit',
    'pv_toolbox_talk',
    'pv_uses_the_fallback_time',
    'pv_uses_the_themes_own_font',
    'pv_voice_language',
    'pv_weak',
    'pv_welcome_to_your_course',
    'pv_what_you_chose_and_well_work',
    'pv_whs_for_supervisors',
    'pv_why_did_i_get_q3_wrong',
    'pv_why_supervisors_care',
    'pv_with_dari',
    'pv_you_chose_b_ppe_first_the',
    'pv_your_ai',
    'pv_your_contacts',
    'pv_your_duty_of_care',
    'pv_your_logo_at_the_top_of_the_course_i',
    'pv_your_logo_here',
];

/** @type {Object} The strings, loaded once by loadPreviewStrings(). */
const STRINGS = {};

/**
 * Load every string the drawings use. Call (and await) before buildSettingPreview().
 *
 * @returns {Promise<void>}
 */
export const loadPreviewStrings = async() => {
    if (Object.keys(STRINGS).length) {
        return;
    }
    const values = await getStrings(STRING_KEYS.map((key) => ({key, component: 'format_dari'})));
    STRING_KEYS.forEach((key, i) => {
        STRINGS[key] = values[i];
    });
};

/**
 * A loaded string.
 *
 * @param {String} key String identifier.
 * @returns {String}
 */
const str = (key) => STRINGS[key] || '';

/**
 * A time chip label, such as "10 min".
 *
 * @param {Number} n Minutes.
 * @returns {String}
 */
const mins = (n) => str('pv_mins').replace('{$a}', String(n));

/** @type {Number} Drawing width. */
const W = 360;
/** @type {Number} Drawing height. */
const H = 214;
/** @type {Number} Counter that keeps gradient ids unique on the page. */
let uid = 0;

/**
 * Escape text for SVG.
 *
 * @param {String} s Text.
 * @returns {String}
 */
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/**
 * Rough text width for DM Sans at a size, good enough to place the next word.
 *
 * @param {String} s Text.
 * @param {Number} size Font size.
 * @param {Boolean} [bold] Semibold or bold.
 * @returns {Number}
 */
const tw = (s, size, bold) => s.length * size * (bold ? 0.6 : 0.56);

/**
 * A rectangle.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} c Class.
 * @param {Number} [r] Corner radius.
 * @returns {String}
 */
const drawRect = (x, y, w, h, c, r) =>
    '<rect class="' +
    c +
    '" x="' +
    x +
    '" y="' +
    y +
    '" width="' +
    w +
    '" height="' +
    h +
    '"' +
    (r ? ' rx="' + r + '"' : '') +
    '/>';

/**
 * A line.
 *
 * @param {Number} x1 Start x.
 * @param {Number} y1 Start y.
 * @param {Number} x2 End x.
 * @param {Number} y2 End y.
 * @param {String} c Class.
 * @returns {String}
 */
const drawLine = (x1, y1, x2, y2, c) => '<path class="' + c + '" d="M' + x1 + ' ' + y1 + 'L' + x2 + ' ' + y2 + '"/>';

/**
 * Text.
 *
 * @param {Number} x Left (or centre/end with an anchor).
 * @param {Number} y Baseline.
 * @param {String} s The words.
 * @param {String} [c] Classes: size (x8..x22), weight (b), colour (k2, k3, kw, kb, kok, kred).
 * @param {String} [a] Anchor: 'm' middle, 'e' end.
 * @returns {String}
 */
const drawText = (x, y, s, c, a) =>
    '<text class="x ' +
    (c || '') +
    '" x="' +
    x +
    '" y="' +
    y +
    '"' +
    (() => {
        if (a === 'm') {
            return ' text-anchor="middle"';
        }
        return a === 'e' ? ' text-anchor="end"' : '';
    })() +
    '>' +
    esc(s) +
    '</text>';

/**
 * A circle.
 *
 * @param {Number} cx Centre x.
 * @param {Number} cy Centre y.
 * @param {Number} r Radius.
 * @param {String} c Class.
 * @returns {String}
 */
const drawCircle = (cx, cy, r, c) => '<circle class="' + c + '" cx="' + cx + '" cy="' + cy + '" r="' + r + '"/>';

/**
 * A group, optionally moved.
 *
 * @param {String} inner Content.
 * @param {Number} [x] Shift x.
 * @param {Number} [y] Shift y.
 * @param {String} [c] Class.
 * @param {Number} [s] Scale.
 * @returns {String}
 */
const drawGroup = (inner, x, y, c, s) =>
    '<g' +
    (c ? ' class="' + c + '"' : '') +
    (x || y || s
        ? ' transform="translate(' + (x || 0) + ' ' + (y || 0) + ')' + (s ? ' scale(' + s + ')' : '') + '"'
        : '') +
    '>' +
    inner +
    '</g>';

/* --------------------------------------------------------------------------
   Icons, drawn on a 12 unit grid and placed with a translate.
   -------------------------------------------------------------------------- */
const ICON = {
    home: 'M1.5 5.5 6 1.8l4.5 3.7M2.8 4.6v5.9h6.4V4.6M5 10.5V7.6h2v2.9',
    grades: 'M2 10.5h8M3.3 9V6.5M6 9V3.5M8.7 9V5',
    spark: 'M6 1.2c.3 2.4 1.5 3.6 3.9 3.9-2.4.3-3.6 1.5-3.9 3.9-.3-2.4-1.5-3.6-3.9-3.9 2.4-.3 3.6-1.5 3.9-3.9z',
    clock: 'M6 2.9a3.95 3.95 0 1 0 0 7.9 3.95 3.95 0 1 0 0-7.9zM6 4.9v2l1.4 1M4.8 1.2h2.4',
    page: 'M3 1.5h4l2 2v7H3zM7 1.5v2h2M4.5 6h3M4.5 8h3',
    forum: 'M2 2.5h8v5.5H6l-2.5 2V8H2z',
    quiz: 'M2.5 3.5l1 1 2-2M2.5 8l1 1 2-2M7 4h3M7 8.5h3',
    assign: 'M3 1.5h6v9H3zM4.5 7.2l1.2 1.2 2-2.4M4.5 4h3',
    list: 'M4.5 3h6M4.5 6h6M4.5 9h4M1.5 3h1M1.5 6h1M1.5 9h1',
    chevr: 'M4.5 2.5 8 6l-3.5 3.5',
    chevl: 'M7.5 2.5 4 6l3.5 3.5',
    chevd: 'M2.5 4.5 6 8l3.5-3.5',
    close: 'M3 3l6 6M9 3 3 9',
    check: 'M2.8 6.2 5 8.4l4.2-4.6',
    send: 'M10.5 1.5 1.5 5.2l3.8 1.5 1.5 3.8z',
    speaker: 'M1.8 4.5h2l2.5-2.2v7.4L3.8 7.5h-2z',
    waves: 'M8 4.3c.8.9.8 2.5 0 3.4M9.6 2.9c1.6 1.8 1.6 4.4 0 6.2',
    mute: 'M8 4.5l3 3M11 4.5l-3 3',
    bell: 'M3 8.5V5.5a3 3 0 0 1 6 0v3l1 1H2zM5 10.5h2',
    grid: 'M1.5 1.5h3.5v3.5H1.5zM7 1.5h3.5v3.5H7zM1.5 7h3.5v3.5H1.5zM7 7h3.5v3.5H7z',
    dots: 'M6 2.5v.01M6 6v.01M6 9.5v.01',
    book: 'M6 3C4.8 2 3.2 1.8 1.5 2v7.5c1.7-.2 3.3 0 4.5 1 1.2-1 2.8-1.2 4.5-1V2C8.8 1.8 7.2 2 6 3zM6 3v7.5',
    target: 'M6 1.8a4.2 4.2 0 1 0 0 8.4 4.2 4.2 0 1 0 0-8.4zM6 4a2 2 0 1 0 0 4 2 2 0 1 0 0-4z',
    building: 'M2 10.5h8M3 10.5V1.8h4v8.7M7 4.5h2v6M4.3 3.6h1.4M4.3 5.6h1.4M4.3 7.6h1.4',
    arrowr: 'M2 6h8M7 3l3 3-3 3',
    cloud: 'M3.5 9.5h5.3a2.2 2.2 0 0 0 .3-4.4A3.2 3.2 0 0 0 3 5.3a2.1 2.1 0 0 0 .5 4.2z',
    lock: 'M3 5.5h6v5H3zM4.3 5.5V4a1.7 1.7 0 0 1 3.4 0v1.5',
    user: 'M6 6a2 2 0 1 0 0-4 2 2 0 1 0 0 4zM2.5 10.5c.5-2 1.8-3 3.5-3s3 1 3.5 3',
    heart: 'M6 10S1.5 7.3 1.5 4.4A2.2 2.2 0 0 1 6 3.3a2.2 2.2 0 0 1 4.5 1.1C10.5 7.3 6 10 6 10z',
    image: 'M1.5 2h9v8h-9zM1.5 8.2l2.6-2.4 2 1.8 1.5-1.2 2.9 2.4',
    flag: 'M2.5 11V1.5M2.5 2h6.5l-1.5 2.3L9 6.5H2.5'
};

/**
 * An icon.
 *
 * @param {String} k Key into ICON.
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} s Size in units (12 = 1:1).
 * @param {String} c Class (ic-2, ic-w, ic-b, ic-ok, plus ic-f for a filled glyph).
 * @returns {String}
 */
const drawIcon = (k, x, y, s, c) =>
    '<path class="ik ' +
    (c || 'ic-2') +
    '" transform="translate(' +
    x +
    ' ' +
    y +
    ') scale(' +
    s / 12 +
    ')" d="' +
    ICON[k] +
    '"/>';

/* --------------------------------------------------------------------------
   Annotation: the ring round the part a setting changes, the callout naming
   it, and the Off / On labels for two-state settings.
   -------------------------------------------------------------------------- */

/**
 * Ring a region in the primary colour.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Number} [r] Radius.
 * @returns {String}
 */
const drawHighlight = (x, y, w, h, r) => drawRect(x, y, w, h, 'hl', r === undefined ? 4 : r);

/**
 * A callout: a primary pill with the name of what changes, and a leader to it.
 *
 * @param {Number} x Pill left.
 * @param {Number} y Pill top.
 * @param {String} s Label.
 * @param {Number} [tx] Leader end x.
 * @param {Number} [ty] Leader end y.
 * @returns {String}
 */
const drawCallout = (x, y, s, tx, ty) => {
    const w = Math.round(tw(s, 9, true) + 14);
    let out = '';
    if (tx !== undefined) {
        const sx = (() => {
            if (tx < x) {
                return x;
            }
            return tx > x + w ? x + w : x + w / 2;
        })();
        const sy = (() => {
            if (ty < y) {
                return y;
            }
            return ty > y + 15 ? y + 15 : y + 7.5;
        })();
        out += drawLine(sx, sy, tx, ty, 'co-ln') + drawCircle(tx, ty, 2.2, 'co-dot');
    }
    return out + drawRect(x, y, w, 15, 'co-bg', 7.5) + drawText(x + w / 2, y + 10.5, s, 'x9 b kw', 'm');
};

/**
 * One of the two states of a setting.
 *
 * @param {Number} x Left.
 * @param {Number} w Width.
 * @param {String} label "Off", "On", "Grid", ...
 * @param {Boolean} on Draw the label in the primary colour.
 * @param {String} values The setting values this panel stands for, space separated.
 * @param {String} inner Content, drawn from y=20 down.
 * @returns {String}
 */
const drawPanel = (x, w, label, on, values, inner) => {
    const lw = Math.round(tw(label, 9, true) + 14);
    return (
        '<g class="pv-panel" data-v="' +
        values +
        '">' +
        drawRect(x + 0.5, 18.5, w - 1, H - 19, 'pv-frame', 7) +
        drawRect(x + 0.5, 18.5, w - 1, H - 19, 'pv-cur', 7) +
        drawRect(x, 0, lw, 14, on ? 'tag-on' : 'tag-off', 7) +
        drawText(x + lw / 2, 10, label, 'x9 b ' + (on ? 'kw' : 'k2'), 'm') +
        drawText(x + lw + 6, 10, '', 'x9 b kb pv-curlabel') +
        '<svg x="' +
        (x + 1) +
        '" y="19" width="' +
        (w - 2) +
        '" height="' +
        (H - 20) +
        '" viewBox="0 0 ' +
        (w - 2) +
        ' ' +
        (H - 20) +
        '" overflow="hidden">' +
        inner +
        '</svg></g>'
    );
};

/**
 * Two panels side by side.
 *
 * @param {Array} a [label, on, values, inner] for the left panel.
 * @param {Array} b The same for the right panel.
 * @returns {String}
 */
const drawTwo = (a, b) => drawPanel(0, 174, a[0], a[1], a[2], a[3]) + drawPanel(186, 174, b[0], b[1], b[2], b[3]);

/**
 * Three panels side by side.
 *
 * @param {Array} list Three [label, on, values, inner].
 * @returns {String}
 */
const drawThree = (list) => list.map((p, i) => drawPanel(i * 122, 114, p[0], p[1], p[2], p[3])).join('');

/* --------------------------------------------------------------------------
   Page parts.
   -------------------------------------------------------------------------- */

/**
 * Moodle's navbar.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {tabs: true to carry the course tabs beside the links}
 * @returns {String}
 */
const drawNavbar = (x, y, w, o) => {
    o = o || {};
    let s =
        drawRect(x, y, w, 20, 'p-surf0') +
        drawLine(x, y + 20, x + w, y + 20, 'p-line') +
        drawText(x + 8, y + 13.5, 'dari', 'x11 b');
    let lx = x + 34;
    const narrow = w < 240;
    const fs = narrow ? 8 : 9;
    const links = (() => {
        if (o.tabs) {
            return narrow
                ? [str('pv_home'), str('pv_course'), str('pv_grades')]
                : [str('pv_home'), str('pv_course'), str('pv_grades'), str('pv_participants')];
        }
        return [str('pv_home'), str('pv_dashboard'), str('pv_my_courses')];
    })();
    links.forEach((l, i) => {
        s += drawText(lx, y + 13, l, 'x' + fs + (o.tabs && i > 0 ? ' kb b' : ''));
        lx += tw(l, fs, o.tabs && i > 0) + (narrow ? 7 : 10);
    });
    if (!narrow) {
        s +=
            drawIcon('bell', x + w - 36, y + 4, 11, 'ic-2') +
            drawCircle(x + w - 13, y + 10, 6.5, 'p-chip') +
            drawText(x + w - 13, y + 12.8, 'SR', 'x8 b k2', 'm');
    }
    return s;
};

/**
 * A theme's logo band above the navbar.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const drawLogoband = (x, y, w) =>
    drawRect(x, y, w, 26, 'p-brandsoft') +
    drawRect(x + 8, y + 5, 16, 16, 'p-brand', 4) +
    drawText(x + 16, y + 16.5, 'D', 'x10 b kw', 'm') +
    drawText(x + 30, y + 16.5, str('pv_dari_academy'), 'x11 b') +
    (w >= 240 ? drawText(x + w - 8, y + 16, str('pv_learning_portal'), 'x9 k3', 'e') : '');

/**
 * The breadcrumb trail.
 *
 * @param {Number} x Left.
 * @param {Number} y Baseline.
 * @param {Number} [n] How many crumbs.
 * @returns {String}
 */
const drawCrumbs = (x, y, n) => {
    const parts = [str('pv_dashboard'), str('pv_my_courses'), 'WHS101', str('pv_spotting_hazards')].slice(0, n || 4);
    let s = '';
    let cx = x;
    parts.forEach((p, i) => {
        const last = i === parts.length - 1;
        s += drawText(cx, y, p, 'x9 ' + (last ? 'k2' : 'kb'));
        cx += tw(p, 9) + 4;
        if (!last) {
            s += drawText(cx, y, '›', 'x9 k3');
            cx += 8;
        }
    });
    return s;
};

/**
 * The course tabs.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const drawTabs = (x, y, w) => {
    let s = drawLine(x, y + 16, x + w, y + 16, 'p-line');
    let cx = x + 2;
    [str('pv_course'), str('pv_settings'), str('pv_participants'), str('pv_grades'), str('pv_reports')].forEach((l, i) => {
        const lw = tw(l, 9, i === 0);
        if (cx + lw > x + w) {
            return;
        }
        s += drawText(cx, y + 11, l, 'x9 ' + (i === 0 ? 'b' : 'kb'));
        if (i === 0) {
            s += drawRect(cx - 2, y + 14.5, lw + 4, 2, 'p-brand');
        }
        cx += lw + 11;
    });
    return s;
};

/**
 * The photograph used for image banners and cards.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} [tone] 'vivid' for saturated colours.
 * @returns {String}
 */
const drawPhoto = (x, y, w, h, tone) => {
    const id = 'pvg' + ++uid;
    const sky = tone === 'vivid' ? ['#3b82f6', '#93c5fd'] : ['#6f93ad', '#c9d8e2'];
    const hill1 = tone === 'vivid' ? '#16a34a' : '#5f7d6a';
    const hill2 = tone === 'vivid' ? '#15803d' : '#4b6656';
    return (
        '<defs><linearGradient id="' +
        id +
        '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' +
        sky[0] +
        '"/><stop offset="1" stop-color="' +
        sky[1] +
        '"/></linearGradient></defs>' +
        '<rect x="' +
        x +
        '" y="' +
        y +
        '" width="' +
        w +
        '" height="' +
        h +
        '" fill="url(#' +
        id +
        ')"/>' +
        '<circle cx="' +
        (x + w * 0.78) +
        '" cy="' +
        (y + h * 0.32) +
        '" r="' +
        h * 0.12 +
        '" fill="' +
        (tone === 'vivid' ? '#fde047' : '#efe6c8') +
        '"/>' +
        '<path fill="' +
        hill1 +
        '" d="M' +
        x +
        ' ' +
        (y + h * 0.72) +
        'Q' +
        (x + w * 0.3) +
        ' ' +
        (y + h * 0.42) +
        ' ' +
        (x + w * 0.62) +
        ' ' +
        (y + h * 0.7) +
        'T' +
        (x + w) +
        ' ' +
        (y + h * 0.6) +
        'V' +
        (y + h) +
        'H' +
        x +
        'z"/>' +
        '<path fill="' +
        hill2 +
        '" d="M' +
        x +
        ' ' +
        (y + h * 0.86) +
        'Q' +
        (x + w * 0.45) +
        ' ' +
        (y + h * 0.66) +
        ' ' +
        (x + w) +
        ' ' +
        (y + h * 0.84) +
        'V' +
        (y + h) +
        'H' +
        x +
        'z"/>'
    );
};

/**
 * A progress ring.
 *
 * @param {Number} cx Centre x.
 * @param {Number} cy Centre y.
 * @param {Number} r Radius.
 * @param {Number} pct 0..100.
 * @param {Boolean} [onmedia] White, over a picture.
 * @returns {String}
 */
const drawRing = (cx, cy, r, pct, onmedia) => {
    const c = 2 * Math.PI * r;
    return (
        drawCircle(cx, cy, r, onmedia ? 'ring-tw' : 'ring-t') +
        '<circle class="' +
        (onmedia ? 'ring-aw' : 'ring-a') +
        '" cx="' +
        cx +
        '" cy="' +
        cy +
        '" r="' +
        r +
        '" stroke-dasharray="' +
        ((c * pct) / 100).toFixed(1) +
        ' ' +
        c.toFixed(1) +
        '" transform="rotate(-90 ' +
        cx +
        ' ' +
        cy +
        ')"/>' +
        drawText(cx, cy + 3, pct + '%', 'x8 b ' + (onmedia ? 'kw' : ''), 'm')
    );
};

/**
 * The course banner.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {image, scrim (0..1), fade (0..1), title, chevrons, compact}
 * @returns {String}
 */
const drawBanner = (x, y, w, h, o) => {
    o = o || {};
    const media = !!o.image;
    let s = '';
    if (media) {
        s +=
            drawPhoto(x, y, w, h) +
            '<rect x="' +
            x +
            '" y="' +
            y +
            '" width="' +
            w +
            '" height="' +
            h +
            '" fill="#020617" fill-opacity="' +
            (() => {
                if (o.scrim === undefined) {
                    return 0.5;
                }
                return o.scrim;
            })() +
            '"/>';
    } else {
        s += drawRect(x, y, w, h, 'p-banner');
        if (o.fade) {
            s +=
                '<rect class="p-c" x="' +
                x +
                '" y="' +
                y +
                '" width="' +
                w +
                '" height="' +
                h +
                '" fill-opacity="' +
                o.fade +
                '"/>';
        }
    }
    s += drawRect(x + 0.5, y + 0.5, w - 1, h - 1, 'p-edge');
    const kt = (() => {
        if (media) {
            return ' kw';
        }
        return '';
    })();
    const wide = w >= 260 && !o.compact;
    const title =
        o.title ||
        (() => {
            if (wide) {
                return str('pv_leading_safe_workplaces');
            }
            return (() => {
                if (w < 130) {
                    return 'WHS101';
                }
                return str('pv_whs_for_supervisors');
            })();
        })();
    const btn = (bx, by, k, ai) =>
        drawRect(
            bx,
            by,
            17,
            17,
            (() => {
                if (ai) {
                    return 'p-brand';
                }
                return media ? 'p-glass' : 'p-btn';
            })(),
            4
        ) + drawIcon(k, bx + 2.5, by + 2.5, 12, ai || media ? 'ic-w' : 'ic-b');
    if (wide) {
        const cy = y + h / 2;
        s += btn(x + 8, cy - 8.5, 'home') + btn(x + 28, cy - 8.5, 'grades') + btn(x + 48, cy - 8.5, 'spark', true);
        s +=
            drawText(x + w / 2 + 6, cy - 1, title, 'x11 b' + kt, 'm') +
            drawText(
                x + w / 2 + 6,
                cy + 11,
                str('pv_3_modules_5_activities_1_hr_10_min'),
                'x8 ' +
                    (() => {
                        if (media) {
                            return 'kw';
                        }
                        return 'k2';
                    })(),
                'm'
            );
        s += drawRing(x + w - 20, cy, 11, 42, media);
    } else {
        s += drawText(x + 8, y + 16, title, 'x10 b' + kt);
        if (h >= 58) {
            s += drawText(
                x + 8,
                y + 28,
                str('pv_3_modules_5_activities'),
                'x8 ' +
                    (() => {
                        if (media) {
                            return 'kw';
                        }
                        return 'k2';
                    })()
            );
        }
        s +=
            btn(x + 8, y + h - 22, 'home') + btn(x + 28, y + h - 22, 'grades') + btn(x + 48, y + h - 22, 'spark', true);
        s += drawRing(x + w - 18, y + h - 15, 10, 42, media);
    }
    if (o.chevrons && !wide) {
        const cy2 = y + h - 13.5;
        s +=
            drawCircle(
                x + 82,
                cy2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            drawIcon(
                'chevl',
                x + 76,
                cy2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            ) +
            drawCircle(
                x + 102,
                cy2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            drawIcon(
                'chevr',
                x + 96,
                cy2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            );
    } else if (o.chevrons) {
        s +=
            drawCircle(
                x + w - 52,
                y + h / 2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            drawIcon(
                'chevl',
                x + w - 58,
                y + h / 2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            ) +
            drawCircle(
                x + w - 32,
                y + h / 2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            drawIcon(
                'chevr',
                x + w - 38,
                y + h / 2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            );
    }
    return s;
};

/**
 * The index rows.
 *
 * @returns {Array}
 */
const indexRows = () => [
    {
        sec: str('pv_general'),
        open: false,
        general: true
    },
    {
        sec: str('pv_your_duty_of_care'),
        open: true
    },
    {
        act: str('pv_why_supervisors_care'),
        icon: 'page',
        time: mins(10),
        done: true
    },
    {
        sec: str('pv_spotting_hazards'),
        open: true
    },
    {
        act: str('pv_hierarchy_of_control'),
        icon: 'page',
        time: mins(10),
        done: true
    },
    {
        act: str('pv_risk_assessment'),
        icon: 'assign',
        time: mins(30),
        done: false,
        current: true
    },
    {
        act: str('pv_hazard_quiz'),
        icon: 'quiz',
        time: mins(15),
        done: false
    },
    {
        sec: str('pv_consultation'),
        open: false
    }
];

/**
 * The course index.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {plain, time, total, logo, nogeneral, head (colour class), heading, icons, bg, op, mark}
 * @returns {String}
 */
const drawIndex = (x, y, w, h, o) => {
    o = o || {};
    let s = '<svg x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" overflow="hidden">';
    s += drawRect(0, 0, w, h, 'p-index');
    if (o.bg) {
        s +=
            '<rect class="p-c" x="0" y="0" width="' +
            w +
            '" height="' +
            h +
            '" fill-opacity="' +
            (o.op || 0.16) +
            '"/>';
    }
    let cy = 0;
    if (o.plain) {
        s += drawText(8, 14, str('pv_course_index'), 'x9 b k2');
        cy = 22;
        indexRows().forEach((r) => {
            if (r.general && o.nogeneral) {
                return;
            }
            if (r.sec) {
                s += drawIcon(r.open ? 'chevd' : 'chevr', 5, cy + 3, 10, 'ic-2') + drawText(17, cy + 11, r.sec, 'x9 b');
            } else {
                s += (r.current ? drawRect(0, cy, w, 17, 'p-brandsoft') : '') + drawText(17, cy + 11, r.act, 'x9 kb');
            }
            cy += 17;
        });
        return s + drawLine(w - 0.5, 0, w - 0.5, h, 'p-line') + '</svg>';
    }
    // Player header: logo row, course title, total time, ring.
    if (o.logo) {
        s +=
            drawRect(0, 0, w, 22, 'p-surf0') +
            drawRect(7, 4, 14, 14, 'p-brand', 3) +
            drawText(14, 14.5, 'D', 'x9 b kw', 'm') +
            drawText(25, 14.5, str('pv_dari_academy'), 'x9 b');
        cy = 22;
    } else {
        s +=
            drawIcon('grid', w - 46, 4, 10, 'ic-2') +
            drawIcon('dots', w - 30, 4, 10, 'ic-2') +
            drawIcon('close', w - 15, 4, 10, 'ic-2');
        cy = 16;
    }
    s += drawRect(0, cy, w, 34, o.head ? 'p-c' : 'p-head');
    const ink = o.head ? 'kw' : 'kb';
    s += drawText(7, cy + 14, w < 130 ? 'WHS101' : str('pv_whs_for_supervisors'), 'x9 b ' + ink + (o.head ? ' onc' : ''));
    if (o.total !== false) {
        s += drawText(7, cy + 26, str('pv_1_hr_10_min'), 'x8 ' + (o.head ? 'kw onc' : 'k2'));
    }
    if (w >= 90) {
        s += drawRing(w - 16, cy + 17, 10, 42, !!o.head);
    }
    if (o.mark === 'total' && o.total !== false) {
        s += drawHighlight(4, cy + 18, 50, 12, 3);
    }
    if (o.mark === 'logo' && o.logo) {
        s += drawHighlight(3, 2, Math.min(w - 6, 84), 18, 3);
    }
    if (o.mark === 'header') {
        s += drawHighlight(1, cy + 1, w - 2, 32, 2);
    }
    cy += 34;
    indexRows().forEach((r) => {
        if (r.general && o.nogeneral) {
            return;
        }
        if (r.sec) {
            s +=
                drawRect(0, cy, w, 20, o.heading ? 'p-c' : 'p-brand') +
                drawLine(0, cy + 20, w, cy + 20, 'p-sep') +
                drawIcon(r.open ? 'chevd' : 'chevr', 5, cy + 4, 11, 'ic-w' + (o.heading ? ' onc' : '')) +
                drawText(19, cy + 13.5, r.sec, 'x9 b kw' + (o.heading ? ' onc' : ''));
            cy += 20;
        } else {
            if (r.current) {
                s += drawRect(0, cy, w, 20, 'p-brandsoft') + drawRect(0, cy, 2.5, 20, 'p-brand');
            }
            s +=
                drawRect(6, cy + 4, 12, 12, o.icons ? 'p-cs' : 'p-brandsoft') +
                drawIcon(r.icon, 7, cy + 5, 10, o.icons ? 'ic-c' : 'ic-b');
            const room = w - 24 - (o.time === false ? 16 : 52);
            let name = r.act;
            while (name.length > 4 && tw(name, 9.5) > room) {
                name = name.slice(0, -2).trim() + '…';
                name = name.replace(/…+$/, '…');
            }
            s += drawText(23, cy + 13.5, name, 'x9');
            if (o.time !== false) {
                s += drawRect(w - 50, cy + 5, 32, 11, 'p-chip') + drawText(w - 34, cy + 13.2, r.time, 'x8 k2', 'm');
                if (o.mark === 'time') {
                    s += drawHighlight(w - 52, cy + 3.5, 36, 14, 3);
                }
            }
            s += r.done
                ? drawCircle(w - 9, cy + 10, 5, 'p-ok') + drawIcon('check', w - 14, cy + 5, 10, 'ic-w')
                : drawCircle(w - 9, cy + 10, 4.5, 'p-tick0');
            s += drawLine(0, cy + 20, w, cy + 20, 'p-line');
            cy += 20;
        }
    });
    return s + drawLine(w - 0.5, 0, w - 0.5, h, 'p-line') + '</svg>';
};

/**
 * A section card.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {colour, op, big, small, acts, limit, time, image, mark, title, eyebrow, cta}
 * @returns {String} Markup; the card is as tall as its content.
 */
const drawCard = (x, y, w, o) => {
    o = o || {};
    const mh = o.mh || 56;
    let s = '';
    if (o.image) {
        s +=
            '<svg x="' +
            (x + 4) +
            '" y="' +
            (y + 4) +
            '" width="' +
            (w - 8) +
            '" height="' +
            mh +
            '">' +
            drawPhoto(0, 0, w - 8, mh) +
            '</svg>';
    } else {
        s +=
            drawRect(x + 4, y + 4, w - 8, mh, 'p-surf0', 5) +
            '<rect class="' +
            (() => {
                if (o.colour) {
                    return 'p-c';
                }
                return 'p-brand';
            })() +
            '" x="' +
            (x + 4) +
            '" y="' +
            (y + 4) +
            '" width="' +
            (w - 8) +
            '" height="' +
            mh +
            '" rx="5" fill-opacity="' +
            (() => {
                if (o.op === undefined) {
                    return 1;
                }
                return o.op;
            })() +
            '"/>';
    }
    const onpanel = o.op === undefined || o.op > 0.45 || o.image;
    const onc = (() => {
        if (o.colour) {
            return ' onc';
        }
        return '';
    })();
    const mini = w < 110;
    if (mh >= 32) {
        s +=
            drawRect(x + 9, y + 9, tw(captionOr(o.eyebrow, str('pv_module_2')), 8, true) + 10, 12, 'p-glass', 6) +
            drawText(
                x + 14,
                y + 17.5,
                captionOr(o.eyebrow, str('pv_module_2')),
                'x8 b ' +
                    (() => {
                        if (onpanel) {
                            return 'kw' + onc;
                        }
                        return '';
                    })()
            );
    }
    const ts = (() => {
        if (o.big) {
            return 'x13';
        }
        return (() => {
            if (o.small) {
                return 'x9';
            }
            return 'x11';
        })();
    })();
    s += drawText(
        x + 10,
        y + mh - 3,
        captionOr(o.title, str('pv_spotting_hazards')),
        ts +
            ' b ' +
            (() => {
                if (onpanel) {
                    return 'kw' + onc;
                }
                return '';
            })()
    );
    if (o.mark === 'title') {
        s += drawHighlight(
            x + 7,
            y +
                mh -
                (() => {
                    if (o.big) {
                        return 16;
                    }
                    return 13;
                })(),
            tw(
                captionOr(o.title, str('pv_spotting_hazards')),
                (() => {
                    if (o.big) {
                        return 13;
                    }
                    return (() => {
                        if (o.small) {
                            return 9;
                        }
                        return 11;
                    })();
                })(),
                true
            ) + 8,
            (() => {
                if (o.big) {
                    return 17;
                }
                return 14;
            })(),
            3
        );
    }
    if (o.mark === 'panel') {
        s += drawHighlight(x + 3, y + 3, w - 6, mh + 2, 6);
    }
    let cy = y + mh + 10;
    // Chips.
    let cx = x + 8;
    const chip = (k, label, hl) => {
        const cw = tw(label, 8) + 22;
        let c =
            drawRect(cx, cy, cw, 13, 'p-chip', 4) +
            drawIcon(k, cx + 3, cy + 2, 9, 'ic-b') +
            drawText(cx + 15, cy + 9.5, label, 'x8 k2');
        if (hl) {
            c += drawHighlight(cx - 2, cy - 2, cw + 4, 17, 4);
        }
        cx += cw + 4;
        return c;
    };
    if (!mini) {
        s += chip('list', (o.acts || 4) + ' activities');
        if (o.time !== false) {
            s += chip('clock', mins(45), o.mark === 'time');
        }
        cy += 20;
    }
    if (o.list) {
        const names = [
            str('pv_hierarchy_of_control'),
            str('pv_risk_assessment'),
            str('pv_hazard_quiz'),
            str('pv_site_walk'),
            str('pv_toolbox_talk')
        ];
        const n = o.limit || names.length;
        names.slice(0, n).forEach((nm, i) => {
            s +=
                drawIcon(
                    (() => {
                        if (i === 2) {
                            return 'quiz';
                        }
                        return (() => {
                            if (i === 1) {
                                return 'assign';
                            }
                            return 'page';
                        })();
                    })(),
                    x + 8,
                    cy - 1,
                    10,
                    'ic-b'
                ) +
                drawText(x + 21, cy + 7, nm, 'x8') +
                (() => {
                    if (i < 2) {
                        return drawCircle(x + w - 14, cy + 4, 4, 'p-ok') + drawIcon('check', x + w - 18, cy, 8, 'ic-w');
                    }
                    return drawCircle(x + w - 14, cy + 4, 3.6, 'p-tick0');
                })();
            cy += 13;
        });
        if (o.limit && o.limit < names.length) {
            s += drawText(x + 21, cy + 7, '+ ' + (names.length - o.limit) + ' more', 'x8 kb b');
            cy += 13;
        }
        if (o.mark === 'list') {
            s += drawHighlight(x + 5, y + mh + 28, w - 10, cy - (y + mh + 28) + 2, 3);
        }
        cy += 9;
    }
    // Progress.
    if (mini) {
        s +=
            drawRect(x + 8, cy, w - 16, 4, 'p-track', 2) +
            drawRect(x + 8, cy, (w - 16) / 2, 4, 'p-brand', 2) +
            drawText(x + 8, cy + 15, '2 of 4 done', 'x8 b k2');
        cy += 22;
    } else {
        s +=
            drawRect(x + 8, cy, w - 66, 4, 'p-track', 2) +
            drawRect(x + 8, cy, (w - 66) / 2, 4, 'p-brand', 2) +
            drawText(x + w - 8, cy + 5, '2 of 4 done', 'x8 b k2', 'e');
        cy += 12;
    }
    if (o.cta !== false && !mini) {
        s +=
            drawLine(x + 8, cy, x + w - 8, cy, 'p-line') +
            drawText(x + 8, cy + 14, str('pv_in_progress'), 'x8 k3') +
            drawRect(x + w - 66, cy + 4, 58, 15, 'p-brandsoft', 5) +
            drawText(x + w - 37, cy + 14.5, str('pv_continue'), 'x8 b kb', 'm');
        cy += 24;
    }
    const body = (() => {
        if (o.body) {
            return (
                '<rect class="p-c" x="' +
                x +
                '" y="' +
                y +
                '" width="' +
                w +
                '" height="' +
                (cy - y) +
                '" rx="8" fill-opacity="' +
                (() => {
                    if (o.bodyop === undefined) {
                        return 0.35;
                    }
                    return o.bodyop;
                })() +
                '"/>' +
                (() => {
                    if (o.mark === 'body') {
                        return drawHighlight(x - 2, y - 2, w + 4, cy - y + 4, 9);
                    }
                    return '';
                })()
            );
        }
        return '';
    })();
    return drawRect(x, y, w, cy - y, 'p-card', 8) + body + s;
};

/**
 * An activity tile.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {time, title, kind, icon, mark}
 * @returns {String}
 */
const drawTile = (x, y, w, o) => {
    o = o || {};
    const mh = 40;
    let s =
        drawRect(x, y, w, mh + 30, 'p-card', 7) +
        drawRect(x + 3, y + 3, w - 6, mh, 'p-brand', 5) +
        drawRect(x + 8, y + 8, 14, 14, 'p-glass', 3) +
        drawIcon(o.icon || 'page', x + 9, y + 9, 12, 'ic-w') +
        drawText(x + 8, y + mh - 4, w < 100 ? o.short || str('pv_controls') : o.title || str('pv_hierarchy_of_control'), 'x9 b kw');
    let cx = x + 7;
    const cy = y + mh + 10;
    const kind = o.kind || str('pv_page');
    if (w >= 100) {
        s +=
            drawRect(cx, cy, tw(kind, 8) + 20, 13, 'p-chip', 4) +
            drawIcon(o.icon || 'page', cx + 3, cy + 2, 9, 'ic-2') +
            drawText(cx + 14, cy + 9.5, kind, 'x8 k2');
        cx += tw(kind, 8) + 24;
    }
    if (o.time !== false) {
        s +=
            drawRect(cx, cy, 44, 13, 'p-chip', 4) +
            drawIcon('clock', cx + 3, cy + 2, 9, 'ic-b') +
            drawText(cx + 15, cy + 9.5, o.timelabel || mins(10), 'x8 k2');
        if (o.mark === 'time') {
            s += drawHighlight(cx - 2, cy - 2, 48, 17, 4);
        }
    }
    return s;
};

/**
 * Moodle's standard activity list for a section.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const drawStdlist = (x, y, w) => {
    let s = drawText(x, y + 11, str('pv_spotting_hazards'), 'x11 b');
    const rows = [
        [str('pv_hierarchy_of_control'), 'page', str('pv_done_view'), true],
        [str('pv_risk_assessment'), 'assign', str('pv_to_do_submit'), false],
        [str('pv_hazard_quiz'), 'quiz', str('pv_to_do_receive_a_grade'), false]
    ];
    let cy = y + 20;
    rows.forEach((r) => {
        s +=
            drawRect(x, cy, w, 34, 'p-card', 5) +
            drawRect(x + 7, cy + 7, 20, 20, 'p-modico', 4) +
            drawIcon(r[1], x + 11, cy + 11, 12, 'ic-w') +
            drawText(x + 33, cy + 15, r[0], 'x9 b kb');
        const bw = tw(r[2], 8) + 10;
        s +=
            drawRect(x + 33, cy + 20, bw, 11, r[3] ? 'p-okbadge' : 'p-chip', 3) +
            drawText(x + 38, cy + 28, r[2], 'x8 ' + (r[3] ? 'kok b' : 'k2'));
        cy += 39;
    });
    return s;
};

/**
 * The site footer.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const drawFooter = (x, y, w) => {
    let s = drawRect(x, y, w, 40, 'p-foot') + drawText(x + 10, y + 14, str('pv_contact_site_support'), 'x9 kfl');
    s += drawText(x + 10, y + 27, str('pv_data_retention_summary'), 'x9 kfl');
    if (w > 220) {
        s +=
            drawText(x + 130, y + 14, str('pv_get_the_mobile_app'), 'x9 kfl') +
            drawText(x + 130, y + 27, str('pv_powered_by_moodle'), 'x9 kf');
    } else {
        s += drawText(x + 10, y + 37, str('pv_powered_by_moodle'), 'x8 kf');
    }
    return s;
};

/**
 * The Ask Dari panel.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {name (false = no first name), answer, tools, support, quiz, mark}
 * @returns {String}
 */
const drawChat = (x, y, w, h, o) => {
    o = o || {};
    let s =
        '<svg x="' +
        x +
        '" y="' +
        y +
        '" width="' +
        w +
        '" height="' +
        h +
        '" overflow="hidden">' +
        drawRect(0.5, 0.5, w - 1, h - 1, 'p-card', 9) +
        drawRect(0.5, 0.5, w - 1, 30, 'p-brand', 9) +
        drawRect(0.5, 20, w - 1, 11, 'p-brand') +
        drawRect(8, 7, 17, 17, 'p-glass', 4) +
        drawIcon('spark', 10.5, 9.5, 12, 'ic-wf') +
        drawText(31, 14, str('pv_ask_dari'), 'x10 b kw') +
        drawText(31, 25, str('pv_whs_for_supervisors'), 'x8 kw') +
        drawIcon('close', w - 18, 9, 12, 'ic-w');
    let cy = 40;
    const bot = (lines, hl) => {
        const bh = lines.length * 11 + 8;
        let b =
            drawRect(8, cy, 13, 13, 'p-brandsoft', 3) +
            drawIcon('spark', 9.5, cy + 1.5, 10, 'ic-bf') +
            drawRect(25, cy, w - 33, bh, 'p-bubble', 6);
        lines.forEach((l, i) => {
            b += drawText(31, cy + 12 + i * 11, l, 'x8' + (i === 0 && o.bold ? ' b' : ''));
        });
        if (hl) {
            b += drawHighlight(23, cy - 2, w - 29, bh + 4, 6);
        }
        cy += bh + 6;
        return b;
    };
    const me = (l) => {
        const bw = Math.min(w - 40, tw(l, 8) + 14);
        const b = drawRect(w - 8 - bw, cy, bw, 18, 'p-brand', 6) + drawText(w - 15, cy + 12, l, 'x8 kw', 'e');
        cy += 24;
        return b;
    };
    s += bot(
        [o.name === false ? str('pv_hi_there_what_are_we_working') : str('pv_hi_sam_what_are_we_working'), str('pv_on_today')],
        o.mark === 'name'
    );
    if (o.question) {
        s += me(o.question);
    }
    if (o.answer) {
        s += bot(o.answer, o.mark === 'answer');
    }
    if (o.tools !== false) {
        s += drawText(8, cy + 8, str('pv_pick_a_tool_or_type_your_own_questio'), 'x8 k2');
        cy += 14;
        [
            ['book', str('pv_explain_the_concept_simply')],
            ['target', str('pv_give_me_some_practice_questions')],
            ['building', str('pv_show_me_a_real_world_example')]
        ].forEach((t) => {
            s +=
                drawRect(8, cy, w - 16, 19, 'p-card', 5) +
                drawRect(12, cy + 3.5, 12, 12, 'p-brandsoft', 3) +
                drawIcon(t[0], 13, cy + 4.5, 10, 'ic-b') +
                drawText(29, cy + 12.5, t[1], 'x8 b');
            cy += 22;
        });
    }
    s +=
        drawRect(8, h - 26, w - 16, 19, 'p-card', 6) +
        drawText(15, h - 13.5, str('pv_ask_about_this_course'), 'x8 k3') +
        drawRect(w - 29, h - 24, 18, 15, 'p-brand', 4) +
        drawIcon('send', w - 26, h - 22.5, 11, 'ic-w');
    return s + '</svg>';
};

/**
 * The tour popover.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {voice: true (narrated) | false (muted) | undefined (no button), lang, mark}
 * @returns {String}
 */
const drawTour = (x, y, w, o) => {
    o = o || {};
    let s =
        drawRect(x, y, w, 108, 'p-pop', 9) +
        drawRect(x, y, w, 3, 'p-brand', 1.5) +
        drawText(x + 10, y + 17, '1 / 8', 'x9 b kb') +
        drawText(x + 10, y + 33, str('pv_welcome_to_your_course'), 'x11 b') +
        drawText(x + 10, y + 47, str('pv_a_one_minute_look_at_how_this'), 'x8 k2') +
        drawText(x + 10, y + 58, str('pv_course_works_skip_any_time'), 'x8 k2');
    if (o.voice !== undefined) {
        s +=
            drawRect(x + w - 26, y + 7, 18, 16, 'p-btn', 4) +
            drawIcon('speaker', x + w - 24, y + 9, 12, 'ic-2') +
            (o.voice
                ? drawIcon('waves', x + w - 24, y + 9, 12, 'ic-b')
                : drawIcon('mute', x + w - 24, y + 9, 12, 'ic-2'));
        if (o.mark === 'voice') {
            s += drawHighlight(x + w - 29, y + 4, 24, 22, 4);
        }
    }
    let dx = x + 10;
    for (let i = 0; i < 8; i++) {
        s += drawRect(dx, y + 68, i === 0 ? 12 : 4, 4, i === 0 ? 'p-brand' : 'p-track', 2);
        dx += i === 0 ? 16 : 7;
    }
    s +=
        drawText(x + 10, y + 95, str('pv_end_tour'), 'x9 k2') +
        drawText(x + w - 74, y + 95, str('pv_back'), 'x9 kb') +
        drawRect(x + w - 46, y + 82, 38, 19, 'p-brand', 5) +
        drawText(x + w - 27, y + 95, str('pv_next'), 'x9 b kw', 'm');
    if (o.lang) {
        s +=
            drawRect(x + 10, y + 113, tw(o.lang, 8) + 26, 15, 'p-chip', 7) +
            drawIcon('speaker', x + 14, y + 115, 11, 'ic-b') +
            drawText(x + 28, y + 123.5, o.lang, 'x8 k2');
    }
    return s;
};

/**
 * The AI connection: Moodle, then the school's AI provider.
 *
 * @param {Object} [o] {mark: 'endpoint' | 'secret' | 'textmodel' | 'imagemodel' | 'content' | 'scene', direct}
 * @returns {String}
 */
const drawFlow = (o) => {
    o = o || {};
    let s =
        drawRect(8, 46, 110, 82, 'p-card', 9) +
        drawRect(18, 56, 20, 20, 'p-brand', 5) +
        drawText(28, 70, 'D', 'x11 b kw', 'm') +
        drawText(44, 65, 'Moodle', 'x11 b') +
        drawText(44, 77, str('pv_with_dari'), 'x8 k2') +
        drawText(18, 96, str('pv_question'), 'x8 k2') +
        drawText(18, 108, str('pv_course_content'), 'x8 k2') +
        drawText(18, 120, str('pv_learner_first_name'), 'x8 k3');
    s +=
        drawRect(242, 46, 110, 82, 'p-card', 9) +
        drawIcon('cloud', 252, 54, 24, 'ic-b') +
        drawText(282, 65, str('pv_your_ai'), 'x11 b') +
        drawText(282, 77, 'provider', 'x11 b') +
        drawText(252, 98, o.direct ? str('pv_openai_compatible') : str('pv_set_in_site_admin'), 'x8 k2') +
        drawText(252, 110, o.direct ? 'endpoint' : '> General > AI', 'x8 k2');
    s +=
        drawLine(122, 74, 236, 74, 'p-arrow') +
        '<path class="p-arrowh" d="M231 70l6 4-6 4"/>' +
        drawText(179, 68, 'question', 'x8 k2', 'm');
    s +=
        drawLine(236, 100, 122, 100, 'p-arrow') +
        '<path class="p-arrowh" d="M127 96l-6 4 6 4"/>' +
        drawText(179, 114, 'answer or image', 'x8 k2', 'm');
    if (o.direct) {
        const rows = [
            ['endpoint', str('pv_endpoint'), 'https://ai.school.edu/v1'],
            ['secret', str('pv_api_key'), '•••••••• 4f2a'],
            ['textmodel', str('pv_text_model'), 'gpt-6-astra'],
            ['imagemodel', str('pv_image_model'), 'gpt-image-2.5-sunburst']
        ];
        rows.forEach((r, i) => {
            const ry = 140 + i * 17;
            s +=
                drawText(150, ry + 9, r[1], 'x8 k2', 'e') +
                drawRect(156, ry, 160, 13, 'p-chip', 3) +
                drawText(161, ry + 9.5, r[2], 'x8');
            if (o.mark === r[0]) {
                s += drawHighlight(154, ry - 2, 164, 17, 4);
            }
        });
        s += drawLine(236, 134, 236, 205, 'p-dash');
    }
    if (o.mark === 'content') {
        s += drawHighlight(14, 100, 90, 12, 3) + drawCallout(14, 150, str('pv_course_content_up_to_your_limit'), 40, 112);
    }
    s += drawText(
        8,
        24,
        o.direct ? str('pv_moodle_4_4_dari_calls_your_provider') : str('pv_dari_uses_your_sites_own_ai_provider'),
        'x10 b'
    );
    return s;
};

/**
 * The standard page: navbar, index, banner and two cards.
 *
 * @param {Object} [o] {index: INDEX options | false, banner: BANNER options | false, card: CARD options}
 * @returns {String}
 */
const drawPage = (o) => {
    o = o || {};
    let s = drawRect(0, 0, W, H, 'p-page', 0) + drawNavbar(0, 0, W);
    const ix = o.index === false ? 0 : 118;
    if (o.index !== false) {
        s += drawIndex(0, 20, 118, H - 20, o.index || {});
    }
    const cx = ix + 8;
    const cw = W - cx - 8;
    let cy = 28 + (o.top || 0);
    if (o.banner !== false) {
        s += drawBanner(cx, cy, cw, 46, o.banner || {});
        cy += 54;
    }
    const half = (cw - 8) / 2;
    const card = Object.assign(
        {
            cta: false
        },
        o.card || {}
    );
    s +=
        drawCard(cx, cy, half, card) +
        drawCard(
            cx + half + 8,
            cy,
            half,
            Object.assign({}, card, {
                title: str('pv_consultation'),
                eyebrow: str('pv_module_3')
            })
        );
    return s;
};

/* --------------------------------------------------------------------------
   The drawings, by setting.
   -------------------------------------------------------------------------- */

/**
 * An image thumbnail in one of the card image styles.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} style photo | illustration | render3d | flat | standard | hd | natural | vivid
 * @returns {String}
 */
const drawThumb = (x, y, w, h, style) => {
    let s = '<svg x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" overflow="hidden">';
    if (style === 'illustration') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#fde7c7"/><circle cx="' +
            w * 0.75 +
            '" cy="' +
            h * 0.3 +
            '" r="' +
            h * 0.13 +
            '" fill="#f59e0b" stroke="#7c2d12" stroke-width="1.2"/>' +
            '<path d="M0 ' +
            h * 0.75 +
            'Q' +
            w * 0.3 +
            ' ' +
            h * 0.4 +
            ' ' +
            w * 0.6 +
            ' ' +
            h * 0.7 +
            'T' +
            w +
            ' ' +
            h * 0.6 +
            'V' +
            h +
            'H0z" fill="#84cc16" stroke="#365314" stroke-width="1.2"/>' +
            '<path d="M' +
            w * 0.2 +
            ' ' +
            h * 0.8 +
            'l6-14 6 14z" fill="#0ea5e9" stroke="#0c4a6e" stroke-width="1.2"/>';
    } else if (style === 'render3d') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#e0e7ff"/><defs><radialGradient id="pvr' +
            ++uid +
            '" cx=".35" cy=".35" r=".7"><stop offset="0" stop-color="#fff"/><stop offset=".5" stop-color="#60a5fa"/>' +
            '<stop offset="1" stop-color="#1e3a8a"/></radialGradient></defs>' +
            '<ellipse cx="' +
            w * 0.5 +
            '" cy="' +
            h * 0.82 +
            '" rx="' +
            w * 0.32 +
            '" ry="5" fill="#94a3b8" opacity=".5"/>' +
            '<circle cx="' +
            w * 0.5 +
            '" cy="' +
            h * 0.5 +
            '" r="' +
            h * 0.3 +
            '" fill="url(#pvr' +
            uid +
            ')"/>';
    } else if (style === 'flat') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#dbeafe"/><rect x="' +
            w * 0.15 +
            '" y="' +
            h * 0.45 +
            '" width="' +
            w * 0.3 +
            '" height="' +
            h * 0.4 +
            '" fill="#2563eb"/><circle cx="' +
            w * 0.68 +
            '" cy="' +
            h * 0.55 +
            '" r="' +
            h * 0.2 +
            '" fill="#f97316"/><rect y="' +
            h * 0.85 +
            '" width="' +
            w +
            '" height="' +
            h * 0.15 +
            '" fill="#1e293b"/>';
    } else {
        s += drawPhoto(0, 0, w, h, style === 'vivid' ? 'vivid' : '');
        if (style === 'standard') {
            const drawStandardTexture = () => {
                for (let i = 0; i < w; i += 6) {
                    for (let j = 0; j < h; j += 6) {
                        if ((i + j) % 12 === 0) {
                            s +=
                                '<rect x="' +
                                i +
                                '" y="' +
                                j +
                                '" width="6" height="6" fill="#fff" fill-opacity=".1"/>';
                        }
                    }
                }
            };
            drawStandardTexture();
        }
    }
    return s + '</svg>' + drawRect(x + 0.5, y + 0.5, w - 1, h - 1, 'p-edge', 3);
};

/**
 * Show/hide values as Moodle offers them.
 */
const drawings = {
    accentcolour: () => {
        return {
            cap: str('pv_the_accent_colour_on_the_course_page'),
            svg:
                drawPage({
                    top: 16,
                    index: {
                        heading: true
                    },
                    card: {
                        colour: true
                    }
                }) +
                drawHighlight(171, 62, 22, 22, 4) +
                drawHighlight(1, 70, 116, 22, 2) +
                drawCallout(210, 24, str('pv_accent_colour'), 182, 62)
        };
    },
    herobannerfade: () => {
        return {
            cap: str('pv_a_banner_without_an_image_untinted_o'),
            svg: drawTwo(
                [
                    str('pv_no_tint'),
                    false,
                    '0',
                    drawBanner(6, 10, 160, 60, {
                        compact: true
                    }) +
                        drawCard(6, 80, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_tinted'),
                    true,
                    '',
                    drawBanner(6, 10, 160, 60, {
                        compact: true,
                        fade: 0.28
                    }) +
                        drawHighlight(5, 9, 162, 62, 2) +
                        drawCard(6, 80, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    heroimageoverlay: () => {
        return {
            cap: str('pv_a_banner_photograph_darkened_lightly'),
            svg: drawThree([
                [
                    str('pv_light'),
                    false,
                    'light',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.25,
                        compact: true
                    }) + drawText(57, 156, str('pv_photo_shows_most'), 'x8 k2', 'm')
                ],
                [
                    str('pv_medium'),
                    true,
                    'medium',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.45,
                        compact: true
                    }) +
                        drawHighlight(3, 9, 108, 122, 2) +
                        drawText(57, 156, str('pv_recommended'), 'x8 kb b', 'm')
                ],
                [
                    str('pv_strong'),
                    false,
                    'strong',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.7,
                        compact: true
                    }) + drawText(57, 156, str('pv_text_stands_out_most'), 'x8 k2', 'm')
                ]
            ])
        };
    },
    scrimstrength: () => {
        return {
            cap: str('pv_a_banner_photograph_darkened_lightly'),
            svg: drawThree([
                [
                    str('pv_light'),
                    false,
                    'light',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.25,
                        compact: true
                    }) + drawText(57, 156, str('pv_photo_shows_most'), 'x8 k2', 'm')
                ],
                [
                    str('pv_medium'),
                    true,
                    'medium',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.45,
                        compact: true
                    }) +
                        drawHighlight(3, 9, 108, 122, 2) +
                        drawText(57, 156, str('pv_recommended'), 'x8 kb b', 'm')
                ],
                [
                    str('pv_strong'),
                    false,
                    'strong',
                    drawBanner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.7,
                        compact: true
                    }) + drawText(57, 156, str('pv_text_stands_out_most'), 'x8 k2', 'm')
                ]
            ])
        };
    },
    playerlogo: () => {
        return {
            cap: str('pv_your_logo_at_the_top_of_the_course_i'),
            svg:
                drawPage({
                    top: 16,
                    index: {
                        logo: true,
                        mark: 'logo'
                    }
                }) + drawCallout(140, 24, str('pv_your_logo_here'), 88, 31)
        };
    },
    indexheadingcolour: () => {
        return {
            cap: str('pv_the_section_heading_bands_in_the_cou'),
            svg:
                drawPage({
                    index: {
                        heading: true
                    }
                }) +
                drawHighlight(1, 70, 116, 22, 2) +
                drawHighlight(1, 132, 116, 22, 2) +
                drawCallout(132, 190, str('pv_section_headings'), 112, 143)
        };
    },
    indexiconcolour: () => {
        return {
            cap: str('pv_the_activity_icons_in_the_course_ind'),
            svg:
                drawPage({
                    index: {
                        icons: true
                    }
                }) +
                drawHighlight(4, 92, 18, 16, 3) +
                drawHighlight(4, 154, 18, 56, 3) +
                drawCallout(132, 190, str('pv_activity_icons'), 22, 160)
        };
    },
    indexcolour: () => {
        return {
            cap: str('pv_the_course_index_background_tinted_w'),
            svg:
                drawPage({
                    index: {
                        bg: true,
                        op: 0.22
                    }
                }) +
                drawHighlight(1, 21, 116, 192, 2) +
                drawCallout(132, 190, str('pv_index_background'), 112, 170)
        };
    },
    indexopacity: () => {
        return {
            cap: str('pv_the_course_index_colour_at_a_weak_st'),
            svg: drawTwo(
                [
                    str('pv_weak'),
                    false,
                    '',
                    drawIndex(0, 0, 172, 195, {
                        bg: true,
                        op: 0.1
                    })
                ],
                [
                    str('pv_strong'),
                    true,
                    '',
                    drawIndex(0, 0, 172, 195, {
                        bg: true,
                        op: 0.45
                    })
                ]
            )
        };
    },
    cardcolour: () => {
        return {
            cap: str('pv_the_background_of_each_section_card'),
            svg:
                drawPage({
                    card: {
                        body: true,
                        bodyop: 0.3,
                        mark: 'body'
                    }
                }) + drawCallout(140, 196, str('pv_card_background'), 180, 176)
        };
    },
    cardopacity: () => {
        return {
            cap: str('pv_the_card_background_colour_at_a_weak'),
            svg: drawTwo(
                [
                    str('pv_weak'),
                    false,
                    '',
                    drawCard(10, 8, 152, {
                        body: true,
                        bodyop: 0.12
                    })
                ],
                [
                    str('pv_strong'),
                    true,
                    '',
                    drawCard(10, 8, 152, {
                        body: true,
                        bodyop: 0.45,
                        mark: 'body'
                    })
                ]
            )
        };
    },
    playerheadercolour: () => {
        return {
            cap: str('pv_the_band_at_the_top_of_the_course_in'),
            svg:
                drawPage({
                    top: 16,
                    index: {
                        head: true,
                        mark: 'header'
                    }
                }) + drawCallout(140, 26, str('pv_index_header_band'), 116, 46)
        };
    },
    colourmode: () => {
        return {
            cap: str('pv_the_same_course_page_in_light_mode_o'),
            svg: drawTwo(
                [
                    str('pv_light'),
                    false,
                    'light',
                    drawGroup(
                        drawIndex(0, 0, 70, 195, {
                            time: false
                        }) +
                            drawBanner(76, 6, 94, 46, {
                                compact: true
                            }) +
                            drawCard(76, 58, 94, {
                                cta: false,
                                small: true
                            }),
                        0,
                        0,
                        'pv-light'
                    )
                ],
                [
                    str('pv_dark'),
                    true,
                    'dark',
                    drawGroup(
                        drawIndex(0, 0, 70, 195, {
                            time: false
                        }) +
                            drawBanner(76, 6, 94, 46, {
                                compact: true
                            }) +
                            drawCard(76, 58, 94, {
                                cta: false,
                                small: true
                            }),
                        0,
                        0,
                        'pv-dark'
                    )
                ]
            )
        };
    },
    fontfamily: () => {
        return {
            cap: str('pv_a_sample_of_the_course_page_text_in'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawRect(14, 14, 120, 120, 'p-card', 12) +
                '<text class="x pv-font x-aa" x="74" y="96" text-anchor="middle">Aa</text>' +
                drawText(150, 40, str('pv_leading_safe_workplaces'), 'pv-font x13 b') +
                drawText(150, 58, str('pv_3_modules_5_activities_1_hr_10_min'), 'pv-font x10 k2') +
                drawText(150, 86, str('pv_spotting_hazards'), 'pv-font x11 b') +
                drawText(150, 102, str('pv_find_what_could_hurt_someone'), 'pv-font x9 k2') +
                drawText(150, 114, str('pv_then_decide_what_to_do_about_it'), 'pv-font x9 k2') +
                drawRect(150, 122, 70, 17, 'p-brandsoft', 5) +
                drawText(185, 134, str('pv_continue'), 'pv-font x9 b kb', 'm') +
                '<text class="x pv-fontname x10 b kb" x="14" y="160"></text>' +
                '<text class="x pv-fontnote x8 k3" x="14" y="176"></text>' +
                drawText(14, 200, 'ABCDEFGHIJKLM abcdefghijklm 0123456789', 'x9 k2 pv-font')
        };
    },
    showherobanner: () => {
        return {
            cap: str('pv_the_course_page_without_the_banner_o'),
            svg: drawTwo(
                [
                    str('pv_off'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawCrumbs(6, 32, 2) +
                        drawText(6, 50, str('pv_whs_for_supervisors'), 'x11 b') +
                        drawCard(6, 60, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_on'),
                    true,
                    '1',
                    drawNavbar(0, 0, 172) +
                        drawBanner(6, 26, 160, 62) +
                        drawHighlight(5, 25, 162, 64, 2) +
                        drawCard(6, 96, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    herosticky: () => {
        return {
            cap: str('pv_after_scrolling_down_on_the_left_the'),
            svg: drawTwo(
                [
                    str('pv_scrolls_away'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawCard(6, 26, 160, {
                            list: true,
                            limit: 3,
                            title: str('pv_consultation'),
                            eyebrow: str('pv_module_3')
                        }) +
                        drawLine(166, 40, 166, 140, 'p-scroll')
                ],
                [
                    str('pv_stays_at_top'),
                    true,
                    '1',
                    drawNavbar(0, 0, 172) +
                        drawCard(6, 70, 160, {
                            list: true,
                            limit: 3,
                            title: str('pv_consultation'),
                            eyebrow: str('pv_module_3')
                        }) +
                        drawRect(0, 20, 172, 58, 'p-shadow') +
                        drawBanner(0, 20, 172, 58, {
                            compact: true
                        }) +
                        drawHighlight(1, 21, 170, 56, 2)
                ]
            )
        };
    },
    heroattop: () => {
        return {
            cap: str('pv_the_banner_under_the_course_tabs_on'),
            svg: drawTwo(
                [
                    str('pv_below_the_tabs'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawText(6, 36, str('pv_whs_for_supervisors'), 'x11 b') +
                        drawTabs(6, 42, 160) +
                        drawBanner(6, 66, 160, 56, {
                            compact: true
                        }) +
                        drawCard(6, 130, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_above_the_tabs'),
                    true,
                    '1',
                    drawNavbar(0, 0, 172) +
                        drawBanner(6, 26, 160, 56, {
                            compact: true
                        }) +
                        drawHighlight(5, 25, 162, 58, 2) +
                        drawTabs(6, 88, 160) +
                        drawCard(6, 112, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    displayascards: () => {
        return {
            cap: str('pv_the_course_home_as_moodles_long_page'),
            svg: drawTwo(
                [str('pv_off_long_page'), false, '0', drawStdlist(6, 8, 160)],
                [
                    str('pv_on_cards'),
                    true,
                    '1',
                    drawCard(6, 8, 77, {
                        small: true,
                        cta: false,
                        title: str('pv_duty_of_care'),
                        eyebrow: str('pv_module_1')
                    }) +
                        drawCard(89, 8, 77, {
                            small: true,
                            cta: false,
                            title: str('pv_hazards')
                        }) +
                        drawCard(6, 112, 77, {
                            small: true,
                            cta: false,
                            title: str('pv_consultation'),
                            eyebrow: str('pv_module_3')
                        })
                ]
            )
        };
    },
    cardlayout: () => {
        return {
            cap: str('pv_section_cards_side_by_side_in_a_grid'),
            svg: drawTwo(
                [
                    str('pv_grid'),
                    false,
                    '0',
                    drawCard(6, 8, 77, {
                        small: true,
                        cta: false,
                        title: str('pv_duty_of_care'),
                        eyebrow: str('pv_module_1')
                    }) +
                        drawCard(89, 8, 77, {
                            small: true,
                            cta: false,
                            title: str('pv_hazards')
                        }) +
                        drawCard(6, 112, 77, {
                            small: true,
                            cta: false,
                            title: str('pv_consultation'),
                            eyebrow: str('pv_module_3')
                        })
                ],
                [
                    str('pv_list'),
                    true,
                    '1',
                    drawCard(6, 8, 160, {
                        mh: 34,
                        cta: false,
                        title: str('pv_your_duty_of_care'),
                        eyebrow: str('pv_module_1')
                    }) +
                        drawCard(6, 98, 160, {
                            mh: 34,
                            cta: false
                        })
                ]
            )
        };
    },
    showactivitiesoncards: () => {
        return {
            cap: str('pv_a_section_card_without_its_activity'),
            svg: drawTwo(
                [str('pv_off'), false, '0', drawCard(10, 8, 152, {})],
                [
                    str('pv_on'),
                    true,
                    '1',
                    drawCard(10, 8, 152, {
                        list: true,
                        limit: 4,
                        mark: 'list'
                    })
                ]
            )
        };
    },
    cardactivitylimit: () => {
        return {
            cap: str('pv_a_section_card_listing_its_first_thr'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawCard(10, 6, 196, {
                    list: true,
                    limit: 3,
                    mark: 'list',
                    cta: false
                }) +
                drawCallout(214, 60, str('pv_shows_3_then_2_more'), 200, 96)
        };
    },
    cardtitlesize: () => {
        return {
            cap: str('pv_the_section_card_title_at_a_small_si'),
            svg: drawTwo(
                [
                    'Small',
                    false,
                    '',
                    drawCard(10, 8, 152, {
                        small: true,
                        mark: 'title'
                    })
                ],
                [
                    str('pv_large'),
                    true,
                    '',
                    drawCard(10, 8, 152, {
                        big: true,
                        mark: 'title'
                    })
                ]
            )
        };
    },
    cardimagestyle: () => {
        return {
            cap: str('pv_the_four_looks_for_ai_card_images_ph'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                ['photo', 'illustration', 'render3d', 'flat']
                    .map((st, i) => {
                        const x = 8 + i * 88;
                        const label = [str('pv_photographic'), str('pv_illustration'), str('pv_3d_render'), str('pv_flat')][i];
                        return (
                            '<g class="pv-panel" data-v="' +
                            st +
                            '">' +
                            drawRect(x - 2, 30, 84, 120, 'pv-cur', 8) +
                            drawThumb(x + 2, 34, 76, 76, st) +
                            drawText(x + 40, 126, label, 'x9 b', 'm') +
                            drawText(x + 40, 140, '', 'x8 b kb pv-curlabel', 'm') +
                            '</g>'
                        );
                    })
                    .join('') +
                drawText(8, 18, str('pv_generated_card_images'), 'x10 b')
        };
    },
    activitydisplaymode: () => {
        return {
            cap: str('pv_a_section_page_as_moodles_standard_a'),
            svg: drawTwo(
                [str('pv_standard_list'), false, '0', drawStdlist(6, 8, 160)],
                [
                    str('pv_activity_tiles'),
                    true,
                    '1',
                    drawText(6, 19, str('pv_spotting_hazards'), 'x11 b') +
                        drawTile(6, 28, 78, {}) +
                        drawTile(88, 28, 78, {
                            title: str('pv_risk_assessment'),
                            "short": str('pv_assessment'),
                            icon: 'assign',
                            kind: 'Assign'
                        }) +
                        drawTile(6, 104, 78, {
                            title: str('pv_hazard_quiz'),
                            "short": str('pv_hazard_quiz'),
                            icon: 'quiz',
                            kind: 'Quiz'
                        })
                ]
            )
        };
    },
    shownavchevrons: () => {
        return {
            cap: str('pv_an_activity_page_banner_without_then'),
            svg: drawTwo(
                [
                    str('pv_off'),
                    false,
                    '0',
                    drawBanner(6, 10, 160, 56, {
                        compact: true,
                        title: str('pv_hierarchy_of_control')
                    }) +
                        drawText(6, 88, str('pv_eliminate_substitute_isolate'), 'x9 k2') +
                        drawText(6, 101, str('pv_engineer_administrate_ppe'), 'x9 k2')
                ],
                [
                    str('pv_on'),
                    true,
                    '1',
                    drawBanner(6, 10, 160, 56, {
                        compact: true,
                        chevrons: true,
                        title: str('pv_hierarchy_of_control')
                    }) +
                        drawHighlight(72, 44, 40, 21, 10) +
                        drawCallout(70, 74, str('pv_previous_next'), 92, 65) +
                        drawText(6, 110, str('pv_eliminate_substitute_isolate'), 'x9 k2') +
                        drawText(6, 123, str('pv_engineer_administrate_ppe'), 'x9 k2')
                ]
            )
        };
    },
    hidegeneral: () => {
        return {
            cap: str('pv_the_course_index_with_the_general_se'),
            svg: drawTwo(
                [str('pv_show'), false, '0', drawIndex(0, 0, 172, 195, {}) + drawHighlight(1, 51, 170, 21, 2)],
                [
                    str('pv_hide'),
                    true,
                    '1 2',
                    drawIndex(0, 0, 172, 195, {
                        nogeneral: true
                    })
                ]
            )
        };
    },
    indexstate: () => {
        return {
            cap: str('pv_a_course_opened_with_the_index_colla'),
            svg: drawTwo(
                [
                    str('pv_start_collapsed'),
                    false,
                    '1',
                    drawNavbar(0, 0, 172) +
                        drawRect(4, 26, 18, 18, 'p-btn', 5) +
                        drawIcon('list', 7, 29, 12, 'ic-b') +
                        drawHighlight(2, 24, 22, 22, 6) +
                        drawBanner(28, 26, 140, 52, {
                            compact: true
                        }) +
                        drawCard(28, 86, 140, {
                            cta: false
                        })
                ],
                [
                    str('pv_start_open'),
                    true,
                    '2',
                    drawNavbar(0, 0, 172) +
                        drawIndex(0, 20, 82, 175, {
                            time: false
                        }) +
                        drawHighlight(1, 21, 80, 173, 2) +
                        drawBanner(88, 26, 80, 52, {
                            compact: true
                        }) +
                        drawCard(88, 86, 80, {
                            cta: false,
                            small: true
                        })
                ]
            )
        };
    },
    playerindex: () => {
        return {
            cap: str('pv_moodles_plain_course_index_on_the_le'),
            svg: drawTwo(
                [
                    str('pv_plain_course_index'),
                    false,
                    '0',
                    drawIndex(0, 0, 172, 195, {
                        plain: true
                    })
                ],
                [str('pv_player_sidebar'), true, '1', drawIndex(0, 0, 172, 195, {})]
            )
        };
    },
    showcourseindex: () => {
        return {
            cap:
                str('pv_the_course_index_beside_the_course_p') + ' ' +
                str('pv_course_home_sections_activities2'),
            svg:
                drawPage({}) +
                drawHighlight(1, 21, 116, 192, 2) +
                drawCallout(130, 186, str('pv_course_home_sections_activities'), 112, 150)
        };
    },
    hidetimeindex: () => {
        return {
            cap: str('pv_course_index_rows_with_their_estimat'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawIndex(0, 0, 172, 195, {
                        mark: 'time'
                    })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1',
                    drawIndex(0, 0, 172, 195, {
                        time: false
                    })
                ]
            )
        };
    },
    hidetimetotal: () => {
        return {
            cap: str('pv_the_total_course_time_under_the_cour'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawIndex(0, 0, 172, 195, {
                        mark: 'total'
                    })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1',
                    drawIndex(0, 0, 172, 195, {
                        total: false
                    })
                ]
            )
        };
    },
    hidetimesectioncards: () => {
        return {
            cap: str('pv_a_section_card_with_its_total_time_c'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawCard(10, 8, 152, {
                        mark: 'time'
                    })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1',
                    drawCard(10, 8, 152, {
                        time: false
                    })
                ]
            )
        };
    },
    hidetimeactivitycards: () => {
        return {
            cap: str('pv_an_activity_tile_with_its_time_chip'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawTile(16, 20, 140, {
                        mark: 'time'
                    })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1',
                    drawTile(16, 20, 140, {
                        time: false
                    })
                ]
            )
        };
    },
    minutes: () => {
        {
            let s = drawRect(0, 0, W, H, 'p-page') + drawText(8, 18, str('pv_assumed_time_per_activity_type'), 'x10 b');
            [
                ['page', str('pv_page'), mins(10)],
                ['forum', str('pv_forum'), mins(10)],
                ['assign', str('pv_assignment'), mins(30)],
                ['book', str('pv_book'), mins(15)],
                ['image', 'H5P', mins(15)]
            ].forEach((r, i) => {
                const y = 30 + i * 21;
                s +=
                    drawRect(8, y, 150, 18, 'p-card', 4) +
                    drawIcon(r[0], 13, y + 3, 12, 'ic-b') +
                    drawText(30, y + 12.5, r[1], 'x9') +
                    drawRect(110, y + 3, 42, 12, 'p-chip', 3) +
                    drawIcon('clock', 113, y + 4.5, 9, 'ic-b') +
                    drawText(124, y + 12, r[2], 'x8 k2');
            });
            s += drawHighlight(108, 31, 46, 104, 4) + drawCallout(176, 40, str('pv_shown_as_time_chips'), 154, 50);
            return {
                cap: str('pv_each_activity_type_with_its_assumed'),
                svg:
                    s +
                    drawIndex(176, 58, 176, 150, {
                        mark: 'time'
                    })
            };
        }
    },
    minutesperquestion: () => {
        return {
            cap: str('pv_a_12_question_quiz_at_2_minutes_a_qu'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawRect(8, 20, 156, 70, 'p-card', 8) +
                drawIcon('quiz', 16, 28, 16, 'ic-b') +
                drawText(38, 40, str('pv_hazard_quiz'), 'x11 b') +
                drawText(16, 60, str('pv_12_questions_2_min'), 'x10') +
                drawText(16, 78, '= ' + mins(24), 'x11 b kb') +
                drawHighlight(13, 66, 60, 16, 4) +
                drawLine(170, 55, 192, 55, 'p-arrow') +
                '<path class="p-arrowh" d="M188 51l6 4-6 4"/>' +
                drawIndex(200, 10, 152, 196, {}) +
                drawText(32, 120, str('pv_questions_are_counted_when'), 'x9 k2') +
                drawText(32, 133, str('pv_the_time_is_worked_out'), 'x9 k2')
        };
    },
    minutesfallback: () => {
        return {
            cap: str('pv_an_activity_type_with_no_time_of_its'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawText(8, 18, str('pv_anything_not_listed'), 'x10 b') +
                drawRect(8, 30, 168, 20, 'p-card', 4) +
                drawIcon('image', 14, 34, 12, 'ic-b') +
                drawText(31, 43.5, str('pv_lesson_no_time_set'), 'x9') +
                drawRect(130, 34, 40, 12, 'p-chip', 3) +
                drawText(150, 43, mins(20), 'x8 k2', 'm') +
                drawHighlight(127, 32, 46, 16, 4) +
                drawCallout(30, 64, str('pv_uses_the_fallback_time'), 140, 48) +
                drawTile(8, 96, 168, {
                    title: str('pv_lesson'),
                    icon: 'image',
                    kind: 'Lesson',
                    mark: 'time',
                    timelabel: mins(20)
                }) +
                drawIndex(188, 10, 164, 196, {})
        };
    },
    hidesecondarynav: () => {
        return {
            cap: str('pv_the_course_tabs_course_settings_part'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawText(6, 36, str('pv_whs_for_supervisors'), 'x11 b') +
                        drawTabs(6, 44, 160) +
                        drawHighlight(4, 42, 164, 20, 3) +
                        drawBanner(6, 70, 160, 50, {
                            compact: true
                        }) +
                        drawCard(6, 128, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1 2',
                    drawNavbar(0, 0, 172) +
                        drawText(6, 36, str('pv_whs_for_supervisors'), 'x11 b') +
                        drawBanner(6, 44, 160, 50, {
                            compact: true
                        }) +
                        drawCard(6, 102, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    coursenavplace: () => {
        return {
            cap: str('pv_the_course_tabs_under_the_page_title'),
            svg: drawTwo(
                [
                    str('pv_below_the_banner'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawBanner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        drawTabs(6, 82, 160) +
                        drawHighlight(4, 80, 164, 20, 3) +
                        drawCard(6, 106, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_in_the_site_header'),
                    true,
                    '1',
                    drawNavbar(0, 0, 172, {
                        tabs: true
                    }) +
                        drawHighlight(58, 2, 112, 16, 3) +
                        drawBanner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        drawCard(6, 84, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    immersive: () => {
        return {
            cap: str('pv_the_site_logo_band_above_the_navbar'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawLogoband(0, 0, 172) +
                        drawHighlight(1, 1, 170, 24, 2) +
                        drawNavbar(0, 26, 172) +
                        drawBanner(6, 52, 160, 50, {
                            compact: true
                        }) +
                        drawCard(6, 110, 160, {
                            cta: false
                        })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1 2',
                    drawNavbar(0, 0, 172) +
                        drawBanner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        drawCard(6, 84, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    hidefooter: () => {
        return {
            cap: str('pv_the_site_footer_with_its_links_at_th'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawCard(6, 6, 160, {
                        cta: false
                    }) +
                        drawFooter(0, 150, 172) +
                        drawHighlight(1, 151, 170, 38, 2)
                ],
                [
                    str('pv_hide'),
                    true,
                    '1 2',
                    drawCard(6, 6, 160, {
                        cta: true
                    })
                ]
            )
        };
    },
    hidebreadcrumb: () => {
        return {
            cap:
                str('pv_the_breadcrumb_trail_dashboard_my_co') +
                ' ' + str('pv_spotting_hazards_shown_on_the_left_a'),
            svg: drawTwo(
                [
                    str('pv_show'),
                    false,
                    '0',
                    drawNavbar(0, 0, 172) +
                        drawCrumbs(6, 36, 3) +
                        drawText(6, 36 + 13, str('pv_spotting_hazards2'), 'x9 k2') +
                        drawHighlight(3, 26, 166, 28, 3) +
                        drawText(6, 72, str('pv_spotting_hazards'), 'x13 b') +
                        drawBanner(6, 82, 160, 48, {
                            compact: true
                        })
                ],
                [
                    str('pv_hide'),
                    true,
                    '1 2',
                    drawNavbar(0, 0, 172) +
                        drawText(6, 44, str('pv_spotting_hazards'), 'x13 b') +
                        drawBanner(6, 54, 160, 48, {
                            compact: true
                        }) +
                        drawCard(6, 110, 160, {
                            cta: false,
                            mh: 30
                        })
                ]
            )
        };
    },
    enabletutor: () => {
        return {
            cap: str('pv_the_ask_dari_panel_a_greeting_a_lear'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawBanner(6, 8, 150, 60, {
                    compact: true
                }) +
                drawHighlight(54, 32, 20, 20, 4) +
                drawCallout(18, 82, str('pv_opens_ask_dari'), 64, 52) +
                drawChat(170, 4, 184, 206, {})
        };
    },
    sendfirstname: () => {
        return {
            cap: str('pv_ask_dari_greeting_a_learner_without'),
            svg: drawTwo(
                [
                    str('pv_off'),
                    false,
                    '0',
                    drawChat(4, 4, 164, 186, {
                        name: false,
                        tools: true,
                        mark: 'name'
                    })
                ],
                [
                    str('pv_on'),
                    true,
                    '1',
                    drawChat(4, 4, 164, 186, {
                        mark: 'name'
                    })
                ]
            )
        };
    },
    supportcontacts: () => {
        return {
            cap: str('pv_ask_dari_answering_a_learner_who_is'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawChat(70, 4, 220, 206, {
                    tools: false,
                    question: str('pv_i_feel_really_stressed'),
                    answer: [
                        str('pv_that_sounds_hard_you_can_talk_to'),
                        str('pv_student_wellbeing_ext_2040'),
                        str('pv_lifeline_13_11_14')
                    ],
                    mark: 'answer'
                }) +
                drawCallout(4, 150, str('pv_your_contacts'), 92, 120)
        };
    },
    maxcontextchars: () => {
        return {
            cap: str('pv_dari_sends_the_question_and_course_c'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawFlow({
                    direct: !!document.getElementById('id_s_format_dari_directendpoint'),
                    mark: 'content'
                })
        };
    },
    shareassessmentanswers: () => {
        return {
            cap: str('pv_ask_dari_explaining_a_quiz_result_wi'),
            svg: drawTwo(
                [
                    str('pv_never_share'),
                    false,
                    '0',
                    drawChat(4, 4, 164, 186, {
                        tools: false,
                        question: str('pv_why_did_i_get_q3_wrong'),
                        answer: [
                            str('pv_i_cant_see_your_answers_tell_me'),
                            str('pv_what_you_chose_and_well_work'),
                            str('pv_through_it_together')
                        ]
                    })
                ],
                [
                    str('pv_share'),
                    true,
                    '1',
                    drawChat(4, 4, 164, 186, {
                        tools: false,
                        question: str('pv_why_did_i_get_q3_wrong'),
                        answer: [
                            str('pv_you_chose_b_ppe_first_the'),
                            str('pv_hierarchy_starts_with_removing'),
                            str('pv_the_hazard_a_elimination')
                        ],
                        mark: 'answer'
                    })
                ]
            )
        };
    },
    directendpoint: () => {
        return {
            cap: str('pv_moodle_4_4_dari_sends_requests_strai'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawFlow({
                    direct: true,
                    mark: 'endpoint'
                })
        };
    },
    directapikey: () => {
        return {
            cap: str('pv_moodle_4_4_the_api_key_dari_uses_wit'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawFlow({
                    direct: true,
                    mark: 'secret'
                })
        };
    },
    directtextmodel: () => {
        return {
            cap: str('pv_moodle_4_4_the_model_that_writes_ask'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawFlow({
                    direct: true,
                    mark: 'textmodel'
                })
        };
    },
    directimages: () => {
        return {
            cap: str('pv_moodle_4_4_banners_and_card_images_d'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawFlow({
                    direct: true,
                    mark: 'imagemodel'
                })
        };
    },
    tourvoiceover: () => {
        return {
            cap: str('pv_the_tour_step_with_narration_muted_o'),
            svg: drawTwo(
                [
                    str('pv_off'),
                    false,
                    '0',
                    drawRect(0, 0, 172, 195, 'p-dim') +
                        drawTour(8, 30, 156, {
                            voice: false,
                            mark: 'voice'
                        })
                ],
                [
                    str('pv_on'),
                    true,
                    '1',
                    drawRect(0, 0, 172, 195, 'p-dim') +
                        drawTour(8, 30, 156, {
                            voice: true,
                            mark: 'voice'
                        })
                ]
            )
        };
    },
    tourvoice: () => {
        return {
            cap: str('pv_a_tour_step_read_aloud_in_the_chosen'),
            svg:
                drawRect(0, 0, W, H, 'p-page') +
                drawBanner(8, 8, 344, 52) +
                drawRect(7, 7, 346, 54, 'pv-spot', 2) +
                drawTour(96, 72, 200, {
                    voice: true,
                    lang: str('pv_narration_english_australia')
                }) +
                drawHighlight(104, 183, 160, 19, 8) +
                drawCallout(276, 188, str('pv_voice_language'), 264, 192)
        };
    }
};
/**
 * The drawing and caption for one setting.
 *
 * @param {String} base The setting name without its default/force prefix.
 * @returns {Object|null} {svg, cap}
 */
const draw = (base) => {
    const drawing = drawings[base];
    if (Object.prototype.hasOwnProperty.call(drawings, base) && typeof drawing === 'function') {
        return drawing();
    }
    return null;
};

/**
 * The colour a colour setting has now, or '' when it has none.
 *
 * @param {Element} item The setting row.
 * @returns {String}
 */
const colourOf = (item) => {
    const input = item.querySelector('input[type="text"], input[type="color"]');
    const v = input ? input.value.trim() : '';
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v) ? v : '';
};

/**
 * Relative luminance of a hex colour, 0 (black) to 1 (white).
 *
 * @param {String} hex #rgb or #rrggbb.
 * @returns {Number}
 */
const luminance = (hex) => {
    let h = hex.replace('#', '');
    if (h.length === 3) {
        h = h
            .split('')
            .map((c) => c + c)
            .join('');
    }
    const ch = [0, 2, 4].map((i) => {
        const v = parseInt(h.substr(i, 2), 16) / 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * ch[0] + 0.7152 * ch[1] + 0.0722 * ch[2];
};

/**
 * Build the preview for one setting, and keep it in step with the setting's control.
 *
 * @param {String} name The setting's own name.
 * @param {Element} item The setting row.
 * @param {Object} t Localised labels {appliestoall, allcourses, current}.
 * @returns {Array} [figure, caption] elements, or [] when the setting has no drawing.
 */
export const buildSettingPreview = (name, item, t) => {
    const isforce = /^force/.test(name);
    const base = name.replace(/^(default|force)/, '');
    const d = draw(base);
    if (!d) {
        return [];
    }
    const fig = document.createElement('figure');
    fig.className = 'dset-fig' + (isforce ? ' dset-fig--all' : '');
    fig.setAttribute('aria-hidden', 'true');
    let svg;
    if (isforce) {
        svg =
            '<svg class="dset-svg" viewBox="0 0 ' +
            (W + 16) +
            ' ' +
            (H + 18) +
            '" focusable="false" role="presentation">' +
            drawRect(16.5, 0.5, W - 1, H - 1, 'pv-sheet back2', 8) +
            drawRect(8.5, 6.5, W - 1, H - 1, 'pv-sheet back1', 8) +
            '<svg x="0" y="18" width="' +
            W +
            '" height="' +
            H +
            '" viewBox="0 0 ' +
            W +
            ' ' +
            H +
            '" overflow="hidden">' +
            drawRect(0, 0, W, H, 'p-page', 8) +
            d.svg +
            '</svg>' +
            drawRect(0.5, 18.5, W - 1, H - 1, 'pv-sheet-edge', 8) +
            drawRect(W - 70, 0, 70, 15, 'co-bg', 7.5) +
            drawText(W - 35, 10.5, t.allcourses || str('pv_all_courses'), 'x9 b kw', 'm') +
            '</svg>';
    } else {
        svg =
            '<svg class="dset-svg" viewBox="0 0 ' +
            W +
            ' ' +
            H +
            '" focusable="false" role="presentation">' +
            '<svg width="' +
            W +
            '" height="' +
            H +
            '" overflow="hidden">' +
            d.svg +
            '</svg>' +
            drawRect(0.5, 0.5, W - 1, H - 1, 'pv-sheet-edge', 8) +
            '</svg>';
    }
    fig.innerHTML = svg;
    if (isforce) {
        const cap = document.createElement('figcaption');
        cap.className = 'dset-figcap';
        cap.textContent = t.appliestoall;
        fig.appendChild(cap);
    }
    const sr = document.createElement('p');
    sr.className = 'dset-figsr visually-hidden sr-only';
    sr.textContent = (isforce ? t.appliestoall + '. ' : '') + d.cap;

    // Keep the drawing in step with the control.
    const control = item.querySelector('select, input[type="checkbox"], input[type="text"], input[type="color"]');
    const sync = () => {
        if (!control) {
            return;
        }
        let value = control.value;
        if (control.type === 'checkbox') {
            value = (() => {
                if (control.checked) {
                    return '1';
                }
                return '0';
            })();
        }
        // Colour settings: paint the drawing in the chosen colour.
        const colour = colourOf(item);
        if (colour) {
            fig.style.setProperty('--pv-c', colour);
        } else {
            fig.style.removeProperty('--pv-c');
        }
        // A near-white choice would show nothing; draw the primary as the sample instead.
        if (colour && luminance(colour) > 0.85) {
            fig.style.removeProperty('--pv-c');
        }
        fig.classList.toggle('pv-c-light', !!colour && luminance(colour) > 0.6 && luminance(colour) <= 0.85);
        // Panels: mark the one matching the current value.
        fig.querySelectorAll('.pv-panel').forEach((p) => {
            const on = (p.getAttribute('data-v') || '').split(' ').indexOf(String(value)) !== -1;
            p.classList.toggle('is-current', on);
            const lab = p.querySelector('.pv-curlabel');
            if (lab) {
                lab.textContent = (() => {
                    if (on) {
                        return t.current || str('pv_current');
                    }
                    return '';
                })();
            }
        });
        // The font.
        if (base === 'fontfamily' && control.tagName === 'SELECT') {
            const opt = control.options[control.selectedIndex];
            const label = (() => {
                if (opt) {
                    return opt.textContent.trim();
                }
                return '';
            })();
            const family = label.replace(/\s*\(.*\)\s*$/, '').trim();
            const theme = value === 'theme';
            let note = '';
            let use = '';
            if (theme) {
                note = str('pv_uses_the_themes_own_font');
            } else if (
                document.fonts &&
                document.fonts.check &&
                document.fonts.check('12px "' + family + '"') &&
                (family === 'DM Sans' || Array.from(document.fonts).some((f) => f.family.replace(/"/g, '') === family))
            ) {
                use = '"' + family + '", ';
                note = str('pv_shown_in') + ' ' + family + '.';
            } else {
                note = str('pv_sample_in_a_similar_font_courses_loa') + ' ' + family + ' ' + str('pv_from_google_fonts');
            }
            fig.style.setProperty(
                '--pv-font',
                use + 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'
            );
            const n = fig.querySelector('.pv-fontname');
            const m = fig.querySelector('.pv-fontnote');
            if (n) {
                n.textContent = (() => {
                    if (theme) {
                        return str('pv_follow_the_theme');
                    }
                    return family;
                })();
            }
            if (m) {
                m.textContent = note;
            }
        }
    };
    if (control) {
        control.addEventListener('change', sync);
        control.addEventListener('input', sync);
    }
    // Moodle's colour picker writes the text input without an input event; watch it.
    if (/colour/.test(base)) {
        item.addEventListener('click', () => window.setTimeout(sync, 50));
        item.addEventListener('keyup', sync);
    }
    sync();
    return [fig, sr];
};
