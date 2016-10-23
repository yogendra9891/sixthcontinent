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

class SixthcontinentCitizenFollowerAdmin extends Admin {

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

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->add('id')
                ->addIdentifier('senderId','integer', array('label' => 'Sender Id'))
                ->add('toId','integer', array('label' => 'To Id'))
                ->add('createdAt');
    }

   

    /**
     *  function for confeguring the list fields 
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     */
    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('senderId','integer', array('label' => 'Sender Id'))
                ->add('toId','integer', array('label' => 'To Id'))
                ->add('createdAt');
    }
    
    protected function configureRoutes(RouteCollection $collection) {
        $collection->remove('create');
    }

    /**
     * function for confugering the batch actions 
     * @return type
     */
    public function getBatchActions() {
        
        // retrieve the default batch actions (currently only delete)
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        if ( $this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE') ) {
//            $actions['createCitizenFollowersOnApplane'] = array(
//                        'label'            => 'Create on Applane',
//                        'ask_confirmation' => false // If true, a confirmation will be asked before performing the action
//                    );

        }
        return $actions;
    }

    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
    }

    
    // Fields to be shown on create/edit forms
       protected function configureFormFields(FormMapper $formMapper)
       {
           $formMapper
                ->add('senderId','integer', array('label' => 'Sender Id'))
                ->add('toId','integer', array('label' => 'To Id'))
                ->add('createdAt','date');
       }
       
       
        public function prePersist($data) {
          
        }
        
    /**
     * function for implementing the custom filter of business category in the list
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('senderId')
                ->add('toId');
    }
    
}
