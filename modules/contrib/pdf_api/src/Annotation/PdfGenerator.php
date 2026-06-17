<?php

namespace Drupal\pdf_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an PDF generator annotation object.
 *
 * @Annotation
 */
class PdfGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the generator.
   *
   * @var string
   */
  public $module;

  /**
   * The human-readable name of the generator.
   *
   * This is used as an administrative summary of what the generator does.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Additional administrative information about the generator's behavior.
   *
   * @var \Drupal\Core\Annotation\Translation optional
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
