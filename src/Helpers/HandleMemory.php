<?php

namespace AKlump\EasyPerms\Helpers;

class HandleMemory {
  const MINIMUM = '512M';

  /**
   * Raise the memory limit to ::MINIMUM when it is lower than that.
   *
   * An existing higher limit is left alone, as is an unlimited one.  Note the
   * comparison must be made in bytes: as strings "1G" sorts below "512M", so
   * comparing the php.ini shorthand directly would reduce the limit.
   *
   * @return void
   */
  public function __invoke(): void {
    $current = $this->toBytes($this->getCurrentLimit());
    // A negative limit means unlimited, which is never worth reducing.
    if ($current < 0) {
      return;
    }
    if ($current < $this->toBytes(self::MINIMUM)) {
      ini_set('memory_limit', self::MINIMUM);
    }
  }

  protected function getCurrentLimit(): string {
    return (string) ini_get('memory_limit');
  }

  /**
   * Resolve a php.ini shorthand byte value.
   *
   * @param string $value
   *   A value such as "512M", "1G", "134217728" or "-1".
   *
   * @return int
   *   The value in bytes; negative means unlimited.
   *
   * @url https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
   */
  private function toBytes(string $value): int {
    $value = trim($value);
    if ($value === '') {
      return 0;
    }
    $number = (int) $value;
    switch (strtolower(substr($value, -1))) {
      case 'g':
        return $number * 1024 * 1024 * 1024;

      case 'm':
        return $number * 1024 * 1024;

      case 'k':
        return $number * 1024;

      default:
        return $number;
    }
  }

}
