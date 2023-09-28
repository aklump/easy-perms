<?php

namespace AKlump\EasyPerms\Traits;


trait PathHandlerTrait {

  private static function isDir(string $path): bool {
    return '/' === substr($path, -1);
  }

}
