<!--
id: hints
tags: ''
-->

# Hints

## Set/Review Perms by Filename

```shell
$ bin/perms.sh -vvv | grep upgrade_npm_deps.sh
Calculating permissions: **/upgrade_npm_deps.sh
0550 🛠  bin/upgrade_npm_deps.sh
0550 🛠  install/composer/my_module/knowledge/vendor/aklump/knowledge/bin/upgrade_npm_deps.sh
0550 🛠  install/composer/my_theme/bin/upgrade_npm_deps.sh
```
