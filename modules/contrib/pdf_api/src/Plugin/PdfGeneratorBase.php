<?php

namespace Drupal\pdf_api\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a base class for PDF generator plugins.
 */
abstract class PdfGeneratorBase extends PluginBase implements PdfGeneratorInterface {

  /**
   * The global options for the PDF generator.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The entity being rendered.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The pdf generator.
   *
   * @var object
   */
  protected $generator;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get the dimensions of a given page size.
   *
   * @param string $page_size
   *   The page size to get the dimensions for (e.g. A4).
   *
   * @return array|false
   *   An array with the keys "width" and "height" that contain the width and
   *   height dimensions respectively. False if the page size is unknown.
   */
  protected function getPageDimensions($page_size) {
    if (!$this->isValidPageSize($page_size)) {
      return FALSE;
    }
    $page_sizes = $this->pageSizes();

    return $page_sizes[$page_size];
  }

  /**
   * Checks if a given page size is valid.
   *
   * @param string $page_size
   *   The page size to check.
   *
   * @return bool
   *   TRUE if the page size is valid, FALSE if not.
   */
  protected function isValidPageSize($page_size) {
    return array_key_exists($page_size, $this->pageSizes());
  }

  /**
   * Get an array of all valid page sizes, keyed by the page size name.
   *
   * @return array
   *   An array of page sizes with the values an array of width and height and
   *   keys the page size name (e.g. A4).
   */
  protected function pageSizes() {
    return [
      'A0' => [
        'width' => 0,
        'height' => 0,
      ],
      'A1' => [
        'width' => 0,
        'height' => 0,
      ],
      'A2' => [
        'width' => 0,
        'height' => 0,
      ],
      'A3' => [
        'width' => 0,
        'height' => 0,
      ],
      'A4' => [
        'width' => 0,
        'height' => 0,
      ],
      'A5' => [
        'width' => 0,
        'height' => 0,
      ],
      'A6' => [
        'width' => 0,
        'height' => 0,
      ],
      'A7' => [
        'width' => 0,
        'height' => 0,
      ],
      'A8' => [
        'width' => 0,
        'height' => 0,
      ],
      'A9' => [
        'width' => 0,
        'height' => 0,
      ],
      'B0' => [
        'width' => 0,
        'height' => 0,
      ],
      'B1' => [
        'width' => 0,
        'height' => 0,
      ],
      'B10' => [
        'width' => 0,
        'height' => 0,
      ],
      'B2' => [
        'width' => 0,
        'height' => 0,
      ],
      'B3' => [
        'width' => 0,
        'height' => 0,
      ],
      'B4' => [
        'width' => 0,
        'height' => 0,
      ],
      'B5' => [
        'width' => 0,
        'height' => 0,
      ],
      'B6' => [
        'width' => 0,
        'height' => 0,
      ],
      'B7' => [
        'width' => 0,
        'height' => 0,
      ],
      'B8' => [
        'width' => 0,
        'height' => 0,
      ],
      'B9' => [
        'width' => 0,
        'height' => 0,
      ],
      'C5E' => [
        'width' => 0,
        'height' => 0,
      ],
      'Comm10E' => [
        'width' => 0,
        'height' => 0,
      ],
      'DLE' => [
        'width' => 0,
        'height' => 0,
      ],
      'Executive' => [
        'width' => 0,
        'height' => 0,
      ],
      'Folio' => [
        'width' => 0,
        'height' => 0,
      ],
      'Ledger' => [
        'width' => 0,
        'height' => 0,
      ],
      'Legal' => [
        'width' => 0,
        'height' => 0,
      ],
      'Letter' => [
        'width' => 0,
        'height' => 0,
      ],
      'Tabloid' => [
        'width' => 0,
        'height' => 0,
      ],
    ];
  }

  /**
   * Get stderr from teh command that was run.
   *
   * @return string
   *   Content of stderr output.
   */
  public function getStderr() {
    return '';
  }

  /**
   * Get stdout from the command that was run.
   *
   * @return string
   *   Content of stdout.
   */
  public function getStdout() {
    return '';
  }

  /**
   * Display error messages.
   *
   * @return bool
   *   Whether any errors occurred and were not ignored.
   */
  public function displayErrors() {
    $error = $this->getStderr();
    if ($error && !$this->generator->ignoreWarnings) {
      // Add stdOut contents - they might help.
      $output = $this->getStdout();
      if ($output) {
        $output = str_replace("\n", "<br />", $output);

        $markup = new TranslatableMarkup('@error<br />Output was:<br />@output',
          [
            '@error' => $error,
            '@output' => new FormattableMarkup($output, []),
          ]);
      }
      else {
        $markup = $error;
      }
      $this->messenger->addError($markup);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Set global options.
   *
   * @param array $options
   *   The array of options to merge into the currently set options.
   */
  public function setOptions(array $options) {
    $this->options += $options;
  }

  /**
   * {@inheritdoc}
   */
  public function usePrintableDisplay() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

}
