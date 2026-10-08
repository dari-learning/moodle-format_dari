# Release record

## 1.0.6 — unreleased CI fixes

- Version code: `2026100806`. The supplied 1.0.5 ZIP and its release tag remain unchanged.
- Correct Moodle coding standards and PHPDoc errors without relaxing validation.
- Refactor JavaScript helpers and rich-text parsing to satisfy the existing lint limits;
  regenerate the complete AMD build and source-map set with the actual Moodle CI
  compiler, including previously stale builds from the supplied package.
- Format CSS, replace embedded data URLs with bundled assets, and remove redundant
  overrides from the final scoped course rules.
- Pin the CSS grammar validator to 3.1.0 so container queries and other valid modern
  syntax are recognised on older Moodle CI toolchains. Keep the rule set and failure
  thresholds unchanged, with positive and negative controls for the validator.
- Link tutor controls to their panel only after the panel exists, and make the
  standalone section-card template example use its editing-mode accessibility context.
- Correct the Behat field locator to the actual activities-on-cards setting label.
- Exercise Moodle 4.4's direct AI HTTP encoder/decoder offline instead of mocking
  a core AI subsystem that does not exist on that version. Use a static availability
  fixture for Moodle 4.5, retain core AI action tests on newer versions, and check
  the appropriate privacy disclosure for each provider path.
- Reset request-local role caches between isolated test requests.

The declared Moodle range is unchanged. This candidate is not a published release;
CI results and manual release checks must be reviewed before distributing it.

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
