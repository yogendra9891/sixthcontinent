<?php

namespace ExportManagement\ExportManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Session\Session;
use ExportManagement\ExportManagementBundle\Document\ProfileExport;
use Ijanki\Bundle\FtpBundle\Exception\FtpException;
use ExportManagement\ExportManagementBundle\Entity\Purchase;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

// purchase service for import and export through command
class PurchaseService {

    protected $em;
    protected $dm;
    protected $container;
    protected $base_six = 1000000;
    protected $purchase_export_transaction_path = "uploads/transaction/purchase";
    protected $purchase_export = 'PURCHASE';
    protected $purchase_export_type = 'PURCHASE';
    protected $purchase_export_sheet_name = 'PURCHASE';

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
     * Import the shop purchase
     */
    public function purchaseimport() {
        $handler = $this->container->get('monolog.logger.purchased_cards_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [purchaseimport]', array());  //write log
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        //finding the transactions ids of transaction get done yesterday(manually)/ Today(recuuring)
        $transaction_ids = $this->getTransactionIds();
        $today = new \DateTime('now');
        $today_date = $today->format('Y-m-d');

//        if (!count($transaction_ids)) {
//            $applane_service->writeAllLogs($handler, 'NO Transaction ids. ', array());  //write log
//            exit('NO_DATA');
//        }
        //get data to be imported
        $purchase_data = array();
        $res_data = array();
        $data['transaction_ids'] = $transaction_ids;
        //get transaction data
        $import_purchase_data = $applane_service->getpurchasetransactiondata($data); //get data from applane of previous day.
        $purchased = $this->toArray($import_purchase_data);

        $applane_service->writeAllLogs($handler, 'Data for import today: ' . $this->convertToJson($purchased), array());  //write log
        if ($import_purchase_data->code == 200) {
            $purchase_data = $import_purchase_data->response->result;
        }
        //check if we have some records for import
        if (count($purchase_data)) {
            //import the data.
            $result = $this->importPurchase($purchase_data);
        }
        $this->importConnectTypePurchase(); //import connect type transactions
        if (!empty($result)) {
            $applane_service->writeAllLogs($handler, 'DATA is imported on date: ' . $today_date, array());  //write log
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        } else {
            $applane_service->writeAllLogs($handler, 'No data is for import on date: ' . $today_date, array());  //write log
            $data = array('code' => 100, 'message' => 'NO_DATA', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * convert to array
     * @param object $data
     * @return type
     */
    public function toArray($data) {
        $array = get_object_vars($data);
        unset($array['_parent'], $array['_index']);
        array_walk_recursive($array, function (&$property) {
            if (is_object($property) && method_exists($property, 'toArray')) {
                $property = $property->toArray();
            }
        });
        return $array;
    }

    /**
     * save the slaes data in database
     * @param type $purchase_data
     */
    public function importPurchase($purchase_data) {
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $time = new \DateTime('now');
        $counter = 1;
        foreach ($purchase_data as $data) {
            $purchase = new Purchase();
            $date_data = (isset($data->date) ? $data->date : null);
            $current_date = date(DATE_RFC3339, strtotime($date_data)); //change it according to application time zone
            $code = (isset($data->card_code) ? $data->card_code : '');
            $description = (isset($data->card_no) ? $data->card_no : '');
            $amount = (isset($data->credit) ? $data->credit : 0);
            $shop_id = (isset($data->shop_id->_id) ? $data->shop_id->_id : 0);
            $citizen_id = (isset($data->citizen_id->_id) ? $data->citizen_id->_id : 0);

            $format_date_object = new \DateTime($current_date);
            $format_date = $format_date_object->format('Y-m-d');
            $purchase_year = date('y', strtotime($format_date));
            $purchase_month = date('m', strtotime($format_date));
            $purchase_day = date('d', strtotime($format_date));
            $counter_value = str_pad($counter, 2, "0", STR_PAD_LEFT); //add 0 in the beginning from 1-9
            $numero_quietanza = $purchase_year . $purchase_month . $purchase_day . $counter_value;

            $purchase->setDate(new \DateTime($date_data));
            $purchase->setNumeroQuietanza($numero_quietanza);
            $purchase->setTipoQuietanza(ApplaneConstentInterface::SIX_TIPO_QUIETANZA);
            $purchase->setCausale(ApplaneConstentInterface::SIX_CAUSALE);
            $purchase->setCode($code);
            $purchase->setDescription($description);
            $purchase->setAmount($amount);
            $purchase->setShopId($shop_id);
            $purchase->setCitizenId($citizen_id);
            $purchase->setCreatedAt($time);
            $em->persist($purchase); //persist the data
            $counter++;
        }
        try {
            $em->flush(); //flush the data 
            $applane_service->writeTransactionLogs('Purchase Data to be import: ' . json_encode($purchase_data), 'purchase imported data successfully');  //write log
            return 1;
        } catch (\Exception $ex) {
            $applane_service->writeTransactionLogs('Purchase Data to be import: ' . json_encode($purchase_data), 'purchase imported data failure');  //write log
            return 0;
        }
    }

    /**
     * getting the purchase daily filename
     * @return string
     */
    public function getPurchasecardExportFileName() {
        $file_name = $this->purchase_export . ".csv";
        return $file_name;
    }

    /**
     * getting the purchase daily filename with date
     * @return string
     */
    public function getPurchasecardExportFileNameWithdate() {
        $file_name = date('Ymd') . $this->purchase_export . ".csv";
        return $file_name;
    }

    /**
     * get shop file sheet name
     * @return string
     */
    public function getPurchaseFileSheetName() {
        return $this->purchase_export_sheet_name;
    }

    /**
     * Exporting the shop purchase
     */
    public function purchaseexport() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        //get data to be exported
        $purchase_data = array();
        //get purchase records to be export
        $purchase_data = $em->getRepository('ExportManagementBundle:Purchase')
                            ->getPurchaseTransaction();

        //check if we have some records for export
        //   if (count($purchase_data)) {
        //exporting the data.
        $result = $this->exportPurchasecsv($purchase_data);
        //   }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file
     * @param type $purchase_data
     */
    public function exportPurchasecsv($purchase_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web/" . $this->purchase_export_transaction_path;

        //getting the file name.
        $file_name = $this->getPurchasecardExportFileName();
        $file_name_date = $this->getPurchasecardExportFileNameWithdate();
        //getting the sheet name
        $sheet_name = $this->getPurchaseFileSheetName();

        $purchase_profile_type = $this->purchase_export_type;

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }

        $data = array();
        $column_format = array('G');
        $column_left_align = array('H', 'I');
        $column_cast = array('H');
        //prepare the head data.
        $head_data_array = array("DATA", "NUMERO QUIETANZA", "TIPO QUIETANZA", "CAUSALE", "CODICE", "DESCRIZIONE", "IMPORTO", "ID-SHOP", "ID-CITIZEN");
        
        $i = 1;
        foreach ($purchase_data as $purchase_record) {
          
            // $date_purchased = $purchase_record->getDate()->format('d/m/Y');
            // $numero_quietanza = $purchase_record->getNumeroQuietanza();
            // $tipo = $purchase_record->getTipoQuietanza();
            // $causale = $purchase_record->getCausale();
            // $code = $purchase_record->getCode();
            // $description = $purchase_record->getDescription();
            // $amount = $purchase_record->getAmount();
            // $amount_deciaml = $this->castToFloat($amount);
            // $shop_id = $purchase_record->getShopId();
            // $citizen_id = $purchase_record->getCitizenId();

            $str_date = $purchase_record['timeCreatedH']->format('d/m/y');

            $new_str_date =  explode('/',$str_date);
          
            $scc = 'SCC'; 
          
            if($purchase_record['card'] == '1'){

              $causale  = 'ECO';
              $code = 'CARD';
              $desc = $purchase_record['ciTransactionSystemId'];
              $shop_id = 'APP-2345DERT';
           }
           else{

              $causale  = 'AC';
              $code = 'CA'.$purchase_record['initAmount'];
              $desc = $purchase_record['cardId'];
              $shop_id = $purchase_record['sellerId'];
           }


            if($i<10){
            
                $i = '0'.$i;
            }

            $date_purchased   = $purchase_record['timeCreatedH']->format('d/m/Y');
            $numero_quietanza = $new_str_date[2].$new_str_date[1].$new_str_date[0].$i;
            $tipo             = $scc;
            $causale          = $causale;
            $code             = $code;
            $description      = $desc;
            $amount_deciaml   = $this->castToFloat($purchase_record['initAmount']);
            $shop_id          = $purchase_record['sellerId'];
            $citizen_id       = $purchase_record['buyerid'];

            $data[] = array("DATA" => $date_purchased, "NUMERO QUIETANZA" => $numero_quietanza, "TIPO QUIETANZA" => $tipo,
                "CAUSALE" => $causale, "CODICE" => $code, "DESCRIZIONE" => $description, "IMPORTO" => $amount_deciaml, "ID-SHOP" => $shop_id, "ID-CITIZEN" => $citizen_id);
            $i++;

        }

        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $file_name_date, $sheet_name, $purchase_profile_type, $head_data_array, $data, $column_format, $column_left_align, $column_cast);
        return $result;
    }

    /**
     * Generate number with two decimal places.
     * @return string
     */
    private function castToFloat($number) {
        return number_format((float) $number, 2, '.', '');
    }

    /**
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount) {
        $final_amount = (float) $amount / $this->base_six;
        return $final_amount;
    }

    /**
     * get pending transactions ids.
     */
    private function getTransactionIds() {
        $handler = $this->container->get('monolog.logger.purchased_cards_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [getTransactionIds]', array());  //write log
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $pending_ids = array();
        $id =  $result_ids = array();
        $ids_data = $ids = $final_ids = '';
        $shop_transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->getShopManualSystemTransactions();
        $applane_service->writeAllLogs($handler, 'Data from ShopTransactionPayment table: ' . $this->convertToJson($shop_transactions), array());  //write log
        if (count($shop_transactions)) {
            foreach ($shop_transactions as $shop_transaction) {
                $pending_ids[] = $shop_transaction['pending_ids']; //$pending_ids[0] = 2,3,4   $pending_ids[1] = 99,100
            }
            $ids = implode(',', $pending_ids);
            $applane_service->writeAllLogs($handler, 'Pending ids of  ShopTransactions table: ' . $ids, array());
        }
        //finding the transaction ids
        //   if (count($ids)) {
        $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->getTransactionIds($ids);
        $applane_service->writeAllLogs($handler, 'Data from ShopTransaction table: ' . $this->convertToJson($transactions), array());
        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                $id[] = "{$transaction['transaction_id']}";
            }
            $final_ids = implode(',', $id); //converting the array indexes to string
        }
        $applane_service->writeAllLogs($handler, 'Ids for : ' . $final_ids, array());
        //    }
        $applane_service->writeAllLogs($handler, 'Exiting from  class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [getTransactionIds]', array());  //write log
        if (strlen(trim($final_ids)) > 0)
            $result_ids = explode(',', $final_ids); //converting to array
        return $result_ids;
    }

    /**
     * convert to json
     * @param array $data
     */
    public function convertToJson($data) {
        return json_encode($data);
    }

    /**
     * import the connect type purchase
     */
    public function importConnectTypePurchase() {
        $em = $this->em;
        $connect_app_service = $this->container->get('sixth_continent_connect.connect_app');
        $handler = $this->container->get('monolog.logger.purchased_cards_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [importConnectTypePurchase]', array());  //write log
        $counter = $this->findCounter();
        $counter = $counter + 1;
        $ci_transaction_data = $em->getRepository('SixthContinentConnectBundle:SixthcontinentconnectPaymentTransaction')
                                  ->getCiTransactions();
        //constants
        $causale = ApplaneConstentInterface::CONNECT_TRANSACTION_CAUSALE;
        $code    = ApplaneConstentInterface::CONNECT_PURCHASE_TRANSACTION_CODICE;
        $tipo_quantaza = ApplaneConstentInterface::SIX_TIPO_QUIETANZA;
        $time = new \DateTime('now');
        foreach ($ci_transaction_data as $transaction) {
            $id = $transaction->getId();
            $applane_service->writeAllLogs($handler, 'Connect CI transaction of table [paypaltransactionrecords] with id: '.$id, array());  //write log
            $purchase = new Purchase();
            $date = $transaction->getDate();
            $formated_date = $date->format('Y-m-d');
            
            $app_id = $transaction->getAppId();
            $citizen_id = $transaction->getUserId();
            $amount = $connect_app_service->changeRoundAmountCurrency($transaction->getCiUsed());
            $paypal_id = $transaction->getPaypalId();
            $paypal_id_object = Utility::decodeData($paypal_id);
            $paypal_reciver_id = isset($paypal_id_object[0]->receiver) ? $paypal_id_object[0]->receiver : '';
            
            $current_date = date(DATE_RFC3339, strtotime($formated_date)); //change it according to application time zone

            $format_date_object = new \DateTime($current_date);
            $format_date = $format_date_object->format('Y-m-d');
            $purchase_year = date('y', strtotime($format_date));
            $purchase_month = date('m', strtotime($format_date));
            $purchase_day = date('d', strtotime($format_date));
            $counter_value = str_pad($counter, 2, "0", STR_PAD_LEFT); //add 0 in the beginning from 1-9
            $numero_quietanza = $purchase_year . $purchase_month . $purchase_day . $counter_value;
            $description = $paypal_reciver_id;
            $purchase->setDate($date);
            $purchase->setNumeroQuietanza($numero_quietanza);
            $purchase->setTipoQuietanza($tipo_quantaza);
            $purchase->setCausale($causale);
            $purchase->setCode($code);
            $purchase->setDescription($description);
            $purchase->setAmount($amount);
            $purchase->setShopId($app_id);
            $purchase->setCitizenId($citizen_id);
            $purchase->setCreatedAt($time);
            $em->persist($purchase); //persist the data
            $counter++;
        }
        try {
           $em->flush();
           $applane_service->writeAllLogs($handler, 'Data saved successfully', array());  //write log
        } catch (\Exception $ex) {
            $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [importConnectTypePurchase] wiith error: '.$ex->getMessage(), array());  //write log
        }
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\PurchaseService] and function [importConnectTypePurchase]', array());  //write log
        return true;
    }

    /**
     * return the counter of the purchase
     * @return int $counter
     */
    public function findCounter() {
        $em = $this->em;
        $counter = $em->getRepository('ExportManagementBundle:Purchase')->getPurchaseCounter();
        return $counter;
    }

}
