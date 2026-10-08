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
 * Records requests made through Moodle 4.4's direct provider.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_dari\test;

/**
 * Provider request, deliberately not a fabricated core AI class.
 */
class direct_action {
    /** @var string Corresponding core action name, without requiring core AI. */
    public string $type;

    /** @var array Fields sent to the provider. */
    private array $configuration;

    /**
     * Constructor.
     *
     * @param string $type The corresponding action name.
     * @param array $configuration Fields actually sent to the provider.
     */
    public function __construct(string $type, array $configuration) {
        $this->type = $type;
        $this->configuration = $configuration;
    }

    /**
     * Read a provider request field.
     *
     * @param string $name Field name.
     * @return mixed
     */
    public function get_configuration(string $name): mixed {
        return $this->configuration[$name] ?? null;
    }
}
