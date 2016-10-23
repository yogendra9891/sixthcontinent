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

class FamilyRelationShipTypeByLangAdmin extends Admin {

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
                ->add('relationshipCode', 'text')
                ->add('langCode', 'text')
                ->add('relationshipName','text');
        

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('relationshipCode')
                ->add('langCode')
                ->add('relationshipName')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->addIdentifier('relationshipCode')
                ->addIdentifier('langCode')
                ->add('relationshipName')
                ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('relationshipCode')
                ->add('langCode')
                ->add('relationshipName')
        ;
    }

}
