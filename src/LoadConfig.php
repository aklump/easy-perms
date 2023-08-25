<?php

namespace AKlump\EasyPerms;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class LoadConfig {

  /**
   * @param string $path
   *   The absolute path to the configuration file.
   *
   * @return array
   *   The loaded configuration; note, paths will be made absolute.  Relative
   *   paths will be resolved relative to the configuration file directory.
   */
  public function __invoke(string $path): array {
    // It's worth nothing that this method seems to parse JSON files too
    // (version 5.4.23).
    $config = Yaml::parseFile($path);
    $config = $this->ensureDefaults($config);

    $base_path = dirname($path);
    foreach (['default', 'writeable', 'executable'] as $type) {
      if (isset($config[$type]) && is_array($config[$type])) {
        $config[$type] = array_map(function (string $path) use ($base_path): string {
          if (substr($path, 0, 1) === '/') {
            throw new \RuntimeException(sprintf('Absolute paths are not allowed; \"%s\" is invalid.', $path));
          }

          return Path::makeAbsolute($path, $base_path);
        }, $config[$type]);
      }
    }

    return $config;
  }

  private function ensureDefaults(array $config): array {
    foreach ([
               'file_permissions' => new DefaultFilePermissions(),
               'directory_permissions' => new DefaultDirectoryPermissions(),
             ] as $type => $perms) {
      if (empty($config[$type]) || !is_array($config[$type])) {
        $config[$type] = [];
      }
      $config[$type] += $perms->jsonSerialize();
    }

    return $config;
  }

}
