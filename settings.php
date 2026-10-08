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
 * Site administration settings for the Dari course format.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Dari's documentation on darilearning.com, marked with the Dari elephant.
    $settings->add(new admin_setting_description(
        'format_dari/docslink',
        get_string('docslink', 'format_dari'),
        \format_dari\local\docs::link_html()
    ));

    // Link to the site-wide admin Q&A report.
    $reporturl = new moodle_url('/course/format/dari/admin_report.php');
    $settings->add(new admin_setting_description(
        'format_dari/adminreportlink',
        get_string('admin_report_link', 'format_dari'),
        html_writer::link(
            $reporturl,
            get_string('admin_report_view', 'format_dari'),
            ['class' => 'btn btn-primary', 'target' => '_self']
        )
    ));

    $settings->add(new admin_setting_heading(
        'format_dari/aiassistant',
        get_string('aiassistant', 'format_dari'),
        get_string('aiassistant_settings_desc', 'format_dari')
    ));

    // Where the AI comes from. Dari has no AI service of its own: every request goes through
    // Moodle's AI subsystem to the provider the administrator has enabled for the whole site.
    $settings->add(new admin_setting_description(
        'format_dari/externalservicenotice',
        get_string('externalservice', 'format_dari'),
        get_string(
            \format_dari\local\ai::subsystem_present() ? 'externalservice_desc' : 'externalservice_desc44',
            'format_dari',
            (object) [
            'aiurl' => (new moodle_url('/admin/settings.php', ['section' => 'aiprovider']))->out(false),
            'usageurl' => (new moodle_url('/ai/usage_report.php'))->out(false),
            ]
        )
    ));

    // A live status line, so an administrator can see at a glance whether the tutor and the
    // image tools will work, without opening another page.
    if (\format_dari\local\ai::subsystem_present()) {
        $textok = \format_dari\local\ai::is_available(\format_dari\local\ai::FEATURE_TEXT);
        $imageok = \format_dari\local\ai::is_available(\format_dari\local\ai::FEATURE_IMAGE);
        $status = html_writer::tag(
            'ul',
            html_writer::tag('li', get_string($textok ? 'aistatus_text_ok' : 'aistatus_text_missing', 'format_dari')) .
            html_writer::tag('li', get_string($imageok ? 'aistatus_image_ok' : 'aistatus_image_missing', 'format_dari')),
            ['class' => 'mb-0']
        );
    } else {
        // Moodle 4.4: no AI subsystem, so Dari connects directly with the settings below.
        $textok = \format_dari\local\ai::is_available(\format_dari\local\ai::FEATURE_TEXT);
        $imageok = \format_dari\local\ai::is_available(\format_dari\local\ai::FEATURE_IMAGE);
        $status = html_writer::tag(
            'ul',
            html_writer::tag('li', get_string(
                $textok ? 'aistatus_direct_text_ok' : 'aistatus_direct_text_missing',
                'format_dari'
            )) .
            html_writer::tag('li', get_string(
                $imageok ? 'aistatus_direct_image_ok' : 'aistatus_direct_image_missing',
                'format_dari'
            )),
            ['class' => 'mb-0']
        );
    }
    $settings->add(new admin_setting_description(
        'format_dari/aistatus',
        get_string('aistatus', 'format_dari'),
        $status
    ));

    // Moodle 4.4 only: there is no AI subsystem, so the school's own OpenAI-compatible endpoint
    // is entered here. On 4.5 and later these settings are not shown and never used.
    if (!\format_dari\local\ai::subsystem_present()) {
        $settings->add(new admin_setting_heading(
            'format_dari/directheading',
            get_string('directheading', 'format_dari'),
            get_string('directheading_desc', 'format_dari')
        ));
        $settings->add(new admin_setting_configtext(
            'format_dari/directendpoint',
            get_string('directendpoint', 'format_dari'),
            get_string('directendpoint_desc', 'format_dari'),
            '',
            PARAM_URL
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'format_dari/directapikey',
            get_string('directapikey', 'format_dari'),
            get_string('directapikey_desc', 'format_dari'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'format_dari/directtextmodel',
            get_string('directtextmodel', 'format_dari'),
            get_string('directtextmodel_desc', 'format_dari'),
            'gpt-4o-mini',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'format_dari/directimagemodel',
            get_string('directimagemodel', 'format_dari'),
            get_string('directimagemodel_desc', 'format_dari'),
            'gpt-image-1',
            PARAM_TEXT
        ));
    }

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/enabletutor',
        get_string('enabletutor', 'format_dari'),
        get_string('enabletutor_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'format_dari/supportcontacts',
        get_string('supportcontacts', 'format_dari'),
        get_string('supportcontacts_desc', 'format_dari'),
        get_string('supportcontacts_default', 'format_dari'),
        PARAM_TEXT,
        60,
        3
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/sendfirstname',
        get_string('sendfirstname', 'format_dari'),
        get_string('sendfirstname_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/maxcontextchars',
        get_string('maxcontextchars', 'format_dari'),
        get_string('maxcontextchars_desc', 'format_dari'),
        40000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/imagequality',
        get_string('imagequality', 'format_dari'),
        get_string('imagequality_desc', 'format_dari'),
        'standard',
        [
            'standard' => get_string('imagequality_standard', 'format_dari'),
            'hd' => get_string('imagequality_hd', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/imagestyle',
        get_string('imagestyle', 'format_dari'),
        get_string('imagestyle_desc', 'format_dari'),
        'natural',
        [
            'natural' => get_string('imagestyle_natural', 'format_dari'),
            'vivid' => get_string('imagestyle_vivid', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/aiscenewriter',
        get_string('aiscenewriter', 'format_dari'),
        get_string('aiscenewriter_desc', 'format_dari'),
        1
    ));

    // Note: assessment answer keys are opt in, and default to OFF.
    //
    // Ask Dari answers from an index of the course that is transmitted to an external
    // service. That index used to include, unconditionally, the correct-answer marker for every
    // multiple choice question, the per-option feedback that usually gives the answer away, and
    // the essay "information for graders" marking guide -- teacher-only text Moodle never shows
    // a student. Sending an assessment's answer key to a third party cannot be a default, so it
    // requires a deliberate decision by a site administrator.
    //
    // Note: the checkbox became a three-value select so that a single course can opt in
    // without the whole site doing so. The two values the checkbox could store keep their exact
    // meanings -- 0 is still "never" and is still the default, 1 is still "always" -- so a site
    // that had ticked the box behaves identically after the upgrade and nothing needs migrating.
    // This setting is the CEILING: the new per-course option is only consulted at value 2.
    $settings->add(new admin_setting_configselect(
        'format_dari/shareassessmentanswers',
        get_string('shareassessmentanswers', 'format_dari'),
        get_string('shareassessmentanswers_desc', 'format_dari'),
        \format_dari\local\contentindex::SHARE_NEVER,
        [
            \format_dari\local\contentindex::SHARE_NEVER =>
                get_string('shareanswers_never', 'format_dari'),
            \format_dari\local\contentindex::SHARE_ALWAYS =>
                get_string('shareanswers_always', 'format_dari'),
            \format_dari\local\contentindex::SHARE_PERCOURSE =>
                get_string('shareanswers_percourse', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_heading(
        'format_dari/display',
        get_string('displaysettings', 'format_dari'),
        get_string('displaysettings_desc', 'format_dari')
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/scrimstrength',
        get_string('scrimstrength', 'format_dari'),
        get_string('scrimstrength_desc', 'format_dari'),
        // Note: superseded by "Hero image overlay opacity", which expresses the same
        // thing as a number and can be set per course. Kept because existing sites have a value
        // stored here and it is still the fallback for any course whose own overlay is -1.
        'medium',
        [
            'light' => get_string('scrimstrength_light', 'format_dari'),
            'medium' => get_string('scrimstrength_medium', 'format_dari'),
            'strong' => get_string('scrimstrength_strong', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/fontfamily',
        get_string('fontfamily', 'format_dari'),
        get_string('fontfamily_desc', 'format_dari'),
        \format_dari\local\fonts::DEFAULT,
        \format_dari\local\fonts::options()
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/colourmode',
        get_string('colourmode', 'format_dari'),
        get_string('colourmode_desc', 'format_dari'),
        'theme',
        [
            'theme' => get_string('colourmode_theme', 'format_dari'),
            'light' => get_string('colourmode_light', 'format_dari'),
            'dark' => get_string('colourmode_dark', 'format_dari'),
            'device' => get_string('colourmode_device', 'format_dari'),
        ]
    ));

    // Note: site-wide accent colour. Everything the format tints —
    // the hero background, card borders, icon wells, the focus ring — derives
    // from --drf-brand, which normally inherits the theme's primary. This
    // overrides it for dari pages only. Empty = keep following the theme.
    // admin_setting_configcolourpicker gives the real picker-and-swatches UI;
    // it stores '' or a #rrggbb string and validates that itself.
    $settings->add(new admin_setting_configcolourpicker(
        'format_dari/defaultaccentcolour',
        get_string('accentcolour', 'format_dari'),
        get_string('defaultaccentcolour_desc', 'format_dari'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultherobannerfade',
        get_string('herobannerfade', 'format_dari'),
        get_string('herobannerfade_desc', 'format_dari'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/defaultshowherobanner',
        get_string('showherobanner', 'format_dari'),
        get_string('showherobanner_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/defaultdisplayascards',
        get_string('displayascards', 'format_dari'),
        get_string('displayascards_desc', 'format_dari'),
        1
    ));

    // Note (settings audit). Every course format option now has a site-level default
    // here, so what an administrator sets on this page is genuinely what a new course starts
    // with. Twelve of these did not exist before: the course form fell back to a value hard
    // coded in lib.php and this page had no say at all.
    //
    // Naming contract, relied on by format_dari::site_default(): the setting is always
    // 'default' . <the course option name>. Do not rename one side without the other.
    //
    // Each reuses the course option's own label string, so the admin page and the course
    // settings form can never drift apart in wording.

    $settings->add(new admin_setting_configselect(
        'format_dari/defaulthidesecondarynav',
        get_string('hidesecondarynav', 'format_dari'),
        get_string('hidesecondarynav_desc', 'format_dari'),
        // Note: ships as "Hide from students", not "Hide from everyone".
        //
        // Hiding the tabs from everybody also took them from teachers, and with edit mode OFF a
        // teacher then had no route to Site administration, the Dashboard or their other courses
        // from inside a course -- reported from a live site. The tabs are clutter for a learner and
        // a tool for staff, so the shipped default now draws that line instead of removing them for
        // both. An administrator who wants them gone entirely can still choose it.
        1,
        [
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: the override, deliberately placed next to the default so the difference
    // Note: ships as "hide from everyone" rather than "follow each course".
    //
    // The default above only seeds courses that have never saved the option, so on any site with
    // existing courses it changes nothing -- which is exactly the confusion it caused. The
    // override is the control that reaches courses already created, so it is the one that has to
    // carry the intent.
    //
    // This does remove per-course control out of the box, which is a real cost: an administrator
    // who wants some courses to keep the tabs must set this back to "Follow each course". It is a
    // deliberate trade in favour of the format's own navigation, and the tabs are still never
    // hidden while edit mode is on.
    $settings->add(new admin_setting_configselect(
        'format_dari/forcehidesecondarynav',
        get_string('forcehidesecondarynav', 'format_dari'),
        get_string('forcehidesecondarynav_desc', 'format_dari'),
        // Note: 1, not 2 — see the note on the default above. This is the control that
        // actually reaches existing courses, so shipping it as "hide from everyone" is what took
        // the navigation away from teachers on every course at once.
        1,
        [
            -1 => get_string('forcehidesecondarynav_follow', 'format_dari'),
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: a logo for the player sidebar, separate from the site logo.
    $settings->add(new admin_setting_configstoredfile(
        'format_dari/playerlogo',
        get_string('playerlogo', 'format_dari'),
        get_string('playerlogo_desc', 'format_dari'),
        'playerlogo',
        0,
        [
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ]
    ));

    global $PAGE;

    // Note: SCOPE THE SETTINGS UI TO THIS PLUGIN'S OWN PAGE.
    //
    // Reported from a live production Moodle 5.0.1 site (College Australia, RTO 31222) by the
    // Wombat LMS platform team, and correct: this module was being loaded on EVERY administration
    // settings page on the site -- Moodle core's, and unrelated third-party plugins' -- where it
    // restructured settings into collapsible areas, added a search box, and rendered an unsized
    // SVG as a large black rectangle.
    //
    // THE MECHANISM, because it is not obvious and it is easy to reintroduce:
    // Moodle includes EVERY plugin's settings.php on EVERY admin settings page request, because it
    // has to build the whole admin tree to render any part of it. A $PAGE->requires->js_call_amd()
    // sitting at the top level of this file therefore queues that module on every one of those
    // pages, not on ours. The `if ($hassiteconfig)` guard above does not help -- it is true for any
    // administrator, on any page. Nor would `$ADMIN->fulltree`, which is also true while the tree
    // is being built for a different section.
    //
    // The module then found `#adminsettings` -- an id core puts on every settings page -- and went
    // to work on whatever was inside it.
    //
    // The section parameter is the thing that actually identifies the page being rendered, so it is
    // what is checked. On any other section, or on an admin page with no section at all, nothing is
    // queued and the plugin has no effect outside its own settings page.
    //
    // settingsui.js carries the same check independently (see its init()). Two guards, because this
    // one is invisible in the rendered page and the fault it prevents is site-wide.
    //
    // NOTE FOR THE NEXT PERSON: guard the js_call_amd, do NOT `return` here. A bare `return` at
    // this point is legal PHP and stops including the rest of the file -- which would leave every
    // setting declared BELOW this line unregistered whenever the admin tree is built for any other
    // page. The settings would then disappear from admin search and from the tree itself. The
    // registrations must always run; only the JavaScript is page-specific.
    $drfsownpage = (optional_param('section', '', PARAM_ALPHANUMEXT) === 'formatsettingdari');


    // Note: the settings page UI.
    //
    // Sixty-five settings in one column is a scroll, not a page. The module puts a hub of feature
    // cards above them -- each with a wireframe of the part of the course page it governs, and
    // direct links into the settings people actually arrive looking for -- then groups the rest by
    // category and colour-codes them.
    //
    // Only the four labels it cannot derive are passed. The "recently added" filter is gone with
    // the rest of the old UI: it depended on settingsmeta, which does not parse this file's three
    // different ways of declaring a setting and was never trusted enough to be used without a
    // hand-kept fallback list beside it.
    if ($drfsownpage) {
        $PAGE->requires->js_call_amd('format_dari/settingsui', 'init', [[
            'about' => get_string('settingsui_about', 'format_dari'),
            'show' => get_string('settingsui_show', 'format_dari'),
            'hide' => get_string('settingsui_hide', 'format_dari'),
            'course' => get_string('settingsui_course', 'format_dari'),
            'appliestoall' => get_string('settingsui_appliestoall', 'format_dari'),
            'search' => get_string('settingsui_search', 'format_dari'),
            'nomatches' => get_string('settingsui_nomatches', 'format_dari'),
            'settings' => get_string('settingsui_settings', 'format_dari'),
            'setting' => get_string('settingsui_setting', 'format_dari'),
            ]]);
    }

    // Note: say plainly which themes this is built against.
    //
    // The format overrides parts of core's course index, navigation and header. Boost and
    // theme_academi are the two it is developed and measured against; another theme may position
    // those differently and produce a layout fault that looks like a plugin bug. Saying so, and
    // giving people somewhere to write, is more useful than letting them guess.
    $settings->add(new admin_setting_heading(
        'format_dari/themesupport',
        get_string('themesupport', 'format_dari'),
        get_string('themesupport_desc', 'format_dari')
    ));

    // Note: hide the General section.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaulthidegeneral',
        get_string('hidegeneral', 'format_dari'),
        get_string('hidegeneral_desc', 'format_dari'),
        0,
        [
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forcehidegeneral',
        get_string('forcehidegeneral', 'format_dari'),
        get_string('forcehidegeneral_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: the estimated-time pills, four places.
    $drftimes = ['hidetimeindex', 'hidetimesectioncards', 'hidetimeactivitycards', 'hidetimetotal'];
    foreach ($drftimes as $drftime) {
        $settings->add(new admin_setting_configselect(
            'format_dari/default' . $drftime,
            get_string($drftime, 'format_dari'),
            get_string('default' . $drftime . '_desc', 'format_dari'),
            0,
            [
                0 => get_string('hidesecondarynav_show', 'format_dari'),
                1 => get_string('hidetime_hide', 'format_dari'),
            ]
        ));
        $settings->add(new admin_setting_configselect(
            'format_dari/force' . $drftime,
            get_string('force' . $drftime, 'format_dari'),
            get_string('force' . $drftime . '_desc', 'format_dari'),
            -1,
            [
                -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
                0 => get_string('hidesecondarynav_show', 'format_dari'),
                1 => get_string('hidetime_hide', 'format_dari'),
            ]
        ));
    }

    // Note: the sticky banner.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultherosticky',
        get_string('herosticky', 'format_dari'),
        get_string('defaultherosticky_desc', 'format_dari'),
        1,
        [
            0 => get_string('herosticky_no', 'format_dari'),
            1 => get_string('herosticky_yes', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forceherosticky',
        get_string('forceherosticky', 'format_dari'),
        get_string('forceherosticky_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('herosticky_no', 'format_dari'),
            1 => get_string('herosticky_yes', 'format_dari'),
        ]
    ));

    // Note: the section heading band and the activity icon colour.
    foreach (['indexheadingcolour', 'indexiconcolour'] as $drfcolour) {
        $settings->add(new admin_setting_configtext(
            'format_dari/default' . $drfcolour,
            get_string($drfcolour, 'format_dari'),
            get_string('default' . $drfcolour . '_desc', 'format_dari'),
            '',
            PARAM_TEXT
        ));
        $settings->add(new admin_setting_configtext(
            'format_dari/force' . $drfcolour,
            get_string('force' . $drfcolour, 'format_dari'),
            get_string('force' . $drfcolour . '_desc', 'format_dari'),
            '',
            PARAM_TEXT
        ));
    }

    // Note: the course index surface.
    $settings->add(new admin_setting_configtext(
        'format_dari/defaultindexcolour',
        get_string('indexcolour', 'format_dari'),
        get_string('defaultindexcolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultindexopacity',
        get_string('indexopacity', 'format_dari'),
        get_string('defaultindexopacity_desc', 'format_dari'),
        100,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/forceindexcolour',
        get_string('forceindexcolour', 'format_dari'),
        get_string('forceindexcolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/forceindexopacity',
        get_string('forceindexopacity', 'format_dari'),
        get_string('forceindexopacity_desc', 'format_dari'),
        -1,
        PARAM_INT
    ));

    // Note: the card surface.
    $settings->add(new admin_setting_configtext(
        'format_dari/defaultcardcolour',
        get_string('cardcolour', 'format_dari'),
        get_string('defaultcardcolour_desc', 'format_dari'),
        '#fafbfc',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultcardopacity',
        get_string('cardopacity', 'format_dari'),
        get_string('defaultcardopacity_desc', 'format_dari'),
        100,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/forcecardcolour',
        get_string('forcecardcolour', 'format_dari'),
        get_string('forcecardcolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/forcecardopacity',
        get_string('forcecardopacity', 'format_dari'),
        get_string('forcecardopacity_desc', 'format_dari'),
        -1,
        PARAM_INT
    ));

    // Note: the course index header band colour.
    $settings->add(new admin_setting_configtext(
        'format_dari/defaultplayerheadercolour',
        get_string('playerheadercolour', 'format_dari'),
        get_string('defaultplayerheadercolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/forceplayerheadercolour',
        get_string('forceplayerheadercolour', 'format_dari'),
        get_string('forceplayerheadercolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    // Note: this block used to re-register 'format_dari/defaultaccentcolour' as a
    // plain text box. admin_settingpage::add() keys by name, so the LATER registration silently
    // replaced the colour picker declared above at Note -- the picker has been dead code
    // ever since, and administrators have been typing hex codes into a text field with no swatches
    // and no validation. One name, one control: the picker above is the one that is kept.
    //
    // The description string this block used, 'defaultaccentcolour_desc', is left in the language
    // file: it is the more specific of the two and is now used by the picker.

    $settings->add(new admin_setting_configtext(
        'format_dari/forceaccentcolour',
        get_string('forceaccentcolour', 'format_dari'),
        get_string('forceaccentcolour_desc', 'format_dari'),
        '',
        PARAM_TEXT
    ));

    // Note: hide the theme's logo band.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultimmersive',
        get_string('immersive', 'format_dari'),
        get_string('immersive_desc', 'format_dari'),
        // Ships hidden FROM STUDENTS. The band carries the site logo, Dashboard and My
        // courses -- a second, competing navigation directly above a banner that already offers
        // Home and My courses in the hero's own icon panel. Teachers keep it, because they move
        // between a course and the rest of the site constantly while building one; learners are
        // in one course and do not.
        //
        // This is the site DEFAULT, so it seeds NEW courses only: upgrading an existing site
        // changes nothing anywhere. 'Apply to ALL existing courses' below is the setting that
        // reaches courses that already have a stored value.
        1,
        [
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forceimmersive',
        get_string('forceimmersive', 'format_dari'),
        get_string('forceimmersive_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: the course index drawer's starting state.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultindexstate',
        get_string('indexstate', 'format_dari'),
        get_string('indexstate_desc', 'format_dari'),
        0,
        [
            0 => get_string('indexstate_remember', 'format_dari'),
            1 => get_string('indexstate_collapsed', 'format_dari'),
            2 => get_string('indexstate_open', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forceindexstate',
        get_string('forceindexstate', 'format_dari'),
        get_string('forceindexstate_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('indexstate_remember', 'format_dari'),
            1 => get_string('indexstate_collapsed', 'format_dari'),
            2 => get_string('indexstate_open', 'format_dari'),
        ]
    ));

    // Note: the player sidebar. On by default in Dari: the course index is where a learner sees
    // their place, the time each activity takes and what is done, so it ships switched on.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultplayerindex',
        get_string('playerindex', 'format_dari'),
        get_string('playerindex_desc', 'format_dari'),
        1,
        [
            0 => get_string('playerindex_off', 'format_dari'),
            1 => get_string('playerindex_on', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forceplayerindex',
        get_string('forceplayerindex', 'format_dari'),
        get_string('forceplayerindex_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('playerindex_off', 'format_dari'),
            1 => get_string('playerindex_on', 'format_dari'),
        ]
    ));

    // Note: the site footer, default and override, same shape as the pair below.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaulthidefooter',
        get_string('hidefooter', 'format_dari'),
        get_string('hidefooter_desc', 'format_dari'),
        // Ships hidden. The format ends on its own content, and a theme's copyright band
        // directly beneath a full-bleed section grid reads as a strip of a different site. This is
        // a site DEFAULT: it seeds new courses only, so no existing course changes when the plugin
        // is upgraded -- the force setting below is the one that reaches those.
        2,
        [
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forcehidefooter',
        get_string('forcehidefooter', 'format_dari'),
        get_string('forcehidefooter_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: the site default for the breadcrumb, and its override, placed together for
    // the same reason as the pair above: the default only seeds new courses, the override applies
    // everywhere at once.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaulthidebreadcrumb',
        get_string('hidebreadcrumb', 'format_dari'),
        get_string('hidebreadcrumb_desc', 'format_dari'),
        0,
        [
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/forcehidebreadcrumb',
        get_string('forcehidebreadcrumb', 'format_dari'),
        get_string('forcehidebreadcrumb_desc', 'format_dari'),
        -1,
        [
            -1 => get_string('forcehidebreadcrumb_leave', 'format_dari'),
            0 => get_string('hidesecondarynav_show', 'format_dari'),
            1 => get_string('hidesecondarynav_students', 'format_dari'),
            2 => get_string('hidesecondarynav_all', 'format_dari'),
        ]
    ));

    // Note: estimated activity durations.
    $settings->add(new admin_setting_heading(
        'format_dari/timingheading',
        get_string('timingheading', 'format_dari'),
        get_string('timingheading_desc', 'format_dari')
    ));

    $settings->add(new admin_setting_configtextarea(
        'format_dari/defaultminutes',
        get_string('defaultminutes', 'format_dari'),
        get_string('defaultminutes_desc', 'format_dari'),
        \format_dari\local\progress::DEFAULT_MINUTES_MAP,
        // Note: plain text rather than an untyped value. The field holds
        // "modname=minutes" lines and nothing else; the parser already ignores anything not
        // matching that shape, so there is no case where markup here would be wanted.
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/minutesperquestion',
        get_string('minutesperquestion', 'format_dari'),
        get_string('minutesperquestion_desc', 'format_dari'),
        1,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/minutesfallback',
        get_string('minutesfallback', 'format_dari'),
        get_string('minutesfallback_desc', 'format_dari'),
        5,
        PARAM_INT
    ));

    // Note: first-run tour.
    $settings->add(new admin_setting_configcheckbox(
        'format_dari/tourvoiceover',
        get_string('tourvoiceover', 'format_dari'),
        get_string('tourvoiceover_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/tourvoice',
        get_string('tourvoice', 'format_dari'),
        get_string('tourvoice_desc', 'format_dari'),
        'en-AU',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/defaultheroattop',
        get_string('heroattop', 'format_dari'),
        get_string('heroattop_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/defaultcardlayout',
        get_string('cardlayout', 'format_dari'),
        get_string('cardlayout_desc', 'format_dari'),
        0,
        [
            0 => get_string('cardlayout_grid', 'format_dari'),
            1 => get_string('cardlayout_list', 'format_dari'),
        ]
    ));

    // The site-wide starting point for the AI card image style.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultcardimagestyle',
        get_string('cardimagestyle', 'format_dari'),
        get_string('cardimagestyle_desc', 'format_dari'),
        'photo',
        \format_dari\local\cardimage::style_options()
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/defaultactivitydisplaymode',
        get_string('activitydisplaymode', 'format_dari'),
        get_string('activitydisplaymode_desc', 'format_dari'),
        1,
        [
            0 => get_string('activitydisplaystandard', 'format_dari'),
            1 => get_string('activitydisplaycards', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/defaultshowactivitiesoncards',
        get_string('showactivitiesoncards', 'format_dari'),
        get_string('showactivitiesoncards_desc', 'format_dari'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_dari/defaultshownavchevrons',
        get_string('shownavchevrons', 'format_dari'),
        get_string('shownavchevrons_desc', 'format_dari'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultcardactivitylimit',
        get_string('cardactivitylimit', 'format_dari'),
        get_string('cardactivitylimit_desc', 'format_dari'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultcardtitlesize',
        get_string('cardtitlesize', 'format_dari'),
        get_string('cardtitlesize_desc', 'format_dari'),
        14,
        PARAM_INT
    ));




    // Note: the missing site default.
    //
    // lib.php's course_format_options() calls $d('coursenavplace', 0) -- i.e. it reads
    // 'format_dari/defaultcoursenavplace' -- but that setting was never registered here, so the
    // helper fell through to its hard-coded fallback on every site and the option's own comment
    // ("every course format option now has a site-level default") was not true. Registered with the
    // same two choices the course settings form offers, so the two cannot describe it differently.
    $settings->add(new admin_setting_configselect(
        'format_dari/defaultcoursenavplace',
        get_string('coursenavplace', 'format_dari'),
        get_string('sitedefault_desc', 'format_dari'),
        0,
        [
            0 => get_string('coursenavplace_default', 'format_dari'),
            1 => get_string('coursenavplace_header', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'format_dari/defaultshowcourseindex',
        get_string('showcourseindex', 'format_dari'),
        get_string('showcourseindex_desc', 'format_dari'),
        7,
        [
            0 => get_string('courseindex_none', 'format_dari'),
            1 => get_string('courseindex_home', 'format_dari'),
            2 => get_string('courseindex_section', 'format_dari'),
            3 => get_string('courseindex_home_section', 'format_dari'),
            4 => get_string('courseindex_activity', 'format_dari'),
            5 => get_string('courseindex_home_activity', 'format_dari'),
            6 => get_string('courseindex_section_activity', 'format_dari'),
            7 => get_string('courseindex_all', 'format_dari'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'format_dari/defaultheroimageoverlay',
        get_string('heroimageoverlay', 'format_dari'),
        get_string('heroimageoverlay_desc', 'format_dari'),
        45,
        PARAM_INT
    ));

    // Note: the override. The default above only seeds NEW courses -- an existing
    // course has its own stored value and ignores it forever, which is why changing the default
    // appears to do nothing. This applies to every course at once. -1 leaves each course alone.
    $settings->add(new admin_setting_configtext(
        'format_dari/forceheroimageoverlay',
        get_string('forceheroimageoverlay', 'format_dari'),
        get_string('forceheroimageoverlay_desc', 'format_dari'),
        -1,
        PARAM_INT
    ));
}
