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

/**
 * Direct connection to an OpenAI-compatible AI service, for Moodle 4.4 only.
 *
 * Moodle 4.5 introduced the AI subsystem, and on 4.5 and later Dari always uses it. Moodle 4.4
 * has nothing equivalent, so there the administrator enters the school's own endpoint, key and
 * text model name in Dari's settings and requests go straight there. Images always use the one
 * model of the chosen image engine (\format_dari\local\imagemodel): Google's is called on the
 * Gemini API directly, OpenAI's on the endpoint above. For text, any service that speaks the
 * OpenAI API works: OpenAI itself, Azure OpenAI's v1 endpoint, Google Gemini's OpenAI-compatible
 * endpoint, OpenRouter, a LiteLLM gateway, or a self-hosted Ollama or vLLM server.
 *
 * Requests go through Moodle's own curl class, so the site's proxy and HTTP security settings
 * apply.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class direct {
    /** @var int Seconds to wait for a text answer. */
    protected const TEXT_TIMEOUT = 120;

    /** @var int Seconds to wait for an image. */
    protected const IMAGE_TIMEOUT = 240;

    /**
     * Whether the connection is set up for a feature.
     *
     * @param string $feature ai::FEATURE_TEXT or ai::FEATURE_IMAGE.
     * @return bool
     */
    public static function is_configured(string $feature): bool {
        if ($feature !== ai::FEATURE_IMAGE && self::endpoint() === '') {
            return false;
        }
        if (
            $feature === ai::FEATURE_IMAGE && imagemodel::engine() === imagemodel::ENGINE_OPENAI
                && self::endpoint() === ''
        ) {
            return false;
        }
        if ($feature === ai::FEATURE_IMAGE) {
            // The image model is fixed by the engine; the administrator turns images on or off.
            if ((string) get_config('format_dari', 'directimages') === '0') {
                return false;
            }
            // Google is called on its own API, which needs a key; OpenAI uses the endpoint above.
            return imagemodel::engine() === imagemodel::ENGINE_GOOGLE ? self::image_key() !== '' : true;
        }
        return trim((string) get_config('format_dari', 'directtextmodel')) !== '';
    }

    /**
     * The base URL, without a trailing slash, e.g. https://api.openai.com/v1.
     *
     * @return string
     */
    protected static function endpoint(): string {
        return rtrim(trim((string) get_config('format_dari', 'directendpoint')), '/');
    }

    /**
     * Send a JSON POST and return the decoded body.
     *
     * @param string $path Path below the endpoint, e.g. /chat/completions.
     * @param array $body Request body.
     * @param int $timeout Seconds.
     * @param string|null $key API key; null for the API key setting.
     * @return array Decoded response.
     * @throws \moodle_exception On any transport or HTTP failure.
     */
    protected static function post(string $path, array $body, int $timeout, ?string $key = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        \core_php_time_limit::raise($timeout + 60);

        $curl = \core\di::get(direct_client::class)->create();
        $curl->setopt(['CURLOPT_TIMEOUT' => $timeout, 'CURLOPT_CONNECTTIMEOUT' => 20]);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $key = $key ?? trim((string) get_config('format_dari', 'directapikey'));
        if ($key !== '') {
            $headers[] = 'Authorization: Bearer ' . $key;
            // Azure OpenAI accepts its key in this header instead.
            $headers[] = 'api-key: ' . $key;
        }
        $curl->setHeader($headers);
        $response = $curl->post(self::endpoint() . $path, $payload);
        $code = (int) ($curl->info['http_code'] ?? 0);

        if ($curl->get_errno() || $code < 200 || $code >= 300) {
            debugging(
                'format_dari direct AI HTTP ' . $code . ' ' . $curl->error . ' ' . substr((string) $response, 0, 500),
                DEBUG_DEVELOPER
            );
            if ($code === 429) {
                throw new \moodle_exception('error_apiratelimited', 'format_dari');
            }
            if ($code === 401 || $code === 403) {
                throw new \moodle_exception('error_apiunauthorized', 'format_dari');
            }
            $decoded = json_decode((string) $response, true);
            $message = is_array($decoded) ? (string) ($decoded['error']['message'] ?? $decoded['message'] ?? '') : '';
            if ($message === '') {
                $message = $curl->error ?: ('HTTP ' . $code);
            }
            $message = s(\core_text::substr(trim(strip_tags($message)), 0, 200));
            $fallback = $path === '/chat/completions' ? 'error_ai_textfailed_detail' : 'error_ai_imagefailed_detail';
            throw new \moodle_exception($fallback, 'format_dari', '', $message);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \moodle_exception(
                $path === '/chat/completions' ? 'error_ai_textfailed' : 'error_ai_imagefailed',
                'format_dari'
            );
        }
        return $decoded;
    }

    /**
     * Generate text.
     *
     * @param string $prompt The complete prompt.
     * @return array{text: string, finishreason: string, model: string} Same shape as ai::generate_text().
     */
    public static function generate_text(string $prompt): array {
        $model = trim((string) get_config('format_dari', 'directtextmodel'));
        $result = self::post('/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ], self::TEXT_TIMEOUT);

        $text = (string) ($result['choices'][0]['message']['content'] ?? '');
        $text = trim(preg_replace('~<(think|thinking|reasoning)>.*?</\1>~is', '', $text));
        if ($text === '') {
            throw new \moodle_exception('error_ai_textfailed', 'format_dari');
        }
        return [
            'text' => $text,
            'finishreason' => strtolower((string) ($result['choices'][0]['finish_reason'] ?? '')),
            'model' => (string) ($result['model'] ?? $model),
        ];
    }

    /**
     * The key for image requests: the image key if one is set, otherwise the API key above.
     *
     * @return string
     */
    protected static function image_key(): string {
        $key = trim((string) get_config('format_dari', 'directimagekey'));
        return $key !== '' ? $key : trim((string) get_config('format_dari', 'directapikey'));
    }

    /**
     * Generate one image with the chosen engine's model and return its bytes.
     *
     * @param string $prompt The image prompt.
     * @param string $aspectratio square, landscape or portrait.
     * @return string Raw image bytes.
     */
    public static function generate_image(string $prompt, string $aspectratio): string {
        return imagemodel::engine() === imagemodel::ENGINE_GOOGLE
            ? self::generate_image_google($prompt, $aspectratio)
            : self::generate_image_openai($prompt, $aspectratio);
    }

    /**
     * Google: Nano Banana 2.1 through the Gemini API's generateContent method, 16:9 at 2K.
     *
     * @param string $prompt The image prompt.
     * @param string $aspectratio square, landscape or portrait.
     * @return string Raw image bytes.
     */
    protected static function generate_image_google(string $prompt, string $aspectratio): string {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $body = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'responseModalities' => ['IMAGE'],
                'imageConfig' => [
                    'aspectRatio' => imagemodel::GOOGLE_RATIOS[$aspectratio] ?? imagemodel::GOOGLE_RATIOS['landscape'],
                    'imageSize' => imagemodel::GOOGLE_IMAGE_SIZE,
                ],
            ],
        ];
        $url = imagemodel::GOOGLE_API . rawurlencode(imagemodel::model()) . ':generateContent';
        \core_php_time_limit::raise(self::IMAGE_TIMEOUT + 60);

        $curl = \core\di::get(direct_client::class)->create();
        $curl->setopt(['CURLOPT_TIMEOUT' => self::IMAGE_TIMEOUT, 'CURLOPT_CONNECTTIMEOUT' => 20]);
        $curl->setHeader(['Content-Type: application/json', 'Accept: application/json',
            'x-goog-api-key: ' . self::image_key()]);
        $response = $curl->post($url, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE));
        $code = (int) ($curl->info['http_code'] ?? 0);
        $decoded = json_decode((string) $response, true);

        if ($curl->get_errno() || $code < 200 || $code >= 300) {
            debugging(
                'format_dari Google image HTTP ' . $code . ' ' . $curl->error . ' ' . substr((string) $response, 0, 500),
                DEBUG_DEVELOPER
            );
            if ($code === 429) {
                throw new \moodle_exception('error_apiratelimited', 'format_dari');
            }
            if ($code === 401 || $code === 403) {
                throw new \moodle_exception('error_apiunauthorized', 'format_dari');
            }
            $message = is_array($decoded) ? (string) ($decoded['error']['message'] ?? '') : '';
            $message = $message !== '' ? $message : ($curl->error ?: ('HTTP ' . $code));
            throw new \moodle_exception(
                'error_ai_imagefailed_detail',
                'format_dari',
                '',
                s(\core_text::substr(trim(strip_tags($message)), 0, 200))
            );
        }

        foreach ((array) ($decoded['candidates'][0]['content']['parts'] ?? []) as $part) {
            $data = $part['inlineData']['data'] ?? $part['inline_data']['data'] ?? '';
            if ($data !== '') {
                $bytes = base64_decode((string) $data, true);
                if ($bytes !== false && $bytes !== '') {
                    return $bytes;
                }
            }
        }
        // No image: usually a safety block, which Google explains in promptFeedback or finishReason.
        $reason = (string) ($decoded['promptFeedback']['blockReason'] ?? $decoded['candidates'][0]['finishReason'] ?? '');
        if ($reason !== '' && strtoupper($reason) !== 'STOP') {
            throw new \moodle_exception('error_ai_imagefailed_detail', 'format_dari', '', s($reason));
        }
        throw new \moodle_exception('error_bannernoimage', 'format_dari');
    }

    /**
     * OpenAI: GPT Image 2.5 Sunburst on the endpoint above, high quality, landscape at 16:9.
     *
     * GPT Image models return base64; a service that answers with a URL instead is followed once.
     *
     * @param string $prompt The image prompt.
     * @param string $aspectratio square, landscape or portrait.
     * @return string Raw image bytes.
     */
    protected static function generate_image_openai(string $prompt, string $aspectratio): string {
        global $CFG;

        $body = [
            'model' => imagemodel::model(),
            'prompt' => $prompt,
            'n' => 1,
            'size' => imagemodel::DIRECT_SIZES[$aspectratio] ?? imagemodel::DIRECT_SIZES['landscape'],
            'quality' => imagemodel::DIRECT_QUALITY,
        ];

        $result = self::post('/images/generations', $body, self::IMAGE_TIMEOUT, self::image_key());
        $item = $result['data'][0] ?? [];
        if (!empty($item['b64_json'])) {
            $bytes = base64_decode((string) $item['b64_json'], true);
            if ($bytes !== false && $bytes !== '') {
                return $bytes;
            }
        }
        if (!empty($item['url']) && preg_match('~^https?://~i', (string) $item['url'])) {
            require_once($CFG->libdir . '/filelib.php');
            $curl = new \curl();
            $curl->setopt(['CURLOPT_TIMEOUT' => 60]);
            $bytes = $curl->get((string) $item['url']);
            if (!$curl->get_errno() && (int) ($curl->info['http_code'] ?? 0) === 200 && $bytes !== '') {
                return $bytes;
            }
        }
        throw new \moodle_exception('error_bannernoimage', 'format_dari');
    }
}
