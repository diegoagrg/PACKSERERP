<?php

namespace Drupal\printable\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\block\Entity\Block;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the blocks present in printable module.
 */
#[Group('Printable')]
class PrintableBlockTest extends NodeTestBase {

  use StringTranslationTrait;

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A web user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['printable', 'block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'administer blocks',
      'access content overview',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create article content',
    ]);
  }

  /**
   * Tests the functionality of the Printable block.
   */
  public function testPrintableBlock() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'settings[label]' => $this->randomMachineName(8),
      'region' => 'sidebar_first',
      'visibility[entity_bundle:node][bundles][article]' => 'article',
    ];
    $theme = \Drupal::service('theme_handler')->getDefault();
    $this->drupalGet("admin/structure/block/add/printable_links_block%3Anode/$theme");
    $this->submitForm($edit, (string) $this->t('Save block'));

    $block = Block::load($edit['id']);
    $visibility = $block->getVisibility();
    $this->assertTrue(isset($visibility['entity_bundle:node']['bundles']['article']), 'Visibility settings were saved to configuration');

    // Test deleting the block from the edit form.
    $this->drupalGet('admin/structure/block/manage/' . $edit['id']);
    $this->clickLink($this->t('Remove block'));
    $this->assertSession()->responseContains($this->t('Are you sure you want to remove the block :name from the :region region?', [
      ':name' => $edit['settings[label]'],
      ':region' => 'Left sidebar',
    ]));
    $this->submitForm([], (string) $this->t('Remove'));
    $this->assertSession()->responseContains($this->t('The block %name has been removed from the %region region.', [
      '%name' => $edit['settings[label]'],
      '%region' => 'Left sidebar',
    ]));
  }

}
