<?php

namespace AdminUserManager\AdminUserManagerBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as CrudaController;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfileRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Notification\NotificationBundle\Document\UserNotifications;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sonata\AdminBundle\Export;
use Exporter\Writer\XlsWriter;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class ShopCRUDController extends CrudaController {
    
    /**
     * Create shop on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionShopcreateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->shopCreateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Update shop on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionShopupdateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->shopUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Update shop referral on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionReferralupdateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->referralUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Update shop referral on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionFavupdateonapplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->favUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Update shop delete on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionShopActiveUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->shopActiveUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    
    /**
     * Update shop followers on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionFollowerUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->shopFollowerUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
     /**
     * Update shop image on applane
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionProfileImageUpdateOnApplane(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->profileImageUpdateOnApplane($selectedModel);
        }
        $this->addFlash('sonata_flash_success', 'Updated successfully');
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Create on applane
     * @param type $selectedModel
     */
    public function shopCreateOnApplane($selectedModel)
    {
        $shop_data = $this->prePareApplaneData($selectedModel);
        $event = new FilterDataEvent($shop_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.create', $event);
    }
    
    /**
     * Update on applane
     * @param type $selectedModel
     */
    public function shopUpdateOnApplane($selectedModel)
    {
        $shop_data = $this->prePareApplaneData($selectedModel);
        $event = new FilterDataEvent($shop_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.update', $event);
    }
    
    /**
     * Prepare applane data
     * @param obect $selectedModel
     */
    public function prePareApplaneData($selectedModel)
    {     
        $data['store_id'] = $selectedModel->getId();
        //get shop owner
        $shop_owner = $this->getShopOwner($data['store_id']);
        $shop_referral = $this->getShopReferral($data['store_id']);
        $data['sale_catid'] = $selectedModel->getSaleCatid();
        $data['sale_country'] = $selectedModel->getSaleCountry();
        $data['user_id'] = $shop_owner;
        $data['business_address'] = $selectedModel->getBusinessAddress();
        $data['sale_phone_number'] = $selectedModel->getSalePhoneNumber();
        $data['name'] = $selectedModel->getName();
        $data['map_place'] = $selectedModel->getMapPlace();
        if($shop_referral > 0){
        $data['referral_id'] = $shop_referral;
        }
        $data['sale_region'] = $selectedModel->getSaleRegion();
        $data['sale_province'] = $selectedModel->getSaleProvince();
        $data['sale_zip'] = $selectedModel->getSaleZip();
        $data['sale_address'] = $selectedModel->getSaleAddress();
        $data['sale_email'] = $selectedModel->getSaleEmail();
        $data['latitude'] = $selectedModel->getLatitude();
        $data['longitude'] = $selectedModel->getLongitude();
        $data['description'] = $selectedModel->getDescription();
        //$data['sale_subcatid'] = $selectedModel->getSaleSubcatid();
        return $data;
    }
    
   /**
    * Get shop owner id.
    * @param int $shop_id
    * @return int
    */
    public function getShopOwner($shop_id) {
    //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $shop_owner = 0;
        $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId' => $shop_id, 'role' => 15));

        if($store_obj){
            $shop_owner = $store_obj->getUserId();
        }
        return $shop_owner;
    }
    
    /**
    * Get shop referral id.
    * @param int $shop_id
    * @return int
    */
    public function getShopReferral($shop_id) {
    //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $referral_id = 0;
        $referral_obj = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
                    ->findOneBy(array('shopId' => $shop_id));

        if($referral_obj){
            $referral_id = $referral_obj->getFromId();
        }
        return $referral_id;
    }
    
    /**
     * 
     * @param type $shop_id
     */
    public function referralUpdateOnApplane($selectedModel)
    {
        $store_id = $selectedModel->getId();
        $shop_referral = $this->getShopReferral($store_id);
        $appalne_data = array();
        $appalne_data['store_id'] = $store_id;
        $appalne_data['referral_id'] = $shop_referral;
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.affiliation', $event);
        return true;
    }
    
    /**
     * Shop fav update on applane
     * @param type $selectedModel
     */
    public function favUpdateOnApplane($selectedModel)
    {
        $fav_objs = array();
        $fav_users = array();
        $store_id = $selectedModel->getId();
        $em = $this->container->get('doctrine')->getManager();
        $fav_id = 0;
        $fav_objs = $em->getRepository('StoreManagerStoreBundle:Favourite')
                    ->getSingleShopFavs($store_id);
        
        if($fav_objs){
            foreach($fav_objs as $fav_obj){
            //get fav object
            $fav_users_id = $fav_obj['userId'];
            $fav_id = $store_id."_".$fav_users_id;
            $appalne_data['id'] = $fav_id;
            $appalne_data['user_id'] = $fav_users_id;
            $appalne_data['store_id'] = $store_id;
            $event = new FilterDataEvent($appalne_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.favourite', $event);
            }
        }
        
    }
    
    /**
     * Update shop delete on applane
     * @param type $selectedModel
     */
    public function shopActiveUpdateOnApplane($selectedModel)
    {
        $store_id = $selectedModel->getId();
        $is_active = $selectedModel->getIsActive();
        $appalne_data['shop_id'] = $store_id;
        $appalne_data['is_active'] = ($is_active == 1) ? 0 : 1;
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.deleteupdate', $event);
    }
    
    /**
     * Update shop follower on applane
     * @param type $selectedModel
     */
    public function shopFollowerUpdateOnApplane($selectedModel)
    {
        $follower_objs = array();
        $store_id = $selectedModel->getId();
        $em = $this->container->get('doctrine')->getManager();
        $follower_objs = $em->getRepository('StoreManagerStoreBundle:ShopFollowers')
                    ->getSingleShopFollowers($store_id);
        if($follower_objs){
            foreach($follower_objs as $follower_obj){
            //get follower object
            $follow_users_id = $follower_obj['userId'];
            $follow_id = $store_id."_".$follow_users_id;
            $appalne_data['id'] = $follow_id;
            $appalne_data['user_id'] = $follow_users_id;
            $appalne_data['shop_id'] = $store_id;
            $event = new FilterDataEvent($appalne_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.follow', $event);
            }
        }
    }
    
    /**
     * Update profile image on applane.
     * @param type $selectedModel
     */
    public function profileImageUpdateOnApplane($selectedModel)
    {
        $original_image = ''; //initialise original image
        $thumb_image = ''; //initialise thumb image
        $store_id = $selectedModel->getId();
        $user_service = $this->container->get('user_object.service');
        $shop_object = $user_service->getStoreObjectService($store_id);
        if($shop_object){
           $original_image = $shop_object['original_path'];
           $thumb_image = $shop_object['thumb_path'];
        }
        $appalne_data['profile_img'] = $thumb_image;
        $appalne_data['shop_id'] = $store_id;
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.updateprofileimg', $event);
    }
}
