<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Cache;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @uses   \AKlump\EasyPerms\Cache
 * @uses   \AKlump\EasyPerms\Helpers\GetFileList
 * @uses   \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses   \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses   \AKlump\EasyPerms\Traits\HasBasePathTrait
 * @uses   \AKlump\EasyPerms\Helpers\IsDir
 */
class GetConcretePathsCoverageTest extends TestCase {

  use TestWithFilesTrait;

  public function testGetFileListCacheHit() {
    $cache = new Cache();
    $start_dir = $this->getTestFixturesDirectory() . 'app';
    $cached_data = [['path' => 'cached/path', 'realpath' => 'realpath']];
    $cache->set($start_dir . DIRECTORY_SEPARATOR, $cached_data);

    $obj = new GetConcretePaths($cache);
    // Use reflection to call private getFileList
    $method = new \ReflectionMethod(GetConcretePaths::class, 'getFileList');
    $method->setAccessible(TRUE);
    
    $result = $method->invoke($obj, $start_dir);
    $this->assertSame($cached_data, $result);
  }

  public function testGetFileListCachePrefixHit() {
    $cache = new Cache();
    $parent_dir = $this->getTestFixturesDirectory();
    $sub_dir = $parent_dir . 'app';
    
    $cached_data = [
      ['path' => $sub_dir . DIRECTORY_SEPARATOR . 'file1.txt'],
      ['path' => $parent_dir . 'other.txt'],
    ];
    $cache->set($parent_dir, $cached_data);

    $obj = new GetConcretePaths($cache);
    $method = new \ReflectionMethod(GetConcretePaths::class, 'getFileList');
    $method->setAccessible(TRUE);
    
    $result = $method->invoke($obj, $sub_dir);
    $this->assertCount(1, $result);
    $this->assertSame($cached_data[0]['path'], $result[0]['path']);
  }

  public function testInvokeWithCacheDeduping() {
    $cache = new Cache();
    $fixtures_dir = $this->getTestFixturesDirectory();
    $file_path = $fixtures_dir . 'lorem_file';
    $real_path = realpath($file_path);
    
    // Pre-populate cache with an "alias" for the same realpath
    $cached_data = [
      [
        'path' => $fixtures_dir . 'alias_file',
        'is_dir' => FALSE,
        'is_link' => TRUE,
        'perms' => '0644',
        'realpath' => $real_path,
      ]
    ];
    $cache->set($fixtures_dir, $cached_data);

    $obj = new GetConcretePaths($cache);
    $result = $obj($file_path);
    
    // We expect both the actual file and the cached alias to be returned
    $paths = array_column($result, 'path');
    $this->assertContains($file_path, $paths);
    $this->assertContains($fixtures_dir . 'alias_file', $paths);
  }

  public function testInvokeWithGlobAndSymlinks() {
    $fixtures_dir = $this->getTestFixturesDirectory();
    $obj = new GetConcretePaths();
    
    // 'lorem_symlink' points to 'lorem_file'
    $result = $obj($fixtures_dir . 'lorem_symlink');
    $paths = array_column($result, 'path');
    
    $this->assertContains($fixtures_dir . 'lorem_file', $paths);
    $this->assertContains($fixtures_dir . 'lorem_symlink', $paths);
  }
  
  public function testInvokeWithEmptyPath() {
      $obj = new GetConcretePaths();
      $result = $obj('');
      $this->assertEmpty($result);
  }

  public function testInvokeWithGlobAndRealSymlink() {
    $fixtures_dir = $this->getTestFixturesDirectory();
    $obj = new GetConcretePaths();
    
    // Use a glob that will definitely match a symlink and its target
    // 'lorem*' matches lorem_file, lorem_dir, lorem_symlink
    $result = $obj($fixtures_dir . 'lorem*');
    $paths = array_column($result, 'path');
    
    $this->assertContains($fixtures_dir . 'lorem_file', $paths);
    $this->assertContains($fixtures_dir . 'lorem_symlink', $paths);
  }

}
