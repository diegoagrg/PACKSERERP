<?php

namespace Drupal\Tests\printable\Unit;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\PrintableEntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable entity manager plugin.
 */
#[Group('Printable')]
#[CoversClass(PrintableEntityManager::class)]
class PrintableEntityManagerTest extends UnitTestCase {

  /**
   * Tests getting the printable entities.
   */
  public function testGetPrintableEntities(): void {
    // Construct a printable entity manager and its dependencies.
    $entity_definition = $this->createMock(EntityType::class);
    $entity_definition->expects($this->any())
      ->method('hasHandlerClass')
      ->willReturn(TRUE);

    $entity_manager = $this->createMock(EntityTypeManager::class);
    $entity_manager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'node' => $entity_definition,
        'comment' => $entity_definition,
      ]);

    $config = $this->getConfigFactoryStub([
      'printable.settings' => [
        'printable_entities' => ['node', 'comment', 'bar'],
      ],
    ]);
    $printable_entity_manager = new PrintableEntityManager($entity_manager, $config);

    // Verify getting the printable entities.
    $expected_entity_definitions = [
      'node' => $entity_definition,
      'comment' => $entity_definition,
    ];
    $actual = $printable_entity_manager->getPrintableEntities();

    $this->assertEquals($expected_entity_definitions, $actual);
  }

}
