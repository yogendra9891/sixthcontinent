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
use Paypal\PaypalIntegrationBundle\Model\PaypalConstentInterface;

require_once(__DIR__ . '/../Resources/lib/adaptiveaccounts-sdk-php-master/sdk/Configuration.php');

use Configuration;

class CardPurchaseController extends Controller {

    protected $miss_param = '';

    CONST CARD_PERCENTAGE = 10;
    CONST PAY_KEY = 'pay_key';
    CONST STATUS = 'status';
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');

        return $jsonContent;
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

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
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
     * buy 100% shopping cards.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * 
     */
    public function postBuyshoppingcardsAction(Request $request) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [postBuyshoppingcardsAction]');
        //initialise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'shop_id', 'offer_id', 'cancel_url', 'return_url');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $shop_id = $object_info->shop_id;
        $user_id = $object_info->session_id;
        $offer_id = $object_info->offer_id;
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        $this->writePaypalLogs('Request data: ' . $this->toJson($object_info));
        //check the store is exist
        $store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        if (!$store_info) {
            $this->writePaypalLogs('Store does not exists shop_id:' . $shop_id);
            $data = array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
        }
        if ($store_info->getShopStatus() == 0) { //if shop is blocked..
            $this->writePaypalLogs('Store is blocked shop_id:' . $shop_id);
            //$data = array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS', 'data' => array());
            $data = array('code' => 1105, 'message' => 'SHOP_IS_BLOCKED', 'data' => array()); //@TODO: need to uncomment and above live to be romeved when front end handled
            $this->returnResponse($data);            
        }
        
        //find paypal account for shop
        $shop_paypal_info = $em->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => 'VERIFIED', 'isDefault' => 1));

        if (!$shop_paypal_info) {
            $this->writePaypalLogs('Store Paypal account does not exists shop_id:' . $shop_id);
            $data = array('code' => 1058, 'message' => 'SHOP_PAYPAL_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
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
        $applane_data = array('shop_id' => $shop_id, 'user_id' => $user_id, 'offer_id' => $offer_id,
            'do_transaction' => ApplaneConstentInterface::SIX_CONTINENT_SHOPPING_CARD_TRANSACTION_WITH_CREDIT, 'status' => ApplaneConstentInterface::SIX_CONTINENT_SHOPPING_CARD_TRANSACTION_INITIATED);
        //get the applane service for transaction data for buy card
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $transaction_system_data = $applane_service->buyShopOfferCard($applane_data); //get transaction data from transaction system.

        if ($transaction_system_data['transaction_id'] == '') { //if transaction is not initiated in transaction system
            $this->writePaypalLogs('Transaction is not initiated on transaction system for shopId: ' . $shop_id.' offerId: '.$offer_id. ' and userId: '.$user_id);
            $data = array('code' => $transaction_system_data['code'], 'message' => $transaction_system_data['message'], 'data' => array());
            $this->returnResponse($data);
        }
        $calculated_data = $this->calculateShoppingCardAmount($transaction_system_data, $store_info); //calculate the amount to be paid.
        $primary_user_amount  = round(($calculated_data['shop_amount'] + $calculated_data['sixth_continent_amount']), 2);
        $secondry_user_amount = round($calculated_data['sixth_continent_amount'], 2);
        $transaction_data = array('transaction_id' => $transaction_system_data['transaction_id'], 'primary_user_paypal_email' => $shop_paypal_email,
            'primary_user_amount' => $primary_user_amount,
            'secondry_user_paypal_email' => $paypal_sixthcontinent_email, 'secondry_user_amount' => $secondry_user_amount, 'transaction_inner_id' => $transaction_system_data['transaction_inner_id']);

        $amount = $calculated_data['total_amount']; //total amount
        $vat_amount = $calculated_data['vat_amount'];
        $transaction_query = '?transaction_id=' . $transaction_data['transaction_id'] . '&shop_id=' . $shop_id;
        $cancel_url = urlencode($object_info->cancel_url . $transaction_query);
        $return_url = urlencode($object_info->return_url . $transaction_query);

        $paypal_response = $this->getPaypalResponse($transaction_data, $shop_id, $user_id, $cancel_url, $return_url); //register transaction on paypal.

        if (isset($paypal_response->responseEnvelope)) { //response from paypal.
            if ($paypal_response->responseEnvelope->ack == 'Success') { //if successfullly transaction registered.
                $pay_key = $paypal_response->payKey;
                $this->writePaypalLogs('Transaction is initiated on paypal with pay key:' . $pay_key);
                $this->writePaypalLogs('Transaction is initiated on transaction system with transaction id:' . $transaction_data['transaction_id']);
                $reurn_data = array('link' => $paypal_authorize_url . $pay_key, 'cancel_url' => $cancel_url, 'return_url' => $return_url);
                $this->savePaymentTransactionRecord($user_id, $shop_id, $pay_key, $transaction_data['transaction_id'], $paypal_id, $amount, $vat_amount, $transaction_data['transaction_inner_id'],$calculated_data['ci_used']);
                $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $reurn_data);
            } else {
                $handler = $this->container->get('monolog.logger.paypal_shopping_logs');
                $reject_status = ApplaneConstentInterface::REJECTED;
                $applane_service->updateShoppingCardStatus($transaction_system_data['transaction_id'], $reject_status, $handler); //update on transaction system.
                $data = array('code' => 1029, 'message' => 'FAILURE', 'data' => array());
            }
        } else {
            $handler = $this->container->get('monolog.logger.paypal_shopping_logs');
            $reject_status = ApplaneConstentInterface::REJECTED;
            $applane_service->updateShoppingCardStatus($transaction_system_data['transaction_id'], $reject_status, $handler); //update on transaction system.
            $data = array('code' => 1029, 'message' => 'FAILURE', 'data' => array());
        }
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [postBuyshoppingcardsAction] with data: '.$this->toJson($data));
        $this->returnResponse($data);
    }

    /**
     * paypal response and update the status
     * @param string $transaction_data
     * @param int $shop_id
     * @param int $citizen_id
     */
    public function getPaypalResponse($transaction_data, $shop_id, $citizen_id, $cancel_url, $return_url) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_sandbox');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
        } else {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_live');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
        }
        $primary_reciever_paypal_email = $transaction_data['primary_user_paypal_email'];
        $primary_reciever_amount = $transaction_data['primary_user_amount'];
        $secondry_reciever_email = $transaction_data['secondry_user_paypal_email'];
        $secondry_reciever_amount = $transaction_data['secondry_user_amount'];
        $curreny_code = $this->container->getParameter('paypal_currency');
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        //get fee payer for the shop
        //$fee_payer = $paypal_transaction_service->checkWhoPayTransactionFee($shop_id);
        $paypal_service = $this->container->get('paypal_integration.payment_transaction');
        $type = PaypalConstentInterface::CHAINED_PAYMENT_FEE_PAYER;
        $item_type = PaypalConstentInterface::ITEM_TYPE_SHOP;
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $feesPayerParam = '&feesPayer=';
        $final_fee_payer = $feesPayerParam.$fee_payer;
        $ipn_notification_url = urlencode($this->container->getParameter('symfony_base_url') . ApplaneConstentInterface::IPN_NOTIFICATION_URL); //ipn notification url
        //$ipn_notification_url = 'http://45.33.45.34/sixthcontinent_symfony/php/web/webapi/ipncallbackresponse'; //for local because paypal does not accept localhost in ipn url
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $payload = "actionType=PAY&ipnNotificationUrl=$ipn_notification_url&cancelUrl=$cancel_url&clientDetails.applicationId=APP-80W284485P519543"
                . "&clientDetails.ipAddress=127.0.0.1&currencyCode=$curreny_code" .
                "&receiverList.receiver(0).amount=$primary_reciever_amount&receiverList.receiver(0).email=$primary_reciever_paypal_email" .
                "&receiverList.receiver(0).primary=true&receiverList.receiver(1).amount=$secondry_reciever_amount" .
                "&receiverList.receiver(1).email=$secondry_reciever_email" .
                "&receiverList.receiver(1).primary=false" .
                "&requestEnvelope.errorLanguage=en_US" .
                "&returnUrl=$return_url".$final_fee_payer;
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true
        );

        try {
            $curl = curl_init($paypal_end_point);
            if (!$curl) {
                throw new \Exception('Could not initialize curl');
            }
            if (!curl_setopt_array($curl, $options)) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            $result = curl_exec($curl);
            if (!$result) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            curl_close($curl);
            //$applane_service->writeTransactionLogs('Paypal chain request: ' . $payload, 'paypal chain response: ' . $result);  //write the log for error 
            $this->writePaypalLogs('Paypal chain request: ' . $payload);
            $this->writePaypalLogs('paypal chain response: ' . $result);
            $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
            return json_decode($result);
        } catch (\Exception $e) {
           // $applane_service->writeTransactionLogs('Paypal chain request: ' . $payload, 'paypal chain response: ' . $e->getMessage());  //write the log for error 
            $this->writePaypalLogs('Paypal chain request: ' . $payload);
            $this->writePaypalLogs('paypal chain response: ' . $e->getMessage());
        }
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
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
     * response of the card purchased (100% card)
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postResponsebuycardsAction(Request $request) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [postResponsebuycardsAction]');
        //initialise the array
        $data = array();
        $handler = $this->container->get('monolog.logger.paypal_shopping_logs');
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'shop_id', 'transaction_id', 'type');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //extract variables.
        $user_id = $object_info->session_id;
        $shop_id = $object_info->shop_id;
        $transaction_id = $object_info->transaction_id;
        $type = $this->convertString($object_info->type);
        $transaction_status = '';
        $applane_status = '';
        $is_local_update = 0;
        $code = ApplaneConstentInterface::SUCCESS_CODE;
        $message = ApplaneConstentInterface::SUCCESS;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        //log for what is request.
        $this->writePaypalLogs('Request data: ' . $this->toJson($object_info));
        $transaction_type_filter = array(ApplaneConstentInterface::SUCCESS, ApplaneConstentInterface::CANCELED);
        if (!in_array($type, $transaction_type_filter)) { //check for array of response of transaction from paypal
            $this->writePaypalLogs('Exiting with message: TRANSACTION_TYPE_RESPONSE_WRONG type=>' . $type);
            $data = array('code' => 1059, 'message' => 'TRANSACTION_TYPE_RESPONSE_WRONG', 'data' => array());
            $this->returnResponse($data);
        }
        //get transaaction record from local database
        $payment_transaction_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("shopId" => $shop_id, "itemId" => $transaction_id));
        if (!$payment_transaction_record) { //check for transaction is exists.
            $this->writePaypalLogs('Exiting with message: TRANSACTION_RECORD_DOES_NOT_EXISTS');
            $data = array('code' => 1060, 'message' => 'TRANSACTION_RECORD_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
        }
        $transaction_owner_id = $payment_transaction_record->getCitizenId();
        if ($transaction_owner_id != $user_id) { //chek if other user is trying.
            $this->writePaypalLogs('Exiting with message: TRANSACTION_RECORD_DOES_NOT_BELONGS_TO_YOU');
            $data = array('code' => 1061, 'message' => 'TRANSACTION_RECORD_DOES_NOT_BELONGS_TO_YOU', 'data' => array());
            $this->returnResponse($data);
        }
        $transaction_current_status = strtoupper($payment_transaction_record->getPaymentStatus());
        $transaction_pay_key = $payment_transaction_record->getTransactionReference();
        $card_value = $payment_transaction_record->getTransactionValue();

        if ($transaction_current_status == ApplaneConstentInterface::CONFIRMED) { //if transaction already confirmed
            $this->writePaypalLogs('Exiting with message: TRANSACTION_RECORD_ALREADY_CONFIRMED');
            $data = array('code' => 1062, 'message' => 'TRANSACTION_RECORD_ALREADY_CONFIRMED', 'data' => array());
            $this->returnResponse($data);
        } else if ($transaction_current_status == ApplaneConstentInterface::CANCELED) { //if transaction already canceled
            $this->writePaypalLogs('Exiting with message: TRANSACTION_RECORD_ALREADY_CANCELED');
            $data = array('code' => 1063, 'message' => 'TRANSACTION_RECORD_ALREADY_CANCELED', 'data' => array());
            $this->returnResponse($data);
        }
        //get shop object.
        $shop_object_service = $this->container->get('user_object.service');
        $shop_info = $shop_object_service->getStoreObjectService($shop_id);
        //get shop owner id
        $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'role' => 15));
        if (!$store_obj) { //check shop is exists.
            $this->writePaypalLogs('Exiting with message: SHOP_DOES_NOT_EXISTS');
            $data = array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
        }
        $shop_owner_id = $store_obj->getuserId();
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        //find paypal account for shop
        $shop_paypal_info = $em->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => 'VERIFIED', 'isDefault' => 1));
        
        if ($type == ApplaneConstentInterface::SUCCESS) {

            //check on paypal if transaction is completed means user paid for this.
            $result = $this->checkTransactionStatus($transaction_pay_key);
            if (isset($result->status)) {
                switch ($this->convertString($result->status)) {
                    case ApplaneConstentInterface::COMPLETED:
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::CONFIRMED;
                        $applane_status = ApplaneConstentInterface::APPROVED;
                        $applane_service->updateShoppingCardStatus($transaction_id, $applane_status, $handler); //update on transaction system.
                        $this->writePaypalLogs('Calliing the CI return code from the postResponsebuycardsAction web-service:shop_id'.$shop_id);
                        $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                        //$this->sendEmailPushNotification($shop_id, $user_id, true, true, $card_value, $shop_info, $shop_owner_id, ApplaneConstentInterface::BUY); //for user
                        $paypal_transaction_service->sendMailNotificationBuyUPTO100($shop_id,$transaction_id,$user_id);
                        //$this->sendEmailPushNotification($shop_id, $shop_owner_id, true, true, $card_value, $shop_info, $user_id, ApplaneConstentInterface::SALE); //for shop owner
                        break;
                    case ApplaneConstentInterface::IN_COMPLETE:
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::IN_COMPLETE;
                        $applane_status = ApplaneConstentInterface::APPROVED;
                        $applane_service->updateShoppingCardStatus($transaction_id, $applane_status, $handler); //update on transaction system.
                        $this->writePaypalLogs('Calliing the CI return code from the postResponsebuycardsAction web-service:shop_id'.$shop_id);
                        $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                        //$this->sendEmailPushNotification($shop_id, $user_id, true, true, $card_value, $shop_info, $shop_owner_id, ApplaneConstentInterface::BUY); //for user
                        $paypal_transaction_service->sendMailNotificationBuyUPTO100($shop_id,$transaction_id,$user_id);
                        //$this->sendEmailPushNotification($shop_id, $shop_owner_id, true, true, $card_value, $shop_info, $user_id, ApplaneConstentInterface::SALE); //for shop owner
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
                        $applane_service->updateShoppingCardStatus($transaction_id, $applane_status, $handler); //update on transaction system.
                        break;
                    default:
                        $code = ApplaneConstentInterface::TRANSACTION_ERROR_CODE;
                        $message = ApplaneConstentInterface::TRANSACTION_ERROR_MESSAGE;
                }
            }
        } else if ($type == ApplaneConstentInterface::CANCELED) {
            $code = ApplaneConstentInterface::TRANSACTION_CANCELED_ERROR_CODE;
            $message = ApplaneConstentInterface::TRANSACTION_CANCELED_ERROR_MESSAGE;
            $is_local_update = 1;
            $transaction_status = ApplaneConstentInterface::CANCELED;
            $applane_status = ApplaneConstentInterface::REJECTED;
            $applane_service->updateShoppingCardStatus($transaction_id, $applane_status, $handler); //update on transaction system.
        }

        //update in our local database
        if ($is_local_update) {
            $payment_transaction_record->setPaymentStatus($transaction_status); //update transaction status
            $em->persist($payment_transaction_record);
            $em->flush();
            $this->writePaypalLogs('Shopping card purchase response service local system status update with: ' . $transaction_status);
        }
        $data = array('code' => $code, 'message' => $message, 'data' => array());
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [postResponsebuycardsAction] with response:' . $this->toJson($data));
        $this->returnResponse($data);
    }

    /**
     * save the data into payment transaction log
     * @param int $user_id
     * @param int $shop_id
     * @param string $pay_key
     * @param string $transaction_id
     * @param string $paypal_id
     * @param double $amount
     * @param double $vat_amount
     * @return boolean 
     */
    public function savePaymentTransactionRecord($user_id, $shop_id, $pay_key, $transaction_id, $paypal_id, $amount, $vat_amount, $transaction_inner_id,$ci_used = 0) {
        $pay_tx_data['item_id'] = $transaction_id;
        $pay_tx_data['reason'] = 'C';
        $pay_tx_data['citizen_id'] = $user_id;
        $pay_tx_data['shop_id'] = $shop_id;
        $pay_tx_data['payment_via'] = 'PAYPAL';
        $pay_tx_data['payment_status'] = 'PENDING';
        $pay_tx_data['error_code'] = '';
        $pay_tx_data['error_description'] = '';
        $pay_tx_data['transaction_reference'] = $pay_key;
        $pay_tx_data['transaction_value'] = $amount;
        $pay_tx_data['vat_amount'] = $vat_amount;
        $pay_tx_data['contract_id'] = '';
        $pay_tx_data['paypal_id'] = $paypal_id;
        $pay_tx_data['transaction_id'] = $transaction_inner_id;
        $pay_tx_data['ci_used'] = $ci_used;
        $payment_transaction = $this->container->get('paypal_integration.payment_transaction');
        $payment_transaction->addPaymentTransaction($pay_tx_data);
        return true;
    }

    /**
     * check the detail if transaction is completed on paypal.
     * @param string $transaction_pay_key
     */
    public function checkTransactionStatus($transaction_pay_key) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [checkTransactionStatus]');
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
            $paypal_end_point = $this->container->getParameter('paypal_detail_end_point_sandbox');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
        } else {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
            $paypal_end_point = $this->container->getParameter('paypal_detail_end_point_live');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
        }
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $payload = "payKey=" . $transaction_pay_key . "&requestEnvelope.errorLanguage=en_US";
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true
        );
        $this->writePaypalLogs('Paypal transaction status check Request: URL=>[' . $paypal_end_point . '] Payload string=>[' . $payload . ']');
        try {
            $curl = curl_init($paypal_end_point);
            if (!$curl) {
                throw new \Exception('Could not initialize curl');
            }
            if (!curl_setopt_array($curl, $options)) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            $result = curl_exec($curl);
            if (!$result) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            curl_close($curl);
            $this->writePaypalLogs('Paypal transaction status check response: ' . $result);  //write the log for error 
            return json_decode($result);
        } catch (\Exception $e) {
            $this->writePaypalLogs('Paypal transaction status check response: ' . $e->getMessage());  //write the log for error 
        }
    }

    /**
     * calculate the amount to be paid by user for purchasing the 100% shopping card
     * @param array $transaction_system_data
     * @return array $result
     */
    public function calculateShoppingCardAmount($transaction_system_data, $shop_info) {
        //get doctrine manager object
        $em = $this->getDoctrine()->getManager();
        $cash_amount  = $transaction_system_data['cash_amount']; //payable amount
        $discount     = $transaction_system_data['discount'];
        $total_amount = $transaction_system_data['total_amount'];
        $ci_value = $transaction_system_data['new_ci_used'];
//        $amount_pecentage = self::CARD_PERCENTAGE;
        //get card percentage to be used.
//        $shop_cat_id = $shop_info->getSaleCatid();
//        $card_percentage = $shop_info->getCardPercentage();
//        if ($card_percentage != '' || $card_percentage != 0 || $card_percentage != null) { //shop card percentage
//            $amount_pecentage = $card_percentage;
//        } else {
//            if ($shop_cat_id != null) { //if shop category is not null
//                $business_category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')->find($shop_cat_id);
//                if ($business_category) { //category is exist.
//                    $business_category_pecentage = $business_category->getCardPercentage();
//                    if ($business_category_pecentage != '' || $business_category_pecentage != 0 || $business_category_pecentage != null) { //if shop category card percentage is not null.
//                        $amount_pecentage = $business_category_pecentage;
//                    }
//                }
//            }
//        }
        //code end for getting the code percentage.
//        $payable_amount = $total_amount - $discount;
//        $sixth_continent_amount = ($payable_amount * $amount_pecentage) / 100;
//
//        $sixth_continent_amount_vat = ($sixth_continent_amount * $vat) / 100;
//        $sixth_continent_amount_with_vat = $sixth_continent_amount + $sixth_continent_amount_vat;
        $sixth_continent_amount_with_vat = $transaction_system_data['total_vat_checkout_value'];
        $vat = $transaction_system_data['vat_checkout_value'];
        $shop_amount = ($cash_amount - $sixth_continent_amount_with_vat);
        $this->writePaypalLogs('Paypal amount is shopAmount: '.$shop_amount. ' sixthcontinetAmount: '.$sixth_continent_amount_with_vat);
        $result = array('shop_amount' => $shop_amount, 'sixth_continent_amount' => $sixth_continent_amount_with_vat, 'transaction_id' => $transaction_system_data['transaction_id'], 'total_amount' => $total_amount, 'vat_amount' => $vat,'ci_used' => $ci_value);
        return $result;
    }

    /**
     * send email for notification on shopping card purchase/sale
     * @param int $shop_id
     * @param int $receiver_id
     * @param string $isWeb
     * @param string $isPush
     * @param int $card_value
     * @param object $isPush
     * @return boolean
     */
    public function sendEmailPushNotification($shop_id, $receiver_id, $isWeb = false, $isPush = false, $card_value, $shop_info, $sender_id, $transaction_type) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [sendEmailPushNotification]');
        //$link = null;
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_profile_url = $this->container->getParameter('shop_profile_url');
        $citizen_wallet_url = $this->container->getParameter('citizen_wallet');
        //send email service
        $receiver = $postService->getUserData($receiver_id, true);
        $recieverByLanguage = $postService->getUsersByLanguage($receiver);
        $shop_name = ($shop_info['name'] != '' ? $shop_info['name'] : $shop_info['businessName']);
        $emailResponse = '';
        foreach ($recieverByLanguage as $lng => $recievers) {
            $locale = $lng === 0 ? $this->container->getParameter('locale') : $lng;
            $lang_array = $this->container->getParameter($locale);
            if ($transaction_type == 'buy') {
                $app_type = 'CITIZEN';
                // push and web
                $msgtype = 'BUYS_SHOPPING_CARD';
                $msg = 'CARD_UPTO_100';
                $mail_sub = $lang_array['BUYS_SHOPPING_CARD_UPTO_100_SUBJECT'];
                $mail_body = $lang_array['BUYS_SHOPPING_CARD_UPTO_100_BODY'];
                $mail_text = sprintf($lang_array['BUYS_SHOPPING_CARD_UPTO_100_TEXT'], '%', $shop_name, $card_value);
                $link = "<a href='$angular_app_hostname$shop_profile_url/$shop_id'>" . $lang_array['CLICK_HERE'] . "</a>"; //shop profile url
                $link1 = "<a href='$angular_app_hostname$citizen_wallet_url'>" . $lang_array['CLICK_HERE'] . "</a>"; //wallet url
                $mail_link_text = sprintf($lang_array['BUYS_SHOPPING_CARD_UPTO_100_LINK_SHOP'], $link, $shop_name);
                $mail_link_text1 = sprintf($lang_array['BUYS_SHOPPING_CARD_UPTO_100_LINK_WALLET'], $link1);
                $bodyData = $mail_text . '<br><br>' . $mail_link_text . '<br><br>' . $mail_link_text1;
            } else if ($transaction_type == 'sale') {
                $app_type = 'SHOP';
                // push and web
                $msgtype = 'SELLS_SHOPPING_CARD';
                $msg = 'CARD_UPTO_100';
                $mail_sub = $lang_array['SELLS_SHOPPING_CARD_UPTO_100_SUBJECT'];
                $mail_body = $lang_array['SELLS_SHOPPING_CARD_UPTO_100_BODY'];
                $mail_text = sprintf($lang_array['SELLS_SHOPPING_CARD_UPTO_100_TEXT'], '%', $shop_name, $card_value);
                $link = "<a href='$angular_app_hostname'>" . $lang_array['CLICK_HERE'] . "</a>";
                $mail_link_text = sprintf($lang_array['SELLS_SHOPPING_CARD_UPTO_100_LINK_REPORT'], $link, $shop_name);
                $mail_link_text1 = sprintf($lang_array['SELLS_SHOPPING_CARD_UPTO_100_LINK_ACCEPT'], $link);
                $bodyData = $mail_text . '<br><br>' . $mail_link_text . '<br><br>' . $mail_link_text1;
            }

            $thumb_path = "";
            $emailResponse = $email_template_service->sendMail($recievers, $bodyData, $mail_body, $mail_sub, $thumb_path, 'UPTO100CARD');
        }
        $extraParams = array('store_id' => $shop_id);
        $itemId = $shop_id;
        $info = array('store_id' => $shop_id, 'store_info' => $shop_info, 'card_value' => $card_value, 'to_id' => $receiver_id);
        $replace_text = array('%', $shop_name, $card_value);
        $postService->sendUserNotifications($sender_id, $receiver_id, $msgtype, $msg, $itemId, $isWeb, $isPush, $replace_text, $app_type, $extraParams, 'T', $info);
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [sendEmailPushNotification]');
        return true;
    }

    /**
     * catch the ipn response for a transaction
     */
    public function ipncallbackresponseAction() {
        sleep(120);
        $this->writeLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [ipncallbackresponseAction]', '');
        $handler = $this->container->get('monolog.logger.ipnnotification');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //get object of the transaction check service
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        // Instead, read raw POST data from the input stream. 
        //https://developer.paypal.com/docs/classic/ipn/ht_ipn/
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        $paypal_status = $pay_key = '';
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
        // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) { //prepare the query string for paypal ipn notiification validation.
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
            if ($this->convertString($key) == $this->convertString(self::STATUS)) { //get transaction status
                $paypal_status = $value;
            } 
            if ($this->convertString($key) == $this->convertString(self::PAY_KEY)) { //get pay key
                $pay_key = $value;
            }
        }
        
        //write logs for post data from ipn paypal
        $post_data = urldecode($req);
        $this->writeLogs('Ipn hit on our system', 'POST data coming from paypal: ' . $post_data);

        $response = $this->verifyrequest($req); //verify it on ipn paypal again it a valid request from  ipn.
        $response = $this->convertString($response);
        $this->writeLogs('Ipn hit on our system with status: '. $paypal_status. ' and pay-key: '.$pay_key, '');
        $paypal_response = array();
        if (strcmp($response, "VERIFIED") == 0) {
            // The IPN is verified, process it
        } else if (strcmp($response, "INVALID") == 0) {
            // IPN invalid, log for manual investigation
            $this->writeLogs('Ipn not verified with status: ' . $response, 'post data: ' . $post_data);
            exit('INVALID respons from IPN');
        } else {
            $this->writeLogs('Ipn not verified', 'post data: ' . $post_data);
            exit('Unknown response from IPN');
        }

        $status = $this->convertString($paypal_status); //paypal ipn status convert to string
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        //get transaaction record from database
        $payment_transaction_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("transactionReference" => $pay_key));
        if (!$payment_transaction_record) {
            $this->writeLogs('pay key ' . $pay_key . ' record requested by ipn', 'pay key' . $pay_key . ' does not exists in our system.');
            exit('payment record does not exists.');
        }
        $is_local_update = 0;
        $transaction_id = $payment_transaction_record->getItemId();
        $transaction_current_status = $this->convertString($payment_transaction_record->getPaymentStatus());
        $user_id = $payment_transaction_record->getCitizenId();
        $card_value = $payment_transaction_record->getTransactionValue();
        $shop_id = $payment_transaction_record->getShopId();
        $transaction_reason = $payment_transaction_record->getReason();
        

        //get shop object.
        $shop_object_service = $this->container->get('user_object.service');
        $shop_info = $shop_object_service->getStoreObjectService($shop_id);

        //get shop owner id
        $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'role' => 15));
        if (!$store_obj) { //check shop is exists.
            $this->writeLogs('Exiting with message: SHOP_DOES_NOT_EXISTS', '');
            exit('SHOP_DOES_NOT_EXISTS');
        }
        $shop_owner_id = $store_obj->getuserId();

        //we are updating the applane and our local database if our database status is pending.
        if ($transaction_current_status == ApplaneConstentInterface::PENDING && Utility::matchString($transaction_reason, ApplaneConstentInterface::OFFER_REASON)) {
            $result_constant = $this->prepareconstant();
            if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::COMPLETED) || ($status == ApplaneConstentInterface::IN_COMPLETE))) {
                $is_local_update = 1;
                $applane_service->updateShoppingCardStatus($transaction_id, $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS], $handler); //update on transaction system.
                //$this->sendEmailPushNotification($shop_id, $user_id, true, true, $card_value, $shop_info, $shop_owner_id, ApplaneConstentInterface::BUY); //for user
                $this->writePaypalLogs('Calliing the CI return code from the ipncallbackresponseAction web-service:shop_id'.$shop_id);
                $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                $paypal_transaction_service->sendMailNotificationBuyUPTO100($shop_id,$transaction_id,$user_id);
                //$this->sendEmailPushNotification($shop_id, $shop_owner_id, true, true, $card_value, $shop_info, $user_id, ApplaneConstentInterface::SALE); //for shop owner
            } else if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED))) {
                $is_local_update = 1;
                $applane_service->updateShoppingCardStatus($transaction_id, $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS], $handler); //update on transaction system.
            }
        } else if ($transaction_current_status == ApplaneConstentInterface::PENDING && Utility::matchString($transaction_reason, ApplaneConstentInterface::INSTANT_CI_REASON)) {
            $result_constant = $this->prepareconstant();
            if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::COMPLETED) || ($status == ApplaneConstentInterface::IN_COMPLETE) || ($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED))) {
               $this->writePaypalLogs('Calliing the CI Confirm code from the ipncallbackresponseAction web-service:shop_id'.$shop_id.": transaction_id:".$transaction_id.": reason :".$transaction_reason.":transaction_refferanace:".$pay_key."new_status:".$result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]);
               $is_local_update = 1;  
            }
            
        }
        //update in our local database
        if ($is_local_update) {
            $payment_transaction_record->setPaymentStatus($result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]); //update transaction status
            $em->persist($payment_transaction_record);
            $em->flush();
            
            //write log for status update.
            $this->writeLogs('pay key ' . $pay_key . ' record requested by ipn', 'pay key ' . $pay_key . ' is updated with applane status '
                    . $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS] . ' and our system status ' . $result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]);
        }
        exit('DONE');
    }

    /**
     * prepare the constatnt array for ipn notification
     * @return arrray
     */
    public function prepareconstant() {
        $constant_array = array();
        $constant_array[ApplaneConstentInterface::COMPLETED] = array(ApplaneConstentInterface::APPLANE_STATUS => ApplaneConstentInterface::APPROVED,
            ApplaneConstentInterface::TRANSACTION_STATUS => ApplaneConstentInterface::CONFIRMED);

        $constant_array[ApplaneConstentInterface::IN_COMPLETE] = array(ApplaneConstentInterface::APPLANE_STATUS => ApplaneConstentInterface::APPROVED,
            ApplaneConstentInterface::TRANSACTION_STATUS => ApplaneConstentInterface::IN_COMPLETE);

        $constant_array[ApplaneConstentInterface::ERROR] = array(ApplaneConstentInterface::APPLANE_STATUS => ApplaneConstentInterface::REJECTED,
            ApplaneConstentInterface::TRANSACTION_STATUS => ApplaneConstentInterface::CANCELED);

        $constant_array[ApplaneConstentInterface::CANCELED] = array(ApplaneConstentInterface::APPLANE_STATUS => ApplaneConstentInterface::REJECTED,
            ApplaneConstentInterface::TRANSACTION_STATUS => ApplaneConstentInterface::CANCELED);
        return $constant_array;
    }

    /**
     * convert the string into uppercase
     * @param string $string
     * @return string $final_string
     */
    public function convertString($string) {
        $final_string = strtoupper(trim($string));
        return $final_string;
    }

    /**
     * verify the status from paypal.
     * @param string $req
     * @return string $response
     */
    public function verifyrequest($req) {
        $this->writeLogs('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [verifyrequest]', '');
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_notify_end_point = $this->container->getParameter('paypal_notify_verify_end_point_sandbox');
        } else {
            $paypal_notify_end_point = $this->container->getParameter('paypal_notify_verify_end_point_live');
        }
        $this->writeLogs('Request to IPN for check transaction verification URL=> ' . $paypal_notify_end_point . ' AND Query string=> ' . $req, '');
        $res = '';
        $ch = curl_init($paypal_notify_end_point);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        // In wamp-like environments that do not come bundled with root authority certificates,
        // please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set 
        // the directory path of the certificate as shown below:
        // curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        //PayPal's message has an HTTP status code of 200 and a body that contains either VERIFIED or INVALID.
        if (!($res = curl_exec($ch))) {
            $this->writeLogs('Curl execution error', '');
            curl_close($ch);
            exit('curl execution error');
        }
        curl_close($ch);
        $this->writeLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [verifyrequest] with response=>' . $res, '');
        return $res;
    }

    /**
     * write logs for IPN notification
     * @param string $request
     * @param string $response
     * @return boolean
     */
    public function writeLogs($request, $response) {
        $handler = $this->container->get('monolog.logger.ipnnotification');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        try {
            $applane_service->writeAllLogs($handler, $request, $response);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * write logs paypal shopping
     * @param string $data
     * @return boolean
     */
    public function writePaypalLogs($data) {
        $handler = $this->container->get('monolog.logger.paypal_shopping_logs');
        try {
            $handler->info($data);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * convert to json
     * @param array/object $data
     */
    private function toJson($data) {
        return json_encode($data);
    }

     /**
     * convert the string into lowercase
     * @param string $string
     * @return string $final_string
     */
    public function convertStringToLower($string) {
        $final_string = strtolower(trim($string));
        return $final_string;
    }   
}
