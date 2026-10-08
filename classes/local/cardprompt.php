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
 * Writes the image prompt for a card or a banner, and the brief behind it.
 *
 * The prompt has two parts:
 *
 *  - The scene: one specific person doing one specific, visible thing in the real place that work
 *    happens, with two or three real objects. On sites with a text model the AI art director
 *    (\format_dari\local\promptwriter) writes it from `brief`, which carries every fact used here.
 *    Without a text model the scene comes from the first of these that applies:
 *      1. a scene for a common section or activity title (Welcome, Assessment, Forum, Quiz…);
 *      2. a scene for the activity type (quiz, assignment, forum, Zoom…);
 *      3. a scene from the course's field (\format_dari\local\imagefields);
 *      4. a general scene built from the topic itself.
 *  - The tail (`promptTail`): medium, colour, 16:9 composition and the no-text rule. It is the same
 *    for every image in a course, so the images look like one set, and nothing the art director
 *    writes can change it.
 *
 * On sites with a text model this class only gathers the facts (`brief`) and the tail: what an
 * image shows is decided by \format_dari\local\imageplanner, and the prompt is written by
 * \format_dari\local\promptwriter. The tail sets only the medium, the accent, the framing and the
 * quality safeguards, so lighting, palette and perspective can vary from image to image.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cardprompt {
    /** @var string Identifies the card recipe in logs. */
    public const VERSION = 'card-5';

    /** @var string Identifies the banner recipe in logs. */
    public const BANNER_VERSION = 'banner-4';

    /** @var string Image kind for a section or activity card. */
    public const KIND_CARD = 'card';

    /** @var string Image kind for a course or section banner. */
    public const KIND_BANNER = 'banner';

    /** @var int Longest template scene part, in characters. */
    public const PROMPT_MAX = 2000;

    /** @var int Longest summary or description excerpt used as detail. */
    public const DETAIL_MAX = 280;

    /** @var int Longest topic, when a summary has to stand in for a missing title. */
    private const TOPIC_MAX = 120;

    /** @var string[] The medium, per course style; the first part of the tail. No fixed lighting or palette. */
    private const MEDIUM = [
        'photo' => 'Photorealistic, high-end documentary and editorial photography: real materials and textures, '
            . 'natural skin tones, professional lighting that suits the scene, crisp focus on the focal point.',
        'illustration' => 'Modern editorial illustration: confident shapes, rich considered colour, subtle texture, '
            . 'natural proportions.',
        'render3d' => 'Polished 3D render: realistic materials, considered lighting, natural proportions, depth of '
            . 'field.',
        'flat' => 'Flat vector illustration: bold clean shapes, a limited harmonious palette, crisp edges, a complete '
            . 'scene rather than a lone icon.',
    ];

    /** @var string[] Composition for each image kind: framing only, so perspectives can vary. */
    private const COMPOSITION = [
        self::KIND_CARD => 'Wide 16:9 landscape frame; keep the focal point and any faces well inside it, away from '
            . 'the top and bottom edges.',
        self::KIND_BANNER => 'Wide panoramic 16:9 frame: the focal point in the right half and the left third calm and '
            . 'simple, because the course title is overlaid there; keep faces away from the top and bottom edges.',
    ];

    /**
     * @var string Quality safeguards, written positively (image models follow concrete description better than
     * long lists of bans). Charts, figures and documents are welcome where they matter; captions, logos and
     * watermarks are not. The main subject must survive being shown as a small card.
     */
    private const NO_TEXT = 'Natural faces, hands and anatomy; accurate real equipment; an uncluttered frame whose main '
        . 'subject is large and clear enough to recognise as a small thumbnail. Charts, tables, figures, screens and '
        . 'documents appear as realistic graphical detail with minimal, clean lettering; no captions, logos or '
        . 'watermarks.';

    /** @var string The closing line of every prompt. */
    private const FINISH = 'Premium educational publication quality.';

    /** @var array Hue upper bounds, in degrees, and the colour word for each band. */
    private const HUES = [
        [15, 'red'], [40, 'orange'], [65, 'golden yellow'], [90, 'lime'], [150, 'green'], [185, 'teal'],
        [205, 'cyan blue'], [245, 'blue'], [275, 'indigo'], [320, 'purple'], [345, 'pink'], [361, 'red'],
    ];

    /**
     * @var string What must never appear. Only the legacy service payload carries it: Moodle's image
     * providers (OpenAI, Gemini) have no negative-prompt parameter, so it is never sent to them.
     */
    public const NEGATIVE = 'captions, logos, watermarks, garbled lettering, signatures, borders, frames, collage, '
        . 'split panels, distorted faces, distorted hands, extra fingers, blurry, low resolution';

    /** @var string Added to the negative prompt for the photographic style. */
    private const NEGATIVE_PHOTO = ', cartoon, illustration, 3D render, plastic skin';

    /**
     * @var array Scenes for common titles, as [title pattern, scene]. Checked in order. The patterns
     * stick to the words courses use for their own housekeeping (welcome, instructions, quiz,
     * resources), not words a topic might contain: "Construction materials" must not become a
     * library. {one} is one learner and {many} is several. Every scene shows people doing something
     * with their hands in a real place; none is a person looking at a laptop.
     */
    private const SCENES = [
        'instructions' => [
            '~\b(instructions?|how to (use|navigate|study|get started)|student (guide|handbook|information)|'
                . 'course (guide|handbook|information|requirements)|important information|read (me|this) first|'
                . 'before you (start|begin)|navigating (this|the) course)\b~i',
            'a friendly trainer standing beside {one} at a large wall planner of colour-coded blocks, pointing to '
                . 'the first step of a simple pathway while the learner follows with a pen and notebook, in a bright '
                . 'training room',
        ],
        'welcome' => [
            '~\b(welcome|introduction|getting started|get started|orientation|start here|about this '
                . '(course|unit|module)|(course|unit|module) overview|induction)\b~i',
            'a smiling trainer greeting {one} at the door of a bright, modern training room, handing over a welcome '
                . 'pack, with other learners settling in softly out of focus behind',
        ],
        'certificate' => [
            '~\b(certificates?|course completion|completion|congratulations|well done|next steps|conclusion|'
                . 'wrap[ -]?up|course summary|graduation|final steps|course close)\b~i',
            '{one} proudly holding a framed certificate in a bright, modern space while two colleagues applaud in '
                . 'the softly lit background',
        ],
        'policy' => [
            '~\b(student polic(y|ies)|policies and procedures|code of conduct|academic integrity|plagiarism|'
                . 'terms and conditions|rights and responsibilities|complaints|appeals|privacy policy)\b~i',
            'a student adviser and {one} talking across a small round table in a calm office, the adviser resting a '
                . 'hand on a closed, bound handbook between them, both attentive and at ease',
        ],
        'schedule' => [
            '~\b(timetable|course schedule|study schedule|calendar|key dates|due dates|study plan|planner|'
                . 'weekly plan)\b~i',
            '{one} standing at a large wall planner of colour-coded blocks, placing a coloured magnet, a backpack '
                . 'over one shoulder',
        ],
        'support' => [
            '~(^(support|help)$|\b(student support|learner support|getting help|help ?desk|need help|contact '
                . '(us|your trainer|details)|faqs?|frequently asked|technical (help|support)|student services|'
                . 'wellbeing)\b)~i',
            'a warm student adviser sitting beside {one} on a sofa in a welcoming student lounge, listening closely '
                . 'and gesturing reassuringly',
        ],
        'live' => [
            '~\b(live sessions?|webinars?|zoom|teams meeting|virtual class(room)?|online class(es)?|tutorial '
                . 'sessions?|drop[ -]?in)\b~i',
            'an energetic trainer teaching a live online class from a small, bright studio, leaning toward a camera '
                . 'on a tripod with one hand raised mid-explanation, a ring light and a microphone in the foreground',
        ],
        'video' => [
            '~\b(videos?|lectures?|recorded|watch|screencasts?|podcasts?)\b~i',
            'a presenter recording a lesson in a small studio, speaking to a camera on a tripod, with soft lights '
                . 'and a microphone boom in the foreground',
        ],
        'workplace' => [
            '~\b(workplace (assessments?|observations?|tasks?|components?|activit(y|ies)|evidence)|work '
                . 'placement|placement|practical (assessments?|tasks?|activit(y|ies)|components?|demonstration)|'
                . 'on[ -]the[ -]job|observation checklist|logbook|log book|third[ -]party|work[ -]based|'
                . 'skills? (check|demonstration))\b~i',
            '{one} applying their skills on the job in a realistic workplace, watched by an experienced '
                . 'supervisor holding a clipboard, with the tools and equipment of the trade around them',
        ],
        'quiz' => [
            '~\b(quiz(zes)?|knowledge (check|test|questions)|self[ -]?(check|test|assessment)|test your|test|'
                . 'exam|review questions|practice questions|check your understanding)\b~i',
            'three {many} around a bright study table playing a quick-fire review game, one raising a coloured '
                . 'answer card and laughing while the others think',
        ],
        'assessment' => [
            '~\b(assessments?|assignments?|submissions?|submit|task \d|portfolio|evidence|written questions|'
                . 'essay|resubmission)\b~i',
            'an assessor and {one} sitting side by side at a table, the assessor pointing to a page of the '
                . 'learner\'s work while the learner nods, focused and confident',
        ],
        'casestudy' => [
            '~\b(case stud(y|ies)|scenarios?|role[ -]?plays?|simulations?)\b~i',
            '{many} gathered around a table working through a real-world case, one person pointing to a printed '
                . 'photo while the others lean in and discuss it',
        ],
        'forum' => [
            '~\b(forums?|discussions?|learning community|group work|networking|peer|chat|q ?& ?a|introduce '
                . 'yourself|meet your)\b~i',
            'a small, diverse group of {many} in a relaxed discussion around a café table, one person speaking with '
                . 'open hands while the others listen and lean in, coffee cups between them',
        ],
        'announcements' => [
            '~\b(announcements?|course news|latest news|news forum|notice ?board|notices)\b~i',
            'a trainer pinning a bright coloured card to a cork noticeboard in a modern training space as two '
                . '{many} stop to look',
        ],
        'feedback' => [
            '~\b(feedback|surveys?|course evaluation|questionnaires?|have your say|tell us what you think)\b~i',
            'a trainer and {one} in relaxed conversation in a bright lounge, the trainer listening intently and '
                . 'making a note while the learner explains with open hands',
        ],
        'reflection' => [
            '~\b(reflect\w*|journal|learning log|diary)\b~i',
            '{one} writing by hand in a journal by a large window, warm light and a plant nearby, calm and '
                . 'thoughtful',
        ],
        'glossary' => [
            '~\b(glossary|terminology|key terms|vocabulary|definitions|acronyms)\b~i',
            'two {many} quizzing each other with a fan of flash cards across a library table, one holding up a '
                . 'card, both smiling',
        ],
        'resources' => [
            '~\b(resources?|readings?|(learning|course|reading|study) materials|library|references|downloads|'
                . 'further reading|toolkit|templates|handouts|learner guide|study guide)\b~i',
            '{one} pulling a reference book from a shelf in a light-filled library, two tabbed guides tucked under '
                . 'their other arm',
        ],
    ];

    /** @var string[] Extra scenes reached only through the activity type. */
    private const MOD_ONLY_SCENES = [
        'page' => '{one} reading a printed guide in a comfortable armchair by a window, a pen in hand and a '
            . 'notebook on the armrest',
        'link' => '{one} reading on a tablet on a sofa in a bright lounge, sitting forward with interest',
        'lesson' => 'a trainer walking {one} through a practical demonstration step by step at a workbench, both '
            . 'focused on the task in the trainer\'s hands',
        'interactive' => '{one} trying a hands-on practice activity with a trainer guiding from beside them, both '
            . 'absorbed and smiling',
        'peerreview' => 'two learners side by side reviewing each other\'s work, one pointing at a printed page '
            . 'while the other listens, supportive and constructive',
    ];

    /** @var string[] Scene for each activity type, by key of SCENES or MOD_ONLY_SCENES. */
    private const MOD_SCENES = [
        'quiz' => 'quiz', 'assign' => 'assessment', 'forum' => 'forum', 'hsuforum' => 'forum',
        'chat' => 'forum', 'wiki' => 'forum', 'resource' => 'resources', 'folder' => 'resources',
        'book' => 'resources', 'page' => 'page', 'url' => 'link', 'lesson' => 'lesson', 'scorm' => 'interactive',
        'h5pactivity' => 'interactive', 'hvp' => 'interactive', 'lti' => 'interactive', 'feedback' => 'feedback',
        'questionnaire' => 'feedback', 'survey' => 'feedback', 'choice' => 'feedback', 'glossary' => 'glossary',
        'workshop' => 'peerreview', 'zoom' => 'live', 'bigbluebuttonbn' => 'live', 'googlemeet' => 'live',
        'customcert' => 'certificate', 'certificate' => 'certificate', 'coursecertificate' => 'certificate',
    ];

    /** @var string Course names and categories that mean school-age learners. */
    private const SCHOOL = '~\b((year|yr|grade|stage)\s*\d{1,2}|primary|secondary|high school|middle school|'
        . 'k-?12|gcse|igcse|a[ -]level|hsc|vce|qce|wace|sace|ncea)\b~i';

    /** @var string A title that is only a number ("Week 3", "Topic 2") and says nothing to draw. */
    private const NUMBERED_ONLY = '~^(week|topic|module|unit|section|part|session|day|lesson|chapter|block|'
        . 'term|stage|step)\s*[0-9ivx]+[\s:.\-–—]*$~iu';

    /** @var string A numbering prefix ("Module 3: ", "Week 1 - ") in front of a real title. */
    private const NUMBER_PREFIX = '~^(week|topic|module|unit|section|part|session|day|lesson|chapter|block|'
        . 'term|stage|step)\s*[0-9ivx]+\s*[:.\-–—|]\s*~iu';

    /** @var string A qualification code in front of a course name ("BSB50420 ", "CPC30220 - "). */
    private const COURSE_CODE = '~^[A-Z]{2,}[A-Z0-9]*\d{3,}[A-Z0-9]*\s*[:.\-–—|]?\s*~u';

    /**
     * Compose the prompt for one section or activity card.
     *
     * @param \stdClass $course The course.
     * @param string $type cardimage::TYPE_SECTION or cardimage::TYPE_CM.
     * @param \section_info|\cm_info $target The card's section or activity.
     * @param string $teacher The teacher's own words; may be empty.
     * @return array{prompt: string, promptTail: string, negativePrompt: string, promptVersion: string,
     *     brief: array}
     */
    public static function compose(\stdClass $course, string $type, $target, string $teacher): array {
        $context = \context_course::instance($course->id);
        $options = course_get_format($course)->get_format_options();

        if ($type === cardimage::TYPE_CM) {
            $title = self::clean(text::plain((string) $target->name, $context));
            $detail = self::excerpt(self::activity_intro($target));
            $modname = (string) $target->modname;
            $kind = $modname === 'subsection' ? 'section' : self::activity_label($modname) . ' activity';
            $section = $target->get_section_info();
            $partname = ($section && trim((string) $section->name) !== '')
                ? self::clean(text::plain((string) $section->name, $context)) : '';
            if (self::is_numbered_only($partname)) {
                $partname = '';
            }
            $colour = cardimage::get_colour((int) $course->id, cardimage::TYPE_CM, (int) $target->id);
        } else {
            $title = trim((string) $target->name) !== ''
                ? self::clean(text::plain((string) $target->name, $context)) : '';
            $detail = self::excerpt(self::html_plain((string) $target->summary));
            $modname = '';
            $kind = 'section';
            $partname = '';
            $colour = cardimage::get_colour((int) $course->id, cardimage::TYPE_SECTION, (int) $target->id);
        }

        $contents = $type === cardimage::TYPE_CM
            ? self::section_contents($course, (int) $target->sectionnum, (int) $target->id)
            : self::section_contents($course, (int) $target->section);

        return self::build(self::KIND_CARD, $course, [
            'title' => $title,
            'detail' => $detail,
            'contents' => $contents,
            'kind' => $kind,
            'modname' => $modname,
            'partname' => $partname,
            'colour' => $colour !== '' ? $colour : self::course_accent($options),
            'style' => cardimage::clean_style($options['cardimagestyle'] ?? ''),
            'teacher' => $teacher,
            'targetKey' => ($type === cardimage::TYPE_CM ? 'cm:' : 'section:') . (int) $target->id,
        ]);
    }

    /**
     * Compose the prompt for the course banner or a section banner.
     *
     * Banners use the course's card image style and colour, so the banner and the cards look like
     * one set. A section banner is about its section; the course banner is about the course.
     *
     * @param \stdClass $course The course.
     * @param \section_info|null $section The section, or null for the course banner.
     * @param string $teacher The teacher's own words; may be empty.
     * @return array{prompt: string, promptTail: string, negativePrompt: string, promptVersion: string,
     *     brief: array}
     */
    public static function compose_banner(\stdClass $course, $section, string $teacher): array {
        $context = \context_course::instance($course->id);
        $options = course_get_format($course)->get_format_options();

        if ($section) {
            $title = trim((string) $section->name) !== ''
                ? self::clean(text::plain((string) $section->name, $context)) : '';
            $detail = self::excerpt(self::html_plain((string) $section->summary));
            $kind = 'section';
            $colour = cardimage::get_colour((int) $course->id, cardimage::TYPE_SECTION, (int) $section->id);
        } else {
            $title = '';
            $detail = self::excerpt(self::html_plain((string) ($course->summary ?? '')));
            $kind = 'course';
            $colour = '';
        }

        $contents = $section
            ? self::section_contents($course, (int) $section->section)
            : self::course_section_names($course);

        return self::build(self::KIND_BANNER, $course, [
            'title' => $title,
            'detail' => $detail,
            'contents' => $contents,
            'kind' => $kind,
            'modname' => '',
            'partname' => '',
            'colour' => $colour !== '' ? $colour : self::course_accent($options),
            'style' => cardimage::clean_style($options['cardimagestyle'] ?? ''),
            'teacher' => $teacher,
            'targetKey' => $section ? 'section:' . (int) $section->id : 'banner',
        ]);
    }

    /**
     * Assemble the prompt, the tail and the brief from what the caller gathered.
     *
     * @param string $imagekind self::KIND_CARD or self::KIND_BANNER.
     * @param \stdClass $course The course.
     * @param array $in title, detail, kind, modname, partname, colour, style, teacher.
     * @return array
     */
    private static function build(string $imagekind, \stdClass $course, array $in): array {
        $context = \context_course::instance($course->id);
        $coursename = self::clean(text::plain((string) $course->fullname, $context));
        $coursetopic = self::course_topic($coursename);
        $category = self::category_name($course);
        $school = preg_match(self::SCHOOL, $coursename . ' ' . $category) === 1;
        $style = $in['style'];
        $iscourse = $in['kind'] === 'course';
        $detail = $in['detail'];

        // What the picture is about. A title that is only a number says nothing, so the summary, then
        // the course, stands in for it.
        $title = $in['title'];
        $untitled = false;
        if (!$iscourse && ($title === '' || self::is_numbered_only($title))) {
            if ($detail !== '') {
                $title = self::first_sentence($detail);
                $detail = $title === $detail ? '' : $detail;
            } else {
                // Nothing to go on but the course; its name must not be read as a housekeeping title
                // ("US CPA Exam Preparation" is not a quiz).
                $title = $coursetopic;
                $untitled = true;
            }
        }
        $topic = $iscourse ? $coursetopic : self::strip_number_prefix($title);

        $contents = array_values(array_filter(array_map(
            fn($c) => self::strip_number_prefix(self::clean((string) $c)),
            (array) ($in['contents'] ?? [])
        )));
        $coursesummary = self::excerpt(self::html_plain((string) ($course->summary ?? '')));

        // The course's field (safety, finance, nursing...) gives a real person, place, task and props.
        // Without one an image model is left to picture "work in <title>", which is what makes a
        // generic stock image.
        $field = imagefields::course_field($coursename . ' ' . $category, $coursesummary);
        $props = [];
        $fieldname = '';
        [$scenekey, $scene] = self::scene($iscourse || $untitled ? '' : $topic, $in['modname'], $school);
        if ($scenekey === 'general' && $field !== null) {
            $seed = $course->id . '|' . $topic . '|' . $in['modname'] . '|' . $in['partname'];
            $picked = imagefields::scene(
                $field,
                $iscourse ? '' : $topic . ' ' . $detail . ' ' . implode(' ', $contents),
                $imagekind === self::KIND_BANNER,
                $school,
                $seed
            );
            $scenekey = 'field:' . $field;
            $scene = $picked['scene'];
            $props = $picked['props'];
            $fieldname = $picked['name'];
        } else if ($scenekey === 'general') {
            $scene = $iscourse
                ? self::general_course_scene($coursetopic, $school)
                : self::general_scene($topic, $coursetopic, $school);
        } else if ($field !== null) {
            $fieldname = imagefields::scene($field, '', false, $school, '')['name'];
        }
        $teacher = self::clean($in['teacher']);
        $colourhex = $in['colour'] !== '' ? strtoupper($in['colour']) : '';
        $colourname = $colourhex !== '' ? self::colour_name($colourhex) : '';
        $audience = $school ? 'school students' : 'adult learners';

        // The template scene, for sites without a text model: the scene first (who, doing what, where),
        // two or three real objects, the teacher's words, then one line of context so the model knows
        // what the picture is for. No title is quoted: image models paint quoted names as lettering.
        $sentences = [];
        $sentences[] = self::sentence(self::capitalise(self::scene_sentence($scene)));
        if ($props) {
            $sentences[] = 'Around them: ' . self::human_list(array_slice($props, 0, 3)) . '.';
        }
        if ($teacher !== '') {
            $sentences[] = self::sentence(self::capitalise($teacher));
        }
        if ($iscourse) {
            $purpose = 'a ' . ($fieldname !== '' ? $fieldname : $coursetopic) . ' course';
        } else if (!$untitled && self::scene($topic, '', $school)[0] !== 'general') {
            // A housekeeping title (Welcome, Quiz, Forum): say what kind of course it is part of.
            $purpose = 'part of a ' . $coursetopic . ' course';
        } else {
            $purpose = 'a lesson on ' . $topic . ($topic === $coursetopic ? '' : ' in a ' . $coursetopic . ' course');
        }
        $sentences[] = 'The image introduces ' . $purpose . ' for ' . $audience
            . ', shown through the real work, not through words or symbols.';

        $head = implode(' ', $sentences);
        if (\core_text::strlen($head) > self::PROMPT_MAX) {
            $head = \core_text::substr($head, 0, self::PROMPT_MAX);
        }
        $tail = self::tail($imagekind, $style, $colourname);
        $courses = $iscourse ? $contents : self::course_section_names($course);

        return [
            'prompt' => $head . "\n\n" . $tail,
            'promptTail' => $tail,
            'negativePrompt' => self::NEGATIVE . ($style === 'photo' ? self::NEGATIVE_PHOTO : ''),
            'promptVersion' => $imagekind === self::KIND_BANNER ? self::BANNER_VERSION : self::VERSION,
            'brief' => [
                'imageKind' => $imagekind,
                'targetKey' => (string) ($in['targetKey'] ?? ''),
                'target' => $in['kind'],
                'title' => $in['title'],
                'topic' => $topic,
                'detail' => $detail,
                'contents' => $contents,
                'courseSections' => array_values(array_filter($courses, fn($c) => $c !== $topic)),
                'activityType' => $in['modname'],
                'partName' => $in['partname'],
                'courseName' => $coursename,
                'courseTopic' => $coursetopic,
                'courseCategory' => $category,
                'courseSummary' => $iscourse ? $detail : $coursesummary,
                'field' => $fieldname,
                'audience' => $school ? 'school students' : 'adult learners',
                'sceneKey' => $scenekey,
                'sceneIdea' => $scene,
                'style' => $style,
                'colourName' => $colourname,
                'colourHex' => $colourhex,
                'teacherDirection' => $teacher,
            ],
        ];
    }

    /**
     * The fixed tail of every prompt: medium, colour, composition, no text.
     *
     * @param string $imagekind self::KIND_CARD or self::KIND_BANNER.
     * @param string $style A value of cardimage::STYLES.
     * @param string $colourname The accent colour in words, or ''.
     * @return string
     */
    public static function tail(string $imagekind, string $style, string $colourname): string {
        return implode(' ', array_filter([
            self::MEDIUM[$style] ?? self::MEDIUM['photo'],
            $colourname !== '' ? 'Where it suits the scene, ' . $colourname . ' appears as a subtle accent.' : '',
            self::COMPOSITION[$imagekind] ?? self::COMPOSITION[self::KIND_CARD],
            self::NO_TEXT,
            self::FINISH,
        ]));
    }

    /**
     * Text with its first letter in capitals.
     *
     * @param string $text Text.
     * @return string
     */
    private static function capitalise(string $text): string {
        return \core_text::strtoupper(\core_text::substr($text, 0, 1)) . \core_text::substr($text, 1);
    }

    /**
     * The scene for a topic: a common title first, then the activity type, else 'general'.
     *
     * @param string $topic The topic, numbering removed; '' to skip title matching.
     * @param string $modname The activity's module name, or ''.
     * @param bool $school Whether the learners are school students.
     * @return array{0: string, 1: string} The scene key and the scene ('' for general).
     */
    public static function scene(string $topic, string $modname, bool $school = false): array {
        $key = 'general';
        if ($topic !== '') {
            foreach (self::SCENES as $name => [$pattern]) {
                if (preg_match($pattern, $topic) === 1) {
                    $key = $name;
                    break;
                }
            }
        }
        if ($key === 'general' && isset(self::MOD_SCENES[$modname])) {
            $key = self::MOD_SCENES[$modname];
        }
        if ($key === 'general') {
            return [$key, ''];
        }
        $scene = self::SCENES[$key][1] ?? self::MOD_ONLY_SCENES[$key];
        return [$key, self::people($scene, $school)];
    }

    /**
     * A scene built from the topic itself, for titles no common scene matches.
     *
     * @param string $topic The topic.
     * @param string $coursetopic The course, code removed.
     * @param bool $school Whether the learners are school students.
     * @return string
     */
    private static function general_scene(string $topic, string $coursetopic, bool $school): string {
        $within = $topic === $coursetopic ? '' : ' (part of ' . $coursetopic . ')';
        return self::people(
            '{pro} doing real, hands-on work in ' . $topic . $within . ', in the kind of place this work really '
                . 'happens, with the equipment, documents and materials of the job around them',
            $school
        );
    }

    /**
     * A scene for a course banner: the field the course belongs to, with people at work in it.
     *
     * @param string $coursetopic The course, code removed.
     * @param bool $school Whether the learners are school students.
     * @return string
     */
    private static function general_course_scene(string $coursetopic, bool $school): string {
        return self::people(
            '{pros} at work in ' . $coursetopic . ', in the kind of place this work really happens, with the '
                . 'equipment and materials of the field around them and one of them in the foreground, engaged and '
                . 'confident',
            $school
        );
    }

    /**
     * A scene fragment as the object of "Show ...".
     *
     * @param string $scene Scene text, possibly starting with a capital or ending with a full stop.
     * @return string
     */
    private static function scene_sentence(string $scene): string {
        $scene = rtrim(trim($scene), '.');
        return \core_text::strtolower(\core_text::substr($scene, 0, 1)) . \core_text::substr($scene, 1);
    }

    /**
     * "a, b and c".
     *
     * @param string[] $items Items.
     * @return string
     */
    private static function human_list(array $items): string {
        $items = array_values(array_unique(array_map(fn($i) => rtrim($i, '.'), $items)));
        if (count($items) < 2) {
            return (string) ($items[0] ?? '');
        }
        $last = array_pop($items);
        return implode(', ', $items) . ' and ' . $last;
    }

    /**
     * Names of the visible activities in a section, for concrete visual references.
     *
     * @param \stdClass $course The course.
     * @param int $sectionnum Section number.
     * @param int $exclude Course module to leave out (the card's own activity), or 0.
     * @return string[]
     */
    public static function section_contents(\stdClass $course, int $sectionnum, int $exclude = 0): array {
        $names = [];
        try {
            $modinfo = get_fast_modinfo($course);
            $context = \context_course::instance($course->id);
            foreach ($modinfo->sections[$sectionnum] ?? [] as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if (
                    (int) $cmid === $exclude || !$cm->visible || $cm->deletioninprogress
                        || in_array($cm->modname, ['label', 'subsection'], true)
                ) {
                    continue;
                }
                $name = self::clean(text::plain((string) $cm->name, $context));
                if ($name !== '' && !self::is_numbered_only($name)) {
                    $names[] = $name;
                }
                if (count($names) >= 8) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $names;
    }

    /**
     * Names of the course's sections, for a course banner.
     *
     * @param \stdClass $course The course.
     * @return string[]
     */
    public static function course_section_names(\stdClass $course): array {
        $names = [];
        try {
            $context = \context_course::instance($course->id);
            foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
                if ((int) $section->section === 0 || !$section->visible || trim((string) $section->name) === '') {
                    continue;
                }
                $name = self::clean(text::plain((string) $section->name, $context));
                if (!self::is_numbered_only($name)) {
                    $names[] = self::strip_number_prefix($name);
                }
                if (count($names) >= 8) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $names;
    }

    /**
     * Fill in who is in the picture.
     *
     * @param string $scene A scene with {one} and {many}.
     * @param bool $school Whether the learners are school students.
     * @return string
     */
    private static function people(string $scene, bool $school): string {
        $one = $school ? 'a secondary school student' : 'an adult learner';
        $many = $school ? 'school students' : 'adult learners';
        $pro = $school ? 'a secondary school student with their teacher' : 'a skilled professional';
        $pros = $school ? 'school students and their teacher' : 'skilled professionals';
        return str_replace(['{one}', '{many}', '{pro}', '{pros}'], [$one, $many, $pro, $pros], $scene);
    }

    /**
     * Whether a title is only a number, such as "Week 3" or "Topic 2".
     *
     * @param string $title The title.
     * @return bool
     */
    public static function is_numbered_only(string $title): bool {
        return $title !== '' && preg_match(self::NUMBERED_ONLY, $title) === 1;
    }

    /**
     * A title without its numbering: "Module 3: Risk management" becomes "Risk management".
     *
     * @param string $title The title.
     * @return string
     */
    public static function strip_number_prefix(string $title): string {
        $stripped = trim((string) preg_replace(self::NUMBER_PREFIX, '', $title));
        return $stripped !== '' ? $stripped : $title;
    }

    /**
     * A course name without its qualification code: "BSB50420 Diploma of Leadership" becomes
     * "Diploma of Leadership". The code means nothing to an image model.
     *
     * @param string $name The course name.
     * @return string
     */
    public static function course_topic(string $name): string {
        $stripped = trim((string) preg_replace(self::COURSE_CODE, '', $name));
        return $stripped !== '' ? $stripped : $name;
    }

    /**
     * The first sentence of a text, at most TOPIC_MAX characters.
     *
     * @param string $text Plain text.
     * @return string
     */
    private static function first_sentence(string $text): string {
        if (preg_match('~^(.+?[.!?])(\s|$)~u', $text, $m)) {
            $text = $m[1];
        }
        if (\core_text::strlen($text) > self::TOPIC_MAX) {
            $cut = \core_text::substr($text, 0, self::TOPIC_MAX);
            $space = \core_text::strrpos($cut, ' ');
            $text = rtrim($space > self::TOPIC_MAX / 2 ? \core_text::substr($cut, 0, $space) : $cut, ' ,;:-') . '…';
        }
        return rtrim($text, '.');
    }

    /**
     * The course category's name, as plain text, or ''.
     *
     * @param \stdClass $course The course.
     * @return string
     */
    public static function category_name(\stdClass $course): string {
        global $DB;
        if (empty($course->category)) {
            return '';
        }
        $name = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
        if ($name === false) {
            return '';
        }
        $name = self::clean(text::plain((string) $name, \context_system::instance()));
        // Moodle's default category names say nothing about the subject.
        return in_array(\core_text::strtolower($name), ['miscellaneous', 'category 1', 'uncategorised'], true)
            ? '' : $name;
    }

    /**
     * The activity's own description, as plain text, or '' when the module has none.
     *
     * @param \cm_info $cm The activity.
     * @return string
     */
    public static function activity_intro(\cm_info $cm): string {
        global $DB;

        $columns = $DB->get_columns($cm->modname);
        if (!isset($columns['intro'])) {
            return '';
        }
        $intro = (string) $DB->get_field($cm->modname, 'intro', ['id' => $cm->instance]);
        return self::html_plain($intro);
    }

    /**
     * Text from HTML, words kept as written, on one line.
     *
     * Not html_to_text(): it writes bold as UPPERCASE and links as footnotes, which a model reads
     * as shouting and noise. Block ends become spaces so paragraphs do not run together.
     *
     * @param string $html Stored HTML.
     * @return string
     */
    public static function html_plain(string $html): string {
        $html = preg_replace('~<(br|/p|/div|/li|/h[1-6]|/td|/tr)\b[^>]*>~i', ' ', $html);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return self::clean(str_replace("\u{00A0}", ' ', $text));
    }

    /**
     * The activity type's name: "Quiz", "Forum".
     *
     * @param string $modname The module's component name without mod_.
     * @return string
     */
    public static function activity_label(string $modname): string {
        $label = get_string_manager()->string_exists('modulename', 'mod_' . $modname)
            ? get_string('modulename', 'mod_' . $modname) : $modname;
        return self::clean($label);
    }

    /**
     * The course's accent colour, resolved as the page resolves it, or ''.
     *
     * @param array $options The course format options.
     * @return string '#rrggbb' or ''.
     */
    private static function course_accent(array $options): string {
        $colour = trim((string) ($options['accentcolour'] ?? ''));
        if ($colour === '') {
            $colour = trim((string) get_config('format_dari', 'defaultaccentcolour'));
        }
        if ($colour === '') {
            // The site's own primary colour, so images sit with the rest of the site.
            global $PAGE;
            $theme = isset($PAGE->theme->name) ? (string) $PAGE->theme->name : 'boost';
            $colour = trim((string) (get_config('theme_' . $theme, 'brandcolor') ?: get_config('theme_boost', 'brandcolor')));
        }
        $forced = trim((string) get_config('format_dari', 'forceaccentcolour'));
        if ($forced !== '') {
            $colour = $forced;
        }
        return cardimage::clean_colour($colour);
    }

    /**
     * A plain colour word for a hex colour, so the model reads intent as well as a value.
     *
     * @param string $hex '#rrggbb' or '#rgb'.
     * @return string
     */
    public static function colour_name(string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        [$r, $g, $b] = array_map(fn($c) => hexdec($c) / 255, str_split($hex, 2));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));

        if ($s < 0.2) {
            return $l < 0.2 ? 'charcoal' : ($l > 0.85 ? 'soft white' : 'slate grey');
        }
        if ($max == $r) {
            $h = 60 * fmod((($g - $b) / $d), 6);
        } else if ($max == $g) {
            $h = 60 * ((($b - $r) / $d) + 2);
        } else {
            $h = 60 * ((($r - $g) / $d) + 4);
        }
        if ($h < 0) {
            $h += 360;
        }

        $name = 'red';
        foreach (self::HUES as [$limit, $label]) {
            if ($h < $limit) {
                $name = $label;
                break;
            }
        }
        if ($l < 0.3) {
            return 'deep ' . $name;
        }
        if ($l > 0.75) {
            return 'pale ' . $name;
        }
        return $name;
    }

    /**
     * One line of plain text: control characters removed, whitespace collapsed.
     *
     * @param string $text Any text.
     * @return string
     */
    private static function clean(string $text): string {
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', (string) $text));
    }

    /**
     * The opening of a summary or description, cut at a word boundary.
     *
     * @param string $text Plain text.
     * @return string
     */
    public static function excerpt(string $text): string {
        $text = self::clean($text);
        if (\core_text::strlen($text) <= self::DETAIL_MAX) {
            return $text;
        }
        $cut = \core_text::substr($text, 0, self::DETAIL_MAX);
        $space = \core_text::strrpos($cut, ' ');
        return rtrim($space > self::DETAIL_MAX / 2 ? \core_text::substr($cut, 0, $space) : $cut, ' ,;:-') . '…';
    }

    /**
     * Text ending in a full stop, so the prompt's parts never run into each other.
     *
     * @param string $text Plain text.
     * @return string
     */
    private static function sentence(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        return preg_match('/[.!?…]$/u', $text) ? $text : $text . '.';
    }
}
