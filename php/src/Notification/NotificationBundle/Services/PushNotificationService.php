<?php
namespace Notification\NotificationBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
//Making the Andriod config file. 
use Notification\NotificationBundle\DependencyInjection\AndroidConfig;
use Notification\NotificationBundle\DependencyInjection\IphoneConfig;
use Notification\NotificationBundle\Model\INotification;
use Notification\NotificationBundle\Document\PushNotification;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Notification\NotificationBundle\Document\UserNotifications;
//use Utility\UniversalNotificationsBundle\Services\ApnsPushService;
//use Utility\UniversalNotificationsBundle\Services\ApnsPushLogService;
use Utility\UniversalNotificationsBundle\Services\NodeJsPushService;
use Utility\UniversalNotificationsBundle\Services\PushLogService;

// service method  class
class PushNotificationService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    protected $iphone_success_msg = 'message sent';
    protected $iphone_failure_msg = 'message not sent';
    protected $iphonePEM=null;
    //define the required params

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
    }
    
    /**
     * send notification by role
     * @param type $from_id
     * @param type $to_id
     * @param type $device_token
     * @param type $msg_code
     * @param type $ref_type
     * @param type $ref_id
     * @param type $notification_role
     * @param string $client_type, Default citizen
     * @return boolean
     */
    public function sendNotificationByRole($from_id,$to_id,$device_token,$msg_code, $msg, $ref_type,$ref_id,$notification_role, $client_type = "CITIZEN",$info = array(), $extraParams=array()) {
        
        switch ($notification_role) {
            case 1:
                /** send web notification **/
                $this->saveUserNotification($from_id, $to_id, $ref_id,$ref_type, $msg_code,$notification_role,$info);
                break;
            case 4:
                /** send push notification **/
//                 if(isset($device_token['I'])){
//                $iphone_device_token = $device_token['I'];
//                //$this->sendPushNotificationIphone($iphone_device_token, $from_id, $msg_code, $msg, $ref_type , $ref_id,$client_type);
//                }
//                if(isset($device_token['A'])){
//                $android_device_token = $device_token['A'];
//                //$this->sendPushNotificationAndriod($android_device_token, $from_id, $msg_code, $msg, $ref_type, $ref_id);
//                }
                $this->sendPush($device_token, $from_id, $msg_code, $msg, $ref_type , $ref_id, $client_type, $extraParams);
                break;
            case 5:
                /** send web and push notification **/
                $this->saveUserNotification($from_id, $to_id, $ref_id,$ref_type, $msg_code,$notification_role,$info);
//                if(isset($device_token['I'])){
//                $iphone_device_token = $device_token['I'];
//                //$this->sendPushNotificationIphone($iphone_device_token, $from_id, $msg_code, $msg, $ref_type , $ref_id, $client_type);
//                }
//                if(isset($device_token['A'])){
//                $android_device_token = $device_token['A'];
//               // $this->sendPushNotificationAndriod($android_device_token, $from_id, $msg_code, $msg, $ref_type, $ref_id);
//                }
              
                $this->sendPush($device_token, $from_id, $msg_code, $msg, $ref_type , $ref_id, $client_type, $extraParams);
                break;
        }
        
        return true;
    }
    
    /**
     * 
     * @param type $device_id
     * @param type $msg
     */
    public function sendPushNotificationAndriod($user_device_token, $from_id, $msg_code, $message , $message_type, $item_id, $extraParams=array())
    {
        $response = '';
    	$msg = array();
        /** get android push notification class object **/
        
    	$andriod_config = new AndroidConfig;
//    	$msg['aps'] = array('alert'=>$message,
//                            'badge'=>$andriod_config->badge,
//                            'sound'=>$andriod_config->sound,
//                            'type'=>$message_type,
//                            'id'  =>$item_id
//                        );
        
        // Create the payload body
    	$body['aps'] = array(
    			'alert' =>$message,
    			'badge'=>$andriod_config->badge,
                        'sound'=>$andriod_config->sound,
    	);
    	$body['msg_code']= $msg_code;
        $body['ref_type']= $message_type;   			 					   
        $body['ref_id']= $item_id;
        $extraParams = is_array($extraParams) ? $extraParams : array();
        $body= $body+$extraParams;
        
        $registration_id = array();
        foreach($user_device_token as $record) {
            $registration_id[] = $record['device_token'];
        }
        
    	$response = $this->sendAndroidNotification($registration_id, $body, $andriod_config);   
        
    	return $response;
    }
    
    /**
     * Send push notification for Andriod device
     * @param string $registrationIds
     * @param array $msg
     * @param object $config
     * @return int $response
     */
    private function sendAndroidNotification($registrationIds, $msg, $config)
    {
    	$headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . $config->androidApiKey);
    	$data = array(
    			'registration_ids' =>$registrationIds,
    			'data' => array('message'=>$msg)
    	);
    	$ch = curl_init();
    	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt( $ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send" );
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // make SSL checking false
    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
    	$response = curl_exec($ch);
    	curl_close($ch);
    	$result_response = json_decode($response);

        if(is_array($result_response->results))
        {   
            $i = 0;
            $UnRegisteredIds = array();
            foreach($result_response->results as $result)
            {
                if(isset($result->error) && ($result->error == 'NotRegistered' || $result->error == 'InvalidRegistration'))
                {
                    $UnRegisteredIds[] = $registrationIds[$i];
                }
                $i++;
            }
            
            if(count($UnRegisteredIds)){ 
                $em = $this->em;
                $results =  $em->getRepository('NManagerNotificationBundle:UserDevice')
                      ->deleteDeviceToken($UnRegisteredIds);
            }
        }
        
        $logger = new ApnsPushLogService();
    	if ($result_response->success == 1 && $result_response->failure == 0)
        {
//            $log_file = __DIR__ . "/../Resources/log/notification.log";
//            $time = date('m-d-Y H:i:s');
//            $notification_msg  = $time." Android: ".json_encode($result_response)."Device Token:".json_encode($registrationIds)."\n";
//            $notification_msg .= " "."\n";  
//            error_log($notification_msg, 3, $log_file); 
            $eMessage = "Android: ".json_encode($result_response)."Device Token:".json_encode($registrationIds);
            $logger->log($eMessage);
            return 1;
        } else {
//            $log_file = __DIR__ . "/../Resources/log/notification.log";
//            $time = date('m-d-Y H:i:s');
//            $notification_msg  = $time." Android: ".json_encode($result_response)."Device Token:".json_encode($registrationIds)."\n";
//            $notification_msg .= " "."\n";  
//            error_log($notification_msg, 3, $log_file); 
            $eMessage = "Android: ".json_encode($result_response)."Device Token:".json_encode($registrationIds);
            $logger->log($eMessage);
            return 0;
        }
        
    	
    }
    
    /**
     * 
     * @param type $device_id
     * @param type $msg
     */

    public function sendPushNotificationIphone($user_device_token, $from_id, $message_code, $messageText, $message_type , $item_id, $client_type, $extraParams=array())
    {
        //get android push notification class object
    	$iphone_config = new IphoneConfig;
    	
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
        
        //check if parameter is defined
        try{
        $server = $this->container->getParameter('iphone_certificate'); //get server
        } catch (\Exception $e){
        $server = 'dev';
        }
       
    	//pem file path for iphone notification.
        //$certificate = $iphone_config->pem_file;
        //check for client
        if($client_type == "SHOP"){
            //check for dev or prod
            if ($server == 'dev') {
                $certificate = $iphone_config->shop_pem_file;
                $gateway_url = $iphone_config->gateway_url;
            } elseif ($server == 'prod') {
                $certificate = $iphone_config->shop_pem_file_prod;
                $gateway_url = $iphone_config->prod_gateway_url;
            }
        }else{
            //for Citizen
            //check for dev or prod
            if ($server == 'dev') {
                $certificate = $iphone_config->pem_file;
                $gateway_url = $iphone_config->gateway_url;
            } elseif ($server == 'prod') {
                $certificate = $iphone_config->pem_file_prod;
                $gateway_url = $iphone_config->prod_gateway_url;
            }
        }
                
    	$pemfile_path = $container->get('kernel')->locateResource('@NManagerNotificationBundle/DependencyInjection/'. $certificate);
        
        try{
            $apnsPush = new ApnsPushService($pemfile_path, $server!='prod' ? true : false);
            // Instanciate a new ApnsPHP_Push object
            $push = $apnsPush->initPush();
            // 1ms = 1000 micro seconds
            $push->setWriteInterval(1000);
            // Connect to the Apple Push Notification Service
            $push->connect();
            $i=1;
            foreach($user_device_token as $device_token_record) {
                    // Instantiate a new Message with a single recipient
                    $message = $apnsPush->createPush($device_token_record['device_token']);

                    // Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
                    // over a ApnsPHP_Message object retrieved with the getErrors() message.
                    $message->setCustomIdentifier(sprintf("Message-%03d", $i));
                    // Set badge icon to "3"
                    $message->setBadge((int)$iphone_config->badge);
                    $message->setSound($iphone_config->sound);
                    $message->setText($messageText);
                    $message->setCustomProperty('msg_code', $message_code);
                    $message->setCustomProperty('ref_type', $message_type);
                    $message->setCustomProperty('ref_id', $item_id);
                    if(!empty($extraParams)){
                        foreach($extraParams as $key=>$value){
                            $message->setCustomProperty($key, $value);
                        }
                    }
                    // Add the message to the message queue
                    $push->add($message);
                    $i++;
                     $push->send();
            }

            // Send all messages in the message queue


            // Disconnect from the Apple Push Notification Service
            $push->disconnect();
            // Examine the error message container
            //$aErrorQueue = $push->getErrors();
        }catch(Symfony\Component\Debug\Exception\FatalErrorException $e){
            
        }  catch (\Exception $e){
            
        }
        return true;
    }
    
    /**
     * Get receiver info
     * @param int $user_id
     * @return int
     */
    public function getReceiverDeviceInfo($user_id, $app_type=null)
    {
        $users_array = array();
        $em = $this->em;
        //check if shop has already transaction on same date
        $results =  $em->getRepository('NManagerNotificationBundle:UserDevice')
                      ->getReceiversDeviceInfo($user_id, $app_type);
        if($results){
          foreach($results as $result){
              $users_array[$result->getDeviceType()][] = array('device_token' => $result->getDeviceId(), 'lang'=>$result->getDeviceLang(), 'user_id'=>$result->getUserId());
          }
        }
        $logger  = new PushLogService();
        $logUserJson = json_encode(is_array($user_id) ? $user_id : (array)$user_id);
        $logger->log('Found device information from database using USER_ID : '. $logUserJson.' AppType : '.$app_type.' Devices : '. json_encode($users_array));
        return $users_array;
    }
    
            
    /*
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($sender_id, $reciever_ids, $item_id, $msgtype, $msg,$notification_type,$info) {
        $dm = $this->dm;
        
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
            $notification->setInfo($info);
            isset($info['message_status'])  ? $notification->setMessageStatus($info['message_status']) : '';
            $dm->persist($notification);
        }        
        $dm->flush();
        return true;
    }
    
    public function sendPush($device_token, $from_id, $message_code, $messageText, $message_type , $item_id, $client_type, $extraParams=array()){
        $push = new NodeJsPushService();
        if(isset($device_token['I'])){
            $push->setDevice($device_token['I'], 'I', $client_type);
        }
        if(isset($device_token['A'])){
            $push->setDevice($device_token['A'], 'A', $client_type);
        }
        
        $body['aps'] = array(
    			'alert' =>$messageText,
    			'badge'=>'0',
                        'sound'=>'default',
    	);
    	$body['msg_code']= $message_code;
        $body['ref_type']= $message_type;   			 					   
        $body['ref_id']= $item_id;
        $extraParams = is_array($extraParams) ? $extraParams : array();
        $body= array_merge($body, $extraParams);
        $push->setData($body)
                ->setMessage($messageText)
                ->send();
    }
    
    /**
     * Get receiver info with user id
     * @param int $user_id
     * @return int
     */
    public function getReceiverDeviceWithUserInfo($user_id, $app_type=null)
    {
        $users_array = array();
        $em = $this->em;
        //check if shop has already transaction on same date
        $results =  $em->getRepository('NManagerNotificationBundle:UserDevice')
                      ->getReceiversDeviceInfo($user_id, $app_type);
        if($results){
          foreach($results as $result){
              $users_array[$result->getUserId()][$result->getDeviceType()][] = array('device_token' => $result->getDeviceId(), 'lang'=>$result->getDeviceLang());
          }
        }
        $logger  = new PushLogService();
        $logUserJson = json_encode(is_array($user_id) ? $user_id : (array)$user_id);
        $logger->log('Found device information from database using USER_ID : '. $logUserJson.' AppType : '.$app_type.' Devices : '. json_encode($users_array));
        return $users_array;
    }
    
    
}
