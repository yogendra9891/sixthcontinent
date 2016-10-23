<?php
namespace ExportManagement\ExportManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
require_once(__DIR__ . '/../Resources/lib/iban/php-iban.php');

// validate the data.like iban, vatnumber etc
class ValidationService
{
    protected $em;
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em        = $em;
    }
    
    /**
     * Varify Iban Number
     * @param string $iban
     * @return boolean
     */
    public function varfyIban($iban) {

        return verify_iban($iban);
    }
    
    /**
     * get the address
     * @param string $address
     * @return string $final_address
     */
    public function getAddress($address) {
        $final_address = '';
        $splited_address = preg_split("/[\s]+/", $address); //split by space
        if (isset($splited_address[0])) {
            $final_address = $splited_address[0];
        }
        if (isset($splited_address[1])) {
            $final_address = $final_address . ' ' . $splited_address[1];
        }
        $final_address = substr($final_address, 0, 35);
        return $final_address;
    }

    /**
     * get the zip
     * @param string $zip
     * @return string $trim_zip
     */
    public function getZip($zip) {
        $trim_zip = substr($zip, 0, 5);
        return $trim_zip;
    }

    /**
     * get the city
     * @param string $city
     * @return string $trim_city
     */
    public function getCity($city) {
        $trim_city = substr($city, 0, 30);
        return $trim_city;
    }

    /**
     * get the ssn
     * @param string $ssn
     * @return string $trim_ssn
     */
    public function getSsn($ssn) {
        $trimed_ssn = substr($ssn, 0, 16);
        $trim_ssn = strtoupper($trimed_ssn);
        return $trim_ssn;
    }

    /**
     * finding the phone no
     * @param $phone
     * @return $trimed_phone
     */
    public function getPhone($phone) {
        $trim_phone = explode(' ', $phone);
        return substr($trim_phone[0], 0, 18);
    }

    /**
     * Check country code
     * @param type $cccode
     */
    public function checkCountry($cccode) {
        $country = ((strtolower($cccode) == 'italia') || (trim($cccode) == '') || (strtolower($cccode) == 'celibe') || (strtolower($cccode) == 'nubile')
                ) ? 'IT' : $cccode;
        return $country;
    }

    /**
     * finding the fiscal code
     * @param string $fiscal_code
     * @return string $trim_fiscal
     */
    public function getFiscalCode($fiscal_code) {
        $fiscal_len = strlen($fiscal_code);
        if ($fiscal_len == 16) {
            return true;
        }
        return false;
    }

    /**
     * Get vat number
     * @param string $iban
     * @return string
     */
    public function getIbanNumber($iban) {
        $iban_no = str_replace(' ', '', $iban);
        //remove IBAN text if present
        # Remove IIBAN or IBAN from start of string, if present
        $iban1 = preg_replace('/^I?IBAN/', '', $iban_no);
        $iban2 = preg_replace('/^IBAN/', '', $iban1);
        # Remove all non basic roman letter / digit characters
        $iban3 = preg_replace('/[^a-zA-Z0-9]/', '', $iban2);
        $iban_upper = strtoupper($iban3);
        $last_iban = str_replace("IBAN", '', $iban_upper);
        return $last_iban;
    }
    
    /**
     * get store name
     * @param string $name
     * @return string $store_name
     */
    public function getStoreName($name) {
      $store_name = substr($name, 0, 60);
      return $store_name;
    }
    
    /**
     * get store email
     * @param type $email
     * @return $store_email
     */
    public function getStoreEmail($email) {
        $store_email = rtrim($email, '__'); 
        return $store_email;
    }
    
    /**
     * get the business name
     * @param string $business_name_only
     * @param string $legalsatus
     * @return string $final_address
     */
    public function getStoreBusinessName($business_name_only, $legalsatus) {
        $business_name = $business_name_only . " " . $legalsatus;
//        $final_address = '';
//        $splited_address = preg_split("/[\s]+/", $business_name);
//        if (isset($splited_address[0])) {
//            $final_address = $splited_address[0];
//        }
//        if (isset($splited_address[1])) {
//            $final_address = $final_address . ' ' . $splited_address[1];
//        }
        $final_business_name = substr($business_name, 0, 60);
        return $final_business_name;
    }
    
    /**
     * get the store business type
     * @param string $business_type
     * @return string $final_business_type
     */
    public function getStoreBusinessType($business_type) {
        $final_business_type = strtoupper(substr($business_type, 0, 5));
        return $final_business_type;
    }
    
    /**
     * get business region
     * @param string $business_region
     * @return string
     */
    public function getStoreBusinessRegion($business_region) {
        $final_business_region = strtoupper(substr($business_region, 0, 5));
        return $final_business_region;
    }
    
    /**
     * get business city of store
     * @param string $city
     * @return type
     */
    public function getStoreBusinessCity($city) {
        $business_city = substr($city, 0, 30);
        return $business_city;
    }
    
    /**
     * Get business address
     * @param string $business_address
     * @return string $final_address
     */
    public function getBusinessAddress($business_address) {
//        $final_address = '';
//        $splited_address = preg_split("/[\s]+/", $business_address);
//        if (isset($splited_address[0])) {
//            $final_address = $splited_address[0];
//        }
//        if (isset($splited_address[1])) {
//            $final_address = $final_address . ' ' . $splited_address[1];
//        }
        $final_address = substr($business_address, 0, 35);
        return $final_address;
    }
    
    /**
     * get store zip
     * @param int $zip
     * @return int $store_zip
     */
    public function getStoreZip($zip) {
        $store_zip = substr($zip, 0, 5);
        return $store_zip;
    }
    
    /**
     * get store province
     * @param string $province
     * @return string $store_province
     */
    public function getStoreProvience($province) {
        $store_province = strtoupper(substr($province, 0, 2));
        return $store_province;
    }
    
    /**
     * Get vat number
     * @param string $vat
     * @return string $vat_upper
     */
    public function getVatNumber($vat) {
        $vat_no = str_replace(' ', '', $vat);
        $vat_upper = strtoupper($vat_no);
        return $vat_upper;
    }

    /**
     * Check vat number validation
     * @param string $variabile
     * @return boolean
     */
    public function checkVatNumber($variabile) {
        if ($variabile == '' || $variabile == 0)
            return false;
        //la p.iva deve essere lunga 11 caratteri
        if (strlen($variabile) != 11)
            return false;
        //la p.iva deve avere solo cifre
//        if (!ereg("^[0-9]+$", $variabile))
//            return false;
        if (!preg_match("/^[0-9]+$/", $variabile))
            return false;
        $primo = 0;
        for ($i = 0; $i <= 9; $i+=2)
            $primo+= ord($variabile[$i]) - ord('0');
        for ($i = 1; $i <= 9; $i+=2) {
            $secondo = 2 * ( ord($variabile[$i]) - ord('0') );
            if ($secondo > 9)
                $secondo = $secondo - 9;
            $primo+=$secondo;
        }
        if ((10 - $primo % 10) % 10 != ord($variabile[10]) - ord('0'))
            return false;
        return true;
    }
    
    /**
     * validate the fiscal code for shop detail
     * @param string $fiscal_code
     * @return string $final_fiscal_code
     */
    public function validateFiscalCode($fiscal_code) {
        $final_fiscal_code = '';
        $fiscal_code       = trim($fiscal_code);
        if (strlen($fiscal_code) == 16) {
            $final_fiscal_code = strtoupper($fiscal_code);            
        }
        return $final_fiscal_code;
    }
 }
