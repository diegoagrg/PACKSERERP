<?php

namespace Drupal\pdf_api\Plugin\PdfGenerator;

use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use Mpdf\Mpdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A PDF generator plugin for the mPDF library.
 *
 * @PdfGenerator(
 *   id = "mpdf",
 *   module = "pdf_api",
 *   title = @Translation("mPDF"),
 *   description = @Translation("PDF generator using the mPDF generator."),
 *   required_class = "Mpdf\Mpdf"
 * )
 */
class MpdfGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the mPdf class library.
   *
   * @var \mPdf
   */
  protected $generator;

  /**
   * The saved header content.
   *
   * @var string
   */
  protected $headerContent;

  /**
   * The saved PDF content.
   *
   * @var string
   */
  protected $pdfContent;

  /**
   * The saved footer content.
   *
   * @var string
   */
  protected $footerContent;

  /**
   * The temporary directory we created.
   *
   * @var string
   */
  protected $tmpDir;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an MpdfGenerator object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   A file system interface instance instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystem = $file_system;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('logger.channel.pdf_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->setPageSize($paper_size);
    $this->setPageOrientation($paper_orientation);

    // Save until the generator is constructed in the preGenerate method.
    $this->headerContent = $header_content;
    $this->pdfContent = $pdf_content;
    $this->footerContent = $footer_content;
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
    $this->generator->SetHeader($text);
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->generator->addPage($html);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    if ($orientation == PdfGeneratorInterface::PORTRAIT) {
      $orientation = 'P';
    }
    else {
      $orientation = 'L';
    }
    $this->setOptions(['orientation' => $orientation]);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->setOptions(['sheet-size' => $page_size]);
    }
  }

  /**
   * Sets the password in PDF.
   *
   * @param string $password
   *   The password which will be used in PDF.
   */
  public function setPassword($password) {
    if (isset($password) && $password != NULL) {
      // Print and Copy is allowed.
      $this->generator->SetProtection(['print', 'copy'], $password, $password);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
    $this->generator->SetFooter($text);
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $this->preGenerate();
    $this->generator->Output($location, 'F');
    $this->postGenerate();
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->generator->Output("", "I");
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->generator->Output($filelocation, 'F');
  }

  /**
   * Set the global options from the plugin into the mPDF generator class.
   */
  protected function preGenerate() {
    /*
     * We have to pass the initial page size and orientation that we want to
     * the constructor, so we delay making the generator until we have those
     * details.
     *
     * mPDF is also strange in its handling of parameters. We can't just set
     * the page size and orientation separately (as you'd expect) but need to
     * combine them in the format argument for them to be effective from the
     * get-go.
     */
    $options = $this->options;

    $orientation = $options['orientation'] ? $options['orientation'] : 'P';

    $random = new Random();
    $this->tmpDir = $this->fileSystem->getTempDirectory() . '/' . $random->name(16, TRUE);
    $this->fileSystem->mkdir($this->tmpDir);

    $config = [
      'format' => $this->isValidPageSize($options['sheet-size']) ? $options['sheet-size'] : 'A4',
      'tempDir' => $this->tmpDir,
    ];

    if ($orientation == 'L') {
      $config['format'] .= '-' . $orientation;
    }

    $this->generator = new mPDF($config);
    $this->generator->setLogger($this->logger);

    // Apply any other options.
    unset($options['orientation']);
    unset($options['sheet-size']);

    $this->generator->AddPageByArray($options);

    $this->setHeader($this->headerContent);
    $this->setFooter($this->footerContent);
    $stylesheet = '.node_view  { display: none; }';
    $this->generator->allow_charset_conversion = FALSE;
    $this->generator->WriteHTML($stylesheet, 1);
    $this->generator->WriteHTML($this->pdfContent, 0);
  }

  /**
   * Post generation cleanup.
   */
  protected function postGenerate() {
    $this->fileSystem->deleteRecursive($this->tmpDir);
  }

}
