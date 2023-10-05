<?php

namespace AKlump\EasyPerms\Helpers;

class HandleSymlinks {

  /**
   * @param string $path
   *   A filepath, which may be a symlink,
   *
   * @return string[]
   *   If $path is a symlink this will contain $path and it's target.  Otherwise
   *   it will only contain $path.
   */
  public function __invoke(string $path): array {
    $files = [$path];
    if (is_link($path)) {
      $target = realpath($path);
      if ($target && $target !== $path) {
        $files[] = $target;
      }
    }

    return $files;
  }

}
