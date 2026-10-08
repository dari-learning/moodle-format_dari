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
 * Concrete visual worlds for common fields of study, for image prompts written without a language model.
 *
 * An image model cannot picture "a professional doing work in Your duty of care". It can picture a
 * site supervisor walking a warehouse floor with a clipboard and a hi-vis vest. This library turns
 * a course's words into a real person, place, task and props, so the template prompt is as specific
 * as one an art director would write. The AI art director (promptwriter) still writes the prompt
 * when a text model is available; this is what it falls back to, and what it improves on.
 *
 * @package    format_dari
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imagefields {

    /**
     * Fields, most specific first. Each has: match (regex over the course's words), name (for
     * "a professional <name> online learning platform"), role, roles, place, props, and tasks as
     * [topic regex or '', task] pairs. A task whose regex matches the section or activity wins;
     * otherwise one is chosen from the rest so neighbouring cards differ.
     */
    private const FIELDS = [
        'whs' => [
            'match' => '~\b(whs|ohs|work(place)? health|health and safety|safe work|hazard|risk assessment|'
                . 'incident|ppe|duty of care|(workplace|site|safety) (safety|officer|leadership|management))\b~i',
            'name' => 'workplace health and safety',
            'role' => 'a site supervisor in a hi-vis vest',
            'roles' => 'a site supervisor and two workers in hi-vis vests and hard hats',
            'place' => 'a busy, well-lit warehouse with racking, forklifts and marked walkways',
            'props' => ['a clipboard with a hazard checklist', 'safety signage shapes without words',
                'hard hats and safety glasses', 'a first-aid kit on the wall', 'yellow floor markings'],
            'tasks' => [
                ['~hazard|inspect|spot|identif~i', 'crouching to inspect a frayed electrical lead beside a workbench, '
                    . 'pen poised over a hazard checklist'],
                ['~risk|control|hierarch|assess~i', 'talking a worker through a risk assessment at a workstation, '
                    . 'pointing to where a guard rail will go'],
                ['~consult|meeting|toolbox|communic|talk~i', 'leading a relaxed toolbox talk with a small team '
                    . 'gathered around a pallet at the start of a shift'],
                ['~incident|report|investigat|injur|first aid~i', 'kneeling beside a colleague with a first-aid kit '
                    . 'open while another worker cordons off a spill'],
                ['~duty|law|legislat|responsib|obligation~i', 'walking the floor with a new team member, gesturing '
                    . 'toward a clearly marked safe walkway and emergency exit'],
                ['~ppe|equipment|protect~i', 'checking a worker\'s harness and hard hat before they climb a ladder'],
                ['', 'talking through a safe lifting technique with a forklift driver beside the racking'],
            ],
        ],
        'accounting' => [
            'match' => '~\b(account\w*|bookkeep\w*|cpa|audit\w*|tax|taxation|payroll|bas|gst|financ\w*|ledger|'
                . 'invoic\w*|budget\w*)\b~i',
            'name' => 'accounting and finance',
            'role' => 'a financial analyst',
            'roles' => 'a small finance team',
            'place' => 'a bright modern office with glass walls and city views',
            'props' => ['bound financial statements', 'a calculator', 'a fountain pen', 'a coffee cup',
                'a leather folio'],
            'tasks' => [
                ['~audit|attest|assur~i', '!walking a warehouse floor with the operations manager during an audit '
                    . 'stocktake, checking a pallet count against a clipboard'],
                ['~tax|bas|gst|lodg~i', '!advising a small-business owner across a café-style meeting table, pen '
                    . 'resting on a folder of receipts, the owner nodding'],
                ['~payroll|wage|salar~i', 'explaining a payslip to an employee in a small glass meeting room'],
                ['~report|analys|forecast|budget~i', '!standing at the head of a boardroom table, walking two '
                    . 'directors through a forecast printed as simple bar charts'],
                ['~control|system|risk|cyber~i', '!walking a data centre aisle with an IT manager, checking a server '
                    . 'rack against an audit checklist on a clipboard'],
                ['', '!leading a finance team discussion around a meeting table spread with bound reports'],
            ],
        ],
        'health' => [
            'match' => '~\b(nurs|health ?care|aged care|disabilit|patient|clinical|medic|individual support|'
                . 'community services|first aid|pharma|allied health|mental health)\w*~i',
            'name' => 'health and community care',
            'role' => 'a care professional in navy scrubs',
            'roles' => 'a small care team',
            'place' => 'a calm, modern clinical setting with natural light',
            'props' => ['a stethoscope', 'a medication trolley', 'a blood pressure cuff', 'a mobility frame'],
            'tasks' => [
                ['~aged|elder|older~i', '!sitting with an elderly resident in a sunny lounge, helping them with a '
                    . 'morning routine'],
                ['~disab|support|ndis~i', '!supporting a client in a wheelchair at a kitchen bench as they prepare '
                    . 'lunch'],
                ['~medic|medicat|pharm~i', 'checking medication against a chart at a medication trolley'],
                ['~infection|hygien|ppe~i', 'washing hands at a clinical basin before putting on gloves and a mask'],
                ['~communic|document|record|care plan~i', 'updating a care plan on a tablet at the bedside while '
                    . 'the patient chats with them'],
                ['', 'taking a patient\'s blood pressure, the two of them sharing an easy smile'],
            ],
        ],
        'it' => [
            'match' => '~\b(ict|information technology|cyber ?security|software|programming|coding|developer|networking|'
                . 'cloud computing|database|web development|computer science|computing|machine learning)\b~i',
            'name' => 'technology',
            'role' => 'a software engineer',
            'roles' => 'a small product team',
            'place' => 'a modern tech studio with standing desks and plants',
            'props' => ['a server rack with neat cabling', 'network patch cables', 'a whiteboard of boxes and '
                . 'arrows', 'sticky notes'],
            'tasks' => [
                ['~cyber|secur|threat|attack~i', '!monitoring a security dashboard in a dim operations room, '
                    . 'leaning in to a screen of network graphs'],
                ['~network|server|cloud|infra~i', 'patching cables in a tidy server rack with a laptop balanced on '
                    . 'the side'],
                ['~data|analy|database|sql~i', 'sketching a data model of linked boxes on a glass wall with a colleague'],
                ['~design|ux|user~i', 'sketching app wireframes on a whiteboard while a teammate holds a phone '
                    . 'prototype'],
                ['', 'explaining a system design to a teammate at a whiteboard of boxes and arrows, marker in hand'],
            ],
        ],
        'hospitality' => [
            'match' => '~\b(hospitality|cookery|chef|kitchen|food|barista|coffee|bartend\w*|restaurant|hotel|catering|'
                . 'tourism)\b~i',
            'name' => 'hospitality',
            'role' => 'a chef in whites',
            'roles' => 'a busy kitchen brigade',
            'place' => 'a gleaming commercial kitchen with stainless steel benches',
            'props' => ['fresh produce', 'copper pans', 'colour-coded chopping boards', 'plated dishes under warm '
                . 'lights', 'a temperature probe'],
            'tasks' => [
                ['~food safety|hygien|temperatur|storage~i', 'checking a dish with a temperature probe beside '
                    . 'labelled storage containers'],
                ['~coffee|barista|beverage~i', '!pouring latte art behind a polished espresso machine'],
                ['~serv|customer|front of house|guest~i', '!welcoming guests to a table in a warm, softly lit '
                    . 'restaurant'],
                ['~knife|prep|cut~i', 'dicing vegetables with precise knife work on a green chopping board'],
                ['', 'plating a dish with tweezers at the pass while the kitchen moves behind them'],
            ],
        ],
        'construction' => [
            'match' => '~\b(construct|building (and|&) construction|builder|carpent|electric|plumb|trade|white card|civil|scaffold|'
                . 'concret|apprentice)\w*~i',
            'name' => 'construction and trades',
            'role' => 'a qualified tradesperson in workwear and a hard hat',
            'roles' => 'a construction crew',
            'place' => 'a modern building site in morning light, timber frames and scaffolding behind',
            'props' => ['a tool belt', 'architectural plans spread on a trestle', 'a laser level',
                'stacked timber'],
            'tasks' => [
                ['~electric|wiring|circuit~i', 'testing a switchboard with a multimeter, safety glasses on'],
                ['~plumb|pipe|water~i', 'fitting copper pipework under a new bathroom vanity'],
                ['~plan|read|drawing|measure~i', 'reading plans on a trestle table with an apprentice, '
                    . 'tape measure in hand'],
                ['', 'checking a timber frame with a laser level while an apprentice steadies it'],
            ],
        ],
        'business' => [
            'match' => '~\b(business|leader|manage|supervis|hr|human resource|project|strategy|entrepreneur|'
                . 'admin|office|team)\w*~i',
            'name' => 'business and leadership',
            'role' => 'a team leader',
            'roles' => 'a diverse team',
            'place' => 'a bright, modern open-plan office with plants and a glass-walled meeting room',
            'props' => ['a whiteboard with a simple process diagram', 'sticky notes in rows',
                'coffee cups on a meeting table', 'notebooks'],
            'tasks' => [
                ['~coach|feedback|perform|mentor~i', 'having a relaxed one-on-one coaching conversation with a '
                    . 'team member over coffee'],
                ['~project|plan|schedul~i', 'arranging sticky notes on a project board with two colleagues'],
                ['~meeting|communic|present~i', '!presenting to a small team in a glass-walled meeting room'],
                ['~recruit|interview|hr|onboard~i', 'welcoming a new starter and showing them around the office'],
                ['', 'leading a stand-up meeting around a high table, the team engaged and smiling'],
            ],
        ],
        'customer' => [
            'match' => '~\b(retail|customer|sales|service desk|call centre|contact centre)\w*~i',
            'name' => 'retail and customer service',
            'role' => 'a customer service specialist',
            'roles' => 'a retail team',
            'place' => 'a stylish, bright retail store',
            'props' => ['neatly merchandised shelves', 'shopping bags', 'a point-of-sale counter'],
            'tasks' => [
                ['~complain|difficult|resolv~i', 'calmly listening to a customer at the counter, nodding with '
                    . 'genuine attention'],
                ['~phone|call|contact~i', '!wearing a headset at a bright contact-centre desk, smiling mid-call'],
                ['', 'helping a customer choose a product at a display table'],
            ],
        ],
        'education' => [
            'match' => '~\b(teach|trainer|assessor|tae|early childhood|childcare|education support|classroom|'
                . 'pedagog)\w*~i',
            'name' => 'education and training',
            'role' => 'a trainer',
            'roles' => 'a trainer and a small group of adult learners',
            'place' => 'a light-filled training room with movable tables',
            'props' => ['learning materials on the tables', 'a whiteboard', 'coloured cards',
                'hands-on practice equipment'],
            'tasks' => [
                ['~early childhood|childcare|child~i', '!kneeling at a low table with toddlers stacking colourful '
                    . 'blocks in a bright early learning centre'],
                ['~assess|evidence|observ~i', 'observing a learner demonstrate a practical skill, notes in hand'],
                ['', 'guiding a small group activity, leaning in to help one learner'],
            ],
        ],
        'law' => [
            'match' => '~\b(law|legal|paralegal|justice|compliance|contract|conveyanc|bar exam)\w*~i',
            'name' => 'law and compliance',
            'role' => 'a lawyer',
            'roles' => 'a legal team',
            'place' => 'a refined law office with timber shelves of legal volumes',
            'props' => ['bound legal volumes', 'a fountain pen', 'neatly tabbed folders', 'a leather briefcase'],
            'tasks' => [
                ['~contract|draft~i', 'marking up a contract draft with a fountain pen at a polished desk'],
                ['~court|advoca|hearing~i', '!preparing notes at a courtroom bar table before a hearing'],
                ['', 'reviewing tabbed folders with a colleague in a quiet meeting room'],
            ],
        ],
        'marketing' => [
            'match' => '~\b(marketing|branding|advertising|social media)\b~i',
            'name' => 'marketing',
            'role' => 'a marketing strategist',
            'roles' => 'a creative marketing team',
            'place' => 'a creative studio with mood boards and natural light',
            'props' => ['a mood board of colour swatches and photos', 'printed design proofs',
                'a phone on a small tripod', 'product samples'],
            'tasks' => [
                ['~social|content~i', 'filming a short product video on a phone on a small tripod'],
                ['~analy|metric|data~i', '!presenting campaign results as simple printed charts to a client in a '
                    . 'bright studio meeting space'],
                ['', 'pinning photos to a mood board while the team discusses a campaign'],
            ],
        ],
        'science' => [
            'match' => '~\b(science|biology|chemistry|physics|laborator|lab|microbiolog|scientific)\w*~i',
            'name' => 'science',
            'role' => 'a scientist in a lab coat and safety glasses',
            'roles' => 'a research team',
            'place' => 'a clean modern laboratory with white benches',
            'props' => ['glassware with coloured solutions', 'a microscope', 'pipettes', 'sample racks'],
            'tasks' => [
                ['~micro|cell|biolog~i', 'adjusting a microscope with a slide under the lens'],
                ['~chem|reaction|solution~i', 'pipetting a coloured solution into a row of test tubes'],
                ['~physics|force|energy|electric~i', 'setting up a pendulum experiment with sensors on the bench'],
                ['', 'recording observations beside a rack of samples'],
            ],
        ],
        'maths' => [
            'match' => '~\b(math|maths|mathematics|algebra|geometry|statistics|numeracy|calculus)\b~i',
            'name' => 'mathematics',
            'role' => 'a learner',
            'roles' => 'learners',
            'place' => 'a bright study space',
            'props' => ['graph paper with plotted curves', 'a geometry set', 'a calculator',
                'geometric models', 'a whiteboard of diagrams'],
            'tasks' => [
                ['~geometr|shape|angle~i', 'measuring an angle on a geometric model with a protractor'],
                ['~statist|data|graph|probab~i', 'plotting a graph from dice results, coloured pens laid out'],
                ['', 'working through a problem on a whiteboard full of diagrams'],
            ],
        ],
        'beauty' => [
            'match' => '~\b(beauty|hairdress|salon|barber|cosmetic|makeup|nail)\w*~i',
            'name' => 'beauty and hairdressing',
            'role' => 'a hair stylist',
            'roles' => 'a salon team',
            'place' => 'a stylish modern salon with large mirrors and warm lighting',
            'props' => ['professional scissors and combs', 'colour bowls and brushes', 'a styling trolley'],
            'tasks' => [
                ['~colour|color~i', 'applying hair colour with a brush, foils neatly laid out'],
                ['', 'shaping a client\'s haircut while they chat in the mirror'],
            ],
        ],
        'fitness' => [
            'match' => '~\b(fitness|personal train|sport|exercise|coach|gym|physical education)\w*~i',
            'name' => 'fitness and sport',
            'role' => 'a personal trainer',
            'roles' => 'a fitness class',
            'place' => 'a bright, modern gym with natural light',
            'props' => ['kettlebells', 'resistance bands', 'a fitness tracker', 'a skipping rope'],
            'tasks' => [
                ['~assess|screen|measure~i', 'watching a client perform an overhead squat, clipboard in hand'],
                ['', 'coaching a client through a kettlebell squat, correcting their posture'],
            ],
        ],
        'logistics' => [
            'match' => '~\b(logistic|warehous|supply chain|transport|driving|forklift|freight|inventory)\w*~i',
            'name' => 'logistics',
            'role' => 'a logistics coordinator',
            'roles' => 'a warehouse team',
            'place' => 'a vast, organised distribution centre',
            'props' => ['a handheld scanner', 'labelled cartons without readable text', 'a forklift',
                'tall racking'],
            'tasks' => [
                ['~inventory|stock~i', 'scanning cartons on tall racking with a handheld scanner'],
                ['', 'directing a forklift as it loads a pallet onto a truck at the dock'],
            ],
        ],
        'agriculture' => [
            'match' => '~\b(agricultur|farm|horticultur|landscap|viticultur|livestock|garden|conservation)\w*~i',
            'name' => 'agriculture and horticulture',
            'role' => 'a farmer',
            'roles' => 'a farm team',
            'place' => 'open green farmland at golden hour',
            'props' => ['a soil sample kit', 'seedlings in trays', 'a utility vehicle', 'pruning shears'],
            'tasks' => [
                ['~soil|plant|crop|horticult~i', 'kneeling in a crop row, checking soil in a gloved hand'],
                ['~livestock|animal|cattle|sheep~i', '!checking cattle in a yard, notebook in hand'],
                ['', '!inspecting seedlings in a sunlit greenhouse'],
            ],
        ],
        'languages' => [
            'match' => '~\b(english|language|esl|eal|ielts|spanish|french|japanese|chinese|literature|'
                . 'literacy)\b~i',
            'name' => 'languages and literacy',
            'role' => 'a learner',
            'roles' => 'learners',
            'place' => 'a warm, light-filled library',
            'props' => ['open books', 'a notebook', 'index cards', 'a cup of tea'],
            'tasks' => [
                ['~speak|conversat|listen|pronunc~i', '!practising conversation with a partner across a café '
                    . 'table'],
                ['~writ|essay~i', 'drafting by hand in a notebook at a sunny library table'],
                ['', 'reading in a library armchair, a stack of books beside them'],
            ],
        ],
    ];

    /** @var string[] Camera framing, chosen per card so a page of cards does not repeat one shot. */
    private const SHOTS = [
        'in a medium-wide shot at eye level',
        'in a medium close-up that shows their hands and the work',
        'seen over their shoulder so the task fills the frame',
        'in a wider shot that shows the whole space around them',
        'with a colleague beside them, both focused on the task',
    ];

    /**
     * The field a course belongs to, by its words.
     *
     * @param string $coursetext Words about the course.
     * @return string|null The field key, or null when none matches.
     */
    public static function field(string $coursetext): ?string {
        foreach (self::FIELDS as $key => $field) {
            if (preg_match($field['match'], $coursetext) === 1) {
                return $key;
            }
        }
        return null;
    }

    /**
     * The field from the course name and category first, then the summary, so a word in passing in
     * the summary does not outrank what the course is called.
     *
     * @param string $name Course name and category.
     * @param string $summary Course summary.
     * @return string|null
     */
    public static function course_field(string $name, string $summary): ?string {
        return self::field($name) ?? ($summary !== '' ? self::field($summary) : null);
    }

    /**
     * A concrete scene for a card or banner in a field.
     *
     * @param string $field Field key from field().
     * @param string $topictext The card's title, summary and contents ('' for the course banner).
     * @param bool $banner Whether this is a banner.
     * @param bool $school Whether the learners are school students.
     * @param string $seed Something stable and unique to the card, to vary the choice.
     * @return array{scene: string, props: string[], name: string}
     */
    public static function scene(string $field, string $topictext, bool $banner, bool $school, string $seed): array {
        $f = self::FIELDS[$field];
        $hash = (int) sprintf('%u', crc32($seed));

        $task = '';
        if ($topictext !== '') {
            foreach ($f['tasks'] as [$pattern, $text]) {
                if ($pattern !== '' && preg_match($pattern, $topictext) === 1) {
                    $task = $text;
                    break;
                }
            }
        }
        if ($task === '') {
            $tasks = array_column($f['tasks'], 1);
            $task = $banner ? end($tasks) : $tasks[$hash % count($tasks)];
        }

        $who = $banner ? $f['roles'] : $f['role'];
        if ($school) {
            $who = $banner ? 'secondary school students with their teacher' : 'a secondary school student';
        }
        // A task marked '!' names its own place (a lounge, a café), so the field's usual place is left out.
        $ownplace = str_starts_with($task, '!');
        $task = ltrim($task, '!');
        $scene = $who . ' ' . ($banner ? 'at work, one of them in the foreground ' : '') . $task
            . ($ownplace ? '' : ', in ' . $f['place']);
        if (!$banner) {
            $shots = self::SHOTS;
            if (preg_match('~\b(with|to|and) (a|an|two|the|their)\b|colleague|team|client|customer|guest|resident|'
                    . 'patient|learner|toddler|partner|apprentice|worker|pair|other|group|owner|director|manager|employee|'
                    . 'teammate|driver~i', $task) === 1) {
                // Someone else is already in the scene; do not add a second companion.
                $shots = array_values(array_filter($shots, fn($s) => !str_contains($s, 'colleague')));
            }
            $shot = $shots[$hash % count($shots)];
            if ($school) {
                $shot = str_replace('a colleague', 'a classmate', $shot);
            }
            $scene .= ', ' . $shot;
        }

        // A task that names its own place brings its own objects; the field's usual props would not fit.
        $props = $ownplace ? [] : $f['props'];
        if (!$props) {
            return ['scene' => $scene, 'props' => [], 'name' => $f['name']];
        }
        $offset = $hash % count($props);
        $props = array_merge(array_slice($props, $offset), array_slice($props, 0, $offset));
        return ['scene' => $scene, 'props' => array_slice($props, 0, 3), 'name' => $f['name']];
    }
}
