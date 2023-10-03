<?php

namespace AKlump\EasyPerms\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\EasyPerms\Traits\HasBasePathTrait
 */
class HasBasePathTraitTest extends TestCase {

  public function testSetBasePathWorksAsExpected() {
    $obj = new Testable('lorem/ipsum');
    $this->assertSame('foo/bar', $obj->setBasePath('foo/bar')->getBasePath());
  }

  public function testPathIsNormalized() {
    $path = (new Testable('lorem\\ipsum'))->getBasePath();
    $this->assertSame('lorem/ipsum', $path);
  }

  public function testBasePathWithPatternThrows() {
    $this->expectException(\InvalidArgumentException::class);
    new Testable('sites*.php');
  }

  public function testEmptyBasePathThrows() {
    $this->expectException(\InvalidArgumentException::class);
    new Testable('');
  }

}

class Testable {

  use \AKlump\EasyPerms\Traits\HasBasePathTrait;
}
