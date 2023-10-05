<!--
id: install
tags: ''
-->

# Installation

Decide if you will install this mingled with top-level dependencies or standalone. You may want to choose standalone if you discover dependency conflicts when installing top-level. Or you may want to choose standalone if you do not want to alter the root-level `minimum-stability` value.

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

## Install Stand-Alone

1. Execute the following:

   ```shell
   j a
   mkdir -p opt/aklump
   cd opt/aklump
   composer create-project aklump/easy-perms --repository='{"type":"github","url":"https://github.com/aklump/easy-perms"}' --repository='{"type":"github","url":"https://github.com/aklump/gitignore"}' --stability=dev
   echo "/vendor/" >> easy-perms/.gitignore
   ```
1. Proceed to [installing the controller](@controller).

### Source Control Concerns with Stand-Alone

* You should commit _opt/aklump/easy-perms/composer.lock_
* You should not commit _opt/aklump/easy-perms/vendor_
* In your deployment workflow, you must handle dependency installation, e.g. `cd opt/aklump/easy-config && composer install`

### Updating Stand-Alone Version

Just delete the _easy-perms/_ directory and recreate...

   ```shell
   j a
   cd opt/aklump
   rm -r easy-perms
   composer create-project aklump/easy-perms --repository='{"type":"github","url":"https://github.com/aklump/easy-perms"}' --repository='{"type":"github","url":"https://github.com/aklump/gitignore"}' --stability=dev
   ```
