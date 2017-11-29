<?php

namespace Drupal\islandora_basic_collection\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a block.
 *
 * @Block(
 *   id = "collection_listing",
 *   admin_label = @Translation("Islandora Collection Listing"),
 * )
 */
class IslandoraBasicCollectionCollectionListing extends BlockBase implements ContainerFactoryPluginInterface {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'islandora_basic_collection', 'includes/blocks');
    $block['#markup'] = islandora_basic_collection_collection_listing_content();
    $block['#title'] = $this->t('Collections');
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    module_load_include('inc', 'islandora', 'includes/utilities');
    $form['islandora_basic_collection_links_to_render'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Number of collections to display'),
      '#default_value' => $this->$configFactory->get('islandora_basic_collection.settings')->get('islandora_basic_collection_listing_block_links_to_render'),
    ];
    $formatted_models = [];
    $models = islandora_get_content_models();
    foreach ($models as $pid => $values) {
      $formatted_models[$pid] = $values['label'];
    }
    $default_cmodel_options = $this->$configFactory->get('islandora_basic_collection.settings')->get('islandora_basic_collection_listing_block_content_models_to_restrict');
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
    $config = $this->$configFactory->getEditable('islandora_basic_collection.settings');
    $config->set('islandora_basic_collection_listing_block_links_to_render', $form_state->getValue('islandora_basic_collection_links_to_render'));
    $config->set('islandora_basic_collection_listing_block_content_models_to_restrict', $form_state->getValue('content_models'));
    $config->save();
  }

}
