var lunrIndex = [{"id":"changelog","title":"Changelog","body":"All notable changes to this project will be documented in this file.\n\nThe format is based on [Keep a Changelog](https:\/\/keepachangelog.com\/en\/1.0.0\/),\nand this project adheres to [Semantic Versioning](https:\/\/semver.org\/spec\/v2.0.0.html).\n\n## [Unreleased]\n- lorem"},{"id":"controller","title":"Controller and Configuration","body":"> As a matter of convention, I like to create an executable file as  _.\/bin\/perms_ in any application I work on, as the single point of action to assign proper file permissions.\n\n1. First, create the controller file:\n\n   ```shell\n   j a\n   mkdir -p bin\/config\n   touch bin\/perms\n   chmod u+x bin\/perms\n   ```\n3. Paste the controller code (see installation pages) into _bin\/perms_:\n5. Open and edit _bin\/config\/perms.yml_ as necessary. (See how to create on the installation pages).\n\n## Multiple and\/or Local Configuration\n\n1. It is possible to pass multiple configuration paths to the controller, which are merged together. This is how you can have environment-specific overrides, e.g.\n\n   ```bash\n   ... bin\/easy-perms config\/perms.yml config\/perms.local.yml \"$@\"\n   ```\n2. Do not commit _perms.local.yml_\n3. Use it for local-only, additional permissions.\n\n## Other Highlights\n\n* Both YAML and JSON is supported.\n* See _json\\_schema\/config.schema.json_ for details of the configuration."},{"id":"readme","title":"Easy Perms","body":"![Banner](..\/..\/images\/easy-perms.jpg)\n\nAdd this to a project to be able to easily manage file and directory permissions.\n\nThe files in a web app are likely to require writeable and\/or executable permissions on some files, but not most. Further, these permissions may need to be more permissive in development environments. It's time-consuming and may be confusing to keep all this in order. This tool allows you to set a baseline and then be only as permissive as necessary. It allows different configuration based on an environment. The management is simply to add paths or globs to a YAML or JSON file and then run the controller.\n\n**This project does not handle ownership of files, by design.**  It assumes proper owner and group on all files, and merely sets the octal permissions.\n\n## Installation (w\/Composer)\n\n1. Because this is an unpublished package, you must define it's repository in\n   your project's _composer.json_ file. Add the following to _composer.json_ in\n   the `repositories` array:\n\n    ```json\n    {\n     \"type\": \"github\",\n     \"url\": \"https:\/\/github.com\/aklump\/gitignore\"\n    },\n    {\n     \"type\": \"github\",\n     \"url\": \"https:\/\/github.com\/aklump\/easy-perms\"\n    }\n    ```\n1. Require this package:\n\n    ```\n    composer require aklump\/easy-perms:^0.0\n    ```\n\n1. Proceed to [installing the controller](@controller).\n\n**For security, install this above web root, and not in a location accessibly by the web.**\n\n### Controller and Configuration Files\n\n1. See snippet below...\n2. Copy the correct controller and commit to source control.\n3. The correct controller is _init\/controller.sh_ or if you used `create-project` then you must use _init\/controller--create-project.sh_.\n\n```shell\nmkdir -p .\/bin\/config\ncp .\/vendor\/aklump\/easy-perms\/init\/controller.sh .\/bin\/perms\nchmod u+x .\/bin\/perms\ncp .\/vendor\/aklump\/easy-perms\/init\/perms.yml .\/bin\/config\/perms.yml\n```\n\n## Alternative Stand-Alone Installation\n\nIf `composer require` fails, that is, if the dependencies of this project conflict with your application, you should install this using `composer create-project`, which creates a stand-alone installation. Copy and paste the following code, executed most likely from your\nrepository root, and certainly above web root.\n\ncomposer create-project aklump\/easy-perms:^0.0 --repository=\"{\\\"type\\\":\\\"github\\\",\\\"url\\\": \\\"https:\/\/github.com\/aklump\/easy-perms\\\"}\"\n\nThe controller and configuration is altered slightly to the following:\n\n```shell\nmkdir -p .\/bin\/config\ncp .\/easy-perms\/init\/controller--create-project.sh .\/bin\/perms\nchmod u+x .\/bin\/perms\ncp .\/easy-perms\/init\/perms.yml .\/bin\/config\/perms.yml\n```\n\nWith this method the only update path is to remove and then reinstall aklump\/easy-perms, repeating the `create-project` process.\n**Running `composer update` will only update the dependencies and not aklump\/easy-perms itself.**\n\n## Configuration\n\n1. Open _bin\/config\/perms.yml_, familiarize yourself with it, then make adjustments as necessary.\n2. Add paths and path globs to each of: `default, readonly, writeable, executable` as is appropriate to your project.\n\n### Pattern Syntax\n\n* The pattern matching rules are the same as used in [gitignore](https:\/\/git-scm.com\/docs\/gitignore#_pattern_format)\n* Double asterix is supported, e.g. `\/foo\/**\/*.php`.\n* If the path ends in a forward-slash `\/` then only directories are matched, e.g. `\/foo\/bar\/*\/`\n* If the path does not end in a forward slash then both files and directories are matched, e.g. `\/foo\/bar\/*`\n\n* Use this tool to visualize your configuration.:\n\n## Usage\n\nTo apply the configured permission to your project at any time, execute the controller like this:\n\n```shell\n$ bin\/perms -v\nChecking bin\/bind_book.sh\nChecking bin\/easy-perms\nChecking bin\/run_unit_tests.sh\nChecking vendor\/bin\/phpunit\n0770 \ud83d\udee0  app.php\n0770 \ud83d\udee0  bin\/bind_book.sh\n0770 \ud83d\udee0  bin\/run_unit_tests.sh\n0770 \ud83d\udee0  vendor\/bin\/phpunit\nCompleted in 0.01 seconds.\nPermission setting was successful.\n```\n\nPaths will print only if their permissions were changed. If you want to see more (as shown above), use the verbose option `-v`.\n\n### Troubleshooting\n\nIf permissions are failing to set, try manually resetting all paths to 0755, e.g. `chmod -R 0755 *` (or `sudo chmod -R 0755 *` if necessary) from the application root.\n\nNow execute the controller and the permissions should apply correctly.\n\n### Things To Note\n\n* If a directory does not have execute permissions, then you cannot change permissions on it's contents.\n* **Symlinks may cause some unexpected output** depending upon how you write your configuration. More specifically it may appear that the same file keeps having the perms set. This is not to worry and things are most likely working correctly on the backend.\n\n## Sponsor this project\n\n  \r\n&nbsp;github.com\n\n&nbsp;buymeacoffee.com\n\n  &nbsp;paypal.com"},{"id":"resources","title":"Resources","body":"* [Drupal Best Practices](https:\/\/www.drupal.org\/docs\/administering-a-drupal-site\/security-in-drupal\/securing-file-permissions-and-ownership)"},{"id":"permission_values","title":"What Are the Permission Values?","body":"* See `DefaultFilePermissions` and `DefaultDirectoryPermissions` for default values.\n* To override these default values add something like the following to your configuration:\n\n```yaml\nfile_permissions:\n  default: '0640'\n  readonly: '0440'\n  writeable: '0640'\n  executable: '0740'\n\ndirectory_permissions:\n  default: '0750'\n  readonly: '0550'\n  writeable: '0770'\n  executable: '0750'\n```"},{"id":"workflow","title":"Workflow Strategy","body":"1. Use `bump dev` when you want to begin developing to get the local perms correct.\n2. Use `bump build` to restore the live permissions before committing to the repo.\n\n## Web Package\n\n1. Set only those execute permissions that are required to `bump build` in _config.yml_. All other configuration for `executable` should be written in _config.local.yml_.\n\n_build\/00\\_set\\_live\\_perms.sh_\n\n```shell\n#!\/usr\/bin\/env bash\n.\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms .\/bin\/config\/perms.yml\n```\n\n_dev\/00\\_set\\_dev\\_perms.sh_\n\n* Use _perms.local.yml_ to set the extra executable permissions used in development only.\n\n```shell\n#!\/usr\/bin\/env bash\nchmod u+x .\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms\n.\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms .\/bin\/config\/perms.yml .\/bin\/config\/perms.local.yml\n\n```\n\n## Install\/Deployment\n\n1. During deployment to live use `.\/bin\/perms` to ensure the live perms are set correctly.\n4. Better yet, do the last step as part of `.\/bin\/install` by setting something like this:\n\n```yaml\n  pre_install_prod:\n    - cd opt\/aklump\/easy-config && composer install\n  post_install_prod:\n    - bin\/perms\n```\n\n_bin\/perms_\n\n```shell\n#!\/usr\/bin\/env bash\ns=\"${BASH_SOURCE[0]}\";[[ \"$s\" ]] || s=\"${(%):-%N}\";while [ -h \"$s\" ];do d=\"$(cd -P \"$(dirname \"$s\")\" && pwd)\";s=\"$(readlink \"$s\")\";[[ $s != \/* ]] && s=\"$d\/$s\";done;__DIR__=$(cd -P \"$(dirname \"$s\")\" && pwd)\n\n$__DIR__\/..\/opt\/aklump\/easy-perms\/vendor\/bin\/easy-perms \"$__DIR__\/config\/perms.yml\" \"$__DIR__\/config\/perms.local.yml\" \"$@\"\n\n```"}]