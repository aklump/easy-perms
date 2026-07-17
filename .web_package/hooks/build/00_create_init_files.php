<?php

use AKlump\EasyPerms\Config\DefaultDirectoryPermissions;
use AKlump\EasyPerms\Config\DefaultFilePermissions;
use Symfony\Component\Yaml\Yaml;

$config_filepath = 'init/easy-perms.yml';
$config = Yaml::parseFile($config_filepath);
$config['directory_permissions'] = (new DefaultDirectoryPermissions())->jsonSerialize();
$config['file_permissions'] = (new DefaultFilePermissions())->jsonSerialize();
file_put_contents($config_filepath, Yaml::dump($config, 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
