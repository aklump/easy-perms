<?php

namespace AKlump\EasyPerms;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;
use AKlump\EasyPerms\Config\ConfigInterface;

class LoadConfig {

  private array $defaults;

  public function __construct(array $defaults) {
    $this->defaults = $defaults;
  }

  /**
   * @param array $config_paths
   *   The absolute path to one or more configuration files, which will be merged into a single config.
   *
   * @return array
   *   The loaded configuration; note, paths will be made absolute.  Relative
   *   paths will be resolved relative to the configuration file directory.
   *
   * @throws \Symfony\Component\Yaml\Exception\ParseException If the file doesn't exist.
   */
  public function __invoke(array $config_paths): array {
    $all_config = [];
    foreach ($config_paths as $path) {
      $one_config = $this->loadFile($path);
      $one_config = $this->ensureDefaults($one_config);
      $base_path = dirname($path);
      foreach ([
                 ConfigInterface::DEFAULT,
                 ConfigInterface::READONLY,
                 ConfigInterface::WRITEABLE,
                 ConfigInterface::EXECUTABLE,
               ] as $type) {
        if (isset($one_config[$type]) && is_array($one_config[$type])) {
          $one_config[$type] = array_map(function (string $path) use ($base_path): string {
            if (Path::isAbsolute($path)) {
              throw new \RuntimeException(sprintf('Absolute paths are not allowed in your configuration file, they must be relative to the configuration file.  \"%s\" is invalid.', $path));
            }

            return Path::makeAbsolute($path, $base_path);
          }, $one_config[$type]);
        }
      }
      $this->merge($one_config, $all_config);
    }

    return $all_config;
  }


  /**
   * @param $value
   *   Newer values to merge or replace in $result.
   * @param array $result
   *   The final merge result.
   *
   * @return void
   */
  private function merge($value, array &$result) {
    if (!is_array($value)) {
      $result = $value;
    }
    else {
      foreach (array_keys($value) as $k) {
        if (!is_numeric($k)) {

          // These arrays get replaced, not merged.
          if (in_array($k, [
            ConfigInterface::FILE_PERMISSIONS,
            ConfigInterface::DIRECTORY_PERMISSIONS,
          ])) {
            $result[$k] = $value[$k];
          }
          else {
            $result[$k] = $result[$k] ?? [];
            $this->merge($value[$k], $result[$k]);
          }
        }
        else {
          $result = array_merge($value, $result);
          $result = array_filter($result);
          $result = array_unique($result);
        }
      }
    }
  }

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

  private function ensureDefaults(array $config): array {
    foreach ($this->defaults as $type => $perms) {
      if (empty($config[$type]) || !is_array($config[$type])) {
        $config[$type] = [];
      }
      $config[$type] += $perms->jsonSerialize();

      // Ensure we have octal strings as values.
      $config[$type] = array_map(function ($value) {
        if (is_int($value)) {
          return '0' . decoct($value);
        }

        return $value;
      }, $config[$type]);
    }

    return $config;
  }

}
