<?php

namespace AKlump\EasyPerms\Config;

use Symfony\Component\Filesystem\Path;

/**
 * Resolve one or more configuration file paths, expanding globs and making them absolute.
 */
class ResolveConfigPaths {

  /**
   * @param array $config_paths
   *   An array of paths, which may include glob patterns.
   * @param string|null $base_dir
   *   The base directory for resolving relative paths. Defaults to getcwd().
   *
   * @return array
   *   An array of absolute paths to configuration files.  Non-existent paths
   *   that were not part of a glob are preserved so they can be handled by
   *   existence checks.
   */
  public function __invoke(array $config_paths, ?string $base_dir = NULL): array {
    if (NULL === $base_dir) {
      $base_dir = getcwd();
    }

    $expanded = [];
    foreach ($config_paths as $path) {
      if ($this->isGlob($path)) {
        $matches = glob($path);
        if ($matches) {
          $expanded = array_merge($expanded, $matches);
        }
        else {
          $expanded[] = $path;
        }
      }
      else {
        $expanded[] = $path;
      }
    }

    $resolved = array_map(fn($path) => Path::makeAbsolute($path, $base_dir), $expanded);

    return array_values(array_unique($resolved));
  }

  /**
   * Check if a path is a glob pattern.
   *
   * @param string $path
   *
   * @return bool
   */
  private function isGlob(string $path): bool {
    return strpos($path, '*') !== FALSE || strpos($path, '?') !== FALSE || strpos($path, '[') !== FALSE;
  }
}
