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
 * The 3.0.0 prompt produced plain images. It handed the image model an abstract label
 * ("Subject: Student Instructions.") and asked it to invent "one concrete scene or object". Image
 * models do not plan scenes, so they drew one object on an empty background. Every other line then
 * pushed further towards empty: laptops, screens, charts and books were banned, and the
 * composition asked for "a single focal point" on a "calm uncluttered background" with shallow
 * depth of field.
 *
 * The prompt is now a described scene, written as prose, in two parts:
 *
 *  - The scene: who is in the picture, where they are, what they are doing and what is around
 *    them. It comes from the first of these that applies:
 *      1. a scene for a common section or activity title (Welcome, Student Instructions,
 *         Assessment, Resources, Forum, Quiz, Certificate…);
 *      2. a scene for the activity type (quiz, assignment, forum, Zoom…);
 *      3. a general scene built from the topic itself.
 *    The teacher's own words follow the scene.
 *  - The tail: style, colour, composition and the no-text rule. It is the same for every image in
 *    a course, so the images look like one set.
 *
 * The scene is the part a language model writes far better than any template. When the
 * "Write image scenes with AI" setting is on, \format_dari\local\ai::image_prompt() has the text
 * model rewrite the scene from `brief` (every fact the plugin used) and appends `promptTail`;
 * otherwise `prompt` is sent as it is.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cardprompt {
    /** @var string Identifies the card recipe in the service's logs. */
    public const VERSION = 'card-3';

    /** @var string Identifies the banner recipe in the service's logs. */
    public const BANNER_VERSION = 'banner-2';

    /** @var string Image kind for a section or activity card. */
    public const KIND_CARD = 'card';

    /** @var string Image kind for a course or section banner. */
    public const KIND_BANNER = 'banner';

    /** @var int Longest prompt sent, in characters; agreed with the image service. */
    public const PROMPT_MAX = 2000;

    /** @var int Longest summary or description excerpt used as detail. */
    public const DETAIL_MAX = 280;

    /** @var int Longest topic, when a summary has to stand in for a missing title. */
    private const TOPIC_MAX = 120;

    /** @var string[] Setting and medium phrase per style, in the house prompt. */
    private const SETTING = [
        'photo' => 'Modern, realistic real-world environment, realistic photography',
        'illustration' => 'Modern editorial illustration with confident shapes, soft gradients and subtle texture',
        'render3d' => 'Polished 3D render with soft studio lighting and smooth matte materials',
        'flat' => 'Flat vector illustration with bold clean geometric shapes and a limited harmonious palette',
    ];

    /** @var string[] The closing finish word per style. */
    private const FINISH = [
        'photo' => 'photorealistic',
        'illustration' => 'crisp illustration',
        'render3d' => 'high-quality 3D render',
        'flat' => 'crisp vector art',
    ];

    /** @var string[] How each style's prompt opens. */
    private const STYLE_LEAD = [
        'photo' => 'A realistic professional photograph',
        'illustration' => 'A modern editorial illustration',
        'render3d' => 'A polished 3D render',
        'flat' => 'A flat vector illustration',
    ];

    /** @var string[] Art direction for each value of cardimage::STYLES. */
    private const STYLE_DIRECTION = [
        'photo' => 'realistic professional photography, natural light, true-to-life colour, sharp focus on '
            . 'the main subject with a softly detailed background, a clean, modern training or workplace look. '
            . 'Not a cartoon, illustration or 3D render',
        'illustration' => 'modern editorial illustration, richly detailed, layered shapes with soft gradients '
            . 'and subtle texture, warm and inviting, consistent line weight',
        'render3d' => 'polished 3D render, soft studio lighting, appealing stylised people and props in smooth '
            . 'matte materials, a complete small scene with depth and gentle shadows',
        'flat' => 'flat vector illustration, bold clean geometric shapes, a harmonious palette of five or six '
            . 'colours, crisp edges, a complete scene with several supporting elements and a clear visual '
            . 'hierarchy',
    ];

    /** @var string[] Composition for each image kind. */
    private const COMPOSITION = [
        self::KIND_CARD => 'Wide 16:9 composition, rich but not cluttered: a clear main subject slightly off '
            . 'centre with supporting details around it and depth from foreground to background. Keep faces '
            . 'and key objects away from the very top and bottom edges, which may be cropped.',
        self::KIND_BANNER => 'Wide panoramic banner composition with depth from foreground to background. '
            . 'Place the main subject in the right half of the frame and keep the left third quieter and '
            . 'less detailed, because the title is overlaid there. Keep faces and key objects away from the '
            . 'very top and bottom edges, which may be cropped.',
    ];

    /** @var string The rule against text, written so screens and papers can still appear. */
    private const NO_TEXT = 'No logos, no readable text, no watermarks.';

    /** @var array Hue upper bounds, in degrees, and the colour word for each band. */
    private const HUES = [
        [15, 'red'], [40, 'orange'], [65, 'golden yellow'], [90, 'lime'], [150, 'green'], [185, 'teal'],
        [205, 'cyan blue'], [245, 'blue'], [275, 'indigo'], [320, 'purple'], [345, 'pink'], [361, 'red'],
    ];

    /**
     * @var string What must never appear. Short on purpose: 3.0.0 also banned laptops, screens,
     * charts and books, which ruled out almost every believable learning scene.
     */
    public const NEGATIVE = 'text, letters, words, numbers, captions, logos, watermarks, signatures, borders, '
        . 'frames, collage, split panels, distorted faces, distorted hands, extra fingers, blurry, low resolution';

    /** @var string Added to the negative prompt for the photographic style. */
    private const NEGATIVE_PHOTO = ', cartoon, illustration, 3D render, plastic skin';

    /**
     * @var array Scenes for common titles, as [title pattern, scene]. Checked in order. The patterns
     * stick to the words courses use for their own housekeeping (welcome, instructions, quiz,
     * resources), not words a topic might contain: "Construction materials" must not become a study
     * corner. {one} is one learner and {many} is several.
     */
    private const SCENES = [
        'instructions' => [
            '~\b(instructions?|how to (use|navigate|study|get started)|student (guide|handbook|information)|'
                . 'course (guide|handbook|information|requirements)|important information|read (me|this) first|'
                . 'before you (start|begin)|navigating (this|the) course)\b~i',
            '{one} seated at a clean desk using a laptop that shows a well-organised online learning platform, '
                . 'with a printed checklist with several steps ticked off, a course handbook, a simple progress '
                . 'pathway sketched on a notepad and a pen beside them; they look focused, confident and ready to '
                . 'begin',
        ],
        'welcome' => [
            '~\b(welcome|introduction|getting started|get started|orientation|start here|about this '
                . '(course|unit|module)|(course|unit|module) overview|induction)\b~i',
            '{one} settling in at the start of a new course, opening a laptop that shows a bright, '
                . 'well-organised learning dashboard, with a welcome pack, a notebook and pen, a coffee cup and a '
                . 'plant on the desk and soft morning light — an inviting sense of a fresh start',
        ],
        'certificate' => [
            '~\b(certificates?|course completion|completion|congratulations|well done|next steps|conclusion|'
                . 'wrap[ -]?up|course summary|graduation|final steps|course close)\b~i',
            '{one} proudly holding a framed certificate in a bright, modern space while colleagues applaud in '
                . 'the softly lit background — a strong sense of achievement and momentum',
        ],
        'policy' => [
            '~\b(student polic(y|ies)|policies and procedures|code of conduct|academic integrity|plagiarism|'
                . 'terms and conditions|rights and responsibilities|complaints|appeals|privacy policy)\b~i',
            '{one} reviewing a neatly bound policy folder at a desk beside a laptop, with a tidy stack of '
                . 'signed forms, a small balance-scale ornament and a shield-shaped paperweight — trustworthy, '
                . 'calm and professional',
        ],
        'schedule' => [
            '~\b(timetable|course schedule|study schedule|calendar|key dates|due dates|study plan|planner|'
                . 'weekly plan)\b~i',
            '{one} planning their study week at a tidy desk, pen in hand, with a large wall calendar of '
                . 'colour-coded blocks, a weekly planner, sticky notes and an open laptop',
        ],
        'support' => [
            '~(^(support|help)$|\b(student support|learner support|getting help|help ?desk|need help|contact '
                . '(us|your trainer|details)|faqs?|frequently asked|technical (help|support)|student services|'
                . 'wellbeing)\b)~i',
            'a friendly support person wearing a headset helping {one} over a video call; both are smiling, '
                . 'with a laptop, handwritten notes and a warm, approachable workspace',
        ],
        'live' => [
            '~\b(live sessions?|webinars?|zoom|teams meeting|virtual class(room)?|online class(es)?|tutorial '
                . 'sessions?|drop[ -]?in)\b~i',
            '{one} at home joining a live online class on a laptop, the screen showing a grid of video '
                . 'participants and a presenter, headphones on and notebook open, smiling as they take part',
        ],
        'video' => [
            '~\b(videos?|lectures?|recorded|watch|screencasts?|podcasts?)\b~i',
            '{one} watching a recorded lecture on a large laptop screen, headphones on, taking notes in a '
                . 'notebook, with a cup of tea and warm desk lighting',
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
            '{one} thoughtfully answering multiple-choice questions on a tablet, pen poised over a notepad of '
                . 'working, with a cup of tea and a small timer on the desk, in soft daylight and a quiet, focused '
                . 'atmosphere',
        ],
        'assessment' => [
            '~\b(assessments?|assignments?|submissions?|submit|task \d|portfolio|evidence|written questions|'
                . 'essay|resubmission)\b~i',
            '{one} concentrating on completing a written assessment at a tidy workspace, laptop open beside '
                . 'neatly organised reference notes, a highlighted marking checklist and a desk calendar with a '
                . 'date circled — capable and in control',
        ],
        'casestudy' => [
            '~\b(case stud(y|ies)|scenarios?|role[ -]?plays?|simulations?)\b~i',
            '{many} gathered around a table working through a real-world case, with printed documents, '
                . 'sticky notes and a whiteboard of boxes and arrows, one person pointing as the group discusses '
                . 'it',
        ],
        'forum' => [
            '~\b(forums?|discussions?|learning community|group work|networking|peer|chat|q ?& ?a|introduce '
                . 'yourself|meet your)\b~i',
            'a small, diverse group of {many} in a relaxed discussion around a shared table with laptops, '
                . 'notebooks and coffee, one person speaking while the others listen and lean in — '
                . 'collaborative, friendly and engaged',
        ],
        'announcements' => [
            '~\b(announcements?|course news|latest news|news forum|notice ?board|notices)\b~i',
            '{one} pausing at a modern noticeboard in a bright training space, covered with pinned cards and '
                . 'colour-coded notes, a phone in hand showing a new notification',
        ],
        'feedback' => [
            '~\b(feedback|surveys?|course evaluation|questionnaires?|have your say|tell us what you think)\b~i',
            '{one} giving feedback on a tablet using a simple star-rating form, with a speech-bubble shaped '
                . 'sticky note and a cup of coffee on the desk, relaxed and thoughtful',
        ],
        'reflection' => [
            '~\b(reflect\w*|journal|learning log|diary)\b~i',
            '{one} writing in a reflective journal by a large window, a closed laptop to one side, warm light '
                . 'and a plant nearby, calm and thoughtful',
        ],
        'glossary' => [
            '~\b(glossary|terminology|key terms|vocabulary|definitions|acronyms)\b~i',
            'an open reference book with coloured index tabs, flash cards fanned across a desk, a highlighter '
                . 'and a tablet, neatly arranged and inviting, with {one} reaching for a card',
        ],
        'resources' => [
            '~\b(resources?|readings?|(learning|course|reading|study) materials|library|references|downloads|'
                . 'further reading|toolkit|templates|handouts|learner guide|study guide)\b~i',
            'a well-organised study corner with open reference books, printed guides with coloured tabs, a '
                . 'tablet showing a document library, a reading lamp and a mug, with {one} selecting a guide '
                . 'from the shelf',
        ],
    ];

    /** @var string[] Extra scenes reached only through the activity type. */
    private const MOD_ONLY_SCENES = [
        'page' => '{one} reading an engaging lesson on a tablet in a comfortable, light-filled space, with a '
            . 'notebook, a pen and a cup of coffee beside them',
        'link' => '{one} exploring a trusted website on a laptop, leaning in with interest, with a notebook of '
            . 'jotted ideas and a coffee nearby',
        'lesson' => '{one} working step by step through an interactive lesson on a laptop, a progress pathway '
            . 'of simple shapes on the screen, focused and making steady progress',
        'interactive' => '{one} engaged with an interactive e-learning module on a laptop, tapping through a '
            . 'scenario of simple shapes and illustrations, absorbed and curious',
        'peerreview' => 'two learners side by side reviewing each other\'s work, one pointing at a printed page '
            . 'with sticky notes while the other listens, supportive and constructive',
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
        if (!$iscourse && ($title === '' || self::is_numbered_only($title))) {
            if ($detail !== '') {
                $title = self::first_sentence($detail);
                $detail = $title === $detail ? '' : $detail;
            } else {
                $title = $coursetopic;
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
        [$scenekey, $scene] = self::scene($iscourse ? '' : $topic, $in['modname'], $school);
        if ($scenekey === 'general' && $field !== null) {
            $seed = $course->id . '|' . $topic . '|' . $in['modname'] . '|' . $in['partname'];
            $picked = imagefields::scene($field, $iscourse ? '' : $topic . ' ' . $detail . ' ' . implode(' ', $contents),
                $imagekind === self::KIND_BANNER, $school, $seed);
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

        // The prompt, written the way a good art director writes one: a single confident paragraph
        // that names the purpose and quality, a specific person doing specific work, concrete
        // references to what the course or section covers, the setting, the medium, a palette, room
        // for the title, the no-text rule and the format. Image models follow plain descriptive prose
        // far better than labelled rule lists.
        $what = $iscourse
            ? 'the ' . $coursetopic . ' online course'
            : $topic . ($topic === $coursetopic ? '' : ', ' . self::article($in['kind']) . ' ' . $in['kind']
                . ' in the ' . $coursetopic . ' online course');
        $sentences = [];
        $sentences[] = 'Create a premium, professional eLearning course image for ' . $what
            . ($category !== '' && !$school ? ' (' . $category . ')' : '') . ', for ' . $audience . '.';
        $sentences[] = 'Show ' . self::scene_sentence($scene) . '.';
        if ($props) {
            // Concrete objects an image model can draw. Titles stay out of the picture: models tend to
            // paint quoted names as lettering.
            $sentences[] = 'Include subtle visual references to ' . self::human_list($props) . '.';
        } else {
            $refs = $contents;
            if ($detail !== '') {
                array_unshift($refs, self::first_sentence($detail));
            }
            if ($refs) {
                $sentences[] = 'Hint at ' . self::human_list(array_slice($refs, 0, 4))
                    . ' through the objects, setting and activity, never as written words.';
            }
        }
        if ($teacher !== '') {
            $sentences[] = self::sentence($teacher);
        }
        $sentences[] = self::SETTING[$style] . ', clean composition, polished professional lighting, '
            . ($colourhex !== ''
                ? $colourname . ' accents with white and soft neutral tones'
                : 'a refined palette of soft neutrals with one confident accent colour') . '.';
        $sentences[] = $imagekind === self::KIND_BANNER
            ? 'Keep the main subject in the right half and leave the left third calm and uncluttered for the course '
                . 'title overlay.'
            : 'Leave some uncluttered space for an optional title overlay.';
        $sentences[] = 'No logos, no readable text, no watermarks.';
        $sentences[] = 'Suitable for a professional ' . ($fieldname !== '' ? $fieldname . ' ' : '')
            . 'online learning platform.';
        $sentences[] = 'Wide landscape composition, high detail, ' . self::FINISH[$style] . '.';

        $head = implode(' ', $sentences);
        $tail = 'No logos, no readable text, no watermarks. Wide landscape composition, high detail, ' . self::FINISH[$style] . '.';
        if (\core_text::strlen($head) > self::PROMPT_MAX) {
            $head = \core_text::substr($head, 0, self::PROMPT_MAX);
        }

        return [
            'prompt' => $head,
            'promptTail' => $tail,
            'negativePrompt' => self::NEGATIVE . ($style === 'photo' ? self::NEGATIVE_PHOTO : ''),
            'promptVersion' => $imagekind === self::KIND_BANNER ? self::BANNER_VERSION : self::VERSION,
            'brief' => [
                'imageKind' => $imagekind,
                'target' => $in['kind'],
                'title' => $in['title'],
                'topic' => $topic,
                'detail' => $detail,
                'contents' => $contents,
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
                if ((int) $cmid === $exclude || !$cm->visible || $cm->deletioninprogress
                        || in_array($cm->modname, ['label', 'subsection'], true)) {
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
     * "a" or "an" for a word, by how it sounds: an Assignment, an H5P, a SCORM package.
     *
     * @param string $word The word.
     * @return string
     */
    private static function article(string $word): string {
        if (preg_match('~^[aeiou]~i', $word) || preg_match('~^[FHLMNRSX][A-Z0-9]{1,2}\b~', $word)) {
            return 'an';
        }
        return 'a';
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
    private static function category_name(\stdClass $course): string {
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
    private static function activity_intro(\cm_info $cm): string {
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
    private static function activity_label(string $modname): string {
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
    private static function excerpt(string $text): string {
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
