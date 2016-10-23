<?php
namespace Transaction\TransactionSystemBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Notification\NotificationBundle\Document\UserNotifications;

class TransactionManager
{  
    protected $em;
    protected $dm;
    protected $container;
    
    public function __construct(EntityManager $em = NULL, DocumentManager $dm = NULL, Container $container = NULL)
    {
        if($em) {
            $this->em  = $em;
        }
        
        if($dm) {
            $this->dm = $dm;
        }
        
        if($container) {
            $this->container = $container;
        }
    }
    
    /**
     * function to get the current ip address of the request
     */
    public function getCurrentIPAddress() {
       $ip_address =  $_SERVER['REMOTE_ADDR'];
       return $ip_address;
    }

    public function getCurrencyCode($code) {
        $amount = 123456;
        $formatter = new \NumberFormatter('en', \NumberFormatter::CURRENCY);
        $string = $formatter->formatCurrency($amount, $code);
        return $this->get_currency_symbol($string);
    }
    
    public function get_currency_symbol($string)
    {
        $symbol = '';
        $length = mb_strlen($string, 'utf-8');
        for ($i = 0; $i < $length; $i++)
        {
            $char = mb_substr($string, $i, 1, 'utf-8');
            if (!ctype_digit($char) && !ctype_punct($char))
                $symbol .= $char;
        }
        return $symbol;
    }
    
    public function p($array) {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
    }

    public function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    public function getTransactionIdToken($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnpqrstuvwxyz";
        $codeAlphabet.= "123456789";
        $max = strlen($codeAlphabet) - 1;
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max)];
        }
        return $token;
    }

    public function getBuyerCurrency($buyerId) {
        return 'EUR';
    }

    public function getSellerCurrency($sellerId) {
        return 'EUR';
    }

    public function getPriceFormat($val) {
        return $val*100;
    }

    public function getCountryVat($country) {
        return '1.22';
    }

    public function getOrigPrice($price) {
        return $price/100;
    }

    public function checkCreditUsage($data) {
        $init_amount = $data['init_amount'];
        $cashpayment = $data['cashpayment'];
        $max_usage_init_price = $data['max_usage_init_price'];
        $available_amount = $data['available_amount'];

        $max_usable_amount = floor(($init_amount * $max_usage_init_price/100) - ($init_amount - $cashpayment));
        //echo "AVL: ".$available_amount." MUA: ".$max_usable_amount." Cash: ".$cashpayment."<br/>";
        if($max_usable_amount > 0) {
            if( $max_usable_amount >= $available_amount ) {
                $usedAmount = $available_amount;
            } else {
                $usedAmount = $max_usable_amount;
            }
        } else {
            $usedAmount = 0;
        }

        $returnObj = array(
                'init_amount' => $init_amount,
                'amount_used' => $usedAmount,
                'cashpayment' => $cashpayment - $usedAmount
            );
        return $returnObj;
    }
    
    /*
     * Transaction Notification
     * @params $requestObj, $senderObj
     */
    public function sendNotification($_data){ 
        $sender = $this->getUserData($_data['from_id'], true);
        $datas = $this->getReadyNotificationData($_data, $sender);
        $push_object_service = $this->container->get('push_notification.service');
        foreach($datas as $_data){
            foreach($_data as $data){
                $to_id = $data['to_id'];
                $from_id = $data['from_id'];
                $pushText = $data['pushText'];
                $isWeb = $data['_notification']['is_web'];
                $isPush = $data['_notification']['is_push'];
                $isEmail = $data['_notification']['is_mail'];
                $extraParams = array('store_id'=>$data['info']['store_id']);
                if(!empty($to_id)){
                    $usersDevices = array();
                    $pushAndWeb = 0;
                    if($isWeb and $isPush){
                        $pushAndWeb = 5;
                        $usersDevices = $push_object_service->getReceiverDeviceInfo($to_id, strtolower($data['client_type']));
                    }elseif($isPush){
                        $pushAndWeb=4;
                        $usersDevices = $push_object_service->getReceiverDeviceInfo($to_id, strtolower($data['client_type']));
                    }elseif ($isWeb) {
                        $pushAndWeb=1;
                    }
                    // send web notifications
                    if(in_array($pushAndWeb, array(1,4,5))){
                        $this->saveUserNotification($from_id, $to_id, $data['item_id'], $data['message_type'], $data['message_code'], $data['message_status'], $data['info'], $pushAndWeb);
                    }

                    // send push notifications
                    if(in_array($pushAndWeb, array(4,5)) and !empty($usersDevices)){
                        $pushInfo = array(
                          'from_id'=>  $from_id, 'to_id' => $to_id, 'msg_code'=>$data['message_code'], 'ref_type'=>$data['message_type'],
                            'ref_id'=> $data['item_id'], 'role'=>4, 'client_type'=> $data['client_type'],
                            'msg'=> $pushText 
                        );
                        $push_object_service->sendPush($usersDevices, $pushInfo['from_id'], $pushInfo['msg_code'], $pushInfo['msg'], $pushInfo['ref_type'] , $pushInfo['ref_id'], $pushInfo['client_type'], $extraParams);
                    }
                    
                    if($isEmail and isset($data['email'])){
                        $this->_sendEmail($to_id, $data['email'], isset($data['email_type']) ? $data['email_type'] : '');
                    }
                }
            }
        }
    }
    
    public function getReadyNotificationData($data, $sender=array()){
        $store = array();
        $receivers = $this->getUserData($data['to_id'], true);
        $receiversByLang = $this->getReceiversByLang($receivers);
        $response = array();
        $i=0;
        $sender = $sender[$data['from_id']];
        foreach($receiversByLang as $lang=>$_receivers){
            $nType = strtoupper($data['message_code'].'-'.$data['message_type']);
            $senderName = (isset($sender) and !empty($sender)) ? ucwords(trim($sender['first_name'].' '.$sender['last_name'])) : '';
            $store = !empty($store) ? $store : $this->getStoreData($data['info']['store_id']);
            switch($nType){
                    case 'TXN_CUST_PENDING-TXN':
                    // initiate transaction
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'SHOP';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>false);
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $senderName, $lang);
                    $response['shop'][$i]['message_status']="T";
                    $response['shop'][$i]['info']['store_info']=$store;
                    break;
                case 'TXN_SHOP_CANCEL-TXN':
                    //Transcation Cancelled
                    $response['citizen'][$i] = $data;
                    $response['citizen'][$i]['to_id'] = array_keys($_receivers);
                    $response['citizen'][$i]['client_type'] = 'CITIZEN';
                    $response['citizen'][$i]['_notification']= array('is_web' => false, 'is_push'=>true, 'is_mail'=>false);
                    $storeName = (!empty($store['name']) ? $store['name'] :$store['businessName']);
                    $response['citizen'][$i]['pushText'] = $this->getPushText($nType, $storeName, $lang);
                    $response['citizen'][$i]['message_status']="T";
                    $response['citizen'][$i]['info']['store_info']=$store;
                    break;
                case 'TXN_SHOP_APPROVE-TXN':
                    //Transcation Confirmed
                    $response['citizen'][$i] = $data;
                    $response['citizen'][$i]['to_id'] = array_keys($_receivers);
                    $response['citizen'][$i]['client_type'] = 'CITIZEN';
                    $response['citizen'][$i]['_notification']= array('is_web' => false, 'is_push'=>true, 'is_mail'=>false);
                    $storeName = (!empty($store['name']) ? $store['name'] :$store['businessName']);
                    $response['citizen'][$i]['pushText'] = $this->getPushText($nType, $storeName, $lang);
                    $response['citizen'][$i]['message_status']="T";
                    $response['citizen'][$i]['info']['store_info']=$store;
                    break;
                case 'CREATED_CARD_UPTO_100-CAMPAIGN_SHOPPING_CARD':
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'SHOP';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>true);
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $senderName, $lang);
                    $response['shop'][$i]['message_status']="T";
                    $response['shop'][$i]['info']['store_info']=$store;
                    $response['shop'][$i]['email'] = $this->getEmailText($nType, $senderName, $lang);
                    $response['shop'][$i]['email_type'] = 'CAMPAIGN';
                    $response['shop'][$i]['email']['thumb'] = (isset($sender['profile_image_thumb']))?$sender['profile_image_thumb']: "";
                    break;
                case 'TXN_CITIZEN_CANCEL-TXN':
                    //Transcation Cancelled
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'SHOP';
                    $response['shop'][$i]['_notification']= array('is_web' => false, 'is_push'=>true, 'is_mail'=>false);
                    $storeName = (!empty($store['name']) ? $store['name'] :$store['businessName']);
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, array($senderName, $storeName), $lang);
                    $response['shop'][$i]['message_status']="T";
                    $response['shop'][$i]['info']['store_info']=$store;
                    break;
                case 'PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT':
                    //BUY ECOMMERCE PRODUCT
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'SHOP';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>true);
                    $product_name = (isset($data['info']['product_name'])) ? $data['info']['product_name'] : '';
                    $product_count = (isset($data['info']['product_count'])) ? $data['info']['product_count'] : '';
                    $link_id = (isset($data['item_id'])) ? $data['item_id'] : '';
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $product_name, $lang);
                    $response['shop'][$i]['message_status']="U";
                    $response['shop'][$i]['info']['store_info']=$store;
                    $replc_txt = array($product_name, $product_count);
                    $response['shop'][$i]['email'] = $this->getEmailText($nType, $replc_txt, $lang, $link_id);
                    $response['shop'][$i]['email_type'] = 'ECOMMERCE_PRODUCT';
                    $response['shop'][$i]['email']['thumb'] = (isset($sender['profile_image_thumb']))?$sender['profile_image_thumb']: "";
                    break;
                case 'CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT':
                    //BUY ECOMMERCE PRODUCT
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'SHOP';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>true);
                    $product_name = (isset($data['info']['product_name'])) ? $data['info']['product_name'] : '';
                    $product_count = (isset($data['info']['product_count'])) ? $data['info']['product_count'] : '';
                    $buyer_id = (isset($data['info']['buyer_id'])) ? $data['info']['buyer_id'] : '';
                    $buyers = $this->getUserData($buyer_id, false);
                    $buyer_name = $buyers['first_name']." ".$buyers['last_name'];
                    $link_id = (isset($data['item_id'])) ? $data['item_id'] : '';
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $product_name, $lang);
                    $response['shop'][$i]['message_status']="U";
                    $response['shop'][$i]['info']['store_info']=$store;
                    $response['shop'][$i]['info']['buyer_info']=$buyers;
                    $replc_txt = array($product_name, $buyer_name);
                    $response['shop'][$i]['email'] = $this->getEmailText($nType, $replc_txt, $lang, $link_id);
                    $response['shop'][$i]['email_type'] = 'ECOMMERCE_PRODUCT';
                    $response['shop'][$i]['email']['thumb'] = (isset($sender['profile_image_thumb']))?$sender['profile_image_thumb']: "";
                    break;
            case 'CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT':
                    //BUY ECOMMERCE PRODUCT
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'CITIZEN';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>true);
                    $product_name = (isset($data['info']['product_name'])) ? $data['info']['product_name'] : '';
                    $link_id = (isset($data['item_id'])) ? $data['item_id'] : '';
                    $storeName = (!empty($store['name']) ? $store['name'] :$store['businessName']);
                    $replc_txt = array($product_name, $storeName);
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $replc_txt, $lang);
                    $response['shop'][$i]['message_status']="U";
                    $response['shop'][$i]['info']['store_info']=$store;
                    $response['shop'][$i]['email'] = $this->getEmailText($nType, $replc_txt, $lang, $link_id);
                    $response['shop'][$i]['email_type'] = 'ECOMMERCE_PRODUCT';
                    $response['shop'][$i]['email']['thumb'] = (isset($sender['profile_image_thumb']))?$sender['profile_image_thumb']: "";
                    break;
            case 'PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT':
                    //BUY ECOMMERCE PRODUCT
                    $response['shop'][$i] = $data;
                    $response['shop'][$i]['to_id'] = array_keys($_receivers);
                    $response['shop'][$i]['client_type'] = 'CITIZEN';
                    $response['shop'][$i]['_notification']= array('is_web' => true, 'is_push'=>true, 'is_mail'=>true);
                    $product_name = (isset($data['info']['product_name'])) ? $data['info']['product_name'] : '';
                    $link_id = (isset($data['item_id'])) ? $data['item_id'] : '';
                    $storeName = (!empty($store['name']) ? $store['name'] :$store['businessName']);
                    $replc_txt = array($product_name, $storeName);
                    $response['shop'][$i]['pushText'] = $this->getPushText($nType, $replc_txt, $lang);
                    $response['shop'][$i]['message_status']="U";
                    $response['shop'][$i]['info']['store_info']=$store;
                    $response['shop'][$i]['email'] = $this->getEmailText($nType, $replc_txt, $lang, $link_id);
                    $response['shop'][$i]['email_type'] = 'ECOMMERCE_PRODUCT';
                    $response['shop'][$i]['email']['thumb'] = (isset($sender['profile_image_thumb']))?$sender['profile_image_thumb']: "";
                    break;
            }
            $i++;
        }
        return $response;
    }
    
    /*
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($sender_id, $reciever_ids, $item_id, $msgtype, $msg, $messageStatus, $info, $notification_type) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $reciever_ids = is_array($reciever_ids) ? $reciever_ids : (array)$reciever_ids;
        foreach($reciever_ids as $reciever_id) {
            $notification = new UserNotifications();
            $notification->setFrom($sender_id);
            $notification->setTo($reciever_id);
            $notification->setMessageType($msgtype);
            $notification->setMessage($msg);
            $time = new \DateTime("now");
            $notification->setDate($time);
            $notification->setIsRead('0');
            $notification->setItemId($item_id);
            $notification->setNotificationRole($notification_type);
            $notification->setMessageStatus($messageStatus);
            $notification->setInfo($info);
            $dm->persist($notification);
        }        
        $dm->flush();
        return true;
    }
    
    /*
     * Get user information
     * @param $user_id
     */
    public function getUserData($userId, $multiple=false){
        $userIds = is_array($userId)? $userId : (array)$userId;
        $user_service = $this->container->get('user_object.service');
        $users = $user_service->MultipleUserObjectService($userIds);
        $response = array();
        if($multiple==false and !empty($users)){
            $response = array_shift($users);
        }else{
            $response = $users;
        }
        return $response;
    }
    
    public function getReceiversByLang(array $users){
        $postService = $this->container->get('post_detail.service');
        $response = $postService->getUsersByLanguage($users);
        return $response;
    }
    
    public function getPushText($type, $replaceText='', $lang=null){
        $locale = (is_null($lang) or $lang=='0') ? $this->container->getParameter('locale') : $lang;
        $language_const_array = $this->container->getParameter($locale);
        $text = '';
        $replaceText = is_array($replaceText) ? $replaceText : (array)$replaceText;
        switch($type){
            case 'TXN_CUST_PENDING-TXN':
                $text =  vsprintf($language_const_array['PUSH_TXN_CUST_PENDING-TXN'], $replaceText);
                break;
            case 'TXN_SHOP_CANCEL-TXN':
                $text = vsprintf($language_const_array['PUSH_TXN_SHOP_CANCEL-TXN'], $replaceText);
                break;
            case 'TXN_SHOP_APPROVE-TXN':
                $text = vsprintf($language_const_array['PUSH_TXN_SHOP_APPROVE-TXN'], $replaceText);
                break;
            case 'TXN_CUST_RATING-TXN':
                $text = vsprintf($language_const_array['PUSH_TXN_CUST_RATING-TXN'], $replaceText);
                break;
            case 'TXN_CUST_SHARE-TXN':
                $text = vsprintf($language_const_array['PUSH_TXN_CUST_SHARE-TXN'], $replaceText);
                break;
            case 'CREATED_CARD_UPTO_100-CAMPAIGN_SHOPPING_CARD':
                $text = $language_const_array['PUSH_CREATED_SHOPPING_CARD_UPTO_100'];
                break;
            case 'TXN_CITIZEN_CANCEL-TXN':
                $text = vsprintf($language_const_array['PUSH_TXN_CITIZEN_CANCEL-TXN'], $replaceText);
                break;
            case 'PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT':
                $text = vsprintf($language_const_array['PUSH_PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT'], $replaceText);
                break;
            case 'CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT':
                $text = vsprintf($language_const_array['PUSH_CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT'], $replaceText);
                break;
            case 'CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT':
                $text = vsprintf($language_const_array['PUSH_CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT'], $replaceText);
                break; 
            case 'PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT':
                $text = vsprintf($language_const_array['PUSH_PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT'], $replaceText);
                break; 
        }
        return $text;
    }
    
    public function getEmailText($type, $replaceText='', $lang=null, $link_id=null){
        $locale = (is_null($lang) or $lang=='0') ? $this->container->getParameter('locale') : $lang;
        $language_const_array = $this->container->getParameter($locale);
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $text = array();
        $replaceText = is_array($replaceText) ? $replaceText : (array)$replaceText;
        switch($type){
            case 'CREATED_CARD_UPTO_100-CAMPAIGN_SHOPPING_CARD':
                $text['subject'] = $language_const_array['CREATED_SHOPPING_CARD_UPTO_100_SUBJECT'];
                $text['title'] = $language_const_array['CREATED_SHOPPING_CARD_UPTO_100_BODY'];
                $text['text'] = $language_const_array['CREATED_SHOPPING_CARD_UPTO_100_TEXT'];
                $clickLink = $email_template_service->getLinkForMail($angular_app_hostname, $lang);
                $text['text'] = $text['text'].'<br><br>'. $clickLink;
                break;
             case 'PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT':
                $text['subject'] = vsprintf($language_const_array['PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT_SUBJECT'], $replaceText);
                $text['title'] = vsprintf($language_const_array['PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT_BODY'],$replaceText) ;
                $text['text'] = vsprintf($language_const_array['PRODUCT_LOW_STOCK-BUY_ECOMMERCE_PRODUCT_TEXT'], $replaceText);
                $link = $angular_app_hostname."/".$link_id;
                $clickLink = $email_template_service->getLinkForMail($link, $lang);
                $text['text'] = $text['text'].'<br><br>'. $clickLink;
                break;
            case 'CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT':
                $text['subject'] = vsprintf($language_const_array['CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT_SUBJECT'], $replaceText);
                $text['title'] = vsprintf($language_const_array['CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT_BODY'],$replaceText) ;
                //reverse array
                $replaceText_reverse = array_reverse($replaceText);
                $text['text'] = vsprintf($language_const_array['CONFIRM_SHIPPING-BUY_ECOMMERCE_PRODUCT_TEXT'], $replaceText_reverse);
                $link = $angular_app_hostname."/".$link_id;
                $clickLink = $email_template_service->getLinkForOrderMail($link, $lang);
                $text['text'] = $text['text'].'<br><br>'. $clickLink;
                break;
            case 'CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT':
                $text['subject'] = vsprintf($language_const_array['CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT_SUBJECT'], $replaceText);
                $text['title'] = vsprintf($language_const_array['CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT_BODY'],$replaceText) ;
                $text['text'] = vsprintf($language_const_array['CANCEL_ORDER-BUY_ECOMMERCE_PRODUCT_TEXT'], $replaceText);
                $link = $angular_app_hostname."/".$link_id;
                $clickLink = $email_template_service->getLinkForOrderMail($link, $lang);
                $text['text'] = $text['text'].'<br><br>'. $clickLink;
                break;
            case 'PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT':
                $text['subject'] = vsprintf($language_const_array['PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT_SUBJECT'], $replaceText);
                $text['title'] = vsprintf($language_const_array['PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT_BODY'],$replaceText) ;
                $text['text'] = vsprintf($language_const_array['PRODUCT_SHIPPED-BUY_ECOMMERCE_PRODUCT_TEXT'], $replaceText);
                $link = $angular_app_hostname."/".$link_id;
                $clickLink = $email_template_service->getLinkForOrderMail($link, $lang);
                $text['text'] = $text['text'].'<br><br>'. $clickLink;
                break;
        }
        return $text;
    }
    
    public function getStoreData($storeId, $multiple=false){
        $storeIds = is_array($storeId)? $storeId : (array)$storeId;
        $user_service = $this->container->get('user_object.service');
        $stores = $user_service->getMultiStoreObjectService($storeIds);
        $response = array();
        if($multiple==false and !empty($stores)){
            $response = array_shift($stores);
        }else{
            $response = $stores;
        }
        return $response;
    }
}