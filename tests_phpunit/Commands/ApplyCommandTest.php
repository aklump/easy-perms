<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
 * @uses \AKlump\EasyPerms\Helpers\HandleMemory
 * @uses \AKlump\EasyPerms\Helpers\SortPermissionTypes
 * @uses \AKlump\EasyPerms\LoadConfig
 * @uses \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses \AKlump\EasyPerms\Helpers\IsDir
 * @uses \AKlump\GitIgnore\Pattern
 * @uses \AKlump\EasyPerms\Traits\HasBasePathTrait
 * @uses \AKlump\EasyPerms\Traits\PathHandlerTrait
 */
class ApplyCommandTest extends TestCase {

  public function testMissingPathEmitsWarning() {
    $application = new Application();
    $application->add(new ApplyCommand());

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = getcwd() . '/test_missing_path.yml';
    $content = <<<EOT
readonly:
  - non_existent_file.txt
  - missing_dir/*
EOT;
    $filesystem->dumpFile($config_file, $content);

    $commandTester->execute(['config' => [$config_file]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('WARNING: No file(s) at non_existent_file.txt', $output);
    $this->assertStringContainsString('WARNING: No file(s) at missing_dir/*', $output);

    unlink($config_file);
  }

  public function testMissingPathEmitsWarningInVerboseMode() {
    $application = new Application();
    $application->add(new ApplyCommand());

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = getcwd() . '/test_missing_path_v.yml';
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
    $this->assertStringContainsString('WARNING: No file(s) at non_existent_file_v.txt', $output);

    unlink($config_file);
  }

  public function testMissingPathEmitsWarningInVeryVerboseMode() {
    $application = new Application();
    $application->add(new ApplyCommand());

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $filesystem = new Filesystem();
    $config_file = getcwd() . '/test_missing_path_vv.yml';
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
    $this->assertStringContainsString('WARNING: No file(s) at non_existent_file_vv.txt', $output);

    unlink($config_file);
  }

  public function testMissingConfigFilePromptsToCreate() {
    $application = new Application();
    $application->add(new ApplyCommand());

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $dest_config = getcwd() . '/non_existent_config.yml';
    if (file_exists($dest_config)) {
      unlink($dest_config);
    }

    // Mock inputs: y (create), 1 (perms.yml)
    $commandTester->setInputs(['y', '1']);
    $commandTester->execute(['config' => [$dest_config]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('non_existent_config.yml', $output);
    $this->assertStringContainsString('does not exist. Would you like to create it?', $output);
    $this->assertStringContainsString('Configuration copied to', $output);
    $this->assertFileExists($dest_config);

    unlink($dest_config);
  }

  public function testMissingConfigFileAbortsIfUserSaysNo() {
    $application = new Application();
    $application->add(new ApplyCommand());

    $command = $application->find('apply');
    $commandTester = new CommandTester($command);

    $dest_config = getcwd() . '/non_existent_config_no.yml';
    if (file_exists($dest_config)) {
      unlink($dest_config);
    }

    // Mock inputs: n (don't create)
    $commandTester->setInputs(['n']);
    $commandTester->execute(['config' => [$dest_config]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('non_existent_config_no.yml', $output);
    $this->assertStringContainsString('does not exist.', $output);
    $this->assertFileDoesNotExist($dest_config);
  }
}
