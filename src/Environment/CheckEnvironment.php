<?php

namespace AKlump\EasyPerms\Environment;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckEnvironment {

  /**
   * Check if the environment is ready for the command execution.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param \Symfony\Component\Console\Helper\HelperSet $helper_set
   * @param array $checks
   *
   * @return bool
   *   TRUE if the environment is ready or the user chose to continue.
   */
  public function isReady(InputInterface $input, OutputInterface $output, HelperSet $helper_set, array $checks = []): bool {
    if (empty($checks)) {
      $checks = [new CheckXdebug()];
    }
    foreach ($checks as $check) {
      if ($check->isOptimized()) {
        continue;
      }
      $recommendations = $check->getRecommendations();
      $output->writeln('<comment>' . implode("\n", $recommendations) . '</comment>');

      $helper = $helper_set->get('question');
      $question = new ConfirmationQuestion('Do you want to continue anyway? (y/N) ', FALSE);
      if (!$helper->ask($input, $output, $question)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
