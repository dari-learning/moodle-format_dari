# Dari Course Format — Marketplace submission pack

## Copy-ready identifiers and links

| Field | Value |
|---|---|
| Plugin name | Dari Course Format |
| Provider | Dari Learning |
| Plugin type | Course format |
| Frankenstyle component | `format_dari` |
| Version | `1.0.5` |
| Version code | `2026100805` |
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

Dari makes Moodle™ courses visual and easy to navigate with course cards, a
course player, progress displays, and optional course-aware AI study support.

## Full description

Dari Course Format by Dari Learning turns courses into a clear, visual learning
experience. Teachers can organise section and activity cards, choose images and
icons, set estimated times, customise banners, and guide learners with tours.
Learners can navigate course, section, and activity pages through a course index
and course player.

Appearance can follow the site's primary colour, with optional overrides,
light/dark modes, locally bundled DM Sans, and optional Google Fonts.

Ask Dari offers course-aware explanations, practice questions, and real-world
examples. Academic-integrity controls guide learners instead of completing
assessments. Teachers can review conversations, ratings, and corrections;
administrators have site reporting and CSV export.

AI-generated banners and card images use the site's configured provider.
On Moodle™ 4.5 and later, requests use its AI subsystem. On Moodle™ 4.4, Dari
uses an administrator-configured OpenAI-compatible connection. AI features need
a configured provider and may incur that provider's charges. Non-AI features
work without an AI provider. Dari does not operate a separate hosted AI service.

## External services and privacy disclosures

- AI requests go to the institution's configured provider. Review the package's
  privacy provider and the documentation for exactly which context/data is sent,
  retention, permissions, export, and deletion controls.
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
- [x] Tagged source and draft release record.

Maintainer actions still required:

- [ ] Attach the correctly structured original installation ZIP to the draft release and verify its checksum.
- [ ] Review the first CI results and fix any failures; rerun affected checks.
- [ ] Confirm supported-version claims with installation/upgrade tests.
- [ ] Verify backup/restore, uninstall, privacy export/deletion, roles, accessibility,
  mobile behaviour, and AI-provider configuration in real test environments.
- [ ] Verify the website and documentation, including the 1.0.5 primary-colour
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
