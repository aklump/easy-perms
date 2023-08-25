<?php

namespace AKlump\EasyPerms;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
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
    $config = $this->loadFile($path);
    $config = $this->ensureDefaults($config);
    $base_path = dirname($path);
    foreach (['default', 'writeable', 'executable'] as $type) {
      if (isset($config[$type]) && is_array($config[$type])) {
        $config[$type] = array_map(function (string $path) use ($base_path): string {
          if (Path::isAbsolute($path)) {
            throw new \RuntimeException(sprintf('Absolute paths are not allowed in your configuration file, they must be relative to the configuration file.  \"%s\" is invalid.', $path));
          }

          return Path::makeAbsolute($path, $base_path);
        }, $config[$type]);
      }
    }

    return $config;
  }

  private function loadFile(string $path): array {
    $mime = (new FinfoMimeTypeDetector())->detectMimeTypeFromPath($path);
    switch ($mime) {
      case 'application/json':
        $config = json_decode(file_get_contents($path), TRUE);
        break;

      case 'text/yaml':
        $config = Yaml::parseFile($path);
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Invalid configuration file type: %s', $mime));
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
