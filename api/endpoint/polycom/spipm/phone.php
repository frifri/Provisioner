<?php

/**
 * Phone Base File
 *
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
class endpoint_polycom_spipm_phone extends endpoint_polycom_base {
	public function __construct(&$config_manager) {
		parent::__construct($config_manager);
	}

	function prepareConfig() {
		parent::prepareConfig();
	
		$settings = $this->config_manager->get_settings();
		$settings['codecs']['audio'][$settings['codecs']['audio']['primary_codec']] = 1;
		$settings['codecs']['audio'][$settings['codecs']['audio']['secondary_codec']] = 2; 
		$settings['codecs']['audio'][$settings['codecs']['audio']['tertiary_codec']] = 3; 
		$settings['codecs']['audio'][$settings['codecs']['audio']['quaternary_codec']] = 4; 
		$this->config_manager->set_settings($settings);
	}
}
