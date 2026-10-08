# Contributing to Dari Course Format

This repository contains the `format_dari` plugin, not the marketing website.
Install a working copy at `course/format/dari` in a Moodle™ development site.
Do not run it as a standalone PHP application.

## Development

- Follow Moodle™ coding standards, component namespaces, and the existing architecture.
- Use language strings for interface text.
- Check login, context, capabilities, and sesskey requirements on server-side actions.
- Preserve privacy export and deletion support when changing stored data.
- Keep AI requests tied to the administrator-configured provider and policy controls.
- Keep the bundled DM Sans font local by default; document external requests when adding font options.
- Build changed AMD sources with the Moodle™ checkout's supported Grunt task and commit their compiled assets.
- Include tests for regressions. Run PHPUnit and Behat in the appropriate development environment.

The GitHub Actions workflow runs `moodle-plugin-ci` against selected supported
versions and PostgreSQL/MariaDB. A configured workflow is not evidence that it
has passed: review the actual job results.

## Pull requests and releases

Open a focused pull request describing the problem, solution, and test results.
Maintain GPL v3-or-later compatibility and preserve third-party licence notices.
Record user-facing changes and increment the version code before a new release.
Never edit an already distributed version silently.

For a release, package a single top-level `dari/` directory containing the
runtime files, compiled AMD assets, licences, and required bundled resources.
Do not use GitHub's automatically generated source archive as the Marketplace
installation ZIP without checking its directory structure.

Users should acquire the plugin through [Moodle™ Marketplace](https://marketplace.moodle.com/).
The maintainer uploads new versions manually to Marketplace and handles its
automated checks and review.
