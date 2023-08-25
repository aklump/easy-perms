#!/usr/bin/env php
<?php

use AKlump\EasyPerms\LoadConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Webmozart\Glob\Glob;

require __DIR__ . '/vendor/autoload.php';


$START_DIR = getcwd() . '/';

(new SingleCommandApplication())
  ->setName('perms')
  ->setVersion('0.0.0')
  ->addArgument('config', InputArgument::REQUIRED)
  ->setCode(function (InputInterface $input, OutputInterface $output) use ($START_DIR): int {

    $filesystem = new Filesystem();
    $config_path = $input->getArgument('config');
    $config_path = Path::makeAbsolute($config_path, $START_DIR);
    $config = (new LoadConfig())($config_path);

    $types = [
      'default' => ['icon' => '☀️  '],
      'writeable' => ['icon' => '✏️  '],
      'executable' => ['icon' => '🛠  '],
    ];
    $failures = [];
    foreach ($types as $type => $meta) {
      if (empty($config[$type])) {
        continue;
      }

      foreach ($config[$type] as $path) {
        $items = Glob::glob($path);
        foreach ($items as $item) {
          $perms = (string) $config['file_permissions'][$type];
          if (is_dir($item)) {
            $perms = (string) $config['directory_permissions'][$type];
          }
          $current_perms = substr(sprintf('%o', fileperms($item)), -4);
          if (strcasecmp($current_perms, $perms) !== 0) {
            try {
              $filesystem->chmod($item, $perms);
              $output->writeln($perms . ' ' . $meta['icon'] . Path::makeRelative($item, $START_DIR));
            }
            catch (IOException $exception) {
              $failures[] = sprintf('Failed to change %s to %s 😞 %s', $current_perms, $perms, Path::makeRelative($item, $START_DIR)) . PHP_EOL;
            }
          }
        }
      }
    }

    if ($failures) {
      $output->writeln(array_map(function ($line) {
        return "<error>$line</error>";
      }, $failures));

      return Command::FAILURE;
    }
    $output->writeln('<info>Permission setting was successful.</info>');

    return Command::SUCCESS;
  })
  ->run();
