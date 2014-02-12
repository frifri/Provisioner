<?php 

/**
 * This file contains The AccessControl class
 * It is used to determine wether or not you are allowed to access an API
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.4.0
 */

class AccessControlKazoo implements iAuthenticate {
    private function REST($verb, $uri, $data) {
        $url = "http://10.26.0.61:8000/v2" . $uri;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $headers = array();
        $headers[] = "Content-Type: application/json";
        if ($this->auth_token) {
            $headers[] = "X-Auth-Token: " . $this->auth_token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        if ($verb == "PUT") {
            $file = tmpfile();
            fwrite($file, $data);
            fseek($file, 0);

            curl_setopt($ch, CURLOPT_PUT, 1);
            curl_setopt($ch, CURLOPT_INFILE, $file);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
        } else if ($verb == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($verb == "PUT")
            fclose($file);

        return $output;
    }

    function __isAllowed() {
        /*$account_id = $_GET['account_id'];
        $auth_token = getallheaders()['X-Auth-Token'];

        $reply = $this->REST("GET", "/accounts/$account_id?auth_token=$auth_token", array());

        $account_info = json_decode($reply, TRUE);

        // TODO: Make sure a valid account is returned!
        return isset($account_info['data']['name']);*/
        return true;
    }
}
