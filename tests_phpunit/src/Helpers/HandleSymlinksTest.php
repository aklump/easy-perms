<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\HandleSymlinks;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses   \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses   \AKlump\EasyPerms\Traits\HasBasePathTrait
 */
class HandleSymlinksTest extends TestCase {

  use TestWithFilesTrait;

  public function testInvoke() {
    $fixtures_dir = $this->getTestFixturesDirectory();
    $path = $fixtures_dir . 'links/symlink_l3';
    $files = (new HandleSymlinks())($path);
    $this->assertCount(4, $files);
    $this->assertContains($fixtures_dir . 'links/symlink_l3', $files);
    $this->assertContains($fixtures_dir . 'symlink_l2', $files);
    $this->assertContains($fixtures_dir . 'lorem_symlink', $files);
    $this->assertContains($fixtures_dir . 'lorem_file', $files);
  }

}
