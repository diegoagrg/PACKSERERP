<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\Group;
use Smalot\PdfParser\Parser;

/**
 * Tests the printable_pdf module functionality.
 */
#[Group('Printable')]
class PrintablePdfTest extends NodeTestBase {

  use StringTranslationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'printable',
    'printable_pdf',
    'pdf_api',
    'node_test_exception',
    'dblog',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'view printer friendly versions',
      'administer printable',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that the 'node/{node}/printable/pdf' path returns the right content.
   */
  public function testCustomPageExists() {
    global $base_url;
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('node/add/page');
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $bodytext = $this->randomMachineName(16) . 'This is functional test which I am writing for the printable module.';
    $edit['body[0][value]'] = $bodytext;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, (string) $this->t('Save'));

    // Check that the Basic page has been created.
    $this->assertSession()->pageTextContains($this->t('@post @title has been created.', [
      '@post' => 'Basic page',
      '@title' => $edit['title[0][value]'],
    ]));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertInstanceOf(NodeInterface::class, $node, 'Node found in database.');

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);

    // Set the PDF generating tool.
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $moduleDir = \Drupal::moduleHandler()->getModule('printable')
      ->getPath();
    $this->submitForm([
      'print_pdf_pdf_tool' => 'mpdf',
      'print_pdf_content_disposition' => 1,
      'print_pdf_filename' => $moduleDir . '/src/Tests/testPDF.pdf',
    ], (string) $this->t('Submit'));
    $this->drupalGet('admin/config/user-interface/printable/pdf');
    $this->assertSession()->statusCodeEquals(200);

    // Test whether PDF page is being generated. The path is irrelevant -
    // the file is sent and deleted after sending.
    $file = $this->drupalGet('node/' . $node->id() . '/printable/pdf');
    $parser = new Parser();
    $pdf = $parser->parseContent($file);

    $text = $pdf->getText();

    $this->drupalGet('node/add');

    $new_edit = [];
    $new_edit['title[0][value]'] = $this->randomMachineName(8);
    $bodytext = $text;
    $new_edit['body[0][value]'] = $bodytext;
    $this->drupalGet('node/add/page');
    $this->submitForm($new_edit, (string) $this->t('Save'));
    $new_node = $this->drupalGetNodeByTitle($new_edit['title[0][value]']);
    $this->drupalGet('node/' . $new_node->id());
    $this->assertSession()->statusCodeEquals(200);

    // Checks the presence of body in the page.
    $this->assertSession()->responseContains($edit['body[0][value]']);

    // Check if footer is rendering correctly.
    $this->assertSession()->responseContains($base_url . '/node/' . $node->id());
  }

}
