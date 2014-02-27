<?php

/**
 * Represent the config file class that will send back the JSON payload
 * used to draw the UI.
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.4.0
 */

class Ui {
    private $_db;
    private $_twig;

    // Initialize the Twig object
    private function _twig_init() {
        $loader = new Twig_Loader_String();
        $this->_twig = new Twig_Environment($loader);
    }
    
    function __construct() {
        $this->_db = new wrapper_bigcouch();
        $this->_twig_init();
    }

    /**
     *  The Options request handler
     *
     * @url OPTIONS /{brand}/{model}
     */
    function options() {
        return;
    }

    /**
     * Will send back a default template for a brand / model
     *
     * @url GET /{brand}/{model}
     */
    function get_default_template($request_data, $brand, $model) {
        $folder = helper_utils::get_folder($brand, $model);
        $factory_defaults_db = helper_utils::get_factory_defaults_db();

        // Global template
        $global_tmpl = $this->_db->get($factory_defaults_db, 'global')['template'];

        // Brand doc
        $doc_name = helper_utils::get_device_doc_name($brand);
        $brand_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // Family doc
        $doc_name = helper_utils::get_device_doc_name($brand, $folder);
        $family_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // Model doc
        $doc_name = helper_utils::get_device_doc_name($brand, $folder, $model);
        $model_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // This kind of merge will override any values coming before
        $merged_object = array_replace_recursive($global_tmpl, $brand_tmpl, $family_tmpl, $model_tmpl);

        return $merged_object;
    }

    /**
     * Will send back the template for a specific device
     *
     * @url GET /{account_id}/{mac_address}
     * @access protected
     * @class  AccessControlKazoo
     */
    function get_specific_template($request_data, $account_id, $mac_address) {
        $account_db = helper_utils::get_account_db($account_id);
        $device_doc = $this->_db->get($account_db, $mac_address);
        $factory_defaults_db = helper_utils::get_factory_defaults_db();

        $param = array();
        $param['lines'][0]['label'] = 'test';

        $brand = $device_doc['brand'];
        $family = $device_doc['family'];
        $model = $device_doc['model'];

        // Global template
        $global_tmpl = $this->_db->get($factory_defaults_db, 'global')['template'];

        // Brand doc
        $doc_name = helper_utils::get_device_doc_name($brand);
        $brand_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // Family doc
        $doc_name = helper_utils::get_device_doc_name($brand, $family);
        $family_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // Model doc
        $doc_name = helper_utils::get_device_doc_name($brand, $family, $model);
        $model_tmpl = $this->_db->get($factory_defaults_db, $doc_name)['template'];

        // This kind of merge will override any values coming before
        $merged_object = array_replace_recursive($global_tmpl, $brand_tmpl, $family_tmpl, $model_tmpl);

        //return json_encode($merged_object);
        //print_r(array('lines' => array(array('label' => 'test'))));
        //return $merged_object;

        return json_decode($this->_twig->render(json_encode($merged_object), array('lines' => array(array('label' => 'test')))));
    }
}