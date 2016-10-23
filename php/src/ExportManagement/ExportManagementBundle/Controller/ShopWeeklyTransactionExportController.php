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
use Transaction\TransactionBundle\Entity\CitizenIncomeToPayToStore;
use ExportManagement\ExportManagementBundle\Entity\PaymentExport;

class ShopWeeklyTransactionExportController extends Controller {

    protected $shop_weekly_transaction_path = "/uploads/transaction/shopweeklytransaction";
    protected $shop = 'shopweeklytransaction';
    protected $shop_weekly_type = 'shopweekly';
    protected $shop_weekly_database_log_type = 1;
    protected $shop_daily_registration_path = "/uploads/transaction/shopdailyregistration";
    protected $shop_registration = 'shopdailyregistration';
    protected $shop_daily_registration_type = 'shopdailyregistration';
    protected $shop_daily_registration_database_log_type = 3;
    protected $shop_daily_registration_received_path = "/uploads/transaction/sixthcontinentregistration";
    protected $shop_received_registration = 'sixthcontinentregistration';
    protected $shop_daily_received_registration_type = 'sixthcontinentregistration';
    protected $shop_daily_registration_pending_registration_database_log_type = 5;

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the shop filename
     * @return string
     */
    public function getShopFileName() {
        $file_name = $this->shop . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the shop weely transaction
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportshopweeklytransactionAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_weekly_transaction_data = $em->getRepository('TransactionTransactionBundle:CitizenIncomeToPayToStore')
                ->getShopWeeklyTransaction();
        if (count($shop_weekly_transaction_data)) {
            //exporting the data.
            $result = $this->exportshopweeklycsv($shop_weekly_transaction_data);
        }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA_FOR_EXPORT', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $shop_transaction_data
     */
    public function exportshopweeklycsv($shop_transaction_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_weekly_transaction_path;

        //creating the file name.
        $file_name = $this->getShopFileName();
        $type = $this->shop_weekly_database_log_type;
        $date = new \DateTime('now');
        $item_type = 'shop';
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $shop_file_name = $file_path . "/" . $file_name;
        $data = array();
        $head_data_array = array("ID-SHOP", "IMPORTO", "DATA-DI-INIZIO", "DATA-DI-FINE");
        $columns = array('B');

        //getting week interval dates.
        $date_array = $this->getWeeklydate();

        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $shop_weekly_transaction_type = $this->shop_weekly_type;
        foreach ($shop_transaction_data as $array_data) {
            $shop_id = $array_data['shop_id'];
            $amount = $this->convertCurrency($array_data['amount']);
            $start_date = $date_array['start_date'];
            $end_date = $date_array['end_date'];
            $data[] = array('shopid' => $shop_id, 'amount' => $amount, 'startdate' => $start_date, 'enddate' => $end_date);
        }

        $s3_file_path = "uploads/transaction/shopweeklytransaction";

        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $shop_weekly_transaction_type, $head_data_array, $data, $item_type, $columns);

        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($result != '') { //means file uploaded on s3 server.
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
            //check the file type to be exported.
            $file_type = $this->container->getParameter('exported_file_type');
            if ($file_type == 'xls') {
                $file_name = $this->convertCsvFileName($file_name) . '.xls';
            } else {
                $file_name = $file_name;
            }
            $payment_logs_Object->saveFileInfo($file_name, $type, $date);
        }
        return (($result != '') ? $result : '');
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
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }

    /**
     * getting a week interval dates including today
     * @return array
     */
    public function getWeeklydate() {
        //calculating previous(7 days) 7 days interval.
        $yesterday = new \DateTime('yesterday');
        $end_date = $yesterday->format('d/m/Y');
        $previous_date = new \DateTime('-7 days');
        $start_date = $previous_date->format('d/m/Y');
        return array('start_date' => $start_date, 'end_date' => $end_date);
    }

    //shop weekly registration export work start from here.

    /**
     * getting the shop registration filename
     * @return string
     */
    public function getShopRegistrationFileName() {
        $file_name = $this->shop_registration . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Export the daily registration fee for stores.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportshopdailyregistrationAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_daily_registration_data = $em->getRepository('CardManagementBundle:ShopRegPayment')
                ->getDailyShopRegistartionInfo();
        $result = array('shopdailyfees' => '');
        if (count($shop_daily_registration_data)) {
            //exporting the data.
            $result = $this->exportshopregistrationdailycsv($shop_daily_registration_data);
        }
        if ($result['shopdailyfees'] != '') {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('links' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA_FOR_EXPORT', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $shop_registration_payment_data
     */
    public function exportshopregistrationdailycsv($shop_registration_payment_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_daily_registration_path;

        //creating the file name.
        $file_name = $this->getShopRegistrationFileName();
        $type = $this->shop_daily_registration_database_log_type;
        $date = new \DateTime('now');

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        //$shop_file_name = $file_path . "/" . $file_name;
        $data = array();
        $item_type = 'shop';
        $head_data_array = array("DATA", "CAUSALE", "CODICE", "DESCRIZIONE", "DESCRIZIONE 2", "IMPORTO", "ID-SHOP", "IMPORTO_PIU_IVA");
        $columns = array('F', 'H');
        //taking the mongodb doctrine object
        $shop_daily_registration_type = $this->shop_daily_registration_type;
        $causale = 'FTAFF'; //registration fee motivation 
        $codice = 'AFF'; //registration fee code
        $description = 'ADESIONE CIRCUITO PUBBLICITARIO';
        $description2 = 'SIXTHCONTINENT ITALIA SRL';
        //prepare the data
        foreach ($shop_registration_payment_data as $array_data) {
            $shop_id = $array_data['shop_id'];
            $amount = $this->convertCurrency($array_data['registration_fee']);
            $amount_vat = $this->convertCurrency($array_data['registration_fee'] + $array_data['registration_vat']);
            $transaction_time = $array_data['transaction_time']->format('d/m/Y');
            $data[] = array('DATA' => $transaction_time, 'CAUSALE' => $causale, 'CODICE' => $codice,
                'DESCRIZIONE' => $description, 'DESCRIZIONE 2' => $description2, 'IMPORTO' => $amount, 'shopid' => $shop_id, 'IMPORTO_PIU_IVA' => $amount_vat);
        }

        $s3_file_path = "uploads/transaction/shopdailyregistration";
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $shop_daily_registration_type, $head_data_array, $data, $item_type, $columns);

        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($result != '') { //means file uploaded on s3 server.
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
            //check the file type to be exported.
            $file_type = $this->container->getParameter('exported_file_type');
            if ($file_type == 'xls') {
                $file_name = $this->convertCsvFileName($file_name) . '.xls';
            } else {
                $file_name = $file_name;
            }
            $payment_logs_Object->saveFileInfo($file_name, $type, $date);
        }
        //move the same file into the sixthcontinent registration path(different location).
        $exported_moved_file = '';
        //end code for moving the file into another location.
        $data_out = array('shopdailyfees' => $result, 'sixthcontinentrefistartion' => $exported_moved_file);
        return $data_out;
    }

    /**
     * Export the daily registration fee received by sixthcontinent payed from shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportsixthcontinentincomefromshopregistrationAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_daily_registration_data = $em->getRepository('CardManagementBundle:ShopRegPayment')
                ->getDailyShopRegistartionFeeReceivedInfo();
        if (count($shop_daily_registration_data)) {
            //exporting the data.
            $result = $this->exportdailyrecivedregistrationfeecsv($shop_daily_registration_data);
        }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA_FOR_EXPORT', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * getting the shop registration filename
     * @return string
     */
    public function getSixthcontinentRegistrationFileName() {
        $file_name = $this->shop_received_registration . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Writing the file.
     * @param type $shop_registration_payment_data
     */
    public function exportdailyrecivedregistrationfeecsv($shop_registration_payment_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_daily_registration_received_path;

        //creating the file name.
        $file_name = $this->getSixthcontinentRegistrationFileName();
        $type = $this->shop_daily_registration_pending_registration_database_log_type;
        $date = new \DateTime('now');
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        //$shop_file_name = $file_path . "/" . $file_name;
        $item_type = 'shop';
        $data = array();
        $head_data_array = array("PROGR", "DATA", "CAUSALE", "ID-SHOP", "CODICE", "DESCRIZIONE", "DESCRIZIONE 2", "IMPORTO", "IMPORTO_PIU_IVA");
        $column = array('H', 'I');
        //$x% in parameter file
        $x = $this->container->getParameter('ci_percentage');
        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $shop_daily_registration_type = $this->shop_daily_received_registration_type;
        $i = 1;
        foreach ($shop_registration_payment_data as $array_data) {
            $shop_id = $array_data['shop_id'];
            $transaction_time = $array_data['created_at']->format('d/m/Y');
            $progr = 'T'.$i;
            if ($array_data['transaction_type'] == 'R') { //registration fee
                $amount  = $this->convertCurrency($array_data['registration_fee']);
                $total_amount_vat = $this->convertCurrency($array_data['registration_fee'] + $array_data['registration_vat']); //amount+vat(vat is already included)
                $causale =  'FTAFF'; //registration fee motivation 
                $codice  =  'AFF'; //registration fee code
                $description = 'ADESIONE CIRCUITO PUBBLICITARIO';
                $description2 = 'SIXTHCONTINENT ITALIA SRL';                
            } else if ($array_data['transaction_type'] == 'P') { //pending amount
                $amount  = $this->convertCurrency($array_data['pending_amount'] - $array_data['pending_vat']);
                $total_amount_vat = $this->convertCurrency($array_data['pending_amount']); //amount+vat(vat is already included)
                $causale = 'RC' . $x.'PC'; //pending amount motivation 
                $codice  = $x.'PC';        //pending amount code
                $description  = "CORRISPETTIVO PUBBLICITA'";
                $description2 = 'PER VENDITE EFFETTUATE';                
            }
            if ($array_data['transaction_type'] == 'T') { //both(registration fee + pending amount)
                //for registration fee
                $amount  = $this->convertCurrency($array_data['registration_fee']);
                $total_amount_vat = $this->convertCurrency($array_data['registration_fee'] + $array_data['registration_vat']); //amount+vat(vat is already included)
                $causale =  'FTAFF'; //registration fee motivation 
                $codice  =  'AFF'; //registration fee code
                $description = 'ADESIONE CIRCUITO PUBBLICITARIO';
                $description2 = 'SIXTHCONTINENT ITALIA SRL';
                $data[] = array('PROGR'=>$progr, 'DATA' => $transaction_time, 'CAUSALE' => $causale, 'shopid' => $shop_id, 'CODICE' => $codice,
                    'DESCRIZIONE' => $description, 'DESCRIZIONE 2' => $description2, 'IMPORTO' => $amount, 'IMPORTO_PIU_IVA' => $total_amount_vat);
                $i++;
                //for x%
                $causale = 'RC' . $x.'PC'; //pending amount motivation 
                $codice  = $x.'PC';        //pending amount code
                $description  = "CORRISPETTIVO PUBBLICITA'";
                $description2 = 'PER VENDITE EFFETTUATE';
                $progr   = 'T'.$i;
                $amount  = $this->convertCurrency($array_data['pending_amount']-$array_data['pending_vat']);
                $total_amount_vat = $this->convertCurrency($array_data['pending_amount']); //amount+vat(vat is already included)
                $data[] = array('PROGR'=>$progr, 'DATA' => $transaction_time, 'CAUSALE' => $causale, 'shopid' => $shop_id, 'CODICE' => $codice,
                    'DESCRIZIONE' => $description, 'DESCRIZIONE 2' => $description2, 'IMPORTO' => $amount, 'IMPORTO_PIU_IVA' => $total_amount_vat);
            } else {
                $data[] = array('PROGR'=>$progr, 'DATA' => $transaction_time, 'CAUSALE' => $causale, 'shopid' => $shop_id, 'CODICE' => $codice,
                    'DESCRIZIONE' => $description, 'DESCRIZIONE 2' => $description2, 'IMPORTO' => $amount, 'IMPORTO_PIU_IVA' => $total_amount_vat);
            }
            $i++;
        }

        $s3_file_path = "uploads/transaction/sixthcontinentregistration";
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $shop_daily_registration_type, $head_data_array, $data, $item_type, $column);

        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($result != '') { //means file uploaded on s3 server.
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
            //check the file type to be exported.
            $file_type = $this->container->getParameter('exported_file_type');
            if ($file_type == 'xls') {
                $file_name = $this->convertCsvFileName($file_name). '.xls';
            } else {
                $file_name = $file_name;
            }            
            $payment_logs_Object->saveFileInfo($file_name, $type, $date);
        }
        return (($result != '') ? $result : '');
    }

    /**
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount) {
        $final_amount = (float) $amount / 1000000;
        // return round($final_amount, 2);
        return number_format($final_amount, 2, '.', ''); //set precision 2 places.
    }

    /**
     * finding the csv file name
     * @param string $file_name
     * @return type
     */
    public function convertCsvFileName($file_name) {
        return rtrim($file_name, '.csv');
    }

}
