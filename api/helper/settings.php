<?php 

/**
 * Represent the class that will manage the provisioner's settings
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

class helper_settings {
    protected static $_instance = null;
    private $_objSettings = null;
    private $_file;

    private function __construct($filepath = 'config.json') {
        $this->_file = PROVISIONER_BASE . $filepath;
        $this->_objSettings = json_decode(file_get_contents($this->_file));
        
        $error = helper_utils::json_errors();
        if ($error) {
            echo $error;
            exit();
        }
    }
    
    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance =  new helper_settings();
        }
        return self::$_instance;
    }
    
    public function get_settings() {
        return $this->_objSettings;
    }
    
    public function set_settings($settings) {
        $this->_objSettings = $settings;
    }
    
    public function write() {
        file_put_contents($this->_file, json_encode($this->_objSettings, JSON_PRETTY_PRINT));
    }
}