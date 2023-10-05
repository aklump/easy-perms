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

  public function testMissingConstructorBasePathIsAllowed() {
    $obj = new Testable();
    $this->assertSame('', $obj->getBasePath());
  }

  public function testSetBasePathWithEmptyThrows() {
    $this->expectException(\InvalidArgumentException::class);
    (new Testable())->setBasePath('');
  }

}

class Testable {

  use \AKlump\EasyPerms\Traits\HasBasePathTrait;
}
