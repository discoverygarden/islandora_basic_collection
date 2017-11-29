<?php

namespace Drupal\islandora_basic_collection\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block.
 *
 * @Block(
 *   id = "collection_object_count",
 *   admin_label = @Translation("Islandora Collection Object Count Listing"),
 * )
 */
class IslandoraBasicCollectionCollectionObjectCount extends BlockBase implements ContainerFactoryPluginInterface {

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
    $block = islandora_basic_collection_object_count_listing_content();
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    module_load_include('inc', 'islandora', 'includes/utilities');
    $form = parent::blockForm($form, $form_state);
    $form['islandora_basic_collection_title_phrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The sentence to appear to describe the number of objects and collections present.'),
      '#description' => $this->t('For the number of objects use !objects, for the number of container objects use !collections.'),
      '#default_value' => $this->config->get('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_phrase'),
    ];
    $form['islandora_basic_collection_title_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AJAX Placeholder'),
      '#description' => $this->t('Placeholder to output, to be replaced by phrase populated by AJAX.'),
      '#default_value' => $this->config->get('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_placeholder'),
    ];
    $formatted_models = [];
    $models = islandora_get_content_models();
    foreach ($models as $pid => $values) {
      $formatted_models[$pid] = $values['label'];
    }
    $default_cmodel_options = $this->config->get('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_content_models_to_restrict');
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
    $config->set('islandora_basic_collection_object_count_listing_content_models_to_restrict', $form_state->getValue('content_models'));
    $config->set('islandora_basic_collection_object_count_listing_phrase', $form_state->getValue('islandora_basic_collection_title_phrase'));
    $config->set('islandora_basic_collection_object_count_listing_placeholder', $form_state->getValue('islandora_basic_collection_title_placeholder'));
    $config->save();
  }

}
