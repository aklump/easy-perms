<!--
id: environments
tags: ''
-->

# Environment Strategy

The cleaner model for managing permissions is to **keep the repository in the permission state that is useful for source control and development**, then apply production permissions **after deployment**, on the deployed copy.

In other words, Git should represent the **source tree**, not the final hardened runtime state.

## Recommended Strategy

1.  **Repository permissions**: Git should store the **source tree** (dev-friendly) permissions.
2.  **Runtime permissions**: Hardened production permissions should be applied **after deployment** on the production copy.

### Configuration Separation

Use two files to separate hardening from ergonomics:

1.  **`easy-perms.yml`**: Production baseline; strictest permissions.
2.  **`easy-perms.dev.yml`**: Development overrides; adds executable bits for scripts.

In development, apply both:
```shell script
vendor/bin/easy-perms apply easy-perms.yml easy-perms.dev.yml
```

In production, apply only the baseline:
```shell script
vendor/bin/easy-perms apply easy-perms.yml
```

## Release Workflow

1.  **Develop**: Use dev-friendly executable bits in your checkout.
2.  **Release**: Commit and tag from a clean working tree.
3.  **Deploy**: Sync release to production.
4.  **Harden**: Apply production permissions on the production copy.

### Production Git Working Trees

If production is a Git checkout, you have two choices:
*   **Option A**: Set `git config --local core.fileMode false` to ignore mode changes on production.
*   **Option B**: Deploy to a non-Git release directory (e.g., `releases/2026-08-03`) and symlink `current` to it.

## Caveat: Timing

Apply production hardening only **after** all deployment scripts (e.g., Composer, database updates) have finished. Hardening too early may block your own deployment process.
