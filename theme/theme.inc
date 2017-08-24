<?php

/**
 * @file
 * This file contains all theme and preprocess functions
 */

/**
 * Returns HTML for the metadata_table_drag_components weight table.
 *
 * @param array $vars
 *   An associative array containing:
 *   - element: Table element array.
 *   - theme_hook_original: Initial theme hook.
 *
 * @return string
 *   Themed table html.
 *
 * @ingroup themeable
 */
function theme_islandora_basic_collection_metadata_table_drag_components($vars) {
  $element = $vars['element'];
  $clean_id = \Drupal\Component\Utility\Html::getId('islandora_basic_collection_meta_page_element_table');
  // @FIXME
// TableDrag is now attached with the #tabledrag property of certain render
// arrays. drupal_add_tabledrag() is now internal and should never be called directly.
//
//
// @see https://www.drupal.org/node/2160571
// drupal_add_tabledrag($clean_id, 'order', 'sibling', 'item-row-weight');


  $header = array(
    'label' => t('Attribute'),
    'textfield' => t('Description'),
    'weight' => t('Weight'),
    'omit' => t('Hide from display'),
  );

  $rows = array();
  foreach (\Drupal\Core\Render\Element::children($element) as $key) {
    $row = array();
    $row['data'] = array();
    foreach ($header as $fieldname => $title) {
      $row['data'][] = \Drupal::service("renderer")->render($element[$key][$fieldname]);
      $row['class'] = array('draggable');
    }
    $rows[] = $row;
  }

  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// return theme('table', array(
//     'header' => $header,
//     'rows' => $rows,
//     'attributes' => array('id' => $clean_id),
//   ));

}

/**
 * Theme the collection policy management table.
 *
 * @param array $variables
 *   Variables passed to this theme function.
 *
 * @return string
 *   Markup representing the table for rendering.
 */
function theme_islandora_basic_collection_policy_management_table(array $variables) {
  // Manually add the table select javascript as we are using a custom table.
  // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
//
//
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_js('misc/tableselect.js');

  $table = $variables['table'];
  $row_elements = $table['rows'];
  $rows = array();
  foreach (\Drupal\Core\Render\Element::children($row_elements) as $key) {
    $columns = array();
    $row_element = $row_elements[$key];
    foreach (\Drupal\Core\Render\Element::children($row_element) as $key) {
      $column_element = $row_element[$key];
      $class = "collection_policy_table_${key}";
      $columns[] = array(
        'data' => \Drupal::service("renderer")->render($column_element),
        'class' => isset($cell_element['#attributes']['class']) ? $column_element['#attributes']['class'] : $class,
      );
    }
    $rows[] = $columns;
  }
  $variables = array(
    'header' => $table['#header'],
    'rows' => $rows,
    'attributes' => $table['#attributes'],
    'caption' => NULL,
    'colgroups' => NULL,
    'sticky' => NULL,
    'empty' => t("No content models"),
  );
  // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
// return theme('table', $variables);

}
