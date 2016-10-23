<?php

namespace Notification\NotificationBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Notification\NotificationBundle\Model\INotification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;
use Notification\NotificationBundle\NManagerNotificationBundle;
//Making the Andriod config file. 
use Notification\NotificationBundle\DependencyInjection\AndroidConfig;
use Notification\NotificationBundle\DependencyInjection\IphoneConfig;

use Notification\NotificationBundle\Document\PushNotification;
/**
 * Define the PushNotification class sending the notification
 * @author admin
 *
 */
class PushNotificationController extends Controller implements INotification
{
	/**
	 * sending the notification.
	 * @param object
	 * @see Notification\NotificationBundle\Model.INotification::sendAction()
	 * @return int
	 */
    public function sendAction($message_info_object)
    { 
    	//get android push notification class object
    	$andriod_config = new AndroidConfig;
    	
    	try {
    		if ($message_info_object->push_type == 'android') {
    			$result = $this->androidNotification($message_info_object, $andriod_config);
    		} else if ($message_info_object->push_type == 'iphone') {
    			$result = $this->iphoneNotification($message_info_object);
    		}	
    	} catch (\Exception $e) {
    		return $e->getMessage();
    	}
    	$this->setPushNotificationAction($message_info_object);
		return $result;
    }
    
    /**
     * Create message array and send android push notification
     * @param object $device_token
     * @param object $config
     * @return int
     */
    public function androidNotification($message_info_object, $config)
    {
    	$response = '';
    	$msg = array();
    	$msg['aps'] = array('alert'=>$message_info_object->message,
    			'badge'=>$config->badge,
    			'sound'=>$config->sound,
    			'type'=>$message_info_object->message_type,
    			'id'  =>$message_info_object->msg_id
				);
    
    	$registration_id = $message_info_object->device_token;
    	$response = $this->sendAndroidNotification($registration_id, $msg, $config);
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
    			'registration_ids' =>array($registrationIds),
    			'data' => array('message'=>$msg)
    	);

    	$ch = curl_init();
    	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt( $ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send" );
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );
    	$response = curl_exec($ch);
    	curl_close($ch);
    	$result_response = json_decode($response);
    	if ($result_response->success == 1 && $result_response->failure == 0)
    		return 1;
    	return 0;
    }
    
    /**
	 * defining the iphone notification method, will send the notification.
	 * @param object $message_info_object
	 * @return int
     */
    public function iphoneNotification($message_info_object)
    {
    	//get android push notification class object
    	$iphone_config = new IphoneConfig;
    	
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();

    	//pem file path for iphone notification.
    	$pemfile_path = $container->get('kernel')->locateResource('@NManagerNotificationBundle/DependencyInjection/'. $iphone_config->pem_file);

    	//create notification array
    	$notification = array();
    	//get certificate object
    	$ctx = stream_context_create();
    	stream_context_set_option($ctx, 'ssl', 'local_cert', $pemfile_path);

    	// Open a connection to the APNS server
 		//getting gateway from config file may be sendbox/production defined in config file.
    	$fp = stream_socket_client(
    			$iphone_config->gateway_url, $err,
    			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
    	
    	if (!$fp) {
    		return "failed to connect";
    		exit;
    	}
    	
    	// Create the payload body
    	$body['aps'] = array(
    			'alert' =>$message_info_object->message,
    			'sound' =>$iphone_config->sound,
    			'badge' =>$iphone_config->badge,
    			'type'  =>$message_info_object->message_type,
    			'id'    =>$message_info_object->msg_id
    	);
    	
    	// Encode the payload as JSON
    	$payload = json_encode($body);
    	$deviceToken = $message_info_object->device_token;
    	
    	// Build the binary notification
    	$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
    	
    	// Send it to the server
    	$result = fwrite($fp, $msg, strlen($msg));

    	//check for result
    	//0: not sent 1: sent
    	if (!$result)
    		$msg_deliver = 0;
    	else
    		$msg_deliver = 1;
    	
    	// Close the connection to the server
    	fclose($fp);
    	return $msg_deliver;    	
    }
    
    /**
     * Saving the push notification into mongo db.
     * @param object $message_info_object
     * @return boolean
     */
    public function setPushNotificationAction($message_info_object)
    {
        $time = new \DateTime("now");
    	//get email notification document object
    	$push_notification = new PushNotification();
    	$push_notification->setMessageType($message_info_object->message_type);
    	$push_notification->setMessage($message_info_object->message);
    	$push_notification->setSubject($message_info_object->mail_subject);
    	$push_notification->setSenderUserid($message_info_object->sender_userid);
    	$push_notification->setReceiverUserid($message_info_object->receiver_userid);
    	$push_notification->setDate($time);
    	$push_notification->setReadvalue(0); //default is unread, (0=>unread, 1=>read)
    	$push_notification->setDeletevalue(0); //default is undelete
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
    	$dm = $container->get('doctrine.odm.mongodb.document_manager');
    	$dm->persist($push_notification);
    	$dm->flush();
    	return true;
    }
    
    /**
     * getting all the messages for a user
     * @param object $current_notification_object(notification_type, receiver_userid)
     * @return object
     */
    public function getNotificationMessagesAction($current_notification_object)
    {
    	// set default limit and range 20, 0.
    	$limit  = (isset($current_notification_object->limit_size))  ? $current_notification_object->limit_size : 20;
    	$offset = (isset($current_notification_object->limit_start)) ? $current_notification_object->limit_start : 0;
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
    	$dm = $container->get('doctrine.odm.mongodb.document_manager')->getRepository('NManagerNotificationBundle:PushNotification');
    	//getting the data of receiver user id and deletevalue field orderby date desc and limit and start.
    	$messages = $dm->findBy(array('receiver_userid' => (int)$current_notification_object->receiver_userid, 'deletevalue' => 0),
    			            array('date' => 'DESC'), $limit, $offset);
    	$messages_count = count($dm->findBy(array('receiver_userid' => (int)$current_notification_object->receiver_userid, 'deletevalue' => 0)));

    	$final_array = array('messages'=>$messages, 'count'=>$messages_count);
        return $final_array;
    }
    
    /**
     * deleting a push message marking it 1.
     * @param object $current_notification_object
     * @return int
     */
    public function deleteNotificationMessageAction($current_notification_object)
    {
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
    	$dm = $container->get('doctrine.odm.mongodb.document_manager');
    	$data = $dm->getRepository('NManagerNotificationBundle:PushNotification')->find($current_notification_object->message_id);
    	if (!$data) { // in case record is not exists.
            return 0;
    	}
    	$data->setDeletevalue(1);
    	$dm->flush();
    	return 1;
    }
    
    /**
     * mark read/unread a email message marking it 1(digital delete).
     * @param object $current_notification_object
     * @return int
     */
    public function readUnreadNotificationMessageAction($current_notification_object)
    {
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
    	$dm = $container->get('doctrine.odm.mongodb.document_manager');
    	$data = $dm->getRepository('NManagerNotificationBundle:PushNotification')->find($current_notification_object->message_id);
    	if (!$data) {  // in case record is not exists.
    		return 0;
    	}
    	$data->setReadvalue($current_notification_object->read_value);
    	$dm->flush();
    	return 1;
    }
    
    /**
     * searching email notification messages.
     * @param object $current_notification_object(text for search)
     * @return array
     */
    public function searchNotificationMessageAction($current_notification_object)
    {
    	// set default limit and range 20, 0.
    	$limit    = (isset($current_notification_object->limit_size))  ? $current_notification_object->limit_size : 20;
    	$offset   = (isset($current_notification_object->limit_start)) ? $current_notification_object->limit_start : 0;
    	$text     = $current_notification_object->search_text;
    	$user_id  = (int)$current_notification_object->receiver_userid;
    	//getting the container object.
    	$container = NManagerNotificationBundle::getContainer();
    	$dm = $container->get('doctrine.odm.mongodb.document_manager');
    	$data = $dm->getRepository('NManagerNotificationBundle:PushNotification')
    	           ->searchBySubjectOrOther($text, $user_id, $offset, $limit);
    	    	//finding the total count
    	$data_count = $dm->getRepository('NManagerNotificationBundle:PushNotification')
    	->searchBySubjectOrOtherCount($text, $user_id);
    	$final_array = array('messages'=>$data, 'count'=>$data_count);
    	return $final_array;
    }
   /**
    * 
    * @param \Notification\NotificationBundle\Controller\Request $request
    */
    public function postTestpushsAction(Request $request) {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $android_device_token_temp = explode(',',$object_info->android_device_token);        
        $iphone_device_token_temp = explode(',',$object_info->iphone_device_token); 
        $device_token['iphone'] = $iphone_device_token_temp;
        $device_token['android'] = $android_device_token_temp;
        
        $from_id = 6252;
        $to_id = array(1,3,4,5,6);
        $item_id = 94848;
        /** Code start for getting the request **/
        $push_object_service = $this->container->get('push_notification.service');

       // $push_object_service->sendPushNotificationAndriod($android_device_token,'TXN_CUST_PENDING','TXN',123);
       // $push_object_service->sendPushNotificationIphone($iphone_device_token,'New Transaction by customer.','TXN',123, 'TXN_CUST_PENDING');

        $push_object_service->sendNotificationByRole($from_id,$to_id,$device_token,'TXN_CUST_PENDING','TXN',$item_id,5);

        echo 'Yes'; exit;
        
    }
}
