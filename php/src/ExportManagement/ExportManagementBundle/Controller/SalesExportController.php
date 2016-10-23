<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;


class SalesExportController extends Controller {

    protected $sales_export_transaction_path = "uploads/transaction/sale";
    protected $sales_export = 'SALE';
    protected $sales_export_type = 'SALE';
    protected $sales_export_sheet_name = 'SALE';
    protected $base_six = 1000000;

    
    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the sales daily filename
     * @return string
     */
    public function getSalescardExportFileName() {
        $file_name = $this->sales_export.".csv";
        return $file_name;
    }
    
    /**
     * getting the sales daily filename with date
     * @return string
     */
    public function getSalesExportFileNameWithdate() {
        $file_name = date('Ymd').$this->sales_export.".csv";
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function salesexportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        
        //get data to be exported
        $sales_data = array();
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();

        
         $freq_obj = $request->get('date');
         $fde_serialize = $this->decodeData($freq_obj);
        
         if(isset($fde_serialize)) {
             $de_serialize = $fde_serialize;
           }
        
        else{
              $de_serialize = $this->getAppData($request);
            }
       
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
            $data = array('code' => 100, 'message' => 'FAILED', 'data' => array());
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
        $file_name      = $this->getSalescardExportFileName();
        $file_name_date = $this->getSalesExportFileNameWithdate();
        //getting the sheet name
        $sheet_name     = $this->getSalesFileSheetName();
        
        $sales_profile_type = $this->sales_export_type;
        
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        
        $data = array();
        $column_format = array('H', 'I');
        $column_left_align = array('D');
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
             $description2 = 'PER VENDITE EFFETTUATE
                            TRANSAZIONI DI RIFERIMENTO.
                            sixthcontinent_trs_id:'.$sales_record['id'].';
                            paypal_trs_id:'.$sales_record['ciTransactionSystemId'].'';
             }
             else{
            
               $causale = 'RC6PC';
               $shop_id = $sales_record['sellerId'];
               $code   = '6PC'; 
               $description2 = 'PER VENDITE EFFETTUATE:
                         CODICE PRELIEVO:(6THCH'.$sales_record['sixcTransactionId'].')
                         TRANSAZIONI DI RIFERIMENTO: .';
            } 
          
            $description      = 'CORRISPETTIVO PUBBLICITA';
            $amount_deciaml   = $sales_record['importo'];
            $final_vat_amount   = $sales_record['importo_piu_iva'];
   
            // $progress         = $sales_record->getProgress();
            // $date_sales       = $sales_record->getDate()->format('d/m/Y');
            // $causale          = $sales_record->getCausale();
            // $shop_id          = $sales_record->getShopId();
            // $code             = $sales_record->getCode();
            // $description      = $sales_record->getDescription();
            // $description2     = $sales_record->getDescription2();
            // $amount           = $sales_record->getAmount();
            // $amount_deciaml   = $this->castToFloat($amount);
            // $amount_vat       = $sales_record->getAmountvat();
            // $amount_vat_decimal = $this->castToFloat($amount_vat);
            // $final_vat_amount   = $amount_deciaml + $amount_vat_decimal;


            $data[]   = array("PROGR"=>$progress, "DATA"=>$date_sales, "CAUSALE"=>$causale, "ID-SHOP"=>$shop_id, "CODICE"=>$code,
                                "DESCRIZIONE"=>$description, "DESCRIZIONE 2"=>$description2, "IMPORTO"=>$amount_deciaml, "IMPORTO_PIU_IVA"=>$final_vat_amount);
          $i++;   
        }


        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $file_name_date, $sheet_name, $sales_profile_type, $head_data_array, $data, $column_format, $column_left_align);
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

     /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Encode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $json = $serializer->serialize($req_obj, 'json');
        return $json;
    }

    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
}
