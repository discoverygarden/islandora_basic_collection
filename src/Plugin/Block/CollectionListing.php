<?php

namespace Drupal\islandora_basic_collection\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\Plugin\Block\AbstractConfiguredBlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block.
 *
 * @Block(
 *   id = "collection_listing",
 *   admin_label = @Translation("Islandora Collection Listing"),
 * )
 */
class CollectionListing extends AbstractConfiguredBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'islandora_basic_collection', 'includes/blocks');
    return [
      '#title' => $this->t('Collections'),
      'markup' => _islandora_basic_collection_collection_listing_content(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    module_load_include('inc', 'islandora', 'includes/utilities');
    $config = $this->configFactory->get('islandora_basic_collection.settings');
    $form['islandora_basic_collection_links_to_render'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Number of collections to display'),
      '#default_value' => $config->get('islandora_basic_collection_listing_block_links_to_render'),
    ];
    $formatted_models = [];
    $models = islandora_get_content_models();
    foreach ($models as $pid => $values) {
      $formatted_models[$pid] = $values['label'];
    }
    $default_cmodel_options = $config->get('islandora_basic_collection_listing_block_content_models_to_restrict');
    $default_checked = [];
    // If we have default values previously set, add them now.
    if ($default_cmodel_options) {
      foreach ($default_cmodel_options as $pid => $val) {
        if ($val) {
          $default_checked[$pid] = $pid;
        }
      }
    }
    else {
      foreach ($formatted_models as $pid => $label) {
        $default_checked[$pid] = $pid;
      }
    }
    $form['content_models'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content models to include:'),
      '#description' => $this->t('When selected objects with a specific content model will not appear in the total count of results.'),
      '#options' => $formatted_models,
      '#default_value' => $default_checked,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('islandora_basic_collection.settings');
    $config->set('islandora_basic_collection_listing_block_links_to_render', $form_state->getValue('islandora_basic_collection_links_to_render'));
    $config->set('islandora_basic_collection_listing_block_content_models_to_restrict', $form_state->getValue('content_models'));
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, ISLANDORA_VIEW_OBJECTS);
  }

}
