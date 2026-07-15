<?php

namespace AKlump\EasyPerms\Tests\Environment;

use AKlump\EasyPerms\Environment\CheckEnvironment;
use AKlump\EasyPerms\Environment\EnvironmentCheckInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \AKlump\EasyPerms\Environment\CheckEnvironment
 */
class CheckEnvironmentTest extends TestCase {


  public function testIsReadyReturnsTrueWhenChecksPass() {
    $mockCheck = $this->createMock(EnvironmentCheckInterface::class);
    $mockCheck->method('isOptimized')->willReturn(TRUE);

    $check = new CheckEnvironment();
    $this->assertTrue($check->isReady(
      $this->createMock(InputInterface::class),
      $this->createMock(OutputInterface::class),
      $this->createMock(HelperSet::class),
      [$mockCheck]
    ));
  }

  public function testIsReadyAsksQuestionWhenCheckFails() {
    $mockCheck = $this->createMock(EnvironmentCheckInterface::class);
    $mockCheck->method('isOptimized')->willReturn(FALSE);
    $mockCheck->method('getRecommendations')->willReturn(['Do this', 'Do that']);

    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    
    $questionHelper = $this->createMock(QuestionHelper::class);
    $questionHelper->method('ask')->willReturn(TRUE);
    
    $helperSet = $this->createMock(HelperSet::class);
    $helperSet->method('get')->with('question')->willReturn($questionHelper);

    $check = new CheckEnvironment();
    $this->assertTrue($check->isReady($input, $output, $helperSet, [$mockCheck]));
  }

  public function testIsReadyReturnsFalseWhenUserDeclines() {
    $mockCheck = $this->createMock(EnvironmentCheckInterface::class);
    $mockCheck->method('isOptimized')->willReturn(FALSE);
    $mockCheck->method('getRecommendations')->willReturn(['Warning']);

    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);
    
    $questionHelper = $this->createMock(QuestionHelper::class);
    $questionHelper->method('ask')->willReturn(FALSE);
    
    $helperSet = $this->createMock(HelperSet::class);
    $helperSet->method('get')->with('question')->willReturn($questionHelper);

    $check = new CheckEnvironment();
    $this->assertFalse($check->isReady($input, $output, $helperSet, [$mockCheck]));
  }
}
