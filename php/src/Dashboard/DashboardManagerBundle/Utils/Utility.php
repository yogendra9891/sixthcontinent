<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Dashboard\DashboardManagerBundle\Utils;
/**
 * The class hold common utility method needed for the bundle
 *
 * @author ddeffolap294
 */
class Utility {
    
    /**
     * Return Base64 decoded password
     * @param String $password
     * @return String
     */
    public static function decodePassword($password){
            return base64_decode($password);
    }
     
    
    /**
     * Convert and return given array into JSON
     * @param Array $data
     * @param int $is_numeric_check
     */
    public static function encodeData($data, $is_numeric_check = 0) {
       
        if ($is_numeric_check == 1) {
            $data = json_encode($data, JSON_NUMERIC_CHECK);
        } else {
            $data = json_encode($data);
        }
        
        return $data;
        
    }
    
    /**
     * Creates and output resonse
     * @param String $resp
     */
    public static function createResponse($resp) {
        
        $resp_data = array('code' => $resp->getCode(), 'message' => $resp->getMessage(), 'data' => $resp->getData());
        
        $output = self::encodeData($resp_data);
        
        echo $data;
        
        exit;
        
    }
    
    
}
