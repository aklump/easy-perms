<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Cache;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Helpers\GetFileList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A directory reachable by two paths is only walked once, so without alias
 * derivation a pattern written against the second path can never match.
 *
 * @covers \AKlump\EasyPerms\Helpers\GetFileList
 * @uses   \AKlump\EasyPerms\Cache
 * @uses   \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @uses   \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses   \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses   \AKlump\EasyPerms\Traits\HasBasePathTrait
 * @uses   \AKlump\EasyPerms\Traits\PathHandlerTrait
 */
class GetFileListSymlinkAliasTest extends TestCase {

  private string $root;

  private Filesystem $filesystem;

  protected function setUp(): void {
    $this->filesystem = new Filesystem();
    $this->root = sys_get_temp_dir() . '/easy_perms_alias_' . uniqid();
    $this->filesystem->mkdir($this->root);
  }

  protected function tearDown(): void {
    // Filesystem::remove does not follow symlinks out of the sandbox.
    $this->filesystem->remove($this->root);
  }

  /**
   * @param string $relative
   *   A path relative to the sandbox root; a trailing slash means directory.
   */
  private function make(string $relative): string {
    $path = $this->root . '/' . ltrim($relative, '/');
    if (substr($relative, -1) === '/') {
      $this->filesystem->mkdir($path);
    }
    else {
      $this->filesystem->mkdir(dirname($path));
      touch($path);
    }

    return $path;
  }

  /**
   * @return string[]
   *   Every enumerated path, relative to the sandbox root.
   */
  private function listRelative(?string $start = NULL): array {
    $start = $start ?? $this->root;
    $prefix = rtrim($start, '/') . '/';
    $paths = [];
    foreach ((new GetFileList())($start) as $item) {
      $paths[] = $item['path'] === $prefix ? '' : substr($item['path'], strlen($prefix));
    }

    return $paths;
  }

  /**
   * The reported case: a theme symlinked out of a package directory. The real
   * path is the shallower of the two, so the walk claims it and the alias is
   * left as a stub.
   */
  public function testContentsAreEnumeratedUnderTheAliasPath() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $paths = $this->listRelative();

    $this->assertContains('install/pkg/bin/tool.sh', $paths, 'The real path must still be enumerated.');
    $this->assertContains('web/themes/custom/theme/bin/tool.sh', $paths, 'The path reachable through the symlink must also be enumerated.');
  }

  /**
   * The mirror image: when the alias is the shallower path the walk claims it
   * instead, and the gap falls on the real side.
   */
  public function testContentsAreEnumeratedUnderTheRealPathWhenTheAliasIsShallower() {
    $this->make('a/b/c/real/pkg/bin/tool.sh');
    symlink('a/b/c/real/pkg', $this->root . '/link');

    $paths = $this->listRelative();

    $this->assertContains('link/bin/tool.sh', $paths, 'The shallower alias must be enumerated.');
    $this->assertContains('a/b/c/real/pkg/bin/tool.sh', $paths, 'The deeper real path must also be enumerated.');
  }

  public function testBothAliasesToTheSameTargetAreEnumerated() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('web/modules/custom/');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/modules/custom/mod');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $paths = $this->listRelative();

    $this->assertContains('web/modules/custom/mod/bin/tool.sh', $paths);
    $this->assertContains('web/themes/custom/theme/bin/tool.sh', $paths);
    $this->assertContains('install/pkg/bin/tool.sh', $paths);
  }

  /**
   * A symlink out of the tree is descended into by the walk, since nothing else
   * claims its target. Deriving as well would duplicate every entry.
   */
  public function testTargetOutsideTheStartDirectoryIsNotDuplicated() {
    $outside = sys_get_temp_dir() . '/easy_perms_alias_outside_' . uniqid();
    $this->filesystem->mkdir($outside . '/pkg/bin');
    touch($outside . '/pkg/bin/tool.sh');
    try {
      $this->make('vendor/');
      symlink($outside . '/pkg', $this->root . '/vendor/thing');

      $paths = $this->listRelative();

      $this->assertContains('vendor/thing/bin/tool.sh', $paths);
      $this->assertSame(1, count(array_keys($paths, 'vendor/thing/bin/tool.sh')), 'The entry must appear exactly once.');
      $this->assertSame(count($paths), count(array_unique($paths)), 'No path may be enumerated twice.');
    }
    finally {
      $this->filesystem->remove($outside);
    }
  }

  public function testNoPathIsEnumeratedTwice() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('install/pkg/README.md');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $paths = $this->listRelative();

    $this->assertSame(count($paths), count(array_unique($paths)), 'Duplicate paths would cause the same file to be chmod-ed repeatedly.');
  }

  /**
   * Exercises the repeat pass: a symlinked directory inside a derived subtree.
   */
  public function testNestedAliasedDirectoryContentsAreEnumerated() {
    $this->make('a/inner/deep/file.txt');
    $this->make('a/outer/');
    symlink('../inner', $this->root . '/a/outer/nested');
    $this->make('z/');
    symlink('../a/outer', $this->root . '/z/alias');

    $paths = $this->listRelative();

    $this->assertContains('a/inner/deep/file.txt', $paths, 'The real path.');
    $this->assertContains('a/outer/nested/deep/file.txt', $paths, 'Through the inner symlink.');
    $this->assertContains('z/alias/nested/deep/file.txt', $paths, 'Through both symlinks in turn.');
  }

  /**
   * Both paths address one inode, so chmod through either is the same
   * operation. ApplyCommand relies on the shared realpath to avoid repeating
   * itself, and on perms to skip files already correct.
   */
  public function testDerivedEntriesCarryTheSourceRealpathAndPerms() {
    $real = $this->make('install/pkg/bin/tool.sh');
    chmod($real, 0741);
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $by_path = [];
    foreach ((new GetFileList())($this->root) as $item) {
      $by_path[$item['path']] = $item;
    }
    $derived = $by_path[$this->root . '/web/themes/custom/theme/bin/tool.sh'] ?? NULL;
    $source = $by_path[$this->root . '/install/pkg/bin/tool.sh'] ?? NULL;

    $this->assertNotNull($derived, 'The aliased path must be enumerated.');
    $this->assertNotNull($source);
    $this->assertSame($source['realpath'], $derived['realpath'], 'Both paths resolve to one inode.');
    $this->assertSame('0741', $derived['perms']);
    $this->assertFalse($derived['is_dir']);
  }

  public function testDerivedDirectoryEntriesKeepTheirTrailingSlashAndFlag() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $by_path = [];
    foreach ((new GetFileList())($this->root) as $item) {
      $by_path[$item['path']] = $item;
    }

    $bin = $this->root . '/web/themes/custom/theme/bin/';
    $this->assertArrayHasKey($bin, $by_path, 'A derived directory keeps the trailing slash convention.');
    $this->assertTrue($by_path[$bin]['is_dir']);
  }

  /**
   * A tree with no symlinks must come back exactly as it did before, both for
   * correctness and so the derivation costs nothing in the common case.
   */
  public function testTreeWithoutSymlinksIsUnchanged() {
    $this->make('one/two/three.txt');
    $this->make('four.txt');

    $paths = $this->listRelative();
    sort($paths);

    $this->assertSame(['', 'four.txt', 'one/', 'one/two/', 'one/two/three.txt'], $paths);
  }

  public function testSymlinkToItsOwnParentTerminates() {
    $this->make('a/file.txt');
    symlink('.', $this->root . '/a/self');

    $paths = $this->listRelative();

    $this->assertContains('a/file.txt', $paths);
    // Deriving through a self-reference would generate a/self/self/self/...
    $this->assertNotContains('a/self/self/', $paths, 'A self-referential symlink must not be expanded.');
    $this->assertLessThan(20, count($paths), 'The list must stay bounded.');
  }

  public function testMutuallyReferentialSymlinksTerminate() {
    $this->make('dir_a/a.txt');
    $this->make('dir_b/b.txt');
    symlink('../dir_b', $this->root . '/dir_a/to_b');
    symlink('../dir_a', $this->root . '/dir_b/to_a');

    $paths = $this->listRelative();

    $this->assertContains('dir_a/a.txt', $paths);
    $this->assertContains('dir_b/b.txt', $paths);
    $this->assertLessThan(200, count($paths), 'Cross-referencing symlinks must not expand without bound.');
  }

  public function testBrokenSymlinkedDirectoryIsIgnored() {
    $this->make('web/');
    symlink('../does/not/exist', $this->root . '/web/dangling');

    $paths = $this->listRelative();

    $this->assertContains('web/', $paths);
    $this->assertNotContains('web/dangling/', $paths);
  }

  /**
   * realpath() resolves symlinks in the start directory too, so targets have to
   * be translated back into the namespace the walk recorded. On macOS this is
   * also why sys_get_temp_dir() needs handling: /var is a link to /private/var.
   */
  public function testStartDirectoryReachedThroughASymlink() {
    $this->make('realroot/pkg/bin/tool.sh');
    $this->make('realroot/web/custom/');
    symlink('../../pkg', $this->root . '/realroot/web/custom/theme');
    symlink('realroot', $this->root . '/startlink');

    $paths = $this->listRelative($this->root . '/startlink');

    $this->assertContains('pkg/bin/tool.sh', $paths);
    $this->assertContains('web/custom/theme/bin/tool.sh', $paths, 'Aliases must resolve when the start directory is itself a symlink.');
  }

  /**
   * The user-visible symptom. The glob worked on its own but not during a real
   * run, because an earlier pattern had already cached the parent listing.
   */
  public function testGlobMatchesThroughSymlinkRegardlessOfCacheState() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');
    $glob = $this->root . '/web/*/custom/*/bin/*';

    $fresh = (new GetConcretePaths(new Cache()))($glob);
    $this->assertCount(1, $fresh, 'The glob must match with a cold cache.');

    // Mirrors `default: ['../../**']` running before the executable patterns.
    $shared = new GetConcretePaths(new Cache());
    $shared($this->root . '/**');
    $after = $shared($glob);
    $this->assertCount(1, $after, 'The glob must still match once a parent listing is cached.');
    $this->assertSame($this->root . '/web/themes/custom/theme/bin/tool.sh', $after[0]['path']);
  }

  public function testDoubleAsteriskPatternMatchesThroughSymlink() {
    $this->make('install/pkg/bin/tool.sh');
    $this->make('web/themes/custom/');
    symlink('../../../install/pkg', $this->root . '/web/themes/custom/theme');

    $matches = (new GetConcretePaths(new Cache()))($this->root . '/web/**/bin/*');
    $paths = array_column($matches, 'path');

    $this->assertContains($this->root . '/web/themes/custom/theme/bin/tool.sh', $paths, 'A ** pattern rooted above the symlink must match through it.');
  }

}
