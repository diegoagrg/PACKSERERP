<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable module functionality.
 */
#[Group('Printable')]
class PrintablePdfFormTest extends BrowserTestBase {

  use StringTranslationTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'printable', 'printable_pdf'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A simple user with 'administer printable' permission.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer printable']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the PDF form.
   */
  public function testPdfFormWorks() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->assertSession()->statusCodeEquals(200);

    $config = $this->config('printable.settings');
    // @todo Think about making a mock generator. For the moment, the tool
    // control may not show - there might not be any plugins available.
    // $this->assertSession()->fieldValueEquals('print_pdf_pdf_tool',
    // $config->get('printable.pdf_tool'));
    $this->assertNull($config->get('printable.save_pdf'));
    $this->assertNull($config->get('printable.paper_size'));
    $this->assertNull($config->get('printable.page_orientation'));
    $this->assertNull($config->get('printable.pdf_location'));

    $this->submitForm([
      // 'print_pdf_pdf_tool' => 'wkhtmltopdf',
      'print_pdf_content_disposition' => 1,
      'print_pdf_paper_size' => 'A9',
      'print_pdf_page_orientation' => 'landscape',
      'print_pdf_filename' => 'test_pdf',
    ], (string) $this->t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->assertSession()->statusCodeEquals(200);
    // $this->assertSession()->fieldValueEquals('print_pdf_pdf_tool',
    // 'wkhtmltopdf');
    $this->assertSession()->fieldValueEquals('print_pdf_content_disposition', 1);
    $this->assertSession()->fieldValueEquals('print_pdf_paper_size', 'A9');
    $this->assertSession()->fieldValueEquals('print_pdf_page_orientation', 'landscape');
    $this->assertSession()->fieldValueEquals('print_pdf_filename', 'test_pdf');
  }

}
