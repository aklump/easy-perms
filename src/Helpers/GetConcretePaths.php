<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\PathHandlerTrait;
use AKlump\GitIgnore\Pattern;

/**
 * @url https://git-scm.com/docs/gitignore#_pattern_format
 * @url https://www.digitalocean.com/community/tools/glob
 */
class GetConcretePaths {

  use PathHandlerTrait;

  /**
   * Get all concrete paths as matched by $path.
   *
   * @param string $path
   *   A file/dir matching rule or value.
   *
   * @return array
   *   All matched paths.
   *
   * @throws
   */
  public function __invoke(string $path): array {
    $return_only_directories = self::isDir($path);
    do {
      if (empty($start_dir)) {
        $start_dir = $path;
      }
      else {
        $start_dir = substr($start_dir, 0, -1);
      }
    } while ($start_dir && !is_dir("$start_dir"));

    $files = (new GetFileList())($start_dir);
    $matcher = new Pattern($path);
    $files = array_filter($files, function ($file) use ($matcher) {
      return $file && $matcher->matches($file);
    });
    $files = array_values($files);

    if ($return_only_directories) {
      $files = array_filter($files, fn($file) => self::isDir($file));
    }

    return $files;
  }

}
