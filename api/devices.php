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

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url GET /{account_id}
     * @access protected
     * @class  AccessControlKazoo
     */

    function retrieve_list($account_id) {
        $result = array();
        $account_db = helper_utils::get_account_db($account_id);

        // Let's first try of the account that we are trying to delete exist
        if ($this->_db->isDBexist($account_db)) {
            $doc_list = $this->_db->getAll($account_db);

            // We get the document list inside of the account database
            foreach ($doc_list['rows'] as $doc) {
                // Check the id of the document to know if it is a device doc or the account doc
                if (preg_match("/^[a-f0-9]{12}$/i", $doc['id'])) {
                    $result[] = $doc['id'];
                }
            }
        }

        return array('data' => $result);
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url GET /{account_id}/{mac_address}
     * @access protected
     * @class  AccessControlKazoo
     */

    function get_device($account_id, $mac_address) {
        $account_db = helper_utils::get_account_db($account_id);
        if (!preg_match("/^[a-f0-9]{12}$/i", $mac_address))
            throw new RestException(400, 'This is not a valid mac_address');
        
        $device_doc = $this->_db->get($account_db, $mac_address);
        if (!$device_doc)
            throw new RestException(500, "Could not retrieve the device. Are you sure it exist?");

        return array('data' => $device_doc);            
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url PUT /{account_id}/{mac_address}
     * @access protected
     * @class  AccessControlKazoo
     */

    function add_device($request_data, $account_id, $mac_address) {
        $account_db = helper_utils::get_account_db($account_id);
        if (!preg_match("/^[a-f0-9]{12}$/i", $mac_address))
            throw new RestException(400, 'This is not a valid mac_address');

        if (!isset($request_data['brand']) or !isset($request_data['family']) or !isset($request_data['model']))
            throw new RestException(400, "You need to specify the brand, the family and the model");
            
        $object_ready = $this->_db->prepare_add_device($request_data, $mac_address);
        if ($this->_db->add($account_db, $object_ready)) {
            $lookup_obj = array(
                '_id' => $mac_address,
                'account_id' => $account_id);
            if ($this->_db->isDocExist('mac_lookup', $mac_address) or !$this->_db->add('mac_lookup', $lookup_obj)) {
                // We then need to delete the newly created document
                $this->_db->delete($account_db, $mac_address);
                throw new RestException(500, "Failed to create the mac_lookup entry. Did not create the device");
            }

            return array('status' => 'sucess', 'message' => 'Device added successfully');
        } else
            throw new RestException(500, "Failed to create the device object in the account db");
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url POST /{account_id}/{mac_address}
     * @access protected
     * @class  AccessControlKazoo
     */

    function update_device($request_data, $account_id, $mac_address) {
        $account_db = helper_utils::get_account_db($account_id);
        if (!preg_match("/^[a-f0-9]{12}$/i", $mac_address))
            throw new RestException(400, 'This is not a valid mac_address');

        foreach ($request_data as $key => $value) {
            if (!$this->_db->update($account_db, $mac_address, $key, $value));
                throw new RestException(500, "Error while updating the doc");
        }

        return array('status' => 'sucess', 'message' => 'Device updated successfully');
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url DELETE /{account_id}/{mac_address}
     * @access protected
     * @class  AccessControlKazoo
     */

    function delete_device($account_id, $mac_address) {
        $account_db = helper_utils::get_account_db($account_id);
        if (!preg_match("/^[a-f0-9]{12}$/i", $mac_address))
            throw new RestException(400, 'This is not a valid mac_address');

        if ($this->_db->delete('mac_lookup', $mac_address)) {
            if (!$this->_db->delete($account_db, $mac_address))
                throw new RestException(500, "Could not delete the device doc in the account db");
            else
                return array('status' => 'sucess', 'message' => 'Device deleted successfully');
        } else
            throw new RestException(500, "Could not delete the mac lookup entry");
            
    }
}