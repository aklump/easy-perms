<!--
id: controller
tags: ''
-->

# Controller and Configuration

> As a matter of convention, I like to create an executable file as  _./bin/perms_ in any application I work on, as the single point of action to assign proper file permissions.

1. First, create the controller file:

   ```shell
   j a
   mkdir -p bin/config
   touch bin/perms
   chmod u+x bin/perms
   ```
3. Paste the controller code (see installation pages) into _bin/perms_:
5. Open and edit _bin/config/perms.yml_ as necessary. (See how to create on the installation pages).

## Multiple and/or Local Configuration

1. It is possible to pass multiple configuration paths to the controller, which are merged together. This is how you can have environment-specific overrides, e.g.

   ```bash
   ... bin/easy-perms config/perms.yml config/perms.local.yml "$@"
   ```
2. Do not commit _perms.local.yml_
3. Use it for local-only, additional permissions.

## Other Highlights

* Both YAML and JSON is supported.
* See _json\_schema/config.schema.json_ for details of the configuration.
