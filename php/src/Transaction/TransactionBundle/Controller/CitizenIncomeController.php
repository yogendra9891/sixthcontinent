<?php

namespace Transaction\TransactionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use StoreManager\StoreBundle\Entity\Transactionshop;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use Acme\GiftBundle\Entity\Movimen;
use CardManagement\CardManagementBundle\Entity\Contract;
use CardManagement\CardManagementBundle\Entity\ShopRegPayment;
use Transaction\TransactionBundle\Document\RecurringPaymentLog;
use Transaction\TransactionBundle\Entity\RecurringPayment;
use Transaction\TransactionBundle\Entity\RecurringPendingPayment;
use Notification\NotificationBundle\Document\UserNotifications;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Transaction\TransactionBundle\Document\CardSoldoErrorLog;
use Transaction\TransactionBundle\Entity\UserInfoFromCardSoldo;

class CitizenIncomeController extends Controller
{
     protected $miss_param = '';
     protected $base_six = 1000000;
    
    /**
    * Functionality decoding data
    * @param json $object	
    * @return array
    */
    public function decodeData($req_obj)
    {
         //get serializer instance
         $serializer = new Serializer(array(), array(
                         'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                         'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
         ));
         $jsonContent = $serializer->decode($req_obj, 'json');
         return $jsonContent;
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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    
     /**
     * recurring payment that shop need to pay to sixthcontinent
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return boolean
     */
//    
//    public function recurringpaymentAction() {
//        // FIX: Need to fix the implementation for reduce the memory consumption
//        set_time_limit(0);
//        ini_set('memory_limit','512M');
//        $time = new \DateTime("now"); 
//        //get entity manager object
//        $em = $this->container->get('doctrine')->getManager();
//        $user_service = $this->get('user_object.service');
//        $shop_profile_url   = $this->container->getParameter('shop_profile_url'); //shop profile url
//        $shop_wallet_url   = $this->container->getParameter('shop_wallet_url'); //shop wallet url
//        //get object of email template service
//        $email_template_service =  $this->container->get('email_template.service');
//         //get object of shopping plus service
//        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
//        //get angular host
//        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');        
//        //get locale
//        $locale = $this->container->getParameter('locale');
//        $lang_array = $this->container->getParameter($locale);
//        $vat = $this->container->getParameter('vat');
//        //get entries from transaction shop
//        $transaction_result = $em
//                ->getRepository('StoreManagerStoreBundle:Transactionshop')
//                ->getYesterdayTransactionShop();
//   
//        $dm = $this->get('doctrine.odm.mongodb.document_manager');
//        $admin_id = $em
//               ->getRepository('TransactionTransactionBundle:RecurringPayment')
//               ->findByRole('ROLE_ADMIN');
//     
//        if($transaction_result) {  
//         
//           foreach($transaction_result as $record) { 
//               
//                $id = $record->getId();
//                $shop_id = $record->getUserId();
//                $transaction_shop_ids = array();
//                $transaction_shop_ids_pending = array();
//                $store_pending_ids = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                               ->findBy(array('shopId'=>$shop_id,'paid'=>0)); 
//               
//                foreach($store_pending_ids as $pending_tb_record) {
//                    $transaction_shop_ids[] = $pending_tb_record->getTransactionId();
//                }
//                
//                
//                /*check exclude the tot_quota if it is enter in pending payment table*/
//                $store_pending_ids_pending = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                               ->findBy(array('shopId'=>$shop_id)); 
//               
//                foreach($store_pending_ids_pending as $pending_tb_record_pending) {
//                    $transaction_shop_ids_pending[] = $pending_tb_record_pending->getTransactionId();
//                }
//                $transaction_ids_array_unique_pending = array_unique($transaction_shop_ids_pending);
//                
//                $transaction_ids_array_unique = array_unique($transaction_shop_ids);
//                $transaction_shop_ids[] = $id;               
//                $transaction_id = serialize(array_unique($transaction_shop_ids));
//                $update_transaction_shop_ids = array_unique($transaction_shop_ids);
//                $transaction_date = $record->getDataMovimento();
//                $data_job = $record->getDataJob();
//                $tot_dare = $record->getTotDare();
//                $tot_quota = $record->getTotQuota();
//                //get store object
//                $store_obj = $em
//                               ->getRepository('StoreManagerStoreBundle:Store')
//                               ->findOneBy(array('id' => $shop_id,'paymentStatus'=>1));
//                $store_pending_amount = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                               ->getShopPendingAmount($shop_id);                
//                
//                if(!$store_pending_amount) {
//                    $store_pending_amount = 0;
//                }
//               
//              
//                if(in_array($id,$transaction_ids_array_unique_pending)) {
//                    $total_amount_with_pending = $store_pending_amount;
//                }else{
//                    $total_amount_with_pending = $tot_quota + $store_pending_amount;
//                }
//                $pending_amount_vat_apply = 0;
//                $pending_amount_vat_apply = ($total_amount_with_pending * $vat) / 100;
//              
//                $total_amount_with_pending = $total_amount_with_pending + (($total_amount_with_pending * $vat) / 100);
//                
//                $amount_to_check = $this->converToEuro($total_amount_with_pending);
//              
//                if((count($store_obj) >0) && ($amount_to_check > 5)) {   
//                    
//                    $shop_name = $store_obj->getName();
//                    $store_object_info = $user_service->getStoreObjectService($shop_id);
//                    $store_user_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
//                           ->findOneBy(array('storeId' => $shop_id));
//                    $user_id = "";
//                    if($store_user_obj) {
//                        $user_id = $store_user_obj->getUserId();
//                    }
//                    
//                    //get contract object
//                    $contract_default_obj = $em
//                                   ->getRepository('CardManagementBundle:Contract')
//                                   ->findOneBy(array('profileId' => $shop_id,'defaultflag'=>1));
//                   
//                    if($contract_default_obj) {
//                        $contract_number = $contract_default_obj->getContractNumber();
//                        $contract_id = $contract_default_obj->getId();
//                        $contract_email = $contract_default_obj->getMail();
//                        $contract_expiration = $contract_default_obj->getExpirationPan();
//                        // code for chiave 
//                        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');      
//
//                        // code for alias
//                        $prod_alias    = $this->container->getParameter('prod_alias');
//                        
//                        // code for recurring_pay_url
//                        $recurring_pay_url    = $this->container->getParameter('recurring_pay_url');
//                        
//                        //$test_payment_mac_key = $this->container->getParameter('test_payment_mac_key');
//                        //$test_alias = $this->container->getParameter('test_alias');
//                
//                        //code for codTrans
//                        $codTrans = "6THCH" . time(). $user_id.'P';
//                        $dec_amount = sprintf("%01.2f", $amount_to_check);
//                        $amount_to_pay = $dec_amount*100;
//                        $currency_code = 'EUR';
//                        //live
//                        $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$prod_payment_mac_key";
//                        //testing
//                        //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$test_payment_mac_key";
//                        
//                                
//                                
//                        $mac = sha1($string);
//                        $data = array( 	  
//                           'alias'=>$prod_alias, 
//                           'tipo_servizio'=>'paga_rico', 
//                           'tipo_richiesta'=>'PR',
//                           'mac'=>$mac,
//                           'divisa'=>'EUR',
//                           'importo'=>$amount_to_pay,
//                           'codTrans'=>$codTrans,
//                           'num_contratto'=>$contract_number,
//                           'descrizione'=>'recurring payment',
//                           'mail' =>$contract_email,
//                           'scadenza'=>$contract_expiration
//                        );
//                       
//                        $pay_result = $this->recurringPaymentCurl($data,$recurring_pay_url);
//                   
//                        if(!empty($pay_result)) {
//                            if($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
//                                // code for payment is successfully done.
//                                $time = new \DateTime("now"); 
//                                $recurring_payment = new RecurringPayment();
//                                $recurring_payment->setShopId($shop_id);
//                                $recurring_payment->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
//                                $recurring_payment->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
//                                $recurring_payment->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
//                                $recurring_payment->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
//                                $recurring_payment->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
//                                $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
//                                $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
//                                $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
//                                $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
//                                $recurring_payment->setCreatedAt($time);
//                                $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
//                                $recurring_payment->setTransactionId($transaction_id);
//                                $recurring_payment->setAmount($total_amount_with_pending);
//                                $em->persist($recurring_payment); 
//                                $em->flush();                                
//                                $recurring_pay_id = $recurring_payment->getId();
//                                // save data in shop_reg_payment table
//                                $shop_reg_fee = new ShopRegPayment();
//                                $shop_reg_fee->setShopId($shop_id);
//                                $shop_reg_fee->setAmount($total_amount_with_pending);
//                                $shop_reg_fee->setCreatedAt($time);                    
//                                $shop_reg_fee->setStatus(1);
//                                $shop_reg_fee->setContractId($contract_id);
//                                $shop_reg_fee->setPaymentId(0);
//                                $shop_reg_fee->setRegFee(0);
//                                $shop_reg_fee->setTransactionType('P');
//                                $shop_reg_fee->setVat(0);
//                                $shop_reg_fee->setPendingAmountVat($pending_amount_vat_apply);
//                                $shop_reg_fee->setTransactionShopId($transaction_id);
//                                $shop_reg_fee->setTransactionCode($pay_result['RootResponse']['StoreRequest']['codTrans']);
//                                $shop_reg_fee->setRecurringPaymentId($recurring_pay_id);
//                                $shop_reg_fee->setMethod('recurring');
//                                $shop_reg_fee->setPendingAmount($total_amount_with_pending);
//                                $shop_reg_fee->setDescription('pending amount paid using recurring method');                    
//                                $em->persist($shop_reg_fee); 
//                                $em->flush();
//                                
//                                $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentsuccess','N');
//                                
//                                //update paid field in recurringpendingpayment
//                                $store_pending_update = $em
//                                    ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                                    ->updatePaidRecurringPayment($shop_id);
//                                $update_pay_log = $dm
//                                    ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
//                                    ->updateRecurringPaymentLog($shop_id);
//                                
//                                /*change status in transaction shop table for payment is done*/
//                                if(count($update_transaction_shop_ids) > 0) {
//                                    $update_transaction_status = $em
//                                            ->getRepository('StoreManagerStoreBundle:Transactionshop')
//                                            ->updateTransactionShopStatus($update_transaction_shop_ids);
//                                }
//                               
//                                
//                                
//                                //mail for payment done successfully
//                                $mail_sub = $lang_array['PAYMENT_SUCCESS_SUBJECT'];
//                                $mail_body = sprintf($lang_array['PAYMENT_SUCCESS_BODY'],$shop_name);
//                                if($store_object_info){
//                                    $thumb_path = $store_object_info['thumb_path'];
//                                } else{
//                                    $thumb_path = '';
//                                }
//                                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
//                                $href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
//                                $link = $email_template_service->getLinkForMail($href);
//                                
//                                $email_body_to_success = $email_template_service->EmailTemplateService($mail_body,$thumb_path,$link ,$user_id);
//                                $mail_notification = $email_template_service->sendEmailNotification($mail_sub, $admin_id, $user_id, $email_body_to_success);
//                                
//                                
//                                /* code for push notification */
//                                
//                                $push_message = sprintf($lang_array['PUSH_PAYMENT_SUCCESS_BODY'],$shop_name);
//                                $label_of_button =$lang_array['PUSH_LINK_LABEL'];
//                                $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
//                                $message_title = $lang_array['PUSH_PAYMENT_SUCCESS_TITLE'];
//                                
//                                $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
//                            }else {
//                                
//                                $time = new \DateTime("now"); 
//                                $recurring_payment = new RecurringPayment();
//                                $recurring_payment->setShopId($shop_id);
//                                $recurring_payment->setTipoCarta('');
//                                $recurring_payment->setPaese('');
//                                $recurring_payment->setTipoProdotto('');
//                                $recurring_payment->setTipoTransazione('');
//                                $recurring_payment->setCodiceAutorizzazione('');
//                                $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
//                                $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
//                                $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
//                                $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
//                                $recurring_payment->setCreatedAt($time);
//                                $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
//                                $recurring_payment->setTransactionId($transaction_id);
//                                $recurring_payment->setAmount($total_amount_with_pending);
//                                $em->persist($recurring_payment); 
//                                $em->flush();
//                                //code for saving pending payment
//                               
//                                $check_pending_payment_status = $em
//                                            ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                                           ->findOneBy(array('transactionId' => $id));
//                                if(!$check_pending_payment_status) {
//                                    $recurring_pending_payment = new RecurringPendingPayment();
//                                    $recurring_pending_payment->setPendingamount($tot_quota);
//                                    $recurring_pending_payment->setTransactionId($id);
//                                    $recurring_pending_payment->setShopId($shop_id);
//                                    $recurring_pending_payment->setType('pending');
//                                    $recurring_pending_payment->setPaid(0);
//                                    $recurring_pending_payment->setCreatedAt($time);
//                                    $em->persist($recurring_pending_payment); 
//                                    $em->flush();
//                                }
//                                
//                                // code for payment failure.
//                                $dm = $this->get('doctrine.odm.mongodb.document_manager');
//                                $paylog_object = $dm
//                                    ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
//                                    ->findOneBy(array('shop_id' => (int)$shop_id,'type'=>'A','status'=>1));
//                                
//                                if(!$paylog_object) {
//                                    $recurring_pay_log = new RecurringPaymentLog();                                
//                                    $transact_obj_json = json_encode($pay_result['RootResponse']);                                
//                                    $store_obj_to_encode = $this->getShopObjArr($shop_id);
//                                    $shop_obj_json = json_encode($store_obj_to_encode);                                
//                                    $recurring_pay_log->setTransactionObj($transact_obj_json);
//                                    $recurring_pay_log->setCreateAt($time);
//                                    $recurring_pay_log->setUpdatedAt($time);
//                                    $recurring_pay_log->setShopObj($shop_obj_json);
//                                    $recurring_pay_log->setType('A');
//                                    $recurring_pay_log->setShopId($shop_id);
//                                    $recurring_pay_log->setStatus(1);
//                                    $recurring_pay_log->setSent(0);
//                                    $recurring_pay_log->setDescription('Payment is failed');
//                                    $dm->persist($recurring_pay_log);
//                                    $dm->flush();
//                                }
//                                $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentfail','A');                                
//                                // code for payment failure.
//                                $dm = $this->get('doctrine.odm.mongodb.document_manager');
//                                $paylog_object = $dm
//                                    ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
//                                    ->findOneBy(array('shop_id' => (int)$shop_id,'type'=>'A','status'=>1));
//                                
//                                if(!$paylog_object) {
//                                    $recurring_pay_log = new RecurringPaymentLog();                                
//                                    $transact_obj_json = json_encode($pay_result['RootResponse']);                                
//                                    $store_obj_to_encode = $this->getShopObjArr($shop_id);
//                                    $shop_obj_json = json_encode($store_obj_to_encode);                                
//                                    $recurring_pay_log->setTransactionObj($transact_obj_json);
//                                    $recurring_pay_log->setCreateAt($time);
//                                    $recurring_pay_log->setUpdatedAt($time);
//                                    $recurring_pay_log->setShopObj($shop_obj_json);
//                                    $recurring_pay_log->setType('A');
//                                    $recurring_pay_log->setShopId($shop_id);
//                                    $recurring_pay_log->setStatus(1);
//                                    $recurring_pay_log->setDescription('Payment is failed');
//                                    $dm->persist($recurring_pay_log);
//                                    $dm->flush();
//                                }
//                                
//                                //email for payment failure
//                                if($store_object_info){
//                                    $thumb_path = $store_object_info['thumb_path'];
//                                } else{
//                                    $thumb_path = '';
//                                }                               
//                               
//                                $mail_sub = $lang_array['PAYMENT_FAILURE_SUBJECT'];
//                                $mail_body = sprintf($lang_array['PAYMENT_FAILURE_BODY'],$shop_name);
//                                
//                                #$href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
//                                #$link = $email_template_service->getLinkForMail($href);
//                                
//                                $mail_link = $lang_array['PAYMENT_FAILURE_LINK'];
//            
//                                $href = "<a href= '$angular_app_hostname$shop_wallet_url'>{$lang_array['CLICK_HERE']}</a>";
//                                $link = $mail_link.'<br><br>'.sprintf($lang_array['PAYMENT_FAILURE_CLICK_HERE'],$href);
//                                
//                                
//                                $email_body_to_failure = $email_template_service->EmailTemplateService($mail_body,$thumb_path,$link ,$user_id);
//                                $mail_notification = $email_template_service->sendEmailNotification($mail_sub, $admin_id, $user_id, $email_body_to_failure);
//                               
//                                /* code for push notification */
//                                
//                                $push_message = sprintf($lang_array['PUSH_PAYMENT_FAILURE_BODY'],$shop_name);
//                                $label_of_button = $lang_array['PUSH_LINK_LABEL'];
//                                $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
//                                $message_title = $lang_array['PUSH_PAYMENT_FAILURE_TITLE'];
//                                $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
//                            }
//                        }
//                       
//                    }else {
//                        
//                        // defaultcard not found for shop
//                        $check_pending_payment_status = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                              ->findOneBy(array('transactionId' => $id));
//                       
//                        if(!$check_pending_payment_status) {
//                            $recurring_pending_payment = new RecurringPendingPayment();
//                            $recurring_pending_payment->setPendingamount($tot_quota);
//                            $recurring_pending_payment->setTransactionId($id);
//                            $recurring_pending_payment->setShopId($shop_id);
//                            $recurring_pending_payment->setType('pending');
//                            $recurring_pending_payment->setPaid(0);
//                            $recurring_pending_payment->setCreatedAt($time);
//                            $em->persist($recurring_pending_payment); 
//                            $em->flush();
//                        }
//                        
//                        
//                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
//                        $paylog_object = $dm
//                                    ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
//                                    ->findOneBy(array('shop_id' => (int)$shop_id,'type'=>'A','status'=>1));
//                        if(!$paylog_object) {
//                            $recurring_pay_log = new RecurringPaymentLog(); 
//                            $store_obj_to_encode = $this->getShopObjArr($shop_id);
//                            $shop_obj_json = json_encode($store_obj_to_encode);
//                            $recurring_pay_log->setTransactionObj('');
//                            $recurring_pay_log->setCreateAt($time);
//                            $recurring_pay_log->setUpdatedAt($time);
//                            $recurring_pay_log->setShopObj($shop_obj_json);
//                            $recurring_pay_log->setType('A');
//                            $recurring_pay_log->setShopId($shop_id);
//                            $recurring_pay_log->setStatus(1);
//                            $recurring_pay_log->setSent(0);
//                            $recurring_pay_log->setDescription('contract not found for shop');
//                            $dm->persist($recurring_pay_log);
//                            $dm->flush();
//                        }
//                        $this->saveUserNotification($admin_id, $user_id, $shop_id, 'shopstatus', 'card_not_found_recurring','A');
//                        
//                         //email for payment card not found
//                        if($store_object_info){
//                            $thumb_path = $store_object_info['thumb_path'];
//                        } else{
//                            $thumb_path = '';
//                        }   
//                        // Send email for contract not found
//                        $mail_sub = $lang_array['PAYMENT_FAILURE_SUBJECT'];
//                        $mail_body = sprintf($lang_array['PAYMENT_FAILURE_BODY'],$shop_name);
//                        #$href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
//                        #$link = $email_template_service->getLinkForMail($href);
//                        
//                        $mail_link = $lang_array['PAYMENT_FAILURE_LINK'];            
//                        $href = "<a href= '$angular_app_hostname$shop_wallet_url'>{$lang_array['CLICK_HERE']}</a>";
//                        $link = $mail_link.'<br><br>'.sprintf($lang_array['PAYMENT_FAILURE_CLICK_HERE'],$href);
//                        
//                        $email_body_to_failure = $email_template_service->EmailTemplateService($mail_body,$thumb_path,$link ,$user_id);                        
//                        $mail_notification = $email_template_service->sendEmailNotification($mail_sub, $admin_id, $user_id, $email_body_to_failure);
//                        
//                        /* code for push notification */
//                        
//                        $push_message = sprintf($lang_array['PUSH_PAYMENT_FAILURE_BODY'],$shop_name);
//                        $label_of_button =$lang_array['PUSH_LINK_LABEL'];
//                        $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
//                        $message_title = $lang_array['PUSH_PAYMENT_FAILURE_TITLE'];
//                        $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
//
//                    }
//                }else {
//                  
//                    if($amount_to_check <= 5) {
//                      
//                        //code for saving pending payment
//                        $check_pending_payment_status = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                              ->findOneBy(array('transactionId' => $id));
//                        
//                        if(!$check_pending_payment_status) {
//                            $time = new \DateTime("now");
//                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
//                            $recurring_pending_payment = new RecurringPendingPayment();
//                            $recurring_pending_payment->setPendingamount($tot_quota);
//                            $recurring_pending_payment->setTransactionId($id);
//                            $recurring_pending_payment->setShopId($shop_id);
//                            $recurring_pending_payment->setType('forward');
//                            $recurring_pending_payment->setPaid(0);
//                            $recurring_pending_payment->setCreatedAt($time);
//                            $em->persist($recurring_pending_payment); 
//                            $em->flush();
//                        }
//                        
//                    } else {
//                        // defaultcard not found for shop
//                        $time = new \DateTime("now");
//                        //get entries from transaction shop
//                        $check_pending_payment_status = $em
//                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
//                              ->findOneBy(array('transactionId' => $id));
//                        if(!$check_pending_payment_status) {
//                            $recurring_pending_payment = new RecurringPendingPayment();
//                            $recurring_pending_payment->setPendingamount($tot_quota);
//                            $recurring_pending_payment->setTransactionId($id);
//                            $recurring_pending_payment->setShopId($shop_id);
//                            $recurring_pending_payment->setType('pending');
//                            $recurring_pending_payment->setPaid(0);
//                            $recurring_pending_payment->setCreatedAt($time);
//                            $em->persist($recurring_pending_payment); 
//                            $em->flush();
//                        }
//                    }
//                    continue;
//                }
//           }
//        }         
//      
//        // code for sending social and email notification to shop each after 2 days and inactive shop after 15 days.
//        $this->sendNotificationToShopEach2Days();
//        
//        return new Response('ok');
//    }
   
    
     /**
     * recurring payment that shop need to pay to sixthcontinent
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return boolean
     */
    public function recurringpaymentAction() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','512M');
        $time = new \DateTime("now"); 
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $user_service = $this->get('user_object.service');
        $shop_profile_url   = $this->container->getParameter('shop_profile_url'); //shop profile url
        $shop_wallet_url   = $this->container->getParameter('shop_wallet_url'); //shop wallet url
        //get object of email template service
        $email_template_service =  $this->container->get('email_template.service');
         //get object of shopping plus service
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        //get angular host
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); 
        
        /**get mongo db object **/
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        /** get admin id **/
        $admin_id = $em
               ->getRepository('TransactionTransactionBundle:RecurringPayment')
               ->findByRole('ROLE_ADMIN');
        
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $vat            = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');
        $reg_fee  = 0;
        $old_shop_date = new \DateTime('2014-11-14');
    
        //get entries from transaction shop
        $shop_revenue_old_shop = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getReminderShopsRevenueForOldShop($old_shop_date,300);
        $shop_revenue_new_shop = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getReminderShopsRevenueForNewShop($old_shop_date,200);
        
        $shop_revenue_merge_shop = array_merge($shop_revenue_old_shop,$shop_revenue_new_shop);
        
        if($shop_revenue_merge_shop) {
            foreach($shop_revenue_merge_shop as $shop_revenue_record) {
             
                $shop_pay_status = $shop_revenue_record['paymentStatus'];
                $shop_id = $shop_revenue_record['storeId'];
                $user_id = $shop_revenue_record['userId'];
                $shop_created_at = $shop_revenue_record['createdAt'];
                
                if($shop_created_at<$old_shop_date) {
                   $reg_fee = $reg_fee_oldshop;
                }else{
                   $reg_fee = $reg_fee_newshop;
                }
                /** get store object **/
                $shop_name = '';
                $store_obj = $em
                               ->getRepository('StoreManagerStoreBundle:Store')
                               ->findOneBy(array('id' => $shop_id));
                if($store_obj) {
                    $shop_name = $store_obj->getName();
                    $store_object_info = $user_service->getStoreObjectService($shop_id);
                }else {
                    continue;
                }
                
                /** Get data from transactionshop **/
                $pending_amount_shop = $em
                    ->getRepository('StoreManagerStoreBundle:Transactionshop')
                    ->getShopsPendingAmount($shop_id);
                $transaction_pending_pay = 0;
                $recurring_pending_amount_base_six = 0;
                $recurring_pending_vat_amount_base_six = 0;
                $recurring_pending_total_amount_base_six = 0;
                $reg_fee_base_six = 0;
                $reg_fee_vat_base_six = 0;
                $total_reg_fee_base_six = 0;
                $total_vat        = 0;
                $transaction_id_arr = array();
                if($pending_amount_shop) {
                    foreach($pending_amount_shop as $pending_amount_record) {
                        $transaction_pending_pay = $transaction_pending_pay + $pending_amount_record->getTotQuota();
                        $transaction_id_arr[] = $pending_amount_record->getId();
                    }
                }
                
                $pay_type = '';
                
                if($shop_pay_status == 0) {
                    $reg_fee_base_six = $reg_fee*10000;
                    $reg_fee_vat_base_six = $reg_fee_base_six*$vat/100;
                    $total_reg_fee_base_six = $reg_fee_base_six + $reg_fee_vat_base_six;
                }
               
                if($transaction_pending_pay > 0){
                    $recurring_pending_amount_base_six = $transaction_pending_pay;
                    $recurring_pending_vat_amount_base_six = $recurring_pending_amount_base_six*$vat/100;
                    $recurring_pending_total_amount_base_six = $recurring_pending_amount_base_six + $recurring_pending_vat_amount_base_six;
                }
                
                $pay_desc = '';
                if($transaction_pending_pay != 0 && $reg_fee_base_six != 0) {
                    $pay_type = 'T';
                    $pay_desc = 'pending and registration fee amount paid using recurring method';
                }else if($transaction_pending_pay == 0 && $reg_fee_base_six != 0) {
                    $pay_type = 'R';
                    $pay_desc = 'registration fee amount paid using recurring method';
                }else if($transaction_pending_pay != 0 && $reg_fee_base_six == 0) {
                    $pay_type = 'P';
                    $pay_desc = 'pending amount paid using recurring method';
                }
                
                
                $amount_to_check_six = $transaction_pending_pay + $reg_fee_base_six;
                $amount_to_check_euro =  $this->converToEuro($amount_to_check_six);
                
                if($amount_to_check_euro > 5) {
                    $total_vat = ($amount_to_check_euro*$vat)/100;
                    $total_amount_to_pay = $amount_to_check_euro + $total_vat;
                    $total_amount_to_pay_base_six = $this->converToBaseSix($total_amount_to_pay);
                    $transaction_serilize_id = serialize($transaction_id_arr);
                    
                    /**get contract object **/
                    $contract_default_obj = $em
                                   ->getRepository('CardManagementBundle:Contract')
                                   ->findOneBy(array('profileId' => $shop_id,'defaultflag'=>1));
                    
                    if($contract_default_obj) {
                        $contract_number = $contract_default_obj->getContractNumber();
                        $contract_id = $contract_default_obj->getId();
                        $contract_email = $contract_default_obj->getMail();
                        $contract_expiration = $contract_default_obj->getExpirationPan();
                        // code for chiave 
                        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');      

                        // code for alias
                        $prod_alias    = $this->container->getParameter('prod_alias');
                        
                        // code for recurring_pay_url
                        $recurring_pay_url    = $this->container->getParameter('recurring_pay_url');
                        
                        //code for codTrans
                        $codTrans = "6THCH" . time(). $user_id.$pay_type;
                        $dec_amount = sprintf("%01.2f", $total_amount_to_pay);
                        $amount_to_pay = $dec_amount*100;
                        $currency_code = 'EUR';
                        //live
                        $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$prod_payment_mac_key";
                        //testing
                        //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$test_payment_mac_key";
                        
                        //$amount_to_pay = 1;
                        $mac = sha1($string);
                        $data = array( 	  
                           'alias'=>$prod_alias, 
                           'tipo_servizio'=>'paga_rico', 
                           'tipo_richiesta'=>'PR',
                           'mac'=>$mac,
                           'divisa'=>'EUR',
                           'importo'=>$amount_to_pay,
                           'codTrans'=>$codTrans,
                           'num_contratto'=>$contract_number,
                           'descrizione'=>'recurring payment',
                           'mail' =>$contract_email,
                           'scadenza'=>$contract_expiration
                        );
                        $pay_result = $this->recurringPaymentCurl($data,$recurring_pay_url);
                       
                        if(!empty($pay_result)) {
                            if($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                                
                                /** saving data in recurring payment table **/
                                $time = new \DateTime("now"); 
                                $recurring_payment = new RecurringPayment();
                                $recurring_payment->setShopId($shop_id);
                                $recurring_payment->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
                                $recurring_payment->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
                                $recurring_payment->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
                                $recurring_payment->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
                                $recurring_payment->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
                                $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                                $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                                $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                                $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                                $recurring_payment->setCreatedAt($time);
                                $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                                $recurring_payment->setTransactionId($transaction_serilize_id);
                                $recurring_payment->setAmount($total_amount_to_pay_base_six);
                                $em->persist($recurring_payment); 
                                $em->flush();   
                                
                                /** get id of recurring payment table **/
                                $recurring_pay_id = $recurring_payment->getId();
                                
                                /** save data in shop_reg_payment table **/
                                $shop_reg_fee = new ShopRegPayment();
                                $shop_reg_fee->setShopId($shop_id);
                                $shop_reg_fee->setAmount($total_amount_to_pay_base_six);
                                $shop_reg_fee->setCreatedAt($time);                    
                                $shop_reg_fee->setStatus(1);
                                $shop_reg_fee->setContractId($contract_id);
                                $shop_reg_fee->setPaymentId(0);
                                $shop_reg_fee->setRegFee($reg_fee_base_six);
                                $shop_reg_fee->setTransactionType($pay_type);
                                $shop_reg_fee->setVat($reg_fee_vat_base_six);
                                $shop_reg_fee->setPendingAmountVat($recurring_pending_vat_amount_base_six);
                                $shop_reg_fee->setTransactionShopId($transaction_serilize_id);
                                $shop_reg_fee->setTransactionCode($pay_result['RootResponse']['StoreRequest']['codTrans']);
                                $shop_reg_fee->setRecurringPaymentId($recurring_pay_id);
                                $shop_reg_fee->setMethod('recurring');
                                $shop_reg_fee->setPendingAmount($recurring_pending_total_amount_base_six);
                                $shop_reg_fee->setDescription($pay_desc);                    
                                $em->persist($shop_reg_fee); 
                                $em->flush();
                               
                                /** change status in transaction shop table for payment is done **/
                                if(count($transaction_id_arr) > 0) {
                                    $update_transaction_status = $em
                                            ->getRepository('StoreManagerStoreBundle:Transactionshop')
                                            ->updateTransactionShopStatus($transaction_id_arr);
                                }
                                
                               /** update paid field in recurringpendingpayment **/
                                $store_pending_update = $em
                                    ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                                    ->updatePaidRecurringPayment($shop_id);
                                
                                /** update payment status of the shop **/
                                if($pay_type == 'R' || $pay_type == 'T') {
                                    $store_obj->setPaymentStatus(1);
                                    $em->persist($store_obj);
                                    $em->flush();
                                }
                                
                                /** remove log from billing cycle mongo db table **/
                                $delete_billing_cycle_log = $dm
                                                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                                                ->removeDeleteBillingCycleLog($shop_id, 'PENDING_PAYMENT_NOT_DONE');
                                
                                /** save notification for payment success **/
                                $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentsuccess','N');
                                
                                $receivers = $user_service->MultipleUserObjectService(array($user_id));
                                //get locale
                                $locale = !empty($receivers[$user_id]['current_language']) ? $receivers[$user_id]['current_language'] : $this->container->getParameter('locale');
                                $lang_array = $this->container->getParameter($locale);
                                                                
                                /** mail for payment done successfully **/
                                $mail_sub = $lang_array['PAYMENT_SUCCESS_SUBJECT'];
                                $mail_body = sprintf($lang_array['PAYMENT_SUCCESS_BODY'],$shop_name);
                                if($store_object_info){
                                    $thumb_path = $store_object_info['thumb_path'];
                                } else{
                                    $thumb_path = '';
                                }
                                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                                $href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
                                $link = $email_template_service->getLinkForMail($href,$locale);   
                                
                                $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'PAYMENT_NOTIFICATION');
                                
                                /* code for push notification */                                
                                $push_message = sprintf($lang_array['PUSH_PAYMENT_FAILURE_BODY'],$shop_name);
                                $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                                $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
                                $message_title = $lang_array['PUSH_PAYMENT_FAILURE_TITLE'];
                                $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
                               
                            }else {
                                
                                /** make entry in recurring pending table **/
                                if($transaction_id_arr) {
                                    foreach($transaction_id_arr as $check_transaction_id) {
                                        $check_pending_payment_status = $em
                                                    ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                                                   ->findOneBy(array('transactionId' => $check_transaction_id));
                                        $tot_quota = 0;
                                        $transaction_record = $em
                                                    ->getRepository('StoreManagerStoreBundle:Transactionshop')
                                                   ->findOneBy(array('id' => $check_transaction_id));
                                        if($transaction_record) {
                                            $tot_quota = $transaction_record->getTotQuota();
                                        }
                                        
                                        if(!$check_pending_payment_status) {
                                                $recurring_pending_payment = new RecurringPendingPayment();
                                                $recurring_pending_payment->setPendingamount($tot_quota);
                                                $recurring_pending_payment->setTransactionId($check_transaction_id);
                                                $recurring_pending_payment->setShopId($shop_id);
                                                $recurring_pending_payment->setType('pending');
                                                $recurring_pending_payment->setPaid(0);
                                                $recurring_pending_payment->setCreatedAt($time);
                                                $em->persist($recurring_pending_payment); 
                                                $em->flush();
                                        }
                                    }
                                }
                                
                                /** saving transaction failure data in reccuring payment table**/
                                $time = new \DateTime("now"); 
                                $recurring_payment = new RecurringPayment();
                                $recurring_payment->setShopId($shop_id);
                                $recurring_payment->setTipoCarta('');
                                $recurring_payment->setPaese('');
                                $recurring_payment->setTipoProdotto('');
                                $recurring_payment->setTipoTransazione('');
                                $recurring_payment->setCodiceAutorizzazione('');
                                $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                                $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                                $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                                $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                                $recurring_payment->setCreatedAt($time);
                                $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                                $recurring_payment->setTransactionId($transaction_serilize_id);
                                $recurring_payment->setAmount($total_amount_to_pay_base_six);
                                $em->persist($recurring_payment); 
                                $em->flush();
                                
                                
                                $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentfail','N'); 
                                /**  email for payment failure **/
                                if($store_object_info){
                                    $thumb_path = $store_object_info['thumb_path'];
                                } else{
                                    $thumb_path = '';
                                }                               

                                $receivers = $user_service->MultipleUserObjectService(array($user_id));
                                //get locale
                                $locale = !empty($receivers[$user_id]['current_language']) ? $receivers[$user_id]['current_language'] : $this->container->getParameter('locale');
                                $lang_array = $this->container->getParameter($locale);
            
                                
                                $mail_sub = $lang_array['PAYMENT_FAILURE_SUBJECT'];
                                $mail_body = sprintf($lang_array['PAYMENT_FAILURE_BODY'],$shop_name);


                                $mail_link = $lang_array['PAYMENT_FAILURE_LINK'];

                                $href = "<a href= '$angular_app_hostname$shop_wallet_url'>{$lang_array['CLICK_HERE']}</a>";
                                $link = $mail_link.'<br><br>'.sprintf($lang_array['PAYMENT_FAILURE_CLICK_HERE'],$href);
                                
                                $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'PAYMENT_NOTIFICATION');

                                /* code for push notification */
                                $push_message = sprintf($lang_array['PUSH_PAYMENT_FAILURE_BODY'],$shop_name);
                                $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                                $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
                                $message_title = $lang_array['PUSH_PAYMENT_FAILURE_TITLE'];
                                $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
                                
                            }
                        }
                    }
                    
                }
                
            }
        }
        
        return new Response('ok');
    }
    
    /**
     * 
     * @return boolean
     */
    public function sendNotificationToShopEach2Days() {
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $em = $this->container->get('doctrine')->getManager();
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $email_template_service = $this->container->get('email_template.service');
        $user_service = $this->get('user_object.service');
        $shop_profile_url   = $this->container->getParameter('shop_profile_url'); //shop profile url
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        $pay_log_object = $dm
                    ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
                    ->findBy(array('type'=>'A','status'=>1));
      
        $admin_id = $em
               ->getRepository('TransactionTransactionBundle:RecurringPayment')
               ->findByRole('ROLE_ADMIN');
        if($pay_log_object) {
            foreach($pay_log_object as $record) {
                $notification_id = $record->getId();
                $shop_id = $record->getShopId();
                $status = $record->getStatus();
                $sent_status = $record->getSent();
                $created_at = $record->getCreateAt();
                $check_date = strtotime($created_at->format('Y-m-d'));
                $current_date = strtotime(date('Y-m-d'));
                $datediff = $current_date - $check_date;
                $number_days = floor($datediff/(60*60*24));
                $updated_at = $record->getUpdatedAt();
                if($updated_at) {
                    $check_updated_date = strtotime($updated_at->format('Y-m-d'));
                }else {
                     $check_updated_date = strtotime($created_at->format('Y-m-d'));
                }
                
                // checking for shop id
                $store_obj = $em
                            ->getRepository('StoreManagerStoreBundle:Store')
                            ->findOneBy(array('id'=>$shop_id));
                
               
                //get store owner id
                $store_user_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                                ->findOneBy(array('storeId' => $shop_id, 'role' => 15));
                if($store_user_obj) {
                    $store_owner_id  = $store_user_obj->getUserId();
                }
                
                $store_name = "";
                $store_object_info = array();
                $current_shop_status = 1;
                
                if($store_obj) {
                    $store_name = $store_obj->getName();
                    $store_object_info = $user_service->getStoreObjectService($shop_id);
                    $current_shop_status = $store_obj->getShopStatus();
                }
                if($number_days >15) {
                    if($sent_status == 1) {
                        continue;
                    }
                    if($current_shop_status == 1){
                        // BLOCK SHOPPING PLUS
                        //$shopping_plus_obj = $this->container->get("store_manager_store.shoppingplus");
                        //deactivate the shop on shopping plus
                        //$shop_deactive_output = $shopping_plus_obj->changeStoreStatusOnShoppingPlus($shop_id,'D');

                        if($store_obj) {                        
                            $store_obj->setShopStatus(0);
                            $em->persist($store_obj);
                            $em->flush();
                        }    
                        
                        $receivers = $user_service->MultipleUserObjectService(array($store_owner_id));
                        //get locale
                        $locale = !empty($receivers[$store_owner_id]['current_language']) ? $receivers[$store_owner_id]['current_language'] : $this->container->getParameter('locale');
                        $lang_array = $this->container->getParameter($locale);
            
                        $mail_sub = $lang_array['SHOP_INACTIVE_SUBJECT'];
                        $mail_body = sprintf($lang_array['SHOP_INACTIVE_BODY'],$store_name);

                        if($store_object_info){
                            $thumb_path = $store_object_info['thumb_path'];
                        } else{
                            $thumb_path = '';
                        }
                        #$href = $angular_app_hostname.$shop_profile_url. "/".$shop_id;
                        #$link =  $email_template_service->getLinkForMail($href);
                        
                        $mail_link = sprintf($lang_array['SHOP_INACTIVE_LINK'],$store_name);            
                        $href = "<a href= '$angular_app_hostname$shop_profile_url'>{$lang_array['CLICK_HERE']}</a>";
                        $link = $mail_link.'<br><br>'.sprintf($lang_array['SHOP_INACTIVE_CLICK_HERE'],$href);
                        
                        $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'SHOP_NOTIFICATION');

                        // notification for shop active
                        $this->saveUserNotification($admin_id, $store_owner_id, $shop_id, 'shopstatus', 'shop_inactive','N');
                        $record->setSent(1);
                        $dm->persist($record);
                        $dm->flush();

                        /* code for push notification */
                        $push_message = sprintf($lang_array['PUSH_SHOP_INACTIVE_BODY'],$store_name);
                        $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                        $redirection_link = "<a href='$angular_app_hostname"."$shop_profile_url/$shop_id'>$label_of_button</a>";
                        $message_title = $lang_array['PUSH_SHOP_INACTIVE_TITLE'];
                        $curl_obj->pushNotification($store_owner_id,$push_message,$label_of_button, $redirection_link, $message_title);
                    }
                    continue;
                }else if($number_days%2 == 0 && $number_days !=0) {
                    if($check_updated_date < $current_date ){
                        
                        $receivers = $user_service->MultipleUserObjectService(array($store_owner_id));
                        //get locale
                        $locale = !empty($receivers[$store_owner_id]['current_language']) ? $receivers[$store_owner_id]['current_language'] : $this->container->getParameter('locale');
                        $lang_array = $this->container->getParameter($locale);
                        
                        $mail_sub = $lang_array['PAYMENT_NOTIFICATION_SUBJECT'];
                        $mail_body = sprintf($lang_array['PAYMENT_NOTIFICATION_BODY'],$store_name);

                         if($store_object_info){
                            $thumb_path = $store_object_info['thumb_path'];
                        } else{
                            $thumb_path = '';
                        }

                        $href = $angular_app_hostname.$shop_profile_url. "/".$shop_id;
                        $click_here = "<a href='$href'>{$lang_array['CLICK_HERE']}</a>";
                        //$link =  $email_template_service->getLinkForMail($href);
                        $link = sprintf($lang_array['PAYMENT_NOTIFICATION_LINK'],$store_name);
                        //$link .= "<br><br>$click_here";
            
                        $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'PAYMENT_NOTIFICATION');
                        
                        $this->saveUserNotification($admin_id, $store_owner_id, $shop_id, 'shopstatus', 'paymentpending','N');


                        /* code for push notification */
                        $push_message = sprintf($lang_array['PUSH_PAYMENT_NOTIFICATION_BODY'],$store_name);
                        $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                        $redirection_link = "<a href='$angular_app_hostname"."$shop_profile_url/$shop_id'>$label_of_button</a>";
                        $message_title = $lang_array['PUSH_PAYMENT_NOTIFICATION_TITLE'];
                        $curl_obj->pushNotification($store_owner_id,$push_message,$label_of_button, $redirection_link, $message_title);
                        $this->updatePendingPaymentLog($notification_id); //update pending payment log
                    }                   
 
                    continue;
                }
            }
        }
        return true;
    }
    
    /**
     * 
     * @param type $notification_id
     * @return boolean
     */
    public function updatePendingPaymentLog($notification_id){
        // get document manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $time = new \DateTime("now");
        $notifications = $dm
                ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
                ->findOneBy(array('id' => $notification_id));
        if ($notifications) {
            $notifications->setUpdatedAt($time);
            $dm->persist($notifications);
            $dm->flush();
        }
        return true;
    }
    
    /**
     * 
     * @return timestamp
     */
    public function getDateToShopTransaction() {
        return strtotime('today');
    }
     /**
     * citizen income distribution
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return boolean
     */
    public function citizenincomeAction() {
        
        $yesterday_date_obj   = new \DateTime('yesterday');
        $yesterday_date   = $yesterday_date_obj->format('Y-m-d'); 
        
        // get x% value
        $ci_percentage = 6;
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //check user is already registerd
        $transaction_result = $em
                ->getRepository('AcmeGiftBundle:Movimen')
                ->getYesterdayTransaction($yesterday_date);
        if($transaction_result) {
           foreach($transaction_result as $record) {
               $id = $record->getId();
               $IDMOVIMENTO = $record->getIDMOVIMENTO();
               $IDCARD = $record->getIDCARD();
               $IDPDV = $record->getIDPDV();
               $IMPORTODIGITATO = $record->getIMPORTODIGITATO();
               $CREDITOSTORNATO = $record->getCREDITOSTORNATO();
               $DATA = $record->getDATA();
               $RCUTI = $record->getRCUTI();
               $SHUTI = $record->getSHUTI();
               $PSUTI = $record->getPSUTI();
               $GCUTI = $record->getGCUTI();
               $GCRIM = $record->getGCRIM();
               $MOUTI = $record->getMOUTI();
           }
        } else {
            echo 'no';
        }
    }
    
    public function recurringPaymentCurl($data,$url) {
        
        $timeout = 5;
        $data_to_url = http_build_query($data); 
        $data_to_post = utf8_encode($data_to_url);
        $ch = curl_init();

        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
        $res = $this->xml2array($data_response);
        return $res;
	// close cURL resource, and free up systesm resources
	curl_close($ch);
    }
    
    function xml2array($contents, $get_attributes=1, $priority = 'tag') {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
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

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference

        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];

                } else { //There was another element with the same tag name

                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }

                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;

                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }  
    
    /**
     * 
     * @param type $shop_id
     * return array
     */
    public function getShopObjArr($shop_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $store_obj_arr = array(); 
        //get store object
        $store_obj = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $shop_id));
        if($store_obj) {
            $store_obj_arr = array(
                'id'=>$store_obj->getId(),
                'parentStoreId'=>$store_obj->getParentStoreId(),
                'email'=>$store_obj->getEmail(),
                'description'=>$store_obj->getDescription(),
                'phone'=>$store_obj->getPhone(),
                'businessName'=>$store_obj->getBusinessName(),
                'legalStatus'=>$store_obj->getLegalStatus(),
                'businessType'=>$store_obj->getBusinessType(),
                'paymentStatus'=>$store_obj->getPaymentStatus(),
                'businessCountry'=>$store_obj->getBusinessCountry(),
                'businessRegion'=>$store_obj->getBusinessRegion(),
                'businessCity'=>$store_obj->getBusinessCity(),
                'businessAddress'=>$store_obj->getBusinessAddress(),
                'zip'=>$store_obj->getZip(),
                'province'=>$store_obj->getProvince(),
                'vatNumber'=>$store_obj->getVatNumber(),
                'iban'=>$store_obj->getIban(),
                'mapPlace'=>$store_obj->getMapPlace(),
                'latitude'=>$store_obj->getLatitude(),
                'longitude'=>$store_obj->getLongitude(),
                'name'=>$store_obj->getName(),
                'storeImage'=>$store_obj->getStoreImage(),
                'createdAt'=>$store_obj->getCreatedAt(),
                'isActive'=>$store_obj->getIsActive(),
                'isAllowed'=>$store_obj->getIsAllowed()
            );
        }
        return $store_obj_arr;
    }
    
    /**
    * Save user notification
    * @param int $user_id
    * @param int $fid
    * @param string $msgtype
    * @param string $msg
    *@param string $msg_type 
    * @return boolean
    */
    public function saveUserNotification($user_id, $sender_id, $item_id, $msgtype, $msg,$msg_type){
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($sender_id);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($item_id);
        $notification->setMessageStatus($msg_type);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }
   /**
     * send email for notification on activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub,$from_id,$to_id,$mail_body){
        $userManager = $this->getUserManager();
        $to_user = $userManager->findUserBy(array('id' => (int)$to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $notification_msg = \Swift_Message::newInstance()
            ->setSubject($mail_sub)
            ->setFrom($sixthcontinent_admin_email)
            ->setTo(array($to_user->getEmail()))
            ->setBody($mail_body, 'text/html');
        
        if($this->container->get('mailer')->send($notification_msg)){            
            return true;
        }else{
            return false;
        }
    }
    
    /**
    * Call api/deletemessage action
    * @param Request $request	
    * @return array
    */
    public function postOnclickrecurringpaymentsAction(Request $request)
    {
        
        //initilise the data array
        $data = array();        
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $email_template_service = $this->container->get('email_template.service');
        $user_service = $this->get('user_object.service');
        $shop_profile_url   = $this->container->getParameter('shop_profile_url'); //shop profile url
        $shop_wallet_url   = $this->container->getParameter('shop_wallet_url'); //shop wallet url
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $vat = $this->container->getParameter('vat');
        
        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $shop_id = $de_serialize['store_id'];
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //get doctrin odm object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        //get store object
        $store_obj = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => (int) $shop_id));
        $store_payment_status = '';
        $store_name = '';
        if($store_obj) {
            $store_payment_status = $store_obj->getPaymentStatus();
            $store_name = $store_obj->getName();
        }
        $vat            = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');
        $reg_fee = 0;
        $old_shop_date = new \DateTime('2014-11-14');
        $shop_created_at = $store_obj->getCreatedAt();
        
        if($shop_created_at<$old_shop_date) {
            $reg_fee = $reg_fee_oldshop;
         }else{
            $reg_fee = $reg_fee_newshop;
         }
        
        $reg_fee_flag   = 0;
        $reg_vat_amount = 0;
        $reg_amount     = 0;
        $real_vat = (($reg_fee*$vat)/100)/100;
        if($store_payment_status == 0) {
            $reg_fee_flag = 1;
            $reg_vat_amount = ($reg_fee + (($reg_fee*$vat)/100))/100;
            $reg_amount = $reg_fee/100;
        }
        //get entries from transaction shop
        $store_pending_amount = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getShopPendingAmount($shop_id);                
        //get entries from transaction shop
        $get_transaction_id = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getTransactionIdForShop($shop_id); 
        $transaction_id_arr = array();
        foreach($get_transaction_id as $record) {
            $transaction_id_arr[] = $record['transactionId'];
        }
        $transaction_id = serialize(array_unique($transaction_id_arr));
        $update_transaction_shop_ids = array_unique($transaction_id_arr);
        $admin_id = $em
               ->getRepository('TransactionTransactionBundle:RecurringPayment')
               ->findByRole('ROLE_ADMIN');        
        if(!$store_pending_amount) {
            $store_pending_amount = 0;
        }
        $tot_quota = $store_pending_amount;
        $amount_to_check = $this->converToEuro($store_pending_amount);
        if($amount_to_check > 5 ||  $reg_fee_flag == 1) {
            $store_user_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                           ->findOneBy(array('storeId' => $shop_id));
            $user_id = "";
            $store_object_info = array();
            $current_shop_status = '';
            if($store_user_obj) {
                $user_id = $store_user_obj->getUserId();              
                $store_object_info = $user_service->getStoreObjectService($shop_id);
            }
            
            //get contract object
            $contract_default_obj = $em
                           ->getRepository('CardManagementBundle:Contract')
                           ->findOneBy(array('profileId' => $shop_id,'defaultflag'=>1));
           
            if($contract_default_obj) {
                $contract_number = $contract_default_obj->getContractNumber();
                $contract_id = $contract_default_obj->getId();
                $contract_email = $contract_default_obj->getMail();
                $contract_expiration = $contract_default_obj->getExpirationPan();
                // code for chiave 
                $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');      

                // code for alias
                $prod_alias    = $this->container->getParameter('prod_alias');

                // code for recurring_pay_url
                $recurring_pay_url    = $this->container->getParameter('recurring_pay_url');
                $pay_type = '';
                
                //variable to save
                $reg_fee_save = 0;
                $reg_fee_vat = 0;
                $amount_fee_total = 0;
                $reg_fee_pending = 0;
                $description = '';
                
                if($reg_fee_flag == 1 && $amount_to_check>5) {
                    $vat_amount =  (($amount_to_check * $vat) / 100);
                    $amount_to_check = $amount_to_check + $vat_amount;
                    $amount_to_pay = $amount_to_check + $reg_vat_amount;
                    $pay_type = 'T';
                    $description = 'pending + registration fee paid using recurring';
                    $reg_fee_save = $this->converToBaseSix($reg_amount);
                    $reg_fee_vat = $this->converToBaseSix($real_vat);
                    $amount_fee_total = $this->converToBaseSix($amount_to_pay);
                    $reg_fee_pending = $this->converToBaseSix($amount_to_check);
                }else if($reg_fee_flag == 1 && $amount_to_check<5) {
                    $transaction_id = serialize(array());
                    $update_transaction_shop_ids = array();
                    $amount_to_pay = $reg_vat_amount;
                    $pay_type = 'R';
                    $description = 'registration fee paid using recurring';
                    $vat_amount = 0;
                    $reg_fee_save = $this->converToBaseSix($reg_amount);
                    $reg_fee_vat = $this->converToBaseSix($real_vat);
                    $amount_fee_total = $this->converToBaseSix($amount_to_pay);
                    $reg_fee_pending = 0;
                }else if($reg_fee_flag == 0 && $amount_to_check>5){
                    $vat_amount =  (($amount_to_check * $vat) / 100);
                    $amount_to_check = $amount_to_check + $vat_amount;
                    $amount_to_pay = $amount_to_check;
                    $pay_type = 'P';
                    $description = 'pending fee paid using recurring';
                    $reg_fee_save = 0;
                    $reg_fee_vat = 0;
                    $amount_fee_total = $this->converToBaseSix($amount_to_pay);
                    $reg_fee_pending = $this->converToBaseSix($amount_to_pay);
                }
                $pending_amount_vat_apply = 0;
                if($vat_amount >0){
                    $pending_amount_vat_apply = $this->converToBaseSix($vat_amount);
                }
                
                $dec_amount = sprintf("%01.2f", $amount_to_pay);
                $amount_to_pay = $dec_amount*100;
                 //for test fix
               //$amount_to_pay = 1;
                //code for codTrans
                $codTrans = "6THCH" . time().$user_id.$pay_type;
                $currency_code = 'EUR';
                //$test_payment_mac_key = $this->container->getParameter('test_payment_mac_key');
                //$test_alias = $this->container->getParameter('test_alias');
                // for live
                $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$prod_payment_mac_key";
                $mac = sha1($string);
                $data_pay = array( 	  
                   'alias'=>$prod_alias, 
                   'tipo_servizio'=>'paga_rico', 
                   'tipo_richiesta'=>'PR',
                   'mac'=>$mac,
                   'divisa'=>'EUR',
                   'importo'=>$amount_to_pay,
                   'codTrans'=>$codTrans,
                   'num_contratto'=>$contract_number,
                   'descrizione'=>'recurring payment',
                   'mail' =>$contract_email,
                   'scadenza'=>$contract_expiration
                );
                $pay_result = $this->recurringPaymentCurl($data_pay,$recurring_pay_url);
             
                if(!empty($pay_result)) {
                    if($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                        // code for payment is successfully done.
                        $time = new \DateTime("now"); 
                        $recurring_payment = new RecurringPayment();
                        $recurring_payment->setShopId($shop_id);
                        $recurring_payment->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
                        $recurring_payment->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
                        $recurring_payment->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
                        $recurring_payment->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
                        $recurring_payment->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
                        $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $recurring_payment->setCreatedAt($time);
                        $recurring_payment->setTransactionId($transaction_id);
                        $recurring_payment->setAmount($amount_fee_total);
                        $em->persist($recurring_payment); 
                        $em->flush();
                        $recurring_pay_id = $recurring_payment->getId();
                        // save data in shop_reg_payment table
                        $shop_reg_fee = new ShopRegPayment();
                        $shop_reg_fee->setShopId($shop_id);
                        $shop_reg_fee->setAmount($amount_fee_total);
                        $shop_reg_fee->setCreatedAt($time);                    
                        $shop_reg_fee->setStatus(1);
                        $shop_reg_fee->setContractId($contract_id);
                        $shop_reg_fee->setPaymentId(0);
                        $shop_reg_fee->setPendingAmountVat($pending_amount_vat_apply);
                        $shop_reg_fee->setRegFee($reg_fee_save);
                        $shop_reg_fee->setTransactionType($pay_type);
                        $shop_reg_fee->setVat($reg_fee_vat);
                        $shop_reg_fee->setTransactionShopId($transaction_id);
                        $shop_reg_fee->setTransactionCode($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_reg_fee->setRecurringPaymentId($recurring_pay_id);
                        $shop_reg_fee->setMethod('recurring');
                        $shop_reg_fee->setPendingAmount($reg_fee_pending);
                        $shop_reg_fee->setDescription($description);                    
                        $em->persist($shop_reg_fee); 
                        $em->flush();
                        
                        $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentsuccess','N');

                        //update paid field in recurringpendingpayment
                        if(count($update_transaction_shop_ids) > 0) {
                            $store_pending_update = $em
                            ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                            ->updatePaidRecurringPayment($shop_id);
                        }
                        
                        /** remove log from billing cycle mongo db table **/
                        $delete_billing_cycle_log = $dm
                                        ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                                        ->removeDeleteBillingCycleLog($shop_id, 'PENDING_PAYMENT_NOT_DONE');
                        
                        /*
                        $update_pay_log = $dm
                                ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
                                ->updateRecurringPaymentLog($shop_id);
                        */
                        /*change status in transaction shop table for payment is done*/
                        if(count($update_transaction_shop_ids) > 0) {
                            $update_transaction_status = $em
                                    ->getRepository('StoreManagerStoreBundle:Transactionshop')
                                    ->updateTransactionShopStatus($update_transaction_shop_ids);
                        }
                       
                        $receivers = $user_service->MultipleUserObjectService(array($user_id));
                        //get locale
                        $locale = !empty($receivers[$user_id]['current_language']) ? $receivers[$user_id]['current_language'] : $this->container->getParameter('locale');
                        $lang_array = $this->container->getParameter($locale);
                                    
                        $mail_sub = $lang_array['PAYMENT_SUCCESS_SUBJECT'];
                        $mail_body = sprintf($lang_array['PAYMENT_SUCCESS_BODY'],$store_name);
                        
                        if($store_object_info){
                            $thumb_path = $store_object_info['thumb_path'];
                        } else{
                            $thumb_path = '';
                        }
                        $href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
                        $link = $email_template_service->getLinkForMail($href,$locale);
                        
                        $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'PAYMENT_NOTIFICATION');
                        
                        /* code for push notification */
                        $push_message = sprintf($lang_array['PUSH_PAYMENT_SUCCESS_BODY'],$store_name);
                        $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                        $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
                        $message_title = $lang_array['PUSH_PAYMENT_SUCCESS_TITLE'];
                        $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
                        
                        //activate the shop
                        // code for sending social notification of shop activation
                        $store_obj->setShopStatus(1);
                        // set registration fee recieved
                        if($reg_fee_flag == 1) {
                            $store_obj->setPaymentStatus(1);
                        }
                        
                        $em->persist($store_obj);
                        $em->flush();
                        $shop_name = $store_obj->getName();
                        $current_shop_status = $store_object_info['shop_status'];
                        if($current_shop_status == 0) {
                            
                            $receivers = $user_service->MultipleUserObjectService(array($to_id));
                            //get locale
                            $locale = !empty($receivers[$to_id]['current_language']) ? $receivers[$to_id]['current_language'] : $this->container->getParameter('locale');
                            $lang_array = $this->container->getParameter($locale);
                            
                            $mail_sub = $lang_array['SHOP_ACTIVE_SUBJECT'];
                            $mail_body = sprintf($lang_array['SHOP_ACTIVE_BODY'],$store_name);
                            if($store_object_info){
                                $thumb_path = $store_object_info['thumb_path'];
                            } else{
                                $thumb_path = '';
                            }
                            $href = $angular_app_hostname.$shop_profile_url. "/".$shop_id;
                            $link =  $email_template_service->getLinkForMail($href,$locale);                            

                            $from_id = $admin_id;
                            $to_id = $user_id;
                            $dm = $this->get('doctrine.odm.mongodb.document_manager');

                            // notification for shop active
                            $this->saveUserNotification($admin_id, $user_id, $shop_id, 'shopstatus', 'shop_active','N');            
                            // code for sending email notification of shop activation
          
                            $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'SHOP_NOTIFICATION');
                  // BLOCK SHOPPING PLUS      
//                            $shopping_plus_obj = $this->container->get("store_manager_store.shoppingplus");
//                            //activate the shop on shopping plus
//                            $shop_deactive_output = $shopping_plus_obj->changeStoreStatusOnShoppingPlus($shop_id,'A');

                            /* code for push notification */
                            $push_message_shop = sprintf($lang_array['PUSH_SHOP_ACTIVE_BODY'],$store_name);
                            $label_of_button_shop = $lang_array['PUSH_LINK_LABEL'];
                            $redirection_link_shop = "<a href='$angular_app_hostname"."$shop_profile_url/$shop_id'>$label_of_button_shop</a>";
                            $message_title_shop = $lang_array['PUSH_SHOP_ACTIVE_TITLE'];
                            $curl_obj->pushNotification($user_id,$push_message_shop,$label_of_button_shop, $redirection_link_shop, $message_title_shop);
                        }
                        
                        
                        $res_data = array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                        echo json_encode($res_data);
                        exit(); 
                    }else {

                        $time = new \DateTime("now"); 
                        $recurring_payment = new RecurringPayment();
                        $recurring_payment->setShopId($shop_id);
                        $recurring_payment->setTipoCarta('');
                        $recurring_payment->setPaese('');
                        $recurring_payment->setTipoProdotto('');
                        $recurring_payment->setTipoTransazione('');
                        $recurring_payment->setCodiceAutorizzazione('');
                        $recurring_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $recurring_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $recurring_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $recurring_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $recurring_payment->setCreatedAt($time);
                        $recurring_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $recurring_payment->setTransactionId($transaction_id);
                        $recurring_payment->setAmount($amount_fee_total);
                        $em->persist($recurring_payment); 
                        $em->flush();                       

                        $this->saveUserNotification($admin_id, $user_id, $recurring_payment->getId(), 'recurringpayment', 'paymentfail','N');
                        // code for payment failure.
                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        
                        /* comment the code enter log entry not required at this time
                        $paylog_object = $dm
                            ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
                            ->findOneBy(array('shop_id' => (int)$shop_id,'type'=>'A','status'=>1));
                        
                        if(!$paylog_object) {
                            $recurring_pay_log = new RecurringPaymentLog();
                            $transact_obj_json = json_encode($pay_result['RootResponse']);
                            $store_obj_to_encode = $this->getShopObjArr($shop_id);
                            $shop_obj_json = json_encode($store_obj_to_encode);
                            $recurring_pay_log->setTransactionObj($transact_obj_json);
                            $recurring_pay_log->setCreateAt($time);
                            $recurring_pay_log->setShopObj($shop_obj_json);
                            $recurring_pay_log->setShopId($shop_id);
                            $recurring_pay_log->setType('A');
                            $recurring_pay_log->setStatus(1);
                            $recurring_pay_log->setSent(0);
                            $recurring_pay_log->setDescription('Payment is failed');
                            $dm->persist($recurring_pay_log);
                            $dm->flush();
                        }
                        */
                        
                        $receivers = $user_service->MultipleUserObjectService(array($user_id));
                        //get locale
                        $locale = !empty($receivers[$user_id]['current_language']) ? $receivers[$user_id]['current_language'] : $this->container->getParameter('locale');
                        $lang_array = $this->container->getParameter($locale);
            
                        
                        $mail_sub = $lang_array['PAYMENT_FAILURE_SUBJECT'];
                        $mail_body = sprintf($lang_array['PAYMENT_FAILURE_BODY'],$store_name);
                        
                        if($store_object_info){
                            $thumb_path = $store_object_info['thumb_path'];
                        } else{
                            $thumb_path = '';
                        }
                        #$href = $angular_app_hostname.$shop_wallet_url. "/".$shop_id;
                        #$link = $email_template_service->getLinkForMail($href);
                        
                        $mail_link = $lang_array['PAYMENT_FAILURE_LINK'];
            
                        $href = "<a href= '$angular_app_hostname$shop_wallet_url'>{$lang_array['CLICK_HERE']}</a>";
                        $link = $mail_link.'<br><br>'.sprintf($lang_array['PAYMENT_FAILURE_CLICK_HERE'],$href);
                        
                        $mail_notification = $email_template_service->sendMail($receivers, $link, $mail_body, $mail_sub, $thumb_path, 'PAYMENT_NOTIFICATION');
                        
                        /* code for push notification */                                
                        $push_message = sprintf($lang_array['PUSH_PAYMENT_FAILURE_BODY'],$store_name);
                        $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                        $redirection_link = "<a href='$angular_app_hostname"."$shop_wallet_url/$shop_id'>$label_of_button</a>";
                        $message_title = $lang_array['PUSH_PAYMENT_FAILURE_TITLE'];
                        $curl_obj->pushNotification($user_id,$push_message,$label_of_button, $redirection_link, $message_title);
                        
                        $res_data = array('code'=>'253', 'message'=>'PAYMENT_FAILURE','data'=>$data);
                        echo json_encode($res_data);
                        exit(); 
                    }
                }

            } else {
                $res_data = array('code'=>'252', 'message'=>'CONTRACT_NOT_FOUND','data'=>$data);
                echo json_encode($res_data);
                exit(); 
            }
        } else {
            $res_data = array('code'=>'251', 'message'=>'AMOUNT_LESS_THAN_MINIMUM_AMOUNT','data'=>$data);
            echo json_encode($res_data);
            exit(); 
        }
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
    
    
    
    public function postGetcifromcardsoldosAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','1024M');
          //initilise the array
        $data = array();
        $time  = new \DateTime("now");
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
        $required_parameter = array('limit_start','limit_size');
        $data = array();
        $limit_start = $object_info->limit_start;
        $limit_size = $object_info->limit_size;
        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
        
       $em = $this->getDoctrine()->getManager();
       $users_results = $em
                            ->getRepository('TransactionTransactionBundle:UserCitizenIncome')
                            ->getAllUsers($limit_start,$limit_size); 

           foreach($users_results as $users_result) {  
          $user_id = $users_result['id'];
          $param_array = array('idcard' => $user_id);
          $params = json_encode($param_array);
          $request = new Request();
          //$params = '{"idcard":"12350"}';
          $request->attributes->set('reqObj', $params);
          $shopping_plus_controller = new ShoppingplusController();
          $response = $shopping_plus_controller->cardsoldsinternalAction($request);
          $user_info_cardsolds = $em
                            ->getRepository('TransactionTransactionBundle:UserInfoFromCardSoldo')
                            ->findBy(array('userId' => $user_id));
          $card_info = $response['data']; 
          if($response['code'] == 101) {
            if(count($user_info_cardsolds) > 0) {
             $user_info = $user_info_cardsolds[0];
             $user_info->setDescrizione($card_info['descrizione']);
             $user_info->setSaldoc($card_info['saldoc']);
             $user_info->setSaldorc($card_info['saldorc']);
             $user_info->setSaldorm($card_info['saldorm']);
             $user_info->setUpdatedAt($time);
             $em->persist($user_info);
             $em->flush();
            } else {
                $user_info = new UserInfoFromCardSoldo();
                $user_info->setUserId($user_id);
                $user_info->setDescrizione($card_info['descrizione']);
                $user_info->setSaldoc($card_info['saldoc']);
                $user_info->setSaldorc($card_info['saldorc']);
                $user_info->setSaldorm($card_info['saldorm']);
                $user_info->setCreatedAt($time);
                $user_info->setUpdatedAt($time);
                $em->persist($user_info);
                $em->flush();
            }
          } else {
             $dm = $this->get('doctrine.odm.mongodb.document_manager');
             $error_log = new CardSoldoErrorLog();
             $error_log->setUserId($user_id);
//             $error_log->setStato($card_info['stato']);
//             $error_log->setDescrizione($card_info['descrizione']);
//             $error_log->setSaldoc($card_info['saldoc']);
//             $error_log->setSaldorc($card_info['saldorc']);
//             $error_log->setSaldorm($card_info['saldorm']);
             $error_log->setCreatedAt($time);
             $dm->persist($error_log);
             $dm->flush();
          }
        }
        echo "sucess";
        die; 
    }
    
    
    
    
    
}