<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\ConfigInterface;
use AKlump\EasyPerms\Helpers\SortPermissionTypes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\SortPermissionTypes
 */
class SortPermissionTypesTest extends TestCase {

  public function dataFortestInvokeProvider() {
    $tests = [];
    $tests[] = [
      [
        ConfigInterface::WRITEABLE,
        ConfigInterface::READONLY,
        ConfigInterface::DEFAULT,
        ConfigInterface::EXECUTABLE,
      ],
      [
        ConfigInterface::DEFAULT,
        ConfigInterface::WRITEABLE,
        ConfigInterface::EXECUTABLE,
        ConfigInterface::READONLY,
      ],
    ];
    $tests[] = [
      [
        ConfigInterface::DEFAULT,
        ConfigInterface::WRITEABLE,
        ConfigInterface::EXECUTABLE,
        ConfigInterface::READONLY,
      ],
      [
        ConfigInterface::DEFAULT,
        ConfigInterface::WRITEABLE,
        ConfigInterface::EXECUTABLE,
        ConfigInterface::READONLY,
      ],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke($unsorted, $sorted) {
    $this->assertSame($sorted, (new SortPermissionTypes())($unsorted));
  }

}
