<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\HandleSymlinks;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Helpers\GetFileList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @covers \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @covers \AKlump\EasyPerms\Helpers\GetFileList
 * @uses \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses \AKlump\EasyPerms\Traits\HasBasePathTrait
 */
class EdgeCasesTest extends TestCase {

  private string $tempDir;
  private Filesystem $filesystem;

  protected function setUp(): void {
    $this->tempDir = sys_get_temp_dir() . '/easy_perms_tests_' . uniqid();
    $this->filesystem = new Filesystem();
    $this->filesystem->mkdir($this->tempDir);
  }

  protected function tearDown(): void {
    $this->filesystem->remove($this->tempDir);
  }

  public function testCircularSymlinkDoesNotCauseInfiniteRecursion() {
    $linkA = $this->tempDir . '/linkA';
    $linkB = $this->tempDir . '/linkB';
    
    // Create circular symlink: linkA -> linkB -> linkA
    symlink($linkB, $linkA);
    symlink($linkA, $linkB);
    
    $handler = new HandleSymlinks();
    $result = $handler($linkA);
    
    $this->assertCount(2, $result);
    $this->assertContains($linkA, $result);
    $this->assertContains($linkB, $result);
  }

  public function testBrokenSymlinkDoesNotCauseWarningInGetConcretePaths() {
    $link = $this->tempDir . '/broken_link';
    symlink($this->tempDir . '/non_existent', $link);
    
    $helper = new GetConcretePaths();
    // This should not trigger a warning because we used @ and try-catch
    $result = $helper($link);
    
    $this->assertNotEmpty($result);
    $this->assertEquals($link, $result[0]['path']);
    $this->assertNull($result[0]['perms']);
  }

  public function testBrokenSymlinkInDirectoryDoesNotCauseWarningInGetFileList() {
    $subDir = $this->tempDir . '/subdir';
    $this->filesystem->mkdir($subDir);
    $link = $subDir . '/broken_link';
    symlink($this->tempDir . '/non_existent', $link);
    
    $helper = new GetFileList();
    // This should not trigger a warning
    $result = $helper($subDir);
    
    $foundLink = false;
    foreach ($result as $item) {
      if ($item['path'] === $link || basename($item['path']) === 'broken_link') {
        $foundLink = true;
        $this->assertNull($item['perms']);
      }
    }
    $this->assertTrue($foundLink, 'Broken link should be found in file list');
  }

  public function testCircularSymlinkInDirectoryDoesNotCauseInfiniteRecursionInGetFileList() {
    $subDir = $this->tempDir . '/subdir';
    $this->filesystem->mkdir($subDir);
    $link = $subDir . '/link_to_parent';
    
    // Create circular symlink: subdir/link_to_parent -> subdir
    symlink('.', $link);
    
    $helper = new GetFileList();
    // If it doesn't handle circular symlinks, this will crash with memory exhaustion or recursion limit
    $result = $helper($subDir);
    
    $this->assertNotEmpty($result);
    // It should at least contain the subdir and the link
    $foundLink = false;
    foreach ($result as $item) {
      if (basename($item['path']) === 'link_to_parent') {
        $foundLink = true;
      }
    }
    $this->assertTrue($foundLink);
  }
}
