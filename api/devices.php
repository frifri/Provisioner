<?php 

/**
 * Devices APIs
 * CRUD for devices on the system
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

class Devices {
    private $_db;

    function __construct() {
        $this->_db = new wrapper_bigcouch();
    }
}