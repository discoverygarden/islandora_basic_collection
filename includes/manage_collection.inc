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
 * Manage Collection local task.
 *
 * Defines the actions to appear in the collection section of the Manage tab.
 *
 * @param AbstractObject $object
 *   The collection object.
 *
 * @return array
 *   A renderable array.
 */
function islandora_basic_collection_manage_object(AbstractObject $object) {
  $return_form = array(
    'manage_collection_object' => array(),
  );
  $data = islandora_invoke_hook_list(ISLANDORA_BASIC_COLLECTION_BUILD_MANAGE_OBJECT_HOOK, $object->models, array(
    $return_form,
    $object)
  );
  $data['manage_collection_object']['#type'] = 'vertical_tabs';
  return $data;
}

/**
 * Implements islandora_basic_collection_build_manage_object_hook().
 *
 * @param array $form_state
 *   The Form state of the form.
 * @param AbstractObject $object
 *   The Islandora object being processed.
 *
 * @return array
 *   Returning the built form.
 */
function islandora_basic_collection_islandora_basic_collection_build_manage_object(array $form_state, AbstractObject $object) {
  // Defines accessible elements.
  $access = array(
    'policy_management' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MANAGE_COLLECTION_POLICY, $object),
    'share_children' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS, $object),
    'migrate_children' => islandora_object_access(ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS, $object),
    'delete_children' => islandora_object_access(ISLANDORA_PURGE, $object),
  );
  // Build forms for only the accessible elements.
  foreach ($access as $element => $accessible) {
    $form = "islandora_basic_collection_{$element}_form";
    $forms[$element] = $accessible ? \Drupal::formBuilder()->getForm($form, $object) : NULL;
  }
  $form_state['manage_collection_object']['policy_management'] = array(
    '#id' => 'policy-management',
    '#group' => 'manage_collection_object',
    '#access' => $access['policy_management'],
    '#type' => 'fieldset',
    '#title' => t('Manage collection policy'),
    'form' => $forms['policy_management'],
  );
  $form_state['manage_collection_object']['share_children'] = array(
    '#id' => 'share-children',
    '#group' => 'manage_collection_object',
    '#access' => $access['share_children'],
    '#type' => 'fieldset',
    '#title' => t('Share members'),
    'form' => $forms['share_children'],
  );
  $form_state['manage_collection_object']['migrate_children'] = array(
    '#id' => 'migrate-children',
    '#group' => 'manage_collection_object',
    '#access' => $access['migrate_children'],
    '#type' => 'fieldset',
    '#title' => t('Migrate members'),
    'form' => $forms['migrate_children'],
  );
  $form_state['manage_collection_object']['delete_children'] = array(
    '#id' => 'delete-children',
    '#group' => 'manage_collection_object',
    '#access' => $access['delete_children'],
    '#type' => 'fieldset',
    '#title' => t('Delete members of this collection'),
    'form' => $forms['delete_children'],
  );
  return $form_state;
}

/**
 * Define collection policy management form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 * @param AbstractObject $object
 *   The collection to manipulate.
 *
 * @return array
 *   The Drupal form definition.
 */
function islandora_basic_collection_policy_management_form(array $form, array &$form_state, AbstractObject $object) {
  module_load_include('inc', 'islandora', 'includes/utilities');
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_css(drupal_get_path('module', 'islandora_basic_collection') . "/css/collection_policy_table.css");

  $form_state['collection'] = $object;
  if (isset($object['COLLECTION_POLICY'])) {
    $policy = new CollectionPolicy($object['COLLECTION_POLICY']->content);
  }
  else {
    $policy = CollectionPolicy::emptyPolicy();
  }
  $policy_content_models = $policy->getContentModels();
  $content_models = islandora_get_content_models();
  $default_namespace = \Drupal\Component\Utility\Unicode::substr($object->id, 0, strpos($object->id, ":"));
  $rows = array();
  foreach ($content_models as $pid => $content_model) {
    $label = $content_model['label'];
    $content_model_in_policy = isset($policy_content_models[$pid]);
    $namespace = $content_model_in_policy ? $policy_content_models[$pid]['namespace'] : $default_namespace;
    $namespace_element = islandora_basic_collection_get_namespace_form_element($namespace);
    $prompt_element = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => isset($policy_content_models[$pid]) ? $policy_content_models[$pid]['name'] : $content_model['label'],
    );
    unset($namespace_element['#title'], $namespace_element['#description']);
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $rows[$pid] = array(
//       'selected' => array(
//         '#type' => 'checkbox',
//         '#default_value' => $content_model_in_policy,
//       ),
//       'title' => array(
//         '#markup' => l(t('@label (@pid)', array('@label' => $label, '@pid' => $pid)), "islandora/object/{$pid}"),
//       ),
//       'prompt' => $prompt_element,
//       'namespace' => $namespace_element,
//     );

  }
  return array(
    '#action' => request_uri() . '#policy-management',
    'help' => array(
      '#type' => 'item',
      '#markup' => \Drupal::l(t('About Collection Policies'), \Drupal\Core\Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA715/How+to+Manage+Collection+Policies')),
    ),
    'table' => array(
      '#tree' => TRUE,
      '#header' => array(
        'class' => array('select-all'),
        'pid' => array('data' => t('PID'), 'class' => "collection_policy_pid"),
        'prompt' => array('data' => t('Prompt'), 'class' => "collection_policy_prompt"),
        'namespace' => array('data' => t('Namespace'), 'class' => "collection_policy_namespace"),
      ),
      '#theme' => 'islandora_basic_collection_policy_management_table',
      'rows' => $rows,
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Update collection policy'),
    ),
  );
}

/**
 * Submit handler for collection policy management form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_policy_management_form_submit(array $form, array &$form_state) {
  $collection = $form_state['collection'];
  $filter_selected = function($o) {
    return $o['selected'];
  };
  $selected = array_filter($form_state['values']['table']['rows'], $filter_selected);
  $policy = CollectionPolicy::emptyPolicy();
  foreach ($selected as $pid => $properties) {
    $content_model = islandora_object_load($pid);
    $policy->addContentModel($pid, $properties['prompt'], $properties['namespace']);
  }
  if (!isset($collection['COLLECTION_POLICY'])) {
    $cp_ds = $collection->constructDatastream('COLLECTION_POLICY', 'M');
    $cp_ds->mimetype = 'application/xml';
    $cp_ds->label = 'Collection Policy';
    $cp_ds->setContentFromString($policy->getXML());
    $collection->ingestDatastream($cp_ds);
  }
  else {
    $collection['COLLECTION_POLICY']->setContentFromString($policy->getXML());
  }
  drupal_set_message(t('Updated collection policy'));
}

/**
 * Define the share children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 * @param AbstractObject $object
 *   The collection to share child objects from.
 *
 * @return array
 *   The Drupal form definition.
 */
function islandora_basic_collection_share_children_form(array $form, array &$form_state, AbstractObject $object) {
  $form_state['collection'] = $object;
  $fragment = '#share-children';
  return array(
    '#action' => request_uri() . $fragment,
    'children' => islandora_basic_collection_get_children_select_table_form_element($object, array(
      'element' => 0,
      'fragment' => $fragment,
    )),
    'collection' => array(
      '#title' => t('Share members with collection'),
      '#description' => t("Members can be shared with any number of collections."),
      '#type' => 'select',
      '#options' => islandora_basic_collection_get_other_collections_as_form_options($object),
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Share selected objects'),
    ),
    'submit_all' => array(
      '#type' => 'submit',
      '#value' => t('Share All Objects'),
    ),
  );
}

/**
 * Form validation for share children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_share_children_form_validate(array $form, array &$form_state) {
  if (!islandora_basic_collection_validate_form($form_state)) {
    form_set_error('collection', t("You cannot share a collection with itself."));
  }
}

/**
 * Submit handler for the share children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_share_children_form_submit(array $form, array &$form_state) {
  $collection = $form_state['collection'];
  $new_collection = islandora_object_load($form_state['values']['collection']);
  $clicked_button = end($form_state['clicked_button']['#parents']);
  if ($clicked_button == 'submit_all') {
    $batch = islandora_basic_collection_share_children_batch($collection, $new_collection, NULL);
  }
  else {
    $children = array_keys(array_filter($form_state['values']['children']));
    $batch = islandora_basic_collection_share_children_batch($collection, $new_collection, $children);
  }

  batch_set($batch);
}

/**
 * Define the migrate children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 * @param AbstractObject $object
 *   The collection to move child objects from.
 *
 * @return array
 *   The Drupal form definition.
 */
function islandora_basic_collection_migrate_children_form(array $form, array &$form_state, AbstractObject $object) {
  $form_state['collection'] = $object;
  $fragment = '#migrate-children';
  return array(
    '#action' => request_uri() . $fragment,
    'children' => islandora_basic_collection_get_children_select_table_form_element($object, array(
      'element' => 1,
      'fragment' => $fragment,
    )),
    'collection' => array(
      '#title' => t('Migrate members to collection'),
      '#description' => t('Removes members from their current collection (%label) and adds them to the selected collection.', array('%label' => $object->label)),
      '#type' => 'select',
      '#options' => islandora_basic_collection_get_other_collections_as_form_options($object),
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Migrate selected objects'),
    ),
    'submit_all' => array(
      '#type' => 'submit',
      '#value' => t('Migrate All Objects'),
    ),
  );
}

/**
 * Form validation for migrate children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_migrate_children_form_validate(array $form, array &$form_state) {
  if (!islandora_basic_collection_validate_form($form_state)) {
    form_set_error('collection', t("You cannot migrate a collection into itself."));
  }
}

/**
 * Submit handler for the migrate children form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_migrate_children_form_submit(array $form, array &$form_state) {
  $collection = $form_state['collection'];
  $new_collection = islandora_object_load($form_state['values']['collection']);
  $clicked_button = end($form_state['clicked_button']['#parents']);
  if ($clicked_button == 'submit_all') {
    $batch = islandora_basic_collection_migrate_children_batch($collection, $new_collection, NULL);
  }
  else {
    $children = array_keys(array_filter($form_state['values']['children']));
    $batch = islandora_basic_collection_migrate_children_batch($collection, $new_collection, $children);
  }
  batch_set($batch);
}

/**
 * Delete child objects form.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 * @param AbstractObject $object
 *   The collection to add the child collection to.
 *
 * @return array
 *   The Drupal form definition.
 */
function islandora_basic_collection_delete_children_form(array $form, array &$form_state, AbstractObject $object) {
  $form_state['collection'] = $object;
  return array(
    '#action' => request_uri() . '#delete-children',
    'children' => islandora_basic_collection_get_children_select_table_form_element($object, array(
      'element' => 2,
      'fragment' => '#delete-children',
    )),
    'description' => array(
      '#type' => 'item',
      '#markup' => t('Objects belonging only to this collection will be purged. If an object is a member of multiple collections, only its relationship to this collection will be removed. Are you sure you want to purge the selected objects?<br/>This action cannot be undone.'),
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Delete selected objects'),
    ),
  );
}

/**
 * Submit handler for  object deletion form in the collection manager.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param array $form_state
 *   The Drupal form state.
 */
function islandora_basic_collection_delete_children_form_submit(array $form, array &$form_state) {
  $collection = $form_state['collection'];
  $children = array_keys(array_filter($form_state['values']['children']));
  $batch = islandora_basic_collection_delete_children_batch($collection, $children);
  batch_set($batch);
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
