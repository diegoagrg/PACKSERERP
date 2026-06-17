<?php

namespace Drupal\excel_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

/**
 * Provides an Excel Importer form.
 */
class ExcelImporterForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $fileSystem, MessengerInterface $messenger, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'excel_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('excel_importer.settings');

    $form = [
      '#attributes' => ['enctype' => 'multipart/form-data'],
    ];

    $form['file_upload_details'] = [
      '#markup' => $config->get('introduction'),
    ];

    $form['excel_file'] = [
      '#type' => 'managed_file',
      '#name' => 'excel_file',
      '#title' => $this->t('Please provide the file'),
      '#size' => 20,
      '#description' => $this->t('Use <em>xlsx</em> file format only. The file size should not exceed <em>@file_size</em>.', ['@file_size' => ini_get('upload_max_filesize')]),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'xlsx'],
      ],
      '#upload_location' => 'public://content/excel_files/',
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('excel_file') && $form_state->getTriggeringElement()['#name'] != 'excel_file_remove_button') {
      $file = $this->entityTypeManager->getStorage('file')
        ->load($form_state->getValue('excel_file')[0]);

      $full_path = $file->get('uri')->value;
      $file_name = basename($full_path);

      $config = $this->configFactory->get('excel_importer.settings');
      $types = $config->get('allowed_types', []);

      try {
        $input_file_name = $this->fileSystem->realpath('public://content/excel_files/' . $file_name);
        $spreadsheet = IOFactory::load($input_file_name);
        $sheets = $spreadsheet->getAllSheets();
        $valid_sheets_count = 0;

        foreach ($sheets as $available_sheet) {
          if (in_array($available_sheet->getTitle(), $types)) {
            $valid_sheets_count++;
          }
        }

        if ($valid_sheets_count == 0) {
          $form_state->setErrorByName('excel_file', $this->t('The file needs to contains at least one sheet with a valid content type name (machine name). Please check if the sheet names of your file match with the content types allowed in the Excel Importer administration settings (admin/config/content/excel_importer).'));
        }
      }
      catch (Exception $e) {
        $this->loggerFactory->get('excel_importer')->error($e->getMessage());
        $this->messenger->addError($this->t('Unable to process the form. Please try again!'));
        $this->messenger->addError($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $saved_entities = 0;
    $file = $this->entityTypeManager->getStorage('file')
      ->load($form_state->getValue('excel_file')[0]);

    $full_path = $file->get('uri')->value;
    $file_name = basename($full_path);

    $config = $this->configFactory->get('excel_importer.settings');
    $types = $config->get('allowed_types', []);

    try {
      $input_file_name = $this->fileSystem->realpath('public://content/excel_files/' . $file_name);
      $spreadsheet = IOFactory::load($input_file_name);
      $sheet_data = [];

      foreach ($types as $type) {
        $sheet = $spreadsheet->getSheetByName($type);
        $names = [];

        if ($sheet) {
          foreach ($sheet->getRowIterator() as $key => $row) {
            $cell_iterator = $row->getCellIterator();
            $cell_iterator->setIterateOnlyExistingCells(FALSE);
            $cells = [];

            // @todo Find a better solution
            if (!$this->isRowEmpty($row)) {
              foreach ($cell_iterator as $cell_key => $cell) {
                $hasValue = strlen(trim((string) $cell->getValue()));

                if ($key == 2 && $hasValue) {
                  $field_label = $cell->getValue();

                  if ($this->isValidField($type, $field_label)) {
                    $names[$cell_key] = $field_label;
                  }
                  else {
                    $this->messenger->addError($this->t('The field <strong>"@field"</strong> is not in the <strong>"@content_type"</strong> content type.', [
                      '@field' => $field_label,
                      '@content_type' => $type,
                    ]));

                    return FALSE;
                  }
                }

                if ($key > 2 && !empty($names[$cell_key])) {
                  if (!$this->isRequireFieldProvided($type, $names[$cell_key], $cell->getValue())) {
                    $this->messenger->addError($this->t('The <strong>"@field"</strong> data is required field for the <strong>"@content_type"</strong> content type. <br/> See <strong>Sheet:</strong> @content_type, <strong>Row:</strong> @row_number', [
                      '@field' => $names[$cell_key],
                      '@content_type' => $type,
                      '@row_number' => $key,
                    ]));

                    return FALSE;
                  }

                  if (!$this->isCorrectDataType($type, $names[$cell_key], $cell->getValue())) {
                    $this->messenger->addError($this->t('The <strong>"@field"</strong> should be a number for the <strong>"@content_type"</strong> content type. <br/> See <strong>Sheet:</strong> @content_type, <strong>Row:</strong> @row_number, <strong>Value:</strong> @value', [
                      '@field' => $names[$cell_key],
                      '@content_type' => $type,
                      '@row_number' => $key,
                      '@value' => $cell->getValue(),
                    ]));

                    return FALSE;
                  }

                  if ($this->isTaxonomyReference($type, $names[$cell_key]) && !$this->isValidTaxonomyReference($type, $names[$cell_key], $cell->getValue())) {
                    $this->messenger->addError($this->t('The <strong>"@field"</strong> is not found in the referenced taxonomy term for the <strong>"@content_type"</strong> content type. <br/> See <strong>Sheet:</strong> @content_type, <strong>Row:</strong> @row_number, <strong>Value:</strong> @value', [
                      '@field' => $names[$cell_key],
                      '@content_type' => $type,
                      '@row_number' => $key,
                      '@value' => $cell->getValue(),
                    ]));

                    return FALSE;
                  }

                  // All is well, add data to collector.
                  $cells[$names[$cell_key]] = $this->getCorrectValue($type, $names[$cell_key], $cell->getValue());
                }
              }

              if ($key > 2) {
                $cells['type'] = $type;
                $sheet_data[$type][] = $cells;
              }
            }
          }
        }
      }

      foreach ($sheet_data as $sheet_content_types) {
        foreach ($sheet_content_types as $sheet_content_type) {
          $node = $this->entityTypeManager->getStorage('node')->create($sheet_content_type);
          if (!strlen(trim((string) $node->getTitle()))) {
            $node->setTitle($node->type->entity->label() . ' ' . date('Y-m-d'));
          }
          $node->save();
          $saved_entities++;
        }
      }

      $this->messenger->addMessage($this->t('Excel File Imported Successfully</br><strong>@number</strong> entries saved.', ['@number' => $saved_entities]));

    }
    catch (Exception $e) {
      $this->loggerFactory->get('excel_importer')->error($e->getMessage());
    }
  }

  /**
   * Checks if a field is a taxonomy reference field.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   *
   * @return bool
   *   TRUE if the field is a taxonomy reference, FALSE otherwise.
   */
  private function isTaxonomyReference($bundle, $field) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

    if (array_key_exists($field, $definitions) && isset($definitions[$field]->getSettings()['target_type']) && $definitions[$field]->getSettings()['target_type'] == 'taxonomy_term') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the vocabulary machine name for a taxonomy term reference field.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   *
   * @return string|null
   *   The vocabulary machine name, or NULL if not found.
   */
  private function getVocabulary($bundle, $field) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
    return $this->arrayKeyFirst($definitions[$field]->getSettings()['handler_settings']['target_bundles']);
  }

  /**
   * Checks if a value is a valid taxonomy reference for a field.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a valid taxonomy reference, FALSE otherwise.
   */
  private function isValidTaxonomyReference($bundle, $field, $value) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
    $vid = $this->getVocabulary($bundle, $field);
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $value, 'vid' => $vid]);
    $tid = $this->arrayKeyFirst($term);

    if ($definitions[$field]->getSettings()['handler_settings']['auto_create'] && !empty($value)) {
      return TRUE;
    }
    elseif ($tid) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a field exists in the content type.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   *
   * @return bool
   *   TRUE if the field exists, FALSE otherwise.
   */
  private function isValidField($bundle, $field) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

    if (array_key_exists($field, $definitions)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a required field is provided in the sheet.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the required field is provided or not required, FALSE otherwise.
   */
  private function isRequireFieldProvided($bundle, $field, $value) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

    if (array_key_exists($field, $definitions) && (($definitions[$field]->isRequired() && !empty($value)) || (!$definitions[$field]->isRequired()))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a field value is the correct data type.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is the correct data type, FALSE otherwise.
   */
  private function isCorrectDataType($bundle, $field, $value) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

    if (array_key_exists($field, $definitions) && ((!empty($value) && $definitions[$field]->getType() == 'integer' && is_numeric($value)) || empty($value) || ($definitions[$field]->getType() != 'integer'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a row is empty (all cells are empty).
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Row $row
   *   The row object.
   *
   * @return bool
   *   TRUE if the row is empty, FALSE otherwise.
   */
  private function isRowEmpty($row) {
    $cell_iterator = $row->getCellIterator();
    $cell_iterator->setIterateOnlyExistingCells(FALSE);

    foreach ($cell_iterator as $cell) {
      $value = $cell->getValue();
      if (strlen(trim((string) $value))) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get value for a field, handling taxonomy/entity references and types.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   * @param mixed $value
   *   The value to process.
   *
   * @return mixed
   *   The processed value for the field.
   */
  private function getCorrectValue($bundle, $field, $value) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);

    if ($this->isEntityReference($bundle, $field)) {
      $target_type = $definitions[$field]->getSettings()['target_type'];

      if ($target_type == 'taxonomy_term') {
        $vid = $this->getVocabulary($bundle, $field);
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $value, 'vid' => $vid]);
        $tid = $this->arrayKeyFirst($term);
        $canAutoCreate = $definitions[$field]->getSettings()['handler_settings']['auto_create'];

        if ($canAutoCreate && empty($tid)) {
          $value = $this->createTaxonomyTerm($value, $vid);
        }
        else {
          $value = $tid;
        }
      }
      elseif ($value && ($target_type == 'user' || $target_type == 'node')) {
        $target_field = $target_type == 'user' ? 'mail' : 'title';
        $value = $this->arrayKeyFirst($this->entityTypeManager->getStorage($target_type)->loadByProperties([$target_field => $value]));
      }
    }
    elseif ($definitions[$field]->getType() == 'daterange') {
      $date_values = explode(",", $value);
      $value = [
        'value' => trim($date_values[0]),
        'end_value' => trim($date_values[1]),
      ];
    }
    elseif (in_array($definitions[$field]->getType(), ['integer', 'float', 'decimal'])) {
      $value = !empty($value) ? $value : 0;
    }

    return $value;
  }

  /**
   * Polyfill for array_key_first().
   *
   * @param array $arr
   *   The array to get the first key from.
   *
   * @return int|string|null
   *   The first key of the array if it exists, NULL otherwise.
   */
  private function arrayKeyFirst(array $arr) {
    foreach ($arr as $key => $unused) {
      return $key;
    }
    return NULL;
  }

  /**
   * Creates a taxonomy term and returns its term ID (tid).
   *
   * @param string $name
   *   The name of the taxonomy term to create.
   * @param string $vid
   *   The vocabulary machine name.
   *
   * @return int|string
   *   The term ID (tid) of the created taxonomy term.
   */
  private function createTaxonomyTerm($name, $vid) {
    $term = Term::create([
      'name' => $name,
      'vid' => $vid,
    ]);
    $term->save();
    return $term->id();
  }

  /**
   * Checks if a field is an entity reference field.
   *
   * @param string $bundle
   *   The content type machine name.
   * @param string $field
   *   The field machine name.
   *
   * @return bool
   *   TRUE if the field is an entity reference, FALSE otherwise.
   */
  private function isEntityReference($bundle, $field) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
    return array_key_exists($field, $definitions) && strpos($definitions[$field]->getType(), 'entity_reference') !== FALSE;
  }

}
