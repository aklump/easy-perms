# Easy Perms

![Banner](images/easy-perms.jpg)

Add this to a project to be able to easily manage file and directory permissions.

The files in a web app are likely to require writeable and/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

## Installation (w/Composer)

1. Because this is an unpublished package, you must define it's repository in
   your project's _composer.json_ file. Add the following to _composer.json_ in
   the `repositories` array:
   
    ```json
    {
        "type": "github",
        "url": "https://github.com/aklump/easy-perms"
    }
    ```

2. Require this package:
   
    ```
    composer require aklump/easy-perms:^0.0
    ```

1. Proceed to [installing the controller](@controller).

**For security, install this above web root, and not in a location accessibly by the web.**

### Controller and Configuration Files

1. Copy the correct controller and commit to source control.
2. The correct controller is _init/controller.sh_ or if you used `create-project` then you must use _init/controller--create-project.sh_.

```shell
mkdir -p ./bin/config
cp ./vendor/aklump/easy-perms/init/controller.sh ./bin/perms
chmod u+x ./bin/perms
cp ./vendor/aklump/easy-perms/init/perms.yml ./bin/config/perms.yml
```

## Alternative Stand-Alone Installation

If `composer require` fails, that is, if the dependencies of this project conflict with your application, you should install this using `composer create-project`, which creates a stand-alone installation. Copy and paste the following code, executed most likely from your
repository root, and certainly above web root.

```shell
composer create-project aklump/easy-perms:^0.0 --repository="{\"type\":\"github\",\"url\": \"https://github.com/aklump/easy-perms\"}"
```

The controller and configuration is altered slightly to the following:

```shell
mkdir -p ./bin/config
cp ./easy-perms/init/controller--create-project.sh ./bin/perms
chmod u+x ./bin/perms
cp ./easy-perms/init/perms.yml ./bin/config/perms.yml
```

With this method the only update path is to remove and then reinstall aklump/easy-perms, repeating the `create-project` process.
**Running `composer update` will only update the dependencies and not aklump/easy-perms itself.**

## Configuration

1. Open _bin/config/perms.yml_, familiarize yourself with it, then make adjustments as necessary.
2. Add paths and path globs to each of: `default, readonly, writeable, executable` as is appropriate to your project.

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

### Things To Note

* If a directory does not have execute permissions, then you cannot change permissions on it's contents.
* **Symlinks may cause some unexpected output** depending upon how you write your configuration. More specifically it may appear that the same file keeps having the perms set. This is not to worry and things are most likely working correctly on the backend.
