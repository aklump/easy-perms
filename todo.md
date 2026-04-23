## Blockers

## Critical

- symlinks and targets should be handled as a single one, not requiring separate config entries, either target or symlink must expand to all paths and apply perms.

## Normal

- document how to fix out of memory problems. `memory_limit = N`; increase N in php.ini
- out of memory error if the config includes a path that does not exist in `executable`.
- if config file doesn't exist ask to create it
- on create; copy from int
- on copy from int; paste the default perms into config so it's transparent to user.

## Backlog

## Notes
