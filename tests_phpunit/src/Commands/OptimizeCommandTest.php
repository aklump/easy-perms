<?php

namespace AKlump\EasyPerms\Tests\Commands;

use AKlump\EasyPerms\Commands\ApplyCommand;
use AKlump\EasyPerms\Environment\CheckEnvironment;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \AKlump\EasyPerms\Commands\OptimizeCommand
 */
class OptimizeCommandTest extends TestCase {

  use TestWithFilesTrait;

  public function testOptimizeConsolidatesPaths() {
    $config_file = $this->getTestFilePath('optimize.yml', TRUE);
    $config = [
      'writable' => [
        'foo/bar.txt',
        'foo/*.txt',
        'baz/qux.txt',
      ],
      'readonly' => [
        'a/b/c.php',
        'a/b/*.php',
      ],
    ];
    file_put_contents($config_file, Yaml::dump($config));

    // We also need to create the files so GetConcretePaths can resolve them
    $this->getTestFilePath('foo/bar.txt', TRUE);
    $this->getTestFilePath('foo/other.txt', TRUE);
    $this->getTestFilePath('baz/qux.txt', TRUE);
    $this->getTestFilePath('a/b/c.php', TRUE);
    $this->getTestFilePath('a/b/d.php', TRUE);

    $application = new Application();
    $application->add(new \AKlump\EasyPerms\Commands\OptimizeCommand());
    $command = $application->find('optimize');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['config' => [$config_file]]);

    // Verify the file was updated
    $optimized_config = Yaml::parseFile($config_file);
    $this->assertEquals(['baz/qux.txt', 'foo/*.txt'], $optimized_config['writable']);
    $this->assertEquals(['a/b/*.php'], $optimized_config['readonly']);

    // Check that backup was created
    $path_info = pathinfo($config_file);
    $backup_glob = sprintf('%s/%s.[0-9]*.%s', $path_info['dirname'], $path_info['filename'], $path_info['extension']);
    $backups = glob($backup_glob);
    $this->assertGreaterThanOrEqual(1, count($backups));
  }

  public function testOptimizeHandlesGlobArguments() {
    $config_file1 = $this->getTestFilePath('glob1.yml', TRUE);
    $config_file2 = $this->getTestFilePath('glob2.yml', TRUE);
    $config = [
      'writable' => [
        'foo/bar.txt',
        'foo/*.txt',
      ],
    ];
    file_put_contents($config_file1, Yaml::dump($config));
    file_put_contents($config_file2, Yaml::dump($config));

    $this->getTestFilePath('foo/bar.txt', TRUE);
    $this->getTestFilePath('foo/other.txt', TRUE);

    $application = new Application();
    $application->add(new \AKlump\EasyPerms\Commands\OptimizeCommand());
    $command = $application->find('optimize');
    $commandTester = new CommandTester($command);

    // Use a glob that matches both files
    $glob = dirname($config_file1) . '/glob*.yml';
    $commandTester->execute(['config' => [$glob]]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('Optimized', $output);
    $this->assertStringContainsString('glob1.yml', $output);
    $this->assertStringContainsString('glob2.yml', $output);

    $this->assertEquals(['foo/*.txt'], Yaml::parseFile($config_file1)['writable']);
    $this->assertEquals(['foo/*.txt'], Yaml::parseFile($config_file2)['writable']);
  }

  public function testOptimizeSortsPathsIgnoringQuotes() {
    $config_file = $this->getTestFilePath('sort.yml', TRUE);
    $config = [
      'writable' => [
        'zebra.txt',
        "'apple.txt'",
        'banana.txt',
        "'carrot.txt'",
      ],
    ];
    file_put_contents($config_file, Yaml::dump($config));

    $this->getTestFilePath('zebra.txt', TRUE);
    $this->getTestFilePath('apple.txt', TRUE);
    $this->getTestFilePath('banana.txt', TRUE);
    $this->getTestFilePath('carrot.txt', TRUE);

    $application = new Application();
    $application->add(new \AKlump\EasyPerms\Commands\OptimizeCommand());
    $command = $application->find('optimize');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['config' => [$config_file]]);

    $optimized_config = Yaml::parseFile($config_file);
    // apple.txt, banana.txt, carrot.txt, zebra.txt
    // Note: Yaml::parse will remove the single quotes if they were used for quoting in YAML,
    // but here we are testing that the logic handles them if they are part of the string or how they are sorted.
    // If we want to test literal single quotes, we might need to be careful with how YAML parses them.
    // Actually, in YAML, 'apple.txt' is parsed as the string apple.txt.
    // To have literal quotes, it would be "'apple.txt'".
    
    $this->assertEquals([
      "'apple.txt'",
      'banana.txt',
      "'carrot.txt'",
      'zebra.txt',
    ], $optimized_config['writable']);
  }
}
