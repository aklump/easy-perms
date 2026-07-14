<?php

namespace AKlump\EasyPerms\Helpers;

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
   * @return array[]
   *   The start directory, plus all paths within it, recursively. Each element
   *   is an array with keys: path, is_dir, is_link, perms.
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
    $queue = [$start_dir];
    $visited = [];
    $start_realpath = realpath($start_dir);
    if ($start_realpath) {
      $visited[$start_realpath] = TRUE;
    }

    $normalizer = new NormalizePath($start_dir);
    $list = [];

    // Add the start directory itself.
    $list[] = [
      'path' => $normalizer($start_dir, TRUE),
      'is_dir' => TRUE,
      'is_link' => is_link($start_dir),
      'perms' => (function ($path) {
        try {
          $p = @fileperms($path);

          return $p !== FALSE ? substr(decoct($p), -4) : NULL;
        }
        catch (\Throwable $e) {
          return NULL;
        }
      })($start_dir),
      'realpath' => realpath($start_dir),
    ];

    while (!empty($queue)) {
      $current_dir = array_shift($queue);
      try {
        $it = new \DirectoryIterator($current_dir);
      }
      catch (\Throwable $e) {
        continue;
      }
      foreach ($it as $info) {
        if ($info->isDot()) {
          continue;
        }

        $path = $info->getPathname();
        $is_dir = $info->isDir();
        $is_link = $info->isLink();
        $perms = NULL;
        try {
          $raw_perms = @$info->getPerms();
          if (FALSE !== $raw_perms) {
            $perms = substr(decoct($raw_perms), -4);
          }
        }
        catch (\Throwable $e) {
        }

        $list[] = [
          'path' => $normalizer($path, $is_dir),
          'is_dir' => $is_dir,
          'is_link' => $is_link,
          'perms' => $perms,
          'realpath' => $info->getRealPath(),
        ];

        if ($is_dir) {
          $realpath = $info->getRealPath();
          if ($realpath && !isset($visited[$realpath])) {
            $visited[$realpath] = TRUE;
            $queue[] = $path;
          }
        }
      }
    }
    usort($list, fn($a, $b) => strcmp($a['path'], $b['path']));
    $this->duration = microtime(TRUE) - $this->duration;

    return $list;
  }

}
