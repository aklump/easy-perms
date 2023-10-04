<!--
id: controller
tags: ''
-->

## Controller and Configuration

1. As a matter of convention, I like to create an executable file as  _./bin/perms_ in any application I work on, as the single point of execution for correcting permission issues. With that in mind...
2. First, create the controller and config files:

   ```shell
   j a
   mkdir -p bin/config
   touch bin/perms
   touch bin/config/perms.yml
   touch bin/config/perms.local.yml
   chmod u+x bin/perms
   ```
3. Now paste these contents into _bin/perms_:

   ```shell
   #!/usr/bin/env bash
   s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)
   
   $__DIR__/../opt/aklump/easy-perms/vendor/bin/easy-perms "$__DIR__/config/perms.yml" "$__DIR__/config/perms.local.yml" "$@"
   ```
