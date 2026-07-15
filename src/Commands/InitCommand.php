<?php

namespace AKlump\EasyPerms\Commands;

use AKlump\EasyPerms\Traits\ConfigInitializerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class InitCommand extends Command {

  use ConfigInitializerTrait;

  protected static $defaultName = 'init';

  protected function configure() {
    $this->setDescription('Initialize a new configuration.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $helper = $this->getHelper('question');
    $init_dir = dirname(__DIR__, 2) . '/init';
    $filesystem = new Filesystem();

    $question = new ConfirmationQuestion('copy default config? (Y/n) ', TRUE);
    if ($helper->ask($input, $output, $question)) {
      $default_dir = 'bin/config';
      $question = new Question(sprintf('to what path? (%s) ', $default_dir), $default_dir);
      $dest_dir = $helper->ask($input, $output, $question);
      $dest_dir = Path::makeAbsolute($dest_dir, getcwd());

      while (TRUE) {
        $result = $this->selectAndCopyConfig($input, $output, $dest_dir, TRUE, TRUE);
        if ($result === NULL) {
          break;
        }
        if ($result === FALSE) {
          return Command::FAILURE;
        }
      }
    }

    $question = new ConfirmationQuestion('copy controller? (Y/n) ', TRUE);
    if ($helper->ask($input, $output, $question)) {
      $source_path = $init_dir . '/apply-perms.sh';
      if (!$filesystem->exists($source_path)) {
        $output->writeln('<error>Controller source file not found.</error>');

        return Command::FAILURE;
      }

      $default_path = 'bin/apply-perms.sh';
      $question = new Question(sprintf('to what path? (%s) ', $default_path), $default_path);
      $dest_path = $helper->ask($input, $output, $question);
      $dest_path = Path::makeAbsolute($dest_path, getcwd());

      if ($filesystem->exists($dest_path)) {
        $output->writeln(sprintf('<error>The file "%s" already exists. Aborting.</error>', $dest_path));

        return Command::FAILURE;
      }

      $filesystem->mkdir(dirname($dest_path));
      $filesystem->copy($source_path, $dest_path);
      $filesystem->chmod($dest_path, 0755);
      $output->writeln(sprintf('<info>Controller copied to %s</info>', $dest_path));
    }

    return Command::SUCCESS;
  }
}
