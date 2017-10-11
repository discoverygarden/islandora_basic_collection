<?php

namespace Drupal\islandora_basic_collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Adds our perms to the manage object access check.
   */
  public function alterRoutes(RouteCollection $collection) {
    $manage_route = $collection->get('islandora.manage_overview_object');
    $current_access_arguments = $manage_route->getDefault('perms');
    $new_access_arguments = [
      ISLANDORA_BASIC_COLLECTION_MANAGE_COLLECTION_POLICY,
      ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS,
    ];
    $access_arguments = array_merge($current_access_arguments, $new_access_arguments);
    $manage_route->setDefault('perms', $access_arguments);
  }

}
