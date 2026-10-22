<!--
id: changelog
tags: ''
-->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.30] - 2026-10-22

### Fixed

- `optimize` can rewrite a read-only configuration file. It wrote the file in place, which failed with a permission warning when the file was read-only, as one locked by easy-perms itself can be. The result is now written to a temporary file and renamed into place, which only needs the directory to be writable, and the file keeps its original permissions.

## [0.0.29] - 2026-10-22

### Fixed

- Works with symfony/console 7. Commands set their names in `configure()` instead of the static `$defaultName` property, which Symfony Console 7 removed; on 7.x every command failed with "cannot have an empty name".
- Patterns now match files reached through a symlinked directory. A directory reachable by two paths was only ever listed under one of them, so a glob such as `web/*/custom/*/bin/*` could not match a theme symlinked out of an install directory, and an explicit path had to be listed instead. The missing paths are derived from the single directory walk, so no extra filesystem scanning is done.
- Where several paths address one inode, by symlink or hard link, the permission applied is the one that won during calculation. Previously whichever path sorted first alphabetically decided the outcome, so a later instruction could be silently dropped.
- `optimize` keeps a path that currently matches nothing. Such a pattern was treated as covered by any glob in the same section and removed, discarding configuration written for paths that do not exist yet--build artifacts, or files created by an install step. Genuine redundancy is still consolidated.
- `HandleMemory` compares the memory limit in bytes. As strings `"1G"` sorts below `"512M"`, so an existing limit of `1G`, `2G` or `-1` (unlimited) was being reduced to 512M, while a limit of `64M` was left too low.

### Changed

- Multiple configuration files are now applied sequentially: each file is processed only after the previous one has been fully processed. A later file still supersedes an earlier one for any path both target.
- As a result, an override to `file_permissions` or `directory_permissions` is no longer retroactive; it applies to the paths listed in the file that declares it and in any file processed after it. Previously the last override found in any file was applied to every path.

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
