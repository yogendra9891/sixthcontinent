<?php
namespace StoreManager\StoreBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
// service method  class
class StoreUpdateService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
  

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
    
    
    /**
     * Get Group Owner ACL code
     * @return int
     */
    public function setStoreContractStatus($store_id) {
       $update_store=$this->em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id'=>$store_id));
       if (count($update_store)) {
            $update_store->setNewContractStatus(1);
            $this->em->persist($update_store);
            $this->em->flush();
            return true;
        }else{
            return false;
        }
        
    }
    
    /**
     * Check store owner
     * @param int $store_id
     * @return boolean
     */
    public function checkStoreOwner($store_id, $user_id) {
        $store_obj = $this->em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id, 'userId' => $user_id, 'role' => 15));
        if ($store_obj) {
            return $store_obj;
        } else {
            return false;
        }
    }

}