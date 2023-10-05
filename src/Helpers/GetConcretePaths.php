<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\PathHandlerTrait;
use AKlump\GitIgnore\Analyzer;
use AKlump\GitIgnore\Pattern;
use Psr\SimpleCache\CacheInterface;

/**
 * @url https://git-scm.com/docs/gitignore#_pattern_format
 * @url https://www.digitalocean.com/community/tools/glob
 */
class GetConcretePaths {

  use PathHandlerTrait;

  /**
   * @var array|null
   */
  private $cache;

  /**
   * @param \Psr\SimpleCache\CacheInterface|NULL $filepath_cache
   *   Passing a cache instance in most all cases will enhance performance, that
   *   is decrease the time used to complete multiple calls.
   */
  public function __construct(CacheInterface $filepath_cache = NULL) {
    $this->cache = $filepath_cache;
  }

  /**
   * Get all concrete paths as matched by $path.
   *
   * @param string $path
   *   A file/dir matching rule or value.
   *
   * @return string[]
   *   All matched paths sorted alphabetically.  If a path is matched to a
   *   symlink, the target of the symlink will be included in this list.
   *
   * @throws
   */
  public function __invoke(string $path): array {
    $return_only_directories = self::isDir($path);
    $symlink_handler = new HandleSymlinks();
    if (file_exists($path)) {
      $files = $symlink_handler($path);
      $files = array_map(fn($path) => (new NormalizePath())($path), $files);
    }
    else {
      do {
        if (empty($start_dir)) {
          $start_dir = $path;
        }
        else {
          $start_dir = substr($start_dir, 0, -1);
        }
      } while ($start_dir && !is_dir("$start_dir"));

      $matcher = new Pattern($path);
      $files = $this->getFileList($start_dir);
      $files = array_filter($files, function ($file) use ($matcher) {
        return $file && $matcher->matches($file);
      });
      $files = array_values($files);

      // Include any possible symlink targets in our file list.
      $size = count($files);
      $resolved = [];
      for ($i = 0; $i < $size; $i++) {
        $path = $files[$i];
        $symlink_resolution = $symlink_handler($path);
        if (isset($symlink_resolution[1])) {
          $resolved = array_merge($resolved, $symlink_resolution);
        }
        else {
          $resolved[] = $path;
        }
      }
      if (count($files) !== count($resolved)) {
        $files = array_unique($resolved);
      }
      else {
        $files = $resolved;
      }
    }

    sort($files);
    if ($return_only_directories) {
      $files = array_filter($files, fn($file) => self::isDir($file));
    }

    return $files;
  }

  private function getFileList(string $start_dir): array {
    if (NULL !== $this->cache) {
      if ($this->cache->has($start_dir)) {
        $files = $this->cache->get($start_dir);
      }
    }
    if (!isset($files)) {
      $files = (new GetFileList())($start_dir);
      if (NULL !== $this->cache) {
        $this->cache->set($start_dir, $files);
      }
    }

    return $files;
  }

}
