<?php

namespace AdminUserManager\AdminUserManagerBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Container;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Notification\NotificationBundle\NManagerNotificationBundle;

class SelfRelationShipTypeCodeByLangAdmin extends Admin {

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
                ->add('relationShipCode', 'text')
                ->add('langCode', 'text')
                ->add('relationShipName','text');
        

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('relationShipCode')
                ->add('langCode')
                ->add('relationShipName')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->addIdentifier('relationShipCode')
                ->addIdentifier('langCode')
                ->add('relationShipName')
                ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('relationShipCode')
                ->add('langCode')
                ->add('relationShipName')
        ;
    }

}
