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
