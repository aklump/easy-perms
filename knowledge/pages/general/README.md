<!--
id: readme
tags: ''
-->

# Easy Perms

![Banner](../../images/easy-perms.jpg)

Add this to a project to be able to easily manage file and directory permissions.

The files in a web app are likely to require writable and/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and then focuses on setting the correct read, write and execute permissions.

**For security, install this above web root, and not in a location accessibly by the web.**

{{ composer.install }}

## Configuration

1. Use `vendor/bin/easy-perms init` to initialize your project. It will give you the option of copying boilerplate configuration files.
2. You may pass multiple configuration files to `vendor/bin/easy-perms apply easy-perms.yml easy-perms.dev.yml`, so having a dedicated development environment config is a possible strategy. Then in the production environment you omit the development config like this: `vendor/bin/easy-perms apply easy-perms.yml`

{{ snippet.easy_perms_yml|fenced }}
{{ snippet.easy_perms_dev_yml|fenced }}

1. Make adjustments as necessary.
4. Add paths and path globs to each of: `default, readonly, writable, executable` as is appropriate to your project.

### Multiple Configuration Files

When you pass more than one configuration file, **the order matters**. Configuration files are processed in the order given, and each file is processed only after the previous one has been fully processed. A later file supersedes an earlier one.

```shell
vendor/bin/easy-perms apply easy-perms.yml easy-perms.dev.yml
```

The above is processed like this:

1. Load the default permission values.
2. Merge _easy-perms.yml_.
3. Calculate permissions for the paths listed in _easy-perms.yml_.
4. Merge _easy-perms.dev.yml_ over the result of step 2.
5. Calculate permissions for the paths listed in _easy-perms.dev.yml_.
6. Apply the calculated permissions.

Two consequences follow from this:

* **A later file wins for any path both files target.** If _easy-perms.yml_ lists `foo.php` as `readonly` and _easy-perms.dev.yml_ lists it as `writable`, the file ends up writable. This is what makes a development config able to loosen a production baseline.
* **A later `file_permissions` or `directory_permissions` override is not retroactive.** It applies to the paths listed in that file and in any file after it — not to paths already calculated by an earlier file. See _What Are the Permission Values?_ for an example.

### Pattern Syntax

* The pattern matching rules are the same as used in [gitignore](https://git-scm.com/docs/gitignore#_pattern_format)
* Double asterix is supported, e.g. `/foo/**/*.php`.
* If the path ends in a forward-slash `/` then only directories are matched, e.g. `/foo/bar/*/`
* If the path does not end in a forward slash then both files and directories are matched, e.g. `/foo/bar/*`

* Use this tool to visualize your configuration.: <https://www.digitalocean.com/community/tools/glob>

## Usage

1. Use `vendor/bin/easy-perms apply bin/config/easy-perms.yml bin/config/easy-perms.dev.yml` to apply the permissions in a dev environment.
2. Use `vendor/bin/easy-perms apply bin/config/easy-perms.yml` to apply the permissions in a production environment.
3. Use `vendor/bin/easy-perms optimize bin/config/easy-perms.yml` to consolidate paths in your configuration file.
4. Or use the controller `bin/apply-perms.sh` to make applying permissions easy.

## Controller

1. Once again use `vendor/bin/easy-perms init` to initialize your project. Skip the configuration step, but use the controller step to copy the file. _Note: the controller file is optional, and should be modified or discarded as you see fit._

{{ snippet.apply_perms_sh|fenced }}

To apply the configured permission to your project at any time, execute the controller like this:

```shell
$ bin/apply-perms.sh -v
Checking bin/bind_book.sh
Checking bin/easy-perms
Checking bin/run_unit_tests.sh
Checking vendor/bin/phpunit
0770 🛠  easy-perms
0770 🛠  bin/bind_book.sh
0770 🛠  bin/run_unit_tests.sh
0770 🛠  vendor/bin/phpunit
Completed in 0.01 seconds.
Permission setting was successful.
```

Paths will print only if their permissions were changed. If you want to see more (as shown above), use the verbose option `-v`.

### Troubleshooting

If permissions are failing to set, try manually resetting all paths to 0755, e.g. `chmod -R 0755 *` (or `sudo chmod -R 0755 *` if necessary) from the application root.

Now execute the controller and the permissions should apply correctly.

More information may be available in the PHP error log, here is an example:

1. Find the path to the php_error.log: `php -i | grep error_log`
2. `tail /Applications/MAMP/logs/php_error.log`

### Things To Note

* If a directory does not have execute permissions, then you cannot change permissions on it's contents.
* **Symlinks may cause some unexpected output** depending upon how you write your configuration. More specifically it may appear that the same file keeps having the perms set. This is not to worry and things are most likely working correctly on the backend.
* A symlinked directory may be matched by either path--the symlink or its target--so a pattern like `web/themes/custom/*/bin/*` works for a theme symlinked out of an install directory. Both paths are the same file, so only one permission can apply to it; when your configuration assigns two, the one calculated last wins, following the same order described under _Multiple Configuration Files_.

{{ composer.sandbox_install }}

{{ funding|raw }}
