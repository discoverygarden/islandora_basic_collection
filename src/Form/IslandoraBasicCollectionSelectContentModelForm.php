<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\islandora_basic_collection\CollectionPolicy;

/**
 * Defines the select content model ingest step form.
 *
 * Assumes that only a single content model can be selected, and only a single
 * object will be ingested.
 *
 * @package \Drupal\islandora_basic_collection\Form
 */
class IslandoraBasicCollectionSelectContentModelForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_select_content_model_form';
  }

  /**
   * Build the form.
   *
   * @param array $form
   *   The Drupal form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param array $models
   *   The models to include in the form.
   *
   * @return array
   *   The Drupal form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $models = []) {
    $form_state->loadInclude('islandora', 'inc', 'includes/ingest.form');
    $options = array();
    $steps = islandora_ingest_form_get_shared_storage($form_state);
    $parent_pid = $steps['parent'];

    $collection = islandora_object_load($parent_pid);
    $policy = new CollectionPolicy($collection['COLLECTION_POLICY']->content);
    $cmodels = $policy->getContentModels();
    foreach ($models as $pid) {
      $object = islandora_object_load($pid);
      if ($object) {
        $options[$pid] = isset($cmodels[$pid]['name']) ? $cmodels[$pid]['name'] : $object->label;
      }
    }

    $model = $form_state->getValue('models') ? $form_state->getValue('models') : key($options);
    $shared_storage = &islandora_ingest_form_get_shared_storage($form_state);
    $shared_storage['models'] = array($model);
    $return_form = array(
      '#prefix' => '<div id="islandora-select-content-model-wrapper">',
      '#suffix' => '</div>',
      'models' => array(
        '#type' => 'select',
        '#title' => t('Select a Content Model to Ingest'),
        '#options' => $options,
        '#default_value' => $model,
        '#ajax' => array(
          'callback' => 'islandora_basic_collection_select_content_model_form_ajax_callback',
          'wrapper' => 'islandora-select-content-model-wrapper',
          'method' => 'replace',
          'effect' => 'fade',
        ),
      ),
    );
    return $return_form;
  }

  /**
   * Select a content model for the ingest object.
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_basic_collection', 'inc', 'includes/ingest.form');
    $model = $form_state->getValue('models');
    islandora_basic_collection_ingest_form_select_model($form_state, $model);
  }

  /**
   * Undo selection of a content model for the ingest object.
   */
  function undoSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_basic_collection', 'inc', 'includes/ingest.form');
    islandora_basic_collection_ingest_form_unselect_model($form_state);
  }

}
