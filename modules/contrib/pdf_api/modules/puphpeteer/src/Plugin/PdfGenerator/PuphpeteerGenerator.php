<?php

namespace Drupal\puphpeteer\Plugin\PdfGenerator;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use NigelCunningham\Puphpeteer\Puppeteer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A PDF generator plugin for Puphpeteer.
 *
 * @PdfGenerator(
 *   id = "puphpeteer",
 *   module = "puphpeteer",
 *   title = @Translation("Puphpeteer"),
 *   description = @Translation("PDF generator using Puphpeteer."),
 *   required_class = "NigelCunningham\Puphpeteer\Puppeteer",
 * )
 */
class PuphpeteerGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the DOMPDF class library.
   *
   * @var \NigelCunningham\puphpeteer\Puppeteer
   */
  protected $generator;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * URL Generator service instance.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $currentUser;

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Extension service.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extensionPathResolver;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Settings for our generated CSS.
   *
   * @var array
   */
  protected $css = [];

  /**
   * Page orientation.
   *
   * @var string
   */
  protected $landscape = FALSE;

  /**
   * Page size.
   *
   * @var string
   */
  protected $pageSize = 'A4';

  /**
   * Header.
   *
   * @var string
   */
  protected $header = '';

  /**
   * Footer.
   *
   * @var string
   */
  protected $footer = '';

  /**
   * HTML content.
   *
   * @var string
   */
  protected $html = '';

  /**
   * The Browser intsance.
   *
   * @var object
   */
  protected $browser = NULL;

  /**
   * Is the browser running as a service?
   *
   * @var bool
   */
  protected $isService = FALSE;

  /**
   * Is the browser running headlessly?
   *
   * @var bool
   */
  protected $isHeadless = FALSE;

  /**
   * The current web page being visited.
   *
   * @var object
   */
  protected $tab;

  /**
   * Puppeteer is running?
   *
   * @var bool
   */
  protected $puppeteerRunning = FALSE;

  /**
   * Puphpeteer configuration for this generator.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ConfigFactory $configFactory,
    LoggerInterface $logger,
    CurrentRouteMatch $currentRouteMatch,
    RouteProviderInterface $routeProvider,
    UrlGeneratorInterface $urlGenerator,
    CsrfTokenGenerator $csrfTokenGenerator,
    AccountInterface $currentUser,
    RequestStack $requestStack,
    ExtensionPathResolver $extensionPathResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $configFactory->get('puphpeteer.settings');
    $this->logger = $logger;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->routeProvider = $routeProvider;
    $this->urlGenerator = $urlGenerator;
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->currentUser = $currentUser;
    $this->request = $requestStack->getCurrentRequest();
    $this->extensionPathResolver = $extensionPathResolver;

    $options = [
      'logger' => $this->getSetting('debug') ? $logger : NULL,
      'log_browser_console' => $this->getSetting('log_to_browser_console'),
      'log_node_console' => $this->getSetting('log_to_node_console'),
      'executable_path' => $this->getSetting('executable_path'),
      'read_timeout' => $this->getSetting('read_timeout') ?: NULL,
      'idle_timeout' => $this->getSetting('idle_timeout') ?: NULL,
      'debug' => $this->getSetting('debug'),
      'leave_running' => $this->getSetting('leave_running'),
    ];

    if ($this->getSetting('debug')) {
      $options['env']['DEBUG'] = "puppeteer:*";
    }

    try {
      // @phpstan-ignore-next-line
      $this->generator = new Puppeteer($options);
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')->get('puphpeteer'),
      $container->get('current_route_match'),
      $container->get('router.route_provider'),
      $container->get('url_generator'),
      $container->get('csrf_token'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('extension.path.resolver'),
    );
  }

  /**
   * Update the generator configuration (API use).
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Get setting, either from overridden config or defaults.
   *
   * @param string $name
   *   The name of the configuration item to return.
   */
  public function getSetting($name) {
    return $this->configuration[$name] ??
      $this->settings->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->setPageOrientation($paper_orientation);
    $this->setHeader($header_content);
    $this->addPage($pdf_content);
  }

  /**
   * {@inheritdoc}
   */
  public function getObject() {
    return $this->generator;
  }

  /**
   * We don't use the pre-rendered HTML.
   */
  public function usePrintableDisplay() {
    return $this->getSetting('source') == 'printable';
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($text) {
    $this->header = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->html = $html;
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    $this->landscape = ($orientation == PdfGeneratorInterface::LANDSCAPE);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($pageSize) {
    if ($this->isValidPageSize($pageSize)) {
      $this->pageSize = $pageSize;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
    $this->footer = $text;
  }

  /**
   * Create the Chrome / Puppeteer instance.
   *
   * @throws \Exception
   *   If fails to start the browser.
   */
  public function startBrowser() {
    // Start the browser, as configured.
    $newHeadless = NULL;
    $this->isService = $this->getSetting('service');
    if ($this->isService) {
      $this->isHeadless = FALSE;
      $launchParams = [
        'browserURL' => $this->getSetting('service_url'),
      ];
    }
    else {
      $this->isHeadless = $this->getSetting('headless');
      $newHeadless = $this->getSetting('headless_new');
      $extraLaunchParams = $this->getSetting('chrome_extra_args') ?? '';
      $extraLaunchParams = str_replace("\r", "", trim($extraLaunchParams));
      $extraLaunchParams = empty($extraLaunchParams) ? [] :
        explode("\n", $extraLaunchParams);
      $launchParams = [
        'args' => array_merge([
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--start-maximized',
        ], $extraLaunchParams),
        'protocolTimeout' => 0,
        'headless' => $this->isHeadless,
        'ignoreHTTPSErrors' => TRUE,
        'defaultViewport' => NULL,
      ];

      if ($this->getSetting('devTools') ||
        $this->getSetting('triggerDebugging')) {
        $launchParams['args'][] = '--auto-open-devtools-for-tabs';
      }

      if ($this->getSetting('debug')) {
        $launchParams['args'][] = '--dumpio';
      }

      if (!empty(trim($this->getSetting('remote_debugging_address')))) {
        $launchParams['args'][] = '--remote-debugging-address=' .
          $this->getSetting('remote_debugging_address');
      }

      if (!empty(trim($this->getSetting('remote_debugging_port')))) {
        $launchParams['args'][] = '--remote-debugging-port=' .
          $this->getSetting('remote_debugging_port');
      }
    }

    if ($this->isHeadless) {
      $launchParams['headless'] = is_null($newHeadless) ? $this->isHeadless :
        ($newHeadless ? 'new' : 'old');
    }

    $launchParams['sloMo'] = $this->getSetting('slowMo');

    try {
      if (!$this->browser) {
        if ($this->isService) {
          $this->browser = $this->generator->connect($launchParams);
        }
        else {
          $this->browser = $this->generator->launch($launchParams);
        }
        $this->tab = NULL;
      }
    }
    catch (\Exception $exception) {
      $this->messenger()
        ->addError(in_array('administrator', $this->currentUser->getRoles()) ?
          $exception->getMessage() :
          'We failed to generate the PDF, sorry. Please try again later.');
      $this->logger
        ->alert($this->t("Puphpeteer failed to start the browser (:message).", [
          ':message' => $exception->getMessage(),
        ]));
      throw($exception);
    }
  }

  /**
   * Create and initialise a tab, if required.
   */
  public function initialiseTab() {
    if ($this->tab) {
      return;
    }

    // Get the default tab instead of opening a new one.
    // This avoids the need to bring the new tab to the front, which we don't
    // seem to be able to properly await.
    $tabs = $this->browser->pages();
    $this->tab = $tabs[0];

    if ($this->getSetting('triggerDebugging')) {
      // As of 5 October 2023, I'm seeing this JS run consistently (a
      // console.log always works) but a debugger call is hit and miss.
      // Use a small timeout to seek to make it more reliable.
      // https://bugs.chromium.org/p/chromium/issues/detail?id=1489548&q=debugger&can=1&sort=-opened
      $this->tab->evaluateOnNewDocument("debugger;");
    }

    $filename = $this->extensionPathResolver->getPath('module', 'puphpeteer') .
      '/js/waitFor.js';
    $js = file_get_contents($filename);

    if (empty($this->configuration['waitFor'])) {
      $wait = $this->getSetting('default_wait');

      switch ($wait) {
        case 'document_loaded':
          $waitFor = [
            'type' => 'event',
            'success' => ['load' => 'window'],
          ];
          break;

        case 'custom_event':
          $waitFor = [
            'type' => 'event',
            'name' => $this->getSetting('default_wait_custom_event'),
          ];
          break;

        case 'fixed_timeout':
          $waitFor = [
            'type' => 'timeout',
            'delay' => $this->getSetting('default_wait_fixed_timeout'),
          ];
          break;

        case 'custom_function':
          $waitFor = [
            'type' => 'function',
            'function' => $this->getSetting('default_wait_custom_function'),
          ];
          break;

        case 'xpath':
          $waitFor = [
            'type' => 'xpath',
            'query' => $this->getSetting('default_wait_xpath'),
          ];
          break;

        case 'readystate_interactive':
        case 'readystate_complete':
        default:

          $waitFor = [
            'type' => 'document_ready',
            'readyState' => $wait ? substr($wait, 11) : 'complete',
          ];
          break;
      }
    }
    else {
      $waitFor = $this->configuration['waitFor'];
    }

    $waitFor = json_encode($waitFor);

    // We can't invoke the setup function yet so we have to modify the script
    // we send.
    $js = str_replace('#WAITFOR_CONFIG#', $waitFor, $js);

    // Evaluating our setup JS on a new tab lets any event listeners receive
    // their event before we start waiting for it, removing the race condition.
    $this->tab->evaluateOnNewDocument($js);
  }

  /**
   * Close the browser.
   */
  public function closeBrowser() {
    if ($this->tab) {
      $this->tab->close();
      $this->tab = NULL;
    }
    if (!$this->isService && $this->browser) {
      $this->browser->close();
      $this->browser = NULL;
    }
  }

  /**
   * Get the browser instance.
   *
   * @return object
   *   The browser instance.
   */
  public function getBrowser() {
    return $this->browser;
  }

  /**
   * Visit a URL and configure Chrome for PDF generation.
   */
  public function setContent() {
    if (!$this->browser) {
      $this->startBrowser();
    }

    if (!$this->tab) {
      $this->initialiseTab();
    }

    // Give Chrome in Puppeteer the same access the current user has.
    $cookies = $this->request->cookies->all();

    // All an external user of the printable service to specify cookies to be
    // provided to a URL.
    if (!empty($this->configuration['cookies'])) {
      $cookies = array_merge($cookies, $this->configuration['cookies']);
    }

    $arg = [];
    foreach ($cookies as $name => $value) {
      $arg[] = [
        'name' => $name,
        'value' => $value,
        'domain' => $this->request->getHost(),
      ];
    }

    if (!empty($arg)) {
      $this->tab->setCookie(... $arg);
    }

    // Is Basic Auth needed?
    if ($this->request->headers->get('authorization')) {
      $this->tab->setExtraHTTPHeaders(['authorization' => $this->request->headers->get('authorization')]);
    }

    if (!empty($this->getSetting('basic_auth_username'))) {
      $this->tab->authenticate([
        'username' => $this->getSetting('basic_auth_username'),
        'password' => $this->getSetting('basic_auth_password'),
      ]);
    }

    $url = NULL;
    // Let an external user of the printable service to specify a URL they want
    // us to visit.
    if (!empty($this->configuration['url'])) {
      $url = $this->configuration['url'];
    }
    else {
      switch ($this->getSetting('source')) {
        case 'printable':
          break;

        case 'canonical':
          $route_name = 'entity.' . $this->entity->getEntityTypeId() . '.canonical';
          $route = $this->routeProvider->getRouteByName($route_name);
          $options = [];
          foreach ($route->getOptions()['parameters'] as $name => $details) {
            if ($name == $this->entity->getEntityTypeId()) {
              $options[$name] = $this->entity->id();
            }
            if ($name == 'webform_submission') {
              $options['webform'] = $this->entity->getWebform()->id();
            }
          }
          $url = $this->urlGenerator->generateFromRoute(
            $route_name, $options, ['absolute' => TRUE]);
          break;

        case 'print':
          $url = $this->urlGenerator->generateFromRoute(
            'printable.show_format.' . $this->entity->getEntityTypeId(), [
              'printable_format' => 'print',
              'entity' => $this->entity->id(),
            ], [
              'absolute' => TRUE,
            ]);
          break;
      }
    }

    if ($url) {
      // Note that if debugging is enabled, it will trigger here.
      // If the developer then closes the browser without
      // getting the event occur, we'll use the error path here.
      $result = $this->tab->goto($url, ['timeout' => 0]);
      if ($result->status() !== 200) {
        $message = (string) $this->t("Failed to generate PDF from :url. Page returned status :status and text :text", [
          ':url' => $url,
          ':status' => $result->status(),
          ':text' => $result->text(),
        ]);
        throw new \Exception($message);
      }
    }
    else {
      $this->tab->setContent($this->html, ['timeout' => 0]);
    }

    if ($this->getSetting('pagedjs')) {
      $this->tab->addScriptTag([
        'url' => 'https://unpkg.com/pagedjs/dist/paged.polyfill.js',
        'text' => 'text/javascript',
      ]);
    }

    $this->tab->emulateMediaType('print');

    if ($this->getSetting('pagedjs')) {
      $this->tab->waitForXPath('//template');
    }

    $result = $this->tab->evaluate(
      "waitForSomething()", $this->configuration['waitFor'] ?? [
        'timeout' => $this->getSetting('debug') ?
        0 : $this->getSetting('read_timeout'),
      ]
    );

    if (!$this->isService && !$this->isHeadless) {
      // Wait until browser is closed.
      try {
        while ($this->browser->isConnected()) {
          sleep(1);
        }
      }
      catch (\Exception $exception) {
        // Just deal with browser already being closed.
      }
      exit(0);
    }

    return $result;
  }

  /**
   * Retrieve a PDF from Chrome.
   */
  public function getPdfContent() {
    $options = [
      'printBackground' => !!($this->getSetting('printBackground') ?? FALSE),
      'preferCSSPageSize' => TRUE,
      'displayHeaderFooter' => TRUE,
    ];
    if ($this->landscape) {
      $options['landscape'] = TRUE;
    }
    if ($this->pageSize !== 'Letter') {
      $options['format'] = $this->pageSize;
    }
    if ($this->header) {
      $options['headerTemplate'] = $this->header ? (string) $this->header : '';
    }
    if ($this->footer) {
      $options['footerTemplate'] = $this->footer ? (string) $this->footer : '';
    }

    // To output from Chrome directly to the filesystem:
    // $options['path'] = $location;.
    $buffer = $this->tab->pdf($options);

    // Don't just cast to a string - that messes up the encoding.
    return base64_decode($buffer->toString('base64'));
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $result = $this->setContent();
    file_put_contents($location, $this->getPdfContent());

    $leaveRunning = $this->configuration['leave_running'] ??
      $this->getSetting('leave_running');

    if (!$leaveRunning) {
      $this->closeBrowser();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->generator->stream("pdf", ['Attachment' => 0]);
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->generator->Output($filelocation, "F");
  }

}
