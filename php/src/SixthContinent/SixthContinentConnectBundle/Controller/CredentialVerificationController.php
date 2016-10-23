<?php

namespace SixthContinent\SixthContinentConnectBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Paypal\PaypalIntegrationBundle\Entity\ShopPaypalInformation;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Transaction\TransactionSystemBundle\Repository\TransactionRepository;

class CredentialVerificationController extends Controller {

    CONST INITIATED = 'INITIATED';
    CONST PENDING = 'PENDING';
    CONST APP_PAYPAL_EMAIL = 'yogendra.singh-buyer@daffodilsw.com';
    CONST PAY_ONCE = 'PAY_ONCE';
    CONST PAYPAL = 'PAYPAL';
    CONST PAY_ONCE_CI = 'PAY_ONCE_CI';
    CONST PAYPAL_IDS_SEPERATOR = ';';

    public function indexAction($name) {
        return $this->render('SixthContinentConnectBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * utility service
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }

    private function _getEntityManager() {
        return $this->getDoctrine()->getManager();
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    protected function _getSixcontinentBusinessAppService() {
        return $this->container->get('sixth_continent_connect.connect_business_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectBusinessAccountService
    }
    
    /**
     * check the parameters for the app
     * @param request object
     * @return json
     */
    public function postAppConnectTransactionInitiateAction(Request $request) {
        
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_business_app_service = $this->_getSixcontinentBusinessAppService();
        $result_data = array();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('app_id', 'amount', 'currency', 'transaction_id', 'url', 'url_post', 'url_back', 'type_service', 'language_id', 'mac');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        $em = $this->_getEntityManager();
        $app_id = $data['app_id'];
        $amount = $data['amount'];
        $user_id = $data['user_id'];
        $currency = $data['currency'];
        $transaction_id = $data['transaction_id'];
        $session_id = isset($data['sessionid']) ? $data['sessionid'] : '';
        $app_data = $em->getRepository('SixthContinentConnectBundle:Application')
                       ->findOneBy(array('applicationId' => $app_id));
        $back_url = $connect_app_service->prepareBackUrl($data); //prepare the back url.
        $result_data = array('url_back' => $back_url);
        if (!$app_data) {
            $resp_data = new Resp(Msg::getMessage(1118)->getCode(), Msg::getMessage(1118)->getMessage(), $result_data);//INVALID_APP
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $app_business_data = $connect_business_app_service->getApplicationBusinessAccount($app_id);
        if (!$app_business_data) { //check for if a application has not Business account it can not make transaction
            $resp_data = new Resp(Msg::getMessage(1135)->getCode(), Msg::getMessage(1135)->getMessage(), $result_data);//APPLICATION_BUSINESS_ACCOUNT_NOT_EXISTS
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $secret = $app_data->getApplicationSecret();
        $application_user_id = $app_data->getUserId();
        $verify_mac = $this->verifyMac($data, $secret);
        if (!$verify_mac) {
            $resp_data = new Resp(Msg::getMessage(1119)->getCode(), Msg::getMessage(1119)->getMessage(), $result_data);//MAC_NOT_MATCHED
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        if ($amount == 0) { //if amount is 0
            $resp_data = new Resp(Msg::getMessage(1120)->getCode(), Msg::getMessage(1120)->getMessage(), $result_data);//AMOUNT_NOT_ACCEPTABLE
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        $transaction_break_data = $connect_app_service->getTransactionBreakupWithCredit($amount, $user_id);
        $amount_currency = $connect_app_service->getConnectAmountCurrency();
        $data['shop_id'] = '50916';
        $data['buyer_id'] = $user_id;
        $data['secret'] = $secret;
        $data['application_id'] = $app_id;
        $data['payble_value']   = $connect_app_service->makeAmountCurrency($transaction_break_data['cash']);
        $data['checkout_value'] = $connect_app_service->makeAmountCurrency($transaction_break_data['secondry_user_amount']);
        $data['discount']       = $connect_app_service->makeAmountCurrency($transaction_break_data['discount']);
        $data['transaction_value'] = $connect_app_service->makeAmountCurrency($transaction_break_data['total_amount']);
        $transaction_break_data['total_amount'] = $transaction_break_data['total_amount'];
        $data['used_ci'] = $connect_app_service->makeAmountCurrency($transaction_break_data['ci_used']);
        $data['total_available_ci'] = $connect_app_service->makeAmountCurrency($transaction_break_data['available_ci']);
        $transaction_break_data['available_ci'] = $transaction_break_data['available_ci'] * $amount_currency;
        $data['vat'] = $connect_app_service->makeAmountCurrency($transaction_break_data['checkout_vat']); //checkout value vat
        $data['status'] = self::INITIATED;
        $data['transaction_type'] = self::PAY_ONCE;
        $data['application_user_id'] = $application_user_id;
        $data['session_id']          = $session_id;
        $data['citizen_aff_charge'] = '0.01';
        $data['shop_aff_charge'] = '0.01';
        $data['friends_follower_charge'] = '0.01';
        $data['buyer_charge'] = '0.01';
        $data['sixc_charge'] = '0.01';
        $data['all_country_charge'] = '0.01';
        //initiate the transaction.
        $TransaData = $em->getRepository("TransactionSystemBundle:Transaction")
                                        ->createTransactionRecord($data);
        $data['ci_transaction_system_id'] = $TransaData['id'];
        
        $connect_transaction_id = $connect_app_service->initiateConnectTransation($data);
        $result_data = array('ci_transaction_system_id' => $TransaData['id'], 'connect_transaction_id' => $connect_transaction_id, 'transaction_break_up' => $transaction_break_data, 'url_back' => $back_url);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAppConnectTransactionInitiateAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }

    /**
     * verify the mac and prepare the array for mac comparision
     * @param array $data
     * @param string $secret
     * @return boolean $result
     */
    public function verifyMac($data, $secret) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $mac = $data['mac'];
        $mac_params = array(
            'transaction_id' => $data['transaction_id'],
            'currency' => $data['currency'],
            'amount' => $data['amount']
        );
        $result = $connect_app_service->verifyMac($mac_params, $mac, $secret);
        return $result;
    }

    /**
     * check the parameters for the app
     * @param request object
     * @return json
     */
    public function postAuthentication1Action(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('app_id', 'amount', 'currency', 'transaction_id', 'url', 'url_post', 'url_back', 'type_service', 'language_id', 'mac');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\postAuthentication1Action] and function [checkConnectAppDataAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $url = $connect_app_service->prepareResponseUrlData($data);
        $result_data = array('url' => $url);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . Utility::encodeData($result_data));
        Utility::createResponse($resp_data);
    }

    public function posturldataAction(Request $request) { 
        $connect_app_service = $this->_getSixcontinentAppService();
        $raw_post_data = file_get_contents('php://input');
        //$raw_post_array = explode('&', $raw_post_data);
        $connect_app_service->__createLog('post data is: ' . $raw_post_data);
        // return true;
    }

    /**
     * confirm the transaction and register transaction on paypal also.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postConfirmConnectTransactionAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postConfirmConnectTransactionAction]', array());
        $utilityService = $this->getUtilityService();
        $em = $this->_getEntityManager();
        $result_data = array();
        $pay_key = '';

        $requiredParams = array('connect_transaction_id', 'user_id', 'return_url', 'cancel_url');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postConfirmConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $connect_transaction_id = $data['connect_transaction_id'];
        $user_id = $data['user_id'];
        $return_url = $data['return_url'];
        $cancel_url = $data['cancel_url'];
        $transaction_data = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                               ->findOneBy(array('id' => $connect_transaction_id, 'userId' => $user_id));
        if (!$transaction_data) {
            $resp_data = new Resp(Msg::getMessage(1122)->getCode(), Msg::getMessage(1122)->getMessage(), $result_data);//TRANSACTION_DOES_NOT_BELONGS_TO_YOU
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . Utility::encodeData($result_data));
            Utility::createResponse($resp_data);
        }
        //extract data from entity
        $ci_transaction_system_id = $transaction_data->getciTransactionSystemId();
        $app_id = $transaction_data->getApplicationId();
        $app_data = $em->getRepository('SixthContinentConnectBundle:Application')
                       ->findOneBy(array('applicationId' => $app_id));
        if (!$app_data) {
            $resp_data = new Resp(Msg::getMessage(1118)->getCode(), Msg::getMessage(1118)->getMessage(), $result_data);//INVALID_APP
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postConfirmConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //fetch the paypal email id from appid
        $app_paypal_email = $connect_app_service->getApplicationPaypalEmail($app_id);
        if ($app_paypal_email == '') {
            $resp_data = new Resp(Msg::getMessage(1134)->getCode(), Msg::getMessage(1134)->getMessage(), $result_data);//APPLICATION_PAYPAL_ACCOUNT_NOT_AVAILABLE
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postConfirmConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $app_name = $app_data->getApplicationName();
        $amount_currency = $connect_app_service->getConnectAmountCurrency();
        $cash_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getPaybleValue());
        $url_back = $transaction_data->getUrlBack();
        $discount_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getDiscount());
        $total_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getTransactionValue());
        $transaction_id = $transaction_data->getTransactionId();
        $currency = $transaction_data->getCurrency();
        $language_id = $transaction_data->getLanguageId();
        $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction_data->getUsedCi());
        $checkout_value = $connect_app_service->changeRoundAmountCurrency($transaction_data->getCheckoutValue());
        $paypal_return_url = $return_url . '?transaction_id=' . $connect_transaction_id;
        $paypal_cancel_url = $cancel_url . '?transaction_id=' . $connect_transaction_id;
        $payble_amount = $total_amount - $ci_used;
        $applane_status = ApplaneConstentInterface::INITIATED;
        $secondry_reciever_amount = $checkout_value;
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_sandbox');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_sandbox');
        } else {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_live');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_live');
        }
        //$ci_transaction_system_id = $connect_app_service->registerTransactionOnApplane($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status); //frezze the ci on transaaction system
        
        
//        $ci_transaction_system_id = $connect_app_service->registerTransactionOnApplane($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status);
        
        if ($ci_transaction_system_id == 0) {
            $resp_data = new Resp(Msg::getMessage(1123)->getCode(), Msg::getMessage(1123)->getMessage(), $result_data);//CITIZEN_INCOME_IS_NOT_RESERVED_ON_TRANSACTION_SYSTEM
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        $connect_app_paypal_email = $app_paypal_email;
        //register the transaction on paypal.
        $paypal_response = $paypal_connect_service->registerTransactionOnPaypal($cash_amount, $user_id, $connect_app_paypal_email, $paypal_return_url, $paypal_cancel_url, $currency, $paypal_sixthcontinent_email, $secondry_reciever_amount, $app_id);
        $back_url_data = array('url_back' => $url_back, 'amount' => ($total_amount * $amount_currency), 'currency' => $currency, 'transaction_id' => $transaction_id);
        $back_url = $connect_app_service->prepareBackUrl($back_url_data); //prepare the back url.
        if (isset($paypal_response->responseEnvelope)) { //response from paypal.
            if ($paypal_response->responseEnvelope->ack == 'Success') { //if successfullly transaction registered.
                $pay_key = $paypal_response->payKey;
                
            } else { //paypal error
                $applane_rejected_status = ApplaneConstentInterface::REJECTED;
                $connect_app_service->__createLog('Applnae transaction is rejected request with id: '.$ci_transaction_system_id);
                //$connect_app_service->upDateConnectApplane($ci_transaction_system_id, $applane_rejected_status);
                $resp_data = new Resp(Msg::getMessage(1124)->getCode(), Msg::getMessage(1124)->getMessage(), array('url_back' => $back_url));//TRANSACTION_NOT_INITIATED_ON_PAYPAL
                $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        } else { //paypal error
            $applane_rejected_status = ApplaneConstentInterface::REJECTED;
            $connect_app_service->__createLog('Applnae transaction is rejected request with id: '.$ci_transaction_system_id);
            $connect_app_service->upDateConnectApplane($ci_transaction_system_id, $applane_rejected_status);
            $resp_data = new Resp(Msg::getMessage(1124)->getCode(), Msg::getMessage(1124)->getMessage(), array('url_back' => $back_url)); //TRANSACTION_NOT_INITIATED_ON_PAYPAL
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        if ($pay_key == '') {
            $resp_data = new Resp(Msg::getMessage(1124)->getCode(), Msg::getMessage(1124)->getMessage(), array('url_back' => $back_url));//TRANSACTION_NOT_INITIATED_ON_PAYPAL
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $status = self::PENDING;
        $reason = self::PAY_ONCE;
        $payment_via = self::PAYPAL;
        $connect_payment_transaction_data = $connect_app_service->prepareConnectPaymentTransactionData($connect_transaction_id, $user_id, $app_id, $reason, $payment_via, $status, $pay_key, ($total_amount * $amount_currency), $ci_transaction_system_id, ($ci_used*$amount_currency));
        $connect_app_service->updateConnectTransaction($transaction_data, $pay_key, $ci_transaction_system_id, $status);
        $connect_app_service->initiateSixthcontinentPaymentTransaction($connect_payment_transaction_data);
        $transaction_break_up_data = array('cash' => $cash_amount, 'ci_used' => $ci_used, 'discount' => ($discount_amount), 'total_amount' => ($total_amount), 'is_ci_used' => 1);
        $result_data = array('paypal_link' => $paypal_authorize_url . $pay_key, 'transaction_break_up' => $transaction_break_up_data, 'cancel_url' => $paypal_cancel_url, 'return_url' => $paypal_return_url, 'url_back' => $back_url);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }

    /**
     * Response of sixthcontinent connect transaction.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json
     */
    public function postResponseConnectTransactionAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $paypal_connect_service = $this->_getSixthcontinentPaypalService();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postResponseConnectTransactionAction]', array());
        $utilityService = $this->getUtilityService();
        $em = $this->_getEntityManager();
        $result_data = array();
        $pay_key = $paypal_transaction_id = $paypal_ids_object = $ci_paypal_ids_object = $ci_pay_key = '';
        $is_local_update = 0;
        $today = new \DateTime('now');
        $today_date = $today->format('Ymd');
        $time = $today->format('his'); //120312(12 hours, 03 minutes, 12 seconds)
        $requiredParams = array('connect_transaction_id', 'user_id', 'type');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postResponseConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $type = Utility::getUpperCaseString($data['type']);
        $user_id = $data['user_id'];
        $connect_transaction_id = $data['connect_transaction_id'];
        $user_info = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
        $user_email = $user_info->getEmail();
        $payment_via = self::PAYPAL;
        $ci_reason = self::PAY_ONCE_CI;
        $first_name = $user_info->getFirstName();
        $last_name = $user_info->getLastName();
        $transaction_type_filter  = array(ApplaneConstentInterface::SUCCESS, ApplaneConstentInterface::CANCELED);
        $transaction_type_filter1 = array(ApplaneConstentInterface::COMPLETED, ApplaneConstentInterface::CANCELED);
        if (!in_array($type, $transaction_type_filter)) { //check for array of response of transaction from paypal
            $resp_data = new Resp(Msg::getMessage(1128)->getCode(), Msg::getMessage(1128)->getMessage(), $result_data);//TRANSACTION_TYPE_NOT_ACCEPTABLE
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postResponseConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $transaction_data = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                               ->findOneBy(array('id' => $connect_transaction_id, 'userId' => $user_id));
        if (!$transaction_data) {
            $resp_data = new Resp(Msg::getMessage(1122)->getCode(), Msg::getMessage(1122)->getMessage(), $result_data);//TRANSACTION_DOES_NOT_BELONGS_TO_YOU
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //extract data from entity
        $app_id = $transaction_data->getApplicationId();
        //fetch the paypal email id from appid
        $app_paypal_email = $connect_app_service->getApplicationPaypalEmail($app_id);
        if ($app_paypal_email == '') {
            $resp_data = new Resp(Msg::getMessage(1134)->getCode(), Msg::getMessage(1134)->getMessage(), $result_data);//APPLICATION_PAYPAL_ACCOUNT_NOT_AVAILABLE
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postConfirmConnectTransactionAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $amount_currency = $connect_app_service->getConnectAmountCurrency();
        $app_data = $connect_app_service->getApplicationData($app_id);
        $secret = $app_data->getApplicationSecret();
        $reciever_paypal_app_owner_email = $app_paypal_email;
        $cash_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getPaybleValue());
        $url_back = $transaction_data->getUrlBack();
        $url = $transaction_data->getUrl();
        $url_post = $transaction_data->getUrlPost();
        $discount_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getDiscount());
        $total_amount = $connect_app_service->changeRoundAmountCurrency($transaction_data->getTransactionValue());
        $amount = ($total_amount * $amount_currency);
        $transaction_id = $transaction_data->getTransactionId();
        $currency = $transaction_data->getCurrency();
        $language_id = $transaction_data->getLanguageId();
        $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction_data->getUsedCi());
        $status = $transaction_data->getStatus();
        $pay_key = $transaction_data->getPaypalTransactionReference();
        $transaction_system_id = $transaction_data->getCiTransactionSystemId();
        $session_id  = $transaction_data->getSessionId();
        $description = $transaction_data->getDescription();
        $back_url_data = array('url_back' => $url_back, 'amount' => ($amount), 'currency' => $currency, 'transaction_id' => $transaction_id);
        $back_url = $connect_app_service->prepareBackUrl($back_url_data); //prepare the back url.
        $result_data = array('url_back' => $back_url);
        if (in_array(Utility::getUpperCaseString($status), $transaction_type_filter1)) {
            $resp_data = new Resp(Msg::getMessage(1125)->getCode(), Msg::getMessage(1125)->getMessage(), $result_data);//TRANSACTION_ALREADY_APPROVED_OR_CANCELED
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $paypal_response = $paypal_connect_service->getTransactionResponseCode($type, $pay_key); //get the paypal status or transaction status
        if ($paypal_response['code'] == 101) { ///success case
            $status = ApplaneConstentInterface::COMPLETED;
            $paypal_ids  = $paypal_response['paypal_transaction_ids'];
            $paypal_ids_object = Utility::encodeData($paypal_ids);
            //$paypal_transaction_id = implode(',', $paypal_ids);
            $paypal_transaction_id = $paypal_response['paypal_transaction_ids'][0]['receiver'];
            $applane_status = ApplaneConstentInterface::APPROVED;
            $connect_app_service->upDateConnectApplane($transaction_system_id, $applane_status);
            if ($ci_used > 0) {
                $ci_return_response = $paypal_connect_service->ciReturnConnectAmount($reciever_paypal_app_owner_email, $ci_used, $currency, $app_id);
                if (isset($ci_return_response->paymentExecStatus)) { 
                    if ($ci_return_response->paymentExecStatus == ApplaneConstentInterface::COMPLETED) {
                        $ci_return_status = ApplaneConstentInterface::COMPLETED;
                        $ci_paypal_ids = $paypal_connect_service->getPaypalTransactionIds($ci_return_response);
                        $ci_paypal_ids_object = Utility::encodeData($ci_paypal_ids);
                        $paypal_transaction_id = $paypal_transaction_id. self::PAYPAL_IDS_SEPERATOR.$ci_paypal_ids[0]['receiver'];
                        $ci_pay_key = $ci_return_response->payKey;
                    } else {
                        $ci_return_status = ApplaneConstentInterface::PENDING;
                    }
                    $ci_used_converted = ($ci_used*$amount_currency);
                    $connect_ci_payment_data = $connect_app_service->prepareConnectPaymentTransactionData($connect_transaction_id, $user_id, $app_id, $ci_reason, $payment_via, $ci_return_status, $ci_pay_key, $ci_used_converted, $transaction_system_id, $ci_used_converted);
                    $connect_ci_payment_data['paypal_id'] = $ci_paypal_ids_object;
                    //make entry for CI return in [SixthcontinentconnectPaymentTransaction]
                    $connect_app_service->initiateSixthcontinentPaymentTransaction($connect_ci_payment_data);
                }
            } 
            $code = 101;
        } else if ($paypal_response['code'] == 1127) { //canceled OR error on paypal
            $status = ApplaneConstentInterface::CANCELED;
            $applane_status = ApplaneConstentInterface::REJECTED;
            $connect_app_service->upDateConnectApplane($transaction_system_id, $applane_status);
            $code = 1127;
        } else if ($paypal_response['code'] == 1126) {
            $code = 1126;
            $status = ApplaneConstentInterface::PENDING;
        }
 
        $ci_transaction_system_id = $transaction_data->getciTransactionSystemId();
        /* Update transaction data */
        $this->updateInTransaction($ci_transaction_system_id , $status);
        
        
        
        $back_ci_used = ($ci_used * $amount_currency);
        $result_mac = $connect_app_service->prePareResponseMac($transaction_id, $connect_transaction_id, $currency, $amount, $back_ci_used, $today_date, $time, $status, $secret);
        $get_data   = $connect_app_service->prepareGetArray($amount, $back_ci_used, $currency, $transaction_id, $language_id, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $session_id, $description);
        $post_data  = $connect_app_service->preparePostArray($amount, $back_ci_used, $currency, $transaction_id, $language_id, $result_mac, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $connect_transaction_id, $paypal_transaction_id, $session_id, $description);
        
        $url_connect = $connect_app_service->prepareQueryString($url, $get_data);
        $connect_app_service->__createLog('Get Url is: '. $url_connect);
        $url_post_query = $connect_app_service->postdataQuery($post_data); //prepare the post data query
        $connect_app_service->__createLog('Post Url is: '. $url_post. ' and post data query: '.$url_post_query);
        $connect_app_service->sendPostUrl($url_post, $url_post_query); //send the post data via curl
        //local database update
        $connect_app_service->updateConnectTransactionStatus($transaction_data, $status, $paypal_ids_object); //update transaction status in [sixthcontinentconnecttransaction] table
        $connect_app_service->updateConnectPaymentTransactionStatus($connect_transaction_id, $status, $paypal_ids_object); //update transaction status in [sixthcontinentconnectpaymenttransaction] table
        $result_data = array('url' => $url_connect, 'url_back' => $back_url);
        $resp_data = new Resp(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $result_data);
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postAuthentication1Action] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
   public function updateInTransaction($id_transaction , $status){
        $em = $this->_getEntityManager();
        $redistribution_ci  = $this->container->get('redistribution_ci');
        $updateArr = array('transaction_id' => $id_transaction, 'status' => $status);
        $TransData = $em->getRepository("TransactionSystemBundle:Transaction")
               ->updateTransactionRecord($updateArr);
        $id_transaction = $TransData["id"];
        $sellerId = $TransData["seller_id"];
        $time_close = $TransData["time_close"];
        $transactionGatewayReference  = TransactionRepository::$TRNS_GATEWAY_REFERENCE_OFFER;

        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
        return true;
    }

}
