<?php

namespace AKlump\EasyPerms\Tests\Environment;

use AKlump\EasyPerms\Environment\CheckXdebug;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Environment\CheckXdebug
 */
class CheckXdebugTest extends TestCase {

  public function testIsOptimizedReturnsTrueIfXdebugNotLoaded() {
    // We can't easily unload extensions in PHP, but we can test the behavior
    // if we were able to mock extension_loaded. Since we can't mock it easily
    // without extensions like runkit or uopz, we'll test the actual environment.
    $check = new CheckXdebug();
    if (!extension_loaded('xdebug')) {
      $this->assertTrue($check->isOptimized());
    }
    else {
      // If xdebug IS loaded, we can manipulate environment variables.
      putenv('XDEBUG_MODE=off');
      $this->assertTrue($check->isOptimized());

      putenv('XDEBUG_MODE=debug');
      $this->assertFalse($check->isOptimized());

      putenv('XDEBUG_MODE=coverage,debug');
      $this->assertFalse($check->isOptimized());

      putenv('XDEBUG_MODE=develop');
      $this->assertTrue($check->isOptimized());
    }
  }

  public function testGetRecommendationsReturnsExpectedArray() {
    $check = new CheckXdebug();
    $recommendations = $check->getRecommendations();
    $this->assertIsArray($recommendations);
    $this->assertNotEmpty($recommendations);
    $this->assertStringContainsString('Xdebug', $recommendations[0]);
  }
}
