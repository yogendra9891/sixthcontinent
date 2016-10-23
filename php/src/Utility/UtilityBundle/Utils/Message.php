<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utility\UtilityBundle\Utils;

/**
 * Description of Messages
 *
 * @author ddeffolap294
 */
class Message {
    //put your code here
    
    private $code=-1;
    private $message='ERROR';
    
    public function __construct($msg) {
        if(isset($msg['code']) && isset($msg['message'])){
            $this->code = $msg['code'];
            $this->message = $msg['message'];
        }
    }
    
    public function getCode() {
        return $this->code;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setMessage($message) {
        $this->message = $message;
    }


}
