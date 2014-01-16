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
            "t1x" => array(
                "t18"
            ),
            "t2x" => array(
                "t20", "t22", "t26", "t28"
            ),
            "t3x" => array(
                "t32", "t38" 
            ),
            "t4x" => array(
                "t46", "t42"
            ),
            "wxx" => array(
                "w52"
            )
        ),
        "polycom" => array(
            "spipm" => array(
                "321", "331", "335", "450", "550", "560", "650", "670", "1500", "6000", "7000", "vvx300", "vvx400", "vvx500", "vvx600"
            ),
            "firmware325" => array(
                "430"
            ),
            "firmware3333" => array(
                "320", "330"
            ),
            "splm" => array(
                "301", "501", "600", "601", "4000"
            ),
            "spom" => array(
                "300", "500"
            )
        ),
        "snom" => array(
            "7xx" => array(
                "710", "720", "760"
            )
        ),
        "cisco" => array(
            "spa" => array(
                "901", "921", "922", "941", "942", "962"
            ),
            "spa5xx" => array(
                "303", "501g", "502g", "504g", "525g", "525g2"
            )
        ),
        "audiocodes" => array(
            "4xx" => array(
                "420"
            )
        ),
        "mocet" => array(
            "3xxx" => array(
                "3062", "3092"
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