<?php

namespace Drupal\Tests\printable\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\PrintableFormatPluginManager;
use Drupal\printable\PrintableLinkBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the print format plugin.
 */
#[Group('Printable')]
#[CoversClass(PrintableLinkBuilder::class)]
class PrintableLinkBuilderTest extends UnitTestCase {

  /**
   * Tests generating the render array of printable links.
   */
  public function testBuildLinks(): void {
    $definitions = [
      'foo' => [
        'title' => 'Foo',
      ],
      'bar' => [
        'title' => 'Bar',
      ],
    ];
    $entity_type = 'node';
    $entity_id = rand(1, 100);

    $config = $this->getConfigFactoryStub([
      'printable.settings' => [
        'open_target_blank' => TRUE,
        'exclude_printable_links' => FALSE,
      ],
    ]);

    $printable_manager = $this->createMock(PrintableFormatPluginManager::class);
    $printable_manager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $link_builder = new PrintableLinkBuilder($config, $printable_manager);

    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->exactly(2))
      ->method('getEntityTypeId')
      ->willReturn($entity_type);
    $entity->expects($this->exactly(2))
      ->method('id')
      ->willReturn($entity_id);

    $links = $link_builder->buildLinks($entity);
    $this->assertEquals(2, count($links));
    foreach ($definitions as $key => $definition) {
      $link = $links[$key];
      $this->assertEquals($definition['title'], $link['title']);
      $this->assertEquals(Url::fromRoute('printable.show_format.' . $entity_type, [
        'printable_format' => $key,
        'entity' => $entity_id,
      ]), $link['url']);
      $this->assertEquals('_blank', $link['attributes']['target']);
    }
  }

}
