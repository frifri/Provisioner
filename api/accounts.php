<?php 

/**
 * All methods in this class are protected
 * Accounts APIs
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

class Accounts {
    private $_db;

    function __construct() {
        $this->_db = new wrapper_bigcouch();
    }

    /**
     * This will handle the options requests
     *
     * @url OPTIONS /{account_id}
     * @url OPTIONS /provider/{provider_id}
     * @url OPTIONS /
     */

    function options() {
        return;
    }

    /**
     * This will allow the user to get the default settings for an account and for a phone 
     *
     * @url GET /{account_id}
     * @access protected
     * @class  AccessControlKazoo {@requires account}
     */

    function retrieve_document($account_id) {
        $account_db = helper_utils::get_account_db($account_id);

        if ($account_db) {
            $obj_return = array();
            $doc = $this->_db->get($account_db, $account_id);

            if ($doc)
                return $obj_return['data'] = $doc;
            else
                throw new RestException(404, 'This account does not exist');
        } else
            throw new RestException(400, 'This account id is not correct');
    }

    /**
     * This will allow the user to get the accounts related to a provider 
     *
     * @url GET /provider/{provider_id}
     * @access protected
     * @class  AccessControlKazoo {@requires provider}
     */

    function get_by_provider($provider_id) {
        $account_doc_arr = array();
        $providers_db = helper_utils::get_providers_db();
        $account_list = $this->_db->get($providers_db, $provider_id)['accounts'];

        foreach ($account_list as $account_id) {
            $account_db = helper_utils::get_account_db($account_id);
            $account_doc = $this->_db->get($account_db, $account_id);
            // This is ugly but huh...
            $account_doc['id'] = $account_id;

            $account_doc_arr['data'][] = $account_doc;
        }

        if (!empty($account_doc_arr))
            return $account_doc_arr;
        else
            throw new RestException(404, "No account related to that provider were found");

    }

    /**
     * This will allow the user to add an account
     *
     * @url PUT /
     * @url PUT /{account_id}
     * @access protected
     * @class  AccessControlKazoo {@requires account}
     */

    function add_document($request_data, $account_id = null) {
        $request_data = $request_data['data'];
        $provider_id = helper_utils::get_param($request_data, 'provider_id', false);

        if (!$provider_id)
            throw new RestException(400, 'Missing provider_id');

        // We need to get the provider doc first
        $providers_db = helper_utils::get_providers_db();
        $provider_doc = $this->_db->get($providers_db, $provider_id);
        $account_list = $provider_doc['accounts'];

        // If there is no account we need to generate one
        if (!$account_id) {
            $account_id = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
                                    mt_rand(0, 65535),
                                    mt_rand(0, 65535),
                                    mt_rand(0, 65535),
                                    mt_rand(16384, 20479),
                                    mt_rand(32768, 49151),
                                    mt_rand(0, 65535),
                                    mt_rand(0, 65535),
                                    mt_rand(0, 65535));
        }

        $account_db = helper_utils::get_account_db($account_id);
        $object_ready = $this->_db->prepare_add_accounts($request_data, $account_id);

        if($this->_db->add($account_db, $object_ready)) {
            // Adding this account to the list.
            $account_list[] = $account_id;
            // And saving
            if ($this->_db->update($providers_db, $provider_id, 'accounts', $account_list))
                return array(
                    'status' => true,
                    'message' => 'Document successfully added',
                    'id' => $account_id);
            else
                throw new RestException(500, 'Could not link the new account with its provider');
        } else
            throw new RestException(500, 'Could not create that account Database');

    }
    
    /**
     * This will allow the user to modify the account settings
     *
     * @url POST /{account_id}
     * @access protected
     * @class  AccessControlKazoo {@requires account}
     */

    function editDocument($request_data, $account_id) {
        $request_data = $request_data['data'];
        $account_db = helper_utils::get_account_db($account_id);

        foreach ($request_data as $key => $value) {
            if (!$this->_db->update($account_db, $account_id, $key, $value))
                throw new RestException(500, 'Error while saving');
        }

        return array('status' => true, 'message' => 'Account successfully modified');
    }

    /**
     * Delete the whole account or just a phone
     *
     * @url DELETE /{account_id}
     * @access protected
     * @class  AccessControlKazoo {@requires account}
     */

    function delete_document($account_id) {
        $account_db = helper_utils::get_account_db($account_id);

        // Let's first try of the account that we are trying to delete exist
        if ($this->_db->isDBexist($account_db)) {
            $doc_list = $this->_db->getAll($account_db);

            // We get the document list inside of the account database
            foreach ($doc_list['rows'] as $doc) {
                // Check the id of the document to know if it is a device doc or the account doc
                if (preg_match("/^[a-f0-9]{12}$/i", $doc['id'])) {
                    // And delete the mac_lookup entry
                    $mac_lookup_db = helper_utils::get_mac_lookup_db();
                    if (!$this->_db->delete($mac_lookup_db, $doc['id'])) {
                        throw new RestException(500, 'Could not delete a lookup entry');
                    }
                }
            }

            // Now let's get the account doc for the provider_id
            $providers_db = helper_utils::get_providers_db();
            $provider_id = $this->_db->get($account_db, $account_id)['provider_id'];
            $account_list = $this->_db->get($providers_db, $provider_id)['accounts'];

            // Getting the position and deleting the element
            $position = array_search($account_id, $account_list);
            unset($account_list[$position]);
            // and then updating the array
            $this->_db->update($providers_db, $provider_id, 'accounts', $account_list);

            // And finally let's delete the account database
            if ($this->_db->delete($account_db)) {
                return array('status' => true, 'message' => 'Account successfully deleted');
            } else {
                throw new RestException(500, 'Could not delete the account database');
            }
        } else {
            throw new RestException(404, 'This account do not exist');
        }
    }
}
