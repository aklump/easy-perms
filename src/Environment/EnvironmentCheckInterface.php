<?php

namespace AKlump\EasyPerms\Environment;

interface EnvironmentCheckInterface {

  /**
   * @return bool True if the environment fully optimized for this check.
   */
  public function isOptimized(): bool;

  /**
   * @return array<string> 1 or more recommendations to make this optimized.
   */
  public function getRecommendations(): array;
}
