<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class PurchaseExportController extends Controller {

    protected $purchase_export_transaction_path = "uploads/transaction/purchase";
    protected $purchase_export = 'PURCHASE';
    protected $purchase_export_type = 'PURCHASE';
    protected $purchase_export_sheet_name = 'PURCHASE';
    protected $base_six = 1000000;

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the purchase daily filename
     * @return string
     */
    public function getPurchasecardExportFileName() {
        $file_name = $this->purchase_export.".csv";
        return $file_name;
    }
    
    /**
     * getting the purchase daily filename with date
     * @return string
     */
    public function getPurchasecardExportFileNameWithdate() {
        $file_name = date('Ymd').$this->purchase_export.".csv";
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function purchaseexportAction(Request $request) {
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
            $data = array('code' => 100, 'message' => 'FAILED', 'data' => array());
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
        $file_name      = $this->getPurchasecardExportFileName();
        $file_name_date = $this->getPurchasecardExportFileNameWithdate();
        //getting the sheet name
        $sheet_name     = $this->getPurchaseFileSheetName();
        
        $purchase_profile_type = $this->purchase_export_type;
        
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        
        $data = array();
        $column_format = array('G');
        $column_left_align = array('H', 'I');
        //prepare the head data.
        $head_data_array = array("DATA", "NUMERO QUIETANZA", "TIPO QUIETANZA", "CAUSALE","CODICE", "DESCRIZIONE","IMPORTO", "ID-SHOP", "ID-CITIZEN");
        $i = 1;
        foreach ($purchase_data as $purchase_record) {
           
            // $date_purchased   = $purchase_record->getDate()->format('d/m/Y');
            // $numero_quietanza = $purchase_record->getNumeroQuietanza();
            // $tipo             = $purchase_record->getTipoQuietanza();
            // $causale          = $purchase_record->getCausale();
            // $code             = $purchase_record->getCode();
            // $description      = $purchase_record->getDescription();
            // $amount           = $purchase_record->getAmount();
            // $amount_deciaml   = $this->castToFloat($amount);
            // $shop_id          = $purchase_record->getShopId();
            // $citizen_id       = $purchase_record->getCitizenId();
            
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
            $shop_id          = 
            $citizen_id       = $purchase_record['buyerid'];


            $data[]   = array("DATA"=>$date_purchased, "NUMERO QUIETANZA"=>$numero_quietanza, "TIPO QUIETANZA"=>$tipo,
                               "CAUSALE"=>$causale,"CODICE"=>$code, "DESCRIZIONE"=> $description,"IMPORTO"=>$amount_deciaml, "ID-SHOP"=>$shop_id, "ID-CITIZEN"=>$citizen_id);
         $i++;

        }

        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $file_name_date, $sheet_name, $purchase_profile_type, $head_data_array, $data, $column_format, $column_left_align);
        return $result;        
    }
    
    /**
     * Generate number with two decimal places.
     * @return string
     */
    private function castToFloat($number){
        return number_format((float)$number, 2, '.', '');
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
}
