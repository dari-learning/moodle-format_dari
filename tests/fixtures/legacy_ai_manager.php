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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Moodle 4.5 manager with deterministic static availability.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_dari\test;

/**
 * Only loaded on versions whose manager availability method is static.
 */
class legacy_ai_manager extends \core_ai\manager {
    /** @var array Availability by action class. */
    public static array $availability = [];

    /**
     * Whether an action is available.
     *
     * @param string $actionclass Action class name.
     * @return bool
     */
    public static function is_action_available(string $actionclass): bool {
        return self::$availability[$actionclass] ?? false;
    }
}
