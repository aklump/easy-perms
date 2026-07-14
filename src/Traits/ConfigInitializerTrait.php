<?php

namespace AKlump\EasyPerms\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;

trait ConfigInitializerTrait {

  /**
   * Select a template and copy it to the destination path.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param string $dest_path
   *   The absolute destination path.
   *
   * @return bool
   *   True if the configuration was successfully created.
   */
  protected function selectAndCopyConfig(InputInterface $input, OutputInterface $output, string $dest_path): bool {
    $helper = $this->getHelper('question');
    $init_dir = dirname(__DIR__, 2) . '/init';
    $filesystem = new Filesystem();

    $files = glob($init_dir . '/*.yml');
    if (empty($files)) {
      $output->writeln('<error>No default configuration files found in init/ directory.</error>');

      return FALSE;
    }

    $choices = array_map(fn($file) => basename($file), $files);
    $question = new ChoiceQuestion('Select source config:', $choices, 0);
    $selected_file_name = $helper->ask($input, $output, $question);
    $source_path = $init_dir . '/' . $selected_file_name;

    $filesystem->mkdir(dirname($dest_path));
    $filesystem->copy($source_path, $dest_path);
    $output->writeln(sprintf('<info>Configuration copied to %s</info>', $dest_path));

    return TRUE;
  }
}
