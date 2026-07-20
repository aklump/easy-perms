<?php

namespace AKlump\EasyPerms\Config;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * Load a single configuration file and normalize paths within it.
 */
class LoadConfigContent {

  /**
   * @param string $path
   *   The absolute path to the configuration file.
   * @param bool $normalize_paths
   *   If TRUE, paths within the config will be made absolute relative to the file.
   *
   * @return array
   *   The loaded configuration.
   *
   * @throws \RuntimeException If absolute paths are found in the config file.
   * @throws \InvalidArgumentException If the file format is not supported.
   */
  public function __invoke(string $path, bool $normalize_paths = TRUE): array {
    $one_config = $this->loadFile($path);
    if (!$normalize_paths) {
      return $one_config;
    }
    $base_path = dirname($path);
    foreach ([
               ConfigInterface::DEFAULT,
               ConfigInterface::READONLY,
               ConfigInterface::WRITABLE,
               ConfigInterface::EXECUTABLE,
             ] as $type) {
      if (isset($one_config[$type]) && is_array($one_config[$type])) {
        $one_config[$type] = array_map(function (string $path) use ($base_path): string {
          if (Path::isAbsolute($path)) {
            throw new \RuntimeException(sprintf('Absolute paths are not allowed in your configuration file, they must be relative to the configuration file.  "%s" is invalid.', $path));
          }

          return Path::makeAbsolute($path, $base_path);
        }, $one_config[$type]);
      }
    }

    return $one_config;
  }

  /**
   * Load the file based on its extension.
   *
   * @param string $path
   *
   * @return array
   */
  private function loadFile(string $path): array {
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if (preg_match('/ya?ml/i', $extension)) {
      $config = Yaml::parseFile($path) ?? [];
    }
    elseif (preg_match('/json/i', $extension)) {
      $config = json_decode(file_get_contents($path), TRUE);
    }
    else {
      throw new \InvalidArgumentException(sprintf('Cannot load configuration as array from path: %s', $path));
    }

    return $config;
  }
}
