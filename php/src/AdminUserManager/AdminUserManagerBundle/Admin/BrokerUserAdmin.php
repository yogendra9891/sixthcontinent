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

class BrokerUserAdmin extends Admin {

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
                ->add('id', 'text')
                ->add('user_id', 'text')


        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
       //->add('profileType')
             // ->add('username')
         ->add('id')
         ->add('email', 'doctrine_orm_callback', array(
            'callback'   => array($this, 'callbackFilterEmail'),
            //'field_type' => 'textbox'
            ));
    }

    public function callbackFilterEmail($queryBuilder, $alias, $field, $value) {
        if (!is_array($value) or ! array_key_exists('value', $value)
                or empty($value['value'])) {

            return;
        }
        //$queryBuilder->from('UserManagerSonataUserBundle:User', 'c');
        $queryBuilder->andWhere('co.email = :email');
        $queryBuilder->setParameter('email', $value['value']);
        return true;
    }   

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper) {
        $listMapper
//                ->addIdentifier('id', null, array(
//                    'route' => array(
//                        'name' => 'activate'
//                    )
//                ))
                ->add('username')
                ->add('firstname')
                ->add('lastname')
                ->add('email')
                ->add('broker_profile_active')
                ->add('_action', 'actions', array(
                    'actions' => array(
                        'activate' => array(
                            'template' => 'AdminUserManagerAdminUserManagerBundle:CRUD:list__action_activate.html.twig'
                        ),
                        'documents' => array(
                    'template' => 'AdminUserManagerAdminUserManagerBundle:CRUD:list__action_documents.html.twig'
                ),
                    )
                ))
//                ->add('_action', 'actions', array(
//                'actions' => array(
//                 'documents' => array(
//                    'template' => 'AdminUserManagerAdminUserManagerBundle:CRUD:list__action_documents.html.twig'
//                )
//            )
//        )
//                        )
              ;
    }

    protected function configureRoutes(RouteCollection $collection) {
        $collection->add('unpublish', $this->getRouterIdParameter() . '/unpublish');
        $collection->remove('create');
        $collection->add('activate', $this->getRouterIdParameter().'/activate');
        $collection->add('documents', $this->getRouterIdParameter().'/documents');
        //$collection->remove('delete');
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('username')
                ->add('email');
    }

    public function getBatchActions() {

        $actions = parent::getBatchActions();
        unset($actions['delete']);
        return $actions;
    }

    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        //$this->setTemplate('list', 'AdminUserManagerAdminUserManagerBundle:UserRoleAdmin:list-citizen.html.twig');
    }

    public function createQuery($context = 'list') {
        $queryBuilder = $this->getModelManager()->getEntityManager($this->getClass())->createQueryBuilder('p');
        $queryBuilder->select('co')
                ->from($this->getClass(), 'p')
                ->leftJoin('UserManagerSonataUserBundle:User', 'co', 'WITH', 'p.userId = co.id');


        $proxyQuery = new ProxyQuery($queryBuilder);
        return $proxyQuery;
    }

}
