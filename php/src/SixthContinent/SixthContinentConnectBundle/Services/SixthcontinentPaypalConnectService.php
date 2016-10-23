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
class SixthcontinentPaypalConnectService {

    protected $em;
    protected $dm;
    protected $container;

    CONST QUESTION_MARK = '?';
    CONSt AM_PERSAND = '&';
    CONST CANCELED = 'CANCELED';
    CONST PENDING = 'PENDING';
    CONST FEES_PAYER = 'PRIMARYRECEIVER';
    CONST CI_FESS_PAYER = 'EACHRECEIVER';
    
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
     * register the transaction on paypal for connect app.
     * @param float $cash_amount
     * @param int $user_id
     */
    public function registerTransactionOnPaypal($amount, $user_id, $connect_app_paypal_email, $return_url, $cancel_url, $currency, $paypal_sixthcontinent_email, $secondry_reciever_amount, $app_id) {
        $connect_app_service = $this->container->get('sixth_continent_connect.connect_app');
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] function [registerTransactionOnPaypal]');
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
        $curreny_code = $currency;
        $amount = $connect_app_service->roundAmount($amount);
        $secondry_user_email = $paypal_sixthcontinent_email;
        $secondry_reciever_amount = $connect_app_service->roundAmount($secondry_reciever_amount);
        
        //getting fees payer option
        $fess_payer_param_data = $this->getPaypalFeesPayer($app_id);
        if ($fess_payer_param_data['fees_payer'] == '') {
            $fess_payer_param = self::FEES_PAYER;
        } else {
            $fess_payer_param = $fess_payer_param_data['fees_payer'];
        }

        $fees_payer = '&feesPayer='.$fess_payer_param;
        //$ipn_notification_url = urlencode($this->container->getParameter('symfony_base_url') . ApplaneConstentInterface::CONNECT_IPN_NOTIFICATION_URL); //ipn notification url
        //$ipn_notification_url = 'http://45.33.45.34/sixthcontinent_symfony/php/web/webapi/ipncallbackresponse'; //for local because paypal does not accept localhost in ipn url
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $payload = "actionType=PAY&cancelUrl=$cancel_url&clientDetails.applicationId=APP-80W284485P519543"
                . "&clientDetails.ipAddress=127.0.0.1&currencyCode=$curreny_code" .
                "&receiverList.receiver(0).amount=$amount&receiverList.receiver(0).email=$connect_app_paypal_email" .
                "&receiverList.receiver(0).primary=true&receiverList.receiver(1).amount=$secondry_reciever_amount" .
                "&receiverList.receiver(1).email=$secondry_user_email" .
                "&receiverList.receiver(1).primary=false" .
                "&requestEnvelope.errorLanguage=en_US" .
                "&returnUrl=$return_url".$fees_payer;
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

            $this->__createLog('Paypal Connect request: ' . $payload);
            $this->__createLog('Paypal Connect response: ' . $result);
            $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] function [registerTransactionOnPaypal]');
            return json_decode($result);
        } catch (\Exception $e) {
            $this->__createLog('Paypal Connect request: ' . $payload);
            $this->__createLog('Paypal Connect response: ' . $e->getMessage());
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] function [registerTransactionOnPaypal]');
    }

    /**
     * get the transaction response code
     * @param string $type
     * @param string $pay_key
     */
    public function getTransactionResponseCode($type, $pay_key) {
        $is_local_update = 0;
        $transaction_status = '';
        $code = 1127;
        $transaction_status = ApplaneConstentInterface::CANCELED;
        $paypal_transaction_ids = array();
        if ($type == ApplaneConstentInterface::SUCCESS) {
            //check transaction paypal
            $result = $this->getDetailPaypalTransaction($pay_key);
            if (isset($result->status)) {
                switch (Utility::getUpperCaseString($result->status)) {
                    case ApplaneConstentInterface::COMPLETED:
                        $is_local_update = 1;
                        $code = 101;
                        $transaction_status = ApplaneConstentInterface::COMPLETED;
                        $paypal_transaction_ids = $this->getPaypalTransactionIds($result);
                        break;
                    case ApplaneConstentInterface::IN_COMPLETE:
                        $code = 101;
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::COMPLETED;
                        break;
                    case ApplaneConstentInterface::PENDING:
                        $code = 1126;
                        break;
                    case ApplaneConstentInterface::PROCESSING:
                        $code = 1126;
                        break;
                    case ApplaneConstentInterface::ERROR: //in case it would be assumed canceled.
                        $code = 1127;
                        $is_local_update = 1;
                        $transaction_status = ApplaneConstentInterface::CANCELED;
                        break;
                    default:
                        $code = 1127;
                }
            }
        } else if ($type == ApplaneConstentInterface::CANCELED) { //transaction canceled
            $code = 1127;
            $is_local_update = 1;
            $transaction_status = ApplaneConstentInterface::CANCELED;
        } else { //error case
            $code = 1127;
            $transaction_status = ApplaneConstentInterface::CANCELED;
            $is_local_update = 1;
        }
        return array('code' => $code, 'is_local_update' => $is_local_update, 'transaction_status' => $transaction_status, 'paypal_transaction_ids' => $paypal_transaction_ids);
    }

    /**
     * get the transaction detail on paypal
     * @param type $pay_key
     * @return array
     */
    public function getDetailPaypalTransaction($pay_key) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] function [getDetailPaypalTransaction]');
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
        $payload = "payKey=" . $pay_key . "&requestEnvelope.errorLanguage=en_US";
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true
        );
        $this->__createLog('Paypal transaction status check Request: URL=>[' . $paypal_end_point . '] Payload string=>[' . $payload . ']');
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
            $this->__createLog('Paypal transaction status check response: ' . $result);  //write the log for error 
            return json_decode($result);
        } catch (\Exception $e) {
            $this->__createLog('Paypal transaction status check response: ' . $e->getMessage());  //write the log for error 
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] function [getDetailPaypalTransaction]');
        return $result;
    }

    /**
     * get the paypal transaction ids in case of completed transaction only
     * @param object array $result
     */
    public function getPaypalTransactionIds($result) {
        $transaction_ids = array();
        if (isset($result->paymentInfoList)) {
            $payment_info_list = $result->paymentInfoList;
            $payment_infos = $payment_info_list->paymentInfo;
            foreach ($payment_infos as $payment_info) {
                if (isset($payment_info->senderTransactionId)) {
                    $transaction_ids[] = array('sender' => $payment_info->senderTransactionId, 'receiver' => $payment_info->transactionId);
                }
            }
        }
        return $transaction_ids;
    }

    /**
     * Return the ci used in transaction
     * @param string $reciver_email
     * @param float $ci_used
     * @params string $currency
     * @param string $app_id
     */
    public function ciReturnConnectAmount($reciver_email, $ci_used, $currency, $app_id) {
        $connect_sixthcontinent_service = $this->container->get('sixth_continent_connect.connect_app');
        $this->__createLog('Enter in [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] and function [ciReturnConnectAmount] with reciver email:' . $reciver_email . ' amount: ' . $ci_used);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        $cancel_url = $this->container->getParameter('symfony_base_url');
        $return_url = $this->container->getParameter('symfony_base_url');
        $cancel_url = $return_url = 'http://45.33.45.34/sixthcontinent_symfony/php/web/webapi/'; //@TODO: need to be comment
        if ($mode == 'sandbox') {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_sandbox');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
            $sender_email = $this->container->getParameter('paypal_sixthcontinent_email_sandbox');
        } else {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_live');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
            $sender_email = $this->container->getParameter('paypal_sixthcontinent_email_live');
        }
        $primary_reciever_paypal_email = $reciver_email;
        $primary_reciever_amount = $connect_sixthcontinent_service->roundAmount($ci_used);
        //$curreny_code = $this->container->getParameter('paypal_currency');
        $curreny_code = $currency;
        
        //getting fees payer option
        $fees_payer_param_data = $this->getPaypalFeesPayer($app_id);
        if ($fees_payer_param_data['ci_fees_payer'] == '') { ///default in case not defined in database
            $fees_payer_param = self::CI_FESS_PAYER;
        } else {
            $fees_payer_param = $fees_payer_param_data['ci_fees_payer'];
        }
        $fees_payer = '&feesPayer='.$fees_payer_param;
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $payload = "actionType=PAY&cancelUrl=$cancel_url&clientDetails.applicationId=$paypal_acct_appid"
                . "&clientDetails.ipAddress=127.0.0.1&currencyCode=$curreny_code" .
                "&receiverList.receiver(0).amount=$primary_reciever_amount&receiverList.receiver(0).email=$primary_reciever_paypal_email" .
                "&requestEnvelope.errorLanguage=en_US" .
                "&returnUrl=$return_url" . "&senderEmail=" . $sender_email.$fees_payer;
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
            $this->__createLog('Paypal CI return request: ' . $payload);
            $this->__createLog('paypal CI return response: ' . $result);
            $this->__createLog('Exiting from [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] and function [ciReturnConnectAmount]');
            return json_decode($result);
        } catch (\Exception $e) {
            $this->__createLog('Paypal CI return request: ' . $payload);
            $this->__createLog('Paypal CI return request: ' . $e->getMessage());
            return $result;
        }
        $this->__createLog('Exiting from [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService] and function [ciReturnConnectAmount]');
    }

    /**
     * getting the fees payer of the application from business account
     * @param string $app_id
     * @return string $app_id
     */
    public function getPaypalFeesPayer($app_id) {
        $em = $this->em;
        $app_business_data = $em->getRepository('SixthContinentConnectBundle:ApplicationBusinessAccount')
                                ->findOneBy(array('applicationId' => $app_id));
        if (!$app_business_data) {
            return array('fees_payer'=> '', 'ci_fees_payer'=>'');
        }
        $fess_payer    = $app_business_data->getConnectFeesPayer();
        $ci_fess_payer = $app_business_data->getConnectCiFeesPayer();
        $result = array('fees_payer' => $fess_payer, 'ci_fees_payer' => $ci_fess_payer);
        return $result;
    }
}
