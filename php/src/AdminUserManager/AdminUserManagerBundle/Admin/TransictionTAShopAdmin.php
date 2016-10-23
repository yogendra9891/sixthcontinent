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

class TransictionTAShopAdmin extends Admin {

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
                ->add('user_id')
                ->add('name','string', array('label' => 'Shop Name'))
                ->add('business_name')
                ->add('tot_avare','string', array('template' => 'AdminUserManagerAdminUserManagerBundle:TransactionShopAdmin:tot_avare.html.twig'))
                ->add('data_movimento','date',array('label' => 'Transaction Date'));
    }

    protected function configureRoutes(RouteCollection $collection) {
        $collection->remove('create');
        //$collection->remove('delete');
    }

    public function configureShowField(ShowMapper $showMapper) {
        $showMapper
                ->add('id')
                ->add('username')
                ->add('email');
    }

    public function getBatchActions() {
        
        // retrieve the default batch actions (currently only delete)
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        if ( $this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE') ) {
            $actions['exportta'] = array(
                        'label'            => 'Export TA List',
                        'ask_confirmation' => false // If true, a confirmation will be asked before performing the action
                    );

        }

        return $actions;
    }

    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        $this->setTemplate('list', 'AdminUserManagerAdminUserManagerBundle:TransactionShopAdmin:TransictionTAShop.php.twig');
    }

    public function createQuery($context = 'list') {
        $queryBuilder = $this->getModelManager()->getEntityManager($this->getClass())->createQueryBuilder('p');
        $queryBuilder->select('p')
                ->from($this->getClass(), 'p');
        $proxyQuery = new ProxyQuery($queryBuilder);
        return $proxyQuery;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getExportFormats()
    {
        
    }
    
}
