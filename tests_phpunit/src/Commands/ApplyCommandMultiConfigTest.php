<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\EasyPerms\Commands\ApplyCommand
 * @uses   \AKlump\EasyPerms\Config\LoadConfig
 * @uses   \AKlump\EasyPerms\Config\LoadConfigContent
 */
class ApplyCommandMultiConfigTest extends TestCase {

  use TestWithFilesTrait;

  public function testLatterConfigOverridesEarlierConfigAcrossTypes() {
    $root = $this->getTestFilePath('multi_config_test/', TRUE);
    $foo_php = $root . 'foo.php';
    touch($foo_php);
    chmod($foo_php, 0644);

    $base_config = $root . 'base.yml';
    // config1 makes it readonly
    file_put_contents($base_config, "readonly:\n  - foo.php");

    $dev_config = $root . 'dev.yml';
    // config2 makes it writable. In a series, this should win.
    file_put_contents($dev_config, "writable:\n  - foo.php");

    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'config' => [$base_config, $dev_config],
    ]);

    // Writable by default is 0660 (see DefaultFilePermissions)
    // Readonly is '0440'

    clearstatcache();
    $perms = substr(sprintf('%o', fileperms($foo_php)), -4);
    $this->assertSame('0660', $perms, 'The latter config (writable) should override the earlier config (readonly).');
  }

  public function testLatterConfigOverridesEarlierConfigSameType() {
    $root = $this->getTestFilePath('multi_config_test_same_type/', TRUE);
    $foo_php = $root . 'foo.php';
    touch($foo_php);

    $base_config = $root . 'base.yml';
    file_put_contents($base_config, "file_permissions:\n  writable: '0664'\nwritable:\n  - foo.php");

    $dev_config = $root . 'dev.yml';
    file_put_contents($dev_config, "file_permissions:\n  writable: '0666'\nwritable:\n  - foo.php");

    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'config' => [$base_config, $dev_config],
    ]);

    clearstatcache();
    $perms = substr(sprintf('%o', fileperms($foo_php)), -4);
    $this->assertSame('0666', $perms, 'The latter config should override the earlier config perms.');
  }

  public function testPermissionModeOverrideDoesNotAffectEarlierConfigPaths() {
    $root = $this->getTestFilePath('multi_config_mode_timing/', TRUE);
    $base_php = $root . 'base.php';
    $dev_php = $root . 'dev.php';
    touch($base_php);
    touch($dev_php);

    $base_config = $root . 'base.yml';
    file_put_contents($base_config, "file_permissions:\n  writable: '0664'\nwritable:\n  - base.php");

    $dev_config = $root . 'dev.yml';
    file_put_contents($dev_config, "file_permissions:\n  writable: '0600'\nwritable:\n  - dev.php");

    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'config' => [$base_config, $dev_config],
    ]);

    // Each config file is applied only after the previous one has been fully
    // processed, so base.php uses the mode in effect when base.yml was
    // processed; dev.yml's later override applies only to its own paths.
    clearstatcache();
    $this->assertSame('0664', substr(sprintf('%o', fileperms($base_php)), -4), 'The latter config mode should not retroactively change paths from the earlier config.');
    $this->assertSame('0600', substr(sprintf('%o', fileperms($dev_php)), -4), 'The latter config mode should apply to its own paths.');
  }

  public function testLatterConfigOverridesEarlierConfigSameTypeDifferentPaths() {
    $root = $this->getTestFilePath('multi_config_test_same_type_diff_paths/', TRUE);
    $foo_php = $root . 'foo.php';
    touch($foo_php);

    $base_config = $root . 'base.yml';
    // config1 makes everything in root writable (which is 0600 by default)
    file_put_contents($base_config, "file_permissions:\n  writable: '0664'\nwritable:\n  - .");

    $dev_config = $root . 'dev.yml';
    // config2 makes foo.php writable with 0666
    file_put_contents($dev_config, "file_permissions:\n  writable: '0666'\nwritable:\n  - foo.php");

    $application = new Application();
    $check_env = $this->createMock(CheckEnvironment::class);
    $check_env->method('isReady')->willReturn(TRUE);
    $application->add(new ApplyCommand($check_env));

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'config' => [$base_config, $dev_config],
    ]);

    clearstatcache();
    $perms = substr(sprintf('%o', fileperms($foo_php)), -4);
    // Since both are writable, the one with 0666 should win if it comes later.
    $this->assertSame('0666', $perms, 'The latter config path should override the earlier config path of same type.');
  }
}
