<?php

namespace Notification\NotificationBundle\Controller;


use Notification\NotificationBundle\Model\IActivityNotification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Define the manager class for checking the notification type and sending the notification
 * @author admin
 *
 */
class ManagerController extends Controller
{

	/**
	 * calling this from UI, getting the object parameter from Rest API
	 * @param none
	 * @return array.
	 */
	public function postNotificationsAction(Request $request)
	{
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.

		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->sendAction();
		if ($return_result == 1) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result);
		echo json_encode($res_data);
                exit();
	}

	/**
	 * Decoding the json string to object
	 * @param json string $encode_object
	 * @return object $decode_object
	 */
	public function decodeObjectAction($encode_object)
	{
		$serializer = new Serializer(array(), array(
				'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
				'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
		));
		$decode_object = $serializer->decode($encode_object, 'json');
		return $decode_object;
	}
	
	/**
	 * getting the all notification on receiver_userid basis.
	 * @param request object
	 * @return json
	 */
	public function postGetnotificationsAction(Request $request)
	{
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.
                
		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->getNotificationMessagesAction();
		if (is_array($return_result)) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result);
		echo json_encode($res_data);
                exit();
	}
	
	/**
	 * deleting the notification on message_id basis.
	 * @param request object
	 * @param json
	 */
	public function postDeletenotificationsAction(Request $request)
	{
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.
                
		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->deleteNotificationMessageAction();
		if ($return_result) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result);
		echo json_encode($res_data);
                exit();
	}
	
	/**
	 * marking read/unread the notification on message_id basis.
	 * @param request object
	 * @param json
	 */
	public function postReadunreadnotificationsAction(Request $request)
	{
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.
                
		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->readUnreadNotificationMessageAction();
		if ($return_result) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result);
		echo json_encode($res_data);
                exit();
	}
	
	/**
	 * search the notification on subject/body basis.
	 * @param request object
	 * @param json
	 */
	public function postSearchnotificationsAction(Request $request)
	{
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.
                
		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->searchNotificationMessageAction();
		if (is_array($return_result)) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result);
		echo json_encode($res_data);
                exit();
	}

        
	/**
	 * get detail of notification by id.
	 * @param request object
	 * @param json
	 */
	public function postDetailnotificationsAction(Request $request)
	{ 
                //Code start for getting the request
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeObjectAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request

                $object_info = (object)$de_serialize; //convert an array into object.
                
		$notification_manager = new NotificationManagerController($object_info);
		$return_result = $notification_manager->getDetailNotificationAction();
		if ($return_result['success']) { //handling the success case
			$error_code = 101;
			$message = 'Success';
		} else { //handling the failure case
			$error_code = 100;
			$message = 'Failure';
                        $return_result['data'] = array();
		}
		//return the response
		$res_data = array('code'=>$error_code, 'message'=>$message, 'data'=>$return_result['data']);
		echo json_encode($res_data);
                exit();
	}
        
    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
    */
    public function getAppData(Request $request)
    {
	$content = $request->getContent();
        $dataer = (object)$this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data; 
        return $req_obj;
    }
}