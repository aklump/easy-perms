<?php

namespace AKlump\EasyPerms\Tests;

use AKlump\EasyPerms\ConfigInterface;
use AKlump\EasyPerms\LoadConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @covers \AKlump\EasyPerms\LoadConfig
 */
class LoadConfigTest extends TestCase {

  public function testEmptyYamlIsHandled() {
    $config_files = [
      __DIR__ . '/test_config_files/empty.yml',
    ];
    $config = (new LoadConfig())($config_files);
    $this->assertCount(2, $config);
    $this->assertArrayHasKey(ConfigInterface::DIRECTORY_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::FILE_PERMISSIONS, $config);
  }

  public function testNonExistentFileThrows() {
    $this->expectException(ParseException::class);
    $config_files = [
      __DIR__ . '/bogus.yml',
    ];
    (new LoadConfig())($config_files);
  }

  public function testInvokeConfig1() {
    $config_files = [
      __DIR__ . '/test_config_files/config1.yml',
    ];
    $config = (new LoadConfig())($config_files);

    $this->assertCount(6, $config);

    $this->assertArrayHasKey(ConfigInterface::DEFAULT, $config);
    $this->assertArrayHasKey(ConfigInterface::DIRECTORY_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::EXECUTABLE, $config);
    $this->assertArrayHasKey(ConfigInterface::FILE_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::READONLY, $config);
    $this->assertArrayHasKey(ConfigInterface::WRITEABLE, $config);

    $this->assertCount(1, $config[ConfigInterface::DEFAULT]);
    $this->assertCount(1, $config[ConfigInterface::EXECUTABLE]);

    $this->assertCount(4, $config[ConfigInterface::FILE_PERMISSIONS]);
    $this->assertCount(4, $config[ConfigInterface::DIRECTORY_PERMISSIONS]);
  }

  public function testInvokeTwoFilesMerge() {
    $config_files = [
      __DIR__ . '/test_config_files/config1.yml',
      __DIR__ . '/test_config_files/config2.yml',
    ];
    $config = (new LoadConfig())($config_files);

    $this->assertCount(6, $config);

    $this->assertArrayHasKey(ConfigInterface::DEFAULT, $config);
    $this->assertArrayHasKey(ConfigInterface::DIRECTORY_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::EXECUTABLE, $config);
    $this->assertArrayHasKey(ConfigInterface::FILE_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::READONLY, $config);
    $this->assertArrayHasKey(ConfigInterface::WRITEABLE, $config);

    $this->assertCount(1, $config[ConfigInterface::DEFAULT]);
    $this->assertCount(2, $config[ConfigInterface::EXECUTABLE]);

    $this->assertCount(4, $config[ConfigInterface::FILE_PERMISSIONS]);
    $this->assertCount(4, $config[ConfigInterface::DIRECTORY_PERMISSIONS]);
  }

  public function testFilePermissionsAreReplaced() {
    $config_files = [
      __DIR__ . '/test_config_files/config1.yml',
      __DIR__ . '/test_config_files/config2.yml',
      __DIR__ . '/test_config_files/config3.yml',
    ];
    $file_perms = (new LoadConfig())($config_files)[ConfigInterface::FILE_PERMISSIONS];

    $this->assertCount(4, $file_perms);
    $this->assertSame('0400', $file_perms[ConfigInterface::READONLY]);
    $this->assertSame('0700', $file_perms[ConfigInterface::EXECUTABLE]);
    $this->assertSame('0600', $file_perms[ConfigInterface::WRITEABLE]);
    $this->assertSame('0500', $file_perms[ConfigInterface::DEFAULT]);
  }

}
