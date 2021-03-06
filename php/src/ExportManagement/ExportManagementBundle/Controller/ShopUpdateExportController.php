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

class ShopUpdateExportController extends Controller {

    protected $shop_update_profile = "/uploads/users/exportprofile/shopupdate";
    protected $shop_update_profile_type = 'shopupdate';

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the shop filename
     * @return string
     */
    public function getShopUpdateFileName() {
        $file_name = $this->shop_update_profile_type . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the shop update profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportshopupdateprofileAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $result = '';
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_profile_data = $em->getRepository('StoreManagerStoreBundle:Store')
                ->getStoreUpdateProfile();
        if (count($shop_profile_data)) {
            //exporting the data.
            $result = $this->exportshopcsv($shop_profile_data);
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
     * @param type $shop_data
     */
    public function exportshopcsv($shop_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_update_profile;

        //creating the file name.
        $file_name = $this->getShopUpdateFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $shop_file_name = $file_path . "/" . $file_name;
        $data = array();
        $head_data_array = array('id', 'parentid', 'name', 'email', 'phone', 'businessname', 'businesstype', 'paymentstatus',
            'businesscountry', 'businessregion', 'businesscity', 'businessaddress', 'zip', 'province', 'vatnumber', 'iban',
            'createdat', 'updatedat', 'isactive', 'shopstatus', 'creditcardstatus');

        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //getting the service for check the data validation.
        $data_validate = $this->get('export_management.validate_data');
        $shop_profile_type = $this->shop_update_profile_type;
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
            $valid_vatnumber = $data_validate->checkVatNumber($vat_number); //call service for vat number check

            $iban = $data_validate->getIbanNumber($array_data->getIban());
            $valid_iban = $data_validate->varfyIban($iban); //call service for iban check

            $created_at = (($array_data->getCreatedAt() != NULL) ? $array_data->getCreatedAt()->format('Y-m-d') : '');
            $updated_at = (($array_data->getUpdatedAt() != NULL) ? $array_data->getUpdatedAt()->format('Y-m-d') : '');
            $is_active = $array_data->getIsActive();
            $shop_status = $array_data->getShopStatus();
            $credit_card_status = $array_data->getCreditCardStatus();

            //if valid vat number not valid
            if (!$valid_vatnumber) {
                $vat_number = '';
            }
            //if valid iban number not valid
            if (!$valid_iban) {
                $iban = '';
            }

            $data[] = array("id" => $shop_id, "parentid" => $parent_id, "name" => $name, "email" => $email, "phone" => $phone,
                "businessname" => $final_business_name, "businesstype" => $business_type, "paymentstatus" => $payment_status,
                "businesscountry" => $business_country, "businessregion" => $business_region, "businesscity" => $business_city,
                "businessaddress" => $business_address, "zip" => $zip, "province" => $province, "vatnumber" => $vat_number, "iban" => $iban,
                "createdat" => $created_at, "updatedat" => $updated_at, "isactive" => $is_active, "shopstatus" => $shop_status, "creditcardstatus" => $credit_card_status);
        }

        $s3_file_path = "uploads/users/exportprofile/shopupdate";
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportFiles($file_path, $file_name, $s3_file_path, $shop_profile_type, $head_data_array, $data);
        return $result;
    }

    /**
     * Exporting the shop update profile in second time(or further)
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportshopupdateprofilebacklogsAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //get mongodb object.
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $shop_profile_logs_data = $dm->getRepository('ExportManagementBundle:ProfileExport')
                ->findBy(array('type' => $this->shop_update_profile_type), array('id' => 'DESC'), 1, 0);
        $last_shop_id = 0;
        $result = '';

        //creating the file name.
        $file_name = $this->getShopUpdateFileName();
        //getting the last citizen profile exported id..
        if (count($shop_profile_logs_data)) {
            $last_shop_id = $shop_profile_logs_data[0]->getUserId();
        }
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_profile_data = $em->getRepository('StoreManagerStoreBundle:Store')
                ->getStoreUpdateProfileBackLogs($last_shop_id);

        //if any profileleft fro exporting..
        if (count($shop_profile_data)) {
            //exporting the data.
            $exported_result = $this->exportshopcsvbacklogs($shop_profile_data);
            $result = $exported_result;
        }

        if ($result != '') {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_PROFILE_FOR_EXPORT', 'data' => array());
        }

        echo json_encode($data);
        exit;
    }

    /**
     * Export the users those are left from first attempt
     * @param $shop_profile_data
     * @return $result_link
     */
    public function exportshopcsvbacklogs($shop_profile_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_update_profile;

        //creating the file name.
        $file_name = $this->getShopUpdateFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $shop_file_name = $this->getS3BaseUri() . $this->shop_update_profile . "/" . $file_name;
        $destination_file = $file_path . "/" . $file_name;

        //copy the csv from s3 to local.
        @copy($shop_file_name, $destination_file);
        $result_link = $this->exportshopcsvlogs($shop_profile_data);
        return $result_link;
    }

    /**
     * Writing the file.
     * @param type $shop_data
     * @return string 
     */
    public function exportshopcsvlogs($shop_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_update_profile;

        //creating the file name.
        $file_name = $this->getShopUpdateFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $shop_file_name = $file_path . "/" . $file_name;
        $data = array();
        $head_data_array = array('id', 'parentid', 'name', 'email', 'phone', 'businessname', 'businesstype', 'paymentstatus',
            'businesscountry', 'businessregion', 'businesscity', 'businessaddress', 'zip', 'province', 'vatnumber', 'iban',
            'createdat', 'updatedat', 'isactive', 'shopstatus', 'creditcardstatus');

            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            //getting the service for check the data validation.
            $data_validate = $this->get('export_management.validate_data');

            $shop_profile_type = $this->shop_update_profile_type;
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
                $valid_vatnumber = $data_validate->checkVatNumber($vat_number); //call service for vat number

                $iban = $data_validate->getIbanNumber($array_data->getIban());
                $valid_iban = $data_validate->varfyIban($iban); //call service for iban check

                $created_at = (($array_data->getCreatedAt() != NULL) ? $array_data->getCreatedAt()->format('Y-m-d') : '');
                $updated_at = (($array_data->getUpdatedAt() != NULL) ? $array_data->getUpdatedAt()->format('Y-m-d') : '');
                $is_active = $array_data->getIsActive();
                $shop_status = $array_data->getShopStatus();
                $credit_card_status = $array_data->getCreditCardStatus();

                //if valid vat number not valid
                if (!$valid_vatnumber) {
                    $vat_number = '';
                }
                //if valid iban number not valid
                if (!$valid_iban) {
                    $iban = '';
                }

                $data[] = array("id" => $shop_id, "parentid" => $parent_id, "name" => $name, "email" => $email, "phone" => $phone,
                    "businessname" => $final_business_name, "businesstype" => $business_type, "paymentstatus" => $payment_status,
                    "businesscountry" => $business_country, "businessregion" => $business_region, "businesscity" => $business_city,
                    "businessaddress" => $business_address, "zip" => $zip, "province" => $province, "vatnumber" => $vat_number, "iban" => $iban,
                    "createdat" => $created_at, "updatedat" => $updated_at, "isactive" => $is_active, "shopstatus" => $shop_status, "creditcardstatus" => $credit_card_status);

            }

        $s3_file_path = "uploads/users/exportprofile/shopupdate";
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportFiles($file_path, $file_name, $s3_file_path, $shop_profile_type, $head_data_array, $data);
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
