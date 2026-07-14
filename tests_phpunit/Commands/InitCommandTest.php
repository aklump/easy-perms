<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\InitCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \AKlump\EasyPerms\Commands\InitCommand
 */
class InitCommandTest extends TestCase {

  public function testExecuteCopiesFile() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_file = getcwd() . '/config_test_init.yml';
    if (file_exists($dest_file)) {
      unlink($dest_file);
    }

    // Mock inputs: y (copy), config_test_init.yml (dest), 0 (drupal.yml), n (no controller)
    $commandTester->setInputs(['y', 'config_test_init.yml', '0', 'n']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Configuration copied to', $output);
    $this->assertFileExists($dest_file);
    $this->assertFileEquals(dirname(__DIR__, 2) . '/init/drupal.yml', $dest_file);

    unlink($dest_file);
  }

  public function testExecuteCopiesController() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_controller = getcwd() . '/bin_test/perms_test.sh';
    if (file_exists($dest_controller)) {
      unlink($dest_controller);
    }

    // Mock inputs: n (skip config), y (copy controller), bin_test/perms_test.sh (dest)
    $commandTester->setInputs(['n', 'y', 'bin_test/perms_test.sh']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Controller copied to', $output);
    $this->assertFileExists($dest_controller);

    unlink($dest_controller);
    rmdir(getcwd() . '/bin_test');
  }

  public function testExecuteAbortsIfFileExists() {
    $application = new Application();
    $application->add(new InitCommand());

    $command = $application->find('init');
    $commandTester = new CommandTester($command);

    $dest_file = getcwd() . '/config_test_init_exists.yml';
    touch($dest_file);

    // Mock inputs: y (copy), config_test_init_exists.yml (dest)
    $commandTester->setInputs(['y', 'config_test_init_exists.yml']);
    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('already exists. Aborting.', $output);

    unlink($dest_file);
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
}
