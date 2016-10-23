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
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use UserManager\Sonata\UserBundle\Utils\MessageFactory as Msg;

class SubscriptionController extends Controller {

    //Subscription status
    protected $subscribed = 'SUBSCRIBED';
    protected $unsubscribed = 'UNSUBSCRIBED';
    protected $failed = 'FAILED';
    protected $cancel = 'CANCEL';
    protected $pending = 'PENDING';
    protected $initiated = 'INITIATED';
    CONST SUBSCRIPTION_PENDING_PAYMENT = "SUBSCRIPTION_PENDING_PAYMENT";
    CONST S = "S";
    CONST SHOP_SUBSCRIPTION_FEE_WAIVER = 'SHOP_SUBSCRIPTION_FEE_WAIVER';
    
    /**
     * Get subscription payment physical url
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetsubscriptionpaymenturlsAction(Request $request)
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
        //check if shop has already subscribed
        $em = $this->container->get('doctrine')->getManager();
        $subscribed_array = array($this->subscribed, $this->pending);
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->checkSubscription($shop_id, $subscribed_array);
        
        if($subscription_obj){
            $data = array('status' => $subscription_obj->getStatus(), 'txn_id' => $subscription_obj->getId());
            //return error for already subscribed
            $res_data = array('code' => 1057, 'message' => 'ALREADY_SUBSCRIBED', 'data' => $data);
            $this->returnResponse($res_data);
        }
        
        //make new subscription
        $response = $this->addSubscription($shop_id, $user_id);
        if(!$response){
            $res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }
        
        //handling for subscription waiver
        $this->subscriptionWaiver($shop_id, $user_id, $response);
        
        //check if deafult credit card added and payment internal is successful
        $response_payment = $this->makeInternalPaymant($response);
        //get subscription url
        if(!$response_payment){
        $subscription_url = $this->createSubscriptionUrl($response, $object_info);
        $data = array('url'=>$subscription_url);
        }
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }
    
    /**
     * Add new subscription
     * @param int $shop_id
     * @param int $user_id
     */
    public function addSubscription($shop_id, $user_id)
    {
        $description = "Susbcription inititated and in pending mode.";
        $time = new \DateTime("now");
        $start_time = new \DateTime("now");
        $end_time = $time->modify('+1 month');
        $status = $this->initiated;
        $transactionId = 0;
        $amount = $this->container->getParameter('subscription_fee');
        $amount = $amount / 100;
        $subscription = new ShopSubscription();
        $subscription->setSubscriberId($user_id);
        $subscription->setShopId($shop_id);
        $subscription->setDescription($description);
        $subscription->setStartDate($start_time);
        $subscription->setExpiryDate($end_time);
        $subscription->setPurchasedDate($start_time);
        $subscription->setShopOwnerId($user_id);
        $subscription->setStatus($status);
        $subscription->setTransactionId($transactionId);
        $subscription->setSubscriptionAmount($amount);
        $subscription->setIntervalDate($end_time);
        $em = $this->container->get('doctrine')->getManager();
        try{
        $em->persist($subscription);
        $em->flush();
        }catch(\Exception $e){
           return false;
        }
       return $subscription;
    }
    
    /**
     * Create subscription cartasi physical url.
     * @param array $data
     * @return string
     */
    public function createSubscriptionUrl($data, $object_info)
    {
        $vat = $this->container->getParameter('vat');
        // code for chiave 
        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');
        // code for alias
        $prod_alias = $this->container->getParameter('prod_alias');
        $payment_type_send = 'S';
        $payment_type = 'SUBSCRIPTION';
        $user_id = $data->getSubscriberId();
        $shop_id  = $data->getShopId();
        $txn_id = $data->getId();
        //$amount = $data->getSubscriptionAmount();
        $amount = (float)$this->container->getParameter('subscription_fee');
        //$amount = 1;
        $amount = (float)($amount + (($amount * $vat) / 100));
        $dec_amount = (float)sprintf("%01.2f", $amount);
        //$amount = $dec_amount * 100;
        $amount = $dec_amount;
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
        $urlpost = $this->container->getParameter('urlpost_subscription');
        $urlpost = $urlpost."?txn_id=".$txn_id;
        $urlpost = urlencode($urlpost);
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        
        //check user is already registerd for contract
        $contract_result = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findBy(array('profileId' => $shop_id), array('createTime' => 'DESC'), 1, 0);

        //code for contract number 
        if ($contract_result) {
            $pre_contract_num = $contract_result[0]->getContractNumber();
            $num_to_inc = explode('_', $pre_contract_num);
            $inc_count = (int) $num_to_inc[3] + 1;
            //$contract_number = 'shop_contract_' . $shop_id . '_' . $inc_count;
            $contract_number = 'shop_contract_' . $shop_id . '_' . time();
        } else {
            //$contract_number = 'shop_contract_' . $shop_id . '_1';
            $contract_number = 'shop_contract_' . $shop_id . '_'. time();
        }
        
        $cancel_url = $object_info->cancel_url."?txn_id=".$txn_id; 
        $cancel_url = urlencode($cancel_url);
        
        $return_url = $object_info->return_url."?txn_id=".$txn_id; 
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
        return $final_url;
    }
    
    /**
     * Create contract when user subscribed
     */
    public function createsubscriptioncontractAction()
    {
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime('now');
      /*
        //code for testing
         $_POST['num_contratto'] = "shop_contract_30041_1432648716";
       
          $_POST['alias'] = "fdg";
          $_POST['tipoTransazione'] = "india";
          $_POST['data'] = '34343434';
          $_POST['orario'] = '3455';
          $_POST['$BRAND'] ="sdf";
          $_POST['tipoProdotto'] = "register";
          $_POST['nome'] = 'pradeep';
          $_POST['cognome'] = 'kumar';
          $_POST['languageId'] = 'in';
          $_POST['pan'] = '345345345';
          $_POST['nazionalita'] = 'indian';
          $_POST['session_id'] = '1';
          $_POST['email'] = "pradeep@gmail.com";
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
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.subscription_log');
        $monolog_data = "Request: Data=>in".json_encode($_POST);
        $applane_service->writeAllLogs($handler, $monolog_data, array());  
       
      
         $subscription_id = $_GET['txn_id'];
       //test data
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

            //check contract is already exist
            $check_contract_result = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findBy(array('contractNumber' => $_POST['num_contratto']));
            if (!$check_contract_result) {
                $is_default_flag = 1;
                $contract_str = explode('_', $_POST['num_contratto']);
                $profile_id = $contract_str[2];
                $shop_name = '';
                $em = $this->getDoctrine()->getManager();
                $store_obj = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => (int) $profile_id));
                
                
                $pay_type = substr($_POST['codTrans'], -1);

                //set the other contract of this shop as not default
                $check_default_contract = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('profileId' => $profile_id, 'deleted' => 0, 'status' => 1));
                if($check_default_contract){
                    $check_default_contract->setDefaultflag(0);
                    $em->persist($check_default_contract);
                }
                
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
                $contract->setTransactionType('S');
                try{
                $em->persist($contract); //save the contract
                $em->flush();
                } catch(\Exception $e){
                    
                }
                
                //get contract id
                $contract_id = $contract->getId();
                
                //update payment transaction table
                $pay_tx_data['item_id'] = $subscription_id;
                $pay_tx_data['reason'] = 'S';
                $pay_tx_data['citizen_id'] = '';
                $pay_tx_data['shop_id'] = $profile_id;
                $pay_tx_data['payment_via'] = 'CARTASI';
                $pay_tx_data['payment_status'] = 'CONFIRMED';
                $pay_tx_data['error_code'] = '';
                $pay_tx_data['error_description'] = '';
                $pay_tx_data['transaction_reference'] =  $_POST['codTrans'];
                $pay_tx_data['transaction_value'] = $_POST['importo'];
                $pay_tx_data['vat_amount'] = '';
                $pay_tx_data['contract_id'] = $contract_id;
                $pay_tx_data['paypal_id'] = '';
                $payment_txn= $this->container->get('paypal_integration.payment_transaction');
                $payment_txn->addPaymentTransaction($pay_tx_data);
                
                //update on shop subscription table for SUBSCRIBED
                $response = $this->updateSusbcriptionStatus($subscription_id, $this->subscribed);
                
                //update on shop subscription table for conreact id
                $response_contract = $this->updateSusbcriptionContract($subscription_id, $contract_id);
                
                //update shop as subscribed
                $this->updatShopSubscription($profile_id, 1);
                
                //update on shop transaction as paid
                $this->updateShopTransaction($profile_id);
                
                //update on applane, in the case of success
                $applane_txn_id = $this->updateOnApplaneSusbcription($profile_id);
                
                //update for applane_transaction_id
                $this->updateTransactionId($applane_txn_id, $subscription_id);
                                
                //send Notification for successful subscription
                $this->sendNotification($profile_id, $response, 'SUCESS');
                exit('Yes Post');
            }
        }
              $_POST['codTrans'] = (isset($_POST['codTrans']) ? $_POST['codTrans'] : 0);
              $_POST['importo'] = (isset($_POST['importo']) ? $_POST['importo'] : 0);
              //on failure
                $pay_tx_data['item_id'] = $subscription_id;
                $pay_tx_data['reason'] = 'S';
                $pay_tx_data['citizen_id'] = '';
                $pay_tx_data['shop_id'] = '';
                $pay_tx_data['payment_via'] = 'CARTASI';
                $pay_tx_data['payment_status'] = 'FAILED';
                $pay_tx_data['error_code'] = '';
                $pay_tx_data['error_description'] = '';
                $pay_tx_data['transaction_reference'] =  $_POST['codTrans'];
                $pay_tx_data['transaction_value'] = $_POST['importo'];
                $pay_tx_data['vat_amount'] = '';
                $pay_tx_data['contract_id'] = '';
                $pay_tx_data['paypal_id'] = '';
                $payment_txn = $this->container->get('paypal_integration.payment_transaction');
                $payment_txn->addPaymentTransaction($pay_tx_data);
                
                //update on shop subscription table for failure
                $this->updateSusbcriptionStatus($subscription_id, $this->failed);
                        
                exit('Yes Post');
    }
    
    
    /**
     * Update shop subscription status
     * @param int $subscription_id
     * @param string $status
     * @return user_id
     */
    public function updateSusbcriptionStatus($subscription_id, $status)
    {
        $em = $this->getDoctrine()->getManager();
         $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('id' => $subscription_id));
         if($subscription_obj){
             $subscription_obj->setStatus($status);
             $subscription_obj->setDescription($status);
             $em->persist($subscription_obj);
             $em->flush();
         }
         
         $user_id = $subscription_obj->getSubscriberId();
         return $user_id;
    }
    
    
    /**
     * Unsubscribe the shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUnsubscribeshopsAction(Request $request)
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
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->user_id;
        $subscribed_array = array($this->subscribed, $this->pending);
        //unsubscribe
         $em = $this->getDoctrine()->getManager();
         //check contract is already exist
         $subscription_obj = $em
                    ->getRepository('CardManagementBundle:ShopSubscription')
                    //->findOneBy(array('shopId' => $shop_id, 'subscriberId' => $user_id, 'status' => $this->subscribed));
                     ->checkIfSubscribed($shop_id, $user_id, $subscribed_array);
         
         if(!$subscription_obj){
            $res_data = array('code' => 1011, 'message' => 'NO_RECORDS_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
         }
         $subscription_obj->setStatus('UNSUBSCRIBED');
         try{
         $em->persist($subscription_obj);
         $em->flush();
         //update shop as unsubscribed
         $this->updatShopSubscription($shop_id, 0);
         $this->removeSubscriptionLog($shop_id, $user_id);
         $this->updateShopTransactionSubscription($shop_id, $user_id);
         }catch(\Exception $e){
             
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
         $this->returnResponse($res_data);
    }
    
    /**
     * Cancel transaction
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUpdatesubscriptiontransactionsAction(Request $request)
    {
        $data = array();
        $shop_subscribe = 0;
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
        $required_parameter = array('txn_id', 'user_id', 'status');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
             $this->returnResponse($res_data);
        }
        
        $subscription_id = $object_info->txn_id;
        $user_id = $object_info->user_id;
        $status = $object_info->status;
        $allow_array = array('CANCEL', 'PENDING');
        if (!in_array($status, $allow_array)) {
            return array('code' => 100, 'message' => 'INVALID_TYPE', 'data' => $data);
        }
        //update on shop subscription table for failure
        $em = $this->getDoctrine()->getManager();
         $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('id' => $subscription_id, 'subscriberId' => $user_id));
         
         if(!$subscription_obj){
            $res_data = array('code' => 1011, 'message' => 'NO_RECORDS_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
         }
         $shop_id  = $subscription_obj->getShopId();
         $current_status = $subscription_obj->getStatus();
         
         //check for shop subscription status
         $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
         if($store_obj){
         $shop_subscribe = $store_obj->getIsSubscribed();
         }
         
         //if status is only in inititated mode and shop subscription status is 0
         if($current_status == $this->initiated && $shop_subscribe == 0){
         $subscription_obj->setStatus($status);
         try{
         $em->persist($subscription_obj);
         $em->flush();
         
         if($status == 'PENDING'){
             //mark the shop as Pending
             $this->updatShopSubscription($shop_id, 2);
         }
         } catch(\Exception $e){
             
         }
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
         $this->returnResponse($res_data);
    }
    
    /**
     * Update on applane for shop registration
     * @param type $shopid
     */
    public function updateOnApplaneSusbcription($shopid) {
        $applane_data['shop_id'] = $shopid;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $susbcription_id = $applane_service->onShopSubscriptionAddAction($shopid);
        return $susbcription_id;
    }
    
    /**
     * Update shop subscription
     * @param type $shop_id
     * @return boolean
     */
    public function updatShopSubscription($shop_id, $status)
    {
        $em = $this->getDoctrine()->getManager();
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if($store_obj){
           $store_obj->setIsSubscribed($status);
           try{
           $em->persist($store_obj);
           $em->flush();
           }catch(\Exception $e){
               
           }
        }
        return true;
    }
    
   /**
    * Send Mail
    * @param int $profile_id
    * @param string $status
    * @return boolean
    */
    public function sendNotification($shop_id, $receiver_id, $status)
    {
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
    public function sendEmailNotification($shop_id, $receiver_id, $isWeb=false, $isPush=false) {
       //$link = null;
       $email_template_service =  $this->container->get('email_template.service');
       $postService = $this->container->get('post_detail.service');
       $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url         = $this->container->getParameter('shop_profile_url');
       //send email service
       $receiver = $postService->getUserData($receiver_id, true);
       $recieverByLanguage = $postService->getUsersByLanguage($receiver);
        $emailResponse = '';
        foreach($recieverByLanguage as $lng=>$recievers){
            $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
            $lang_array = $this->container->getParameter($locale);
            $mail_sub = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_SUBJECT'];
            $mail_body = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_BODY'];
            $mail_text = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_TEXT'];
            $_shopUrl = $angular_app_hostname.$shop_url.'/'.$shop_id;
            $link = "<a href='$_shopUrl'>".$lang_array['CLICK_HERE']."</a>";
            $mail_link_text = sprintf($lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_LINK'], $link);
            $bodyData = $mail_text.'<br /><br />'.$mail_link_text;
            $thumb_path = "";
            $emailResponse = $email_template_service->sendMail($recievers, $bodyData, $mail_body, $mail_sub, $thumb_path, 'SUBSCRIPTION');
        }
        
        // push and web
        $msgtype = 'SUBSCRIPTION';
        $msg = '39EURO_SHOPPING_CARD';
        $extraParams = array('store_id'=>$shop_id);
        $itemId = $shop_id;
        $postService->sendUserNotifications($receiver_id, $receiver_id, $msgtype, $msg, $itemId, $isWeb, $isPush, null, 'SHOP', $extraParams, 'T');
       return true;
    }
    
    /**
     * Map applane transaction id with subscription id
     * @param string $applane_txn_id
     * @param int $subsc_id
     */
    public function updateTransactionId($applane_txn_id, $subsc_id)
    {
         $em = $this->getDoctrine()->getManager();
         $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('id' => $subsc_id));
         if($subscription_obj){
             $subscription_obj->setTransactionId($applane_txn_id);
             $em->persist($subscription_obj);
             $em->flush();
         }
         
         $user_id = $subscription_obj->getSubscriberId();
         return $user_id;
    }
    
    
    /**
     * Update shop subscription contract
     * @param int $subscription_id
     * @param int $contract_id
     * @return user_id
     */
    public function updateSusbcriptionContract($subscription_id, $contract_id)
    {
        $em = $this->getDoctrine()->getManager();
         $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('id' => $subscription_id));
         if($subscription_obj){
             $subscription_obj->setContractId($contract_id);
             $em->persist($subscription_obj);
             $em->flush();
         }
         
         $user_id = $subscription_obj->getSubscriberId();
         return true;
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
    
    /**
     * 
     * @param type $shop_id
     */
    public function makeInternalPaymant($response)
    {
        $resp = $this->payTransaction($response);
        return $resp;
    }
    
    /**
     * Subscription transaction
     * @param array $subscribed_users
     */
    public function payTransaction($subscribed_users) {
        $this->createLog('Entering In SubscriptionService->payTransaction'); //create Log
        $serializer = $this->container->get('serializer');
        $subscribe_json = $serializer->serialize($subscribed_users, 'json'); //convert documnt object to json string
        $this->createLog('SubscriptionService->payTransaction sebscribe Array' . $subscribe_json); //create Log
        $time = new \DateTime("now");
        $em = $this->getDoctrine()->getManager();
        $amount = (float) $this->container->getParameter('subscription_fee') / 100;
        $vat = $this->container->getParameter('vat');
        $total_amount_to_pay = $amount + (($amount * $vat) / 100);
        $shop_id = $subscribed_users->getShopId();
        $user_id = $subscribed_users->getSubscriberId();
        $txn_id = $subscribed_users->getId();

        //get default contract object
        $contract_obj = $this->getDefaultContract($shop_id);
        if ($contract_obj) {
            $contract_number = $contract_obj->getContractNumber();
            $contract_id = $contract_obj->getId();
            $contract_email = $contract_obj->getMail();
            $contract_expiration = $contract_obj->getExpirationPan();
            // code for chiave 
            $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');

            // code for alias
            $prod_alias = $this->container->getParameter('prod_alias');

            // code for recurring_pay_url
            $recurring_pay_url = $this->container->getParameter('recurring_pay_url');
            $pay_type = 'S';
            //code for codTrans
            $codTrans = "6THCH" . time() . $user_id . $pay_type;
            $dec_amount = sprintf("%01.2f", $total_amount_to_pay);
            $amount_to_pay = $dec_amount * 100;
            //$amount_to_pay = $dec_amount;
            $currency_code = 'EUR';
            //live
            $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$prod_payment_mac_key";
            //testing
            //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$test_payment_mac_key";
            //$amount_to_pay = 1;
            $mac = sha1($string);
            $data = array(
                'alias' => $prod_alias,
                'tipo_servizio' => 'paga_rico',
                'tipo_richiesta' => 'PR',
                'mac' => $mac,
                'divisa' => 'EUR',
                'importo' => $amount_to_pay,
                'codTrans' => $codTrans,
                'num_contratto' => $contract_number,
                'descrizione' => 'recurring payment',
                'mail' => $contract_email,
                'scadenza' => $contract_expiration
            );

            $pay_result = $this->recurringPaymentCurl($data, $recurring_pay_url);

            //maintain logger for cartasi response
            $monolog_data = "SubscriptionService->payTransaction: Data=>" . json_encode($pay_result) . " \n Url:" . $recurring_pay_url;
            $monolog_data_pay_result = json_encode($pay_result);
            $this->createLog($monolog_data, $monolog_data_pay_result); //create Log
            //end to maintain the logger

            if (!empty($pay_result)) {
                if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                //update on shop subscription table for SUBSCRIBED
                $response = $this->updateSusbcriptionStatus($txn_id, $this->subscribed);
                
                //update on shop subscription table for conreact id
                $response_contract = $this->updateSusbcriptionContract($txn_id, $contract_id);
                
                //update shop as subscribed
                $this->updatShopSubscription($shop_id, 1);
                
                 //update on shop transaction as paid
                $this->updateShopTransaction($shop_id);
                
                //update on applane, in the case of success
                $applane_txn_id = $this->updateOnApplaneSusbcription($shop_id);
                
                //update for applane_transaction_id
                $this->updateTransactionId($applane_txn_id, $txn_id);
                $txn_ref = $pay_result['RootResponse']['StoreRequest']['codTrans'];
                $txn_value = $amount_to_pay;
                //update payment transaction table
                $pay_tx_data['item_id'] = $txn_id;
                $pay_tx_data['reason'] = 'S';
                $pay_tx_data['citizen_id'] = $user_id;
                $pay_tx_data['shop_id'] = $shop_id;
                $pay_tx_data['payment_via'] = 'CARTASI';
                $pay_tx_data['payment_status'] = 'CONFIRMED';
                $pay_tx_data['error_code'] = '';
                $pay_tx_data['error_description'] = '';
                $pay_tx_data['transaction_reference'] =  $txn_ref;
                $pay_tx_data['transaction_value'] = $txn_value;
                $pay_tx_data['vat_amount'] = '';
                $pay_tx_data['contract_id'] = $contract_id;
                $pay_tx_data['paypal_id'] = '';
                $payment_txn= $this->container->get('paypal_integration.payment_transaction');
                $payment_txn->addPaymentTransaction($pay_tx_data);
                
                //send Notification for successful subscription
                $this->sendNotification($shop_id, $response, 'SUCESS');
                $this->createLog('SubscriptionController->payTransaction: Paymant Done', array()); //create Log
                return true;
                } else {
                    //code for payment failed
                    //maintain logger for cartasi response
                    $monolog_data = "SubscriptionController->payTransaction: Paymant Failed";
                    $monolog_data_pay_result = array();
                    $this->createLog($monolog_data, $monolog_data_pay_result); //create Log
                    return false;
                }
            }
        } else {
            $this->createLog('Default card not found', array()); //create Log
            return false;
        }
       return true;
    }

    /**
     * Get Default contract
     * @param int $shop_id
     * @return array
     */
    public function getDefaultContract($shop_id)
    {
          $em = $this->getDoctrine()->getManager();
          $contract_default_obj = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('profileId' => $shop_id, 'defaultflag' => 1, 'status' => 1, 'deleted' => 0));
          return $contract_default_obj;
    }
    
     /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function createLog($monolog_req = null, $monolog_response = null){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.subscription_log');
        //$monolog_data = "Entering In RecurringShopPaymentService.importshopstransaction";
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Call cartasi service
     * @param array $data
     * @param string $url
     * @return type
     */
    public function recurringPaymentCurl($data, $url) {

        $timeout = 5;
        $data_to_url = http_build_query($data);
        $data_to_post = utf8_encode($data_to_url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        //TRUE to do a regular HTTP POST.
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // make SSL checking false
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_post);
        //TRUE to return the transfer as a string of the return value
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // grab URL and pass it to the browser
        $data_response = curl_exec($ch);

        $data_response_suc = "<RootResponse>
	<StoreRequest>
		<alias>payment_31297124</alias>
		<codTrans>PRA7684653448</codTrans>
		<divisa>EUR</divisa>
		<importo>1</importo>
		<mail>yiresse.abia@gmail.com</mail>
		<scadenza>201508</scadenza>
		<pan>1233</pan>
		<cv2></cv2>
		<num_contratto>shop_contract_50004_9</num_contratto>
		<tipo_richiesta>PR</tipo_richiesta>
		<tipo_servizio>paga_rico</tipo_servizio>
		<gruppo></gruppo>
		<descrizione>recurring payment</descrizione>
	</StoreRequest>
	<StoreResponse>
		<tipoCarta>VISA</tipoCarta>
		<paese>ITA</paese>
		<tipoProdotto>ELECTRON+-+DEBIT+-+S</tipoProdotto>
		<tipoTransazione>NO_3DSECURE</tipoTransazione>
		<codiceAutorizzazione>005598</codiceAutorizzazione>
		<dataOra>20141127T163445</dataOra>
		<codiceEsito>0</codiceEsito>
		<descrizioneEsito>autorizzazione concessa</descrizioneEsito>
		<ParametriAggiuntivi></ParametriAggiuntivi>
		<mac>63c73bea18e32a8123afa4d76a7710128ca30b8d</mac>
	</StoreResponse>
</RootResponse>";

        $data_response_unsuc = "<RootResponse>
<StoreRequest>
<alias>payment_3444168</alias>
<codTrans>ASDG45345345345</codTrans>
<divisa>EUR</divisa>
<importo>1</importo>
<mail>sunil.thakur@daffodilsw.com</mail>
<scadenza>201710</scadenza>
<pan>9992</pan>
<cv2>***</cv2>
<num_contratto>test_shop_contract_test_1_2</num_contratto>
<tipo_richiesta/>
<tipo_servizio>paga_rico</tipo_servizio>
<gruppo/>
<descrizione>dummy</descrizione>
</StoreRequest>
<StoreResponse>
	<tipoCarta>MasterCard</tipoCarta>
	<codiceAutorizzazione/>
	<dataOra>20141127T152153</dataOra>
	<codiceEsito>101</codiceEsito>
	<descrizioneEsito>errore nei parametri</descrizioneEsito>
	<ParametriAggiuntivi></ParametriAggiuntivi>
	<mac>ef60bb0a66cfbbb481d7cd476f795169af671454</mac>
</StoreResponse>
</RootResponse>";

        $res = $this->xml2array($data_response);
        return $res;
        // close cURL resource, and free up systesm resources
        curl_close($ch);
    }
    
    /**
     * 
     * @param type $contents
     * @param type $get_attributes
     * @param type $priority
     * @return type
     */
    function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
        if (!$contents)
            return array();

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values)
            return; //Hmm...

            
//Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference
        //Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        foreach ($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if (isset($value)) {
                if ($priority == 'tag')
                    $result = $value;
                else
                    $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag')
                        $attributes_data[$attr] = $val;
                    else
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if ($type == "open") {//The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) or ( !in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;

                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag], $result); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;

                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                } else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }

                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return($xml_array);
    }
    
    /**
     * Remove subscription log
     * @param int $shop_id
     * @param int $user_id
     * @return boolean
     */
    private function removeSubscriptionLog($shop_id, $user_id){
        $serializer = $this->container->get('serializer');
        $this->createLog('Entering In SubscriptionService->removeSubscriptionLog'); //create Log
        $pending_payment_type = self::SUBSCRIPTION_PENDING_PAYMENT;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //get record if exist
        $subscription_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:SubscriptionPaymentNotificationLog')
                ->checkSubscriptionTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        
        if ($subscription_payment_log) { //if record exists we will remove.
            $subscription_payment_log1 = $subscription_payment_log;
            $dm->remove($subscription_payment_log);
            try {
                $dm->flush();
                $json = $serializer->serialize($subscription_payment_log1, 'json'); //convert documnt object to json string
                 $this->createLog('Entering In SubscriptionService->removeSubscriptionLog for shop: ' . $shop_id . ' and user: ' . $user_id . ' and data:' . $json, array()); //create Log
            } catch (\Exception $ex) {
                $this->createLog('Exception for removing the record for shop: ' . $shop_id . ' and user: ' . $user_id, 'Exception is:' . $ex->getMessage());
            }
        }
        return true;
    }
    
    /**
     * Update Shop Transaction for subscription
     * @param int $shop_id
     * @param int $user_id
     */
    public function updateShopTransactionSubscription($shop_id, $user_id)
    {
        $this->createLog('Entering in [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id);
        $em = $this->getDoctrine()->getManager();
        //check if invoice exist
        $check_subscription = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->findOneBy(array('shopId' => $shop_id, 'type' => 'S', 'status' => 0));
        if(!$check_subscription){
            //$res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
           // $this->returnResponse($res_data);
            $this->createLog('Exiting from [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id." With message no subscription found");
            return true;
        }
        //check subscription, Make the susbcription as 2
        $check_subscription->setStatus(2);
        try{
        $em->persist($check_subscription);
        $em->flush();
        } catch (\Exception $e){
              $this->createLog('Exception in [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id, 'Exception is:' . $e->getMessage());
        }
         $this->createLog('Exiting from [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id);
        return true;
    }
    
    /**
     * Update shop transaction table for subscription fee as paid
     * @param int $shop_id
     */
    public function updateShopTransaction($shop_id)
    {
        $this->createLog('Entering in [updateShopTransaction] for shop: ' . $shop_id);
        //get shop owner id
        $em = $this->getDoctrine()->getManager();
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id));
        if(!$store_obj){
         $this->createLog('Exiting from [updateShopTransaction] with message: No shop found');
         return false;
        }
        $time = new \DateTime('now');
        $user_id = $store_obj->getUserId(); //get store owner id
        $vat = $this->container->getParameter('vat');
        $subs_fee_shop = $this->container->getParameter('subscription_fee');
        $sub_fee = $subs_fee_shop / 100;
        $sub_fee_vat = (($sub_fee * $vat) / 100);
        $total_payable_amount = ($sub_fee + $sub_fee_vat);
        $shop_transaction = new ShopTransactions();
        $shop_transaction->setDate($time);
        $shop_transaction->setCreatedAt($time);
        $shop_transaction->setShopId($shop_id);
        $shop_transaction->setUserId($user_id);
        $shop_transaction->setTotalTransactionAmount($sub_fee);
        $shop_transaction->setPayableAmount($sub_fee);
        $shop_transaction->setInvoiceId('');
        $shop_transaction->setStatus(1);
        $shop_transaction->setType(self::S);
        $shop_transaction->setVat($sub_fee_vat);
        $shop_transaction->setTotalPayableAmount($total_payable_amount);
        $shop_transaction->setTransactionId('');
        try{
        $em->persist($shop_transaction);
        $em->flush();
        }catch(\Exception $e){
           $this->createLog('Exiting from [updateShopTransaction] with message:'.$e->getMessage()); 
        }
         $this->createLog('Exiting from [updateShopTransaction]'); 
         return true;
    }
    
    /**
     * Subscription Waivers
     * @param int $shop_id
     * @param int $user_id
     */
    public function subscriptionWaiver($shop_id, $user_id, $subscription_obj)
    {
        $this->createLog('Entering in Class[SubscriptionController] for Function [subscriptionWaiver] for shop: ' . $shop_id.' user_id: '.$user_id);
        //get subscription waiver setting
        try{
        $waiver_type = self::SHOP_SUBSCRIPTION_FEE_WAIVER;
        $waiver_value = $this->container->getParameter($waiver_type);
        }catch (Exception $ex) {
           $waiver_value = 0;
        }
        if($waiver_value == 0){
            $this->createLog('Exiting from Class[SubscriptionController] for Function [subscriptionWaiver] no waiver found');
            return true; //no setting found
        }
        $data = array();
        $waiver_service = $this->container->get('card_management.waiver');
        $date = new \DateTime("now");
        $waiver_obj = $waiver_service->getWaiverStatus(self::SHOP_SUBSCRIPTION_FEE_WAIVER, $date);
        if(!$waiver_obj){
                $this->createLog('Exiting From class [RestStoreController] function [checkSubscriptionWaiverStatus] With message: No options found for type '.self::SHOP_SUBSCRIPTION_FEE_WAIVER);
                return true; //no waiver options found
        }
        $waiver_service->checkSubscriptionWaiverStatus($shop_id); //for subscription fee waiver
        $subscription_id = $subscription_obj->getId();
        //update subscription
        $response = $this->updateSusbcriptionStatus($subscription_id, $this->subscribed);
        $this->createLog('UpdateSusbcriptionStatus for subscription id: ' . $subscription_id);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->createLog('Exiting from [updateShopTransaction] with message:'.(string)$resp_data); 
        Utility::createResponse($resp_data);
    }
}