<?php

namespace AKlump\EasyPerms\Config;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

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
    foreach ($this->defaults as $type => $perms) {
      $all_config[$type] = [];
      foreach ($perms->jsonSerialize() as $key => $value) {
        $all_config[$type][$key] = $this->normalizePermissionMode($value, $key);
      }
    }

    foreach ($config_paths as $path) {
      $one_config = $this->loadFile($path);
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
      foreach ($value as $k => $v) {
        if ($k === 'writeable') {
          throw new \RuntimeException('The "writeable" key is deprecated and has been removed.  Please use "writable" instead.');
        }
        if (!is_numeric($k)) {

          // These arrays get replaced, not merged.
          if (in_array($k, [
            ConfigInterface::FILE_PERMISSIONS,
            ConfigInterface::DIRECTORY_PERMISSIONS,
          ])) {
            $result[$k] = $result[$k] ?? [];
            foreach ($v as $key => $value) {
              if ($key === 'writeable') {
                throw new \RuntimeException(sprintf('The "%s.writeable" key is deprecated and has been removed.  Please use "writable" instead.', $k));
              }
              $result[$k][$key] = $this->normalizePermissionMode($value, $k . '.' . $key);
            }
          }
          else {
            $result[$k] = $result[$k] ?? [];
            $this->merge($v, $result[$k]);
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

  private function normalizePermissionMode($value, string $key): string {
    if (is_int($value)) {
      $value = '0' . decoct($value);
    }
    if (!is_string($value) || !preg_match('/^0[0-7]{3}$/', $value)) {
      throw new \InvalidArgumentException(sprintf('Invalid permission mode "%s" for key "%s".  It must be a four-digit octal string starting with 0, e.g., "0644".', print_r($value, TRUE), $key));
    }

    return $value;
  }


}
