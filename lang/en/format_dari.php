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
 * Strings for component 'format_dari', language 'en'.
 *
 * Keys are kept in alphabetical order, as required by the Moodle coding style.
 *
 * @package    format_dari
 * @category   string
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accentcolour'] = 'Accent colour (used for headings, icons and highlights)';
$string['accentcolour_desc'] = 'The colour this format tints everything with: the hero banner background, card borders and hover states, icon wells, progress chips and the keyboard focus ring. Leave empty to keep following your theme\'s primary colour. Set here it applies to every course using this format; an individual course can override it in its own course settings.';
$string['accentcolour_help'] = 'The accent colour is the one colour that marks the structure of your course. It is used for:

* section and card headings
* activity icons
* the headings and dividers in the course index
* the outline that appears when you tab to a button with the keyboard

Enter a hex colour — six characters after a hash, like <code>#194866</code> for navy or <code>#b5179e</code> for magenta. Your theme\'s own primary colour is used if you leave this empty.

*Tip:* pick something with reasonable contrast against white. Very pale colours will make headings hard to read.';
$string['activities'] = 'activities';
$string['activity'] = 'activity';
$string['activitydisplaycards'] = 'Activity tiles';
$string['activitydisplaymode'] = 'How activities look inside a section';
$string['activitydisplaymode_desc'] = '<strong>What this does:</strong> chooses how activities look inside a section.<br /><br /><strong>Standard Moodle list</strong> is the familiar plain list.<br /><strong>Activity cards</strong> gives each activity a tile with its icon, its type and how long it takes.<br /><br /><em>Example:</em> a section holding a page, a video and a quiz reads as three distinct things rather than three lines of text.';
$string['activitydisplaymode_help'] = 'Choose between traditional Moodle activity list or beautiful card view with status badges and icons.';
$string['activitydisplaystandard'] = 'Standard Moodle list';
$string['activitynumberstatus'] = 'Activity {$a->num}: {$a->name} ({$a->status})';
$string['activitytype_activities'] = 'Learning Activities';
$string['activitytype_content'] = 'Learning Content';
$string['activitytype_knowledgecheck'] = 'Knowledge Check';
$string['activitytype_slides'] = 'Learning Slides';
$string['activitywithstatus'] = '{$a->name} ({$a->status})';
$string['addicon'] = 'Add icon';
$string['addiconfor'] = 'Add an icon for {$a}';
$string['addsection'] = 'Add section';
$string['admin_report_all'] = 'All';
$string['admin_report_all_courses'] = 'All courses';
$string['admin_report_answered'] = 'Answered';
$string['admin_report_answered_only'] = 'Answered only';
$string['admin_report_col_activity'] = 'Activity';
$string['admin_report_col_activityid'] = 'Activity ID';
$string['admin_report_col_course'] = 'Course';
$string['admin_report_col_email'] = 'Email';
$string['admin_report_col_refused'] = 'Guided';
$string['admin_report_export_csv'] = 'Export CSV';
$string['admin_report_filter_capped'] = 'Showing the first {$a} entries only.';
$string['admin_report_filter_course'] = 'Course';
$string['admin_report_filter_datefrom'] = 'From date';
$string['admin_report_filter_dateto'] = 'To date';
$string['admin_report_filter_rating'] = 'Rating';
$string['admin_report_filter_refused'] = 'Response type';
$string['admin_report_filter_student'] = 'Student';
$string['admin_report_filter_unrated'] = 'Unrated';
$string['admin_report_link'] = 'Ask Dari Q&A Report';
$string['admin_report_no_filtered'] = 'No records match the current filters.';
$string['admin_report_refused'] = 'Guided';
$string['admin_report_refused_only'] = 'Guided only';
$string['admin_report_reset'] = 'Reset';
$string['admin_report_search'] = 'Search questions / responses';
$string['admin_report_show_more'] = 'Show more';
$string['admin_report_showing'] = 'Showing {$a->from}–{$a->to} of {$a->total} records';
$string['admin_report_stat_courses'] = 'Active Courses';
$string['admin_report_stat_helpful'] = 'Rated Helpful';
$string['admin_report_stat_refused'] = 'Guided without giving the answer';
$string['admin_report_stat_students'] = 'Active Students';
$string['admin_report_stat_total'] = 'Total Questions (all time)';
$string['admin_report_table_caption'] = 'Ask Dari questions and responses from every course on this site';
$string['admin_report_title'] = 'Ask Dari — All Q&A (Site-wide)';
$string['admin_report_view'] = 'View all Ask Dari Q&A';
$string['ai_notready_title'] = 'AI images are not set up yet';
$string['ai_opensettings'] = 'Open the AI settings';
$string['aiassistant'] = 'Ask Dari';
$string['aiassistant_askagain'] = 'Ask again';
$string['aiassistant_checklist_progress'] = '{$a->done} of {$a->total} done';
$string['aiassistant_collapse'] = 'Back to compact view';
$string['aiassistant_composer_hint'] = 'Enter to send · Shift + Enter for a new line';
$string['aiassistant_context_activity'] = 'Activity';
$string['aiassistant_context_course'] = 'Course';
$string['aiassistant_context_heading'] = 'You\'re studying';
$string['aiassistant_context_section'] = 'Section';
$string['aiassistant_copied'] = 'Copied';
$string['aiassistant_copy'] = 'Copy';
$string['aiassistant_cutoff'] = 'This answer was cut off before it finished.';
$string['aiassistant_error'] = 'Sorry, I couldn\'t process your question. Please try again.';
$string['aiassistant_expand'] = 'Open study view';
$string['aiassistant_input_label'] = 'Your question for Ask Dari';
$string['aiassistant_integrity_note'] = 'Your tutor helps you think. It won\'t write assessment answers for you. It can make mistakes, so check your course material and ask your trainer or teacher if unsure.';
$string['aiassistant_locked'] = 'You\'ve submitted this assignment, so I can\'t help with it here until it\'s marked or reopened. To keep studying, open another activity or the course page and ask me there.';
$string['aiassistant_locked_assessment'] = 'Ask Dari is switched off for this assessment so your answers are your own work. You can use it again once you\'ve finished, or in any other activity.';
$string['aiassistant_newchat'] = 'New conversation';
$string['aiassistant_notconfigured'] = 'Ask Dari is not available because no AI provider with text generation is enabled on this site. Ask your administrator to set one up under Site administration > General > AI.';
$string['aiassistant_placeholder'] = 'Ask about this course…';
$string['aiassistant_policydeclined'] = 'AI features need you to accept this site\'s AI usage policy first. Nothing was sent.';
$string['aiassistant_prompt_checklist'] = 'Using only the task instructions, give me a checklist to review my own work before I submit. Don\'t write any of the content for me.';
$string['aiassistant_prompt_concepts'] = 'Explain the key ideas I need for {activity} in plain language, then ask me one quick question to check I\'ve understood.';
$string['aiassistant_prompt_practice'] = 'Give me 3 multiple-choice practice questions on {activity}, from easier to harder.';
$string['aiassistant_prompt_structure'] = 'Can you help me understand how to structure my response for {activity}? I don\'t need an answer, just guidance on the format and what sections to include.';
$string['aiassistant_prompt_workplace'] = 'Show me a real-world example of {activity} so I can see how it works in practice. Use a different situation from my assessment.';
$string['aiassistant_quick_checklist'] = 'Checklist';
$string['aiassistant_quick_checklist_desc'] = 'Make sure nothing is missed before you submit';
$string['aiassistant_quick_concepts'] = 'Explain the concept simply';
$string['aiassistant_quick_concepts_desc'] = 'The key idea in plain words, then a quick check';
$string['aiassistant_quick_label'] = 'Pick a tool, or type your own question';
$string['aiassistant_quick_practice'] = 'Give me some practice questions';
$string['aiassistant_quick_practice_desc'] = 'Interactive questions, easier to harder';
$string['aiassistant_quick_structure'] = 'How to structure';
$string['aiassistant_quick_structure_desc'] = 'Plan the format and sections of your response';
$string['aiassistant_quick_workplace'] = 'Show me a real-world example';
$string['aiassistant_quick_workplace_desc'] = 'See how this works in a real situation';
$string['aiassistant_quiz_another'] = 'Another question';
$string['aiassistant_quiz_another_prompt'] = 'Give me another multiple-choice practice question on this topic.';
$string['aiassistant_quiz_choose'] = 'For the question "{$a->question}", my answer is {$a->letter}) {$a->option}. Is that right? Please explain why.';
$string['aiassistant_quiz_correct'] = 'Correct!';
$string['aiassistant_quiz_counter'] = 'Question {$a->num} of {$a->total}';
$string['aiassistant_quiz_explain'] = 'Explain this';
$string['aiassistant_quiz_explain_prompt'] = 'Can you explain in more detail why {$a->letter}) {$a->option} is the right answer to "{$a->question}", and why the other options are wrong?';
$string['aiassistant_quiz_hint'] = 'Show a hint';
$string['aiassistant_quiz_incomplete'] = 'Some practice questions were cut off. Ask again for a shorter set.';
$string['aiassistant_quiz_incorrect'] = 'Not quite. The correct answer is {$a}.';
$string['aiassistant_quiz_label'] = 'Practice question';
$string['aiassistant_quiz_review'] = 'Correct answer: {$a}';
$string['aiassistant_quiz_score'] = 'You scored {$a->score} out of {$a->total}';
$string['aiassistant_quiz_sent'] = 'Answer sent. Your tutor will check it.';
$string['aiassistant_quiz_tryagain'] = 'Not quite. Have another go — use the hint if you\'re stuck.';
$string['aiassistant_rate_helpful'] = 'This answer was helpful';
$string['aiassistant_rate_nothelpful'] = 'This answer was not helpful';
$string['aiassistant_rating_thanks'] = 'Thanks for the feedback!';
$string['aiassistant_restored'] = 'Restored from history';
$string['aiassistant_send'] = 'Send message';
$string['aiassistant_settings_desc'] = 'Ask Dari and AI images use the AI provider configured for the whole site in Moodle\'s AI subsystem. The settings below control what Dari sends to it and how.';
$string['aiassistant_studyview'] = 'Study view';
$string['aiassistant_thinking'] = 'Thinking…';
$string['aiassistant_thisactivity'] = 'this activity';
$string['aiassistant_tools_heading'] = 'Study tools';
$string['aiassistant_welcome_activity'] = 'Hello {$a->name}, you\'re on <strong>{$a->activity}</strong>. Where shall we start?';
$string['aiassistant_welcome_name'] = 'Hello {$a}, what are we working on today?';
$string['aiassistant_welcome_question'] = 'I see you\'re on question {$a->num}. This question is about {$a->topic}. How can I help you think it through?';
$string['aiassistant_welcome_questionnotopic'] = 'I see you\'re on question {$a}. How can I help you think it through?';
$string['aiassistant_welcome_section'] = 'Hello {$a->name}, you\'re in <strong>{$a->section}</strong>. Where shall we start?';
$string['aireport'] = 'Ask Dari Report';
$string['aireport_actions'] = 'Actions';
$string['aireport_activities'] = 'Activities';
$string['aireport_all_groups'] = 'All Groups';
$string['aireport_all_ratings'] = 'All Ratings';
$string['aireport_all_students'] = 'All Students';
$string['aireport_apply'] = 'Apply Filters';
$string['aireport_cancel'] = 'Cancel';
$string['aireport_characters'] = 'Characters Learned';
$string['aireport_chattable_caption'] = 'Ask Dari questions and responses for this course';
$string['aireport_content'] = 'Course Content';
$string['aireport_correct'] = 'Correct';
$string['aireport_corrected'] = 'Corrected Responses';
$string['aireport_correction'] = 'Correction';
$string['aireport_correction_placeholder'] = 'Enter the correct answer to retrain the AI...';
$string['aireport_course_summary'] = 'Course Summary';
$string['aireport_date'] = 'Date';
$string['aireport_filter_corrected'] = 'Corrected Only';
$string['aireport_filter_helpful'] = 'Helpful Only';
$string['aireport_filter_nothelpful'] = 'Not Helpful Only';
$string['aireport_fullanswer'] = 'Show full answer';
$string['aireport_helpful'] = 'Marked Helpful';
$string['aireport_history'] = 'Chat History';
$string['aireport_learned'] = 'AI has learned this content';
$string['aireport_learned_content'] = 'Content Learned by AI';
$string['aireport_no_chats'] = 'No chat history yet';
$string['aireport_no_chats_desc'] = 'Students haven\'t asked any questions to Ask Dari yet.';
$string['aireport_nocourses'] = 'No courses using Dari course format';
$string['aireport_nocourses_desc'] = 'Change a course format to Dari course format to enable Ask Dari.';
$string['aireport_nocoursesummary'] = 'No course summary';
$string['aireport_practicequestions'] = '[Practice questions: {$a}]';
$string['aireport_question'] = 'Question';
$string['aireport_rating'] = 'Rating';
$string['aireport_rating_learner_helpful'] = 'Learner: helpful';
$string['aireport_rating_learner_none'] = 'Learner: not rated';
$string['aireport_rating_learner_nothelpful'] = 'Learner: not helpful';
$string['aireport_response'] = 'AI Response';
$string['aireport_save'] = 'Save';
$string['aireport_search'] = 'Search questions or responses...';
$string['aireport_sections'] = 'Sections';
$string['aireport_student'] = 'Student';
$string['aireport_total_questions'] = 'Total Questions';
$string['aireport_unknownuser'] = 'Unknown user';
$string['aireport_view'] = 'View Report';
$string['aiscenewriter'] = 'AI art director for images (recommended)';
$string['aiscenewriter_desc'] = '<strong>What this does:</strong> before a banner or card image is generated, the site\'s text AI acts as an art director. Once per course it plans a visual world for the subject (the real places, people, equipment, palette and light), then for each image it writes a specific, believable scene that fits that world and differs from the course\'s other images. Dari\'s fixed style, colour, composition and no-text rules are added unchanged.<br /><br />The result is a matching, professional-looking set of images instead of generic ones. It costs one short text request per image (plus one per course). Needs a text AI; without one, Dari uses its built-in prompt templates.';
$string['aistatus'] = 'AI provider status';
$string['aistatus_direct_image_missing'] = '<strong>Banners and card images:</strong> not set up &mdash; enter an image model below. The "Generate with AI" buttons are hidden until then.';
$string['aistatus_direct_image_ok'] = '<strong>Banners and card images:</strong> ready &mdash; using the AI connection below (Moodle 4.4).';
$string['aistatus_direct_text_missing'] = '<strong>Ask Dari:</strong> not set up &mdash; enter the endpoint and a text model below. The tutor bubble is hidden until then.';
$string['aistatus_direct_text_ok'] = '<strong>Ask Dari:</strong> ready &mdash; using the AI connection below (Moodle 4.4).';
$string['aistatus_image_missing'] = '<strong>Banners and card images:</strong> unavailable &mdash; no enabled AI provider has <em>Generate image</em> turned on. The "Generate with AI" buttons are hidden until one does. Upload and colour tools still work.';
$string['aistatus_image_ok'] = '<strong>Banners and card images:</strong> ready &mdash; using this site\'s AI image provider.';
$string['aistatus_text_missing'] = '<strong>Ask Dari:</strong> unavailable &mdash; no enabled AI provider has <em>Generate text</em> turned on. The tutor bubble is hidden until one does.';
$string['aistatus_text_ok'] = '<strong>Ask Dari:</strong> ready &mdash; using this site\'s AI text provider.';
$string['bannerdel_confirm'] = 'Remove image';
$string['bannerdel_desc'] = 'The banner image will be permanently removed from this course. You can generate or upload a new one at any time.';
$string['bannerdel_error'] = 'Failed to remove banner. Please try again.';
$string['bannerdel_removed'] = 'Banner image removed';
$string['bannerdel_removing'] = 'Removing';
$string['bannerdel_title'] = 'Remove banner image?';
$string['bannergen_applied'] = 'Banner applied to your course';
$string['bannergen_cost'] = 'Your school\'s AI';
$string['bannergen_costdetail'] = 'Generated by the AI provider your site administrator has connected';
$string['bannergen_desc'] = 'Dari writes an image brief from your course name and summary, and your site\'s AI provider paints a wide banner to match. It is cropped for the header and saved to the course automatically.';
$string['bannergen_extrahint'] = 'Anything you type here is added to the image prompt alongside the course name. Leave it empty for the standard banner.';
$string['bannergen_extralabel'] = 'Add your own detail (optional)';
$string['bannergen_extraph'] = 'e.g. warm evening light, a laboratory bench, muted blues, no people';
$string['bannergen_failed'] = 'Generation failed. Please try again.';
$string['bannergen_failedtitle'] = 'Generation failed';
$string['bannergen_generate'] = 'Generate banner';
$string['bannergen_loadingsub'] = 'AI is crafting a photorealistic banner for your course. This usually takes one to two minutes - please leave this window open.';
$string['bannergen_loadingtitle'] = 'Generating your banner';
$string['bannergen_previewalt'] = 'Generated course banner';
$string['bannergen_retry'] = 'Try again';
$string['bannergen_subtitle'] = 'Image generation';
$string['bannergen_success'] = 'Your AI banner has been saved to the course.';
$string['bannergen_title'] = 'Create a banner image';
$string['bannerimage'] = 'Upload banner image';
$string['bannerimage_help'] = 'Upload one image to use as this course\'s hero banner. It replaces the course image on the course home page and on every section page. Landscape images work best - around 1920 x 600 pixels, at most 5 MB, in JPG, PNG or WebP. Leave it empty to fall back to the course image, or generate one with AI from the course page.';
$string['bannerimage_ratio_formats'] = 'Accepted formats: JPG · PNG · WebP  ·  Maximum file size: 5 MB  ·  One image per course.';
$string['bannerimage_ratio_hint'] = 'Ideal image size is 1920 × 600 px (or 1600 × 500 px minimum). Centre your subject — edges may be cropped on narrow screens. Dark or high-contrast images give the best result with the white text overlay.';
$string['bannerimage_ratio_title'] = 'Recommended ratio: 16:5';
$string['bannerimageheader'] = 'Course Banner Image';
$string['bannerqueued'] = 'Generating your banner image. This takes one to two minutes and continues even if you leave this page \\u2014 the banner will appear when it is ready.';
$string['bannerstillrunning'] = 'Still generating. You can leave this page; the banner will be there when it is done.';
$string['cachedef_ajaxratelimit'] = 'Ask Dari and banner generation rate limit counters';
$string['cachedef_coursecontent'] = 'Course content index used by Ask Dari';
$string['cardactivitiesmore'] = 'View all activities in {$a}';
$string['cardactivitylabel'] = '{$a->name}, {$a->section}';
$string['cardactivitylimit'] = 'How many activities to list on each section card';
$string['cardactivitylimit_desc'] = '<strong>What this does:</strong> caps how many activities are listed on each section card.<br /><br /><em>Example:</em> set it to 4 and a section holding 12 activities lists the first four and then a <code>+8</code> link. Set it to 0 and every activity is listed, however many there are.';
$string['cardactivitylimit_help'] = 'Only applies when **Show activities on cards** is turned on.

* **0** &mdash; list every activity. Cards grow as tall as they need and, because the grid stretches cards to a common height, the whole row matches the tallest one. This is the default.
* **4** &mdash; the old fixed behaviour: four activities and a "+N" link to the section.

Set a cap if you have sections with a great many activities and you want the course home page to stay scannable.';
$string['cardactivitystatuslabel'] = '{$a->name}, {$a->section} ({$a->status})';
$string['cardcolour'] = 'Background colour of the cards';
$string['cardcolour_help'] = 'The background of the section and activity cards.

A hex colour such as <code>#fafbfc</code>, which is the default - a very soft grey that lifts a card off the white page behind it without reading as a filled panel.

Leave empty to follow the site setting.

*Tip:* this sits behind the card\'s title and its list of activities, so keep it light. A strong colour here makes the text on top harder to read, and the card headings already carry your accent colour.';
$string['cardcta_continue'] = 'Continue';
$string['cardcta_review'] = 'Review';
$string['cardcta_start'] = 'Start';
$string['cardcta_view'] = 'Open';
$string['carddone'] = '{$a->done} of {$a->total} done';
$string['cardeyebrow'] = 'Module {$a}';
$string['cardimage_ai'] = 'AI image';
$string['cardimage_aifor'] = 'Generate an AI image for {$a}';
$string['cardimage_all_button'] = 'Generate card images';
$string['cardimage_all_capped'] = 'A batch is limited to {$a} cards. Run it again afterwards for the rest.';
$string['cardimage_all_confirm'] = 'Generate {$a} images';
$string['cardimage_all_counting'] = 'Counting cards…';
$string['cardimage_all_desc'] = 'Each card gets its own AI image, based on its title, its description and the course, in this course\'s image style ({$a}).';
$string['cardimage_all_none'] = 'Every card in this selection already has an image.';
$string['cardimage_all_onlymissing'] = 'Only cards without an image';
$string['cardimage_all_queued'] = '{$a} images are being generated. Each one appears on its card as soon as it is ready - you can keep working.';
$string['cardimage_all_scope'] = 'Which cards';
$string['cardimage_all_scope_activities'] = 'Activity cards';
$string['cardimage_all_scope_all'] = 'All cards';
$string['cardimage_all_scope_sections'] = 'Section cards';
$string['cardimage_all_summary'] = '{$a->count} cards will be generated';
$string['cardimage_all_title'] = 'Generate images for your cards';
$string['cardimage_colour'] = 'Card colour';
$string['cardimage_colour_auto'] = 'Auto';
$string['cardimage_colour_custom'] = 'Custom colour';
$string['cardimage_colour_default'] = 'Course accent';
$string['cardimage_colour_desc'] = 'Shown when the card has no image.';
$string['cardimage_colour_saved'] = 'Card colour saved';
$string['cardimage_colourfor'] = 'Choose a colour for {$a}';
$string['cardimage_cost'] = 'Uses your site\'s AI image provider';
$string['cardimage_dialogtitle'] = 'Card image: {$a}';
$string['cardimage_failed'] = 'The image could not be generated: {$a}';
$string['cardimage_generate'] = 'Generate image';
$string['cardimage_generated'] = 'AI image added to {$a}';
$string['cardimage_generating'] = 'Generating image…';
$string['cardimage_menu'] = 'Card image';
$string['cardimage_prompthint'] = 'The image is based on the card\'s title, its description and the course. Anything you add here steers it.';
$string['cardimage_promptlabel'] = 'Describe the image (optional)';
$string['cardimage_promptph'] = 'e.g. a small team around a whiteboard, warm natural light, no text';
$string['cardimage_promptused'] = 'Prompt used last time';
$string['cardimage_promptused_ai'] = 'Written by the AI art director.';
$string['cardimage_promptused_template'] = 'Built from Dari\'s template (the AI art director was off or unavailable).';
$string['cardimage_remove'] = 'Remove image';
$string['cardimage_removed'] = 'Image removed';
$string['cardimage_removefor'] = 'Remove the image from {$a}';
$string['cardimage_saved'] = 'Image saved';
$string['cardimage_style'] = 'Style: {$a}';
$string['cardimage_title'] = 'Create an image';
$string['cardimage_titlefor'] = 'For: {$a}';
$string['cardimage_toolarge'] = 'That image is too large. Choose one under 20 MB.';
$string['cardimage_upload'] = 'Upload image';
$string['cardimage_uploaderror'] = 'That file could not be used as an image.';
$string['cardimage_uploadfor'] = 'Upload an image for {$a}';
$string['cardimage_uploading'] = 'Uploading image…';
$string['cardimagestyle'] = 'AI card image style';
$string['cardimagestyle_desc'] = '<strong>What this does:</strong> sets the art style every AI card image and banner in a course is generated in, so they look like one set.<br /><br />Each course can change it in its own settings; this is the starting point for new courses.';
$string['cardimagestyle_flat'] = 'Flat illustration';
$string['cardimagestyle_help'] = 'The art style every AI card image and banner in this course is generated in.

Choosing one style for the whole course keeps the cards looking like one set rather than a mix of photos, drawings and renders. Images already generated are not changed; generate them again to switch style.';
$string['cardimagestyle_illustration'] = 'Illustration';
$string['cardimagestyle_photo'] = 'Photographic';
$string['cardimagestyle_render3d'] = '3D render';
$string['cardlayout'] = 'How the section cards are arranged';
$string['cardlayout_desc'] = '<strong>What this does:</strong> chooses how the section cards are arranged.<br /><br /><strong>Grid</strong> puts them side by side — good for a course with several short modules a learner picks between.<br /><strong>List</strong> puts one per row down the page — good when section names are long, or when the course is meant to be worked through in order.';
$string['cardlayout_grid'] = 'Grid - cards side by side';
$string['cardlayout_help'] = 'How the section cards are arranged.

**Grid** fits as many cards across the page as the width allows and is the default.

**List** gives every card the full width of the page and stacks them vertically, which suits long section names and courses with only a few sections. On a phone both settings look the same, because a single column is all that fits either way.';
$string['cardlayout_list'] = 'List - one card per row, down the page';
$string['cardopacity'] = 'How strong the card colour is';
$string['cardopacity_help'] = 'How strongly the card colour is applied, as a percentage from 0 to 100.

* **100** - the colour exactly as you set it.
* **50** - halfway between your colour and white.
* **0** - plain white; the card colour has no effect.

This lets you pick a colour you like and then dial it back until it is as subtle as you want, rather than hunting for a paler hex value.

The card stays fully opaque at every setting - the strength mixes your colour toward white rather than making the card see-through, so nothing behind it shows through when the page scrolls.';
$string['cardstatus_notstarted'] = 'Not started';
$string['cardstatus_progress'] = 'In progress';
$string['cardtitlesize'] = 'Size of the title on each section card';
$string['cardtitlesize_desc'] = '<strong>What this does:</strong> sets the size of the section name on each card, in pixels.<br /><br /><em>Example:</em> 16 is compact and fits long names on one line; 22 is bold and easy to scan. Around 18 suits most courses.';
$string['cardtitlesize_help'] = 'Set the font size for section card and activity card titles in pixels. For example, enter 12 for smaller titles or 16 for larger. Default is 14px.';
$string['changeicon'] = 'Change icon';
$string['changeiconfor'] = 'Change the icon for {$a}';
$string['colourmode'] = 'Light or dark appearance';
$string['colourmode_dark'] = 'Always dark';
$string['colourmode_desc'] = '<strong>What this does:</strong> decides whether the course pages are painted light or dark.<br /><br /><strong>Follow the theme</strong> (recommended) matches whatever your theme is doing.<br /><strong>Follow the device</strong> uses each person\'s own phone or laptop setting.<br /><strong>Always light</strong> or <strong>Always dark</strong> ignores both and picks one.<br /><br /><em>If you are unsure, leave it on Follow the theme.</em>';
$string['colourmode_device'] = 'Follow the device setting';
$string['colourmode_light'] = 'Always light';
$string['colourmode_theme'] = 'Follow the theme (recommended)';
$string['completed'] = 'Completed';
$string['completedof'] = '{$a->completed}/{$a->total} done';
$string['completionrequirement_auto'] = 'Complete activity';
$string['completionrequirement_grade100'] = 'Required grade 100%';
$string['completionrequirement_gradeany'] = 'Receive a grade';
$string['completionrequirement_gradepass'] = 'Required grade {$a}';
$string['completionrequirement_gradepasspct'] = 'Required grade {$a}%';
$string['completionrequirement_manual'] = 'Mark as done';
$string['completionrequirement_view'] = 'View activity';
$string['courseindex_activity'] = 'Activity pages only';
$string['courseindex_all'] = 'All pages (home, section, activity)';
$string['courseindex_home'] = 'Course home only';
$string['courseindex_home_activity'] = 'Course home + Activity pages';
$string['courseindex_home_section'] = 'Course home + Section pages';
$string['courseindex_none'] = 'Hide on all pages';
$string['courseindex_section'] = 'Section pages only';
$string['courseindex_section_activity'] = 'Section + Activity pages';
$string['coursenavplace'] = 'Where the course tabs sit';
$string['coursenavplace_default'] = 'Below the banner, as the theme renders them';
$string['coursenavplace_header'] = 'In the site header, beside Home and Dashboard';
$string['coursenavplace_help'] = 'Course, Settings, Participants, Grades and Reports are links only teachers use — but as a full-width row under the banner they take up space on every page, including for people who never click them.

* **Below the banner** — leave them where your theme puts them.
* **In the site header** — move them up to sit beside Home, Dashboard and My courses, where a teacher already looks for links like these.

This only affects people who can edit the course. What students see is decided by the "Course navigation tabs" setting instead.

The tabs go back to their normal place while Edit mode is on.';
$string['courseprogress'] = 'Course Progress';
$string['coursesectionsregion'] = 'Course sections';
$string['currentsection'] = 'This section';
$string['dari:correctresponses'] = 'Correct an Ask Dari response';
$string['dari:useaitutor'] = 'Use Ask Dari';
$string['dari:view'] = 'View Dari course format';
$string['dari:viewreport'] = 'View Dari course format reports';
$string['defaultaccentcolour_desc'] = '<strong>What this does:</strong> sets the one colour the format uses for section headings, activity icons, course index headings and focus outlines.<br /><br />Write a hex colour such as <code>#194866</code>. Leave it empty to follow your theme\'s own primary colour, which is usually what you want.';
$string['defaultcardcolour_desc'] = '<strong>What this does:</strong> the background colour of the section and activity cards.<br /><br />Keep it light — the card\'s title and activity list sit on top of it. <em>Example:</em> <code>#fafbfc</code>, a very soft grey, which is the shipped default.';
$string['defaultcardopacity_desc'] = '<strong>What this does:</strong> how strongly the card colour above is applied, from 0 to 100.<br /><br />100 is the colour exactly as you set it, 50 is halfway to white, 0 is plain white.<br /><br />The card never becomes see-through — this mixes toward white rather than turning the card transparent.';
$string['defaultgreetingname'] = 'there';
$string['defaultherosticky_desc'] = '<strong>What this does:</strong> keeps the banner pinned to the top of the screen while the learner scrolls, instead of letting it scroll away.<br /><br /><em>Example:</em> on a long section page, a pinned banner keeps the progress ring and the next/previous arrows within reach the whole way down.';
$string['defaulthidetimeactivitycards_desc'] = 'Whether the time pill on each activity card, on section pages and in the activity lists is shown, for any course that has not chosen its own.';
$string['defaulthidetimeindex_desc'] = 'Whether the small time pill on each activity row in the course index panel is shown, for any course that has not chosen its own.';
$string['defaulthidetimesectioncards_desc'] = 'Whether the time pill in the corner of each section card, showing the total for that section is shown, for any course that has not chosen its own.';
$string['defaulthidetimetotal_desc'] = 'Whether the total time shown under the course name at the top of the course index panel is shown, for any course that has not chosen its own.';
$string['defaultindexcolour_desc'] = '<strong>What this does:</strong> the background colour of the course index panel.<br /><br /><strong>Leave it empty and it matches the cards automatically</strong>, which is usually what looks right. Only set a colour here if you deliberately want the panel to differ.';
$string['defaultindexheadingcolour_desc'] = 'The background of section headings in the course index, for any course that has not chosen its own. The heading text is white.<br /><br />Leave empty to use the accent colour.';
$string['defaultindexiconcolour_desc'] = 'The colour of the activity icons in the course index, for any course that has not chosen its own.<br /><br />Leave empty to use the accent colour.';
$string['defaultindexopacity_desc'] = '<strong>What this does:</strong> how strongly the course index colour above is applied, from 0 to 100.<br /><br />It does nothing at all while that colour is left empty.';
$string['defaultminutes'] = 'How many minutes each type of activity is assumed to take';
$string['defaultminutes_desc'] = '<strong>What this does:</strong> tells the plugin how long each <strong>type</strong> of activity usually takes. These figures add up to the time shown on each section card and in the course index.<br /><br />Write one line per type, as <code>name=minutes</code>, using Moodle\'s internal module names:<br /><br /><code>assign=30<br />page=5<br />quiz=10<br />forum=10</code><br /><br /><em>A teacher can override any single activity</em> by clicking its time badge with editing turned on, and that override always wins over the figures here.';
$string['defaultplayerheadercolour_desc'] = '<strong>What this does:</strong> the colour of the band at the very top of the course index — the part holding your logo, the course name and the progress ring.<br /><br />A hex colour such as <code>#eceff4</code>. Leave empty for the default shade.';
$string['deletesection'] = 'Delete section';
$string['deletesectionconfirm'] = 'Are you sure you want to delete this section? This action cannot be undone.';
$string['deletesectionnamed'] = 'Delete section: {$a}';
$string['directapikey'] = 'API key';
$string['directapikey_desc'] = 'Your school\'s own API key for the service above. Leave empty for a self-hosted server that needs none.';
$string['directendpoint'] = 'API endpoint';
$string['directendpoint_desc'] = 'The base URL of the API, ending before /chat/completions. Examples: <code>https://api.openai.com/v1</code>, <code>https://generativelanguage.googleapis.com/v1beta/openai</code>, <code>https://openrouter.ai/api/v1</code>, <code>http://your-ollama-server:11434/v1</code>. A server on your own network may also need adding to Moodle\'s allowed hosts (Site administration &gt; General &gt; Security &gt; HTTP security).';
$string['directheading'] = 'AI connection (Moodle 4.4)';
$string['directheading_desc'] = 'Moodle 4.4 has no built-in AI settings, so Dari connects to your school\'s AI service directly using the details below. Any service that speaks the OpenAI API works: OpenAI, Azure OpenAI (v1 endpoint), Google Gemini\'s OpenAI-compatible endpoint, OpenRouter, a LiteLLM gateway, or a self-hosted Ollama or vLLM server. When this site is upgraded to Moodle 4.5 or later, Dari switches to Moodle\'s own AI settings automatically and these settings disappear.';
$string['directimagemodel'] = 'Image model';
$string['directimagemodel_desc'] = 'The model used for banners and card images, for example <code>gpt-image-2.5-sunburst</code> (best quality), <code>gpt-image-2.5-flare</code> (faster and cheaper) or <code>gpt-image-2</code>. Older models such as <code>dall-e-3</code> give noticeably more generic images. Leave empty to turn AI images off; upload and colour tools still work.';
$string['directtextmodel'] = 'Text model';
$string['directtextmodel_desc'] = 'The model Ask Dari uses, for example <code>gpt-6-astra</code> (best quality), <code>gpt-6.1-sol</code> (lower cost), <code>gemini-2.5-flash</code> or <code>llama3.1:8b</code>. Leave empty to turn Ask Dari off. This model also writes the image prompts, so a stronger model gives better images.';
$string['displayascards'] = 'Show sections as cards instead of a long page';
$string['displayascards_desc'] = '<strong>What this does:</strong> shows each section as a card in a grid instead of Moodle\'s usual single long page.<br /><br /><em>Example:</em> a course with six modules becomes six tiles a learner can scan in a second, each showing its own progress and estimated time, rather than a page they have to scroll.';
$string['displayascards_help'] = 'Choose between traditional section view (expandable sections with activities listed) or beautiful card view (modern cards with progress tracking, estimated time, and activity dots).';
$string['displayascardsoption'] = 'Section blocks';
$string['displayassections'] = 'Traditional sections';
$string['displaysettings'] = 'Default Display Settings';
$string['displaysettings_desc'] = 'These are the starting values for <strong>new</strong> courses using this format. Every course can change any of them in its own course settings.<br /><br />Where a setting has an <strong>\'apply to ALL existing courses\'</strong> version below it, that is the one that changes courses you have already built.';
$string['docslink'] = 'Documentation';
$string['docslink_label'] = 'Dari documentation (opens in a new tab)';
$string['docslink_text'] = 'Dari documentation at darilearning.com';
$string['duplicatesection'] = 'Duplicate section';
$string['duplicatesectionnamed'] = 'Duplicate section: {$a}';
$string['editsectionnamed'] = 'Edit section: {$a}';
$string['edittime'] = 'Edit estimated time';
$string['edittime_invalid'] = 'Enter a whole number of minutes between 0 and 10000.';
$string['edittime_prompt'] = 'Estimated minutes for this activity. Leave blank to use the site default, or enter 0 to hide the badge.';
$string['edittime_saved'] = 'Estimated time updated';
$string['enabletutor'] = 'Turn Ask Dari on';
$string['enabletutor_desc'] = '<strong>What this does:</strong> shows the Ask Dari chat bubble to learners and teachers on courses using this format.<br /><br />Ask Dari uses Moodle\'s AI provider settings (Site administration &gt; General &gt; AI) on Moodle 4.5 and later, or Dari\'s own AI connection settings on Moodle 4.4, and only appears once text generation is available. Switch this off and Ask Dari disappears everywhere and nothing is sent.';
$string['error_activitynotfound'] = 'That activity could not be found.';
$string['error_activitynotvisible'] = 'You do not have access to this activity.';
$string['error_addsectionfailed'] = 'The section could not be added.';
$string['error_ai_disabledincourse'] = 'AI tools have been switched off for this course or activity.';
$string['error_ai_imagefailed'] = 'The site\'s AI provider could not generate an image. Please try again.';
$string['error_ai_imagefailed_detail'] = 'The site\'s AI provider could not generate an image: {$a}';
$string['error_ai_nodirect'] = 'Ask Dari and AI images are not set up yet. On Moodle 4.4 an administrator enters the school\'s AI connection in the Dari course format settings.';
$string['error_ai_noimageprovider'] = 'No AI provider on this site has image generation enabled. Ask your administrator to enable "Generate image" for a provider under Site administration > General > AI.';
$string['error_ai_nosubsystem'] = 'Moodle\'s AI subsystem is not available on this site.';
$string['error_ai_notextprovider'] = 'No AI provider on this site has text generation enabled. Ask your administrator to enable "Generate text" for a provider under Site administration > General > AI.';
$string['error_ai_policynotaccepted'] = 'You need to accept this site\'s AI usage policy before using AI features.';
$string['error_ai_textfailed'] = 'The site\'s AI provider could not answer just now. Please try again.';
$string['error_ai_textfailed_detail'] = 'The site\'s AI provider could not answer: {$a}';
$string['error_apiratelimited'] = 'The site\'s AI provider is receiving too many requests right now. Please wait a moment and try again.';
$string['error_apiunauthorized'] = 'The AI service rejected the request. Ask your administrator to check the API key: under Site administration > General > AI on Moodle 4.5 and later, or in Dari\'s AI connection settings on Moodle 4.4.';
$string['error_bannerfailed'] = 'The banner image could not be generated. Please try again.';
$string['error_bannerfailed_detail'] = 'Banner generation failed. {$a}';
$string['error_bannerinvalidimage'] = 'The generated banner image could not be read.';
$string['error_bannernoimage'] = 'The site\'s AI provider did not return an image.';
$string['error_bannersavefailed'] = 'The banner image could not be saved to this course.';
$string['error_bannertoolarge'] = 'The generated banner image is too large to be stored.';
$string['error_cannotdeletegeneral'] = 'The General section cannot be deleted.';
$string['error_cannotdeletesection'] = 'This section cannot be deleted.';
$string['error_cannotduplicategeneral'] = 'The General section cannot be duplicated.';
$string['error_cardcolour'] = 'That is not a valid colour. Use a hex colour such as #1f6feb.';
$string['error_cardimageinvalid'] = 'That file is not a usable image. Use a JPG, PNG, GIF or WebP image.';
$string['error_cardimagetoolarge'] = 'That image is too large. The limit is 5 MB.';
$string['error_cardimagetype'] = 'Unknown card type.';
$string['error_chatlogunavailable'] = 'Your question was answered, but it could not be saved to the chat history.';
$string['error_chatnotfound'] = 'That chat entry could not be found.';
$string['error_cmnotincourse'] = 'That activity is not part of this course.';
$string['error_correctionfailed'] = 'The correction could not be saved. Please try again.';
$string['error_deletesectionfailed'] = 'The section could not be deleted.';
$string['error_duplicatesectionfailed'] = 'The section could not be duplicated.';
$string['error_guestnotallowed'] = 'Guest users cannot use Ask Dari.';
$string['error_invalidicon'] = 'That icon is not available.';
$string['error_invalidrating'] = 'That rating value is not valid.';
$string['error_invalidsection'] = 'That section number is not valid.';
$string['error_memoryunavailable'] = 'Ask Dari could not update what it remembers about this activity.';
$string['error_questionrequired'] = 'Please enter a question.';
$string['error_ratingfailed'] = 'The rating could not be saved. Please try again.';
$string['error_sectionnotfound'] = 'The requested section could not be found.';
$string['error_sectionnotincourse'] = 'That section is not part of this course.';
$string['error_toomanyrequests'] = 'You have made too many requests. Please wait a moment and try again.';
$string['error_tutordisabled'] = 'Ask Dari has been turned off for this site by an administrator.';
$string['error_unknownaction'] = 'Unknown action requested.';
$string['estimatedtime'] = 'Est. time';
$string['estimatedtimefor'] = 'Estimated time {$a}';
$string['esttime_h'] = '{$a} hr';
$string['esttime_hm'] = '{$a->hours} hr {$a->mins} min';
$string['esttime_m'] = '{$a} min';
$string['externalservice'] = 'Where the AI comes from';
$string['externalservice_desc'] = 'Dari has no AI service of its own. Ask Dari, banner images and card images all go through <strong>Moodle\'s AI subsystem</strong> to the provider your site administrator has connected under <a href="{$a->aiurl}">Site administration &gt; General &gt; AI &gt; AI providers</a> &mdash; for example OpenAI, Azure AI, Google Gemini, Anthropic, AWS Bedrock or a self-hosted Ollama server, using the school\'s own API key.<br /><br />When a user asks the tutor a question, the question, their first name, where they are in the course and the text content of the course are sent to that provider. Every request is subject to Moodle\'s AI usage policy, which each user accepts once, and is recorded in the <a href="{$a->usageurl}">AI usage report</a>.';
$string['externalservice_desc44'] = 'Dari has no AI service of its own. On Moodle 4.4, Ask Dari, banner images and card images are sent to the AI service you enter under <strong>AI connection (Moodle 4.4)</strong> below, using your school\'s own key. When a user asks a question, the question, their first name, where they are in the course and the text content of the course are sent to that service. From Moodle 4.5, Dari uses Moodle\'s own AI settings, usage report and AI policy instead.';
$string['fontfamily'] = 'Font';
$string['fontfamily_desc'] = 'The typeface used on course pages, the course index, the banner and Ask Dari. <strong>DM Sans</strong> is the default and is bundled with the plugin, so nothing is loaded from the internet. <strong>Follow the theme</strong> uses your theme\'s own font. Any of the <strong>Google Fonts</strong> options loads that font from fonts.googleapis.com on Dari pages, which means each visitor\'s browser contacts Google; check this fits your privacy policy before choosing one.';
$string['fontfamily_dmsans'] = 'DM Sans (default)';
$string['fontfamily_google'] = '{$a} (Google Fonts)';
$string['fontfamily_help'] = 'The typeface for this course\'s pages, course index, banner and Ask Dari. <strong>Use the site setting</strong> follows the font chosen in the Dari Course Format site settings. DM Sans is bundled with the plugin. <strong>Follow the theme</strong> uses your Moodle theme\'s font. A <strong>Google Fonts</strong> option loads that font from fonts.googleapis.com, so learners\' browsers contact Google.';
$string['fontfamily_sitedefault'] = 'Use the site setting ({$a})';
$string['fontfamily_theme'] = 'Follow the theme';
$string['forceaccentcolour'] = 'Accent colour — apply to ALL existing courses';
$string['forceaccentcolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses. Leave empty to let each course decide.';
$string['forcecardcolour'] = 'Card colour — apply to ALL existing courses';
$string['forcecardcolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses. Leave empty to let each course decide.';
$string['forcecardopacity'] = 'Card colour strength — apply to ALL existing courses';
$string['forcecardopacity_desc'] = 'A strength from 0 to 100 applied to every course, overriding each course\'s own choice. Set to -1 to let each course decide.';
$string['forceheroimageoverlay'] = 'Banner darkening — apply to ALL existing courses';
$string['forceheroimageoverlay_desc'] = 'Applies one overlay opacity to every course using this format at once, overriding each course\'s own setting. Unlike the site default above, this DOES affect existing courses. Set it to -1 to leave each course alone.';
$string['forceherosticky'] = 'Keep the banner on screen — apply to ALL existing courses';
$string['forceherosticky_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forcehidebreadcrumb'] = 'Breadcrumb trail — apply to ALL existing courses';
$string['forcehidebreadcrumb_desc'] = 'Applies one breadcrumb setting to every course using this format at once, overriding each course\'s own choice. Leave as \'Let each course decide\' to respect the per-course setting.';
$string['forcehidebreadcrumb_leave'] = 'Let each course decide';
$string['forcehidefooter'] = 'Site footer — apply to ALL existing courses';
$string['forcehidefooter_desc'] = 'Applies one footer setting to every course using this format at once, overriding each course\'s own choice. Unlike the site default above, this DOES affect existing courses. Leave as \'Let each course decide\' to respect the per-course setting.';
$string['forcehidegeneral'] = 'Hide the General section — apply to ALL existing courses';
$string['forcehidegeneral_desc'] = '<strong>What this does:</strong> forces one choice onto <strong>every</strong> course using this format, right now, ignoring what each course has set for itself.<br /><br /><em>Example:</em> set it to \'Hide from everyone\' and the General section disappears from all 40 of your courses the moment you save. Set it back to \'Let each course decide\' and each course returns to its own setting — nothing is lost.<br /><br />Use this when you want one rule for the whole site. Use the setting above instead when you only want to change what new courses start with.';
$string['forcehidesecondarynav'] = 'Course tabs — apply to ALL existing courses';
$string['forcehidesecondarynav_desc'] = 'Applies one choice to <strong>every</strong> course using this format at once, ignoring what each course has chosen for itself.<br /><br /><strong>Why this exists.</strong> The "Course navigation tabs" setting above only affects <em>brand new</em> courses. A course that has ever had its settings saved keeps its own stored value and will never pick up a change you make to the default. This override is the only way to change courses that already exist.<br /><br /><em>Example:</em> you have 200 courses and want the tabs gone from all of them. Setting the default above does nothing. Setting this to "Hide from everyone" does it immediately.<br /><br />Choose <strong>Follow each course\'s own setting</strong> if you would rather decide course by course.';
$string['forcehidesecondarynav_follow'] = 'Follow each course\'s own setting';
$string['forcehidetimeactivitycards'] = 'Time on activity cards — apply to ALL existing courses';
$string['forcehidetimeactivitycards_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forcehidetimeindex'] = 'Time in the course index — apply to ALL existing courses';
$string['forcehidetimeindex_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forcehidetimesectioncards'] = 'Time on section cards — apply to ALL existing courses';
$string['forcehidetimesectioncards_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forcehidetimetotal'] = 'Total course time — apply to ALL existing courses';
$string['forcehidetimetotal_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forceimmersive'] = 'Hide the site logo band — apply to ALL existing courses';
$string['forceimmersive_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['forceindexcolour'] = 'Course index colour — apply to ALL existing courses';
$string['forceindexcolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses. Leave empty to let each course decide.';
$string['forceindexheadingcolour'] = 'Section heading colour — apply to ALL existing courses';
$string['forceindexheadingcolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses.';
$string['forceindexiconcolour'] = 'Activity icon colour — apply to ALL existing courses';
$string['forceindexiconcolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses.';
$string['forceindexopacity'] = 'Course index colour strength — apply to ALL existing courses';
$string['forceindexopacity_desc'] = 'A strength from 0 to 100 applied to every course. Set to -1 to let each course decide.';
$string['forceindexstate'] = 'Course index on first entry — apply to ALL existing courses';
$string['forceindexstate_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Still applied only once per user per course.';
$string['forceplayerheadercolour'] = 'Course index header colour — apply to ALL existing courses';
$string['forceplayerheadercolour_desc'] = 'A hex colour applied to every course using this format, overriding each course\'s own choice. Unlike the default above, this DOES affect existing courses. Leave empty to let each course decide.';
$string['forceplayerindex'] = 'Course player sidebar — apply to ALL existing courses';
$string['forceplayerindex_desc'] = 'Applies one choice to every course using this format at once, overriding each course\'s own setting. Unlike the default above, this DOES affect existing courses.';
$string['generatebannerimage'] = 'Generate AI banner image';
$string['generatesectionbannerimage'] = 'Generate AI banner image for this section';
$string['gotocourse'] = 'Course Home';
$string['gradefraction'] = '{$a->current}/{$a->max}';
$string['gradefractionnone'] = '-/{$a}';
$string['grades'] = 'My Grades';
$string['heroattop'] = 'Put the banner above the course tabs';
$string['heroattop_desc'] = '<strong>What this does:</strong> moves the banner above the course tabs, so it is the very first thing on the page rather than sitting underneath Moodle\'s page furniture.<br /><br /><em>Example:</em> with this on, a learner opening a course sees the course image and their progress immediately, and the tabs come after it.';
$string['heroattop_help'] = 'Moves the hero banner above Moodle\'s page header and course navigation tabs, so it is the first thing on the page.

Moodle renders the page header before a course format gets a chance to output anything, so this is done by moving the banner in the browser after the page loads. Two consequences worth knowing:

* It is switched off automatically while **edit mode** is on, so the page header controls stay where you expect them.
* On a theme with an unusual page structure it simply does nothing and the banner stays where Moodle put it. It will not break the page.

Turn it off if your theme puts something above the banner that you need to keep there.';
$string['herobanneralign'] = 'Hero banner alignment';
$string['herobanneralign_center'] = 'Centre';
$string['herobanneralign_help'] = 'Choose whether the hero banner is centred or left-aligned on the page. Left-aligned is useful when you want the banner to line up with the left edge of your page content.';
$string['herobanneralign_left'] = 'Left';
$string['herobannerfade'] = 'Tint the banner when a course has no image';
$string['herobannerfade_desc'] = 'How much of the accent colour is mixed into the hero banner background when the course has no banner image, as a percentage. 0 is the plain card surface, 3 is a barely-there tint (the default), 8 is noticeably coloured, 16 and above is a solid accent panel. Values are clamped to 0-100. This has no effect when a banner image is set, because the image covers the background.';
$string['herobannerfade_help'] = 'A whole number from 0 to 100.

* **0** &mdash; no tint at all, the banner matches the cards below it.
* **3** &mdash; the default: the palest hint of your accent colour.
* **8** &mdash; clearly coloured but still light behind the title.
* **16+** &mdash; a solid accent panel.

This only applies when the course has **no banner image**; with an image the background is covered by the picture. Text contrast stays above the WCAG AA threshold at every value in the range.';
$string['herobannerheight'] = 'Hero banner height';
$string['herobannerheight_help'] = 'The **minimum** height of the hero banner in pixels, for courses with no banner image. The banner never gets shorter than this, and grows past it if the content needs more room &mdash; so it is a floor, not a cap, and a long course name is never clipped.

The default is 110, which matches the compact banner layout. Set it to 0 to let the banner size itself entirely from its content. Larger values (160-240) give a plain banner more presence.

Courses **with** a banner image ignore this: an image banner is sized by its own layout so the picture always has a sensible aspect ratio.';
$string['herobannerwidth'] = 'Hero banner width';
$string['herobannerwidth_help'] = 'Set the maximum width of the hero banner in pixels to match your theme\'s content width. For example, if your Moodle theme has a 1200px content area, set this to 1200. Set to 0 (default) for full width up to 1400px.';
$string['herocollapse'] = 'Collapse header';
$string['heroexpand'] = 'Expand header';
$string['heroimageoverlay'] = 'How much to darken the banner image behind the text';
$string['heroimageoverlay_desc'] = '<strong>What this does:</strong> darkens the banner image so the white course title on top of it stays readable. 0 means no darkening at all; 100 is almost black.<br /><br /><em>Why it matters:</em> a pale photograph — snow, a bright sky, a white background — makes white text vanish. The overlay is what stops that.<br /><br /><em>Suggested:</em> around 45 for a normal photograph. Below 25 only if your images are already dark. Above 70 the picture is barely visible.';
$string['heroimageoverlay_help'] = 'How dark the overlay between the banner **image** and the banner text is, as a percentage. Leave it at **-1** to follow the site-wide "Banner overlay strength" setting.

* **-1** &mdash; follow the site setting (Light 55, Medium 62, Strong 72).
* **0** &mdash; no overlay at all. The image shows at full strength.
* **55** &mdash; the lightest value at which white text still clears the WCAG AA contrast threshold on a worst-case near-white image.
* **62** &mdash; the default.
* **72** &mdash; heavy; use it if your banner images are busy or pale.

The overlay is a single flat tone, not a gradient, so it dims the whole image evenly. Below about 55 the title can become hard to read over a light photo &mdash; check your own images before going lower.

This has no effect on courses with no banner image.';
$string['herosticky'] = 'Keep the banner on screen while scrolling';
$string['herosticky_help'] = 'Whether the course banner stays at the top of the screen as the page scrolls.

* **Stays at the top** — the banner and its navigation remain reachable however far down a learner is. This is the default.
* **Scrolls away** — the banner behaves like the rest of the page.

Sticky suits a course a learner works through, where the progress ring and the next/previous controls are worth keeping to hand.

*When you might turn it off:* on a short course the banner never leaves the screen anyway, and on a small laptop it takes height from the content for the whole visit.';
$string['herosticky_no'] = 'Scrolls away with the page';
$string['herosticky_yes'] = 'Stays at the top while scrolling';
$string['hidebreadcrumb'] = 'Breadcrumb trail (the \'Home / My courses / …\' line above the page)';
$string['hidebreadcrumb_desc'] = 'Whether Moodle\'s breadcrumb trail is shown on this course\'s pages. The activity hero already names the course, the section and the activity, so on many sites the breadcrumb repeats all three directly beneath it. This hides the trail only — it is not an access control, and every page in it stays reachable. It is never hidden while editing.';
$string['hidebreadcrumb_help'] = 'The breadcrumb is the small trail of links near the top of the page, like *Home ▸ My courses ▸ Workplace Safety ▸ Section 1*.

On this format the banner already tells you the course, the section and the activity you are in — so the breadcrumb usually repeats all three, immediately below it.

* **Show** — leave it as your theme draws it.
* **Hide from students** — all course staff keep it, including non-editing teachers. Students do not.
* **Hide from everyone** — nobody sees it while reading the course.

This hides a trail, it does not lock anything: every page it pointed at is still reachable, and still checks permissions in the normal way. It returns in Edit mode.';
$string['hidefooter'] = 'Site footer (the block of links at the very bottom of every page)';
$string['hidefooter_desc'] = '<strong>What the footer is:</strong> the block of links and site information at the very bottom of every Moodle page.<br /><br /><strong>What this does:</strong> hides it on course pages only, so a course ends with its content rather than with the site\'s links.<br /><br />It is never hidden while editing, so a teacher can still reach everything.';
$string['hidefooter_help'] = 'The site footer is the strip at the very bottom of every page, usually holding a copyright line, contact details or policy links.

On a course page it is the last thing a learner needs and the first thing between them and the end of the content.

* **Show** — leave it as your theme draws it.
* **Hide from students** — all course staff keep it, including non-editing teachers. Students do not.
* **Hide from everyone** — nobody sees it while reading the course.

The editing toolbar that appears at the bottom of the screen while you build a course is a different thing and is **never** hidden, so Move, Duplicate and Delete always stay available. The footer returns in Edit mode.';
$string['hidefromothers'] = 'Hide section';
$string['hidegeneral'] = 'Hide the General section (Moodle\'s \'Section 0\', usually just Announcements)';
$string['hidegeneral_desc'] = '<strong>What the General section is:</strong> every Moodle course has a first section called \'General\' (Moodle calls it Section 0). Usually it holds nothing but the Announcements forum.<br /><br />On a course whose real content starts at Section 1, that empty section is one more thing a learner scrolls past before reaching what they came for.<br /><br /><strong>What this does:</strong> hides it from the course index and from the section cards.<br /><br /><em>It always comes back when editing is turned on</em>, so a teacher can still post announcements.<br /><br />If a course keeps activities in General besides Announcements, it is never hidden there, so learners can always find them.';
$string['hidegeneral_help'] = 'Section 0 of a Moodle course is called "General". It usually holds only the Announcements forum, and on a course whose real content starts at Section 1 it is a heading learners read past before reaching anything they came for.

* **Show** - leave it in the course index and the cards.
* **Hide from students** - all course staff still see it, including non-editing teachers. Students do not.
* **Hide from everyone** - nobody sees it while reading the course.

This hides the section from view. It does not delete anything and does not stop announcements being posted or emailed - the forum still works exactly as before.

It always comes back in edit mode, so a teacher can still reach it.

If General holds any activity besides Announcements, it is never hidden, so learners can always find that content in the course index.';
$string['hidesecondarynav'] = 'Course tabs (Course, Settings, Participants, Grades, Reports)';
$string['hidesecondarynav_all'] = 'Hide from everyone';
$string['hidesecondarynav_desc'] = '<strong>What the course tabs are:</strong> the row reading <em>Course, Settings, Participants, Grades, Reports, More</em> that Moodle puts under the course name.<br /><br /><strong>What this does:</strong> hides that row.<br /><br /><em>Why you might:</em> almost none of it is for learners — Settings, Reports and Participants are teacher tools. \'Hide from students\' keeps the tabs for course staff — teachers, non-editing teachers and managers alike — and clears them away for everyone else.<br /><br />A non-editing teacher needs this row as much as an editing one does: on an activity page it is the row carrying the assignment\'s <em>Submissions</em> tab.';
$string['hidesecondarynav_help'] = 'The Course navigation tabs are the row of links Moodle puts above your course content: Course, Settings, Participants, Grades, Reports and More.

This format already gives you the same places to go — the banner has quick links, and the course index lists every section and activity — so the tabs are often just a duplicate row taking up space.

* **Show** — leave them exactly as your theme draws them.
* **Hide from students** — all course staff still see them: teachers, non-editing teachers, managers and anyone who can edit the course. Students do not.
* **Hide from everyone** — nobody sees them while simply reading the course.

**They always come back when you turn Edit mode on**, so you never lose them while you are building the course.

*Example:* a short induction course looks much cleaner with them set to Hide from everyone. A large course where teachers constantly check Grades might prefer Hide from students.';
$string['hidesecondarynav_show'] = 'Show';
$string['hidesecondarynav_students'] = 'Hide from students';
$string['hidetime_hide'] = 'Hide';
$string['hidetimeactivitycards'] = 'Show the estimated time on activity cards';
$string['hidetimeactivitycards_help'] = 'Whether to show the time pill on each activity card, on section pages and in the activity lists.

* **Show** - display it.
* **Hide** - remove it.

Hiding a time removes only that one; the others have their own settings, so you can keep times where they help and drop them where they do not.

*Tip:* estimates are only useful if they are roughly right. If a course has activities whose durations have not been set, hiding the times reads better than showing figures nobody trusts.';
$string['hidetimeindex'] = 'Show the estimated time in the course index';
$string['hidetimeindex_help'] = 'Whether to show the small time pill on each activity row in the course index panel.

* **Show** - display it.
* **Hide** - remove it.

Hiding a time removes only that one; the others have their own settings, so you can keep times where they help and drop them where they do not.

*Tip:* estimates are only useful if they are roughly right. If a course has activities whose durations have not been set, hiding the times reads better than showing figures nobody trusts.';
$string['hidetimesectioncards'] = 'Show the estimated time on section cards';
$string['hidetimesectioncards_help'] = 'Whether to show the time pill in the corner of each section card, showing the total for that section.

* **Show** - display it.
* **Hide** - remove it.

Hiding a time removes only that one; the others have their own settings, so you can keep times where they help and drop them where they do not.

*Tip:* estimates are only useful if they are roughly right. If a course has activities whose durations have not been set, hiding the times reads better than showing figures nobody trusts.';
$string['hidetimetotal'] = 'Show the total course time in the course index';
$string['hidetimetotal_help'] = 'Whether to show the total time shown under the course name at the top of the course index panel.

* **Show** - display it.
* **Hide** - remove it.

Hiding a time removes only that one; the others have their own settings, so you can keep times where they help and drop them where they do not.

*Tip:* estimates are only useful if they are roughly right. If a course has activities whose durations have not been set, hiding the times reads better than showing figures nobody trusts.';
$string['icon_alert_triangle'] = 'Warning triangle';
$string['icon_award'] = 'Award';
$string['icon_book'] = 'Book';
$string['icon_book_open'] = 'Open book';
$string['icon_briefcase'] = 'Briefcase';
$string['icon_calendar'] = 'Calendar';
$string['icon_check_circle'] = 'Tick in a circle';
$string['icon_clipboard'] = 'Clipboard';
$string['icon_clock'] = 'Clock';
$string['icon_file_text'] = 'Text document';
$string['icon_flag'] = 'Flag';
$string['icon_folder'] = 'Folder';
$string['icon_graduation'] = 'Graduation cap';
$string['icon_hard_hat'] = 'Hard hat';
$string['icon_heart'] = 'Heart';
$string['icon_help_circle'] = 'Question mark in a circle';
$string['icon_home'] = 'Home';
$string['icon_info'] = 'Information';
$string['icon_laptop'] = 'Laptop';
$string['icon_layers'] = 'Layers';
$string['icon_lightbulb'] = 'Light bulb';
$string['icon_lock'] = 'Padlock';
$string['icon_map_pin'] = 'Map pin';
$string['icon_message'] = 'Message';
$string['icon_monitor'] = 'Monitor';
$string['icon_package'] = 'Package';
$string['icon_pen'] = 'Pen';
$string['icon_play_circle'] = 'Play button';
$string['icon_rocket'] = 'Rocket';
$string['icon_settings'] = 'Settings';
$string['icon_shield'] = 'Shield';
$string['icon_shield_check'] = 'Shield with a tick';
$string['icon_star'] = 'Star';
$string['icon_target'] = 'Target';
$string['icon_trophy'] = 'Trophy';
$string['icon_user'] = 'Person';
$string['icon_users'] = 'People';
$string['icon_wrench'] = 'Spanner';
$string['icon_zap'] = 'Lightning bolt';
$string['iconcategory_achievement'] = 'Achievement';
$string['iconcategory_education'] = 'Education';
$string['iconcategory_general'] = 'General';
$string['iconcategory_numbers'] = 'Numbers';
$string['iconcategory_people'] = 'People';
$string['iconcategory_safety'] = 'Safety & compliance';
$string['iconcategory_work'] = 'Work & industry';
$string['iconnumber'] = 'Number {$a}';
$string['iconsaved'] = 'Icon saved';
$string['iconsaveerror'] = 'Error saving icon';
$string['imagequality'] = 'Image quality';
$string['imagequality_desc'] = 'Asked of the image provider for banners and card images. High definition costs more with providers that charge per image and is ignored by those that do not support it.';
$string['imagequality_hd'] = 'High definition';
$string['imagequality_standard'] = 'Standard';
$string['imagestyle'] = 'Image rendering';
$string['imagestyle_desc'] = 'Passed to providers that support it (OpenAI DALL&middot;E 3). Natural looks more realistic and restrained; vivid is more dramatic and saturated. The course\'s own image style setting (photo, illustration, 3D, flat) still applies either way.';
$string['imagestyle_natural'] = 'Natural';
$string['imagestyle_vivid'] = 'Vivid';
$string['immersive'] = 'Hide the site logo band (the tall strip carrying your logo)';
$string['immersive_desc'] = '<strong>What this does:</strong> hides the tall band at the top of the page that carries your site logo and site links — usually well over a hundred pixels of height on every single page.<br /><br />The compact bar above it stays, so notifications, the user menu and the Edit mode toggle are all still there.<br /><br /><em>Example:</em> on a laptop this is roughly one more paragraph of course content visible without scrolling.';
$string['immersive_help'] = 'Most themes put two bands across the top of every page:

1. a thin bar with notifications, messages and your profile menu
2. a taller band underneath holding the site logo and links like Home and Dashboard

This setting hides the **second** band only, which is often 100–150 pixels of height on every single course page.

* **Show** — leave both bands alone.
* **Hide from students** — all course staff keep the logo band, including non-editing teachers. Students do not.
* **Hide from everyone** — nobody sees the logo band while reading the course.

**The thin bar with your profile and the Edit mode switch is never hidden**, so nothing is taken away — and if you turn on the player sidebar, your logo appears there instead.

The band always comes back in Edit mode.';
$string['indexcolour'] = 'Background colour of the course index';
$string['indexcolour_help'] = 'The background of the course index panel - the area listing the sections and activities.

**Leave this empty and it follows the card colour**, so the panel and the cards match without you setting the same value twice. That is the default.

Set a hex colour here only if you want the panel to differ from the cards.

*Tip:* the band at the top of the panel, holding the logo and progress ring, has its own setting - Course index header colour.';
$string['indexheadingcolour'] = 'Colour of the section headings in the course index';
$string['indexheadingcolour_help'] = 'The background of the section headings in the course index. The heading text is white, so this needs to be dark enough to read against.

Leave empty to use your accent colour, which follows the theme\'s primary if you have not set one. That is the default.

*Tip:* the headings are what break a long list into sections, so a colour with some weight works better here than a pale one.';
$string['indexiconcolour'] = 'Colour of the activity icons in the course index';
$string['indexiconcolour_help'] = 'The colour of the small activity icons in the course index.\\n\\nLeave empty to use your accent colour, which follows the theme\'s primary if you have not set one. That is the default, and it matches the section headings so the course\'s colour appears in both places.\\n\\nThe icons have no background of their own - the shape itself is coloured, so a mid to dark tone reads best against the panel behind it.\\n\\n*Tip:* if you would rather the icons blended with the activity names instead of standing out, set this to your body text colour.';
$string['indexopacity'] = 'How strong the course index colour is';
$string['indexopacity_help'] = 'How strongly the course index colour is applied, 0 to 100.

* **100** - the colour exactly as set.
* **50** - halfway between your colour and white.
* **0** - plain white.

This has no effect while Course index colour is empty, because the panel is then following the card colour and its strength instead.

The panel stays fully opaque at every setting - the strength mixes toward white rather than making it see-through.';
$string['indexstate'] = 'How the course index looks when a student first opens the course';
$string['indexstate_collapsed'] = 'Start collapsed';
$string['indexstate_desc'] = '<strong>What this does:</strong> decides whether the course index starts open or closed the very first time a person opens a course.<br /><br />After that first visit, whatever they choose themselves is remembered and this setting stays out of the way. It would be rude to keep reopening a menu somebody has closed.<br /><br /><em>Example:</em> \'Start collapsed\' gives a learner the full width for reading on their first visit; if they open the menu, it stays open next time.';
$string['indexstate_help'] = 'This decides whether the course index panel is already open the first time someone enters the course.

Moodle normally opens it and then remembers whatever that person last chose, across the whole site.

* **Remember the user\'s choice** — leave Moodle\'s normal behaviour alone.
* **Start collapsed** — closed on first entry, so the course content gets the full width of the screen.
* **Start open** — open on first entry, so learners can see the whole course straight away.

**This only sets the starting point.** After that first visit the learner\'s own choice is respected — if they open the panel, it stays open next time. A setting that forced it every page load would be fighting the person using it.

*Example:* a course meant to be read straight through suits Start collapsed. A reference course people dip in and out of suits Start open.';
$string['indexstate_open'] = 'Start open';
$string['indexstate_remember'] = 'Remember the user\'s choice';
$string['inprogress'] = 'In Progress';
$string['js_completionerror'] = 'Failed to update completion status';
$string['js_done'] = 'Done';
$string['js_iconremoved'] = 'Icon removed';
$string['js_iconsfound'] = '{$a} icons found';
$string['js_progressannounce'] = 'Course progress: {$a}%';
$string['js_sectionadded'] = 'Section added';
$string['js_sectionadderror'] = 'Failed to add section';
$string['js_sectiondeleted'] = 'Section deleted successfully';
$string['js_sectiondeleteerror'] = 'Failed to delete section';
$string['js_sectionduplicated'] = 'Section duplicated successfully';
$string['js_sectionduplicateerror'] = 'Failed to duplicate section';
$string['labelseparator'] = ', ';
$string['listseparator'] = ' • ';
$string['markasdonefor'] = 'Mark {$a} as done';
$string['markasdoneundo'] = 'Mark {$a} as not done';
$string['maxcontextchars'] = 'Course content sent with each question';
$string['maxcontextchars_desc'] = 'The most characters of course content (section names, activity text, slide text and so on) included with each Ask Dari question. 40000 characters is roughly 10,000 tokens and suits hosted models such as GPT-4o, Gemini or Claude. For small self-hosted models (for example through Ollama) use 6000-8000, or the start of the prompt is cut off.';
$string['minutesfallback'] = 'Minutes to use for anything not listed above';
$string['minutesfallback_desc'] = '<strong>What this does:</strong> the time used for any activity type you have not listed in the box above.<br /><br />Keep it small. It is a guess, and a confident-looking wrong number is worse than a modest one. <em>Example:</em> 5 minutes.';
$string['minutesperquestion'] = 'Minutes to allow per quiz question';
$string['minutesperquestion_desc'] = '<strong>What this does:</strong> works out how long a quiz should take by multiplying this number by the number of questions in it.<br /><br /><em>Example:</em> at 1 minute per question, a 10-question quiz shows \'10 min\' and a 40-question exam shows \'40 min\'.<br /><br />It is done per question rather than as one flat figure because \'a quiz\' is not one length — a five-question check and a final exam are very different things.';
$string['nactivities'] = '{$a} activities';
$string['nextactivity'] = 'Next activity';
$string['nextactivitynamed'] = 'Next activity: {$a}';
$string['nextsection'] = 'Next section';
$string['nextsectionnamed'] = 'Next section: {$a}';
$string['noactivitiesinsection'] = 'This section is empty. Add activities to get started.';
$string['nocompletion'] = 'No completion tracking';
$string['notstarted'] = 'Not Started';
$string['nsections'] = '{$a} modules';
$string['oneactivity'] = '1 activity';
$string['onesection'] = '1 module';
$string['page-course-view-dari'] = 'Any course main page in Dari course format';
$string['page-course-view-dari-x'] = 'Any course page in Dari course format';
$string['percentcomplete'] = '{$a}% complete';
$string['percentvalue'] = '{$a}%';
$string['player_closeindex'] = 'Close course index';
$string['player_completedon'] = 'Completed {$a}';
$string['player_dashboard'] = 'Dashboard';
$string['player_done'] = 'Completed';
$string['player_gradeachieved'] = 'Grade {$a->grade} / {$a->max} ({$a->percent}%)';
$string['player_home'] = 'Home';
$string['player_mycourses'] = 'My courses';
$string['player_navlabel'] = 'Site navigation';
$string['player_notdone'] = 'Not completed';
$string['player_progress'] = '{$a}% of this course complete';
$string['player_requires'] = 'To complete this activity';
$string['playerheadercolour'] = 'Colour of the band at the top of the course index';
$string['playerheadercolour_help'] = 'This is the background of the band at the very top of the course index — the part holding your logo, the course name, the progress ring and the total time.

The rest of the panel is white, so this band is what separates the course information from the list of activities beneath it.

Enter a hex colour like <code>#eceff4</code>. Leave it empty to use the site setting, and if that is empty too, a light grey.

*Tip:* keep it subtle. This is a background behind text, not a feature colour — something close to white usually reads best.';
$string['playerindex'] = 'Course player sidebar (turns the plain side menu into a progress tracker)';
$string['playerindex_desc'] = '<strong>What this does:</strong> turns Moodle\'s plain course index into a progress sidebar.<br /><br />You get the course name, a progress ring, the total time the course should take, a link back to My courses, and one row per activity showing how long it takes and whether it is finished.<br /><br /><em>Example:</em> a learner opening Module 2 sees at a glance that they are 63% through the course and that three activities in this module are still outstanding.<br /><br />It decorates Moodle\'s own course index rather than replacing it, so drag-and-drop and everything else a teacher expects still works.';
$string['playerindex_help'] = 'The course index is the panel that slides out on the left, listing every section and activity in the course.

Moodle\'s version is a plain list of links. The **player sidebar** turns it into something a learner can plan with:

* your logo at the top
* the course name, a progress ring, and the total time the course takes
* every activity showing its own icon, how long it takes, and a green tick once it is finished

It still behaves like Moodle\'s course index underneath — sections still collapse, drag and drop still works while editing, and the page you are on is still highlighted.

* **Plain course index** — Moodle\'s normal list.
* **Player sidebar** — the richer version described above.';
$string['playerindex_off'] = 'Plain course index';
$string['playerindex_on'] = 'Player sidebar';
$string['playerindex_site'] = 'Use the site default';
$string['playerlogo'] = 'Logo shown at the top of the course index';
$string['playerlogo_desc'] = '<strong>What this does:</strong> puts your own logo at the top of the course index, instead of the site logo.<br /><br /><em>Useful when</em> a course is branded for a client rather than for the institution hosting it.<br /><br />Leave it empty to fall back to the site logo. Any web image format works and it is scaled to fit, so a wide logo is fine.';
$string['plugin_description'] = 'Visual section cards with a progress banner, and Ask Dari, an AI study assistant that runs on your school\'s own AI.';
$string['pluginname'] = 'Dari Course Format';
$string['previousactivity'] = 'Previous activity';
$string['previousactivitynamed'] = 'Previous activity: {$a}';
$string['previoussection'] = 'Previous section';
$string['previoussectionnamed'] = 'Previous section: {$a}';
$string['privacy:metadata:core_ai'] = 'To answer Ask Dari questions and generate images, the Dari course format passes the request to Moodle\'s AI subsystem, which sends it to the AI provider configured for the site and records it in the AI usage log. The tutor request includes the user\'s question, their first name, the name of the course, section and activity they are in, the quiz question they are working on (if any), a summary of what they previously asked about in that activity, their last few questions and answers, and the text content of the course.';
$string['privacy:metadata:dari_direct_ai'] = 'On Moodle 4.4, Ask Dari questions and image requests are sent directly to the AI service the administrator configured in the Dari course format settings.';
$string['privacy:metadata:dari_direct_ai:coursecontent'] = 'The text content of the course and where the user is in it.';
$string['privacy:metadata:dari_direct_ai:firstname'] = 'The user\'s first name, unless the administrator has turned this off or the course audience is primary school.';
$string['privacy:metadata:dari_direct_ai:question'] = 'The question the user asked, and their last few questions and answers in the same activity.';
$string['privacy:metadata:format_dari_actminutes'] = 'Per-activity estimated durations set by a teacher.';
$string['privacy:metadata:format_dari_actminutes:cmid'] = 'The activity the estimate applies to.';
$string['privacy:metadata:format_dari_actminutes:courseid'] = 'The course the activity belongs to.';
$string['privacy:metadata:format_dari_actminutes:minutes'] = 'The estimated duration in minutes.';
$string['privacy:metadata:format_dari_actminutes:timemodified'] = 'When the estimate was last changed.';
$string['privacy:metadata:format_dari_actminutes:usermodified'] = 'The user who last set this estimate.';
$string['privacy:metadata:format_dari_ai_memory'] = 'A short rolling summary, held per user and per activity, of the topics the user has previously asked Ask Dari about. It is used to give continuity between tutoring sessions and never stores answers to assessed work.';
$string['privacy:metadata:format_dari_ai_memory:activityid'] = 'The ID of the course module the memory relates to.';
$string['privacy:metadata:format_dari_ai_memory:courseid'] = 'The ID of the course the memory relates to.';
$string['privacy:metadata:format_dari_ai_memory:memory'] = 'The summary of the topics the user has previously asked about.';
$string['privacy:metadata:format_dari_ai_memory:timeupdated'] = 'The time the memory was last updated.';
$string['privacy:metadata:format_dari_ai_memory:userid'] = 'The ID of the user the memory belongs to.';
$string['privacy:metadata:format_dari_cardstyle'] = 'Colours a teacher has chosen for section and activity cards.';
$string['privacy:metadata:format_dari_cardstyle:colour'] = 'The chosen colour.';
$string['privacy:metadata:format_dari_cardstyle:courseid'] = 'The course the card belongs to.';
$string['privacy:metadata:format_dari_cardstyle:targetid'] = 'The section or activity the card shows.';
$string['privacy:metadata:format_dari_cardstyle:targettype'] = 'Whether the card is a section card or an activity card.';
$string['privacy:metadata:format_dari_cardstyle:timemodified'] = 'When the colour was last changed.';
$string['privacy:metadata:format_dari_cardstyle:usermodified'] = 'The user who last chose this colour.';
$string['privacy:metadata:format_dari_chats'] = 'A record of every question asked of Ask Dari, the answer the AI gave, and any correction a teacher later applied to that answer.';
$string['privacy:metadata:format_dari_chats:activityid'] = 'The ID of the course module the question was asked from, or 0 if it was asked from the course home page.';
$string['privacy:metadata:format_dari_chats:correctedby'] = 'The ID of the teacher who wrote the correction.';
$string['privacy:metadata:format_dari_chats:correction'] = 'A correction written by a teacher to replace or amend the AI response.';
$string['privacy:metadata:format_dari_chats:courseid'] = 'The ID of the course the question was asked in.';
$string['privacy:metadata:format_dari_chats:locked'] = 'Whether the related activity had already been submitted, in which case Ask Dari answers in reflection mode only.';
$string['privacy:metadata:format_dari_chats:question'] = 'The full text of the question the user asked Ask Dari.';
$string['privacy:metadata:format_dari_chats:questionslot'] = 'The quiz question slot number the question relates to, where the user asked from within a quiz.';
$string['privacy:metadata:format_dari_chats:rating'] = 'The rating the user gave the AI response: helpful, not helpful, or unrated.';
$string['privacy:metadata:format_dari_chats:refused'] = 'Whether the AI declined to answer in order to protect academic integrity.';
$string['privacy:metadata:format_dari_chats:response'] = 'The full text of the answer Ask Dari returned.';
$string['privacy:metadata:format_dari_chats:timecorrected'] = 'The time the correction was written.';
$string['privacy:metadata:format_dari_chats:timecreated'] = 'The time the question was asked.';
$string['privacy:metadata:format_dari_chats:userid'] = 'The ID of the user who asked the question.';
$string['privacy:metadata:preference:herocollapsed'] = 'Whether the user has collapsed the course banner.';
$string['privacy:metadata:preference:indexstate'] = 'Whether the course index\'s starting open or closed state has been applied for the user in a course.';
$string['privacy:metadata:preference:tourseen'] = 'Whether the user has already been offered the first-visit tour.';
$string['privacy:metadata:preference:tutorseen'] = 'Whether Ask Dari has already introduced itself to the user in a course.';
$string['privacy:path:chats'] = 'Ask Dari conversations';
$string['privacy:path:corrections'] = 'Ask Dari corrections written by you';
$string['privacy:path:memory'] = 'Ask Dari memory';
$string['removebannerimage'] = 'Remove banner image';
$string['removeicon'] = 'Remove icon';
$string['removesectionbannerimage'] = 'Remove this section\'s banner image';
$string['returntosectionnamed'] = 'Return to section: {$a}';
$string['scrimstrength'] = 'Banner darkening (older setting — use the one above instead)';
$string['scrimstrength_desc'] = 'How much the hero banner image is darkened behind the course title.<br /><br />The overlay exists so white text stays readable over any image, including a near-white one. <strong>Strong</strong> was the only behaviour before 2.1.12 and is heavier than accessibility requires — its foot reaches 16.2:1 against white text where WCAG AA asks for 4.5:1 — which visibly crushes darker photographs. <strong>Medium</strong> (the default) and <strong>Light</strong> relax the top and bottom of the gradient only; the band where the title and summary sit never drops below 4.9:1 on a worst-case white image, so all three remain accessible.';
$string['scrimstrength_light'] = 'Light - image most visible';
$string['scrimstrength_medium'] = 'Medium (recommended)';
$string['scrimstrength_strong'] = 'Strong - pre-2.1.12 appearance';
$string['searchicons'] = 'Search icons…';
$string['section0name'] = 'General';
$string['sectionactivitiesregion'] = '{$a} activities';
$string['sectionbannerheader'] = 'Section Banner Image';
$string['sectionbannerimage'] = 'Upload section banner image';
$string['sectionbannerimage_help'] = 'Upload one image to use as this section\'s hero banner. It replaces the course banner on this section\'s page and on the pages of the activities inside it, leaving every other section unchanged. Landscape images work best - around 1920 x 600 pixels, at most 5 MB, in JPG, PNG or WebP. Leave it empty and this section uses the course banner instead. You can also generate one with AI from the section page.';
$string['sectionbannerimage_inherits'] = 'Leave this empty and the section uses the course banner. Accepted formats: JPG - PNG - WebP. Maximum file size: 5 MB. One image per section.';
$string['sectionname'] = 'Section';
$string['sectionnotfound'] = 'Section not found.';
$string['sectionnumber'] = 'Section {$a}';
$string['sectionprogress'] = 'Section progress';
$string['selecticon'] = 'Select icon';
$string['sendfirstname'] = 'Send learners\' first names';
$string['sendfirstname_desc'] = 'When on, the learner\'s first name is included so the tutor can address them by name. Turn off to send no names at all. Always off for courses whose tutor audience is set to primary school.';
$string['settingsui_about'] = 'About this plugin';
$string['settingsui_all'] = 'All';
$string['settingsui_appliestoall'] = 'Applies to every course that already exists';
$string['settingsui_course'] = 'Course';
$string['settingsui_filterby'] = 'Filter:';
$string['settingsui_hide'] = 'Hide';
$string['settingsui_new'] = 'Recently added';
$string['settingsui_nomatches'] = 'No settings match that search. Try a shorter word, or choose All settings.';
$string['settingsui_other'] = 'Other';
$string['settingsui_search'] = 'Search settings…';
$string['settingsui_setting'] = 'setting';
$string['settingsui_settings'] = 'settings';
$string['settingsui_show'] = 'Show';
$string['shareanswers_always'] = 'Always share, in every course';
$string['shareanswers_never'] = 'Never share';
$string['shareanswers_percourse'] = 'Let each course decide';
$string['shareassessmentanswers'] = 'Send quiz and knowledge-check answers to Ask Dari';
$string['shareassessmentanswers_desc'] = 'Ask Dari answers from an index of each course that is sent to the site\'s AI provider. This setting decides whether that index also includes the correct answers to quiz and knowledge check questions, the answer options, the per-option feedback, the slide answers and the essay marking guide ("information for graders") that learners never see.<br /><br /><strong>Never share</strong> (the default) keeps every answer key inside your Moodle site.<br /><strong>Always share, in every course</strong> sends answer keys for every course on this site.<br /><strong>Let each course decide</strong> keeps answer keys back unless a teacher turns "Send quiz and knowledge-check answers to Ask Dari" on in that individual course\'s settings; this setting is always the ceiling, so a course can never share what you have not permitted here.<br /><br /><strong>Leave this on "Never share" unless you have a specific reason to change it, and have confirmed that your AI provider agreement permits assessment answers to leave your Moodle site.</strong> With it off the tutor still receives every question\'s wording, so it can discuss the topic and point learners at the right material - it simply does not hold the answer key.';
$string['shareassessmentanswers_help'] = 'Ask Dari answers from an index of this course that is sent to the site\'s AI provider. When this is set to Yes, that index also includes the correct answers to this course\'s quiz and knowledge check questions, the answer options, the per-option feedback, the slide answers and the essay marking guide ("information for graders") that learners never see.<br /><br /><strong>This setting only takes effect if your site administrator has set "Send quiz and knowledge-check answers to Ask Dari" to "Let each course decide". On a site set to "Never share" nothing is shared whatever you choose here, and on a site set to "Always share" answers are shared whatever you choose here.</strong><br /><br /><strong>Only choose Yes if you have a specific teaching reason - a revision course, for example - and have confirmed with your site administrator that your AI provider agreement permits assessment answers to leave your Moodle site.</strong> With it set to No the tutor still receives every question\'s wording, so it can discuss the topic and point learners at the right material - it simply does not hold the answer key.';
$string['showactivitiesoncards'] = 'List the activities on each section card';
$string['showactivitiesoncards_desc'] = '<strong>What this does:</strong> lists the activities inside each section card, on the course home page, with a tick beside the ones already finished.<br /><br /><em>Example:</em> a learner can see that Module 2 contains a video, a reading and a quiz — and that they have done the first two — without opening it.';
$string['showactivitiesoncards_help'] = 'When enabled, each section card on the course home page also lists that section\'s activities beneath the summary, with the learner\'s completion state for each one. Only the first few are listed; a "+N" link opens the section to see the rest. Activities a learner cannot see are never listed. Leave this off to keep the card grid at its most compact and scannable.';
$string['showcourseindex'] = 'Show the course index (the side menu listing every section and activity)';
$string['showcourseindex_desc'] = '<strong>What the course index is:</strong> the menu that slides out from the side of a course, listing every section and every activity so a learner can jump straight to any part of it.<br /><br /><strong>What this does:</strong> chooses which pages show that menu.<br /><br /><em>Example:</em> choose \'Course home only\' and learners get the menu on the main course page, while an activity page fills the whole width with nothing beside it — good for reading, less good for jumping around.<br /><br />This is a starting value for new courses. Each course can change it in its own settings.';
$string['showcourseindex_help'] = 'The course index sidebar appears on the left side, allowing quick navigation between sections and activities. Choose which pages should display it.';
$string['showfromothers'] = 'Show section';
$string['showherobanner'] = 'Show the hero banner (the wide image strip across the top of the course)';
$string['showherobanner_desc'] = '<strong>What the hero banner is:</strong> the wide strip across the top of a course carrying the course image, its name, and the learner\'s progress.<br /><br /><em>Example:</em> switch it off and the course starts straight at the section cards, which suits a short course where a banner is more decoration than help.';
$string['showherobanner_help'] = 'When enabled, a beautiful sticky hero banner appears at the top of the course page featuring the course image, title, and real-time progress tracking. The banner uses glassmorphism effects and stays visible as students scroll.';
$string['shownavchevrons'] = 'Show next / previous arrows on activity pages';
$string['shownavchevrons_desc'] = '<strong>What this does:</strong> shows back and forward arrows on activity pages so a learner can move to the next activity without returning to the course page.<br /><br /><em>Example:</em> finishing a video, they press the arrow and land on the quiz that follows it.';
$string['shownavchevrons_help'] = 'When enabled, elegant navigation chevrons appear on the left and right sides of activity pages, allowing students to quickly move between activities without returning to the course page.';
$string['sitedefault_desc'] = '<strong>What this does:</strong> sets the starting value for <em>brand new</em> courses only.<br /><br /><strong>Important:</strong> changing this does <strong>not</strong> touch courses that already exist. A course keeps whatever it has as soon as anyone saves its settings form.<br /><br /><em>Example:</em> you set this to \'Hide\'. A course created tomorrow starts hidden. The forty courses you already have carry on exactly as they were.<br /><br />To change every existing course, use the <strong>\'apply to ALL existing courses\'</strong> setting that sits directly below this one.';
$string['supportcontacts'] = 'Wellbeing contacts';
$string['supportcontacts_default'] = 'your teacher or trainer, or a trusted adult. In an emergency call 000. Kids Helpline 1800 55 1800 (ages 5-25). Lifeline 13 11 14';
$string['supportcontacts_desc'] = 'If a learner tells Ask Dari they are being hurt, are in danger or are thinking of harming themselves or someone else, the tutor does not counsel them: it replies briefly and kindly and tells them to get help now from the people and services listed here. Adjust it to your country and your organisation\'s own child-safe or student-support policy.';
$string['taskgeneratebanner'] = 'Generate AI course banner image';
$string['taskgeneratecardimage'] = 'Generate AI card image';
$string['themesupport'] = 'Theme compatibility';
$string['themesupport_desc'] = 'This course format is developed and tested against <strong>Boost</strong> and <strong>Academi</strong>. Both are checked on every release, on desktop, tablet and mobile widths.<br /><br />It should work with most Boost-based themes, because it builds on the same course index, navigation and header that Boost provides. A theme that positions those differently may produce a layout that does not look right — a banner that will not reach the edges, a course index that overlaps the content, or spacing that looks wrong.<br /><br /><strong>If you are using another theme and something looks wrong</strong>, please email <a href="mailto:miss.darika2533@icloud.com">miss.darika2533@icloud.com</a> and tell us which theme you are using. Nearly all of these turn out to be small differences we can support once we know the theme exists.';
$string['timingheading'] = 'Estimated activity durations';
$string['timingheading_desc'] = '<strong>What these do:</strong> the time badge on each section card is simply the sum of its activities\' estimated times. These settings decide the starting figure for each kind of activity.<br /><br />A teacher can override any single activity by clicking its time badge with editing turned on, and that override always wins.';
$string['tour_back'] = 'Back';
$string['tour_finish'] = 'Finish';
$string['tour_mute'] = 'Mute narration';
$string['tour_next'] = 'Next';
$string['tour_offer_body'] = 'Two minutes, with narration, on how this course is laid out. Stop or mute whenever you like.';
$string['tour_offer_dismiss'] = 'Not now';
$string['tour_offer_start'] = 'Show me around';
$string['tour_offer_title'] = 'Want a short walkthrough?';
$string['tour_progress'] = '{$a->current} / {$a->total}';
$string['tour_s_cards_body'] = 'Each block is one section of the course. It shows what is inside and how much of it you have done.';
$string['tour_s_cards_title'] = 'Sections';
$string['tour_s_done_body'] = 'Choose a section to begin.';
$string['tour_s_done_title'] = 'All set';
$string['tour_s_grades_body'] = 'Opens your grades for this course. Only you can see them.';
$string['tour_s_grades_title'] = 'Your marks';
$string['tour_s_index_body'] = 'Every section and activity is listed here. Pick any of them to go straight there.';
$string['tour_s_index_title'] = 'The course map';
$string['tour_s_progress_body'] = 'The ring and bar show how much of the course you have completed, and they update as you go.';
$string['tour_s_progress_title'] = 'Progress so far';
$string['tour_s_ring_body'] = 'The ring counts only the activities your teacher tracks, so it may not include everything you can see.';
$string['tour_s_ring_title'] = 'Your completion';
$string['tour_s_sidebar_body'] = 'This panel lists the whole course. Each row shows about how long it takes, and gets a tick when you finish. The ring at the top shows your overall progress.';
$string['tour_s_sidebar_title'] = 'Everything in one panel';
$string['tour_s_status_body'] = 'A filled square means finished; an empty one is still waiting. Some activities finish themselves; others you mark yourself.';
$string['tour_s_status_title'] = 'Done and still to do';
$string['tour_s_time_body'] = 'Every activity shows an estimated time, and each section adds them up. Use it to plan your study time.';
$string['tour_s_time_title'] = 'Time to allow';
$string['tour_s_tutor_body'] = 'Stuck? Ask Dari. It knows this course and helps you work things out instead of just giving you the answer.';
$string['tour_s_tutor_title'] = 'Ask Dari';
$string['tour_s_welcome_body'] = 'A one-minute look at how this course works. Skip it whenever you like.';
$string['tour_s_welcome_title'] = 'Let\'s get you started';
$string['tour_skip'] = 'End tour';
$string['tour_t_activities_body'] = 'Each block lists its activities and marks the finished ones. Limit the list or turn it off in course settings.';
$string['tour_t_activities_title'] = 'Activity lists';
$string['tour_t_banner_body'] = 'It holds the course name, progress and quick actions. Upload your own image in course settings, or generate one.';
$string['tour_t_banner_title'] = 'The banner';
$string['tour_t_cards_body'] = 'Each section is a block showing its activities and learner progress. Give each an icon and choose a grid or list layout in course settings.';
$string['tour_t_cards_title'] = 'Sections as blocks';
$string['tour_t_done_body'] = 'Try the learner view next to see the course the way your students do.';
$string['tour_t_done_title'] = 'End of tour';
$string['tour_t_generate_body'] = 'Creates a banner image from the course name in the background, using your site\'s AI provider. Keep working while it runs.';
$string['tour_t_generate_title'] = 'Make a banner';
$string['tour_t_grades_body'] = 'Goes straight to the gradebook. Learners see the same button, but only their own grades.';
$string['tour_t_grades_title'] = 'Gradebook shortcut';
$string['tour_t_icons_body'] = 'While editing, select a section\'s icon to change it. Sections without one show a plain placeholder.';
$string['tour_t_icons_title'] = 'Choosing icons';
$string['tour_t_index_body'] = 'You can show the course map separately on the course page, on section pages and on activity pages (course settings).';
$string['tour_t_index_title'] = 'Course map';
$string['tour_t_report_body'] = 'The course\'s More menu has the Ask Dari Report: what learners ask, where they get stuck, and which activities raise the most questions.';
$string['tour_t_report_title'] = 'Ask Dari insights';
$string['tour_t_ring_body'] = 'It counts only activities with completion tracking turned on. If it looks low, check which activities track completion.';
$string['tour_t_ring_title'] = 'Completion ring';
$string['tour_t_settings_body'] = 'Everything in this tour is configurable per course under Settings, and site-wide under Plugins, Course formats, Dari course format.';
$string['tour_t_settings_title'] = 'Where the settings are';
$string['tour_t_sidebar_body'] = 'When it is turned on, the course map becomes a player panel: logo, course, progress ring, total time, and one row per activity with its time and a tick.';
$string['tour_t_sidebar_title'] = 'Player panel';
$string['tour_t_studentview_body'] = 'Learners see a simpler page: the Course, Settings and Participants tabs and all editing controls are hidden by default.';
$string['tour_t_studentview_title'] = 'Preview as a learner';
$string['tour_t_time_body'] = 'Each activity has an estimated time, and section totals add them up. Defaults are in the site settings; quiz times are worked out from their questions.';
$string['tour_t_time_title'] = 'Time estimates';
$string['tour_t_tutor_body'] = 'Learners ask questions here. It reads this course\'s content, so its answers are about your material.';
$string['tour_t_tutor_title'] = 'Ask Dari';
$string['tour_t_welcome_body'] = 'A two-minute look at what Dari adds to this course. Leave whenever you like.';
$string['tour_t_welcome_title'] = 'Welcome to the Dari course format';
$string['tour_unmute'] = 'Unmute narration';
$string['tourvoice'] = 'Language for the tour narration';
$string['tourvoice_desc'] = '<strong>What this does:</strong> chooses the accent the narration uses.<br /><br />Use a standard language tag: <code>en-AU</code> for Australian English, <code>en-GB</code> for British, <code>en-US</code> for American. The closest available voice is picked.';
$string['tourvoiceover'] = 'Read the guided tour aloud';
$string['tourvoiceover_desc'] = '<strong>What this does:</strong> lets the first-run guided tour read each step out loud.<br /><br />Learners and teachers can mute it themselves and the choice is remembered, so this only sets the starting point.';
$string['tutoraudience'] = 'Ask Dari audience';
$string['tutoraudience_adult'] = 'Adult learners (VET, higher education, workplace)';
$string['tutoraudience_help'] = 'Who Ask Dari is talking to in this course. It sets the reading level, tone and safeguarding rules the tutor follows. Adult learners get plain-English, workplace-focused help. Secondary students get friendly, school-appropriate language and are steered back to their learning if they go off topic. Primary students get very simple language, and their first names are never sent to the AI provider.';
$string['tutoraudience_primary'] = 'Primary school students';
$string['tutoraudience_secondary'] = 'Secondary school students';
$string['viewallactivities'] = 'View all activities';
$string['viewsection'] = 'View section';
