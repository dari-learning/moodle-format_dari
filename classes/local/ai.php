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
use core_ai\aiactions\generate_text;

/**
 * The single gateway between this format and Moodle's own AI subsystem (core_ai).
 *
 * Every AI feature in the format -- Ask Dari, course and section banners, and card images --
 * goes through here, and nothing here talks to any network service directly. The request is
 * handed to \core_ai\manager, which passes it to whichever AI provider the site administrator has
 * enabled under Site administration > General > AI (OpenAI, Azure AI, Ollama, Gemini, Anthropic,
 * AWS Bedrock, DeepSeek or any third party provider plugin). That means:
 *
 *  - the school uses its own API key and its own provider account, configured once for the site;
 *  - every request is logged in core's AI usage report and counted against core's rate limits;
 *  - core's AI policy acceptance applies, exactly as it does for the TinyMCE and course assistance
 *    placements.
 *
 * Moodle 4.4 has no AI subsystem. There, and only there, requests go to an OpenAI-compatible
 * endpoint the administrator enters in Dari's own settings (see \format_dari\local\direct), still
 * using the school's own key.
 *
 * The manager's API changed between Moodle 4.5 (static methods, no constructor) and 5.0 (instance
 * methods, a database dependency, provider instances). Obtaining the manager from the dependency
 * injection container and always calling it as an instance works on both: PHP allows a static
 * method to be called through an instance, but not the reverse.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai {
    /** @var string Feature key for Ask Dari (text generation). */
    public const FEATURE_TEXT = 'text';

    /** @var string Feature key for banners and card images (image generation). */
    public const FEATURE_IMAGE = 'image';

    /** @var string Marker the tutor is told to append when it declines to hand over an answer. */
    public const REFUSAL_MARKER = '[[DARI_REFUSED]]';

    /** @var string Marker the tutor is told to append to a reply to a wellbeing disclosure. */
    public const WELLBEING_MARKER = '[[DARI_WELLBEING]]';

    /**
     * Whether this Moodle has the AI subsystem at all (4.5 and later).
     *
     * @return bool
     */
    public static function subsystem_present(): bool {
        return class_exists(\core_ai\manager::class);
    }

    /**
     * The core AI manager.
     *
     * @return \core_ai\manager
     */
    public static function manager(): \core_ai\manager {
        return \core\di::get(\core_ai\manager::class);
    }

    /**
     * The core action class for a feature.
     *
     * @param string $feature One of the FEATURE_ constants.
     * @return string Fully qualified action class name.
     */
    protected static function action_class(string $feature): string {
        return $feature === self::FEATURE_IMAGE ? generate_image::class : generate_text::class;
    }

    /**
     * Whether a feature can be used right now in the given context.
     *
     * True only when the subsystem exists, at least one enabled provider has the action enabled,
     * and (on versions that support it) AI tools have not been switched off for the course or
     * activity.
     *
     * @param string $feature One of the FEATURE_ constants.
     * @param \context|null $context The course or module context, or null to skip the context check.
     * @return bool
     */
    public static function is_available(string $feature, ?\context $context = null): bool {
        return self::unavailable_reason($feature, $context) === null;
    }

    /**
     * Why a feature cannot be used, as a language string key, or null when it can.
     *
     * @param string $feature One of the FEATURE_ constants.
     * @param \context|null $context The course or module context, or null to skip the context check.
     * @return string|null
     */
    public static function unavailable_reason(string $feature, ?\context $context = null): ?string {
        // One course page asks once per card; the answer cannot change within a request.
        $key = $feature . ':' . ($context ? $context->id : 0);
        if (!array_key_exists($key, self::$memo)) {
            self::$memo[$key] = self::compute_unavailable_reason($feature, $context);
        }
        return self::$memo[$key];
    }

    /** @var array The last image prompt written this request: prompt, source (artdirector|template|fallback), reason. */
    public static array $lastprompt = ['prompt' => '', 'source' => '', 'reason' => ''];

    /** @var array Per-request memo of unavailable_reason(). */
    protected static array $memo = [];

    /**
     * Forget memoised availability; for unit tests that change AI configuration mid-test.
     *
     * @return void
     */
    public static function reset_memo(): void {
        self::$memo = [];
    }

    /**
     * Uncached body of unavailable_reason().
     *
     * @param string $feature One of the FEATURE_ constants.
     * @param \context|null $context The course or module context.
     * @return string|null
     */
    protected static function compute_unavailable_reason(string $feature, ?\context $context): ?string {
        if (!self::subsystem_present()) {
            // Moodle 4.4: no AI subsystem, so Dari's own connection settings are used instead.
            return direct::is_configured($feature) ? null : 'error_ai_nodirect';
        }
        $actionclass = self::action_class($feature);
        try {
            $manager = self::manager();
            if (!$manager->is_action_available($actionclass)) {
                return $feature === self::FEATURE_IMAGE ? 'error_ai_noimageprovider' : 'error_ai_notextprovider';
            }
            // Images are only ever painted with one model; a provider with any other is not enough.
            if ($feature === self::FEATURE_IMAGE && !imagemodel::provider_ready()) {
                return 'error_ai_notopimagemodel';
            }
            if ($context !== null) {
                // Moodle 5.x lets a course, and an activity, switch AI tools off.
                if (
                    method_exists($manager, 'is_ai_tools_enabled_in_course')
                        && in_array($context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)
                        && !$manager::is_ai_tools_enabled_in_course($context)
                ) {
                    return 'error_ai_disabledincourse';
                }
                if (
                    $context->contextlevel === CONTEXT_MODULE
                        && method_exists($manager, 'is_action_enabled_in_context')
                        && !$manager->is_action_enabled_in_context($context, $actionclass)
                ) {
                    return 'error_ai_disabledincourse';
                }
            }
        } catch (\Throwable $e) {
            debugging('format_dari ai availability check failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return 'error_ai_nosubsystem';
        }
        return null;
    }

    /**
     * Throw a clear, translated exception when a feature cannot be used.
     *
     * @param string $feature One of the FEATURE_ constants.
     * @param \context|null $context The course or module context.
     * @return void
     * @throws \moodle_exception
     */
    public static function require_available(string $feature, ?\context $context = null): void {
        $reason = self::unavailable_reason($feature, $context);
        if ($reason !== null) {
            throw new \moodle_exception($reason, 'format_dari', '', imagemodel::string_params());
        }
    }

    /**
     * Whether a user has accepted the site's AI usage policy.
     *
     * @param int $userid The user.
     * @return bool
     */
    public static function policy_accepted(int $userid): bool {
        if (!self::subsystem_present()) {
            // Moodle 4.4 has no AI usage policy to accept.
            return true;
        }
        try {
            return (bool) \core_ai\manager::get_user_policy_status($userid);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Throw when the user has not yet accepted the AI usage policy.
     *
     * The browser shows core's own policy dialogue before any request is made; this is the server
     * side of the same rule, so it cannot be bypassed by calling the web service directly.
     *
     * @param int $userid The user.
     * @return void
     * @throws \moodle_exception
     */
    public static function require_policy(int $userid): void {
        if (!self::policy_accepted($userid)) {
            throw new \moodle_exception('error_ai_policynotaccepted', 'format_dari');
        }
    }

    /**
     * Generate text through the site's AI provider.
     *
     * @param \context $context The context the request is made in (logged by core).
     * @param int $userid The user the request is made for.
     * @param string $prompt The complete prompt.
     * @return array{text: string, finishreason: string, model: string}
     * @throws \moodle_exception When no provider could answer.
     */
    public static function generate_text(\context $context, int $userid, string $prompt): array {
        self::require_available(self::FEATURE_TEXT, $context);
        if (!self::subsystem_present()) {
            return direct::generate_text($prompt);
        }

        \core_php_time_limit::raise(300);
        $action = new generate_text(
            contextid: $context->id,
            userid: $userid,
            prompttext: $prompt,
        );
        $start = microtime(true);
        $response = self::manager()->process_action($action);
        $ms = (int) round((microtime(true) - $start) * 1000);
        if (imagelog::$requestid !== null) {
            imagelog::add(
                'text_request',
                $response->get_success() ? 'ok' : 'fail',
                $ms,
                \core_text::strlen($prompt) . ' chars sent'
            );
        }

        if (!$response->get_success()) {
            self::throw_failure($response, 'error_ai_textfailed');
        }

        $data = $response->get_response_data();
        $text = (string) ($data['generatedcontent'] ?? '');
        // Reasoning models served through Ollama and similar may return their thinking inline.
        // Moodle 5.1+ strips this in core; strip it here too so 4.5 and 5.0 behave the same.
        $text = trim(preg_replace('~<(think|thinking|reasoning)>.*?</\1>~is', '', $text));

        return [
            'text' => $text,
            'finishreason' => strtolower((string) ($data['finishreason'] ?? '')),
            'model' => (string) ($data['model'] ?? ''),
        ];
    }

    /**
     * Generate one image through the site's AI provider and return its bytes.
     *
     * Core writes the image into the user's draft area. The bytes are read back and the draft
     * file is deleted at once, so nothing is left behind in the user's draft files.
     *
     * @param \context $context The context the request is made in (logged by core).
     * @param int $userid The user the request is made for.
     * @param string $prompt The image prompt.
     * @param string $aspectratio square, landscape or portrait.
     * @return string The raw image bytes.
     * @throws \moodle_exception When no provider could produce an image.
     */
    public static function generate_image(
        \context $context,
        int $userid,
        string $prompt,
        string $aspectratio = 'landscape'
    ): string {
        self::require_available(self::FEATURE_IMAGE, $context);
        if (!self::subsystem_present()) {
            return self::optimise_image(imagelog::time('image_request', fn() =>
                direct::generate_image(\core_text::substr($prompt, 0, 3900), $aspectratio), imagemodel::model()));
        }

        // Always the highest quality core can ask for ('hd' is sent to GPT Image models as 'high');
        // style is a DALL·E-only option that GPT Image models do not receive.
        \core_php_time_limit::raise(300);
        $action = new generate_image(
            contextid: $context->id,
            userid: $userid,
            prompttext: \core_text::substr($prompt, 0, 3900),
            quality: 'hd',
            aspectratio: in_array($aspectratio, ['square', 'landscape', 'portrait'], true) ? $aspectratio : 'landscape',
            numimages: 1,
            style: 'natural',
        );
        // Run on the provider configured with Dari's image model only, never on another provider.
        $start = microtime(true);
        $response = imagemodel::process($action);
        $ms = (int) round((microtime(true) - $start) * 1000);
        if (!$response->get_success()) {
            imagelog::add('image_request', 'fail', $ms, 'HTTP ' . $response->get_errorcode() . ' '
                . (method_exists($response, 'get_errormessage') ? (string) $response->get_errormessage() : ''));
            self::throw_failure($response, 'error_ai_imagefailed');
        }
        imagelog::add('image_request', 'ok', $ms, imagemodel::model());

        $data = $response->get_response_data();
        $file = $data['draftfile'] ?? null;
        if (!$file instanceof \stored_file) {
            throw new \moodle_exception('error_bannernoimage', 'format_dari');
        }
        $bytes = $file->get_content();
        try {
            $file->delete();
        } catch (\Throwable $e) {
            // A draft file left behind is cleaned up by core's draft file cleanup task.
            debugging('format_dari could not remove AI draft image: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        if ($bytes === '' || $bytes === false) {
            throw new \moodle_exception('error_bannernoimage', 'format_dari');
        }
        return self::optimise_image($bytes);
    }

    /**
     * Turn a composed image recipe into the prompt sent to the image provider.
     *
     * \format_dari\local\promptwriter (Dari's art director) writes it with the site's text model:
     * a per-course visual guide, then a specific scene for this image, then the fixed style,
     * colour, composition and no-text rules. Only a site with no text model at all uses the
     * template prompt from \format_dari\local\cardprompt.
     *
     * @param array $composed Output of cardprompt::compose() or cardprompt::compose_banner().
     * @param \context $context The context the request is made in.
     * @param int $userid The user the request is made for.
     * @return string
     */
    public static function image_prompt(array $composed, \context $context, int $userid): string {
        // The art director writes the prompt with the site's text model; the template prompt from
        // cardprompt is used only when the site has no text model.
        $prompt = promptwriter::write($composed, $context, $userid);
        // Source: plan or written (the AI art director), template (no text model) or fallback (planning
        // failed; the reason is kept so it is visible, not silent).
        self::$lastprompt = [
            'prompt' => $prompt,
            'source' => promptwriter::$last['source'] === 'plan' || promptwriter::$last['source'] === 'written'
                ? 'artdirector' : (string) promptwriter::$last['source'],
            'reason' => (string) promptwriter::$last['reason'],
        ];
        return $prompt;
    }

    /**
     * Resize and recompress a generated image so banners and cards stay light to load.
     *
     * Providers return anything from a 300 KB JPEG to a 4 MB PNG. Anything wider than 1920 px is
     * scaled down and the result is saved as a JPEG at quality 86, which is visually lossless for
     * photographic and illustrated scenes. Without GD, or if anything goes wrong, the original
     * bytes are returned untouched.
     *
     * @param string $bytes Raw image bytes.
     * @return string
     */
    public static function optimise_image(string $bytes): string {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return $bytes;
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return $bytes;
        }
        [$width, $height] = $info;
        if ($width <= 1920 && strlen($bytes) < 700 * 1024) {
            return $bytes;
        }
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return $bytes;
        }
        $newwidth = min(1920, $width);
        $newheight = (int) round($height * ($newwidth / $width));
        $target = imagecreatetruecolor($newwidth, $newheight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefilledrectangle($target, 0, 0, $newwidth, $newheight, $white);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        ob_start();
        $ok = imagejpeg($target, null, 86);
        $out = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);
        return ($ok && $out !== '' && strlen($out) < strlen($bytes)) ? $out : $bytes;
    }

    /**
     * Turn a failed core_ai response into an exception with a reason a teacher can act on.
     *
     * @param \core_ai\aiactions\responses\response_base $response The failed response.
     * @param string $fallback String key used when the provider gave no reason.
     * @return void
     * @throws \moodle_exception Always.
     */
    protected static function throw_failure(\core_ai\aiactions\responses\response_base $response, string $fallback): void {
        $code = (int) $response->get_errorcode();
        $message = '';
        if (method_exists($response, 'get_errormessage')) {
            $message = (string) $response->get_errormessage();
        }
        if ($message === '' && method_exists($response, 'get_error')) {
            $message = (string) $response->get_error();
        }
        debugging('format_dari core_ai failure ' . $code . ': ' . $message, DEBUG_DEVELOPER);

        if ($code === 429) {
            throw new \moodle_exception('error_apiratelimited', 'format_dari');
        }
        if ($code === 401 || $code === 403) {
            throw new \moodle_exception('error_apiunauthorized', 'format_dari');
        }
        $message = trim(preg_replace('/\s+/', ' ', strip_tags($message)));
        if ($message !== '') {
            throw new \moodle_exception($fallback . '_detail', 'format_dari', '', s(\core_text::substr($message, 0, 200)));
        }
        throw new \moodle_exception($fallback, 'format_dari');
    }

    /**
     * Data the browser needs to decide whether to show core's AI policy dialogue first.
     *
     * @param string $feature One of the FEATURE_ constants.
     * @param \context $context The course or module context.
     * @param int $userid The user.
     * @return array{available: bool, policyaccepted: bool}
     */
    public static function client_state(string $feature, \context $context, int $userid): array {
        return [
            'available' => self::is_available($feature, $context),
            'policyaccepted' => self::policy_accepted($userid),
        ];
    }

    /**
     * What an editing teacher's browser needs to know about AI images on this page.
     *
     * The "Generate with AI" buttons are always shown to editors, so the feature can be found.
     * When it cannot run yet, the button explains why instead of generating, and an administrator
     * also gets a link straight to the place it is set up.
     *
     * @param \context $context The course context.
     * @return array{ready: bool, reason: string}
     */
    public static function image_hint(\context $context): array {
        $reason = self::unavailable_reason(self::FEATURE_IMAGE, $context);
        if ($reason === null) {
            return ['ready' => true, 'reason' => ''];
        }
        $text = get_string($reason, 'format_dari', imagemodel::string_params());
        if (has_capability('moodle/site:config', \context_system::instance())) {
            $url = self::subsystem_present()
                ? new \moodle_url('/admin/settings.php', ['section' => 'aiprovider'])
                : new \moodle_url('/admin/settings.php', ['section' => 'formatsettingdari']);
            $text .= ' ' . \html_writer::link($url, get_string('ai_opensettings', 'format_dari'));
        }
        return ['ready' => false, 'reason' => $text];
    }
}
