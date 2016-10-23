<?php

namespace Utility\ApplaneIntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use CardManagement\CardManagementBundle\Entity\Contract;
use Transaction\TransactionBundle\Document\RecurringPaymentLog;
use Notification\NotificationBundle\Document\UserNotifications;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionDetail;
use Utility\ApplaneIntegrationBundle\Document\TransactionNotificationLog;

class TransactionNotificationController extends Controller {
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
     public function sendtransactionnotificationAction(Request $request) {
         $shops_array = array();
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //get admin id
        $admin_id = $em
                ->getRepository('TransactionTransactionBundle:RecurringPayment')
                ->findByRole('ROLE_ADMIN');
        //get shops that have total revenue greater than 200 and not paid the registration fee
//        $total_shop_revenues = $em
//                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
//                        ->getAllShopTotalRevenue();
        $total_shop_revenues = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getShopRegistrationFee();
        
         if($total_shop_revenues){
         //check for shop revenue. get shop total revenue from applane
         foreach($total_shop_revenues as $total_shop_revenue){
         $shop_id = $total_shop_revenue['storeId'];//get store id
         $applane_service = $this->container->get('appalne_integration.callapplaneservice');
         $shop_revenue_val = $applane_service->getShopRevenueFromApplaneByDate($shop_id); //greater than 20 April  
         if($shop_revenue_val > 200){
            $shops_array[] = $total_shop_revenue;
         }
         }
         $type = "REG_FEE_NOT_PAID";
         //send notifications
         $this->sendNotification($shops_array, $type, $admin_id);
         //}
         }
         
        //send notifications(web/push) for failed pending payment.
        //$this->pendingPaymentFailedNotification($admin_id); 
        
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
     }
     
     /**
      * Send notification
      * @param int $revenue
      * @param int $receiver_id
      * @param int $store_id
      * @param string $type
      */
     public function sendNotification($shop_info, $msg_type, $admin_id)
     {
        $shop_sent_id = array();
        $shop_revenue_single_id = array();
        $shop_ids = array();
        $is_block = 0;
         //get locale
        $locale = $this->container->getParameter('locale');
        //get language array
        $lang_array = $this->container->getParameter($locale);

        //check for message type
        switch ($msg_type) {
            case "REG_FEE_NOT_PAID":
                $mail_body = $lang_array['REG_FEE_NOT_PAID_MAIL_BODY'];
                $mail_subject = $lang_array['REG_FEE_NOT_PAID_SUBJECT'];
                $mail_body_header = $lang_array['REG_FEE_NOT_PAID_MAIL_BODY_HEADER'];
                $msg_code = "REG_FEE_NOT_PAID";
                $msg_role = "RECURRING_NOTIFICATION";
                $msg_code_push = "REG_FEE_NOT_PAID";
                $msg_role_push = "RECURRING_NOTIFICATION_PUSH";
                break;
        }
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $owners = array();
        //get shop id array
         foreach ($shop_info as $shop) {
             $shop_ids[] = $shop['storeId'];
             $shop_id_s = $shop['storeId'];
             $owners[$shop_id_s] = $shop['store_owner'];
         }
         
       //get all shop info
        $user_service = $this->get('user_object.service');
        $store_details = $user_service->getMultiStoreObjectService($shop_ids);
        
//        //get shop owners
//        $shop_owners = $em
//                ->getRepository('StoreManagerStoreBundle:UserToStore')
//                ->getShopOwners($shop_ids);
//
//        //check if shop owner
//        if($shop_owners){
//            $owners = array();
//            foreach($shop_owners as $shop_owner){
//                $shop_id = $shop_owner['storeId'];
//                $owner_id = $shop_owner['userId'];
//                $owners[$shop_id] = $owner_id;
//            }
//        }
        
        
        foreach ($shop_info as $shop) {
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
                    //sending the email when a owner invite to a user for a store join.       
                    $userManager = $this->getUserManager();
                    $to_user = $userManager->findUserBy(array('id' => (int) $shop_owner_id));
                    if($to_user){
                    $to_email = $to_user->getEmail();
                    $mail_sub = $mail_subject;
                    $mail_body = $mail_body;
                    $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                    //$link = $host_name.'shop/'.$shop_id;
                    $shop_url = $this->container->getParameter('shop_wallet_url');
                    
                    //get click name
                    $click_name = $lang_array['CLICK_HERE'];
                    $message_detail = $lang_array['MESSAGE_DETAIL'];
                    $link = "<a href='$angular_app_hostname"."$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail"; 
                    $mail_body = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY'],$shop_name, '7',$link);
                    $mail_body_header = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);
                    
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    //check if notification sent in same date
                    $check_shops_notifications_sent = $dm
                            ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                            ->checkNotificationSent($shop_id);

                    $check_shops_notifications_sent_status = 0;
                    
                    if($check_shops_notifications_sent){
                        $notification_id = $check_shops_notifications_sent->getId();
                        $updated_at = $check_shops_notifications_sent->getUpdatedDate();
                        $check_date = strtotime($updated_at->format('Y-m-d'));
                        $current_date = strtotime(date('Y-m-d'));
                        $send_count = $check_shops_notifications_sent->getSendCount();
                        $remain_count = (7 - $send_count);
                        $mail_body = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY'], $shop_name, $remain_count, $link);
                        if($check_date == $current_date){
                             $check_shops_notifications_sent_status = 1;
                        }
                         $is_block  = 0;
                             //block the shop if shop got notification more than 7
                             if($send_count >= 7 ){
                                 $is_block  = 1;
                                 $this->deleteNotifications($notification_id);
                                 //block shop
                                 $this->blockShop($shop_id);
                                 //send notification for blocking the shop
                                 $mail_sub = $lang_array['BLOCK_SHOP_SUBJECT'];
                                 $block_mail_body = $lang_array['BLOCK_SHOP_MAIL_BODY'];
                                 $mail_body = sprintf($block_mail_body, $shop_bs_name, $shop_bs_name, $link);
                                 $mail_body_header = $lang_array['BLOCK_SHOP_MAIL_BODY_HEADER'];
                                 $msg_code = "SHOP_BLOCKED";
                                 $msg_role_push = "RECURRING_NOTIFICATION_PUSH";
                             }
                    }
                   
                    // not send the already sent notifications
                    //send social notification
                    $from_id = $admin_id;
                    $msg = $mail_body;
                    $msg_db_type = 'REG_FEE_NOT_PAID';
                    if ($check_shops_notifications_sent_status == 0) {
                        //send mail
                       $this->sendEmailNotification($mail_sub, $to_email, $mail_body_header, $store_img, $shop_owner_id, $mail_body);
                        //send social notification
                        $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code);                        
                        //send push notification
                        $this->sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role_push, $msg_code_push);                      
                        //maintain log for billing circle
                        if( $is_block  != 1){
                        $this->billerCycleLog($from_id, $shop_owner_id, $shop_id, $msg_type);
                        }
                   }
                    }//end to user check
            }//end owner id check
            }//end shop id check
        }//end foreach
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
     * Maintain log
     * @param int $from_id
     * @param int $shop_owner_id
     * @param int $shop_id
     * @param int $msg_type
     * @return boolean
     */
    public function billerCycleLog($from_id, $shop_owner_id, $shop_id, $msg_type) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $shops_id = (int)$shop_id;
        $time = new \DateTime("now");
        $start_time = new \DateTime("now");
        $end_time = $time->modify('+1 week'); //add 7 days.
        //delete the previous log
        $check_shops_notifications = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->findOneBy(array('to_shop_id' => $shops_id));

        if ($check_shops_notifications) {
        $send_count = $check_shops_notifications->getSendCount();
        $update_send_count = ((int)$send_count+1);
        //add new entry
        $check_shops_notifications->setUpdatedDate($start_time);
        $check_shops_notifications->setSendCount($update_send_count);
        $check_shops_notifications->setNotificationType($msg_type);
        $dm->persist($check_shops_notifications);
        $dm->flush();

    }else{
        $txlog = new TransactionNotificationLog();
        $txlog->setToUserId($shop_owner_id);
        $txlog->setToShopId($shop_id);
        $txlog->setIsActive(1);
        $txlog->setStartDate($start_time);
        $txlog->setEndDate($end_time);
        $txlog->setUpdatedDate($start_time);
        $txlog->setSendCount(1);
        $txlog->setNotificationType($msg_type);
        $dm->persist($txlog);
        $dm->flush();
    }
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
    public function sendEmailNotification($mail_sub, $to_email, $mail_body_header, $thumb_path, $reciever_id, $mail_body) {
       //$link = null;
       $email_template_service =  $this->container->get('email_template.service');
       $postService = $this->container->get('post_detail.service');
       //send email service
       $receiver = $postService->getUserData($reciever_id, true);
       $emailResponse = $email_template_service->sendMail($receiver, $mail_body, $mail_body_header, $mail_sub, $thumb_path, 'BILLING_CIRCLE', $attachment=null, $mailDelay=2 , $is_email = 0, $is_shop=1);
       return true;
    }
    
    /**
     * Send SOcial Notification
     * @param int $from_id
     * @param int $shop_owner_id
     * @param int $shop_id
     * @param string $msg_type
     * @param string $msg
     * @return boolean
     */
    public function sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code) {

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($from_id);
        $notification->setTo($shop_owner_id);
        $notification->setMessageType($msg_role);
        $notification->setMessage($msg_code);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($shop_id);
        $notification->setMessageStatus('A');
        $dm->persist($notification);
        $dm->flush();
        return true;
    }
    
    /**
     * Block Shop
     * @param int $shop_id
     */
    public function blockShop($shop_id)
    {
         // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        $check_shops_registration = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if ($check_shops_registration) {
            //block the shop
            $check_shops_registration->setShopStatus(0);
            $check_shops_registration->setUpdatedAt($time); //set the update time..
            $em->persist($check_shops_registration);
            $em->flush();
            return true;
        }
    }
    
    /**
     * Delete notifications
     */
    public function deleteNotifications($id){
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //delete the previous log
           $notifications = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->findOneBy(array('id' => $id));
           if($notifications){
            $dm->remove($notifications);
            $dm->flush();
           }
           return true;
    }
    
    /**
     * Save push notification
     * @param int $from_id
     * @param int $shop_owner_id
     * @param int $shop_id
     * @param string $msg_role
     * @param string $msg_code
     * @return boolean
     */
    public function sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code)
    {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($from_id);
        $notification->setTo($shop_owner_id);
        $notification->setMessageType($msg_role);
        $notification->setMessage($msg_code);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($shop_id);
        $notification->setMessageStatus('A');
        $dm->persist($notification);
        $dm->flush();
        return true;
    }
    
    /**
     * send the pending payment failed notification web, push and email
     * @param type $admin_id
     */
    public function pendingPaymentFailedNotification($admin_id) {
        $time = new \DateTime('now');
        $shop_ids = $user_ids = array();
        $current_time_date = new \DateTime('now');
        $current_date      = $current_time_date->format('Y-m-d');
        //get odm manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $transaction_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                               ->findTransactionPaymentLogs(); //find active logs

        //code for get store and user detail start
        //get shop id and user ids array
         foreach ($transaction_logs as $record) {
             $shop_ids[] = $record->getToShopId();
             $user_ids[] = $record->getToUserId();
          }
        //get all shop info
        $user_service        = $this->get('user_object.service');
        $store_details       = $user_service->getMultiStoreObjectService($shop_ids);
        $store_owner_details = $user_service->MultipleUserObjectService($user_ids);
        //code for store detail and user detail end 
        
        foreach ($transaction_logs as $transaction_log) {
            $user_id = $transaction_log->getToUserId(); //shop owner id
            $shop_id = $transaction_log->getToShopId();
            $count   = $transaction_log->getSendCount();
            $last_updated_at = $transaction_log->getUpdatedDate();
            $updated_at   = $last_updated_at->format('Y-m-d');
            $store_detail = $store_details[$shop_id];
            $user_info    = $store_owner_details[$user_id];
            $is_to_be_sent = 0; //variable for decide the notification to be sent or not.
            //email code       
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($user_id, true);
            $lng      = $receiver[$user_id]['current_language'];
            $locale =  empty($lng)? $this->container->getParameter('locale') : $lng;  
			
            //get language array
            $lang_array = $this->container->getParameter($locale);
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $shop_name = $store_detail['name'];
            $shop_url         = $this->container->getParameter('shop_wallet_url');
            $mail_sub         = $lang_array['PENDING_PAYMENT_NOT_PAID_SUBJECT'];
            $mail_body_header = sprintf($lang_array['PENDING_PAYMENT_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);
            //get click name
            $click_name     = $lang_array['CLICK_HERE'];
            $message_detail = $lang_array['MESSAGE_DETAIL'];
            $store_img      = $store_detail['thumb_path'];
            $shop_bs_name   = $store_detail['businessName'];
            $shop_name      = $store_detail['name'];
            $link = "<a href='$angular_app_hostname"."$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";
            $mail_body = sprintf($lang_array['PENDING_PAYMENT_PAID_MAIL_BODY'], $shop_name, (7-$count), $link);
            $to_email  = $user_info['email'];
            //email code end
        
            if ($count >= 7) {
                $msg_code_push = "SHOP_BLOCKED";
                $msg_role_push = "RECURRING_NOTIFICATION_PUSH";
                $msg_code      = 'SHOP_BLOCKED';
                $msg_role      = 'RECURRING_NOTIFICATION';
                $is_to_be_sent = 1;
                $dm->remove($transaction_log);
                //update the shop status.
                $this->blockShop($shop_id);//block the shop
                $mail_sub         = $lang_array['BLOCK_SHOP_SUBJECT'];
                $block_mail_body  = $lang_array['BLOCK_SHOP_MAIL_BODY'];
                $mail_body        = sprintf($block_mail_body, $shop_bs_name, $shop_bs_name, $link);
                $mail_body_header = $lang_array['BLOCK_SHOP_MAIL_BODY_HEADER'];
            } else {
                $msg_code_push = "PENDING_FEE_NOT_PAID";
                $msg_role_push = "RECURRING_NOTIFICATION_PUSH";
                $msg_role      = 'RECURRING_NOTIFICATION';
                $msg_code      = 'PENDING_FEE_NOT_PAID';
                $new_counter   = $count + 1;
                if (($count == 0) || ($current_date != $updated_at)) { //check if notification count is sending first time and not sending again in the same day.
                    //set the counter and updated for the same record.
                    $transaction_log->setSendCount($new_counter);
                    $transaction_log->setUpdatedDate($time);
                    $dm->persist($transaction_log); 
                    $is_to_be_sent = 1;
                }                
            }
            try {
                if ($is_to_be_sent == 1) { //when we need to sent the notification.
                    $this->savePaymentFailedPushNotification($user_id, $shop_id, $admin_id, $msg_role_push, $msg_code_push); //save push log
                    $this->savePaymentFailedWebNotification($user_id, $shop_id, $admin_id, $msg_role, $msg_code); //save social log
                    $this->sendEmailNotification($mail_sub, $to_email, $mail_body_header, $store_img, $user_id, $mail_body); //sen email notification
                    $dm->flush();
                }
            } catch (\Exception $ex) {}
        }
        return true;
    }
    
    /**
     * save push notification for failed pending payment
     * @param int $user_id
     * @param int $shop_id
     * @param int $admin_id
     */
    public function savePaymentFailedPushNotification($user_id, $shop_id, $admin_id, $msg_role_push, $msg_code_push) {
        $from_id       = $admin_id;
        $shop_owner_id = $user_id;
        $this->sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role_push, $msg_code_push); //save notification for push type
        return true;        
    }
    
    /**
     * save web notification for failed pending payment
     * @param int $user_id
     * @param int $shop_id
     * @param int $admin_id
     */
    public function savePaymentFailedWebNotification($user_id, $shop_id, $admin_id, $msg_role, $msg_code) {
        $from_id       = $admin_id;
        $shop_owner_id = $user_id;
        $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code); //save notification for web type.
        return true;
    }
    
    /**
     * call payment notification logs
     * 
     */
    public function callpaymentnotificationlogAction() {
       // exit('do not call me.');
        $response = $this->pendingPaymentFailedNotification(3); exit('send notification'); //here 3 is admin id.
    }
}