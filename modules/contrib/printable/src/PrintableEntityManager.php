<?php

namespace Drupal\printable;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper class for the printable module.
 */
class PrintableEntityManager implements PrintableEntityManagerInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity definitions of entities that have printable versions available.
   *
   * @var array
   */
  protected $compatibleEntities = [];

  /**
   * Constructs a new PrintableEntityManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('printable.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityName(EntityInterface $entity) {
    return $entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPrintableEntities() {
    $compatible_entities = $this->getCompatibleEntities();
    $entities = [];
    foreach ($this->config->get('printable_entities') as $entity_type) {
      if (isset($compatible_entities[$entity_type])) {
        $entities[$entity_type] = $compatible_entities[$entity_type];
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrintableEntity(EntityInterface $entity) {
    $entityTypeId = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    return array_key_exists($entity->getEntityTypeId(), $this->getPrintableEntities()) && (
      $this->config->get("printable_entities_bundles.{$entityTypeId}._all") ||
      $this->config->get("printable_entities_bundles.{$entityTypeId}.{$bundle}")
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getCompatibleEntities() {
    // If the entities are yet to be populated, get the entity definitions from
    // the entity manager.
    if (empty($this->compatibleEntities)) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $entity_definition) {
        // If this entity has a render controller, it has a printable version.
        if ($entity_definition->hasHandlerClass('view_builder')) {
          $this->compatibleEntities[$entity_type] = $entity_definition;
        }
      }
    }
    return $this->compatibleEntities;
  }

}
