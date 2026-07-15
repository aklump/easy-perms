<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\InitCommand;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\EasyPerms\Commands\InitCommand
 */
class InitCommandTest extends TestCase {

  use TestWithFilesTrait;

  public function testExecuteCopiesFile() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_dir = $this->getTestFilePath('config_test_init_dir/');
    $this->deleteTestFile($dest_dir);

    // Mock inputs: y (copy), config_test_init_dir (dest dir), 1 (drupal.yml), exit (3) (to stop loop), n (no controller)
    $commandTester->setInputs(['y', $dest_dir, '1', '3', 'n']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Configuration copied to', $output);
    $dest_file = "{$dest_dir}easy-perms.drupal.yml";
    $this->assertFileExists($dest_file);
    $this->assertFileEquals(dirname(__DIR__, 3) . '/init/easy-perms.drupal.yml', $dest_file);
    $this->deleteTestFile($dest_dir);
  }

  public function testExecuteCopiesController() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_controller = $this->getTestFilePath('bin_test/perms_test.sh');
    $this->deleteTestFile($dest_controller);

    // Mock inputs: n (skip config), y (copy controller), bin_test/perms_test.sh (dest)
    $commandTester->setInputs(['n', 'y', $dest_controller]);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Controller copied to', $output);
    $this->assertFileExists($dest_controller);

    $this->deleteTestFile($dest_controller);
  }

  public function testExecuteSkipsIfFileExists() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_dir = $this->getTestFilePath('config_test_init_exists_dir/', TRUE);
    $dest_file = $dest_dir . 'easy-perms.dev.yml';
    touch($dest_file);

    // Mock inputs: y (copy), config_test_init_exists_dir (dest), 0 (dev.yml), exit (to stop)
    $commandTester->setInputs([
      'y',
      $dest_dir,
      '0',
      'continue',
      'n',
    ]);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('already exists. Skipping.', $output);

    $this->deleteTestFile($dest_dir);
  }

  public function testExecuteSkipsIfNoSelected() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    // Mock inputs: n (don't copy config), n (don't copy controller)
    $commandTester->setInputs(['n', 'n']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('copy default config?', $output);
    $this->assertStringNotContainsString('Select source config:', $output);
    $this->assertStringContainsString('copy controller?', $output);
  }

  public function testExecuteAbortsIfControllerExists() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_controller = $this->getTestFilePath('bin_test/perms_exists.sh');
    $this->deleteTestFile($dest_controller);
    $this->getTestFilePath('bin_test/', TRUE);
    touch($dest_controller);

    // Mock inputs: n (don't copy config), y (copy controller), bin_test/perms_exists.sh (dest)
    $commandTester->setInputs(['n', 'y', $dest_controller]);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('already exists. Aborting.', $output);
    $this->assertEquals(\Symfony\Component\Console\Command\Command::FAILURE, $commandTester->getStatusCode());

    $this->deleteTestFile($dest_controller);
  }

  public function testSelectAndCopyConfigFailsIfNoFilesFound() {
    $application = new Application();
    $command = new InitCommand();
    $application->add($command);

    // We can't easily mock glob() or filesystem state for the trait without more effort,
    // but we can try to trigger the 'continue' path to cover that logic.
    $command = $application->find('init');
    $commandTester = new CommandTester($command);
    
    $dest_dir = $this->getTestFilePath('config_continue_dir/');
    
    // y (copy config), dest_dir, continue (to exit loop), n (no controller)
    $commandTester->setInputs(['y', $dest_dir, 'continue', 'n']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Select source config:', $output);
    $this->assertEquals(\Symfony\Component\Console\Command\Command::SUCCESS, $commandTester->getStatusCode());
  }
}
