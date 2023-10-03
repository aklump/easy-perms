<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\GetConcretePaths;
use AKlump\EasyPerms\Tests\FilesTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Helpers\GetConcretePaths
 */
class GetConcretePathsTest extends TestCase {

  use FilesTestTrait;

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
        'lorem_dir/',
        'lorem_file',
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
        'lorem_dir/',
        'lorem_file',
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
        'lorem_dir/',
        'lorem_file',
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
    $base = $this->getBasePath();
    $expected = array_map(fn($path) => "$base/$path", $expected);
    $this->assertSame($expected, (new GetConcretePaths())("$base/$path"));
  }

}
