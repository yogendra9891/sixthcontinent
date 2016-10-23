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
    
   class CitizenUserAdmin extends Admin
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

      

       // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper) {
        $datagridMapper
                ->add('userId')
        ;
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
               // ->add('sellerProfile','boolean', array('label' => 'Seller'))
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
        public function prePersist($data) {
          
//            $user = $this->getConfigurationPool()->getContainer()->get('security.context')
//                        ->getToken()
//                        ->getUser();
//            $data->setCreatedAt(new \DateTime());
//            $data->setAuthorId($user->getId());
             
        }
        public function preUpdate($data) {
         
//            $user = $this->getConfigurationPool()->getContainer()->get('security.context')
//                        ->getToken()
//                        ->getUser();
//            $data->setCreatedAt(new \DateTime());
//            $data->setAuthorId($user->getId());
        }
     protected function configureRoutes(RouteCollection $collection) { 
         $collection->add('unpublish', $this->getRouterIdParameter().'/unpublish');
         $collection->remove('create');
         //$collection->remove('delete');
         
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
    
    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        $this->setTemplate('list', 'AdminUserManagerAdminUserManagerBundle:UserRoleAdmin:list-citizen.html.twig');
    }
    public function createQuery($context = 'list') 
    {     
        $queryBuilder = $this->getModelManager()->getEntityManager($this->getClass())->createQueryBuilder('p');
        $queryBuilder->select('co')
            ->from($this->getClass(), 'p')
                ->leftJoin('UserManagerSonataUserBundle:User', 'co', 'WITH', 'p.userId = co.id');
        $proxyQuery = new ProxyQuery($queryBuilder);
        return $proxyQuery;
       
    } 
   
   }
