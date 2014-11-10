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
        'required' => 'false',
        'value' => 'optional',
      ),
      'parent' => array(
        'description' => 'The collection to which the new collections should ' .
        'be added. Defaults to the root Islandora repository PID.',
        'required' => 'false',
        'value' => 'optional',
      ),
      'input' => array(
        'description' => 'The absolute path to the tab-delimited file generated ' .
        'by the get_collection_data.php script.',
        'required' => 'true',
        'value' => 'required',
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
  );
  $collections_data = file($params['input']);
  foreach ($collections_data as $collection_config) {
    $data = explode("\t", $collection_config);
    islandora_migrate_cdm_collections_ingest_collection($params['namespace'], $params['parent'], $params['input'], $data);
  }
}
