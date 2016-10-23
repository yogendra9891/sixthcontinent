<?php
namespace Utility\RatingBundle\Services;

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
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\Storealbum;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;

// service method class for user object.
class RatingNotificationService
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
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $item_id, $msgtype, $msg) {
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId($item_id);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setMessageStatus('U');
        $this->dm->persist($notification);
        $this->dm->flush();
        return $notification->getId();
    }
    

    /**
     * remove rate notification if a user not read yet and user remove rate.
     * @param int $item_owner_id
     * @param int $user_id
     * @param string $item_id
     * @param string $message_type
     */
    public function removeNotification($item_owner_id, $user_id, $item_id, $message_type) {
       
       
        $user_notifications = $this->dm->getRepository('NManagerNotificationBundle:UserNotifications')
                                ->findBy(array('from'=>"{$user_id}", 'to'=>"{$item_owner_id}", 'message_type'=>$message_type, 'item_id'=>"{$item_id}", 'is_read'=>'0'), null, 1, 0);
                                
        if (count($user_notifications)) {
            $user_notification = $user_notifications[0];
            $this->dm->remove($user_notification);
            $this->dm->flush();
        }
        return true;
    }
    
    
     /**
     * Save Multi user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveMultiUserNotification($user_ids, $fid, $item_id, $msgtype, $msg) {
        $notification=null;
        foreach($user_ids as $user_id){
        //notification will not be send to the user who is rating
            $notification = new UserNotifications();
            if($user_id != $fid){
                
                $notification->setFrom($user_id);
                $notification->setTo($fid);
                $notification->setMessageType($msgtype);
                $notification->setMessage($msg);
                $notification->setItemId($item_id);
                $time = new \DateTime("now");
                $notification->setDate($time);
                $notification->setIsRead('0');
                $notification->setMessageStatus('U');
                $this->dm->persist($notification);
            }
        }
        $this->dm->flush();
        return $notification ? $notification->getId() : $notification;
    }


}