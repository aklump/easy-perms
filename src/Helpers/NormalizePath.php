<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Traits\HasBasePathTrait;
use Symfony\Component\Filesystem\Path;

class NormalizePath {

  use HasBasePathTrait;

  public function __invoke(string $path): string {
    $path = Path::normalize($path);
    if (!Path::isAbsolute($path)) {
      $path = Path::makeAbsolute($path, $this->getBasePath());
    }
    if (strstr($path, '..') !== FALSE) {
      $path = Path::canonicalize($path);
    }
    $path = rtrim($path, '/');
    if (file_exists($path)) {
      if (is_dir($path)) {
        $path .= '/';
      }
    }

    return $path;
  }

}
