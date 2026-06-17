<?php

/**
 * @file
 * Describes hooks provided by the Unique Field Ajax module.
 */

use Drupal\Core\Database\Query\AlterableInterface;

/**
 * Modify the query for checking uniqueness.
 *
 * This hook may be used to modify the query to check if a value is unique
 * or not.
 *
 * The query contains the following metadata:
 *   - entity_type
 *   - lang_code
 *   - field_name
 *   - field_value
 *   - bundle
 *   - is_unique_per_lang
 *   - entity
 *
 * @param \Drupal\Core\Database\Query\AlterableInterface $query
 *   The (alterable) query object.
 */
function hook_query_unique_field_ajax_alter(AlterableInterface $query) {
  // Alter query...
}

/**
 * Modify the unique results.
 *
 * This hook can be used to modify the unique results, this could be to
 * remove something or add something in.
 *
 * @param array $result
 *   The results' data to be adjusted or manipulated.
 * @param array $metadata
 *   Metadata associated with the results.
 */
function hook_unique_field_ajax_unique_results_alter(array &$result, array $metadata) {
  // Alter results..
  // e.g. $results[0] = 0; If you want it to always fail uniqueness.
}
