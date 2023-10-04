<!--
id: install
tags: ''
-->

# Installation

Decide if you will install this mingled with top-level dependencies or standalone. You may want to choose standalone if you discover dependency conflicts when installing top-level. Or you may want to choose standalone if you do not want to alter the root-level `minimum-stability` value.

## Install project with Composer at top level.

1. Merge in the contents of _composer.json_ as shown below, with your app's top-level _composer.json_. This step will add the private repository locations and the stability flags to allow these development packages.
2. Then run `composer require --dev aklump/easy-perms:@dev`

## Install Stand-Alone

```shell
j a
mkdir -p opt/aklump/easy-perms
cd opt/aklump/easy-perms
touch composer.json
echo "/vendor/" >> .gitignore
echo "composer.lock" >> .gitignore
```

1. Copy _composer.json_ as shown below into the file just created.
2. Then run `composer require --dev aklump/easy-perms:@dev `

## _composer.json_

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
