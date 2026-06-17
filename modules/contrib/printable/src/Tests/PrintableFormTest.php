<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable module functionality.
 */
#[Group('Printable')]
class PrintableFormTest extends BrowserTestBase {

  use StringTranslationTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['printable', 'node'];

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
   * Tests the Print form.
   */
  public function testPrintFormWorks() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/user-interface/printable/print');
    $this->assertSession()->statusCodeEquals(200);

    $config = $this->config('printable.settings');
    $this->assertSession()->fieldValueEquals('print_html_sendtoprinter', $config->get('send_to_printer'));

    $this->submitForm([
      'print_html_sendtoprinter' => 1,
    ], (string) $this->t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/print');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('print_html_sendtoprinter', 1);
  }

}
