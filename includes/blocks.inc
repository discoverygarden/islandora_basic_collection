<?php
/**
 * @file
 * Provides block functionality for the basic collection module.
 */

use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Constructs the contents of the object count block.
 *
 * @return string
 *   The markup to be rendered inside the block.
 */
function islandora_basic_collection_object_count_listing_content() {
  $block = [];
  $id = \Drupal\Component\Utility\Html::getId('islandora_basic_collection_object_count_block_count');
  $block['counts'] = [
    '#attached' => [
      'library' => ['islandora_basic_collection/islandora-basic-collection-block-count'],
      'drupalSettings' => [
        'islandora_basic_collection' => [
          'count_block' => [
            'id' => $id,
            'callback' => Url::fromRoute('islandora_basic_collection.object_count_callback')->toString(),
          ],
        ],
      ],
    ],
    '#prefix' => "<span id='$id'>",
    '#suffix' => '</span>',
    '#markup' => \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_placeholder'),
  ];

  return $block;
}

/**
 * AJAX callback to get info about the count of objects and collections.
 *
 * Outputs a JSON string to the buffer.
 */
function islandora_basic_collection_object_count_callback() {
  $tuque = islandora_get_tuque_connection();
  $root_pid = \Drupal::config('islandora.settings')->get('islandora_repository_pid');
  $objects_query_array = islandora_basic_collection_get_query_info([
    'object' => islandora_object_load($root_pid),
    'collection_listing' => TRUE,
    'all_objects' => TRUE,
  ]);

  $collection_query_array = islandora_basic_collection_get_query_info([
    'object' => islandora_object_load($root_pid),
    'collection_listing' => TRUE,
    'all_objects' => FALSE,
  ]);

  $collection_objects = $tuque->repository->ri->sparqlQuery($collection_query_array['query'], $collection_query_array['type']);
  $total_object_count = $tuque->repository->ri->countQuery($objects_query_array['query'], $objects_query_array['type']);

  $collections = [];
  foreach ($collection_objects as $collection) {
    $collections[$collection['object']['value']] = $collection['object']['value'];
  }
  $models_to_exclude = \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_content_models_to_restrict');
  if ($models_to_exclude) {
    $collections = islandora_basic_collection_filter_collection_by_cmodel($collections, array_filter($models_to_exclude));
  }
  $total_collection_count = count($collections);

  $substitutions = [
    '!objects' => $total_object_count,
    '!collections' => $total_collection_count,
  ];

  $title_phrase = \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_object_count_listing_phrase');
  $text = strtr($title_phrase, $substitutions);
  return new JsonResponse($text);
}

/**
 * Constructs the content of the collection listing block.
 *
 * @return string
 *   The markup to be rendered in the block.
 */
function islandora_basic_collection_collection_listing_content() {
  $block = [];
  $tuque = islandora_get_tuque_connection();
  $collection_query_array = islandora_basic_collection_get_query_info([
    'object' => islandora_object_load('islandora:root'),
    'collection_listing' => TRUE,
    'all_objects' => FALSE,
  ]);
  $collections_to_display = \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_listing_block_links_to_render');
  $collection_objects = $tuque->repository->ri->sparqlQuery($collection_query_array['query'], $collection_query_array['type']);
  $collections = [];
  $stored_collections = [];
  foreach ($collection_objects as $collection) {
    $pid = $collection['object']['value'];
    $collections[$pid] = $pid;
    $stored_collections[$pid] = $collection['title']['value'];
  }
  $models_to_exclude = \Drupal::config('islandora_basic_collection.settings')->get('islandora_basic_collection_listing_block_content_models_to_restrict');
  if ($models_to_exclude) {
    $collections = islandora_basic_collection_filter_collection_by_cmodel($collections, array_filter($models_to_exclude));
  }

  $formatted_objects = [];
  foreach ($collections as $pid) {
    $formatted_objects[$pid] = Link::createFromRoute($stored_collections[$pid], 'islandora.view_object', ['object' => $pid])->toString();
  }
  $block['collections'] = [
    '#theme' => 'item_list',
    '#items' => array_slice($formatted_objects, 0, $collections_to_display),
    '#type' => 'ul',
  ];
  $block['browse_repository'] = [
    '#type' => 'item',
    '#markup' => Link::createFromRoute(t('Browse repository'), 'islandora.view_object', ['object' => $pid])->toString(),
    '#attributes' => [
      'class' => ['islandora-basic-collection-read-more'],
    ],
  ];

  return \Drupal::service("renderer")->render($block);
}

/**
 * Filters out collections that only contain included content models.
 *
 * @param array $collections
 *   An array of PIDs representing collection objects.
 * @param array $models
 *   An array of content models to be included.
 *
 * @return array
 *   An array of collections to be used.
 */
function islandora_basic_collection_filter_collection_by_cmodel(array $collections, array $models) {
  $collection_filter = function ($collection) use ($models) {
    $tuque = islandora_get_tuque_connection();
    $models_query = <<<EOQ
    SELECT DISTINCT ?model from <#ri> where {
?object <fedora-model:hasModel> ?model ;
        <fedora-rels-ext:isMemberOfCollection> <info:fedora/{$collection}>
}
EOQ;
    $models_results = $tuque->repository->ri->sparqlQuery($models_query);
    $collection_models = [];
    foreach ($models_results as $result) {
      $collection_models[$result['model']['value']] = $result['model']['value'];
    }
    return array_intersect($collection_models, $models);
  };
  $filtered_collections = array_filter($collections, $collection_filter);
  return $filtered_collections;
}
