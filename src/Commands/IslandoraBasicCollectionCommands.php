<?php

namespace Drupal\islandora_basic_collection\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commandfile for Islandora Basic Collection.
 */
class IslandoraBasicCollectionCommands extends DrushCommands {

  /**
   * Recursively grabs a thumbnail from collections children for its thumbnail.
   *
   * @option collection
   *   Where to start creating thumbnails.
   * @usage drush -u1 islandora-basic-collection-generate-thumbs-from-children --collection=islandora:root
   *   Start generation from the root collection.
   * @validate-module-enabled islandora,islandora_basic_collection
   *
   * @command islandora:basic-collection-generate-thumbs-from-children
   * @aliases islandora-basic-collection-generate-thumbs-from-children
   * @islandora-user-wrap
   * @islandora-require-option collection
   */
  public function basicCollectionGenerateThumbsFromChildren(array $options = ['collection' => self::REQ]) {
    module_load_include('inc', 'islandora_basic_collection', 'includes/thumbs.batch');
    batch_set(islandora_basic_collection_thumbs_batch($options['collection']));
    drush_backend_batch_process();
  }

}
