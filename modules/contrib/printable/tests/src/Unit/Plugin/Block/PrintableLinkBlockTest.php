<?php

namespace Drupal\Tests\printable\Unit\Plugin\Block;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\Plugin\Block\PrintableLinksBlock;
use Drupal\printable\PrintableLinkBuilderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable links block plugin.
 */
#[Group('Printable')]
#[CoversClass(PrintableLinksBlock::class)]
class PrintableLinkBlockTest extends UnitTestCase {

  /**
   * The block configuration.
   *
   * @var array
   */
  protected array $configuration = [];

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected array $pluginDefinition = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginId = 'printable_links_block:node';
    $this->pluginDefinition['module'] = 'printable';
    $this->pluginDefinition['provider'] = 'printable';
  }

  /**
   * Tests the block build method.
   */
  public function testBuild(): void {
    $route_match = $this->createMock(CurrentRouteMatch::class);
    $route_match->expects($this->exactly(2))
      ->method('getMasterRouteMatch')
      ->willReturnSelf();
    $route_match->expects($this->exactly(2))
      ->method('getParameter')
      ->willReturn($this->createMock(EntityInterface::class));

    $links = [
      'title' => 'Print',
      'url' => '/foo/1/printable/print',
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    $links_builder = $this->createMock(PrintableLinkBuilderInterface::class);
    $links_builder->expects($this->once())
      ->method('buildLinks')
      ->willReturn($links);

    $dateFormatter = $this->getMockBuilder(DateFormatter::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entityTypeManager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $block = new PrintableLinksBlock($this->configuration, $this->pluginId, $this->pluginDefinition, $route_match, $links_builder, $dateFormatter, $entityTypeManager);

    $expected_build = [
      '#theme' => 'links__entity__printable',
      '#links' => $links,
      '#cache' => [
        'contexts' => ['route'],
        'tags' => ['node:'],
        'max-age' => 180,
      ],
    ];
    $this->assertEquals($expected_build, $block->build());
  }

}
