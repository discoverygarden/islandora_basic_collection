<?php

/**
 * @file
 * Contains \Drupal\islandora_basic_collection\Form\IslandoraBasicCollectionAdmin.
 */

namespace Drupal\islandora_basic_collection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class IslandoraBasicCollectionAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_basic_collection_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_basic_collection.settings');
kint($form_state->getValues());
/*    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();
*/
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_basic_collection.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $backend_options = \Drupal::moduleHandler()->invokeAll('islandora_basic_collection_query_backends');
    $map_to_title = function ($backend) {
      return $backend['title'];
    };

    $form = [
      // Display options.
      'display_generation_details' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Display Generation'),
        '#states' => [
          'invisible' => [
            ':input[name="islandora_basic_collection_disable_display_generation"]' => ['checked' => TRUE],
          ],
        ],
        'islandora_collection_display' => [
          'islandora_basic_collection_page_size' => [
            '#type' => 'textfield',
            '#title' => $this->t('Default collection objects per page'),
            '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_page_size'),
            '#description' => $this->t('The default number of objects to show in a collection view.'),
          ],
          'islandora_basic_collection_disable_count_object' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Disable object count query in collection overview'),
            '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_disable_count_object'),
            '#description' => $this->t('Disabling the object count query can improve performance when loading the overview for large collections.'),
          ],
          'islandora_basic_collection_default_view' => [
            '#type' => 'select',
            '#title' => $this->t('Default collection view style.'),
            '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_default_view'),
            '#options' => [
              'list' => $this->t('List'),
              'grid' => $this->t('Grid'),
            ],
          ],
          'islandora_basic_collection_display_backend' => [
            '#type' => 'radios',
            '#title' => $this->t('Display Generation'),
            '#options' => array_map($map_to_title, $backend_options),
            '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_display_backend'),
          ],
        ],
      ],
      'islandora_basic_collection_disable_display_generation' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Completely disable default collection display generation.'),
        '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_disable_display_generation'),
        '#description' => $this->t('Disabling display generation allows for alternate collection displays to be used.'),
      ],
      'islandora_basic_collection_admin_page_size' => [
        '#type' => 'textfield',
        '#title' => $this->t('Objects per page during collection management'),
        '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_admin_page_size'),
        '#description' => $this->t('The number of child objects to show per page in the migrate/share/delete interface.'),
        '#element_validate' => ['element_validate_integer_positive'],
        '#required' => TRUE,
      ],
      'islandora_basic_collection_disable_collection_policy_delete' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable deleting the collection policy'),
        '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_disable_collection_policy_delete'),
        '#description' => $this->t("Disables the 'delete' link for the COLLECTION_POLICY datastream."),
      ],
      // Metadata display.
      'metadata_display_details' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Metadata display'),
        'islandora_collection_metadata_display' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Display object metadata'),
          '#description' => $this->t('Display object metadata below the collection display.'),
          '#default_value' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_collection_metadata_display'),
        ],
      ],
    ];


    // Define the elements that appear on a collection objects display page.
    // The key's match up with the form elements array keys.
    $page_content = [
      'description' => [
        'name' => $this->t('Description'),
        'description' => $this->t('An objects description field.'),
      ],
      'collections' => [
        'name' => $this->t('In Collection'),
        'description' => $this->t('Indicates which collections this object belongs to.'),
      ],
      'wrapper' => [
        'name' => $this->t('Metadata'),
        'description' => $this->t('An objects metadata collection set.'),
      ],
      'islandora_basic_collection_display' => [
        'name' => $this->t('Object Content'),
        'description' => $this->t('Main object page content, such as configured viewers.'),
      ],
    ];

    $form['metadata_display_details']['islandora_basic_collection_metadata_info_table_drag_attributes'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Page content placement'),
      '#description' => $this->t('Use the table below to determine the rendering order of page and metadata content.'),
      'collection_display' => [
        '#type' => 'table',
        '#tree' => TRUE,
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
           'group' => 'collection-display-table-order-weight',
          ],
        ],
        '#header' => [$this->t('Attribute'), $this->t('Description'), $this->t('Weight'), $this->t('Hide from display')],
        '#attributes' => array(
          'id' => 'collection-display-table',
        ),
      ],
      '#states' => [
        'visible' => [
          ':input[name="islandora_basic_collection_display_backend"]' => array('!value' => ISLANDORA_BASIC_COLLECTION_LEGACY_BACKEND),
          ':input[name="islandora_collection_metadata_display"]' => [
            'checked' => TRUE
          ],
        ],
      ],
    ];

    $config = \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_metadata_info_table_drag_attributes');

    foreach ($page_content as $key => $data) {
      $form['metadata_display_details']['islandora_basic_collection_metadata_info_table_drag_attributes']['collection_display'][$key] = [];
      $element = &$form['metadata_display_details']['islandora_basic_collection_metadata_info_table_drag_attributes']['collection_display'][$key];
      if (!isset($config[$key])) {
        $config[$key] = [];
      }
      $config[$key] += [
        'weight' => 0,
        'omit' => 0,
      ];

      $element['#attributes']['class'][] = 'draggable';
      $element['#weight'] = $config[$key]['weight'];

      $element['label'] = [
        '#type' => 'item',
        '#markup' => $data['name'],
      ];
      $element['textfield'] = [
        '#type' => 'item',
        '#markup' => $data['description'],
      ];
      $element['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $element['#weight'],
        '#attributes' => [
          'class' => [
            'collection-display-table-order-weight',
          ],
        ],
      );
      $element['omit'] = [
        '#type' => 'checkbox',
        '#default_value' => $config[$key]['omit'],
        '#attributes' => [
          'title' => $this->t('Hide the selected element from display, marking it as an invisible element in the DOM.'),
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}
