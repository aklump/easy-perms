<?php

namespace AKlump\EasyPerms\Tests\Config;

use AKlump\EasyPerms\Config\DefaultDirectoryPermissions;
use AKlump\EasyPerms\Config\DefaultFilePermissions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Config\DefaultDirectoryPermissions
 * @covers \AKlump\EasyPerms\Config\DefaultFilePermissions
 */
class DefaultPermissionsTest extends TestCase {

  public function testDefaultDirectoryPermissions() {
    $perms = new DefaultDirectoryPermissions();
    $data = $perms->jsonSerialize();
    $this->assertArrayHasKey('default', $data);
    $this->assertArrayHasKey('readonly', $data);
    $this->assertArrayHasKey('writeable', $data);
    $this->assertArrayHasKey('executable', $data);
    $this->assertSame(0750, $data['default']);
  }

  public function testDefaultFilePermissions() {
    $perms = new DefaultFilePermissions();
    $data = $perms->jsonSerialize();
    $this->assertArrayHasKey('default', $data);
    $this->assertArrayHasKey('readonly', $data);
    $this->assertArrayHasKey('writeable', $data);
    $this->assertArrayHasKey('executable', $data);
    $this->assertSame(0640, $data['default']);
  }
}
