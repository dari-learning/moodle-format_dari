# Release record

## 2.1.0

- Version code: `2026100906`

Same code as 2.0.5, released under a new version number so it can be uploaded to Moodle™
Marketplace after 2.0.5. The README and SECURITY notes are brought up to date. The Marketplace
release notes for 2.1.0 cover every change since 2.0.0.

## 2.0.5

- Version code: `2026100905`

Changes since 2.0.4:

- **One image route, the highest-quality one.** The *Image prompt building* setting is removed.
  For every card and banner, the text model writes the final prompt from the card's plan as one
  clear paragraph. If writing fails, the card's own plan is used directly, and the log records why.
  The upgrade deletes the old setting.

## 2.0.4

- Version code: `2026100904`

Changes since 2.0.3 (corrections from the 2.0.3 image-generation review):

- **Activity cards show what the learner does.** New `\format_dari\local\activitypurpose` classifies
  each activity as content, instructions, assessment, practice, discussion, resources, submission,
  feedback, completion or other. The title decides first, then the description (or a page's or
  book's own text), then the module type. "CPA Practice Exam Instructions" is instructions, "Full CPA
  Practice Examination" is assessment, and "Risk assessment in the workplace" is content. An activity's
  planning request carries its purpose, says what that purpose should show, and names its section's
  image so it is not repeated.
- **Faster.** The plan JSON is slimmer:
  no candidates, scores or explanations. Measured on a 16-card course with the same simulated
  provider delays: 2.0.3 took 160 s with one task runner and 58 s with three; 2.0.4 takes 131 s and 48 s.
- **Diagnostics log** (new table `format_dari_imagelog`; setting *Diagnostics log*, on by default).
  Every image job records the time each stage took: cron wait, course plan, item plan, text request,
  image request, save, then done or failed. Browser errors on Dari pages are recorded too. Both are
  shown on *Image plan preview*, with a CSV download and each card at thumbnail size.
- **Reliable concurrency.** Course planning takes a lock and waits at most 10 s. A job that cannot get
  it is queued again 30 s later under the same request id, and no request is charged. A stored plan
  is replaced only by a validated plan, inside a transaction.
- **Job states.** A card moves through queued, planning, generating, saving, then done or failed,
  with a request id and timestamps. An older job never overwrites a newer one. The browser shows the
  real stage. A long wait is reported as "still working", never as a failure. Nothing is retried
  automatically at a cost.
- **Retry or new concept.** On a card that already has an image, the dialog offers *Same idea again*
  (reuses the stored prompt, so only the image request is made) or *Try a new idea* (replans that card
  alone, rejecting the earlier concept). A failed image never changes the concept. Attempts,
  successes and failures are counted separately.
- **Structured diversity check.** Each plan item records an environment category, composition,
  people arrangement and light category, plus subject, action and object. Repeats are detected in
  code, for the whole course and for each single card.
- **Teacher's own description first.** The teacher's description outranks the plan and is kept with
  the card through every regeneration. The dialog shows it for editing or clearing. Changing it
  replans only that card.
- **Prompts.** Prompts are concrete and positive, about 60–130 words. Charts, tables and figures
  are allowed with minimal clean lettering. The negative prompt no longer bans numbers; Moodle's
  providers do not take a negative prompt anyway.
- **Fixes found by browser testing:**
  - a JavaScript error that stopped the card colour dialog from opening;
  - escaped `{$a}` placeholders in three new strings;
  - browser error reports that held a stack trace were rejected.
- **Privacy:** the provider declares, exports and deletes `format_dari_imagelog`.
- **Upgrade:** 2.0.3 plans are discarded and planned again, because they have no purpose and no
  categories.

## 2.0.3

- Version code: `2026100903`

Changes since 2.0.2:

- **AI images are planned before they are written.** Image generation now has two separate
  stages, so a poor image can be traced to its cause:
  1. **Visual plan** (new `\format_dari\local\imageplanner`): the text model acts as a creative
     director and plans every section card and the course banner in one request. For each it
     records the interpretation of the learning topic, the concepts considered, the chosen concept
     and why, a signature visual element, camera perspective, environment, people, lighting and a
     self-score (relevance 40, distinctiveness 25, quality 20, consistency 15). Dari then checks the
     set in code for repeated environments, perspectives and concept types, and for low scores,
     and asks once for the weaker items to be revised.
  2. **Prompt writing**: the final image prompt is written from one plan item.
- No fixed visual formulas: no industry stereotypes, any composition (overhead, macro, wide,
  documentary, close-up, portrait, object-focused) and environments without people. Relevant
  screens, documents and diagrams are allowed with plausible graphical detail and no legible words.
- The fixed tail no longer sets lighting, palette or perspective: only the medium, the accent, the
  framing and the quality safeguards.
- Plans and prompts are stored per course (new table `format_dari_imageplan`). Regenerating a card
  re-plans only that card, with its earlier concept excluded. Activity cards and new sections are
  planned one at a time against the existing plan.
- **Test mode:** *Image plan preview* in the course's More menu (`imageplan.php`) and
  `cli/preview_image_plan.php` show every section's interpretation, concepts, signature element,
  perspective, environment and final prompt without generating any image. A card's first image is
  painted from exactly the prompt shown.
- Cost: one planning request per course, then one prompt request per image (as before).

## 2.0.2

- Version code: `2026100902`

Changes since 2.0.1:

- New **Image engine** setting (`imageengine`), **Google by default**: Nano Banana 2.1
  (`gemini-nano-banana-2.1`, Google's highest rated image model). OpenAI (GPT Image 2.5
  Sunburst) is the other choice. Either way Dari uses only that one model, with no fallback.
- Moodle 5.2+: matches a Google Gemini provider whose Generate image model or endpoint names
  `gemini-nano-banana-2.1`.
- Moodle 4.4: the Google engine calls the Gemini API's generateContent method directly (16:9,
  2K) with a new optional **Image API key** (`directimagekey`; empty uses the API key).
- Status lines and messages name the chosen engine's model.

## 2.0.1

- Version code: `2026100901`
- Minimum Moodle™ version code: `2024042200` (4.4); supported range Moodle™ 4.4–5.3

Changes since 2.0.0:

- **One image model, no fallbacks.** Every banner and card image is generated with GPT Image 2.5
  Sunburst (`gpt-image-2.5-sunburst`), at high quality. On Moodle 4.5+ Dari runs the image request
  on the provider configured with that model only; core's fall-through to other providers is not
  used. If no provider has it, the image tools say so and the settings status line explains what to
  set. On Moodle 4.4 the model is fixed and landscape images are 2048×1152 (16:9, like the cards).
- Removed settings: `directimagemodel` (replaced by an on/off `directimages`), `imagequality`,
  `imagestyle` and `aiscenewriter` (the art director now always runs when a text model exists).
- **Prompting rewritten** to fix boring, off-topic images and artefacts:
  - the art director interprets what the section teaches and shows the real-world moment where it
    is used, instead of copying one "analyst at a desk with screens" example;
  - one focal subject, at most three people and two to four objects (no "visual references" lists);
  - no one sitting at a laptop or monitor, no holograms, floating icons, signs or readable papers;
  - it sees the course's other section titles and the concepts already used, so cards differ;
  - cards no longer ask for title space; banners keep the left third calm for the title.
- Template prompts (sites without a text model) rewritten: no desk or laptop scenes, field props
  without screens, and a course whose name contains "exam" no longer makes untitled sections a quiz.
- Upgrade clears remembered scenes and art direction written by the old prompts.

## 2.0.0

- Version code: `2026100900`
- Minimum Moodle™ version code: `2024042200` (4.4); supported range Moodle™ 4.4–5.3

Changes since 1.0.5:

- AI image prompts rewritten in a single art-directed paragraph: a real person, place, task and
  props for the course's field, references to what the section or activity covers, palette,
  title space and a strict no-text rule.
- The AI art director (the site's text model) now writes the whole prompt, with the template as
  fallback; it is on by default.
- Image dialogs show the prompt used last time and whether the AI art director or the template
  wrote it (new web service `format_dari_get_image_prompt`, requires `moodle/course:update`).
- Moodle 4.4 direct connection defaults: text model `gpt-6-astra`, image model
  `gpt-image-2.5-sunburst`, image quality HD. Sites still on the earlier shipped defaults are
  moved on upgrade; models an administrator chose are kept.
- Image palette falls back to the theme's brand colour when no accent colour is set.
- Section cards about 25% smaller: three across, four on wide screens.
- Background card and banner generation now loads the course library in cron.

## 1.0.5 — initial repository import

- Component: `format_dari`
- Version code: `2026100805`
- Minimum Moodle™ version code: `2024042200` (4.4)
- Package-declared supported range: Moodle™ 4.4–5.3
- Package maturity: stable

This imports the maintainer-supplied 1.0.5 package. It does not invent a
comparison with earlier releases or independently certify compatibility.

The supplied package documents visual course cards and a course player,
theme-primary-colour appearance controls, locally bundled DM Sans and optional
Google Fonts, Ask Dari, AI banner/card images, guided tours, and privacy/reporting
controls.

Repository-only additions include issue forms, contribution and security
guidance, a Moodle™ Plugin CI workflow, submission notes, and screenshots.
Review CI results before publishing the draft release or submitting the ZIP.
