<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\GetLabel;
use AKlump\EasyPerms\Helpers\GetShortPath;
use AKlump\EasyPerms\Helpers\IsDir;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetLabel
 * @covers \AKlump\EasyPerms\Helpers\GetShortPath
 * @covers \AKlump\EasyPerms\Helpers\IsDir
 * @covers \AKlump\EasyPerms\Traits\PathHandlerTrait
 */
class HelpersTest extends TestCase {

  use TestWithFilesTrait;

  public function testIsDir() {
    $is_dir = new IsDir();
    $this->assertTrue($is_dir('some/path/'));
    $this->assertFalse($is_dir('some/file.txt'));
  }

  public function testGetShortPath() {
    $base = '/Users/aklump/Code';
    $get_short = new GetShortPath($base);
    $this->assertSame('Packages/cli/easy-perms', $get_short($base . '/Packages/cli/easy-perms'));
    $this->assertSame('/Other/Path', $get_short('/Other/Path'));

    $cwd = getcwd();
    $get_short_cwd = new GetShortPath();
    $this->assertSame('./some/file.txt', $get_short_cwd($cwd . '/some/file.txt'));
  }

  public function testGetLabel() {
    $cwd = getcwd();
    $get_label = new GetLabel();

    $file_path = $cwd . '/some_file.txt';
    $this->assertSame('some_file.txt', $get_label($file_path));

    $dir_path = $cwd . '/some_dir/';
    $this->assertSame('some_dir/', $get_label($dir_path));
  }
}
