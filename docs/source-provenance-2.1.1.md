# Dari 2.1.1 source and release provenance

Verified against the live repository on 17 October 2026.

## Supplied package

| Field | Verified value |
|---|---|
| Component | `format_dari` |
| Release | `2.1.1` |
| Version code | `2026100907` |
| Minimum Moodle | `2024042200` (4.4) |
| Declared support | Moodle 4.4–5.3 |
| Archive root | `dari/` |
| File count | 246 files, 4,379,061 uncompressed bytes |
| Original filename | `format_dari_2.1.1_1791471516162.zip` |
| Original ZIP SHA-256 | `590266c9bdda8959c8afe6cb0d6749242d50f371992ca02bf4bf6256bf430f44` |
| Matching source commit | `d0914e4f2d36c1f2a4601c7c738bb54bd8210809` |

Every one of the 246 package files matches that commit byte for byte, including
the README, changelog, source, compiled AMD assets, source maps, fonts and tests.
Repository-only issue forms, CI configuration, submission documentation and
screenshots are not installation-package contents.

The earlier commit `f3ecb86` introduced the 2.1.1 version, but eight of its files
differ from this ZIP. Do not use that earlier commit as this package's source
target. The subsequent CI corrections and removal of generated PHPUnit
configuration are already present in the matching commit.

## Existing source work: do not duplicate or downgrade

The authenticated write connection reports `dari-learning`, matching the
repository owner, with push permission. The repository is
`dari-learning/moodle-format_dari`.

The live `main` branch has already advanced to 2.1.2 (`2026101600`), at commit
`098e18d4d765a49507119b9273549611819f1af4` when inspected. It includes 2.1.1 in
its ancestry and changelog. Importing the ZIP over `main` would remove newer
Marketplace-review corrections, so no source downgrade is proposed.

The local submission source is the supplied 2.1.1 snapshot, not a mirror of
current `main`. Repository-only supporting files are maintained separately.

## Strict CI evidence and failure diagnosis

[The passing 2.1.1 run](https://github.com/dari-learning/moodle-format_dari/actions/runs/37791987994)
tests the exact matching commit above. All four jobs completed successfully:

| Moodle branch | PHP | Database | Result |
|---|---|---|---|
| `MOODLE_404_STABLE` | 8.3 | PostgreSQL | Passed |
| `MOODLE_405_STABLE` | 8.3 | MariaDB | Passed |
| `MOODLE_500_STABLE` | 8.3 | PostgreSQL | Passed |
| `MOODLE_503_STABLE` | 8.3 | MariaDB | Passed |

The existing workflow retains PHP lint, zero-warning Moodle coding standards
and PHPDoc, plugin validation, upgrade savepoints, Mustache, zero-warning
JavaScript/CSS checks, AMD build freshness, PHPUnit with `--fail-on-warning`,
and Behat. No checks, matrix entries or failure thresholds were relaxed here.

Historical failures were resolved in the existing source, not bypassed:

- Direct text requests on Moodle 4.4 now record timing when image diagnostics
  are active, just as the core AI route does.
- The offline direct-image fixture reads the real request's aspect ratio.
- PHPUnit coverage attributes precede adjacent class documentation so older
  Moodle checkers recognise both forms of coverage metadata.
- Moodle 4.4 image assertions check the Google engine's actual size contract,
  rather than the later core AI subsystem's quality/style contract.
- Generated AMD files were rebuilt with the real Moodle compiler; generated
  PHPUnit configuration was removed rather than distributed.

The [earlier failed run](https://github.com/dari-learning/moodle-format_dari/actions/runs/37789718661)
failed only the Moodle 4.4 PHPUnit step; the version-aware assertions above
explain that failure and the later passing result.

[Current 2.1.2 CI](https://github.com/dari-learning/moodle-format_dari/actions/runs/38841241294)
also passed the same four-job matrix. That is separate evidence, not a
replacement for the exact 2.1.1 package's result.

CI uses offline AI fixtures. It does not certify Marketplace approval, live
provider connections, or complete installation/upgrade and privacy testing.

## Release metadata for review only

`releases/2.1.1.json` records a proposed draft release payload and its package
provenance. It is not an automated publishing instruction. Only the nested
`github_release` object is a GitHub API payload.

At inspection there was one historical 1.0.5 draft release, with no uploaded
assets, and the historical `v1.0.5` tag. There was no 2.1.1 release or tag.
The older correction pull request #2 remains open, but its fixes are already
incorporated into the newer source. Preserve those historical records.

No release has been created or published by this update, no tag has been
created or replaced, and no ZIP has been attached or submitted to Marketplace.
If the maintainer approves a release later, recheck existing records, confirm
the exact target commit, upload the original single-root ZIP through an
authorized method, and verify the actual asset list and SHA-256. A release
record alone does not prove its installation ZIP is attached.

Public acquisition remains restricted to an approved Marketplace listing.
No approved listing URL is recorded here; the website must not offer ZIP or
GitHub download buttons while approval is pending.
