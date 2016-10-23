<?php

namespace CardManagement\CardManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use CardManagement\CardManagementBundle\Entity\Waivers;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

// validate the data.like iban, vatnumber etc
class WaiverService {

    protected $em;
    protected $container;
    const FAILED = "FAILED";
    CONST SHOP_REG_FEE_WAIVER = 'SHOP_REG_FEE_WAIVER';
    CONST WAIVER_TYPE = 'SHOP';
    CONST SHOP_SUBSCRIPTION_FEE_WAIVER = 'SHOP_SUBSCRIPTION_FEE_WAIVER';
    CONST SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER = 'SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER';
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em, Container $container) {
        $this->em = $em;
        $this->container = $container;
    }
    
    /**
     * Get waiver status
     * @param string $waiver_type
     * @param date $entity_date
     */
    public function getWaiverStatus($waiver_type, $entity_date)
    {   $serializer = $this->container->get('serializer');
        $date_json = $serializer->serialize($entity_date, 'json'); //convert documnt object to json string
        $this->__createLog('Enter In class [CardManagement\CardManagementBundle\Services\WaiverService] function [getWaiverStatus] With Waiver_type:'.$waiver_type.' date:'.$date_json);
        $em = $this->em;
        try{
        $waiver_value = $this->container->getParameter($waiver_type);
        }catch (\Exception $ex) {
           $waiver_value = 0;
        }
        if($waiver_value == 0){
            return 0;
        } 
        //handling for waiver status
        $waiver_result = $em
                ->getRepository('CardManagementBundle:WaiverOptions')
                ->CheckWaiverStatus($waiver_type, $entity_date);
        if(!$waiver_result){
            $this->__createLog('Exiting From class [CardManagement\CardManagementBundle\Services\WaiverService] function [getWaiverStatus] With Message: No result found');
            return 0;  //no result found
        }
        //get status
        //$waiver_status = $waiver_result['status'];
        $waiver_status = $waiver_result;
        $this->__createLog('Exiting From class [CardManagement\CardManagementBundle\Services\WaiverService] function [getWaiverStatus] With Waiver status: '.Utility::encodeData($waiver_result));
        return $waiver_status;
    }
    
    /**
     * Add waivers
     * @param int $waiver_id
     * @param int $shop_id
     * @param string $waiver_type
     */
    public function addWaiver($waiver_id, $shop_id, $waiver_type)
    {
        $this->__createLog('Enter In class [CardManagement\CardManagementBundle\Services\WaiverService] function [addWaiver] With Waiver_id:'.$waiver_id.' shop_id:'.$shop_id.' waiver_type:'.$waiver_type);
        try{
            $em = $this->em;
            $createdAt = new \DateTime('now');
            $waivers = new Waivers();
            $waivers->setItemId($shop_id);
            $waivers->setItemType($waiver_type);
            $waivers->setWaverId($waiver_id);
            $waivers->setCreatedAt($createdAt);
            $em->persist($waivers);
            $em->flush();
        } catch (\Exception $ex) {
            $this->__createLog('Exiting From class [CardManagement\CardManagementBundle\Services\WaiverService] function [addWaiver] With Exception:'.$ex->getMessage());
        }
        return true;
    }
    
    
    /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLog($monolog_req, $monolog_response = array()){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.waiver_log');
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Check for shop waiver settings
     * @return int
     */
    public function checkRegistrationWaiverStatus($shop_id)
    {
        $this->__createLog('Enter In class [RestStoreController] function [checkRegistrationWaiverStatus] With shop_id:'.$shop_id);
        $em = $this->em;
        $payment_status = 0;
        try {
            $date = new \DateTime("now");
            $waiver_obj = $this->getWaiverStatus(self::SHOP_REG_FEE_WAIVER, $date);
            if(!$waiver_obj){
                $this->__createLog('Exiting From class [RestStoreController] function [checkRegistrationWaiverStatus] With message: No options found for type '.self::SHOP_REG_FEE_WAIVER);
                return true; //no waiver options found
            }
            $payment_status = $waiver_obj['status'];
            $waiver_id = $waiver_obj['id'];
            //get shop object
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => $shop_id));
            if ($store) {
                $store->setPaymentStatus($payment_status);
                    $em->persist($store);
                    $em->flush();
                    $addwaiver_obj = $this->addWaiver($waiver_id, $shop_id, self::WAIVER_TYPE);
                    //update on applane
                    //$this->updateOnApplaneRegistration($shop_id);
                    $this->__createLog('Exiting From class [RestStoreController] function [checkRegistrationWaiverStatus]');
            }
        } catch (\Exception $ex) {   
             $this->__createLog('Exiting From class [RestStoreController] function [checkRegistrationWaiverStatus] With Exception :'.$ex->getMessage());
        }
            
        return true;
    }
    
    /**
     * Update on applane for shop registration
     * @param type $shopid
     */
    public function updateOnApplaneRegistration($shopid) {
        $applane_data['shop_id'] = $shopid;
        //get dispatcher object
        $event = new FilterDataEvent($applane_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.registrationfeeupdate', $event);
    }
    
    /**
     * Check for shop waiver settings
     * @return int
     */
    public function checkSubscriptionWaiverStatus($shop_id)
    {
        $this->__createLog('Enter In class [RestStoreController] function [checkSubscriptionWaiverStatus] With shop_id:'.$shop_id);
        $em = $this->em;
        $subscription_status = 0;
        try {
            $date = new \DateTime("now");
            $waiver_obj = $this->getWaiverStatus(self::SHOP_SUBSCRIPTION_FEE_WAIVER, $date);
            if(!$waiver_obj){
                $this->__createLog('Exiting From class [RestStoreController] function [checkSubscriptionWaiverStatus] With message: No options found for type '.self::SHOP_SUBSCRIPTION_FEE_WAIVER);
                return true; //no waiver options found
            }
            $subscription_status = $waiver_obj['status'];
            $waiver_id = $waiver_obj['id'];
            //get shop object
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => $shop_id));
            if ($store) {
                $store->setIsSubscribed($subscription_status);
                    $em->persist($store);
                    $em->flush();
                    $this->addWaiver($waiver_id, $shop_id, self::WAIVER_TYPE);
                    //update on applane
                    //$this->updateOnApplaneSusbcription($shop_id);
                    $this->__createLog('Exiting From class [RestStoreController] function [checkSubscriptionWaiverStatus]');
            }
        } catch (\Exception $ex) {
            $this->__createLog('Exiting From class [RestStoreController] function [checkSubscriptionWaiverStatus] With Exception :'.$ex->getMessage());
        }
            
        return true;
    }
    
    /**
     * Update on applane for shop subscription
     * @param type $shopid
     */
    public function updateOnApplaneSusbcription($shopid) {
        $applane_data['shop_id'] = $shopid;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $susbcription_id = $applane_service->onShopSubscriptionAddAction($shopid);
        return $susbcription_id;
    }
    
    
     /**
     * Add waivers
     * @param int $waiver_id
     * @param int $shop_id
     * @param string $waiver_type
     */
    public function addMultipleWaiver($waiver_id, $shop_ids, $waiver_type)
    {
        $this->__createLog('Enter In class [CardManagement\CardManagementBundle\Services\WaiverService] function [addMultipleWaiver] With Waiver_id:'.$waiver_id.' shop_ids:'.Utility::encodeData($shop_ids).' waiver_type:'.$waiver_type);
        try{
            $em = $this->em;
            $createdAt = new \DateTime('now');
            foreach($shop_ids as $shop_id){ //for multiple shop
            $waivers = new Waivers();
            $waivers->setItemId($shop_id);
            $waivers->setItemType($waiver_type);
            $waivers->setWaverId($waiver_id);
            $waivers->setCreatedAt($createdAt);
            $em->persist($waivers);
            }
            $em->flush();
        } catch (\Exception $ex) {
            $this->__createLog('Exiting From class [CardManagement\CardManagementBundle\Services\WaiverService] function [addWaiver] With Exception:'.$ex->getMessage());
        }
        return true;
    }
}
