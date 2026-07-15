<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\GetFileList;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetFileList
 * @uses \AKlump\EasyPerms\Helpers\NormalizePath
 */
class GetFileListTest extends TestCase {

  use TestWithFilesTrait;

  protected function tearDown(): void {
    $this->deleteAllTestFiles();
    parent::tearDown();
  }

  public function testInvokeReturnsFilesRecursively() {
    $base = $this->getTestFilePath('file_list_test/', TRUE);
    mkdir($base . 'subdir');
    touch($base . 'file1.txt');
    touch($base . 'subdir/file2.txt');

    $get_list = new GetFileList();
    $list = $get_list($base);

    $this->assertCount(4, $list); // base, file1.txt, subdir, subdir/file2.txt
    
    $paths = array_column($list, 'path');
    $this->assertContains($base, $paths);
    $this->assertContains($base . 'file1.txt', $paths);
    $this->assertContains($base . 'subdir/', $paths);
    $this->assertContains($base . 'subdir/file2.txt', $paths);
    
    $this->assertGreaterThan(0, $get_list->duration);
  }

  public function testInvokeWithNonExistentDirThrows() {
    $this->expectException(\UnexpectedValueException::class);
    (new GetFileList())('/non/existent/path');
  }

  public function testInvokeWithFileInsteadOfDirThrows() {
    $file = $this->getTestFilePath('not_a_dir.txt');
    touch($file);
    $this->expectException(\UnexpectedValueException::class);
    (new GetFileList())($file);
  }
}
