<?php

namespace AKlump\EasyPerms\Config;

interface ConfigInterface {

  const READONLY = 'readonly';

  const DEFAULT = 'default';

  const WRITEABLE = 'writeable';

  const EXECUTABLE = 'executable';

  const DIRECTORY_PERMISSIONS = 'directory_permissions';

  const FILE_PERMISSIONS = 'file_permissions';

}
