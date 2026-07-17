<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\PathHandlerTrait;
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
  public function __construct(?CacheInterface $filepath_cache = NULL) {
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
    if (empty($path)) {
      return [];
    }
    $return_only_directories = self::isDir($path);
    $symlink_handler = new HandleSymlinks();
    $normalizer = new NormalizePath();
    if (file_exists($path)) {
      $files_data = $symlink_handler($path);
      $files_data = array_map(function ($p) use ($normalizer) {
        $is_dir = is_dir($p);

        return [
          'path' => $normalizer($p, $is_dir),
          'is_dir' => $is_dir,
          'is_link' => is_link($p),
          'perms' => (function ($path) {
            try {
              $p = @fileperms($path);

              return $p !== FALSE ? substr(decoct($p), -4) : NULL;
            }
            catch (\Throwable $e) {
              return NULL;
            }
          })($p),
          'realpath' => realpath($p),
        ];
      }, $files_data);

      if ($this->cache) {
        $matched_realpaths = [];
        foreach ($files_data as $data) {
          if (!empty($data['realpath'])) {
            $matched_realpaths[$data['realpath']] = TRUE;
          }
        }
        if (!empty($matched_realpaths)) {
          $deduped = [];
          foreach ($files_data as $data) {
            $deduped[$data['path']] = $data;
          }
          if (method_exists($this->cache, 'getKeys')) {
            foreach ($this->cache->getKeys() as $cached_dir) {
              $all_files = $this->cache->get($cached_dir);
              foreach ($all_files as $data) {
                if (!empty($data['realpath']) && isset($matched_realpaths[$data['realpath']])) {
                  $deduped[$data['path']] = $data;
                }
              }
            }
          }
          $files_data = array_values($deduped);
        }
      }
    }
    else {
      $start_dir = $path;
      while ($start_dir && !is_dir($start_dir)) {
        $prev_dir = $start_dir;
        $start_dir = dirname($start_dir);
        if ($start_dir === $prev_dir) {
          $start_dir = '.';
          break;
        }
      }
      if (!$start_dir || !is_dir($start_dir)) {
        $start_dir = '.';
      }

      $matcher = new Pattern($path);
      $all_files = $this->getFileList($start_dir);
      $files_data = [];
      $matched_realpaths = [];
      foreach ($all_files as $data) {
        $item_path = $data['path'];
        if ($item_path && $matcher->matches($item_path)) {
          $files_data[$item_path] = $data;
          if (!empty($data['realpath'])) {
            $matched_realpaths[$data['realpath']] = TRUE;
          }
        }
      }

      // Include all aliases (symlinks pointing to the same realpath)
      foreach ($all_files as $data) {
        if (!empty($data['realpath']) && isset($matched_realpaths[$data['realpath']])) {
          $files_data[$data['path']] = $data;
        }
      }
      $files_data = array_values($files_data);

      // Include any possible symlink targets in our file list.
      $resolved = [];
      foreach ($files_data as $data) {
        $path = is_array($data) ? $data['path'] : $data;
        $resolved[$path] = $data;

        $is_link = is_array($data) ? $data['is_link'] : is_link($path);
        if ($is_link) {
          $symlink_resolution = $symlink_handler($path);
          foreach ($symlink_resolution as $resolved_path) {
            if (!isset($resolved[$resolved_path])) {
              $is_dir_res = is_dir($resolved_path);
              $resolved[$resolved_path] = [
                'path' => $normalizer($resolved_path, $is_dir_res),
                'is_dir' => $is_dir_res,
                'is_link' => is_link($resolved_path),
                'perms' => (function ($path) {
                  try {
                    $p = @fileperms($path);

                    return $p !== FALSE ? substr(decoct($p), -4) : NULL;
                  }
                  catch (\Throwable $e) {
                    return NULL;
                  }
                })($resolved_path),
                'realpath' => realpath($resolved_path),
              ];
            }
          }
        }
      }
      $files_data = array_values($resolved);
    }

    if ($return_only_directories) {
      $directories = [];
      foreach ($files_data as $data) {
        $is_item_dir = is_array($data) ? $data['is_dir'] : self::isDir($data);
        if ($is_item_dir) {
          $directories[] = $data;
        }
      }
      $files_data = $directories;
    }

    usort($files_data, function ($a, $b) {
      $a_path = is_array($a) ? $a['path'] : $a;
      $b_path = is_array($b) ? $b['path'] : $b;

      return strcmp($a_path, $b_path);
    });

    return $files_data;
  }

  private function getFileList(string $start_dir): array {
    $normalizer = new NormalizePath();
    $start_dir = $normalizer($start_dir, TRUE);
    if (NULL !== $this->cache) {
      if ($this->cache->has($start_dir)) {
        return $this->cache->get($start_dir);
      }
      if (method_exists($this->cache, 'getKeys')) {
        foreach ($this->cache->getKeys() as $cached_dir) {
          if (strpos($start_dir, $cached_dir) === 0) {
            $all_files = $this->cache->get($cached_dir);
            $files = [];
            foreach ($all_files as $data) {
              $path = is_array($data) ? $data['path'] : $data;
              if (strpos($path, $start_dir) === 0) {
                $files[] = $data;
              }
            }

            return $files;
          }
        }
      }
    }
    $files = (new GetFileList())($start_dir);
    if (NULL !== $this->cache) {
      $this->cache->set($start_dir, $files);
    }

    return $files;
  }

}
