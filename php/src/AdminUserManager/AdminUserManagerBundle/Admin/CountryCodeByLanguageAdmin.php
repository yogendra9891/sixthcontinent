<?php

namespace AdminUserManager\AdminUserManagerBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Container;

class CountryCodeByLanguageAdmin extends Admin {

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
                ->add('countryCode', 'text')
                ->add('langCode', 'text')
                ->add('countryString', 'text')
            ;
        

    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('countryCode')
                ->add('langCode')
                ->add('countryString')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->addIdentifier('id')
                ->addIdentifier('countryCode')
                ->add('langCode')
                ->add('countryString')               
                ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('countryCode')
                ->add('langCode')
                ->add('countryString')
        ;
    }

}
