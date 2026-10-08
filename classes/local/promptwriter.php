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
            $scene = self::scene($courseid, $brief, $art, $context, $userid);
            if ($scene === '') {
                return $template;
            }
            self::remember_scene($courseid, $scene);
            return self::assemble($scene, $art, $brief, (string) ($composed['promptTail'] ?? ''));
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

    /**
     * Write the scene for one image.
     *
     * @param int $courseid The course.
     * @param array $brief The image brief.
     * @param array $art The course's art direction.
     * @param \context $context The course context.
     * @param int $userid The teacher.
     * @return string One paragraph, or '' when the reply was unusable.
     */
    protected static function scene(int $courseid, array $brief, array $art, \context $context, int $userid): string {
        $isbanner = ($brief['imageKind'] ?? '') === cardprompt::KIND_BANNER;
        $target = (string) ($brief['target'] ?? 'section');
        $used = self::recent_scenes($courseid);

        $lines = [
            'You write prompts for an AI image generator. Write the SCENE for one image in a matching set of '
                . 'course images.',
            '',
            'THE COURSE\'S VISUAL WORLD (keep to it):',
        ];
        foreach ($art as $key => $value) {
            if ($value !== '') {
                $lines[] = '- ' . ucfirst($key) . ': ' . $value;
            }
        }
        $lines[] = '';
        $lines[] = 'THIS IMAGE:';
        $lines[] = '- It is ' . ($isbanner ? 'the wide banner for the whole course' : 'the image for one ' . $target) . '.';
        foreach ([
            'topic' => 'Topic',
            'title' => 'Title',
            'detail' => 'What it covers',
            'partName' => 'Part of',
            'activityType' => 'Activity type',
            'teacherDirection' => 'The teacher asks for',
            'sceneIdea' => 'A starting idea you may improve on',
        ] as $key => $label) {
            $value = trim((string) ($brief[$key] ?? ''));
            if ($value !== '') {
                $lines[] = '- ' . $label . ': ' . \core_text::substr($value, 0, 400);
            }
        }
        if ($used) {
            $lines[] = '';
            $lines[] = 'SCENES OTHER IMAGES IN THIS COURSE ALREADY SHOW (choose a clearly different moment, place or '
                . 'angle):';
            foreach ($used as $prior) {
                $lines[] = '- ' . $prior;
            }
        }
        $lines[] = '';
        $lines[] = 'WRITE ONE PARAGRAPH OF 60 TO 110 WORDS that describes a single, specific, believable moment:';
        $lines[] = '- who is in it (role, age, clothing, expression, what their hands are doing),';
        $lines[] = '- what they are doing, shown through action rather than a symbol for the idea,';
        $lines[] = '- where they are and the specific objects, tools or equipment around them,';
        $lines[] = '- foreground, middle ground and background, so the picture has depth.';
        $lines[] = 'Show the topic concretely: a real task, place or situation from the subject, never an abstract '
            . 'metaphor (no lightbulbs, puzzle pieces, handshakes, thumbs up, floating icons or people pointing at '
            . 'screens).';
        if (($brief['audience'] ?? '') === 'school students') {
            $lines[] = 'The learners are school students: keep everyone age-appropriate and the setting school-safe.';
        }
        if ($isbanner) {
            $lines[] = 'Banner: keep the main subject in the right half and the left third calm and simple, because '
                . 'the course title is printed over it.';
        }
        $lines[] = 'Do NOT mention art style, medium, camera, colours, text, words, signs, logos, brand names or the '
            . 'course title. Do not use quotation marks. Reply with the paragraph only.';

        $reply = ai::generate_text($context, $userid, implode("\n", $lines))['text'];
        $scene = self::clean($reply, 1200);
        // Drop a leading label some models add ("Scene:", "Prompt:").
        $scene = preg_replace('~^(scene|prompt|image|description)\s*:\s*~i', '', $scene);
        return \core_text::strlen($scene) >= 40 ? $scene : '';
    }

    /**
     * Put the final prompt together: subject first, then the course's look, then the fixed rules.
     *
     * @param string $scene The scene paragraph.
     * @param array $art The art direction.
     * @param array $brief The image brief.
     * @param string $tail cardprompt's fixed style, colour, composition and no-text rules.
     * @return string
     */
    public static function assemble(string $scene, array $art, array $brief, string $tail): string {
        $style = (string) ($brief['style'] ?? 'photo');
        $parts = [$scene];
        $look = [];
        if (($art['light'] ?? '') !== '') {
            $look[] = 'Lighting: ' . rtrim($art['light'], '.') . '.';
        }
        if (($art['palette'] ?? '') !== '') {
            $look[] = 'Palette: ' . rtrim($art['palette'], '.') . '.';
        }
        if (($art['mood'] ?? '') !== '') {
            $look[] = 'Mood: ' . rtrim($art['mood'], '.') . '.';
        }
        if ($look) {
            $parts[] = implode(' ', $look);
        }
        $parts[] = self::MEDIUM[$style] ?? self::MEDIUM['photo'];
        $parts[] = 'People look natural and unposed, with realistic hands and faces, and reflect a diverse mix of '
            . 'ages and backgrounds that fits the setting. Equipment, clothing and safety gear are accurate for the '
            . 'real-world task.';
        if ($tail !== '') {
            $parts[] = trim($tail);
        }
        if (($art['avoid'] ?? '') !== '') {
            $parts[] = 'Keep out: ' . rtrim($art['avoid'], '.') . '.';
        }
        $prompt = implode("\n\n", $parts);
        if (\core_text::strlen($prompt) > self::PROMPT_MAX) {
            $prompt = \core_text::substr($prompt, 0, self::PROMPT_MAX);
        }
        return $prompt;
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
