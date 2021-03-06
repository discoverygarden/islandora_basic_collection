<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Define the migrate children form.
 */
class MigrateChildrenForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_migrate_children_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $object = NULL) {
    $form_state->set('collection', $object);
    $fragment = '#migrate-children';
    return [
      '#action' => Url::fromRoute(
        '<current>',
        [],
        ['query' => $this->getRequest()->query->all(), 'fragment' => $fragment]
      )->toString(),
      'children' => islandora_basic_collection_get_children_select_table_form_element($object, [
        'element' => 1,
        'fragment' => $fragment,
      ]),
      'collection' => [
        '#title' => $this->t('Migrate members to collection'),
        '#description' => $this->t('Removes members from their current collection (%label) and adds them to the selected collection.', ['%label' => $object->label]),
        '#type' => 'select',
        '#options' => islandora_basic_collection_get_other_collections_as_form_options($object),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Migrate selected objects'),
      ],
      'submit_all' => [
        '#type' => 'submit',
        '#value' => $this->t('Migrate All Objects'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $children = array_filter($form_state->getValue('children'));
    if (!$form['children']['#options'] ||
      (empty($children) && reset($form_state->getTriggeringElement()['#parents']) != 'submit_all')) {
      $form_state->setErrorByName('children', $this->t('No children available to migrate.'));
    }
    if (!islandora_basic_collection_validate_form($form_state)) {
      $form_state->setErrorByName('collection', $this->t('One cannot migrate a collection into itself.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection = $form_state->get('collection');
    $new_collection = islandora_object_load($form_state->getValue('collection'));
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button == 'submit_all') {
      $batch = islandora_basic_collection_migrate_children_batch($collection, $new_collection, NULL);
    }
    else {
      $children = array_keys(array_filter($form_state->getValue('children')));
      $batch = islandora_basic_collection_migrate_children_batch($collection, $new_collection, $children);
    }
    batch_set($batch);
  }

}
