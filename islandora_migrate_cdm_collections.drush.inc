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
    'arguments' => array(
      'cdmdata' => dt('The absolute path to the tab-separated file containing collection data exported from CONTENTdm.'),
    ),
    'required-arguments' => TRUE,
    'description' => dt('Creates Islandora collections using the specified data exported from CONTENTdm.'),
    'examples' => array(
      'Standard example' => 'drush --user=fedoraAdmin create-islandora-collections-from-cdm /tmp/cdmcollectiondata/collection_data.tsv',
      'Alias example' => 'drush --user=fedoraAdmin cicfc /tmp/cdmcollectiondata/collection_data.tsv',
    ),
    'aliases' => array('cicfcc'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  return $items;
}

/**
 * Callback function for drush create-islandora-collections-from-cdm.
 */
function drush_islandora_migrate_cdm_collections_create_islandora_collections_from_cdm($input_path = NULL) {
  $collections_data = file($input_path);
  foreach ($collection_data as $collection_config) {
    $data = explode("\t", $collection_config);
    islandora_migrate_cdm_collections_ingest_collection('islandora', $data);
  }
}
