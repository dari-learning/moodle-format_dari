# Dari Course Format — Marketplace submission pack

## Copy-ready identifiers and links

| Field | Value |
|---|---|
| Plugin name | Dari Course Format |
| Provider | Dari Learning |
| Plugin type | Course format |
| Frankenstyle component | `format_dari` |
| Version | `2.1.0` |
| Version code | `2026100906` |
| Repository | https://github.com/dari-learning/moodle-format_dari |
| Bug tracker | https://github.com/dari-learning/moodle-format_dari/issues |
| Documentation | https://darilearning.com/docs |
| Website | https://darilearning.com |
| Licence | GNU GPL v3 or later |
| Package-declared compatibility | Moodle™ 4.4–5.3 |
| Public acquisition link until approval | https://marketplace.moodle.com/ |

Do not claim an approved listing URL or compatibility verified by this
repository setup. Confirm the website documentation is current and reachable
before submitting.

## Short description

Visual course cards, a course player, progress tracking, Ask Dari (a course-aware AI study assistant) and AI-generated course images, all in one Moodle™ course format.

## Full description

## Dari Course Format

Dari turns any Moodle™ course into a clear, visual learning experience. Learners see where they are, what's next and how far they've come. Teachers get modern course design without writing code.

### For learners
- **Course cards.** Sections and activities appear as image cards with progress, estimated time and completion status.
- **Course player.** Move through sections and activities in order, with a course index always to hand.
- **Progress at a glance.** Progress rings, progress bars and activity numbering on every page.
- **Ask Dari study assistant.** Explanations, worked examples and interactive practice questions drawn from the course's own content.
  - It guides learners with hints instead of giving assessment answers.
  - It pauses automatically during graded quizzes and after an assignment is submitted.

### For teachers
- **Banners, images and icons.** A custom banner, card images, icons and card colours for each section and activity.
- **AI course images.** Dari plans every image for the course together, so each card shows its own topic instead of a generic stock scene.
  - Activity cards show what the learner actually does there, such as preparing for an exam, practising, discussing or submitting work.
  - Teachers can preview the plan and every image prompt before any image is generated.
- **Estimated times and guided tours.** Set estimated times, and walk learners through the course with guided tours.
- **Ask Dari report.** Review learners' questions, see their ratings, and correct any answer. Ask Dari then follows your correction from then on.

### For administrators
- **Branding.** Follows your site's primary colour, with optional overrides, light and dark modes, and a locally bundled font.
- **Reporting.** Site-wide Ask Dari reporting with CSV export, and an image-generation log with timings for each step.
- **Privacy.** Full Privacy API support covering export and deletion of everything the plugin stores.

### AI requirements
- **Your own provider.** AI features use your site's own AI provider: Moodle's AI subsystem on Moodle 4.5 and later, or an OpenAI-compatible connection you configure on Moodle 4.4. Dari runs no hosted AI service of its own.
- **Image models.** Google Nano Banana 2.1 is the default, or GPT Image 2.5 Sunburst. Dari only ever uses the one model you choose.
- **Costs.** AI features may incur your provider's charges.
- **Everything else works without AI.**

### Compatibility
Moodle™ 4.4 to 5.3. GNU GPL v3 or later.

## Release notes (2.1.0)

See [release-notes-2.1.0.md](release-notes-2.1.0.md). Paste it into the version's release notes field. It covers every change since 2.0.0.

## External services and privacy disclosures

- AI requests go to the institution's configured provider. Ask Dari sends the
  course content index and the learner's question; image generation sends section
  and activity titles and descriptions. Review the package's privacy provider and
  the documentation for exactly which context/data is sent, retention,
  permissions, export, and deletion controls.
- The image diagnostics log (`format_dari_imagelog`) stores image-job timings and,
  for course editors only, browser errors on Dari pages. It is kept 30 days, can
  be turned off in the plugin settings, and is covered by the privacy provider.
- DM Sans is bundled locally by default. Selecting a Google Font creates
  browser requests to Google Fonts; this must be disclosed to administrators.
- Include the GPL licence, `thirdpartylibs.xml`, and `fonts/OFL.txt`.
- Do not upload real learner transcripts or identifiable learner screenshots
  with the listing.

## Installation summary

1. Obtain the approved ZIP through Moodle™ Marketplace when available.
2. Back up and test in staging.
3. Open **Site administration → Plugins → Install plugins** and upload the ZIP.
4. Complete the site's admin notifications and configure Dari.
5. Change a test course's format to Dari and verify learner/teacher behaviour.
6. Configure an AI provider only if AI functionality is required.

## Submission checklist

Prepared in the repository:

- [x] Public source repository and issue tracker.
- [x] Supplied plugin source, compiled AMD assets, privacy provider, and tests.
- [x] GPL and third-party font licence files.
- [x] Issue forms, support/contribution guidance, and private security reporting.
- [x] CI configuration for selected Moodle™ versions and two database types.
- [x] Genuine supplied screenshots in `docs/screenshots/`.
- [x] Source for 2.1.0 on `main` (release 2026100906).
- [ ] Create the `v2.1.0` tag and GitHub release (Releases, then Draft a new release, then new tag `v2.1.0` on `main`).

Maintainer actions still required:

- [ ] Attach the correctly structured original installation ZIP to the draft release and verify its checksum.
- [ ] Review the first CI results and fix any failures; rerun affected checks.
- [ ] Confirm supported-version claims with installation/upgrade tests.
- [ ] Verify backup/restore, uninstall, privacy export/deletion, roles, accessibility,
  mobile behaviour, and AI-provider configuration in real test environments.
- [ ] Verify the website and documentation reflect 2.1.0, including the image engine
  (Google Nano Banana 2.1 default), Image plan preview, diagnostics log, primary-colour
  and Google Fonts details.
- [ ] Read the current Marketplace submission guidelines while signed in.
- [ ] Choose free/paid listing terms and confirm provider/legal details in Marketplace.
- [ ] Upload the original correctly structured `dari/` ZIP and complete Marketplace
  archive validation and plugin CI checks.
- [ ] Add description, documentation/setup details, compatibility, and screenshots.
- [ ] Submit for review and address any requested changes.
- [ ] After approval, finish the listing and publish it in Marketplace.
- [ ] Only then replace the website's generic Marketplace link with the approved listing URL.

## Current process references

The official Moodle™ Plugins FAQ describes manual ZIP upload, automated archive
and plugin CI checks, a review queue, and provider publication after review:

- https://docs.moodle.org/en/Plugins_FAQ
- https://moodle.atlassian.net/wiki/spaces/MMPD/pages/3498180610/Plugin+submission+guidelines
- https://moodle.atlassian.net/wiki/spaces/MMPD/pages/3976560653/Quick+guide+to+listing+a+plugin+on+Moodle+Marketplace

The detailed guidelines require sign-in in this environment and were not
fully verified here. The old Plugins Directory checklist is legacy guidance,
not a replacement for the current Marketplace requirements.

Marketplace version upload through the old release API is not currently
available according to that FAQ. Do not configure a legacy token-based
automatic Marketplace publishing workflow.
