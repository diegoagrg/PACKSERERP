<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the printable module functionality.
 */
#[Group('Printable')]
class PrintableLinkTest extends NodeTestBase {

  use StringTranslationTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['printable', 'node', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'view printer friendly versions',
      'administer printable',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the links are rendered correctly in the page.
   */
  public function testPrintLinkExists() {
    $this->drupalGet('admin/config/user-interface/printable/links');
    $this->assertSession()->statusCodeEquals(200);
    // Enable the print link in content area.
    $this->submitForm([
      'print_print_link_pos[node]' => TRUE,
    ], (string) $this->t('Submit'));
    $this->assertSession()->statusCodeEquals(200);

    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
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

    $this->assertSession()->responseContains('Print');
  }

}
