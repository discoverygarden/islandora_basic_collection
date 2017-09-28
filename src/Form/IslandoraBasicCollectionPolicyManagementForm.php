<?php

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\URL;

use Drupal\islandora_basic_collection\CollectionPolicy;

/**
 * Define collection policy management form.
 */
class IslandoraBasicCollectionPolicyManagementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_policy_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $object = NULL) {
    $form_state->loadInclude('islandora', 'inc', 'includes/utilities');
    $form_state->set('collection', $object);
    if (isset($object['COLLECTION_POLICY'])) {
      $policy = new CollectionPolicy($object['COLLECTION_POLICY']->content);
    }
    else {
      $policy = CollectionPolicy::emptyPolicy();
    }
    $policy_content_models = $policy->getContentModels();
    $content_models = islandora_get_content_models();
    $default_namespace = \Drupal\Component\Utility\Unicode::substr($object->id, 0, strpos($object->id, ":"));
    $rows = [];
    foreach ($content_models as $pid => $content_model) {
      $label = $content_model['label'];
      $content_model_in_policy = isset($policy_content_models[$pid]);
      $namespace = $content_model_in_policy ? $policy_content_models[$pid]['namespace'] : $default_namespace;
      $namespace_element = islandora_basic_collection_get_namespace_form_element($namespace);
      $prompt_element = [
        '#type' => 'textfield',
        '#size' => 15,
        '#default_value' => isset($policy_content_models[$pid]) ? $policy_content_models[$pid]['name'] : $content_model['label'],
      ];
      unset($namespace_element['#title'], $namespace_element['#description']);
      $rows[$pid] = [
        'selected' => [
          '#type' => 'checkbox',
          '#default_value' => $content_model_in_policy,
        ],
        'title' => [
          '#markup' => Link::createFromRoute($this->t('@label (@pid)', ['@label' => $label, '@pid' => $pid]), 'islandora.view_object', ['object' => $pid]),
        ],
        'prompt' => $prompt_element,
        'namespace' => $namespace_element,
      ];
    }
    return [
      '#attached' => ['library' => ['islandora_basic_collection' => 'policy-table-css']],
      '#action' => Url::fromRoute('<current>', [], ['fragment' => '#policy-management'])->toString(),
      'help' => [
        '#type' => 'item',
        '#markup' => \Drupal::l($this->t('About Collection Policies'), \Drupal\Core\Url::fromUri('https://wiki.duraspace.org/display/ISLANDORA715/How+to+Manage+Collection+Policies')),
      ],
      'table' => [
        '#tree' => TRUE,
        '#header' => [
          'class' => ['select-all'],
          'pid' => ['data' => $this->t('PID'), 'class' => "collection_policy_pid"],
          'prompt' => ['data' => $this->t('Prompt'), 'class' => "collection_policy_prompt"],
          'namespace' => ['data' => $this->t('Namespace'), 'class' => "collection_policy_namespace"],
        ],
        '#theme' => 'islandora_basic_collection_policy_management_table',
        'rows' => $rows,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Update collection policy'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $collection = $form_state->get('collection');
    $filter_selected = function($o) {
      return $o['selected'];
    };
    $selected = array_filter($form_state->getValue(['table']['rows']), $filter_selected);
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
    drupal_set_message($this->t('Updated collection policy.'));
  }

}
