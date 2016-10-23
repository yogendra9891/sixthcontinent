<?php
namespace UserManager\Sonata\UserBundle\Services;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class IPAddressService {
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
    
    /**
     * function to get the current ip address of the request
     */
    public function getCurrentIPAddress() {
       $ip_address =  $this->container->get('request')->getClientIp();
       return $ip_address;
    }
}