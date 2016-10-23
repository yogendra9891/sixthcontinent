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

class ShopTransactionPaymentAdmin extends Admin {

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


    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
        ->add('shopId')
        ->add('status')
        ->add('mode')
        ->add('payType')
        ->add('createdAt')
        ->add('paymentDate')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->add('shopId')
                ->add('pendingIds')
                ->add('pendingAmount')
                ->add('totalAmount')
                ->add('contractTxnId')
                ->add('payType')
                ->add('status')
                ->add('comment')
                ->add('mode')
                ->add('createdAt')
                ->add('paymentDate')
                ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('shopId')
                ->add('pendingIds')
                ->add('pendingAmount')
                ->add('totalAmount')
                ->add('contractTxnId')
                ->add('payType')
                ->add('status')
                ->add('comment')
                ->add('mode')
                ->add('createdAt')
                ->add('paymentDate');
    }
    
    protected function configureRoutes(RouteCollection $collection) {
        //$collection->add('unpublish', $this->getRouterIdParameter() . '/unpublish');
        $collection->remove('create');
        $collection->remove('edit');
        //$collection->add('activate', $this->getRouterIdParameter().'/activate');
        //$collection->add('documents', $this->getRouterIdParameter().'/documents');
        //$collection->remove('delete');
    }
    
    public function getBatchActions() {
        // retrieve the default (currently only the delete action) actions
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        return $actions;
    }

}
