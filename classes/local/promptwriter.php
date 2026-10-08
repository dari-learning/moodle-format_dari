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
 * Stage 2 of AI images: the final image prompt, built from the visual plan.
 *
 * The text model turns the card's plan into one clear prose paragraph (one request per image), in
 * the order image models follow best: subject, action, signature element, environment, composition,
 * lighting and style. If that request fails twice, the prompt is built directly from the plan's
 * fields instead, and the fallback and its reason are logged.
 *
 * The prompt for a card is stored with its plan, so the preview shows exactly what is painted and
 * a retry reuses it. If planning fails, the template prompt from cardprompt is used and the
 * fallback and its reason are recorded, never silently.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class promptwriter {
    /** @var int Fewest words a written prompt has. */
    protected const PROMPT_MIN_WORDS = 40;

    /** @var int Longest scene part kept. */
    protected const SCENE_MAX = 1600;

    /** @var int Longest prompt sent to the image model. */
    protected const PROMPT_MAX = 3600;

    /** @var string Opening words of the prompt-writing request. */
    public const SCENE_OPENING = 'You write image-generation prompts for an online course, working from a creative '
        . 'director\'s plan.';

    /** @var string[] The medium per course style, for the prompt writer. */
    protected const MEDIUM = [
        'photo' => 'photorealistic documentary and editorial photography',
        'illustration' => 'modern editorial illustration',
        'render3d' => 'polished 3D render',
        'flat' => 'flat vector illustration',
    ];

    /** @var array Where the last prompt came from: source (plan, written, template, fallback) and reason. */
    public static array $last = ['source' => '', 'reason' => ''];

    /**
     * Whether planning runs: whenever the site can generate text.
     *
     * @param \context $context The course context.
     * @return bool
     */
    public static function enabled(\context $context): bool {
        return ai::is_available(ai::FEATURE_TEXT, $context);
    }

    /**
     * Write the final image prompt for a composed recipe.
     *
     * @param array $composed Output of cardprompt::compose() or cardprompt::compose_banner().
     * @param \context $context The course context.
     * @param int $userid The teacher the request is made for.
     * @return string The prompt for the image model.
     * @throws planning_busy_exception When another worker is planning the course (retry later).
     */
    public static function write(array $composed, \context $context, int $userid): string {
        $template = (string) ($composed['prompt'] ?? '');
        $brief = (array) ($composed['brief'] ?? []);
        $tail = (string) ($composed['promptTail'] ?? '');
        if ($brief === [] || !self::enabled($context)) {
            self::$last = ['source' => 'template', 'reason' => $brief === [] ? 'no brief' : 'no text model'];
            return $template;
        }
        try {
            $course = get_course((int) $context->instanceid);
            $result = self::prompt_for($course, $brief, $context, $userid, true);
            return self::assemble($result['prompt'], $tail);
        } catch (planning_busy_exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::$last = ['source' => 'fallback', 'reason' => $e->getMessage()];
            imagelog::add('fallback', 'fail', 0, 'Planning or prompt writing failed; the template prompt was used: '
                . $e->getMessage());
            debugging('format_dari image planning failed; template prompt used: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $template;
        }
    }

    /**
     * Plan (if needed) and build the prompt for one image.
     *
     * @param \stdClass $course The course.
     * @param array $brief The image brief (mode and teacherDirection are read from it).
     * @param \context $context The course context.
     * @param int $userid The user.
     * @param bool $generating True when an image is about to be requested (counts an attempt).
     * @return array{prompt: string, entry: array, course: array, key: string}
     */
    public static function prompt_for(\stdClass $course, array $brief, \context $context, int $userid,
            bool $generating): array {
        $key = imageplanner::item_key($brief);
        $iscard = ($brief['imageKind'] ?? '') !== cardprompt::KIND_BANNER;
        // Cards and the course banner keep their own prompt; a section banner borrows its card's plan.
        $tracked = $iscard || $key === imageplanner::BANNER_KEY;
        $teacher = trim((string) ($brief['teacherDirection'] ?? ''));
        $mode = (string) ($brief['mode'] ?? imageplanner::MODE_AUTO);
        if (!$tracked && $mode === imageplanner::MODE_NEW) {
            $mode = imageplanner::MODE_AUTO;
        }

        $plan = imageplanner::plan_for($course, $brief, $context, $userid, $mode, $tracked ? $teacher : '',
            $tracked && !empty($brief['replaceTeacher']));
        if ($tracked) {
            // The stored description, when this request did not give one, is part of the prompt too.
            $brief['teacherDirection'] = $plan['teacher'];
        }
        $row = $plan['row'];
        $stored = trim((string) ($row->prompt ?? ''));
        if ($tracked && !$plan['planned'] && $stored !== '') {
            $prompt = $stored;
            self::$last = ['source' => 'plan', 'reason' => 'stored prompt reused'];
        } else {
            try {
                $prompt = imagelog::time('prompt_written', fn() => self::write_prompt($brief, $plan['entry'],
                    $plan['course'], $context, $userid));
                self::$last = ['source' => 'written', 'reason' => ''];
            } catch (planning_busy_exception $e) {
                throw $e;
            } catch (\Throwable $e) {
                // Still the card's own plan, just not rewritten as prose.
                $prompt = self::assembled_prompt($plan['entry'], $plan['course'], $brief);
                self::$last = ['source' => 'plan', 'reason' => 'prompt writing failed: ' . $e->getMessage()];
                imagelog::add('fallback', 'fail', 0, 'The prompt could not be written; it was built from the plan: '
                    . $e->getMessage());
            }
            if ($tracked) {
                imageplanner::set_prompt($row, $prompt);
            }
        }
        if ($generating && $tracked) {
            imageplanner::record_attempt((int) $course->id, $key);
        }
        return ['prompt' => $prompt, 'entry' => $plan['entry'], 'course' => $plan['course'], 'key' => $key];
    }

    /**
     * The prompt built from the plan's fields, in the order an image model reads best: subject and
     * action, signature element, environment, people, composition, lighting, colour. No request.
     *
     * @param array $entry The plan item.
     * @param array $coursedata The course-level plan.
     * @param array $brief The image brief.
     * @return string
     */
    public static function assembled_prompt(array $entry, array $coursedata, array $brief): string {
        $teacher = trim((string) ($brief['teacherDirection'] ?? ''));
        $parts = [
            self::sentence((string) ($entry['concept'] ?? '')),
            self::labelled('Key detail', (string) ($entry['signature_element'] ?? '')),
            self::labelled('Setting', (string) ($entry['environment'] ?? '')),
            self::people_line((string) ($entry['people'] ?? '')),
            self::labelled('Camera', (string) ($entry['perspective'] ?? '')),
            self::labelled('Lighting', (string) ($entry['lighting'] ?? '')),
            self::labelled('Colour', (string) ($coursedata['colour_treatment'] ?? '')),
            $teacher !== '' ? self::labelled('Also', $teacher) : '',
        ];
        return trim(implode(' ', array_filter($parts)));
    }

    /**
     * Ask the text model to write the prompt from the plan, twice at most.
     *
     * @param array $brief The image brief.
     * @param array $entry The plan item.
     * @param array $coursedata The course-level plan.
     * @param \context $context The course context.
     * @param int $userid The user.
     * @return string
     * @throws \moodle_exception When no usable prompt came back.
     */
    protected static function write_prompt(array $brief, array $entry, array $coursedata, \context $context,
            int $userid): string {
        $request = self::scene_request($brief, $entry, $coursedata);
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $prompt = self::parse_prompt(ai::generate_text($context, $userid, $request)['text']);
            if ($prompt !== '') {
                return $prompt;
            }
        }
        throw new \moodle_exception('error_ai_textfailed', 'format_dari');
    }

    /**
     * The request that turns one plan item into a prose prompt (written mode).
     *
     * @param array $brief The image brief.
     * @param array $entry The plan item.
     * @param array $coursedata The course-level plan.
     * @return string
     */
    public static function scene_request(array $brief, array $entry, array $coursedata): string {
        $isbanner = ($brief['imageKind'] ?? '') === cardprompt::KIND_BANNER;
        $medium = self::MEDIUM[(string) ($brief['style'] ?? 'photo')] ?? self::MEDIUM['photo'];
        $teacher = trim((string) ($brief['teacherDirection'] ?? ''));
        $lines = [
            self::SCENE_OPENING . ' The plan already decides what the image shows. Describe it so an image model renders '
                . 'it exactly.',
            '',
            'THE PLAN:',
            '- Concept: ' . ($entry['concept'] ?? ''),
            '- Signature element: ' . ($entry['signature_element'] ?? ''),
            '- Environment: ' . ($entry['environment'] ?? ''),
            '- People: ' . (($entry['people'] ?? '') !== '' ? $entry['people'] : 'none'),
            '- Camera: ' . ($entry['perspective'] ?? ''),
            '- Lighting: ' . ($entry['lighting'] ?? ''),
        ];
        if (($coursedata['colour_treatment'] ?? '') !== '') {
            $lines[] = '- Colour treatment: ' . $coursedata['colour_treatment'];
        }
        $lines[] = '- Medium: ' . $medium;
        $lines[] = '- Format: ' . ($isbanner ? 'a wide course banner' : 'a 16:9 course card, also seen as a small thumbnail');
        if ($teacher !== '') {
            $lines[] = '- The teacher asks for (must be honoured): ' . $teacher;
        }
        $lines[] = '';
        $lines[] = 'Write one clear paragraph, usually 60 to 130 words, in this order: the main subject; the action or '
            . 'visual concept; the signature element; the environment; the composition; the lighting and style. Use '
            . 'positive, concrete description of what is visible. Do not add props to fill space. Begin with the subject. '
            . 'Never name the course, a title or an exam code, and never put words in quotation marks.';
        $lines[] = '';
        $lines[] = 'Reply with the paragraph only.';
        return implode("\n", $lines);
    }

    /**
     * The test mode: plan every section card and the course banner and build their prompts,
     * without generating any image. Stored prompts are what each card's first image uses.
     *
     * @param \stdClass $course The course.
     * @param \context $context The course context.
     * @param int $userid The user.
     * @param bool $replan Plan the course again from scratch.
     * @param bool $activities Also plan every activity card.
     * @return array[] One row per image: title, key, entry, prompt (with the tail).
     */
    public static function preview(\stdClass $course, \context $context, int $userid, bool $replan = false,
            bool $activities = false): array {
        $modinfo = get_fast_modinfo($course);
        $targets = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if ((int) $section->section === 0 || !empty($section->component)) {
                continue;
            }
            $targets[] = [get_section_name($course, $section),
                cardprompt::compose($course, cardimage::TYPE_SECTION, $section, '')];
            if ($activities) {
                foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if ($cm->deletioninprogress || in_array($cm->modname, ['label', 'subsection'], true)) {
                        continue;
                    }
                    $targets[] = [$cm->get_formatted_name(), cardprompt::compose($course, cardimage::TYPE_CM, $cm, '')];
                }
            }
        }
        $targets[] = [get_string('imageplan_banner', 'format_dari'), cardprompt::compose_banner($course, null, '')];

        if ($replan) {
            imageplanner::course_plan($course, $targets[0][1]['brief'], $context, $userid, true);
        }
        $rows = [];
        foreach ($targets as [$title, $composed]) {
            $result = self::prompt_for($course, $composed['brief'], $context, $userid, false);
            $rows[] = [
                'title' => $title,
                'key' => $result['key'],
                'entry' => $result['entry'],
                'course' => $result['course'],
                'prompt' => self::assemble($result['prompt'], (string) $composed['promptTail']),
            ];
        }
        return $rows;
    }

    /**
     * The prompt paragraph out of a reply; '' when unusable.
     *
     * @param string $reply The model's reply.
     * @return string
     */
    public static function parse_prompt(string $reply): string {
        $reply = str_replace(['**', '__', '```'], '', $reply);
        if (preg_match('~PROMPT\s*:\s*(.+)$~is', $reply, $m)) {
            $reply = $m[1];
        }
        $prompt = self::clean($reply, self::SCENE_MAX);
        $prompt = (string) preg_replace('~^((image )?(generation )?prompt|paragraph|scene)\s*:\s*~i', '', $prompt);
        $prompt = (string) preg_replace('~^(Create|Generate)\s+an?\s+[^.]*?\b(image|photo|photograph)\s+(of|showing)\s+~i',
            '', $prompt);
        $prompt = trim($prompt);
        if ($prompt !== '' && preg_match('~^[a-z]~', $prompt)) {
            $prompt = \core_text::strtoupper(\core_text::substr($prompt, 0, 1)) . \core_text::substr($prompt, 1);
        }
        return str_word_count($prompt) >= self::PROMPT_MIN_WORDS ? $prompt : '';
    }

    /**
     * The final prompt: the scene, then the plugin's fixed tail, within the limit.
     *
     * @param string $scene The scene.
     * @param string $tail The fixed tail from cardprompt.
     * @return string
     */
    public static function assemble(string $scene, string $tail): string {
        $tail = trim($tail);
        $room = self::PROMPT_MAX - ($tail === '' ? 0 : \core_text::strlen($tail) + 2);
        $scene = \core_text::substr(trim($scene), 0, max(200, $room));
        return $tail === '' ? $scene : $scene . "\n\n" . $tail;
    }

    /**
     * Forget a course's visual plan, prompts and the art direction stored by earlier versions.
     *
     * @param int $courseid The course.
     * @return void
     */
    public static function forget(int $courseid): void {
        imageplanner::forget($courseid);
        imagelog::forget($courseid);
        unset_config('artdirection_' . $courseid, 'format_dari');
        unset_config('artscenes_' . $courseid, 'format_dari');
    }

    /**
     * Text as a sentence: capital first letter, full stop at the end.
     *
     * @param string $text Text.
     * @return string
     */
    protected static function sentence(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $text = \core_text::strtoupper(\core_text::substr($text, 0, 1)) . \core_text::substr($text, 1);
        return preg_match('~[.!?]$~u', $text) ? $text : $text . '.';
    }

    /**
     * "Label: text." or ''.
     *
     * @param string $label Label.
     * @param string $text Text.
     * @return string
     */
    protected static function labelled(string $label, string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        return $label . ': ' . rtrim($text, '.') . '.';
    }

    /**
     * The people line, or a clear "no people" when the plan has none.
     *
     * @param string $people Plan people field.
     * @return string
     */
    protected static function people_line(string $people): string {
        $people = trim($people);
        if ($people === '' || preg_match('~^(none|no people|nobody)\b~i', $people)) {
            return 'No people in the frame.';
        }
        return self::labelled('People', $people);
    }

    /**
     * Flatten model output to one clean line of plain text.
     *
     * @param string $text Model output.
     * @param int $max Longest result.
     * @return string
     */
    protected static function clean(string $text, int $max): string {
        $text = strip_tags($text);
        $text = (string) preg_replace('~(^|\n)\s*#{1,6}\s+~', '$1', $text);
        $text = trim((string) preg_replace('/\s+/', ' ', $text), " \t\n\r\0\x0B\"'“”‘’<>");
        return \core_text::substr($text, 0, $max);
    }
}
