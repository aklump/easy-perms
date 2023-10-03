# Easy Perms

Add this to a project to be able to easily manage file permissions.

The files in a web app are likely to require writeable and/or executable permissions on some files, but not most. It's time-consuming and may be confusing to keep these in order. This tool allows you to set a baseline and then be only as permissive as necessary. The management is simply to add paths or globs to a YAML or JSON file and then run the script.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

## Configuration

* Both YAML and JSON is supported.
* See _json\_schema/config.schema.json_ for details of the configuration.
* Notice globs are supported. So are `/**/` doubles.
* **Directories are ALWAYS recursive.**  Don't be confused because, you will not see the recursive files in the output.
* Also, the same and same number of files are handled by `../..` and `../../*`; however with the second example you will see the glob-matched filenames in the output, so it may be more desirable to use that format.

_./bin/config/perms.yml_

```yaml
default:
  - ../../*
writeable:
  - ../../web/sites/*/files
executable:
  - ../../vendor/bin/*
```

## Usage

Here is an example for a simple Drupal project with the following structure, using the configuration as shown above.

```text
.
├── bin
│   └── config
│       └── perms.yml
├── vendor
│   └── bin
│       └── perms.php
└── web
```

Apply permissions like this:

```shell
./vendor/bin/perms.php ./bin/config/perms.yml
```

## What Are the Permission Values?

* See `\AKlump\ProjectPermissions\DefaultFilePermissions` and `\AKlump\ProjectPermissions\DefaultDirectoryPermissions` for default values.
* To override these values add any or all of following to your configuration file:

```yaml
file_permissions:
  default: '0640'
  writeable: '0640'
  executable: '0740'
directory_permissions:
  default: '0750'
  writeable: '0770'
  executable: '0750'
```

## Troubleshooting

This tool may reveal invalid symlinks, which you will see in the error output at the end, and should be deleted to avoid further errors.
