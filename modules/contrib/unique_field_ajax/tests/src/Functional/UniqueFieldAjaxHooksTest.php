<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

/**
 * Test the custom hooks.
 *
 * @package Drupal\Tests\unique_field_ajax
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxHooksTest extends UniqueFieldAjaxBase {

  /**
   * Test unique field ajax query hook - when matching key.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUniqueFieldAjaxHookQueryAlterMatchingKey() {
    foreach ($this->fieldTypes as $field_type) {
      // Create field with custom key name.
      $field_name = $this->createRandomData() . "_hook_query_alter_123";
      $this->createField($field_type['type'], $field_type['widget'], $field_type['settings'], $field_name);

      // Field unique enabled, create 2 nodes.
      // These should save as the hook module has not been enabled yet.
      $this->updateThirdPartyFieldSetting('unique', TRUE);
      $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
      $this->itCanSaveField($edit);
      $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
      $this->itCanSaveField($edit);

      // Install the custom hooks module.
      \Drupal::getContainer()->get('module_installer')->install([
        'unique_field_ajax_test_hooks',
      ]);

      // All updates should fail now.
      $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
      $this->itCannotSaveField($edit);
      $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
      $this->itCannotSaveField($edit);

      // Uninstall the custom hooks module.
      \Drupal::getContainer()->get('module_installer')->uninstall([
        'unique_field_ajax_test_hooks',
      ]);
    }
  }

  /**
   * Test unique field ajax query hook - when not matching key.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUniqueFieldAjaxHookQueryAlterNoMatchingKey() {
    foreach ($this->fieldTypes as $field_type) {
      if ($field_type['type'] != 'link') {
        // Create field.
        $this->createField($field_type['type'], $field_type['widget'], $field_type['settings']);
        $field_name = $this->fieldStorage->getName();

        // Field unique enabled, create 2 nodes.
        // These should save as the hook module has not been enabled yet.
        $this->updateThirdPartyFieldSetting('unique', TRUE);
        $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
        $this->itCanSaveField($edit);
        $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
        $this->itCanSaveField($edit);

        // Install the custom hooks module.
        \Drupal::getContainer()->get('module_installer')->install([
          'unique_field_ajax_test_hooks',
        ]);

        // All updates should still work as they don't match key.
        $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
        $this->itCanSaveField($edit);
        $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $field_type['effect']);
        $this->itCanSaveField($edit);

        // Uninstall the custom hooks module.
        \Drupal::getContainer()->get('module_installer')->uninstall([
          'unique_field_ajax_test_hooks',
        ]);
      }
    }
  }

  /**
   * Test unique field ajax results alter.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUniqueFieldAjaxHookUniqueResultsAlter() {
    $field_type = $this->fieldTypes['string'];

    $this->createField($field_type['type'], $field_type['widget'], $field_type['settings']);
    $field_name = $this->fieldStorage->getName();
    $effect = $field_type['effect'];

    $this->updateThirdPartyFieldSetting('unique', TRUE);
    $edit = $this->createUpdateFieldData($field_name, $field_type['value'], $effect);

    // Install the custom hooks module.
    \Drupal::getContainer()->get('module_installer')->install([
      'unique_field_ajax_test_hooks',
    ]);

    // "Tree" should be equal to "tree".
    $edit["{$field_name}[0][{$effect}]"] = "http://this-is-a-website.com";
    $this->itCanSaveField($edit);
    $edit["{$field_name}[0][{$effect}]"] = "http://www.myexamplewebsite.com";
    $this->itCannotSaveField($edit);

    // Uninstall the custom hooks module.
    \Drupal::getContainer()->get('module_installer')->uninstall([
      'unique_field_ajax_test_hooks',
    ]);
  }

}
