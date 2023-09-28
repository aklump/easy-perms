<?php

namespace AKlump\EasyPerms;

class DefaultFilePermissions implements \JsonSerializable {

  public function jsonSerialize() {
    return [
      'readonly' => 0444,
      'default' => 0644,
      'writeable' => 0644,
      'executable' => 0744,
    ];
  }
}
