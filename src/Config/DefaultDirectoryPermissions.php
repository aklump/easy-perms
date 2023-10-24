<?php

namespace AKlump\EasyPerms\Config;

/**
 * @url https://www.drupal.org/docs/administering-a-drupal-site/security-in-drupal/securing-file-permissions-and-ownership#permissions
 */
class DefaultDirectoryPermissions implements \JsonSerializable {
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return [
      'default' => 0750,
      'readonly' => 0550,
      'writeable' => 0770,
      'executable' => 0750,
    ];
  }
}
