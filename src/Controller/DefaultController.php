<?php
/**
 * @file
 * Contains \Drupal\islandora_basic_collection\Controller\DefaultController.
 */

namespace Drupal\islandora_basic_collection\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Default controller for the islandora_basic_collection module.
 */
class DefaultController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  public function islandora_basic_collection_manage_access($object = NULL, Drupal\Core\Session\AccountInterface $account) {
    $collection_models = islandora_basic_collection_get_collection_content_models();
    $is_a_collection = count(array_intersect($collection_models, $object->models)) > 0;

    return $is_a_collection && (
      islandora_object_access(ISLANDORA_BASIC_COLLECTION_MANAGE_COLLECTION_POLICY, $object) || islandora_object_access(ISLANDORA_BASIC_COLLECTION_MIGRATE_COLLECTION_MEMBERS, $object) || islandora_object_access(ISLANDORA_INGEST, $object) || islandora_object_access(ISLANDORA_PURGE, $object)
      );
  }

  public function islandora_basic_collection_manage_object(AbstractObject $object) {
    $return_form = ['manage_collection_object' => []];
    $data = islandora_invoke_hook_list(ISLANDORA_BASIC_COLLECTION_BUILD_MANAGE_OBJECT_HOOK, $object->models, [
      $return_form,
      $object,
    ]);
    $data['manage_collection_object']['#type'] = 'vertical_tabs';
    return $data;
  }

  public function islandora_basic_collection_get_collections_filtered($search_value) {
    $tuque = islandora_get_tuque_connection();
    $sparql_query = <<<EOQ
SELECT ?pid ?label
WHERE {
  ?pid <fedora-model:label> ?label ;
       <fedora-model:hasModel> <info:fedora/islandora:collectionCModel> .
  FILTER(regex(?label, '$search_value', 'i') || regex(str(?pid), '$search_value', 'i'))
}
EOQ;
    $results = $tuque->repository->ri->sparqlQuery($sparql_query);
    $return = [];
    foreach ($results as $objects) {
      $return[$objects['pid']['value']] = t('@label (@pid)', [
        '@label' => $objects['label']['value'],
        '@pid' => $objects['pid']['value'],
      ]);
    }
    drupal_json_output($return);
  }

  public function islandora_basic_collection_ingest_access(AbstractObject $object, \Drupal\Core\Session\AccountInterface $account) {
    $collection_models = islandora_basic_collection_get_collection_content_models();
    $is_a_collection = (
      (count(array_intersect($collection_models, $object->models)) > 0) && isset($object['COLLECTION_POLICY'])
      );

    if (!$is_a_collection) {
      return AccessResult::forbidden();
    }

    module_load_include('inc', 'islandora', 'includes/ingest.form');
    module_load_include('inc', 'islandora_basic_collection', 'includes/ingest.form');
    $configuration = islandora_basic_collection_get_ingest_configuration($object);
    $has_ingest_steps = islandora_ingest_can_display_ingest_form($configuration);

    return AccessResult::allowedIf($has_ingest_steps && islandora_object_access(ISLANDORA_INGEST, $object));
  }

  public function islandora_basic_collection_ingest_action(AbstractObject $object) {
    if (($configuration = islandora_basic_collection_get_ingest_configuration($object)) !== FALSE) {
      module_load_include('inc', 'islandora', 'includes/ingest.form');
      return $this->formBuilder->getForm('Drupal\islandora\Form\IslandoraIngestForm', $configuration);
    }
    drupal_not_found();
  }

  public function islandora_basic_collection_object_count_callback() {
    module_load_include('inc', 'islandora_basic_collection', 'includes/blocks');
    return islandora_basic_collection_object_count_callback();
  }

}
