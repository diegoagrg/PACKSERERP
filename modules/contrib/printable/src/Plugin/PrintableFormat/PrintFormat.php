<?php

namespace Drupal\printable\Plugin\PrintableFormat;

use Drupal\Core\Form\FormStateInterface;
use Drupal\printable\Plugin\PrintableFormatBase;

/**
 * Provides a plugin to display a printable version of a page.
 *
 * @PrintableFormat(
 *   id = "print",
 *   module = "printable",
 *   title = @Translation("Print"),
 *   description = @Translation("Printable version of page.")
 * )
 *
 * @todo Looks like the form stuff is unused.
 */
class PrintFormat extends PrintableFormatBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_print_dialogue' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form['show_print_dialogue'] = [
      '#type' => 'checkbox',
      '#title' => 'Show print dialogue',
      '#default_value' => $config['show_print_dialogue'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
