<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Shares an object that is not a collection to an additional collection.
 */
class IslandoraBasicCollectionShareItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_share_item_form';
  }

  /**
   * The form build function.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $object = NULL) {
    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('Share single item'),
    ];
    $form['new_collection_name'] = [
      '#autocomplete_route_name' => 'islandora_basic_collection.get_collections_filtered',
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Share Object'),
    ];
    $form_state->set('basic_collection_share', $object->id);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
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
      $form_state->setErrorByName('new_collection_name', $this->t('Not a valid collection'));
    }
    $has_ingest_permissions = islandora_object_access(ISLANDORA_INGEST, $new_collection);
    if (!$has_ingest_permissions) {
      $form_state->setErrorByName('new_collection_name', $this->t('You do not have permission to ingest objects to this collection'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_basic_collection', 'inc', 'includes/utilities');
    $object = islandora_object_load($form_state->get('basic_collection_share'));
    $new_collection = islandora_object_load($form_state->getValue('new_collection_name'));

    if ($object && $new_collection) {
      islandora_basic_collection_add_to_collection($object, $new_collection);
      $message = $this->t('The object @object has been added to @collection.', [
        '@object' => $object->label,
        '@collection' => $new_collection->label,
      ]);
      drupal_set_message($message);
    }
  }

}
