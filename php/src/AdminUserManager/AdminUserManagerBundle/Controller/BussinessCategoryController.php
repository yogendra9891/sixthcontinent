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

class BussinessCategoryController extends CrudaController {
 
    /**
     *  Batch action for creating the business category on the applane 
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCreateBusinessCategory(ProxyQueryInterface $selectedModelQuery) {
        //$this->getRequest()->getSession()->getFlashBag()->add("success", "Saved to Applane also");
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $parent = $selectedModel->getParent();
            if($parent == 0) {
            $this->categoryCreateOnApplane($selectedModel);
            } else {
               $this->categoryCodeCreateOnApplane($selectedModel); 
            }
        }
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));

    }
    
    /**
     * function thats calls the applane services for creating the business category on applane
     * @param type $selectedModel
     */
    private function categoryCreateOnApplane($selectedModel) {
        $applane_data = $this->prepareApplaneDataCategoryCreate($selectedModel);
    }
    
    /**
     * function for preparing the data for business category create on applane
     * @param type $selectedModel
     */
    private function prepareApplaneDataCategoryCreate($selectedModel) {
        $data = array();
        $data['_id'] = (string)$selectedModel->getId();
        $data['txn_percentage'] = $selectedModel->getTxnPercentage();
        $data['card_percentage'] = $selectedModel->getCardPercentage();
        $data['name'] = trim($selectedModel->getName());
        $event = new FilterDataEvent($data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('bussinesscategory.create', $event);
        $this->addFlash('sonata_flash_success', $this->admin->trans('Business Category Created on applane'));
        
        
    }
    
    /**
     *  Batch action for updating the business category on the applane 
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
     public function batchActionUpdateBusinessCategory(ProxyQueryInterface $selectedModelQuery) {
        //$this->getRequest()->getSession()->getFlashBag()->add("success", "Saved to Applane also");
         
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $parent = $selectedModel->getParent();
            if($parent == 0) {
            $this->updateCreateOnApplane($selectedModel);
            } else {
               $this->categoryCodeUpdateOnApplane($selectedModel); 
            }
        }
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));

    }
    
    /**
     * function thats calls the applane services for up datingthe business category on applane
     * @param type $selectedModel
     */
    private function updateCreateOnApplane($selectedModel) {
        $applane_data = $this->prepareApplaneDataCategoryUpdate($selectedModel);
    }
    
    
    /**
     * function for preparing the data for business category update on applane
     * @param type $selectedModel
     */
    private function prepareApplaneDataCategoryUpdate($selectedModel) {
        $data = array();
        $data['id'] = (string)$selectedModel->getId();
        $data['txn_percentage'] = $selectedModel->getTxnPercentage();
        $data['card_percentage'] = $selectedModel->getCardPercentage();
        $data['name'] = trim($selectedModel->getName());
        $event = new FilterDataEvent($data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('bussinesscategory.update', $event);
        $this->addFlash('sonata_flash_success', $this->admin->trans('Business Category Updated on applane'));
    }
    
    /**
     * function thats calls the applane services for creating the business category code on applane
     * @param type $selectedModel
     */
    private function categoryCodeCreateOnApplane($selectedModel) {
        $applane_data = $this->prepareApplaneDataCategoryCodeCreate($selectedModel);
    }
    
    /**
     * function for preparing the data for business category code create on applane
     * @param type $selectedModel
     */
    private function prepareApplaneDataCategoryCodeCreate($selectedModel) {
        $data = array();
        $data['_id'] = (string)$selectedModel->getId();
        $data['category_id'] = $selectedModel->getParent();
        $data['name'] = trim($selectedModel->getName());
        
        $event = new FilterDataEvent($data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('bussinesscategorycode.create', $event);
        $this->addFlash('sonata_flash_success', $this->admin->trans('Business Sub Category Created on applane'));
    }
    
    /**
     * function thats calls the applane services for updating the business category code on applane
     * @param type $selectedModel
     */
    private function categoryCodeUpdateOnApplane($selectedModel) {
        $applane_data = $this->prepareApplaneDataCategoryCodeUpdate($selectedModel);
    }
    
    /**
     * function for preparing the data for business category code update on applane
     * @param type $selectedModel
     */
    private function prepareApplaneDataCategoryCodeUpdate($selectedModel) {
        $data = array();
        $data['_id'] = (string)$selectedModel->getId();
        $data['category_id'] = $selectedModel->getParent();
        $data['name'] = trim($selectedModel->getName());
        
        $event = new FilterDataEvent($data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('bussinesscategorycode.update', $event);
        $this->addFlash('sonata_flash_success', $this->admin->trans('Business Sub Category Updated on applane'));
    }
    
    
}
