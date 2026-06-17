<?php

namespace Drupal\puphpeteer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdf_api\PdfGeneratorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Puphpeteer.
 */
class Configuration extends ConfigFormBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\pdf_api\PdfGeneratorPluginManager
   */
  protected $pdfGeneratorManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\pdf_api\PdfGeneratorPluginManager $pdf_generator_manager
   *   The plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PdfGeneratorPluginManager $pdf_generator_manager) {
    $this
      ->setConfigFactory($config_factory);

    $this->pdfGeneratorManager = $pdf_generator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.pdf_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'puphpeteer.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'puphpeteer_configuration';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('puphpeteer.settings')->get();

    $form['service'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use an external service instead of launching it ourselves'),
      '#default_value' => $config['service'],
    ];

    $form['service_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t("URL to pass to puppeteer.connect's browserURL option"),
      '#default_value' => $config['service_url'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['executable_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to node'),
      '#required' => TRUE,
      '#default_value' => $config['executable_path'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['chrome_extra_args'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra Chrome Parameters'),
      '#default_value' => $config['chrome_extra_args'] ?? FALSE,
      '#description' => $this->t('Enter any additional flags you want Chrome to be invoked with, one per line'),
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['idle_timeout'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Idle timeout'),
      '#default_value' => $config['idle_timeout'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['read_timeout'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Read timeout'),
      '#default_value' => $config['read_timeout'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['stop_timeout'] = [
      '#type' => 'number',
      '#min' => 5,
      '#title' => $this->t('Stop timeout'),
      '#default_value' => $config['stop_timeout'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Content source'),
      '#options' => [
        'printable' => $this->t('Normal printable rendering'),
        'canonical' => $this->t('Canonical URL for the entity'),
        'print' => $this->t('Print view mode for the entity'),
      ],
      '#default_value' => $config['source'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['headless'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Headless? (Normally on)'),
      '#description' => $this->t('To use Chrome in headful mode, you need to set DISPLAY in your PHP environment. If Chrome is in headful mode, it disables its ability to generate PDFs. PHP will wait for the browser to be closed before completing the request.'),
      '#default_value' => $config['headless'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['headless_new'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('New Chrome Headless Mode (Chrome 112 on)'),
      '#description' => $this->t('Chrome 112 and later implement a new headless mode. Should we use it?'),
      '#default_value' => $config['headless_new'] ?? FALSE,
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
          ':input[name="headless"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug?'),
      '#default_value' => $config['debug'] ?? FALSE,
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['remote_debugging_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The IP address on which Chrome should listen for debugging connections'),
      '#default_value' => $config['remote_debugging_address'] ?? '',
    ];

    $form['remote_debugging_port'] = [
      '#min' => 1025,
      '#type' => 'number',
      '#title' => $this->t('The port on which Chrome should listen for debugging connections'),
      '#default_value' => $config['remote_debugging_port'] ?? '',
    ];

    $form['log_to_node_console'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log node console?'),
      '#default_value' => $config['log_to_node_console'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['log_to_browser_console'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log browser console?'),
      '#default_value' => $config['log_to_browser_console'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['leave_running'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Leave the browser running when done'),
      '#default_value' => $config['leave_running'],
      '#states' => [
        'visible' => [
          ':input[name="service"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['triggerDebugging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trigger JS debugging immediately? (Normally off)'),
      '#default_value' => $config['triggerDebugging'],
    ];

    $form['devTools'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open devtools? (Normally off)'),
      '#default_value' => $config['devTools'],
    ];

    $form['slowMo'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Delay to add between Pupeteer actions'),
      '#default_value' => $config['slowMo'],
    ];

    $form['printBackground'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Print background images'),
      '#description' => $this->t('Printing background images can sometimes cause PDF generation failure. This option provides a way to check whether you are experiencing that issue.'),
      '#default_value' => $config['printBackground'] ?? FALSE,
    ];

    $form['pagedjs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load pagedjs in Chrome?'),
      '#default_value' => $config['pagedjs'],
    ];

    $form['basic_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Basic auth username (if required)'),
      '#default_value' => $config['basic_auth_username'] ?? '',
    ];

    $form['basic_auth_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Basic auth password'),
      '#default_value' => $config['basic_auth_password'] ?? '',
    ];

    $form['default_wait'] = [
      '#title' => $this->t('How long to wait before generating PDF'),
      '#type' => 'select',
      '#options' => [
        'document_loaded' => $this->t('Document Loaded'),
        'readystate_interactive' => $this->t('Ready state is interactive'),
        'readystate_complete' => $this->t('Ready state is complete'),
        'custom_event' => $this->t('Custom event'),
        'fixed_timeout' => $this->t('Fixed timeout'),
        'custom_function' => $this->t("A custom function that should complete when it's time generate the PDF"),
        'xpath' => $this->t('Wait for an XPath selection'),
      ],
      '#default_value' => $config['default_wait'] ?? 'readystate_complete',
    ];

    $form['default_wait_custom_event'] = [
      '#title' => $this->t('Custom event name'),
      '#description' => $this->t('The name of an event that can be listened for on the document DOM element'),
      '#type' => 'textfield',
      '#default_value' => $config['default_wait_custom_event'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="default_wait"]' => ['value' => 'custom_event'],
        ],
      ],
    ];

    $form['default_wait_fixed_timeout'] = [
      '#title' => $this->t('Fixed timeout'),
      '#description' => $this->t('A number of milliseconds to wait'),
      '#type' => 'number',
      '#minimum' => 0,
      '#default_value' => $config['default_wait_fixed_timeout'] ?? 500,
      '#states' => [
        'visible' => [
          ':input[name="default_wait"]' => ['value' => 'fixed_timeout'],
        ],
      ],
    ];

    $form['default_wait_custom_function'] = [
      '#title' => $this->t('Custom function'),
      '#description' => $this->t('A custom set of commands that will be invoked with eval by Chrome'),
      '#type' => 'textfield',
      '#default_value' => $config['default_wait_custom_function'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="default_wait"]' => ['value' => 'custom_function'],
        ],
      ],
    ];

    $form['default_wait_xpath'] = [
      '#title' => $this->t('XPath selector'),
      '#description' => $this->t('The selector to an XPath query to use'),
      '#type' => 'textfield',
      '#default_value' => $config['default_wait_xpath'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="default_wait"]' => ['value' => 'xpath'],
        ],
      ],
    ];

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<em>When debugging with headful Chrome, you can preview the print media version by pressing Control+Shift+P. Type "Rendering" and select Show Rendering. In the Emulate CSS media dropdown, select print.</em>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * Copied from the smtp module as it's better than what I've managed to
   * come up with but it is still imperfect - it will be fooled if the override
   * is the same and the editable value.
   *
   * @param string $name
   *   SMTP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('puphpeteer.settings')
      ->get($name);
    $current = $this->configFactory->get('puphpeteer.settings')->get($name);
    return $original != $current;
  }

  /**
   * Validate that we can run node and get a Chrome version.
   *
   * @param array $form
   *   The form being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateExecutable(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!$values['service']) {
      $path = $values['executable_path'];
      $dir = dirname($path);
      if (!is_dir($dir)) {
        $form_state->setErrorByName('executable_path',
          $this->t('The directory :dir does not exist', [':dir' => $dir]));
        return;
      }

      if (!is_executable($path)) {
        $form_state->setErrorByName('executable_path',
          $this->t(':path is not an executable', [':path' => $path]));
        return;
      }

      $output = '';
      $result_code = 0;
      $invoke = "{$path} -v";
      exec($invoke, $output, $result_code);

      if ($result_code) {
        $form_state->setErrorByName('executable_path',
          $this->t('Seeking to execute :path gave result code :result', [
            ':path' => $path,
            ':result' => $result_code,
          ]));
        return;
      }

      $this->messenger()
        ->addMessage($this->t('Node :version found', [
          ':version' => $output[0],
        ]));

      try {
        $generator = $this->pdfGeneratorManager
          ->createInstance('puphpeteer', []);
        $generator->startBrowser();
        $chromeVersion = $generator->getBrowser()->version();

        $this->messenger()
          ->addMessage($this->t('Chrome :version found', [
            ':version' => $chromeVersion,
          ]));
      }
      catch (\Exception $e) {
        // If there is an error, it will have been displayed already.
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateExecutable($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('puphpeteer.settings');
    foreach ([
      'executable_path',
      'chrome_extra_args',
      'idle_timeout',
      'read_timeout',
      'stop_timeout',
      'log_to_node_console',
      'debug',
      'log_to_browser_console',
      'headless',
      'headless_new',
      'remote_debugging_port',
      'remote_debugging_address',
      'slowMo',
      'devTools',
      'triggerDebugging',
      'source',
      'pagedjs',
      'service',
      'service_url',
      'leave_running',
      'basic_auth_username',
      'basic_auth_password',
      'printBackground',
      'default_wait',
      'default_wait_custom_event',
      'default_wait_fixed_timeout',
      'default_wait_custom_function',
      'default_wait_xpath',
    ] as $key) {
      $config->set($key, $values[$key]);
    }
    $config->save();
  }

}
