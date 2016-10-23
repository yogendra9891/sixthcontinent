<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Paypal\PaypalIntegrationBundle\Utils;

/**
 * Description of Messages
 *
 * @author ddeffolap294
 */
class Messages {
    //put your code here
    
    protected static $messages = array(
        1113 => array('code' => 1113, 'message' => 'INVALID_PAYPAL_PAYER'),  
        1055 => array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS'),  
        1105 => array('code' => 1105, 'message' => 'SHOP_IS_BLOCKED'),
        1058 => array('code' => 1058, 'message' => 'SHOP_PAYPAL_DOES_NOT_EXISTS'),
        1029 => array('code' => 1029, 'message' => 'FAILURE'),
        1060 => array('code' => 1060, 'message' => 'TRANSACTION_RECORD_DOES_NOT_EXISTS'),
        1061 => array('code' => 1061, 'message' => 'TRANSACTION_RECORD_DOES_NOT_BELONGS_TO_YOU'),
        1062 => array('code' => 1062, 'message' => 'TRANSACTION_RECORD_ALREADY_CONFIRMED'),
        1063 => array('code' => 1063, 'message' => 'TRANSACTION_RECORD_ALREADY_CANCELED'),
        1055 => array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS'),
    );
}
