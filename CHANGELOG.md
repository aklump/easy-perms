# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.22] - 2026-07-17

### Removed

- **BREAKING CHANGE**: The `writeable` configuration key has been removed in favor of the canonical `writable`. Using `writeable` in any configuration file will now throw a `RuntimeException`.

## [0.0.21] - 2026-07-17

### Fixed

- Fixed a bug where configuration defaults were incorrectly merged into every file load, causing them to supersede actual settings when multiple files (e.g., `easy-perms.yml` and `easy-perms.dev.yml`) were used.
- Improved octal value handling in configuration merging to ensure consistency when YAML parsers interpret permissions as integers.
