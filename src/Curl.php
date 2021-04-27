<?php

namespace Orbital\Utils;

abstract class Curl {

    /**
     * Request timeout
     * @var integer
     */
    public static $timeout = 30;

    /**
     * Generate CURL request object
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return object
     */
    public static function generate($url, $method, $data = array(), $headers = array()){

        if( $data AND is_array($data)
            AND in_array($method, array('HEAD', 'GET', 'DELETE')) ){

            if( strpos('?', $url) ){
                $url .= '&'. http_build_query($data);
            }else{
                $url .= '?'. http_build_query($data);
            }

        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if( $headers ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // POST
        if( $method === 'POST' ){

            if( is_array($data) ){
                $data = http_build_query($data);
            }

            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // PUT
        }elseif( $method === 'PUT' ){

            // $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // DELETE
        }elseif( $method === 'DELETE' ){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        }

        return $curl;
    }

    /**
     * Make HTTP request on client
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return array
     */
    public static function makeRequest($url, $method = "POST", $data = array(), $headers = array()){

        $curl = self::generate($url, $method, $data, $headers);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_errno($curl);
        $errorMessage = curl_error($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Make HTTP GET request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public static function get($url, $params = array(), $headers = array()){
        return self::makeRequest($url, 'GET', $params, $headers);
    }

    /**
     * Make HTTP POST request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public static function post($url, $data, $headers = array()){
        return self::makeRequest($url, 'POST', $data, $headers);
    }

    /**
     * Make HTTP PUT request
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return mixed
     */
    public static function put($url, $data, $headers = array()){
        return self::makeRequest($url, 'PUT', $data, $headers);
    }

    /**
     * Make HTTP DELETE request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public static function delete($url, $params = array(), $headers = array()){
        return self::makeRequest($url, 'DELETE', $params, $headers);
    }

}