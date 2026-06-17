<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable module functionality.
 */
#[Group('Printable')]

class PrintablePageTest extends NodeTestBase {

  use StringTranslationTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'printable',
    'node',
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
   * Tests that the node/{node}/printable/print path returns the right content.
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
    $bodytext = $this->randomMachineName(16) . 'This is functional test which I am writing for printable module.';
    $edit['body[0][value]'] = $bodytext;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, (string) $this->t('Save'));

    // Check that the Basic page has been created.
    $this->assertSession()->linkByHrefExists('/node/1', 0, $edit['title[0][value]']);

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull(($node === FALSE ? NULL : $node), 'Node found in database.');

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/' . $node->id() . '/printable/print');
    $this->assertSession()->statusCodeEquals(200);
    // Checks the presence of header in the page.
    $this->assertSession()->responseContains($edit['title[0][value]']);
    // Checks the presence of image in the header.
    $this->assertSession()->responseContains(theme_get_setting('logo.url'));
    // Checks the presence of body in the page.
    $this->assertSession()->responseContains($edit['body[0][value]']);
    // Check if footer is rendering correctly.
    $this->assertSession()->responseContains($base_url . '/node/' . $node->id());
    // Enable the option of showing links present in the footer of page.
    $this->drupalGet('admin/config/user-interface/printable/print');
    $this->submitForm([
      'print_html_display_sys_urllist' => 1,
    ], (string) $this->t('Submit'));

    // Check that the printable URL can be retrieved without error.
    $this->drupalGet('node/' . $node->id() . '/printable/print');
    $this->assertSession()->statusCodeEquals(200);

    // Checks whether the URLs in the footer region are rendering properly.
    // The printable links should not be there at this point.
    $this->assertSession()->responseContains('List of links present in page');
    $this->assertSession()->responseContains($base_url . '/node/' . $node->id());
    $this->assertSession()->responseNotContains('/node/' . $node->id() . '/printable/print');

    // Printable links default to being hidden.
    $this->drupalGet('admin/config/user-interface/printable/links');
    $this->submitForm([
      'exclude_printable_links' => 0,
    ], (string) $this->t('Submit'));

    // Check that the printable URL can be retrieved without error.
    $this->drupalGet('node/' . $node->id() . '/printable/print');
    $this->assertSession()->statusCodeEquals(200);

    // Checks whether the URLs in the footer region are rendering properly.
    $this->assertSession()->responseContains('List of links present in page');
    $this->assertSession()->responseContains($base_url . '/node/' . $node->id());
    $this->assertSession()->responseContains('/node/' . $node->id() . '/printable/print');

    // Check that invalid plugin URLs throw a 404.
    $this->drupalGet('node/' . $node->id() . '/printable/UNDEFINED');
    $this->assertSession()->statusCodeEquals(404);
  }

}
