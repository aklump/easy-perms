<?php

namespace AKlump\EasyPerms;

class DefaultDirectoryPermissions implements \JsonSerializable {

  public function jsonSerialize() {
    return [
      'default' => 0755,
      'writeable' => 0777,
      'executable' => 0755,
    ];
  }
}
