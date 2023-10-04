<!--
id: install
tags: ''
-->

# Installation

Decide if you will install this mingled with top-level dependencies or standalone. You may want to choose standalone if you discover dependency conflicts when installing top-level. Or you may want to choose standalone if you do not want to alter the root-level `minimum-stability` value.

## Install project with Composer at top level.

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

2. Then run `composer require --dev aklump/easy-perms:@dev`



## Install Stand-Alone

```shell
j a
mkdir -p opt/aklump
cd opt/aklump
composer create-project aklump/easy-perms --repository='{"type":"github","url":"https://github.com/aklump/easy-perms"}' --repository='{"type":"github","url":"https://github.com/aklump/gitignore"}' --stability=dev
```
