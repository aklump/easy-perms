<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\HasBasePathTrait;
use AKlump\EasyPerms\Traits\PathHandlerTrait;
use AKlump\GitIgnorePatternMatcher\StringAnalyzer;
use AKlump\GitIgnorePatternMatcher\StringMatcher;
use Symfony\Component\Filesystem\Path;

/**
 * @url https://git-scm.com/docs/gitignore#_pattern_format
 * @url https://www.digitalocean.com/community/tools/glob
 */
class GetConcretePaths {

  use HasBasePathTrait;
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
  public function __invoke(string $path_or_pattern): array {
    $return_only_directories = self::isDir($path_or_pattern);

    // The start directory has to be an actual path, it cannot contain
    // pattern-matching values, so this next bit will find the closest ancestor
    // directory to use for GetFileList().
    $base = $this->getBasePath();
    $pattern = Path::makeRelative($path_or_pattern, $this->getBasePath());
    do {
      if (empty($temp)) {
        $temp = $pattern;
      }
      else {
        $temp = substr($temp, 0, -1);
      }
      $start_dir = "$base/$temp";
    } while ($temp && !is_dir("$start_dir"));

    $files = (new GetFileList())($start_dir);
    $matcher = new StringMatcher($pattern);
    $files = array_filter($files, function ($file) use ($matcher) {
      $file = Path::makeRelative($file, $this->getBasePath());

      return $file && $matcher->matches($file);
    });
    $files = array_values($files);

    if ($return_only_directories) {
      $files = array_filter($files, fn($file) => self::isDir($file));
    }

    return $files;
  }

}
