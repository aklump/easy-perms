<?php

namespace AKlump\EasyPerms\Helpers;

use Lead\Dir\Dir;

/**
 * Generate a list of all paths within a directory and all child directories.
 */
class GetFileList {

  /**
   * @var float Time in microseconds used for the most-recent invocation.
   */
  public float $duration;

  /**
   * @param string $start_dir
   *
   * @return array
   *   The start directory, plus all paths within it, recursively.
   * @throws \Exception
   */
  public function __invoke(string $start_dir): array {
    if (!file_exists($start_dir)) {
      throw new \UnexpectedValueException(sprintf('%s does not exist.', $start_dir));
    }
    if (!is_dir($start_dir)) {
      throw new \UnexpectedValueException(sprintf('%s must be a directory.', $start_dir));
    }
    $this->duration = microtime(TRUE);
    $list = Dir::scan($start_dir);
    $list[] = $start_dir;
    $list = array_map(fn($path) => (new NormalizePath($start_dir))($path), $list);
    sort($list);
    $this->duration = microtime(TRUE) - $this->duration;

    return $list;
  }

}
