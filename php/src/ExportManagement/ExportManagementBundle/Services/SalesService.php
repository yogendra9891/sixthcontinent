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
use ExportManagement\ExportManagementBundle\Entity\Sales;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use ExportManagement\ExportManagementBundle\Model\ExportConstantInterface;
use Utility\UtilityBundle\Utils\Utility;

// sales service for import and export through command
class SalesService implements ExportConstantInterface {

    protected $em;
    protected $dm;
    protected $container;
    protected $base_six = 1000000;
    protected $sales_export_transaction_path = "uploads/transaction/sale";
    protected $sales_export = 'SALE';
    protected $sales_export_type = 'SALE';
    protected $sales_export_sheet_name = 'SALE';

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
     * Import the shop sales
     */
    public function salesimport() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        $sales_data = array();

        //get data to be imported
        $res_data = array();
        $time = new \DateTime('now');
        $data['start_date'] = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) - (60 * 60 * 24)));
        $data['end_date'] = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));

        //get the applane service for transaction data
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $import_sales_data = $applane_service->getsalestransactiondata($data); //get data from applane of previous day.

        if ($import_sales_data->code == 200) {
            $sales_data = $import_sales_data->response->result;
        }
        //import the data.
        $result = $this->importSales($sales_data);

        if ($result) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * save the slaes data in database
     * @param type $sales_data
     */
    public function importSales($sales_data) {
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $time = new \DateTime('now');
        $counter = 1;
        foreach ($sales_data as $data) {
            $sale = new Sales();
            $date_data = (isset($data->date) ? $data->date : null);
            $current_date = date(DATE_RFC3339, strtotime($date_data)); //change it according to application time zone
            $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
            $causale = (isset($data->transaction_type_id->code) ? $data->transaction_type_id->code : '');
            $code = (isset($data->transaction_type_id->sub_code) ? $data->transaction_type_id->sub_code : '');
            $shop_id = (isset($data->shop_id->_id) ? $data->shop_id->_id : 0);
            $description1 = (isset($data->transaction_type_id->description1) ? $data->transaction_type_id->description1 : '');
            $description2 = (isset($data->transaction_type_id->description2) ? $data->transaction_type_id->description2 : '');
            $amount = (isset($data->checkout_value) ? $data->checkout_value : 0);
            $vat = (isset($data->vat) ? $data->vat : 0);
            //$amount_vat = $amount + $vat;
            $sale->setDate(new \DateTime($current_date));
            $sale->setProgress($progress);
            $sale->setCausale($causale);
            $sale->setCode($code);
            $sale->setShopId($shop_id);
            $sale->setDescription($description1);
            $sale->setDescription2($description2);
            $sale->setAmount($amount);
            $sale->setAmountvat($vat);
            $sale->setCreatedAt($time);
            $em->persist($sale); //persist the data
            $counter++;
        }
        try {
            $em->flush(); //flush the data
            $applane_service->writeTransactionLogs('Sales Data to be import: ' . json_encode($sales_data), 'sales imported data successfully');  //write log
            return 1;
        } catch (\Exception $ex) {
            $applane_service->writeTransactionLogs('Sales Data to be import: ' . json_encode($sales_data), 'sales imported data failed');  //write log
            return 0;
        }
    }

    /**
     * getting the sales daily filename
     * @return string
     */
    public function getSalescardExportFileName() {
        $file_name = $this->sales_export . ".csv";
        return $file_name;
    }

    /**
     * getting the sales daily filename with date
     * @return string
     */
    public function getSalesExportFileNameWithdate() {
        $file_name = date('Ymd') . $this->sales_export . ".csv";
        return $file_name;
    }

    /**
     * get shop file sheet name
     * @return string
     */
    public function getSalesFileSheetName() {
        return $this->sales_export_sheet_name;
    }

    /**
     * Exporting the shop sales
     */
    public function salesexport() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        //get data to be exported
        $sales_data = array();
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        //get sales records to be export
        $sales_data = $em->getRepository('ExportManagementBundle:Sales')
                ->getSalesTransaction();

        //export without caring data is available 
        //     if (count($sales_data)) {
        //exporting the data.
        $result = $this->exportSalescsv($sales_data);
        //     }
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
     * @param type $sales_data
     */
    public function exportSalescsv($sales_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web/" . $this->sales_export_transaction_path;

        //getting the file name.
        $file_name = $this->getSalescardExportFileName();
        $file_name_date = $this->getSalesExportFileNameWithdate();
        //getting the sheet name
        $sheet_name = $this->getSalesFileSheetName();

        $sales_profile_type = $this->sales_export_type;

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $yester_day = new \DateTime('yesterday');
        $yesterday_date = $yester_day->format('d/m/Y');
        $data = array();
        $column_format = array('H', 'I');
        $column_left_align = array('D');
        $column_cast = array('D');
        //prepare the head data.
        $head_data_array = array("PROGR", "DATA", "CAUSALE", "ID-SHOP", "CODICE", "DESCRIZIONE", "DESCRIZIONE 2", "IMPORTO", "IMPORTO_PIU_IVA");
        
        $i = 1;
        foreach ($sales_data as $sales_record) {
           
            $progress         = 'T'.$i;
            $date_sales       = $sales_record['timeInitH']->format('d/m/Y');

            if($sales_record['trn_type']=='1' || $sales_record['trn_type']=='4'){

             $causale = 'RCECO';
             $shop_id = 'APP-2345DERT';
             $code  = 'PC'; 
             $description2 = 'PER VENDITE EFFETTUATE TRANSAZIONI DI RIFERIMENTO.
             sixthcontinent_trs_id:'.$sales_record['id'].';
             paypal_trs_id:'.$sales_record['ciTransactionSystemId'].'';
             }
             else{
            
               $causale = 'RC6PC';
               $shop_id = $sales_record['sellerId'];
               $code   = '6PC'; 
               $description2 = 'PER VENDITE EFFETTUATE:CODICE PRELIEVO:
(6THCH'.$sales_record['sixcTransactionId'].').
TRANSAZIONI DI RIFERIMENTO:';
            } 
          
            $description      = 'CORRISPETTIVO PUBBLICITA';
            $amount_deciaml   = $sales_record['importo'];
            $amount_vat_decimal   = $sales_record['importo_piu_iva'];

            // $progress = $sales_record->getProgress();
            // $date_sales = $sales_record->getDate()->format('d/m/Y');
            // //$created_at = $sales_record->getCreatedAt()->format('d/m/Y');
            // $causale = $sales_record->getCausale();
            // $shop_id = $sales_record->getShopId();
            // $code = $sales_record->getCode();
            // $description = $sales_record->getDescription();
            // $description2 = $sales_record->getDescription2();
            // $amount = $sales_record->getAmount();
            // $amount_deciaml = $this->castToFloat($amount);
            // $amount_vat = $sales_record->getAmountvat();
            // $amount_vat_decimal = $this->castToFloat($amount_vat);
            //$final_vat_amount = $amount_deciaml + $amount_vat_decimal;
            $data[] = array("PROGR" => $progress, "DATA" => $date_sales, "CAUSALE" => $causale, "ID-SHOP" => $shop_id, "CODICE" => $code,
                "DESCRIZIONE" => $description, "DESCRIZIONE 2" => $description2, "IMPORTO" => $amount_deciaml, "IMPORTO_PIU_IVA" => $amount_vat_decimal);
         $i++;

        }

        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        //$result = $convert_files->ExportTransactionFiles($file_path, $file_name, $file_name_date, $sheet_name, $sales_profile_type, $head_data_array, $data, $column_format, $column_left_align);
        $result = $convert_files->ExportTransactionSalesFiles($file_path, $file_name, $file_name_date, $sheet_name, $sales_profile_type, $head_data_array, $data, $column_format, $column_left_align, $column_cast);
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
     * import sales from our database
     */
    public function salesimporttransactions() {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [salesimporttransactions]', array());  //write log
        $shop_transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->getShopTransactions();
        $counter = 1;
        $time = new \DateTime('now');
        if (count($shop_transactions)) {
            foreach ($shop_transactions as $shop_transaction) {
                $serializer = $this->container->get('serializer');
                $json = $serializer->serialize($shop_transaction, 'json');
                $applane_service->writeAllLogs($handler, 'Sales Data from ShopTransactionsPayment table: ' . $json, '');  //write log
                $shop_id = $shop_transaction->getShopId();
                $pending_ids = $shop_transaction->getPendingIds();
                $contract_id = $shop_transaction->getContractId();
                $payment_date = $shop_transaction->getPaymentDate();
                $transaction_code = $shop_transaction->getCodTrans();
                $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getShopTransactionData($shop_id, $pending_ids);
                foreach ($transactions as $transaction) {
                    $type = $transaction['type'];
                    $current_shop_id = $transaction['shop_id'];
                    $transaction_ids = $transaction['transaction_ids'];
                    $exploded_pending_ids = explode(',', $transaction_ids);
                    $desc2 = '';
                    $paypal_ids = array();
                    if ($type == self::T) //6%
                        if ($exploded_pending_ids[0] != '') {
                            $desc2 = implode(';\n', $exploded_pending_ids);
                        }
                    if ($type == self::C) { //upto100%
                        foreach ($exploded_pending_ids as $id) {
                            $paypal_ids[] = $this->__getPaypalId($shop_id, $id);
                        }
                        $desc2 = implode(';\n', $paypal_ids);
                    }
                    if ($desc2 != '') //append . in last of string
                        $desc2 = $desc2 . '.';
                    $sale = new Sales();
                    $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                    $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                    $code = $this->prepareCodeDescription(self::CODICE, $type);
                    $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                    $description2 = sprintf($this->prepareCodeDescription(self::DESCRIZIONE2, $type) . $desc2, $transaction_code);
                    $amount = $transaction['payable_amount'];
                    $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                    $sale->setDate($payment_date);
                    $sale->setProgress($progress);
                    $sale->setCausale($causale);
                    $sale->setCode($code);
                    $sale->setShopId($current_shop_id);
                    $sale->setDescription($description1);
                    $sale->setDescription2($description2);
                    $sale->setAmount($amount);
                    $sale->setAmountvat($amount_with_vat);
                    $sale->setCreatedAt($time);
                    $em->persist($sale); //persist the data
                    try {
                        $em->flush();
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data successfully');  //write log
                    } catch (\Exception $ex) {
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data failed');  //write log
                    }
                    $counter++;
                }
            }
        }
        $this->importSubscriptionShoppingCard($counter); //import susbscription and shopping card UPTO100%
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [salesimporttransactions]', array());  //write log
        return true;
    }

    /**
     * prepare the sales constant array
     * @param string $column
     * @param string $type
     */
    public function prepareCodeDescription($column, $type) {
        $const_array = array();
        $const_array[self::CAUSALE][self::R] = self::REGISTRATION_CAUSALE;
        $const_array[self::CAUSALE][self::T] = self::SIX_PERCENT_CAUSALE;
        $const_array[self::CAUSALE][self::C] = self::TEN_PERCENT_CAUSALE;
        $const_array[self::CAUSALE][self::S] = self::SUBSCRIPTION_PERCENT_CAUSALE;

        $const_array[self::CODICE][self::R] = self::REGISTRATION_CODICE;
        $const_array[self::CODICE][self::T] = self::SIX_PERCENT_CODICE;
        $const_array[self::CODICE][self::C] = self::TEN_PERCENT_CODICE;
        $const_array[self::CODICE][self::S] = self::SUBSCRIPTION_PERCENT_CODICE;

        $const_array[self::DESCRIZIONE][self::R] = self::REGISTRATION_DESCRIPTION1;
        $const_array[self::DESCRIZIONE][self::T] = self::SIX_PERCENT_DESCRIPTION1;
        $const_array[self::DESCRIZIONE][self::C] = self::TEN_PERCENT_DESCRIPTION1;
        $const_array[self::DESCRIZIONE][self::S] = self::SUBSCRIPTION_PERCENT_DESCRIPTION1;

        $const_array[self::DESCRIZIONE2][self::R] = self::REGISTRATION_DESCRIPTION2;
        $const_array[self::DESCRIZIONE2][self::T] = self::SIX_PERCENT_DESCRIPTION2;
        $const_array[self::DESCRIZIONE2][self::C] = self::TEN_PERCENT_DESCRIPTION2;
        $const_array[self::DESCRIZIONE2][self::S] = self::SUBSCRIPTION_PERCENT_DESCRIPTION2;
        return (isset($const_array[$column][$type]) ? $const_array[$column][$type] : '');
    }

    /**
     * get paypal id from paymenttransaction table.
     * @param int $shop_id
     * @param string $id
     * @return string
     */
    private function __getPaypalId($shop_id, $id) {
        $em = $this->em;
        $result = '';
        $payment_transaction_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("shopId" => $shop_id, "transationId" => $id));
        if ($payment_transaction_record) {
            $result = $payment_transaction_record->getTransactionReference();
        }
        return $result;
    }

    /**
     * import the subscription and shooping cards
     */
    public function importSubscriptionShoppingCard($counter) {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importSubscriptionShoppingCard]', array());  //write log
        $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->getShopSubscriptionShoppingCardTransactionData();
        $time = new \DateTime('now');
        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                $shop_id = $transaction['shop_id'];
                $payment_date = new \DateTime(date('Y-m-d', strtotime($transaction['created_at'])));
                $type = $transaction['type'];
                $transaction_ids = $transaction['transaction_ids'];
                $exploded_pending_ids = explode(',', $transaction_ids);
                $desc2 = '';
                $paypal_ids = array();
                if ($type == self::C) { //upto100%
                    foreach ($exploded_pending_ids as $id) {
                        $paypal_ids[] = $this->__getPaypalId($shop_id, $id);
                    }
                    $desc2 = implode(';\n', $paypal_ids);
                }
                if ($desc2 != '') //append . in last of string
                    $desc2 = $desc2 . '.';
                $sale = new Sales();
                $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                $code = $this->prepareCodeDescription(self::CODICE, $type);
                $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                $description2 = $this->prepareCodeDescription(self::DESCRIZIONE2, $type) . $desc2;
                $amount = $transaction['payable_amount'];
                $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                $sale->setDate($payment_date);
                $sale->setProgress($progress);
                $sale->setCausale($causale);
                $sale->setCode($code);
                $sale->setShopId($shop_id);
                $sale->setDescription($description1);
                $sale->setDescription2($description2);
                $sale->setAmount($amount);
                $sale->setAmountvat($amount_with_vat);
                $sale->setCreatedAt($time);
                $em->persist($sale); //persist the data
                try {
                    $em->flush();
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data successfully');  //write log
                } catch (\Exception $ex) {
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data failed');  //write log
                }
                $counter++;
            }
        }
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importSubscriptionShoppingCard]', array());  //write log
        return true;
    }

    /**
     * import data into table [Sales]
     */
    public function salesDataimport() {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [salesDataimport]', array());  //write log
        $shop_transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->getShopManualSystemTransactions();
        $counter = 1;
        $time = new \DateTime('now');
        if (count($shop_transactions)) {
            foreach ($shop_transactions as $shop_transaction) {
                $json = $this->convertToJson($shop_transaction);
                $applane_service->writeAllLogs($handler, 'Sales Data from ShopTransactionsPayment table: ' . $json, '');  //write log
                $shop_id = $shop_transaction['shop_id'];
                $pending_ids = $shop_transaction['pending_ids'];
                $contract_id = $shop_transaction['contract_id'];
                $payment_date = new \DateTime(date('Y-m-d', strtotime($shop_transaction['payment_date'])));
                $transaction_code = $shop_transaction['codTrans'];
                $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getShopSystemManualTransactionData($shop_id, $pending_ids);
                foreach ($transactions as $transaction) {
                    $type = $transaction['type'];
                    $current_shop_id = $transaction['shop_id'];
                    $transaction_ids = $transaction['transaction_ids'];
                    $exploded_pending_ids = explode(',', $transaction_ids);
                    $desc2 = '';
                    $paypal_ids = array();
                    if ($type == self::T) //6%
                        if ($exploded_pending_ids[0] != '') {
                            $desc2 = implode(';\n', $exploded_pending_ids);
                        }
                    if ($desc2 != '') //append . in last of string
                        $desc2 = $desc2 . '.';
                    $sale = new Sales();
                    $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                    $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                    $code = $this->prepareCodeDescription(self::CODICE, $type);
                    $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                    $description2 = sprintf($this->prepareCodeDescription(self::DESCRIZIONE2, $type) . $desc2, $transaction_code);
                    $amount = $transaction['payable_amount'];
                    $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                    $sale->setDate($payment_date);
                    $sale->setProgress($progress);
                    $sale->setCausale($causale);
                    $sale->setCode($code);
                    $sale->setShopId($current_shop_id);
                    $sale->setDescription($description1);
                    $sale->setDescription2($description2);
                    $sale->setAmount($amount);
                    $sale->setAmountvat($amount_with_vat);
                    $sale->setCreatedAt($time);
                    $em->persist($sale); //persist the data
                    try {
                        $em->flush();
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data successfully');  //write log
                    } catch (\Exception $ex) {
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data failed');  //write log
                    }
                    $counter++;
                }
            }
        }
        //finding the UPTO 100% cards 
        $new_counter = $this->importShoppingCards($counter);
        //importing subscription type records
        $this->importSubscription($new_counter);
        //find the last counter for last day date.
        $connect_counter = $this->findCounter();
        $this->importConnectTransaction($connect_counter); //import connect type transaction
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [salesDataimport]', array());  //write log
        return true;
    }

    /**
     * import Shopping cards.
     * @return boolean
     */
    public function importShoppingCards($counter) {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importShoppingCards]', array());  //write log
        $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->getShopShoppingCardTransactionData();
        $time = new \DateTime('now');
        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                $shop_id = $transaction['shop_id'];
                $payment_date = new \DateTime(date('Y-m-d', strtotime($transaction['created_at'])));
                $type = $transaction['type'];
                $transaction_ids = $transaction['transaction_ids'];
                $exploded_pending_ids = explode(',', $transaction_ids);
                $desc2 = '';
                $paypal_ids = array();
                if ($type == self::C) { //upto100%
                    foreach ($exploded_pending_ids as $id) {
                        $paypal_ids[] = $this->__getPaypalId($shop_id, $id);
                    }
                    $desc2 = implode(';\n', $paypal_ids);
                }
                if ($desc2 != '') //append . in last of string
                    $desc2 = $desc2 . '.';
                $sale = new Sales();
                $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                $code = $this->prepareCodeDescription(self::CODICE, $type);
                $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                $description2 = $this->prepareCodeDescription(self::DESCRIZIONE2, $type) . $desc2;
                $amount = $transaction['payable_amount'];
                $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                $sale->setDate($payment_date);
                $sale->setProgress($progress);
                $sale->setCausale($causale);
                $sale->setCode($code);
                $sale->setShopId($shop_id);
                $sale->setDescription($description1);
                $sale->setDescription2($description2);
                $sale->setAmount($amount);
                $sale->setAmountvat($amount_with_vat);
                $sale->setCreatedAt($time);
                $em->persist($sale); //persist the data
                try {
                    $em->flush();
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . $this->convertToJson($transaction), 'sales imported data successfully');  //write log
                } catch (\Exception $ex) {
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . $this->convertToJson($transaction), 'sales imported data failed');  //write log
                }
                $counter++;
            }
        }
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importShoppingCards]', array());  //write log
        return $counter;
    }

    /**
     * importing the subscription data.
     * @param int $new_counter
     */
    public function importSubscription($new_counter) {
        $counter = $new_counter;
        $subscription_ids = array();
        $subscription_imported_ids = array();
        $ids = '';
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [salesDataimport]', array());  //write log
        $shop_transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->getShopSubscirptionManualSystemTransactions(); //echo "<pre>"; print_r($shop_transactions); exit;
        $time = new \DateTime('now');
        if (count($shop_transactions)) {
            foreach ($shop_transactions as $shop_transaction) {
                $json = $this->convertToJson($shop_transaction);
                $applane_service->writeAllLogs($handler, 'Sales Data from ShopTransactionsPayment table: ' . $json, '');  //write log
                $shop_id = $shop_transaction['shop_id'];
                $pending_ids = $shop_transaction['pending_ids'];
                $contract_id = $shop_transaction['contract_id'];
                $payment_date = new \DateTime(date('Y-m-d', strtotime($shop_transaction['payment_date'])));
                $transaction_code = $shop_transaction['codTrans'];
                $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getShopSystemManualSubscriptionTransactionData($shop_id, $pending_ids);
                foreach ($transactions as $transaction) {
                    $ids = explode(',', $transaction['ids']);
                    $subscription_ids[] = $ids;
                    $type = $transaction['type'];
                    $current_shop_id = $transaction['shop_id'];
                    $transaction_ids = $transaction['transaction_ids'];
                    $exploded_pending_ids = explode(',', $transaction_ids);
                    $desc2 = '';
                    $sale = new Sales();
                    $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                    $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                    $code = $this->prepareCodeDescription(self::CODICE, $type);
                    $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                    $description2 = sprintf($this->prepareCodeDescription(self::DESCRIZIONE2, $type) . $desc2, $transaction_code);
                    $amount = $transaction['payable_amount'];
                    $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                    $sale->setDate($payment_date);
                    $sale->setProgress($progress);
                    $sale->setCausale($causale);
                    $sale->setCode($code);
                    $sale->setShopId($current_shop_id);
                    $sale->setDescription($description1);
                    $sale->setDescription2($description2);
                    $sale->setAmount($amount);
                    $sale->setAmountvat($amount_with_vat);
                    $sale->setCreatedAt($time);
                    $em->persist($sale); //persist the data
                    try {
                        $em->flush();
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data successfully');  //write log
                    } catch (\Exception $ex) {
                        $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . json_encode($transaction), 'sales imported data failed');  //write log
                    }
                    $counter++;
                }
            }
            //extracting the subscription ids
            foreach ($subscription_ids as $subscription_id) {
                foreach ($subscription_id as $id)
                    $subscription_imported_ids[] = $id;
            }
            $ids = implode(',', $subscription_imported_ids); //get the subscription ids.
        }
        $counter = $this->importRemainSubscription($ids, $counter);
        return true;
    }

    /**
     * import remain all subscription of previous day.
     * @param string $ids
     * @param int $counter
     */
    public function importRemainSubscription($ids, $counter) {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importRemainSubscription]', array());  //write log
        $transactions = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->getRemainSubscriptionCardTransactionData($ids);
        $time = new \DateTime('now');
        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                $shop_id = $transaction['shop_id'];
                $payment_date = new \DateTime(date('Y-m-d', strtotime($transaction['created_at'])));
                $type = $transaction['type'];
                $sale = new Sales();
                $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST . $counter;
                $causale = $this->prepareCodeDescription(self::CAUSALE, $type);
                $code = $this->prepareCodeDescription(self::CODICE, $type);
                $description1 = $this->prepareCodeDescription(self::DESCRIZIONE, $type);
                $description2 = $this->prepareCodeDescription(self::DESCRIZIONE2, $type);
                $amount = $transaction['payable_amount'];
                $amount_with_vat = $transaction['total_payable_amount']; //amount with vat means(amount+vat)
                $sale->setDate($payment_date);
                $sale->setProgress($progress);
                $sale->setCausale($causale);
                $sale->setCode($code);
                $sale->setShopId($shop_id);
                $sale->setDescription($description1);
                $sale->setDescription2($description2);
                $sale->setAmount($amount);
                $sale->setAmountvat($amount_with_vat);
                $sale->setCreatedAt($time);
                $em->persist($sale); //persist the data
                try {
                    $em->flush();
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . $this->convertToJson($transaction), 'sales imported data successfully');  //write log
                } catch (\Exception $ex) {
                    $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . $this->convertToJson($transaction), 'sales imported data failed');  //write log
                }
                $counter++;
            }
        }
        $applane_service->writeAllLogs($handler, 'Exiting from class [ExportManagement\ExportManagementBundle\Services\SalesService] and function [importRemainSubscription]', array());  //write log
        return $counter;
    }

    /**
     * convert to json
     * @param array $data
     */
    public function convertToJson($data) {
        return json_encode($data);
    }

    /**
     * find the latest counter 
     */
    public function findCounter() {
        $em = $this->em;
        $counter = $em->getRepository('ExportManagementBundle:Sales')
                      ->getTransactionCounter();
        return $counter;
    }

    /**
     * import the connect transactions
     * @param integer $connect_counter
     */
    public function importConnectTransaction($connect_counter) {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $transaction_records = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                                  ->getconnectTransactions(); //find the completed transaction of day before
        $causale = ApplaneConstentInterface::CONNECT_INCASSI_TRANSACTION_CAUSALE;
        $code = ApplaneConstentInterface::CONNECT_TRANSACTION_CODICE;
        $description1 = ApplaneConstentInterface::CONNECT_TRANSACTION_DESCRIPTION1;
        $description2 = ApplaneConstentInterface::CONNECT_TRANSACTION_DESCRIPTION2;
        $sixthcontinent_id_constant = ApplaneConstentInterface::CONNECT_SIXTHCONTINENT_TRANSACTION_ID_CONSTANT;
        $sixthcontinent_paypal_id_constant = ApplaneConstentInterface::CONNECT_SIXTHCONTINENT_PAYPAL_TRANSACTION_ID_CONSTANT;
        $semi_colon = ApplaneConstentInterface::SEMI_COLON;
        $sixthcontinent_app_service = $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
        $time = new \DateTime('now');
        if (count($transaction_records)) {
            foreach ($transaction_records as $transaction) {
                $id = $transaction->getId();
                $app_id = $transaction->getApplicationId();
                $checkout_value_with_vat = $transaction->getCheckoutValue();
                $date = $transaction->getDate();
                $vat = $transaction->getVat();
                $checkout_value = $checkout_value_with_vat - $vat;
                $converted_checkout_value = $sixthcontinent_app_service->changeRoundAmountCurrency($checkout_value);
                $converted_checkout_value_with_vat = $sixthcontinent_app_service->changeRoundAmountCurrency($checkout_value_with_vat);
                $paypal_transaction_reference = $transaction->getPaypalTransactionReference();
                $paypal_transaction_ids = $transaction->getPaypalTransactionId();
                $decode_ids = Utility::decodeData($paypal_transaction_ids);
                $reciver_id = isset($decode_ids[1]->receiver) ? $decode_ids[1]->receiver : '';
                $desc2[0]   =  $description2 . $sixthcontinent_id_constant . $id;
                $desc2[1]   = $sixthcontinent_paypal_id_constant . $reciver_id;
                $final_description22 = implode(";\n", $desc2);
              //  $desc2 = $description2 . $sixthcontinent_id_constant . $id . $semi_colon . '\n' . $sixthcontinent_paypal_id_constant . $reciver_id;
                $connect_counter = $connect_counter + 1;
                $progress = ApplaneConstentInterface::SIX_PROGRESS_CONST.$connect_counter;
                $this->saveSaledata($progress, $date, $causale, $app_id, $code, $description1, $final_description22, $converted_checkout_value, $converted_checkout_value_with_vat, $time);
                $applane_service->writeAllLogs($handler, 'Transaction of connect type data imported with id: '.$id.' of [transaction] table.', '');  //write log
            }
        }
    }

    /**
     * save the sales into sales table.
     * @param string $progress
     * @param type $date
     * @param string $causale
     * @param string $app_id
     * @param string $code
     * @param string $desc1
     * @param string $desc2
     * @param float $amount
     * @param type $amount_with_vat
     * @param type $time
     * @return boolean
     */
    public function saveSaledata($progress, $date, $causale, $app_id, $code, $desc1, $desc2, $amount, $amount_with_vat, $time) {
        $em = $this->em;
        $handler = $this->container->get('monolog.logger.sales_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $sale = new Sales();
        $sale->setDate($date);
        $sale->setProgress($progress);
        $sale->setCausale($causale);
        $sale->setCode($code);
        $sale->setShopId($app_id);
        $sale->setDescription($desc1);
        $sale->setDescription2($desc2);
        $sale->setAmount($amount);
        $sale->setAmountvat($amount_with_vat);
        $sale->setCreatedAt($time);
        $em->persist($sale); //persist the data
        try {
            $em->flush();
            $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . $app_id. ' progress: '.$progress. ' desc1: '.$desc1. ' desc2: '.$desc2. ' amount: '.$amount. ' amount_vat: '.$amount_with_vat. ' causale:'.$causale, 'sales imported data successfully');  //write log
        } catch (\Exception $ex) {
            $applane_service->writeAllLogs($handler, 'Sales Data to be import: ' . 'with app id: '.$app_id. ' desc1: '.$desc1. ' desc2: '.$desc2. ' amount:'.$amount, 'sales imported data failed');  //write log
        }
        return true;
    }

}
