<?php

namespace CardManagement\CardManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use StoreManager\StoreBundle\Entity\Store;
use Notification\NotificationBundle\Document\UserNotifications;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use CardManagement\CardManagementBundle\Entity\ShopSubscription;
use CardManagement\CardManagementBundle\Entity\Contract;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionsPayment;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;

class PayRecurringController extends Controller {

    CONST MANUAL = "MANUAL";
    protected $payment_limit = 0;
    CONST SUCCESS = "SUCCESS";
    CONST FAILED = "FAILED";
    CONST CONFIRMED = "CONFIRMED";
    CONST RECURRING = "RECURRING";
    CONST R = "R";
    CONST T = "T";
    CONST S = "S";
    CONST PENDING_PAYMENT = "PENDING_PAYMENT";
    CONST SUBSCRIBED = "SUBSCRIBED";
    CONST UNSUBSCRIBED = "UNSUBSCRIBED";
    CONST SUBSCRIPTION_PENDING_PAYMENT = "SUBSCRIPTION_PENDING_PAYMENT";
    
    /**
     * Get recurring payment physical url
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetrecurringpaymenturlsAction(Request $request)
    {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController.postGetrecurringpaymenturlsAction";
        $applane_service->writeAllLogs($handler, $monolog_data, array());  
        
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

        $required_parameter = array('shop_id', 'user_id', 'return_url', 'cancel_url');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($res_data); 
        }
        
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->user_id;
        
        $recurring_id = $this->updateShopTransactionDetail($shop_id);
        if($recurring_id == 0){
            $res_data = array('code' => 1067, 'message' => 'RECURRING_ID_NOT_FOUND ', 'data' => $data);
            $this->returnResponse($res_data); 
        }
        if($recurring_id == -1){
            $res_data = array('code' => 1072, 'message' => 'PAYABLE_AMOUNT_MUST_BE_'.  strtoupper($this->payment_limit).'_OR_MORE', 'data' => $data);
            $this->returnResponse($res_data);
        }
        //get recurring url
        $recurring_url = $this->createRecurringUrl($recurring_id, $object_info);
        $data = array('url'=>$recurring_url);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }
    
    
    /**
     * Create subscription cartasi physical url.
     * @param array $data
     * @return string
     */
    public function createRecurringUrl($rec_id, $object_info)
    {
         //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->createRecurringUrl";
        $applane_service->writeAllLogs($handler, $monolog_data, array());  
        
        $data = array();
        $vat = $this->container->getParameter('vat');
        // code for chiave 
        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');
        // code for alias
        $prod_alias = $this->container->getParameter('prod_alias');
        $payment_type_send = 'R';
        $payment_type = self::RECURRING;
        $em = $this->container->get('doctrine')->getManager();
        //get recuring paymant object
        $shop_payment = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                        ->findOneBy(array('id' => $rec_id));
        if(!$shop_payment){
             $res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
             $this->returnResponse($res_data);
        }
        //$recurring_amount = 1;
        $recurring_amount = $shop_payment->getTotalAmount();
        $dec_amount = sprintf("%01.2f", $recurring_amount);
        $amount_to_pay = $dec_amount * 100;
        $user_id = $object_info->user_id;
        $shop_id = $shop_payment->getShopId();
        //$amount_to_pay = 1;
        //$amount = $dec_amount * 100;
        $amount = $amount_to_pay;
        //code for codTrans
        $codTrans = "6THCH" . time() . $user_id . $payment_type_send;
        // code for divisa
        $currency_code = 'EUR';
        //code for string for live
        $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$prod_payment_mac_key";
        //code for sting for test - fix
        //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$test_payment_mac_key";
        //code for mac
        $mac = sha1($string);
        // fix
        //$prod_alias = $test_alias;
        //code for symfony url hit by payment gateway
        $urlpost = $this->container->getParameter('urlpost_recurring');
        $urlpost = $urlpost."?txn_id=".$rec_id; 
        $urlpost = urlencode($urlpost);
        
        //create contract
        $contract_obj = $this->createContract($shop_id);
        if(!$contract_obj){
            $res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
            //write log
            $monolog_data = "PayRecurringController->createRecurringUrl: Contract not created";
            $applane_service->writeAllLogs($handler, $monolog_data, array());  
            $this->returnResponse($res_data);
        }
        
        $contract_number = $contract_obj->getContractNumber();
        $contract_id = $contract_obj->getId();
        //map contarct with recuuring paymant
        $resp = $this->mapContractWithRecurring($contract_id, $shop_id);
        //check for response
        if(!$resp){
            $res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }
        
        $cancel_url = $object_info->cancel_url."?txn_id=".$rec_id; 
        $cancel_url = urlencode($cancel_url);
        
        $return_url = $object_info->return_url."?txn_id=".$rec_id; 
        $return_url = urlencode($return_url);
        //code for session id
        $session_id = $user_id;
        //code for descrizione
        $description_amount = $amount/100;
        $description = "payment for $description_amount euro with type $payment_type to Sixthcontinent";
        //code for url that is angular js url for payment success and failure
        $url = $return_url;
        //code for tipo_servizio (type service)        
        $type_service = "paga_rico";
        //code for final url to return
        $final_url = $this->container->getParameter('oneclick_pay_url')."?session_id=$session_id&alias=$prod_alias&urlpost=$urlpost&tipo_servizio=$type_service&mac=$mac&divisa=$currency_code&importo=$amount&codTrans=$codTrans&url=$url&url_back=$cancel_url&num_contratto=$contract_number&descrizione=$description";
        //write log for recurring url
        $monolog_data = "PayRecurringController->createRecurringUrl:Transaction recurring url:".$final_url;
        $applane_service->writeAllLogs($handler, $monolog_data, array());  
        return $final_url;
    }
    
    /**
     * Create contract when user subscribed
     */
    public function createrecurringcontractAction()
    {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController.createrecurringcontractAction";
        $applane_service->writeAllLogs($handler, $monolog_data, array()); 
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime('now');
/*
        //code for testing
         $_POST['num_contratto'] = "shop_contract_30041_143256203011    ";
       
          $_POST['alias'] = "test";
          $_POST['tipoTransazione'] = "india";
          $_POST['data'] = '34343434';
          $_POST['orario'] = '3455';
          $_POST['$BRAND'] ="sdf";
          $_POST['tipoProdotto'] = "register";
          $_POST['nome'] = 'abhishek';
          $_POST['cognome'] = 'gupta';
          $_POST['languageId'] = 'in';
          $_POST['pan'] = '345345345';
          $_POST['nazionalita'] = 'indian';
          $_POST['session_id'] = '1';
          $_POST['email'] = "abhishek@gmail.com";
          $_POST['scadenza_pan'] = "1234534";
          $_POST['importo'] = '12810';
          #$_POST['importo'] = '13908';
          #$_POST['importo'] = '12078';
          
          $_POST['descrizione'] = '138520000';
          $_POST['codTrans'] = '6THCH14219319624T';
          $_POST['mac'] = '1098ccccc0';
          $_POST['divisa'] = 'ered';
          $_POST['esito'] = "ok";
          $_POST['payment_type'] = "Ok";
*/
        //maintain logger for cartasi response
        $monolog_data = "Request: Data=>in".json_encode($_POST);
        $applane_service->writeAllLogs($handler, $monolog_data, array());  
        $recurring_id = (isset($_GET['txn_id']) ? $_GET['txn_id'] : 0);
       //success case
        if (isset($_POST['esito']) && strtolower($_POST['esito']) == 'ok') {
            
            /*check for brand */
            if(isset($_POST['$BRAND'])) {
                $brand = $_POST['$BRAND'];
            }else if(isset($_POST['brand'])) {
                $brand = $_POST['brand'];
            }else{
                $brand = '';
            }
            
            /*check for email */
            if(isset($_POST['email'])) {
                $gateway_email = $_POST['email'];
            }else if(isset($_POST['mail'])) {
                $gateway_email = $_POST['mail'];
            }else{
                $gateway_email = '';
            }

            $_POST['alias'] = (isset($_POST['alias']) ? $_POST['alias'] : '');
            $_POST['tipoTransazione'] = (isset($_POST['tipoTransazione']) ? $_POST['tipoTransazione'] : '');
            $_POST['num_contratto'] = (isset($_POST['num_contratto']) ? $_POST['num_contratto'] : '');
            $_POST['tipoProdotto'] = (isset($_POST['tipoProdotto']) ? $_POST['tipoProdotto'] : '');
            $_POST['nome'] = (isset($_POST['nome']) ? $_POST['nome'] : '');
            $_POST['cognome'] = (isset($_POST['cognome']) ? $_POST['cognome'] : '');
            $_POST['languageId'] = (isset($_POST['languageId']) ? $_POST['languageId'] : '');
            $_POST['pan'] = (isset($_POST['pan']) ? $_POST['pan'] : '');
            $_POST['nazionalita'] = (isset($_POST['nazionalita']) ? $_POST['nazionalita'] : '');
            $_POST['session_id'] = (isset($_POST['session_id']) ? $_POST['session_id'] : null);
            $_POST['scadenza_pan'] = (isset($_POST['scadenza_pan']) ? $_POST['scadenza_pan'] : '');
            $_POST['codiceAutorizzazione'] = (isset($_POST['codiceAutorizzazione']) ? $_POST['codiceAutorizzazione'] : '');
            $_POST['codTrans'] = (isset($_POST['codTrans']) ? $_POST['codTrans'] : '');
            $_POST['mac'] = (isset($_POST['mac']) ? $_POST['mac'] : '');
            $_POST['messaggio'] = (isset($_POST['messaggio']) ? $_POST['messaggio'] : '');
            $_POST['importo'] = (isset($_POST['importo']) ? $_POST['importo'] : 0);
            
            //check contract is already exist
            $check_contract_result = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('contractNumber' => $_POST['num_contratto']));
            //if contract exist
            if($check_contract_result){
                $is_default_flag = 0;
                $contract_str = explode('_', $_POST['num_contratto']);
                $profile_id = $contract_str[2];
                //update the contract
                $check_contract_result->setAlias($_POST['alias']);
                $check_contract_result->setRegion($_POST['tipoTransazione']);
                $check_contract_result->setRegistrationTime($time);
                $check_contract_result->setContractNumber($_POST['num_contratto']);
                $check_contract_result->setBrand($brand);
                $check_contract_result->setProductType($_POST['tipoProdotto']);
                $check_contract_result->setName($_POST['nome']);
                $check_contract_result->setLastName($_POST['cognome']);
                $check_contract_result->setLanguageCode($_POST['languageId']);
                $check_contract_result->setPan($_POST['pan']);
                $check_contract_result->setNationality($_POST['nazionalita']);
                $check_contract_result->setSessionId($_POST['session_id']);
                $check_contract_result->setMail($gateway_email);
                $check_contract_result->setDeleted(0);
                $check_contract_result->setProfileId($profile_id);
                $check_contract_result->setStatus(1);
                $check_contract_result->setDefaultflag($is_default_flag);
                $check_contract_result->setCreateTime($time);
                $check_contract_result->setExpirationPan($_POST['scadenza_pan']);
                $check_contract_result->setTransactionType('RC');
                $check_contract_result->setMessage($_POST['messaggio']);
                $em->persist($check_contract_result); //storing the comment data.
                $em->flush();
                $contract_id = $check_contract_result->getId();
                //mark the recurring as paid
                $rec_resp = $this->updateRecurring($recurring_id, $_POST, $contract_id);
                $pay_type = $rec_resp;
                $user_id = $this->getShopOwnerId($profile_id);
                /////////////**************/////////////////
                $pos_s = strpos($pay_type, self::S); //check if S exist
                if ($pos_s !== false) {
                    //mark the shop as subscribed
                    $this->updateShopSubscriptionStatus($profile_id, '1');
                    //update applane for subscribed transaction
                    $applane_txn_id = $this->updateOnApplaneSusbcription($profile_id);
                    //update for applane_transaction_id
                    $this->updateTransactionId($profile_id, $applane_txn_id);
                    $sub_status = self::CONFIRMED;
                    //update shop subscription
                    $this->updateShopSubscription($profile_id, $sub_status);
                    //Send Success Mail
                    $this->sendNotification($profile_id, $user_id, 'SUCCESS');
                    //Update subscription log
                    $this->subscriptionPaymentSuccessLogs($user_id, $profile_id);
                }
                $pos_r = strpos($pay_type, self::R); //check if R exist
                if ($pos_r !== false) {
                    $this->transactionRegistrationPaymentSuccessLogs($user_id, $profile_id); //make logs when registration payment failed for notifications
                }
                /////////////**************/////////////////
                $this->transactionPaymentSuccessLogs($profile_id, $user_id); //remove the transaction payment notification logs if exists.
                
                $citizen_id = $user_id;
                $pstatus = self::CONFIRMED;
                $error_code = '';
                $error_description = '';
                $transaction_reference = $_POST['codTrans'];
                $transaction_value = $_POST['importo'];
                $vat_amount = 0;
               
                $paypal_id = '';
                //update on payment transaction table
                $this->updatePaymentTransaction($recurring_id, $rec_resp, $citizen_id, $profile_id, 'CARTASI', $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id);
                $monolog_data = "Request: Data=>SUCCESS";
                $applane_service->writeAllLogs($handler, $monolog_data, array());  
                exit('Yes Post');
            }
            if (!$check_contract_result) {
                $contract_str = explode('_', $_POST['num_contratto']);
                $profile_id = $contract_str[2];
                $shop_name = '';
                $em = $this->getDoctrine()->getManager();
                $store_obj = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => (int) $profile_id));
                
                
                $pay_type = substr($_POST['codTrans'], -1);
                $is_default_flag = 0;
                // create a new contract
                $contract = new Contract();
                $contract->setAlias($_POST['alias']);
                $contract->setRegion($_POST['tipoTransazione']);
                $contract->setRegistrationTime($time);
                $contract->setContractNumber($_POST['num_contratto']);
                $contract->setBrand($brand);
                $contract->setProductType($_POST['tipoProdotto']);
                $contract->setName($_POST['nome']);
                $contract->setLastName($_POST['cognome']);
                $contract->setLanguageCode($_POST['languageId']);
                $contract->setPan($_POST['pan']);
                $contract->setNationality($_POST['nazionalita']);
                $contract->setSessionId($_POST['session_id']);
                $contract->setMail($gateway_email);
                $contract->setDeleted(0);
                $contract->setProfileId($profile_id);
                $contract->setStatus(1);
                $contract->setDefaultflag($is_default_flag);
                $contract->setCreateTime($time);
                $contract->setExpirationPan($_POST['scadenza_pan']);
                $contract->setTransactionType('RC');
                $contract->setMessage($_POST['messaggio']);
                $em->persist($contract); //storing the comment data.
                $em->flush();
                
                //mark the recurring as paid
                $contract_id = $contract->getId(); //get contract id
                //mark the recurring as paid
                $rec_resp = $this->updateRecurring($recurring_id, $_POST, $contract_id);
                $user_id = $this->getShopOwnerId($profile_id);
                $pay_type = $rec_resp;
                /////////////**************/////////////////
                $pos_s = strpos($pay_type, self::S); //check if S exist
                if ($pos_s !== false) {
                    //mark the shop as subscribed
                    $this->updateShopSubscriptionStatus($profile_id, '1');
                    //update applane for subscribed transaction
                    $applane_txn_id = $this->updateOnApplaneSusbcription($profile_id);
                    //update for applane_transaction_id
                    $this->updateTransactionId($profile_id, $applane_txn_id);
                    $sub_status = self::CONFIRMED;
                    //update shop subscription
                    $this->updateShopSubscription($profile_id, $sub_status);
                    //Send Success Mail
                    $this->sendNotification($profile_id, $user_id, 'SUCCESS');
                    //Update subscription log
                    $this->subscriptionPaymentSuccessLogs($user_id, $profile_id);
                }
                $pos_r = strpos($pay_type, self::R); //check if R exist
                if ($pos_r !== false) {
                    $this->transactionRegistrationPaymentSuccessLogs($user_id, $profile_id); //make logs when registration payment failed for notifications
                }
                /////////////**************/////////////////
                
                $this->transactionPaymentSuccessLogs($profile_id, $user_id); //remove the transaction payment notification logs if exists.
                
                $citizen_id = $user_id;
                $pstatus = self::CONFIRMED;
                $error_code = '';
                $error_description = '';
                $transaction_reference = $_POST['codTrans'];
                $transaction_value = $_POST['importo'];
                $vat_amount = 0;
                $paypal_id = '';
                //update on payment transaction table
                $this->updatePaymentTransaction($recurring_id, $rec_resp, $citizen_id, $profile_id, 'CARTASI', $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id);
                $monolog_data = "Request: Data=>SUCCESS";
                $applane_service->writeAllLogs($handler, $monolog_data, array());
                exit('Yes Post');
            }
        }
        //Fail case
        $profile_id = 0;
        $contract_id = 0;
            if(isset($_POST['$BRAND'])) {
                $brand = $_POST['$BRAND'];
            }else if(isset($_POST['brand'])) {
                $brand = $_POST['brand'];
            }else{
                $brand = '';
            }
            
            /*check for email */
            if(isset($_POST['email'])) {
                $gateway_email = $_POST['email'];
            }else if(isset($_POST['mail'])) {
                $gateway_email = $_POST['mail'];
            }else{
                $gateway_email = '';
            }

            $_POST['alias'] = (isset($_POST['alias']) ? $_POST['alias'] : '');
            $_POST['tipoTransazione'] = (isset($_POST['tipoTransazione']) ? $_POST['tipoTransazione'] : '');
            $_POST['num_contratto'] = (isset($_POST['num_contratto']) ? $_POST['num_contratto'] : '');
            $_POST['tipoProdotto'] = (isset($_POST['tipoProdotto']) ? $_POST['tipoProdotto'] : '');
            $_POST['nome'] = (isset($_POST['nome']) ? $_POST['nome'] : '');
            $_POST['cognome'] = (isset($_POST['cognome']) ? $_POST['cognome'] : '');
            $_POST['languageId'] = (isset($_POST['languageId']) ? $_POST['languageId'] : '');
            $_POST['pan'] = (isset($_POST['pan']) ? $_POST['pan'] : '');
            $_POST['nazionalita'] = (isset($_POST['nazionalita']) ? $_POST['nazionalita'] : '');
            $_POST['session_id'] = (isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $_POST['scadenza_pan'] = (isset($_POST['scadenza_pan']) ? $_POST['scadenza_pan'] : '');
            $_POST['codiceAutorizzazione'] = (isset($_POST['codiceAutorizzazione']) ? $_POST['codiceAutorizzazione'] : '');
            $_POST['codTrans'] = (isset($_POST['codTrans']) ? $_POST['codTrans'] : '');
            $_POST['mac'] = (isset($_POST['mac']) ? $_POST['mac'] : '');
            $_POST['messaggio'] = (isset($_POST['messaggio']) ? $_POST['messaggio'] : '');
            $_POST['importo'] = (isset($_POST['importo']) ? $_POST['importo'] : 0);
            
            //check contract is already exist
            $check_contract_result = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('contractNumber' => $_POST['num_contratto']));
            //if contract exist
            if($check_contract_result){
                $is_default_flag = 0;
                $contract_str = explode('_', $_POST['num_contratto']);
                $profile_id = $contract_str[2];
                //update the contract
                $check_contract_result->setAlias($_POST['alias']);
                $check_contract_result->setRegion($_POST['tipoTransazione']);
                $check_contract_result->setRegistrationTime($time);
                $check_contract_result->setContractNumber($_POST['num_contratto']);
                $check_contract_result->setBrand($brand);
                $check_contract_result->setProductType($_POST['tipoProdotto']);
                $check_contract_result->setName($_POST['nome']);
                $check_contract_result->setLastName($_POST['cognome']);
                $check_contract_result->setLanguageCode($_POST['languageId']);
                $check_contract_result->setPan($_POST['pan']);
                $check_contract_result->setNationality($_POST['nazionalita']);
                $check_contract_result->setSessionId($_POST['session_id']);
                $check_contract_result->setMail($gateway_email);
                $check_contract_result->setDeleted(0);
                $check_contract_result->setProfileId($profile_id);
                $check_contract_result->setStatus(0);
                $check_contract_result->setDefaultflag($is_default_flag);
                $check_contract_result->setCreateTime($time);
                $check_contract_result->setExpirationPan($_POST['scadenza_pan']);
                $check_contract_result->setTransactionType('RC');
                $check_contract_result->setMessage($_POST['messaggio']);
                $em->persist($check_contract_result);
                $em->flush();
                $contract_id = $check_contract_result->getId();
            }
                 
                //mark the recurring as failed
                $rec_resp_failed = $this->updateRecurringFailed($recurring_id, $_POST, $contract_id);
                //$this->transactionPaymentLogs($user_id, $shop_id); //make logs when payment failed for notifications
                //mark the recurring as failed
                $user_id = $this->getShopOwnerId($profile_id);
                $citizen_id = $user_id;
                $pstatus = self::FAILED;
                $error_code = '';
                $error_description = '';
                $transaction_reference = $_POST['codTrans'];
                $transaction_value = $_POST['importo'];
                $vat_amount = 0;
                $paypal_id = '';
                //update on payment transaction table
                $this->updatePaymentTransaction($recurring_id, $rec_resp_failed, $citizen_id, $profile_id, 'CARTASI', $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id);
                $monolog_data = "Request: Data=>FAILED, No transaction done";
                $applane_service->writeAllLogs($handler, $monolog_data, array());
                exit('Yes Post');
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
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Exit";
        $applane_service->writeAllLogs($handler, '', $monolog_data);  
        exit;
    }
    
    
     /**
     * Update shop transaction detail table
     */
    public function updateShopTransactionDetail($shop_id) {
         //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->updateShopTransactionDetail";
        $applane_service->writeAllLogs($handler, $monolog_data, ''); 
        
        $time = new \DateTime("now");
        $txn_id = 0;
        $pending_type = '';
        //entity Object
        $em = $this->container->get('doctrine')->getManager();
        /** get admin id * */
        $admin_id = $em
                ->getRepository('TransactionTransactionBundle:RecurringPayment')
                ->findByRole('ROLE_ADMIN');
        $vat = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');

        //check if already added
       $shop_transaction_check = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('shopId' => $shop_id, 'status' => 0));
       //mark as Failed
       if($shop_transaction_check){
           $shop_transaction_check->setPaymentDate($time);
           $shop_transaction_check->setComment("Manual Failed");
           $shop_transaction_check->setStatus(2);
           $shop_transaction_check->setContractId(0);
           $em->persist($shop_transaction_check);
           $em->flush();
       }
        //get entries from shop transaction table whose type is T
        $shop_transaction_entry = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->getShopTransactionById($shop_id);

        if (count($shop_transaction_entry) > 0) {
            foreach ($shop_transaction_entry as $transaction_record) {
                $transaction_id = $transaction_record->getId();
                $shop_id = $transaction_record->getShopId();
                $user_id = $transaction_record->getUserId();
                $shop_payable_amount = $transaction_record->getPayableAmount();
                $shop_payable_vat_amount = $transaction_record->getVat();
                $type = $transaction_record->getType();
                //get shop whose type is T,R
                $shop_pending_transaction = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->getShopPedningTransaction($shop_id, $transaction_id);

                $pending_transaction_ids = array();
                $pending_transaction_amount = array();
                if (count($shop_pending_transaction) > 0) {

                    $pending_type = '';
                    foreach ($shop_pending_transaction as $shop_pending_record) {
                        $pending_transaction_ids[] = $shop_pending_record->getId();
                        $pending_transaction_amount[] = ($shop_pending_record->getPayableAmount() + $shop_pending_record->getVat());
                        $shop_pending_type_val = $shop_pending_record->getType();
                        $pos = strpos($pending_type, $shop_pending_type_val);
                        if ($pos ===  false) {
                           $pending_type = $pending_type . $shop_pending_type_val;
                        }
                    }
                } //end if
                $pending_transaction_ids[] = $transaction_id; //also add the current txn id
                $pending_transaction_amount[] = ($shop_payable_amount + $shop_payable_vat_amount); //also add the current payable amount of txn id
                $previous_pending_amount = array_sum($pending_transaction_amount);
                $previous_pending_id = implode(',', $pending_transaction_ids);
                //check payment type
                if (count($shop_pending_transaction) > 0) {
                $updated_type = $this->getPayType($type, $pending_type);
                 } else{
                     $updated_type = $type;
                 }
                //check if already updated
                $shop_payment = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                        ->findOneBy(array('shopId' => $shop_id, 'status' => 0));
                //if transaction is equal or more than 5 euro
                $min_amount_limit = $this->payment_limit;
                if ((!$shop_payment) && ($previous_pending_amount >= $min_amount_limit)) {
                    $mode = self::MANUAL;
                    $shop_payment = new ShopTransactionsPayment();
                    $shop_payment->setShopId($shop_id);
                    $shop_payment->setPendingIds($previous_pending_id);
                    $shop_payment->setPendingAmount($previous_pending_amount);
                    $shop_payment->setTotalAmount($previous_pending_amount);
                    $shop_payment->setPayType($updated_type);
                    $shop_payment->setMode($mode);
                    $shop_payment->setCreatedAt($time);
                    $shop_payment->setPaymentDate($time);
                    $shop_payment->setStatus(0);
                    $shop_payment->setContractId(0); //this field will be updated in pay transaction function
                    $em->persist($shop_payment);
                    $em->flush();
                    $txn_id = $shop_payment->getId(); //get last inserted id
                }else{
                    $txn_id = -1; //amount should be $min_amount_limit or more
                }
            }
        }
        
        //write log       
        $monolog_data = "Exiting From PayRecurringController->updateShopTransactionDetail, Recurring id :".$txn_id;
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        //end log
        return $txn_id;
    }
    
     /**
     * Get Pay Type
     * @param string $current_type
     * @param string $pending_type
     * @return string
     */
    public function getPayType($current_type, $pending_type) {
        $pos = strpos($pending_type, $current_type);
        if ($pos ===  false) {
            return $current_type . $pending_type;
        }
        return $pending_type;
    }
    
    /**
     * Create contract with status 0
     * @param int $shop_id
     * @return string
     */
    public function createContract($shop_id) {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->createContract";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
         //get contract number
        $contract_number = 'shop_contract_' . $shop_id . '_' . time();
        $time = new \DateTime("now");
        //entity Object
        $em = $this->container->get('doctrine')->getManager();
        // create a new contract
        $contract = new Contract();
        $contract->setAlias('');
        $contract->setRegion('');
        $contract->setRegistrationTime($time);
        $contract->setContractNumber($contract_number);
        $contract->setBrand('');
        $contract->setProductType('');
        $contract->setName('');
        $contract->setLastName('');
        $contract->setLanguageCode('');
        $contract->setPan('');
        $contract->setNationality('');
        $contract->setSessionId('');
        $contract->setMail('');
        $contract->setDeleted(0);
        $contract->setProfileId($shop_id);
        $contract->setStatus(0);
        $contract->setDefaultflag(0);
        $contract->setCreateTime($time);
        $contract->setExpirationPan('');
        $contract->setTransactionType('RC');
        try{
        $em->persist($contract);
        $em->flush();
        //maintain log
        $serializer = $this->container->get('serializer');
        $json = $serializer->serialize($contract, 'json');
        $monolog_data = "Exiting From PayRecurringController->createContract, Contract Obj: ".$json;
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        //End log
        return $contract;
        }catch(\Exception $e){
            return false;
        }
    }
    
    /**
     * Update paymant transaction table
     * @param type $recurring_id
     * @param type $recurring
     * @param type $citizen_id
     * @param type $shop_id
     * @param type $mode
     * @param type $pstatus
     * @param type $error_code
     * @param type $error_description
     * @param type $transaction_reference
     * @param type $transaction_value
     * @param type $vat_amount
     * @param type $contract_id
     * @param type $paypal_id
     * $return boolean
     */
    public function updatePaymentTransaction($recurring_id, $recurring, $citizen_id, $shop_id, $mode, $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id) {
         //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->updatePaymentTransaction";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
        //update payment transaction table
        $pay_tx_data['item_id'] = $recurring_id;
        $pay_tx_data['reason'] = $recurring;
        $pay_tx_data['citizen_id'] = $citizen_id;
        $pay_tx_data['shop_id'] = $shop_id;
        $pay_tx_data['payment_via'] = $mode;
        $pay_tx_data['payment_status'] = $pstatus;
        $pay_tx_data['error_code'] = $error_code;
        $pay_tx_data['error_description'] = $error_description;
        $pay_tx_data['transaction_reference'] = $transaction_reference;
        $pay_tx_data['transaction_value'] = $transaction_value;
        $pay_tx_data['vat_amount'] = $vat_amount;
        $pay_tx_data['contract_id'] = $contract_id;
        $pay_tx_data['paypal_id'] = $paypal_id;
        $payment_txn = $this->container->get('paypal_integration.payment_transaction');
        $payment_txn->addPaymentTransaction($pay_tx_data);
        //write log
        $monolog_data = "Exiting From PayRecurringController->updatePaymentTransaction";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
    }

    /**
     * 
     * @param type $recurring_id
     * @param type $_POST
     */
    public function updateRecurring($recurring_id, $cdata, $contract_id){  
        $data = array();
        $time = new \DateTime("now");
        $pay_type = '';
        $em = $this->container->get('doctrine')->getManager();
        $shop_pending_transaction = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('id' => $recurring_id));
        if(!$shop_pending_transaction){
            //make log
            $res_data = array('code' => 1067, 'message' => 'RECURRING_ID_NOT_FOUND ', 'data' => $data);
            
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            $handler = $this->container->get('monolog.logger.recurring');
            $monolog_data = "Request: Data=>in".json_encode($res_data);
            $applane_service->writeAllLogs($handler, $monolog_data, array());  
            $this->returnResponse($res_data); 
        }
        $shop_pending_transaction->setPaymentDate($time);
        $shop_pending_transaction->setTipoCarta('');
        $shop_pending_transaction->setPaese('');
        $shop_pending_transaction->setTipoProdotto($cdata['tipoProdotto']);
        $shop_pending_transaction->setTipoTransazione($cdata['tipoTransazione']);
        $shop_pending_transaction->setCodiceAutorizzazione($cdata['codiceAutorizzazione']);
        $shop_pending_transaction->setDataOra('');
        $shop_pending_transaction->setCodiceEsito('');
        $shop_pending_transaction->setDescrizioneEsito('');
        $shop_pending_transaction->setMac($cdata['mac']);
        $shop_pending_transaction->setCodTrans($cdata['codTrans']);
        $shop_pending_transaction->setComment('');
        $shop_pending_transaction->setContractTxnId($cdata['codTrans']);
        $shop_pending_transaction->setStatus(1);
        $shop_pending_transaction->setContractId($contract_id);
        try{
        $em->persist($shop_pending_transaction);
        $em->flush();
        $pending_transaction_ids = $shop_pending_transaction->getPendingIds();
        $shop_id = $shop_pending_transaction->getShopId();
        $pay_type = $shop_pending_transaction->getPayType();
        $current_txn_id = $shop_pending_transaction->getId();
        //get registration fee txn id
            $shop_pending_type_val = self::R;
            $pos = strpos($pay_type, $shop_pending_type_val); //check if R exist
            if ($pos === false) {
                $reg_txn_id = '';
            } else {
                $reg_txns = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->findOneBy(array('shopId' => $shop_id, 'type' => 'R', 'status' => 0));
                if ($reg_txns) {
                    $reg_txn_id = $reg_txns->getId();
                }
            }

            //get subscription id
            $pos_s = strpos($pay_type, self::S); //check if S exist
            if ($pos_s === false) {
                $sub_txn_id = '';
            } else {
                $sub_txns = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->findOneBy(array('shopId' => $shop_id, 'type' => 'S', 'status' => 0));
                if ($sub_txns) {
                    $sub_txn_id = $sub_txns->getId();
                }
            }
            // mark status as success for previous pending transaction
        if (count($pending_transaction_ids) > 0) {
             $update_pending_transaction = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                    ->setMultiTransactionStatus($pending_transaction_ids, 1);
             
             //mark shop as active and registration fee paid
             $this->updateShop($shop_id, $pay_type);
             //update on applane for success
             $this->updateOnApplane($pending_transaction_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, self::SUCCESS);
            }
        }catch(\Exception $e){
            
        }
        
        return $pay_type;
    }
    
    /**
     * Update Shop
     * @param int $shop_id
     */
    public function updateShop($shop_id, $pay_type) {
        $em = $this->container->get('doctrine')->getManager();
        //mark shop registration fee paid
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));

        $pos = strpos($pay_type, self::R); //check if R exist
        if ($pos !== false) {
            if (count($store_obj) > 0) {
                $store_obj->setPaymentStatus(1);
                $em->persist($store_obj);
                $em->flush();
                //update on applane
                $this->updateOnApplaneRegistration($shop_id);
            }
        }

        //check if shop status is enabled
        if (count($store_obj) > 0) {
            $shop_status = $store_obj->getShopStatus();
            if ($shop_status != 1) {
                //enable the shop
                $store_obj->setShopStatus(1);
                $em->persist($store_obj);
                $em->flush();
            }
        }
    }
    
    /**
     * Update on applane
     * @param string $pending_ids
     * @param int $current_id
     */
    public function updateOnApplane($pending_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, $status) {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->updateOnApplane reg_txn_id: ".$reg_txn_id." sub_txn_id".$sub_txn_id;
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
        //$reg_txn_id = array($reg_txn_id);
        $em = $this->container->get('doctrine')->getManager();
        $pending_shop_array = array();
        if (strlen($pending_ids) > 0) {
            //prepare pending shop array
            $pending_shop_array = explode(',', $pending_ids);
        }

        $transaction_data_detail = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                    ->findOneBy(array('id' => $current_txn_id));

        foreach ($pending_shop_array as $txn) {
            $txn_id = $txn;
            $transaction_data = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                    ->findOneBy(array('id' => $txn_id));

            $invoice_id = $transaction_data->getInvoiceId();
            $transaction_id_carte_si = $transaction_data_detail->getContractTxnId();
            $transaction_note = $transaction_data_detail->getComment();
            $paid_on = $transaction_data_detail->getPaymentDate();
            $payment_date = $transaction_data_detail->getPaymentDate();
            $payment_status = $status;
            $vat_amount = $transaction_data->getVat();  //calculate vat
            $amount_paid = ($transaction_data->getPayableAmount()) + $vat_amount; //calculate total amount paid
            $applane_data['invoice_id'] = $invoice_id;
            $applane_data['transaction_id_carte_si'] = $transaction_id_carte_si;
            $applane_data['transaction_note'] = $transaction_note;
            $applane_data['amount_paid'] = $amount_paid;
            $paid_on_sec = strtotime($paid_on->format('Y-m-d'));
            $applane_data['paid_on'] = date(DATE_RFC3339, ($paid_on_sec));
            $payment_date_sec = strtotime($payment_date->format('Y-m-d'));
            $applane_data['payment_date'] = date(DATE_RFC3339, ($payment_date_sec));
            $applane_data['payment_status'] = $payment_status;
            $applane_data['vat_amount'] = $vat_amount;
            $applane_data['shop_id'] = $transaction_data_detail->getShopId();
           if($txn_id != $reg_txn_id && $txn_id != $sub_txn_id && $invoice_id != ""){
            //get dispatcher object
            $event = new FilterDataEvent($applane_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.recurringupdate', $event);
            }elseif($txn_id == $reg_txn_id && $status == self::SUCCESS) { 
            $event = new FilterDataEvent($applane_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.recurringinsert', $event);
            }
        }
        $monolog_data = "Exiting From PayRecurringController->updateOnApplane";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        return true;
    }
    
    /**
     * Update on applane for shop registration
     * @param type $shopid
     */
    public function updateOnApplaneRegistration($shopid) {
        //Write log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->updateOnApplaneRegistration";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
        $applane_data['shop_id'] = $shopid;
        //get dispatcher object
        $event = new FilterDataEvent($applane_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.registrationfeeupdate', $event);
        
        $monolog_data = "Exiting From PayRecurringController->updateOnApplaneRegistration";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
    }
    
    /**
     * 
     * @param type $recurring_id
     * @param type $_POST
     */
    public function updateRecurringFailed($recurring_id, $cdata, $contract_id){
        //Write log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->updateRecurringFailed";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
        $reg_txn_id = '';
        $pay_type = '';
        $sub_txn_id = '';
        $time = new \DateTime("now");
        $em = $this->container->get('doctrine')->getManager();
        $shop_pending_transaction = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('id' => $recurring_id));
        if(!$shop_pending_transaction){
            //make log
            $res_data = array('code' => 1067, 'message' => 'RECURRING_ID_NOT_FOUND ', 'data' => $data);
            
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            $handler = $this->container->get('monolog.logger.recurring');
            $monolog_data = "Request: Data=>in".json_encode($res_data);
            $applane_service->writeAllLogs($handler, $monolog_data, array());  
            $this->returnResponse($res_data); 
        }
        $shop_pending_transaction->setPaymentDate($time);
        $shop_pending_transaction->setTipoCarta('');
        $shop_pending_transaction->setPaese('');
        $shop_pending_transaction->setTipoProdotto($cdata['tipoProdotto']);
        $shop_pending_transaction->setTipoTransazione($cdata['tipoTransazione']);
        $shop_pending_transaction->setCodiceAutorizzazione($cdata['codiceAutorizzazione']);
        $shop_pending_transaction->setDataOra('');
        $shop_pending_transaction->setCodiceEsito('');
        $shop_pending_transaction->setDescrizioneEsito('');
        $shop_pending_transaction->setMac($cdata['mac']);
        $shop_pending_transaction->setCodTrans($cdata['codTrans']);
        $shop_pending_transaction->setComment('');
        $shop_pending_transaction->setContractTxnId($cdata['codTrans']);
        $shop_pending_transaction->setStatus(2);
        $shop_pending_transaction->setContractId($contract_id);
        try{
        $em->persist($shop_pending_transaction);
        $em->flush();
        $pending_transaction_ids = $shop_pending_transaction->getPendingIds();
        $shop_id = $shop_pending_transaction->getShopId();
        $pay_type = $shop_pending_transaction->getPayType();
        $current_txn_id = $shop_pending_transaction->getId();
        $this->updateOnApplane($pending_transaction_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, self::FAILED);
        }catch(\Exception $e){
            
        }
        //Write log
        $monolog_data = "Exiting From PayRecurringController->updateRecurringFailed";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        return $pay_type;
    }
    
    /**
     * 
     * @param type $contract_number
     * @param type $shop_id
     */
    public function mapContractWithRecurring($contract_id, $shop_id)
    {
        $em = $this->container->get('doctrine')->getManager();
        //check if already added
       $shop_transaction_check = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('shopId' => $shop_id, 'status' => 0));
       if($shop_transaction_check){
           $shop_transaction_check->setContractId($contract_id);
           $em->persist($shop_transaction_check);
           $em->flush();
           return true;
       }
       return false;
    }
    
    /**
     * Update pending payment
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUpdatependingpaymentsAction(Request $request)
    {
        //Write log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In PayRecurringController->postUpdatependingpaymentsAction";
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
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

        $required_parameter = array('shop_id', 'user_id', 'txn_id', 'status', 'message');
        $data = array();

        $allowed_array = array(self::SUCCESS, self::FAILED);
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER' . $this->miss_param, 'data' => $data);
            $this->returnResponse($res_data); 
        }
       
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->user_id;
        $status = $object_info->status;
        $txn_id = $object_info->txn_id;
        $message = $object_info->message;
        
        $monolog_data = "PayRecurringController->postUpdatependingpaymentsAction: Requset Object :".json_encode($de_serialize);
        $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
        if(!in_array($status, $allowed_array)){
            $res_data = array('code' => 100, 'message' => 'INVALID_TYPE', 'data' => $data);
            $this->returnResponse($res_data); 
        }
        
        switch ($status){
                case self::SUCCESS: 
                    //success
                    $this->updateContract($shop_id, $txn_id, self::SUCCESS, $message);
                    
                case self::FAILED:
                    //failed
                    $this->updateContract($shop_id, $txn_id, self::FAILED, $message);
        }
    }
    
    /**
     * Update Contract
     * @param int $shop_id
     * @param int $txn_id
     * @param string $status
     */
    public function updateContract($shop_id, $txn_id, $status, $message)
    {
       //Write log
       $applane_service = $this->container->get('appalne_integration.callapplaneservice');
       $handler = $this->container->get('monolog.logger.recurring');
       $monolog_data = "Entering In PayRecurringController->updateContract: Arguments :".$shop_id."/".$txn_id."/".$status."/".$message;
       $applane_service->writeAllLogs($handler, '', $monolog_data); 
        
       $data = array();
        //get contract id
       $em = $this->container->get('doctrine')->getManager();
       //check if already added
       $shop_transaction_check = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('id' => $txn_id));
       if(!$shop_transaction_check){
            $res_data = array('code' => 1067, 'message' => 'RECURRING_ID_NOT_FOUND ', 'data' => $data);
            $monolog_data = "PayRecurringController->updateContract: ".json_encode($res_data);
            $applane_service->writeAllLogs($handler, '', $monolog_data); 
            $this->returnResponse($res_data); 
       }
       $contract_id = $shop_transaction_check->getContractId(); //get contract id
       
       //get contract object
       $contract_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findOneBy(array('id' => $contract_id, 'status' => 0));
       //contract found
       if($contract_obj){
       $contract_obj->setMessage($message);
       if($status == self::SUCCESS){
           $contract_obj->setStatus(1);
       }
       $em->persist($contract_obj);
       $em->flush();
       }
       $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
       //Write Logs
       $monolog_data = "PayRecurringController->updateContract: ".json_encode($res_data);
       $applane_service->writeAllLogs($handler, '', $monolog_data); 
            
       $this->returnResponse($res_data); 
    }
    
    /**
     * Get Shop Owner Id
     * @param int $shop_id
     * @return int
     */
    public function getShopOwnerId($shop_id)
    {
        //get shop owner id
        $shop_ids[] = $shop_id;
        //get shop objects and extract shop owner id
        $user_object = $this->container->get('user_object.service');
        $user_object_service = $user_object->getShopsOwnerIds($shop_ids, array(), true);
        $shop_ids_users = $user_object_service['owner_ids']; //userid,shop_owner_id associated array
        $user_id = (isset($shop_ids_users[$shop_id]) ? $shop_ids_users[$shop_id] : 0); //store owner id.
        return $user_id;
    }
    /**
     * pending payment success logs remove(will not sent furthernotifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionPaymentSuccessLogs($shop_id, $user_id) {
        //get shop owner id
        $pending_payment_type = self::PENDING_PAYMENT;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** get record if exists * */
        $transaction_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                                      ->checkTransactionPaymentLogs((int)$user_id, (int)$shop_id, $pending_payment_type);
        if ($transaction_payment_log) { //if record exists we will remove.
            $dm->remove($transaction_payment_log);
            try {
                $dm->flush();
            } catch (\Exception $ex) {
                
            }
        }
        return true;
    }
    
     /**
     * Update shop subscription
     * @param type $shop_id
     * @return boolean
     */
    public function updateShopSubscriptionStatus($shop_id, $status) {
        $this->__subscriptionLog('Enter In SubscriptionService->updateShopSubscriptionStatus', array());
        $em = $this->container->get('doctrine')->getManager();
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if ($store_obj) {
            $store_obj->setIsSubscribed($status);
            try {
                $em->persist($store_obj);
                $em->flush();
            } catch (\Exception $e) {
                
            }
        }
        $this->__subscriptionLog('Exit From SubscriptionService->updateShopSubscriptionStatus', array());
        return true;
    }

    /**
     * Update on applane for shop subscription
     * @param type $shopid
     */
    public function updateOnApplaneSusbcription($shopid) {
        $this->__subscriptionLog('Enter In SubscriptionService->updateOnApplaneSusbcription', array());
        $applane_data['shop_id'] = $shopid;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $susbcription_id = $applane_service->onShopSubscriptionAddAction($shopid);
        $this->__subscriptionLog('Exit From SubscriptionService->updateOnApplaneSusbcription: With Subscription id' . $susbcription_id, array());
        return $susbcription_id;
    }

    /**
     * Map applane transaction id with subscription id
     * @param string $applane_txn_id
     * @param int $subsc_id
     */
    public function updateTransactionId($shop_id, $applane_txn_id) {
        $em = $this->container->get('doctrine')->getManager();
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('shopId' => $shop_id, 'status' => self::SUBSCRIBED));
        if ($subscription_obj) {
            $subscription_obj->setTransactionId($applane_txn_id);
            $em->persist($subscription_obj);
            $em->flush();
        }

        //$user_id = $subscription_obj->getSubscriberId();
        return true;
    }
    
     /**
     * Update shop subscription
     * @param int $txn_id
     * @param string $sub_status
     */
    public function updateShopSubscription($shop_id, $sub_status) {
        $this->__subscriptionLog('Enter into RecurringShopPaymentService->updateShopSubscription', array());
        $payment_confirmed = self::CONFIRMED;
        $payment_failed = self::FAILED;
        $subscribed = self::SUBSCRIBED;
        $unsubscribed = self::UNSUBSCRIBED;
        //get subscription object
        $em = $this->container->get('doctrine')->getManager();
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('shopId' => $shop_id, 'status' => $subscribed));
        if ($subscription_obj) {
            //manage start date
            $start_date = new \DateTime('now');
            $start_date_updated = $start_date;

            //manage expiry date
            $expiry_date = $subscription_obj->getExpiryDate();
            $interval_date = $subscription_obj->getIntervalDate();

            $expiry_date_updated = $expiry_date->modify('+1 month'); //adding 1 month
            $interval_date_updated = $interval_date->modify('+1 month'); //adding 1 month

            $expiry_date_updated1 = $expiry_date_updated->format('Y-m-d H:i:s');
            $expiry_date_updated2 = strtotime($expiry_date_updated1);
            $expiry_date_updated3 = new \DateTime("@$expiry_date_updated2");

            $interval_date_updated1 = $interval_date_updated->format('Y-m-d H:i:s');
            $interval_date_updated2 = strtotime($interval_date_updated1);
            $interval_date_updated3 = new \DateTime("@$interval_date_updated2");

            if ($sub_status == $payment_confirmed) {
                //if success
                $subscription_obj->setStartDate($start_date_updated);
                $subscription_obj->setExpiryDate($expiry_date_updated3);
                $subscription_obj->setIntervalDate($interval_date_updated3);
                $subscription_obj->setStatus($subscribed);
            } elseif ($sub_status == $payment_failed) {
                //if failed
                $subscription_obj->setStatus($unsubscribed);
            }

            try {
                $em->persist($subscription_obj);
                $em->flush();
            } catch (\Exception $e) {
                $this->__subscriptionLog('Error in save the RecurringShopPaymentService->updateShopSubscription' . $e->getMessage());
            }
        }
        $this->__subscriptionLog('Exit From RecurringShopPaymentService->updateShopSubscription', array());
        return true;
    }
    /**
     * Send Mail
     * @param int $profile_id
     * @param string $status
     * @return boolean
     */
    public function sendNotification($shop_id, $receiver_id, $status) {
        $this->sendEmailNotification($shop_id, $receiver_id, true, true);
    }
    
     /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($shop_id, $receiver_id, $isWeb = false, $isPush = false) {
        //$link = null;
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_profile_url');
        //send email service
        $receiver = $postService->getUserData($receiver_id, true);
        $recieverByLanguage = $postService->getUsersByLanguage($receiver);
        $emailResponse = '';
        foreach ($recieverByLanguage as $lng => $recievers) {
            $locale = $lng === 0 ? $this->container->getParameter('locale') : $lng;
            $lang_array = $this->container->getParameter($locale);
            $mail_sub = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_SUBJECT'];
            $mail_body = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_BODY'];
            $mail_text = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_TEXT'];
            $_shopUrl = $angular_app_hostname . $shop_url . '/' . $shop_id;
            $link = "<a href='$_shopUrl'>" . $lang_array['CLICK_HERE'] . "</a>";
            $mail_link_text = sprintf($lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_LINK'], $link);
            $bodyData = $mail_text . '<br /><br />' . $mail_link_text;
            $thumb_path = "";
            $emailResponse = $email_template_service->sendMail($recievers, $bodyData, $mail_body, $mail_sub, $thumb_path, 'SUBSCRIPTION');
        }

        // push and web
        $msgtype = 'SUBSCRIPTION';
        $msg = '39EURO_SHOPPING_CARD';
        $extraParams = array('store_id' => $shop_id);
        $itemId = $shop_id;
        $postService->sendUserNotifications($receiver_id, $receiver_id, $msgtype, $msg, $itemId, $isWeb, $isPush, null, 'SHOP', $extraParams, 'T');
        return true;
    }
    
    /**
     * subscription payment success logs remove(will not sent further notifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function subscriptionPaymentSuccessLogs($user_id, $shop_id) {
        $serializer = $this->container->get('serializer');
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionPaymentSuccessLogs]', array());
        $pending_payment_type = self::SUBSCRIPTION_PENDING_PAYMENT;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** get record if exists * */
        $subscription_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:SubscriptionPaymentNotificationLog')
                ->checkSubscriptionTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        if ($subscription_payment_log) { //if record exists we will remove.
            $subscription_payment_log1 = $subscription_payment_log;
            $dm->remove($subscription_payment_log);
            try {
                $dm->flush();
                $json = $serializer->serialize($subscription_payment_log1, 'json'); //convert documnt object to json string
                $this->__subscriptionLog('Removing the logs from collection [SubscriptionPaymentNotificationLog] for shop: ' . $shop_id . ' and user: ' . $user_id . ' and data:' . $json, array());
            } catch (\Exception $ex) {
                $this->__subscriptionLog('Exception for removing the record for shop: ' . $shop_id . ' and user: ' . $user_id, 'Exception is:' . $ex->getMessage());
            }
        }
        return true;
    }
    
     /**
     * Registration pending payment success logs remove(will not sent furthernotifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionRegistrationPaymentSuccessLogs($user_id, $shop_id) {
        $user_id = (int) $user_id;
        $shop_id = (int) $shop_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //check notification sent
        $notifications_sent = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->checkNotificationSent($shop_id);
        if ($notifications_sent) { //if record exists we will remove.
            $dm->remove($notifications_sent);
            try {
                $dm->flush();
            } catch (\Exception $ex) {
                 $this->__subscriptionLog('Exception in [transactionRegistrationPaymentSuccessLogs] for shop block: ' . $shop_id . ' and user id: ' . $user_id.":". $e->getMessage());
            }
        }
        return true;
    }
    
    /**
     * write the subscription logs
     * @param string $request
     * @param string $response
     */
    private function __subscriptionLog($request, $response=array()) {
        $handler = $this->container->get('monolog.logger.recurring');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        try {
            $applane_service->writeAllLogs($handler, $request, $response);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

}