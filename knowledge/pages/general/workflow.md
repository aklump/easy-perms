<!--
id: workflow
tags: ''
-->

# Workflow Strategy

1. Use `bump dev` when you want to begin developing to get the local perms correct.
2. Use `bump build` to restore the live permissions before committing to the repo.

## Web Package

1. Set only those execute permissions that are required to `bump build` in _config.yml_. All other configuration for `executable` should be written in _config.local.yml_.

_build/00\_set\_live\_perms.sh_

```shell
#!/usr/bin/env bash
./opt/aklump/easy-perms/vendor/bin/easy-perms ./bin/config/perms.yml
```

_dev/00\_set\_dev\_perms.sh_

* Use _perms.local.yml_ to set the extra executable permissions used in development only.

```shell
#!/usr/bin/env bash
chmod u+x ./opt/aklump/easy-perms/vendor/bin/easy-perms
./opt/aklump/easy-perms/vendor/bin/easy-perms ./bin/config/perms.yml ./bin/config/perms.local.yml

```

## Install/Deployment

1. During deployment to live use `./bin/perms` to ensure the live perms are set correctly.
4. Better yet, do the last step as part of `./bin/install` by setting something like this:

```yaml
  pre_install_prod:
    - cd opt/aklump/easy-config && composer install
  post_install_prod:
    - bin/perms
```

_bin/perms_

```shell
#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P "$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] && s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

$__DIR__/../opt/aklump/easy-perms/vendor/bin/easy-perms "$__DIR__/config/perms.yml" "$__DIR__/config/perms.local.yml" "$@"

```
