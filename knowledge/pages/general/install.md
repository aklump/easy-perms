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

## Controller File _./bin/perms_
```shell
j a
mkdir -p bin
touch bin/perms
chmod u+x bin/perms
```


```shell
#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

$__DIR__/../opt/aklump/easy-perms/vendor/bin/easy-perms "$__DIR__/config/perms.yml"
```


## Config File _./bin/config/perms.yml_
```shell
j a
mkdir -p bin/config
touch bin/config/perms.yml
```
1. Copy the contents of _init/perms.yml_ to get started.
