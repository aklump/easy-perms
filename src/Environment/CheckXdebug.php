<?php

namespace AKlump\EasyPerms\Environment;

class CheckXdebug implements EnvironmentCheckInterface {

  public function isOptimized(): bool {
    return !$this->xdebugMaySlowProcessing();
  }

  public function getRecommendations(): array {
    return [
      'WARNING: Xdebug appears to be active in a mode that may slow processing.',
      'It is recommended to run with XDEBUG_MODE=off for better performance.',
      'For example: export XDEBUG_MODE=off',
    ];
  }

  private function xdebugMaySlowProcessing(): bool {
    if (!extension_loaded('xdebug')) {
      return FALSE;
    }

    $mode = getenv('XDEBUG_MODE');
    if ($mode === FALSE || $mode === '') {
      $mode = ini_get('xdebug.mode') ?: '';
    }
    $mode = strtolower(trim($mode));
    if ($mode === '' || $mode === 'off') {
      return FALSE;
    }
    $modes = array_map('trim', explode(',', $mode));
    $slow_modes = [
      'debug',
      'coverage',
      'profile',
      'trace',
    ];

    return (bool) array_intersect($modes, $slow_modes);
  }

}
