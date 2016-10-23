<?php

namespace Paypal\PaypalIntegrationBundle\Services;

use Doctrine\ORM\EntityManager;
use Paypal\PaypalIntegrationBundle\Entity\PaymentTransaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\UtilityBundle\Utils\Utility;
use Paypal\PaypalIntegrationBundle\Model\PaypalConstentInterface;

// verify the paypal transaction status
class PaypalTransactionVerifyService {

    protected $em;
    protected $dm;
    protected $container;

    CONST PAY_KEY = 'pay_key';
    CONST STATUS = 'status';
    CONST INSTANT_CI_PAYBACK = 'INSTANT_CI_PAYBACK';
    
    private $valid_paypal_payers = array('PRIMARYRECEIVER','EACHRECEIVER','SECONDARYONLY');

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
     * check the paypal transaction status and update the same in local system and in transaction system
     */
    public function verifyPaypalTransactionStatus() {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [verifyPaypalTransactionStatus]');
        $buyEcommerceProductService = $this->container->get('buy_ecommerce_product.ecommerce');
        $em = $this->em;
        $paypal_sender_transaction_id = '';
        $paypal_reciver_transaction_id = '';
        $sender_paypal_email = '';
        $handler         = $this->container->get('monolog.logger.paypal_shopping_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //find all the pending paypal shopping cards status
        $transactions = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                           ->findBy(array("reason" => array(ApplaneConstentInterface::OFFER_REASON,ApplaneConstentInterface::INSTANT_CI_REASON, ApplaneConstentInterface::ECOMMERCE_TYPE), "paymentStatus" => ApplaneConstentInterface::PENDING, 'paymentVia' => ApplaneConstentInterface::TRANSACTION_VIA_PAYPAL));
        //get object of the transaction check service
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        foreach ($transactions as $payment_transaction_record) {
            $id  = $payment_transaction_record->getId();
            $this->writePaypalLogs('Request is intialize for check the status on paypal of table [PaymentTransaction] with id:' . $id);
            $current_status = $payment_transaction_record->getPaymentStatus();
            $pay_key        = $payment_transaction_record->getTransactionReference();
            $is_local_update = 0;
            $transaction_id  = $payment_transaction_record->getItemId();
            $user_id    = $payment_transaction_record->getCitizenId();
            $card_value = $payment_transaction_record->getTransactionValue();
            $shop_id    = $payment_transaction_record->getShopId();
            $transaction_reason = $payment_transaction_record->getReason();
            $product_name = $payment_transaction_record->getProductName();
            $order_id = $payment_transaction_record->getOrderId();
            //get shop object.
            $shop_object_service = $this->container->get('user_object.service');
            $shop_info = $shop_object_service->getStoreObjectService($shop_id);

            //get shop owner id
            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                            ->findOneBy(array('storeId' => $shop_id, 'role' => 15)); //for finding the store owner id.
            if (!$store_obj) { //check shop is exists.
                $this->writePaypalLogs('Exiting with message: SHOP_USER_RELATION_DOES_NOT_EXISTS in table [UserToStore] with shopId:' . $shop_id);
                continue;
            }
            $shop_owner_id = $store_obj->getuserId();

            if (Utility::matchString($current_status, ApplaneConstentInterface::PENDING) && Utility::matchString($transaction_reason, ApplaneConstentInterface::OFFER_REASON)) { //compare the string and check the status (if our syatem status is pending)
                $result = $this->checkTransactionStatus($pay_key);
                $status = (isset($result->status) ? $result->status : ''); //get paypal response status
                $this->writePaypalLogs('Paypal status for normal transaction is coming: '. $status);
                $result_constant = $this->prepareconstant(); //prepare the constant  array
                if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::COMPLETED) || ($status == ApplaneConstentInterface::IN_COMPLETE))) {
                    $is_local_update = 1;
                    //$this->sendEmailPushNotification($shop_id, $user_id, true, true, $card_value, $shop_info, $shop_owner_id, ApplaneConstentInterface::BUY); //for user
                    $this->writePaypalLogs('Calliing the CI return code from the PaypalTransactionStatusCommand:shop_id'.$shop_id);
                    $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                    $paypal_transaction_service->sendMailNotificationBuyUPTO100($shop_id,$transaction_id,$user_id);
                    //$this->sendEmailPushNotification($shop_id, $shop_owner_id, true, true, $card_value, $shop_info, $user_id, ApplaneConstentInterface::SALE); //for shop owner
                } else if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED) || ($status == ApplaneConstentInterface::EXPIRED))) {
                    $is_local_update = 1;
                }
            } else if (Utility::matchString($current_status, ApplaneConstentInterface::PENDING) && Utility::matchString($transaction_reason, ApplaneConstentInterface::INSTANT_CI_REASON)) { //compare the string and check the status (if our syatem status is pending)
                $result = $this->checkTransactionStatus($pay_key);
                $status = (isset($result->status) ? $result->status : ''); //get paypal response status
                $this->writePaypalLogs('Paypal status for CI return is coming: '. $status);
                $result_constant = $this->prepareconstant();
                if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::COMPLETED) || ($status == ApplaneConstentInterface::IN_COMPLETE) || ($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED) || ($status == ApplaneConstentInterface::EXPIRED))) {
                    $this->writePaypalLogs('Calliing the CI Confirm code from the PaypalTransactionStatusCommand:shop_id' . $shop_id . ": transaction_id:" . $transaction_id . ": reason :" . $transaction_reason . ":transaction_refferanace:" . $pay_key . "new_status:" . $result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]);
                    $is_local_update = 1;
                }
            }else if (Utility::matchString($current_status, ApplaneConstentInterface::PENDING) && Utility::matchString($transaction_reason, ApplaneConstentInterface::ECOMMERCE_TYPE)) { //check for Ecommerce Product Pending Transaction
                $this->writePaypalLogs('>>Enter in Ecommerce Product Check');
                $result = $this->checkTransactionStatus($pay_key);
                $this->writePaypalLogs('Paypal response for paykey: '. Utility::encodeData($result));
                $paypal_data = $this->getPaypalTransactionIds($result);
                $paypal_sender_transaction_id = $paypal_data['sender_transaction_id'];
                $paypal_reciver_transaction_id = $paypal_data['reciever_transaction_id'];
                $sender_paypal_email = $paypal_data['sender_email'];
                $status = (isset($result->status) ? $result->status : ''); //get paypal response status
                $this->writePaypalLogs('Paypal status for normal transaction is coming: '. $status);
                $result_constant = $this->prepareconstant(); //prepare the constant  array

                if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::COMPLETED) || ($status == ApplaneConstentInterface::IN_COMPLETE))) {
                    $is_local_update = 1;
                    //$this->sendEmailPushNotification($shop_id, $user_id, true, true, $card_value, $shop_info, $shop_owner_id, ApplaneConstentInterface::BUY); //for user
                    $this->writePaypalLogs('Calliing the CI return code from the PaypalTransactionStatusCommand:shop_id'.$shop_id);
                    $paypal_transaction_service->payPaypalAmount($shop_id,$transaction_id);
                    $paypal_transaction_service->sendMailNotificationBuyEcommerceProduct($shop_id,$transaction_id,$user_id, $product_name);
                    $buyEcommerceProductService->sendPushNotifications($shop_owner_id, $user_id, $shop_id, $product_name, $order_id);//send web and push notification
                } else if (isset($result_constant[$status]) && (($status == ApplaneConstentInterface::ERROR) || ($status == ApplaneConstentInterface::CANCELED) || ($status == ApplaneConstentInterface::EXPIRED))) {
                    $is_local_update = 1;
                }
                 $this->writePaypalLogs('>>Exiting from Ecommerce Product Check');
            }
            //update in our local database
            if ($is_local_update) {
                try {
                    $payment_transaction_record->setPaymentStatus($result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]); //update transaction status
                    $em->persist($payment_transaction_record);
                    $em->flush();                    
                } catch (\Exception $ex) {
                    $this->writePaypalLogs('There is some error in updating the status in local database: '. $ex->getMessage());
                }
                //update status on applane.
                if( Utility::matchString($transaction_reason, ApplaneConstentInterface::ECOMMERCE_TYPE)){
                 $paypal_status = $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS];
                 $applane_service->updateEcommerceProductStatus($transaction_id, $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS], $paypal_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id); //update on transaction system.
                }else{
                $applane_service->updateShoppingCardStatus($transaction_id, $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS], $handler); //update on transaction system.
                }
                //write log for status update.
                $this->writePaypalLogs('Pay key ' . $pay_key . ' is updated with applane status '
                        . $result_constant[$status][ApplaneConstentInterface::APPLANE_STATUS] . ' and our system status ' . $result_constant[$status][ApplaneConstentInterface::TRANSACTION_STATUS]);
            }
        }
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [verifyPaypalTransactionStatus]');
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
        
        $constant_array[ApplaneConstentInterface::EXPIRED] = array(ApplaneConstentInterface::APPLANE_STATUS => ApplaneConstentInterface::REJECTED,
            ApplaneConstentInterface::TRANSACTION_STATUS => ApplaneConstentInterface::CANCELED);
        return $constant_array;
    }

    /**
     * check the detail if transaction is completed on paypal.
     * @param string $transaction_pay_key
     */
    public function checkTransactionStatus($transaction_pay_key) {
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkTransactionStatus]');
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
            $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkTransactionStatus]');
            return json_decode($result);
        } catch (\Exception $e) {
            $this->writePaypalLogs('Paypal transaction status check response: ' . $e->getMessage());  //write the log for error 
        }
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkTransactionStatus]');
    }

    /**
     * send email for notification on shopping card purchase/sale
     * @param int $shop_id
     * @param int $receiver_id
     * @param string $isWeb
     * @param string $isPush
     * @param int $card_value
     * @param array $shop_info
     * @param int $sender_id
     * @param string $transaction_type
     * @return boolean
     */
    public function sendEmailPushNotification($shop_id, $receiver_id, $isWeb = false, $isPush = false, $card_value, $shop_info, $sender_id, $transaction_type) {
        
        $this->writePaypalLogs('Entering into class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [sendEmailPushNotification]');
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
        $msgtype = '';
        $app_type = '';
        $msg = '';
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
        $this->writePaypalLogs('Exiting from class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [sendEmailPushNotification]');
        return true;
    } 
    
    /**
     * function for checking who pays the transaction fee
     * @param type $shop_id
     */
    public function checkWhoPayTransactionFee($shop_id) {
        $feesPayerParam = '&feesPayer=';
        $feesPayer = 'EACHRECEIVER';
        $instant_ci_pay = 0;
        try{
            $instant_ci_pay = $this->container->getParameter(self::INSTANT_CI_PAYBACK);
        } catch (\Exception $ex) {
            $instant_ci_pay = 0;
        }
        
        if($instant_ci_pay == 0) {
            $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] INSTANT_CI_PAYBACK is set to 0');
           return $feesPayerParam.$feesPayer; 
        }
        
        $em = $this->em;
        $fee_payment_options = $em->getRepository('CardManagementBundle:WaiverOptions')
                            ->CheckWaiverStatusForPaypal(self::INSTANT_CI_PAYBACK); //for finding the store owner id.
        //get the shop paypal payment option
        $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] response from table:'.  json_encode((array)$fee_payment_options));
        if(count($fee_payment_options) > 0) {
            $shop_ids = $fee_payment_options->getItemIds();
            $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] Shops Id:'.  $shop_ids);
            $shops_id = json_decode($shop_ids);           
            //get shop check status
            $shop_check = $this->getShopCheck($shops_id, $shop_id);
            //get rule start and end date
            $start_date = $fee_payment_options->getStartDate();
            $end_date = $fee_payment_options->getEndDate();
            //check the date status
            $date_check = $this->getDateCheck($start_date, $end_date);
            //check if shop exist in the waiver list
            if($shop_check && $date_check) {
                $options = $fee_payment_options->getOptions();
                $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] response for option for Paypal transaction fee:'.  $options);
                $options = (array)json_decode($options);
                $option_check = $this->getOptionCheck($options, ApplaneConstentInterface::CHAINED_PAYPAL_FEE_PAYER);
                if($option_check) {
                    
                    $feesPayer = $options[ApplaneConstentInterface::CHAINED_PAYPAL_FEE_PAYER];
                    $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] Paypal transaction fee pay parameter:'.$feesPayerParam.$feesPayer);
                    return $feesPayerParam.$feesPayer;
                }
            }
        } 
            return $feesPayerParam.$feesPayer;
        
    }
    
    /**
     *  function for checking if the shop exist in the waiver option
     * @param type $shops_id
     * @param type $shop_id
     * @return boolean
     */
    private function getShopCheck($shops_id, $shop_id) {
        $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getShopCheck] with shop ids:'.  json_encode($shops_id));
        $shops_id = (array) $shops_id;
        // check if shop array exist in DB
        if (count($shops_id) > 0) {
            if (in_array($shop_id, $shops_id)) {
                $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] return true for the shop paypal check:'.$shop_id);
                return true;
            }
            $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [checkWhoPayTransactionFee] Paypal rule is present but shop is not under it:'.$shop_id);
            return false;
        }
        $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getShopCheck] No shop in rule');
        return true;
    }

    /**
     *  function for checking if the option is present and has a valid option
     * @param type $options
     * @param type $option_check
     */
    private function getOptionCheck($options,$option_check) {
        $result = false;
        $options = (array)$options;
       if(isset($options[ApplaneConstentInterface::CHAINED_PAYPAL_FEE_PAYER])) {
           $fee_payer = $options[ApplaneConstentInterface::CHAINED_PAYPAL_FEE_PAYER];
           $valid_fee_payer = $this->valid_paypal_payers;
           if(in_array($fee_payer, $valid_fee_payer)) {
               $result =  true;
           }
       }
       return $result;
    }
    
    /**
     *  function for checking the valid date for the rule
     * @param type $start_date
     * @param type $end_date
     * @return boolean
     */
    private function getDateCheck($start_date, $end_date) {
        $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getDateCheck] with Start date:'.  json_encode($start_date)." And end date :".  json_encode($end_date));
        $start_date = strtotime($start_date->format('Y-m-d'));
        $end_date = strtotime($end_date->format('Y-m-d'));
        $test_date = strtotime('0000-00-00');
        $date = time();
        if ($start_date == $test_date && $end_date == $test_date) {
            $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getDateCheck] start date or end date is dirty');
            return true;
        } else {
            if ($date >= $start_date && $date < $end_date) {
                $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getDateCheck] date is comes under valid date');
                return true;
            }
            $this->writePaypalLogs('In class [Paypal\PaypalIntegrationBundle\Services\PaypalTransactionVerifyService] function [getDateCheck] date is exiperd');
            return false;
        }
    }
    
    /**
     *  function for paying back the CI to the shop for a shopping card purchased 
     * @param type $shop_id
     * @param type $transaction_id
     */
    public function payPaypalAmount($shop_id,$transaction_id) {
        $em = $this->em;
        $this->writePaypalLogs('Enter into Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and payPaypalAmount method:');
        $payment_transaction_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("shopId" => $shop_id, "itemId" => $transaction_id));  
        
        $payment_transaction_ci_record = $em->getRepository('PaypalIntegrationBundle:PaymentTransaction')
                ->findOneBy(array("itemId" => $transaction_id, 'reason' => ApplaneConstentInterface::INSTANT_CI_REASON,"shopId" => $shop_id));
        //check if record exist for the CI for the given transaction
        if(count($payment_transaction_ci_record) > 0) {
            $this->writePaypalLogs('In Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and payPaypalAmount method: CI record alredy exist:Shop_id'.$shop_id.":transaction_id".$transaction_id);
            return true;
        }
        
        //get the amount of CI used for card purchase
        $ci_used = $payment_transaction_record->getCiUsed();
        $citizen_id = $payment_transaction_record->getCitizenId();
        $shop_status_for_ci_return = $this->checkShopIdAndDateStatus($shop_id);
        
        //check if shop comes under the instant CI payback rule
        if($shop_status_for_ci_return == false) {
            $this->writePaypalLogs('Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and payPaypalAmount method:This shop not comes under instant ci payback rule:'.$shop_id);
            return true;
        }
        //check if ci to be paid is grater then 0
        if($ci_used > 0) {
        //find paypal account for shop
        $shop_paypal_info = $em->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => 'VERIFIED', 'isDefault' => 1));
        //get shop paypal email-id
        $shop_paypal_email = $shop_paypal_info->getEmailId();   
        $this->writePaypalLogs('left Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and payPaypalAmount method for paypal hit');
        $paypal_resp = $this->hitPaypalForTransaction($shop_paypal_email, $ci_used, $shop_id);
        $this->writePaypalLogs('back in  Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and payPaypalAmount method for paypal hit with data:'.  json_encode($paypal_resp));
        $paypal_payment_status = isset($paypal_resp->responseEnvelope->ack) ? $paypal_resp->responseEnvelope->ack : 'error';
        $payment_status = '';
        $error_code = '';
        $error_description = '';
        $transaction_reference = '';
        $transaction_value = 0;
        $paypal_id= '';
        if($paypal_payment_status == 'Success') {
           $payment_status =  $paypal_resp->paymentExecStatus;
           $payment_infos =  $paypal_resp->paymentInfoList->paymentInfo;
           $payment_info = $payment_infos[0];
           $payment_details = $payment_info->receiver;
           $paypal_id = $payment_details->accountId;
           $transaction_value = $payment_details->amount;
           $confirm_array = array(ApplaneConstentInterface::COMPLETED,ApplaneConstentInterface::IN_COMPLETE);
           if(in_array($payment_status, $confirm_array)) {
               $payment_status = ApplaneConstentInterface::CONFIRMED;
               $transaction_reference = $paypal_resp->payKey;
           } else {
               $payment_status = ApplaneConstentInterface::PENDING;
               $transaction_reference = $paypal_resp->payKey;
           }
        } else {
            $error_resp = $paypal_resp->error;
            $error_resp = $error_resp[0]; 
            $payment_status = ApplaneConstentInterface::ERROR;
            $error_code = $error_resp->errorId;
            $error_description = $error_resp->message;
        }
        
        $this->savePaymentTransactionForCIReturn($shop_id, $transaction_value, $paypal_id, $payment_status, $error_code, $error_description, $transaction_reference,$transaction_id);
        }
        
        
    }
    
    
    /**
     * function for checking who pays the transaction fee for the ci return to the shop
     * @param type $shop_id
     */
    public function checkWhoPayTransactionFeeCIReturn($shop_id) {
        $feesPayerParam = '&feesPayer=';
        $feesPayer = 'SENDER';
        $instant_ci_pay = 0;
        try{
            $instant_ci_pay = $this->container->getParameter(self::INSTANT_CI_PAYBACK);
        } catch (\Exception $ex) {
            $instant_ci_pay = 0;
        }
        
        if($instant_ci_pay == 0) {
           return $feesPayerParam.$feesPayer; 
        }
        
        $em = $this->em;
        $fee_payment_options = $em->getRepository('CardManagementBundle:WaiverOptions')
                            ->CheckWaiverStatusForPaypal(self::INSTANT_CI_PAYBACK); //for finding the store owner id.
        //get the shop paypal payment option
        if(count($fee_payment_options) > 0) {
            $shop_ids = $fee_payment_options->getItemIds();
            $shops_id = json_decode($shop_ids);
            //get shop check status
            $shop_check = $this->getShopCheck($shops_id, $shop_id);
            //get rule start and end date
            $start_date = $fee_payment_options->getStartDate();
            $end_date = $fee_payment_options->getEndDate();
            //check the date status
            $date_check = $this->getDateCheck($start_date, $end_date);
            //check if shop exist in the waiver list
            if($shop_check && $date_check) {
                $options = $fee_payment_options->getOptions();
                $options = (array)json_decode($options);
                $option_check = $this->getOptionCheck($options, ApplaneConstentInterface::CI_RETURN_FEE_PAYER);
                if($option_check) {
                    $feesPayer = $options[ApplaneConstentInterface::CI_RETURN_FEE_PAYER];
                    return $feesPayerParam.$feesPayer;
                }
            }
        } 
            return $feesPayerParam.$feesPayer;
        
    }
    
    
    /**
     * paypal response and update the status
     * @param string $transaction_data
     * @param int $shop_id
     * @param int $citizen_id
     */
    public function hitPaypalForTransaction($paypal_email,$amount, $shop_id) {
        $this->writePaypalLogs('Enter in Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and hitPaypalForTransaction method:');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        $cancel_url = $this->container->getParameter('symfony_base_url');
        $return_url = $this->container->getParameter('symfony_base_url');
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
        $primary_reciever_paypal_email = $paypal_email;
        $primary_reciever_amount = $amount;
        $curreny_code = $this->container->getParameter('paypal_currency');
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        //get fee payer for the CI return
        //$fee_payer = $paypal_transaction_service->checkWhoPayTransactionFeeCIReturn($shop_id);
        $paypal_service = $this->container->get('paypal_integration.payment_transaction');
        $type = PaypalConstentInterface::CI_RETURN_FEE_PAYER;
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
        $payload = "actionType=PAY&ipnNotificationUrl=$ipn_notification_url&cancelUrl=$cancel_url&clientDetails.applicationId=$paypal_acct_appid"
                . "&clientDetails.ipAddress=127.0.0.1&currencyCode=$curreny_code" .
                "&receiverList.receiver(0).amount=$primary_reciever_amount&receiverList.receiver(0).email=$primary_reciever_paypal_email" .
                "&requestEnvelope.errorLanguage=en_US" .
                "&returnUrl=$return_url".$final_fee_payer."&senderEmail=".$sender_email;
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
            $this->writePaypalLogs('Paypal CI return request: ' . $payload);
            $this->writePaypalLogs('paypal CI return response: ' . $result);
            $this->writePaypalLogs('Exiting from Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and hitPaypalForTransaction method:');
            return json_decode($result);
        } catch (\Exception $e) {
           // $applane_service->writeTransactionLogs('Paypal chain request: ' . $payload, 'paypal chain response: ' . $e->getMessage());  //write the log for error 
            $this->writePaypalLogs('Paypal CI return request: ' . $payload);
            $this->writePaypalLogs('Paypal CI return request: ' . $e->getMessage());
        }
        $this->writePaypalLogs('Exiting from Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService.php class and hitPaypalForTransaction method:');
    }
    
    
    /**
     *  function for saving the Payment transaction in the local Database
     * @param type $shop_id
     * @param type $transaction_value
     * @param type $paypal_id
     * @param type $payment_status
     * @param type $error_code
     * @param type $error_description
     * @param type $transaction_reference
     * @return boolean
     */
    public function savePaymentTransactionForCIReturn($shop_id,$transaction_value,$paypal_id,$payment_status = '',$error_code = '',$error_description = '',$transaction_reference= '',$item_id = '') {
        $pay_tx_data['item_id'] = $item_id;
        $pay_tx_data['reason'] = 'CI';
        $pay_tx_data['citizen_id'] = '';
        $pay_tx_data['shop_id'] = $shop_id;
        $pay_tx_data['payment_via'] = 'PAYPAL';
        $pay_tx_data['payment_status'] = $payment_status;
        $pay_tx_data['error_code'] = '';
        $pay_tx_data['error_description'] = '';
        $pay_tx_data['transaction_reference'] = $transaction_reference;
        $pay_tx_data['transaction_value'] = $transaction_value;
        $pay_tx_data['vat_amount'] = 0;
        $pay_tx_data['contract_id'] = '';
        $pay_tx_data['paypal_id'] = $paypal_id;
        $pay_tx_data['transaction_id'] = '';
        $pay_tx_data['ci_used'] = 0;
        $payment_transaction = $this->container->get('paypal_integration.payment_transaction');
        $payment_transaction->addPaymentTransaction($pay_tx_data);
        return true;
    }
    
    /**
     *  function for checking the shopid and date status based on the waver rule
     * @param type $shop_id
     * @return type
     */
    public function checkShopIdAndDateStatus($shop_id) {
        $instant_ci_pay = 0;
        try {
            $instant_ci_pay = $this->container->getParameter(self::INSTANT_CI_PAYBACK);
        } catch (\Exception $ex) {
            $instant_ci_pay = 0;
        }

        if ($instant_ci_pay == 0) {
            return false;
        }

        $em = $this->em;
        $fee_payment_options = $em->getRepository('CardManagementBundle:WaiverOptions')
                ->CheckWaiverStatusForPaypal(PaypalConstentInterface::CI_RETURN_FEE_PAYER); //for finding the store owner id.
        //get the shop paypal payment option
        if (count($fee_payment_options) > 0) {
            $shop_ids = $fee_payment_options->getItemIds();
            $shops_id = json_decode($shop_ids);
            //get shop check status
            $shop_check = $this->getShopCheck($shops_id, $shop_id);
            //get rule start and end date
            $start_date = $fee_payment_options->getStartDate();
            $end_date = $fee_payment_options->getEndDate();
            //check the date status
            $date_check = $this->getDateCheck($start_date, $end_date);
            //check if shop exist in the waiver list
            if ($shop_check && $date_check) {
                return true;
            }
        }
        
        return false;
    }
    
    
    public function sendMailNotificationBuyUPTO100($shop_id,$transaction_id,$user_id) {
        //get applane service 
        $this->writePaypalLogs('Entering in class [Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyUPTO100]');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_service = $this->container->get('user_object.service');
        $shopping_card_details = $applane_service->getShoppingCardUPTO100Details($transaction_id);
        $user_data = $user_service->UserObjectService($user_id);
        $shop_data = $user_service->getStoreObjectService($shop_id);
        $postService = $this->container->get('post_detail.service');
        //check if user and shop exist in the symfony DB
        if(count($user_data) > 0 && count($shop_data) > 0) {
            $this->writePaypalLogs('[Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyUPTO100] Sending mail to the '.json_encode($shop_data).',User_data:'.  json_encode($user_data));
           $postService->sendShoppingCardUPTO100MailNotification($user_data,$shop_data,$shopping_card_details);
        } else {
            $this->writePaypalLogs('Exiting from class [Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyUPTO100] one of the shop_id or User_id is dirty, Shop_id:'.$shop_id.',User_id:'.$user_id);
        }
    }
    
    /**
     * 
     * @param type $shop_id
     * @param type $transaction_id
     * @param type $user_id
     */
     public function sendMailNotificationBuyEcommerceProduct($shop_id,$transaction_id,$user_id, $product_name) {
        //get applane service 
        $this->writePaypalLogs('Entering in class [Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyEcommerceProduct]');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_service = $this->container->get('user_object.service');
        $user_data = $user_service->UserObjectService($user_id);
        $shop_data = $user_service->getStoreObjectService($shop_id);
        $postService = $this->container->get('post_detail.service');
        //check if user and shop exist in the symfony DB
        if(count($user_data) > 0 && count($shop_data) > 0) {
            $this->writePaypalLogs('[Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyEcommerceProduct] Sending mail to the '.json_encode($shop_data).',User_data:'.  json_encode($user_data));
           $postService->sendEcommerceProductMailNotification($user_data,$shop_data,$product_name);
        } else {
            $this->writePaypalLogs('Exiting from class [Paypal/PaypalIntegrationBundle/Services/PaypalTransactionVerifyService] function [sendMailNotificationBuyUPTO100] one of the shop_id or User_id is dirty, Shop_id:'.$shop_id.',User_id:'.$user_id);
        }
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
    
}
