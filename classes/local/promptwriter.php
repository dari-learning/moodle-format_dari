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
 * Dari's art director: writes the image prompt for every banner and card with the site's own AI.
 *
 * Image models draw what they are told, literally. A template ("an illustration representing
 * Hazard identification") gives them nothing to draw, so they produce one object on an empty
 * background. A good prompt names who is in the picture, where they are, what they are doing,
 * what surrounds them, the light, the camera or medium, and where the empty space goes.
 *
 * This class gets the site's text model to write that prompt, in two steps:
 *
 *  1. Art direction, once per course. A short visual guide -- the world the course lives in,
 *     the people, the places, the props, palette, light and mood -- so every banner and card in
 *     a course looks like one commissioned set rather than a pile of unrelated stock images.
 *     It is cached per course and rewritten only when the course name, summary, image style or
 *     accent colour changes.
 *  2. The scene, once per image. A specific, believable moment for that section or activity,
 *     written with the art direction in hand and told which scenes the course already has, so
 *     the cards do not all show the same thing.
 *
 * The plugin's own fixed rules (style, colour, composition, no text) are appended unchanged, so
 * the model's writing can make a picture better but never break the set's consistency or put
 * words on an image. Any failure at any step falls back to the template prompt from
 * \format_dari\local\cardprompt, so generation never fails because the text model did.
 *
 * Works with any provider: hosted (OpenAI, Gemini, Claude, Azure) and small local models alike.
 * Every instruction asks for plain text or a tiny JSON object and every reply is validated.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class promptwriter {
    /** @var int Bumped whenever the art-direction instruction changes, so caches refresh. */
    public const ART_VERSION = 2;

    /** @var int Most recent scenes remembered per course, to keep the set varied. */
    protected const SCENE_MEMORY = 14;

    /** @var int Longest prompt sent to an image model (DALL·E 3 allows 4,000 characters). */
    protected const PROMPT_MAX = 3600;

    /** @var string[] Camera or medium language per course image style. */
    protected const MEDIUM = [
        'photo' => 'Shot like an editorial photograph on a full-frame camera with a 35mm lens at eye level, '
            . 'natural window or site light, true-to-life skin tones, crisp focus on the main subject and a softly '
            . 'detailed background.',
        'illustration' => 'Drawn as a modern editorial illustration: confident shapes, soft gradients, subtle paper '
            . 'texture, consistent line weight and a clear focal point.',
        'render3d' => 'Rendered as a polished 3D scene: soft studio lighting, smooth matte materials, appealing '
            . 'stylised people with natural proportions, gentle shadows and depth of field.',
        'flat' => 'Drawn as a flat vector illustration: bold clean geometric shapes, a harmonious limited palette, '
            . 'crisp edges and a clear visual hierarchy, with a complete scene rather than a lone icon.',
    ];

    /**
     * Whether the art director should run: the setting is on and the site can generate text.
     *
     * @param \context $context The course context.
     * @return bool
     */
    public static function enabled(\context $context): bool {
        $setting = get_config('format_dari', 'aiscenewriter');
        // On unless an administrator has switched it off.
        $on = ($setting === false || $setting === '' || !empty($setting));
        return $on && ai::is_available(ai::FEATURE_TEXT, $context);
    }

    /**
     * Write the final image prompt for a composed recipe.
     *
     * @param array $composed Output of cardprompt::compose() or cardprompt::compose_banner().
     * @param \context $context The course context.
     * @param int $userid The teacher the request is made for.
     * @return string The prompt for the image model.
     */
    public static function write(array $composed, \context $context, int $userid): string {
        $template = (string) ($composed['prompt'] ?? '');
        $brief = (array) ($composed['brief'] ?? []);
        if ($brief === [] || !self::enabled($context)) {
            return $template;
        }

        try {
            $courseid = (int) $context->instanceid;
            $art = self::art_direction($courseid, $brief, $context, $userid);
            $prompt = self::full_prompt($courseid, $brief, $art, $context, $userid);
            if ($prompt === '') {
                return $template;
            }
            self::remember_scene($courseid, self::show_sentence($prompt));
            return $prompt;
        } catch (\Throwable $e) {
            debugging('format_dari art director failed, using the template prompt: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $template;
        }
    }

    /**
     * The course's art direction, from cache or freshly written.
     *
     * @param int $courseid The course.
     * @param array $brief The image brief (course facts are read from it).
     * @param \context $context The course context.
     * @param int $userid The teacher.
     * @return array{world: string, people: string, places: string, props: string, palette: string, light: string, mood: string, avoid: string}
     */
    public static function art_direction(int $courseid, array $brief, \context $context, int $userid): array {
        $facts = [
            'course' => (string) ($brief['courseName'] ?? ''),
            'topic' => (string) ($brief['courseTopic'] ?? ''),
            'category' => (string) ($brief['courseCategory'] ?? ''),
            'summary' => (string) ($brief['courseSummary'] ?? ''),
            'audience' => (string) ($brief['audience'] ?? ''),
            'style' => (string) ($brief['style'] ?? ''),
            'colour' => trim(($brief['colourName'] ?? '') . ' ' . ($brief['colourHex'] ?? '')),
        ];
        $hash = sha1(self::ART_VERSION . '|' . json_encode($facts));
        $key = 'artdirection_' . $courseid;
        $cached = json_decode((string) get_config('format_dari', $key), true);
        if (is_array($cached) && ($cached['hash'] ?? '') === $hash && is_array($cached['art'] ?? null)) {
            return $cached['art'];
        }

        $ask = implode("\n", [
            'You are the art director for an online course. Plan the visual world for a matching set of course '
                . 'images (a wide banner plus one image per section and activity).',
            '',
            'COURSE FACTS:',
            '- Course: ' . $facts['course'],
            '- Subject: ' . $facts['topic'],
            $facts['category'] !== '' ? '- Category: ' . $facts['category'] : '',
            $facts['summary'] !== '' ? '- Summary: ' . \core_text::substr($facts['summary'], 0, 500) : '',
            '- Learners: ' . ($facts['audience'] ?: 'adult learners'),
            '- Image style: ' . ($facts['style'] ?: 'photo'),
            $facts['colour'] !== '' ? '- Brand accent colour: ' . $facts['colour'] : '',
            '',
            'Think about the REAL workplaces, places, people, tools and equipment of this subject, as someone who '
                . 'works in it would know them. Be specific and accurate (correct safety gear, real equipment, real '
                . 'settings), and avoid generic office or stock-photo clichés unless the subject really is office work.',
            '',
            'Reply with ONLY a JSON object, no other text, with these keys, each a short phrase or sentence:',
            '{"world": "the overall setting the images live in",',
            ' "people": "who appears: roles, ages, clothing, diversity, how they behave",',
            ' "places": "three to five specific locations to rotate through",',
            ' "props": "the specific tools, equipment and objects that belong in this world",',
            ' "palette": "a colour palette that suits the subject and works with the accent colour",',
            ' "light": "lighting and time of day",',
            ' "mood": "the feeling of the set",',
            ' "avoid": "clichés or inaccuracies to keep out of this subject\'s images"}',
        ]);
        $reply = ai::generate_text($context, $userid, $ask)['text'];
        $art = self::parse_art($reply);

        // Only a real JSON answer is cached. A non-JSON reply (a refusal, an apology, a model
        // that ignored the format) is still used for this image, as the world description, but
        // caching it would pin that reply as the course's art direction for every later image
        // until the course itself changed.
        if (preg_match('~\{.*\}~s', $reply, $m) && is_array(json_decode($m[0], true))) {
            set_config($key, json_encode(['hash' => $hash, 'art' => $art, 'time' => time()]), 'format_dari');
        }
        return $art;
    }

    /**
     * Pull the art-direction fields out of a model reply, tolerating chatter and code fences.
     *
     * @param string $reply The model's reply.
     * @return array
     */
    public static function parse_art(string $reply): array {
        $keys = ['world', 'people', 'places', 'props', 'palette', 'light', 'mood', 'avoid'];
        $art = array_fill_keys($keys, '');
        $data = null;
        if (preg_match('~\{.*\}~s', $reply, $m)) {
            $data = json_decode($m[0], true);
        }
        if (is_array($data)) {
            foreach ($keys as $key) {
                $value = $data[$key] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', array_map('strval', $value));
                }
                $art[$key] = self::clean((string) $value, 240);
            }
        }
        if (implode('', $art) === '') {
            // Not JSON: keep the reply as a single description of the world.
            $art['world'] = self::clean($reply, 400);
        }
        return $art;
    }

    /** @var string The house style the text model is shown: one paragraph, in this order. */
    public const HOUSE_EXAMPLE = 'Create a premium, professional eLearning course image for BAR – Business '
        . 'Analysis & Reporting, part of a US CPA exam preparation course. Show a modern financial analyst or CPA '
        . 'professional working at a desk with multiple screens displaying financial statements, business performance '
        . 'dashboards, charts, forecasts, variance analysis and data visualisations. Include subtle visual references to '
        . 'advanced financial reporting, business analysis, budgeting and strategic decision-making. Modern corporate '
        . 'office environment, sophisticated accounting and finance atmosphere, realistic photography, clean '
        . 'composition, polished professional lighting, navy blue, white and subtle gold accents. Leave some uncluttered '
        . 'space for optional course-title overlay. No logos, no readable text, no watermarks. Suitable for a '
        . 'professional CPA online learning platform. Wide landscape composition, 16:9 aspect ratio, high detail, '
        . 'photorealistic.';

    /** @var string[] Medium words per course image style, for the model and the finishing check. */
    protected const STYLE_WORDS = [
        'photo' => ['realistic photography', 'photorealistic'],
        'illustration' => ['modern editorial illustration', 'crisp illustration'],
        'render3d' => ['polished 3D render', 'high-quality 3D render'],
        'flat' => ['flat vector illustration', 'crisp vector art'],
    ];

    /**
     * Have the text model write the complete image prompt in the house style.
     *
     * @param int $courseid The course.
     * @param array $brief The image brief.
     * @param array $art The course's art direction.
     * @param \context $context The course context.
     * @param int $userid The teacher.
     * @return string The finished prompt, or '' when the reply was unusable.
     */
    protected static function full_prompt(int $courseid, array $brief, array $art, \context $context, int $userid): string {
        $isbanner = ($brief['imageKind'] ?? '') === cardprompt::KIND_BANNER;
        $target = (string) ($brief['target'] ?? 'section');
        $style = (string) ($brief['style'] ?? 'photo');
        [$medium, $finish] = self::STYLE_WORDS[$style] ?? self::STYLE_WORDS['photo'];
        $used = self::recent_scenes($courseid);

        $lines = [
            'You are an expert art director writing an image-generation prompt for one image in an online course. '
                . 'Write it in exactly the style and order of this example, but entirely about THIS course and THIS '
                . ($isbanner ? 'banner' : $target) . ' (do not reuse the example\'s subject):',
            '',
            'EXAMPLE: ' . self::HOUSE_EXAMPLE,
            '',
            'THE ORDER TO FOLLOW, as one paragraph of 120 to 170 words:',
            '1. "Create a premium, professional eLearning course image for <title>, part of <course and what it prepares '
                . 'learners for>."',
            '2. "Show <a specific person by role, e.g. a site supervisor, a registered nurse, a CPA> <doing a specific '
                . 'real task> <with the specific equipment, documents or screens of that task>."',
            '3. "Include subtle visual references to <four to six concrete things this ' . ($isbanner ? 'course' : $target)
                . ' covers, taken from the facts below>."',
            '4. "<Environment>, <atmosphere>, ' . $medium . ', clean composition, polished professional lighting, '
                . '<a named three-colour palette>."',
            '5. ' . ($isbanner
                ? '"Keep the main subject in the right half and leave the left third uncluttered for the course title overlay."'
                : '"Leave some uncluttered space for optional course-title overlay."'),
            '6. "No logos, no readable text, no watermarks. Suitable for a professional <field> online learning platform. '
                . 'Wide landscape composition, 16:9 aspect ratio, high detail, ' . $finish . '."',
            '',
            'FACTS:',
            '- Course: ' . (string) ($brief['courseName'] ?? ''),
        ];
        foreach ([
            'courseCategory' => 'Course category',
            'courseSummary' => 'Course summary',
            'audience' => 'Learners',
            'title' => $isbanner ? 'Banner for' : 'This ' . $target . "'s title",
            'partName' => 'Part of',
            'detail' => 'What it covers',
            'activityType' => 'Activity type',
            'teacherDirection' => 'The teacher asks for (must be honoured)',
        ] as $key => $label) {
            $value = trim((string) ($brief[$key] ?? ''));
            if ($value !== '') {
                $lines[] = '- ' . $label . ': ' . \core_text::substr($value, 0, 500);
            }
        }
        $contents = array_filter((array) ($brief['contents'] ?? []));
        if ($contents) {
            $lines[] = '- ' . ($isbanner && ($brief['target'] ?? '') === 'course' ? 'Its sections' : 'Activities in it')
                . ': ' . implode('; ', array_slice($contents, 0, 8));
        }
        $lines[] = '';
        $lines[] = 'THE COURSE\'S VISUAL WORLD (keep every image in the course consistent with it):';
        foreach ($art as $key => $value) {
            if ($value !== '') {
                $lines[] = '- ' . ucfirst($key) . ': ' . $value;
            }
        }
        if (($brief['colourName'] ?? '') !== '') {
            $lines[] = '- Brand accent: ' . $brief['colourName'] . ' (use it as one of the palette colours)';
        }
        if ($used) {
            $lines[] = '';
            $lines[] = 'OTHER IMAGES IN THIS COURSE ALREADY SHOW (pick a clearly different person, task, place or angle):';
            foreach ($used as $prior) {
                $lines[] = '- ' . $prior;
            }
        }
        $lines[] = '';
        $lines[] = 'RULES: Be specific and accurate to the real work of this subject (correct equipment, clothing and '
            . 'safety gear). Screens and documents may show charts, forms and layouts, but nothing readable. No '
            . 'abstract metaphors (lightbulbs, puzzle pieces, handshakes, thumbs up, floating icons). People look natural '
            . 'and diverse.' . (($brief['audience'] ?? '') === 'school students'
                ? ' The learners are school students: keep people age-appropriate and settings school-safe.' : '')
            . ' Never put the course title or any words in quotation marks. Reply with the prompt paragraph only.';

        $reply = ai::generate_text($context, $userid, implode("\n", $lines))['text'];
        return self::finalise($reply, $isbanner, $finish);
    }

    /**
     * Check and complete a model-written prompt; '' when it is unusable.
     *
     * @param string $reply The model's reply.
     * @param bool $isbanner Whether this is a banner.
     * @param string $finish The closing finish word for the style.
     * @return string
     */
    public static function finalise(string $reply, bool $isbanner, string $finish): string {
        $prompt = self::clean($reply, self::PROMPT_MAX);
        $prompt = preg_replace('~^(image )?(generation )?prompt\s*:\s*~i', '', $prompt);
        if (str_word_count($prompt) < 40) {
            return '';
        }
        if (stripos($prompt, 'no readable text') === false) {
            $prompt .= ' No logos, no readable text, no watermarks.';
        }
        if ($isbanner && stripos($prompt, 'left third') === false) {
            $prompt .= ' Keep the main subject in the right half and the left third uncluttered for the course title overlay.';
        }
        if (stripos($prompt, 'aspect ratio') === false) {
            $prompt .= ' Wide landscape composition, 16:9 aspect ratio, high detail, ' . $finish . '.';
        }
        return \core_text::substr($prompt, 0, self::PROMPT_MAX);
    }

    /**
     * The "Show ..." sentence of a prompt, remembered so later images choose something different.
     *
     * @param string $prompt The prompt.
     * @return string
     */
    protected static function show_sentence(string $prompt): string {
        if (preg_match('~\bShow\s[^.]+\.~', $prompt, $m)) {
            return $m[0];
        }
        return preg_split('~(?<=[.!?])\s~', $prompt, 2)[0] ?? $prompt;
    }

    /**
     * Short summaries of the scenes this course's images already show, oldest first.
     *
     * @param int $courseid The course.
     * @return string[]
     */
    public static function recent_scenes(int $courseid): array {
        $list = json_decode((string) get_config('format_dari', 'artscenes_' . $courseid), true);
        return is_array($list) ? array_values(array_filter(array_map('strval', $list))) : [];
    }

    /**
     * Remember a scene's opening words so later images in the course choose something different.
     *
     * @param int $courseid The course.
     * @param string $scene The scene paragraph.
     * @return void
     */
    protected static function remember_scene(int $courseid, string $scene): void {
        $first = preg_split('~(?<=[.!?])\s~', $scene, 2)[0] ?? $scene;
        $list = self::recent_scenes($courseid);
        $list[] = \core_text::substr($first, 0, 160);
        $list = array_slice($list, -self::SCENE_MEMORY);
        set_config('artscenes_' . $courseid, json_encode($list), 'format_dari');
    }

    /**
     * Forget a course's art direction and scene memory (course reset or deletion).
     *
     * @param int $courseid The course.
     * @return void
     */
    public static function forget(int $courseid): void {
        unset_config('artdirection_' . $courseid, 'format_dari');
        unset_config('artscenes_' . $courseid, 'format_dari');
    }

    /**
     * Flatten model output to one clean line of plain text.
     *
     * @param string $text Model output.
     * @param int $max Longest result.
     * @return string
     */
    protected static function clean(string $text, int $max): string {
        $text = preg_replace('~```[a-z]*~i', '', $text);
        $text = strip_tags($text);
        $text = str_replace(['**', '__'], '', $text);
        // Markdown heading marks only; a hex colour such as #0F766E keeps its '#'.
        $text = preg_replace('~(^|\n)\s*#{1,6}\s+~', '$1', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text), " \t\n\r\0\x0B\"'“”‘’");
        return \core_text::substr($text, 0, $max);
    }
}
