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
use ExportManagement\ExportManagementBundle\Document\GiftcardExportLogs;

class GiftcardExportController extends Controller {

    protected $giftcard_export_transaction_path = "uploads/transaction/giftcardexporttransaction";
    protected $giftcard_export = 'giftcardexporttransiction';
    protected $giftcard_export_type = 'giftcardexport';
    protected $giftcard_export_database_log_type = 6;
    protected $gift_card = 'giftcard';
    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the giftcard daily filename
     * @return string
     */
    public function getGiftcardExportFileName() {
        $days_to_export = $this->container->getParameter('gift_card_export_interval');
        $file_name = $this->giftcard_export . "_" .$days_to_export."_days_". date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the shop weely transaction
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function giftcardexportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        //getting the number of days for export 
        $days_to_export = $this->container->getParameter('gift_card_export_days');
        //get giftcard record on the daily basic
        $giftcard_export_transaction_data = $em->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                ->getgiftcardexportTransaction($days_to_export);
        //check if we have some records for export
        if (count($giftcard_export_transaction_data)) {
            //exporting the data.
            $result = $this->exportGiftcardcsv($giftcard_export_transaction_data);
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
     * @param type $citizen_data
     */
    public function exportGiftcardcsv($giftcard_export_transaction_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web/" . $this->giftcard_export_transaction_path;
        $today_date       = new \DateTime('now');        
        //creating the file name.
        $file_name = $this->getGiftcardExportFileName();
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $giftcard_export_file_name = $file_path . "/" . $file_name;
        
        //check if file exist
        if (!file_exists($giftcard_export_file_name)) {
            $fp = fopen($giftcard_export_file_name, 'a');
            $head_data = array("id_citizen", "id_shop", "amount_gift_card", "date","startdate", "enddate");
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }
        //getting week interval dates.
        $date_array = $this->getStartEnddate();
        //if file exist then open it in write mode
        if (file_exists($giftcard_export_file_name)) {
            $fp = fopen($giftcard_export_file_name, 'w'); //get the file object
            $head_data = array("id_citizen", "id_shop", "amount_gift_card", "date","startdate", "enddate");
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
            //loop for writing the data in the csv file
            foreach ($giftcard_export_transaction_data as $array_data) {
                $user_id = $array_data['userId'];
                $shop_id = $array_data['shopId'];
                $gc_amount = $array_data['gc_amount'];
                $date = date('Y-m-d',$array_data['date']);
                $start_date    = $date_array['start_date'];
                $end_date      = $date_array['end_date'];

                $data = array('id_citizen' => $user_id, 'id_shop' => $shop_id, 'amount_gift_card' => $gc_amount, 'date' => $date, 'startdate'=>$start_date, 'enddate'=>$end_date);
                //Preparing the head for csv file.
                try {
                    fputcsv($fp, $data);
                } catch (\Exception $ex) {
                    continue;
                }
                //saving the data for logs
                //taking the mongodb doctrine object
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $giftcard_export_type = $this->giftcard_export_type;
                $profile_export = new GiftcardExportLogs();
                $profile_export->setUserId($user_id);
                $profile_export->setShopId($shop_id);
                $profile_export->setType($giftcard_export_type);
                $dm->persist($profile_export);
                $dm->flush();
            }
        }
        fclose($fp); //close the file

        $s3_file_path = $this->giftcard_export_transaction_path;
        $file_local_path = $file_path . '/' . $file_name;
        $exported_file = $this->s3fileUpload($s3_file_path, $file_local_path, $file_name);
        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($exported_file != '') { //means file uploaded on s3 server.
            $type = $this->giftcard_export_database_log_type;
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
            $payment_logs_Object->saveFileInfo($file_name, $type, $today_date);
        }
        return (($exported_file != '') ? $exported_file : '');
    }
    
    /**
     * Export gift cards, Latest discussion on 22 Jan 2015
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportgiftcardsAction(Request $request){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $days_interval = $this->container->getParameter('gift_card_export_interval');
        //get giftcard record on the daily basic
        $giftcard_export_transaction_data = $em->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                ->getExportGiftCards($days_interval);
        //check if we have some records for export
        if (count($giftcard_export_transaction_data)) {
            //exporting the data.
            $result = $this->exportGiftcardsDatacsv($giftcard_export_transaction_data);
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
     * Export Gift Card CSV
     * @param array $giftcard_export_transaction_data
     * @return string
     */
    public function exportGiftcardsDatacsv($giftcard_export_transaction_data)
    {
        $count = 0;
        $giftcard_export_type = $this->giftcard_export_type;
       //create a file path
        $file_path = __DIR__ . "/../../../../web/" . $this->giftcard_export_transaction_path;
        $today_date       = new \DateTime('now');        
        //creating the file name.
        $file_name = $this->getGiftcardExportFileName();
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $giftcard_export_file_name = $file_path . "/" . $file_name;
        
        //check if file exist

        $head_data = array("DATA", "NUMERO QUIETANZA", "TIPO QUIETANZA",
                               "CAUSALE","CODICE", "DESCRIZIONE","IMPORTO", "ID-SHOP");

            //loop for writing the data in the csv file
            foreach ($giftcard_export_transaction_data as $array_data) {
                $user_id = $array_data['userId'];
                $shop_id = $array_data['shopId'];
                $gc_amount = $this->convertCurrency($array_data['gc_amount']);
                $gc_amount_deciaml = $this->castToFloat($gc_amount);
                $date_purchased = $this->getDateFormat($array_data['date']);//date('m/d/Y',$array_data['date']);
                
                $purchase_year = date('y', $array_data['date']);
                $purchase_month = date('m', $array_data['date']);
                $purchase_day = date('d', $array_data['date']);
                //$export_date = date('m/d/Y',time());
                $current_year = date("Y");
                $start_time = strtotime("01/01/".$current_year);

                //get day diff from beginning of the year
                $purchased_date_string = $array_data['date'];

                $datediff = $purchased_date_string -  $start_time;
                $day_count = floor($datediff/(60*60*24))+1;

                //get NUMERO QUIETANZA
                $count = $count+1;
                $counter_value = str_pad($count, 2, "0", STR_PAD_LEFT); //add 0 in the beginning from 1-9
                $numero_quietanza = $purchase_year.$purchase_month.$purchase_day.$counter_value;
                $dscription = $array_data['giftCardId'];

                $data[] = array("DATA"=>$date_purchased, "NUMERO QUIETANZA"=>$numero_quietanza, "TIPO QUIETANZA"=>'SCC',
                               "CAUSALE"=>'CA',"CODICE"=>'CA'.$gc_amount, "DESCRIZIONE"=> $dscription,"IMPORTO"=>$gc_amount_deciaml, "ID-SHOP"=>$shop_id);
              
                //maintain log
                $this->saveGiftCardLog($user_id, $shop_id, $giftcard_export_type);
            }
        $giftcards_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs and change the file format
        $s3_file_path = $this->giftcard_export_transaction_path;
        $type = $this->gift_card;
        $item_type = $this->gift_card;
        $column = array("G");
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $type, $head_data, $data, $item_type, $column);

        //show file in admin section
        $this->saveFileToShowInAdmin($file_path, $file_name);
        return (($result != '') ? $result : '');
    }
    
    /**
     * Save the file to show in admin section
     */
    public function saveFileToShowInAdmin($file_path, $file_name)
    {
        $today_date       = new \DateTime('now'); 
        $s3_file_path = $this->giftcard_export_transaction_path;
        $file_local_path = $file_path . '/' . $file_name;
        $exported_file = $this->s3fileUpload($s3_file_path, $file_local_path, $file_name);
        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($exported_file != '') { //means file uploaded on s3 server.
            $type = $this->giftcard_export_database_log_type;
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
                        //check the file type to be exported.
            $file_type = $this->container->getParameter('exported_file_type');
            if ($file_type == 'xls') {
                $file_name = $this->convertCsvFileName($file_name). '.xls';
            } else {
                $file_name = $file_name;
            }
            $payment_logs_Object->saveFileInfo($file_name, $type, $today_date);
        }
    }
    
    /**
     * Maintain gift card export log object
     * @param type $user_id
     * @param type $shop_id
     * @param type $giftcard_export_type
     */
    public function saveGiftCardLog($user_id, $shop_id, $giftcard_export_type) {
        //saving the data for logs
        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $giftcard_export_type = $this->giftcard_export_type;
        $profile_export = new GiftcardExportLogs();
        $profile_export->setUserId($user_id);
        $profile_export->setShopId($shop_id);
        $profile_export->setType($giftcard_export_type);
        $dm->persist($profile_export);
        $dm->flush();
        return true;
    }

   
    
    /**
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount)
    {
        $final_amount = (float)$amount/1000000;
        return $final_amount;
    }
    
    /**
     * getting the citizen income file name
     * @return string
     */
    public function getGiftCardFileName() {
        $file_name = $this->gift_card . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Upload documents on s3 server
     * @param string $s3filepath
     * @param string $file_local_path
     * @param string $filename
     * @return string $file_url
     */
    public function s3fileUpload($s3filepath, $file_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $file_url = $amazan_service->ImageS3UploadService($s3filepath, $file_local_path, $filename);
        return $file_url;
    }
    
    /**
     * getting a week interval dates including today
     * @return array
     */
    public function getStartEnddate()
    {
        //calculating previous(7 days) 7 days interval.
        $days_to_export = $this->container->getParameter('gift_card_export_days');
        $yesterday      =  new \DateTime('yesterday');
        $end_date       =  $yesterday->format('Y-m-d');
        $previous_date  =  new \DateTime("-$days_to_export days");
        $start_date     =  $previous_date->format('Y-m-d');
        return array('start_date'=>$start_date, 'end_date'=>$end_date);
    }
    
    /**
     * finding the csv file name
     * @param string $file_name
     * @return type
     */
    public function convertCsvFileName($file_name) {
        return rtrim($file_name, '.csv');
    }
    
    /**
     * Generate number with two decimal places.
     * @return string
     */
    private function castToFloat($number){
        return number_format((float)$number, 2, '.', '');
    }
    
    /**
     * Get Date format
     * @param string $data
     * @return string
     */
    public function getDateFormat($data){
        return date('d/m/Y',$data);
    }
}
