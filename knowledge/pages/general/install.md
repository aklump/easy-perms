<!--
id: install
tags: ''
-->

# Installation

## Install project with Composer at top level.

```shell
$ j a
$ cd install/composer
$ grab easy-perms 
```

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./install/composer/easy-perms"
    }
  ]
}  
```

```shell
j a
composer require --dev aklump/easy-perms:@dev
```

## Install In Isolation Due to Top-level Dependency Conflicts

```shell
j a
cd install/composer
grab easy-perms
j a
mkdir -p opt/aklump/easy-perms
cd opt/aklump/easy-perms
touch composer.json
```

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./install/composer/easy-perms"
    }
  ]
}  
```

```shell
j a
cd opt/aklump/easy-perms
composer require --dev aklump/easy-perms:@dev
```

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
