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
      'cdmdata' => dt('The absolute filesystem path to the collection data exported from CONTENTdm.'),
    ),
    'required-arguments' => TRUE,
    'description' => dt('Creates Islandora collections using the specified data exported from CONTENTdm.'),
    'examples' => array(
      'Standard example' => 'drush --user=fedoraAdmin create-islandora-collections-from-cdm /tmp/cdmdata',
      'Alias example' => 'drush --user=fedoraAdmin cicfc /tmp/cdmdata',
    ),
    'aliases' => array('cicfc'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  return $items;
}

/**
 * Callback function for drush create-islandora-collections-from-cdm.
 */
function drush_islandora_migrate_cdm_collections_create_islandora_collections_from_cdm($cdmdata = NULL) {
  drush_print("Hi from drush, using data from " . $cdmdata);
}
