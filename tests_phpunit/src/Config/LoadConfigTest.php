<?php

namespace AKlump\EasyPerms\Tests\Config;

use AKlump\EasyPerms\Config\ConfigInterface;
use AKlump\EasyPerms\Config\DefaultDirectoryPermissions;
use AKlump\EasyPerms\Config\DefaultFilePermissions;
use AKlump\EasyPerms\Config\LoadConfig;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @covers \AKlump\EasyPerms\Config\LoadConfig
 * @uses   \AKlump\EasyPerms\Config\DefaultDirectoryPermissions
 * @uses   \AKlump\EasyPerms\Config\DefaultFilePermissions
 */
class LoadConfigTest extends TestCase {

  use TestWithFilesTrait;

  public function testEmptyYamlIsHandled() {
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $config_files = [
      $this->getTestFixturePath('config/empty.yml'),
    ];
    $config = (new LoadConfig($config_defaults))($config_files);
    $this->assertCount(2, $config);
    $this->assertArrayHasKey(ConfigInterface::DIRECTORY_PERMISSIONS, $config);
    $this->assertArrayHasKey(ConfigInterface::FILE_PERMISSIONS, $config);
  }

  public function testNonExistentFileThrows() {
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $this->expectException(ParseException::class);
    $config_files = [
      $this->getTestFilePath('config/bogus.yml'),
    ];
    (new LoadConfig($config_defaults))($config_files);
  }

  public function testInvokeConfig1() {
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $config_files = [
      $this->getTestFixturePath('config/config1.yml'),
    ];
    $config = (new LoadConfig($config_defaults))($config_files);

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
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $config_files = [
      $this->getTestFixturePath('config/config1.yml'),
      $this->getTestFixturePath('config/config2.yml'),
    ];
    $config = (new LoadConfig($config_defaults))($config_files);

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
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $config_files = [
      $this->getTestFixturePath('config/config1.yml'),
      $this->getTestFixturePath('config/config2.yml'),
      $this->getTestFixturePath('config/config3.yml'),
    ];
    $file_perms = (new LoadConfig($config_defaults))($config_files)[ConfigInterface::FILE_PERMISSIONS];

    $this->assertCount(4, $file_perms);
    $this->assertSame('0400', $file_perms[ConfigInterface::READONLY]);
    $this->assertSame('0700', $file_perms[ConfigInterface::EXECUTABLE]);
    $this->assertSame('0600', $file_perms[ConfigInterface::WRITEABLE]);
    $this->assertSame('0500', $file_perms[ConfigInterface::DEFAULT]);
  }

  public function testLoadJsonFile() {
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $json_file = $this->getTestFilePath('config.json');
    file_put_contents($json_file, json_encode([
      ConfigInterface::READONLY => ['foo.txt'],
    ]));
    $config = (new LoadConfig($config_defaults))([$json_file]);
    $this->assertArrayHasKey(ConfigInterface::READONLY, $config);
    $this->assertContains(dirname($json_file) . '/foo.txt', $config[ConfigInterface::READONLY]);
  }

  public function testLoadUnsupportedExtensionThrows() {
    $config_defaults = [];
    $file = $this->getTestFilePath('config.txt');
    touch($file);
    $this->expectException(\InvalidArgumentException::class);
    (new LoadConfig($config_defaults))([$file]);
  }

  public function testAbsolutePathsInConfigThrows() {
    $config_defaults = [];
    $yaml_file = $this->getTestFilePath('abs.yml');
    file_put_contents($yaml_file, "readonly:\n  - /absolute/path");
    $this->expectException(\RuntimeException::class);
    (new LoadConfig($config_defaults))([$yaml_file]);
  }

  public function testDevConfigOverridesBaseConfig() {
    $config_defaults = [
      ConfigInterface::FILE_PERMISSIONS => new DefaultFilePermissions(),
      ConfigInterface::DIRECTORY_PERMISSIONS => new DefaultDirectoryPermissions(),
    ];
    $base_config_file = $this->getTestFixturePath('easy-perms.yml');
    $dev_config_file = $this->getTestFixturePath('easy-perms.dev.yml');

    // Default for file_permissions.default in DefaultFilePermissions is 0640.
    // Base config sets file_permissions.default to 0644
    file_put_contents($base_config_file, "file_permissions:\n  default: '0644'");

    // Dev config does NOT set file_permissions.default.
    // It only sets some other value.
    file_put_contents($dev_config_file, "readonly:\n  - foo.txt");

    $config = (new LoadConfig($config_defaults))([
      $base_config_file,
      $dev_config_file,
    ]);

    // It should be 0644 because base set it.
    // BUT if defaults (0640) are merged into dev_config_file (which doesn't have it),
    // and THEN dev_config is merged into base_config, it might overwrite 0644 with 0640.
    $this->assertSame('0644', $config[ConfigInterface::FILE_PERMISSIONS]['default']);
  }

}
