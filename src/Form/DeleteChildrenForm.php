<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Delete child objects form.
 */
class DeleteChildrenForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_delete_children_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $object = NULL) {
    $form_state->set('collection', $object);
    return [
      '#action' => Url::fromRoute(
        '<current>',
        [],
        ['query' => $this->getRequest()->query->all(), 'fragment' => '#delete-children']
      )->toString(),
      'children' => islandora_basic_collection_get_children_select_table_form_element($object, [
        'element' => 2,
        'fragment' => '#delete-children',
      ]),
      'description' => [
        '#type' => 'item',
        '#markup' => $this->t('Objects belonging only to this collection will be purged. If an object is a member of multiple collections, only its relationship to this collection will be removed. Are you sure you want to purge the selected objects?<br/>This action cannot be undone.'),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected objects'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection = $form_state->get('collection');
    $children = array_keys(array_filter($form_state->getValue('children')));
    $batch = islandora_basic_collection_delete_children_batch($collection, $children);
    batch_set($batch);
  }

}
