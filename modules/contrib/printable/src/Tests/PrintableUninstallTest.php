<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the whether printable module uninstall successfully.
 */
#[Group('Printable')]
class PrintableUninstallTest extends NodeTestBase {

  use StringTranslationTrait;
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['printable', 'node_test_exception', 'dblog'];

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
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that the node/{node}/printable/print path returns the right content.
   */
  public function testCustomPageExists() {
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('node/add/page');
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
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

    $this->drupalGet('node/' . $node->id() . '/printable/print');
    $this->assertSession()->statusCodeEquals(200);
    // Uninstall the printable module and check the printable version of node
    // is also deleted.
    \Drupal::service('module_installer')->uninstall(['printable']);
    $this->drupalGet('node/' . $node->id() . '/printable/print');
    $this->assertSession()->statusCodeEquals(404);
  }

}
