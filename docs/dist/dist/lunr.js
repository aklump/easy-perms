var lunrIndex = [{"id":"changelog","title":"Changelog","body":"All notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https:\/\/keepachangelog.com\/en\/1.0.0\/),\nand this project adheres to [Semantic Versioning](https:\/\/semver.org\/spec\/v2.0.0.html).\n\n## [Unreleased]\n- lorem"},{"id":"configuration","title":"Configuration","body":"* Both YAML and JSON is supported.\n* See _json\\_schema\/config.schema.json_ for details of the configuration.\n* Notice globs are supported. So are `\/**\/` doubles.\n\n1. Copy the contents of _vendor\/aklump\/easy-perms\/init\/perms.yml_ into _bin\/config\/perms.yml_ to get started with configuration."},{"id":"readme","title":"Easy Perms","body":"Add this to a project to be able to easily manage file and directory permissions.\n\nThe files in a web app are likely to require writeable and\/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.\n\n**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions."},{"id":"install","title":"Installation","body":"## Install project with Composer at top level.\n\n```shell\n$ j a\n$ cd install\/composer\n$ grab easy-perms\n```\n\n```json\n{\n  \"repositories\": [\n    {\n      \"type\": \"path\",\n      \"url\": \".\/install\/composer\/easy-perms\"\n    }\n  ]\n}\n```\n\n```shell\nj a\ncomposer require --dev aklump\/easy-perms:@dev\n```\n\n## Install In Isolation Due to Top-level Dependency Conflicts\n\n```shell\nj a\ncd install\/composer\ngrab easy-perms\nj a\nmkdir -p opt\/aklump\/easy-perms\ncd opt\/aklump\/easy-perms\ntouch composer.json\n```\n\n```json\n{\n  \"repositories\": [\n    {\n      \"type\": \"path\",\n      \"url\": \".\/install\/composer\/easy-perms\"\n    }\n  ]\n}\n```\n\n```shell\nj a\ncd opt\/aklump\/easy-perms\ncomposer require --dev aklump\/easy-perms:@dev\n```\n\n## Controller and Configuration\n\n1. First, create the controller and config files:\n\n   ```shell\n   j a\n   mkdir -p bin\/config\n   touch bin\/perms\n   touch bin\/config\/perms.yml\n   touch bin\/config\/perms.local.yml\n   chmod u+x bin\/perms\n   ```\n2. Now paste these contents into _bin\/perms_:\n\n   ```shell\n   #!\/usr\/bin\/env bash\n   s=\"${BASH_SOURCE[0]}\";[[ \"$s\" ]] || s=\"${(%):-%N}\";while [ -h \"$s\" ];do d=\"$(cd -P \"$(dirname \"$s\")\" && pwd)\";s=\"$(readlink \"$s\")\";[[ $s != \/* ]] && s=\"$d\/$s\";done;__DIR__=$(cd -P \"$(dirname \"$s\")\" && pwd)\n\n   $__DIR__\/..\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms \"$__DIR__\/config\/perms.yml\" \"$__DIR__\/config\/perms.local.yml\" \"$@\"\n   ```"},{"id":"resources","title":"Resources","body":"* [Drupal Best Practices](https:\/\/www.drupal.org\/docs\/administering-a-drupal-site\/security-in-drupal\/securing-file-permissions-and-ownership)"},{"id":"symlinks","title":"Symlinks","body":"Symlinks may cause some unexpected output depending upon how you write your configuration.  More specifically it may appear that the same file keeps having the perms set.  This is not to worry and things are most likely working out correctly on the backend.\n\nIf permissions are failing to set, one thing to try is manually setting everything to 0755 `chmod -R 0755 ...` and then starting over with the controller file.  You may need to `sudo` to do this."},{"id":"syntax","title":"Syntax Rules","body":"* The pattern matching rules are the same as used in [gitignore](https:\/\/git-scm.com\/docs\/gitignore#_pattern_format)\n* If the path ends in a forward-slash `\/` then only directories are matched, e.g. `\/foo\/bar\/*\/`\n* If the path does not end in a forward slash then both files and directories are matched, e.g. `\/foo\/bar\/*`\n\n* Use this tool to visualize your configuration.:"},{"id":"troubleshooting","title":"Troubleshooting","body":"* If a directory does not have execute permissions, then you cannot change permissions on it's contents."},{"id":"permission_values","title":"What Are the Permission Values?","body":"* See `DefaultFilePermissions` and `DefaultDirectoryPermissions` for default values.\n* To override these default values add something like the following to your configuration:\n\n```yaml\nfile_permissions:\n  default: '0640'\n  readonly: '0440'\n  writeable: '0640'\n  executable: '0740'\n\ndirectory_permissions:\n  default: '0750'\n  readonly: '0550'\n  writeable: '0770'\n  executable: '0750'\n```"},{"id":"workflow","title":"Workflow Strategy","body":"1. Use `bump dev` when you want to begin developing to get the local perms correct.\n2. Use `bump build` to restore the live permissions before committing to the repo.\n\n## Web Package\n\n_build\/00\\_set\\_live\\_perms.sh_\n\n```shell\n#!\/usr\/bin\/env bash\n.\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms \".\/bin\/config\/perms.yml\"\n\n```\n\n_dev\/00\\_set\\_dev\\_perms.sh_\n\n* Use _perms.local.yml_ to set the extra executable permissions used in development only.\n\n```shell\n#!\/usr\/bin\/env bash\nchmod u+x .\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms\n.\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms \".\/bin\/config\/perms.yml\" \".\/bin\/config\/perms.local.yml\"\n\n```\n\n## Install\/Deployment\n\n3. During deployment to live use `.\/bin\/perms` to ensure the live perms are set correctly.\n4. Better yet, do the last step as part of `.\/bin\/install` by setting something like this:\n\n```yaml\n  pre_install_prod:\n    - bin\/perms\n```\n\n_bin\/perms_\n\n```shell\n#!\/usr\/bin\/env bash\ns=\"${BASH_SOURCE[0]}\";[[ \"$s\" ]] || s=\"${(%):-%N}\";while [ -h \"$s\" ];do d=\"$(cd -P \"$(dirname \"$s\")\" && pwd)\";s=\"$(readlink \"$s\")\";[[ $s != \/* ]] && s=\"$d\/$s\";done;__DIR__=$(cd -P \"$(dirname \"$s\")\" && pwd)\n\n$__DIR__\/..\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms \"$__DIR__\/config\/perms.yml\" \"$__DIR__\/config\/perms.local.yml\" \"$@\"\n\n```"}]