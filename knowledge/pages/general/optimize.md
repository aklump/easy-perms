<!--
id: optimize
tags: ''
-->

# Config Optimization

The `optimize` command is used to clean up and organize your configuration files. It performs several operations to ensure your configuration remains concise and manageable.

## Key Features

- **Consolidation**: It removes redundant explicit paths that are already covered by glob patterns, favoring the more general glob patterns.
- **Sorting**: It sorts paths alphabetically within each group (e.g., `writable`, `readonly`), ignoring single quotes during the sorting process for consistent results.
- **Backups**: Before making any changes, it creates a backup of your original configuration file with a timestamp suffix.

## Scope

Each configuration file is rewritten from its own contents alone, so:

- **The order you list the files in does not matter.** This is unlike `apply`, where a later file supersedes an earlier one.
- A glob in one file never removes a path from another file, and a glob in one section never removes a path from another section. Coverage is only ever considered within a single section of a single file.
- A path that matches nothing at the moment is left alone, because there is no evidence a glob covers it. This keeps configuration for paths that do not exist yet, such as build artifacts or files an install step creates.

Consolidation reflects the files on disk when you run it. If a glob covers an explicit path today, that path is removed; should the filesystem later change so the glob no longer matches, the permission goes with it. Re-run `apply` after optimizing to confirm the result is what you expect.

## Usage

You can run the optimization as often as you like:

```bash
easy-perms optimize easy-perms.yml
```

You can also use glob patterns to optimize multiple files at once:

```bash
easy-perms optimize *.yml
```

## Reviewing Changes

While the optimization process is automated, it is recommended to:

1. Review the optimized configuration file to ensure it meets your expectations.
2. Verify that the permissions are still applied correctly using the `apply` command.
3. Periodically review and delete the timestamped backup files (e.g., `easy-perms.20230101120000.yml`) once you are satisfied with the changes.
