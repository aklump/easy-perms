# Easy Perms

![Banner](images/easy-perms.jpg)

Add this to a project to be able to easily manage file and directory permissions.

The files in a web app are likely to require writeable and/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

**For security, install this above web root, and not in a location accessibly by the web.**

##        Installation (w/Composer)

1. Installation requires explicit repositories:
   
   ```shell
   composer config repositories.9024aba2baa64b08169c0ca94a832bc7 composer https://packages.intheloftstudios.com
   composer config repositories.d420c4664ef4513f6bec1e47bd4c5144 github https://github.com/aklump/easy-perms
   ```

2. Require the latest stable version:
   
   ```shell
   composer require aklump/easy-perms:^0.0
   ```
3. ... or require the dev channel:
   
   ```shell
   composer config minimum-stability dev
   composer require aklump/easy-perms:@dev
   ```

## Configuration

```shell
touch./bin/perms
chmod u+x ./bin/perms
mkdir -p ./bin/config
touch ./bin/config/perms.yml
```

**File:** `controller.sh`

```bash
#!/usr/bin/env bash

# @file
# Place this in ./bin/perms to create a configured controller for aklump/easy-perms

s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P
"$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] &&
s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

base="$__DIR__/../"
[[ -d "$base/.easy-perms" ]] && base="$base/.easy-perms"
base="$(cd "$base" && pwd)"

chmod u+x "$base/vendor/bin/easy-perms"
"$base/vendor/bin/easy-perms" apply "$__DIR__/config/easy-perms.yml" "$@"
```
**File:** `perms.yml`

```yaml
file_permissions:
  default: 0644
  readonly: 0444
  writeable: 0666
  executable: 0744
directory_permissions:
  default: 0755
  readonly: 0555
  writeable: 0777
  executable: 0755
default:
#  - ../../**
readonly:
# - ../../web/default/settings*.php
writeable:
# - ../../private/default/files/*
# - ../../web/sites/default/files/*
executable:
# - ../../vendor/bin/*
```

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

## Sandbox Installation (w/Composer)

A sandboxed installation separates this project, its dependencies, and even the PHP version from your main application. The project is installed in an isolated, hidden subdirectory in the root of your project. This is particularly useful if you have conflicts with your application's dependencies or if you need to use a different PHP version. It functions similarly to [asdf](https://asdf-vm.com/) shims, providing a project-specific environment for the tool.

### Install in your Project

1. Create the isolated directory (the sandbox):
   
   ```shell
   mkdir -p .easy-perms
   cd .easy-perms
   composer init -n --name=sandboxed/easy-perms
   composer config version dev-main
   echo "vendor/" > .gitignore
   touch README.md
   echo "Learn more: https://github.com/aklump/easy-perms\n" >> README.md
   echo "Execute with: .easy-perms/vendor/bin/easy-perms\n" >> README.md
   ```

1. Installation requires explicit repositories:
   
   ```shell
   composer config repositories.9024aba2baa64b08169c0ca94a832bc7 composer https://packages.intheloftstudios.com
   composer config repositories.d420c4664ef4513f6bec1e47bd4c5144 github https://github.com/aklump/easy-perms
   ```

2. Require the latest stable version:
   
   ```shell
   composer require aklump/easy-perms:^0.0
   ```
3. ... or require the dev channel:
   
   ```shell
   composer config minimum-stability dev
   composer require aklump/easy-perms:@dev
   ```



### Sandbox Usage

1. Call `.easy-perms/vendor/bin/easy-perms` to execute.

### Sandbox Updates

Updating your sandboxed installation requires an extra step beyond your main app `composer update`.

```shell
cd .easy-perms && composer update
```

## Support My Open Source Work

If you’ve found this project useful, please consider supporting its ongoing maintenance. Even a small contribution helps fund updates, fixes, and new ideas.


  * [Sponsor on GitHub](https://github.com/sponsors/aklump)

  * [Buy Me a Coffee](https://buymeacoffee.com/aklump)

  * [paypal.com](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Open%20Source%20Sponsorship)
