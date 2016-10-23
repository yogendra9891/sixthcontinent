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
   use Sonata\AdminBundle\Datagrid\ORM\ProxyQuery;
    
   class UsersRoleAdmin extends Admin
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
               ->add('firstname', 'text')               
               ->add('lastname','text')
               ->add('gender', 'text')
               ->add('birthDate','date')
               ->add('phone','text')
               ->add('country','text')
               ->add('street','text')
               ->add('profileType','text', array('help'=>'22 for Citizen, 23 for Citizen Writer, 24 for Broker, 25 for Ambassador'))
               ->add('isActive','text',array('help'=>'1 for active, 0 for inactive'))
                  
               ;
       }

       // Fields to be shown on filter forms
       protected function configureDatagridFilters(DatagridMapper $datagridMapper)
       {
           $datagridMapper
              // ->add('profileType')
                ->add(
            'profileType',
            'doctrine_orm_number',
            [],
            'choice',
            [
                'choices' => array(22=>'Citizen', 23=>'Citizen writer', 24=>'Broker', 25=>'Ambassador')
            ]
        )
               ->add('email')
               ->add('isActive')
               
             
           ;
       }

       // Fields to be shown on lists
       protected function configureListFields(ListMapper $listMapper)
       {
           $listMapper
               ->addIdentifier('email')
               ->add('firstName')
               ->add('lastName')
               ->add('gender')
               ->add('birthDate')
               ->add('phone')
               ->add('country')
               ->add('street')
               ->add('isActive')
                   
                   ;
            
               
       }
       
       public function configureShowField(ShowMapper $showMapper) {
           $showMapper
                   ->add('id')
                   ->add('username')
                   ->add('email');
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
      public function getBatchActions()
    {
        // retrieve the default (currently only the delete action) actions
       // $actions = parent::getBatchActions();
          //$cobj = new CRUDController())
        $actions = parent::getBatchActions();

        unset($actions['delete']);
        // check user permissions
        if($this->hasRoute('edit') && $this->isGranted('EDIT') && $this->hasRoute('delete') && $this->isGranted('DELETE')) {
            $actions['broker'] = array(
                'label'            => 'Add profile as broker',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );
             $actions['ambassador'] = array(
                'label'            => 'Add profile as ambassador',
                'ask_confirmation' => true // If true, a confirmation will be asked before performing the action
            );

        }

        return $actions;
    }   
    // Configure our custom roles for this entity
    public function configure() {
        parent::configure();
        $this->setTemplate('list', 'AdminUserManagerAdminUserManagerBundle:UserRoleAdmin:list-myentity.html.twig');
    }
     public function createQuery($context = 'list') 
    {    
         $request = $this->getRequest();
         $data = $request->get('filter');
        
         $filter_type = 22;
         if(isset($data)){
             if(isset($data['profileType']['value'])){
             $filter_type = $data['profileType']['value'];
             }else{
                 $filter_type = 22;
             }
             
         }
         //$em = $this->container->getDoctrine()->getEntityManager();
        
        //$query = $em->createQueryBuilder('c');
        $query = parent::createQuery($context); 
        if($filter_type == 22){
        $query->Select();

        $query->andWhere( 
            $query->expr()->eq($query->getRootAlias().'.profileType', ':profileType') 
        ); 
        $query->setParameter('profileType', '22'); // eg get from security context 
       }
//        
        return $query; 
       
    } 
   
    
   
   }
