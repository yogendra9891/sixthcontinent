<?php

namespace Paypal\PaypalIntegrationBundle\Services;

use Doctrine\ORM\EntityManager;
use Paypal\PaypalIntegrationBundle\Entity\PaymentTransaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\UtilityBundle\Utils\Utility;

// verify the paypal transaction status
class EcommerceProductService {

    protected $em;
    protected $dm;
    protected $container;

    CONST PAY_KEY = 'pay_key';
    CONST STATUS = 'status';
    CONST INSTANT_CI_PAYBACK = 'INSTANT_CI_PAYBACK';
    CONST BUY_ECOMMERCE_PRODUCT_SUCCESS = 'BUY_ECOMMERCE_PRODUCT_SUCCESS';
    CONST BUY_ECOMMERCE_PRODUCT = 'BUY_ECOMMERCE_PRODUCT';
    CONST CITIZEN_APP_TYPE = 'CITIZEN';
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
     * Create log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.ecommerce_logs');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
    
    /**
     * calculate the amount to be paid by user for purchasing the 100% shopping card
     * @param array $transaction_system_data
     * @return array $result
     */
    public function calculateShoppingCardAmount($transaction_system_data, $shop_info) {
        //get doctrine manager object
        $em = $this->em;
        $cash_amount  = $transaction_system_data['cash_amount']; //payable amount
        $discount     = $transaction_system_data['discount'];
        $total_amount = $transaction_system_data['total_amount'];
        $ci_value = $transaction_system_data['bucket_value'];
        $sixth_continent_amount_with_vat = $transaction_system_data['total_vat_checkout_value'];
        $vat = $transaction_system_data['vat_checkout_value'];
        $shop_amount = ($cash_amount - $sixth_continent_amount_with_vat);
        $this->__createLog('Paypal amount is shopAmount: '.$shop_amount. ' sixthcontinetAmount: '.$sixth_continent_amount_with_vat);
        $result = array('shop_amount' => $shop_amount, 'sixth_continent_amount' => $sixth_continent_amount_with_vat, 'transaction_id' => $transaction_system_data['transaction_id'], 'total_amount' => $total_amount, 'vat_amount' => $vat,'ci_used' => $ci_value);
        return $result;
    }
    
     /**
     * paypal response and update the status
     * @param string $transaction_data
     * @param int $shop_id
     * @param int $citizen_id
     */
    public function getPaypalResponse($transaction_data, $shop_id, $citizen_id, $cancel_url, $return_url) {
        $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
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
        $fee_payer = $paypal_transaction_service->checkWhoPayTransactionFee($shop_id);
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
                #"&payKeyDuration=PT0H5M0S".
                "&returnUrl=$return_url".$fee_payer;
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
            $this->__createLog('Paypal chain request: ' . $payload);
            $this->__createLog('paypal chain response: ' . $result);
            $this->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
            return json_decode($result);
        } catch (\Exception $e) {
           // $applane_service->writeTransactionLogs('Paypal chain request: ' . $payload, 'paypal chain response: ' . $e->getMessage());  //write the log for error 
            $this->__createLog('Paypal chain request: ' . $payload);
            $this->__createLog('paypal chain response: ' . $e->getMessage());
        }
        $this->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Controller\CardPurchaseController] function [getPaypalResponse]');
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
    public function savePaymentTransactionRecord($user_id, $shop_id, $pay_key, $transaction_id, $paypal_id, $amount, $vat_amount, $transaction_inner_id,$ci_used = 0, $reason, $product_name, $order_id) {
        $pay_tx_data['item_id'] = $transaction_id;
        $pay_tx_data['reason'] = $reason;
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
        $pay_tx_data['order_id'] = $order_id;
        $pay_tx_data['product_name'] = $product_name;
        $payment_transaction = $this->container->get('paypal_integration.payment_transaction');
        $payment_transaction->addPaymentTransaction($pay_tx_data);
        return true;
    }
    
    /**
     * check the detail if transaction is completed on paypal.
     * @param string $transaction_pay_key
     */
    public function checkTransactionStatus($transaction_pay_key) {
        try {
            $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus]');
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
            $this->__createLog('In class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Paypal transaction status check Request: URL=>[' . $paypal_end_point . '] Payload string=>[' . $payload . ']');
            try {
                $curl = curl_init($paypal_end_point);
                if (!$curl) {
                    throw new \Exception('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Could not initialize curl');
                }
                if (!curl_setopt_array($curl, $options)) {
                    throw new \Exception('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Curl error:' . curl_error($curl));
                }
                $result = curl_exec($curl);
                if (!$result) {
                    throw new \Exception('Curl error:' . curl_error($curl));
                }
                curl_close($curl);
                $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Paypal transaction status check response: ' . $result);  //write the log for error 
                return json_decode($result);
            } catch (\Exception $e) {
                $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Paypal transaction status check response: ' . $e->getMessage());  //write the log for error 
                return (object)array();
            }
        } catch (\Exception $ex) {
            $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [checkTransactionStatus] Exception occures ' . $ex->getMessage());  //write the log for error 
            return (object)array();
        }
    }
    
    /**
     * Send Push and Email Notification
     * @param int $shop_owner_id
     * @param int $user_id
     * @param int $shop_id
     * @param string $replaceText
     */
    public function sendPushNotifications($shop_owner_id, $user_id, $shop_id, $replaceText, $order_id)
    {
        $this->__createLog('Entering into class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [sendPushNotifications] With receiver userId:'.$user_id);
        $postService = $this->container->get('post_detail.service');
        $user_service = $this->container->get('user_object.service');
        $store_detail = $user_service->getStoreObjectService($shop_id);
        $isWeb = true;
        $isPush = true;
        $msgtype = self::BUY_ECOMMERCE_PRODUCT;
        $msg = self::BUY_ECOMMERCE_PRODUCT_SUCCESS;
        $info = array('product_name' => $replaceText, 'store_info' => $store_detail, 'order_id' => $order_id);
        $postService->sendUserNotifications($shop_owner_id, $user_id, $msgtype, $msg, $shop_id, $isWeb, $isPush, $replaceText, self::CITIZEN_APP_TYPE, array(), 'U', $info);
        $this->__createLog('Exiting from class [Paypal\PaypalIntegrationBundle\Services\EcommerceProductService] function [sendPushNotifications]');
        return true;
    }

}