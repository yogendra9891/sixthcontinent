<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Utility\UtilityBundle\Utils;


/**
 * The class hold common utility method needed for the bundle
 *
 * @author ddeffolap294
 */
class Utility implements IUtility{
    
    
    const PUSH_SETTING = 'PUSH_SETTING';
    const WEB_SETTING = 'WEB_SETTING';
    const MAIL_SETTING = 'MAIL_SETTING';
    
    public static $notification_type = array(self::MAIL_SETTING,self::WEB_SETTING,self::PUSH_SETTING);

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
    public static function createResponse($resp,$is_numeric_check = 0) {
        
        $resp_data = array('code' => $resp->getCode(), 'message' => $resp->getMessage(), 'data' => $resp->getData());
        
        $output = self::encodeData($resp_data,$is_numeric_check);
        
        echo $output;
        
        exit;
        
    }
    /**
     * Creates and output resonse
     * @param String $resp
     */
    public static function createResponseResult($resp,$is_numeric_check = 0) {
        
        $resp_data = array('code' => $resp->getCode(), 'message' => $resp->getMessage(), 'result' => $resp->getData() , 'dataInfo' => $resp->getDataInfo());
        
        $output = self::encodeData($resp_data,$is_numeric_check);
        
        echo $output;
        
        exit;
        
    }
    /**
     * Creates and output resonse
     * @param String $resp
     */
    public static function createResponseDataInfo($resp,$is_numeric_check = 0) {
        
        $resp_data = array('code' => $resp->getCode(), 'message' => $resp->getMessage(), 'result' => $resp->getData() , 'dataInfo' => $resp->getDataInfo());
        
        $output = self::encodeData($resp_data,$is_numeric_check);
        
        echo $output;
        
        exit;
        
    }
    
    /**
     * Compare String
     * @param string $string1
     * @param string $string2
     * @return boolean
     */
    public static function matchString($string1, $string2)
    {
        //remove balnk space
        $string1 = self::getTrimmedString($string1);
        $string2 = self::getTrimmedString($string2);
        //convert in to lower case
        $string1 = self::getLowerCaseString($string1);
        $string2 = self::getLowerCaseString($string2);
        if($string1 != $string2){
            return false;
        }
        return true;
    }
    
    /**
     * Get trimmed string
     * @param string $str
     * @return string
     */
    public static function getTrimmedString($str)
    {
        return trim($str);
    }
    
    /**
     * Convert to lower case
     * @param string $str
     * @return string
     */
    public static function getLowerCaseString($str)
    {
        return strtolower($str);
    }
    
    /**
     * COnvert to md5
     * @param string $str
     * @reeturn string
     */
    public static function convertMd5($str)
    {
        return md5($str);
    }
    
    /**
     * Convert to lower case
     * @param string $str
     * @return string
     */
    public static function getUpperCaseString($str)
    {
        return strtoupper($str);
    }
    
    /**
     * Convert to integer value
     * @param int $number
     * @return int 
     */
    public static function getIntergerValue($number)
    {
        return (int)$number;
    }    
    
    /**
     * return unique array
     * @param array $array
     * @return array
     */
    public static function getUniqueArray($array) {
        return array_unique($array);
    }
    
    /**
     * Convert to string
     * @param string $string
     * @return string
     */
    public static function getStringValue($string)
    {
        return (string)$string;
    }
    public static function getDate($format, $day){
        
        
        return (date($format, (self::getTodayTimeInSeconds() + self::getDaysInSeconds($day))));
        
    }
    
    
    public static function getTodayTimeInSeconds(){
        
        return mktime(0, 0, 0, date('n'), date('j'), date('Y'));
    }
    
    public static function getDaysInSeconds($day){
        return ($day * (60 * 60 * 24));
    }
    
    /**
     * Right trim the string
     * @param string $str
     * @param string param
     * @return string
     */
    public static function getRightTrimString($str, $param = ' ')
    {
        return rtrim($str, $param);
    }
    
    /**
     * Left trim the string
     * @param string $str
     * @param string param
     * @return string
     */
    public static function getLeftTrimString($str, $param = ' ')
    {
        return ltrim($str, $param);
    }
    
    /**
     * Decode the json
     * @param Array $data
     * @param int $is_numeric_check
     */
    public static function decodeData($data) {
        $data = json_decode($data);
        return $data;
    }
    
    /**
     * replace the string into a string
     * @param type $search
     * @param type $replace
     * @param type $source
     * @return string $final_string
     */
    public static function getReplaceString($search, $replace, $source) {
       $final_string =  str_replace($search, $replace, $source);
       return $final_string;
    }
    
    /**
     * get Substring from right
     * @param type $string
     * @param type $position
     * @return string
     */
    public static function getRightSubString($string, $position) {
        $sub_string = substr($string, $position);
        return $sub_string;
    }
    
    /**
     * function for converting the notification setting into binary value from array
     * @param type $notification_setting
     */
    public static function convertNotificationSettingToDecimal($notification_setting) {
        $keys = str_replace( ' ', '', array_keys($notification_setting) );
        $keys = array_map("strtoupper", $keys);
	$values = array_values($notification_setting);
	$notification_setting = array_combine($keys, $values);
        
        $valid_sequance = self::$notification_type;
        $sequence_array = array();
        $sequence_array[$valid_sequance[0]] = $notification_setting[$valid_sequance[0]];
        $sequence_array[$valid_sequance[1]] = $notification_setting[$valid_sequance[1]];
        $sequence_array[$valid_sequance[2]] = $notification_setting[$valid_sequance[2]];
        
        $sequance_values = array_values($sequence_array);
        $binary_value = implode('', $sequance_values);
        $decimal_num = self::binaryToDecimalNumber($binary_value);
        return $decimal_num;
    }
    
    /**
     * function for converting the binery to decimal
     * @param type $binary_number
     * @return type
     */
    public static function binaryToDecimalNumber($binary_number) {
        
        return bindec($binary_number);
    }
    
    
    /**
     * function for converting the notification setting into binary value from array
     * @param type $notification_setting
     */
    public static function convertNotificationSettingToBinary($binary_value) {
        $decimal_num = self::binaryToDecimalBinary($binary_value);
        $notification_setting = array();
        $valid_sequance = self::$notification_type;
        for($i = 0; $i < strlen($decimal_num);$i++) {
            $notification_setting[$valid_sequance[$i]] = $decimal_num[$i];
        }
        
        return $notification_setting;
    }
    
    /**
     * function for converting the decimal to binary
     * @param type $binary_number
     * @return type
     */
    public static function binaryToDecimalBinary($binary_number) {
        
        return decbin($binary_number);
    }
    
    /*
     * encode the url
     * @param string $url
     * @return string $url
     */
    public static function urlEncode($url) {
        return urlencode($url);
    }
}
