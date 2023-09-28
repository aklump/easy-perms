<?php

namespace AKlump\EasyPerms\Helpers;

use Symfony\Component\Filesystem\Path;

class GetLabel {

  /**
   * Get a path label, relative to CWD preserving directory trailing slash.
   *
   * @param string $path
   *
   * @return string
   */
  public function __invoke(string $path): string {
    $label = Path::makeRelative($path, getcwd());
    if ((new IsDir())($path)) {
      $label .= '/';
    }

    return $label;
  }

}
