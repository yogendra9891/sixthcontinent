<?php
namespace Utility\ApplaneIntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

class ShopEventListner extends Event implements ApplaneConstentInterface
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
     * Shop favourite listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopFavouriteAction(Event $event)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopFavouriteAction]', array());
        $data = $event->getData();
        //check for shop and customer mapping on applane
        $customer_choice_id = $this->getCustomerChoice($data);
        if($customer_choice_id == 0){
            //new insert
            $this->shopFavCreate($data);
        }else{
        //update
        $this->shopFavUpdate($data);
        }
    }
    
    /**
     * Create shop favourite
     * @param type $data
     */
    public function shopFavCreate($data)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFavCreate]', array());
        $data = $this->prepareApplaneDataStoreFav($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $collection = self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, $collection,$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFavCreate]:'.$applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Update shop favourite
     * @param type $data
     */
    public function shopFavUpdate($data)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFavUpdate]', array());
        $data = $this->prepareApplaneDataFavUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CUSTOMER_CHOICE, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFavUpdate]:'.$applane_resp, array());
        
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Prepare applane data for fav update
     * @param type $data
     */
    public function prepareApplaneDataFavUpdate($data)
    {
         $response_data = array(
            '_id' => (string)$data['id'],
            '$set' => array(
            'is_favourate' => true
        ));      
        return $response_data;
    }
    
//     /**
//     * Shop unfavourite listner
//     * @param \Symfony\Component\EventDispatcher\Event $event
//     */
//    public function onShopUnFavouriteAction(Event $event)
//    {
//        $data = $event->getData();
//        $data = $this->prepareApplaneDataStoreUnFav($data);
//        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
//        //getting the varibles from the interface
//        $url_update    = self::URL_UPDATE;
//        $query_update  = self::QUERY_UPDATE;
//        $applane_resp = $applane_service->callApplaneService($data, $url_update, $query_update);
//        $appalne_decode_resp = json_decode($applane_resp);
//        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
//    }
   
//     /**
//     * Shop follow listner
//     * @param \Symfony\Component\EventDispatcher\Event $event
//     */
//    public function onShopFollowAction(Event $event)
//    {
//        $data = $event->getData();
//        $data = $this->prepareApplaneDataStoreFollow($data);
//        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
//        //getting the varibles from the interface
//        $action_insert = self::ACTION_INSERT;
//        $url_update = self::URL_UPDATE;
//        $query_update = self::QUERY_UPDATE;
//        $collection = self::SIX_CONTINENT_CUSTOMER_CHOICE;
//        $final_data = $applane_service->getMongoDataFormatInsert($data, $collection, $action_insert);
//        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
//        $appalne_decode_resp = json_decode($applane_resp);
//        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
//    }
//    
    
    
     /**
     * Shop follow listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopFollowAction(Event $event)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopFollowAction]', array());
        $data = $event->getData();
        //check for shop and customer mapping on applane
        $customer_choice_id = $this->getCustomerChoice($data);
        if($customer_choice_id == "0"){
            //new insert
            $this->shopFollowCreate($data);
        }else{
        //update
        $this->shopFollowUpdate($data);
        }
    }
    
    /**
     * Create shop follow
     * @param type $data
     */
    public function shopFollowCreate($data)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFollowCreate]', array());
        $data = $this->prepareApplaneDataStoreFollow($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $collection = self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, $collection, $action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFollowCreate]:'.$applane_resp, array());
        
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Update shop follow
     * @param type $data
     */
    public function shopFollowUpdate($data)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFollowUpdate]', array());
        $data = $this->prepareApplaneDataFollowUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CUSTOMER_CHOICE, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [shopFollowUpdate]:'.$applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
     /**
     * Prepare applane data
     * @param type $data
     */
    public function prepareApplaneDataFollowUpdate($data)
    {
         $response_data = array(
            '_id' => (string)$data['id'],
            '$set' => array(
            'is_following' => true 
        ));      
        return $response_data;
    }
    
    /**
     * Prepare applane data
     * @param type $data
     */
    public function prepareApplaneDataUnfollowUpdate($data)
    {
         $response_data = array(
            '_id' => (string)$data['id'],
            '$set' => array(
            'is_following' => false 
        ));      
        return $response_data;
    }
    
     /**
     * Prepare applane data
     * @param type $data
     */
    public function prepareApplaneDataUnfavUpdate($data)
    {
         $response_data = array(
            '_id' => (string)$data['id'],
            '$set' => array(
            'is_favourate' => false 
        ));      
        return $response_data;
    }
    
//    /**
//     * Shop unfollow listner
//     * @param \Symfony\Component\EventDispatcher\Event $event
//     */
//    public function onShopUnFollowAction(Event $event)
//    {
//        $data = $event->getData();
//        $data = $this->prepareApplaneDataStoreUnFollow($data);
//        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
//        //getting the varibles from the interface
//        $url_update   = self::URL_UPDATE;
//        $query_update = self::QUERY_UPDATE;
//        $applane_resp = $applane_service->callApplaneService($data, $url_update, $query_update);
//        $appalne_decode_resp = json_decode($applane_resp);
//        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
//    }
    
    
     /**
     * Shop unfollow listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUnFollowAction(Event $event)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopUnFollowAction]', array());
        $data = $event->getData();
        $data = $this->prepareApplaneDataUnfollowUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CUSTOMER_CHOICE, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopUnFollowAction]:'.$applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
     /**
     * Shop unfollow listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUnFavouriteAction(Event $event)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopUnFavouriteAction]', array());
        $data = $event->getData();
        $data = $this->prepareApplaneDataUnfavUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CUSTOMER_CHOICE, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $this->__createLogCustomerChoice('Request Object:'.$final_data, array());
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [onShopUnFollowAction]:'.$applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
    /**
     * Shop create listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopCreateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreCreate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_shops',$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        //write log
        $handler = $this->container->get('monolog.logger.store_profile');
        $monolog_data_request = "Shop affiliation update: Request Data:".json_encode($final_data);
        $monolog_data_response = "Shop affiliation update: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    
    /**
     * Shop update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUpdateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_shops',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Prepare the applane data for store create
     * @param array $data
     * @param int $register_id
     * @param array $friend_data
     * @param array $follower_data
     * @return string
     */
    public function prepareApplaneDataStoreCreate($data){
        $response_data = array(
            '_id' => (string)$data['store_id'],
            //'citizen_id' => (object)array('_id' => $data['user_id']),
            'category_id' => (object)array('_id' => (string)$data['sale_catid']),
            //'country' => (object)array('$query'=>array('code' => $data['business_country'])),
            'country' => (object)array('$query'=>array('code' => $data['sale_country'])),
            'shopowner_id' => (object)array( '_id' => (string)$data['user_id']),
            'address_l1' => $data['business_address'],
            //'mobile_no' => $data['phone'],
            'mobile_no' => $data['sale_phone_number'],
            'name' => $data['name'],
            'credit_card_added' => '0',
            'address_l2' => $data['map_place'],
            'is_shop_deleted' => '0',            
        );
        
        if (isset($data['referral_id']) && $data['referral_id'] != '') {
            $response_data['referred_by_id'] = (object)array('_id'=> (string)$data['referral_id']);
   
        } 
        //check for region
        if (isset($data['sale_region']) && $data['sale_region'] != '') {
            $response_data['region'] = (string)$data['sale_region'];
        } 
        
        //check for city
//        if (isset($data['sale_city']) && $data['sale_city'] != '') {
//            $response_data['city'] = (object)array('$query'=>array('code' => $data['sale_city']));
//   
//        }
        //check for city
        if (isset($data['sale_province']) && $data['sale_province'] != '') {
            $response_data['province'] = (string)$data['sale_province'];
   
        }
        //check for city
        if (isset($data['sale_zip']) && $data['sale_zip'] != '') {
            $response_data['zip'] = (string)$data['sale_zip'];
   
        }
        //check for city
        if (isset($data['sale_address']) && $data['sale_address'] != '') {
            $response_data['street_address'] = (string)$data['sale_address'];
   
        }
        //check for city
        if (isset($data['sale_email']) && $data['sale_email'] != '') {
            $response_data['email_address'] = (string)$data['sale_email'];
   
        }
        //check for city
//        if (isset($data['shop_keyword']) && $data['shop_keyword'] != '') {
//            $response_data['keyword'] = explode(",",$data['shop_keyword']);
//   
//        }
        
        //check for latitude
        if (isset($data['latitude']) && $data['latitude'] != '' && $data['latitude'] != 'undefined') {
            $response_data['latitude'] = (string)$data['latitude'];
        }
        
        //check for longitude
        if (isset($data['longitude']) && $data['longitude'] != '' && $data['longitude'] != 'undefined') {
            $response_data['longitude'] = (string)$data['longitude'];
        }
        
        //check for description
        if (isset($data['description']) && $data['description'] != '') {
            $response_data['shop_description'] = (string)$data['description'];
        }
        return $response_data;
    }
    
    /**
     * Prepare the applane data for store update
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreUpdate($data){
      
        $appalne_data = array(
            'category_id' => (object)array('_id' => (string)$data['sale_catid']),
            //'country' => (object)array('$query'=>array('code' => $data['business_country'])),
            //'shopowner_id' => (object)array( '_id' => (string)$data['user_id']),
            'country' => (object)array('$query'=>array('code' => $data['sale_country'])),
            'address_l1' => $data['business_address'],
            'address_l2' => $data['map_place'],
            //'mobile_no' => $data['phone'],
            'mobile_no' => $data['sale_phone_number'],
            'name' => $data['name']
        );
                
         if (isset($data['sale_subcatid']) && $data['sale_subcatid'] != '') {
            $appalne_data['subcategory_id'] = (object)array('_id' => (string)$data['sale_subcatid']);
         }
        
        //check for region
        if (isset($data['sale_region']) && $data['sale_region'] != '') {
            $appalne_data['region'] = (string)$data['sale_region'];
   
        } 
        
        //check for city
//        if (isset($data['sale_city']) && $data['sale_city'] != '') {
//            $response_data['city'] = (object)array('$query'=>array('code' => $data['sale_city']));
//   
//        }
        //check for sale prov
        if (isset($data['sale_province']) && $data['sale_province'] != '') {
            $appalne_data['province'] = (string)$data['sale_province'];
   
        }
        //check for sale zip
        if (isset($data['sale_zip']) && $data['sale_zip'] != '') {
            $appalne_data['zip'] = (string)$data['sale_zip'];
   
        }
        //check for sale address
        if (isset($data['sale_address']) && $data['sale_address'] != '') {
            $appalne_data['streetaddress'] = (string)$data['sale_address'];
   
        }
        //check for sale email
        if (isset($data['sale_email']) && $data['sale_email'] != '') {
            $appalne_data['email_address'] = (string)$data['sale_email'];
   
        }
        
        //check for latitude
        if (isset($data['latitude']) && $data['latitude'] != '' && $data['latitude'] != 'undefined') {
            $appalne_data['latitude'] = (string)$data['latitude'];
        }
        
        //check for longitude
        if (isset($data['longitude']) && $data['longitude'] != '' && $data['longitude'] != 'undefined') {
            $appalne_data['longitude'] = (string)$data['longitude'];
        }
        
        //check for description
        if (isset($data['description']) && $data['description'] != '') {
            $appalne_data['shop_description'] = (string)$data['description'];
        }
        
        $response_data = array(
            '_id' => (string)$data['store_id'],
            '$set' => $appalne_data);
                
        //check for keywords
//        if (isset($data['shop_keyword']) && $data['shop_keyword'] != '') {
//            $response_data['keyword'] = explode(",",$data['shop_keyword']);
//   
//        }
        return $response_data;
    }
    
    
     /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreFav($data){

        $response_data = array(
            '_id' => (string)$data['id'],
            'citizen_id' => (object)array('_id' => (string)$data['user_id']),
            'is_favourate' => true, 
            'shop_id' => (object)array( '_id' => (string)$data['store_id']),
            
        );
        
        return $response_data;
    }
    
    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreUnFav($data){
        $data = array(
            '$collection'=>self::SIX_CONTINENT_CUSTOMER_CHOICE,
            '$delete'=> array((object)array(
                    '_id'=>(string)$data['id']
            ))
       );
       $response_data = json_encode($data);
       return $response_data; 
    }
    
     /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreFollow($data){
        $response_data = array(
            '_id' => (string)$data['id'],
            'citizen_id' => (object)array('_id' => (string)$data['user_id']),
            'is_following' => true, 
            'shop_id' => (object)array( '_id' => (string)$data['shop_id']),
            
        );
       return $response_data;
       
    }
    
     /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreUnFollow($data){
        $data = array(
            '$collection'=>self::SIX_CONTINENT_CUSTOMER_CHOICE,
            '$delete'=> array((object)array(
                    '_id'=>(string)$data['id']
            ))
        );
       $response_data = json_encode($data);
       return $response_data; 
    }
    
    /**
     * Shop update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUpdateProfileImgAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreProfileImgUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_shops',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Prepare the applane data for store update
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreProfileImgUpdate($data){
        $response_data = array(
            '_id' => (string)$data['shop_id'],
            '$set' => array(
            'shop_thumbnail_img' => $data['profile_img']
        ));      
        return $response_data;
    }
    
    /**
     * Update shop card status
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUpdateCardStatusAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreCardUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_shops',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    }
    
    /**
     * Prepare the applane data for store update
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreCardUpdate($data){
        $response_data = array(
            '_id' => (string)$data['shop_id'],
            '$set' => array(
            'credit_card_added' => '0'
        ));      
        return $response_data;
    }
    
    /**
     * Update shop shop status
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopUpdateShopStatusAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreShopUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $final_data    = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_SHOP_COLLECTION, $action_update);
        $applane_resp  = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }    
    /**
     * Update shop card status
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopDeleteAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreDelete($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $final_data    = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_SHOP_COLLECTION, $action_update);
        $applane_resp  = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    } 
    
    /**
     * Prepare the applane data for store shop status
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreShopUpdate($data){
        $response_data = array(
                '_id' => (string)$data['store_id'],
                '$set' => array(
                'shop_status' =>(string)$data['status']
            ));      
        return $response_data;
    }
    
    /**
     * Prepare the applane data for store delete
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreDelete($data){
        $response_data = array(
            '_id' => (string)$data['shop_id'],
            '$set' => array(
            'is_shop_deleted' => '1'
        ));      
        return $response_data;
    }
    
    /**
     * On recurring update
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopRecurringUpdateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataRecurringUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $final_data    = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_SHOP_INCOME, $action_update);
        $applane_resp  = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data_request = "Update recurring on applane Request Data:".json_encode($final_data);
        $monolog_data_response = "Update recurring on applane Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
        
    }
    
    /**
     * 
     * @param array $data
     */
    public function prepareApplaneDataRecurringUpdate($data)
    {
        $amount_paid = array(
            'amount' => $data['amount_paid'],
            'type' => array(
                '_id'=> '5534f97c0b76c58f769c105c',
                'currency' => 'EUR'
            )
        );
        
        $vat = array(
            'amount' => $data['vat_amount'],
            'type' => array(
                '_id'=> '5534f97c0b76c58f769c105c',
                'currency' => 'EUR'
            )
        );
        //'_id' => '552f4906e99da6b3516dd787',
        $response_data = array(
            '_id' => (string)$data['invoice_id'],
            '$set' => array(
            'TransactionIDCarteSI' => $data['transaction_id_carte_si'],
                'TransactionNote' => $data['transaction_note'],
                'amount_paid' => $amount_paid,
                'paidon' => $data['paid_on'],
                'payment_date' => $data['payment_date'],
                'payment_status' => $data['payment_status'],
                'vat' => $vat
        ));      
        return $response_data ;
    }
    
    /**
     * On shop registration update on applane
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopRegistrationFeeUpdateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreFeeAdd($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::TRANSACTION_COLLECTION, $action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data_request = "Update registration fee on applane Request Data:".json_encode($final_data);
        $monolog_data_response = "Update registration fee on applane Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     * Preapare applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreFeeAdd($data)
    {
        $reg_fee_newshop = $this->container->getParameter('reg_fee'); //reg fee
        $reg_fee_newshop = $reg_fee_newshop / 100;
        $transaction_type_id = array(
          "_id" => "553209267dfd81072b176bb6"  
        );
        
        $shop_id = array(
          "_id" => (string)$data['shop_id']  
        );
        
        $response_data = array(
            'transaction_type_id' => $transaction_type_id,
            'shop_id' => $shop_id,
            'status' => "Approved",
            'Value' => $reg_fee_newshop,
            'total_income' => $reg_fee_newshop,
            'transaction_value' => $reg_fee_newshop,
            'checkout_value' => $reg_fee_newshop
            );      
        return $response_data ;
    }
    
    /**
     * On recurring insert
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopRecurringInsertAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataRecurringInsert($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_INSERT;
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $final_data    = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_SHOP_INCOME, $action_update);
        $applane_resp  = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data_request = "Registration fee paid by recuuring: Request Data:".json_encode($final_data);
        $monolog_data_response = "Registration fee paid by recuuring: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     * 
     * @param array $data
     */
    public function prepareApplaneDataRecurringInsert($data)
    {
        $amount_paid = array(
            'amount' => $data['amount_paid'],
            'type' => array(
                '_id'=> '5534f97c0b76c58f769c105c',
                'currency' => 'EUR'
            )
        );
        
        $vat = array(
            'amount' => $data['vat_amount'],
            'type' => array(
                '_id'=> '5534f97c0b76c58f769c105c',
                'currency' => 'EUR'
            )
        );
        
        $shop_id = array(
                '_id'=> (string)$data['shop_id']
            );
        
        //'_id' => '552f4906e99da6b3516dd787',
        $response_data = array(
                'TransactionIDCarteSI' => $data['transaction_id_carte_si'],
                'TransactionNote' => $data['transaction_note'],
                'amount_paid' => $amount_paid,
                'paidon' => $data['paid_on'],
                'payment_date' => $data['payment_date'],
                'payment_status' => $data['payment_status'],
                'vat' => $vat,
                'date' => $data['paid_on'],
                'shop_id' => $shop_id
        );      
        return $response_data ;
    }
    
    /**
     *  Event that is called when shop affiliation updates
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopAffiliationAction(Event $event) {
        $data = $event->getData();
        $data = $this->prepareApplaneDataAffiliationUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data,'sixc_shops',$action_update);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_update,$query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
        //write log
        $handler = $this->container->get('monolog.logger.store_profile');
        $monolog_data_request = "Shop affiliation update: Request Data:".json_encode($final_data);
        $monolog_data_response = "Shop affiliation update: Response Data:".$applane_resp;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
    }
    
    /**
     *  funciton for preparing the data for updating the shop affiliation process
     * @param type $data
     * @return type
     */
    private function prepareApplaneDataAffiliationUpdate($data) {
        $response_data = array(
                '_id' => (string)$data['store_id'],
                '$set' => array(
                'referred_by_id' => (object)array('_id'=> (string)$data['referral_id'])
            ));      
        return $response_data;
    }
    
      /**
     * Get SHop followers
     * @return type
     */
    public function getCustomerChoice($data)
    {
        $this->__createLogCustomerChoice('Entering into class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [getCustomerChoice] with data'.Utility::encodeData($data), array());
        $data = $this->prepareGetCustomerChoiceInfoData($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $applane_resp = $applane_service->callApplaneService($data, $api, $queryParam);
        $this->__createLogCustomerChoice('Customer choice request Object:'.$data, array());
        $this->__createLogCustomerChoice('Customer choice list:'.$applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $cc_id = $this->getCustomerChoiceId($appalne_decode_resp);
        $this->__createLogCustomerChoice('Exiting from class [Utility\ApplaneIntegrationBundle\Event\ShopEventListner] and function [getCustomerChoice] with data:'.$cc_id, array());
        return $cc_id;
    }
    
     /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetCustomerChoiceInfoData($data)
    {
        $id = $data['id'];
        $collection_data =  self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object)array( '_id' => (string)$id);
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
    /**
     * Get User Ids from applane
     * @param array $response
     * @return type
     */
    public function getCustomerChoiceId($response)
    {
        $applane_customer_choice_id = "0";
        $applane_shops = array();
        $final_data = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
               $applane_customer_choice_id = (isset($single_result->_id)) ? $single_result->_id : 0;
            }
        }
        return $applane_customer_choice_id;
    }
    
    /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLogCustomerChoice($monolog_req, $monolog_response = array())
    {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.applane_customer_choice_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);  
        return true;
    }
    
    /**
     * Update shop delete status
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopDeleteUpdateAction(Event $event)
    {
        $data = $event->getData();
        $data = $this->prepareApplaneDataStoreDeleteUpdate($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $final_data    = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_SHOP_COLLECTION, $action_update);
        $applane_resp  = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
    } 
    
     /**
     * Prepare the applane data for store delete
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreDeleteUpdate($data){
        $response_data = array(
            '_id' => (string)$data['shop_id'],
            '$set' => array(
            'is_shop_deleted' => (string)$data['is_active']
        ));      
        return $response_data;
    }
}