<?php

namespace WalletManagement\WalletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use CardManagement\CardManagementBundle\Entity\ShopRegPayment;
use Transaction\TransactionBundle\Entity\RecurringPayment;
use Transaction\TransactionBundle\Entity\RecurringPendingPayment;

/**
 * Controller for shop wallet
 */
class ShopTransactionHistoryController extends Controller
{
    protected $base_six = 1000000;
    protected $miss_param = '';
    public function indexAction($name)
    {
        return $this->render('WalletManagementWalletBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * Finding the transaction history for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postShoptransactionhistorysAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id');
        $data        = array();
        $result_data = array();
        $transaction_history_data_count = 0;
        //checking for parameter missing.
        $chk_error = $this->checkParams($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id   = $object_info->user_id;
        $shop_id   = $object_info->shop_id;
        $offset    = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit     = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $transaction_history_data = $em->getRepository('AcmeGiftBundle:Movimen')
                                       ->getTransactionHistory($shop_id, $offset, $limit);
        
        if (count($transaction_history_data)) {
            
            //find the count for transaction history for a shop.
            $transaction_history_data_count = $em->getRepository('AcmeGiftBundle:Movimen')
                                                 ->getTransactionHistoryCount($shop_id);            
            foreach ($transaction_history_data as $transaction_data) {
                $user_id         = $transaction_data->getIDCARD();
                $shop_current_id = $transaction_data->getIDPDV();
                $transaction_id  = $transaction_data->getIDMOVIMENTO();
                $total           = $transaction_data->getIMPORTODIGITATO();
                $date_object     = $transaction_data->getDATA();
                $date            = $date_object->format('Y-m-d');
                $rcuti           = $transaction_data->getRCUTI();
                $shuti           = $transaction_data->getSHUTI();
                $pshuti          = $transaction_data->getPSUTI();
                $gcuti           = $transaction_data->getGCUTI();
                $gcrim           = $transaction_data->getGCRIM();
                $mouti           = $transaction_data->getMOUTI();
                
                //making the calculation for shop transaction history..
                $user_service   = $this->get('user_object.service'); //user object service.
                $user_info      = $user_service->UserObjectService($user_id); //transaction user object
                $card           = $this->convertCurrency($gcuti - $gcrim);
                $cash           = $this->convertCurrency($total - ($shuti+$pshuti+$card));
                $discount       = $this->convertCurrency($pshuti + $shuti);
                $total          = $this->convertCurrency($total);
                
                $result_data[]  = array(
                    'user_obj'=>$user_info,
                    'date'=>$date,
                    'total'=>$total,
                    'card'=>$card,
                    'cash'=>$cash,
                    'discount'=>$discount
                );
            }
        }
        $data = array('transactions'=>$result_data, 'total'=>$transaction_history_data_count);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit;
    }
    
    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }
    
    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParams($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && ($converted_array[$param] != '')) {
                $check_error = 0;
            } else {
                $check_error = 1;
                $this->miss_param = $param;
                break;
            }
        }
        return $check_error;
    }
    
    /**
     * Finding the payment history for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postShoppaymenthistorysAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $vat_parameter            = $this->container->getParameter('vat');
        $reg_fee_newshop        = $this->container->getParameter('reg_fee');   
        $reg_fee_oldshop        = $this->container->getParameter('reg_fee_oldshop');  
        $reg_fee_parameter = 0;
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id');
        $data        = array();
        
        //checking for parameter missing.
        $chk_error = $this->checkParams($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        //get limit size
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
        }
        
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        
        $user_id   = $object_info->user_id;
        $shop_id   = $object_info->shop_id;
        
        $store_pending_amount = 0;
        $reg_fee= 0;
        $reg_vat= 0;
        $reg_fee_total = 0;
        $reg_fee_total_res = 0;
        $store_pending_amount_res = 0;
        //get store object
        $store_obj = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $shop_id));
        
        //if store not found
        if (!$store_obj) {
            $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
            return $res_data;
        }
        
        $old_shop_date = new \DateTime('2014-11-14');
        $shop_created_at = $store_obj->getCreatedAt();
        
        if($shop_created_at<$old_shop_date) {
            $reg_fee_parameter = $reg_fee_oldshop;
        }else{
           $reg_fee_parameter = $reg_fee_newshop;
        }
        
        $reg_vat_amount_add = 0;
        $store_pending_amount_add = 0;
        $store_pending_amount_add_res = 0;
        
        /* code for getting previous success payment history of shop for pending payment */
        $previous_success_payment = $em
                               ->getRepository('CardManagementBundle:ShopRegPayment')
                               ->getPreviousPaymentOfShop($shop_id,$limit_start,$limit_size);
        $count = 0;
        $previous_success_payment_count = $em
                               ->getRepository('CardManagementBundle:ShopRegPayment')
                               ->getPreviousPaymentOfShopCount($shop_id);    
        $count = $previous_success_payment_count;
        $previous_pending_payment_res = array();
        
        
        if($previous_success_payment) {
           foreach($previous_success_payment as $record) {
               $pending_amount = $record->getAmount();
               $pending_amount_res = 0;
               if($pending_amount > 0) {
                   $pending_amount_res = $this->converToEuro($pending_amount);
               }
               $pre_arr = array(
                   'date' => $record->getCreatedAt(),
                   'amount' =>$pending_amount_res
               );
               $previous_pending_payment_res[] = $pre_arr; 
           }
        }
        
        /*Code for fetching result for pending amount to pay*/
        $pending_record_count = 0;
        $current_pending_amount_of_shop = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->findBy(array('paid' =>0,'shopId'=>$shop_id));
       
        $payment_status = $store_obj->getPaymentStatus();
        
        $payto_pending_payment_res = array();
        if($current_pending_amount_of_shop) {
            foreach($current_pending_amount_of_shop as $record) {
               $pending_amount_curr = $record->getPendingamount();
               $pending_amount_res_curr = 0;
               if($pending_amount_curr > 0) {
                   $pending_amount_res_curr = $this->converToEuro($pending_amount_curr);
               }
               $pre_arr_curr = array(
                   'date' => $record->getCreatedAt(),
                   'amount' =>$pending_amount_res_curr
               );
               $payto_pending_payment_res[] = $pre_arr_curr; 
           }
        }
        
        if($payment_status == 0) {
            $pending_record_count = $pending_record_count + 1;
            $pre_arr_registration = array(
                   'date' =>$store_obj->getCreatedAt(),
                   'amount' =>$reg_fee_parameter/100,
               );
            $payto_pending_payment_res[] = $pre_arr_registration;
        }
        $total_pending_amount = 0;
        $total_pending_amount_vat = 0;
        $total_grand_pending_amount = 0;
        foreach($payto_pending_payment_res as $record) {
            $total_pending_amount = $total_pending_amount + $record['amount'];
        }
        if($total_pending_amount > 0) {
            $total_pending_amount_vat = ($total_pending_amount*$vat_parameter)/100;
        }        
        $total_grand_pending_amount = $total_pending_amount_vat + $total_pending_amount;
        
        /* code for getting registration fee recieved by shop */
        $reg_fee_recieved_by_sixcontinent = 0;    
        $reg_fee_recieved_by_sixcontinent = $em
                               ->getRepository('CardManagementBundle:ShopRegPayment')
                               ->getAllPaymentGivenToSix((int)$shop_id); 
           
        if($reg_fee_recieved_by_sixcontinent > 0) {
            $reg_fee_recieved_by_sixcontinent = $this->converToEuro($reg_fee_recieved_by_sixcontinent);
        }
         
        $data = array(            
            'pending_payment_detail' =>$payto_pending_payment_res,
            'total_pending_vat' =>$total_pending_amount_vat,
            'pending_payment' =>$total_grand_pending_amount,
            #'pending_record_count' =>$pending_record_count,
            'previous_payment_success' => $previous_pending_payment_res,
            'payment_received_by_sixthcontinent' =>$reg_fee_recieved_by_sixcontinent,
            'count' =>$count
        );
        
        $res_data = array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToEuro($amount) {
        $amount_euro =  $amount/$this->base_six;
        return $amount_euro;
    }
    /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToBaseSix($amount) {
        $amount_euro =  $amount*$this->base_six;
        return $amount_euro;
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
}
