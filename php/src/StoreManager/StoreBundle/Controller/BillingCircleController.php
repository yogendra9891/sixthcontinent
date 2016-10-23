<?php

namespace StoreManager\StoreBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\Transactionshop;
use Notification\NotificationBundle\Document\UserNotifications;
use StoreManager\StoreBundle\Document\BillerCycleLog;

class BillingCircleController extends Controller {

    /**
     * Send registration fee reminder notification
     * 
     */
    public function sendremindernotificationsAction(Request $request) {
        //increase memory size
        set_time_limit(0);
        ini_set('memory_limit','512M');
       
        //get users that have not added 
        $this->getUserNotHaveCreditCard();
        
        //get users that have pending payment
        $this->getShopPendingPayment();
        
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit;
    }
    
    /**
     * Get all shops that have not added credit card
     */
    public function getUserNotHaveCreditCard(){
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
         //send the cc to all the shop
        $shops = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getUserShopNotCc();
        if($shops){
        $msg_type = "CC_NOT_ADDED";
        $msg_status = "A";
        $is_active = 1;
        $type = "type_a";
        $this->sendShopNotification($shops, $msg_type, $msg_status, $is_active, $type);
        return true;
        }
        return true;
    }

    /**
     * Get shops list that have pending 
     */
    public function getShopPendingPayment(){
         //get entity manager object
        $em = $this->getDoctrine()->getManager();
         //send the cc to all the shop
        $shops = $em
                ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                ->getShopsHavePendingPayments();

        if($shops){
        $msg_type = "PENDING_PAYMENT_NOT_DONE";
        $msg_status = "A";
        $is_active = 1;
        $type = "type_b";
        $this->sendShopNotification($shops, $msg_type, $msg_status, $is_active, $type);
        return true;
        }
        return true;
    }

    /**
     * Send Notification
     * @param type $shop_revenue
     * @param string $msg_type
     * @param type $msg_status
     * @param type $is_active
     * @param type $type
     * @return boolean
     */
    public function sendShopNotification($shop_revenue, $msg_type, $msg_status, $is_active, $type) {
       
        $shop_sent_id = array();
        $shop_revenue_single_id = array();
        $shop_ids = array();
         //get locale
        $locale = $this->container->getParameter('locale');
        //get language array
        $lang_array = $this->container->getParameter($locale);

        
        
        //check for message type
        switch ($msg_type) {
            case "CC_NOT_ADDED":
                $mail_body = $lang_array['BILLING_CIRCLE_NO_CC_MAIL_BODY'];
                $mail_subject = $lang_array['BILLING_CIRCLE_REGISTRATION_SUBJECT'];
                break;
            case "PENDING_PAYMENT_NOT_DONE":
                $mail_body = $lang_array['PAYMENT_NOTIFICATION_BODY'];
                $mail_subject = $lang_array['PAYMENT_NOTIFICATION_SUBJECT'];
                break;
        }
        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        //get admin id
        $admin_id = $em
                ->getRepository('TransactionTransactionBundle:RecurringPayment')
                ->findByRole('ROLE_ADMIN');

        //get shop id of sent notification for type N and W
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
      
      
        //get shop id array
         foreach ($shop_revenue as $shop) {
             $shop_ids[] = $shop['storeId'];
         }
         
       //get all shop info
        $user_service = $this->get('user_object.service');
        $store_details = $user_service->getMultiStoreObjectService($shop_ids);
        
        //get shop owners
        $shop_owners = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->getShopOwners($shop_ids);
        
        //check if shop owner
        if($shop_owners){
            $owners = array();
            foreach($shop_owners as $shop_owner){
                $shop_id = $shop_owner['storeId'];
                $owner_id = $shop_owner['userId'];
                $owners[$shop_id] = $owner_id;
            }
        }
        
        
        foreach ($shop_revenue as $shop) {
            $shop_id = $shop['storeId'];
            $store_img = '';
            //get shop info
            
            if(isset($store_details[$shop_id])){
            $store_detail = $store_details[$shop_id];
            //get shop business name
            $shop_bs_name = $store_detail['businessName'];
            $shop_name = $store_detail['name'];
            if($store_detail){
                $store_img = $store_detail['thumb_path'];
            }
            if(isset($owners[$store_detail['id']])){
                    $shop_owner_id = $owners[$store_detail['id']];

                    //$shop_owner_id = $shop_owner_id_array['userId'];

                    //sending the email when a owner invite to a user for a store join.       
                    $userManager = $this->getUserManager();
                    $to_user = $userManager->findUserBy(array('id' => (int) $shop_owner_id));
                    if($to_user){
                    $to_email = $to_user->getEmail();
                    $mail_sub = $mail_subject;
                    $mail_body = $mail_body;
                    $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                    //$link = $host_name.'shop/'.$shop_id;
                    $shop_url = $this->container->getParameter('shop_profile_url');
                    
                    //get click name
                    $click_name = $lang_array['CLICK_HERE'];
                    $message_detail = $lang_array['MESSAGE_DETAIL'];
                    if($msg_type == 'CC_NOT_ADDED'){
                    $link = "<a href='$angular_app_hostname"."$shop_url/$shop_id'>$click_name</a> $message_detail"; 
                    } else{
                        $link = sprintf($lang_array['PAYMENT_NOTIFICATION_LINK'],$shop_name);
                    }
            
                    //check if notification sent
                    $check_shops_notifications_sent = $dm
                            ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                            ->checkLogEntrySent($shop_owner_id, $shop_id);
          

                    // not send the already sent notifications
                    //send social notification
                        $from_id = $admin_id;
                        $msg = $mail_body;
                        $msg_db_type = 'billing_circle';

                        
                    if ($check_shops_notifications_sent == 0) {
                        //send mail @TODO to be enabled
                        //$this->sendEmailNotification($mail_sub, $to_email, $mail_body, $store_img, $shop_owner_id, $link);

                        //send social notification
                        $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_db_type, $msg, $msg_status, $msg_type);
                        
                        //send push notification
                        $this->sendPushNotification($mail_body, $mail_sub, $shop_id, $shop_owner_id);
                        
                        //maintain log for billing circle
                        $this->billerCycleLog($from_id, $shop_owner_id, $shop_id, $msg_db_type, $msg, $msg_status, $is_active, $type, $msg_type, $mail_subject);
                   }
                    }//end to user check
            }//end owner id check
            }//end shop id check
        }//end foreach
        
        //$this->sendintervalbillingnotificationsAction();
        return true;
    }

    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $to_email, $mail_body, $thumb_path, $reciever_id, $link) {
       //$link = null;
       $email_template_service =  $this->container->get('email_template.service');
       $postService = $this->container->get('post_detail.service');
        //send email service
        $receiver = $postService->getUserData($reciever_id, true);
        $emailResponse = $email_template_service->sendMail($receiver, $link, $mail_body, $mail_sub, $thumb_path, 'BILLING_CIRCLE');
        return true;
    }
    
    /**
    * function for gettign the admin id
    * @param None
    */
   private function getAdminId() {
       $em = $this->container->get('doctrine')->getManager();
       $admin_id = $em
               ->getRepository('StoreManagerStoreBundle:Storeoffers')
               ->findByRole('ROLE_ADMIN');
       return $admin_id;
   }
    
    /**
     * 
     * @param type $push_message
     * @param string $message_title
     * @param type $shop_id
     */
    public function sendPushNotification($push_message, $message_title, $shop_id, $user_id) {
         //get locale
        $locale = $this->container->getParameter('locale');
        //get language array
        $lang_array = $this->container->getParameter($locale);
        
        //get shop business name
        $user_service = $this->get('user_object.service');
        $shop_bs_name = '';
        $store_detail = $user_service->getStoreObjectService($shop_id);
        if($store_detail){
        $shop_bs_name = $store_detail['businessName'];
        }
        $push_message_variable = $lang_array['PUSH_PAYMENT_NOTIFICATION_BODY'];
        $push_message = sprintf($push_message_variable, $shop_bs_name);
        /* code for push notification */
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        $angular_host_url = $this->container->getParameter('angular_app_hostname');
        $shop_profile_url = $this->container->getParameter('shop_profile_url');
        $push_message = $push_message;
        $label_of_button = $lang_array['VIEW_SHOP'];
        $redirection_link = "<a href='$angular_host_url"."$shop_profile_url/$shop_id'>$label_of_button</a>";
        $message_title = $message_title;
        //echo $user_id.$push_message.$label_of_button.$redirection_link.$message_title;
        $curl_obj->pushNotification($user_id, $push_message, $label_of_button, $redirection_link, $message_title);
        return true;
    }

    /**
     * 
     * @param int $from_id
     * @param int $shop_owner_id
     * @param int $shop_id
     * @param string $msg_type
     * @param string $msg
     * @return boolean
     */
    public function sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_type, $msg, $msg_status, $msg_code) {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($from_id);
        $notification->setTo($shop_owner_id);
        $notification->setMessageType($msg_type);
        $notification->setMessage($msg_code);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($shop_id);
        $notification->setMessageStatus($msg_status);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * Maintain log
     * @param int $from_id
     * @param int $shop_owner_id
     * @param int $shop_id
     * @param int $msg_type
     * @param int $msg
     * @param int $msg_status
     * @param int $is_active
     * @param int $type
     * @param int $ntype
     * @return boolean
     */
    public function billerCycleLog($from_id, $shop_owner_id, $shop_id, $msg_type, $msg, $msg_status, $is_active, $type, $msg_code, $mail_subj) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
        if($msg_status == 'A'){
            $recurring = 'R';
        }else{
             $recurring = 'S';
        }
        //check if notification sent
        $check_shops_notifications = $dm
                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                ->checkLogEntry($shop_owner_id, $shop_id, $msg_status);
            
       
        if ($check_shops_notifications == 0) {
            $shops_id = (string)$shop_id;
            
           //delete the previous log
           $check_shops_notificationsa = $dm
                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                ->findOneBy(array('shop_id' => $shops_id));
           if($check_shops_notificationsa){
            $dm->remove($check_shops_notificationsa);
            $dm->flush();
           }
            //add new entry
            $billercycle = new BillerCycleLog();
            $billercycle->setFromId($from_id);
            $billercycle->setToId($shop_owner_id);
            $billercycle->setShopId($shop_id);
            $billercycle->setShopObj('');
            $billercycle->setMessage($msg);
            $billercycle->setMessageType($msg_type);
            $billercycle->setMessageStatus($msg_status);
            $billercycle->setIsActive($is_active);
            $billercycle->setDateGroup($type);
            $time = new \DateTime("now");
            $billercycle->setCreatedAt($time);
            $billercycle->setUpdatedAt($time);
            $billercycle->setSendType($recurring);
            $billercycle->setMsgCode($msg_code);
            $billercycle->setMsgSubject($mail_subj);
            $dm->persist($billercycle);
            $dm->flush();
            
      
        } 
        return true;
    }

    /**
     * Send crone notification of type alert.
     * @return boolean
     */
    public function sendintervalbillingnotificationsAction() {
        //increase memory size
        set_time_limit(0);
        ini_set('memory_limit','512M');
        $deactivate_shops = array();
        $notification_ids = array();
        //get locale
        $locale = $this->container->getParameter('locale');
        //get language array
        $lang_array = $this->container->getParameter($locale);
        
        $check_shops_notifications = array();
        // get document manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        //check if notification sent
        $check_shops_notifications = $dm
                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                ->findBy(array('message_status' => 'A', 'is_active' => true));
        if ($check_shops_notifications) {
            foreach ($check_shops_notifications as $check_shops_notification) {
                $created_at = $check_shops_notification->getCreatedAt();
                $updated_at = $check_shops_notification->getUpdatedAt();
                $check_date = strtotime($created_at->format('Y-m-d'));
                $current_date = strtotime(date('Y-m-d'));
                $datediff = $current_date - $check_date;
                $number_days = floor($datediff / (60 * 60 * 24));
                $notification_id = $check_shops_notification->getId();
                
                $check_updated_date = strtotime($updated_at->format('Y-m-d')); //get updated_at time

                //send alter in 2 day interval before 15 days from day of launch
                if ($number_days <= 15) {

                    if ($number_days % 2 == 0 && $number_days != 0) {
                        //send notification
                        //check if mail already sent for same date
                        if($check_updated_date < $current_date ){
                        $this->sendReminderCroneSocialNotification($check_shops_notification);
                        //$this->sendReminderCroneEmailNotification($check_shops_notification); @TODO to be enabled
                        
                         //send push notification
                        $shop_owner_id = $check_shops_notification->getToId();
                        $shop_id = $check_shops_notification->getShopId();
                        $msg = $check_shops_notification->getMessage();
                        $mail_sub = $check_shops_notification->getMsgSubject();
                        
                        $this->sendPushNotification($msg, $mail_sub, $shop_id, $shop_owner_id);
                        $this->updateBillerCycleLog($notification_id); //update biller cycle log for updated_at
                        }
                    }
                } else {
                    //block the shop on db
                    $shop_id = $check_shops_notification->getShopId();
                    $date_group = $check_shops_notification->getDateGroup();

                    //deactivate shop and notifications
                    //prepare shop array
                    $deactivate_shops[] = $shop_id;
                    $notification_ids[] = $notification_id;
                    //$this->deactivateShop($deactivate_shops);
                    $this->deactivateNotifications($notification_id);  
                }
                
            }
               //check if deactivate shop is not null
                if(count($deactivate_shops)>0){
                    $this->deactivateShop($deactivate_shops);
                   //$this->deactivateNotifications($notification_ids); 
                }
        }
        
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit;
    }


    /**
     * Send reminder social notification after 2 days.
     * @param array $check_shops_notification
     * @return boolean
     */
    public function sendReminderCroneSocialNotification($check_shops_notification) {
        //get log detail
        $from_id = $check_shops_notification->getFromId();
        $shop_owner_id = $check_shops_notification->getToId();
        $shop_id = $check_shops_notification->getShopId();
        $msg_type = $check_shops_notification->getMessageType();
        $msg = $check_shops_notification->getMessage();
        $msg_status = $check_shops_notification->getMessageStatus();
        $msg_code = $check_shops_notification->getMsgCode();
        $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_type, $msg, $msg_status, $msg_code);
        return true;
    }

    /**
     * Send reminder email notification after 2 days.
     * @param array $check_shops_notification
     * @return boolean
     */
    public function sendReminderCroneEmailNotification($check_shops_notification) {
        
        //get log detail
        $store_img = '';
        $from_id = $check_shops_notification->getFromId();
        $shop_owner_id = $check_shops_notification->getToId();
        $shop_id = $check_shops_notification->getShopId();
        $msg_type = $check_shops_notification->getMessageType();
        $msg = $check_shops_notification->getMessage();
        $msg_status = $check_shops_notification->getMessageStatus();
        $mail_sub = $check_shops_notification->getMsgSubject();
        $userManager = $this->getUserManager();
        $to_user = $userManager->findUserBy(array('id' => (int) $shop_owner_id));
        $to_email = $to_user->getEmail();
        $mail_body = $msg;
        $msg_code = $check_shops_notification->getMsgCode();
        
        $current_language = $to_user->getCurrentLanguage();
        //get locale
        $locale = !empty($current_language) ? $current_language : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        //get shop info
            $user_service = $this->get('user_object.service');
            $store_detail = $user_service->getStoreObjectService($shop_id);
            if($store_detail){
                $store_img = $store_detail['thumb_path'];
                $shop_name = $store_detail['name'];
            }
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_profile_url');
        
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $message_detail = $lang_array['MESSAGE_DETAIL'];
        if($msg_code == 'CC_NOT_ADDED'){
                   $link = "<a href='$angular_app_hostname"."$shop_url/$shop_id'>$click_name</a> $message_detail";  
        } else{
                   $link = sprintf($lang_array['PAYMENT_NOTIFICATION_LINK'],$shop_name);
        }            
         
        $this->sendEmailNotification($mail_sub, $to_email, $mail_body, $store_img, $shop_owner_id, $link);
        return true;
    }

    /**
     * Deactivate the notifications
     * @param type $notification_id
     */
    public function deactivateNotifications($notification_id) {
        // get document manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notifications = $dm
                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                ->findOneBy(array('id' => $notification_id));
        if ($notifications) {
            $notifications->setIsActive(false);
            $dm->persist($notifications);
            $dm->flush();
        }
        return true;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    
    /**
     * Update biller cycle log
     * @param int $notification_id
     * @return boolean
     */
    public function updateBillerCycleLog($notification_id){
        // get document manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $time = new \DateTime("now");
        $notifications = $dm
                ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                ->findOneBy(array('id' => $notification_id));
        if ($notifications) {
            $notifications->setUpdatedAt($time);
            $dm->persist($notifications);
            $dm->flush();
        }
        return true;
    }
    
    /**
     * Deactivate the shop
     * @param $shop_id
     * @return boolean
     */
    public function deactivateShop($shop_ids){
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
         $check_shops_registration = $em
              ->getRepository('StoreManagerStoreBundle:Store')
               ->deactivateShops($shop_ids);
         // BLOCK SHOPPING PLUS
        //block shop on shopping plus
//         foreach($shop_ids as $shop_id){
//              $shopping_plus_obj = $this->container->get("store_manager_store.shoppingplus");
//              $shop_deactive_output = $shopping_plus_obj->changeStoreStatusOnShoppingPlus($shop_id, 'D');
//         }
//        if ($check_shops_registration) {
//        $time = new \DateTime("now");
//        $check_shops_registration = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->findOneBy(array('id' => $shop_id));
//        if ($check_shops_registration) {
//            //block the shop
//            $check_shops_registration->setShopStatus(0);
//            $check_shops_registration->setUpdatedAt($time); //set the update time..
//            $em->persist($check_shops_registration);
//            $em->flush();
//
//            //block shop on shopping plus
//            //$shopping_plus_obj = $this->container->get("store_manager_store.shoppingplus");
//            //$shop_deactive_output = $shopping_plus_obj->changeStoreStatusOnShoppingPlus($shop_id, 'D');
//        }
       return true;
    }

}
