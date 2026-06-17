<?php

namespace Drupal\Tests\printable\Unit\Plugin\PrintableFormat;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\printable\Plugin\PrintableFormat\PrintFormat;
use Drupal\printable\Plugin\PrintableLinkExtractor\PrintableLinkExtractorInterface;
use Drupal\printable\PrintableCssIncludeInterface;
use Drupal\printable\PrintableLinkExtractorPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the print format plugin.
 */
#[Group('Printable')]
#[CoversClass(PrintFormat::class)]
class PrintFormatTest extends UnitTestCase {

  /**
   * The plugin definition of this plugin.
   *
   * @var array
   */
  protected array $pluginDefinition;

  /**
   * The ID of the plugin.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * The configuration to be passed into the format plugin constructor.
   *
   * @var array
   */
  protected array $configuration;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->pluginDefinition = [
      'description' => 'Print description.',
      'id' => 'print',
      'module' => 'printable',
      'title' => 'Print',
      'class' => 'Drupal\printable\Plugin\PrintableFormat\PrintFormat',
      'provider' => 'printable',
    ];
    $this->pluginId = 'print';
    $this->configuration = [];
  }

  /**
   * Tests getting the plugin label from the plugin.
   */
  public function testGetLabel(): void {
    $format = new PrintFormat($this->configuration, $this->pluginId, $this->pluginDefinition, $this->getConfigFactoryStub([
      'printable.settings' => [
        'extract_links' => 'none',
      ],
    ]), $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $this->assertEquals('Print', $format->getLabel());
  }

  /**
   * Tests getting the plugin description from the plugin.
   */
  public function testGetDescription(): void {
    $format = new PrintFormat($this->configuration, $this->pluginId, $this->pluginDefinition, $this->getConfigFactoryStub([
      'printable.settings' => [
        'extract_links' => 'none',
      ],
    ]), $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $this->assertEquals('Print description.', $format->getDescription());
  }

  /**
   * Tests getting the default configuration for this plugin.
   */
  public function testDefaultConfiguration(): void {
    $format = new PrintFormat($this->configuration, $this->pluginId, $this->pluginDefinition, $this->getConfigFactoryStub([
      'printable.settings' => [
        'extract_links' => 'none',
      ],
    ]), $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $this->assertEquals(['show_print_dialogue' => TRUE], $format->defaultConfiguration());
  }

  /**
   * Tests getting the current configuration for this plugin.
   */
  public function testGetConfiguration(): void {
    $format = new PrintFormat($this->configuration, $this->pluginId, $this->pluginDefinition, $this->getConfigFactoryStub([
      'printable.settings' => [
        'extract_links' => 'none',
      ],
    ]), $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $this->assertEquals(['show_print_dialogue' => TRUE], $format->getConfiguration());
  }

  /**
   * Tests that additional configuration is internally stored and accessible.
   */
  public function testGetPassedInConfiguration(): void {
    $format = new PrintFormat(['test_configuration_value' => TRUE], $this->pluginId, $this->pluginDefinition, $this->getConfigFactoryStub([
      'printable.settings' => [
        'show_print_dialogue' => TRUE,
      ],
    ]), $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $this->assertEquals(
      [
        'test_configuration_value' => TRUE,
        'show_print_dialogue' => TRUE,
      ],
      $format->getConfiguration()
    );
  }

  /**
   * Test that default configuration can be modified and changes accessed.
   */
  public function testSetConfiguration(): void {
    $new_configuration = ['show_print_dialogue' => FALSE];

    $settings_config_mock = $this->createMock(Config::class);
    $settings_config_mock->expects($this->once())
      ->method('get')
      ->with('extract_links')
      ->willReturn('none');

    $config_mock = $this->createMock(Config::class);
    $config_mock->expects($this->once())
      ->method('set')
      ->with('print', $new_configuration)
      ->willReturnSelf();
    $config_mock->expects($this->once())
      ->method('save');

    $config_factory_mock = $this->createMock(ConfigFactory::class);
    $config_factory_mock->expects($this->once())
      ->method('getEditable')
      ->with('printable.format')
      ->willReturn($config_mock);

    $config_factory_mock->expects($this->once())
      ->method('get')
      ->with('printable.settings')
      ->willReturn($settings_config_mock);

    $format = new PrintFormat($this->configuration, $this->pluginId, $this->pluginDefinition, $config_factory_mock, $this->getCssIncludeStub(), $this->getLinkExtractorPluginManagerStub(), $this->getRendererStub());
    $format->setConfiguration($new_configuration);

    $this->assertEquals($new_configuration, $format->getConfiguration());
  }

  /**
   * Get the CSS include stub.
   */
  protected function getCssIncludeStub() {
    return $this->createMock(PrintableCssIncludeInterface::class);
  }

  /**
   * Get the Link extractor stub.
   */
  protected function getLinkExtractorIncludeStub() {
    return $this->createMock(PrintableLinkExtractorInterface::class);
  }

  /**
   * Get the Link extractor plugin manager stub.
   */
  protected function getLinkExtractorPluginManagerStub() {
    $linkExtractor = $this->createMock(PrintableLinkExtractorInterface::class);
    $manager = $this->createMock(PrintableLinkExtractorPluginManager::class);
    $manager->expects($this->any())
      ->method('createInstance')
      ->willReturn($linkExtractor);
    return $manager;
  }

  /**
   * Get the Renderer stub.
   */
  protected function getRendererStub() {
    return $this->createMock(RendererInterface::class);
  }

}
