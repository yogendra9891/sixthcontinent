<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use ExportManagement\ExportManagementBundle\Entity\Purchase;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

/**
 * import the purchased data
 */
class PurchaseImportController extends Controller {

    protected $base_six = 1000000;

    
    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * Import the shop purchase
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function purchaseimportAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        
        //get data to be imported
        $purchase_data = array();
        $res_data = array();
        $time = new \DateTime('now');
        $data['start_date'] = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) - (60 * 60 * 24)));
        $data['end_date']   = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));

        //get the applane service for transaction data
        $applane_service         = $this->container->get('appalne_integration.callapplaneservice');
        $import_purchase_data = $applane_service->getpurchasetransactiondata($data); //get data from applane of previous day.

        if ($import_purchase_data->code == 200) {
            $purchase_data = $import_purchase_data->response->result;
        }
        //check if we have some records for import
        if (count($purchase_data)) {
            //import the data.
            $result = $this->importPurchase($purchase_data);
        }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA', 'data' => array());
        }
        echo json_encode($data);
        exit;
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
            $purchase    = new Purchase();
            $date_data   = (isset($data->date) ? $data->date : null);
            $current_date = date(DATE_RFC3339, strtotime($date_data)); //change it according to pplication time zone
            $code        = (isset($data->card_code)? $data->card_code : '');
            $description = (isset($data->card_no) ? $data->card_no : '');
            $amount      = (isset($data->credit) ? $data->credit : 0);
            $shop_id     = (isset($data->shop_id->_id) ? $data->shop_id->_id : 0);
            $citizen_id  = (isset($data->citizen_id->_id) ? $data->citizen_id->_id : 0);
            
            $format_date_object = new \DateTime($current_date);
            $format_date        = $format_date_object->format('Y-m-d');
            $purchase_year  = date('y', strtotime($format_date));
            $purchase_month = date('m', strtotime($format_date));
            $purchase_day   = date('d', strtotime($format_date));
            $counter_value  = str_pad($counter, 2, "0", STR_PAD_LEFT); //add 0 in the beginning from 1-9
            $numero_quietanza = $purchase_year.$purchase_month.$purchase_day.$counter_value;
            
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
            $applane_service->writeTransactionLogs('Purchase Data to be import: '.  json_encode($purchase_data), 'purchase imported data successfully');  //write log
            return 1;
        } catch (\Exception $ex) {
            $applane_service->writeTransactionLogs('Purchase Data to be import: '.  json_encode($purchase_data), 'purchase imported data failure');  //write log
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
