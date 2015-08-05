<?php

/**
 * @file
 * Drush integration file for the Islandora CONTENTdm Collection Migrator.
 */

/**
 * Implements hook_drush_help().
 */
function islandora_migrate_cdm_collections_drush_help($command) {
  switch ($command) {
    case 'drush:create-islandora-collections-from-cdm':
      return dt('Creates Islandora collections using the specified data exported from CONTENTdm.');
  }
}

/**
 * Implements hook_drush_command().
 */
function islandora_migrate_cdm_collections_drush_command() {
  $items = array();
  $items['create-islandora-collections-from-cdm'] = array(
    'description' => dt('Creates Islandora collections using the specified data exported from CONTENTdm.'),
    'options' => array(
      'namespace' => array(
        'description' => 'The namespace to use for the new collections. Defaults to "islandora".',
      ),
      'parent' => array(
        'description' => 'The collection to which the new collections should ' .
        'be added. Defaults to the root Islandora repository PID.',
      ),
      'input' => array(
        'description' => 'The absolute path to the tab-delimited file generated ' .
        'by the get_collection_data.php script.',
        'required' => 'TRUE',
      ),
      'create_node_with_content_type' => array(
        'description' => 'Create a node for each collection with the specified Drupal content type. ' .
        'Defaults to "page".',
      ),
    ),
    'examples' => array(
      'Standard example' => 'drush --user=admin create-islandora-collections-from-cdm --namespace=mynamespace --parent=mycollection:10  --input=/tmp/cdmcollectiondata/collection_data.tsv',
      'Alias example' => 'drush --user=admin cicfc --namespace=mynamespace --parent=mycollection:10  --input=/tmp/cdmcollectiondata/collection_data.tsv',
    ),
    'aliases' => array('cicfc'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  return $items;
}

/**
 * Callback function for drush create-islandora-collections-from-cdm.
 */
function drush_islandora_migrate_cdm_collections_create_islandora_collections_from_cdm() {
  $params = array(
    'namespace' => drush_get_option('namespace', 'islandora'),
    'input' => drush_get_option('input'),
    'parent' => drush_get_option('parent', variable_get('islandora_repository_pid', 'islandora:root')),
    'create_node_with_content_type' => drush_get_option('create_node_with_content_type', NULL),
  );

  if (empty(islandora_object_load(drush_get_option('parent')))) {
    return drush_set_error(dt('The specified parent object (!parent) is not found or is not accessible.',
      array('!parent' => drush_get_option('parent'))));
  }

  if (!file_exists($params['input'])) {
    return drush_set_error(dt("Can't find data file at @path.", array('@path' => $params['input'])));
  }

  // Do some validation checks on the content type.
  // Replace all non letters, numbers, and spaces with _ prior to node_type_load(),
  // same as Drupal core does, in case the user copies the machine name from the URL,
  // which uses - instead of _.
  $content_type = preg_replace('/[^a-zA-Z0-9]+/', '_', $params['create_node_with_content_type']);
  if ($params['create_node_with_content_type']) {
    if (!$type = node_type_load($content_type)) {
      return drush_set_error(dt("Can't find the content type @type.",
        array('@type' => $content_type)));
    }

    if (!$type->has_title) {
      return drush_set_error(dt("Content type @type has no title field.",
        array('@type' => $content_type)));
    }

    $required_fields = array(
      'field_description',
      'field_cdm_alias',
      'field_thumbnail',
    );
    $fields = field_info_instances('node', $content_type);
    foreach ($required_fields as $required_field) {
      if (!array_key_exists($required_field, $fields)) {
        return drush_set_error(dt("Content type @type has no @field field.",
          array('@type' => $content_type, '@field' => $required_field)));
      }
    }
  }

  $collections_data = file($params['input']);
  foreach ($collections_data as $collection_config) {
    $data = explode("\t", $collection_config);
    islandora_migrate_cdm_collections_ingest_collection($params['namespace'], $params['parent'], $params['input'], $data);
    if ($params['create_node_with_content_type']) {
      islandora_migrate_cdm_collections_ingest_node($params['input'], $data, $content_type);
    }
  }
}
