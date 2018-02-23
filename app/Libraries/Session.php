<?php

class Session {

    /**
     * Session id
     * @var string
     */
    public $id = FALSE;

    /**
     * Session data
     * @var array
     */
    private $data = array();

    /**
     * CONSTRUCTOR
     * @param boolean $overload
     */
    public function __construct($overload = TRUE){

        if( !session_id() ){
            session_start();
        }

        if( $overload ){
            $this->data =& $_SESSION;
        }

        $this->id();

        return $this;
    }

    /**
     * Return session ID
     * @return string
     */
    public function id(){

        if( !$this->id ){
            $this->id = session_id();
        }

        return $this->id;
    }

    /**
     * Regenerate session id
     * @return string
     */
    public function regenerate(){
        session_regenerate_id();
        return $this->id();
    }

    /**
     * Set session data
     * @param string|array $key
     * @param string|NULL $value
     * @return object
     */
    public function set($key, $value = NULL){

        if( is_array($key) AND is_null($value) ){
            $this->data = array_merge($this->data, $key);
        }else{
            $this->data[ $key ] = $value;
        }

        return $this;
    }

    /**
     * Retrieve session data
     * @param {optional} mixed $key
     * @return mixed
     */
    public function get($key = NULL){

        if( $key ){
            return ( array_key_exists($key, $this->data) ) ? $this->data[ $key ] : NULL;
        }

        return $this->data;
    }

    /**
     * Remove session data
     * @param string $key
     * @return object
     */
    public function delete($key){

        if( isset($this->data[ $key ]) ){
            unset($this->data[ $key ]);
        }

        return $this;
    }

    /**
     * Destroy session
     * @return boolean
     */
    public function destroy(){
        $this->id = FALSE;
        return session_destroy();
    }

    /**
     * Add session message
     * @param string $type
     * @param string $message
     * @return object
     */
    public function addMessage($type, $message){

        $messages = $this->getMessages();
        $messages[ $type ][] = $message;

        $this->set('_messages', json_encode($messages));

        return $this;
    }

    /**
     * Retrieve session messages
     * @return array
     */
    public function getMessages(){

        if( $this->get('_messages') ){
            $messages = json_decode($this->get('_messages'), TRUE);
        }else{
            $messages = array();
        }

        return $messages;
    }

    /**
     * Delete session messages
     * @return object
     */
    public function deleteMessages(){
        $this->delete('_messages');
    }

}