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

class BussinessCategoryByLanguageAdmin extends Admin {

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
                ->addIdentifier('categoryCode','string', array('label' => 'Category code'))
                ->add('langCode','string', array('label' => 'Language'))
                ->add('categoryName');
    }

   

    /**
     *  function for confeguring the list fields 
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     */
    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('categoryCode','string', array('label' => 'Category Code'))
                ->add('langCode','string', array('label' => 'Language'))
                ->add('categoryName');
    }
    
    protected function configureRoutes(RouteCollection $collection) {
        
    }

    /**
     * function for confugering the batch actions 
     * @return type
     */
    public function getBatchActions() {
        
        // retrieve the default batch actions (currently only delete)
        $actions = parent::getBatchActions();
        unset($actions['delete']);
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
               ->add('categoryCode', 'text', array('label' => 'Catogory Code'))               
               ->add('langCode',null)
               ->add('categoryName',null);
       }
       
       
        public function prePersist($data) {
          
        }
        
    /**
     * function for implementing the custom filter of business category in the list
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('name', 'doctrine_orm_callback', array(
                    'callback' => array($this, 'callbackFilterCatogory'),
                    'field_type' => 'checkbox'
                        ), 'choice', array('choices' => $this->getCategoryChoices()))
                ->add('langCode')
                ->add('categoryCode');
    }

    /**
     *  function that will call for custom feltering based on category
     * @param type $queryBuilder
     * @param type $alias
     * @param type $field
     * @param type $value
     * @return boolean
     */
    public function callbackFilterCatogory($queryBuilder, $alias, $field, $value) {
        if (!is_array($value) or !array_key_exists('value', $value) or empty($value['value'])) {

            return;
        }

        $queryBuilder->select('p')
                ->from($this->getClass(), 'p')
                ->andWhere('p.categoryCode = :category')
                ->setParameter('category', $value['value']);

        return true;
    }
    
    /**
     * function for getting the list of all category for showing in filter dropdown
     * @return type
     */
    public function getCategoryChoices() {
        $data = array();
        $queryBuilder = $this->getModelManager()->getEntityManager($this->getClass())->createQueryBuilder('p');
          $qb = $queryBuilder->select('p')
                ->from($this->getClass(), 'p');
          $query = $qb->getQuery();
          $results = $query->getResult();
          
          foreach($results as $key => $result) {
              $data[$result->getcategoryCode()] = $result->getcategoryCode();
          }
          
          return $data;
    }

}
