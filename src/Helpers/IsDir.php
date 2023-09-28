<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\PathHandlerTrait;

class IsDir {

  use PathHandlerTrait;

  /**
   * @param string $path
   *
   * @return bool
   *   True if $path is a directory.
   */
  public function __invoke(string $path): bool {
    return self::isDir($path);
  }

}
