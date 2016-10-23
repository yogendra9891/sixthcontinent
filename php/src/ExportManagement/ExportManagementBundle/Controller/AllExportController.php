<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use ExportManagement\ExportManagementBundle\Document\ProfileExport;

require_once(__DIR__ . '/../Resources/lib/iban/php-iban.php');

class AllExportController extends Controller {

    protected $shop_profile = "/uploads/users/exportprofile/shopexp";
    protected $citizen_profile_type = 'citizen';

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the citizen filename
     * @return string
     */
    public function getCitizenFileName() {
        $file_name = $this->citizen_profile_type . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the citizen user profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportallprofileAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getShopProfile();


        if (count($stores)) {
            //exporting the data.
            $result = $this->exportshopcsv($stores);
        }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_PROFILE_FOR_EXPORT', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $citizen_data
     */
    public function exportshopcsv($shop_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_profile;

        //creating the file name.
        $file_name = 'shopexport.csv';

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $shop_file_name = $file_path . "/" . $file_name;
        $head_data_array = array('id', 'parentid', 'name', 'email', 'phone', 'businessname', 'businesstype', 'paymentstatus',
            'businesscountry',
            'businessregion', 'businesscity', 'businessaddress', 'zip', 'province', 'vatnumber', 'iban',
            'createdat', 'isactive', 'shopstatus', 'creditcardstatus');
        //check if file exist
        if (!file_exists($shop_file_name)) {
            $fp = fopen($shop_file_name, 'a');
            $head_data = $head_data_array;
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($shop_file_name)) {
            $fp = fopen($shop_file_name, 'w'); //get the file object
            $head_data = $head_data_array;
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $data_validate = $this->get('export_management.validate_data');
            //$citizen_profile_type = $this->citizen_profile_type;
            foreach ($shop_data as $array_data) {
                $check = 0;
                $data = array();
                $id = $array_data['id'];
                $parent_id = $array_data['parent_store_id'];
                $name = substr($array_data['name'], 0, 60);
                $email = rtrim($array_data['email'], '__');
                //$description  = $array_data['description'];
                $phone = $this->getPhone($array_data['phone']);
                $legalsatus = $array_data['legal_status'];
                if (trim($array_data['business_name']) == '') {
                    $businessname = '0000';
                } else {
                    $businessname = $this->getBusinessName($array_data['business_name'], $legalsatus);
                }

                $businesstype = strtoupper(substr($array_data['business_type'], 0, 5));
                $paymentstatus = $array_data['payment_status'];
                $businesscountry = $this->checkCountry($array_data['business_country']);
                $businessregion = strtoupper(substr($array_data['business_region'], 0, 5));
                $businesscity = substr($array_data['business_city'], 0, 30);
                $businessaddress = $this->getBusinessAddress($array_data['business_address']);
                $zip = substr($array_data['zip'], 0, 5);
                $province = strtoupper(substr($array_data['province'], 0, 2));

                $vatnumber = $this->getVatNumber($array_data['vat_number']);
                //$valid_vatnumber = $this->checkVatNumber($vatnumber);
                $valid_vatnumber = $data_validate->checkVatNumber($vatnumber); //call service

                $iban = $this->getIbanNumber($array_data['iban']);
                //$valid_iban = $this->varfyIban($iban);
                $valid_iban = $data_validate->varfyIban($iban); //call service

                $mapplace = substr($array_data['map_place'], 0, 30);
                $latitude = $array_data['latitude'];
                $longitude = $array_data['longitude'];

                $storeimage = $array_data['store_image'];
                $createdat = $array_data['created_at'];
                $isactive = $array_data['is_active'];
                $isallowed = $array_data['is_allowed'];
                $shop_status = $array_data['shop_status'];
                $credit_card_status = $array_data['credit_card_status'];


                //if valid vat number and valid iban number
                if (!$valid_vatnumber) {
                    $vatnumber = '';
                }
                if (!$valid_iban) {
                    $iban = '';
                }


                $data = array('id' => $id, 'parentid' => $parent_id, 'name' => $name,
                    'email' => $email,
                    'phone' => $phone, 'businessname' => $businessname,
                    'businesstype' => $businesstype, 'paymentstatus' => $paymentstatus,
                    'businesscountry' => $businesscountry,
                    'businessregion' => $businessregion, 'businesscity' => $businesscity, 'businessaddress' => $businessaddress, 'zip' => $zip,
                    'province' => $province, 'vatnumber' => $vatnumber,
                    'iban' => $iban,
                    'createdat' => $createdat, 'isactive' => $isactive, 'shopstatus' => $shop_status,
                    'creditcardstatus' => $credit_card_status
                );
                try {
                    fputcsv($fp, $data);
                } catch (\Exception $ex) {
                    
                }
            }
        }
        fclose($fp); //close the file
        exit('success');
        $file_name = 'shopexport.csv';
        $shope_file_name = $file_path . "/" . $file_name;

        $file_name_xml = 'shopexport.xml';
        $shope_file_name_xml = $file_path . "/" . $file_name_xml;

        $inputFilename = $shope_file_name;
        $outputFilename = $shope_file_name_xml;

// Open csv to read
        $inputFile = fopen($inputFilename, 'rt');

// Get the headers of the file
        $headers = fgetcsv($inputFile);

// Create a new dom document with pretty formatting
        $doc = new \DomDocument();
        $doc->formatOutput = true;

// Add a root node to the document
        $root = $doc->createElement('rows');
        $root = $doc->appendChild($root);

// Loop through each row creating a <row> node with the correct data
        while (($row = fgetcsv($inputFile)) !== FALSE) {
            $container = $doc->createElement('row');

            foreach ($headers as $i => $header) {
                $child = $doc->createElement($header);
                $child = $container->appendChild($child);
                $value = $doc->createTextNode($row[$i]);
                $value = $child->appendChild($value);
            }

            $root->appendChild($container);
        }

        //$doc->saveXML();
        $strxml = $doc->saveXML();
        $handle = fopen($outputFilename, "w");
        fwrite($handle, $strxml);
        fclose($handle);
        $body = file_get_contents($file_path."/".$file_name);
		$file_path_csv = $file_path."/".$file_name;
        $data_pdf = $this->get('card_management.pdf_export');
        $attachment_path = $file_path;
        $file_name_pdf = 'shopexport.pdf';
        $getpdf = $data_pdf->generatePdf($file_path_csv, $attachment_path, $file_name_pdf);
        
        die('success');
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
     * get the address
     * @param string $address
     * @return string $final_address
     */
    public function getAddress($address) {
        $final_address = '';
        $splited_address = preg_split("/[\s]+/", $address);
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
     * get the business name
     * @param string $business_name_only
     * @param string $legalsatus
     * @return string $final_address
     */
    public function getBusinessName($business_name_only, $legalsatus) {
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
     * Get business address
     * @param string $business_address
     * @return string
     */
    public function getBusinessAddress($business_address) {
        $final_address = '';
        $splited_address = preg_split("/[\s]+/", $business_address);
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
        $trim_zip = substr($zip, 0, 2);
        return $trim_zip;
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
     * Get vat number
     * @param string $vat
     * @return string
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
    private function checkVatNumber($variabile) {
        if ($variabile == '' || $variabile == 0)
            return false;
        //la p.iva deve essere lunga 11 caratteri
        if (strlen($variabile) != 11)
            return false;
        //la p.iva deve avere solo cifre
        if (!ereg("^[0-9]+$", $variabile))
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
     * Get vat number
     * @param string $iban
     * @return string
     */
    public function getIbanNumber($iban) {
        $iban_no = str_replace(' ', '', $iban);
        
        //remove IBAN text if present
       # Remove IIBAN or IBAN from start of string, if present
       $iban = preg_replace('/^I?IBAN/', '', $iban_no);
       # Remove all non basic roman letter / digit characters
       $iban = preg_replace('/[^a-zA-Z0-9]/', '', $iban);
    
        $iban_upper = strtoupper($iban);
        return $iban_upper;
    }

    /**
     * Varify Iban Number
     * @param string $iban
     * @return boolean
     */
    private function varfyIban($iban) {

        return verify_iban($iban);
    }

}
