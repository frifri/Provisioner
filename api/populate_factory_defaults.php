<?php 

/**
 * This file will configure your factory_defaults database.
 * It MUST be run before using the provisioner
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

require_once 'bootstrap.php';

require_once LIB_BASE . '/php_on_couch/couch.php';
require_once LIB_BASE . '/php_on_couch/couchClient.php';
require_once LIB_BASE . '/php_on_couch/couchDocument.php';

define('CONFIG_FILE', PROVISIONER_BASE . 'config.json');

// Loading config file
$configs = json_decode(file_get_contents(CONFIG_FILE));

if (!$configs)
    die("Could not load the config file\n");

$server_url = $configs->database->url . ":" . $configs->database->port;

if (strtolower($configs->database->type) == "bigcouch") {
    if (strlen($configs->database->username) && strlen($configs->database->password)) {
        $server_url = str_replace('http://', '', $server_url);
        $credentials = $configs->database->username . ':' . $configs->database->password . '@';
        $server_url = 'http://' . $credentials . $server_url;
    }

    // Providers
    // =========

    // Creating the database
    $couch_client = new couchClient($server_url, $configs->db_prefix . "factory_defaults");

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();

    $list = array(
        "yealink" => array(
            "t3x" => array(
                "t32", "t36"
            ),
            "t4x" => array(
                "t46", "t48"
            )
        ),
        "polycom" => array(
            "spipm" => array(
                "550", "335"
            )
        )
    );

    foreach ($list as $brand => $families) {
        // The doc that will be used for every brand
        $doc = new stdClass();
        $doc->settings = array();

        // Yealink first... Duh!
        $doc->_id = $brand;
        $doc->brand = $brand;
        $doc->pvt_type = "brand";
        $couch_client->storeDoc($doc);

        foreach ($families as $family => $models) {
            $doc->_id = $brand . "_" . $family;
            $doc->family = $family;
            $doc->pvt_type = "family";
            $couch_client->storeDoc($doc);

            foreach ($models as $model) {
                $doc->_id = $brand . "_" . $family . "_" . $model;
                $doc->model = $model;
                $doc->pvt_type = "model";
                $couch_client->storeDoc($doc);
            }
        }
    }

    // OK, this is lame... But better than nothing.
    // TODO: put an ugly ASCII art right here
    echo "=========================== \n";
    echo "SUCCESS!\n";
    echo "=========================== \n";
}

 ?>