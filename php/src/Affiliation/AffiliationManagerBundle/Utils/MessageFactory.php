<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Affiliation\AffiliationManagerBundle\Utils;

use Utility\UtilityBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Message;

/**
 * Description of Message
 *
 * @author ddeffolap294
 */
class MessageFactory extends Messages{
    //put your code here
    
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
            //return (Object)(self::$messages[$code]);
        } else if (Msg::getMessage($code)) {
            return Msg::getMessage($code);
        } else {
            return new Message();
        }
            
        
    }
    
}
