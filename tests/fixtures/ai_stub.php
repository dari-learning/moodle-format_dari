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

/**
 * Test double for Moodle's AI subsystem, shared by the format_dari tests.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_dari\tests;

use core_ai\aiactions\generate_image;
use core_ai\aiactions\generate_text;
use core_ai\aiactions\responses\response_generate_image;
use core_ai\aiactions\responses\response_generate_text;

/**
 * Replaces \core_ai\manager in the DI container with a PHPUnit mock, so no test reaches a network.
 *
 * Use from an \advanced_testcase. Call stub_ai() (it also clears \format_dari\local\ai's memo),
 * queue replies with queue_text_reply() / queue_image_reply() / queue_failure(), and inspect the
 * actions the plugin sent through $this->aiactions or last_prompt().
 *
 * Text and image replies are queued separately, so a reply meant for the image call is never
 * consumed by a text call made on the way (the image planner and the prompt writer make text
 * calls before every image). With no queued reply, a course planning request gets a plan with
 * one distinct item per section, an item planning request gets one item, a prompt-writing request
 * gets a usable paragraph, and any other
 * text request gets 'A complete answer.'.
 *
 * @package    format_dari
 * @category   test
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait ai_stub {
    /** @var \core_ai\aiactions\base[] Every action the plugin handed to the manager, in order. */
    protected array $aiactions = [];

    /** @var array Queued text replies, consumed in order. Each is a closure taking the action. */
    protected array $aireplies = [];

    /** @var array Queued image replies, consumed in order. Each is a closure taking the action. */
    protected array $aiimagereplies = [];

    /** @var string Default scene paragraph (over the art director's 40-word minimum). */
    protected static string $defaultscene = 'An apprentice chef in crisp whites checks the core temperature of a '
        . 'chicken stew with a probe thermometer while a senior chef watches from the pass, one hand resting on the '
        . 'steel bench. Labelled containers and a stack of copper pans sit behind them. Medium-wide shot at eye '
        . 'level, bright morning light through high windows, stainless steel with fresh green accents.';


    /**
     * A default plan item. Items with different $n differ in every structured attribute that the
     * planner's repetition check compares, so default plans never trigger a revision request.
     *
     * @param string $concept The concept (at least four words).
     * @param int $n A number that makes the item distinct.
     * @return array
     */
    protected static function default_item(string $concept, int $n = 0): array {
        $cats = \format_dari\local\imageplanner::CATEGORIES;
        $objects = ['probe thermometer', 'copper saucepan', 'delivery docket', 'piping bag', 'chopping board',
            'herb planter', 'wine glass', 'stock pot', 'mandoline slicer', 'pastry brush', 'fish scale', 'menu card'];
        $subjects = ['apprentice chef', 'sous chef', 'kitchen porter', 'pastry cook', 'receiving clerk', 'gardener',
            'waiter', 'butcher', 'baker', 'sommelier', 'fishmonger', 'trainer'];
        return [
            'interpretation' => 'What this part teaches.',
            'concept' => $concept,
            'signature_element' => 'a ' . $objects[$n % count($objects)],
            'environment' => 'a specific place number ' . $n,
            'perspective' => $cats['composition'][$n % 7] . ' view',
            'people' => 'one chef',
            'lighting' => 'bright morning light',
            'subject' => $subjects[$n % count($subjects)],
            'action' => ['checking', 'plating', 'receiving', 'piping', 'sorting', 'picking'][$n % 6],
            'object' => $objects[$n % count($objects)],
            'env_category' => $cats['env_category'][$n % 12],
            'composition' => $cats['composition'][$n % 7],
            'people_arrangement' => $cats['people_arrangement'][$n % 5],
            'light_category' => $cats['light_category'][$n % 5],
        ];
    }

    /**
     * The default reply to a single-item planning request: unlike any default course-plan item.
     *
     * @return array
     */
    protected static function default_single_item(): array {
        return [
            'interpretation' => 'What this card represents.',
            'concept' => 'An apprentice chef plating a dessert at the pass',
            'signature_element' => 'a quenelle of sorbet',
            'environment' => 'a test kitchen',
            'perspective' => 'portrait',
            'people' => 'two chefs',
            'lighting' => 'low key',
            'subject' => 'dessert chef',
            'action' => 'plating sorbet',
            'object' => 'quenelle spoon',
            'env_category' => 'laboratory or clinical',
            'composition' => 'portrait',
            'people_arrangement' => 'two people',
            'light_category' => 'low key',
        ];
    }

    /**
     * A default course plan with one item for every key in the planning request.
     *
     * @param string $prompt The planning request.
     * @return string JSON.
     */
    protected static function default_plan(string $prompt): string {
        preg_match_all('~^- \[(section:\d+)\]~m', $prompt, $m);
        $items = [];
        foreach ($m[1] as $n => $key) {
            $items[] = ['key' => $key] + self::default_item('A distinct kitchen scene for ' . $key, $n);
        }
        return json_encode([
            'course' => ['interpretation' => 'A kitchen course.', 'colour_treatment' => 'Steel and fresh greens'],
            'items' => $items,
            'banner' => self::default_item('A kitchen brigade at evening service', count($items)),
        ]);
    }

    /**
     * Install the mock manager.
     *
     * @param bool $text Whether a provider offers text generation.
     * @param bool $image Whether a provider offers image generation.
     * @return void
     */
    protected function stub_ai(bool $text = true, bool $image = true): void {
        $this->aiactions = [];
        $this->aireplies = [];
        $this->aiimagereplies = [];

        $manager = $this->getMockBuilder(\core_ai\manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['is_action_available', 'process_action'])
            ->getMock();
        $manager->method('is_action_available')->willReturnCallback(
            function (string $actionclass) use ($text, $image): bool {
                if ($actionclass === generate_text::class) {
                    return $text;
                }
                if ($actionclass === generate_image::class) {
                    return $image;
                }
                return false;
            }
        );
        $manager->method('process_action')->willReturnCallback(
            function (\core_ai\aiactions\base $action) {
                $this->aiactions[] = $action;
                $isimage = $action instanceof generate_image;
                $reply = $isimage ? array_shift($this->aiimagereplies) : array_shift($this->aireplies);
                if ($reply !== null) {
                    return $reply($action);
                }
                // Defaults: a small landscape PNG; the art director's two replies; a short answer.
                if ($isimage) {
                    return self::image_response($action, self::make_png(64, 36));
                }
                $prompt = (string) $action->get_configuration('prompttext');
                if (strpos($prompt, \format_dari\local\imageplanner::COURSE_OPENING) === 0) {
                    return self::text_response(self::default_plan($prompt));
                }
                if (strpos($prompt, \format_dari\local\imageplanner::ITEM_OPENING) === 0) {
                    return self::text_response(json_encode(self::default_single_item()));
                }
                if (strpos($prompt, \format_dari\local\promptwriter::SCENE_OPENING) === 0) {
                    return self::text_response(self::$defaultscene);
                }
                return self::text_response('A complete answer.');
            }
        );
        \core\di::set(\core_ai\manager::class, $manager);
        \format_dari\local\ai::reset_memo();
    }

    /**
     * Queue a successful text reply.
     *
     * @param string $text Generated text.
     * @param string $finishreason Provider finish reason.
     * @return void
     */
    protected function queue_text_reply(string $text, string $finishreason = 'stop'): void {
        $this->aireplies[] = fn($action) => self::text_response($text, $finishreason);
    }

    /**
     * Queue a successful image reply; the image is written into the user's draft area as core does.
     *
     * @param string|null $bytes Image bytes, or null for a small PNG.
     * @return void
     */
    protected function queue_image_reply(?string $bytes = null): void {
        $this->aiimagereplies[] = fn($action) => self::image_response($action, $bytes ?? self::make_png(64, 36));
    }

    /**
     * Queue a failed reply for the next text call, or for the next image call.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @param bool $image True to fail the next image call rather than the next text call.
     * @return void
     */
    protected function queue_failure(int $code, string $message, bool $image = false): void {
        if ($image) {
            $this->aiimagereplies[] = fn($action) => new response_generate_image(false, $code, $message);
        } else {
            $this->aireplies[] = fn($action) => new response_generate_text(false, $code, $message);
        }
    }

    /**
     * The actions of one kind the plugin sent, in order.
     *
     * @param string $class generate_text::class or generate_image::class.
     * @return \core_ai\aiactions\base[]
     */
    protected function actions_of(string $class): array {
        return array_values(array_filter($this->aiactions, fn($action) => $action instanceof $class));
    }

    /**
     * Build a successful text response.
     *
     * @param string $text Generated text.
     * @param string $finishreason Provider finish reason.
     * @return response_generate_text
     */
    protected static function text_response(string $text, string $finishreason = 'stop'): response_generate_text {
        $response = new response_generate_text(true);
        $response->set_response_data([
            'id' => 'test',
            'fingerprint' => 'test',
            'generatedcontent' => $text,
            'finishreason' => $finishreason,
            'prompttokens' => '1',
            'completiontokens' => '1',
            'model' => 'test-model',
        ]);
        return $response;
    }

    /**
     * Build a successful image response with a real draft file, as a provider does.
     *
     * @param \core_ai\aiactions\base $action The action being answered.
     * @param string $bytes Image bytes.
     * @return response_generate_image
     */
    protected static function image_response(\core_ai\aiactions\base $action, string $bytes): response_generate_image {
        $userid = (int) $action->get_configuration('userid');
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($userid)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => random_int(100000000, 999999999),
            'filepath' => '/',
            'filename' => 'ai_image_' . random_int(1, 999999) . '.png',
        ], $bytes);
        $response = new response_generate_image(true);
        $response->set_response_data([
            'draftfile' => $file,
            'revisedprompt' => '',
            'sourceurl' => '',
            'model' => 'test-model',
        ]);
        return $response;
    }

    /**
     * The prompt text of the most recent text action.
     *
     * @return string
     */
    protected function last_prompt(): string {
        for ($i = count($this->aiactions) - 1; $i >= 0; $i--) {
            if ($this->aiactions[$i] instanceof generate_text) {
                return (string) $this->aiactions[$i]->get_configuration('prompttext');
            }
        }
        return '';
    }

    /**
     * Record that a user accepted the site's AI policy.
     *
     * @param int $userid The user.
     * @return void
     */
    protected function accept_ai_policy(int $userid): void {
        \core_ai\manager::user_policy_accepted($userid, \context_system::instance()->id);
    }

    /**
     * Number of files in a user's draft areas.
     *
     * @param int $userid The user.
     * @return int
     */
    protected function count_draft_files(int $userid): int {
        global $DB;
        return $DB->count_records_select(
            'files',
            "contextid = :ctx AND component = 'user' AND filearea = 'draft' AND filename <> '.'",
            ['ctx' => \context_user::instance($userid)->id]
        );
    }

    /**
     * A real PNG of the given size, with some noise so it does not compress to nothing.
     *
     * @param int $width Width in pixels.
     * @param int $height Height in pixels.
     * @return string
     */
    protected static function make_png(int $width, int $height): string {
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y += 4) {
            for ($x = 0; $x < $width; $x += 4) {
                imagefilledrectangle($img, $x, $y, $x + 3, $y + 3,
                    imagecolorallocate($img, ($x * 7 + $y) % 256, ($y * 5) % 256, ($x ^ $y) % 256));
            }
        }
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return (string) ob_get_clean();
    }
}
