<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use ExportManagement\ExportManagementBundle\Entity\Sales;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

/**
 * import the sales data
 */
class SalesImportController extends Controller {

    protected $base_six = 1000000;

    
    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Import the shop sales
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function salesimportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        
        $sales_data = array();

        //get data to be imported
        $res_data = array();
        $time     = new \DateTime('now');
        $data['start_date'] = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) - (60 * 60 * 24)));
        $data['end_date']   = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));

        //get the applane service for transaction data
        $applane_service   = $this->container->get('appalne_integration.callapplaneservice');
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
            $date_data   = (isset($data->date) ? $data->date : null);
            $current_date = date(DATE_RFC3339, strtotime($date_data)); //change it according to pplication time zone
            $progress    = ApplaneConstentInterface::SIX_PROGRESS_CONST.$counter;
            $causale     = (isset($data->transaction_type_id->code) ? $data->transaction_type_id->code : '');
            $code        = (isset($data->transaction_type_id->sub_code) ? $data->transaction_type_id->sub_code : '');
            $shop_id     = (isset($data->shop_id->_id) ? $data->shop_id->_id : 0);
            $description1        = (isset($data->transaction_type_id->description1) ? $data->transaction_type_id->description1 : '');
            $description2        = (isset($data->transaction_type_id->description2) ? $data->transaction_type_id->description2 : '');
            $amount  = (isset($data->checkout_value) ? $data->checkout_value : 0);
            $vat     = (isset($data->vat) ? $data->vat : 0);
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
            $applane_service->writeTransactionLogs('Sales Data to be import: '.  json_encode($sales_data), 'sales imported data successfully');  //write log
            return 1;
        } catch (\Exception $ex) {
            $applane_service->writeTransactionLogs('Sales Data to be import: '.  json_encode($sales_data), 'sales imported data failed');  //write log
            return 0;
        }
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
