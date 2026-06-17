<?php

namespace Drupal\pdf_api\Plugin\PdfGenerator;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use mikehaertl\wkhtmlto\Pdf;

/**
 * A PDF generator plugin for the WKHTMLTOPDF library.
 *
 * @PdfGenerator(
 *   id = "wkhtmltopdf",
 *   module = "pdf_api",
 *   title = @Translation("wkhtmltoPDF"),
 *   description = @Translation("PDF generator using the WKHTMLTOPDF binary."),
 *   required_class = "mikehaertl\wkhtmlto\Pdf"
 * )
 */
class WkhtmltopdfGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the WKHtmlToPdf class library.
   *
   * @var \WkHtmlToPdf
   */
  protected $generator;

  /**
   * Instance of the messenger class.
   *
   * @var \Drupal\Core\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->generator = new Pdf();
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger')
    );
  }

  /**
   * Set the path of binary file.
   *
   * @param string $path_to_binary
   *   Path to binary file.
   */
  public function configBinary($path_to_binary) {
    $this->setOptions(['binary' => $path_to_binary]);
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->configBinary($path_to_binary);
    $this->addPage($pdf_content);
    $this->setPageSize($paper_size);
    $this->setPageOrientation($paper_orientation);
    $this->setHeader($header_content);
    $this->setFooter($footer_content);
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
    $this->setOptions(['header-right' => $text]);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    $this->setOptions(['orientation' => $orientation]);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->setOptions(['page-size' => $page_size]);
    }
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
  public function setFooter($text) {
    $this->setOptions(['footer-center' => $text]);
  }

  /**
   * {@inheritdoc}
   */
  public function save($location) {
    $this->preGenerate();
    $this->generator->saveAs($location);
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->preGenerate();
    $this->generator->send($this->generator->getPdfFilename(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->preGenerate();
    $this->generator->saveAs($filelocation);
    $this->generator->send($filelocation, FALSE);
  }

  /**
   * Set the global options from plugin into the WKHTMLTOPDF generator class.
   */
  protected function preGenerate() {
    $this->generator->setOptions($this->options);
  }

  /**
   * Get errors from the generator.
   *
   * @return string
   *   The content of the stderr pipe.
   */
  public function getStderr() {
    return $this->generator->getError();
  }

  /**
   * Get stdout output from the generator.
   *
   * @return string
   *   The content of the stdout pipe.
   */
  public function getStdout() {
    return $this->generator->getCommand()->getOutput();
  }

}
