<?php

namespace ExportManagement\ExportManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Session\Session;
use ExportManagement\ExportManagementBundle\Document\ProfileExport;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

// shop service for export through command
class ShopService {

    protected $em;
    protected $dm;
    protected $container;
    protected $shop_profile = "/uploads/users/exportprofile/shop";
    protected $shop_profile_type = 'SHOP';
    protected $shop_file_sheet   = 'SHOP';
    CONST VAT_CONST = "&";
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    /**
     * getting the shop filename
     * @return string
     */
    public function getShopFileName() {
        $file_name = $this->shop_profile_type .".csv";
        return $file_name;
    }
    
    /**
     * getting the shop filename with date
     * @return string
     */
    public function getShopFileNameWithDate() {
        $file_name_date = date('Ymd').$this->shop_profile_type . ".csv";
        return $file_name_date;
    }

    /**
     * get shop file sheet name
     * @return string
     */
    public function getShopFileSheetName() {
        return $this->shop_file_sheet;
    }
    
    /**
     * Exporting the shop user profile
     */
    public function exportshopprofile() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $result = '';
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_profile_data = $em->getRepository('StoreManagerStoreBundle:Store')
                ->getStoreProfile();
       // if (count($shop_profile_data)) {
            //exporting the data.
            $result = $this->exportshopcsv($shop_profile_data);
       // }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'ERROR_IN_UPLOADING', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $shop_data
     */
    public function exportshopcsv($shop_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_profile;

        //creating the file name.
        $file_name = $this->getShopFileName();
        
        //creating the file name with date
        $file_name_date = $this->getShopFileNameWithDate();
        //sheet name
        $sheet_name = $this->getShopFileSheetName();
        
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        //$shop_file_name = $file_path . "/" . $file_name;
        $data = array();
        $head_data_array = array('ID', 'PARENTID', 'NAME', 'EMAIL', 'PHONE', 'BUSINESSNAME', 'BUSINESSTYPE', 'PAYMENTSTATUS',
            'BUSINESSCOUNTRY', 'BUSINESSREGION', 'BUSINESSCITY', 'BUSINESSADDRESS', 'ZIP', 'PROVINCE', 'VATNUMBER', 'IBAN',
            'CREATEDAT', 'ISACTIVE', 'SHOPSTATUS', 'CREDITCARDSTATUS', 'SSN');

        //taking the mongodb doctrine object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //getting the service for check the data validation.
        $data_validate = $this->container->get('export_management.validate_data');
        $shop_profile_type = $this->shop_profile_type;
        foreach ($shop_data as $array_data) {
            $shop_id = $array_data->getId();
            $parent_id = $array_data->getparentStoreId();
            $name = $data_validate->getStoreName($array_data->getName());
            $email = $data_validate->getStoreEmail($array_data->getEmail());
            $phone = $data_validate->getPhone($array_data->getPhone());
            $business_name = trim($array_data->getBusinessName());
            $legal_status = $array_data->getLegalStatus();
            if ($business_name == '') {
                $final_business_name = '0000';
            } else {
                $final_business_name = $data_validate->getStoreBusinessName($business_name, $legal_status);
            }
            $business_type = $data_validate->getStoreBusinessType($array_data->getBusinessType());
            $payment_status = $array_data->getPaymentStatus();
            $business_country = $data_validate->checkCountry($array_data->getBusinessCountry());
            $business_region = $data_validate->getStoreBusinessRegion($array_data->getBusinessRegion());
            $business_city = $data_validate->getStoreBusinessCity($array_data->getBusinessCity());
            $business_address = $data_validate->getBusinessAddress($array_data->getBusinessAddress());
            $zip = $data_validate->getStoreZip($array_data->getZip());
            $province = $data_validate->getStoreProvience($array_data->getProvince());
            $vat_number = $data_validate->getVatNumber($array_data->getVatNumber());
            $valid_vatnumber = $data_validate->checkVatNumber($vat_number); //call service

            $iban = $data_validate->getIbanNumber($array_data->getIban());
            $valid_iban = $data_validate->varfyIban($iban); //call service

            $created_at = (($array_data->getUpdatedAt() != NULL) ? $array_data->getUpdatedAt()->format('Y-m-d') : ''); //here we are picking up updated at field
            $is_active = $array_data->getIsActive();
            $shop_status = (($array_data->getShopStatus() == 1) ? $array_data->getShopStatus() : 0);
            $credit_card_status = $array_data->getCreditCardStatus();
            $ssn = $data_validate->validateFiscalCode($array_data->getFiscalCode());
            
            //if valid vat number not valid
            if (!$valid_vatnumber) {
                $vat_number = '';
            }
            //if valid iban number not valid
            if (!$valid_iban) {
                $iban = '';
            }
            $vat_number = self::VAT_CONST.$vat_number;
            $data[] = array("ID" => $shop_id, "PARENTID" => $parent_id, "NAME" => $name, "EMAIL" => $email, "PHONE" => $phone,
                "BUSINESSNAME" => $final_business_name, "BUSINESSTYPE" => $business_type, "PAYMENTSTATUS" => $payment_status,
                "BUSINESSCOUNTRY" => $business_country, "BUSINESSREGION" => $business_region, "BUSINESSCITY" => $business_city,
                "BUSINESSADDRESS" => $business_address, "ZIP" => $zip, "PROVINCE" => $province, "VATNUMBER" => $vat_number, "IBAN" => $iban,
                "CREATEDAT" => $created_at, "ISACTIVE" => $is_active, "SHOPSTATUS" => $shop_status, "CREDITCARDSTATUS" => $credit_card_status, 'SSN'=>$ssn);
        }
        $column_format = array();
        $column_left   =  array('A', 'O');
        $column_cast   =  array('A', 'O');
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportFiles($file_path, $file_name, $file_name_date, $sheet_name, $shop_profile_type, $head_data_array, $data, $column_format, $column_left, $column_cast);
        return $result;
    }

    /**
     * Upload documents on s3 server
     * @param string $s3filepath
     * @param string $file_local_path
     * @param string $filename
     * @return string $file_url
     */
    public function s3imageUpload($s3filepath, $file_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $file_url = $amazan_service->ImageS3UploadService($s3filepath, $file_local_path, $filename);
        return $file_url;
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
     
}
