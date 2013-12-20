<?php 

/**
 * All methods in this class are protected - Some more than others
 * Brand/family/model APIs
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

require_once LIB_BASE . 'KLogger.php';

class Files {
    private $_settings;
    private $_log;

    function __construct() {
        // First load the settings
        $this->_settings = helper_settings::get_instance();

        // Logger
        $this->_log = KLogger::instance(LOGS_BASE, Klogger::DEBUG);
    }

    /**
     *  This API will generate the config files for a given phone.
     *  The idea is to merge the data from whatever datasource
     *  and from the settings given to this API.
     *  The settings sent through the APis will always override
     *  the settings from the datasource.
     *
     * @url POST /generate
     * @access protected
     * @class  AccessControlKazoo
     */
    function generate_files($request_data) {
        $this->_log->logInfo(' - Entering generate_files (/generate) - ');

        $this->_log->logDebug("Loading adapter");
        $adapter_name = "adapter_" . $this->_settings->adapter . "_adapter";
        $adapter = new $adapter_name();

        // This grab the settings from whatever datasource you want.
        // Can set the settings to nothing if the only settings that you need
        // to use are comming from the payload of this API
        $this->_log->logDebug("Loading the config_manager...");
        $config_manager = $adapter->get_config_manager(
            $request_data['provider_id'],
            strtolower($request_data['mac']), 
            $request_data['brand'], 
            $request_data['model']
        );

        $this->_log->logDebug("Now importing custom settings...");
        $settings = $request_data['settings'];
        $config_manager->import_settings($settings);
        
        if ($config_manager->generate_config_files())
            return array('status' => 'success');

        return array('status' => 'error', 'message' => 'Could not write one or more configuration files');
    }
}