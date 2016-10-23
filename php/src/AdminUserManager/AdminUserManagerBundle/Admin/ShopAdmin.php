<?php

namespace AdminUserManager\AdminUserManagerBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use FOS\UserBundle\Controller\SecurityController as BaseController;
use Newsletter\NewsletterBundle\NewsletterNewsletterBundle;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Container;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Notification\NotificationBundle\NManagerNotificationBundle;

class ShopAdmin extends Admin {

    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @param Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper) {

        $formMapper
                ->add('name', 'text')
                ->add('businessName', 'text')
                ->add('shopStatus', 'text')
                ->add('isActive', 'text')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('id')
                ->add('name')
                ->add('businessName')
                ->add('shopStatus')
                ->add('isActive')
                ->add('creditCardStatus')
        // ->add('toId')
        // ->add('shopId')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->add('name')
                ->add('businessName')
                ->add('shopStatus')
                ->add('isActive')
                ->add('paymentStatus')
                ->add('creditCardStatus')
        ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('name')
                ->add('businessName')
                ->add('shopStatus')
                ->add('isActive')
                ->add('paymentStatus')
                ->add('creditCardStatus')
        ;
    }

    /**
     * Method call after saving the data
     * @param object $data
     */
    public function postPersist($data) {
        
    }

    /**
     * Method call after updating the data
     * @param object $data
     */
    public function postUpdate($data) {
        
    }

    /**
     * Method call before updating the data
     * @param object $data
     */
    public function preUpdate($data) {
        //before update
    }

    public function getBatchActions() {
        // retrieve the default (currently only the delete action) actions
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        // check user permissions
        if ($this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE')) {

            $request = $this->getRequest();
            $data = $request->get('type');

            $actions['shopcreateonapplane'] = array(
                'label' => 'Shop Create On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['shopupdateonapplane'] = array(
                'label' => 'Shop Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['referralupdateonapplane'] = array(
                'label' => 'Referral Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['favupdateonapplane'] = array(
                'label' => 'Fav Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['followerUpdateOnApplane'] = array(
                'label' => 'Follower Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['shopActiveUpdateOnApplane'] = array(
                'label' => 'ShopActive Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['profileImageUpdateOnApplane'] = array(
                'label' => 'ShopImage Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
        }

        return $actions;
    }

}
