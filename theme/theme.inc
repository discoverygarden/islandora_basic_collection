<?php

/**
 * @file
 * This file contains all theme and preprocess functions.
 */

use Drupal\Core\Render\Element;

/**
 * Theme the collection policy management table.
 *
 * @param array $variables
 *   Variables passed to this theme function.
 *
 * @return string
 *   Markup representing the table for rendering.
 */
function islandora_basic_collection_policy_management_table(array $variables) {
  $table = $variables['table'];
  $row_elements = $table['rows'];

  $rows = [];
  foreach (Element::children($row_elements) as $key) {
    $columns = array();
    $row_element = $row_elements[$key];
    foreach (Element::children($row_element) as $key) {
      $column_element = $row_element[$key];
      $columns[] = [
        'data' => \Drupal::service('renderer')->render($column_element),
        'class' => "collection_policy_table_${key}",
      ];
    }
    $rows[] = $columns;
  }

  $return = [
    '#type' => 'table',
    '#header' => $table['#header'],
    '#attached' => ['library' => ['core/drupal.tableselect']],
    '#rows' => $rows,
    '#attributes' => $table['#attributes'],
    'caption' => NULL,
    'colgroups' => NULL,
    'sticky' => NULL,
    '#empty' => t("No content models"),
  ];
  return \Drupal::service('renderer')->render($return);
}
