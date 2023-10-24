<?php

namespace AKlump\EasyPerms\Config;

/**
 * @url https://www.drupal.org/docs/administering-a-drupal-site/security-in-drupal/securing-file-permissions-and-ownership#permissions
 */
class DefaultFilePermissions implements \JsonSerializable {

  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return [
      'default' => 0640,
      'readonly' => 0440,
      'writeable' => 0660,
      'executable' => 0770,
    ];
  }
}
