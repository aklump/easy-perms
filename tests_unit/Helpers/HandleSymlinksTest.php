<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\HandleSymlinks;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\HandleSymlinks
 */
class HandleSymlinksTest extends TestCase {

  use \AKlump\EasyPerms\Tests\FilesTestTrait;

  public function testInvoke() {
    $base = $this->getBasePath();
    $path = "$base/lorem_symlink";
    $files = (new HandleSymlinks())($path);
    $this->assertCount(2, $files);
    $this->assertContains("$base/lorem_symlink", $files);
    $this->assertContains("$base/lorem_file", $files);
  }

}
