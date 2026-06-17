<?php

namespace Drupal\Tests\printable\Unit\Plugin\Derivative;

use Drupal\Core\Entity\EntityType;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\Plugin\Derivative\PrintableLinksBlock;
use Drupal\printable\PrintableEntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable links block plugin derivative.
 */
#[Group('Printable')]
#[CoversClass(PrintableLinksBlock::class)]
class PrintableLinksBlockTest extends UnitTestCase {

  /**
   * Tests getting the plugin label from the plugin.
   */
  public function testGetDerivativeDefinitions(): void {
    $foo_entity_definition = $this->createMock(EntityType::class);
    $foo_entity_definition->method('getLabel')
      ->willReturn('Foo');

    $bar_entity_definition = $this->createMock(EntityType::class);
    $bar_entity_definition->method('getLabel')
      ->willReturn('Bar');

    $printable_entity_manager = $this->createMock(PrintableEntityManager::class);
    $printable_entity_manager->expects($this->once())
      ->method('getPrintableEntities')
      ->willReturn([
        'foo' => $foo_entity_definition,
        'bar' => $bar_entity_definition,
      ]);

    $derivative = new PrintableLinksBlock($printable_entity_manager);
    $derivative->setStringTranslation($this->getStringTranslationStub());

    $base_plugin_definition = [
      'admin_label' => 'Printable Links Block',
    ];

    $expected = [
      'foo' => [
        'admin_label' => 'Printable Links Block (Foo)',
      ],
      'bar' => [
        'admin_label' => 'Printable Links Block (Bar)',
      ],
    ];

    $this->assertEquals($expected, $derivative->getDerivativeDefinitions($base_plugin_definition));
  }

}
