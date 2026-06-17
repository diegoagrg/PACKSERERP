<?php

namespace Drupal\printable;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Helper class for the printable module.
 */
class PrintableLinkBuilder implements PrintableLinkBuilderInterface {

  /**
   * Constructs a new PrintableLinkBuilder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\printable\PrintableFormatPluginManager $printableFormatManager
   *   The printable format plugin manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected PrintableFormatPluginManager $printableFormatManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function buildLinks(?EntityInterface $entity = NULL, ?string $viewMode = 'printable') {

    $printable_settings = $this->configFactory->get('printable.settings');

    if ($viewMode == 'printable' &&
      ($printable_settings->get('exclude_printable_links') ?? TRUE)) {
      return [];
    }

    $links = [];

    // Build the array of links to be added to the entity.
    foreach ($this->printableFormatManager->getDefinitions() as $key => $definition) {
      $links[$key] = [
        'title' => $definition['title'],
        'url' => Url::fromRoute('printable.show_format.' . $entity->getEntityTypeId(), [
          'printable_format' => $key,
          'entity' => $entity->id(),
        ]),
      ];
      // Add target "blank" if the configuration option is set.
      if ($printable_settings->get('open_target_blank') && ($key == 'print' or !$printable_settings->get('save_pdf'))) {
        $links[$key]['attributes']['target'] = '_blank';
      }
    }
    return $links;
  }

}
