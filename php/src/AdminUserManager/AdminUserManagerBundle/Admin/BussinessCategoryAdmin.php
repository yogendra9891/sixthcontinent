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

class BussinessCategoryAdmin extends Admin {

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
                ->add('parent')
                ->addIdentifier('name','string', array('label' => 'Category Name'))
                ->add('image')
                ->add('image_thumb')
                ->add('txn_percentage')
                ->add('card_percentage');
    }

   
    /**
     * function for confugering the fields need to be show
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     */
    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('parent')
                ->add('name','string', array('label' => 'Category Name'))
                ->add('image')
                ->add('image_thumb')
                ->add('txn_percentage')
                ->add('card_percentage');
    }
    
    /**
     *  function for confugering the routes
     * @param \Sonata\AdminBundle\Route\RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection) {
        //$collection->remove('create');
        //$collection->remove('delete');
        $collection->add('clone', $this->getRouterIdParameter().'/clone');
        $collection->add('addCategoryByLanguage','add-category-by-language');
    }

    /**
     * function for adding the batch action
     * @return boolean
     */
    public function getBatchActions() {
        
        // retrieve the default batch actions (currently only delete)
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        if ( $this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE') ) {
            $actions['createBusinessCategory'] = array(
                        'label'            => 'Create on Applane',
                        'ask_confirmation' => false // If true, a confirmation will be asked before performing the action
                    );
            $actions['updateBusinessCategory'] = array(
                        'label'            => 'Update on Applane',
                        'ask_confirmation' => false // If true, a confirmation will be asked before performing the action
                    );

        }
        return $actions;
    }

    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        $this->setTemplate('edit', 'AdminUserManagerAdminUserManagerBundle:BussinessCategory:edit.html.twig');
    }
    
    // Fields to be shown on create/edit forms
       protected function configureFormFields(FormMapper $formMapper)
       {
           $formMapper
               ->add('parent', 'integer', array('label' => 'Parent'))               
               ->add('name',null)
               ->add('image',null)
               ->add('image_thumb','text')
               ->add('txn_percentage','text')
               ->add('card_percentage','text')
               ;
       }
       
       
        public function prePersist($data) {
          
        }
        
}
