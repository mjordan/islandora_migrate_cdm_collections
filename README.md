# Islandora CONTENTdm Collection Migrator [![Build Status](https://travis-ci.org/mjordan/islandora_migrate_cdm_collections.png?branch=7.x)](https://travis-ci.org/mjordan/islandora_migrate_cdm_collections)

A utility module for exporting collection configuration data from a CONTENTdm instance and then creating Islandora collections using this data. This module will only be of use to sites that are migrating from CONTENTdm to Islandora.

## Introduction

This module has two main parts: 1) a command-line PHP script that harvests configuration data for collections on a CONTENTdm server and 2) a drush script for creating collections in an Islandora instance using that data. The PHP script outputs the collection data into a tab-separated file so it can be modified prior to being used by the drush script.

Detailed instructions for running the PHP script are provided within the script itself, but in a nutshell, you configure a few variables and then run the script. If you have access to your CONTENTdm server's shell and run the script there, the resulting data will contain the title and description for each CONTENTdm collection, plus the collection's thumbnail image. If you don't have access to your CONTENTdm server's shell (e.g., your CONTENTdm is hosted by OCLC), you can run the script from any computer that has PHP installed, but the output will only contain the collection titles. In both cases, you run the script by issuing the following command:

```
php get_collection_data.php
```

Running this script on the CONTENTdm server's command line creates a file containing one tab-delimited row per collection, for example (with tabs represented here by [\t]):

```
ubcCtn[\t]Chinatown News[\t]<p>The <em>Chinatown News</em> was an English-language biweekly magazine.</p>[\t]chn2.jpg
```

Thumbnail images identified in the last field are copied into the output directory within subdirectories named after the collection alias (the value in the first field). If run outside the CONTENTdm server, using the CONTENTdm Web API, the lines in the file only contain the first two fields, and the thumbnail images are not copied: 
 
```
ubcCtn[\t]Chinatown News
```

Once you have copied the output from the script over to your Islandora server, you run the drush command on your Islandora server to create the collections identified in the output:

```
drush --user=admin create-islandora-collections-from-cdm --namespace=mynamespace --parent=mycollection:10  --input=/tmp/cdmcollectiondata/collection_data.tsv
```
or is short form:

```
drush --user=admin cicfc --namespace=mynamespace --parent=mycollection:10  --input=/tmp/cdmcollectiondata/collection_data.tsv
```

You are free to edit the output file before running it through the drush script as long as you don't change structure of the fields and don't add any line breaks.

If there are no thumbnail images in the collection data directory, or if the drush script can't find an image identified in the tab-delimited file (due to a mismatching filename, for example), the newly created collection is assigned the thumbnail image provided by the Islandora Collection Solution Pack.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/islandora/islandora)

## Current maintainer

* [Mark Jordan](https://github.com/mjordan)

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
