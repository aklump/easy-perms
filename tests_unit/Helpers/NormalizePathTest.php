<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\NormalizePath;
use AKlump\EasyPerms\Tests\FilesTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\NormalizePath
 */
class NormalizePathTest extends TestCase {

  use FilesTestTrait;

  public function dataFortestInvokeProvider() {
    $tests = [];
    $tests[] = [
      'lorem_file/',
      'lorem_file',
    ];
    $tests[] = [
      'lorem_dir',
      'lorem_dir/',
    ];
    $tests[] = [
      'app/web/../web/sites/sites.php',
      "app/web/sites/sites.php",
    ];
    $tests[] = [
      'app/web/../../app/web/sites/default',
      "app/web/sites/default/",
    ];
    $tests[] = [
      'app/web/sites/sites.php',
      "app/web/sites/sites.php",
    ];
    $tests[] = [
      'app/web',
      "app/web/",
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(string $path, string $normalized) {
    $base = $this->getBasePath();
    // Send relative $path.
    $this->assertSame("$base/$normalized", (new NormalizePath($base))($path));
    // Send absolute $path.
    $this->assertSame("$base/$normalized", (new NormalizePath($base))("$base/$path"));
  }

}
