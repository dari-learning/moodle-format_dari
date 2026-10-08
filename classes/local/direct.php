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
 * model names in Dari's settings and requests go straight there. Any service that speaks the
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
        if (self::endpoint() === '') {
            return false;
        }
        $model = $feature === ai::FEATURE_IMAGE ? 'directimagemodel' : 'directtextmodel';
        return trim((string) get_config('format_dari', $model)) !== '';
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
     * @return array Decoded response.
     * @throws \moodle_exception On any transport or HTTP failure.
     */
    protected static function post(string $path, array $body, int $timeout): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        \core_php_time_limit::raise($timeout + 60);

        $curl = new \curl();
        $curl->setopt(['CURLOPT_TIMEOUT' => $timeout, 'CURLOPT_CONNECTTIMEOUT' => 20]);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $key = trim((string) get_config('format_dari', 'directapikey'));
        if ($key !== '') {
            $headers[] = 'Authorization: Bearer ' . $key;
            // Azure OpenAI accepts its key in this header instead.
            $headers[] = 'api-key: ' . $key;
        }
        $curl->setHeader($headers);
        $response = $curl->post(self::endpoint() . $path, $payload);
        $code = (int) ($curl->info['http_code'] ?? 0);

        if ($curl->get_errno() || $code < 200 || $code >= 300) {
            debugging('format_dari direct AI HTTP ' . $code . ' ' . $curl->error . ' ' . substr((string) $response, 0, 500),
                DEBUG_DEVELOPER);
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
            throw new \moodle_exception($path === '/chat/completions' ? 'error_ai_textfailed' : 'error_ai_imagefailed',
                'format_dari');
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
     * Generate one image and return its bytes.
     *
     * DALL·E models are asked for base64 directly. gpt-image models always return base64 and reject
     * the response_format field. Services that answer with a URL instead are followed once.
     *
     * @param string $prompt The image prompt.
     * @param string $aspectratio square, landscape or portrait.
     * @return string Raw image bytes.
     */
    public static function generate_image(string $prompt, string $aspectratio): string {
        global $CFG;

        $model = trim((string) get_config('format_dari', 'directimagemodel'));
        $isdalle = stripos($model, 'dall-e') === 0;
        $sizes = $isdalle
            ? ['square' => '1024x1024', 'landscape' => '1792x1024', 'portrait' => '1024x1792']
            : ['square' => '1024x1024', 'landscape' => '1536x1024', 'portrait' => '1024x1536'];
        $body = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $sizes[$aspectratio] ?? $sizes['landscape'],
        ];
        $quality = get_config('format_dari', 'imagequality') === 'hd';
        if ($isdalle) {
            $body['response_format'] = 'b64_json';
            $body['quality'] = $quality ? 'hd' : 'standard';
            $body['style'] = get_config('format_dari', 'imagestyle') === 'vivid' ? 'vivid' : 'natural';
        } else if (stripos($model, 'gpt-image') === 0) {
            $body['quality'] = $quality ? 'high' : 'medium';
        }

        $result = self::post('/images/generations', $body, self::IMAGE_TIMEOUT);
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
