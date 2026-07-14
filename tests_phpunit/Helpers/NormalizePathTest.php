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
    $this->assertSame("$base/$normalized", (new NormalizePath())("$base/$path"));
  }

  public function testNoBasePathWithRelativePathThrows() {
    $this->expectException(\RuntimeException::class);
    (new NormalizePath())('lorem_dir');
  }

  public function testNoBasePathWithAbsolutePathWorksAsExpected() {
    $base = $this->getBasePath();
    $path = "$base/lorem_dir";
    $this->assertSame("$base/lorem_dir/", (new NormalizePath())($path));
  }

  public function testRemoveDots() {
    $base = $this->getBasePath();
    $path = "$base/../files/lorem_dir";
    $this->assertSame("$base/lorem_dir/", (new NormalizePath())($path));
  }

  public function testEnsureForwardSlashes() {
    $base = $this->getBasePath();
    $path = "$base\\app\\web\\";
    $this->assertSame("$base/app/web/", (new NormalizePath())($path));
  }

  public function testAddTrailingSlashToDirectory() {
    $base = $this->getBasePath();
    $path = "$base/app";
    $this->assertSame("$base/app/", (new NormalizePath())($path));
  }

  public function testConvertPathToAbsolute() {
    $base = $this->getBasePath();
    $this->assertSame("$base/app/", (new NormalizePath($base))('app/'));
  }

}
