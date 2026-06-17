<?php

namespace Drupal\Tests\printable\Unit\Plugin\Derivative;

use Drupal\Tests\UnitTestCase;
use Drupal\printable\Plugin\Derivative\PrintableFormatConfigureTabs;
use Drupal\printable\PrintableFormatPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable configuration tabs plugin derivative.
 */
#[Group('Printable')]
#[CoversClass(PrintableFormatConfigureTabs::class)]
class PrintableFormatConfigureTabsTest extends UnitTestCase {

  /**
   * Tests getting the plugin label from the plugin.
   */
  public function testGetDerivativeDefinitions(): void {
    $printable_format_manager = $this->createMock(PrintableFormatPluginManager::class);
    $printable_format_manager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'foo' => [
          'title' => 'Foo',
        ],
        'bar' => [
          'title' => 'Bar',
        ],
      ]);
    $derivative = new PrintableFormatConfigureTabs($printable_format_manager);

    $expected = [
      'foo' => [
        'title' => 'Foo',
        'route_name' => 'printable.format_configure_foo',
      ],
      'bar' => [
        'title' => 'Bar',
        'route_name' => 'printable.format_configure_bar',
      ],
    ];
    $this->assertEquals($expected, $derivative->getDerivativeDefinitions([]));
  }

}
