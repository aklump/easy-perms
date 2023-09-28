#!/usr/bin/env php
<?php

use AKlump\EasyPerms\ConfigInterface;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Helpers\GetLabel;
use AKlump\EasyPerms\Helpers\IsDir;
use AKlump\EasyPerms\Helpers\SortPermissionTypes;
use AKlump\EasyPerms\LoadConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

require __DIR__ . '/vendor/autoload.php';


$START_DIR = getcwd() . '/';

(new SingleCommandApplication())
  ->setName('perms')
  ->setVersion('0.0.0')
  ->addArgument('config', InputArgument::REQUIRED)
  ->setCode(function (InputInterface $input, OutputInterface $output) use ($START_DIR): int {

    $start_time = microtime(TRUE);
    $filesystem = new Filesystem();
    $config_path = $input->getArgument('config');
    $config_path = Path::makeAbsolute($config_path, $START_DIR);
    $config = (new LoadConfig())($config_path);

    $types = [
      ConfigInterface::READONLY => ['icon' => '📘️ '],
      ConfigInterface::DEFAULT => ['icon' => '☀️  '],
      ConfigInterface::WRITEABLE => ['icon' => '✏️  '],
      ConfigInterface::EXECUTABLE => ['icon' => '🛠  '],
    ];
    $apply_order = (new SortPermissionTypes())(array_keys($types));

    $failures = [];
    $perms_to_set = [];
    $is_dir = new IsDir();

    foreach ($apply_order as $type) {
      $meta = $types[$type];
      if (empty($config[$type])) {
        continue;
      }
      foreach ($config[$type] as $path) {
        if (!Path::isAbsolute($path)) {
          $path = Path::makeAbsolute($path, dirname($config_path));
        }
        $items = (new GetConcretePaths(dirname($config_path)))($path);
        foreach ($items as $item) {
          $perms_to_set[$item][] = $meta;
          if ($is_dir($item)) {
            $perms_to_set[$item][] = (string) $config['directory_permissions'][$type];
          }
          else {
            $perms_to_set[$item][] = (string) $config['file_permissions'][$type];
          }
        }
      }
    }

    ksort($perms_to_set);

    // Second, set the perms for all paths.
    $get_label = new GetLabel();
    foreach ($perms_to_set as $item => $data) {
      list($meta, $perms) = $data;
      try {
        $filesystem->chmod($item, octdec($perms));
        $output->writeln($perms . ' ' . $meta['icon'] . $get_label($item));
      }
      catch (IOException $exception) {
        $output->writeln('<error>' . $perms . ' ' . $meta['icon'] . $get_label($item) . '</error>');
        $output->writeln('<error>' . $exception->getMessage() . '</error>');
        $failures[] = $exception->getMessage() . PHP_EOL;
      }
    }

    if ($failures) {
      $output->writeln(array_map(function ($line) {
        return "<error>$line</error>";
      }, $failures));

      return Command::FAILURE;
    }

    $duration = microtime(TRUE) - $start_time;

    $output->writeln(sprintf('<info>Completed in %.2f seconds.</info>', $duration));
    $output->writeln('<info>Permission setting was successful.</info>');

    return Command::SUCCESS;
  })
  ->run();
