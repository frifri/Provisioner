<?php 

/**
 * Yealink Base File
 *
 * @author Andrew Nagy
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
class endpoint_yealink_base extends endpoint_base {
	public function __construct(&$config_manager) {
		parent::__construct($config_manager);
	}

	function prepareConfig() {
		parent::prepareConfig();

		//$constants = $this->config_manager->get_constants();
		$settings = $this->config_manager->get_settings();

		$original_timezone = $settings['other']['time']['timezone'];
		$settings['other']['time']['timezone'] = explode(' ', $original_timezone)[0];
		$settings['other']['time']['timezone_name'] = substr($original_timezone, strlen($settings['other']['time']['timezone']) + 1);

		$this->config_manager->set_settings($settings);
	}
	
	public function setFilename($strFilename) {
		$settings = $this->config_manager->get_settings();
		$constants = $this->config_manager->get_constants();

		if ($strFilename != 'phonebook1.xml') {
			$model = $this->config_manager->get_model();
			$strFilename = preg_replace('/\$suffix/', $constants['yealink']['suffixes'][$model], $strFilename);
			
			//Yealink likes lower case letters in its mac address
			$strFilename = preg_replace('/\$mac/', strtolower($this->config_manager->get_mac_address()), $strFilename);
		} else {
			$folder = CONFIG_FILES_BASE . '/directory/' . $this->config_manager->get_mac_address();
			if (!file_exists($folder))
				mkdir($folder);
			
			$strFilename = 'directory/' . $this->config_manager->get_mac_address() . '/phonebook1.xml';
		}

		return $strFilename;
	}
}