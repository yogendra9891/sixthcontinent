<?php

namespace Utility\ApplaneIntegrationBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionsPayment;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\ApplaneIntegrationBundle\Document\TransactionNotificationLog;
use Utility\ApplaneIntegrationBundle\Document\TransactionPaymentNotificationLog;
use Notification\NotificationBundle\Document\UserNotifications;

// service method  class
class RecurringShopPaymentNotificationService {

    protected $em;
    protected $dm;
    protected $container;

    CONST PENDING_PAYMENT = "PENDING_PAYMENT";
    CONST SUBSCRIBED = 'SUBSCRIBED';
    CONST UNSUBSCRIBED = 'UNSUBSCRIBED';
    CONST PENDING = 'PENDING';
    CONST DAYS_COUNTER = 8;
    CONST SHOP_SUBSCRIPTION_BLOCKED = "SHOP_SUBSCRIPTION_BLOCKED";
    CONST SUBSCRIPTION_NOTIFICATION_PUSH = "SUBSCRIPTION_NOTIFICATION_PUSH";
    CONST SUBSCRIPTION_RECURRING_NOTIFICATION = "SUBSCRIPTION_RECURRING_NOTIFICATION";
    CONST SUBSCRIPTION_FEE_NOT_PAID = "SUBSCRIPTION_FEE_NOT_PAID";
    CONST REG_FEE_NOT_PAID = "REG_FEE_NOT_PAID";
    CONST RECURRING_NOTIFICATION_PUSH = "RECURRING_NOTIFICATION";
    CONST RECURRING_NOTIFICATION = "RECURRING_NOTIFICATION";
    CONST SHOP_BLOCKED = "SHOP_BLOCKED";
    CONST PENDING_FEE_NOT_PAID = "PENDING_FEE_NOT_PAID";

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
        //$this->request   = $request;
    }

    /**
     * send notification to shop owner whose pending payment failed
     */
    public function sendtransactionnotification() {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring_notification_log');
        $monolog_data = "Entering In RecurringShopPaymentNotificationService->sendtransactionnotification";
        $applane_service->writeAllLogs($handler, $monolog_data, array());

        //get entity manager object
        $em = $this->em;
        $shops_array = array();
        //get admin id
        $admin_id = $em
                ->getRepository('TransactionTransactionBundle:RecurringPayment')
                ->findByRole('ROLE_ADMIN');

        //send notifications(web/push) for failed pending payment.
        $this->registrationFailedNotification($admin_id);

        //send notifications(web/push) for failed pending payment.
        $this->pendingPaymentFailedNotification($admin_id);

        //send notification(web/push) for failed subscription payment
        $this->pendingSubscriptionPaymentFailedNotification($admin_id);
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        $monolog_data = "Exiting From RecurringShopPaymentNotificationService->sendtransactionnotification:" . json_encode($resp_data);
        $applane_service->writeAllLogs($handler, $monolog_data, array());
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
    public function sendNotification($shop_info, $msg_type, $admin_id) {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring_notification_log');
        $monolog_data = "Entering In RecurringShopPaymentNotificationService->sendNotification";
        $applane_service->writeAllLogs($handler, $monolog_data, array());

        $shop_sent_id = array();
        $shop_revenue_single_id = array();
        $shop_ids = array();
        $is_block = 0;
        $owners = array();
        //get shop id array
        foreach ($shop_info as $shop) {
            $shop_ids[] = $shop['storeId'];
            $shop_id_s = $shop['storeId'];
            $owners[$shop_id_s] = $shop['store_owner'];
        }

        //get all shop info
        $user_service = $this->container->get('user_object.service');
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
            $push_remain_count = 7;
            //get shop info

            if (isset($store_details[$shop_id])) {
                $store_detail = $store_details[$shop_id];
                //get shop business name
                $shop_bs_name = $store_detail['businessName'];
                $shop_name = $store_detail['name'];
                if ($store_detail) {
                    $store_img = $store_detail['thumb_path'];
                }
                if (isset($owners[$store_detail['id']])) {
                    $shop_owner_id = $owners[$store_detail['id']];
                    //sending the email when a owner invite to a user for a store join.       
                    $userManager = $this->getUserManager();
                    $to_user = $userManager->findUserBy(array('id' => (int) $shop_owner_id));
                    if ($to_user) {
                        $postService = $this->container->get('post_detail.service');
                        $receiver = $postService->getUserData($shop_owner_id, true);
                        $lng = $receiver[$shop_owner_id]['current_language'];
                        $locale = empty($lng) ? $this->container->getParameter('locale') : $lng;
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
                                $msg_role_push = "RECURRING_NOTIFICATION";
                                break;
                        }
                        $to_email = $to_user->getEmail();
                        $mail_sub = $mail_subject;
                        $mail_body = $mail_body;
                        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                        //$link = $host_name.'shop/'.$shop_id;
                        $shop_url = $this->container->getParameter('shop_wallet_url');
                        //get click name
                        $click_name = $lang_array['CLICK_HERE'];
                        $message_detail = $lang_array['MESSAGE_DETAIL'];
                        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";
                        $mail_body = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY'], $shop_name, '7', $link);
                        $mail_body_header = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);

                        $dm = $this->dm;
                        //check if notification sent in same date
                        $check_shops_notifications_sent = $dm
                                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                                ->checkNotificationSent($shop_id);

                        $check_shops_notifications_sent_status = 0;

                        if ($check_shops_notifications_sent) {
                            $notification_id = $check_shops_notifications_sent->getId();
                            $updated_at = $check_shops_notifications_sent->getUpdatedDate();
                            $check_date = strtotime($updated_at->format('Y-m-d'));
                            $current_date = strtotime(date('Y-m-d'));
                            $send_count = $check_shops_notifications_sent->getSendCount();
                            $remain_count = (7 - $send_count);
                            $push_remain_count = $remain_count;
                            $mail_body = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY'], $shop_name, $remain_count, $link);
                            if ($check_date == $current_date) {
                                $check_shops_notifications_sent_status = 1;
                            }
                            $is_block = 0;
                            //block the shop if shop got notification more than 7
                            if ($send_count >= 7) {
                                $is_block = 1;
                                $this->deleteNotifications($notification_id);
                                //block shop
                                $this->blockShop($shop_id);
                                //send notification for blocking the shop
                                $mail_sub = $lang_array['BLOCK_SHOP_SUBJECT'];
                                $block_mail_body = $lang_array['BLOCK_SHOP_MAIL_BODY'];
                                $mail_body = sprintf($block_mail_body, $shop_bs_name, $shop_bs_name, $link);
                                $mail_body_header = $lang_array['BLOCK_SHOP_MAIL_BODY_HEADER'];
                                $msg_code = "SHOP_BLOCKED";
                                $msg_code_push = "SHOP_BLOCKED";
                                $msg_role_push = "RECURRING_NOTIFICATION";
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
                            $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code, $push_remain_count);
                            //send push notification
                            $this->sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role_push, $msg_code_push, $push_remain_count);
                            //maintain log for billing circle
                            if ($is_block != 1) {
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
        $dm = $this->dm;
        $shops_id = (int) $shop_id;
        $time = new \DateTime("now");
        $start_time = new \DateTime("now");
        $end_time = $time->modify('+1 week'); //add 7 days.
        //delete the previous log
        $check_shops_notifications = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->findOneBy(array('to_shop_id' => $shops_id));

        if ($check_shops_notifications) {
            $send_count = $check_shops_notifications->getSendCount();
            $update_send_count = ((int) $send_count + 1);
            //add new entry
            $check_shops_notifications->setUpdatedDate($start_time);
            $check_shops_notifications->setSendCount($update_send_count);
            $check_shops_notifications->setNotificationType($msg_type);
            $dm->persist($check_shops_notifications);
            $dm->flush();
        } else {
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
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring_notification_log');
        $monolog_data = "Entering In RecurringShopPaymentNotificationService->sendEmailNotification: Receiver Emai:" . $to_email;
        $applane_service->writeAllLogs($handler, $monolog_data, array());

        //$link = null;
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        //send email service
        $receiver = $postService->getUserData($reciever_id, true);
        $emailResponse = $email_template_service->sendMail($receiver, $mail_body, $mail_body_header, $mail_sub, $thumb_path, 'BILLING_CIRCLE', $attachment = null, $mailDelay = 2, $is_email = 0, $is_shop = 1);

        //maintain log
        $monolog_data = "Exit From RecurringShopPaymentNotificationService->sendEmailNotification: Response" . json_encode($emailResponse);
        $applane_service->writeAllLogs($handler, array(), $monolog_data);
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
    public function sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code, $push_remain_count) {
        $info = array('days' => $push_remain_count);
        $dm = $this->dm;
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
        $notification->setInfo($info);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * Block Shop
     * @param int $shop_id
     */
    public function blockShop($shop_id) {
        // get entity manager object
        $em = $this->em;
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
    public function deleteNotifications($id) {
        $dm = $this->dm;
        //delete the previous log
        $notifications = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->findOneBy(array('id' => $id));
        if ($notifications) {
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
    public function sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code, $push_remain_count = null) {
        $postService = $this->container->get('post_detail.service');
        $isWeb = false;
        $isPush = true;
        // push
        $msgtype = $msg_role;
        $msg = $msg_code;
        $extraParams = array('store_id' => $shop_id);
        $itemId = $shop_id;
        $reciever_id = $shop_owner_id;
        $postService->sendUserNotifications($reciever_id, $reciever_id, $msgtype, $msg, $itemId, $isWeb, $isPush, $push_remain_count, 'CITIZEN', $extraParams, 'T');

        /*
          $dm = $this->dm;
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
          $dm->flush(); */
        return true;
    }

    /**
     * send the pending payment failed notification web, push and email
     * @param type $admin_id
     */
    public function pendingPaymentFailedNotification($admin_id) {
        $handler = $this->container->get('monolog.logger.recurring_notification_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [pendingPaymentFailedNotification]', array());

        $time = new \DateTime('now');
        $shop_ids = $user_ids = array();
        $current_time_date = new \DateTime('now');
        $current_date = $current_time_date->format('Y-m-d');
        //get odm manager object
        $dm = $this->dm;
        $transaction_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                ->findTransactionPaymentLogs(); //find active logs
        //code for get store and user detail start
        //get shop id and user ids array
        foreach ($transaction_logs as $record) {
            $shop_ids[] = $record->getToShopId();
            $user_ids[] = $record->getToUserId();
        }
        //get all shop info
        $user_service = $this->container->get('user_object.service');
        $store_details = $user_service->getMultiStoreObjectService($shop_ids);
        $store_owner_details = $user_service->MultipleUserObjectService($user_ids);
        //code for store detail and user detail end 

        foreach ($transaction_logs as $transaction_log) {
            $user_id = $transaction_log->getToUserId(); //shop owner id
            $shop_id = $transaction_log->getToShopId();
            $count = $transaction_log->getSendCount();
            $last_updated_at = $transaction_log->getUpdatedDate();
            $updated_at = $last_updated_at->format('Y-m-d');
            $store_detail = $store_details[$shop_id];
            $user_info = $store_owner_details[$user_id];
            $is_to_be_sent = 0; //variable for decide the notification to be sent or not.
            //email code       
            //get locale
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($user_id, true);
            $lng = $receiver[$user_id]['current_language'];
            $locale = empty($lng) ? $this->container->getParameter('locale') : $lng;
            $shop_name = $store_detail['name'];
            $store_img = $store_detail['thumb_path'];
            $shop_bs_name = $store_detail['businessName'];
            $days_counter = (int) self::DAYS_COUNTER;
            $remain_count = ($days_counter - $count);
            $to_email = $user_info['email'];
            //email code end

            if ($count >= $days_counter) {
                $msg_code_push = self::SHOP_BLOCKED;
                $msg_role_push = self::RECURRING_NOTIFICATION_PUSH;
                $msg_code = self::SHOP_BLOCKED;
                $msg_role = self::RECURRING_NOTIFICATION;
                $is_to_be_sent = 1;
                $applane_service->writeAllLogs($handler, 'Notification for shop blocked to be sent, Notification log will be removed', array());
                $dm->remove($transaction_log); //remove the log 
                $mail_data = $this->prepareShopBlockNotificationMailText($locale, $store_detail, $remain_count, $shop_id);//prepare mail text
                $mail_sub = $mail_data['mail_sub'];
                $mail_body_header = $mail_data['mail_body_header'];
                $mail_body = $mail_data['mail_body'];
            } else {
                $msg_code_push = self::PENDING_FEE_NOT_PAID;
                $msg_role_push = self::RECURRING_NOTIFICATION_PUSH;
                $msg_role = self::RECURRING_NOTIFICATION;
                $msg_code = self::PENDING_FEE_NOT_PAID;
                $applane_service->writeAllLogs($handler, 'Notification to be sent for shop pending payment.', array());
                $is_to_be_sent = 1;
                $mail_data = $this->preparePendingFailNotificationMailText($locale, $store_detail, $remain_count, $shop_id);//prepare mail text
                $mail_sub = $mail_data['mail_sub'];
                $mail_body_header = $mail_data['mail_body_header'];
                $mail_body = $mail_data['mail_body'];
            }
            try {
                if (($is_to_be_sent == 1) && ($store_detail['isActive'] == 1)) { //when we need to sent the notification.
                    $this->savePaymentFailedPushNotification($user_id, $shop_id, $admin_id, $msg_role_push, $msg_code_push, $remain_count); //save push log
                    $this->savePaymentFailedWebNotification($user_id, $shop_id, $admin_id, $msg_role, $msg_code, $remain_count); //save social log
                    $this->sendEmailNotification($mail_sub, $to_email, $mail_body_header, $store_img, $user_id, $mail_body); //sen email notification
                    $applane_service->writeAllLogs($handler, 'Notification is sent to userid: ' . $user_id . ' for shopid: ' . $shop_id, array());
                } else {
                    $applane_service->writeAllLogs($handler, 'Shop is inactive OR blocked so no notification sent to userid: ' . $user_id . ' for shopid: ' . $shop_id, array());
                }
                $dm->flush();
            } catch (\Exception $ex) {
                $applane_service->writeAllLogs($handler, 'Erro in sending the notification to userid: ' . $user_id . ' for shopid: ' . $shop_id, 'Error is:' . $ex->getMessage());
            }
        }
        return true;
    }

    /**
     * save push notification for failed pending payment
     * @param int $user_id
     * @param int $shop_id
     * @param int $admin_id
     */
    public function savePaymentFailedPushNotification($user_id, $shop_id, $admin_id, $msg_role_push, $msg_code_push, $remain_count) {
        $from_id = $admin_id;
        $shop_owner_id = $user_id;
        $this->sendPushNotifications($from_id, $shop_owner_id, $shop_id, $msg_role_push, $msg_code_push, $remain_count); //save notification for push type
        return true;
    }

    /**
     * save web notification for failed pending payment
     * @param int $user_id
     * @param int $shop_id
     * @param int $admin_id
     */
    public function savePaymentFailedWebNotification($user_id, $shop_id, $admin_id, $msg_role, $msg_code, $remain_count) {
        $from_id = $admin_id;
        $shop_owner_id = $user_id;
        $this->sendSocialNotification($from_id, $shop_owner_id, $shop_id, $msg_role, $msg_code, $remain_count); //save notification for web type.
        return true;
    }

    /**
     * check shop status
     * @param type $store_detail
     * @return int
     */
    public function checkShopStatus($store_detail) {
        if ($store_detail['isActive'] == 0 || $store_detail['isActive'] == '' || $store_detail['shop_status'] == 0 || $store_detail['shop_status'] == '')
            return 0;
        return 1;
    }

    /**
     * send the subscription pending payment failed notification web, push and email
     * @param type $admin_id
     */
    public function pendingSubscriptionPaymentFailedNotification($admin_id) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [pendingSubscriptionPaymentFailedNotification]', array());

        $time = new \DateTime('now');
        $shop_ids = $user_ids = array();
        $current_time_date = new \DateTime('now');
        $current_date = $current_time_date->format('Y-m-d');
        //get odm manager object
        $dm = $this->dm;
        $subscription_transaction_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:SubscriptionPaymentNotificationLog')
                ->findSubscriptionPaymentLogs(); //find active logs
        //code for get store and user detail start
        //get shop id and user ids array
        foreach ($subscription_transaction_logs as $record) {
            $shop_ids[] = $record->getToShopId();
            $user_ids[] = $record->getToUserId();
        }
        //get all shop info
        $user_service = $this->container->get('user_object.service');
        $store_details = $user_service->getMultiStoreObjectService($shop_ids);
        $store_owner_details = $user_service->MultipleUserObjectService($user_ids);
        //code for store detail and user detail end 

        foreach ($subscription_transaction_logs as $transaction_log) {
            $user_id = $transaction_log->getToUserId(); //shop owner id
            $shop_id = $transaction_log->getToShopId();
            $count = $transaction_log->getSendCount();
            $last_updated_at = $transaction_log->getUpdatedDate();
            $updated_at = $last_updated_at->format('Y-m-d');
            $store_detail = $store_details[$shop_id];
            $user_info = $store_owner_details[$user_id];
            $is_to_be_sent = 0; //variable for decide the notification to be sent or not.
            //get locale
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($user_id, true);
            $lng = $receiver[$user_id]['current_language'];
            $locale = empty($lng) ? $this->container->getParameter('locale') : $lng;
            $days_count = (int) self::DAYS_COUNTER;
            $remain_count = ($days_count - $count);
            $shop_bs_name = $store_detail['businessName'];
            $store_img = $store_detail['thumb_path'];
            $to_email = $user_info['email'];

            if ($count >= $days_count) { //if days count is =>8 need to unsubscribe a shop status
                $msg_code_push = self::SHOP_SUBSCRIPTION_BLOCKED;
                $msg_role_push = self::SUBSCRIPTION_NOTIFICATION_PUSH;
                $msg_code = self::SHOP_SUBSCRIPTION_BLOCKED;
                $msg_role = self::SUBSCRIPTION_RECURRING_NOTIFICATION;
                $is_to_be_sent = 1;
                $dm->remove($transaction_log);
                $this->__subscriptionLog('Notification for shop subscription blocked, notification log will be removed. shopid: ' . $shop_id, array());
                $extra_param = array('%', $shop_bs_name);
                $mail_data = $this->__prepareSubscriptionBlockMailText($locale, $store_detail, $shop_bs_name, $shop_id);
                $mail_sub = $mail_data['mail_sub'];
                $mail_body_header = $mail_data['mail_body_header'];
                $mail_body = $mail_data['mail_body'];
            } else {
                $extra_param = $remain_count;
                $msg_code_push = self::SUBSCRIPTION_FEE_NOT_PAID;
                $msg_role_push = self::SUBSCRIPTION_NOTIFICATION_PUSH;
                $msg_code = self::SUBSCRIPTION_FEE_NOT_PAID;
                $msg_role = self::SUBSCRIPTION_RECURRING_NOTIFICATION;
                $this->__subscriptionLog('Notification to be sent for shop subscription payment for shopid: ' . $shop_id, array());
                $is_to_be_sent = 1;
                $mail_data = $this->__prepareSubscriptionPendingMailText($locale, $store_detail, $remain_count, $shop_id); //prepare the mail text
                $mail_sub = $mail_data['mail_sub'];
                $mail_body_header = $mail_data['mail_body_header'];
                $mail_body = $mail_data['mail_body'];
            }
            try {
                if (($is_to_be_sent == 1) && ($store_detail['isActive'] == 1)) { //when we need to sent the notification.
                    $this->savePaymentFailedPushNotification($user_id, $shop_id, $admin_id, $msg_role_push, $msg_code_push, $extra_param); //save push log
                    $this->savePaymentFailedWebNotification($user_id, $shop_id, $admin_id, $msg_role, $msg_code, $remain_count); //save social log
                    $this->sendEmailNotification($mail_sub, $to_email, $mail_body_header, $store_img, $user_id, $mail_body); //send email notification
                    $this->__subscriptionLog('Notification is sent to userid: ' . $user_id . ' for shopid: ' . $shop_id, array());
                } else {
                    $this->__subscriptionLog('Shop is inactive OR blocked, so no notification will send to userid: ' . $user_id . ' for shopid: ' . $shop_id, array());
                }
                $dm->flush();
            } catch (\Exception $ex) {
                $this->__subscriptionLog('There is some error shop subscription payment. shopid: ' . $shop_id, 'Error is : ' . $ex->getMessage());
            }
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [pendingSubscriptionPaymentFailedNotification]', array());
        return true;
    }

    /**
     * write the subscription logs
     * @param string $request
     * @param string $response
     */
    private function __subscriptionLog($request, $response) {
        $handler = $this->container->get('monolog.logger.subscription_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        try {
            $applane_service->writeAllLogs($handler, $request, $response);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * Unsubscribe the shop
     * @param int $shop_id
     */
    private function __unsubscribeshop($shop_id, $user_id) {
        $this->__subscriptionLog('Entering into [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__unsubscribeshop]', array());
        $subscribed_array = array(self::SUBSCRIBED, self::PENDING);
        //unsubscribe
        $em = $this->em;
        //check contract is already exist
        $subscription_obj = $em->getRepository('CardManagementBundle:ShopSubscription')
                ->checkIfSubscribed($shop_id, $user_id, $subscribed_array);

        if (!$subscription_obj) {
            $this->__subscriptionLog('Subscription record does not exists in table [ShopSubscription] for shop: ' . $shop_id, array());
            return true;
        }
        $subscription_obj->setStatus(self::UNSUBSCRIBED);
        try {
            $em->persist($subscription_obj);
            $em->flush();
            $this->__subscriptionLog('Shop Subscription is unsubscribed in table [ShopSubscription] for shop: ' . $shop_id . ' with status: ' . self::UNSUBSCRIBED, array());
        } catch (\Exception $e) {
            
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__unsubscribeshop]', array());
        return true;
    }

    /**
     * Update shop subscription in store table
     * @param type $shop_id
     * @return boolean
     */
    private function __updatShopSubscription($shop_id, $status) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__updatShopSubscription]', array());
        $em = $this->em;
        $store_obj = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if ($store_obj) {
            $store_obj->setIsSubscribed($status);
            try {
                $em->persist($store_obj);
                $em->flush();
                $this->__subscriptionLog('Shop status is unsubscribed in [Store] table for shop: ' . $shop_id, array());
            } catch (\Exception $e) {
                
            }
        } else {
            $this->__subscriptionLog('Shop Record(store record) does not exists for shop: ' . $shop_id, array());
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__updatShopSubscription]', array());
        return true;
    }

    /**
     * Send registration failed notification
     * @param int $admin_id
     * @return boolean
     */
    public function registrationFailedNotification($admin_id) {
        $handler = $this->container->get('monolog.logger.recurring_notification_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_service->writeAllLogs($handler, 'Entering into [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [registrationFailedNotification]', array());

        $time = new \DateTime('now');
        $shop_ids = $user_ids = array();
        $current_time_date = new \DateTime('now');
        $current_date = $current_time_date->format('Y-m-d');
        //get odm manager object
        $dm = $this->dm;
        $transaction_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->findRegistrationLogs(); //find active logs
        
        if(!$transaction_logs){
          $applane_service->writeAllLogs($handler, 'Entering into [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [registrationFailedNotification]: No User found', array());
        }
        //get shop id and user ids array
        foreach ($transaction_logs as $record) {
            $shop_ids[] = $record->getToShopId();
            $user_ids[] = $record->getToUserId();
        }
        //get all shop info
        $user_service = $this->container->get('user_object.service');
        $store_details = $user_service->getMultiStoreObjectService($shop_ids);
        $store_owner_details = $user_service->MultipleUserObjectService($user_ids);
        //code for store detail and user detail end 

        foreach ($transaction_logs as $transaction_log) {
            $user_id = $transaction_log->getToUserId(); //shop owner id
            $shop_id = $transaction_log->getToShopId();
            $count = $transaction_log->getSendCount();
            $last_updated_at = $transaction_log->getUpdatedDate();
            $updated_at = $last_updated_at->format('Y-m-d');
            $store_detail = $store_details[$shop_id];
            $user_info = $store_owner_details[$user_id];
            $is_to_be_sent = 0; //variable for decide the notification to be sent or not.
            //email code       
            //get locale
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($user_id, true);
            $lng = $receiver[$user_id]['current_language'];
            $locale = empty($lng) ? $this->container->getParameter('locale') : $lng;

            $reg_mail_data = $this->prepareRegistrationMailText($locale, $store_detail, $count);
            $shop_name = $store_detail['name'];
            $store_img = $store_detail['thumb_path'];
            $shop_bs_name = $store_detail['businessName'];
            $days_counter = (int) self::DAYS_COUNTER;
            $remain_count = ($days_counter - $count);
            $to_email = $user_info['email'];
            $mail_sub = $reg_mail_data['mail_sub'];
            $mail_body_header = $reg_mail_data['mail_body_header'];
            $mail_body = $reg_mail_data['mail_body'];
            //email code end

            if ($count >= $days_counter) {
                $msg_code_push = self::SHOP_BLOCKED;
                $msg_role_push = self::RECURRING_NOTIFICATION_PUSH;
                $msg_code = self::SHOP_BLOCKED;
                $msg_role = self::RECURRING_NOTIFICATION;
                $block_mail_data = $this->prepareBlockMailText($locale, $store_detail);
                $mail_sub = $block_mail_data['mail_sub'];
                $mail_body = $block_mail_data['mail_body'];
                $mail_body_header = $block_mail_data['mail_body_header'];
                $is_to_be_sent = 1;
                $applane_service->writeAllLogs($handler, 'Notification for shop blocked to be sent, Notification log will be removed', array());
                $dm->remove($transaction_log); //remove the log 
            } else {
                $msg_code_push = self::REG_FEE_NOT_PAID;
                $msg_role_push = self::RECURRING_NOTIFICATION_PUSH;
                $msg_role = self::RECURRING_NOTIFICATION;
                $msg_code = self::REG_FEE_NOT_PAID;
                $applane_service->writeAllLogs($handler, 'Notification to be sent for shop registration pending payment.', array());
                $is_to_be_sent = 1;
            }
            try {
                if (($is_to_be_sent == 1) && ($store_detail['isActive'] == 1)) { //when we need to sent the notification.
                    //send mail
                    $this->sendEmailNotification($mail_sub, $to_email, $mail_body_header, $store_img, $user_id, $mail_body);
                    //send social notification
                    $this->sendSocialNotification($admin_id, $user_id, $shop_id, $msg_role, $msg_code, $remain_count);
                    //send push notification
                    $this->sendPushNotifications($admin_id, $user_id, $shop_id, $msg_role_push, $msg_code_push, $remain_count);

                    $applane_service->writeAllLogs($handler, 'Notification is sent to userid: ' . $user_id . ' for shopid: ' . $shop_id, array());
                    $dm->flush();
                } else {
                    $applane_service->writeAllLogs($handler, 'Inactive or shop blocked: ' . $shop_id, array());
                }
            } catch (\Exception $ex) {
                $applane_service->writeAllLogs($handler, 'Error in sending the notification to userid: ' . $user_id . ' for shopid: ' . $shop_id, 'Error is:' . $ex->getMessage());
            }
        }
        return true;
    }

    /**
     * prepare the subscription pending payment mail text
     * @param string $locale
     * @param string $store_detail
     * @param int $remain_count
     * @param int $shop_id
     * @return array $mail_array
     */
    private function __prepareSubscriptionPendingMailText($locale, $store_detail, $remain_count, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__prepareSubscriptionPendingMailText]', array());
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $shop_name = $store_detail['name'];
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_wallet_url');
        $mail_sub = $lang_array['SUBSCRIPTION_PENDING_PAYMENT_NOT_PAID_SUBJECT'];
        $mail_body_header = sprintf($lang_array['SUBSCRIPTION_PENDING_PAYMENT_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a>";
        $mail_body = sprintf($lang_array['SUBSCRIPTION_PENDING_PAYMENT_PAID_MAIL_BODY'], $shop_name, $remain_count, $link, $shop_name);
        $mail_array = array('mail_sub' => $mail_sub, 'mail_body_header' => $mail_body_header, 'mail_body' => $mail_body);
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__prepareSubscriptionPendingMailText]', array());
        return $mail_array;
    }

    /**
     * prepare the subscription block payment mail text
     * @param string $locale
     * @param string $store_detail
     * @param string $shop_bs_name
     * @param int $shop_id
     * @return array $mail_array
     */
    private function __prepareSubscriptionBlockMailText($locale, $store_detail, $shop_bs_name, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__prepareSubscriptionBlockMailText]', array());
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $shop_name = $store_detail['name'];
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_wallet_url');
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a>";
        $mail_sub = $lang_array['BLOCK_SHOP_SUBSCRIPTION_SUBJECT'];
        $block_mail_body = $lang_array['BLOCK_SHOP_SUBSCRIPTION_MAIL_BODY'];
        $mail_body = sprintf($block_mail_body, '%', '%', $link);
        $mail_body_header = sprintf($lang_array['BLOCK_SHOP_SUBSCRIPTION_MAIL_BODY_HEADER'], $shop_bs_name, '%');
        $mail_array = array('mail_sub' => $mail_sub, 'mail_body_header' => $mail_body_header, 'mail_body' => $mail_body);
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [__prepareSubscriptionBlockMailText]', array());
        return $mail_array;
    }

    /**
     * Prepare registration mail text
     * @param type $locale
     * @param type $store_detail
     * @param type $count
     */
    private function prepareRegistrationMailText($locale, $store_detail, $count) {
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_name = $store_detail['name'];
        $shop_url = $this->container->getParameter('shop_wallet_url');
        $mail_sub = $lang_array['REG_FEE_NOT_PAID_SUBJECT'];
        $mail_body_header = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $message_detail = $lang_array['MESSAGE_DETAIL'];
        $days_counter = (int) self::DAYS_COUNTER;
        $remain_count = ($days_counter - $count);
        $shop_id = $store_detail['id'];
        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";
        $mail_body = sprintf($lang_array['REG_FEE_NOT_PAID_MAIL_BODY'], $shop_name, $remain_count, $link);

        $data = array(
            'mail_sub' => $mail_sub,
            'mail_body_header' => $mail_body_header,
            'mail_body' => $mail_body
        );
        return $data;
    }

    /**
     * Prepare registration mail text
     * @param type $locale
     * @param type $store_detail
     */
    private function prepareBlockMailText($locale, $store_detail) {
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_name = $store_detail['name'];
        $shop_url = $this->container->getParameter('shop_wallet_url');
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $message_detail = $lang_array['MESSAGE_DETAIL'];
        $shop_id = $store_detail['id'];
        $shop_bs_name = $store_detail['businessName'];
        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";

        $mail_sub = $lang_array['BLOCK_SHOP_SUBJECT'];
        $block_mail_body = $lang_array['BLOCK_SHOP_MAIL_BODY'];
        $mail_body = sprintf($block_mail_body, $shop_bs_name, $shop_bs_name, $link);
        $mail_body_header = $lang_array['BLOCK_SHOP_MAIL_BODY_HEADER'];

        $data = array(
            'mail_sub' => $mail_sub,
            'mail_body_header' => $mail_body_header,
            'mail_body' => $mail_body
        );
        return $data;
    }

    /**
     * prepare the pending payment fail mail text
     * @param string $locale
     * @param string $store_detail
     * @param int $remain_count
     * @param int $shop_id
     * @return array $mail_array
     */
    public function preparePendingFailNotificationMailText($locale, $store_detail, $remain_count, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [preparePendingFailNotificationMailText]', array());
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $shop_name = $store_detail['name'];
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_wallet_url');
        //get click name
        $click_name = $lang_array['CLICK_HERE'];
        $mail_sub = $lang_array['PENDING_PAYMENT_NOT_PAID_SUBJECT'];
        $mail_body_header = sprintf($lang_array['PENDING_PAYMENT_NOT_PAID_MAIL_BODY_HEADER'], $shop_name);
        $message_detail = $lang_array['MESSAGE_DETAIL'];
        $link = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";
        $mail_body = sprintf($lang_array['PENDING_PAYMENT_PAID_MAIL_BODY'], $shop_name, $remain_count, $link);
        $mail_array = array('mail_sub' => $mail_sub, 'mail_body_header' => $mail_body_header, 'mail_body' => $mail_body);
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [preparePendingFailNotificationMailText]', array());
        return $mail_array;
    }

    /**
     * prepare the pending payment fail mail text
     * @param string $locale
     * @param string $store_detail
     * @param int $remain_count
     * @param int $shop_id
     * @return array $mail_array
     */
    public function prepareShopBlockNotificationMailText($locale, $store_detail, $remain_count, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [prepareShopBlockNotificationMailText]', array());
        //get language array
        $lang_array = $this->container->getParameter($locale);
        $shop_bs_name = $store_detail['businessName'];
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_wallet_url');
        //get click name
        $click_name     = $lang_array['CLICK_HERE'];
        $message_detail = $lang_array['MESSAGE_DETAIL'];
        $link     = "<a href='$angular_app_hostname" . "$shop_url/$shop_id/pending/payment'>$click_name</a> $message_detail";        
        $mail_sub = $lang_array['BLOCK_SHOP_SUBJECT'];
        $block_mail_body  = $lang_array['BLOCK_SHOP_MAIL_BODY'];
        $mail_body        = sprintf($block_mail_body, $shop_bs_name, $shop_bs_name, $link);
        $mail_body_header = $lang_array['BLOCK_SHOP_MAIL_BODY_HEADER'];
        $mail_array = array('mail_sub' => $mail_sub, 'mail_body_header' => $mail_body_header, 'mail_body' => $mail_body);
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentNotificationService] function [prepareShopBlockNotificationMailText]', array());
        return $mail_array;
    }

}
