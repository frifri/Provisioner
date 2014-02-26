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

class Phones {
    private $_db;

    private $_FIELDS = array('settings');

    function __construct() {
        $this->_db = new wrapper_bigcouch();
    }

    private function _getAllPhonesInfo() {
        $factory_defaults_db = helper_settings::get_factory_defaults_db();
        $brands = $this->_db->getAllByKey($factory_defaults_db, 'brand', null);
        
        foreach ($brands as $brand_key => $brand_content) {
            $families = $this->_db->getAllByKey($factory_defaults_db, 'family', $brand_key);
            
            foreach ($families as $family_key => $family_value) {
                $models = $this->_db->getAllByKey($factory_defaults_db, 'model', $family_key);
                
                if ($models)
                    $families[$family_key]['models'] = $models;
            }
            
            $brands[$brand_key]["families"] = $families;
        }

        return $brands;
    }
    
    /**
     *  The Options request handler
     *
     * @url OPTIONS /
     * @url OPTIONS /{brand}
     * @url OPTIONS /{brand}/{family}
     * @url OPTIONS /{brand}/{family}/{model}
     */
    function options() {
        return;
    }

    /**
     *  This is the function that will allow the administrator to retrieve all brands/families/models/
     *
     * @url GET /
     * @url GET /{brand}
     * @url GET /{brand}/{family}
     * @url GET /{brand}/{family}/{model}
     */

    function getElement($brand = null, $family = null, $model = null) {
        $factory_defaults_db = helper_settings::get_factory_defaults_db();
        if (!$brand)
            $result['data'] = $this->_getAllPhonesInfo();
            //$result = $this->_db->getAllByKey($factory_defaults_db, 'brand', null);
        elseif (!$family)
            $result['data'] = $this->_db->getAllByKey($factory_defaults_db, 'family', $brand);
        elseif (!$model)
            $result['data'] = $this->_db->getAllByKey($factory_defaults_db, 'model', $family);
        else
            $result['data'] = $this->_db->get($factory_defaults_db, $brand . '_' . $family . '_' . $model);

        if (!empty($result))
            return $result;
        else
            throw new RestException(404, "No data found");
    }

    /**
     *  This is the function that will allow the administrator to modify a brand/family/model/
     *
     * @url POST /{brand}
     * @url POST /{brand}/{family}
     * @url POST /{brand}/{family}/{model}
     * @access protected
     * @class  AccessControl {@requires admin}
     */

    function editElement($brand, $family = null, $model = null, $request_data = null) {
        if (empty($request_data))
            throw new RestException(400, "The body for this request cannot be empty");

        $document_name = helper_utils::get_device_doc_name($brand, $family, $model);
        if (!$document_name)
            throw new RestException(400, "Could not find at least the brand");

        Validator::validateEdit($request_data, $this->_FIELDS);

        foreach ($request_data as $key => $value) {
            $factory_defaults_db = helper_settings::get_factory_defaults_db();
            if ($this->_db->update($factory_defaults_db, $document_name, $key, $value))
                return array('status' => true, 'message' => 'Document successfully modified');
            else
                throw new RestException(500, 'Error while modifying the data');
        }
    }

    /**
     *  This is the function that will allow the administrator to add a brand/family/model/
     *
     * @url PUT /{brand}
     * @url PUT /{brand}/{family}
     * @url PUT /{brand}/{family}/{model}
     * @access protected
     * @class  AccessControl {@requires admin}
     */

    function addElement($brand, $family = null, $model = null, $request_data = null) {
        if (empty($request_data))
            throw new RestException(400, "The body for this request cannot be empty");

        $document_name = helper_utils::get_device_doc_name($brand, $family, $model);
        if (!$document_name)
            throw new RestException(400, "Could not find at least the brand");
        
        $object_ready = $this->_db->prepareAddPhones($request_data, $document_name, $brand, $family, $model);

        $factory_defaults_db = helper_settings::get_factory_defaults_db();

        if (!$this->_db->add($factory_defaults_db, $object_ready))
            throw new RestException(500, 'Error while Adding the data');

        return array('status' => true, 'message' => 'Document successfully added');
    }

    /**
     *  This is the function that will allow the administrator to delete a brand/family/model/
     *
     * @url DELETE /{brand}
     * @url DELETE /{brand}/{family}
     * @url DELETE /{brand}/{family}/{model}
     * @access protected
     * @class  AccessControl {@requires admin}
     */

    function delElement($brand, $family = null, $model = null) {
        // NOP, this is NOT OK for the wrapper. it is too specific.
        // I MUST find a way to use the delete() function instead of this one.
        // If I don't the other wrappers will need to implement this function as well.
        $factory_defaults_db = helper_settings::get_factory_defaults_db();
        $this->_db->deleteView($factory_defaults_db, $brand, $family, $model);

        return array('status' => true, 'message' => 'Document successfully deleted');
    }
}