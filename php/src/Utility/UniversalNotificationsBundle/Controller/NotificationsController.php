<?php

namespace Utility\UniversalNotificationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UniversalNotificationsBundle\Controller\TokenAuthenticatedController;
use Notification\NotificationBundle\Document\UserNotifications;
use Utility\UniversalNotificationsBundle\Services\PushLogService;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\UniversalNotificationsBundle\Utils\MessageFactory as Msg;


class NotificationsController extends Controller 
//implements TokenAuthenticatedController
{
    public function notificationSendAction(Request $request){
        $this->_log('Transaction notifications intiated.');
        $this->_log('Request data found. '. $request->getContent());
        
        $chk_params = array('from_id', 'to_id', 'message_type', 'message_code', 'item_id', 'info');
        $utilityService = $this->getUtilityService();
        if (($result = $utilityService->checkRequest($request, $chk_params)) !== true) {
            $this->_log('[Notifications:notificationSend] Missing parameters');
            $resp_data = new Resp(100, 'Missing parameters', array());
            return Utility::createResponse($resp_data);
        }

        $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
        
        $this->_log('Getting user data for id: '.$de_serialize['from_id']);
        $sender = $this->getUserData($de_serialize['from_id']);
        $this->sendNotification($de_serialize, $sender);        
        $this->_log('Transaction process has been finished');
        $response = array('code'=>101, 'message'=>'Notifications has been sent.');
        echo json_encode($response);
        exit;
    }
    
    protected function sendNotification($_data, $sender){
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
    
    protected function getStoreOwner($shopIds, $excludeFrom=array()){
        $em = $this->getDoctrine()->getManager();
        $shops = $em
                    ->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->getShopOwners($shopIds, $excludeFrom);
        $shopOwners = array();
        if(!empty($shops)){
            foreach($shops as $shop){
                $shopOwners[$shop['storeId']] = $shop['userId'];
            }
        }
        return $shopOwners;
    }
    
    protected function getUserData($userId, $multiple=false){
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

    private function getReadyNotificationData($data, $sender=array()){
        $store = array();
        $receivers = $this->getUserData($data['to_id'], true);
        $receiversByLang = $this->getReceiversByLang($receivers);
        $response = array();
        $i=0;
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
    
    protected function getPushText($type, $replaceText='', $lang=null){
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
    
    protected function getEmailText($type, $replaceText='', $lang=null, $link_id=null){
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
    
    /**
     * Get all transaction web notifications
     * @uses /api/get_transaction_notifications
     * @param Request $request
     * @return JSON
     */
    public function getTransactionNotificationsAction(Request $request)
    {
        $required_parameter = array('user_id');
        $utilityService = $this->getUtilityService();
        if (($result = $utilityService->checkRequest($request, $required_parameter)) !== true) {
            $this->_log('[Notifications:getTransactionNotifications] Missing parameters');
            $resp_data = new Resp($result['code'], $result['message'], array());
            return Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $limit_start = (isset($data['limit_start']) && $data['limit_start'] !='') ? $data['limit_start'] : 0;
        $limit_size   = (isset($data['limit_size']) && $data['limit_size'] !='') ? $data['limit_size'] : 50;
        
        /** get documen manager object **/
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        // update is_viewed
        $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getisviewUpdateUserNotification($user_id,1, array(), array('TXN','BUY_ECOMMERCE_PRODUCT')); 
        
        $notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getAllTransactionNotification($user_id,$limit_start,$limit_size, false, 7);
        $notifications_count = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getAllTransactionNotificationCount($user_id, false, 7);
        $response = array();
        if(count($notifications) >0) {
            /** getting the senders user id. **/
            $sender_user_ids = array_map(function($record) {
                return "{$record->getFrom()}";
            }, $notifications);
            
            /** getting the recivere user id. **/
            $reciever_user_ids = array_map(function($record) {
                return "{$record->getTo()}";
            }, $notifications);
            
            /** fetch user object **/
            $users_ids = array_unique(array_merge($reciever_user_ids,$sender_user_ids));
            $users = $this->getUserData($users_ids, true);
            try{
                foreach($notifications as $notification){
                    $notification_id = $notification->getId();
                    /** get $from user object **/
                    $from_id= $notification->getFrom();
                    $message_type = $notification->getMessageType();
                    $message = $notification->getMessage();
                    $message_status = $notification->getMessageStatus();
                    $info = $notification->getInfo();
                    $store_info = isset($info['store_info']) ? $info['store_info'] : array();
                    if(isset($info['store_info'])){
                        unset($info['store_info']);
                    }
                    
                    $response[] = array(
                        'notification_id' => $notification_id,
                        'notification_from' => isset($users[$from_id]) ? $users[$from_id] : array(),
                        'message_type' => $message_type,
                        'message_status'=>$message_status ,                
                        'message' => $message, 
                        'transaction_info' => $info,
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date' => $notification->getDate(),
                        'shop_info'=>$store_info
                    );
                }
            }catch(\Exception $e){
                
            }
            
        }
        $resp_data = new Resp(
                Msg::getMessage(101)->getCode(),
                Msg::getMessage(101)->getMessage(),
                array(
                    'requests' => $response,
                    'size' => $notifications_count
                    )
                );
            return Utility::createResponse($resp_data);
    }
    
    private function getStoreData($storeId, $multiple=false){
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
    
    private function getReceiversByLang(array $users){
        $postService = $this->container->get('post_detail.service');
        $response = $postService->getUsersByLanguage($users);
        return $response;
    }
    
    private function _log($sMessage){
        $logger = new PushLogService();
        $logger->log($sMessage);
    }
    
    private function _sendEmail($toIds, array $data, $category){
        $email_template_service = $this->container->get('email_template.service');
        $receivers = $this->getUserData($toIds, true);
        $bodyData = isset($data['text']) ? $data['text'] : '';
        $bodyTitle = isset($data['title']) ? $data['title'] : '';
        $subject = isset($data['subject']) ? $data['subject'] : '';
        $thumb = isset($data['thumb']) ? $data['thumb'] : '';
        return $email_template_service->sendMail($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category);
    }
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility'); //StoreManager\StoreBundle\Utils\UtilityService
    }
}
