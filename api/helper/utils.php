<?php

/**
 * This file contains utilities functions
 *
 * @author Francis Genet
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

require_once LIB_BASE . 'KLogger.php';

class helper_utils {
    public static function get_settings() {
        return helper_settings::get_instance()->get_settings();
    }

    public static function get_mac_lookup_db() {
        return self::get_settings()->database->mac_lookup_db;
    }
    
    public static function get_providers_db() {
        return self::get_settings()->database->providers_db;
    }

    public static function get_factory_defaults_db() {
        return self::get_settings()->database->factory_defaults_db;
    }

    public static function get_system_account_db() {
        return self::get_settings()->database->system_account_db;
    }

    /**
     * Extract the mac address from a User Agent or URI
     *
     * @author	frifri
     * @param	string	$ua	User Agent
     * @param	string	$uri	URI
     * @return	mixed	The Extracted Address or False
     */
    public static function get_mac_address($ua, $uri) {
        // Let's check in the User-Agent
        if (!preg_match("/linksys|cisco/i", $ua) && preg_match("#[0-9a-fA-F]{2}(?=([:-]?))(?:\\1[0-9a-fA-F]{2}){5}#", $ua, $match_result))
            // need to return the mac address without the ':'
            return strtolower(preg_replace('/[:-]/', '', $match_result[0]));
        else 
            $requested_file = self::strip_uri($uri);
        
        if (preg_match("#[0-9a-fA-F]{12}#", $requested_file, $match_result))
            return strtolower($match_result[0]);
        else 
            return false;
    }

    /**
    * Will return the host and only that
    *
    * Removes 'www.' and port
    *
    * @author   frifri
    * @param    string  $http_host  The HTTP Host
    * @return   string  HTTP HOST without www. and port
    */
    public static function get_provider_domain($http_host) {
        $host = preg_replace("#:\d*$#", '', preg_replace("/^www\./", '', $http_host));
        return $host;
    }

    /**
    * Will return the raw account_id from the URI
    *
    * @author   frifri
    * @param    string  $uri    The URI
    * @return   mixed   The account ID
    */
    public static function get_account_id($uri) {
        if (preg_match("#\/([0-9a-f]{32})\/#", $uri, $match_result))
            return $match_result[1];
        else
            return false;
    }

    /**
    * Will return the formatted account_id from the raw account_id
    *
    * @author   frifri
    * @param    string  $account_id The Raw Account ID
    * @return   mixed   formatted account_id
    */
    public static function get_account_db($uri) {
        // making sure that $account_id is well formed
        $account_id = get_account_id($uri);
        if ($account_id) {
            // account/xx/xx/xxxxxxxxxxxxxxxx
            $account_db = "account/" . substr_replace(substr_replace($account_id, '/', 2, 0), '/', 5, 0);
            $suffix = self::get_settings()->database->account_db_suffix;
            if (!empty($suffix)) {
                return $account_db . '-' . $suffix;
            }
            return $account_db;
        }
        return false;
    }

    /**
    * Get the Brand extra data from the brand json file
    *
    * @author   frifri
    * @param    string  $brand  The brand name (yealink, cisco, polycom)
    * @return   $array  the decoded settings from inside brand_data.json
    */
    private static function _get_brand_data($brand) {
        if ($brand) {
            $base_folder = MODULES_DIR . $brand . "/";
            if(!file_exists($base_folder . "brand_data.json"))
                throw new Exception('Missing:'.$base_folder . "brand_data.json");
            return json_decode(file_get_contents($base_folder . "brand_data.json"), true);
        } else 
            return false;
    }

    /**
    * Get the family folder for a brand and model
    *
    * @author   frifri
    * @param    string  $brand  The brand name (yealink, cisco, polycom)
    * @param    string  $model  The model name (t26, 7960, 501)
    * @return   string  the folder location
    */
    public static function get_folder($brand, $model) {
        if ($brand && $model) {
            $brand_data = self::_get_brand_data($brand);
            $folder = $brand_data[$model]["folder"];

            if ($folder)
                return $folder;
            else 
                return false;
        } else
            return true;
    }

    /**
    * Get List of Configuration files to be generated
    *
    * @author   frifri
    * @param    string  $brand  The brand name (yealink, cisco, polycom)
    * @param    string  $model  The model name (t26, 7960, 501)
    * @return   array   the configuration files that need to be generated
    */
    public static function get_file_list($brand, $model) {
        if ($brand && $model) {
            $files = json_decode(file_get_contents(MODULES_DIR . $brand . "/brand_data.json"), true);
            $config_files = $files[$model]["config_files"];
            
            if ($config_files)
                return $config_files;
            else 
                return false;
        } else
            return false;
    }

    /**
    * Get the Regular Expressions for matching web requests
    *
    * @author   frifri
    * @param    string  $brand  The brand name (yealink, cisco, polycom)
    * @param    string  $model  The model name (t26, 7960, 501)
    * @return   array   the list of regular expressions
    */
    public static function get_regex_list($brand, $model) {
        if ($brand && $model) {
            $files = json_decode(file_get_contents(MODULES_DIR . $brand . "/brand_data.json"), true);
            $regexs = $files[$model]["regexs"];
            
            if ($regexs)
                return $regexs;
            else 
                return false;
        } else
            return false;
    }

    /**
    * Get The JSON Error code (IF any) of preceding json_decode command
    *
    * @author   tm1000
    * @return   string  the error code
    */
    public static function json_errors() {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return false;
            break;
            case JSON_ERROR_DEPTH:
                return ' - Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                return ' - Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                return ' - Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                return ' - Syntax error, malformed JSON (check the comas)';
            break;
            case JSON_ERROR_UTF8:
                return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                return ' - Unknown error';
            break;
        }
    }

    /**
    * Get Couch doc name from the brand/family/model
    *
    * @author   Francis Genet
    * @param    string   $brand       The device brand
    * @param    string   $family      The device family
    * @param    string   $model       The device model
    * @return   string   The document name
    */
    public static function get_device_doc_name($brand, $family = null, $model = null) {
        if ($model)
            return $brand . "_" . $family . "_" . $model;
        elseif ($family)
            return $brand . "_" . $family;
        elseif ($brand)
            return $brand;
        else
            return false;
    }

    public static function get_param($array, $key, $default) {
        if (!isset($array[$key]))
            return $default;
        else
            return $array[$key];
    }

    /**
    * Get the Regular Expressions for matching web requests
    *
    * @author   ?
    * @param    array   $array       An array
    * @return   object  the object that was an array
    */
    public static function array_to_object($array) {
        $obj = new stdClass;
        foreach($array as $key => $value) {
            if(is_array($value))
                $obj->{$key} = self::array_to_object($value);
            else
                $obj->{$key} = $value;
        }
        return $obj;
    }

    /**
    * Get the Regular Expressions for matching web requests
    *
    * @author   ?
    * @param    object  $data       An object?
    * @return   array   the array that was an object
    */
    public static function object_to_array($data) {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::object_to_array($value);
            }
            return $result;
        }
        return $data;
    }
}

