<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Migrates an object that is not a collection to a collection.
 */
class IslandoraBasicCollectionMigrateItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_migrate_item_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $object = NULL) {
    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Migrate this item'),
    ];
    $form['new_collection_name'] = [
      '#autocomplete_route_name' => 'islandora_basic_collection.get_collections_filtered',
      '#type' => 'textfield',
      '#title' => $this->t('New Collection'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate Object'),
    ];
    $form_state->set('basic_collection_migrate', $object->id);
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_collection = islandora_object_load($form_state->getValue(
      'new_collection_name'
      ));
    $collection_models = islandora_basic_collection_get_collection_content_models();
    $is_a_collection = FALSE;
    if (is_object($new_collection)) {
      $is_a_collection = (
        (count(array_intersect($collection_models, $new_collection->models)) > 0) && isset($new_collection['COLLECTION_POLICY'])
        );
    }
    if (!$is_a_collection) {
      $form_state->setErrorByName('new_collection_name', $this->t('Not a valid collection.'));
    }
    $has_ingest_permissions = islandora_object_access(ISLANDORA_INGEST, $new_collection);
    if (!$has_ingest_permissions) {
      $form_state->setErrorByName('new_collection_name', $this->t('You do not have permission to ingest objects to this collection.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_basic_collection', 'inc', 'includes/utilities');
    $object = islandora_object_load($form_state->get('basic_collection_migrate'));
    $new_collection = islandora_object_load($form_state->getValue('new_collection_name'));
    $current_parents = islandora_basic_collection_get_parent_pids($object);
    if ($object && $new_collection) {
      foreach ($current_parents as $parents) {
        $parent = islandora_object_load($parents);
        islandora_basic_collection_remove_from_collection($object, $parent);
      }
      islandora_basic_collection_add_to_collection($object, $new_collection);
      $message = $this->t('The object @object has been added to @collection.', [
        '@object' => $object->label,
        '@collection' => $new_collection->label,
      ]);
      drupal_set_message($message);

    }
  }

}
