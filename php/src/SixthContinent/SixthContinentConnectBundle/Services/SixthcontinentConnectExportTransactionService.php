<?php

namespace SixthContinent\SixthContinentConnectBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

// validate the data.like iban, vatnumber etc
class SixthcontinentConnectExportTransactionService {

    protected $em;
    protected $dm;
    protected $container;

    CONST CONNECT_TRANSACTION_PATH = "/uploads/transaction/connecttransaction";
    CONST CONNECT_TRANSACTION_FILE_NAME = 'PAGAMENTI';
    CONST CONNECT_TRANSACTION_SHEET_NAME = 'PURCHASE';
    CONST PAY_ONCE_CI = 'PAY_ONCE_CI';
    CONST CI_CAUSALE = 'ECO';

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
     * Create connect app log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.connect_app_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    /**
     * getting the connect transaction filename
     * @return string
     */
    public function getConnectTransactionFileName() {
        $file_name = self::CONNECT_TRANSACTION_FILE_NAME . ".csv";
        return $file_name;
    }

    /**
     * getting the connect transaction with date
     * @return string
     */
    public function getConnectTransactionWithDate() {
        $file_name_date = date('Ymd') . self::CONNECT_TRANSACTION_FILE_NAME . ".csv";
        return $file_name_date;
    }

    /**
     * get connect transaction file sheet name
     * @return string
     */
    public function getConnectTransactionFileSheetName() {
        return self::CONNECT_TRANSACTION_SHEET_NAME;
    }

    /**
     * export the ci transaction of connect.
     */
    public function exportCiTransaction() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $result = '';
        //getting the entity manager object.
        $em = $this->em;
        $ci_transaction_data = $em->getRepository('SixthContinentConnectBundle:SixthcontinentconnectPaymentTransaction')
                                  ->getCiTransactions();
        //exporting the data.
        $result = $this->exportCiTransactioncsv($ci_transaction_data);
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        } else {
            $data = array('code' => 100, 'message' => 'ERROR_IN_UPLOADING', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $transaction_data
     */
    public function exportCiTransactioncsv($transaction_data) {
        $connect_app_service = $this->_getSixcontinentAppService();
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . self::CONNECT_TRANSACTION_PATH;

        //creating the file name.
        $file_name = $this->getConnectTransactionFileName();
        
        //creating the file name with date
        $file_name_date = $this->getConnectTransactionWithDate();
        //sheet name
        $sheet_name = $this->getConnectTransactionFileSheetName();
        
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $data = array();
        $head_data_array = array('DATA', 'CAUSALE', 'ID-SHOP', 'IMPORTO', 'IMPORTO PAGAMENTO', 'DESCRIPTION');

        foreach ($transaction_data as $transaction) {
            $date = $transaction->getDate();
            $formated_date = $date->format('d/m/Y');
            $causale = self::CI_CAUSALE;
            $app_id  = $transaction->getAppId();
            $amount  = $connect_app_service->changeRoundAmountCurrency($transaction->getCiUsed());
            $paypal_id = $transaction->getPaypalId();
            $paypal_id_object  = Utility::decodeData($paypal_id);
            $paypal_reciver_id = isset($paypal_id_object[0]->receiver) ? $paypal_id_object[0]->receiver : '';
            $payment_amount = 0;
            $app_id_blank = '';
            $data[] = array('DATA' => $formated_date, 'CAUSALE'=>$causale, 'ID-SHOP' => $app_id, 'IMPORTO' => $amount,
                            'IMPORTO PAGAMENTO' => $payment_amount, 'DESCRIPTION' => $paypal_reciver_id);
            $data[] = array('DATA' => $formated_date, 'CAUSALE' => $causale, 'ID-SHOP' => $app_id_blank, 'IMPORTO' => $payment_amount,
                            'IMPORTO PAGAMENTO' => $amount, 'DESCRIPTION' => $paypal_reciver_id);
        }
        $column_left   =  array('B', 'C', 'F'); //left align
        $column_format = array('D', 'E'); //number with 2 decimal.
        $column_cast = array('C'); //cast to string
        //Exporting exporting the file.
        $result = $this->ExportFiles($file_path, $file_name, $file_name_date, $sheet_name, $head_data_array, $data, $column_format, $column_left, $column_cast);
        return $result;
    }
    
    /**
     * Exporting the data into file(ci payed back).
     * @param string $file_path
     * @param string $file_name
     * @param string $file_name_date
     * @param string $sheet_name
     * @param string $file_log_type
     * @param array $head_data
     * @param array $data
     */
    public function ExportFiles($file_path, $file_name, $file_name_date, $sheet_name, $head_data, $data, $column_format=array(), $column_left=array(), $column_cast=array()) {
        //making the local path for file
        $local_file_path = $file_path . "/" . $file_name_date;
        //check the file type to be exported.
        $file_type = $this->container->getParameter('exported_file_type');
        //check if file exist
        if (!file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'a');
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'w'); //get the file object
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
            foreach ($data as $array_data) {
                $current_row = $array_data;
                try {
                    fputcsv($fp, $current_row); //write the file
                } catch (\Exception $ex) {    
                }
            }
        }
        fclose($fp); //close the file
        if ($file_type == 'xls') { //for excel
            $new_file_name              = $this->convertCsvFileName($file_name_date) . '.XLSX';
            $new_file_name_without_date = $this->convertCsvFileName($file_name) . '.XLSX';
            $file_local_path = $file_path . '/' . $new_file_name;
            $this->createExcelFile($file_path, $local_file_path, $file_name, $file_name_date, $sheet_name, $column_format, $column_left, $column_cast);
        }
       return 1;
    }
    
     /**
     * Creating the excel file.
     * @param string $file_local_path
     */
    public function createExcelFile($file_path, $file_local_path, $file_name, $file_name_date, $sheet_name, $cloumns_format=array(), $column_left=array(), $column_cast=array()) {

        //code for xls
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        $objReader->setDelimiter(",");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        $objPHPExcel = $objReader->load($file_local_path);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $xls_file_name = $this->convertCsvFileName($file_name_date) . '.XLSX';
        $file = $file_path . '/' . $xls_file_name; //get file path
        $objWriter->save($file); //convert csv to xls.

        
        $excel = \PHPExcel_IOFactory::load($file); //load excel class
        $worksheet = $excel->getActiveSheet()->setTitle($sheet_name); //set sheet title.
        
        if (count($cloumns_format) > 0) {
                $highestRow = $worksheet->getHighestRow();
                foreach ($cloumns_format as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getNumberFormat()->setFormatCode('0.00');
                }
        }
        //get column for left align.
        if (count($column_left) > 0) {
                //convert column for format
                $highestRow = $worksheet->getHighestRow();
                foreach ($column_left as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    //get the cloumn data left align
                   $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                }
        }
        //get column for casting to string
        if (count($column_cast) > 0) {
                $i = 1;
                //convert column for cast
                $highestRow = $worksheet->getHighestRow();
                $column_cast1 = $column_cast[0];
                while($i <= $highestRow) {
                    $cloumn1 = $column_cast1.$i;
                    $column_value = $worksheet->getCell($cloumn1)->getvalue();
                    $worksheet->setCellValueExplicit($cloumn1, $column_value, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $i++;
                }
        }
        //save file
        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save($file);
        chmod($file, 0777);
        return true;
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
