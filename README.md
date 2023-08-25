# Configuration

Add this to a project to be able to easily manage file permissions.

The files in a web app are likely to require writeable and executable permissions on some files, but not most. This tool allows you to set a baseline and then be only as permissive as necessary. The management is simply to add paths or globs to a YAML or JSON file and then run the script.

**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.

#* Both YAML and JSON is supported.
* See _json_schema/config.schema.json_ for details of the configuration.

_./bin/config/perms.yml_

```yaml
default:
  - ../..
  - ../../*
writeable:
  - ../../web/sites/*/files
  - ../../web/sites/*/files/*
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
* To override these values add something like the following to your configuration file:

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
