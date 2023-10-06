<!--
id: install_standalone
tags: ''
-->

# Installation Alt

1. Execute the following:

   ```shell
   j a
   mkdir -p opt/aklump/easy-perms
   cd opt/aklump/easy-perms
   touch composer.json
   echo "/vendor/" >> .gitignore
   ```
2. Copy the contents to _opt/aklump/easy-perms/composer.json_.

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

### Extra Source Control Concerns with This Method

Because you are creating a second dependency library you need to take additional steps regarding source control and dependency management.

* You should commit _opt/aklump/easy-perms/composer.lock_
* You should not commit _opt/aklump/easy-perms/vendor_
* In your deployment workflow, you must handle dependency installation, e.g. `cd opt/aklump/easy-config && composer install`

### Updating Stand-Alone Version

   ```shell
   j a
   cd opt/aklump/easy-perms
   composer update
   ```

## Controller Code

```shell
#!/usr/bin/env bash
s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P
"$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] &&
s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

chmod u+x $__DIR__/../opt/aklump/easy-perms/vendor/bin/easy-perms
$__DIR__/../opt/aklump/easy-perms/vendor/bin/easy-perms $__DIR__/config/perms.yml "$@"
```

## Create the Configuration File

```shell
j a
cp ./opt/aklump/easy-perms/init/perms.yml ./bin/config/perms.yml
```
