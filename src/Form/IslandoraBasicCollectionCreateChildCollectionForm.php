<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\islandora_basic_collection\CollectionPolicy;

/**
 * Collection creation form.
 *
 * @package \Drupal\islandora_basic_collection\Form
 */
class IslandoraBasicCollectionCreateChildCollectionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_create_child_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = NULL) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    // If the form has step_storage values set, use them instead of the defaults.
    $step_storage = &islandora_ingest_form_get_step_storage($form_state, 'islandora_basic_collection');
    $form_values = isset($step_storage['values']) ? $step_storage['values'] : NULL;
    $parent_object = islandora_object_load($form_state->get(['islandora', 'shared_storage', 'parent']));
    // Permissions handling.
    if (!\Drupal::currentUser()->hasPermission(ISLANDORA_BASIC_COLLECTION_CREATE_CHILD_COLLECTION)) {
      drupal_set_message(t('You do not have permissions to create collections.'), 'error');
      drupal_goto('islandora/object/' . $parent_object->id);
    }
    $policy = new CollectionPolicy($parent_object['COLLECTION_POLICY']->content);
    $policy_content_models = $policy->getContentModels();
    $content_models = islandora_get_content_models();
    $form_state->setValue('content_models', $content_models);
    $default_namespace = islandora_get_namespace($policy_content_models['islandora:collectionCModel']['namespace']);
    $the_namespace = isset($form_values['namespace']) ? $form_values['namespace'] : $default_namespace;
    $content_models_values = isset($form_values['content_models']) ? array_filter($form_values['content_models']) : array();

    return [
      '#action' => \Drupal::request()->getUri() . '#create-child-collection',
      'pid' => [
        '#type' => 'textfield',
        '#title' => $this->t('Collection PID'),
        '#description' => $this->t("Unique PID for this collection. Leave blank to use the default.<br/>PIDs take the general form of <strong>namespace:collection</strong> (e.g., islandora:pamphlets)"),
        '#size' => 15,
        '#default_value' => isset($form_values['pid']) ? $form_values['pid'] : '',
      ],
      'inherit_policy' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Inherit collection policy?'),
        '#default_value' => isset($form_values['inherit_policy']) ? $form_values['inherit_policy'] == 1 : TRUE,
      ],
      'policy' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Collection Policy'),
        '#states' => [
          'visible' => [
            ':input[name="inherit_policy"]' => ['checked' => FALSE],
          ],
        ],
        'namespace' => islandora_basic_collection_get_namespace_form_element($the_namespace),
        'content_models' => [
          '#title' => "Allowable content models",
          '#type' => 'checkboxes',
          '#options' => islandora_basic_collection_get_content_models_as_form_options($content_models),
          '#default_value' => $content_models_values,
          '#description' => $this->t("Content models describe the behaviours of objects with which they are associated."),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');

    // Prepare Object.
    $new_collection = $form_state->get(['islandora', 'objects', 0]);
    if (!empty($form_state->getValue('pid'))) {
      $new_collection->id = $form_state->getValue('pid');
    }

    // Add COLLECTION_POLICY datastream.
    $parent_collection = islandora_object_load($form_state->get(['islandora', 'shared_storage', 'parent']));
    if ($form_state->getValue('inherit_policy')) {
      $collection_policy = $parent_collection['COLLECTION_POLICY']->content;
    }
    else {
      $policy = CollectionPolicy::emptyPolicy();
      $content_models = array_filter($form_state->getValue(['values', 'content_models']));
      foreach (array_keys($content_models) as $pid) {
        $policy->addContentModel($pid, $form_state->get(['content_models', $pid, 'label']), $form_state->getValue('namespace'));
      }
      $collection_policy = $policy->getXML();
    }
    $policy_datastream = $new_collection->constructDatastream('COLLECTION_POLICY', 'X');
    $policy_datastream->setContentFromString($collection_policy);
    $policy_datastream->label = 'Collection policy';
    $new_collection->ingestDatastream($policy_datastream);

    $step_storage = &islandora_ingest_form_get_step_storage($form_state, 'islandora_basic_collection');
    $step_storage['created']['COLLECTION_POLICY'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    if (!empty($form_state->getValue('pid'))) {
      $pid = $form_state->getValue('pid');
      if (!islandora_is_valid_pid($pid)) {
        $form_state->setErrorByName('pid', $this->t('Collection PID is invalid.'));
      }
      elseif (islandora_object_load($pid)) {
        $form_state->setErrorByName('pid', $this->t('Collection PID already exists.'));
      }
    }
  }

  /**
   * Undo setting the COLLECTION_POLICY, purging the datastream that was created.
   *
   * @param array $form
   *   The Drupal form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   */
  function undoSubmit(array &$form, FormStateInterface $form_state) {
    $step_storage = &islandora_ingest_form_get_step_storage($form_state, 'islandora_basic_collection');
    $object = islandora_ingest_form_get_object($form_state);
    foreach ($step_storage['created'] as $dsid => $created) {
      if ($created) {
        $object->purgeDatastream($dsid);
      }
    }
    unset($step_storage['created']);
  }

}
