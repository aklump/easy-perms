# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.24] - 2026-07-20

### Added

- Added `optimize` command to consolidate configuration paths, support glob patterns, and create backups.

### Changed

- Updated `apply-perms.sh` with `--no-dev` and `--help` options, and improved production environment detection.
- Refined configuration loading to support path consolidation.

## [0.0.23] - 2026-07-20

### Changed

- Updated documentation build process and refined PHPUnit configuration.

### Removed

- Removed unused scripts: `bin/bind_book.sh` and `bin/flush_book.sh`.

## [0.0.22] - 2026-07-17

### Removed

- **BREAKING CHANGE**: The `writeable` configuration key has been removed in favor of the canonical `writable`. Using `writeable` in any configuration file will now throw a `RuntimeException`.

## [0.0.21] - 2026-07-17

### Fixed

- Fixed a bug where configuration defaults were incorrectly merged into every file load, causing them to supersede actual settings when multiple files (e.g., `easy-perms.yml` and `easy-perms.dev.yml`) were used.
- Improved octal value handling in configuration merging to ensure consistency when YAML parsers interpret permissions as integers.
