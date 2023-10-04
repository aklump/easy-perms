<?php

namespace AKlump\EasyPerms\Helpers;

use AKlump\EasyPerms\Config\ConfigInterface;

/**
 * Sort configuration keys into proper apply order.
 *
 * If permissions are not applied in the correct order, the result will not be
 * correct.  This class handles the correct ordering.
 */
class SortPermissionTypes {

  public function __invoke(array $types) {
    $weights = array_flip([
      ConfigInterface::DEFAULT,
      ConfigInterface::WRITEABLE,
      ConfigInterface::EXECUTABLE,
      ConfigInterface::READONLY,
    ]);
    uasort($types, function ($a, $b) use ($weights) {
      return ($weights[$a] ?? 0) - ($weights[$b] ?? 0);
    });

    return array_values($types);
  }

}
