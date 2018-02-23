<?php

class Input {

    /**
     * $_POST
     * @var array
     */
    private $_post = array();

    /**
     * $_GET
     * @var array
     */
    private $_get = array();

    /**
     * $_PUT
     * @var array
     */
    private $_put = array();

    /**
     * $_DELETE
     * @var array
     */
    private $_delete = array();

    /**
     * $_FILES
     * @var array
     */
    private $_files = array();

    /**
     * CONSTRUCTOR
     * Process input data
     */
    public function __construct() {

        if( isset($_POST) ){
            foreach( $_POST as $key => $value ){
                if( !is_null($value) ){
                    $this->_post[ $key ] = $value;
                }
            }
        }

        if( isset($_GET) ){
            foreach( $_GET as $key => $value ){
                if( !is_null($value) ){
                    $this->_get[ $key ] = $value;
                }
            }
        }

        if( isset($_FILES) ){
            foreach( $_FILES as $key => $value ){

                if( is_null($value) ){
                    continue;
                }

                $data = array();

                foreach( $value as $subKey => $subValue ){
                    if( is_array($subValue) ){
                        foreach( $subValue as $subFile => $subFileValue ){
                            $data[ $subFile ][ $subKey ] = $subFileValue;
                        }
                    }else{
                        $data[ $subKey ] = $subValue;
                    }
                }

                $this->_files[ $key ] = $data;
            }
        }

        switch( $_SERVER['REQUEST_METHOD'] ){
        case 'PUT':
            parse_str( file_get_contents( 'php://input' ), $this->_put );
        break;
        case 'DELETE':
            parse_str( file_get_contents( 'php://input' ), $this->_delete );
        break;
        }

    }

    /**
     * Retrieve values sent by $_POST
     * @param string $key
     * @return mixed
     */
    public function post($key = NULL){

        if( is_null($key) ){
            return $this->_post;
        }

        if( isset($this->_post[ $key ]) ){
            return $this->_post[ $key ];
        }

        return FALSE;
    }

    /**
     * Retrieve values sent by $_GET
     * @param string $key
     * @return mixed
     */
    public function get($key = NULL){

        if( is_null($key) ){
            return $this->_get;
        }

        if( isset($this->_get[ $key ]) ){
            return $this->_get[ $key ];
        }

        return FALSE;
    }

    /**
     * Retrieve values sent by $_PUT
     * @param string $key
     * @return mixed
     */
    public function put($key = NULL){

        if( is_null($key) ){
            return $this->_put;
        }

        if( isset($this->_put[ $key ]) ){
            return $this->_put[ $key ];
        }

        return FALSE;
    }

    /**
     * Retrieve values sent by $_DELETE
     * @param string $key
     * @return mixed
     */
    public function delete($key = NULL){

        if( is_null($key) ){
            return $this->_delete;
        }

        if( isset($this->_delete[ $key ]) ){
            return $this->_delete[ $key ];
        }

        return FALSE;
    }

    /**
     * Retrieve values sent by $_FILES
     * @param string $key
     * @return mixed
     */
    public function files($key = NULL){

        if( is_null($key) ){
            return $this->_files;
        }

        if( isset($this->_files[ $key ]) ){
            return $this->_files[ $key ];
        }

        return FALSE;
    }

}