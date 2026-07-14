<?php

namespace AKlump\EasyPerms\Helpers;

use Symfony\Component\Filesystem\Path;

class HandleSymlinks {

  private $normalizer;

  /**
   * @param string $path
   *   A filepath, which may be a symlink,
   *
   * @return string[]
   *   If $path is a symlink this will contain $path and it's target.  Otherwise
   *   it will only contain $path.
   */
  public function __invoke(string $path): array {
    $this->normalizer = $this->normalizer ?? new NormalizePath();
    $files = [];
    $this->resolver($path, $files);

    return array_values($files);
  }

  private function resolver($value, array &$files = []) {
    $queue = [$value];
    while (!empty($queue)) {
      $val = array_shift($queue);
      $normalized = ($this->normalizer)($val);
      if (isset($files[$normalized])) {
        continue;
      }
      $files[$normalized] = $normalized;
      if (is_link($val)) {
        $target = readlink($val);
        if (!Path::isAbsolute($target)) {
          $target = dirname($val) . '/' . $target;
        }
        $queue[] = $target;
      }
    }

    return $files;
  }

}
