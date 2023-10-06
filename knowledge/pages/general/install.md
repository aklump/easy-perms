<!--
id: install
tags: ''
-->

# Installation

Decide if you will install this mingled with top-level dependencies or [standalone](@install_standalone). You may want to choose standalone if you discover dependency conflicts when installing top-level. Or you may want to choose standalone if you do not want to alter the root-level `minimum-stability` value.

## Install Project with Composer at Top Level.

1. Merge in the contents of _composer.json_ as shown below, with your app's top-level _composer.json_. This step will add the private repository locations and the stability flags to allow these development packages.

    ```json
    {
      "repositories": [
        {
          "type": "github",
          "url": "https://github.com/aklump/easy-perms"
        },
        {
          "type": "github",
          "url": "https://github.com/aklump/gitignore"
        }
      ],
      "minimum-stability": "dev",
      "prefer-stable" : true
    }
    ```

2. Then run `composer require aklump/easy-perms`
1. Proceed to [installing the controller](@controller).

## Controller Code

```shell
#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P
"$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] &&
s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

chmod u+x $__DIR__/vendor/bin/easy-perms
$__DIR__/vendor/bin/easy-perms $__DIR__/config/perms.yml "$@"
```
## Create the Configuration File

```shell
j a
cp ./vendor/aklump/easy-perms/init/perms.yml ./bin/config/perms.yml
```
