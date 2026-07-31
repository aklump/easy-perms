<?php

namespace AKlump\EasyPerms\Helpers;

/**
 * Generate a list of all paths within a directory and all child directories.
 */
class GetFileList {

  /**
   * Upper bound on passes used to derive nested symlinked directory contents.
   *
   * @see ::addAliasedDirectoryContents
   */
  const MAX_ALIAS_PASSES = 10;

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
    $list = $this->addAliasedDirectoryContents($list, $normalizer, $start_dir, $start_realpath);
    usort($list, fn($a, $b) => strcmp($a['path'], $b['path']));
    $this->duration = microtime(TRUE) - $this->duration;

    return $list;
  }

  /**
   * Add the contents of symlinked directories under their alias path.
   *
   * A directory reachable by two paths inside $start_dir is only walked once;
   * the shallower path wins and the other receives a single stub entry.  That
   * means a pattern written against the alias path--e.g.
   * "web/themes/custom/my_theme/bin/*" for a symlink into "install/"--could
   * never match, because no enumerated path began with the alias.
   *
   * The missing entries are derived here from the entries already collected,
   * rather than walking the filesystem a second time.  Both paths share an
   * inode, so the copied is_dir, perms and realpath values are accurate, and
   * chmod against either path is the same operation.
   *
   * Which of the two paths ends up short depends on which the walk reached
   * first, so the gap is filled in whichever direction it appears.
   *
   * Nothing is derived for a symlink whose contents were already walked under
   * the alias while its target sits outside $start_dir--that target was never
   * claimed by another path, so there is no gap and deriving would duplicate
   * every entry.
   *
   * @param array $list
   *   The entries collected by the walk.
   * @param \AKlump\EasyPerms\Helpers\NormalizePath $normalizer
   * @param string $start_dir
   * @param string|false $start_realpath
   *
   * @return array
   *   $list, plus one entry per path made reachable through a symlink.
   */
  private function addAliasedDirectoryContents(array $list, NormalizePath $normalizer, string $start_dir, $start_realpath): array {
    $aliases = [];
    foreach ($list as $item) {
      if ($item['is_link'] && $item['is_dir'] && !empty($item['realpath'])) {
        $aliases[$item['path']] = $item['realpath'];
      }
    }
    if (empty($aliases)) {
      return $list;
    }

    // realpath() resolves symlinks in the start directory too, e.g. on macOS
    // "/tmp" becomes "/private/tmp".  Translate targets back into the walked
    // namespace so they can be compared against the collected paths.
    $normalized_start = $normalizer($start_dir, TRUE);
    $start_realpath = $start_realpath ? rtrim($start_realpath, '/') . '/' : '';
    $to_walked_path = function (string $realpath) use ($normalized_start, $start_realpath): string {
      $realpath = rtrim($realpath, '/') . '/';
      if ($start_realpath && strpos($realpath, $start_realpath) === 0) {
        return $normalized_start . substr($realpath, strlen($start_realpath));
      }

      return $realpath;
    };

    // Group entries by parent directory so a subtree can be copied by
    // following the index, rather than scanning every entry once per alias.
    // $walked reflects the filesystem walk only and is never extended, so a
    // derived nested alias is not mistaken for one that was already walked.
    $index = [];
    $walked = [];
    $children = [];
    foreach ($list as $item) {
      $index[$item['path']] = TRUE;
      $parent = $this->getParent($item['path']);
      $walked[$parent] = TRUE;
      $children[$parent][] = $item;
    }

    // A derived subtree may itself hold a symlinked directory, so repeat until
    // nothing new appears.  The cap guarantees termination even when aliases
    // reference one another.
    for ($pass = 0; $pass < self::MAX_ALIAS_PASSES; $pass++) {
      $added = [];
      foreach ($aliases as $alias => $realpath) {
        $alias_prefix = rtrim($alias, '/') . '/';
        $target_prefix = $to_walked_path($realpath);
        if ($alias_prefix === $target_prefix) {
          continue;
        }
        // A symlink to its own ancestor or descendant would derive
        // alias/alias/alias... without end.
        if (strpos($alias_prefix, $target_prefix) === 0 || strpos($target_prefix, $alias_prefix) === 0) {
          continue;
        }
        if (isset($walked[$alias_prefix])) {
          // The alias was walked rather than the target, because it is the
          // shallower of the two paths.  The gap is on the target side, so fill
          // that instead--but only when the target is itself within the walked
          // tree, otherwise there is no path to attach the entries to.
          if (!isset($walked[$target_prefix]) && isset($index[$target_prefix])) {
            $this->copySubtree($alias_prefix, $target_prefix, $children, $index, $added);
          }
          continue;
        }
        $this->copySubtree($target_prefix, $alias_prefix, $children, $index, $added);
      }
      if (empty($added)) {
        break;
      }
      foreach ($added as $item) {
        $list[] = $item;
        $children[$this->getParent($item['path'])][] = $item;
        if ($item['is_link'] && $item['is_dir'] && !empty($item['realpath'])) {
          $aliases[$item['path']] = $item['realpath'];
        }
      }
    }

    return $list;
  }

  /**
   * Copy the entries beneath $source_dir to sit beneath $destination_dir.
   *
   * Only directories reached by the walk have an entry in $children, so a
   * symlinked directory nested inside the subtree contributes its own entry and
   * stops there; its contents are picked up on a later pass.  That also keeps
   * this terminating, since the walk itself is free of cycles.
   *
   * @param string $source_dir
   *   Directory to copy from, with a trailing slash.
   * @param string $destination_dir
   *   Directory to copy to, with a trailing slash.
   * @param array $children
   *   Entries grouped by parent directory.
   * @param array $index
   *   All known paths, by reference, to prevent duplicates.
   * @param array $added
   *   Collects the derived entries, by reference.
   *
   * @return void
   */
  private function copySubtree(string $source_dir, string $destination_dir, array $children, array &$index, array &$added): void {
    $queue = [[$source_dir, $destination_dir]];
    while (!empty($queue)) {
      list($from, $to) = array_pop($queue);
      if (empty($children[$from])) {
        continue;
      }
      $length = strlen($from);
      foreach ($children[$from] as $item) {
        $derived = $to . substr($item['path'], $length);
        if (isset($index[$derived])) {
          continue;
        }
        $index[$derived] = TRUE;
        $source_path = $item['path'];
        $item['path'] = $derived;
        $added[] = $item;
        if ($item['is_dir']) {
          $queue[] = [$source_path, $derived];
        }
      }
    }
  }

  /**
   * @param string $path
   *
   * @return string
   *   The parent directory of $path, with a trailing slash.
   */
  private function getParent(string $path): string {
    return rtrim(dirname(rtrim($path, '/')), '/') . '/';
  }

}
