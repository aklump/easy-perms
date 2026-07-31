<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * End-to-end coverage for globs that reach files through a symlinked directory.
 *
 * The layout mirrors a Drupal app where custom modules and themes are symlinked
 * out of an install directory.
 *
 * @covers \AKlump\EasyPerms\Commands\ApplyCommand
 * @uses   \AKlump\EasyPerms\Cache
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
class ApplyCommandSymlinkTest extends TestCase {

  private string $app;

  private Filesystem $filesystem;

  protected function setUp(): void {
    $this->filesystem = new Filesystem();
    $this->app = sys_get_temp_dir() . '/easy_perms_apply_symlink_' . uniqid();
    $this->filesystem->mkdir([
      $this->app . '/bin/config',
      $this->app . '/install/composer/se_theme/bin',
      $this->app . '/web/themes/custom',
    ]);
    touch($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh');
    chmod($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh', 0644);
    symlink('../../../install/composer/se_theme', $this->app . '/web/themes/custom/se_theme');
  }

  protected function tearDown(): void {
    $this->filesystem->remove($this->app);
  }

  private function apply(array $config_paths): string {
    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));
    $tester = new CommandTester($application->find('apply'));
    $tester->execute(['config' => $config_paths]);

    return $tester->getDisplay();
  }

  private function perms(string $path): string {
    clearstatcache();

    return substr(sprintf('%o', fileperms($path)), -4);
  }

  /**
   * A glob written against the symlinked path must set the permission, with no
   * explicit path needed alongside it.
   */
  public function testGlobThroughSymlinkedThemeSetsExecutable() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      'default:',
      "  - '../../**'",
    ]));
    $dev = $this->app . '/bin/config/easy-perms.dev.yml';
    file_put_contents($dev, implode("\n", [
      'executable:',
      "  - '../../web/*/custom/*/bin/*'",
    ]));

    $output = $this->apply([$base, $dev]);

    $this->assertStringContainsString('Permission setting was successful.', $output);
    $this->assertSame('0744', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'), 'The glob alone must make the file executable through the symlinked path.');
    // One inode, so the real path necessarily agrees.
    $this->assertSame('0744', $this->perms($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * Without the glob the file should stay at the default, confirming the test
   * above proves the glob did the work rather than something else.
   */
  public function testWithoutTheGlobTheFileKeepsTheDefaultPermission() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      'default:',
      "  - '../../**'",
    ]));

    $this->apply([$base]);

    $this->assertSame('0644', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * The explicit path was the workaround; it must keep working, and adding it
   * next to the glob must not produce a conflict.
   */
  public function testExplicitPathAlongsideGlobStillWorks() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      'default:',
      "  - '../../**'",
      'executable:',
      "  - '../../web/*/custom/*/bin/*'",
      "  - '../../web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'",
    ]));

    $output = $this->apply([$base]);

    $this->assertStringContainsString('Permission setting was successful.', $output);
    $this->assertStringNotContainsString('WARNING', $output);
    $this->assertSame('0744', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * A pattern against the real path must keep working too.
   */
  public function testGlobAgainstTheRealPathStillWorks() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      'default:',
      "  - '../../**'",
      'executable:',
      "  - '../../install/composer/*/bin/*'",
    ]));

    $this->apply([$base]);

    $this->assertSame('0744', $this->perms($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh'));
    $this->assertSame('0744', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * Both paths address one inode, so only one chmod can win. It has to be the
   * instruction that came last during calculation, not whichever path happens
   * to sort first: "install/..." precedes "web/..." alphabetically.
   */
  public function testLaterConfigWinsWhenTwoPathsShareAnInode() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      "  readonly: '0444'",
      'default:',
      "  - '../../**'",
      'executable:',
      "  - '../../install/composer/*/bin/*'",
    ]));
    $dev = $this->app . '/bin/config/easy-perms.dev.yml';
    file_put_contents($dev, implode("\n", [
      'readonly:',
      "  - '../../web/*/custom/*/bin/*'",
    ]));

    $this->apply([$base, $dev]);

    $this->assertSame('0444', $this->perms($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh'), 'The later config must win even though it names the path that sorts last.');
    $this->assertSame('0444', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * The reverse of the above, so the winner is not simply being flipped: here
   * the last instruction names the path that sorts first.
   */
  public function testLaterConfigWinsWhenItNamesThePathThatSortsFirst() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      "  readonly: '0444'",
      'default:',
      "  - '../../**'",
      'executable:',
      "  - '../../web/*/custom/*/bin/*'",
    ]));
    $dev = $this->app . '/bin/config/easy-perms.dev.yml';
    file_put_contents($dev, implode("\n", [
      'readonly:',
      "  - '../../install/composer/*/bin/*'",
    ]));

    $this->apply([$base, $dev]);

    $this->assertSame('0444', $this->perms($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh'));
    $this->assertSame('0444', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * Every path addressing the inode should report the permission that actually
   * ends up on disk, rather than an instruction that lost.
   */
  public function testVerboseOutputReportsTheWinningPermissionForBothPaths() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'file_permissions:',
      "  default: '0644'",
      "  executable: '0744'",
      'default:',
      "  - '../../**'",
      'executable:',
      "  - '../../web/*/custom/*/bin/*'",
    ]));

    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));
    $tester = new CommandTester($application->find('apply'));
    $tester->execute(['config' => [$base]], ['verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE]);
    $output = $tester->getDisplay();

    $this->assertMatchesRegularExpression('/0744.*install\/composer\/se_theme\/bin\/upgrade-npm-deps\.sh/', $output, 'The real path must report the winning permission, not the default it lost to.');
    $this->assertSame('0744', $this->perms($this->app . '/install/composer/se_theme/bin/upgrade-npm-deps.sh'));
  }

  /**
   * Directories reached through the symlink must get directory permissions, not
   * file permissions.
   */
  public function testDirectoryPermissionsApplyThroughTheSymlink() {
    $base = $this->app . '/bin/config/easy-perms.yml';
    file_put_contents($base, implode("\n", [
      'directory_permissions:',
      "  default: '0755'",
      "  writable: '0777'",
      'file_permissions:',
      "  default: '0644'",
      "  writable: '0666'",
      'default:',
      "  - '../../**'",
      'writable:',
      "  - '../../web/*/custom/*/bin/'",
    ]));

    $this->apply([$base]);

    $this->assertSame('0777', $this->perms($this->app . '/web/themes/custom/se_theme/bin'), 'A trailing slash matches directories only.');
    $this->assertSame('0644', $this->perms($this->app . '/web/themes/custom/se_theme/bin/upgrade-npm-deps.sh'), 'The file inside must keep the default.');
  }

}
