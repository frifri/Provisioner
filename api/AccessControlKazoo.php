<?php 

/**
 * This file contains The AccessControl class
 * It is used to determine wether or not you are allowed to access an API
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.4.0
 */

class AccessControlKazoo implements iAuthenticate {
    public static $requires = 'account';
    public static $role = 'account';
    private $_settings;

    private function get($uri, $data) {
        $url = $this->_settings->hz2600->api_url . $uri;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        return json_decode($output);
    }

    function __isAllowed() {
        //return true;
        $account_id = null;

        $current_uri = $_SERVER['REQUEST_URI'];
        if (preg_match("/\/(devices|accounts)\/([a-f0-9]{32})/", $current_uri, $match))
            $account_id = $match[2];

        $db = new wrapper_bigcouch();
        $this->_settings  = helper_utils::get_settings();
        $providers_db = helper_utils::get_providers_db();

        $is_reseller = false;

        $auth_token = getallheaders()['X-Auth-Token'];

        if ($account_id)
            $account_info = $this->get("/accounts/$account_id/apps_link/authorize?auth_token=$auth_token", array());
        else 
            $account_info = $this->get("/apps_link/authorize?auth_token=$auth_token", array());

        if ($account_info->status == 'error')
            return false;

        if (!$account_info->data->is_reseller) {
            $account_db = helper_utils::get_account_db($account_info->data->account_id);

            if (!$account_db)
                return false;

            // If the creation occurs, then we need to create the doc too
            if ($db->createDatabase($account_db)) {
                $data = array(
                    "_id" => $account_info->data->account_id,
                    "name" => $account_info->data->account_name,
                    "provider_id" => $account_info->data->reseller_id,
                    "settings" => []
                    );

                $db->add($account_db, $data);

                // Let's now test if The provider for this account exist
                if (!$db->isDocExist($providers_db, $account_info->data->reseller_id)) {
                    $data = array(
                        "_id" => $account_info->data->reseller_id,
                        "accounts" => array($account_info->data->account_id),
                        "authorize_ip" => $this->_settings->database->master_provider->ip,
                        "domain" => $this->_settings->database->master_provider->domain,
                        "name" => "Default Name",
                        "pvt_access_type" => "user",
                        "pvt_type" => "provider",
                        "settings" => array()
                        );

                    $db->add($providers_db, $data);

                } else {
                    // We need to add the account to the account list
                    $doc = $db->get($providers_db, $account_info->data->reseller_id);
                    $doc['accounts'][] = $account_info->data->account_id;
                    $db->update($providers_db, $account_info->data->reseller_id, 'accounts', $doc['accounts']);
                }
            }
        } else {
            static::$role = 'provider';
            // The current account is a reseller
            // Let's check if the account exist
            if (!$db->isDocExist($providers_db, $account_info->data->account_id)) {
                // Then create it
                $data = array(
                    "_id" => $account_info->data->account_id,
                    "accounts" => array(),
                    "authorize_ip" => $this->_settings->database->master_provider->ip,
                    "domain" => $this->_settings->database->master_provider->domain,
                    "name" => "Default Name",
                    "pvt_access_type" => "user",
                    "pvt_type" => "provider",
                    "settings" => array()
                    );

                $db->add($providers_db, $data);
            }
        }

        return static::$requires == static::$role || static::$role == 'provider';
    }
}