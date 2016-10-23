<?php
namespace Utility\ApplaneIntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

class CitizenEventListner extends Event implements ApplaneConstentInterface
{
     protected $container;
     
    /**
     *  define custom custructor and pass container as argument
     * @param \Utility\ApplaneIntegrationBundle\Event\ContainerInterface $container
     */
    public function __construct(Container $container) // this is @service_container
    {
        $this->container = $container;
    }
    
    /**
     * Citizen register listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenRegisterAction(Event $event)
    {
        //get constant values
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenRegister($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens',$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.citizen_profile_logs');
        $monolog_data_request = "Citizen Registration: Request Data:".json_encode($final_data);
        $monolog_data_response = "Citizen Registration : Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
        
    }

    /**
     * Citizen register listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenUpdateAction(Event $event)
    {
        //get constant values
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.citizen_profile_logs');
        $monolog_data_request = "Citizen Updation: Request Data:".json_encode($final_data);
        $monolog_data_response = "Citizen Updation : Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
        
    }
    
    /**
     * Citizen Update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenFriendAddAction(Event $event)
    {
        //get constant values
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenAddFriend($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens', $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        //write log
        $handler = $this->container->get('monolog.logger.store_profile');
        $monolog_data_request = "Made friend on applane: Request Data:".json_encode($final_data);
        $monolog_data_response = "Made friend on applane: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     * Update profile image
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenUpdateProfileImageAction(Event $event)
    {
        //get constant values
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenProfileImageUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Update keyword
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenKeywordUpdateAction(Event $event)
    {
        //get constant values
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenKeywordUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
     public function prepareApplaneDataCitizenRegister($data){
        
        $response_data = array(
            '_id' => (string)$data['register_id'],
            //'address_l1' => '',
            //'city' => '',
            'country' => (object)array('$query'=>array('code' => $data['country'])),
            'email_id' => $data['email'],
            //'followers' => '',
            //'keywords' => '',
            //'mobile_no' => '',
            //'my_friends' => '',
            'name' => $data['firstname']." ".$data['lastname'], 
            //'refferred_by'=>(object)array('_id'=>'123434'),
            'sex'=>$data['gender'],
            //'state' => ''  
        );
        
        if(isset($data['referral_id']) && $data['referral_id'] != ''){
            $response_data['refferred_by'] = (object)array('_id'=>(string)$data['referral_id']);
        }
        return $response_data;

    }
    
    /**
     * follow a citizen 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenFollowCreateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenFollowCreate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data   = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CITIZEN_COLLECTION, self::ACTION_UPDATE);
        $applane_resp = $applane_service->callApplaneService($final_data, self::URL_UPDATE, self::QUERY_UPDATE);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status) ? $appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.citizen_profile_logs');
        $monolog_data_request = "Citizen Follow create: Request Data:".json_encode($final_data);
        $monolog_data_response = "Citizen Follow create: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     * Prepare the applane data for citizen follow create
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenFollowCreate($data) {
        $setDataObject = array(
            self::FOLLOWERS => (object)array(
                '$'.self::INNER_ACTION_INSERT => array(array('_id'=>(string)$data['sender_id']))
            )
        );
        
        $response_data = array(
            '_id'  => (string)$data['to_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }
    
    /**
     * Unfollow a citizen 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenFollowDeleteAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenFollowDelete($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data   = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CITIZEN_COLLECTION, self::ACTION_UPDATE);
        $applane_resp = $applane_service->callApplaneService($final_data, self::URL_UPDATE, self::QUERY_UPDATE);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status) ? $appalne_decode_resp->status:'error';
    }
 
    /**
     * Prepare the applane data for citizen follow delete
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenFollowDelete($data) {
        $setDataObject = array(
            self::FOLLOWERS => (object)array(
                '$'.self::INNER_ACTION_DELETE => array(array('_id'=>(string)$data['user_id']))
            )
        );
        
        $response_data = array(
            '_id'  => (string)$data['friend_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }

    /**
     * Citizen Update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenFriendDeleteAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenDeleteFriend($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CITIZEN_COLLECTION, self::ACTION_UPDATE);
        $applane_resp = $applane_service->callApplaneService($final_data, self::URL_UPDATE, self::QUERY_UPDATE);
        $appalne_decode_resp = json_decode($applane_resp,JSON_NUMERIC_CHECK);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }

    /**
     * Prepare the applane data for citizen remove friend
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenDeleteFriend($data){
        $setDataObject = array(
            self::MY_FRIENDS => (object)array(
                '$'.self::INNER_ACTION_DELETE => array(array('_id'=>(string)$data['friend_id']))
            )
        );
        
        $response_data = array(
            '_id' => (string)$data['register_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }
    
    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenAddFriend($data){
        $action_insert = self::INNER_ACTION_INSERT;
        $setDataObject = array(
            'my_friends' => (object)array(
                '$'.$action_insert => array(array('_id' =>(string)$data['friend_data']))
            )
        );
        
        $response_data = array(
            '_id' => (string)$data['register_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }
    
    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenUpdate($data){
        $setDataObject = array(
            'name' => $data['firstname']." ".$data['lastname'], 
            'sex'=>$data['gender'],
            'address_l1' => $data['address'],
            'address_l2' => $data['map_place'],
            'country' => (object)array('$query'=>array('code' => $data['country']))
        );
        
        //check for latitude
        if (isset($data['latitude']) && $data['latitude'] != '' && $data['latitude'] != 'undefined') {
            $setDataObject['latitude'] = (string)$data['latitude'];
        }
        
        //check for longitude
        if (isset($data['longitude']) && $data['longitude'] != '' && $data['longitude'] != 'undefined') {
            $setDataObject['longitude'] = (string)$data['longitude'];
        }
        
        //check for city
        if (isset($data['city']) && $data['city'] != '') {
            $setDataObject['city'] = (string)$data['city'];
        }
        
        $response_data = array(
            '_id' => (string)$data['user_id'],
            '$set' => $setDataObject, 
        );
        
       return $response_data;
    }
    
    /**
     * Prepare applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenProfileImageUpdate($data){
        $setDataObject = array(
            'user_thumbnail_image' => $data['profile_img'],
        );
        $response_data = array(
            '_id' => (string)$data['user_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }
    
    
    /**
     * Prepare applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataCitizenKeywordUpdate($data){
        $action_insert = self::INNER_ACTION_INSERT;
        $setDataObject = array(
            'keywords' => (object)array(
                '$'.$action_insert => array(array('name' =>(string)$data['keywords']))
            )
        );
        
        $response_data = array(
            '_id' => (string)$data['user_id'],
            '$set' => $setDataObject, 
        );
       return $response_data;
    }
    
    /**
     *  function for updating the affiliation on the applane
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onCitizenAffiliationUpdateAction(Event $event) {
        //get constant values
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $data = $this->prepareApplaneDataCitizenAffiliationUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_citizens',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        //write log
        $handler = $this->container->get('monolog.logger.store_profile');
        $monolog_data_request = "Citizen affiliation update: Request Data:".json_encode($final_data);
        $monolog_data_response = "Citizen affiliation update: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     *  function for preparing the applane data for the Citizen affiliation
     * @param type $data
     */
    private function prepareApplaneDataCitizenAffiliationUpdate($data) {
        $setDataObject = array(
            'refferred_by' => (object)array('_id'=>(string)$data['referral_id'])
        );
        
        $response_data = array(
            '_id' => (string)$data['user_id'],
            '$set' => $setDataObject, 
        );
        
        return $response_data;
    }
    
    /**
     * write logs for transaction system integration
     * @param json $request
     * @param json $response
     * @return boolean
     */
    public function writeAllLogs($handler, $request, $response) {
        try {
            $handler->info($request);
            $handler->info($response);
        } catch (\Exception $ex) {

        }
        return true;
    }
}