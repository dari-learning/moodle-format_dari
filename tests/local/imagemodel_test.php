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

#[\PHPUnit\Framework\Attributes\CoversClass(\format_dari\local\imagemodel::class)]
/**
 * Tests for the image engine and its single model.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_dari\local\imagemodel
 */
final class imagemodel_test extends \advanced_testcase {
    /**
     * Google is the default engine; an unknown value falls back to it.
     */
    public function test_engine_defaults_to_google(): void {
        $this->resetAfterTest();
        unset_config('imageengine', 'format_dari');
        $this->assertSame(imagemodel::ENGINE_GOOGLE, imagemodel::engine());
        $this->assertSame('gemini-nano-banana-2.1', imagemodel::model());
        $this->assertStringContainsString('Nano Banana 2.1', imagemodel::label());

        set_config('imageengine', 'nonsense', 'format_dari');
        $this->assertSame(imagemodel::ENGINE_GOOGLE, imagemodel::engine());

        set_config('imageengine', 'openai', 'format_dari');
        $this->assertSame('gpt-image-2.5-sunburst', imagemodel::model());
        $this->assertSame('gpt-image-2.5-sunburst', imagemodel::string_params()->model);
    }

    /**
     * Google: the model name, a router prefix or a Gemini endpoint naming it match; other models do not.
     */
    public function test_matches_google(): void {
        $this->resetAfterTest();
        set_config('imageengine', 'google', 'format_dari');
        $this->assertTrue(imagemodel::matches('gemini-nano-banana-2.1'));
        $this->assertTrue(imagemodel::matches('google/gemini-nano-banana-2.1'));
        $this->assertTrue(imagemodel::matches(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-nano-banana-2.1:generateContent'
        ));
        $this->assertFalse(imagemodel::matches('gemini-3.1-flash-image'));
        $this->assertFalse(imagemodel::matches('imagen-4.0-generate-001'));
        $this->assertFalse(imagemodel::matches('gpt-image-2.5-sunburst'));
    }

    /**
     * Snapshots, router prefixes and deployments named after the model match; other models do not.
     */
    public function test_matches(): void {
        $this->resetAfterTest();
        set_config('imageengine', 'openai', 'format_dari');
        $this->assertTrue(imagemodel::matches('gpt-image-2.5-sunburst'));
        $this->assertTrue(imagemodel::matches('gpt-image-2.5-sunburst-2026-09-08'));
        $this->assertTrue(imagemodel::matches('openai/GPT-Image-2.5-Sunburst'));
        $this->assertFalse(imagemodel::matches('gpt-image-2.5-flare'));
        $this->assertFalse(imagemodel::matches('gpt-image-2'));
        $this->assertFalse(imagemodel::matches('dall-e-3'));
        $this->assertFalse(imagemodel::matches(''));
    }

    /**
     * Moodle 5.0+: the model is read from the provider instance's Generate image settings.
     */
    public function test_configured_model_from_instance_settings(): void {
        $this->resetAfterTest();
        set_config('imageengine', 'openai', 'format_dari');
        $provider = new class {
            /** @var array Action settings, as a 5.0+ provider instance holds them. */
            public array $actionconfig = [
                generate_image::class => ['settings' => ['model' => 'gpt-image-2.5-sunburst', 'endpoint' => 'https://x']],
                \core_ai\aiactions\generate_text::class => ['settings' => ['model' => 'some-text-model']],
            ];
        };
        $configured = imagemodel::configured_model($provider);
        $this->assertTrue(imagemodel::matches($configured));
        $this->assertStringNotContainsString('some-text-model', $configured);
    }

    /**
     * The direct (Moodle 4.4) route asks for 16:9 landscape images from either engine.
     */
    public function test_direct_constants(): void {
        $this->assertSame('16:9', imagemodel::GOOGLE_RATIOS['landscape']);
        $this->assertSame('2K', imagemodel::GOOGLE_IMAGE_SIZE);
        $this->assertSame('high', imagemodel::DIRECT_QUALITY);
        [$w, $h] = array_map('intval', explode('x', imagemodel::DIRECT_SIZES['landscape']));
        $this->assertEqualsWithDelta(16 / 9, $w / $h, 0.01);
    }
}
