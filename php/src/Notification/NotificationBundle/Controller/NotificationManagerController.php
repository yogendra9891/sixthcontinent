<?php

namespace Notification\NotificationBundle\Controller;

use Notification\NotificationBundle\Model\IActivityNotification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;
use Symfony\Component\HttpFoundation\Request;
/**
 * Define the manager class for checking the notification type and sending the notification
 * @author admin
 *
 */
class NotificationManagerController extends Controller {
    /*
     * defining the properties.
     */

    private $notification_object;

    const CONTROLLER_CONST = 'Controller';

    /**
     * initializing the object
     * @param object $object_manager_info
     */
    public function __construct($object_manager_info) {
        //set the object
        $this->setNotificationObject($object_manager_info);
    }

    /**
     * Sending the notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return int
     */
    public function sendAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->sendAction($current_notification_object);
        return $return_result;
    }

    /**
     * setting the Notification object
     * @param object $object_manager
     */
    private function setNotificationObject($object_manager) {
        $this->notification_object = $object_manager;
    }

    /**
     * getting the Notification object
     * @param none
     * @return object
     */
    public function getNotificationObject() {
        return $this->notification_object;
    }

    /**
     * get all the notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return object
     */
    public function getNotificationMessagesAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->getNotificationMessagesAction($current_notification_object);
        return $return_result;
    }

    /**
     * delete message all the notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return int
     */
    public function deleteNotificationMessageAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->deleteNotificationMessageAction($current_notification_object);
        return $return_result;
    }

    /**
     * read/unread notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return int
     */
    public function readUnreadNotificationMessageAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->readUnreadNotificationMessageAction($current_notification_object);
        return $return_result;
    }

    /**
     * search notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return object array
     */
    public function searchNotificationMessageAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->searchNotificationMessageAction($current_notification_object);
        return $return_result;
    }

    /**
     * generic function for getting the class name
     * @param none
     * @return string
     */
    public function getDynamicObjectAction() {
        //@TODO: Need to create Notification class object without specifying package prefix explicitly.
        $notificationNameSpace = "Notification\\NotificationBundle\\Controller\\";

        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();

        $class_name = $current_notification_object->notification_type . self::CONTROLLER_CONST;
        $class_name = $notificationNameSpace . $class_name;
        return $class_name;
    }

    /**
     * get detail notifications(Email, PushNotification, Sms).
     * @param object getting from getter method
     * @return object array
     */
    public function getDetailNotificationAction() {
        //checking the notification object type and making the same object.
        $current_notification_object = $this->getNotificationObject();
        $class_name = $this->getDynamicObjectAction();
        $notification_type_object = new $class_name();
        $return_result = $notification_type_object->getDetailNotificationAction($current_notification_object);
        return $return_result;
    }
    
    /**
     * Get approved friend request notification
     * @param \Notification\NotificationBundle\Controller\Request $request
     */
    public function postGetapprovedfriendrequestsAction(Request $request)
    {
        //initilise the array
        $data = array();

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
       return;
    }

}
