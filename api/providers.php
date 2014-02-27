<?php 

/**
 * This file manage all the APIs for the providers
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 * @access protected
 * @class  AccessControlKazoo {@requires provider}
 */

class Providers {
    private $_db;

    function __construct() {
        $this->_db = new wrapper_bigcouch();
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url GET /
     */
    function retrieveAll() {
        $result = array();
        $providers_db = helper_utils::providers_db();
        
        $result['data'] = $this->_db->getAllByKey($providers_db, 'domain');

        return $result;
    }

    /**
     *  This will get the information for a specific provider
     *
     * @url GET /{provider_id}
     */
    function getOne($request_data, $provider_id) {
        $provider = array();
        $providers_db = helper_utils::providers_db();

        $provider['data'] = $this->_db->get($providers_db, $provider_id);

        if ($provider)
            return $provider;
        else
            throw new RestException(404, 'No information for this provider');
    }

    /**
     *  This will update the information for a provider
     *
     * @url POST /{provider_id}
     */
    function update($request_data, $provider_id) {
        $providers_db = helper_utils::providers_db();
        $request_data = $request_data['data'];

        foreach ($request_data as $key => $value) {
            if (!$this->_db->update($providers_db, $provider_id, $key, $value))
                throw new RestException(500, 'Error while saving');
        }

        return array('status' => true, 'message' => 'Provider successfully modified');
    }

    /**
     *  This will add a provider
     *
     * @url PUT /
     */
    function create($request_data) {
        $request_data = $request_data['data'];
        $object_ready = $this->_db->prepare_add_providers($request_data);

        // Let's just check if the domain do not exist already
        $providers_db = helper_utils::providers_db();
        $domain_exist = $this->_db->getOneByKey($providers_db, 'domain', $object_ready['domain']);
        if ($domain_exist)
            throw new RestException(409, "domain. You can't create a provider with this domain");

        $new_doc = $this->_db->add($providers_db, $object_ready);
        if (!$new_doc)
            throw new RestException(500, 'Error while Adding');
        else
            return array(
                'status' => true,
                'message' => 'Provider successfully added',
                'id' => $new_doc->id);
    }

    /**
     *  This function only delete the provider
     *  TODO: allow the provider to also delete the users linked to this provider.
     *
     * @url DELETE /{provider_id}
     */
    function delete($provider_id) {
        $providers_db = helper_utils::providers_db();

        if (!$this->_db->delete($providers_db, $provider_id))
            throw new RestException(500, 'Error while deleting');
        else
            return array('status' => true, 'message' => 'Provider successfully deleted');
    }
}
