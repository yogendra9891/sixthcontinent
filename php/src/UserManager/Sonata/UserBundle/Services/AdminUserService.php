<?php
namespace UserManager\Sonata\UserBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

// service method class for seller user handling.
class AdminUserService {

    protected $em;
    protected $dm;
    protected $container;
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    public function getAdminId() {
        $em = $this->em;
        $qb = $this->em->createQueryBuilder();
        $qb->select('u.id')
                ->from('UserManagerSonataUserBundle:User', 'u')
                ->where('u.roles LIKE :roles')
                ->setParameter('roles', '%"ROLE_ADMIN"%');
        $admin_id =  $qb->getQuery()->getResult();
        if(count($admin_id) > 0){
            return $admin_id[0]['id'];
        } else {
            return "";
        }
    }
    
}