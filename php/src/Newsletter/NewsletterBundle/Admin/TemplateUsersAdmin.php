<?php
namespace Newsletter\NewsletterBundle\Admin;

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
    use Newsletter\NewsletterBundle\Controller\CRUDController;
    use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

    
   class TemplateUsersAdmin extends Admin
   {
        /**
        * @var Symfony\Component\DependencyInjection\Container
        */
        private $container;

        /**
         * @param Symfony\Component\DependencyInjection\Container $container
         */
        public function setContainer(Container $container)
        {
            $this->container = $container;
        }

       // Fields to be shown on create/edit forms
       protected function configureFormFields(FormMapper $formMapper)
       {
           $formMapper
               ->add('username', 'text')               
               ->add('email','text') 
               
                  ;
       }

       // Fields to be shown on filter forms
       protected function configureDatagridFilters(DatagridMapper $datagridMapper)
       {
           $datagridMapper
               ->add('id')
               ->add('username')
                  ->add('email')
           ;
       }

       // Fields to be shown on lists
       protected function configureListFields(ListMapper $listMapper)
       {
           $listMapper
               ->addIdentifier('id')
               ->add('username')
               ->add('email')
                   
           ;
       }
       public function configureShowField(ShowMapper $showMapper) {
           $showMapper
                   ->add('id')
               ->add('username')
                   ->add('email');
       }
       
        public function prePersist($data) {
          
        }
        public function preUpdate($data) {
         
        }
     protected function configureRoutes(RouteCollection $collection) { 
         $collection->add('unpublish', $this->getRouterIdParameter().'/unpublish');
         $collection->remove('create');
         
     }
      public function getBatchActions()
    {
        // retrieve the default (currently only the delete action) actions
       // $actions = parent::getBatchActions();
          //$cobj = new CRUDController())
        $actions = parent::getBatchActions();

        unset($actions['delete']);
        // check user permissions
        if($this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE')) {
            $actions['newsletter'] = array(
                'label'            => 'Send Newsletter',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );

        }

        return $actions;
    }   
    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        $this->setTemplate('list', 'NewsletterNewsletterBundle:Admin:list-myentity.html.twig');
    }
    
    public function createQuery($context = 'list') 
    {     
        $queryBuilder = $this->getModelManager()->getEntityManager('UserManagerSonataUserBundle:User')->createQueryBuilder();
        $queryBuilder->select('co')
            ->from('UserManagerSonataUserBundle:User', 'co');
        $proxyQuery = new ProxyQuery($queryBuilder);
        return $proxyQuery;
    } 
    
   }
