<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\HandleMemory;

/**
 * @covers \AKlump\EasyPerms\Helpers\HandleMemory
 */
class HandleMemoryTest extends \PHPUnit\Framework\TestCase {

  public function testInvokeDoesNothingWhenExistingAboveClassMinimum() {
    $existing = '512M';
    $this->assertLessThan($existing, HandleMemory::MINIMUM);

    ini_set('memory_limit', $existing);
    (new HandleMemory())();
    $this->assertSame($existing, ini_get('memory_limit'));
  }

  public function testInvokeIncreasesWhenExistingBelowClassMinimum() {
    $existing = '128M';
    $this->assertGreaterThan($existing, HandleMemory::MINIMUM);

    ini_set('memory_limit', $existing);
    (new HandleMemory())();
    $this->assertSame(HandleMemory::MINIMUM, ini_get('memory_limit'));
  }

}
