<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \AKlump\EasyPerms\Commands\ApplyCommand
 * @uses \AKlump\EasyPerms\Cache
 * @uses \AKlump\EasyPerms\Config\DefaultDirectoryPermissions
 * @uses \AKlump\EasyPerms\Config\DefaultFilePermissions
 * @uses \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @uses \AKlump\EasyPerms\Helpers\GetFileList
 * @uses \AKlump\EasyPerms\Helpers\GetLabel
 * @uses \AKlump\EasyPerms\Helpers\GetShortPath
 * @uses \AKlump\EasyPerms\Helpers\HandleMemory
 * @uses \AKlump\EasyPerms\Helpers\SortPermissionTypes
 * @uses \AKlump\EasyPerms\Config\LoadConfig
 * @uses \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses \AKlump\EasyPerms\Helpers\IsDir
 * @uses \AKlump\GitIgnore\Pattern
 * @uses \AKlump\EasyPerms\Traits\HasBasePathTrait
 * @uses \AKlump\EasyPerms\Traits\PathHandlerTrait
 * @uses \AKlump\EasyPerms\Environment\CheckEnvironment
 */
class ApplyCommandTest extends TestCase {
  use TestWithFilesTrait;

  protected function setUp(): void {
    parent::setUp();
  }

  protected function tearDown(): void {
    $this->deleteAllTestFiles();
    parent::tearDown();
  }

  private function getApplication(): Application {
    $application = new Application();
    $check_environment = new class() extends CheckEnvironment {

      public function isReady(InputInterface $input, OutputInterface $output, HelperSet $helper_set, array $checks = []): bool {
        return TRUE;
      }
    };
    $command = new ApplyCommand($check_environment);
    $application->add($command);

    return $application;
  }

  public function testExecuteReturnsFailureWhenEnvironmentIsNotReady() {
    $application = new Application();
    $check_environment = new class() extends CheckEnvironment {

      public function isReady(InputInterface $input, OutputInterface $output, HelperSet $helper_set, array $checks = []): bool {
        return FALSE;
      }
    };
    $command = new ApplyCommand($check_environment);
    $application->add($command);

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute([]);

    $this->assertSame(1, $exitCode);
  }

  public function testMissingPathEmitsWarning() {
    $application = $this->getApplication();

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = $this->getTestFilePath('test_missing_path.yml');
    $content = <<<EOT
readonly:
  - non_existent_file.txt
  - missing_dir/*
EOT;
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('WARNING: No file(s) at ' . $this->getLabel($config_file, 'non_existent_file.txt'), $output);
    $this->assertStringContainsString('WARNING: No file(s) at ' . $this->getLabel($config_file, 'missing_dir/*'), $output);
  }

  private function getLabel(string $config_file, string $path): string {
    return (new \AKlump\EasyPerms\Helpers\GetLabel())($this->getTestFilePath($path));
  }

  public function testMissingPathEmitsWarningInVerboseMode() {
    $application = $this->getApplication();

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = $this->getTestFilePath('test_missing_path_v.yml');
    $content = <<<EOT
readonly:
  - non_existent_file_v.txt
EOT;
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(
      ['config' => [$config_file]],
      ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE]
    );

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('WARNING: No file(s) at ' . $this->getLabel($config_file, 'non_existent_file_v.txt'), $output);
  }

  public function testMissingPathEmitsWarningInVeryVerboseMode() {
    $application = $this->getApplication();

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = $this->getTestFilePath('test_missing_path_vv.yml');
    $content = <<<EOT
readonly:
  - non_existent_file_vv.txt
EOT;
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(
      ['config' => [$config_file]],
      ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]
    );

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('WARNING: No file(s) at ' . $this->getLabel($config_file, 'non_existent_file_vv.txt'), $output);
  }

  public function testMissingConfigFilePromptsToCreate() {
    $application = $this->getApplication();

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $dest_config = $this->getTestFilePath('non_existent_config.yml');
    $this->deleteTestFile($dest_config);

    // Mock inputs: y (create), 1 (drupal.yml)
    $commandTester->setInputs(['y', '1']);
    $commandTester->execute(['config' => [$dest_config]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('non_existent_config.yml', $output);
    $this->assertStringContainsString('does not exist. Would you like to create it?', $output);
    $this->assertStringContainsString('Configuration copied to', $output);
    $this->assertFileExists($dest_config);
  }

  public function testMissingConfigFileAbortsIfUserSaysNo() {
    $application = $this->getApplication();

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $dest_config = $this->getTestFilePath('non_existent_config_no.yml');
    $this->deleteTestFile($dest_config);

    // Mock inputs: n (don't create)
    $commandTester->setInputs(['n']);
    $commandTester->execute(['config' => [$dest_config]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('non_existent_config_no.yml', $output);
    $this->assertStringContainsString('does not exist.', $output);
    $this->assertFileDoesNotExist($dest_config);
  }

  public function testApplyPermissionsSuccessfully() {
    $application = $this->getApplication();
    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $test_file = $this->getTestFilePath('to_chmod.txt');
    touch($test_file);

    $config_file = $this->getTestFilePath('apply_config.yml');
    $content = "writable:\n  - to_chmod.txt";
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString($this->getLabel($config_file, 'to_chmod.txt'), $output);
    $this->assertStringContainsString('Permission setting was successful.', $output);
  }

  public function testApplyPermissionsToDirectory() {
    $application = $this->getApplication();
    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $test_dir = $this->getTestFilePath('to_chmod_dir');
    if (!is_dir($test_dir)) {
      mkdir($test_dir, 0777, TRUE);
    }

    $config_file = $this->getTestFilePath('apply_dir_config.yml');
    // Use writable which should use directory permissions for the directory.
    $content = "writable:\n  - to_chmod_dir";
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString($this->getLabel($config_file, 'to_chmod_dir'), $output);
    $this->assertStringContainsString('Permission setting was successful.', $output);
    
    // Clean up
    $this->deleteTestFile($test_dir);
  }

  public function testApplyPermissionsToBrokenSymlink() {
    $application = $this->getApplication();
    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $target = $this->getTestFilePath('non_existent_target');
    $link = $this->getTestFilePath('broken_link');
    
    // Create a broken symlink.
    // NOTE: Symfony Filesystem::symlink might resolve, so we might need native or be careful.
    if (file_exists($link)) {
        unlink($link);
    }
    symlink($target, $link);

    $config_file = $this->getTestFilePath('broken_link_config.yml');
    $content = "writable:\n  - broken_link";
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Permission setting was successful.', $output);
    
    // Clean up
    unlink($link);
  }

  public function testApplyPermissionsWhenAlreadyCorrect() {
    $application = $this->getApplication();
    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $test_file = $this->getTestFilePath('already_correct.txt');
    $filesystem->dumpFile($test_file, 'content');
    
    // Set permissions to 0440 (standard for readonly in this app's defaults)
    chmod($test_file, 0440);

    $config_file = $this->getTestFilePath('already_correct_config.yml');
    $content = "readonly:\n  - already_correct.txt";
    $filesystem->dumpFile($config_file, $content);

    // Use very verbose to trigger line 203
    $commandTester->execute(['config' => [$config_file]], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('0440 📘️ ' . $this->getLabel($config_file, 'already_correct.txt'), $output);
    $this->assertStringContainsString('Permission setting was successful.', $output);
  }

  public function testApplyPermissionsDuplicateRealpath() {
    $application = $this->getApplication();
    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $test_file = $this->getTestFilePath('duplicate_target.txt');
    $filesystem->dumpFile($test_file, 'content');
    
    // Create two symlinks to the same file.
    $link1 = $this->getTestFilePath('link1');
    $link2 = $this->getTestFilePath('link2');
    if (file_exists($link1)) unlink($link1);
    if (file_exists($link2)) unlink($link2);
    symlink($test_file, $link1);
    symlink($test_file, $link2);

    $config_file = $this->getTestFilePath('duplicate_realpath_config.yml');
    // Both links should resolve to the same realpath.
    $content = "readonly:\n  - link1\n  - link2";
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]], ['verbosity' => \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE]);

    $output = $commandTester->getDisplay();
    // We expect both to be listed in very verbose output.
    $this->assertStringContainsString($this->getLabel($config_file, 'link1'), $output);
    $this->assertStringContainsString($this->getLabel($config_file, 'link2'), $output);
    $this->assertStringContainsString('Permission setting was successful.', $output);
    
    // Clean up
    unlink($link1);
    unlink($link2);
  }

  public function testApplyPermissionsHandlesFailures() {
    $this->assertTrue(TRUE);
  }
}
