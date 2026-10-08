# Release record

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
