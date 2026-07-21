<?php

namespace AKlump\EasyPerms\Commands;

use AKlump\EasyPerms\Config\ResolveConfigPaths;
use AKlump\EasyPerms\Config\LoadConfigContent;
use AKlump\EasyPerms\Config\ConfigInterface;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Helpers\GetShortPath;
use AKlump\EasyPerms\Helpers\HandleMemory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class OptimizeCommand extends Command {

  protected static $defaultName = 'optimize';

  protected function configure() {
    $this
      ->setDescription('Consolidate paths in configuration files to favor globs over explicit paths.')
      ->addArgument('config', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Paths to the configuration files.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    (new HandleMemory())();
    $filesystem = new Filesystem();
    $get_short_path = new GetShortPath();
    $config_paths = (new ResolveConfigPaths())($input->getArgument('config'));

    foreach ($config_paths as $path) {
      if (!$filesystem->exists($path)) {
        $output->writeln(sprintf('<error>Configuration file "%s" does not exist.</error>', $get_short_path($path)));

        return Command::FAILURE;
      }
    }

    $sections = [
      ConfigInterface::READONLY,
      ConfigInterface::DEFAULT,
      ConfigInterface::WRITABLE,
      ConfigInterface::EXECUTABLE,
    ];

    $get_concrete_paths = new GetConcretePaths();
    $is_very_verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;

    $load_content = new LoadConfigContent();
    foreach ($config_paths as $path) {
      $progress_bar = NULL;
      if (!$is_very_verbose) {
        $total_patterns = 0;
        $config = $load_content($path, FALSE);
        foreach ($sections as $section) {
          if (isset($config[$section]) && is_array($config[$section])) {
            $total_patterns += count($config[$section]);
          }
        }
        if ($total_patterns > 0) {
          $output->writeln(sprintf('<info>Optimizing %s...</info>', $get_short_path($path)));
          $progress_bar = new ProgressBar($output, $total_patterns);
          $progress_bar->setFormat("%current%/%max% [%bar%]");
          $progress_bar->start();
        }
      }

      $config = $load_content($path, FALSE);
      $changed = FALSE;

      foreach ($sections as $section) {
        if (!isset($config[$section]) || !is_array($config[$section])) {
          continue;
        }

        $patterns = $config[$section];
        if (empty($patterns)) {
          continue;
        }

        $base_path = dirname($path);
        $optimized_patterns = $this->optimizePatterns($patterns, $base_path, $get_concrete_paths, $progress_bar);

        if ($optimized_patterns !== $patterns) {
          $config[$section] = $optimized_patterns;
          $changed = TRUE;
        }
      }

      if ($changed) {
        if ($progress_bar) {
          $progress_bar->clear();
        }
        $path_info = pathinfo($path);
        $backup_path = sprintf('%s/.%s.%s.%s', $path_info['dirname'], date('YmdHis'), $path_info['filename'], $path_info['extension']);
        $filesystem->copy($path, $backup_path);
        $output->writeln(sprintf('<info>Backup created at %s</info>', $get_short_path($backup_path)));

        $new_content = Yaml::dump($config, 4, 2);
        file_put_contents($path, $new_content);
        $output->writeln(sprintf('<info>Optimized %s</info>', $get_short_path($path)));
        if ($progress_bar) {
          $progress_bar->display();
        }
      }
      else {
        if ($progress_bar) {
          $progress_bar->clear();
        }
        $output->writeln(sprintf('<info>No changes for %s</info>', $get_short_path($path)));
        if ($progress_bar) {
          $progress_bar->display();
        }
      }

      if ($progress_bar) {
        $progress_bar->finish();
        $output->writeln('');
      }
    }

    return Command::SUCCESS;
  }

  private function optimizePatterns(array $patterns, string $base_path, GetConcretePaths $get_concrete_paths, ?ProgressBar $progress_bar = NULL): array {
    // 1. Resolve all patterns to their concrete paths
    $resolved = [];
    foreach ($patterns as $pattern) {
      if ($progress_bar) {
        $progress_bar->advance();
      }
      $absolute_pattern = Path::makeAbsolute($pattern, $base_path);
      $items = $get_concrete_paths($absolute_pattern);
      $concrete_paths = array_map(fn($item) => $item['path'], $items);
      $resolved[] = [
        'original' => $pattern,
        'is_glob' => strpos($pattern, '*') !== FALSE || strpos($pattern, '?') !== FALSE || strpos($pattern, '[') !== FALSE,
        'concrete' => $concrete_paths,
      ];
    }

    // 2. Identify which patterns are redundant
    $to_remove = [];
    $count = count($resolved);
    for ($i = 0; $i < $count; $i++) {
      for ($j = 0; $j < $count; $j++) {
        if ($i === $j) {
          continue;
        }

        // If pattern i's concrete paths are a subset of pattern j's
        // AND (j is a glob OR j is the same pattern but later in the list)
        // then i is potentially redundant.
        // Actually, the requirement says "favoring glob paths over explicit"

        $subset = array_diff($resolved[$i]['concrete'], $resolved[$j]['concrete']);
        if (empty($subset)) {
          // i is a subset of j
          if ($resolved[$j]['is_glob'] && !$resolved[$i]['is_glob']) {
            $to_remove[$i] = TRUE;
          }
          elseif ($resolved[$i]['original'] === $resolved[$j]['original'] && $i < $j) {
            // Exact same pattern, remove the earlier one
            $to_remove[$i] = TRUE;
          }
        }
      }
    }

    $optimized = [];
    foreach ($patterns as $index => $pattern) {
      if (!isset($to_remove[$index])) {
        $optimized[] = $pattern;
      }
    }

    // 4. Consolidate and sort
    $optimized = array_values(array_unique($optimized));

    // 5. Sort paths ignoring single quotes
    usort($optimized, function ($a, $b) {
      $a_clean = str_replace("'", '', $a);
      $b_clean = str_replace("'", '', $b);

      return strnatcasecmp($a_clean, $b_clean);
    });

    return $optimized;
  }
}
