<?php

class RestClient {

    /**
     * Auth type - Bearer
     */
    const AUTH_TYPE_BEARER = 'bearer';

    /**
     * Auth type - Basic
     */
    const AUTH_TYPE_BASIC = 'basic';

    /**
     * API URL
     * @var string
     */
    protected $apiUrl = 'https://system.com/api/';

    /**
     * API Scope
     * @var string
     */
    protected $apiScope = NULL;

    /**
     * API User
     * @var string
     */
    protected $apiUser = NULL;

    /**
     * API Password
     * @var string
     */
    protected $apiPassword = NULL;

    /**
     * API Key
     * @var string
     */
    protected $apiKey = NULL;

    /**
     * API Auth TYPE
     * @var string
     */
    protected $apiAuthType = NULL;

    /**
     * API General headers
     * @var array
     */
    protected $headers = array();

    /**
     * Retrieve API Scope
     * @return string
     */
    public function getApiScope(){
        return $this->apiScope;
    }

    /**
     * Retrieve API User
     * @return string
     */
    public function getApiUser(){
        return $this->apiUser;
    }

    /**
     * Retrieve API Password
     * @return string
     */
    public function getApiPassword(){
        return $this->apiPassword;
    }

    /**
     * Retrieve API Key
     * @return string
     */
    public function getApiKey(){
        return $this->apiKey;
    }

    /**
     * Retrieve API Auth Type
     * @return string
     */
    public function getApiAuthType(){
        return $this->apiAuthType;
    }

    /**
     * Set API Scope
     * @param string $scope
     * @return void
     */
    public function setApiScope($scope){
        $this->apiScope = $scope;
    }

    /**
     * Set API User
     * @param string $user
     * @return void
     */
    public function setApiUser($user){
        $this->apiUser = $user;
    }

    /**
     * Set API Password
     * @param string $password
     * @return void
     */
    public function setApiPassword($password){
        $this->apiPassword = $password;
    }

    /**
     * Set API Key
     * @param string $key
     * @return void
     */
    public function setApiKey($key){
        $this->apiKey = $key;
    }

    /**
     * Set API Auth Type
     * @param string $type
     * @return void
     */
    public function setApiAuthType($type){
        $this->apiAuthType = $type;
    }

    /**
     * Retrieve generated API URL for method
     * @param string $method
     * @return string
     **/
    public function generateApiUrl($method, $format = 'json'){

        $url = trim($this->apiUrl, '/');

        if( $this->apiScope ){
            $url .= '/'. $this->apiScope;
        }

        $url .= '/'. $method;

        if( $format ){
            $url .= '.'. $format;
        }

        return $url;
    }

    /**
     * Generate CURL request object
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return object
     */
    public function generateCurl($url, $method, $data = array(), $headers = array()){

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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        if( $this->getApiAuthType() == self::AUTH_TYPE_BASIC ){
            curl_setopt($curl, CURLOPT_USERPWD,
                        $this->getApiUser(). ':'. $this->getApiPassword());

        }elseif( $this->getApiAuthType() == self::AUTH_TYPE_BEARER ){
            $headers[] = 'Authorization: Bearer '. $this->getApiKey();

        }

        if( $headers OR $this->headers ){
            $headers = array_merge($this->headers, $headers);
        }

        if( $headers ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // POST
        if( $method == 'POST' ){

            // $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // PUT
        }elseif( $method == 'PUT' ){

            // $data = http_build_query($data);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // DELETE
        }elseif( $method == 'DELETE' ){
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
    public function makeRequest($url, $method = "POST", $data = array(), $headers = array()){

        $curl = $this->generateCURL($url, $method, $data, $headers);

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
    public function get($url, $params = array(), $headers = array()){
        return $this->makeRequest($url, 'GET', $params, $headers);
    }

    /**
     * Make HTTP POST request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public function post($url, $data, $headers = array()){
        return $this->makeRequest($url, 'POST', $data, $headers);
    }

    /**
     * Make HTTP PUT request
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return mixed
     */
    public function put($url, $data, $headers = array()){
        return $this->makeRequest($url, 'PUT', $data, $headers);
    }

    /**
     * Make HTTP DELETE request
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    public function delete($url, $params = array(), $headers = array()){
        return $this->makeRequest($url, 'DELETE', $params, $headers);
    }

}