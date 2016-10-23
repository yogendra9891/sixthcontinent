<?php

namespace Paypal\PaypalIntegrationBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Paypal\PaypalIntegrationBundle\Entity\ShopPaypalInformation;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Paypal\PaypalIntegrationBundle\Utils\MessageFactory as Msg;

require_once(__DIR__ . '/../Resources/lib/adaptiveaccounts-sdk-php-master/sdk/Configuration.php');

use Configuration;

class EcommerceProductController extends Controller {
    
    CONST RESPONSE_TYPE_SUCCESS = 'SUCCESS';
    CONST RESPONSE_TYPE_CANCEL = 'CANCELED';
    
    protected $valid_response_type = array(self::RESPONSE_TYPE_CANCEL,self::RESPONSE_TYPE_SUCCESS);


    CONST ECOMMERCE_PRODUCT = 'EP';
    CONST PAYPAL_SUCCESS = 'Success';
    /**
     * Buy ecommerce product
     * @param \Symfony\Component\HttpFoundation\Request $request
     * 
     */
    public function postBuyEcommerceProductAction(Request $request) 
    {
       $buyEcommerceProductService = $this->getBuyEcommerceProductService();
       $buyEcommerceProductService->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postBuyEcommerceProductAction]', array());
       $utilityService = $this->getUtilityService();
        $requiredParams = array('session_id', 'offer_id', 'citizen_id','ordr_citizen_id','ordr_creation','ordr_line_item','ordr_shop_id','cancel_url', 'return_url', 'product_name');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request); 
        $object_info = (object)$data;
        $shop_id = $object_info->ordr_shop_id;
        $user_id = $object_info->session_id;
        $offer_id = $object_info->offer_id;
        $paypal_sender_transaction_id = '';
        $sender_paypal_email = '';
        $paypal_reciver_transaction_id = '';
        $product_name = $object_info->product_name;
        $reurn_data = array();
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        $buyEcommerceProductService->__createLog('Request data: ' . Utility::encodeData($object_info));
        //check the store is exist
        $store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        if (!$store_info) {
            $buyEcommerceProductService->__createLog('Store does not exists shop_id:' . $shop_id);
            $resp_data = new Resp(Msg::getMessage(1055)->getCode(), Msg::getMessage(1055)->getMessage(), array()); //SHOP_DOES_NOT_EXISTS
            Utility::createResponse($resp_data);
        }
        if ($store_info->getShopStatus() == 0) { //if shop is blocked..
            $buyEcommerceProductService->__createLog('Store is blocked shop_id:' . $shop_id);
            $resp_data = new Resp(Msg::getMessage(1105)->getCode(), Msg::getMessage(1105)->getMessage(), array()); //SHOP_IS_BLOCKED
            Utility::createResponse($resp_data);         
        }
        
        //find paypal account for shop
        $shop_paypal_info = $em->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => 'VERIFIED', 'isDefault' => 1));

        if (!$shop_paypal_info) {
            $buyEcommerceProductService->__createLog('Store Paypal account does not exists shop_id:' . $shop_id);
            $resp_data = new Resp(Msg::getMessage(1058)->getCode(), Msg::getMessage(1058)->getMessage(), array()); //SHOP_PAYPAL_DOES_NOT_EXISTS
            Utility::createResponse($resp_data);        
        }
        $paypal_id = $shop_paypal_info->getAccountId();
        $shop_paypal_email = $shop_paypal_info->getEmailId();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
         if ($mode == 'sandbox') {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_sandbox');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_sandbox');
        } else {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_live');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_live');
        }
       $applane_data_array = (array)$object_info;
        //unset the parameter
        if(isset($applane_data_array['cancel_url'])){
            unset($applane_data_array['cancel_url']);
        }
        if(isset($applane_data_array['return_url'])){
            unset($applane_data_array['return_url']);
        }
        $applane_data = json_encode($applane_data_array); //get the applane service for transaction data for buy card
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $transaction_system_data = $applane_service->buyEcommerceProductCard($applane_data); //get transaction data from transaction system.
        
        if ($transaction_system_data['transaction_id'] == '') { //if transaction is not initiated in transaction system
            $buyEcommerceProductService->__createLog('Transaction is not initiated on transaction system for shopId: ' . $shop_id.' offerId: '.$offer_id. ' and userId: '.$user_id);
            $data = array('code' => $transaction_system_data['code'], 'message' => $transaction_system_data['message'], 'data' => array());
            //$resp_data = new Resp($result['code'], $result['message'], array());
            //Utility::createResponse($resp_data);      
            $this->returnResponse($data);
        }
        $calculated_data = $buyEcommerceProductService->calculateShoppingCardAmount($transaction_system_data, $store_info); //calculate the amount to be paid.
        $primary_user_amount  = round(($calculated_data['shop_amount'] + $calculated_data['sixth_continent_amount']), 2);
        $secondry_user_amount = round($calculated_data['sixth_continent_amount'], 2);
        $transaction_data = array('transaction_id' => $transaction_system_data['transaction_id'], 'primary_user_paypal_email' => $shop_paypal_email,
            'primary_user_amount' => $primary_user_amount,
            'secondry_user_paypal_email' => $paypal_sixthcontinent_email, 'secondry_user_amount' => $secondry_user_amount, 'transaction_inner_id' => $transaction_system_data['transaction_inner_id'], 'order_id' => $transaction_system_data['order_id']);

        $amount = $calculated_data['total_amount']; //total amount
        $vat_amount = $calculated_data['vat_amount'];
        $transaction_query = '?transaction_id=' . $transaction_data['transaction_id'] . '&shop_id=' . $shop_id;
        $cancel_url = urlencode($object_info->cancel_url . $transaction_query);
        $return_url = urlencode($object_info->return_url . $transaction_query);
        $paypal_response = $buyEcommerceProductService->getPaypalResponse($transaction_data, $shop_id, $user_id, $cancel_url, $return_url); //register transaction on paypal.
        if (isset($paypal_response->responseEnvelope)) { //response from paypal.
            if ($paypal_response->responseEnvelope->ack == self::PAYPAL_SUCCESS) { //if successfullly transaction registered.
                $pay_key = $paypal_response->payKey;
                $buyEcommerceProductService->__createLog('Transaction is initiated on paypal with pay key:' . $pay_key);
                $buyEcommerceProductService->__createLog('Transaction is initiated on transaction system with transaction id:' . $transaction_data['transaction_id']);
                $reurn_data = array('link' => $paypal_authorize_url . $pay_key, 'cancel_url' => $cancel_url, 'return_url' => $return_url);
                $reason = self::ECOMMERCE_PRODUCT;
                $buyEcommerceProductService->savePaymentTransactionRecord($user_id, $shop_id, $pay_key, $transaction_data['transaction_id'], $paypal_id, $amount, $vat_amount, $transaction_data['transaction_inner_id'],$calculated_data['ci_used'], $reason, $product_name, $transaction_data['order_id']);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $reurn_data); //SUCCESS
                $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] function [postBuyEcommerceProductAction] with data: '.Utility::encodeData($resp_data));
                Utility::createResponse($resp_data);        
            } else {
                $reject_status = ApplaneConstentInterface::REJECTED;
                $applane_service->updateEcommerceProductStatus( $transaction_data['transaction_id'], $reject_status, $reject_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id); //update on transaction system.
                $resp_data = new Resp(Msg::getMessage(1029)->getCode(), Msg::getMessage(1029)->getMessage(), $reurn_data); //FAILURE
                $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] function [postBuyEcommerceProductAction] with data: '.Utility::encodeData($resp_data));
                Utility::createResponse($resp_data);  
            }
         } else {
            $reject_status = ApplaneConstentInterface::REJECTED;
            $paypal_sender_transaction_id = '';
            $sender_paypal_email = '';
            $paypal_reciver_transaction_id = '';
            //$reject_status1 = '{"status_code":"Rejected"'_id' => array('5599216cbde466271fd344f7')}';
            $applane_service->updateEcommerceProductStatus($transaction_system_data['transaction_id'], $reject_status, $reject_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id); //update on transaction system.
            $resp_data = new Resp(Msg::getMessage(1029)->getCode(), Msg::getMessage(1029)->getMessage(), $reurn_data); //FAILURE
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] function [postBuyEcommerceProductAction] with data: '.Utility::encodeData($resp_data));
            Utility::createResponse($resp_data);  
        }
        //$this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [postBuyshoppingcardsAction] with data: '.$this->toJson($data));
        Utility::createResponse($resp_data); 
    }
    
    /**
     * Response Of Buy Ecommerce Product
     * response of the ecommerce product purchased (100% card)
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postResponseBuyEcommerceProductAction(Request $request) {
        $data = array();
        $buyEcommerceProductService = $this->getBuyEcommerceProductService();
        $buyEcommerceProductService->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction]', array());
        $utilityService = $this->getUtilityService();
        $requiredParams = array('session_id', 'shop_id', 'transaction_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $deserailized = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $deserailized['session_id'];
        $shop_id = $deserailized['shop_id'];
        $transaction_id = $deserailized['transaction_id'];
        $type = $deserailized['type'];
        $code = ApplaneConstentInterface::SUCCESS_CODE;
        $message = ApplaneConstentInterface::SUCCESS;
        $type_uppercase = Utility::getUpperCaseString(Utility::getTrimmedString($type));
        $valid_types = $this->valid_response_type;
        if(!in_array($type_uppercase, $valid_types)) {
            $resp_data = new Resp(Msg::getMessage(1136)->getCode(), Msg::getMessage(1136)->getMessage(), $data); //INAVALID_TYPE
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        
        //get transaaction record from local database
        $payment_transaction_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("shopId" => $shop_id, "itemId" => $transaction_id));
        if (!$payment_transaction_record) { //check for transaction is exists.
            $resp_data = new Resp(Msg::getMessage(1060)->getCode(), Msg::getMessage(1060)->getMessage(), $data); //TRANSACTION_RECORD_DOES_NOT_EXISTS
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        //check transaction owner
        $transaction_owner_id = $payment_transaction_record->getCitizenId();
        $product_name = $payment_transaction_record->getProductName();
        $order_id = $payment_transaction_record->getOrderId();
        if ($transaction_owner_id != $user_id) { //chek if other user is trying.
            $resp_data = new Resp(Msg::getMessage(1061)->getCode(), Msg::getMessage(1061)->getMessage(), $data); //TRANSACTION_RECORD_DOES_NOT_BELONGS_TO_YOU
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        $transaction_current_status = Utility::getUpperCaseString($payment_transaction_record->getPaymentStatus());
        $transaction_pay_key = $payment_transaction_record->getTransactionReference();

        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //check for the transaction already confirm and cancel
        if ($transaction_current_status == ApplaneConstentInterface::CONFIRMED) { //if transaction already confirmed
            $resp_data = new Resp(Msg::getMessage(1062)->getCode(), Msg::getMessage(1062)->getMessage(), $data); //TRANSACTION_RECORD_ALREADY_CONFIRMED
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        } else if ($transaction_current_status == ApplaneConstentInterface::CANCELED) { //if transaction already canceled
            $resp_data = new Resp(Msg::getMessage(1063)->getCode(), Msg::getMessage(1063)->getMessage(), $data); //TRANSACTION_RECORD_ALREADY_CANCELED
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        //get shop object.
        $shop_object_service = $this->container->get('user_object.service');
        $shop_info = $shop_object_service->getStoreObjectService($shop_id);
        //get shop owner id
        $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'role' => 15));
        if (!$store_obj) { //check shop is exists.
            $resp_data = new Resp(Msg::getMessage(1055)->getCode(), Msg::getMessage(1055)->getMessage(), $data); //SHOP_DOES_NOT_EXISTS
            $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
         $shop_owner_id = $store_obj->getuserId();
         $replacetxt = $product_name; //for notification
         $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        //perform action based on the type
        if (Utility::matchString($type_uppercase, self::RESPONSE_TYPE_SUCCESS)) {
            //check on paypal if transaction is completed means user paid for this.
            $result = $buyEcommerceProductService->checkTransactionStatus($transaction_pay_key);
            if (isset($result->status)) {
                switch ($this->convertString($result->status)) {
                    case ApplaneConstentInterface::COMPLETED:
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::CONFIRMED;
                        $applane_status = ApplaneConstentInterface::APPROVED;
                        $payment_status = ApplaneConstentInterface::APPROVED;
                        $transaction_info = $this->getPaypalTransactionIds($result);
                        $applane_service->updateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, $transaction_info['sender_transaction_id'], $transaction_info['sender_email'], $transaction_info['reciever_transaction_id']); //update on transaction system.
                        $buyEcommerceProductService->__createLog('Calliing the CI return code from the postResponseBuyEcommerceProductAction web-service:shop_id'.$shop_id);
                        $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                        $paypal_transaction_service->sendMailNotificationBuyEcommerceProduct($shop_id,$transaction_id,$user_id, $product_name);//send mail
                        $buyEcommerceProductService->sendPushNotifications($shop_owner_id, $user_id, $shop_id, $replacetxt, $order_id); //send web and push notification
                        break;
                    case ApplaneConstentInterface::IN_COMPLETE:
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::IN_COMPLETE;
                        $applane_status = ApplaneConstentInterface::APPROVED;
                        $payment_status = ApplaneConstentInterface::APPROVED;
                        $transaction_info = $this->getPaypalTransactionIds($result);
                        $applane_service->updateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, $transaction_info['sender_transaction_id'], $transaction_info['sender_email'], $transaction_info['reciever_transaction_id']); //update on transaction system.
                        $buyEcommerceProductService->__createLog('Calliing the CI return code from the postResponseBuyEcommerceProductAction web-service:shop_id'.$shop_id);
                        $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                        $paypal_transaction_service->sendMailNotificationBuyEcommerceProduct($shop_id,$transaction_id,$user_id, $product_name);//send mail
                        $buyEcommerceProductService->sendPushNotifications($shop_owner_id, $user_id, $shop_id, $replacetxt, $order_id);//send web and push notification
                        break;
                    case ApplaneConstentInterface::PENDING:
                        $message = ApplaneConstentInterface::TRANSACTION_PENDING_ERROR_MESSAGE;
                        $code = ApplaneConstentInterface::TRANSACTION_PENDING_ERROR_CODE;
                        break;
                    case ApplaneConstentInterface::PROCESSING:
                        $message = ApplaneConstentInterface::TRANSACTION_PROCESSING_ERROR_MESSAGE;
                        $code = ApplaneConstentInterface::TRANSACTION_PROCESSING_ERROR_CODE;
                        break;
                    case ApplaneConstentInterface::ERROR:
                        $message = ApplaneConstentInterface::TRANSACTION_ERROR_MESSAGE;
                        $code = ApplaneConstentInterface::TRANSACTION_ERROR_CODE;
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::CANCELED;
                        $applane_status = ApplaneConstentInterface::REJECTED;
                        $payment_status = ApplaneConstentInterface::REJECTED;
                        $applane_service->updateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, '', '', ''); //update on transaction system.
                        break;
                    default:
                        $code = ApplaneConstentInterface::TRANSACTION_ERROR_CODE;
                        $message = ApplaneConstentInterface::TRANSACTION_ERROR_MESSAGE;
                }
            }
        } else if (Utility::matchString ($type_uppercase, ApplaneConstentInterface::CANCELED)) {
            $code = ApplaneConstentInterface::TRANSACTION_CANCELED_ERROR_CODE;
            $message = ApplaneConstentInterface::TRANSACTION_CANCELED_ERROR_MESSAGE;
            $is_local_update = 1;
            $transaction_status = ApplaneConstentInterface::CANCELED;
            $applane_status = ApplaneConstentInterface::REJECTED;
            $payment_status = ApplaneConstentInterface::REJECTED;
            $applane_service->updateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, '', '', ''); //update on transaction system.
        }

        //update in our local database
        if ($is_local_update) {
            $payment_transaction_record->setPaymentStatus($transaction_status); //update transaction status
            $em->persist($payment_transaction_record);
            $em->flush();
            $buyEcommerceProductService->__createLog('In class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] and function [postResponseBuyEcommerceProductAction] Shopping card purchase response service local system status update with: ' . $transaction_status);
        }
        
        $data = array('code' => $code, 'message' => $message, 'data' => array());
        $buyEcommerceProductService->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\EcommerceProductController] function [postResponseBuyEcommerceProductAction] with response:' . Utility::encodeData($data));
        $this->returnResponse($data);
    }
    
    
    /**
     *  function for getting the transaction id and email of sender and recievers
     * @param type $result
     */
    private function getPaypalTransactionIds($result) {
        try {
            $data = array();
            $payment_info = isset($result->paymentInfoList) ? $result->paymentInfoList : (object) array();
            $payment_info = $payment_info->paymentInfo;
            $reciever_info = $payment_info[0];
            $data['reciever_transaction_id'] = $reciever_info->transactionId;
            $data['sender_transaction_id'] = $reciever_info->senderTransactionId;
            $data['sender_email'] = $result->senderEmail;
        } catch (\Exception $ex) {
            $data['reciever_transaction_id'] = '';
            $data['sender_transaction_id'] = '';
            $data['sender_email'] = '';
        }
        
        return $data;
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array, JSON_NUMERIC_CHECK);
        exit;
    }
    /**
     * Get Object of ecommerce service
     * @return type
     */
    public function getBuyEcommerceProductService()
    {
        return $this->container->get('buy_ecommerce_product.ecommerce');
    }
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility'); //StoreManager\StoreBundle\Utils\UtilityService
    }
    
    
     /**
     * convert the string into uppercase
     * @param string $string
     * @return string $final_string
     */
    public function convertString($string) {
        $final_string = Utility::getUpperCaseString(Utility::getTrimmedString($string));;       
        return $final_string;
    }
    
    
}
