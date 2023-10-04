<?php

namespace AKlump\EasyPerms\Helpers;

class HandleMemory {
  const MINIMUM = '384M';

  public function __invoke(): void {
    if ($this->getCurrentLimit() < self::MINIMUM) {
      ini_set('memory_limit', self::MINIMUM);
    }
  }

  protected function getCurrentLimit(): string {
    return ini_get('memory_limit');
  }

}
