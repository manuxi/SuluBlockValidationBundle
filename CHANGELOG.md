# Changelog

All notable changes of this bundle. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the versions follow [Semantic Versioning](https://semver.org/).

## [1.1.1] - 2026-10-02

### Changed

- Documentation only: the README (English and German) has an example with a before/after table and a compatibility table, and links to this changelog.

## [1.1.0] - 2026-10-02

### Added

- Admin JavaScript (optional): after a failed save, the blocks that contain an invalid field open up, so the red marks are visible. Only blocks with an error open, one nesting level after the other; a block that was collapsed again opens again with the next failed save. See `docs/expand-invalid-blocks.en.md`.
- `composer test` also runs the logic test of the JavaScript (`node`), the GitHub workflow runs it.
- README: example, compatibility table, changelog.

## [1.0.0] - 2026-10-02

### Added

- Conditional validation of the admin forms: mandatory fields and block entries are only checked where their `visibleCondition` shows them. A hidden field may be missing or empty, a hidden block is not checked at all, a field in a hidden section follows the section.
- Supported conditions: `==`, `!=`, `in [...]`, `!x`, `!(...)`, `AND`/`&&`, `OR`/`||`, `false`, and at the top level of a form names without `__parent.`. A condition that is not understood leaves the field required as before.
- Command `sulu:block-validation:schema` prints the JSON schema of a template form, or validates data against it (`--check`, needs `opis/json-schema`).
