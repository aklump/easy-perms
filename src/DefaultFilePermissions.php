<?php

namespace AKlump\EasyPerms;

class DefaultFilePermissions implements \JsonSerializable {

  public function jsonSerialize() {
    return [
      'default' => 0644,
      'writeable' => 0644,
      'executable' => 0744,
    ];
  }
}
