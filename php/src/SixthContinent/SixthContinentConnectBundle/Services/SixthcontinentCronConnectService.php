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
class SixthcontinentCronConnectService {

    protected $em;
    protected $dm;
    protected $container;

    CONST QUESTION_MARK = '?';
    CONSt AM_PERSAND = '&';
    CONST CANCELED = 'CANCELED';
    CONST PENDING = 'PENDING';
    CONST PAY_ONCE = 'PAY_ONCE';
    CONST PAY_ONCE_CI = 'PAY_ONCE_CI';
    CONST PAYPAL = 'PAYPAL';
    CONST PAYPAL_IDS_SEPERATOR = ';';

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
     * check the transaction status on paypal and give the same status to CARERA
     */
    public function ConnectPaypalTransactionStatus() {
        $em = $this->em;
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_app_service->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [ConnectPaypalTransactionStatus]');
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        // fetch the transactions od PENDING status
        $pending_transactions = $em->getRepository('SixthContinentConnectBundle:SixthcontinentconnectPaymentTransaction')
                ->findBy(array('paymentStatus' => self::PENDING, 'reason' => array(self::PAY_ONCE, self::PAY_ONCE_CI)));

        foreach ($pending_transactions as $payment_transaction_data) {
            $is_local_update = 0;
            $paypal_ids_object = $paypal_transaction_id = $applane_status = $ci_return_paypal_transaction_id = $post_paypal_transaction_id = '' ;
            $paypal_ids_array = array();
            $paypal_reciever1_id = $paypal_status = '';
            $today = new \DateTime('now');
            $today_date = $today->format('Ymd');
            $time = $today->format('his'); //120312(12 hours, 03 minutes, 12 seconds)
            $status = $payment_transaction_data->getPaymentStatus();
            $transaction_reference_key = $payment_transaction_data->getTransactionReference();
            $reason = $payment_transaction_data->getReason();
            if (Utility::getUpperCaseString($status) == Utility::getUpperCaseString(self::PENDING)) {
                $connect_payment_transaction_id = $payment_transaction_data->getId();
                $connect_app_service->__createLog('Record from table [SixthcontinentconnectPaymentTransaction] is coming with id: ' . $connect_payment_transaction_id . ' and reason: ' . $reason);
                $connect_sixthcontinent_id = $payment_transaction_data->getSixthcontinentConnectId();
                $transaction_data = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                                        ->findOneBy(array('id' => $connect_sixthcontinent_id));
                $transaction_data_id = $transaction_data->getId();
                $connect_app_service->__createLog('Record from table [Sixthcontinentconnecttransaction] is coming with id: ' . $transaction_data_id);
                $user_id = $transaction_data->getUserId();
                $user_info = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
                $user_email = $user_info->getEmail();
                $first_name = $user_info->getFirstName();
                $last_name = $user_info->getLastName();
                //extract data from entity
                $app_id = $transaction_data->getApplicationId();
                //fetch the paypal email id from appid
                $app_paypal_email = $connect_app_service->getApplicationPaypalEmail($app_id);
                if ($app_paypal_email == '') {
                    $connect_app_service->__createLog('Paypal email id is not found for app id: ' . $app_id. ' and table [SixthcontinentconnectPaymentTransaction] is coming with id:'. $connect_payment_transaction_id);
                    continue;
                }
                $app_data = $connect_app_service->getApplicationData($app_id);
                $secret = $app_data->getApplicationSecret();
                $cash_amount = round($transaction_data->getPaybleValue(), 2);
                $url_back = $transaction_data->getUrlBack();
                $url = $transaction_data->getUrl();
                $url_post = $transaction_data->getUrlPost();
                $discount_amount = $transaction_data->getDiscount();
                $total_amount = $transaction_data->getTransactionValue();
                $amount_curreny = $connect_app_service->getConnectAmountCurrency();
                $amount = ($total_amount * $amount_curreny);
                $transaction_id = $transaction_data->getTransactionId();
                $currency = $transaction_data->getCurrency();
                $language_id = $transaction_data->getLanguageId();
                $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction_data->getUsedCi());
                $chekout_value = $secondry_user_amount = $transaction_data->getCheckoutValue();
                $status = $transaction_data->getStatus();
                $pay_key = $transaction_data->getPaypalTransactionReference();
                $ci_transaction_system_id = $transaction_data->getCiTransactionSystemId();
                $paypal_response = $paypal_connect_service->getDetailPaypalTransaction($transaction_reference_key); //check the status on paypal
                if ($reason == self::PAY_ONCE) {
                    if (isset($paypal_response->status) && ($paypal_response->status == ApplaneConstentInterface::COMPLETED)) {
                        $paypal_status       = ApplaneConstentInterface::COMPLETED;
                        $paypal_ids_array    = $paypal_connect_service->getPaypalTransactionIds($paypal_response);
                        $paypal_ids_object   = $paypal_ids_array[0];
                        $paypal_reciever1_id = isset($paypal_ids_array[0]['receiver']) ? $paypal_ids_array[0]['receiver'] : '';
                        //return the ci.
                        $ci_paypal_response = $paypal_connect_service->ciReturnConnectAmount($app_paypal_email, $ci_used, $currency, $app_id);
                        $ci_return_paypal_transaction_id = $this->updateCireturnStatus($transaction_data, $ci_paypal_response); //return id with separator OR blank
                        $post_paypal_transaction_id = $paypal_reciever1_id.$ci_return_paypal_transaction_id;
                        $applane_status = ApplaneConstentInterface::APPROVED;
                        $connect_app_service->upDateConnectApplane($ci_transaction_system_id, $applane_status);
                    }  else if ((Utility::getUpperCaseString($paypal_response->status) == ApplaneConstentInterface::CANCELED) || (Utility::getUpperCaseString($paypal_response->status) == ApplaneConstentInterface::ERROR) || (Utility::getUpperCaseString($paypal_response->status) == ApplaneConstentInterface::EXPIRED)) { //canceled/expired or Erorr case
                        $paypal_status = ApplaneConstentInterface::CANCELED;
                        $applane_status = ApplaneConstentInterface::REJECTED;
                        $connect_app_service->upDateConnectApplane($ci_transaction_system_id, $applane_status);
                    }
                    $this->updateTransactionStatus($payment_transaction_data, $transaction_data, $paypal_ids_array, $paypal_status, $post_paypal_transaction_id);
                } else if ($reason == self::PAY_ONCE_CI) {
                    $this->returnCiResult($payment_transaction_data, $transaction_data, $paypal_response);
                }
            }
        }
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [ConnectPaypalTransactionStatus]');
    }

    /**
     * update the transaction status when a main transaction get completed/canceled/error etc
     * @param object array $payment_transaction_data
     * @param object array $transaction_data
     * @param string $paypal_ids_object
     * @param string $paypal_status
     * @param string $post_paypal_transaction_id
     */
    public function updateTransactionStatus($payment_transaction_data, $transaction_data, $paypal_ids_object, $paypal_status, $post_paypal_transaction_id) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_app_service->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [updateTransactionStatus] and paypal status: '.$paypal_status);
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $today = new \DateTime('now');
        $today_date = $today->format('Ymd');
        $time = $today->format('his'); //120312(12 hours, 03 minutes, 12 seconds)
        if ($paypal_status != '') { //if transaction is completed/canceled/error
            $user_id = $transaction_data->getUserId();
            $user_info = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
            $user_email = $user_info->getEmail();
            $first_name = $user_info->getFirstName();
            $last_name = $user_info->getLastName();
            $status = $paypal_status;
            $connect_payment_transaction_id = $payment_transaction_data->getId();
            $connect_transaction_id = $transaction_data->getId();
            //extract data from entity
            $app_id = $transaction_data->getApplicationId();
            $app_data = $connect_app_service->getApplicationData($app_id);
            $secret = $app_data->getApplicationSecret();
            $url_post = $transaction_data->getUrlPost();
            $total_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getTransactionValue());
            $amount_curreny = $connect_app_service->getConnectAmountCurrency();
            $amount = ($total_amount * $amount_curreny);
            $transaction_id = $transaction_data->getTransactionId();
            $currency = $transaction_data->getCurrency();
            $language_id = $transaction_data->getLanguageId();
            $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction_data->getUsedCi());
            $session_id  = $transaction_data->getSessionId();
            $description = $transaction_data->getDescription();
            $final_paypal_id = $post_paypal_transaction_id;
            $back_ci_used = ($ci_used * $amount_curreny);
            $result_mac = $connect_app_service->prePareResponseMac($transaction_id, $connect_transaction_id, $currency, $amount, $back_ci_used, $today_date, $time, $status, $secret);
            $post_data = $connect_app_service->preparePostArray($amount, $back_ci_used, $currency, $transaction_id, $language_id, $result_mac, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $connect_transaction_id, $final_paypal_id, $session_id, $description);
            $url_post_query = $connect_app_service->postdataQuery($post_data); //prepare the post data query
            $connect_app_service->__createLog('Post url is: ' . $url_post . 'post query data is: ' . $url_post_query);
            $connect_app_service->sendPostUrl($url_post, $url_post_query); //send the post data via curl
            //local database update
            $connect_app_service->updateConnectTransactionStatus($transaction_data, $status, Utility::encodeData($paypal_ids_object));
            $connect_app_service->updateConnectPaymentTransactionStatusById($payment_transaction_data, $status, Utility::encodeData($paypal_ids_object));
            $connect_app_service->__createLog('Table [sixthcontinentconnecttransaction] is update with id : ' . $connect_transaction_id . ' status: ' . $status . ' and paypal ids: ' . Utility::encodeData($paypal_ids_object));
            $connect_app_service->__createLog('Table [SixthcontinentconnectPaymentTransaction] is update with id : ' . $connect_payment_transaction_id . ' status: ' . $status . ' and paypal ids: ' . Utility::encodeData($paypal_ids_object));
            
        }
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [updateTransactionStatus] and paypal status: '.$paypal_status);
        return true;
    }
    /**
     * handle response of paypal ci return
     * @param object array $transaction_data
     * @param object $ci_paypal_response
     */
    public function returnCiResult($connect_payment_transaction, $transaction_data, $paypal_ci_response) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_app_service->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [returnCiResult]');
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $today = new \DateTime('now');
        $today_date = $today->format('Ymd');
        $time = $today->format('his'); //120312(12 hours, 03 minutes, 12 seconds)
        $completed_status = Utility::getUpperCaseString(ApplaneConstentInterface::COMPLETED);
        $cancel_status = Utility::getUpperCaseString(ApplaneConstentInterface::CANCELED);
        if (Utility::getUpperCaseString($paypal_ci_response->status) == $completed_status) { //completed case
            $ci_status = $completed_status;
        } else if ((Utility::getUpperCaseString($paypal_ci_response->status) == $cancel_status) || (Utility::getUpperCaseString($paypal_ci_response->status) == ApplaneConstentInterface::ERROR) || (Utility::getUpperCaseString($paypal_ci_response->status) == ApplaneConstentInterface::EXPIRED)) {
            $ci_status = $cancel_status; //canceled
        } else {
            //$ci_status = $cancel_status; //it may be in still pending mode
        }
        if ($ci_status != '') {
            $connect_payment_transaction_id = $connect_payment_transaction->getId();
            $pay_key = $connect_payment_transaction->getTransactionReference();
            $connect_app_service->__createLog('Paypal status for payKey: ' . $pay_key . ' and table [SixthcontinentconnectPaymentTransaction] id: ' . $connect_payment_transaction_id);
            $ci_paypal_id = $paypal_connect_service->getPaypalTransactionIds($paypal_ci_response);
            $ci_paypal_id_object = isset($ci_paypal_id[0]) ? $ci_paypal_id[0] : '';
            $ci_paypal_reciver_id = isset($ci_paypal_id[0]['receiver']) ? self::PAYPAL_IDS_SEPERATOR . $ci_paypal_id[0]['receiver'] : '';
            $user_id = $transaction_data->getUserId();
            $user_info = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
            $user_email = $user_info->getEmail();
            $first_name = $user_info->getFirstName();
            $last_name = $user_info->getLastName();
            $status = $ci_status;
            $connect_transaction_id = $transaction_data->getId();
            //extract data from entity
            $app_id = $transaction_data->getApplicationId();
            $app_data = $connect_app_service->getApplicationData($app_id);
            $secret = $app_data->getApplicationSecret();
            $url = $transaction_data->getUrl();
            $url_post = $transaction_data->getUrlPost();
            $discount_amount = $transaction_data->getDiscount();
            $total_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getTransactionValue());
            $amount_curreny = $connect_app_service->getConnectAmountCurrency();
            $amount = ($total_amount * $amount_curreny);
            $transaction_id = $transaction_data->getTransactionId();
            $currency = $transaction_data->getCurrency();
            $language_id = $transaction_data->getLanguageId();
            $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction_data->getUsedCi());
            $paypal_id = $transaction_data->getPaypalTransactionId();
            $session_id  = $transaction_data->getSessionId();
            $description = $transaction_data->getDescription();
            $decoded_paypal_ids = Utility::decodeData($paypal_id);
            $decode_single_transaction_object = $decoded_paypal_ids[0];
            $reciever_paypal_id = $this->returnPaypalid($decode_single_transaction_object);
            $final_paypal_id = $reciever_paypal_id . $ci_paypal_reciver_id;
            $back_ci_used = ($ci_used * $amount_curreny);
            $result_mac = $connect_app_service->prePareResponseMac($transaction_id, $connect_transaction_id, $currency, $amount, $back_ci_used, $today_date, $time, $status, $secret);
            $post_data = $connect_app_service->preparePostArray($amount, $back_ci_used, $currency, $transaction_id, $language_id, $result_mac, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $connect_transaction_id, $final_paypal_id, $session_id, $description);
            $url_post_query = $connect_app_service->postdataQuery($post_data); //prepare the post data query
            $connect_app_service->__createLog('Post url is: ' . $url_post . ' and post query is: ' . $url_post_query);
            $connect_app_service->sendPostUrl($url_post, $url_post_query); //send the post data via curl
            //local database update
            //$connect_app_service->updateConnectTransactionStatus($transaction_data, $status, $paypal_ids_object);
            $connect_app_service->updateConnectPaymentTransactionStatusById($connect_payment_transaction, $status, Utility::encodeData(array($ci_paypal_id_object)));
            $connect_app_service->__createLog('Table [SixthcontinentconnectPaymentTransaction] is update with id : ' . $connect_payment_transaction_id . ' status: ' . $status . ' and paypal ids: ' . Utility::encodeData($ci_paypal_id_object));
        }

        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [returnCiResult]');
        return true;
    }

    public function returnPaypalid($paypal_id_json) {
        $reciever_paypal_id = $paypal_id_json->receiver;
        return $reciever_paypal_id;
    }

    /**
     * Handle the Ci return response
     * @param type $transaction_data
     * @param type $paypal_ci_response
     */
    public function updateCireturnStatus($transaction_data, $paypal_ci_response) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_app_service->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [updateCireturnStatus]');
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $ci_pay_key = $paypal_ids_object = $reciver_paypal_transaction_id = $ci_payment_status = '';
        if (isset($paypal_ci_response->responseEnvelope->ack)) {
            if (Utility::getUpperCaseString($paypal_ci_response->responseEnvelope->ack) == Utility::getUpperCaseString(ApplaneConstentInterface::SUCCESS)) {
                if ($paypal_ci_response->paymentExecStatus == Utility::getUpperCaseString(ApplaneConstentInterface::COMPLETED)) {
                    $ci_payment_status = ApplaneConstentInterface::COMPLETED;
                    $ci_pay_key = $paypal_ci_response->payKey;
                    $paypal_transaction_ids = $paypal_connect_service->getPaypalTransactionIds($paypal_ci_response);
                    $single_transaction_id = $paypal_transaction_ids[0];
                    $reciver_paypal_transaction_id  = isset($single_transaction_id['receiver']) ? self::PAYPAL_IDS_SEPERATOR.$single_transaction_id['receiver'] : '';
                    $paypal_ids_object = Utility::encodeData(array($single_transaction_id));
                } else if (($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED) || ($status == ApplaneConstentInterface::EXPIRED)) {
                    $ci_payment_status = ApplaneConstentInterface::CANCELED;
                } else {
                    $ci_payment_status = ApplaneConstentInterface::PENDING;
                }
            }
            $connect_app_service->__createLog('CI return with paykey '.$ci_pay_key. ' with status: '.$ci_payment_status);
            $connect_transaction_id = $transaction_data->getId();
            $user_id = $transaction_data->getUserId();
            //extract data from entity
            $app_id = $transaction_data->getApplicationId();
            $app_data = $connect_app_service->getApplicationData($app_id);
            $secret = $app_data->getApplicationSecret();
            $total_amount = $transaction_data->getTransactionValue();
            $amount_curreny = $connect_app_service->getConnectAmountCurrency();
            $ci_used = $transaction_data->getUsedCi();
            $ci_transaction_id = $transaction_data->getCiTransactionSystemId();
            $connect_payment_transaction_data = array(
                'connect_transaction_id' => $connect_transaction_id,
                'user_id' => $user_id,
                'reason' => self::PAY_ONCE_CI,
                'app_id' => $app_id,
                'payment_via' => self::PAYPAL,
                'status' => $ci_payment_status,
                'pay_key' => $ci_pay_key,
                'cash_amount' => $ci_used,
                'paypal_id' => $paypal_ids_object,
                'ci_transaction_id' => $ci_transaction_id,
                'ci_used' => $ci_used
            );
            $connect_app_service->__createLog('Enter a new '.self::PAY_ONCE_CI.' type transaction into table [SixthcontinentconnectPaymentTransaction] with status: '.$ci_payment_status);
            $connect_app_service->initiateSixthcontinentPaymentTransaction($connect_payment_transaction_data);
        }
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentCronConnectService] and function [updateCireturnStatus]');
        return $reciver_paypal_transaction_id;
    }

}
