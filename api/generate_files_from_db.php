<?php 

require_once 'bootstrap.php' ;

$result = true;

// Load the settings
$settings = helper_settings::get_instance();
// Load the adapter 
// I agree, the class name is weird. Deal with it
$adapter = new adapter_2600hz_adapter();
// Load the db wrapper
$db = new wrapper_bigcouch();
$all_mac_docs = $db->getAll('mac_lookup');

foreach ($all_mac_docs['rows'] as $mac_doc) {
    $cur_mac = $mac_doc['id'];
    $mac_doc = $db->get('mac_lookup', $cur_mac);
    $account_db = helper_utils::get_account_db($mac_doc['account_id']);

    $account_doc = $db->get($account_db, $mac_doc['account_id']);
    $provider_id = $account_doc['provider_id'];

    $device_doc = $db->get($account_db, $cur_mac);
    // Device info
    $brand = $device_doc['brand'];
    $model = $device_doc['model'];

    $config_manager = $adapter->get_config_manager(
        $provider_id,
        $cur_mac, 
        $brand, 
        $model
    );

    if (!$config_manager->generate_config_files())
        $result = False;
}

if ($result)
    print("Success!\n");