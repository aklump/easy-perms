<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\GetFileList;
use AKlump\EasyPerms\Tests\FilesTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetFileList
 */
class GetFileListTest extends TestCase {

  use FilesTestTrait;

  public function dataFortestInvokeReturnsExpectedArrayProvider() {
    $tests = [];
    $tests[] = [
      'app',
      [
        'app/',
        'app/web/',
        'app/web/sites/',
        'app/web/sites/all/',
        'app/web/sites/all/files/',
        'app/web/sites/default/',
        'app/web/sites/default/files/',
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
        'app/web/sites/sites.php',
      ],
    ];
    $tests[] = [
      'app/web/sites/',
      [
        'app/web/sites/',
        'app/web/sites/all/',
        'app/web/sites/all/files/',
        'app/web/sites/default/',
        'app/web/sites/default/files/',
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
        'app/web/sites/sites.php',
      ],
    ];
    $tests[] = [
      'app/web/sites/default/',
      [
        'app/web/sites/default/',
        'app/web/sites/default/files/',
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
      ],
    ];
    $tests[] = [
      'lorem_dir',
      ['lorem_dir/'],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeReturnsExpectedArrayProvider
   */
  public function testInvokeReturnsExpectedArray(string $start_dir, array $list) {
    $base = $this->getBasePath();
    $list = array_map(fn($path) => "$base/$path", $list);
    $this->assertSame($list, (new GetFileList())("$base/$start_dir"));
  }

  public function testDurationPublicVariableIsFloat() {
    $lister = new GetFileList();
    $lister($this->getBasePath());
    $this->assertIsFloat($lister->duration);
    $this->assertGreaterThan(0, $lister->duration);
  }

  public function testNonExistingThrows() {
    $this->expectException(\UnexpectedValueException::class);
    $path = $this->getBasePath() . '/bogus';
    (new GetFileList())($path);
  }

  public function testNotDirectoryThrows() {
    $this->expectException(\UnexpectedValueException::class);
    $path = $this->getBasePath() . '/lorem_file';
    (new GetFileList())($path);
  }

}
