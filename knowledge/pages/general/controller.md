<!--
id: controller
tags: ''
-->

# Controller and Configuration

> As a matter of convention, I like to create an executable file as  _./bin/perms_ in any application I work on, as the single point of action to assign proper file permissions.

1. First, create the controller file:

   ```shell
   j a
   mkdir -p bin/config
   touch bin/perms
   chmod u+x bin/perms
   ```
3. Now paste these contents into _bin/perms_:

   ```shell
   #!/usr/bin/env bash
   s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)
   chmod u+x $__DIR__/../opt/aklump/easy-perms/bin/easy-perms
   $__DIR__/../opt/aklump/easy-perms/bin/easy-perms "$__DIR__/config/perms.yml" "$@"
   ```
4. Now create the configuration

   ```shell
   j a
   cp opt/aklump/easy-perms/init/perms.yml bin/config/perms.yml
   ```
5. Open and edit _bin/config/perms.yml_ as necessary.

## Multiple and/or Local Configuration

1. It is possible to pass multiple configuration paths to the controller, which are merged together. This is how you can have environment-specific overrides, e.g.

   ```bash
   $__DIR__/../opt/aklump/easy-perms/bin/easy-perms "$__DIR__/config/perms.yml" "$__DIR__/config/perms.local.yml" "$@"
   ```
2. Do not commit _perms.local.yml_
3. Use it for local-only, additional permissions.

## Other Highlights

* Both YAML and JSON is supported.
* See _json\_schema/config.schema.json_ for details of the configuration.
