<?php
namespace StoreManager\StoreBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use StoreManager\StoreBundle\Utils\MessageFactory as Msg;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationShop;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationCitizen;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use UserManager\Sonata\UserBundle\Entity\UserConnection;
// service method  class
class StoreUpdateService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
  

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
        //$this->request   = $request;
    }
    
    
    /**
     * Get Group Owner ACL code
     * @return int
     */
    public function setStoreContractStatus($store_id) {
       $update_store=$this->em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id'=>$store_id));
       if (count($update_store)) {
            $update_store->setNewContractStatus(1);
            $this->em->persist($update_store);
            $this->em->flush();
            return true;
        }else{
            return false;
        }
        
    }
    
    /**
     * Check store owner
     * @param int $store_id
     * @return boolean
     */
    public function checkStoreOwner($store_id, $user_id) {
        $store_obj = $this->em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id, 'userId' => $user_id, 'role' => 15));
        if ($store_obj) {
            return $store_obj;
        } else {
            return false;
        }
    }
    
    public function checkStoreAffiliation($store_id) {
        $affiliation_obj = $this->em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
                ->findStoreAffiliator($store_id);
        if ($affiliation_obj) {
            return $affiliation_obj;
        } else {
            return false;
        }
    }
    
       /**
     *  function for updating the store affiliation 
     * @param type $user_id
     * @param type $from_id
     * @param type $to_id
     * @param type $affiliation_status
     */
    public function updateStoreAffiliation($user_id, $from_id, $to_id, $affiliation_status) {
        $data = array();
        $this->__createLog('entering in class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation]');
        //get shop owner object
        $user_service = $this->container->get('user_object.service');
        $em = $this->em;
        $store_obj = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $to_id,'isActive' => 1));
        //check if store exist
        if (!$store_obj) {
            $resp_data = new Resp(Msg::getMessage(413)->getCode(), Msg::getMessage(413)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }

        //check user to store relationship
        $store_security = $this->checkUserToStoreSecurity($to_id, $user_id);
        if ($store_security == false) {
            $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        $store_service = $this->container->get('store_manager_store.storeUpdate');
        //check if store is already affiliated
        $already_affiliated = $store_obj->getAffiliationStatus();
        
        //check if store is already gone through the affiliation process
        if($already_affiliated != 0) {
            $resp_data = new Resp(Msg::getMessage(1109)->getCode(), Msg::getMessage(1109)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        
        //check if store is affiliated by a user
        $is_store_affiliated = $this->checkStoreAffiliation($to_id);
        if ($is_store_affiliated != false) {
            $resp_data = new Resp(Msg::getMessage(1109)->getCode(), Msg::getMessage(1109)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        
        //means affiliation approved
        if ($affiliation_status == 1) {            
             $user_detail = $user_service->UserObjectService($from_id);
            //check if user exist
            if (!$user_detail) {
                $resp_data = new Resp(Msg::getMessage(1015)->getCode(), Msg::getMessage(1015)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
                return $resp_data;
            }           
            //check if user is affiliating him self for a shop
            if ($user_id == $from_id) {
                $resp_data = new Resp(Msg::getMessage(1111)->getCode(), Msg::getMessage(1111)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
                return $resp_data;
            }
        
            $affiliation_id = $this->addAfflicationOfStore($to_id, $user_id, $from_id);
            if($affiliation_id) {
            $this->updateStoreInfo($store_obj, $affiliation_status);
            //update affiliation on the applane 
            $this->__createLog("Updating the shop affiliation on the applane");
            //get dispatcher object
            $appalne_data['store_id'] = $to_id;
            $appalne_data['referral_id'] = $from_id;
            $event = new FilterDataEvent($appalne_data); 
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.affiliation', $event);
            
            //connect the users as friends 
            $this->connectUsersAsFriend($user_id,$from_id);
            $this->madeFrieendOnApplane($user_id, $from_id);
            }
        }
        //means affiliation cancel
        if ($affiliation_status == 2) {
            $this->updateStoreInfo($store_obj, $affiliation_status);
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
        $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
        return $resp_data;
    }
    
    /**
     *  function for updating the store affiliation 
     * @param type $user_id
     * @param type $from_id
     * @param type $to_id
     * @param type $affiliation_status
     */
    public function updateUserAffiliation($user_id, $from_id, $to_id, $affiliation_status) {
        $data = array();
        $this->__createLog('entering in class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation]');
        //get shop owner object
        $user_service = $this->container->get('user_object.service');
        $user_obj = $user_service->UserObjectService($user_id);
        if($user_id != $to_id) {
            $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        //check if user exist and active user
        if(isset($user_obj['active']) && $user_obj['active'] != 1) {
            $resp_data = new Resp(Msg::getMessage(1015)->getCode(), Msg::getMessage(1015)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        $em = $this->em;
        //get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
    	
    	$user = $userManager->findUserBy(array('id' => $user_id));
        //check if citizen exist
        if (!$user) {
            $resp_data = new Resp(Msg::getMessage(1015)->getCode(), Msg::getMessage(1015)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        
        $already_affiliated = $user->getAffiliationStatus();
        
        //check if store is already gone through the affiliation process
        if($already_affiliated != 0) {
            $resp_data = new Resp(Msg::getMessage(1112)->getCode(), Msg::getMessage(1112)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        
        //check if citizen is affiliated by a user
        $is_user_affiliated = $user_service->checkUserAffiliation($user_id);
        if ($is_user_affiliated != false) {
            $resp_data = new Resp(Msg::getMessage(1112)->getCode(), Msg::getMessage(1112)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
            return $resp_data;
        }
        
        //means affiliation approved
        if ($affiliation_status == 1) {
            $user_detail = $user_service->UserObjectService($from_id);
            //check if user exist
            if (!$user_detail) {
                $resp_data = new Resp(Msg::getMessage(1015)->getCode(), Msg::getMessage(1015)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
                return $resp_data;
            }
            //check if user is affiliating him self for a shop
            if ($user_id == $from_id) {
                $resp_data = new Resp(Msg::getMessage(1111)->getCode(), Msg::getMessage(1111)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
                return $resp_data;
            }
            $affiliation_id = $this->addAfflicationOfCitizen($to_id, $user_id, $from_id);
            if($affiliation_id) {
            $this->updateCitizenInfo($user, $affiliation_status);
             //update affiliation on the applane 
            $this->__createLog("Updating the citizen affiliation on the applane");
            //get dispatcher object
            $appalne_data['user_id'] = $to_id;
            $appalne_data['referral_id'] = $from_id;
            $event = new FilterDataEvent($appalne_data); 
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('citizen.updateaffiliation', $event);
            
            //connect the users as friends 
            $this->connectUsersAsFriend($user_id,$from_id);
            $this->madeFrieendOnApplane($user_id, $from_id);
            }
        }
        //means affiliation cancel
        if ($affiliation_status == 2) {
            $this->updateCitizenInfo($user, $affiliation_status);
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
        $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateUserAffiliation] with response: ' . (string) $resp_data);
        return $resp_data;
    }

    /**
     *  function for checking the user to store security 
     * @param type $to_id
     * @param type $user_id
     * @return boolean
     */
    private function checkUserToStoreSecurity($to_id, $user_id) {
        $store_service = $this->container->get('store_manager_store.storeUpdate');
        $store_obj = $this->checkStoreOwner($to_id, $user_id);
        if (!$store_obj) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.store_profile');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    /**
     *  function for updating the store info for the affiliation process
     * @param type $store_id
     * @param type $affiliation_status
     */
    private function updateStoreInfo($store_obj, $affiliation_status) {        
        //check if store exist
        $em = $this->em;
        if($store_obj) {
            $store_obj->setAffiliationStatus($affiliation_status);
        }
        try{
            $em->persist($store_obj);
            $em->flush();
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreInfo] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
    }

    /**
     *  function for adding the stoer affiliation
     * @param type $store_id
     * @param type $to_id
     * @param type $from_id
     * @return type
     */
    private function addAfflicationOfStore($store_id, $to_id, $from_id) {
        $em = $this->em;
        $store_affiliation = new AffiliationShop();
        $time = new \DateTime('now');
        $store_affiliation->setFromId($from_id);
        $store_affiliation->setToId($to_id);
        $store_affiliation->setShopId($store_id);
        $store_affiliation->setCreatedAt($time);
        try {
            $em->persist($store_affiliation);
            $em->flush();
            return $store_affiliation->getId();
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [addAfflicationOfStore] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
    }
    
    /**
     *  function for adding the stoer affiliation
     * @param type $store_id
     * @param type $to_id
     * @param type $from_id
     * @return type
     */
    private function addAfflicationOfCitizen($store_id, $to_id, $from_id) {
        $em = $this->em;
        $citizen_affiliation = new AffiliationCitizen();
        $time = new \DateTime('now');
        $citizen_affiliation->setFromId($from_id);
        $citizen_affiliation->setToId($to_id);
        $citizen_affiliation->setCreatedAt($time);
        try {
            $em->persist($citizen_affiliation);
            $em->flush();
            return $citizen_affiliation->getId();
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
    }
    
    /**
     *  function for updating the citizen info
     * @param type $user
     * @param type $affiliation_status
     */
    private function updateCitizenInfo($user, $affiliation_status) {
        //check if user exist
        $data = array();
        $userManager = $this->container->get('fos_user.user_manager');
        if($user) {
            $user->setAffiliationStatus($affiliation_status);
        }
        try{
            $userManager->updateUser($user);
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [updateStoreAffiliation] with response: ' . $ex->getMessage());
            Utility::createResponse($resp_data);
        }
    }
    
    /**
     *  function for making the users friends
     * @param type $from_id
     * @param type $to_id
     */
    private function connectUsersAsFriend($from_id, $to_id) {
        $this->__createLog('Entering in class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [connectUsersAsFriend]');
        $time = new \DateTime('now');
        $em = $this->em;
        //get the object if users are already try to become fiends of each other 
        $friend_obj = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->findOneBy(array('connectFrom' => $from_id, 'connectTo' => $to_id));

        $friend_obj1 = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->findOneBy(array('connectFrom' => $to_id, 'connectTo' => $from_id));

        //check if users are already send friend requests or they are not connected either
        if (count($friend_obj) == 0 && count($friend_obj1) == 0) {
            $user_connection = new UserConnection();
            //call function for making the users as personal and professional friends
            $user_connection = $this->madeUsersFriend($user_connection,$from_id,$to_id,$time);
            try {
                $em->persist($user_connection);
                $em->flush();
            } catch (\Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [connectUsersAsFriend] with response: ' . $ex->getMessage());
            }
        } else if (count($friend_obj) > 0 && count($friend_obj1) > 0) {
            //set friend ship status if user already made a friend request
            $friend_obj = $this->setFrienshipStatus($friend_obj);
            $friend_obj1 = $this->setFrienshipStatus($friend_obj1);
            try {
                $em->persist($friend_obj);
                $em->persist($friend_obj1);
                $em->flush();
            } catch (\Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [connectUsersAsFriend] with response: ' . $ex->getMessage());
            }
        } else if (count($friend_obj) > 0) {
            //call function for making the users as personal and professional friends
            $user_connection = $this->madeUsersFriend($friend_obj,$from_id,$to_id,$time);
            try {
                $em->persist($user_connection);
                $em->flush();
            } catch (\Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [connectUsersAsFriend] with response: ' . $ex->getMessage());
            }
        } else if (count($friend_obj1) > 0) {
            //call function for making the users as personal and professional friends
            $user_connection = $this->madeUsersFriend($friend_obj1,$from_id,$to_id,$time);
            try {
                $em->persist($user_connection);
                $em->flush();
            } catch (\Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
                $this->__createLog('Exiting from class [StoreManager\StoreBundle\Services\StoreUpdateService] and function [connectUsersAsFriend] with response: ' . $ex->getMessage());
            }
        }
    }

    /**
     * function for setting up the friendship status
     * @param type $connection_object
     * @return type
     */
    private function setFrienshipStatus($connection_object) {

        $professional_request = $connection_object->getProfessionalRequest();
        $personal_request = $connection_object->getPersonalRequest();
        //check if professional request send
        if ($professional_request == 1) {
            $connection_object->setProfessionalStatus(1);
            $connection_object->setMsg('request accepted');
            $connection_object->setStatus(1);
        }
        //send if personal request send
        if ($personal_request == 1) {
            $connection_object->setPersonalStatus(1);
            $connection_object->setMsg('request accepted');
            $connection_object->setStatus(1);
        }

        return $connection_object;
    }

    /**
     *  function for setting up the parameters for making user friends
     * @param type $user_connection
     * @return type
     */
    private function madeUsersFriend($user_connection,$from_id,$to_id,$time) {
        //setting the different parameters for connecting users 
        $user_connection->setConnectFrom($from_id);
        $user_connection->setConnectTo($to_id);
        $user_connection->setCreated($time);
        $user_connection->setMsg('request accepted');
        $user_connection->setPersonalRequest(1);
        $user_connection->setPersonalStatus(1);
        $user_connection->setProfessionalRequest(1);
        $user_connection->setProfessionalStatus(1);
        $user_connection->setStatus(1);
        return $user_connection;
    }
    
    /**
     *  function for calling the event dispacher that makes the user frisnd on the applane 
     * @param type $to_id
     * @param type $from_id
     */
    private function madeFrieendOnApplane($to_id,$from_id) {
        // call applane service 
        //from login user to friend
        $appalne_data = array();
        $appalne_data['register_id'] = $to_id;
        $appalne_data['friend_data'] = $from_id;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.addfriend', $event);
        
        //from friend to login user
        $appalne_data['register_id'] = $from_id;
        $appalne_data['friend_data'] = $to_id;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher->dispatch('citizen.addfriend', $event);
    } 
    
    
    
    

}