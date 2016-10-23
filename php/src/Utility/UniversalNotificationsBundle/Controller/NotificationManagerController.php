<?php

namespace Utility\UniversalNotificationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\UniversalNotificationsBundle\Utils\MessageFactory as Msg;


class NotificationManagerController extends Controller
{
    
    const USER_TYPE_CITIZEN = 'CITIZEN';

    /**
     * function for saving the notification setting for a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function saveUserNotificationsAction(Request $request) {
        
        $this->__createLog('[Entering in NotificationManagerController->saveUserNotificationsAction(Request)]');
        $data = array();
        $required_parameter = array('user_id', 'rule');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [Utility/UniversalNotificationsBundle/Controller/NotificationManagerController] and function [saveUserNotificationsAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }

        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $this->__createLog('Request Data in [Utility/UniversalNotificationsBundle/Controller/NotificationManagerController] and function [saveUserNotificationsAction] is: ' . json_encode($de_serialize));
        $user_id = (int)$de_serialize['user_id'];
        $rules = (array)$de_serialize['rule'];
        $final_data = $this->prepareAddedData($user_id,$rules);
        
        $em = $this->getDoctrine()->getManager();
        $result = $em
                    ->getRepository('UtilityUniversalNotificationsBundle:NotifictionManager')
                    ->updateUserNotificationSetting($user_id, $final_data, $rules);
        
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
        $this->__createLog('Exiting from [NotificationManagerController->saveUserNotificationsAction(Request)] with response'.(string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * function for preapring the data for update insert
     * @param type $user_id
     * @param type $rules
     * @return string
     */
    private function prepareAddedData($user_id,$rules) {
        
        $final_array = array();
        foreach ($rules as $key => $rule) {
            $key = Utility::getUpperCaseString(Utility::getTrimmedString($key));
            $decimal_value = Utility::convertNotificationSettingToDecimal($rule);
            $final_array[] = "(".implode(",",array($user_id,"'".self::USER_TYPE_CITIZEN."'","'".$key."'","@value := ".$decimal_value)).")";           
        }
        return implode(",",$final_array);
        
    }
    
    /**
     * function for saving the notification setting for a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getUserNotificationSettingsAction(Request $request) {
        
        $this->__createLog('[Entering in NotificationManagerController->getUserNotificationSettingsAction(Request)]');
        $data = array();
        $required_parameter = array('user_id');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [Utility/UniversalNotificationsBundle/Controller/NotificationManagerController] and function [saveUserNotificationsAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //deseralize the request data
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $this->__createLog('Request Data in [Utility/UniversalNotificationsBundle/Controller/NotificationManagerController] and function [getUserNotificationSettingsAction] is: ' . json_encode($de_serialize));
        $user_id = (int)$de_serialize['user_id'];
        $em = $this->getDoctrine()->getManager();
        //get the notification setting form the DB for the user
        $notification_settings = $em
                    ->getRepository('UtilityUniversalNotificationsBundle:NotifictionManager')
                    ->findBy(array('userId' => $user_id));
        
        //preparing the data for the notification setting list
        $final_data = array();
        $final_data['user_id'] = $user_id;
        $final_data['rule'] = array();
        $setting_info = NULL;
        //prepare data for the notification setting
        foreach($notification_settings as $setting) {
            $setting_info[$setting->getNotificationType()] = Utility::convertNotificationSettingToBinary($setting->getNotificationSetting());
        }
        $final_data['rule'] = $setting_info;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $final_data); //INAVALID_SHOP_OR_OWNER_ID
        $this->__createLog('Exiting from [NotificationManagerController->getUserNotificationSettingsAction(Request)] with response'.(string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    
    /**
     * function for saving the notification setting for a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getUserNotificationIndividualSettingsAction(Request $request) {
        
        $this->__createLog('[Entering in NotificationManagerController->saveUserNotificationsAction(Request)]');
        $data = array();
        $required_parameter = array('user_id','notification_type','user_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Registersellers] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //deseralize the request data
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $user_id = (int)$de_serialize['user_id'];
        $notification_type = $de_serialize['notification_type'];
        $notification_setting = $de_serialize['notification_setting'];
        $user_type = $de_serialize['user_type'];
        $notification_manager_service = $this->container->get('notification_manager.notificationManagement');
        $notification_rule = $notification_manager_service->getUserNotificationSetting($user_id,$notification_setting,$notification_type,$user_type);
        echo "<pre>";
        print_r($notification_rule);
        exit;
        $em = $this->getDoctrine()->getManager();
        //get the notification setting form the DB for the user
        $notification_settings = $em
                    ->getRepository('UtilityUniversalNotificationsBundle:NotifictionManager')
                    ->findBy(array('userId' => $user_id));
        
        //preparing the data for the notification setting list
        $final_data = array();
        $final_data['user_id'] = $user_id;
        $final_data['rule'] = array();
        $setting_info = NULL;
        //prepare data for the notification setting
        foreach($notification_settings as $setting) {
            $setting_info[$setting->getNotificationType()] = Utility::convertNotificationSettingToBinary($setting->getNotificationSetting());
        }
        $final_data['rule'] = $setting_info;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $final_data); //INAVALID_SHOP_OR_OWNER_ID
        $this->__createLog('Exiting from [NotificationManagerController->saveUserNotificationsAction(Request)] with response'.(string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    
    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.notification_manager_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
}