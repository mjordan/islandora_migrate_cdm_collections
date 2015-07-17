<?php
/**
 * @file
 * Script to parse CONTENTdm collection configuration files to get the collection alias,
 * title, description, and thumbnail URL. To avoid having to run a scirpt on the server,
 * we would scrapte this data is from the CONTENTdm index page, but that page is not
 * scrapable. Also, the CONTENTdm Web API doesn't provide access to the description or
 * collection thumbnail path.
 *
 * Purpose of this script is to create output used by the Islandora CONTENTdm Collection
 * Migrator module (https://github.com/mjordan/islandora_migrate_cdm_collections).
 *
 * Usage: If you have shell access to your CONTENdm server, upload this script to the server,
 * set the $method variable to 'local', configure $collection_data_base_dir, $public_html_base_dir,
 * $output_directory, and $default_locale, and then run the following command:
 *
 * php get_collection_data.php
 *
 * After the script finishes running, you will find your collections' configuration data in the
 * directory specified in $output_dir on your CONTENTdm server. Instructions for what to do with
 * this data are available in the README.md file for the Islandora CONTENTdm Collection Migrator
 * module.
 *
 * If you do not have command-line access to your CONTENTdm server, you can still generate a list
 * of collection titles via the CONTENTdm Web API. But that's the only collection configuration
 * data you can get (no descriptions, no thumbnails). To use the script, upload this script to any
 * server running PHP (or run it on a desk/laptop running PHP), set the $method variable to 'api',
 * configure the $output_dir variable uncomment and configure the $contentdm_api_base_url variable
 * below, and run the following command:
 *
 * php get_collection_data.php
 *
 * After the script finishes running, you will find your collections' configuration data in the
 * directory specified in $output_dir on the machine running the script.
 *
 * For both the 'local' and 'api' methods, you have the option of fetching each collection's
 * field configuration info. If you choose this option, each of the Islandora collection
 * objects created by the Drush script will have a datastream with the DSID 'CDMFIELDINFO'.
 * This datastream is not required but it will contain a snapshot, in JSON format, of the
 * collection's metadata configuration, which may prove useful in your migration process.
 */

// Set to 'api' if you can't run this script on your CONTENTdm server.
$method = 'local';

/**
 * If you are running this script on the CONTENTdm server (that is, $method = 'local'),
 * you will need to configure the following variables to match paths on your server.
 */
$collection_data_base_dir = '/usr/local/Content6/Website/public_html/ui/custom/default/collection';
$public_html_base_dir = '/usr/local/Content6/Website/public_html';
$output_dir = '/tmp/collecions';
$default_locale = 'en_US';

/**
 * If you don't have shell access to your CONTENTdm server, you can still export a
 * list of collection titles by using the CONTENTdm Web API. Set $method above to 'api',
 * uncomment the $contentdm_api_base_url line below and set that variable to your
 * CONTENTdm instance's Web API base URL. Also,uncomment and configure $output_dir to
 * point to a location on the machine running the script, where the output will be stored.
 */
// $contentdm_api_base_url = 'http://yourcontentdmserver.example.com:81/dmwebservices/index.php';
// $output_dir = '/tmp/collections';

/**
 * If you want to fetch each collection's field configuration to store as a datastream
 * in Islandora, set $get_collection_field_info to TRUE and uncomment and configure
 * $contentdm_api_base_url.
 */
$get_collection_field_info = FALSE;
// $contentdm_api_base_url = 'http://yourcontentdmserver.example.com:81/dmwebservices/index.php';

/**
 * Main script logic.
 */

// If not using the Web API (that is, $method is 'local'), loop through each collection's
// configuration directory and parse the configuration files.
if ($method == 'local') {
  $collection_dirs = scandir($collection_data_base_dir);
  // Get rid of . and .. directories.
  array_shift($collection_dirs);
  array_shift($collection_dirs);
  // Get rid of 'default' (which will always sort at the end).
  array_pop($collection_dirs);
  $output_records = array();

  foreach ($collection_dirs as $collection_dir) {
    $collection_data = array();
    if (is_dir($collection_data_base_dir . DIRECTORY_SEPARATOR . $collection_dir)) {
      $alias = preg_replace('/^coll_/', '', $collection_dir);
      $collection_data[] = $alias;
      $locale_file_path = $collection_data_base_dir . DIRECTORY_SEPARATOR . 'coll_' .  $alias .
        DIRECTORY_SEPARATOR . 'resources/languages/cdm_language_coll_' .  $alias .  ".xml";
      if (!file_exists($locale_file_path)) {
        print "Locale file $locale_file_path can't be found\n";
      }
      $collection_data[] = get_collection_title($locale_file_path, $default_locale);
      $collection_data[] = get_collection_description($locale_file_path, $default_locale);

      $ini_file_path = $collection_data_base_dir . DIRECTORY_SEPARATOR . 'coll_' .  $alias .
        DIRECTORY_SEPARATOR . "config/cdm_collection.ini";
      if (!file_exists($ini_file_path)) {
        print "Ini file $ini_file_path can't be found\n";
      }
      $collection_data[] = get_collection_thumbnail_path($ini_file_path);
    }
    $output_records[] = $collection_data;
  }
  write_output($output_records);
  print "Your collection data is in $output_dir on your CONTENTdm server.\n";
}

if ($method == 'api' && isset($contentdm_api_base_url)) {
  $output_records = get_collection_titles_via_api();
  write_output($output_records);
  print "Your collection data is in $output_dir on the computer running this script.\n";
}

/**
 * Functions.
 */

/**
 * Get the path to the collection's thumbnail image.
 *
 * @param string $ini_file_path
 *   The path to the current collection's ini file.
 *
 * @return string $thumbnail_path
 *   The path to the current collection's thumbnail image.
 */
function get_collection_thumbnail_path($ini_file_path) {
  $config = parse_ini_file($ini_file_path);
  if (array_key_exists('imageCarouselOffImageHomepage', $config)) {
    return $config['imageCarouselOffImageHomepage'];
  }
  else {
    return '';
  }
}

/**
 * Get the output of the CONTENTdm API dmGetCollectionFieldInfo function.
 *
 * @param string $contentdm_base_url
 *   The URL of the CONTENTdm server that is being queried.
 * @param string $alias
 *   The alias of the collection.
 *
 * @return string $collection_field_info
 *   A JSON string containing the response from the dmGetCollectionFieldInfo
 *   request.
 */
function get_collection_field_info($contentdm_api_url, $alias) {
  $ws_url = $contentdm_api_url . "?q=dmGetCollectionFieldInfo/$alias/json";
  $json = file_get_contents($ws_url);
  return $json;
}

/**
 * Get the collection's title form the collection's ini file.
 *
 * @param string $path
 *   The path to the current collection's Oliphant file.
 * @param string $locale
 *   The locale code.
 *
 * @return string $title_text
 *   The title in the language specified locale.
 */
function get_collection_title($path, $locale = 'en_US') {
  $xml = simplexml_load_file($path);
  // Assumes the title is always in the first <seg> element.
  $title = $xml->xpath('//body/tu[@tuid="SITE_CONFIG_title"]/tuv[@xml:lang="en_US"]/seg[1]');
  $title_text = (string) $title[0];
  // Remove line breaks so we can write out this data to
  // the tab-delimited file.
  $title_text = preg_replace("/\n/", '', $title_text);
  return $title_text;
}

/**
 * Get the collection's title form the collection's ini file.
 *
 * @return string $title_text
 *   An associative array containing alias => title pairs.
 */
function get_collection_titles_via_api() {
  global $contentdm_api_base_url;
  $collection_records = array();
  // Get the collection list from the API.
  $collection_list_json = file_get_contents($contentdm_api_base_url . '/dmwebservices/index.php?q=dmGetCollectionList/json');
  $collection_list = json_decode($collection_list_json, TRUE);
  foreach ($collection_list as $collection) {
    $alias = trim($collection['alias'], '/');
    $collection_records[] = array($alias, $collection['name']);
  }
  return $collection_records;
}

/**
 * Get the collection's description form the collection's ini file.
 *
 * @param string $path
 *   The path to the current collection's Oliphant file.
 * @param string $locale
 *   The locale code.
 *
 * @return string $description_text
 *   The description in the language specified locale.
 */
function get_collection_description($path, $locale = 'en_US') {
  $xml = simplexml_load_file($path);
  // Assumes the description is always in the first <seg> element.
  $description = $xml->xpath('//body/tu[@tuid="SITE_CONFIG_landingPageHtml"]/tuv[@xml:lang="en_US"]/seg[1]');
  $description_text = (string) $description[0];
  $description_text = preg_replace("/\n/", '', $description_text);
  return $description_text;
}

/**
 * Creates a directory for each collection and writes out its data.
 *
 * @param array $data
 *   An array of records, one per collection, each containing the alias, title,
 *   description, and path to the thumbnail image.
 */
function write_output($data) {
  global $output_dir;
  global $public_html_base_dir;
  global $get_collection_field_info;
  global $contentdm_api_base_url;
  if (!file_exists($output_dir)) {
    mkdir($output_dir);
  }

  foreach ($data as $collection) {
    // First, create a subdirectory for this collection under $output_dir, provided
    // $collection contains more than just an alias and a title
    if (count($collection) > 2) {
      $collection_output_dir = $output_dir . DIRECTORY_SEPARATOR . $collection[0];
      if (!file_exists($collection_output_dir)) {
        mkdir($collection_output_dir);
      }
    }

    // Copy the collection's thumbnail image to the output directory.
    if (count($collection) > 2 && strlen($collection[3])) {
      $thumbnail_path = $collection[3];
      $thumbnail_source_path = $public_html_base_dir . $collection[3];
      $thumbnail_path_parts = pathinfo($thumbnail_source_path);
      $thumbnail_dest_path = $collection_output_dir . DIRECTORY_SEPARATOR . $thumbnail_path_parts['basename'];
      copy($thumbnail_source_path, $thumbnail_dest_path);
      // Update the path to the thumbnail image, serialize the other data in the array,
      // and write it to a file in the output directory.
      $collection[3] = $thumbnail_path_parts['basename'];
    }

    // Fetch the collection's field configuration info.
    if (isset($contentdm_api_base_url)) {
      if ($get_collection_field_info) {
        global $contentdm_api_base_url;
        $datastream_filename = 'CDMFIELDINFO.json';
        $field_info_dest_path = $collection_output_dir . DIRECTORY_SEPARATOR . $datastream_filename;
        if ($field_info = get_collection_field_info($contentdm_api_base_url, $collection[0])) {
          file_put_contents($field_info_dest_path, $field_info);
        }
      }
    }

    // Write out the collection record to the tab-delimited file in the output directory.
    $tsv_output_file_path = $output_dir . DIRECTORY_SEPARATOR . 'collection_data.tsv';
    $serialized_collection_record = implode("\t", $collection) . "\n";
    file_put_contents($tsv_output_file_path, $serialized_collection_record, FILE_APPEND);
  }
}

?>
