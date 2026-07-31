<?php

namespace AKlump\EasyPerms\Tests\Helpers;

use AKlump\EasyPerms\Helpers\HandleMemory;

/**
 * @covers \AKlump\EasyPerms\Helpers\HandleMemory
 */
class HandleMemoryTest extends \PHPUnit\Framework\TestCase {

  /**
   * @var string
   */
  private $original;

  protected function setUp(): void {
    parent::setUp();
    $this->original = ini_get('memory_limit');
  }

  protected function tearDown(): void {
    ini_set('memory_limit', $this->original);
    parent::tearDown();
  }

  public function testInvokeDoesNothingWhenExistingAboveClassMinimum() {
    ini_set('memory_limit', '768M');
    (new HandleMemory())();
    $this->assertSame('768M', ini_get('memory_limit'));
  }

  public function testInvokeIncreasesWhenExistingBelowClassMinimum() {
    ini_set('memory_limit', '128M');
    (new HandleMemory())();
    $this->assertSame(HandleMemory::MINIMUM, ini_get('memory_limit'));
  }

  public function testInvokeDoesNothingWhenExactlyAtClassMinimum() {
    ini_set('memory_limit', HandleMemory::MINIMUM);
    (new HandleMemory())();
    $this->assertSame(HandleMemory::MINIMUM, ini_get('memory_limit'));
  }

  /**
   * A string comparison puts "1G" below "512M", so the limit must be resolved
   * to bytes or these larger limits get silently reduced.
   *
   * @dataProvider dataForLargerLimitIsNeverReducedProvider
   */
  public function testLargerLimitIsNeverReduced(string $existing) {
    ini_set('memory_limit', $existing);
    (new HandleMemory())();
    $this->assertSame($existing, ini_get('memory_limit'), sprintf('Expected "%s" to be left alone, not lowered to %s.', $existing, HandleMemory::MINIMUM));
  }

  public function dataForLargerLimitIsNeverReducedProvider(): array {
    return [
      ['1G'],
      ['2G'],
      ['8G'],
      ['1024M'],
      ['8192M'],
      // 768 MB expressed in kilobytes and in bytes.
      ['786432K'],
      ['805306368'],
    ];
  }

  public function testUnlimitedIsNeverReduced() {
    ini_set('memory_limit', '-1');
    (new HandleMemory())();
    $this->assertSame('-1', ini_get('memory_limit'), 'An unlimited memory limit must not be capped.');
  }

  /**
   * A negative quantity carrying a unit suffix is not a value php.ini accepts,
   * so it is injected rather than set, but it must still read as unlimited.
   *
   * @dataProvider dataForNegativeQuantityReadsAsUnlimitedProvider
   */
  public function testNegativeQuantityReadsAsUnlimited(string $reported) {
    ini_set('memory_limit', '128M');
    $subject = new class($reported) extends HandleMemory {

      private string $reported;

      public function __construct(string $reported) {
        $this->reported = $reported;
      }

      protected function getCurrentLimit(): string {
        return $this->reported;
      }

    };
    $subject();
    $this->assertSame('128M', ini_get('memory_limit'), sprintf('"%s" must be treated as unlimited, leaving the limit untouched.', $reported));
  }

  public function dataForNegativeQuantityReadsAsUnlimitedProvider(): array {
    return [
      ['-1'],
      ['-1M'],
      ['-1G'],
      ['-2'],
    ];
  }

  /**
   * @dataProvider dataForSmallerLimitIsRaisedProvider
   */
  public function testSmallerLimitIsRaised(string $existing) {
    ini_set('memory_limit', $existing);
    (new HandleMemory())();
    $this->assertSame(HandleMemory::MINIMUM, ini_get('memory_limit'));
  }

  public function dataForSmallerLimitIsRaisedProvider(): array {
    return [
      ['64M'],
      ['128M'],
      ['256M'],
      ['511M'],
      // 256 MB expressed in kilobytes and in bytes.
      ['262144K'],
      ['268435456'],
    ];
  }

  public function testLowercaseUnitSuffixIsUnderstood() {
    ini_set('memory_limit', '1g');
    (new HandleMemory())();
    $this->assertSame('1g', ini_get('memory_limit'), 'A lowercase unit suffix must be resolved, not lowered.');
  }

}
