<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class OneTimeExportController extends Controller {

    protected $broker_profile_path = "/uploads/users/onetime/broker";
    protected $broker_user = 'broker';
    protected $citizen_profile_path = "/uploads/users/onetime/citizen";
    protected $citizen_user = 'citizen';

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the shop filename
     * @return string
     */
    public function getShopFileName() {
        $file_name = $this->shop_profile_type . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the shop user profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function brokeruserexportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $result = '';
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $broker_profile_data = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->getBrokerProfile();
        if (count($broker_profile_data)) {
            //exporting the data.
            $result = $this->exportbrokercsv($broker_profile_data);
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
     * getting the citizen income file name
     * @return string
     */
    public function getBrokerUserFileName() {
        $file_name = $this->broker_user . ".csv";
        return $file_name;
    }

    /**
     * Writing the file.
     * @param type $broker_profile_data
     */
    public function exportbrokercsv($broker_profile_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->broker_profile_path;

        //creating the file name.
        $file_name = $this->getBrokerUserFileName();
        // $type        = $this->citizen_income_utilized_database_log_type;
        $date = new \DateTime('now');
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $broker_file_name = $file_path . "/" . $file_name;
        $header_data = array("id", "username", "email", "firstname", "lastname", "gender", "phone", "country", "dob", "vatnumber", "fiscalcode", "iban",
            "active");
        //check if file exist
        if (!file_exists($broker_file_name)) {
            $fp = fopen($broker_file_name, 'a');
            $head_data = $header_data;
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($broker_file_name)) {
            $fp = fopen($broker_file_name, 'w'); //get the file object
            $head_data = $header_data;
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            //getting the service for check the data validation.
            $data_validate = $this->get('export_management.validate_data');
            // $citizen_income_type = $this->citizen_income_utilized_type;
            foreach ($broker_profile_data as $array_data) {
                $check = 0;
                $id = $array_data['id'];
                $user_name = $array_data['username'];
                $email = $array_data['email'];
                $firstname = $array_data['firstname'];
                $lastname = $array_data['lastname'];
                $gender = $array_data['gender'];
                $phone = $this->getPhone($array_data['phone']);
                $country = $this->checkCountry($array_data['country']);
                $dob = $array_data['dob'];
                $vat_number = $array_data['vatnumber'];
                $vat_check = $data_validate->checkVatNumber($vat_number);
                $fiscal_code = strtoupper($array_data['fiscalcode']);
                $fiscal_code_check = $this->getFiscalCode($fiscal_code);
                $iban = $this->getIbanNumber($array_data['iban']);
                $iban_check = $data_validate->varfyIban($iban);
                $active = $array_data['active'];

                if (!$vat_check) { //check if vat number and iban and fiscal code is valid
                    $vat_number = '';
                }
                if (!$iban_check) { //check for iban
                    $iban = '';
                }
                if (!$fiscal_code_check) { // check for fiscal code
                    $fiscal_code = '';
                }
                $data = array("id" => $id, "username" => $user_name, "email" => $email, "firstname" => $firstname, "lastname" => $lastname, "gender" => $gender, "phone" => $phone,
                    "country" => $country, "dob" => $dob, "vatnumber" => $vat_number, "fiscalcode" => $fiscal_code, "iban" => $iban,
                    "active" => $active);
                try {
                    fputcsv($fp, $data); //write the file
                } catch (\Exception $ex) {
                    
                }
            }
        }
        fclose($fp); //close the file
        return 'true';
    }

    /**
     * Function to retrieve s3 server base url
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }

    /**
     * Exporting the citizen user profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function citizenuserexportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $result = '';
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $citizen_profile_data = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->getCitizenProfile();
        if (count($citizen_profile_data)) {
            //exporting the data.
            $result = $this->exportcitizencsv($citizen_profile_data);
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
     * @param type $citizen_profile_data
     */
    public function exportcitizencsv($citizen_profile_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->citizen_profile_path;

        //creating the file name.
        $file_name = $this->getCitizenUserFileName();
        // $type        = $this->citizen_income_utilized_database_log_type;
        $date = new \DateTime('now');
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $citizen_file_name = $file_path . "/" . $file_name;
        $header_data = array('id', 'username', 'email', 'firstname', 'lastname', 'gender', 'phone', 'country', 'dob', 'region',
            'city', 'address', 'zip', 'createdat');
        //check if file exist
        if (!file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'a');
            $head_data = $header_data;
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'w'); //get the file object
            $head_data = $header_data;
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            /// $citizen_income_type = $this->citizen_income_utilized_type;
            foreach ($citizen_profile_data as $array_data) {
                $id = $array_data['id'];
                $user_name = $array_data['username'];
                $email = $array_data['email'];
                $firstname = $array_data['firstname'];
                $lastname = $array_data['lastname'];
                $gender = $array_data['gender'];
                $phone = $this->getPhone($array_data['phone']);
                $country = $this->checkCountry($array_data['country']);
                $dob = $array_data['dob'];
                $region = $array_data['region'];
                $city = $this->getCity($array_data['city']); //get city 
                $address = $this->getAddress($array_data['address']); //get address
                $zip = $this->getZip($array_data['zip']); //get zip
                $created_at = $array_data['created_at'];

                $data = array('id' => $id, 'username' => $user_name, 'email' => $email, 'firstname' => $firstname, 'lastname' => $lastname, 'gender' => $gender, 'phone' => $phone,
                    'country' => $country, 'dob' => $dob, 'region' => $region, 'city' => $city, 'address' => $address,
                    'zip' => $zip, 'createdat' => $created_at);
                try {
                    fputcsv($fp, $data); //write the file
                } catch (\Exception $ex) {
                    
                }
            }
        }
        fclose($fp); //close the file
        return 'true';
    }

    /**
     * getting the citizen income file name
     * @return string
     */
    public function getCitizenUserFileName() {
        $file_name = $this->citizen_user . ".csv";
        return $file_name;
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

}
