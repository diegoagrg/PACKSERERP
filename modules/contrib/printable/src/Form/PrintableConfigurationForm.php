<?php

namespace Drupal\printable\Form;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\printable\PrintableEntityManagerInterface;
use Drupal\printable\PrintableLinkExtractorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides shared configuration form for all printable formats.
 */
class PrintableConfigurationForm extends ConfigFormBase {

  /**
   * Constructs a new form object.
   *
   * @param \Drupal\printable\PrintableEntityManagerInterface $printableEntityManager
   *   The printable entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Defines the configuration object factory.
   * @param \Drupal\Core\Block\BlockManager $blockManager
   *   Manages discovery and instantiation of block plugins.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\printable\PrintableLinkExtractorPluginManager $linkExtractorPluginManager
   *   The link extractor plugin manager.
   */
  public function __construct(
    protected PrintableEntityManagerInterface $printableEntityManager,
    protected $configFactory,
    protected BlockManager $blockManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    protected PrintableLinkExtractorPluginManager $linkExtractorPluginManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('printable.entity_manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.block'),
      $container->get('entity_type.bundle.info'),
      $container->get('printable.link_extractor_plugin_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'printable_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['printable.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $printable_format = NULL) {
    $config = $this->config('printable.settings');

    $compatibleEntities = $this->printableEntityManager->getCompatibleEntities();
    $currentlySelected = $this->printableEntityManager->getPrintableEntities();

    // Allow users to choose what entities printable is enabled for.
    $form['printable_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Printable Enabled Entities'),
      '#description' => $this->t('Select the entities that printable support should be enabled for.'),
      '#options' => array_combine(array_keys($compatibleEntities), array_map(function ($entity) {
        return $entity->getLabel();
      }, $compatibleEntities)),
      '#default_value' => array_keys($currentlySelected),
    ];

    // I'd like to use vertical tabs here but depends on a fix for
    // https://www.drupal.org/project/drupal/issues/1148950
    $form['printable_entities_bundles'] = [
      '#type' => 'container',
      '#title' => $this->t('Per bundle settings'),
    ];

    $needsSave = FALSE;

    // Build the options array.
    foreach ($this->printableEntityManager->getCompatibleEntities() as $entity_type => $entity_definition) {
      $form['printable_entities']['#options'][$entity_type] = $entity_definition->getLabel();

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

      $form['printable_entities_bundles'][$entity_type] = [
        '#type' => 'details',
        '#title' => $entity_definition->getLabel(),
        '#states' => [
          'visible' => [
            ':input[name="printable_entities[' . $entity_type . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#group' => 'printable_entities_bundles',
      ];

      $default = $config->get("printable_entities_bundles.{$entity_type}._all");
      if (is_null($default)) {
        $default = TRUE;
        $needsSave = TRUE;
        $config->set("printable_entities_bundles.{$entity_type}._all", TRUE);
      }

      $form['printable_entities_bundles'][$entity_type]['_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('All bundles for this entity type'),
        '#description' => $this->t('(If new bundles are created, they will be automatically included)'),
        '#default_value' => $default,
        '#parents' => [
          'printable_entities_bundles',
          $entity_type,
          '_all',
        ],
        '#attributes' => [
          'name' => "printable_entities_bundles__{$entity_type}__all",
        ],
      ];

      foreach ($bundles as $bundleName => $bundle) {

        $default = $config->get("printable_entities_bundles.{$entity_type}.{$bundleName}");
        if (is_null($default)) {
          $default = FALSE;
          $needsSave = TRUE;
          $config->set("printable_entities_bundles.{$entity_type}.{$bundleName}", FALSE);
        }

        $form['printable_entities_bundles'][$entity_type][$bundleName] = [
          '#type' => 'checkbox',
          '#title' => $bundle['label'],
          '#default_value' => $default,
          '#parents' => [
            'printable_entities_bundles',
            $entity_type,
            $bundleName,
          ],
          '#states' => [
            'invisible' => [
              ':input[name="printable_entities_bundles__' . $entity_type . '__all"]' => ['checked' => TRUE],
            ],
            '#attributes' => [
              'name' => "printable_entities_bundles__{$entity_type}__{$bundleName}",
            ],
          ],
        ];
      }
    }

    if ($needsSave) {
      $config->save();
    }

    // Provide option to open printable page in a new tab/window.
    $form['open_target_blank'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open in New Tab'),
      '#description' => $this->t('Open the printable version in a new tab/window.'),
      '#default_value' => $config->get('open_target_blank'),
    ];

    // Allow users to include CSS from the current theme.
    $form['css_include'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Include'),
      '#description' => $this->t('Specify an additional CSS file to include. Relative to the root of the Drupal install. The token <em>[theme:theme_machine_name]</em> is available.'),
      '#default_value' => $config->get('css_include'),
    ];

    // Provide option to configure link extraction.
    $form['extract_links'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link handling'),
      '#description' => $this->t('Choose what happens with to any links in the content.'),
      '#options' => [],
      '#default_value' => $config->get('extract_links'),
    ];

    $definitions = $this->linkExtractorPluginManager->getDefinitions();
    uksort($definitions, function ($a, $b) use ($definitions) {
      $result = $definitions[$a]['weight'] <=> $definitions[$b]['weight'];
      if ($result) {
        return $result;
      }
      return $definitions[$a]['title'] <=> $definitions[$b]['title'];
    });

    foreach ($definitions as $pluginId => $definition) {
      $form['extract_links']['#options'][$pluginId] = $definition['title'];
    }

    // Provide option to include a canonical URL.
    $form['settings']['print_link_canonical'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include &lt;link rel="canonical"&gt;'),
      '#default_value' => $this->config('printable.settings')
        ->get('link_canonical'),
      '#description' => $this->t('Enabling this option will include a canonical URL link tag in the print output, to direct search engines to the canonical content display.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // The 'all' checkboxes don't get processed right by the form API.
    $rawInput = $form_state->getUserInput();
    $fixedInput = $rawInput['printable_entities_bundles'];
    foreach ($fixedInput as $entityType => $values) {
      $fixedInput[$entityType]['_all'] = (!empty($rawInput["printable_entities_bundles__{$entityType}__all"]));
    }

    $this->configFactory->getEditable('printable.settings')
      ->set('printable_entities', $form_state->getValue('printable_entities'))
      ->set('printable_entities_bundles', $fixedInput)
      ->set('open_target_blank', $form_state->getValue('open_target_blank'))
      ->set('css_include', $form_state->getValue('css_include'))
      ->set('extract_links', $form_state->getValue('extract_links'))
      ->set('link_canonical', $form_state->getValue('print_link_canonical'))
      ->save();
    // Invalidate the block cache to update custom block-based derivatives.
    // @todo try to make configsaveevent later.
    $this->blockManager->clearCachedDefinitions();
    parent::submitForm($form, $form_state);
  }

}
