<!--
id: readme
tags: ''
-->

# Easy Perms

![Banner](../../images/easy-perms.jpg)

Add this to a project to be able to easily manage file and directory permissions.

The files in a web app are likely to require writeable and/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

**For security, install this above web root, and not in a location accessibly by the web.**

{{ composer.install }}

## Configuration

```shell
touch./bin/perms
chmod u+x ./bin/perms
mkdir -p ./bin/config
touch ./bin/config/perms.yml
```

{{ snippet.controller_sh|fenced }}
{{ snippet.perms_yml|fenced }}

1. Copy the controller code to `./bin/perms`
2. Copy the default configuration shown above to `./bin/config`.
3. Make adjustments as necessary.
4. Add paths and path globs to each of: `default, readonly, writeable, executable` as is appropriate to your project.

### Pattern Syntax

* The pattern matching rules are the same as used in [gitignore](https://git-scm.com/docs/gitignore#_pattern_format)
* Double asterix is supported, e.g. `/foo/**/*.php`.
* If the path ends in a forward-slash `/` then only directories are matched, e.g. `/foo/bar/*/`
* If the path does not end in a forward slash then both files and directories are matched, e.g. `/foo/bar/*`

* Use this tool to visualize your configuration.: <https://www.digitalocean.com/community/tools/glob>

## Usage

To apply the configured permission to your project at any time, execute the controller like this:

```shell
$ bin/perms -v
Checking bin/bind_book.sh
Checking bin/easy-perms
Checking bin/run_unit_tests.sh
Checking vendor/bin/phpunit
0770 🛠  app.php
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

{{ composer.sandbox_install }}

{{ funding|raw }}
