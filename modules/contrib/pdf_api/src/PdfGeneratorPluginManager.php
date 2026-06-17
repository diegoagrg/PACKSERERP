<?php

namespace Drupal\pdf_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages PDF generator plugins.
 */
class PdfGeneratorPluginManager extends DefaultPluginManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Pdf Generator Plugin Manager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, ConfigFactory $config, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    parent::__construct('Plugin/PdfGenerator', $namespaces, $module_handler, 'Drupal\pdf_api\Plugin\PdfGeneratorInterface', 'Drupal\pdf_api\Annotation\PdfGenerator');
    $this->config = $config;
    $this->alterInfo('pdf_api_generator');
    $this->setCacheBackend($cache_backend, 'pdf_api_generator');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $configuration += (array) $this->config->get('printable.format')
      ->get($plugin_id);
    return parent::createInstance($plugin_id, $configuration);
  }

}
