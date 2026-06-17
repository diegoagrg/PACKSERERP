<?php

namespace Drupal\pdf_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Dompdf Settings Form.
 */
class DompdfSettingsForm extends ConfigFormBase {

  /**
   * The settings of the form.
   */
  const SETTINGS = 'pdf_api.dom_pdf.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pdf_api_dom_pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config(self::SETTINGS);
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;

    $form['defaultFont'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default font'),
      '#description' => $this->t('Used if no suitable fonts can be found. This must exist in the font folder.'),
      '#default_value' => $settings->get('defaultFont') ?? 'serif',
    ];

    $form['dpi'] = [
      '#type' => 'number',
      '#title' => $this->t('DPI'),
      '#description' => $this->t('The DPI setting for images and fonts. This setting determines the size of the rendered image used internally for positioning and layout. Defaults to 96.'),
      '#default_value' => $settings->get('dpi') ?? 96,
    ];

    $form['fontHeightRatio'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font height ratio'),
      '#description' => $this->t('The ratio between the font height and the line height. This affects the vertical positioning of text. Defaults to 1.1.'),
      '#default_value' => $settings->get('fontHeightRatio') ?? 1.1,
    ];

    // phpcs:disable DrupalPractice.General.OptionsT.TforValue
    $form['pdfBackend'] = [
      '#type' => 'select',
      '#title' => $this->t('PDF backend'),
      '#description' => $this->t('The PDF rendering backend to use. Currently supported: <strong>CPDF</strong>, <strong>GD</strong>, <strong>PDFlib</strong>. "auto" will look for PDFlib and use it if found, or if not it will fall back on CPDF. "GD" renders PDFs to graphic files.'),
      '#options' => [
        'PDFlib' => 'PDFlib',
        'CPDF' => 'CPDF',
        'GD' => 'GD',
        'auto' => 'auto',
      ],
      '#default_value' => $settings->get('pdfBackend') ?? 'CPDF',
    ];
    // phpcs:enable DrupalPractice.General.OptionsT.TforValue

    $form['pdflibLicense'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PDFlib license'),
      '#description' => $this->t('If you are using a licensed, commercial version of PDFlib, specify your license key here. If you are not using PDFlib, the setting will be ignored.'),
      '#default_value' => $settings->get('pdflibLicense'),
    ];

    $form['isPhpEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PHP'),
      '#description' => $this->t('If checked, dompdf will automatically evaluate embedded PHP contained within &lt;script type="text/php"&gt; ... &lt;/script&gt; tags. Enabling this for documents you do not trust (e.g. arbitrary remote html pages) is a security risk. Embedded scripts are run with the same level of system access available to dompdf. Set this option to false (recommended) if you wish to process untrusted documents.'),
      '#default_value' => $settings->get('isPhpEnabled'),
    ];

    $form['isRemoteEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable remote file access'),
      '#description' => $this->t('If checked, dompdf will access remote sites for images and CSS files as required.'),
      '#default_value' => $settings->get('isRemoteEnabled'),
    ];

    $form['isJavascriptEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable inline JavaScript'),
      '#description' => $this->t('If checked, dompdf will automatically insert JavaScript code contained within &lt;script type="text/javascript"&gt; ... &lt;/script&gt; tags as written into the PDF. NOTE: This is PDF-based JavaScript to be executed by the PDF viewer, not browser-based JavaScript executed by Dompdf.'),
      '#default_value' => $settings->get('isJavascriptEnabled'),
    ];

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['directories'] = [
      '#type' => 'details',
      '#title' => $this->t('Directories'),
      '#group' => 'advanced',
    ];

    $form['directories']['fontDir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font directory'),
      '#description' => $this->t('The directory where dompdf will store fonts and font metrics. This directory must exist and be writable by the web server process.'),
      '#default_value' => $settings->get('fontDir') ?? 'temporary://',
    ];

    $form['directories']['fontCache'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Font cache'),
      '#description' => $this->t('The location of the DOMPDF font cache directory. This directory contains the cached font metrics for the fonts used by DOMPDF. This directory can be the same as font directory.'),
      '#default_value' => $settings->get('fontCache') ?? 'temporary://',
    ];

    $form['directories']['tempDir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Temporary directory'),
      '#description' => $this->t('The temporary directory is required to download remote images and when using the PDFlib component. This directory must be writable by the web server.'),
      '#default_value' => $settings->get('tempDir') ?? 'temporary://',
    ];

    $form['chroot'] = [
      '#type' => 'details',
      '#title' => $this->t('Chroot'),
      '#description' => $this->t('All local files opened by dompdf must be in a subdirectory of the directory or directories specified by this option. Relative to DRUPAL_ROOT. The "." directory is equivalent to DRUPAL_ROOT. This is a security measure to prevent DOMPDF from accessing system files.'),
      '#group' => 'advanced',
    ];

    $chroot = [];
    if ($form_state->hasValue('chroot')) {
      $chroot = $form_state->getValue('chroot');
      $chroot = $chroot['directories'] ?? ['.'];
    }
    else {
      $chroot = $settings->get('chroot') ?? ['.'];
    }

    $form['chroot']['directories'] = [
      '#type' => 'container',
      '#prefix' => '<div id="chroot-directories-wrapper">',
      '#suffix' => '</div>',
    ];
    foreach ($chroot as $key => $value) {
      $form['chroot']['directories'][$key] = [
        '#type' => 'textfield',
        '#default_value' => $value,
      ];
    }

    if ($form_state->isRebuilding()) {
      $form['chroot']['directories'][] = [
        '#type' => 'textfield',
      ];
    }

    $form['chroot']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another item'),
      '#submit' => ['::addChrootItem'],
      '#ajax' => [
        'callback' => '::addChrootItemAjaxCallback',
        'wrapper' => 'chroot-directories-wrapper',
      ],
    ];

    $form['debug'] = [
      '#type' => 'details',
      '#title' => $this->t('Debug'),
      '#group' => 'advanced',
    ];

    $form['debug']['debugPng'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug PNG'),
      '#default_value' => $settings->get('debugPng'),
    ];

    $form['debug']['debugKeepTemp'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug keep temp'),
      '#default_value' => $settings->get('debugKeepTemp'),
    ];

    $form['debug']['debugCss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug CSS'),
      '#default_value' => $settings->get('debugCss'),
    ];

    $form['debug']['debugLayout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug layout'),
      '#default_value' => $settings->get('debugLayout'),
    ];

    $form['debug']['debugLayoutLines'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug layout lines'),
      '#default_value' => $settings->get('debugLayoutLines'),
      '#states' => [
        'visible' => [
          ':input[name="debug[debugLayout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['debug']['debugLayoutBlocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug layout blocks'),
      '#default_value' => $settings->get('debugLayoutBlocks'),
      '#states' => [
        'visible' => [
          ':input[name="debug[debugLayout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['debug']['debugLayoutInline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug layout inline'),
      '#default_value' => $settings->get('debugLayoutInline'),
      '#states' => [
        'visible' => [
          ':input[name="debug[debugLayout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['debug']['debugLayoutPaddingBox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug layout padding box'),
      '#default_value' => $settings->get('debugLayoutPaddingBox'),
      '#states' => [
        'visible' => [
          ':input[name="debug[debugLayout]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory->getEditable(static::SETTINGS);
    $values = $form_state->getValues();

    unset($values['chroot']['directories']['add_item']);
    $chroot = array_filter($values['chroot']['directories']);
    if (empty($chroot)) {
      $chroot = ['.'];
    }
    $settings
      ->set('defaultFont', $values['defaultFont'])
      ->set('dpi', $values['dpi'])
      ->set('fontHeightRatio', $values['fontHeightRatio'])
      ->set('pdfBackend', $values['pdfBackend'])
      ->set('pdflibLicense', $values['pdflibLicense'])
      ->set('isPhpEnabled', $values['isPhpEnabled'])
      ->set('isRemoteEnabled', $values['isRemoteEnabled'])
      ->set('isJavascriptEnabled', $values['isJavascriptEnabled'])
      ->set('chroot', $chroot)
      ->set('fontDir', $values['directories']['fontDir'])
      ->set('fontCache', $values['directories']['fontCache'])
      ->set('tempDir', $values['directories']['tempDir'])
      ->set('debugPng', $values['debug']['debugPng'])
      ->set('debugKeepTemp', $values['debug']['debugKeepTemp'])
      ->set('debugCss', $values['debug']['debugCss'])
      ->set('debugLayout', $values['debug']['debugLayout'])
      ->set('debugLayoutLines', $values['debug']['debugLayoutLines'])
      ->set('debugLayoutBlocks', $values['debug']['debugLayoutBlocks'])
      ->set('debugLayoutInline', $values['debug']['debugLayoutInline'])
      ->set('debugLayoutPaddingBox', $values['debug']['debugLayoutPaddingBox'])
      ->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the "add another item" button.
   *
   * Add a new empty item to the chroot field.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addChrootItem(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for adding another item to the chroot field.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The chroot field directories.
   */
  public function addChrootItemAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['chroot']['directories'];
  }

}
