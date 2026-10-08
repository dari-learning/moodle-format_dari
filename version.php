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
 * Version details for the Dari course format.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'format_dari';
$plugin->version      = 2026100807;
// Supports Moodle 4.4 to 5.3. Moodle 4.4 is the minimum: the hero banner and Ask Dari are injected
// through the footer hook added in 4.4. On 4.5 and later all AI goes through Moodle's AI subsystem;
// on 4.4, which has none, Dari uses the school's own OpenAI-compatible connection set in its settings.
$plugin->requires     = 2024042200;
$plugin->supported    = [404, 503];
$plugin->maturity     = MATURITY_STABLE;
$plugin->release      = '1.0.6';
$plugin->dependencies = [];
