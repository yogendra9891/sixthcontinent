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

    
   class TemplateAdmin extends Admin
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
               ->add('title', 'text', array('label' => 'Title'))               
               ->add('body','textarea'
                    )                 ;
       }

       // Fields to be shown on filter forms
       protected function configureDatagridFilters(DatagridMapper $datagridMapper)
       {
           $datagridMapper
               ->add('title')
               ->add('body')
                   ->add('createdAt')
               ->add('authorId')
              
           ;
       }

       // Fields to be shown on lists
       protected function configureListFields(ListMapper $listMapper)
       {
           $listMapper
               ->addIdentifier('title')
               ->add('body')
               ->add('createdat','date')
               ->add('authorid')
           ;
       }
       public function configureShowField(ShowMapper $showMapper) {
           $showMapper
                   ->add('title')
                   ->add('body')
                   ->add('createdat','date')
               ->add('authorid');
       }
       
        public function prePersist($data) {
          
            $user = $this->getConfigurationPool()->getContainer()->get('security.context')
                        ->getToken()
                        ->getUser();

          
            $data->setCreatedAt(new \DateTime());
            $data->setAuthorId($user->getId());
             
        }
        public function preUpdate($data) {
            
            $user = $this->getConfigurationPool()->getContainer()->get('security.context')
                        ->getToken()
                        ->getUser();
            
           
            $data->setCreatedAt(new \DateTime());
            $data->setAuthorId($user->getId());
        }
       
        
        
   }
