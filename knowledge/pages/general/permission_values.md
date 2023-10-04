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
  writeable: '0640'
  executable: '0740'

directory_permissions:
  default: '0750'
  readonly: '0550'
  writeable: '0770'
  executable: '0750'
```
