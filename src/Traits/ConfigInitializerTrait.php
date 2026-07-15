<?php

namespace AKlump\EasyPerms\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

trait ConfigInitializerTrait {

  /**
   * Select a template and copy it to the destination path.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param string $dest
   *   The destination path or directory.
   * @param bool $is_dir
   *   True if $dest is a directory.
   * @param bool $show_continue
   *   True if "continue" should be an option.
   *
   * @return bool|null
   *   True if the configuration was successfully created, FALSE on error, NULL if continue was selected.
   */
  protected function selectAndCopyConfig(InputInterface $input, OutputInterface $output, string $dest, bool $is_dir = FALSE, bool $show_continue = FALSE): ?bool {
    $helper = $this->getHelper('question');
    $init_dir = dirname(__DIR__, 2) . '/init';
    $filesystem = new Filesystem();

    $files = glob($init_dir . '/*.yml');
    if (empty($files)) {
      $output->writeln('<error>No default configuration files found in init/ directory.</error>');

      return FALSE;
    }

    $choices = array_map(fn($file) => basename($file), $files);
    if ($show_continue) {
      $choices[] = 'continue';
    }
    $question = new ChoiceQuestion('Select source config:', $choices, 0);
    $selected_file_name = $helper->ask($input, $output, $question);

    if ($selected_file_name === 'continue') {
      return NULL;
    }

    $source_path = $init_dir . '/' . $selected_file_name;
    $dest_path = $is_dir ? Path::join($dest, $selected_file_name) : $dest;

    if ($filesystem->exists($dest_path)) {
      $output->writeln(sprintf('<error>The file "%s" already exists. Skipping.</error>', $dest_path));

      return TRUE;
    }

    $filesystem->mkdir(dirname($dest_path));
    $filesystem->copy($source_path, $dest_path);
    $output->writeln(sprintf('<info>Configuration copied to %s</info>', $dest_path));

    return TRUE;
  }
}
