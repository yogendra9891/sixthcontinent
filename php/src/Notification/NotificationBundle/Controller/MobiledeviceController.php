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
use Notification\NotificationBundle\Entity\UserDevice;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;

class MobiledeviceController extends FOSRestController {
    
     protected $service_gcm = "gcm";
     protected $service_apn = "apn";
     protected $service_apns = "apns";
    
    
    /**
     * Get approved friend request notification
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postRegisterdevicesAction(Request $request)
    {
       //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('device_type', 'device_id', 'user_id','app_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        
        $user_id = $object_info->user_id;
        $device_id = $object_info->device_id;
        $device_type = $object_info->device_type;
        $app_type = $object_info->app_type;
        $device_lang = isset($de_serialize['device_lang']) ? $de_serialize['device_lang'] : '';
        $unique_device_id = isset($de_serialize['unique_device_id']) ? $de_serialize['unique_device_id'] : '';
        $time = new \DateTime("now");
        $em = $this->getDoctrine()->getManager();
        
        //check if device is already registerd
//        $register_device = $em->getRepository('NManagerNotificationBundle:UserDevice')
//                              ->findOneBy(array('deviceId'=>$device_id, 'deviceType' => $device_type));
        
        $register_device = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                ->getRegisterDevice($device_id,$device_type,$unique_device_id,$app_type);
            try{
                if($register_device){
               //update device with updated user id
                $register_device->setUserId($user_id);
                $register_device->setAppType($app_type);
                $register_device->setDeviceLang($device_lang);
                $register_device->setDeviceType($device_type);
                $register_device->setUniqueDeviceID($unique_device_id);
                $register_device->setDeviceId($device_id);
                $em->persist($register_device);
                //save the store info
                $em->flush();
            }else{
                //save user devices
                //get entity object
                $user_device = new UserDevice();
                $user_device->setUserId($user_id);
                $user_device->setDeviceId($device_id);
                $user_device->setDeviceType($device_type);
                $user_device->setAppType($app_type);
                $user_device->setCreatedAt($time);
                $user_device->setDeviceLang($device_lang);
                $user_device->setUniqueDeviceID($unique_device_id);
                //persist the store object
                $em->persist($user_device);
                //save the store info
                $em->flush();
            }
       } catch (\Exception $ex) {
         //  echo $ex->getMessage();
        }
        $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($data);
    }
    
    /**
     * Unregister device
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUnregisterdevicesAction(Request $request)
    {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('device_type', 'device_id', 'user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        
        $user_id = $object_info->user_id;
        $device_id = $object_info->device_id;
        $device_type = $object_info->device_type;
        $time = new \DateTime("now");
        $em = $this->getDoctrine()->getManager();
        
        //check if device is already registerd
        $register_device = $em->getRepository('NManagerNotificationBundle:UserDevice')
                              ->findOneBy(array('deviceId'=>$device_id, 'deviceType' => $device_type, 'userId' =>$user_id));
        if($register_device){
            $em->remove($register_device);
            //remove devices
            $em->flush();
            $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            $this->returnResponse($data);
        }
        //save user devices
        //get entity object
        $data = array('code' => '174', 'message' => 'NO_DEVICE_FOUND', 'data' => $data);
        $this->returnResponse($data);
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
     * return the response.
     * @param type $data_array
     */
    public function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }
    
    
    public function pushfeedbacksAction(Request $request){
        //call the service for getting the request object.     
        $de_serialize = $this->RequestObjectPushfeedBackService($request);
        $this->_log('ReqObj is '. Utility::encodeData($de_serialize));
        // this is without key for request
//        $request_object_service =$request->getContent(); 
//        $de_serialize = json_decode(trim($request_object_service),true);
        
        $data = array('tobe_deleted'=>array(), 'tobe_updated'=>array());
        foreach($de_serialize as $_data) {
            $event = $_data['event'];
            switch ($event){
                case 'deleted':
                        $data['tobe_deleted'] = $_data['deviceId1'];
                        $response = $this->deleteDeviceData($data['tobe_deleted'],$_data['service']);
                        break;
                case 'updated':
                        $data['tobe_updated'][] = array(
                                'deviceId1'=>$_data['deviceId1'],
                                'deviceId2'=>$_data['deviceId2']
                        );
                        $response = $this->updateDeviceData($data['tobe_updated'],$_data['service']);
                        break;          
            }
        }
        //out put the response             
        Utility::createResponse($response);
    }
    /**
     * function delete device ids
     * @param type $device_ids
     * @return Resp
     */
    
    
      public function deleteDeviceData($device_id,$service_type){
        $em = $this->getDoctrine()->getManager();
        $service_type = Utility::getLowerCaseString($service_type);
        try{
            if($service_type == $this->service_gcm ) {
                $device_type = 'A';
                $a_device = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                       ->findOneBy(array('deviceId'=>$device_id, 'deviceType' => $device_type));
                if($a_device) {
                    $a_device_id= $a_device->getDeviceId();
                    $a_device_to_deleted = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                        ->deleteDevices($a_device_id,$device_type);
                    if($a_device_to_deleted){
                        $this->_log('Device of '. $a_device_id .' of type '. $device_type.' is deleted');
                    }
                }
            }elseif($service_type == ($this->service_apn OR $this->service_apns) ) {
                $device_type = 'I';
                $I_device = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                       ->findOneBy(array('deviceId'=>$device_id, 'deviceType' => $device_type));
                if($I_device) {
                    $I_device_id= $I_device->getDeviceId();
                    $i_device_to_deleted = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                        ->deleteDevices($I_device_id,$device_type);
                    if($i_device_to_deleted){
                          $this->_log('Device of '. $a_device_id .' of type '. $device_type.' is deleted');
                    }
                }
            }else {
                return;
            }
            
        } catch (\Exception $ex) {

        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
        return $resp_data;
    }
    
    /**
     * update d1 on basis of d2 and device type
     * @param type $device_ids
     * @param type $service_type
     * @return Resp
     */
    public function updateDeviceData($device_ids,$service_type){
        $em = $this->getDoctrine()->getManager();
        $service_type = Utility::getLowerCaseString($service_type);
        try{
            if($service_type == $this->service_gcm ){
                $device_type = 'A'; 
            }elseif($service_type == ($this->service_apn OR $this->service_apns) ) {
                $device_type = 'I'; 
            }else{
                return;
            }
            foreach($device_ids as $device_id){
                $device_id_1 = $device_id['deviceId1']; 
                $device_id_2 = $device_id['deviceId2'];

                $devices_2_info = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                      ->findOneBy(array('deviceId'=>$device_id_2, 'deviceType' => $device_type));
                if($devices_2_info){
                        $devices_1_info = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                      ->findOneBy(array('deviceId'=>$device_id_1, 'deviceType' => $device_type));
                        if($devices_1_info){
                            $device_to_deleted = $em->getRepository('NManagerNotificationBundle:UserDevice')
                                                    ->deleteDevices($device_id_1,$device_type);
                            if($device_to_deleted) {
                                $this->_log('device1 : '. $device_id_1 . ' of type '. $device_type. ' is deleted');
                            } else {
                                 $this->_log('device1 : '. $device_id_1 . ' of type '. $device_type. ' is not deleted');
                            }
                        } else {
                            $this->_log('device1 : '. $device_id_1 . ' of type '. $device_type. ' is not present');
                        }
                } else {
                     $this->_log('device2 : '. $device_id_2 . ' of type '. $device_type. ' is not present');
                }
            }
                
        } catch (\Exception $ex) {

        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
        return $resp_data;
    }
    
     /**
     *  function for writting the logs
     * @param type $sMessage
     */
    protected function _log($sMessage, $type='info') { 
        
        $monoLog = $this->container->get('monolog.logger.save_device');
        if($type=='error'){
            $monoLog->error($sMessage);
        }else{
            $monoLog->info($sMessage);
        }
    }
    
     /**
     * service for fetching the request object 
     * @param Request
     * @return object array
     */
   public function RequestObjectPushfeedBackService(Request $request)
   {
        $freq_obj  = $request->get('feedback');
        $request_object_service = $this->container->get('request_object.service');
        $fde_serialize = $request_object_service->decodeObjectAction($freq_obj);
        $de_serialize = $this->getPushFeedBackAppData($request);
        return $de_serialize;    
   }
      /**
     * 
     * @param type $request
     * @return type
     */
    public function getPushFeedBackAppData(Request $request) {
        $content = $request->getContent();
        $request_object_service = $this->container->get('request_object.service');
        $dataer = (object) $request_object_service->decodeObjectAction($content);
        $app_data = $dataer->feedback;
        $req_obj = $app_data;
        return $req_obj;
    }
    
//    public function deleteDeviceData($device_ids){
//        $em = $this->getDoctrine()->getManager();
//
//        try{
//          
//                $devices_to_deleted = $em->getRepository('NManagerNotificationBundle:UserDevice')
//                                         ->deleteDevices($device_ids);
//            }
//            
//        } catch (\Exception $ex) {
//
//        }
//        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
//        return $resp_data;
//    }
    /**
     * function update device2 with device1
     * @param type $device_ids
     * @return Resp
     */
    
//    public function updateDeviceData($device_ids){
//        $em = $this->getDoctrine()->getManager();
//        try{
//                foreach($device_ids as $device_id){
//                $device_id_1 = $device_id['deviceId1']; 
//                $device_id_2 = $device_id['deviceId2'];
//                $devices_1_info = $em->getRepository('NManagerNotificationBundle:UserDevice')
//                                      ->findOneBy(array('deviceId'=>$device_id_1));
//                $device_1 = $devices_1_info->getId();
//                if($device_1) {
//                        $devices_1_info->setDeviceId($device_id_2);
//                        $em->persist($devices_1_info);
//                        $em->flush();
//                    }
//                }
//        } catch (\Exception $ex) {
//
//        }
//        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
//        return $resp_data;
//    }
}

