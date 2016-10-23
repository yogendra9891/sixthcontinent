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

class CountryCodeAdmin extends Admin {

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
                ->add('countryName', 'text')
                ->add('countryCode', 'text')
                ->add('languageCode','text')
                ->add('status', 'integer')
                ->add('image', 'text',array('required' => false))
                ->add('imageThumb', 'text',array('required' => false))
                ->add('timezone', 'text');
        

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('countryName')
                ->add('countryCode')
                ->add('languageCode')
                ->add('status')
                ->add('timezone')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->addIdentifier('countryName')
                ->add('countryCode')
                ->add('languageCode')
                ->add('image')
                ->add('imageThumb')
                ->add('timezone')
                ->add('status')
                
                ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('countryName')
                ->add('countryCode')
                ->add('languageCode')
                ->add('image')
                ->add('imageThumb')
                ->add('timezone')
                ->add('status')
        ;
    }

}
