<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\HasBasePathTrait;
use Symfony\Component\Filesystem\Path;

/**
 * This class should be used instead Symfony's methods listed below.
 *
 * - Removes dots in path.
 * - Ensures all forward slashes.
 * - Adds trailing slash to existing directory paths.
 * - Converts paths to absolute.
 *
 * @see \Symfony\Component\Filesystem\Path::normalize()
 * @see \Symfony\Component\Filesystem\Path::canonicalize()
 */
class NormalizePath {

  use HasBasePathTrait;

  /**
   * @param string $path
   *   This must be absolute unless you set a basepath, either using the method
   *   or constructor.
   *
   * @return string
   */
  public function __invoke(string $path): string {
    $path = Path::normalize($path);
    if (!Path::isAbsolute($path)) {
      if (empty($this->getBasePath())) {
        throw new \RuntimeException(sprintf('Since %s::basePath is empty, $path must be absolute. %s is not.', static::class, $path));
      }
      $path = Path::makeAbsolute($path, $this->getBasePath());
    }
    if (strstr($path, '..') !== FALSE) {
      $path = Path::canonicalize($path);
    }
    $path = rtrim($path, '/');
    if (file_exists($path)) {
      if (is_dir($path)) {
        $path .= '/';
      }
    }

    return $path;
  }

}
