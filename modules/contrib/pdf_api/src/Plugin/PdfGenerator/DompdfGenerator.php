<?php

namespace Drupal\pdf_api\Plugin\PdfGenerator;

use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Disable DOMPDF's internal autoloader if you are using Composer.
if (!defined('DOMPDF_ENABLE_AUTOLOAD')) {
  define('DOMPDF_ENABLE_AUTOLOAD', FALSE);
}

/**
 * A PDF generator plugin for the dompdf library.
 *
 * @PdfGenerator(
 *   id = "dompdf",
 *   module = "pdf_api",
 *   title = @Translation("dompdf"),
 *   description = @Translation("PDF generator using the DOMPDF generator."),
 *   required_class = "Dompdf\Dompdf",
 * )
 */
class DompdfGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the DOMPDF class library.
   *
   * @var \DOMPDF
   */
  protected $generator;

  /**
   * Instance of the logger class.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a \Drupal\Plugin\PdfGenerator\DompdfGenerator object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The PDF API settings.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, FileSystemInterface $file_system, ImmutableConfig $settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->generator = new Dompdf();

    $default_font = $settings->get('defaultFont') ?? 'serif';
    $dpi = $settings->get('dpi') ?? 96;
    $font_height_ratio = $settings->get('fontHeightRatio') ?? 1.1;
    $pdf_backend = $settings->get('pdfBackend') ?? 'CPDF';
    $pdflib_license = $settings->get('pdflibLicense');
    $php_enabled = $settings->get('isPhpEnabled');
    $remote_enabled = $settings->get('isRemoteEnabled');
    $javascript_enabled = $settings->get('isJavascriptEnabled');
    $chroot = $settings->get('chroot') ?? ['.'];
    $chroot_path = [];
    foreach ($chroot as $path) {
      $chroot_path[] = $file_system->realpath(DRUPAL_ROOT . '/' . $path);
    }

    $font_dir = $settings->get('fontDir') ?? 'temporary://';
    $font_dir = $file_system->realpath($font_dir);

    $font_cache = $settings->get('fontCache') ?? 'temporary://';
    $font_cache = $file_system->realpath($font_cache);

    $temp_dir = $settings->get('tempDir') ?? 'temporary://';
    $temp_dir = $file_system->realpath($temp_dir);

    $debug_png = $settings->get('debugPng');
    $debug_keep_temp = $settings->get('debugKeepTemp');
    $debug_css = $settings->get('debugCss');
    $debug_layout = $settings->get('debugLayout');
    $debug_layout_lines = $settings->get('debugLayoutLines');
    $debug_layout_blocks = $settings->get('debugLayoutBlocks');
    $debug_layout_inline = $settings->get('debugLayoutInline');
    $debug_layout_padding_box = $settings->get('debugLayoutPaddingBox');
    $options_array = [
      'defaultFont' => $default_font,
      'dpi' => $dpi,
      'fontHeightRatio' => $font_height_ratio,
      'pdfBackend' => $pdf_backend,
      'isPhpEnabled' => $php_enabled,
      'isRemoteEnabled' => $remote_enabled,
      'isJavascriptEnabled' => $javascript_enabled,
      'chroot' => $chroot_path,
      'fontDir' => $font_dir,
      'fontCache' => $font_cache,
      'tempDir' => $temp_dir,
      'debugPng' => $debug_png,
      'debugKeepTemp' => $debug_keep_temp,
      'debugCss' => $debug_css,
      'debugLayout' => $debug_layout,
      'debugLayoutLines' => $debug_layout_lines,
      'debugLayoutBlocks' => $debug_layout_blocks,
      'debugLayoutInline' => $debug_layout_inline,
      'debugLayoutPaddingBox' => $debug_layout_padding_box,
    ];
    $options_array = array_filter($options_array, function ($value, $key) {
      return !is_null($value);
    }, ARRAY_FILTER_USE_BOTH);
    $options = new Options($options_array);

    if ($pdflib_license) {
      $options['pdflibLicense'] = $pdflib_license;
    }

    $this->generator->setOptions($options);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.pdf_api'),
      $container->get('file_system'),
      $container->get('config.factory')->get('pdf_api.dom_pdf.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->setPageOrientation($paper_orientation);
    $this->addPage($pdf_content);
    $this->setHeader($header_content);
  }

  /**
   * {@inheritdoc}
   */
  public function getObject() {
    return $this->generator;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($text) {
    if (!$text) {
      return;
    }

    $canvas = $this->generator->get_canvas();
    $canvas->page_text(72, 18, $text, "", 11, [0, 0, 0]);
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->generator->loadHtml($html);
    $this->generator->render();
    if (is_array($GLOBALS['_dompdf_warnings'])) {
      foreach ($GLOBALS['_dompdf_warnings'] as $warning) {
        $this->logger->warning($warning);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    $this->generator->setPaper("", $orientation);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->generator->setPaper($page_size);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
    // @todo see issue over here: https://github.com/dompdf/dompdf/issues/571
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $content = $this->generator->output([]);
    file_put_contents($location, $content);
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
