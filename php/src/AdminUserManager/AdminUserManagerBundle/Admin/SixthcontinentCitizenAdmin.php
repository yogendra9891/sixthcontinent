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
use Sonata\UserBundle\Admin\Model\UserAdmin as BaseUserAdmin;

class SixthcontinentCitizenAdmin extends BaseUserAdmin {

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
                ->add('username')
                ->add('firstname')
                ->add('email')                
                ->add('lastname')
                ->add('password','password')
                ->add('country')
                ->add('dateOfBirth',"date")
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('id')
                ->add('username')
                ->add('email')
                ->add('enabled', null, array('label' => 'Is Enabled'))
                ->add('Profile_Type', 'doctrine_orm_callback', array(
                    'label' => 'Profile Type','callback' => array($this, 'callbackFilterCatogory'),
                    'field_type' => 'checkbox'
                        ), 'choice', array('choices' => $this->getCategoryChoices(),'expanded' => true, 'multiple' => false))
//                ->add('langCode')
//                ->add('categoryCode');
        // ->add('toId')
        // ->add('shopId')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
                ->add('id','integer', array('label' => 'User id'))
                ->add('username')
                ->add('firstname','string', array('label' => 'Firstname'))
                ->add('lastname','string', array('label' => 'Lastname'))
                ->add('country','string', array('label' => 'Country'))
                ->add('gender','string', array('label' => 'Gender'))
                ->add('enabled')
                ->add('citizenProfile','boolean', array('label' => 'Citizen'))
                ->add('sellerProfile','boolean', array('label' => 'Seller'))
        ;
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id','integer', array('label' => 'User id'))
                ->add('username')
                ->add('firstname','string', array('label' => 'Firstname'))
                ->add('lastname','string', array('label' => 'Lastname'))
                ->add('country','string', array('label' => 'Country'))
                ->add('gender','string', array('label' => 'Gender'))
                ->add('enabled')
                ->add('citizenProfile','boolean', array('label' => 'Citizen'))
                ->add('sellerProfile','boolean', array('label' => 'Seller'))
        ;
    }

    /**
     * Method call after saving the data
     * @param object $data
     */
    public function postPersist($data) {
        
    }

    /**
     * Method call after updating the data
     * @param object $data
     */
    public function postUpdate($data) {
        
    }

    /**
     * Method call before updating the data
     * @param object $data
     */
    public function preUpdate($data) {
        //before update
    }

    public function getBatchActions() {
        // retrieve the default (currently only the delete action) actions
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        // check user permissions
        if ($this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE')) {

            $request = $this->getRequest();
            $data = $request->get('type');

            $actions['citizencreateonapplane'] = array(
                'label' => 'Citizen Create On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['citizenupdateonapplane'] = array(
                'label' => 'Citizen Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['citizenreferralupdateonapplane'] = array(
                'label' => 'Citizen Referral Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['citizenFriendsUpdateOnApplane'] = array(
                'label' => 'Citizen Friends Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['citizenFollowersUpdateOnApplane'] = array(
                'label' => 'Citizen Followers Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
            $actions['citizenProfilePicUpdateOnApplane'] = array(
                'label' => 'Citizen Profile Image Update On Applane',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
        }

        return $actions;
    }
    


    public function callbackFilterCatogory($queryBuilder, $alias, $field, $value) {
        
        if (!is_array($value) or !array_key_exists('value', $value) or empty($value['value'])) {

            return;
        }

        $filter_field = $value['value'];
        $queryBuilder->select('p')
                ->from($this->getClass(), 'p')
                ->andWhere("p.".$filter_field. '= :value')
                ->setParameter('value', 1);

        return true;
    }
    
    public function getCategoryChoices() {
        $data = array();
        $data['sellerProfile'] = 'sellerProfile';
          
          return $data;
    }
    
    
    protected function configureRoutes(RouteCollection $collection) {
        //$collection->add('unpublish', $this->getRouterIdParameter() . '/unpublish');
        $collection->remove('create');
        //$collection->add('activate', $this->getRouterIdParameter().'/activate');
        //$collection->add('documents', $this->getRouterIdParameter().'/documents');
        //$collection->remove('delete');
    }

}
