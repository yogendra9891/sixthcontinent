<?php

namespace Notification\NotificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Notification\NotificationBundle\Document\UserNotification;
use Transaction\TransactionBundle\Entity\RecurringPendingPayment;
use Transaction\TransactionBundle\Entity\RecurringPayment;
use Votes\VotesBundle\Service\VoteNotificationService;
use Notification\NotificationBundle\Services\MediaNotificationService;

class NotificationController extends FOSRestController {
    
    
    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = '/uploads/users/media/thumb/';
    protected $group_media_album_path_thumb = '/uploads/groups/thumb/';
    protected $group_media_album_path = '/uploads/groups/original/';
    protected $store_media_path = '/uploads/documents/stores/gallery/';
    private $mediaNotification;
    
    /**
     * Get approved friend request notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetapprovedfriendrequestsAction(Request $request)
    {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get friend approved notification
         $friend_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getFriendAcceptNotification($user_id);
         if(count($friend_notification) == 0){
             return array('code' => 151, 'message' => 'NO_NOTIFICATION', 'data' => $data);
         }
         
         $user_service = $this->get('user_object.service');
         foreach($friend_notification as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             
             $motification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message);
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $motification_array);
         echo json_encode($res_data);
         exit();
    }
    
    /**
     * Get group response notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetgroupresponsenotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get group response notification
         $group_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getGroupResponseNotification($user_id);

         if(count($group_notification) == 0){
             return array('code' => 151, 'message' => 'NO_NOTIFICATION', 'data' => $data);
         }
         
         $user_service = $this->get('user_object.service');
         foreach($group_notification as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             
             //get club info
             $group_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $item_id));
             
             $title = $group_detail->getTitle();
             $group_info = array('id'=>$item_id, 'name' => $title);
             $motification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message, 'group_info'=>$group_info);
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $motification_array);
         echo json_encode($res_data);
         exit();
    }
    
    
    /**
     * Get group response notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetbrokerresponsenotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get broker approved notification
         $broker_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getBrokerResponseNotification($user_id);

         if(count($broker_notification) == 0){
             return array('code' => 151, 'message' => 'NO_NOTIFICATION', 'data' => $data);
         }
         
         $user_service = $this->get('user_object.service');
         foreach($broker_notification as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();

             $motification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message);
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $motification_array);
         echo json_encode($res_data);
         exit();
    }
    
    
    /**
     * Get shop response notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetshopresponsenotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get shop response notification
         $shop_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopResponseNotification($user_id);

         if(count($shop_notification) == 0){
             return array('code' => 151, 'message' => 'NO_NOTIFICATION', 'data' => $data);
         }
         // get entity manager object
         $em = $this->getDoctrine()->getManager();
         $user_service = $this->get('user_object.service');
         foreach($shop_notification as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();

             //get store detail
             $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $item_id));
        
             $store_id = $store_detail->getId();
             $store_name = $store_detail->getName();
             $store_bus_name = $store_detail->getBusinessName();
             $store_array = array('id'=>$store_id, 'name'=>$store_name, 'business_name'=>$store_bus_name);
             $motification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message, 'shop_info'=>$store_array);
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $motification_array);
         echo json_encode($res_data);
         exit();
    }
    
    /**
     * Get shop response notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetshopapprovalnotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get shop approved notification
         $shop_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopApprovalNotification($user_id);

         if(count($shop_notification) == 0){
             return array('code' => 151, 'message' => 'NO_NOTIFICATION', 'data' => $data);
         }
         // get entity manager object
         $em = $this->getDoctrine()->getManager();
         $user_service = $this->get('user_object.service');
         foreach($shop_notification as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();

             //get store detail
             $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $item_id));
        
             $store_id = $store_detail->getId();
             $store_name = $store_detail->getName();
             $store_bus_name = $store_detail->getBusinessName();
             $store_array = array('id'=>$store_id, 'name'=>$store_name, 'business_name'=>$store_bus_name);
             $motification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message, 'shop_info'=>$store_array);
         }
         $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $motification_array);
         echo json_encode($res_data);
         exit();
    }
    
    /**
     * Mark the notification as read
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postMarkreadnotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id','notification_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $notification_id = $object_info->notification_id;
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get friend approved notification
        $mark_notification_read = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->markNotificationAsRead($user_id, $notification_id);
       if(!$mark_notification_read){
           return array('code' => 152, 'message' => 'NO_NOTIFICATION_FOUND', 'data' => $data);
       }
       
       //return the result
       $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
       echo json_encode($res_data);
       exit();

    }
    
    /**
     * Get all notification
     * @return type
     */
    public function postGetallnotificationsAction(Request $request)
    {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','is_view');
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        $is_view = $object_info->is_view;
        
        /* Limit Set with Notification list */
        $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:10);
        $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);
        /* End here notification list */
        
        $notificationTypes = isset($de_serialize['notification_type']) ? $de_serialize['notification_type'] : array();
        $nTypes = array('include'=>array(), 'exclude'=>array());
        if(!empty($notificationTypes)){
           foreach($notificationTypes as $nType=>$val){
               switch(trim($val)){
                   case '1':
                       $nTypes['include'][] = $nType;
                       break;
                   case '0':
                       $nTypes['exclude'][] = $nType;
                       break;
               }
           } 
        }
        /*Update  is view in UserNotification and GroupNotifications*/                
            
        $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getisviewUpdateUserNotification($user_id,$is_view, $nTypes['exclude'], $nTypes['include']);        
        
        // function added for get all notifications by single function
        $responseNotifications = $this->_getallnotifications($user_id, $offset, $limit, $nTypes);
        $responseNotificationsCount = $this->_getallnotificationsCount($user_id, $nTypes);
        $data_res = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('requests'=>$responseNotifications,'size'=>$responseNotificationsCount));  
        echo json_encode($data_res);
        exit;
    }
    
    
    /**
     * Get counts for following, followers, citizen affiliates, broker affiliates, shop affiliates
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postGetallcountsAction(Request $request)
    {
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
        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        
        $userId = $object_info->user_id;
        //get follower counts
         //check if user has already connected
         
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //get document manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        
        //get followers count
        $user_followers_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowersCount($userId);
        
        //get following count
        $user_following_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowingsCount($userId);
        
        //get Citizen affiliates
        $citizen_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')                   
                                    ->findCitizenAffiliationUsersCount($userId);
        
        //get Broker affiliates
        $broker_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationBroker')                   
                                    ->findBrokerAffiliationUsersCount($userId);
        
        //count of shop affiliates.
        $shop_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')                   
                                    ->findUserAffiliationShopsCount($userId);
       
        //get friends count
        $friends_count = $em->getRepository('UserManagerSonataUserBundle:UserConnection')                   
                                    ->getAllUserFriendsCount($userId, "");
        
        //get Club count
        $clubs_count = $dm->getRepository('UserManagerSonataUserBundle:Group')                   
                                    ->getUserCreatedGroupCount($userId);
        
        //get social project count
        $social_project_count = 0;
        
        $response_data = array('friends'=>$friends_count, 'clubs'=>$clubs_count, 'social_projects'=>$social_project_count, 'followers'=>$user_followers_count, 'followings'=>$user_following_count, 'citizen_affiliates'=>$citizen_affiliates_count, 'broker_affiliates'=>$broker_affiliates_count, 'shop_affiliates'=>$shop_affiliates_count);
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $response_data); 
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
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
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->encode($req_obj, 'json');
        return $jsonContent;
    }
    
    /**
     * Get notification list
     * @param Request $request
     */
    public function Getgroupjoinnotifications($user_id,$limit,$offset) {
        
        $data = array();
        //get user login id
        $user_id = (int)$user_id;
        $group_notification_data = array();
        $group_notification_user_id = array();
        //@TODOcheck for active member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');        
        /*$group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupJoinNotifications($user_id);        */
        $group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupJoinNotificationsNew($user_id,$limit,$offset);  
        //print_r($group_notification_id);exit;
        if (count($group_notification_id) == 0) {
            //no notification found
            //return success
            return $group_notification_data;
        }
        //if found then get the notification details
        $group_notification_id_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupJoinNotificationsDetail($group_notification_id);
        
        //prepare from id array
         foreach ($group_notification_id_detail as $group_notifications) {
             $group_notification_user_id[] = $group_notifications['sender_id'];
         }
         
         $user_service   = $this->get('user_object.service');
         $group_response_user_objects    = $user_service->MultipleUserObjectService($group_notification_user_id);

        foreach ($group_notification_id_detail as $group_notifications) {
            $sender_id      = $group_notifications['sender_id'];
            //call the serviec for user object.
            $user_object = isset($group_response_user_objects[$sender_id]) ? $group_response_user_objects[$sender_id] : array();
           // $user_object    = $group_response_user_objects[$sender_id];
           // $group_notification['sender_info'] = $user_object;
            $group_notification['notification_id'] = $group_notifications['request_id'];
            $group_notification['notification_from'] = $user_object;
            $group_notification['message_type'] = "group";
            $group_notification['message_status'] = "U";
            $group_notification['message'] = "join_request";
            $group_notification['group_info'] = array('id'=>$group_notifications['group_id'], 'group_name'=>$group_notifications['group_name'],'group_type'=>$group_notifications['group_type']);
            $group_notification_data[] = $group_notification;
        }
        
        return $group_notification_data;
    }
 
    public function GetgroupjoinnotificationsCount($user_id) {
        
        $data = array();
        //get user login id
        $user_id = (int)$user_id;
        $group_notification_data = array();
        $group_notification_user_id = array();
        //@TODOcheck for active member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');        
        /*$group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupJoinNotifications($user_id);        */
        $group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupJoinNotificationsNewCount($user_id);  
        //print_r($group_notification_id);exit;
        if (count($group_notification_id) == 0) {
            //no notification found
            //return success
            return $group_notification_data;
        }
        //if found then get the notification details
        $group_notification_id_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupJoinNotificationsDetail($group_notification_id);
        
        //prepare from id array
         foreach ($group_notification_id_detail as $group_notifications) {
             $group_notification_user_id[] = $group_notifications['sender_id'];
         }
         
         $user_service   = $this->get('user_object.service');
         $group_response_user_objects    = $user_service->MultipleUserObjectService($group_notification_user_id);

        foreach ($group_notification_id_detail as $group_notifications) {
            $sender_id      = $group_notifications['sender_id'];
            //call the serviec for user object.
            $user_object = isset($group_response_user_objects[$sender_id]) ? $group_response_user_objects[$sender_id] : array();
           // $user_object    = $group_response_user_objects[$sender_id];
           // $group_notification['sender_info'] = $user_object;
            $group_notification['notification_id'] = $group_notifications['request_id'];
            $group_notification['notification_from'] = $user_object;
            $group_notification['message_type'] = "group";
            $group_notification['message_status'] = "U";
            $group_notification['message'] = "join_request";
            $group_notification['group_info'] = array('id'=>$group_notifications['group_id'], 'group_name'=>$group_notifications['group_name'],'group_type'=>$group_notifications['group_type']);
            $group_notification_data[] = $group_notification;
        }
        
        return $group_notification_data;
    }
 
    
    /**
     * function for getting all the discount position notification for a user
     * @param type $user_id
     * @return Array array of all the discount position notification  
     */
//    private function discountPositionNotification($user_id) {
//        $data = array();
//        $user_id = $user_id;
//        $dp_notification_data = array();
//        // get documen manager object
//        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        $discount_position_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
//                ->getDiscountPositionNotification($user_id);
//        if (count($discount_position_notification) == 0) {
//            //no notification found
//            //return success
//            return $discount_position_notification;
//        }
//        //get entity object
//        $em = $this->getDoctrine()->getManager();
//        $user_service = $this->get('user_object.service');
//        foreach ($discount_position_notification as $notification) {
//            $notification_id = $notification->getId();
//            $from = $notification->getFrom();
//            //get $from user object
//            $from_id = $notification->getFrom();
//            $user_info = $user_service->UserObjectService($from_id);
//            $message_type = $notification->getMessageType();
//            $message = $notification->getMessage();
//            $item_id = $notification->getItemId();
//            $message_status = $notification->getMessageStatus();
//            //get store offer object by id
//            $dp_info = $em
//                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
//                    ->findOneBy(array('id' => $item_id));
//            //check if store offer object exist
//            if (count($dp_info)) {
//                //getting the store information
//                 $store_detail = $user_service->getStoreObjectService($dp_info->getshopId());
//                //check if store details exist
//                if (count($store_detail) > 0) {
//                    $store_array = $store_detail;
//                    $discount_amount = $dp_info->getAffilationAmount()/1000000;
//                    $dp_notification_data[] = array('notification_id' => $notification_id, 'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_array, 'discount_amount' => $discount_amount, 'message_status'=>$message_status);
//                }
//            }
//        }
//        return $dp_notification_data;
//    }
    
    
    private function discountPositionNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $dp_notification_data = array();
        //initilizing blank array
        $dp_user_id = $dp_item_id = $store_ids = $store_dps = $store_item_map = $dp_user_objects = $dp_shop_objects = array();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get discount position notification array
        /*$discount_position_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getDiscountPositionNotification($user_id);*/
        $discount_position_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getDiscountPositionNotificationNew($user_id);
        if (count($discount_position_notification) == 0) {
            //no notification found
            //return success
            return $discount_position_notification;
        }
        
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        //loop for getting the from user id and item ids
        foreach($discount_position_notification as $notification){
             $dp_user_id[] = $notification->getFrom();
             $dp_item_id[] = $notification->getItemId();
        }
        
        //count number of items in notification
        $dp_item_count = count($dp_item_id);
        
        //if item count id grater then 0
        if($dp_item_count > 0) {
        $store_details = $em
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getStoreIdFromItemId($dp_item_id);
        }
        
        // loop for getting the discount position and store id
        foreach($store_details as $store_offer){
             $store_ids[] = $store_offer->getShopId();
             $store_dps[$store_offer->getShopId()] = $store_offer->getDiscountPosition();
             $store_item_map[$store_offer->getId()] = $store_offer->getShopId();
        }
        //get unique store id
        $store_ids = array_unique($store_ids);
        //get unique user id
        $dp_user_id = array_unique($dp_user_id);
        //getting the user object and shop objects from respective ids
        $dp_user_objects = $user_service->MultipleUserObjectService($dp_user_id);
        $dp_shop_objects = $user_service->getMultiStoreObjectService($store_ids);
        
        //loop for making the final responce
        foreach ($discount_position_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = isset($dp_user_objects[$from]) ? $dp_user_objects[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            if(isset($store_item_map[$item_id])) {
            $store_info = isset($dp_shop_objects[$store_item_map[$item_id]]) ? $dp_shop_objects[$store_item_map[$item_id]] : array();
            $store_dp =   isset($store_dps[$store_item_map[$item_id]]) ? $store_dps[$store_item_map[$item_id]] : 0;
            $discount_amount = $store_dp/1000000;
            $dp_notification_data[] = array('notification_id' => $notification_id, 'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_info, 'discount_amount' => $discount_amount, 'message_status'=>$message_status,
                                            'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
            }
        }
        return $dp_notification_data;
    }

    /**
     * function for getting all the shop active and inactive notifications
     * @param type $user_id
     * @return Array array of all the shop active and inactive notifications  
     */
    /*
    private function shopActiveInactiveNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $dp_notification_data = array();
        $vat            = $this->container->getParameter('vat');
        $reg_fee        = $this->container->getParameter('reg_fee');
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopStatusNotification($user_id);
        if (count($shop_status_notification) == 0) {
            //no notification found
            //return success
            return $shop_status_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        foreach ($shop_status_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = $user_service->UserObjectService($from_id);
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            
            //getting the store information
            $store_detail = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => (int)$item_id));
            //check in store exist
            if (count($store_detail) > 0) {
                $store_object = $user_service->getStoreObjectService($item_id);
                $total_pending_amount = 0;
                $reg_vat_amount = 0;
                $transaction_pending_amount = 0;
                
                if($message == 'paymentpending' || $message == 'card_not_found_recurring') {
                    //get entries from transaction shop
                    $store_pending_amount = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getShopPendingAmount($item_id); 
                    if($store_pending_amount) {
                        $transaction_pending_amount_check = $store_pending_amount/1000000;
                        if($transaction_pending_amount_check>5) {
                             $transaction_pending_amount = $transaction_pending_amount_check;
                        }    
                    }
                    $payment_status = $store_detail->getPaymentStatus();
                    if($payment_status == 0) {
                        $reg_vat_amount = ($reg_fee + (($reg_fee*$vat)/100))/100;
                    }
                    $total_pending_amount = $reg_vat_amount + $transaction_pending_amount;
                    $total_pending_amount = sprintf("%01.2f", $total_pending_amount);
                    $dp_notification_data[] = array('notification_id' => $notification_id,'message_status'=>$message_status ,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object,'amount'=>$total_pending_amount);
                }else {
                    $dp_notification_data[] = array('notification_id' => $notification_id, 'message_status'=>$message_status,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object);
                }
                
            }
        }
        return $dp_notification_data;
    }
    */
    
    /**
     * function for getting all the shop active and inactive notifications
     * @param type $user_id
     * @return Array array of all the shop active and inactive notifications  
     */
    private function shopActiveInactiveNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $dp_notification_data = array();
        $vat            = $this->container->getParameter('vat');
        $reg_fee        = $this->container->getParameter('reg_fee');
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        /*$shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopStatusNotification($user_id);*/
        $shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopStatusNotificationNew($user_id);
        if (count($shop_status_notification) == 0) {
            //no notification found
            //return success
            return $shop_status_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        
        //getting the shops ids.
        $all_shops_ids = array();
        $all_shops_ids = array_map(function($notification_record) {
            return "{$notification_record->getItemId()}";
        }, $shop_status_notification);
        
        //getting the user ids.
        $all_froms_ids = array();
        $all_froms_ids = array_map(function($froms_record) {
            return "{$froms_record->getFrom()}";
        }, $shop_status_notification);
        
        $pay_users_objects = array();
        $pay_shops_objects = array();
        
        $pay_users_objects    = $user_service->MultipleUserObjectService($all_froms_ids);
        $pay_shops_objects    = $user_service->getMultiStoreObjectService($all_shops_ids);
        
        foreach ($shop_status_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = isset($pay_users_objects[$from_id]) ? $pay_users_objects[$from_id] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            
            //getting the store information
            $store_detail = isset($pay_shops_objects[$item_id]) ? $pay_shops_objects[$item_id] : array();
         
            //check in store exist
       
            if (count($store_detail) >0) {
                $store_object = $store_detail;
                $total_pending_amount = 0;
                $reg_vat_amount = 0;
                $transaction_pending_amount = 0;
                
                if($message == 'paymentpending' || $message == 'card_not_found_recurring') {
                    //get entries from transaction shop
                    $store_pending_amount = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getShopPendingAmount($item_id); 
                    if($store_pending_amount) {
                        $transaction_pending_amount_check = $store_pending_amount/1000000;
                        if($transaction_pending_amount_check>5) {
                             $transaction_pending_amount = $transaction_pending_amount_check;
                        }    
                    }
                    $payment_status = $store_detail['paymentStatus'];
                    if($payment_status == 0) {
                        $reg_vat_amount = ($reg_fee + (($reg_fee*$vat)/100))/100;
                    }
                    $total_pending_amount = $reg_vat_amount + $transaction_pending_amount;
                    $total_pending_amount = sprintf("%01.2f", $total_pending_amount);
                    $dp_notification_data[] = array('notification_id' => $notification_id,'message_status'=>$message_status ,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object,'amount'=>$total_pending_amount,'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
                }else {
                    $dp_notification_data[] = array('notification_id' => $notification_id, 'message_status'=>$message_status,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object,'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
                }
                
            }
        }
        return $dp_notification_data;
    }
    
      /**
     * function for getting all the shop recurring payment status notifications
     * @param type $user_id
     * @return Array array 
     */
    /*
    private function shopRecurringPaymentStatusNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $dp_notification_data = array();
        $vat            = $this->container->getParameter('vat');
        $reg_fee        = $this->container->getParameter('reg_fee');
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getRecurringPaymentStatusNotification($user_id);
        if (count($shop_status_notification) == 0) {
            //no notification found
            //return success
            return $shop_status_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
       
        foreach ($shop_status_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = $user_service->UserObjectService($from_id);
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            
            $recurring_payment_obj = $em
                    ->getRepository('TransactionTransactionBundle:RecurringPayment')
                    ->findOneBy(array('id' => (int)$item_id));
            $amount_euro = 0;
            $store_id = 0;
            if($recurring_payment_obj) {
                $amount = $recurring_payment_obj->getAmount();
                $amount_euro = $amount/1000000;
                $store_id = $recurring_payment_obj->getShopId();
            }
            $store_object = array();
            //getting the store information
            $store_detail = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => (int)$store_id));
            //check in store exist
            if (count($store_detail) > 0) {
                $store_object = $user_service->getStoreObjectService($store_id);
                
            }
            $dp_notification_data[] = array('notification_id' => $notification_id,'message_status'=>$message_status ,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object,'amount'=>$amount_euro);
        }
        return $dp_notification_data;
    }
    */
    
    /**
     * function for getting all the shop recurring payment status notifications
     * @param type $user_id
     * @return Array array 
     */
    private function shopRecurringPaymentStatusNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $dp_notification_data = array();
        $vat            = $this->container->getParameter('vat');
        $reg_fee        = $this->container->getParameter('reg_fee');
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        /*$shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getRecurringPaymentStatusNotification($user_id);*/
        $shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getRecurringPaymentStatusNotificationNew($user_id);
        if (count($shop_status_notification) == 0) {
            //no notification found
            //return success
            return $shop_status_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        
        //getting the user ids.
        $all_items_ids = array();
        $all_shops_ids = array();
        $all_users_ids = array();
        $all_amount_arr = array();
        $store_item_map = array();
        
        $all_items_ids = array_map(function($items_record) {
            return "{$items_record->getItemId()}";
        }, $shop_status_notification);
        
        $all_users_ids = array_map(function($items_record) {
            return "{$items_record->getFrom()}";
        }, $shop_status_notification);
        
        $all_reccuring_records = $em
                    ->getRepository('TransactionTransactionBundle:RecurringPayment')
                    ->getAllRecurringRecords($all_items_ids);
        
        if($all_reccuring_records) {
            foreach($all_reccuring_records as $items_record) {
                $temp_amount = 0;
                $store_item_map[$items_record->getId()] = $items_record->getShopId();
                $all_shops_ids[] = $items_record->getShopId();
                $temp_amount = $items_record->getAmount();
                $all_amount_arr[$items_record->getId()] = $temp_amount/1000000; 
            }
            
        }
        
        $recurring_users_objects = array(); 
        $recurring_shops_objects = array();
        $recurring_users_objects    = $user_service->MultipleUserObjectService($all_users_ids);
        $recurring_shops_objects    = $user_service->getMultiStoreObjectService($all_shops_ids);
       
        
        foreach ($shop_status_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = isset($recurring_users_objects[$from_id]) ? $recurring_users_objects[$from_id] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            
            $amount_euro = 0;
            $amount_euro = isset($all_amount_arr[$item_id]) ? $all_amount_arr[$item_id] : array(); 
            $store_object = array(); 
            if(isset($all_amount_arr[$item_id])) {
                $store_object = isset($recurring_shops_objects[$store_item_map[$item_id]]) ? $recurring_shops_objects[$store_item_map[$item_id]] : array();
                $dp_notification_data[] = array('notification_id' => $notification_id,'message_status'=>$message_status ,'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_object,'amount'=>$amount_euro,
                                                'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
            }
                      
            
            
        }
        return $dp_notification_data;
    }
     /**
     * function for getting all the shot notification for a user
     * @param type $user_id
     * @return Array array of all the shots notification  
     */
//    private function shotsNotification($user_id) {
//        $data = array();
//        $user_id = $user_id;
//        $shot_notification_data = array();
//        // get documen manager object
//        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        $shots_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
//                ->getShotNotification($user_id);
//        if (count($shots_notification) == 0) {
//            //no notification found
//            //return success
//            return $shots_notification;
//        }
//        //get entity object
//        $em = $this->getDoctrine()->getManager();
//        $user_service = $this->get('user_object.service');
//        foreach ($shots_notification as $notification) {
//            $notification_id = $notification->getId();
//            $from = $notification->getFrom();
//            //get $from user object
//            $from_id = $notification->getFrom();
//            $user_info = $user_service->UserObjectService($from_id);
//            $message_type = $notification->getMessageType();
//            $message = $notification->getMessage();
//            $item_id = $notification->getItemId();
//            $message_status = $notification->getMessageStatus();
//            //get store offer object 
//            $shot_info = $em
//                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
//                    ->findOneBy(array('id' => $item_id));
//            //check if store offer exist
//            if (count($shot_info) > 0) {
//                //get store detail
//                $store_detail = $user_service->getStoreObjectService($shot_info->getshopId());
//                //check if store details exist
//                if (count($store_detail) > 0) {
//                    $store_array = $store_detail;
//                    $shot_amount = $this->container->getParameter('shot_amount');
//                    $shot_notification_data[] = array('notification_id' => $notification_id, 'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_array, 'shot_amount' => $shot_amount, 'message_status' => $message_status);
//                }
//            }
//        }
//        return $shot_notification_data;
//    }
    
    
    private function shotsNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $shot_notification_data = array();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        /*$shots_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShotNotification($user_id);*/
        $shots_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShotNotificationNew($user_id);
        if (count($shots_notification) == 0) {
            //no notification found
            //return success
            return $shots_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        //loop for getting the from user id and item ids
        foreach($shots_notification as $notification){
             $shot_user_id[] = $notification->getFrom();
             $shot_item_id[] = $notification->getItemId();
        }
        
        //count number of items in notification
        $shot_item_count = count($shot_item_id);
        
        //if item count id grater then 0
        if($shot_item_count > 0) {
        $store_details = $em
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getStoreIdFromItemId($shot_item_id);
        }
        
        
        $store_ids = array();
        $store_shot = array();
        // loop for getting the discount position and store id
        foreach($store_details as $store_offer){
             $store_ids[] = $store_offer->getShopId();
             $store_shot[$store_offer->getShopId()] = $store_offer->getShots();
             $store_item_map[$store_offer->getId()] = $store_offer->getShopId();
        }
        //get unique store id
        $store_ids = array_unique($store_ids);
        //get unique user id
        $shot_user_id = array_unique($shot_user_id);
        //getting the user object and shop objects from respective ids
        $shot_user_objects = $user_service->MultipleUserObjectService($shot_user_id);
        $shot_shop_objects = $user_service->getMultiStoreObjectService($store_ids);
        
        foreach ($shots_notification as $notification) {
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = isset($shot_user_objects[$from]) ? $shot_user_objects[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            if(isset($store_item_map[$item_id])){
            $store_info = isset($shot_shop_objects[$store_item_map[$item_id]]) ? $shot_shop_objects[$store_item_map[$item_id]] : array();
            $shot_amount = isset($store_shot[$store_item_map[$item_id]]) ? $store_shot[$store_item_map[$item_id]] : 0;
            $shot_amount = $shot_amount/1000000;
            $shot_notification_data[] = array('notification_id' => $notification_id, 'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_info, 'shot_amount' => $shot_amount,
                                              'message_status' => $message_status,'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
        }
        }
        return $shot_notification_data;
    }

    
    /**
     * Get notification list
     * @param Request $request
     */
    public function GetBillingCirclenotifications($user_id) {
        $data = array();
        //get user login id
        $user_id = (int)$user_id;
        $billing_circle_notification_data = array();
        $billing_circle_notifications = array();
        //@TODOcheck for active member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
       //get billing circle notifications
        /*$billing_circle_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getBillingCircleNotification($user_id);*/
       $billing_circle_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getBillingCircleNotificationNew($user_id);
      
        if (count($billing_circle_notifications) == 0) {
            //no notification found
            //return success
           return $billing_circle_notifications;
        }
        
       // get entity manager object
         $em = $this->getDoctrine()->getManager();
         $user_service = $this->get('user_object.service');
         
        foreach($billing_circle_notifications as $notification){
             $billing_user_id[] = $notification->getFrom();
             $billing_item_id[] = $notification->getItemId();
        }
        $billing_user_objects = array(); 
        $billing_shop_objects = array();
        $billing_user_objects    = $user_service->MultipleUserObjectService($billing_user_id);
        $billing_shop_objects    = $user_service->getMultiStoreObjectService($billing_item_id);
         
         foreach($billing_circle_notifications as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             //$user_info = $billing_user_objects[$from_id];
             $user_info = isset($billing_user_objects[$from_id]) ? $billing_user_objects[$from_id] : array();
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $message_status = $notification->getMessageStatus();
             //get store detail
             if(isset($billing_shop_objects[$item_id])){
             $store_detail = $billing_shop_objects[$item_id];
             if($store_detail){
             $store_array = $store_detail;
             $billing_notification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message'=>$message, 'message_status'=>$message_status, 'shop_info'=>$store_array,'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
             }
             }
             }

        return $billing_notification_array;
    }
    
    public function shopDPNotification($user_id) {
        $data = array();
        $user_id = $user_id;
        $shop_dp_notification_data = array();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        /*$shop_dp_notifications = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopDPNotification($user_id);*/
        $shop_dp_notifications = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopDPNotificationNew($user_id);
        if (count($shop_dp_notifications) == 0) {
            //no notification found
            //return success
            return $shop_dp_notification_data;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        foreach ($shop_dp_notifications as $shop_dp_notification) {
            $notification_id = $shop_dp_notification->getId();
            $from = $shop_dp_notification->getFrom();
            //get $from user object
            $from_id = $shop_dp_notification->getFrom();
            $user_info = $user_service->UserObjectService($from_id);
            $message_type = $shop_dp_notification->getMessageType();
            $message = $shop_dp_notification->getMessage();
            $item_id = $shop_dp_notification->getItemId();
            $message_status = $shop_dp_notification->getMessageStatus();
            
            $store_detail = $user_service->getStoreObjectService($item_id);
            //check if store exist
                if (count($store_detail) > 0) {
                    $store_array = $store_detail;
                    $shot_amount = $this->container->getParameter('shop_discount_position_amount');
                    $shop_dp_notification_data[] = array('notification_id' => $notification_id, 'notification_from' => $user_info, 'message_type' => $message_type, 'message' => $message, 'shop_info' => $store_array, 'discount_amount' => $shot_amount, 'message_status' => $message_status,
                                                         'is_read'=>(int)$shop_dp_notification->getIsRead(),'create_date'=>$shop_dp_notification->getDate());
                }
        }
        return $shop_dp_notification_data;
    }
    
    /**
    * Function to retrieve s3 server base
    */
   public function getS3BaseUri() {
       //finding the base path of aws and bucket name
       $aws_base_path = $this->container->getParameter('aws_base_path');
       $aws_bucket    = $this->container->getParameter('aws_bucket');
       $full_path     = $aws_base_path.'/'.$aws_bucket;
       return $full_path;
   }
   
   /**
    * Get post rating notification for user wall
    * @param int $user_id
    * @return array
    */
   public function getPostRatingNotifications($notification, $users)
   {
       $response = array();
       try{
           $rating_notification = $notification;
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();;
            $response = array('notification_id'=>$notification_id, 
                'notification_from'=>$notification_from,
                'message_type' =>$message_type,
                'message'=>"rate",
                'message_status'=>$message_status,
                'post_info'=>array('postId'=>$item_id, 'rate'=>$message),
                'is_read'=>(int)$rating_notification->getIsRead(),
                'create_date'=>$rating_notification->getDate());
        }catch(\Exception $e){
           
        }
        return $response;
   }

   /**
    * Get dashboard comment rating notification for user wall
    * @param int $user_id
    * @return array
    */
   protected function getDashboardCommentRatingNotifications($notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
           $rating_notification = $notification;
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>array('postId'=>$postId, 'rate'=>$message),
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate());
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
   /**
    * Get user album rating notification for user wall
    * @param int $user_id
    * @return array
    */
   protected function getUserAlbumRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $dm
                  ->getRepository('MediaMediaBundle:UserAlbum')
                  ->find($item_id);
            
            $media_details = array();
            if($media_detail){

                    $media_details['albumId'] = $media_detail->getId();
                    $media_details['albumTitle'] = $media_detail->getAlbumName();
                    $media_details['userId'] = $media_detail->getUserId();
                    $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                    $media_details['rate'] = $message;
                    $media_details['album_type'] = $message_type;
                    $media_details['owner_id'] = $media_detail->getUserId();
                    $response = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>"rate",
                        'message_status'=>$message_status,
                        'post_info'=>$media_details,
                        'is_read'=>(int)$rating_notification->getIsRead(),
                        'create_date'=>$rating_notification->getDate());
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
      
   /**
    * Get user album image rating notification for user wall
    * @param int $user_id
    * @return array
    */
   protected function getUserAlbumImageRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $dm
                ->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array('id'=>$item_id));


           if($media_detail)
            {

                $media_name = $media_detail->getName();
                $user_id = $media_detail->getUserid();
                $album_id = $media_detail->getAlbumid();

                $user_album = $dm
                  ->getRepository('MediaMediaBundle:UserAlbum')
                  ->find($album_id);
                $photo_info['albumId'] = $user_album->getId();
                $photo_info['albumTitle'] = $user_album->getAlbumName();
                $photo_info['userId'] = $user_album->getUserId();
                $photo_info['albumDesc'] = $user_album->getAlbumDesc();
                $photo_info["photoId"] = $item_id;
                $photo_info["owner_id"] = $user_album->getUserId();
                $photo_info["album_type"] = $message_type;

                $photo_info['rate'] = $message;
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>$photo_info,'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate());
            }


        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }

   /**********************************************/
      /**
     * Get Notification Count
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postGetnotificationscountsAction(Request $request)
    {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) 
        {
            $de_serialize = $fde_serialize;
        } 
        else 
        {
            $de_serialize = $this->getAppData($request);
        }
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) 
        {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        /*Fetch all the is_view Count in User Notification*/
           $notificationCount = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getisviewcountUserNotification($user_id);
        
       $data_res = array('code' => 101, 'message' => 'SUCCESS', 'count' => $notificationCount);  
        echo json_encode($data_res);
        exit();

    }
      /**
     * Get Group Notification Count
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
     public function postGetallnotificationscountsAction(Request $request)
    {
         
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) 
        {
            $de_serialize = $fde_serialize;
        } 
        else 
        {
            $de_serialize = $this->getAppData($request);
        }
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id','notification','message','group');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) 
        {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        $notification = $object_info->notification;
        $message = $object_info->message;
        $group = $object_info->group;
        $transaction = isset($de_serialize['transaction']) ? $de_serialize['transaction'] : 0;
        $notificationCount = 0;
        $groupCount = 0;
        $messageCount = 0;   
        $transactionNotifications=0;
        $notificationTypes = isset($de_serialize['notification_type']) ? $de_serialize['notification_type'] : array();
        $nTypes = array('include'=>array(), 'exclude'=>array());
        if(!empty($notificationTypes)){
           foreach($notificationTypes as $nType=>$val){
               switch(trim($val)){
                   case '1':
                       $nTypes['include'][] = $nType;
                       break;
                   case '0':
                       $nTypes['exclude'][] = $nType;
                       break;
               }
           } 
        }
        /*if($notification==0 || $message==0 || $group==0)
        {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER', 'data' => $data);
        }*/
        
        if($group==1)
        {
            $group_notifications_Is_View_Count = $this->get('doctrine_mongodb')
                 ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                 ->getisviewcountGroupNotification($user_id);
            $groupCount = count($group_notifications_Is_View_Count);           
        }
        if($notification==1)
        {
            $notificationCount = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getisviewcountUserNotification($user_id, false, 7, $nTypes);
            
        }
        if($message==1)
        {
                $message_total_dm = $this->get('doctrine.odm.mongodb.document_manager');
                $deleted_thread = $message_total_dm->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupDeletedMemebersThreadId($user_id);        
                //getting deleted member thread id array
                if(is_array($deleted_thread) && count($deleted_thread))
                {
                    $thread_ids = array_map(function($threads) 
                    {
                        return $threads->getId();
                    }, $deleted_thread);    
                } 
                else 
                {
                    $thread_ids = array();
                }
                /*$message_total_res = $message_total_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listGroupUnreadTotalThread($user_id);    */
                $message_total_res = $message_total_dm->getRepository('MessageMessageBundle:Message')
                ->listAllGroupUnViewedTotalThread($user_id,$thread_ids);
                if($message_total_res)
                {
                    $messageCount = count($message_total_res);
                }
                else
                {
                     $messageCount = 0;
                }
        }
        
        if($transaction){
            $transactionNotifications = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getUnViewedTransactionNotification($user_id);
        }
        
        $data_res = array('code' => 101, 
            'message' => 'SUCCESS', 
            'data' =>array(
                'notificationCount'=>$notificationCount,
                'groupCount'=>$groupCount,
                'messageCount'=>$messageCount,
                'transactionCount'=>$transactionNotifications
            )
        );  
        echo json_encode($data_res);
        exit();
    }
 
    /**
     * Mark the notification as delete
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postMarkdeletenotificationsAction(Request $request)
    {
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

        $required_parameter = array('user_id','notification_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $notification_id = $object_info->notification_id;
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get friend approved notification
        $mark_notification_read = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->markNotificationAsDelete($user_id, $notification_id);
       if(!$mark_notification_read){
           return array('code' => 152, 'message' => 'NO_NOTIFICATION_FOUND', 'data' => $data);
       }
       
       //return the result
       $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
       echo json_encode($res_data);
       exit();

    }
    public function postGetallgroupnotificationsAction(Request $request)
    {
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
        $required_parameter = array('user_id','is_view');
        $data = array();
        $group_join_notifications = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        $is_view = $object_info->is_view;
        
        /* Limit Set with Notification list*/
        $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:10);
        $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);
        /*End here notification list*/
        /*Update  is view in GroupNotification and GroupNotifications*/
            $user_notifications_Is_View_Count = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getisviewUpdateGroupNotification($user_id,$is_view);      
        //get group join notifications 
        $group_join_notifications = $this->Getgroupjoinnotifications($user_id,$limit,$offset);
        $group_join_notificationsCount = $this->GetgroupjoinnotificationsCount($user_id);
        $data_res = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('requests'=>$group_join_notifications,'size'=>count($group_join_notificationsCount)));  
        echo json_encode($data_res);
        exit();
    }
   /**********************************************/
    
    /**
    * Get club post rating notification for user wall
    * @param int $user_id
    * @return array
    */
   public function getClubPostRatingNotifications($rating_notification, $users)
   { 
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
        
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $_post = $dm->getRepository('PostPostBundle:Post')
                        ->find($item_id);
            if($_post){
                $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                            ->find($_post->getPostGid());
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>array(
                      'postId'=>$_post->getId(),
                      'clubId'=>$_post->getPostGid(),
                       'rate'=>$message,
                        "status"=>$_club->getGroupStatus(),
                        'clubName'=>$_club->getTitle()
                    ),
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate()
                    );
            }
       }catch(\Exception $e){
           
       }
       return $response;
   }
   
   /**
    * Get club post comment rating notification
    * @param int $user_id
    * @return array
    */
   protected function getClubPostCommentRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');  
       
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $comment = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $_post = $dm->getRepository('PostPostBundle:Post')
                    ->find($postId);
                if($_post){
                    $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($_post->getPostGid());
                    $response = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>"rate",
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'clubId'=>$_post->getPostGid(),
                            "rate"=>$message,
                            "status"=>$_club->getGroupStatus(),
                            'clubName'=>$_club->getTitle()
                          ),
                        'is_read'=>(int)$rating_notification->getIsRead(),
                        'create_date'=>$rating_notification->getDate()
                        );
                }
               
            }

        }catch(\Exception $e){
           
       }
       return $response;
   }
   
   /**
    * Get club album rating notification
    * @param int $user_id
    * @return array
    */
   protected function getClubAlbumRatingNotifications($rating_notification, $users)
   { 
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');  
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $dm
                  ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                  ->find($item_id);
            
            if($media_detail){
                $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->find($media_detail->getGroupId());
                    $media_details = array();
                    $media_details['albumId'] = $media_detail->getId();
                    $media_details['albumTitle'] = $media_detail->getAlbumName();
                    $media_details['clubId'] = $media_detail->getGroupId();
                    $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                    $media_details['rate']=$message;
                    $media_details["status"]=$_club->getGroupStatus();
                    $media_details["clubName"]=$_club->getTitle();

                    $response = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>"rate",
                        'message_status'=>$message_status,
                        'post_info'=>$media_details,
                        'is_read'=>(int)$rating_notification->getIsRead(),
                        'create_date'=>$rating_notification->getDate());
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get club album image rating notification
    * @param int $user_id
    * @return array
    */
   protected function getClubAlbumImageRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');  
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->findOneBy(array('id'=>$item_id));
          
           if($media_detail)
            {
                $media_name = $media_detail->getMediaName();
                $group_id = $media_detail->getGroupId();
                $album_id = $media_detail->getAlbumid(); 
                $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($group_id);
                $club_album = $dm
                  ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                  ->find($album_id);
                $photo_info['albumId'] = $club_album->getId();
                $photo_info['albumTitle'] = $club_album->getAlbumName();
                $photo_info['clubId'] = $club_album->getGroupId();
                $photo_info['owner_id'] = $club_album->getGroupId();
                $photo_info['album_type'] = $message_type;
                $photo_info['albumDesc'] = $club_album->getAlbumDesc();
                $photo_info['rate']=$message;
                $photo_info["status"]=$_club->getGroupStatus();
                $photo_info["photoId"] = $item_id;
                $photo_info["clubName"]=$_club->getTitle();
                
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>$photo_info,
                    'is_read'=>(int)$rating_notification->getIsRead()
                    ,'create_date'=>$rating_notification->getDate());
            }
            
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get club post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getClubPostCommentNotifications($notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
        
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $_post = $dm->getRepository('PostPostBundle:Post')
                    ->find($postId);
                if($_post){
                    $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($_post->getPostGid());
                    $response = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'clubId'=>$_post->getPostGid(),
                            'status'=>$_club->getGroupStatus(),
                            'clubName'=>$_club->getTitle()
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
            }

        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get store post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getStorePostCommentNotifications($notification, $users)
   {
       $response = array();
       try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
        
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                    ->find($postId);
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                        'postId'=>$_post->getId(),
                        'shopId'=>$_post->getStoreId()
                      ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get dashboard post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getDashboardPostCommentNotifications($notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');  
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                        'postId'=>$postId,
                      ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get shop post rating notification
    * @param int $user_id
    * @return array
    */
   public function getShopPostRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');  
        
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                        ->find($item_id);
            if($_post){
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>array(
                      'postId'=>$_post->getId(),
                      'shopId'=>$_post->getStoreId(),
                       'rate'=>$message
                    ),
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate()
                    );
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get shop post comment rating notification
    * @param int $user_id
    * @return array
    */
   protected function getShopPostCommentRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);
            if($comment)
            {
                $postId = $comment->getPostId();
                $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                    ->find($postId);
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>array(
                        'postId'=>$_post->getId(),
                        'shopId'=>$_post->getStoreId(),
                        "rate"=>$message
                      ),
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate()
                    );
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get shop album rating notification
    * @param int $user_id
    * @return array
    */
   protected function getShopAlbumRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
           $em = $this->getDoctrine()->getManager();
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $em
                  ->getRepository('StoreManagerStoreBundle:Storealbum')
                  ->find($item_id);
            
            if($media_detail){
                $media_details = array();
                $media_details['albumId'] = $media_detail->getId();
                $media_details['albumTitle'] = $media_detail->getStoreAlbumName();
                $media_details['shopId'] = $media_detail->getStoreId();
                $media_details['albumDesc'] = $media_detail->getStoreAlbumDesc();
                $media_details['owner_id'] = $media_detail->getStoreId();
                $media_details['album_type'] = $message_type;
                $media_details['rate']=$message;
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>$media_details,
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate());
            }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * Get shop album image rating notification
    * @param int $user_id
    * @return array
    */
   protected function getShopAlbumImageRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
           $em = $this->getDoctrine()->getManager();
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            //get media details
            $media_detail = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->find($item_id);
          
            if($media_detail)
             {
                 $photoId = $media_detail->getId();
                 $media_name = $media_detail->getImageName();
                 $store_id = $media_detail->getStoreId();
                 $store_album_id = $media_detail->getAlbumId();

                 $shop_album = $em
                   ->getRepository('StoreManagerStoreBundle:Storealbum')
                   ->find($store_album_id);

                 $photo_info['albumId'] = $shop_album->getId();
                 $photo_info['albumTitle'] = $shop_album->getStoreAlbumName();
                 $photo_info['shopId'] = $shop_album->getStoreId();
                 $photo_info['photoId']=$photoId;
                 $photo_info['albumDesc'] = $shop_album->getStoreAlbumDesc();
                 $photo_info['owner_id'] = $shop_album->getStoreId();
                 $photo_info['rate']=$message;
                 $photo_info['album_type']=$message_type;                     
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>"rate",
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$rating_notification->getIsRead(),
                     'create_date'=>$rating_notification->getDate());
             }
        }catch(\Exception $e){
           
        }
        return $response;
   }
   
   /**
    * 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return type
    */
    public function postGetpushnotificationsAction(Request $request)
    {
        /** get request object **/
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        /** parameter check start **/
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id');
        $data = array();
        $notification_array = array();
        
        /** checking for parameter missing. **/
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;
        
        /** user object service **/
        $user_service = $this->get('user_object.service');
        
        /** check limit start **/
        if(isset($object_info->limit_start) && $object_info->limit_start !='') {
            $limit_start   = $object_info->limit_start;
        }else{
            $limit_start   = 0;
        }
        
         /** check limit size **/
        if(isset($object_info->limit_size) && $object_info->limit_size !='') {
            $limit_size   = $object_info->limit_size;
        }else {
            $limit_size   = 50;
        }
        
        /** get documen manager object **/
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $transaction_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getTransactionNotification($user_id,$limit_start,$limit_size, true);
        $transaction_notification_count = 0;
        $transaction_notification_count = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getTransactionNotificationCount($user_id, true);
        
        if(count($transaction_notification) >0) {
            
            /** getting the senders user id. **/
            $sender_user_ids = array_map(function($record) {
                return "{$record->getFrom()}";
            }, $transaction_notification);
            
            /** getting the recivere user id. **/
            $reciever_user_ids = array_map(function($record) {
                return "{$record->getTo()}";
            }, $transaction_notification);
            
            /** fetch user object **/
            $users_array = array_unique(array_merge($reciever_user_ids,$sender_user_ids));
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
            
            foreach($transaction_notification as $notification){
                $notification_id = $notification->getId();
                /** get $from user object **/
                $from_id= $notification->getFrom();
                $to_id= $notification->getTo();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $time_stamp = strtotime($notification->getDate()->format('Y-m-d H:i:s'));
                $timezone =  date_default_timezone_get();
                $info = $notification->getInfo();
                //$store_info = isset($info['store_info']) ? $info['store_info'] : array();
                //unset($info['store_info']);
                $notification_info = array(
                            'notification_id'=>$notification_id,
                            'notification_from'=>isset($users_object_array[$from_id]) ? $users_object_array[$from_id] : array(),
                            'notification_to'=>isset($users_object_array[$to_id]) ? $users_object_array[$to_id] : array(),
                            'item_type' =>$message_type,
                            'message'=>$message,
                            'item_id'=>$item_id,
                            'date' =>$notification->getDate(),
                            'timestamp' => $time_stamp,
                            'timezone' => $timezone,
                            'info' => $info
                        );
                
            $notification_array[] = $notification_info;
            }
            
        }
        
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' =>array('notifications' => $notification_array, 'size' => $transaction_notification_count));
        $this->returnResponse($response_data);
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array,JSON_NUMERIC_CHECK);
        exit;
    }
    
    /**
    * Get club rating notification
    * @param int $user_id
    * @return array
    */
   public function getClubRatingNotifications($rating_notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $from      = $rating_notification->getFrom();
            $notification_id = $rating_notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $rating_notification->getMessageType();
            $message_status = $rating_notification->getMessageStatus();
            $message = $rating_notification->getMessage();
            $item_id = $rating_notification->getItemId();
            $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($item_id);
            if($_club){
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>"rate",
                    'message_status'=>$message_status,
                    'post_info'=>array(
                      'clubId'=>$_club->getId(),
                       'rate'=>$message,
                        "status"=>$_club->getGroupStatus(),
                        "clubName"=>$_club->getTitle()
                    ),
                    'is_read'=>(int)$rating_notification->getIsRead(),
                    'create_date'=>$rating_notification->getDate()
                );
            }

        }catch(\Exception $e){
           
        }
        return $response;
   }
   
    /**
     * function for getting all the transaction related notification
     * @param type $user_id
     * @return Array array of all the transaction related notification
     */
    private function shopTransactionNotification($user_id) { 
        $data = array();
        $user_id = (string)$user_id;
        $dp_notification_data = array();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        /*$shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopStatusNotification($user_id);*/
        $shop_status_notification = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getShopTransactionNotification($user_id);
        if (count($shop_status_notification) == 0) {
            //no notification found
            //return success
            return $shop_status_notification;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
             
        //getting the user ids.
        $all_froms_ids = array();
        $all_froms_ids = array_map(function($froms_record) {
            return "{$froms_record->getFrom()}";
        }, $shop_status_notification);
        
        $pay_users_objects = array();
        $pay_shops_objects = array();
        
        $pay_users_objects    = $user_service->MultipleUserObjectService($all_froms_ids);
        
       
        foreach ($shop_status_notification as $notification) {
            $notification_id = $notification->getId();
           // $from = $notification->getFrom();
            //get $from user object
            $from_id = $notification->getFrom();
            $user_info = isset($pay_users_objects[$from_id]) ? $pay_users_objects[$from_id] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = isset($info['store_info']) ? $info['store_info'] : array();
            unset($info['store_info']);
            $dp_notification_data[] = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'transaction_info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate(),
                'shop_info'=>$store_info
            );
        }
        return $dp_notification_data;
    }
    
    /**
    * Get post notification for user wall
    * @param int $user_id
    * @return array
    */
   public function getUserWallPostNotifications($notification, $users)
   {
        $response = array();
        try{
                $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
                $from      = $notification->getFrom();
                $notification_id = $notification->getId();
                $notification_from = isset($users[$from]) ? $users[$from] : array();
                $message_type = $notification->getMessageType();
                $message_status = $notification->getMessageStatus();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array('postId'=>$item_id),'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate());
            }catch(\Exception $e){
               // echo $e->getMessage();
        }

        return $response;
   }
   
   /**
    * Get shop post notification
    * @param int $user_id
    * @return array
    */
   public function getShopPostNotifications($notification,$users)
   { 
            $response = array();
           try{
            // get documen manager object
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                        ->find($item_id);
            if($_post){
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                      'postId'=>$_post->getId(),
                      'shopId'=>$_post->getStoreId(),
                    ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
           }catch(\Exception $e){
               
           }
        return $response;
   }
   
   /**
    * Get club post rating notification for user wall
    * @param int $user_id
    * @return array
    */
   public function getClubPostNotifications($notification,$users)
   { 
       $response = array();
        try{
            // get documen manager object
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $_post = $dm->getRepository('PostPostBundle:Post')
                        ->find($item_id);
            if($_post){
                $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($_post->getPostGid());
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                      'postId'=>$_post->getId(),
                      'clubId'=>$_post->getPostGid(),
                        "status"=>$_club->getGroupStatus(),
                        'clubName'=>$_club->getTitle()
                    ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
            
            }catch(\Exception $e){
            }
        return $response;
   }
   
   /**
    * Get dashboard post comment tagging notification for user wall
    * @param int $user_id
    * @return array
    */
   public function getDashboardCommentTaggingNotifications($notification, $users)
   {
       $response = array();
       try{
           $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        
            $commentId = $notification->getItemId();
            $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')->getCommentsByIds(array($commentId));
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            if(isset($comments[$item_id])){
                $_comment = $comments[$item_id];
                $response = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array('postId'=>$_comment->getPostId()),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }

       }catch(\Exception $e){
           
       }
       return $response;
   }
   
   /**
    * Get club post comment tagging notification for user wall
    * @param int $user_id
    * @return array
    */
   protected function getClubCommentTaggingNotifications($notification, $users)
   {
        $n_info = array();
        
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $_post = $dm->getRepository('PostPostBundle:Post')
                        ->find($postId);
                    $_postGid = $_post->getPostGid();
                    
                    $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($_postGid);
                                        
                    $n_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'clubName'=>$_club->getTitle(),
                            'postId'=>$_post->getId(),
                            'clubId'=>$_club->getId(),
                            "status"=>$_club->getGroupStatus()
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }
 
        return $n_info;
   }
   
   /**
    * Get shop post comment tagging notification
    * @param int $user_id
    * @return array
    */
   protected function getShopCommentTaggingNotifications($notification, $users)
   {
        $n_info = array();

        try {
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $em = $this->getDoctrine()->getManager();
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                        ->find($postId);
                    $_postStoreId = $_post->getStoreId();

                    $store = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => $_postStoreId));

                    $storeName = $store->getName() != '' ? $store->getName() : $store->getBusinessName();
                    $n_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'shopId'=>$store->getId(),
                            'shopName'=> $storeName
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }

        return $n_info;
   }
   
   /**
    * Get dashboard post comment on commented notification
    * @param int $user_id
    * @return array
    */
   protected function getDashboardPostCommentOnCommentedNotifications($notification, $users)
   {

        $notification_info = array();
        
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);

            if($comment)
            {
                $postId = $comment->getPostId();
                $notification_info = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                        'postId'=>$postId,
                      ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
       }catch(\Exception $e){

       }
       return $notification_info;
   }
   
   /**
    * Get store post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getStorePostCommentOnCommentedNotifications($notification, $users)
   {
       
        $notification_info = array();

        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                        ->find($postId);
                    $notification_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'shopId'=>$_post->getStoreId()
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }

        return $notification_info;
   }
   
   /**
    * Get club post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getClubPostCommentOnCommentedNotifications($notification, $users)
   {

        $notification_info = array();

        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $_post = $dm->getRepository('PostPostBundle:Post')
                        ->find($postId);
                    $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                        ->find($_post->getPostGid());
                    $notification_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'clubId'=>$_post->getPostGid(),
                            'status'=>$_club->getGroupStatus(),
                            'clubName'=>$_club->getTitle()
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }

        return $notification_info;
   }
   
   /**
    * Get dashboard wall post comment  notification
    * @param int $user_id
    * @return array
    */
   protected function getDashboardWallPostCommentNotifications($notification, $users)
   {

        $notification_info = array();

        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $notification_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$postId,
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }

        return $notification_info;
   }
   
   /**
    * Get dashboard comment on tagged post  notification
    * @param int $user_id
    * @return array
    */
   protected function getDashboardCommentOnTaggedPostNotifications($notification, $users)
   {
        $data = array();
        $notification_from_ids = array();
        $notification_info = array();
        
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
          
            if($comment)
            {
                $postId = $comment->getPostId();
                $notification_info = array('notification_id'=>$notification_id, 
                    'notification_from'=>$notification_from,
                    'message_type' =>$message_type,
                    'message'=>$message,
                    'message_status'=>$message_status,
                    'post_info'=>array(
                        'postId'=>$postId,
                      ),
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                    );
            }
           }catch(\Exception $e){
               
           }

        return $notification_info;
   }
   
   /**
    * Get store post comment notification
    * @param int $user_id
    * @return array
    */
   protected function getStoreCommentOnTaggedPostNotifications($notification, $users)
   {

        $notification_info = array();
        
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);

                if($comment)
                {
                    $postId = $comment->getPostId();
                    $_post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                        ->find($postId);
                    $notification_info = array('notification_id'=>$notification_id, 
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'postId'=>$_post->getId(),
                            'shopId'=>$_post->getStoreId()
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                        );
                }
           }catch(\Exception $e){
               
           }

        return $notification_info;
   }
   
   private function getTaggedInPostNotification($notification, $users){

        $friend_notification_array = array(); 
        
        try{ 
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = isset($users[$from_id]) ? $users[$from_id] : array();
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();

                $post_info['postId'] = $notification->getItemId();
                $friend_notification_array = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type, 'message_status' =>'U','message'=>$message, 'post_info'=>$post_info,'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
            }catch(\Exception $e){
                
            }

         return $friend_notification_array;
   }
   
   private function getTaggedInUserAlbumPhotoNotification($notification, $users){
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); 

        //prepare from id array
        $tag_media_ids = array();
        $friend_notification_array = array(); 
        if($notification->getItemId()){
            $tag_media_ids[] = $notification->getItemId();
        }

        //get media details
        $media_details = $dm
               ->getRepository('MediaMediaBundle:UserMedia')
               ->findUserProfileMediaInfo($tag_media_ids);

        if(is_array($media_details)){

            $media_details_array = array();
            foreach($media_details as $media_detail){
                 $media_details_array[$media_detail->getId()]['id'] = $media_detail->getId();
                 $media_details_array[$media_detail->getId()]['name'] = $media_detail->getName();
                 $media_details_array[$media_detail->getId()]['user_id'] = $media_detail->getUserid();
                 $media_details_array[$media_detail->getId()]['album_id'] = $media_detail->getAlbumid();
            }

        } else {
             $media_details_array = array();
        }

        try{
             $notification_id = $notification->getId();
             
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = isset($users[$from_id]) ? $users[$from_id] : array();
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();

                $post_id = $notification->getItemId();
                if(isset($media_details_array[$post_id]))
                {
                    $media_name = $media_details_array[$post_id]['name'];

                    $__user_id = $media_details_array[$post_id]['user_id'];
                    $album_id = $media_details_array[$post_id]['album_id']; 
                    $request = new Request();
                    $document_root = $request->server->get('DOCUMENT_ROOT');
                    $BasePath = $request->getBasePath();
                    
                    $photo_info['owner_id'] = $__user_id;
                    $photo_info['albumId'] = $album_id;                 
                    $photo_info["photoId"] = $post_id;
                    $photo_info['album_type']=$message_type;
                    $photo_info["media_path"] = $this->getS3BaseUri() . $this->user_media_path . $__user_id . '/' . $album_id . '/' . $media_name;
                    $photo_info["media_path_thumb"] = $this->getS3BaseUri() . $this->user_media_path_thumb . $__user_id . '/' . $album_id . '/' . $media_name;
                    $friend_notification_array = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type, 'message_status' =>'U','message'=>$message, 'photo_info'=>$photo_info,
                                                     'is_read'=>(int)$notification->getIsRead(),'create_date'=>$notification->getDate());
                }
             }catch(\Exception $e){
                 
             }
         
         return $friend_notification_array;
   }
   
   
   /**
    *  function for getting the notification for the transaction rating 
    * @param type $user_id
    * @return type
    */
   public function getTransactionRatingNotification($user_id) {
       $notification_array = array();
        $user_id = $user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get group response notification
         $transaction_rating_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getTransactionRatingNotification($user_id);

         $user_service = $this->get('user_object.service');
         foreach($transaction_rating_notifications as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $store_info = isset($info['store_id']) ? $user_service->getStoreObjectService($info['store_id']) : array();
             $is_read = (int)$notification->getIsRead();
             $create_date = $notification->getDate();
             $notification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message_status' => 'T','message'=>$message, 'transaction_info'=>$info,'is_read' => $is_read, 'create_date' => $create_date,'shop_info' => $store_info);
         }
         
         return $notification_array;
   }
   
   /**
    * Get recurring payment notifications
    * @param int $user_id
    * @return array
    */
   public function getRecurringPaymentNotifications($user_id)
   {
        $user_id = (string)$user_id;
        $recurring_notifications = array();
        $recurring_notification_data = array();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $recurring_notifications = $this->get('doctrine_mongodb')->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getRecurringNotifications($user_id);
        

        if (count($recurring_notifications) == 0) {
            //no notification found
            //return success
            return $recurring_notifications;
        }
        //get entity object
        $em = $this->getDoctrine()->getManager();
        $user_service = $this->get('user_object.service');
        foreach ($recurring_notifications as $recurring_notification) {
            $notification_id = $recurring_notification->getId();
            $from = $recurring_notification->getFrom();
            //get $from user object
            $from_id = $recurring_notification->getFrom();
            $user_info = $user_service->UserObjectService($from_id);
            $message_type = $recurring_notification->getMessageType();
            $message = $recurring_notification->getMessage();
            $item_id = $recurring_notification->getItemId();
            $message_status = $recurring_notification->getMessageStatus();
            
            $store_detail = $user_service->getStoreObjectService($item_id);
            //check if store exist
                if (count($store_detail) > 0) {
                    $store_array = $store_detail;
                    $recurring_notification_data[] = array(
                        'notification_id' => $notification_id, 
                        'notification_from' => $user_info, 
                        'message_type' => $message_type, 
                        'message' => $message, 
                        'shop_info' => $store_array,  
                        'message_status' => $message_status,
                        'is_read'=>(int)$recurring_notification->getIsRead(),
                        'create_date'=>$recurring_notification->getDate()
                      );
                }
        }
        return $recurring_notification_data;
   }
   
   /**
    *  function for getting the tagged user in transaction share post
    * @param type $user_id
    * @return type
    */
   public function getTaggedInShopTransactionNotification($user_id) {
       $notification_array = array();
        $user_id = $user_id;
        //get document object
         $dm = $this->get('doctrine.odm.mongodb.document_manager');
         
         //get group response notification
         $transaction_rating_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getTransactionTaggedNotification($user_id);

         $user_service = $this->get('user_object.service');
         foreach($transaction_rating_notifications as $notification){
             $notification_id = $notification->getId();
             $from = $notification->getFrom();
             //get $from user object
             $from_id= $notification->getFrom();
             $user_info = $user_service->UserObjectService($from_id);
            
             $message_type = $notification->getMessageType();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $store_info = isset($info['store_id']) ? $user_service->getStoreObjectService($info['store_id']) : array();
             $is_read = (int)$notification->getIsRead();
             $create_date = $notification->getDate();
             $notification_array[] = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message_status' => 'T','message'=>$message, 'transaction_info'=>$info,'is_read' => $is_read, 'create_date' => $create_date,'shop_info' => $store_info);             
         }
         
         return $notification_array;
    }
   protected function _getallnotifications($user_id, $limit_start=0, $limit_size=10, $nTypes=array()){
       $dm = $this->get('doctrine.odm.mongodb.document_manager');
         $user_service = $this->get('user_object.service');
         $this->mediaNotification = new MediaNotificationService();
         //get group response notification
         $notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getAllNotifications($user_id, $limit_start, $limit_size, false, 7, $nTypes);
         $response = array();
         if($notifications){
             $usersIds = array_map(function($notification){
                 return $notification->getFrom();
             }, $notifications);
             $users = $user_service->MultipleUserObjectService($usersIds);
             foreach($notifications as $notification){
                if(is_object($notification)){
                    $from = $notification->getFrom();
                    if(!key_exists($from, $users)){
                        continue;
                    }
                    $_response = $this->_getFormatNotification($notification, $users);
                    if(!empty($_response)){
                        $response[] = $_response;
                    }else{
                        //var_dump($notification);
                    }
                }
             }
         }
         return $response;
   }
   
   private function _getallnotificationsCount($user_id, $nTypes=array()){
       $dm = $this->get('doctrine.odm.mongodb.document_manager');
       $notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                ->getAllNotificationsCount($user_id, false, 7, $nTypes);
       return $notifications;
   }
   
   private function _getFormatNotification($notification, $users){
       $response = array();
       $messageType = $notification->getMessageType();
      
        switch(strtoupper($messageType)){
            case 'FRIEND':
                $response = $this->_getFriendNotification($notification, $users);
                break;
            case 'GROUP':
                $response = $this->_getGroupJoinResponseNotification($notification, $users);
                break;
            case 'BROKER':
                $response = $this->_getBrokerNotifications($notification, $users);
                break;
            case 'SHOP':
                $response = $this->_getShopNotification($notification, $users);
                break;
            case 'SHOP_RESPONSE':
                $response = $this->_getShopApprovalNotifications($notification, $users);
                break;
            case 'SHOPSTATUS':
                $response = $this->_getShopActiveInactiveNotification($notification, $users);
                break;
            case 'RECURRINGPAYMENT':
                $response = $this->_getShopRecurringPaymentStatusNotification($notification, $users);
                break;
            case 'DISCOUNT_POSITION':
                $response = $this->_getDiscountPositionNotification($notification, $users);
                break;
            case 'SHOT':
                $response = $this->_getShotsNotification($notification, $users);
                break;
            case 'DISCOUNT_POSITION_SHOP':
                $response = $this->_getShopDPNotification($notification, $users);
                break;
            case 'BILLING_CIRCLE':
                $response = $this->_getBillingCircleNotification($notification, $users);
                break;
            case 'DASHBOARD_POST_RATE':
                $response = $this->getPostRatingNotifications($notification, $users);
                break;
            case 'DASHBOARD_COMMENT_RATE':
                $response = $this->getDashboardCommentRatingNotifications($notification, $users);
                break;
            case 'USER_ALBUM_RATE':
                $response = $this->getUserAlbumRatingNotifications($notification, $users);
                break;
            case 'USER_PHOTO_RATE':
                $response = $this->getUserAlbumImageRatingNotifications($notification, $users);
                break;
            case 'POST_AT_USER_WALL':
                $response = $this->getUserWallPostNotifications($notification, $users);
                break;
            case 'POST_AT_CLUB_WALL':
                $response = $this->getClubPostNotifications($notification, $users);
                break;
            case 'POST_AT_SHOP_WALL':
                $response = $this->getShopPostNotifications($notification, $users);
                break;          
            case 'CLUB_POST_RATE':
                $response = $this->getClubPostRatingNotifications($notification, $users);
                break;
            case 'CLUB_POST_COMMENT_RATE':
                $response = $this->getClubPostCommentRatingNotifications($notification, $users);
                break;
            case 'CLUB_ALBUM_RATE':
                $response = $this->getClubAlbumRatingNotifications($notification, $users);
                break;
            case 'CLUB_ALBUM_PHOTO_RATE':
                $response = $this->getClubAlbumImageRatingNotifications($notification, $users);
                break;
            case 'DASHBOARD_POST_COMMENT':
                $response = $this->getDashboardPostCommentNotifications($notification, $users);
                break;
            case 'CLUB_POST_COMMENT':
                $response = $this->getClubPostCommentNotifications($notification, $users);
                break;
            case 'STORE_POST_COMMENT':
                $response = $this->getStorePostCommentNotifications($notification, $users);
                break;
            case 'STORE_POST_RATE':
                $response = $this->getShopPostRatingNotifications($notification, $users);
                break;
            case 'STORE_POST_COMMENT_RATE':
                $response = $this->getShopPostCommentRatingNotifications($notification, $users);
                break;
            case 'STORE_ALBUM_RATE':
                $response = $this->getShopAlbumRatingNotifications($notification, $users);
                break;
            case 'STORE_MEDIA_RATE':
                $response = $this->getShopAlbumImageRatingNotifications($notification, $users);
                break;
            case 'CLUB_RATE':
                $response = $this->getClubRatingNotifications($notification, $users);
                break;
            case 'USER_TAGGED_IN_DASHBOARD_COMMENT':
                $response = $this->getDashboardCommentTaggingNotifications($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_DASHBOARD_COMMENT':
                $response = $this->getDashboardCommentTaggingNotifications($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_DASHBOARD_COMMENT':
                $response = $this->getDashboardCommentTaggingNotifications($notification, $users);
                break;
            case 'USER_TAGGED_IN_CLUB_COMMENT':
                $response = $this->getClubCommentTaggingNotifications($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_CLUB_COMMENT':
                $response = $this->getClubCommentTaggingNotifications($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_CLUB_COMMENT':
                $response = $this->getClubCommentTaggingNotifications($notification, $users);
                break;
            case 'USER_TAGGED_IN_STORE_COMMENT':
                $response = $this->getShopCommentTaggingNotifications($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_STORE_COMMENT':
                $response = $this->getShopCommentTaggingNotifications($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_STORE_COMMENT':
                $response = $this->getShopCommentTaggingNotifications($notification, $users);
                break;
            case 'DASHBOARD_COMMENT_ON_TAGGED_POST':
                $response = $this->getDashboardCommentOnTaggedPostNotifications($notification, $users);
                break;
            case 'DASHBOARD_COMMENT_ON_COMMENTED':
                $response = $this->getDashboardPostCommentOnCommentedNotifications($notification, $users);
                break;
            case 'DASHBOARD_WALL_POST_COMMENT':
                $response = $this->getDashboardWallPostCommentNotifications($notification, $users);
                break;
            case 'STORE_COMMENT_ON_TAGGED_POST':
                $response = $this->getStoreCommentOnTaggedPostNotifications($notification, $users);
                break;
            case 'STORE_COMMENT_ON_COMMENTED':
                $response = $this->getStorePostCommentOnCommentedNotifications($notification, $users);
                break;
            case 'CLUB_COMMENT_ON_COMMENTED':
                $response = $this->getClubPostCommentOnCommentedNotifications($notification, $users);
                break;
            case 'TAGGED_IN_POST':
                $response = $this->getTaggedInPostNotification($notification, $users);
                break;
            case 'TAGGED_IN_PHOTO':
                $response = $this->getTaggedInUserAlbumPhotoNotification($notification, $users);
                break;
            case 'TXN':
                $response = $this->_getShopTransactionNotification($notification, $users);
                break;
            case 'RECURRING_NOTIFICATION':
                $response = $this->_getRecurringPaymentNotifications($notification, $users);
                break;
            case 'TAGGED_IN_SHOP_CUSTOMER_POST':
                $response = $this->_getTaggedInShopTransactionNotification($notification, $users);
                break;
            case 'CAMPAIGN_SHOPPING_CARD':
                $response = $this->_getCampaignShoppingCardNotification($notification, $users);
                break;
            case 'BUYS_SHOPPING_CARD':
                $response = $this->_getBuysShoppingCardNotification($notification, $users);
                break;
            case 'SELLS_SHOPPING_CARD':
                $response = $this->_getSellsShoppingCardNotification($notification, $users);
                break;
            case 'SUBSCRIPTION':
                $response = $this->_getSubscriptionShoppingCardNotification($notification, $users);
                break;
            case 'SHOP_AFFILIATION':
                $response = $this->_getReferralAmountShopAffiliationNotification($notification, $users);
                break;
            case 'USER_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumComment($notification, $users);
                break;
            case 'USER_ALBUM_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getUserAlbumComment($notification, $users);
                break;
            case 'CLUB_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumComment($notification, $users);
                break;
            case 'CLUB_ALBUM_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getClubAlbumComment($notification, $users);
                break;
            case 'STORE_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumComment($notification, $users);
                break;
            case 'STORE_ALBUM_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getStoreAlbumComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_STORE_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_STORE_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_STORE_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_USER_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_USER_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_USER_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumMediaComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumMediaComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumMediaComment($notification, $users);
                break;
            case 'STORE_MEDIA_COMMENT_RATE':
                $response = $this->mediaNotification->_getStoreAlbumMediaCommentRate($notification, $users);
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT_RATE':
                $response = $this->mediaNotification->_getClubAlbumMediaCommentRate($notification, $users);
                break;
            case 'USER_ALBUM_MEDIA_COMMENT_RATE':
                $response = $this->mediaNotification->_getUserAlbumMediaCommentRate($notification, $users);
                break;
            case 'USER_ALBUM_COMMENT_RATE':
                $response = $this->mediaNotification->_getUserAlbumCommentRate($notification, $users);
                break;
            case 'STORE_ALBUM_COMMENT_RATE':
                $response = $this->mediaNotification->_getStoreAlbumCommentRate($notification, $users);
                break;
            case 'CLUB_ALBUM_COMMENT_RATE':
                $response = $this->mediaNotification->_getClubAlbumCommentRate($notification, $users);
                break;
            case 'SUBSCRIPTION_RECURRING_NOTIFICATION':
                $response = $this->_getRecurringSubscriptionPaymentNotifications($notification, $users);
                break;
            case 'SOCIAL_PROJECT': 
                $voteService = new VoteNotificationService();
                $response = $voteService->getFormatedWebNotification($notification, $users, $notification->getMessageType());
                break;
            case 'SP_MEDIA_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_MEDIA_COMMENT_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                                 ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                                 ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                                 ->getWebNotification($notification, $users);
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubAlbumMediaComment($notification, $users);
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getClubAlbumMediaComment($notification, $users);
                break;
            case 'STORE_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStoreAlbumMediaComment($notification, $users);
                break;
            case 'STORE_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getStoreAlbumMediaComment($notification, $users);
                break;
            case 'USER_ALBUM_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getUserAlbumMediaComment($notification, $users);
                break;
            case 'USER_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getUserAlbumMediaComment($notification, $users);
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getDashboardPostMediaComment($notification, $users);
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getDashboardPostMediaComment($notification, $users);
                break;
            case 'STORE_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStorePostMediaComment($notification, $users);
                break;
            case 'STORE_POST_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getStorePostMediaComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStorePostMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStorePostMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getStorePostMediaComment($notification, $users);
                break;
            case 'STORE_POST_MEDIA_COMMENT_RATE':
                $response = $this->mediaNotification->_getStorePostMediaCommentRate($notification, $users);
                break;
            case 'USER_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getDashboardPostMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getDashboardPostMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getDashboardPostMediaComment($notification, $users);
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT_RATE':
                $response = $this->mediaNotification->_getDashboardPostMediaCommentRate($notification, $users);
                break;
            case 'STORE_POST_MEDIA_RATE':
                $response = $this->mediaNotification->_getStorePostMediaRate($notification, $users);
                break;
            case 'CLUB_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubPostMediaComment($notification, $users);
                break;
            case 'CLUB_POST_MEDIA_COMMENT_ON_COMMENTED':
                $response = $this->mediaNotification->_getClubPostMediaComment($notification, $users);
                break;
            case 'USER_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubPostMediaComment($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubPostMediaComment($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $response = $this->mediaNotification->_getClubPostMediaComment($notification, $users);
                break;
            case 'CLUB_POST_MEDIA_COMMENT_RATE': 
                $response = $this->mediaNotification->_getClubPostMediaCommentRate($notification, $users);
                break;
            case 'TAGGED_IN_CLUB_POST':
                $response = $this->mediaNotification->_getTaggedInClubPost($notification, $users);
                break;
            case 'TAGGED_IN_STORE_PHOTO':
                $response = $this->mediaNotification->_getTaggedInShopAlbumImage($notification, $users);
                break;
            case 'SP_MEDIA_COMMENTED':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_COMMENT_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'POST_AT_SOCIAL_PROJECT_WALL':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_COMMENTED':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_POST_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_POST_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_POST_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_POST':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_POST':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_POST':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_USER':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_CLUB':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_SHOP':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'POST_COMMENT_SP_POST_TAGGED_USER':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'POST_COMMENT_SP_POST_TAGGED_CLUB':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'POST_COMMENT_SP_POST_TAGGED_SHOP':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_POST_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_POST_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_POST_MEDIA':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_MEDIA_COMMENTED':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'USER_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SHOP_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'CLUB_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_USER':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_SHOP':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_CLUB':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_MEDIA_COMMENT_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
            case 'SP_POST_MEDIA_RATE':
                $response = $this->container->get('post_feeds.notificationFeeds')
                        ->getWebNotification($notification, $users);
                break;
           /* case 'BUY_ECOMMERCE_PRODUCT':
                $response = $this->_getBuyEcommerceProductNotifications($notification, $users);
                break;*/
        }
       return $response;
   }
   
   private function _getFriendNotification($notification, $users){
       $response = array();
       try{
            $from = $notification->getFrom();
             $status = $notification->getMessageStatus();
             $response = array(
                         'notification_id'=>$notification->getId(),
                         'notification_from'=>isset($users[$from]) ? $users[$from] : array(),
                         'message_type' =>$notification->getMessageType(),
                         'message_status' => !empty($status) ? $status : 'U',
                         'message'=>$notification->getMessage(),
                         'is_read'=>(int)$notification->getIsRead(),
                         'create_date'=>$notification->getDate()
                     );
       }catch(\Exception $e){
           
       }
        return $response;
   }
   
   private function _getGroupJoinResponseNotification($notification, $users){
       $dm = $this->get('doctrine.odm.mongodb.document_manager');
       $response = array();
       try{
            $clubId = $notification->getItemId();
            $from = $notification->getFrom();
            $notification_id = $notification->getId();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $status = $notification->getMessageStatus();
            $club = $dm
                    ->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id'=>$clubId));

            //get club info
            if($club){
               $title = $club->getTitle();
               $group_info = array('id' => $clubId, 'name' => $title, 'status'=>$club->getGroupStatus());
               $response = array(
                        'notification_id' => $notification_id,
                        'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                        'message_type' => $message_type,
                        'message_status' =>!empty($status) ? $status : 'U',
                        'message' => $message,
                        'group_info' => $group_info,
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                       );
            }
       }catch(\Exception $e){
//           echo $e->getMessage();
       }
       return $response;
   }
   
   private function _getBrokerNotifications($notification, $users){
       $response = array();
       try{
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $status = $notification->getMessageStatus();
            $response = array(
                        'notification_id'=>$notification_id,
                        'notification_from'=>isset($users[$from]) ? $users[$from] : array(),
                        'message_type' =>$message_type,
                        'message_status' =>!empty($status) ? $status : 'U',
                        'message'=>$message,
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                    );
        }catch(\Exception $e){
//           echo $e->getMessage();
       }
       return $response;
   }
   
   private function _getShopNotification($notification, $users){
        $response = array();
        try{
            $item_id = $notification->getItemId();
            $em = $this->getDoctrine()->getManager();
                //get all shop object
            $shop = $em
                   ->getRepository('StoreManagerStoreBundle:Store')
                   ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $status = $notification->getMessageStatus();
                $store_id = $shop->getId();
                $store_name = $shop->getName();
                $store_bus_name = $shop->getBusinessName();
                $store_array = array('id'=>$store_id, 'name'=>$store_name, 'business_name'=>$store_bus_name);
                $response = array(
                            'notification_id'=>$notification_id,
                            'notification_from'=>isset($users[$from]) ? $users[$from] : array(),
                            'message_type' =>$message_type,
                            'message_status' =>!empty($status) ? $status : 'U',
                            'message'=>$message,
                            'shop_info'=>$store_array,
                            'is_read'=>(int)$notification->getIsRead(),
                            'create_date'=>$notification->getDate()
                        );
            }

        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
   private function _getShopApprovalNotifications($notification, $users){
       $response = array();
        try{
            $item_id = $notification->getItemId();
            $em = $this->getDoctrine()->getManager();
                //get all shop object
            $shop = $em
                   ->getRepository('StoreManagerStoreBundle:Store')
                   ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                    $notification_id = $notification->getId();
                    $from = $notification->getFrom();
                    $message_type = $notification->getMessageType();
                    $message = $notification->getMessage();
                    $status = $notification->getMessageStatus();
                    $store_id = $shop->getId();
                    $store_name = $shop->getName();
                    $store_bus_name = $shop->getBusinessName();
                    $store_array = array('id'=>$store_id, 'name'=>$store_name, 'business_name'=>$store_bus_name);
                    $response = array(
                                'notification_id'=>$notification_id,
                                'notification_from'=>isset($users[$from]) ? $users[$from] : array(),
                                'message_type' =>$message_type,
                                'message_status' =>!empty($status) ? $status : 'U',
                                'message'=>$message,
                                'shop_info'=>$store_array,
                                'is_read'=>(int)$notification->getIsRead(),
                                'create_date'=>$notification->getDate()
                            );
            }

        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
    private function _getShopActiveInactiveNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
            $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $vat            = $this->container->getParameter('vat');
                $reg_fee        = $this->container->getParameter('reg_fee');

                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $status = $notification->getMessageStatus();

                $total_pending_amount = 0;
                $reg_vat_amount = 0;
                $transaction_pending_amount = 0;
                $shopAsArray = $user_service->getShopDataObjectToArray($shop);
                
                if($message == 'paymentpending' || $message == 'card_not_found_recurring') {
                    //get entries from transaction shop
                    $store_pending_amount = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getShopPendingAmount($item_id); 
                    if($store_pending_amount) {
                        $transaction_pending_amount_check = $store_pending_amount/1000000;
                        if($transaction_pending_amount_check>5) {
                             $transaction_pending_amount = $transaction_pending_amount_check;
                        }    
                    }
                    $payment_status = $shopAsArray['paymentStatus'];
                    if($payment_status == 0) {
                        $reg_vat_amount = ($reg_fee + (($reg_fee*$vat)/100))/100;
                    }
                    $total_pending_amount = $reg_vat_amount + $transaction_pending_amount;
                    $total_pending_amount = sprintf("%01.2f", $total_pending_amount);
                    
                    
                    $response = array(
                        'notification_id' => $notification_id,
                        'message_status'=>!empty($status) ? $status : 'U',
                        'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                        'message_type' => $message_type,
                        'message' => $message,
                        'shop_info' => $shopAsArray,
                        'amount'=>$total_pending_amount,
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                    );
                }else {
                    $response = array(
                                'notification_id' => $notification_id, 
                                'message_status'=>!empty($status) ? $status : 'U',
                                'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                                'message_type' => $message_type, 
                                'message' => $message,
                                'shop_info' => $shopAsArray,
                                'is_read'=>(int)$notification->getIsRead(),
                                'create_date'=>$notification->getDate()
                            );
                }

            }

        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getShopRecurringPaymentStatusNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
            $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $vat            = $this->container->getParameter('vat');
                $reg_fee        = $this->container->getParameter('reg_fee');
                $user_service = $this->get('user_object.service');

                //getting the user ids.
                $all_amount_arr = array();
                $store_item_map = array();

                $item_id = $notification->getItemId();
                $from = $notification->getFrom();
                $all_reccuring_records = $em
                            ->getRepository('TransactionTransactionBundle:RecurringPayment')
                            ->getAllRecurringRecords(array($item_id));

                if($all_reccuring_records) {
                    foreach($all_reccuring_records as $items_record) {
                        $temp_amount = 0;
                        $store_item_map[$items_record->getId()] = $items_record->getShopId();
                        $temp_amount = $items_record->getAmount();
                        $all_amount_arr[$items_record->getId()] = $temp_amount/1000000; 
                    }

                }

                $recurring_shops_objects = $user_service->getShopDataObjectToArray($shop);
                $notification_id = $notification->getId();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $status = $notification->getMessageStatus();

                $amount_euro = 0;
                $amount_euro = isset($all_amount_arr[$item_id]) ? $all_amount_arr[$item_id] : array(); 
                $store_object = array(); 
                if(isset($all_amount_arr[$item_id])) {
                    $store_object = (isset($store_item_map[$item_id]) and !empty($recurring_shops_objects)) ? $recurring_shops_objects : array();
                    $response = array(
                                'notification_id' => $notification_id,
                                'message_status'=>!empty($status) ? $status : 'U',
                                'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                                'message_type' => $message_type,
                                'message' => $message, 
                                'shop_info' => $store_object,
                                'amount'=>$amount_euro,
                                'is_read'=>(int)$notification->getIsRead(),
                                'create_date'=>$notification->getDate()
                            );
                }
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getDiscountPositionNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
            $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $dp_user_id = $notification->getFrom();
                $dp_item_id = $notification->getItemId();
                $store_details = $em
                            ->getRepository('StoreManagerStoreBundle:Storeoffers')
                            ->getStoreIdFromItemId(array($dp_item_id));
                $store_item_map  = $store_dps =array();
                // loop for getting the discount position and store id
                foreach($store_details as $store_offer){
                     $store_dps[$store_offer->getShopId()] = $store_offer->getDiscountPosition();
                     $store_item_map[$store_offer->getId()] = $store_offer->getShopId();
                }

                $dp_shop_objects = $user_service->getShopDataObjectToArray($shop);

                //loop for making the final responce
                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $status = $notification->getMessageStatus();
                if(isset($store_item_map[$item_id])) {
                    $store_info = $dp_shop_objects;
                    $store_dp =   isset($store_dps[$store_item_map[$item_id]]) ? $store_dps[$store_item_map[$item_id]] : 0;
                    $discount_amount = $store_dp/1000000;
                    $response = array(
                        'notification_id' => $notification_id,
                        'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                        'message_type' => $message_type,
                        'message' => $message,
                        'shop_info' => $store_info,
                        'discount_amount' => $discount_amount,
                        'message_status'=>!empty($status) ? $status : 'U',
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                    );
                }
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getShotsNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
            $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $shot_user_id = $notification->getFrom();
                $shot_item_id = $notification->getItemId();
                $store_details = $em
                            ->getRepository('StoreManagerStoreBundle:Storeoffers')
                            ->getStoreIdFromItemId(array($shot_item_id));


                $store_shot = $store_item_map = array();
                // loop for getting the discount position and store id
                foreach($store_details as $store_offer){
                     $store_shot[$store_offer->getShopId()] = $store_offer->getShots();
                     $store_item_map[$store_offer->getId()] = $store_offer->getShopId();
                }

                $shot_shop_objects = $user_service->getShopDataObjectToArray($shop);

                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $status = $notification->getMessageStatus();
                if(isset($store_item_map[$item_id])){
                    $store_info = $shot_shop_objects;
                    $shot_amount = isset($store_shot[$store_item_map[$item_id]]) ? $store_shot[$store_item_map[$item_id]] : 0;
                    $shot_amount = $shot_amount/1000000;
                    $response = array(
                        'notification_id' => $notification_id,
                        'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                        'message_type' => $message_type,
                        'message' => $message, 
                        'shop_info' => $store_info,
                        'shot_amount' => $shot_amount,
                        'message_status' => !empty($status) ? $status : 'U',
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate()
                    );
                }
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getShopDPNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
            $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $status = $notification->getMessageStatus();

                $store_detail = $user_service->getShopDataObjectToArray($shop);
                //check if store exist
                $store_array = $store_detail;
                $shot_amount = $this->container->getParameter('shop_discount_position_amount');
                $response = array(
                            'notification_id' => $notification_id,
                            'notification_from' => isset($users[$from]) ? $users[$from] : array(),
                            'message_type' => $message_type,
                            'message' => $message,
                            'shop_info' => $store_array,
                            'discount_amount' => $shot_amount,
                            'message_status' =>  !empty($status) ? $status : 'U',
                            'is_read'=>(int)$notification->getIsRead(),
                            'create_date'=>$notification->getDate()
                        );
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getBillingCircleNotification($notification, $users) {
        $response = array();
        try{
            // get entity manager object
            $em = $this->getDoctrine()->getManager();
            $user_service = $this->get('user_object.service');
            
            $item_id = $notification->getItemId();
             $shop = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id'=>$item_id));
            
            if($shop){
         
                $billing_user_id = $notification->getFrom();
                $billing_item_id = $notification->getItemId();
                $billing_shop_objects = $user_service->getShopDataObjectToArray($shop);

                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $message_status = $notification->getMessageStatus();
                //get store detail
                $store_array = $billing_shop_objects;
                $response = array(
                            'notification_id'=>$notification_id,
                            'notification_from'=>isset($users[$from]) ? $users[$from] : array(),
                            'message_type' =>$message_type,
                            'message'=>$message,
                            'message_status'=>$message_status,
                            'shop_info'=>$store_array,
                            'is_read'=>(int)$notification->getIsRead(),
                            'create_date'=>$notification->getDate()
                        );
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getShopTransactionNotification($notification, $users) { 
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = isset($info['store_info']) ? $info['store_info'] : array();
            unset($info['store_info']);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'transaction_info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getRecurringPaymentNotifications($recurring_notification, $users)
   {
        $response = array();
        try{
            $user_service = $this->get('user_object.service');
            $notification_id = $recurring_notification->getId();
            $from = $recurring_notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $recurring_notification->getMessageType();
            $message = $recurring_notification->getMessage();
            $item_id = $recurring_notification->getItemId();
            $message_status = $recurring_notification->getMessageStatus();

            $store_detail = $user_service->getStoreObjectService($item_id);
            $info = $recurring_notification->getInfo();
            //check if store exist
            if (count($store_detail) > 0) {
                $store_array = $store_detail;
                $response = array(
                    'notification_id' => $notification_id, 
                    'notification_from' => $user_info, 
                    'message_type' => $message_type, 
                    'message' => $message, 
                    'shop_info' => $store_array,  
                    'message_status' => $message_status,
                    'is_read'=>(int)$recurring_notification->getIsRead(),
                    'create_date'=>$recurring_notification->getDate(),
                    'info'=>$info
                  );
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
   private function _getTaggedInShopTransactionNotification($notification, $users) {
       $response = array();
        try{

            $user_service = $this->get('user_object.service');
            $message = $notification->getMessage();
            if($message=='tagging'){
                $notification_id = $notification->getId();
                $from = $notification->getFrom();
                $user_info = isset($users[$from]) ? $users[$from] : array();

                $message_type = $notification->getMessageType();
                $message = $notification->getMessage();
                $item_id = $notification->getItemId();
                $info = $notification->getInfo();
                $store_info = isset($info['store_id']) ? $user_service->getStoreObjectService($info['store_id']) : array();
                $is_read = (int)$notification->getIsRead();
                $create_date = $notification->getDate();
                $response = array('notification_id'=>$notification_id, 'notification_from'=>$user_info, 'message_type' =>$message_type,'message_status' => 'T','message'=>$message, 'transaction_info'=>$info,'is_read' => $is_read, 'create_date' => $create_date,'shop_info' => $store_info);             
            }
         
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getCampaignShoppingCardNotification($notification, $users) { 
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = array('store_id'=>$item_id);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getBuysShoppingCardNotification($notification, $users) { 
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = isset($info['store_info']) ? $info['store_info'] : array();
            unset($info['store_info']);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getSellsShoppingCardNotification($notification, $users) { 
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = isset($info['store_info']) ? $info['store_info'] : array();
            unset($info['store_info']);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getSubscriptionShoppingCardNotification($notification, $users) { 
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = array('store_id'=>$item_id);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
    private function _getReferralAmountShopAffiliationNotification($notification, $users){
        $response = array();
        try{
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $user_service = $this->container->get('user_object.service');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $message_status = $notification->getMessageStatus();
            $info = $notification->getInfo();
            $store_info = $user_service->getStoreObjectService($item_id);
            $response = array(
                'notification_id' => $notification_id,
                'notification_from' => $user_info,
                'message_type' => $message_type,
                'message_status'=>$message_status ,                
                'message' => $message, 
                'info' => $info,
                'is_read'=>(int)$notification->getIsRead(),
                'create_date' => $notification->getDate()
            );
            if(!empty($store_info)){
                $response['shop_info']=$store_info;
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
    }
    
   private function _getRecurringSubscriptionPaymentNotifications($subscription_recurring_notification, $users)
   {
        $response = array();
        try{
            $user_service = $this->get('user_object.service');
            $notification_id = $subscription_recurring_notification->getId();
            $from = $subscription_recurring_notification->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $subscription_recurring_notification->getMessageType();
            $message = $subscription_recurring_notification->getMessage();
            $item_id = $subscription_recurring_notification->getItemId();
            $message_status = $subscription_recurring_notification->getMessageStatus();

            $store_detail = $user_service->getStoreObjectService($item_id);
            $info = $subscription_recurring_notification->getInfo();
            //check if store exist
            if (count($store_detail) > 0) {
                $store_array = $store_detail;
                $response = array(
                    'notification_id' => $notification_id, 
                    'notification_from' => $user_info, 
                    'message_type' => $message_type, 
                    'message' => $message, 
                    'shop_info' => $store_array,  
                    'message_status' => $message_status,
                    'is_read'=>(int)$subscription_recurring_notification->getIsRead(),
                    'create_date'=>$subscription_recurring_notification->getDate(),
                    'info'=>$info
                  );
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
   /**
    * List Ecommerce Product Notifications
    * @param array $notifications
    * @param array $users
    */
   private function _getBuyEcommerceProductNotifications($notifications, $users)
   {
        $response = array();
        try{
            $user_service = $this->get('user_object.service');
            $notification_id = $notifications->getId();
            $from = $notifications->getFrom();
            $user_info = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notifications->getMessageType();
            $message = $notifications->getMessage();
            $item_id = $notifications->getItemId();
            $message_status = $notifications->getMessageStatus();
            //get shop detail
            $store_detail = $user_service->getStoreObjectService($item_id);
            $info = $notifications->getInfo();
            //check if store exist
            if (count($store_detail) > 0) {
                $store_array = $store_detail;
                $response = array(
                    'notification_id' => $notification_id, 
                    'notification_from' => $user_info, 
                    'message_type' => $message_type, 
                    'message' => $message, 
                    'shop_info' => $store_array,  
                    'message_status' => $message_status,
                    'is_read'=>(int)$notifications->getIsRead(),
                    'create_date'=>$notifications->getDate(),
                    'info'=>$info
                  );
            }
        }catch(\Exception $e){
//           echo $e->getMessage();
        }
        return $response;
   }
   
}


