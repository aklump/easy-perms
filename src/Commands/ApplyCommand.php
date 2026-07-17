<?php

namespace AKlump\EasyPerms\Commands;

use AKlump\EasyPerms\Cache;
use AKlump\EasyPerms\Config\ConfigInterface;
use AKlump\EasyPerms\Config\DefaultDirectoryPermissions;
use AKlump\EasyPerms\Config\DefaultFilePermissions;
use AKlump\EasyPerms\Config\LoadConfig;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Helpers\GetLabel;
use AKlump\EasyPerms\Helpers\GetShortPath;
use AKlump\EasyPerms\Helpers\HandleMemory;
use AKlump\EasyPerms\Helpers\SortPermissionTypes;
use AKlump\EasyPerms\Traits\ConfigInitializerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ApplyCommand extends Command {

  use ConfigInitializerTrait;

  protected static $defaultName = 'apply';

  /**
   * @var \AKlump\EasyPerms\Environment\CheckEnvironment
   */
  protected CheckEnvironment $checkEnvironment;

  public function __construct(CheckEnvironment $check_environment, ?string $name = NULL) {
    $this->checkEnvironment = $check_environment;
    parent::__construct($name);
  }

  protected function configure() {
    $this
      ->setDescription('Apply permissions to files and directories based on configuration.')
      ->addArgument('config', InputArgument::IS_ARRAY, 'Paths to the configuration files.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->checkEnvironment->isReady($input, $output, $this->getHelperSet())) {
      return Command::FAILURE;
    }
    (new HandleMemory())();
    $get_short_path = new GetShortPath();
    $start_time = microtime(TRUE);
    $start_dir = getcwd() . '/';
    $filesystem = new Filesystem();
    $config_paths = $input->getArgument('config');
    $config_paths = array_map(fn($path) => Path::makeAbsolute($path, $start_dir), $config_paths);

    foreach ($config_paths as $path) {
      if (!$filesystem->exists($path)) {
        $helper = $this->getHelper('question');
        $short_path = $get_short_path($path);
        $question = new ConfirmationQuestion(sprintf('Configuration file "%s" does not exist. Would you like to create it? (Y/n) ', $short_path), TRUE);
        if ($helper->ask($input, $output, $question)) {
          if (!$this->selectAndCopyConfig($input, $output, $path)) {
            return Command::FAILURE;
          }
        }
        if (!$filesystem->exists($path)) {
          $output->writeln(sprintf('<error>Configuration file "%s" does not exist.</error>', $short_path));

          return Command::FAILURE;
        }
      }
    }

    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $config = (new LoadConfig($config_defaults))($config_paths);
    $types = [
      ConfigInterface::READONLY => ['icon' => '📘️ '],
      ConfigInterface::DEFAULT => ['icon' => '☀️  '],
      ConfigInterface::WRITABLE => ['icon' => '✏️  '],
      ConfigInterface::EXECUTABLE => ['icon' => '🛠  '],
    ];
    $apply_order = (new SortPermissionTypes())(array_keys($types));

    $failures = [];
    $warnings = [];
    $perms_to_set = [];
    $get_label = new GetLabel();
    $filepath_cache = new Cache();
    $get_concrete_paths = new GetConcretePaths($filepath_cache);

    $is_verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    $is_very_verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE;
    $progress_bar = NULL;
    if (!$is_very_verbose) {
      $output->writeln('<info>Calculating permissions...</info>');
      $total_patterns = 0;
      foreach ($apply_order as $type) {
        if (!empty($config[$type])) {
          $total_patterns += count($config[$type]);
        }
      }
      $progress_bar = new ProgressBar($output, $total_patterns);
      $progress_bar->setFormat("%current%/%max% [%bar%]");
      $progress_bar->start();
    }

    foreach ($apply_order as $type) {
      $meta = $types[$type];
      if (empty($config[$type])) {
        continue;
      }
      foreach ($config[$type] as $path) {
        $label = $get_label($path);
        if ($is_very_verbose) {
          $output->writeln(sprintf('<info>Calculating permissions: %s</info>', $label));
        }
        elseif ($progress_bar) {
          $progress_bar->advance();
        }
        $items = $get_concrete_paths($path);
        if (empty($items)) {
          $warnings[$label] = "No file(s) at $label";
        }
        foreach ($items as $data) {
          $item = $data['path'];
          $is_item_dir = $data['is_dir'];
          $current_perms = $data['perms'] ?? NULL;
          $perms_to_set[$item] = [$meta];
          if ($is_item_dir) {
            $perms_to_set[$item][] = (string) $config[ConfigInterface::DIRECTORY_PERMISSIONS][$type];
          }
          else {
            $perms_to_set[$item][] = (string) $config[ConfigInterface::FILE_PERMISSIONS][$type];
          }
          $perms_to_set[$item][] = $current_perms;
          $perms_to_set[$item][] = $data['realpath'] ?? NULL;
        }
        unset($items);
      }
    }

    ksort($perms_to_set);

    if (($is_verbose || $is_very_verbose) && $progress_bar) {
      $progress_bar->finish();
      $output->writeln('');
      $progress_bar = NULL;
    }

    if ($progress_bar) {
      $output->writeln('');
      $output->writeln('<info>Applying permissions...</info>');
      $count = count($perms_to_set);
      if ($count > 0) {
        $progress_bar->setMaxSteps($count);
        $progress_bar->setFormat("%current%/%max% [%bar%] %percent:3s%%");
        $progress_bar->setProgress(0);
      }
      else {
        $progress_bar->finish();
        $output->writeln('');
        $progress_bar = NULL;
      }
    }

    // Second, set the perms for all paths.
    $processed_realpaths = [];
    foreach ($perms_to_set as $item => $data) {
      $label = $get_label($item);
      if ($progress_bar) {
        $progress_bar->advance();
      }
      list($meta, $perms, $current, $realpath) = $data;
      if ($realpath && isset($processed_realpaths[$realpath])) {
        if ($is_very_verbose) {
          $output->writeln(sprintf("%s %s%s", $perms, $meta['icon'], $label));
        }
        continue;
      }

      try {
        if ($current === NULL) {
          $p = @fileperms($item);
          if ($p === FALSE && !file_exists($item)) {
            // The file doesn't exist; it's probably a broken symlink or a
            // symlink target that is missing. Skip without error.
            continue;
          }
          $current = $p !== FALSE ? substr(decoct($p), -4) : NULL;
        }
        if ($current === $perms) {
          if ($realpath) {
            $processed_realpaths[$realpath] = TRUE;
          }
          if ($is_very_verbose) {
            $output->writeln(sprintf("%s %s%s", $perms, $meta['icon'], $label));
          }
          continue;
        }
        $filesystem->chmod($item, octdec($perms));
        if ($realpath) {
          $processed_realpaths[$realpath] = TRUE;
        }
        if ($is_verbose) {
          $output->writeln(sprintf("%s %s%s", $perms, $meta['icon'], $label));
        }
      }
      catch (\Throwable $exception) {
        if ($realpath) {
          $processed_realpaths[$realpath] = FALSE;
        }
        if ($progress_bar) {
          $progress_bar->clear();
        }
        $output->writeln('<error>' . $perms . ' ' . $meta['icon'] . $label . '</error>');
        $output->writeln('<error>' . $exception->getMessage() . '</error>');
        if ($progress_bar) {
          $progress_bar->display();
        }
        $failures[] = $exception->getMessage() . PHP_EOL;
      }
    }

    if ($progress_bar) {
      $progress_bar->finish();
      $output->writeln('');
    }

    if ($warnings) {
      $output->writeln(array_map(function ($line) {
        return "<comment>WARNING: $line</comment>";
      }, $warnings));
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
  }
}
