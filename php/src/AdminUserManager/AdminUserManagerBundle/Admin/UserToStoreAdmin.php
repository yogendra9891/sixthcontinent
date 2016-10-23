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

class UserToStoreAdmin extends Admin {

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
                ->add('storeId', 'text')
                ->add('userId', 'text')
                ->add('createdAt');
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('storeId')
                ->add('userId')
               
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->add('storeId')
                ->add('userId')
        ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
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

   

}
