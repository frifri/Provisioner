<?php

class Ui {
    /**
     *  The Options request handler
     *
     * @url OPTIONS /
     */
    function options() {
        return;
    }

    /**
     *  Test function
     *
     * @url GET /test
     */
    function test($request_data) {
        return array('status' => true, 'message' => 'Document successfully added');
    }
}