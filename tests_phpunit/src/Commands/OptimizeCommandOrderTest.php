<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use AKlump\EasyPerms\Commands\OptimizeCommand;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * `apply` processes configuration files as a sequence, so their order changes
 * the result. `optimize` rewrites each file on its own, so it must not.
 *
 * Were that not so, optimizing in one order and applying in another could shift
 * permissions, and the two commands would disagree about the same files.
 *
 * @covers \AKlump\EasyPerms\Commands\OptimizeCommand
 * @uses   \AKlump\EasyPerms\Cache
 * @uses   \AKlump\EasyPerms\Commands\ApplyCommand
 * @uses   \AKlump\EasyPerms\Config\ConfigInterface
 * @uses   \AKlump\EasyPerms\Config\DefaultDirectoryPermissions
 * @uses   \AKlump\EasyPerms\Config\DefaultFilePermissions
 * @uses   \AKlump\EasyPerms\Config\LoadConfig
 * @uses   \AKlump\EasyPerms\Config\LoadConfigContent
 * @uses   \AKlump\EasyPerms\Config\ResolveConfigPaths
 * @uses   \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @uses   \AKlump\EasyPerms\Helpers\GetFileList
 * @uses   \AKlump\EasyPerms\Helpers\GetLabel
 * @uses   \AKlump\EasyPerms\Helpers\GetShortPath
 * @uses   \AKlump\EasyPerms\Helpers\HandleMemory
 * @uses   \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses   \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses   \AKlump\EasyPerms\Helpers\SortPermissionTypes
 * @uses   \AKlump\EasyPerms\Traits\HasBasePathTrait
 * @uses   \AKlump\EasyPerms\Traits\PathHandlerTrait
 */
class OptimizeCommandOrderTest extends TestCase {

  private string $root;

  private Filesystem $filesystem;

  protected function setUp(): void {
    $this->filesystem = new Filesystem();
    $this->root = sys_get_temp_dir() . '/easy_perms_optimize_' . uniqid();
    $this->filesystem->mkdir($this->root . '/bin');
    foreach (['one.sh', 'two.sh', 'three.sh'] as $name) {
      touch($this->root . '/bin/' . $name);
    }
  }

  protected function tearDown(): void {
    $this->filesystem->remove($this->root);
  }

  private function write(string $name, array $config): string {
    $path = $this->root . '/' . $name;
    file_put_contents($path, Yaml::dump($config, 4, 2));

    return $path;
  }

  private function optimize(array $config_paths): string {
    $application = new Application();
    $application->add(new OptimizeCommand());
    $tester = new CommandTester($application->find('optimize'));
    $tester->execute(['config' => $config_paths]);

    return $tester->getDisplay();
  }

  private function apply(array $config_paths): void {
    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));
    $tester = new CommandTester($application->find('apply'));
    $tester->execute(['config' => $config_paths]);
  }

  private function perms(string $relative): string {
    clearstatcache();

    return substr(sprintf('%o', fileperms($this->root . '/' . $relative)), -4);
  }

  /**
   * Remove the timestamped backups so only the rewritten files are compared.
   */
  private function removeBackups(): void {
    foreach (glob($this->root . '/.[0-9]*.*') as $backup) {
      $this->filesystem->remove($backup);
    }
  }

  public function testResultIsIdenticalRegardlessOfFileOrder() {
    $first = ['executable' => ['bin/*.sh', 'bin/one.sh']];
    $second = ['executable' => ['bin/*.sh', 'bin/two.sh'], 'readonly' => ['bin/three.sh']];

    $a_first = $this->write('a.yml', $first);
    $a_second = $this->write('b.yml', $second);
    $this->optimize([$a_first, $a_second]);
    $forward = [Yaml::parseFile($a_first), Yaml::parseFile($a_second)];
    $this->removeBackups();

    // Same inputs, opposite order on the command line.
    $this->write('a.yml', $first);
    $this->write('b.yml', $second);
    $this->optimize([$a_second, $a_first]);
    $reverse = [Yaml::parseFile($a_first), Yaml::parseFile($a_second)];

    $this->assertSame($forward, $reverse, 'Optimize must not depend on the order its config files are given in.');
    // Guard against both orders being equally broken.
    $this->assertSame(['bin/*.sh'], $forward[0]['executable']);
  }

  /**
   * Each file is rewritten from its own contents, so a glob in one file must not
   * strip an explicit path from another. Anything else would make the result
   * depend on which files were passed together.
   */
  public function testGlobInOneFileDoesNotStripAnExplicitPathFromAnother() {
    $base = $this->write('base.yml', ['executable' => ['bin/*.sh']]);
    $dev = $this->write('dev.yml', ['executable' => ['bin/one.sh']]);

    $this->optimize([$base, $dev]);

    $this->assertSame(['bin/*.sh'], Yaml::parseFile($base)['executable']);
    $this->assertSame(['bin/one.sh'], Yaml::parseFile($dev)['executable'], 'A glob in another file must not remove this path.');
  }

  public function testOptimizingOneFileAloneMatchesOptimizingItAlongsideOthers() {
    $config = ['executable' => ['bin/*.sh', 'bin/one.sh']];

    $alone = $this->write('alone.yml', $config);
    $this->optimize([$alone]);
    $result_alone = Yaml::parseFile($alone);
    $this->removeBackups();

    $together = $this->write('together.yml', $config);
    $other = $this->write('other.yml', ['readonly' => ['bin/two.sh']]);
    $this->optimize([$together, $other]);

    $this->assertSame($result_alone, Yaml::parseFile($together), 'Passing extra files must not change how this one is optimized.');
  }

  /**
   * Sections carry different permissions, so coverage in one says nothing about
   * another.
   */
  public function testGlobInOneSectionDoesNotRemoveAnExplicitPathFromAnother() {
    $config_file = $this->write('sections.yml', [
      'readonly' => ['bin/one.sh'],
      'executable' => ['bin/*.sh'],
    ]);

    $this->optimize([$config_file]);

    $config = Yaml::parseFile($config_file);
    $this->assertSame(['bin/one.sh'], $config['readonly'], 'An executable glob must not remove a readonly path.');
    $this->assertSame(['bin/*.sh'], $config['executable']);
  }

  /**
   * The strongest guarantee: optimizing must not change what apply does.
   */
  public function testOptimizeDoesNotChangeTheOutcomeOfApply() {
    $base = $this->write('base.yml', [
      'file_permissions' => ['default' => '0644', 'executable' => '0744', 'readonly' => '0444'],
      'default' => ['**'],
      'executable' => ['bin/*.sh', 'bin/one.sh'],
    ]);
    $dev = $this->write('dev.yml', ['readonly' => ['bin/three.sh', 'bin/*ee.sh']]);

    $this->apply([$base, $dev]);
    $before = [
      'one.sh' => $this->perms('bin/one.sh'),
      'two.sh' => $this->perms('bin/two.sh'),
      'three.sh' => $this->perms('bin/three.sh'),
    ];

    $this->optimize([$base, $dev]);
    foreach (array_keys($before) as $name) {
      chmod($this->root . '/bin/' . $name, 0600);
    }
    $this->apply([$base, $dev]);

    $after = [
      'one.sh' => $this->perms('bin/one.sh'),
      'two.sh' => $this->perms('bin/two.sh'),
      'three.sh' => $this->perms('bin/three.sh'),
    ];

    $this->assertSame($before, $after, 'Optimizing must leave the applied permissions untouched.');
    $this->assertSame('0744', $after['one.sh']);
    $this->assertSame('0444', $after['three.sh']);
  }

  /**
   * A pattern resolving to nothing cannot be proven redundant. array_diff()
   * reports the empty set as a subset of everything, which would delete
   * configuration for paths that do not exist yet.
   */
  public function testPathThatMatchesNothingIsPreserved() {
    $config_file = $this->write('missing.yml', [
      'executable' => ['bin/*.sh', 'bin/created-by-build.sh'],
    ]);

    $this->optimize([$config_file]);

    $optimized = Yaml::parseFile($config_file)['executable'];
    $this->assertContains('bin/created-by-build.sh', $optimized, 'A path that does not exist yet must not be discarded.');
    $this->assertContains('bin/*.sh', $optimized);
  }

  /**
   * The worst form: the explicit path was removed on the strength of a glob that
   * itself matched nothing.
   */
  public function testPathThatMatchesNothingIsPreservedBesideAGlobThatAlsoMatchesNothing() {
    $config_file = $this->write('both_missing.yml', [
      'executable' => ['nowhere/*.sh', 'bin/created-by-build.sh'],
    ]);

    $this->optimize([$config_file]);

    $optimized = Yaml::parseFile($config_file)['executable'];
    $this->assertContains('bin/created-by-build.sh', $optimized, 'A glob matching nothing proves nothing.');
    $this->assertContains('nowhere/*.sh', $optimized);
  }

  public function testGlobThatMatchesNothingIsPreserved() {
    $config_file = $this->write('empty_glob.yml', [
      'executable' => ['nowhere/*.sh', 'bin/*.sh'],
    ]);

    $this->optimize([$config_file]);

    $optimized = Yaml::parseFile($config_file)['executable'];
    $this->assertContains('nowhere/*.sh', $optimized);
    $this->assertContains('bin/*.sh', $optimized);
  }

  /**
   * The preservation above must not stop real consolidation from happening.
   */
  public function testGenuineRedundancyIsStillRemoved() {
    $config_file = $this->write('redundant.yml', [
      'executable' => ['bin/*.sh', 'bin/one.sh', 'bin/two.sh', 'bin/absent.sh'],
    ]);

    $this->optimize([$config_file]);

    $optimized = Yaml::parseFile($config_file)['executable'];
    $this->assertNotContains('bin/one.sh', $optimized, 'A path the glob really covers must still be removed.');
    $this->assertNotContains('bin/two.sh', $optimized);
    $this->assertContains('bin/*.sh', $optimized);
    $this->assertContains('bin/absent.sh', $optimized);
  }

}
