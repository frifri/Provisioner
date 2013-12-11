<?php 

/**
 * This file manage all the APIs for the providers
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

class Providers {
    public $db;

    function __construct() {
        $this->db = new wrapper_bigcouch();
    }

    /**
     *  This is the function that will allow the administrator to retrieve all the providers
     *
     * @url GET /
     * @access protected
     * @class  AccessControlKazoo
     */
    function retrieveAll() {
        $result = array();

        $result['data'] = $this->db->getAllByKey('providers', 'domain');
        
        return $result;
    }

    /**
     *  This will get the information for a specific provider
     *
     * @url GET /{provider_id}
     * @access protected
     * @class  AccessControlKazoo
     */
    function getOne($request_data, $provider_id) {
        $provider = array();

        $provider['data'] = $this->db->get('providers', $provider_id);

        if ($provider)
            return $provider;
        else
            throw new RestException(404, 'No information for this provider');
    }

    /**
     *  This will update the information for a provider
     *
     * @url POST /{provider_id}
     * @access protected
     * @class  AccessControlKazoo
     */
    function update($request_data, $provider_id) {
        foreach ($request_data as $key => $value) {
            if (!$this->db->update('providers', $provider_id, $key, $value))
                throw new RestException(500, 'Error while saving');
        }

        return array('status' => true, 'message' => 'Provider successfully modified');
    }

    /**
     *  This will add a provider
     *
     * @url PUT /
     * @access protected
     * @class  AccessControlKazoo
     */
    function create($request_data) {
        $object_ready = $this->db->prepareAddProviders($request_data);
        if (!$this->db->add('providers', $object_ready))
            throw new RestException(500, 'Error while Adding');
        else
            return array('status' => true, 'message' => 'Provider successfully added');
    }

    /**
     *  This function only delete the provider
     *  TODO: allow the provider to also delete the users linked to this provider.
     *
     * @url DELETE /{provider_id}
     * @access protected
     * @class  AccessControlKazoo
     */
    function delete($provider_id) {
        if (!$this->db->delete('providers', $provider_id))
            throw new RestException(500, 'Error while deleting');
        else
            return array('status' => true, 'message' => 'Provider successfully deleted');
    }
}