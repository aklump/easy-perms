<!--
id: symlinks
tags: ''
-->

# Symlinks

Symlinks may cause some unexpected output depending upon how you write your configuration.  More specifically it may appear that the same file keeps having the perms set.  This is not to worry and things are most likely working out correctly on the backend.

If permissions are failing to set, one thing to try is manually setting everything to 0755 `chmod -R 0755 ...` and then starting over with the controller file.  You may need to `sudo` to do this.
