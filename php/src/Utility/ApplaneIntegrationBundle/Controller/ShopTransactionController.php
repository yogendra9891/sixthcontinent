<?php

namespace Utility\ApplaneIntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use CardManagement\CardManagementBundle\Entity\Contract;
use Notification\NotificationBundle\Document\UserNotifications;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionDetail;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\ApplaneIntegrationBundle\Document\TransactionPaymentNotificationLog;

class ShopTransactionController extends Controller {
    
    /**
     * List pending transactions for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postListpendingpaymentsAction(Request $request)
    {
        $data = array();
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

        $required_parameter = array('shop_id', 'user_id');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($res_data); 
        }
        
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->user_id;
        $limit_start = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit_size = (isset($object_info->limit_size)) ? $object_info->limit_size: 20;
        $this->getPendingAmount($shop_id, $limit_start, $limit_size);
    }
    
    /**
     * Get pending amount
     * @param int $shop_id
     */
    public function getPendingAmount($shop_id, $limit_start, $limit_size)
    {
        $data = array();
        $em = $this->getDoctrine()->getManager();
        $shop_transactions = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getAllShopPedningTransaction($shop_id, $limit_start, $limit_size);
        if(!$shop_transactions){
            $res_data = array('code' => 1066, 'message' => 'NO_TRANSACTION_FOUND', 'data' => $data);
            $this->returnResponse($res_data);
        }
 
        //get shop object
        $user_service = $this->get('user_object.service');
        $store_detail = $user_service->getStoreObjectService($shop_id);
        if(!$store_detail){
            $store_detail = array();
        }
      
        //handling for transaction
        foreach($shop_transactions as $shop_transaction){
            $vat_amount = $shop_transaction->getVat();  //calculate vat
            $amount_paid_with_vat = $shop_transaction->getTotalPayableAmount();
            $amount_paid_without_vat = $shop_transaction->getPayableAmount(); //calculate total amount paid
            $type = $shop_transaction->getType();
            $data[] = array('shop_info'=> $store_detail, 'pending_amount_with_vat' => $amount_paid_with_vat, 'pending_amount_without_vat' => $amount_paid_without_vat, 'vat' => $vat_amount, 'type'=> $type, 'date'=>$shop_transaction->getDate());
        }
        
        $shop_transactions_count = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getAllShopPedningTransactionCount($shop_id);
        
        $final_data = array('transactions' =>$data,  'size' => $shop_transactions_count);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $final_data);
        $this->returnResponse($res_data);
    }
    
    /**
     * Get total pending payment
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGettotalpendingpaymentsAction(Request $request){
         $data = array();
         $total_payable_amount = array();
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

        $required_parameter = array('shop_id', 'user_id');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($res_data); 
        }
        
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->user_id;
        
        //get shop object
        $user_service = $this->get('user_object.service');
        $store_detail = $user_service->getStoreObjectService($shop_id);
        if(!$store_detail){
            $store_detail = array();
        }
        
        $em = $this->getDoctrine()->getManager();
        $shop_transactions = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getAllShopPedningTransaction($shop_id);

        if($shop_transactions){
            foreach($shop_transactions as $shop_transaction){
                $total_payable_amount[] = $shop_transaction->getTotalPayableAmount();
            }
        }
        $total_amount = array_sum($total_payable_amount);
        $data = array('shop_info'=> $store_detail, 'pending_amount' => $total_amount);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParamsAction($chk_params, $object_info) {
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
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }
    
}