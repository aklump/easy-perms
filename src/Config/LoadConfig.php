<?php

namespace AKlump\EasyPerms\Config;

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
    $config_paths = (new ResolveConfigPaths())($config_paths);
    $all_config = $this->createDefaultConfig();

    $load_content = new LoadConfigContent();
    foreach ($config_paths as $path) {
      $one_config = $load_content($path);
      $this->mergeConfig($one_config, $all_config);
    }

    return $all_config;
  }

  /**
   * Create the starting configuration, that is the defaults only.
   *
   * Use this together with ::mergeConfig to merge configuration files one at a
   * time, e.g. to obtain the effective configuration at each step of a
   * sequence.
   *
   * @return array
   *   The configuration containing only the normalized defaults.
   */
  public function createDefaultConfig(): array {
    $config = [];
    foreach ($this->defaults as $type => $perms) {
      $config[$type] = [];
      foreach ($perms->jsonSerialize() as $key => $value) {
        $config[$type][$key] = $this->normalizePermissionMode($value, $key);
      }
    }

    return $config;
  }

  /**
   * Merge the contents of a single configuration file into a result.
   *
   * Later configurations supersede earlier ones; permission modes are
   * replaced and path lists are appended.
   *
   * @param array $one_config
   *   The contents of a single configuration file.
   * @param array $result
   *   The evolving configuration to merge into; passed by reference.
   *
   * @return void
   *
   * @see \AKlump\EasyPerms\Config\LoadConfigContent
   */
  public function mergeConfig(array $one_config, array &$result): void {
    $this->merge($one_config, $result);
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
          $result = array_merge($result, $value);
          $result = array_filter($result);
          $result = array_unique($result);
        }
      }
    }
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
