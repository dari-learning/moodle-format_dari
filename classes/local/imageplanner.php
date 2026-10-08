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
 * Stage 1 of AI images: the visual plan. Decides WHAT each image shows, and why.
 *
 * A section card shows what learners study. An activity card shows what the learner is asked to
 * do (\format_dari\local\activitypurpose): exam instructions show preparing for the exam, a practice
 * examination shows the exam setting, a content page shows its own topic.
 *
 *  - Course plan: one request per course plans every section card and the course banner as
 *    structured JSON (concept, signature element, environment, perspective, people, lighting, and
 *    short categories used to compare images). Dari checks the set in code for repetition and asks
 *    once for repeated items to be revised. The new plan replaces the old one only after it has
 *    been produced and validated, inside a transaction, under a course lock: a second worker never
 *    plans the same course at the same time (it gets planning_busy_exception and retries later).
 *  - Item plan: one request for an activity card, a section added after the plan, a teacher's own
 *    description, or a "new concept" regeneration. It is checked against the course's other images
 *    and re-planned once if it repeats one (unless the teacher asked for it).
 *
 * Attempts, successes and failures are counted separately, so a failed image never turns the next
 * attempt into a new concept: "retry" reuses the stored prompt, "new" plans a different concept.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imageplanner {
    /** @var int Bumped when the planning instruction or plan shape changes, so stored plans are rebuilt. */
    public const VERSION = 2;

    /** @var string Table holding plans and prompts. */
    public const TABLE = 'format_dari_imageplan';

    /** @var string Item key of the course-level row. */
    public const COURSE_KEY = 'course';

    /** @var string Item key of the course banner. */
    public const BANNER_KEY = 'banner';

    /** @var int Most sections planned in the course request; later ones are planned one by one. */
    public const MAX_PLAN_SECTIONS = 30;

    /** @var int Seconds to wait for another worker's course plan before giving up for now. */
    public const LOCK_WAIT = 10;

    /** @var string Generation modes. */
    public const MODE_AUTO = 'auto';
    /** @var string Use the stored prompt again (after a failure, or to get another take). */
    public const MODE_RETRY = 'retry';
    /** @var string Plan a different concept for this card. */
    public const MODE_NEW = 'new';

    /** @var string Opening words of the course planning request. */
    public const COURSE_OPENING = 'You are the creative director for the images of an online course. Plan every card.';

    /** @var string Opening words of a single-item planning request. */
    public const ITEM_OPENING = 'You are the creative director for the images of an online course. Plan ONE image.';

    /** @var string Opening words of the revision request. */
    public const REVISE_OPENING = 'You are the creative director for the images of an online course. Revise part of a plan.';

    /** @var string[] Description fields of a plan item, written as concrete visual description. */
    public const TEXT_FIELDS = ['interpretation', 'concept', 'signature_element', 'environment', 'perspective', 'people',
        'lighting', 'subject', 'action', 'object'];

    /** @var array Category fields and their allowed values, used to compare images. */
    public const CATEGORIES = [
        'env_category' => ['office', 'meeting room', 'home', 'classroom', 'exam room', 'industrial', 'outdoor',
            'laboratory or clinical', 'retail or hospitality', 'technical', 'studio or workshop', 'public or civic', 'other'],
        'composition' => ['overhead', 'macro', 'close-up', 'medium', 'wide', 'portrait', 'over the shoulder'],
        'people_arrangement' => ['none', 'hands only', 'one person', 'two people', 'small group'],
        'light_category' => ['daylight', 'warm interior', 'cool artificial', 'dusk or night', 'low key'],
    ];

    /**
     * The plan item for one image, planning the course or the item first when needed.
     *
     * @param \stdClass $course The course.
     * @param array $brief The image brief from cardprompt (targetKey and course facts).
     * @param \context $context The course context.
     * @param int $userid The user the requests are made for.
     * @param string $mode MODE_AUTO, MODE_RETRY or MODE_NEW.
     * The teacher's own description is kept with the item: a request without one (Generate all, a
     * plain retry) keeps the stored description, so it flows through every regeneration. Only a
     * request that sets $replace (the card dialog, which shows the stored description for editing)
     * changes or clears it.
     *
     * Retry reuses the stored plan and prompt whenever they exist, even if the item's facts changed;
     * a failed image never changes the concept.
     *
     * @param string $teacher The teacher's own description, or ''.
     * @param bool $replace True when $teacher replaces the stored description even if empty.
     * @return array{course: array, entry: array, row: \stdClass, planned: bool, teacher: string}
     */
    public static function plan_for(
        \stdClass $course,
        array $brief,
        \context $context,
        int $userid,
        string $mode = self::MODE_AUTO,
        string $teacher = '',
        bool $replace = false
    ): array {
        $coursedata = self::course_plan($course, $brief, $context, $userid);
        $key = self::item_key($brief);
        $facts = self::item_facts($course, $key);
        $hash = self::facts_hash($facts);

        $row = self::get_row((int) $course->id, $key);
        $entry = $row ? (array) json_decode((string) $row->plan, true) : [];
        $stored = (string) ($entry['teacher'] ?? '');
        $teacher = ($replace || $teacher !== '') ? $teacher : $stored;
        $teacherhash = $teacher === '' ? '' : sha1($teacher);
        $usable = $row && $entry && self::valid_entry($entry);
        $stale = !$usable || $row->factshash !== $hash || (string) ($row->teacherhash ?? '') !== $teacherhash;
        if ($mode === self::MODE_RETRY && $usable && $teacherhash === (string) ($row->teacherhash ?? '')) {
            $stale = false;
        }
        if ($stale || $mode === self::MODE_NEW) {
            $rejected = ($mode === self::MODE_NEW && $entry) ? (string) ($entry['concept'] ?? '') : '';
            $entry = imagelog::time('item_plan', fn() => self::plan_item(
                $course,
                $brief,
                $key,
                $facts,
                $context,
                $userid,
                $rejected,
                $teacher
            ), $key);
            if ($teacher !== '') {
                $entry['teacher'] = $teacher;
            }
            $row = self::save_row((int) $course->id, $key, $hash, $teacherhash, $entry, null, $row);
            return ['course' => $coursedata, 'entry' => $entry, 'row' => $row, 'planned' => true, 'teacher' => $teacher];
        }
        return ['course' => $coursedata, 'entry' => $entry, 'row' => $row, 'planned' => false, 'teacher' => $teacher];
    }

    /**
     * The teacher's own description stored with an item, or ''.
     *
     * @param int $courseid The course.
     * @param string $key The item key.
     * @return string
     */
    public static function teacher_text(int $courseid, string $key): string {
        $row = self::get_row($courseid, $key);
        $entry = $row ? (array) json_decode((string) $row->plan, true) : [];
        return (string) ($entry['teacher'] ?? '');
    }

    /**
     * The image key for a brief: section:{id}, cm:{id} or banner.
     *
     * @param array $brief The image brief.
     * @return string
     */
    public static function item_key(array $brief): string {
        $key = (string) ($brief['targetKey'] ?? '');
        return $key !== '' ? $key : self::BANNER_KEY;
    }

    /**
     * The course-level plan, planning every section and the banner if it is missing or stale.
     *
     * @param \stdClass $course The course.
     * @param array $brief Any image brief for the course (course facts are read from it).
     * @param \context $context The course context.
     * @param int $userid The user.
     * @param bool $force Plan again even when a current plan exists.
     * @return array The course-level data: interpretation, colour_treatment.
     * @throws planning_busy_exception When another worker is planning this course.
     */
    public static function course_plan(
        \stdClass $course,
        array $brief,
        \context $context,
        int $userid,
        bool $force = false
    ): array {
        $facts = self::course_facts($course, $brief);
        $hash = sha1(self::VERSION . '|' . json_encode($facts));
        $row = self::get_row((int) $course->id, self::COURSE_KEY);
        if ($row && !$force && $row->factshash === $hash) {
            return (array) json_decode((string) $row->plan, true);
        }

        // Only one worker plans a course. Without the lock nothing is deleted or written; the
        // caller retries once the other worker's plan is stored.
        $factory = \core\lock\lock_config::get_lock_factory('format_dari_imageplan');
        $lock = $factory->get_lock('course' . (int) $course->id, self::LOCK_WAIT);
        if (!$lock) {
            imagelog::add(
                'course_plan',
                'info',
                0,
                'Another worker is planning this course; will retry.',
                (int) $course->id
            );
            throw new planning_busy_exception();
        }
        try {
            $row = self::get_row((int) $course->id, self::COURSE_KEY);
            if ($row && !$force && $row->factshash === $hash) {
                return (array) json_decode((string) $row->plan, true);
            }
            return imagelog::time('course_plan', fn() => self::plan_course($course, $facts, $hash, $context, $userid));
        } finally {
            $lock->release();
        }
    }

    /**
     * Plan every section and the banner in one request, check the set, and replace the stored plan.
     *
     * @param \stdClass $course The course.
     * @param array $facts Course facts.
     * @param string $hash Course facts hash.
     * @param \context $context The course context.
     * @param int $userid The user.
     * @return array The course-level data.
     * @throws \moodle_exception When the reply is not a usable plan (nothing stored is touched).
     */
    protected static function plan_course(
        \stdClass $course,
        array $facts,
        string $hash,
        \context $context,
        int $userid
    ): array {
        global $DB;
        $sections = self::sections($course);
        $planned = array_slice($sections, 0, self::MAX_PLAN_SECTIONS, true);
        $reply = ai::generate_text($context, $userid, self::course_request($facts, $planned))['text'];
        $data = self::parse_json($reply);

        $items = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            $key = (string) ($item['key'] ?? '');
            $entry = self::normalise_entry((array) $item);
            if (isset($planned[$key]) && self::valid_entry($entry)) {
                $items[$key] = $entry;
            }
        }
        if (is_array($data['banner'] ?? null)) {
            $banner = self::normalise_entry((array) $data['banner']);
            if (self::valid_entry($banner)) {
                $items[self::BANNER_KEY] = $banner;
            }
        }
        // Validate before anything stored is touched.
        if ($data === null || ($planned && count(array_intersect_key($items, $planned)) < ceil(count($planned) / 2))) {
            throw new \moodle_exception('error_imageplan_invalid', 'format_dari');
        }
        $coursedata = [
            'interpretation' => self::clean((string) ($data['course']['interpretation'] ?? ''), 400),
            'colour_treatment' => self::clean((string) ($data['course']['colour_treatment'] ?? ''), 300),
        ];

        // The course-wide repetition check, in code; one revision request when needed.
        $issues = self::diversity_issues($items);
        if ($issues) {
            imagelog::add('diversity', 'info', 0, json_encode($issues), (int) $course->id);
            try {
                $revised = imagelog::time('plan_revision', fn() =>
                    self::revise($facts, $items, $issues, $planned, $context, $userid));
                foreach ($revised as $key => $entry) {
                    $items[$key] = $entry;
                }
            } catch (\Throwable $e) {
                debugging('format_dari image plan revision failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // Replace the stored plan in one step.
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(self::TABLE, ['courseid' => (int) $course->id]);
        self::save_row((int) $course->id, self::COURSE_KEY, $hash, '', $coursedata, null, null);
        foreach ($items as $key => $entry) {
            $itemfacts = $key === self::BANNER_KEY ? self::item_facts($course, self::BANNER_KEY) : $planned[$key];
            self::save_row((int) $course->id, $key, self::facts_hash($itemfacts), '', $entry, null, null);
        }
        $transaction->allow_commit();
        return $coursedata;
    }

    /**
     * Plan one image against the course's existing plan, re-planning once if it repeats another image.
     *
     * @param \stdClass $course The course.
     * @param array $brief The image brief.
     * @param string $key The item key.
     * @param array $facts The item's facts.
     * @param \context $context The course context.
     * @param int $userid The user.
     * @param string $rejected A concept the teacher asked to replace, or ''.
     * @param string $teacher The teacher's own description, or ''.
     * @return array The plan item.
     */
    public static function plan_item(
        \stdClass $course,
        array $brief,
        string $key,
        array $facts,
        \context $context,
        int $userid,
        string $rejected = '',
        string $teacher = ''
    ): array {
        $coursefacts = self::course_facts($course, $brief);
        $courserow = self::get_row((int) $course->id, self::COURSE_KEY);
        $coursedata = $courserow ? (array) json_decode((string) $courserow->plan, true) : [];
        $others = [];
        foreach (self::get_rows((int) $course->id) as $row) {
            if ($row->itemkey !== self::COURSE_KEY && $row->itemkey !== $key) {
                $others[$row->itemkey] = (array) json_decode((string) $row->plan, true);
            }
        }

        $conflict = '';
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $reply = ai::generate_text($context, $userid, self::item_request(
                $coursefacts,
                $coursedata,
                $key,
                $facts,
                $others,
                $rejected,
                $teacher,
                $conflict
            ))['text'];
            $entry = self::normalise_entry((array) (self::parse_json($reply) ?? []));
            if (!self::valid_entry($entry)) {
                throw new \moodle_exception('error_imageplan_invalid', 'format_dari');
            }
            // A New concept request must not come back with the concept the teacher rejected.
            $repeat = $rejected !== '' && self::overlap((string) $entry['concept'], $rejected) >= 0.6;
            // A teacher's own description is kept even if it repeats another image.
            $similar = $teacher === '' ? self::similar_to($entry, $others) : [];
            if ((!$similar && !$repeat) || $attempt === 2) {
                if ($repeat) {
                    imagelog::add('diversity', 'fail', 0, $key . ' repeated the rejected concept twice', (int) $course->id);
                }
                return $entry;
            }
            $conflict = $repeat
                ? 'Your first plan repeated the rejected concept. Choose a clearly different concept.'
                : 'Your first plan was too similar to: ' . implode(' | ', array_map(
                    fn($k) => self::summary_line($others[$k]),
                    $similar
                )) . '. Choose a clearly different concept.';
            imagelog::add('diversity', 'info', 0, $key . ($repeat ? ' repeated the rejected concept'
                : ' similar to ' . implode(', ', $similar)), (int) $course->id);
        }
        return $entry;
    }

    /**
     * The facts about the course that shape every image. The card colour is left out: it differs
     * from card to card, and the accent is added to every prompt's tail instead.
     *
     * @param \stdClass $course The course.
     * @param array $brief An image brief for the course.
     * @return array
     */
    public static function course_facts(\stdClass $course, array $brief): array {
        $context = \context_course::instance($course->id);
        return [
            'course' => (string) ($brief['courseName'] ?? text::plain((string) $course->fullname, $context)),
            'category' => (string) ($brief['courseCategory'] ?? ''),
            'summary' => \core_text::substr((string) ($brief['courseSummary']
                ?? cardprompt::html_plain((string) ($course->summary ?? ''))), 0, 800),
            'audience' => (string) ($brief['audience'] ?? 'adult learners'),
            'style' => (string) ($brief['style'] ?? 'photo'),
        ];
    }

    /**
     * Every section that has a card, as key => facts, in course order.
     *
     * @param \stdClass $course The course.
     * @return array
     */
    public static function sections(\stdClass $course): array {
        $out = [];
        foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
            if ((int) $section->section === 0 || !empty($section->component)) {
                continue;
            }
            $key = 'section:' . $section->id;
            $out[$key] = self::item_facts($course, $key);
        }
        return $out;
    }

    /**
     * The facts for one image, read from the course so a stored plan can be checked against them.
     *
     * @param \stdClass $course The course.
     * @param string $key section:{id}, cm:{id} or banner.
     * @return array
     */
    public static function item_facts(\stdClass $course, string $key): array {
        $context = \context_course::instance($course->id);
        [$type, $id] = array_pad(explode(':', $key, 2), 2, 0);
        $id = (int) $id;
        $modinfo = get_fast_modinfo($course);
        if ($type === 'section') {
            foreach ($modinfo->get_section_info_all() as $section) {
                if ((int) $section->id !== $id) {
                    continue;
                }
                $title = trim((string) $section->name) !== '' ? text::plain((string) $section->name, $context) : '';
                return [
                    'kind' => 'section',
                    'number' => (int) $section->section,
                    'title' => $title,
                    'untitled' => $title === '' || cardprompt::is_numbered_only($title),
                    'summary' => cardprompt::excerpt(cardprompt::html_plain((string) $section->summary)),
                    'activities' => cardprompt::section_contents($course, (int) $section->section),
                ];
            }
        } else if ($type === 'cm' && isset($modinfo->cms[$id])) {
            $cm = $modinfo->get_cm($id);
            $section = $cm->get_section_info();
            $title = text::plain((string) $cm->name, $context);
            $summary = cardprompt::excerpt(cardprompt::activity_intro($cm));
            $content = $summary === '' ? activitypurpose::content_text($cm) : '';
            $purpose = activitypurpose::classify(
                (string) $cm->modname,
                $title,
                $summary !== '' ? $summary : $content,
                activitypurpose::quiz_graded($cm)
            );
            return [
                'kind' => 'activity',
                'type' => cardprompt::activity_label((string) $cm->modname),
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'purpose' => $purpose['purpose'],
                'purposebasis' => $purpose['basis'],
                'section' => $section && trim((string) $section->name) !== ''
                    ? text::plain((string) $section->name, $context) : '',
                'sectionkey' => $section ? 'section:' . $section->id : '',
            ];
        }
        return ['kind' => 'banner', 'title' => text::plain((string) $course->fullname, $context)];
    }

    /**
     * A hash of an item's facts, to notice when a section or activity has changed since it was planned.
     *
     * @param array $facts Item facts.
     * @return string
     */
    public static function facts_hash(array $facts): string {
        return sha1(self::VERSION . '|' . json_encode($facts));
    }

    /**
     * The course facts as instruction lines.
     *
     * @param array $facts Course facts.
     * @return string[]
     */
    protected static function course_lines(array $facts): array {
        return array_values(array_filter([
            '- Course: ' . $facts['course'],
            $facts['category'] !== '' ? '- Category: ' . $facts['category'] : null,
            $facts['summary'] !== '' ? '- Course description: ' . $facts['summary'] : null,
            '- Learners: ' . $facts['audience'],
            '- Image medium for the whole course: ' . self::medium_name($facts['style']),
        ]));
    }

    /**
     * The medium, in words.
     *
     * @param string $style A value of cardimage::STYLES.
     * @return string
     */
    protected static function medium_name(string $style): string {
        return [
            'illustration' => 'editorial illustration',
            'render3d' => '3D render',
            'flat' => 'flat vector illustration',
        ][$style] ?? 'photography';
    }

    /**
     * One item's facts as a line for an instruction.
     *
     * @param string $key Item key.
     * @param array $facts Item facts.
     * @return string
     */
    public static function item_line(string $key, array $facts): string {
        $kind = $facts['kind'] ?? '';
        if ($kind === 'banner') {
            return '- [' . $key . '] The course banner: a wide image for the whole course; the left third stays calm '
                . 'for the title overlay, so the subject sits in the right half.';
        }
        $parts = [];
        if ($kind === 'activity') {
            $parts[] = 'ACTIVITY card: ' . ($facts['type'] ?? 'Activity') . ' "' . ($facts['title'] ?? '') . '"'
                . (($facts['section'] ?? '') !== '' ? ' in section "' . $facts['section'] . '"' : '');
            $purpose = (string) ($facts['purpose'] ?? activitypurpose::OTHER);
            $parts[] = 'purpose: ' . $purpose . ' (judged from its ' . ($facts['purposebasis'] ?? 'type') . ') - the image '
                . 'should show ' . (activitypurpose::GUIDANCE[$purpose] ?? activitypurpose::GUIDANCE[activitypurpose::OTHER]);
        } else {
            $parts[] = !empty($facts['untitled'])
                ? 'SECTION card: section ' . ($facts['number'] ?? '') . ' (no descriptive title: use its summary and activities)'
                : 'SECTION card: "' . ($facts['title'] ?? '') . '"';
        }
        if (($facts['summary'] ?? '') !== '') {
            $parts[] = 'description: ' . $facts['summary'];
        } else if (($facts['content'] ?? '') !== '') {
            $parts[] = 'content: ' . $facts['content'];
        }
        if (!empty($facts['activities'])) {
            $parts[] = 'activities: ' . implode('; ', array_slice((array) $facts['activities'], 0, 8));
        }
        return '- [' . $key . '] ' . implode(' | ', $parts);
    }

    /**
     * How to think: shared by every planning request.
     *
     * @param bool $school Whether the learners are school students.
     * @return string[]
     */
    protected static function method_lines(bool $school): array {
        return array_values(array_filter([
            'You understand instructional design, the subject and professional photography. Each image is a card in a '
                . 'course: it should be educationally meaningful, visually distinctive and professionally made.',
            '',
            'HOW TO PLAN:',
            '1. A SECTION card shows what learners study in that section. An ACTIVITY card shows what the learner is asked '
                . 'to do in that activity, using its purpose, title and content. An activity card must not repeat its '
                . 'section\'s image or simply restate the section subject. Do not make every page someone reading or every '
                . 'quiz an exam room: use the title and content.',
            '2. Interpret the topic from the description, content, activities and other section titles. With only a '
                . 'title, use established knowledge of the subject; do not invent specific contexts the title does not '
                . 'support.',
            '3. Consider several concepts (a real activity, distinctive equipment or object, a workplace situation, a '
                . 'technical close-up, a process, a decision moment, a symbolic arrangement of real things, an environment '
                . 'without people) and choose the one that best represents the topic, not the most decorative. Avoid '
                . 'industry stereotypes (finance as people in suits, healthcare as a doctor in a corridor) and repetitive '
                . 'meeting-room scenes.',
            '4. One signature element directly tied to the topic, large and simple enough to recognise when the card is '
                . 'shown at about 320 x 180 pixels. Fine detail is secondary.',
            '5. Choose composition, environment and lighting intentionally so the images in the course differ from each '
                . 'other while staying accurate. Never introduce unrelated industries just for variety.',
            '6. Real, accurate equipment; natural people and hands; one focal point; no clutter, holograms or futuristic '
                . 'graphics. Charts, tables, figures, screens and documents are welcome where they matter to the topic; '
                . 'they appear as realistic graphical detail, without depending on exact readable text.',
            $school ? '7. The learners are school students: keep people age-appropriate and settings school-safe.' : null,
            '',
            'Write concept, signature_element, environment, perspective, people and lighting as concrete visual '
                . 'descriptions (what a camera would see), each a short phrase or sentence: they are pasted into the image '
                . 'prompt. Keep every field brief.',
        ], fn($line) => $line !== null));
    }

    /**
     * The JSON shape of one item, for instructions.
     *
     * @return string
     */
    public static function item_shape(): string {
        $enum = fn(string $field) => 'one of: ' . implode(', ', self::CATEGORIES[$field]);
        return '{"interpretation": "what this card represents, one sentence", '
            . '"concept": "the chosen visual concept: who or what, doing what", '
            . '"signature_element": "the distinctive element tied to the topic", '
            . '"environment": "the specific setting", '
            . '"perspective": "camera perspective and framing", '
            . '"people": "who appears and what they do, or none", '
            . '"lighting": "lighting and mood", '
            . '"subject": "primary subject, 2-4 words", "action": "main action, 2-4 words", '
            . '"object": "signature object, 1-3 words", '
            . '"env_category": "' . $enum('env_category') . '", '
            . '"composition": "' . $enum('composition') . '", '
            . '"people_arrangement": "' . $enum('people_arrangement') . '", '
            . '"light_category": "' . $enum('light_category') . '"}';
    }

    /**
     * The request that plans every section card plus the banner, in one call.
     *
     * @param array $facts Course facts.
     * @param array $sections Section key => facts.
     * @return string
     */
    public static function course_request(array $facts, array $sections): string {
        $lines = array_merge(
            [self::COURSE_OPENING . ' Plan one image for every section card listed below, and one wide banner for the '
                . 'whole course.', ''],
            self::method_lines($facts['audience'] === 'school students'),
            ['', 'COURSE:'],
            self::course_lines($facts),
            ['', 'CARDS (use the key in brackets):']
        );
        foreach ($sections as $key => $sectionfacts) {
            $lines[] = self::item_line($key, $sectionfacts);
        }
        $lines[] = '';
        $lines[] = 'Before answering, compare the plans: if two share most of environment, composition, people and '
            . 'lighting, or the same primary object, change the weaker one.';
        $lines[] = '';
        $lines[] = 'Reply with ONLY a JSON object, no other text:';
        $lines[] = '{"course": {"interpretation": "what the course is about", "colour_treatment": "a colour treatment '
            . 'that unifies the collection"},';
        $lines[] = ' "items": [{"key": "the key in brackets", ' . substr(self::item_shape(), 1) . ', ...],';
        $lines[] = ' "banner": ' . self::item_shape() . '}';
        return implode("\n", $lines);
    }

    /**
     * The request that plans one image against the course's existing plan.
     *
     * @param array $facts Course facts.
     * @param array $coursedata Course-level plan.
     * @param string $key Item key.
     * @param array $itemfacts Item facts.
     * @param array $others Other planned items, key => entry.
     * @param string $rejected A concept to replace, or ''.
     * @param string $teacher The teacher's own description, or ''.
     * @param string $conflict Why the previous attempt was rejected, or ''.
     * @return string
     */
    public static function item_request(
        array $facts,
        array $coursedata,
        string $key,
        array $itemfacts,
        array $others,
        string $rejected = '',
        string $teacher = '',
        string $conflict = ''
    ): string {
        $lines = array_merge(
            [self::ITEM_OPENING, ''],
            self::method_lines($facts['audience'] === 'school students'),
            ['', 'COURSE:'],
            self::course_lines($facts)
        );
        if (($coursedata['colour_treatment'] ?? '') !== '') {
            $lines[] = '- Colour treatment of the collection: ' . $coursedata['colour_treatment'];
        }
        $lines[] = '';
        $lines[] = 'THE IMAGE TO PLAN:';
        $lines[] = self::item_line($key, $itemfacts);
        $sectionkey = (string) ($itemfacts['sectionkey'] ?? '');
        if ($sectionkey !== '' && isset($others[$sectionkey])) {
            $lines[] = '- Its section card already shows: ' . self::summary_line($others[$sectionkey])
                . ' Do not repeat that image.';
        }
        if ($teacher !== '') {
            $lines[] = '';
            $lines[] = 'THE TEACHER ASKS FOR (highest priority; keep it even if it resembles another image, within safety '
                . 'and quality rules): ' . $teacher;
        }
        $others = array_diff_key($others, [$sectionkey => true]);
        if ($others) {
            $lines[] = '';
            $lines[] = 'OTHER IMAGES IN THIS COURSE (do not repeat their concept or their combination of environment, '
                . 'composition, people and lighting):';
            foreach (array_slice($others, 0, 24, true) as $entry) {
                $lines[] = '- ' . self::summary_line($entry);
            }
        }
        if ($rejected !== '') {
            $lines[] = '';
            $lines[] = 'The teacher asked for a new concept, so this one is rejected: ' . $rejected;
        }
        if ($conflict !== '') {
            $lines[] = '';
            $lines[] = $conflict;
        }
        $lines[] = '';
        $lines[] = 'Reply with ONLY a JSON object, no other text:';
        $lines[] = self::item_shape();
        return implode("\n", $lines);
    }

    /**
     * Ask once for the flagged items of a course plan to be revised.
     *
     * @param array $facts Course facts.
     * @param array $items All items.
     * @param array $issues Key => reason.
     * @param array $sections Section key => facts.
     * @param \context $context The course context.
     * @param int $userid The user.
     * @return array Key => revised entry.
     */
    protected static function revise(
        array $facts,
        array $items,
        array $issues,
        array $sections,
        \context $context,
        int $userid
    ): array {
        $lines = array_merge(
            [self::REVISE_OPENING, ''],
            self::method_lines($facts['audience'] === 'school students'),
            ['', 'COURSE:'],
            self::course_lines($facts),
            ['', 'THE CURRENT PLAN:']
        );
        foreach ($items as $key => $entry) {
            $lines[] = '- [' . $key . '] ' . self::summary_line($entry);
        }
        $lines[] = '';
        $lines[] = 'REVISE ONLY THESE CARDS, keeping them accurate to their topic:';
        foreach ($issues as $key => $reason) {
            $lines[] = (isset($sections[$key]) ? self::item_line($key, $sections[$key]) : '- [' . $key . ']') . ' -> ' . $reason;
        }
        $lines[] = '';
        $lines[] = 'Reply with ONLY a JSON object, no other text:';
        $lines[] = '{"items": [{"key": "the key in brackets", ' . substr(self::item_shape(), 1) . ', ...]}';
        $data = self::parse_json(ai::generate_text($context, $userid, implode("\n", $lines))['text']);
        $out = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            $key = (string) ($item['key'] ?? '');
            $entry = self::normalise_entry((array) $item);
            if (isset($issues[$key]) && self::valid_entry($entry)) {
                $out[$key] = $entry;
            }
        }
        return $out;
    }

    /**
     * How alike two plan items are, from their structured attributes (0 to 7).
     *
     * One point each for the same environment category, composition, people arrangement and
     * lighting category; one for a shared primary object; one for the same subject and action;
     * one for a largely shared concept.
     *
     * @param array $a Plan item.
     * @param array $b Plan item.
     * @return int
     */
    public static function likeness(array $a, array $b): int {
        $score = 0;
        foreach (array_keys(self::CATEGORIES) as $field) {
            if (($a[$field] ?? '') !== '' && ($a[$field] ?? '') === ($b[$field] ?? '')) {
                $score++;
            }
        }
        if (self::overlap((string) ($a['object'] ?? ''), (string) ($b['object'] ?? '')) >= 0.5) {
            $score++;
        }
        if (
            self::overlap(
                ($a['subject'] ?? '') . ' ' . ($a['action'] ?? ''),
                ($b['subject'] ?? '') . ' ' . ($b['action'] ?? '')
            ) >= 0.6
        ) {
            $score++;
        }
        if (self::overlap((string) ($a['concept'] ?? ''), (string) ($b['concept'] ?? '')) >= 0.6) {
            $score++;
        }
        return $score;
    }

    /** @var int Likeness at which two images count as repeats. */
    public const SIMILAR_AT = 4;

    /**
     * Which other items a plan item repeats.
     *
     * @param array $entry Plan item.
     * @param array $others Key => plan item.
     * @return string[] Keys.
     */
    public static function similar_to(array $entry, array $others): array {
        $keys = [];
        foreach ($others as $key => $other) {
            if (self::likeness($entry, $other) >= self::SIMILAR_AT) {
                $keys[] = (string) $key;
            }
        }
        return $keys;
    }

    /**
     * The course-wide check: items that repeat an earlier item, or overuse one environment category.
     * Only the later item of a repeated pair is flagged, so the first keeps its concept.
     *
     * @param array $items Key => entry.
     * @return array Key => reason.
     */
    public static function diversity_issues(array $items): array {
        $issues = [];
        $earlier = [];
        $envs = [];
        $envlimit = max(2, (int) ceil(count($items) / 2));
        foreach ($items as $key => $entry) {
            $similar = self::similar_to($entry, $earlier);
            if ($similar) {
                $issues[$key] = 'too similar to [' . implode('], [', $similar) . ']; choose a clearly different concept';
            }
            $env = (string) ($entry['env_category'] ?? '');
            if ($env !== '' && $env !== 'other') {
                $envs[$env] = ($envs[$env] ?? 0) + 1;
                if ($envs[$env] > $envlimit && !isset($issues[$key])) {
                    $issues[$key] = 'too many images are set in a ' . $env . '; choose a different real setting';
                }
            }
            $earlier[$key] = $entry;
        }
        return $issues;
    }

    /**
     * Shared significant words over the shorter phrase.
     *
     * @param string $a Phrase.
     * @param string $b Phrase.
     * @return float
     */
    public static function overlap(string $a, string $b): float {
        $wa = self::significant_words($a);
        $wb = self::significant_words($b);
        $shorter = min(count($wa), count($wb));
        return $shorter === 0 ? 0.0 : count(array_intersect($wa, $wb)) / $shorter;
    }

    /**
     * The significant words of a phrase.
     *
     * @param string $phrase The phrase.
     * @return string[]
     */
    public static function significant_words(string $phrase): array {
        $words = preg_split('~[^\p{L}\p{N}]+~u', \core_text::strtolower($phrase), -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['a', 'an', 'the', 'of', 'in', 'on', 'at', 'with', 'and', 'from', 'to', 'its', 'their', 'his', 'her',
            'by', 'for', 'during', 'into', 'over', 'one', 'two', 'while', 'as', 'is', 'are', 'who', 'that'];
        return array_values(array_unique(array_filter(
            $words,
            fn($w) => !in_array($w, $stop, true) && \core_text::strlen($w) > 2
        )));
    }

    /**
     * One plan entry summarised on one line, for other requests.
     *
     * @param array $entry Plan entry.
     * @return string
     */
    public static function summary_line(array $entry): string {
        return trim(implode('; ', array_filter([
            $entry['concept'] ?? '',
            ($entry['object'] ?? '') !== '' ? 'object: ' . $entry['object'] : '',
            ($entry['env_category'] ?? '') !== '' ? 'setting: ' . $entry['env_category'] : '',
            ($entry['composition'] ?? '') !== '' ? 'composition: ' . $entry['composition'] : '',
            ($entry['people_arrangement'] ?? '') !== '' ? 'people: ' . $entry['people_arrangement'] : '',
            ($entry['light_category'] ?? '') !== '' ? 'light: ' . $entry['light_category'] : '',
        ])));
    }

    /**
     * A plan entry with every field present and cleaned; categories outside their lists become ''.
     *
     * @param array $item Raw item from the model.
     * @return array
     */
    public static function normalise_entry(array $item): array {
        $entry = [];
        foreach (self::TEXT_FIELDS as $field) {
            $value = $item[$field] ?? '';
            $entry[$field] = self::clean(
                is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value,
                300
            );
        }
        foreach (self::CATEGORIES as $field => $allowed) {
            $value = \core_text::strtolower(trim((string) ($item[$field] ?? '')));
            $entry[$field] = in_array($value, $allowed, true) ? $value : '';
        }
        return $entry;
    }

    /**
     * Whether a plan entry can be painted.
     *
     * @param array $entry Plan entry.
     * @return bool
     */
    public static function valid_entry(array $entry): bool {
        return ($entry['concept'] ?? '') !== '' && str_word_count((string) $entry['concept']) >= 4;
    }

    /**
     * The JSON object in a reply, tolerating chatter and code fences.
     *
     * @param string $reply The model's reply.
     * @return array|null
     */
    public static function parse_json(string $reply): ?array {
        $reply = (string) preg_replace('~' . str_repeat(chr(96), 3) . '[a-z]*~i', '', $reply);
        $start = strpos($reply, '{');
        $end = strrpos($reply, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $data = json_decode(substr($reply, $start, $end - $start + 1), true);
        return is_array($data) ? $data : null;
    }

    /**
     * One clean line of text.
     *
     * @param string $text Text.
     * @param int $max Longest result.
     * @return string
     */
    protected static function clean(string $text, int $max): string {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)), " \t\n\r\0\x0B\"'");
        return \core_text::substr($text, 0, $max);
    }

    /**
     * A stored row, or null.
     *
     * @param int $courseid The course.
     * @param string $key Item key.
     * @return \stdClass|null
     */
    public static function get_row(int $courseid, string $key): ?\stdClass {
        global $DB;
        $row = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'itemkey' => $key]);
        return $row ?: null;
    }

    /**
     * Every stored row for a course.
     *
     * @param int $courseid The course.
     * @return \stdClass[]
     */
    public static function get_rows(int $courseid): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['courseid' => $courseid], 'id');
    }

    /**
     * Store a plan item, keeping the counters of the row it replaces.
     *
     * @param int $courseid The course.
     * @param string $key Item key.
     * @param string $hash Facts hash.
     * @param string $teacherhash sha1 of the teacher's description, or ''.
     * @param array $plan Plan data.
     * @param string|null $prompt Prompt, or null for none yet.
     * @param \stdClass|null $previous The row being replaced, for its counters.
     * @return \stdClass The stored row.
     */
    public static function save_row(
        int $courseid,
        string $key,
        string $hash,
        string $teacherhash,
        array $plan,
        ?string $prompt,
        ?\stdClass $previous
    ): \stdClass {
        global $DB;
        $record = (object) [
            'courseid' => $courseid,
            'itemkey' => $key,
            'factshash' => $hash,
            'teacherhash' => $teacherhash,
            'plan' => json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'prompt' => $prompt,
            'attempts' => $previous ? (int) $previous->attempts : 0,
            'successes' => $previous ? (int) $previous->successes : 0,
            'failures' => $previous ? (int) $previous->failures : 0,
            'lastresult' => $previous ? (string) $previous->lastresult : '',
            'timemodified' => time(),
        ];
        $existing = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'itemkey' => $key], 'id');
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
        } else {
            $record->id = $DB->insert_record(self::TABLE, $record);
        }
        return $record;
    }

    /**
     * Keep the prompt written for an item.
     *
     * @param \stdClass $row The row.
     * @param string $prompt The prompt.
     * @return void
     */
    public static function set_prompt(\stdClass $row, string $prompt): void {
        global $DB;
        $DB->set_field(self::TABLE, 'prompt', $prompt, ['id' => $row->id]);
        $row->prompt = $prompt;
    }

    /**
     * Record an image attempt for an item.
     *
     * @param int $courseid The course.
     * @param string $key Item key.
     * @return void
     */
    public static function record_attempt(int $courseid, string $key): void {
        global $DB;
        $DB->execute('UPDATE {' . self::TABLE . '} SET attempts = attempts + 1, timemodified = ?
                       WHERE courseid = ? AND itemkey = ?', [time(), $courseid, $key]);
    }

    /**
     * Record the outcome of an image request for an item.
     *
     * @param int $courseid The course.
     * @param string $key Item key.
     * @param bool $success Whether the image was generated and stored.
     * @return void
     */
    public static function record_result(int $courseid, string $key, bool $success): void {
        global $DB;
        $field = $success ? 'successes' : 'failures';
        $DB->execute('UPDATE {' . self::TABLE . '} SET ' . $field . ' = ' . $field . ' + 1, lastresult = ?, timemodified = ?
                       WHERE courseid = ? AND itemkey = ?', [$success ? 'success' : 'failure', time(), $courseid, $key]);
    }

    /**
     * Forget a course's visual plan.
     *
     * @param int $courseid The course.
     * @return void
     */
    public static function forget(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }
}
