<?php

/**
 * @file
 * Handles the management of collection objects and their children.
 */

require_once dirname(__FILE__) . '/utilities.inc';
require_once dirname(__FILE__) . '/batch.inc';

// Hook definition.
define('ISLANDORA_BASIC_COLLECTION_BUILD_MANAGE_OBJECT_HOOK', 'islandora_basic_collection_build_manage_object');

/**
 * Implements islandora_basic_collection_build_manage_object_hook().
 *
 * @param array $render_array
 *   The render array for the tab.
 * @param AbstractObject $object
 *   The Islandora object being processed.
 *
 * @return array
 *   The completed render array.
 */
function islandora_basic_collection_islandora_basic_collection_build_manage_object(array $render_array, AbstractObject $object) {
  // Defines accessible elements.
  $access = [
    'policy_management' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MANAGE_COLLECTION_POLICY, $object),
/*
    'share_children' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS, $object),
    'migrate_children' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS, $object),
    'delete_children' => islandora_object_access(ISLANDORA_PURGE, $object),
  */
  ];
  $forms = [
    'policy_management' => 'Drupal\islandora_basic_collection\Form\IslandoraBasicCollectionPolicyManagementForm',
    'share_children' => 'Drupal\islandora_basic_collection\Form\IslandoraBasicCollectionShareChildrenForm',
    'migrate_children' => 'Drupal\islandora_basic_collection\Form\IslandoraBasicCollectionMigrateChildrenForm',
    'delete_children' => 'Drupal\islandora_basic_collection\Form\IslandoraBasicCollectionDeleteChildrenForm',
  ];
  // Build forms for only the accessible elements.
  foreach ($access as $element => $accessible) {
    $form = $forms[$element];
    $forms[$element] = $accessible ? \Drupal::formBuilder()->getForm($form, $object) : NULL;
  }
  $render_array['policy'] = [
    '#id' => 'policy-management',
    '#group' => 'manage_collection_object',
    '#access' => $access['policy_management'],
    '#type' => 'details',
    '#title' => t('Manage collection policy'),
    'form' => $forms['policy_management'],
  ];
/*
  $render_array['share_children'] = [
    '#id' => 'share-children',
    '#access' => $access['share_children'],
    '#type' => 'details',
    '#title' => t('Share members'),
    'form' => $forms['share_children'],
  ];
  $render_array['migrate_children'] = [
    '#id' => 'migrate-children',
    '#access' => $access['migrate_children'],
    '#type' => 'details',
    '#title' => t('Migrate members'),
    'form' => $forms['migrate_children'],
  ];
  $render_array['delete_children'] = [
    '#id' => 'delete-children',
    '#group' => 'manage_collection_object',
    '#access' => $access['delete_children'],
    '#type' => 'details',
    '#title' => t('Delete members of this collection'),
    'form' => $forms['delete_children'],
  ];
 */
  return $render_array;
}

/**
 * Common form validation for share/migrate members.
 *
 * Ensures you don't share/migrate a collection into itself.
 *
 * @param array $form_state
 *   The Drupal form state.
 *
 * @return bool
 *   Whether the form is valid or not.
 */
function islandora_basic_collection_validate_form(array $form_state) {
  $new_collection = $form_state['values']['collection'];
  $clicked_button = end($form_state['clicked_button']['#parents']);

  if ($clicked_button == 'submit_all') {
    $source_collection = $form_state['collection'];
    $query = <<<EOT
PREFIX fedora-rels-ext: <info:fedora/fedora-system:def/relations-external#>
SELECT ?object WHERE {
?object fedora-rels-ext:isMemberOfCollection <info:fedora/{$source_collection}> .
FILTER (sameTerm(?object, <info:fedora/{$new_collection}>))
}
EOT;
    $connection = islandora_get_tuque_connection();
    $num_results = $connection->repository->ri->countQuery($query, 'sparql');
    return ($num_results <= 0);
  }
  else {
    $children = array_keys(array_filter($form_state['values']['children']));
    if (array_intersect($children, array($new_collection))) {
      return FALSE;
    }
  }
  return TRUE;
}
