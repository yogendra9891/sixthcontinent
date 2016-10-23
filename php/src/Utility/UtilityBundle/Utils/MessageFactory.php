<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Utility\UtilityBundle\Utils;
/**
 *
 * @author ddeffolap294
 */
class MessageFactory extends Messages{
   
    
    public function __construct() {
        //TODO;
    }
    
    /**
     * Returns Message object based on integer code.
     * @param Integer $code
     * @return Object
     */
    public static function getMessage($code) {
        
        if(isset(self::$messages[$code])) {
            return new Message(self::$messages[$code]);
        } else {
            new Message();
        }
    }
}
