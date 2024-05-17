<!--
id: readme
tags: ''
-->

# Easy Perms

![Banner](../../images/easy-perms.jpg)

Add this to a project to be able to easily manage file and directory permissions.

The files in a web app are likely to require writeable and/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

{{ composer.install|raw }}

1. Proceed to [installing the controller](@controller).

**For security, install this above web root, and not in a location accessibly by the web.**

### Controller installation

1. Copy the controller and commit to source control.

```shell
cp ./vendor/aklump/easy-perms/init/conroller.sh ./bin/perms
```

## Configuration

1. Copy the config file and commit to source control.

    ```shell
    cp ./vendor/aklump/easy-perms/init/perms.yml ./bin/config/perms.yml
    ```

1. Open the copied file and familiarize yourself and make adjustments as desired.

### Syntax Rules

* The pattern matching rules are the same as used in [gitignore](https://git-scm.com/docs/gitignore#_pattern_format)
* If the path ends in a forward-slash `/` then only directories are matched, e.g. `/foo/bar/*/`
* If the path does not end in a forward slash then both files and directories are matched, e.g. `/foo/bar/*`

* Use this tool to visualize your configuration.: <https://www.digitalocean.com/community/tools/glob>

## Usage

To apply the configured permissions do this:

```shell
./bin/perms
```

In normal mode, you will only see the path display if permissions were changed. If you want to see all paths, use the verbose option `-v`.
