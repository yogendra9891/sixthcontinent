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

class AffiliationCitizenAdmin extends Admin {

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
                ->add('fromId', 'text')
                ->add('toId', 'text')
                ->add('createdAt', 'date')
                ->add('applane', 'checkbox', array(
                    'label' => 'Send To Applane',
                    'required' => false,
                    'mapped' => false,
        ));

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
        ->add('fromId')
        ->add('toId')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->add('fromId')
                ->add('toId');
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('fromId')
                ->add('toId');
    }

    /**
     * Method call after saving the data
     * @param object $data
     */
    public function postPersist($data) {
        $datas = $_POST;
        $applane = 0;
        foreach($datas as $key => $value){
            if(isset($datas[$key]['applane'])){
            $applane = $datas[$key]['applane'];
            }
        }
        if($applane == 1){
        $container = NManagerNotificationBundle::getContainer();
        //save to applane
        //get dispatcher object
        $appalne_data = array();
        $appalne_data['user_id'] = $data->gettoId();
        $appalne_data['referral_id'] = $data->getfromId();
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.updateaffiliation', $event);
        $this->getRequest()->getSession()->getFlashBag()->add("success", "Saved to Applane also");
        }
    }

    /**
     * Method call after updating the data
     * @param object $data
     */
    public function postUpdate($data) {
        $datas = $_POST;
        $applane = 0;
        foreach($datas as $key => $value){
            if(isset($datas[$key]['applane'])){
            $applane = $datas[$key]['applane'];
            }
        }
        if($applane == 1){
        $container = NManagerNotificationBundle::getContainer();
        //save to applane
        //get dispatcher object
        $appalne_data = array();
        $appalne_data['user_id'] = $data->gettoId();
        $appalne_data['referral_id'] = $data->getfromId();
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.updateaffiliation', $event);
        $this->getRequest()->getSession()->getFlashBag()->add("success", "Saved to Applane also");
        }
    }

    /**
     * Method call before updating the data
     * @param object $data
     */
    public function preUpdate($data) {
         //before update
    }



}
