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

use core_ai\aiactions\generate_image;

/**
 * The one image model Dari paints with, and the routing that makes sure nothing else is used.
 *
 * Dari generates every banner and card image with a single model: the highest rated image model of
 * the image engine the administrator chose (`imageengine`). There is no second choice and no
 * cheaper fallback.
 *
 *  - Google (the default): Nano Banana 2.1, `gemini-nano-banana-2.1`, Google's highest rated image
 *    model (LMArena text-to-image, October 2026), stable in the Gemini API since 6 October 2026.
 *  - OpenAI: GPT Image 2.5 Sunburst, `gpt-image-2.5-sunburst`, first on the LMArena text-to-image
 *    leaderboard in October 2026.
 *
 * That needs care on Moodle 4.5 and later. Core's AI manager hands an action to every enabled
 * provider in turn until one succeeds, so a site with a second provider (or an older model)
 * silently gets that provider's image whenever the first one fails. Dari therefore picks the one
 * provider instance whose Generate image action is configured with the engine's model and runs the
 * action on that provider only. The request still goes through core: the provider's own processor,
 * its rate limits and the AI usage report record are exactly what core would do; only the
 * fall-through to other providers is removed.
 *
 * On Moodle 4.4 there is no AI subsystem and \format_dari\local\direct calls the engine itself.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imagemodel {
    /** @var string Google's image engine. */
    public const ENGINE_GOOGLE = 'google';

    /** @var string OpenAI's image engine. */
    public const ENGINE_OPENAI = 'openai';

    /** @var string The engine used when the administrator has not chosen one. */
    public const DEFAULT_ENGINE = self::ENGINE_GOOGLE;

    /** @var array The one model per engine: API identifier and name for people. */
    public const ENGINES = [
        self::ENGINE_GOOGLE => [
            'model' => 'gemini-nano-banana-2.1',
            'label' => 'Nano Banana 2.1 (Google Gemini)',
        ],
        self::ENGINE_OPENAI => [
            'model' => 'gpt-image-2.5-sunburst',
            'label' => 'GPT Image 2.5 Sunburst (OpenAI)',
        ],
    ];

    /** @var string Google's API base, for the direct (Moodle 4.4) route. */
    public const GOOGLE_API = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /** @var string Image size asked of Google on the direct route (1K, 2K or 4K; upper-case K). */
    public const GOOGLE_IMAGE_SIZE = '2K';

    /** @var string[] Aspect ratios asked of Google on the direct route; landscape is 16:9, like the cards. */
    public const GOOGLE_RATIOS = [
        'square' => '1:1',
        'landscape' => '16:9',
        'portrait' => '9:16',
    ];

    /** @var string Quality asked of OpenAI on the direct route. */
    public const DIRECT_QUALITY = 'high';

    /** @var string[] Sizes asked of OpenAI on the direct route; landscape is 16:9, like the cards. */
    public const DIRECT_SIZES = [
        'square' => '1024x1024',
        'landscape' => '2048x1152',
        'portrait' => '1024x1536',
    ];

    /**
     * The image engine the administrator chose.
     *
     * @return string One of the ENGINE_ constants.
     */
    public static function engine(): string {
        $engine = (string) get_config('format_dari', 'imageengine');
        return isset(self::ENGINES[$engine]) ? $engine : self::DEFAULT_ENGINE;
    }

    /**
     * The model's API identifier for the chosen engine.
     *
     * @return string
     */
    public static function model(): string {
        return self::ENGINES[self::engine()]['model'];
    }

    /**
     * The model's name, for people.
     *
     * @return string
     */
    public static function label(): string {
        return self::ENGINES[self::engine()]['label'];
    }

    /**
     * Values for language strings that name the model ({$a->label}, {$a->model}).
     *
     * @return \stdClass
     */
    public static function string_params(): \stdClass {
        return (object) ['label' => self::label(), 'model' => self::model()];
    }

    /**
     * Choices for the imageengine setting.
     *
     * @return string[]
     */
    public static function engine_options(): array {
        $options = [];
        foreach (self::ENGINES as $engine => $info) {
            $options[$engine] = $info['label'] . ' (' . $info['model'] . ')';
        }
        return $options;
    }

    /**
     * Whether a configured model, deployment or endpoint names the chosen engine's model.
     *
     * Matched as a substring, case-insensitively, so dated snapshots, router prefixes
     * (openai/gpt-image-2.5-sunburst, google/gemini-nano-banana-2.1), Azure deployments named after
     * the model and Gemini endpoints (…/models/gemini-nano-banana-2.1:generateContent) all count.
     *
     * @param string $configured Model, deployment or endpoint.
     * @return bool
     */
    public static function matches(string $configured): bool {
        return $configured !== '' && stripos($configured, self::model()) !== false;
    }

    /**
     * Whether core's AI manager is the real one (and not a test double or a site's own override).
     *
     * A replaced manager is trusted to route the action itself.
     *
     * @param object $manager The manager.
     * @return bool
     */
    protected static function is_core_manager(object $manager): bool {
        return get_class($manager) === \core_ai\manager::class;
    }

    /**
     * Every model and deployment value configured for a provider's Generate image action, joined.
     *
     * Moodle 5.0 and later keep them on the provider instance (actionconfig). Moodle 4.5 keeps them
     * in the provider plugin's config, as action_generate_image_model (OpenAI) or
     * action_generate_image_deployment (Azure AI).
     *
     * @param object $provider A \core_ai\provider.
     * @return string
     */
    public static function configured_model(object $provider): string {
        $values = [];
        if (property_exists($provider, 'actionconfig') && is_array($provider->actionconfig ?? null)) {
            $settings = $provider->actionconfig[generate_image::class]['settings'] ?? [];
            foreach ((array) $settings as $value) {
                if (is_scalar($value)) {
                    $values[] = (string) $value;
                }
            }
        } else {
            $component = strtok(get_class($provider), '\\');
            foreach ((array) get_config($component) as $name => $value) {
                if (str_starts_with((string) $name, 'action_generate_image') && is_scalar($value)) {
                    $values[] = (string) $value;
                }
            }
        }
        return trim(implode(' ', $values));
    }

    /**
     * The enabled provider configured with the engine's model for Generate image, or null.
     *
     * @return object|null A \core_ai\provider.
     */
    public static function provider(): ?object {
        $manager = ai::manager();
        $providers = $manager->get_providers_for_actions([generate_image::class], true);
        foreach ($providers[generate_image::class] ?? [] as $provider) {
            if (self::matches(self::configured_model($provider))) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Whether a provider with the engine's model is set up (Moodle 4.5 and later).
     *
     * @return bool
     */
    public static function provider_ready(): bool {
        $manager = ai::manager();
        if (!self::is_core_manager($manager)) {
            return true;
        }
        return self::provider() !== null;
    }

    /**
     * Run a Generate image action on the provider configured with the engine's model, and only on it.
     *
     * Core's own private steps are used (the provider's processor, then the usage record), so the
     * request is handled and logged exactly as core would, without falling through to another
     * provider when it fails.
     *
     * @param generate_image $action The configured action.
     * @return \core_ai\aiactions\responses\response_base
     * @throws \moodle_exception When no provider has the engine's model.
     */
    public static function process(generate_image $action): \core_ai\aiactions\responses\response_base {
        $manager = ai::manager();
        if (!self::is_core_manager($manager)) {
            return $manager->process_action($action);
        }
        $provider = self::provider();
        if ($provider === null) {
            throw new \moodle_exception('error_ai_notopimagemodel', 'format_dari', '', self::string_params());
        }
        // Core's own private steps, called on this one provider only.
        $result = (new \ReflectionMethod(\core_ai\manager::class, 'call_action_provider'))
            ->invoke($manager, $provider, $action);
        if (method_exists($manager, 'store_action_result')) {
            (new \ReflectionMethod(\core_ai\manager::class, 'store_action_result'))
                ->invoke($manager, $provider, $action, $result);
        }
        return $result;
    }
}
