<?php

namespace AdminUserManager\AdminUserManagerBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as CrudaController;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class UserCRUDController extends CrudaController {

    /**
     * Create citizen on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizencreateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenCreateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen created on applane successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }

    /**
     * Update citizen on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenupdateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Record Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }

    /**
     * CItizen refferal on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenreferralupdateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenReferralUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Affiliation Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * CItizen follower on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCreateCitizenFollowersOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenFollowerCreateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Follower Added Successfully on Applane');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Add citizen friends on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenFriendsUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenFriendUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Friends Added Successfully on Applane');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * remove CItizen friends on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionRemoveCitizenFriendsOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenFriendRemoveOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Friends removed Successfully from Applane');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * remove CItizen friends on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenFollowersUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenFollowerUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Followers Successfully Update on Applane');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * update profile pic in applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenProfilePicUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->citizenProfilePicUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Citizen Profile Image Successfully Update on Applane');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }

    /**
     * Create CItizen on applane
     * @param type $selectedModel
     */
    public function citizenCreateOnApplane($selectedModel) {
        $shop_data = $this->prepareApplaneDataCitizenCreate($selectedModel);
        $event = new FilterDataEvent($shop_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.register', $event);
    }

    /**
     * Update citizen on applane
     * @param type $selectedModel
     */
    public function citizenUpdateOnApplane($selectedModel) {
        $shop_data = $this->prePareApplaneDataCitizenUpdate($selectedModel);
        $event = new FilterDataEvent($shop_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.update', $event);
    }

    /**
     * Prepare applane data for citizen creation
     * @param obect $selectedModel
     */
    public function prepareApplaneDataCitizenCreate($selectedModel) {
        $data['register_id'] = $selectedModel->getId();
        //get shop owner
        $citizen_referral = $this->getCitizenReferral($data['register_id']);
        $data['country'] = $selectedModel->getCountry();
        $data['email'] = $selectedModel->getEmail();
        $data['firstname'] = $selectedModel->getFirstname();
        $data['lastname'] = $selectedModel->getLastname();
        $data['gender'] = $selectedModel->getGender();
        if ($citizen_referral > 0) {
            $data['referral_id'] = $citizen_referral;
        }
        return $data;
    }

    /**
     * Prepare applane data for citizen update
     * @param obect $selectedModel
     */
    public function prePareApplaneDataCitizenUpdate($selectedModel) {
        $data['user_id'] = $selectedModel->getId();
        $citizen_data = $this->getCitizenUser($data['user_id']);
        //get shop owner
        $data['country'] = $selectedModel->getCountry();
        $data['firstname'] = $selectedModel->getFirstname();
        $data['lastname'] = $selectedModel->getLastname();
        $data['gender'] = $selectedModel->getGender();
        $data['address'] = '';
        $data['map_place'] = '';
        //ceh kif record exist in the citizenuser table
        if (count($citizen_data) > 0) {
            $data['address'] = $citizen_data->getAddress();
            $data['map_place'] = $citizen_data->getMapPlace();
            $data['latitude'] = $citizen_data->getLatitude();
            $data['longitude'] = $citizen_data->getLongitude();
            $data['city'] = $citizen_data->getCity();
        }

        return $data;
    }

    /**
     * prepare applane data for the citizen refferal update
     * @param type $selectedModel
     * @return boolean
     */
    public function citizenReferralUpdateOnApplane($selectedModel) {
        $citizen_id = $selectedModel->getId();
        $citizen_referral = $this->getCitizenReferral($citizen_id);
        $appalne_data = array();
        $appalne_data['user_id'] = $citizen_id;
        $appalne_data['referral_id'] = $citizen_referral;
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.updateaffiliation', $event);
        return true;
    }
    
    /**
     * function for creating the followers on applane
     * @param type $selectedModel
     */
    public function citizenFollowerCreateOnApplane($selectedModel) {
        $appalne_data = array();
        $appalne_data['sender_id'] = $selectedModel->getSenderId();
        $appalne_data['to_id'] = $selectedModel->getToId();
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.follow', $event);
        return true;
    }
    
    /**
     * function for updating the friends on applane
     * @param type $selectedModel
     */
    public function citizenFriendUpdateOnApplane($selectedModel) {
        //get citizen friends 
        $friends = $this->getCitizensFriends($selectedModel->getId());
        //check if users are friends in the symfony database 
        foreach ($friends as $friend) {
        $appalne_data = array();
        $appalne_data['register_id'] = $selectedModel->getId();
        $appalne_data['friend_data'] = $friend['user_id'];
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.addfriend', $event);
        //made friends in applane
        $appalne_data1['register_id'] = $friend['user_id'];
        $appalne_data1['friend_data'] = $selectedModel->getId();
        $event1 = new FilterDataEvent($appalne_data1);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.addfriend', $event1);
        }
        return true;
    }
    
    /**
     *  function for getting the list of all the friends for a user 
     * @param type $citizen_id
     * @return type
     */
    public function getCitizensFriends($citizen_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        
        //fire the query in User Repository
        $response = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllFriendsType($citizen_id, '', 0, 0,1);
        return $response;
    }
    
    
    /**
     * function for removing the friends on applane
     * @param type $selectedModel
     */
    public function citizenFriendRemoveOnApplane($selectedModel) {
        $status = $selectedModel->getStatus();
        if($status == 0) {
        $appalne_data = array();
        $appalne_data['register_id'] = $selectedModel->getConnectTo();
        $appalne_data['friend_id'] = $selectedModel->getConnectFrom();
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.deletefriend', $event);
        //made friends in applane
        $appalne_data1['register_id'] = $selectedModel->getConnectFrom();
        $appalne_data1['friend_id'] = $selectedModel->getConnectTo();
        $event1 = new FilterDataEvent($appalne_data1);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.deletefriend', $event1);
        }
        return true;
    }
    
    
    /**
     * function for update citizen followers on applane
     * @param type $selectedModel
     */
    public function citizenFollowerUpdateOnApplane($selectedModel) {
        //get citizen friends 
        $followers = $this->getCitizensFollowers($selectedModel->getId());
        //check if users are friends in the symfony database 
        foreach ($followers as $follower) {
        $appalne_data = array();
        $appalne_data['sender_id'] = $follower['id'];
        $appalne_data['to_id'] = $selectedModel->getId();
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.follow', $event);
        }
        return true;
    }
    
    /**
     * function for update citizen profile image on applane
     * @param type $selectedModel
     */
    public function citizenProfilePicUpdateOnApplane($selectedModel) {
        //get citizen friends 
        $user_data = $this->getCitizenData($selectedModel->getId());
        if(isset($user_data['profile_image_thumb']) && $user_data['profile_image_thumb'] != '') {
        $appalne_data = array();
        $appalne_data['user_id'] = $selectedModel->getId();
        $appalne_data['profile_img'] = $user_data['profile_image_thumb'];
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.updateprofileimg', $event);
        }
        return true;
    }
    
    
    /**
     *  get citizen followers 
     * @param type $citizen_id
     * @return type
     */
    public function getCitizensFollowers($citizen_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        //check if user has already connected
        $user_cons = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowers($citizen_id, 0, 5);
        return $user_cons;
    }
    
    /**
     * Get Citizen referral id.
     * @param int $citizen_id
     * @return int
     */
    public function getCitizenReferral($citizen_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $referral_id = 0;
        $referral_obj = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                ->findOneBy(array('toId' => $citizen_id));
        if ($referral_obj) {
            $referral_id = $referral_obj->getFromId();
        }
        return $referral_id;
    }

    /**
     * Get citizen data
     * @param int $citizen_id
     * @return int
     */
    public function getCitizenUser($citizen_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $citizen_data = (object) array();
        $citizen_obj = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId' => $citizen_id));
        if ($citizen_obj) {
            $citizen_data = $citizen_obj;
        }
        return $citizen_data;
    }
    
    
    /**
     * Get citizen data
     * @param int $citizen_id
     * @return int
     */
    public function getCitizenData($citizen_id) {
         //find user object service..
        $user_service = $this->container->get('user_object.service');
        //get user profile and cover images..
        $user_obj = $user_service->UserObjectService($citizen_id);
        return $user_obj;
    }

}
