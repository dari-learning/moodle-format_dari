<!-- Repository metadata added for Marketplace submission; runtime source remains as supplied. -->

[Documentation](https://darilearning.com/docs) · [Report a bug](https://github.com/dari-learning/moodle-format_dari/issues) · [Submission checklist](docs/marketplace-submission.md) · [Moodle™ Marketplace](https://marketplace.moodle.com/)

**Marketplace listing: not approved yet.** Source hosting does not imply approval or independently verified compatibility. See [CHANGELOG.md](CHANGELOG.md) for the imported package version.

# Dari course format (format_dari)

Dari is a course format for Moodle™. It turns a course into a clear, visual learning experience, It also adds **Ask Dari**, a course-aware Ask Dari, and **AI-generated course images**. Both run on **your school's own AI provider**:

- **Moodle™ 4.5 and later:** through Moodle™'s built-in AI subsystem.
- **Moodle™ 4.4:** through an OpenAI-compatible connection you enter in Dari's settings.

Dari has no AI service of its own. There is no extra AI subscription, and nothing is sent to the plugin's maker.

- **On Moodle™ 4.5 and later**, every AI request goes from Moodle™ to the provider your administrator has connected under *Site administration > General > AI*, for example OpenAI, Azure AI, Google Gemini, Anthropic, AWS Bedrock, DeepSeek or a self-hosted Ollama server. Each request follows Moodle™'s AI usage policy and appears in Moodle™'s AI usage report.
- **On Moodle™ 4.4**, which has no AI subsystem, requests go directly from Moodle™ to the service your administrator enters in Dari's settings, using your school's own key.

Everything apart from the AI features works without an AI provider.

Website: https://darilearning.com
Made by Dari Learning.

---

## Features

### Course layout

- **Hero banner.** A banner across the top of the course shows the course name, module count, activity count and total estimated time, plus a progress ring and bar. Quick buttons link to grades, Ask Dari and course home. You can upload a banner image, generate one with AI or let the banner show a soft gradient wash of the primary colour. When there's an image, the banner's buttons sit in translucent "glass" groups over it. Each section can have its own banner. The banner can stay at the top of the screen while learners scroll, sit above Moodle™'s course tabs, and collapse.
- **Section cards.** Each section becomes a card in a grid or a list. A card shows its module number or a chosen icon, an image or colour, the activity count, the estimated time, a progress bar ("2 of 3 done"), and a status with a call to action (Start, Continue or Review). Cards can also list their activities with completion ticks, up to a limit you set. You can switch back to traditional sections for any course.
- **Activity tiles.** Inside a section, each activity can be a tile showing its icon, type, estimated time and completion. You can switch back to the standard Moodle™ list.
- **Inline General section.** Section 0 (usually Announcements) appears at the top of the course page. It can be hidden from students or from everyone. It always shows in edit mode, and it is never hidden if it holds anything besides Announcements.
- **Course index "player" sidebar.** Moodle™'s course index becomes a progress panel. It shows your logo, the course name, a progress ring and the total time, then one row per activity with its time and a completion tick. The current activity is highlighted. Moodle™'s own course index stays underneath, so collapsing and drag-and-drop still work.
- **Activity pages.** A compact hero shows the section and activity name, what the learner must do to complete the activity, and previous/next arrows.
- **Per-section icons.** A searchable icon picker grouped into Numbers, Education, Work & industry, Safety & compliance, People, Achievement and General.
- **Card images and colours.** Upload an image for any section or activity card, generate one with AI, or pick a card colour.
- **Estimated time.** Dari sets a default time for each activity type and works out quiz times from the number of questions. Teachers can override any single activity by clicking its time badge in edit mode. Section and course totals add these up.
- **Page tidying.** Per course, or forced across the site, you can hide Moodle™'s course tabs, breadcrumb, site footer and logo band from students or from everyone. All of them come back in edit mode.
- **Colour and appearance.** Dari follows your theme's primary colour (Boost's default is `#0f6cbf`). The banner, card gradients, course index headings, icons, buttons and focus ring all take their colour from it, so the format matches your site out of the box. You can optionally override it with an accent colour for the whole site or a single course. You can also set card and course index colours, colour strength, course index heading and icon colours, a logo for the player sidebar, and light, dark, follow-theme or follow-device mode.
- **Guided tours.** Separate first-visit tours for teachers and for learners, with optional spoken narration.

### Ask Dari (AI study assistant)

- The Ask Dari panel opens on course, section and activity pages, with a full-screen "study view".
- **Aware of where the learner is.** It knows the course, the section and the activity. Inside a quiz it knows which question the learner is on.
- **Three study tools:** *Explain the concept simply*, *Give me some practice questions* and *Show me a real-world example*.
- **Interactive practice questions.** Multiple-choice cards with a hint. A first wrong answer gets one retry. A second wrong answer reveals the answer and the explanation.
- Answers use formatted Markdown with tip, key-idea, warning and example callouts, checklists and tables. Learners can copy answers and rate them as helpful or not.
- **Academic integrity rules.** Ask Dari will not answer course quiz or assessment questions or write anything a learner could hand in. It uses a three-step hint ladder instead (see *Academic integrity* below).
- **Grounded in the course.** It answers from the course material and names the section or activity it used. When something is not covered, it says so.
- **Audience setting per course.** Choose adult learners (VET, higher education, workplace), secondary or primary.
- **Wellbeing response.** If a learner discloses harm or danger, Ask Dari gives a short, kind reply that points them to support contacts you set.
- **Teacher corrections.** Corrections a teacher writes in the report are fed into future answers for that activity.
- **Ask Dari reports.** A course report and a site-wide report both show every question, answer, rating and correction, with filters. The site-wide report also exports to CSV.

### AI images

- **AI banners.** Dari writes an image brief from the course name and summary, and your school's AI provider generates a wide banner. Teachers can add optional detail. The banner is generated in the background and saved to the course automatically.
- **AI card images.** Generate an image for one section or activity card, or for all cards at once (only the cards without an image, if you prefer).
- **One art style per course.** Photographic, Illustration, 3D render or Flat illustration, so every image in a course looks like one set.
- **Optional AI scene writer.** Before each image, the text provider writes a specific scene from the course, section and activity details.

---

## Requirements

Supports Moodle™ 4.4 to 5.3. Tested on Moodle™ 4.4, 5.0 and 5.3.

| | |
|---|---|
| Moodle™ | 4.4 to 5.3. Tested on 4.4, 5.0 and 5.3. |
| PHP | As required by your Moodle™ version |
| Database | Any database Moodle™ supports |
| Themes | Developed and tested against Boost and Academi. Should work with most Boost-based themes. |
| AI features, Moodle™ 4.5 and later | An AI provider configured in Moodle™ core, with **Generate text** enabled (for Ask Dari) and/or **Generate image** enabled (for banners and card images) |
| AI features, Moodle™ 4.4 | An OpenAI-compatible API your school controls, entered under **AI connection (Moodle™ 4.4)** in Dari's settings |

Moodle™ 4.4 has no AI subsystem (`core_ai` first shipped in 4.5), so on 4.4 Dari connects to the service you configure directly. When the site is upgraded to 4.5 or later, Dari switches to Moodle™'s own AI settings automatically, and the 4.4 connection settings disappear.

Everything apart from the AI features works on every supported version without any AI connection.

---

## Installation

**Option 1: upload the ZIP**

1. Go to *Site administration > Plugins > Install plugins*.
2. Upload the `format_dari` ZIP file and follow the prompts.

**Option 2: copy the files**

1. Unzip the plugin into `course/format/dari` in your Moodle™ code.
2. Go to *Site administration > Notifications* to finish the installation.

**To use Dari in a course**, open *Course settings > Course format* and choose **Dari**.

---

## Configuration

### Step 1: connect your AI (only needed for the AI features)

#### Moodle™ 4.5 and later: Moodle™'s AI providers

Dari uses whatever AI provider your site already has. If you do not have one yet:

1. Go to *Site administration > General > AI > AI providers*.
2. Add or enable a provider, for example OpenAI, Azure AI, Gemini, Anthropic, AWS Bedrock, DeepSeek or Ollama, depending on what your Moodle™ version offers. Enter your organisation's API key or endpoint.
3. In that provider's action settings, turn on:
   - **Generate text** for Ask Dari, and the AI scene writer if you use it
   - **Generate image** for banners and card images (the provider and model must support image generation)
4. Check the **AI usage policy**. Each user accepts it once before using any AI feature, and Dari will not send a request until they have.
5. Optional: Moodle™ 5.0 and later can turn AI tools on or off per course and per activity. Dari respects those switches.

#### Moodle™ 4.4: Dari's own AI connection

Moodle™ 4.4 has no AI settings of its own. Go to *Site administration > Plugins > Course formats > Dari* and fill in **AI connection (Moodle™ 4.4)**:

| Setting | What to enter | Default |
|---|---|---|
| `directendpoint`: API endpoint | The base URL of any OpenAI-compatible API, ending before `/chat/completions`. For example `https://api.openai.com/v1`, an Azure OpenAI v1 endpoint, `https://generativelanguage.googleapis.com/v1beta/openai` (Gemini), `https://openrouter.ai/api/v1`, a LiteLLM gateway, or `http://your-server:11434/v1` (Ollama or vLLM) | empty |
| `directapikey`: API key | Your school's own key. Leave empty for a self-hosted server that needs none. | empty |
| `directtextmodel`: Text model | The model Ask Dari uses, for example `gpt-6-astra`, `gpt-6.1-sol`, `gemini-2.5-flash` or `llama3.1:8b`. It also writes the image prompts. Leave empty to turn Ask Dari off. | `gpt-6-astra` |
| `directimagemodel`: Image model | The model for banners and card images, for example `gpt-image-2.5-sunburst`, `gpt-image-2.5-flare` or `gpt-image-2`. Leave empty to turn AI images off. | `gpt-image-2.5-sunburst` |

Requests go through Moodle™'s own HTTP client, so your proxy settings apply. Moodle™'s HTTP security settings also apply, so a server on your own network may need adding to the allowed hosts under *Site administration > General > Security > HTTP security*. These settings only appear on Moodle™ 4.4. After an upgrade to 4.5 or later, set up a provider in Moodle™'s AI settings instead.

#### Status check

Dari's settings page shows an **AI provider status** line on every version. It tells you whether Ask Dari and the image tools are ready, or what is missing. Until they are ready, the Ask Dari button and the "Generate with AI" buttons stay hidden. Uploads and colour tools work either way.

### Step 2: Dari's site settings

Go to *Site administration > Plugins > Course formats > Dari*. The settings page is grouped into categories and has its own search box.

**Ask Dari and images**

| Setting | What it does | Default |
|---|---|---|
| `enabletutor`: Turn Ask Dari on | Shows Ask Dari on courses that use Dari. Turn it off and Ask Dari disappears everywhere and nothing is sent. | On |
| `supportcontacts`: Wellbeing contacts | Who Ask Dari tells a learner to contact if they disclose harm or danger. Change it to suit your country and your child-safe or student-support policy. | Teacher or trainer, a trusted adult, 000, Kids Helpline, Lifeline |
| `sendfirstname`: Send learners' first names | Includes the learner's first name so Ask Dari can greet them. Always off for primary-school courses. | On |
| `maxcontextchars`: Course content sent with each question | The maximum number of characters of course text included with each question. Use 6000–8000 for small self-hosted models. | 40000 |
| `shareassessmentanswers`: Send quiz and knowledge-check answers to Ask Dari | *Never share*, *Always share, in every course*, or *Let each course decide*. This is the site ceiling: a course can never share more than this allows. | **Never share** |
| `imagequality`: Image quality | Standard or high definition, for providers that support it. | Standard |
| `imagestyle`: Image rendering | Natural or vivid, for providers that support it (OpenAI DALL·E 3). | Natural |
| `aiscenewriter` (AI art director for images, on by default): Write image scenes with AI | Uses one extra small text request per image to write a specific scene before the image is generated. | Off |

The page also links to the site-wide **Ask Dari Q&A Report**.

**Default display settings**

Most course options have a site **default** (`default<option>`) and a matching **apply to ALL existing courses** override (`force<option>`).

- The **default** only applies to new courses.
- The **override** changes every existing course at once. Set it to *Let each course decide* (or -1, or leave it empty) to stop overriding.

| Area | Settings |
|---|---|
| Look and colour | `fontfamily` (DM Sans, bundled — the default; follow the theme; or one of 31 Google Fonts such as Inter, Roboto, Open Sans, Lato, Montserrat, Poppins, Nunito, Lexend, Atkinson Hyperlegible, Merriweather or Lora, loaded from fonts.googleapis.com), `colourmode` (follow theme / always light / always dark / follow device), `defaultaccentcolour` + `forceaccentcolour`, `defaultcardcolour` / `defaultcardopacity` (+ force), `defaultindexcolour` / `defaultindexopacity` (+ force), `defaultindexheadingcolour`, `defaultindexiconcolour`, `defaultplayerheadercolour` (+ force), `playerlogo` (logo at the top of the course index) |
| Banner | `defaultshowherobanner`, `defaultheroattop`, `defaultherosticky` (+ force), `defaultherobannerfade` (tint when there is no image), `defaultheroimageoverlay` (+ force; darkening behind the title), `scrimstrength` (older fallback) |
| Section cards | `defaultdisplayascards`, `defaultcardlayout` (grid or list), `defaultshowactivitiesoncards`, `defaultcardactivitylimit`, `defaultcardtitlesize`, `defaultcardimagestyle` |
| Inside a section | `defaultactivitydisplaymode` (activity tiles or standard list), `defaultshownavchevrons` |
| Course index | `defaultshowcourseindex` (which page types show it), `defaultplayerindex` (+ force; player sidebar or plain index), `defaultindexstate` (+ force; how it looks on a learner's first visit) |
| Page furniture | `defaulthidesecondarynav` + `forcehidesecondarynav` (course tabs), `defaultcoursenavplace`, `defaulthidegeneral` (+ force), `defaulthidebreadcrumb` (+ force), `defaulthidefooter` (+ force), `defaultimmersive` (+ force; site logo band) |
| Estimated time | `defaultminutes` (one `modname=minutes` line per activity type), `minutesperquestion` (for quizzes), `minutesfallback`, and show/hide for time in the course index, on section cards, on activity cards and as the course total (default + force each) |
| First-visit tour | `tourvoiceover` (read the tour aloud), `tourvoice` (language tag for narration, default `en-AU`) |

---

## Per-course options

Set these in *Course settings > Course format*. Each one starts from the site default.

| Option | Choices |
|---|---|
| `tutoraudience`: Ask Dari audience | **Adult learners** (VET, higher education, workplace), **Secondary school students**, **Primary school students**. This sets Ask Dari's reading level, tone and safeguarding. Primary courses get very simple language, and the learner's first name is never sent to the AI provider. |
| `shareassessmentanswers`: Share assessment answers with Ask Dari | No / Yes. **This only has an effect if the site setting is "Let each course decide".** Default: No. |
| `fontfamily` | The course's font: use the site setting (default), DM Sans, follow the theme, or one of the Google Fonts. |
| `cardimagestyle`: AI card image style | Photographic, Illustration, 3D render, Flat illustration. Images already generated stay as they are until you regenerate them. |
| `showherobanner`, `heroattop`, `herosticky` | Show the banner, put it above the course tabs, keep it on screen while scrolling |
| `herobannerfade`, `heroimageoverlay`, `accentcolour` | Tint with no image (0–100), darkening behind the title (0–100, or -1 for the site setting), accent colour (hex; leave empty to follow the theme's primary colour) |
| `displayascards`, `cardlayout`, `cardtitlesize` | Section blocks or traditional sections, grid or list, title size in px |
| `showactivitiesoncards`, `cardactivitylimit` | List activities on section cards, and how many (0 = all) |
| `activitydisplaymode` | Activity tiles or standard Moodle™ list |
| `shownavchevrons` | Previous/next arrows on activity pages |
| `showcourseindex` | Which pages show the course index: none, home, section, activity, or any combination |
| `playerindex`, `indexstate` | Player sidebar or plain index; remember / start collapsed / start open |
| `hidesecondarynav`, `coursenavplace`, `hidegeneral`, `hidebreadcrumb`, `hidefooter`, `immersive` | Show / hide from students / hide from everyone (tabs can also move into the site header for staff) |
| `hidetimeindex`, `hidetimesectioncards`, `hidetimeactivitycards`, `hidetimetotal` | Show or hide each estimated-time display |
| `indexheadingcolour`, `indexiconcolour`, `indexcolour`, `indexopacity`, `cardcolour`, `cardopacity`, `playerheadercolour` | Colours (hex) and strength (0–100) |

Each section's settings form also has a **section banner image** upload.

---

## Capabilities

| Capability | What it allows | Default roles |
|---|---|---|
| `format/dari:view` | See Dari's course view (hero banner, section cards) | Guest, student, teacher, editing teacher, manager |
| `format/dari:useaitutor` | Use Ask Dari. Carries `RISK_PERSONAL`, because questions are sent to the school's AI provider and stored. | Student, teacher, editing teacher, manager |
| `format/dari:viewreport` | View the course Ask Dari report, which shows other users' questions. `RISK_PERSONAL`. | Teacher, editing teacher, manager |
| `format/dari:correctresponses` | Write a correction on an Ask Dari answer. `RISK_XSS`, `RISK_PERSONAL`. | Teacher, editing teacher, manager |

Guests can never use Ask Dari.

---

## AI and privacy

**Where the data goes.**

- **On Moodle™ 4.5 and later**, every AI request goes from your Moodle™ site, through Moodle™'s AI subsystem, to the AI provider your administrator has configured.
- **On Moodle™ 4.4**, requests go directly from your Moodle™ site to the service entered under *AI connection (Moodle™ 4.4)*.

Either way, your organisation's own account and API key are used, and the data goes to that service under your agreement with it. **Nothing is sent to Dari Learning or to any other service run by the plugin's maker.**

**What Ask Dari sends** with each question:

- the question
- the learner's first name (unless turned off, and never for primary-school courses)
- the names of the course, section and activity they are in
- the quiz question they are on, if any
- a short summary of what they asked before in that activity
- their last few questions and answers (up to four, from the last hour)
- up to five teacher corrections for that activity
- the text content of the course, up to `maxcontextchars`

Hidden activities, and activities restricted from the learner, are never used as context.

**What image generation sends:**

- the course name and summary, or the card's section or activity title and description
- the course's art style
- any detail the teacher typed in
- with the scene writer on, one extra text request that turns those details into a scene

No learner data is included in image prompts.

**Answer keys stay in Moodle™ by default.** Unless an administrator allows it, the course index sent to the provider leaves out:

- correct-answer markers
- answer options and per-option feedback
- slide answers
- essay "information for graders"

Ask Dari still sees each question's wording, so it can discuss the topic and point learners to the right material.

**Governance:**

- **Moodle™ 4.5 and later:**
  - Users must accept Moodle™'s AI usage policy before any request. This is checked on the server as well as in the browser.
  - Every request is recorded in Moodle™'s AI usage report.
  - Moodle™ 5.x per-course and per-activity AI switches are respected.
- **Moodle™ 4.4:** there is no Moodle™ AI usage policy or AI usage report. Ask Dari's own reports still log every question and answer. If you need a policy acknowledgement, use your site's usual policy tools.
- **All versions:**
  - AI calls are rate limited per user per course.
  - Capabilities control who can use Ask Dari.
  - Activities tagged `ai-tutor-off` are excluded.

**Stored by Dari:**

- Ask Dari conversations (question, answer, rating, any teacher correction)
- a short rolling summary per user per activity of topics they asked about
- teacher-set activity time estimates
- teacher-chosen card colours
- a preference recording whether a user has seen the tour

**Privacy API.** Dari implements Moodle™'s Privacy API. It declares the data it sends to `core_ai` (and, on Moodle™ 4.4, to the directly configured AI service) and exports and deletes Ask Dari conversations, corrections and Ask Dari memory for data requests.

---

## Academic integrity

- **No assessment answers.** Ask Dari will not give answers to the course's quiz, knowledge-check or assessment questions. It will not write paragraphs, plans or rewrites a learner could hand in, even if the learner says it is urgent, "just an example", or that they are a teacher. It can still explain ideas, give hints and give feedback on the learner's own work.
- **Hint ladder.** When a learner asks for an answer, Ask Dari gives only the next step:
  1. Ask what they already think, and point to the section that covers it.
  2. Name the key idea, and ask a leading question.
  3. Work through a similar example with different details, and ask them to apply it.

  It never goes past step 3.
- **Lockouts.** Ask Dari gives a fixed reply, and sends nothing to the AI provider, when:
  - a learner is in a graded quiz attempt
  - an assignment has already been submitted (reflection only until it is marked or reopened)
  - an activity has the tag **`ai-tutor-off`**
- **Practice questions are new.** Ask Dari writes its own practice questions and never copies or rewords the course's assessment questions.
- **Resistant to prompt injection.** Text in course material or a learner's message is treated as information, never as instructions.
- **Reports.** Answers where Ask Dari guided the learner without giving the answer are flagged in the reports as evidence the rules are working.

Teachers (users who can update the course) get full answers. The integrity rules apply to learners.

---

## Guided tours

The first time someone opens a Dari course, they are offered a short walkthrough. Teachers and learners get different tours.

- The **teacher tour** covers the banner, AI banner generation, the completion ring, the course map, section blocks, icons, the player panel, time estimates, Ask Dari, "Tutor insights" (the Ask Dari report) and where the settings are.
- The **learner tour** covers progress, the course map, sections, time, done and to-do, grades and Ask Dari.

Narration uses the browser's speech voice in the language set by `tourvoice`. Learners and teachers can mute it, and their choice is remembered.

---

## Reports

- **Ask Dari Report** for each course (course menu; needs `format/dari:viewreport`):
  - **Course Content** tab: what Ask Dari can read for each activity.
  - **Chat History** tab: every question and answer, with filters by student, group and rating, plus learner ratings and teacher corrections.
- **Site-wide Ask Dari Q&A Report** (linked from Dari's settings page; administrators only):
  - totals: questions, rated helpful, guided without giving the answer, active courses, active students
  - filters: course, student, rating, response type and date
  - **CSV export**

---

## Accessibility

Dari is designed to meet WCAG 2.2 AA:

- **Contrast:** text and background pairs in the design tokens are checked for contrast. Banner overlays keep the title readable on any image.
- **Keyboard:** full keyboard operation with a visible focus ring. Drag-and-drop uses Moodle™'s keyboard-accessible move dialogues.
- **Screen readers:** accessible names on every control, and live announcements for progress and status changes.
- **User preferences:** respects `prefers-reduced-motion`, `prefers-contrast: more` and Windows high contrast (`forced-colors`).
- **Layout:** rem-based type that follows the browser's font size, with a 12px minimum. The layout reflows down to 320px without horizontal scrolling.
- **Dark mode:** a full token-level dark theme.

---

## Backup and restore

Course backups include:

- the course banner, section banners, and section and activity card images
- card colours
- course and section format options, such as section icons, which Moodle™ core handles

Ask Dari conversations are user data and are not part of course backups.

---

## Uninstalling

1. Change any courses that use Dari to another course format.
2. Go to *Site administration > Plugins > Plugins overview*, find **Dari** under course formats and choose **Uninstall**.

Uninstalling removes Dari's settings and its database tables, including Ask Dari conversations, Ask Dari memory, time estimates and card colours. Download any reports you want to keep first. Records in Moodle™'s own AI usage log (Moodle™ 4.5 and later) are not affected.

---

## Support

- Email: **miss.darika2533@icloud.com**
- Website: https://darilearning.com

If something looks wrong on a theme other than Boost or Academi, tell us which theme you use. Most of these turn out to be small differences we can support.

---

## Licence

Dari is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version. See `LICENSE`.

© 2026 Dari Learning
