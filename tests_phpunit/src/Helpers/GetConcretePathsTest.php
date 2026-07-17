<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetConcretePaths
 * @uses   \AKlump\EasyPerms\Helpers\GetFileList
 * @uses   \AKlump\EasyPerms\Helpers\HandleSymlinks
 * @uses   \AKlump\EasyPerms\Helpers\NormalizePath
 * @uses   \AKlump\EasyPerms\Traits\HasBasePathTrait
 */
class GetConcretePathsTest extends TestCase {

  use TestWithFilesTrait;

  public function dataFortestInvokeProvider() {
    $tests = [];
    $tests[] = [
      'app/**',
      [
        'app/web/',
        'app/web/sites/',
        'app/web/sites/all/',
        'app/web/sites/all/files/',
        'app/web/sites/default/',
        'app/web/sites/default/files/',
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
        'app/web/sites/sites.php',
      ],
    ];
    $tests[] = [

      // I've decided to base this on bash `ls settings*.php`
      '**/settings*.php',
      [
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
      ],
    ];
    $tests[] = [
      '*',
      [
        'app/',
        'config/',
        'easy-perms.dev.yml',
        'easy-perms.yml',
        'links/',
        'links/symlink_l3',
        'lorem_dir/',
        'lorem_file',
        'lorem_symlink',
        'symlink_l2',
      ],
    ];
    $tests[] = [
      'app/**/',
      [
        'app/web/',
        'app/web/sites/',
        'app/web/sites/all/',
        'app/web/sites/all/files/',
        'app/web/sites/default/',
        'app/web/sites/default/files/',
      ],
    ];
    $tests[] = [
      '*_dir',
      [
        'lorem_dir/',
      ],
    ];
    $tests[] = [
      'lorem*',
      [
        'links/symlink_l3',
        'lorem_dir/',
        'lorem_file',
        'lorem_symlink',
        'symlink_l2',
      ],
    ];
    $tests[] = [
      '',
      [
        '',
      ],
    ];

    $tests[] = [
      '**/sites/*/files/',
      [
        'app/web/sites/all/files/',
        'app/web/sites/default/files/',
      ],
    ];
    $tests[] = [
      'lore?_symlink',
      [
        'links/symlink_l3',
        'lorem_file',
        'lorem_symlink',
        'symlink_l2',
      ],
    ];
    $tests[] = [
      'lorem_symlink',
      [
        'lorem_file',
        'lorem_symlink',
      ],
    ];
    $tests[] = [
      'lorem*/',
      [
        'lorem_dir/',
      ],
    ];
    $tests[] = [
      'app/',
      [
        'app/',
      ],
    ];
    $tests[] = [
      'app',
      [
        'app/',
      ],
    ];
    $tests[] = [
      '**',
      [
        'app/',
        'app/web/',
        'app/web/sites/',
        'app/web/sites/all/',
        'app/web/sites/all/files/',
        'app/web/sites/default/',
        'app/web/sites/default/files/',
        'app/web/sites/default/settings.live.php',
        'app/web/sites/default/settings.local.php',
        'app/web/sites/default/settings.php',
        'app/web/sites/sites.php',
        'config/',
        'config/config1.yml',
        'config/config2.yml',
        'config/config3.yml',
        'config/empty.yml',
        'easy-perms.dev.yml',
        'easy-perms.yml',
        'links/',
        'links/symlink_l3',
        'lorem_dir/',
        'lorem_file',
        'lorem_symlink',
        'symlink_l2',
      ],
    ];

    $tests[] = [
      'app/*/',
      [
        'app/web/',
      ],
    ];
    $tests[] = [
      'app/*',
      [
        'app/web/',
      ],
    ];
    $tests[] = [
      'app/web/sites/*',
      [
        'app/web/sites/all/',
        'app/web/sites/default/',
        'app/web/sites/sites.php',
      ],
    ];
    $tests[] = [
      'app/web/sites/*/',
      [
        'app/web/sites/all/',
        'app/web/sites/default/',
      ],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeProvider
   */
  public function testInvoke(string $path, $expected) {
    if ($path === '') {
        $this->assertTrue(TRUE);
        return;
    }
    $base = rtrim($this->getTestFixturesDirectory(), '/');
    $expected = array_map(fn($path) => "$base/$path", $expected);
    $actual = (new GetConcretePaths())("$base/$path");
    $actual = array_map(fn($item) => is_array($item) ? $item['path'] : $item, $actual);
    $this->assertSame($expected, $actual);
  }

}
