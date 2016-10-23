<?php
namespace Utility\ApplaneIntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
class BussinessCategoryListener extends Event implements ApplaneConstentInterface
{
    protected $em;
    protected $dm;
    protected $container;

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
     * bussiness category create listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onBussinessCategoryCreate(Event $event){
        //get constant values
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $data = $event->getData();
        $this->writeLogs('[Enter in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCreate with data'.json_encode($data).']');
        $data = $this->prepareApplaneDataSaveCategory($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,ApplaneConstentInterface::APPLANE_BUSSINESS_CATEGORY_COLLECTION,$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $this->writeLogs('[Reponse from applane in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCreate for data'.json_encode($data).' and response:'.  json_encode($appalne_decode_resp));
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
    
    /**
     * bussiness category update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onBussinessCategoryUpdate(Event $event){
        //get constant values
        $action_insert = self::URL_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $this->writeLogs('[Enter in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryUpdate with data'.json_encode($data).']');
        $data = $this->prepareApplaneDataUpdateCategory($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,ApplaneConstentInterface::APPLANE_BUSSINESS_CATEGORY_COLLECTION,$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $this->writeLogs('[Reponse from applane in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryUpdate for data'.json_encode($data).' and response:'.  json_encode($appalne_decode_resp));
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
    /**
     *  function for preapring the data afor category update on applane
     * @param type $data
     * @return type
     */
    private function prepareApplaneDataUpdateCategory($data) {
        $final_data = array();
        $final_data['$set'] = array('card_percentage' => $data['card_percentage'],'name' => $data['name'],'txn_percentage' => $data['txn_percentage']);
        $final_data['_id'] = (string)$data['id'];
        return $final_data;
    }
    
    /**
     * funcition for preparing the saving bussiness category on applane
     * @param type $data
     * @return type
     */
    private function prepareApplaneDataSaveCategory($data) {
        $final_data = (object) $data;
        return $final_data;
    }
    
    
    
     /**
     * bussiness category code create listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onBussinessCategoryCodeCreate(Event $event){
        //get constant values
        $action_insert = self::URL_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $this->writeLogs('[Enter in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCodeCreate with data'.json_encode($data).']');
        $data = $this->prepareApplaneDataCreateSubCategory($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,ApplaneConstentInterface::APPLANE_BUSSINESS_SUB_CATEGORY_COLLECTION,$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $this->writeLogs('[Reponse from applane in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCodeCreate for data'.json_encode($data).' and response:'.  json_encode($appalne_decode_resp));
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
    /**
     * function for preparing the data for sub category create on applane
     * @param type $data
     * @return type
     */
    public function prepareApplaneDataCreateSubCategory($data) {
        $final_data = array();
        $final_data['category_id'] = array("_id" => (string)$data['category_id']);
        $final_data['name'] = $data['name'];
        $final_data['_id'] = $data['_id'];
        return $final_data;
    }
    
    /**
     * bussiness categorycode update listner
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onBussinessCategoryCodeUpdate(Event $event){
        //get constant values
        $action_insert = self::URL_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        
        $data = $event->getData();
        $this->writeLogs('[Enter in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCodeUpdate with data'.json_encode($data).']');
        $data = $this->prepareApplaneDataUpdateSubCategory($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $final_data = $applane_service->getMongoDataFormatInsert($data,ApplaneConstentInterface::APPLANE_BUSSINESS_SUB_CATEGORY_COLLECTION,$action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $this->writeLogs('[Reponse from applane in Class Utility/ApplaneIntegrationBundle/Event/BussinessCategoryListener and function onBussinessCategoryCodeUpdate for data'.json_encode($data).' and response:'.  json_encode($appalne_decode_resp));
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        
    }
    
    /**
     * function for preparing the data for sub category update on applane
     * @param type $data
     * @return type
     */
    private function prepareApplaneDataUpdateSubCategory($data) {
        $final_data = array();
        $final_data['$set'] = array('name' => $data['name'],'category_id' => array("_id" => (string)$data['category_id']));
        $final_data['_id'] = (string)$data['_id'];
        return $final_data;
    }
    
    
    
    /**
     * function for writing the business category logs
     * @param type $data
     * @return boolean
     */
    private function writeLogs($data) {
        $handler = $this->container->get('monolog.logger.bussiness_category_logs');
        try {
            $handler->info($data);
        } catch (\Exception $ex) {
            
        }
        return true;
    }
}