<?php 

/**
 * This file will configure your database.
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

$settings = helper_utils::get_settings();

$server_url = $settings->database->url . ":" . $settings->database->port;

if (strtolower($settings->database->type) == "bigcouch") {
    if (strlen($settings->database->username) && strlen($settings->database->password)) {
        $server_url = str_replace('http://', '', $server_url);
        $credentials = $settings->database->username . ':' . $settings->database->password . '@';
        $server_url = 'http://' . $credentials . $server_url;
    }

    // Providers
    // =========

    // Creating the database
    $providers_db = helper_utils::get_providers_db();
    $couch_client = new couchClient($server_url, $providers_db);

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();

    // Creating the master account
    $provider = $settings->database->master_provider;

    $provider_doc = new stdClass();
    $provider_doc->name = $provider->name;
    $provider_doc->authorized_ip = $provider->ip;
    $provider_doc->domain = $provider->domain;
    $provider_doc->default_account_id = null;
    $provider_doc->pvt_access_type = "admin";
    $provider_doc->pvt_type = "provider";
    $provider_doc->settings = null;

    $provider_view = new stdCLass();
    $provider_view->_id = "_design/" . $providers_db;
    $provider_view->language = "javascript";

    $view = new stdCLass();
    // by domain
    $view->{"list_by_domain"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'provider') return; emit(doc.domain, {'id': doc._id, 'name': doc.name, 'domain' : doc.domain , 'default_account_id' : doc.default_account_id, 'settings': doc.settings}); }"
    );

    // by ip
    $view->{"list_by_ip"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'provider') return; for (i in doc.authorized_ip) {emit(doc.authorized_ip[i], {'access_type': doc.pvt_access_type})}; }"
    );

    $provider_view->views = $view;

    try {
        $couch_client->storeDoc($provider_doc);
        $couch_client->storeDoc($provider_view);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }

    // Factory defaults
    // ================

    // Creating the database
    $factory_defaults_db = helper_utils::get_factory_defaults_db();
    $couch_client->useDatabase($factory_defaults_db);

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();


    // Creating the views
    $factory_view = new stdCLass();
    $factory_view->_id = "_design/" . $factory_defaults_db;
    $factory_view->language = "javascript";

    // reset
    $view = new stdCLass();
    // By brand
    $view->{"list_by_brand"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'brand') return; emit(doc.brand, {'id': doc._id, 'name': doc.brand, 'settings' : doc.settings}); }"
    );

    // By family
    $view->{"list_by_family"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'family') return; emit([doc.brand,doc.family], {'id': doc._id, 'name': doc.family, 'settings' : doc.settings}); }"
    );

    // By model
    $view->{"list_by_model"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'model') return; emit([doc.family,doc.model], {'id': doc._id, 'name': doc.model, 'settings' : doc.settings}); }"
    );

    // Get All
    $view->{"list_by_all"} = array(
        "map" => "function(doc) { emit([doc.brand,doc.family,doc.model], {'id': doc._id, 'settings': doc.settings}); }"
    );
    $factory_view->views = $view;

    try {
        $couch_client->storeDoc($factory_view);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }

    // System Account settings
    // =======================

    // Creating the database
    $system_account_db = helper_utils::get_system_account_db();
    $couch_client->useDatabase($system_account_db);

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();

    // Creating the documents 
    $system_global_doc = new stdCLass();
    $system_global_doc->_id = "global_settings";
    $system_global_doc->settings = null;

    $system_manual_doc = new stdClass();
    $system_manual_doc->_id = "manual_provisioning";
    $system_manual_doc->settings = null;

    try {
        $couch_client->storeDoc($system_global_doc);
        $couch_client->storeDoc($system_manual_doc);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }

    // Mac Lookup Database
    // ===================

    // Creating the database
    $mac_lookup_db = helper_utils::get_mac_lookup_db();
    $couch_client->useDatabase($mac_lookup_db);

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();

    // OK, this is lame... But better than nothing.
    // TODO: put an ugly ASCII art right here
    echo "=========================== \n";
    echo "SUCCESS!\n";
    echo "=========================== \n";
}