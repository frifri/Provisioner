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
	private $_db;

	function __construct() {
		// First load the settings
		$this->_settings = helper_utils::get_settings();

		// Logger
		$this->_log = KLogger::instance(LOGS_BASE, Klogger::DEBUG);

		// Bigcouch_wrapper
		$this->_db = new wrapper_bigcouch();
	}

	/**
	 * This will handle the options requests
	 *
	 * @url OPTIONS /generate
	 */

	function options() {
		return;
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
	 * @class  AccessControlKazoo {@requires account}
	 */
	function generate_files($request_data) {
		$this->_log->logInfo(' - Entering generate_files (/generate) - ');
		$request_data = $request_data['data'];
		$mac_address = strtolower($request_data['mac_address']);

		$this->_log->logDebug("Loading adapter");
		$adapter_name = "adapter_" . $this->_settings->adapter . "_adapter";
		$adapter = new $adapter_name();

		// So here we are going to retrieve the provider_id
		// First, let's get the account.. The provider_id should be there
                $mac_lookup_db = helper_utils::get_mac_lookup_db();
		$account_id = $this->_db->get($mac_lookup_db, $mac_address)['account_id'];
		$account_db = helper_utils::get_account_db($account_id);
		$provider_id = $this->_db->get($account_db, $account_id)['provider_id'];

		// This grab the settings from whatever datasource you want.
		// Can set the settings to nothing if the only settings that you need
		// to use are comming from the payload of this API
		$this->_log->logDebug("Loading the config_manager...");
		$config_manager = $adapter->get_config_manager(
			$provider_id,
			$mac_address);

		$this->_log->logDebug("Now importing custom settings...");
		$settings = $request_data['settings'];
		$config_manager->import_settings($settings);
		
		if ($config_manager->generate_config_files())
			return array('status' => 'success');

		return array('status' => 'error', 'message' => 'Could not write one or more configuration files');
	}
}