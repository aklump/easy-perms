<!--
id: permission_values
tags: ''
-->

# What Are the Permission Values?

* See `DefaultFilePermissions` and `DefaultDirectoryPermissions` for default values.
* To override these default values add something like the following to your configuration:

```yaml
file_permissions:
  default: '0640'
  readonly: '0440'
  writable: '0640'
  executable: '0740'

directory_permissions:
  default: '0750'
  readonly: '0550'
  writable: '0770'
  executable: '0750'
```

## When Overrides Take Effect

Because configuration files are processed in order — each one only after the previous has been fully processed — an override to `file_permissions` or `directory_permissions` is **not retroactive**. It affects the paths listed in the file that declares it, and any file processed after it.

Given `easy-perms apply base.yml dev.yml`:

_base.yml_

```yaml
file_permissions:
  writable: '0664'
writable:
  - base.php
```

_dev.yml_

```yaml
file_permissions:
  writable: '0600'
writable:
  - dev.php
```

The result is:

| Path       | Mode   | Why                                                        |
|------------|--------|------------------------------------------------------------|
| _base.php_ | `0664` | The mode in effect when _base.yml_ was processed.           |
| _dev.php_  | `0600` | _dev.yml_ raised the override before its own paths ran.     |

To change the mode of _base.php_ too, either list it again in _dev.yml_ or move the override into _base.yml_.
