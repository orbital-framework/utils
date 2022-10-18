<?php
declare(strict_types=1);

namespace Orbital\Utils;

use \Exception;
use \CurlHandle;

abstract class Curl {

    /**
     * Request timeout
     * @var integer
     */
    public static $timeout = 30;

    /**
     * Generate CURL request object
     * @throws Exception
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return object
     */
    public static function generate(string $url, string $method, array $data = array(), array $headers = array()): CurlHandle {

        if( $data AND is_array($data)
            AND in_array($method, array('HEAD', 'GET', 'DELETE')) ){

            if( strpos('?', $url) ){
                $url .= '&'. http_build_query($data);
            }else{
                $url .= '?'. http_build_query($data);
            }

        }

        $curl = curl_init();

        if( $curl === false ){
            throw new Exception('CURL connection could not be generated.');
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        if( $headers ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // POST
        if( $method === 'POST' ){

            if( is_array($data) ){
                $data = http_build_query($data);
            }

            curl_setopt($curl, CURLOPT_POST, true);
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
     * @return string
     */
    public static function makeRequest(string $url, string $method = "POST", array $data = array(), array $headers = array()): string {

        $curl = self::generate($url, $method, $data, $headers);
        $response = curl_exec($curl);
        // $info = curl_getinfo($curl);
        // $error = curl_errno($curl);
        // $errorMessage = curl_error($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Make HTTP GET request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return string
     */
    public static function get(string $url, array $params = array(), array $headers = array()): string {
        return self::makeRequest($url, 'GET', $params, $headers);
    }

    /**
     * Make HTTP POST request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return string
     */
    public static function post(string $url, array $data, array $headers = array()): string {
        return self::makeRequest($url, 'POST', $data, $headers);
    }

    /**
     * Make HTTP PUT request
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public static function put(string $url, array $data, array $headers = array()): string {
        return self::makeRequest($url, 'PUT', $data, $headers);
    }

    /**
     * Make HTTP DELETE request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return string
     */
    public static function delete(string $url, array $params = array(), array $headers = array()): string {
        return self::makeRequest($url, 'DELETE', $params, $headers);
    }

}