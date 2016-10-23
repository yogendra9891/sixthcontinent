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
use ExportManagement\ExportManagementBundle\Entity\PaymentExport;

class PaymentExportUserAdmin extends Admin {

    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;
    public  $s3_url = '';
    public  $shop_weekly_transaction = 'uploads/transaction/shopweeklytransaction';
    public  $citizenincomeutilized   = 'uploads/transaction/citizenincomeutilized';
    public  $shopdailyregistration   = 'uploads/transaction/shopdailyregistration';
    public  $shopdailypay            = 'uploads/transaction/shopdailypay';
    public  $shop_pending_registration = 'uploads/transaction/sixthcontinentregistration';
    public  $gift_card_daily_transaction = 'uploads/transaction/giftcardexporttransaction';

    /**
     * @param Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper) {

        $formMapper
                ->add('id', 'text')
                ->add('type', 'text')
                ->add('date', 'text')
                ->add('filename', 'text')


        ;
    }
    protected function configureRoutes(RouteCollection $collection) {
        $collection->remove('create');
        $collection->remove('edit');
        //$collection->remove('delete');
    }
    // Fields to be shown on lists
       protected function configureListFields(ListMapper $listMapper)
       {
           $listMapper
               ->addIdentifier('id', null)
               ->add('filename' ,'string', array('template' => 'AdminUserManagerAdminUserManagerBundle:CRUD:list_filename.html.twig'))
               ->add('TypeAsString', null, array('sortable' => true, 'label' => 'Type'))
               ->add('date','date')
              
           ;
           $s3_server_url = $this->getS3BaseUri(); //getting the s3 server url..
           $this->s3_url  = $s3_server_url;
           $this->shop_weekly_transaction   = $this->shop_weekly_transaction;
           $this->citizenincomeutilized     = $this->citizenincomeutilized;
           $this->shopdailyregistration     = $this->shopdailyregistration;
           $this->shopdailypay              = $this->shopdailypay;
           $this->shop_pending_registration = $this->shop_pending_registration;
           $this->gift_card_daily_transaction = $this->gift_card_daily_transaction;
           
       }
       
    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
         ->add('type', 'doctrine_orm_string', array(), 'choice', array('choices' => PaymentExport::getTypeList()))
         ->add('filename')
         ->add('date', 'doctrine_orm_date', array('input_type' => 'date'));
    }


    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('type')
                ->add('date')
                ->add('filename')
                ;
    }

    public function getBatchActions() {

        $actions = parent::getBatchActions();
        unset($actions['delete']);
        return $actions;
    }

    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();       
    }
    
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        $container = $this->getConfigurationPool()->getContainer();
        //finding the base path of aws and bucket name
        $aws_base_path = $container->getParameter('aws_base_path');
        $aws_bucket = $container->getParameter('aws_bucket');
        $full_path = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
}
