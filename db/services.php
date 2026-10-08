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
 * External function definitions for the Dari course format.
 *
 * The 'capabilities' entry of each definition documents the capability the function itself
 * enforces with require_capability(); it is advisory metadata used by the web service
 * administration screens and is NOT a substitute for the check inside execute().
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'format_dari_get_progress' => [
        'classname' => 'format_dari\external\get_progress',
        'methodname' => 'execute',
        'description' => 'Get the calling user\'s completion progress for a course.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'format/dari:view',
        'readonlysession' => true,
    ],

    'format_dari_save_icon' => [
        'classname' => 'format_dari\external\save_icon',
        'methodname' => 'execute',
        'description' => 'Set or clear the decorative icon shown on a section card.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_add_section' => [
        'classname' => 'format_dari\external\add_section',
        'methodname' => 'execute',
        'description' => 'Append a new section to the end of the course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_duplicate_section' => [
        'classname' => 'format_dari\external\duplicate_section',
        'methodname' => 'execute',
        'description' => 'Duplicate a section, including every activity inside it.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_delete_section' => [
        'classname' => 'format_dari\external\delete_section',
        'methodname' => 'execute',
        'description' => 'Delete a section and the activities inside it.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_ai_chat' => [
        'classname' => 'format_dari\external\ai_chat',
        'methodname' => 'execute',
        'description' => 'Ask Ask Dari a question about the course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'format/dari:useaitutor',
        // The remote call takes up to 60 seconds; do not hold the session lock for its duration.
        'readonlysession' => true,
    ],

    'format_dari_rate_chat' => [
        'classname' => 'format_dari\external\rate_chat',
        'methodname' => 'execute',
        'description' => 'Rate one of the calling user\'s own Ask Dari answers.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'format/dari:useaitutor',
    ],

    'format_dari_correct_chat' => [
        'classname' => 'format_dari\external\correct_chat',
        'methodname' => 'execute',
        'description' => 'Write a teacher correction onto an Ask Dari answer.',
        'type' => 'write',
        'ajax' => true,
        // NOT moodle/course:viewparticipants, which students hold by default.
        'capabilities' => 'format/dari:viewreport, format/dari:correctresponses',
    ],

    'format_dari_get_activity_context' => [
        'classname' => 'format_dari\external\get_activity_context',
        'methodname' => 'execute',
        'description' => 'Get the public introduction and question prompts of an activity, with '
            . 'every answer key removed.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'format/dari:useaitutor',
        'readonlysession' => true,
    ],

    // Note: inline editing of an activity's estimated duration.
    'format_dari_set_activity_minutes' => [
        'classname' => 'format_dari\\external\\set_activity_minutes',
        'methodname' => 'execute',
        'description' => 'Set or clear the estimated duration for one activity.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    // Note: polled while the adhoc task runs. Read-only, no remote call, no credits.
    'format_dari_get_banner_status' => [
        'classname' => 'format_dari\\external\\get_banner_status',
        'methodname' => 'execute',
        'description' => 'Report the state of a queued banner image generation.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
        'readonlysession' => true,
    ],

    'format_dari_generate_banner_image' => [
        'classname' => 'format_dari\external\generate_banner_image',
        'methodname' => 'execute',
        'description' => 'Generate an AI course banner image and store it against the course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
        // The remote call takes up to 90 seconds; do not hold the session lock for its duration.
        'readonlysession' => true,
    ],

    'format_dari_delete_banner_image' => [
        'classname' => 'format_dari\external\delete_banner_image',
        'methodname' => 'execute',
        'description' => 'Remove the AI generated banner image from a course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    // Card images and colours.
    'format_dari_upload_card_image' => [
        'classname' => 'format_dari\\external\\upload_card_image',
        'methodname' => 'execute',
        'description' => 'Store an uploaded picture as a section or activity card image.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_log_client_event' => [
        'classname' => 'format_dari\\external\\log_client_event',
        'methodname' => 'execute',
        'description' => 'Record a browser error from a Dari course page in the diagnostics log.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
        'readonlysession' => true,
    ],

    'format_dari_generate_card_image' => [
        'classname' => 'format_dari\\external\\generate_card_image',
        'methodname' => 'execute',
        'description' => 'Queue an AI generated image for a section or activity card.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
        'readonlysession' => true,
    ],

    'format_dari_get_card_image_status' => [
        'classname' => 'format_dari\\external\\get_card_image_status',
        'methodname' => 'execute',
        'description' => 'Report the state of a queued card image generation.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
        'readonlysession' => true,
    ],

    'format_dari_delete_card_image' => [
        'classname' => 'format_dari\\external\\delete_card_image',
        'methodname' => 'execute',
        'description' => 'Remove a section or activity card image.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_set_card_colour' => [
        'classname' => 'format_dari\\external\\set_card_colour',
        'methodname' => 'execute',
        'description' => 'Set or clear the colour of a section or activity card.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],

    'format_dari_generate_all_card_images' => [
        'classname' => 'format_dari\\external\\generate_all_card_images',
        'methodname' => 'execute',
        'description' => 'Count, or queue, AI images for many section and activity cards at once.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],
    'format_dari_get_image_prompt' => [
        'classname' => 'format_dari\\external\\get_image_prompt',
        'methodname' => 'execute',
        'description' => 'The prompt last sent to the image model for a card or banner.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],
];
