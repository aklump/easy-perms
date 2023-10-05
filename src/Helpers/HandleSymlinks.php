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
    return $this->recursiveResolver($path);
  }

  private function recursiveResolver($value, array &$files = []) {
    $files[] = (new NormalizePath())($value);
    if (is_link($value)) {
      $target = dirname($value) . '/' . readlink($value);
      $this->recursiveResolver($target, $files);
    }

    return $files;
  }

}
