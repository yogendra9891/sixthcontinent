<?php

namespace Dashboard\DashboardManagerBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DashboardFeedsService {

    protected $em;
    protected $dm;
    protected $container;

    CONST PERSONAL = 'PERSONAL';
    CONST PROFESSIONAL = 'PROFESSIONAL';
    //define the required params

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
        //$this->request   = $request;
    }

    /**
     * finding sender and receiver users array
     * @param object
     * @return array
     */
    public function getFeedUsers($posts) {
        $sender_users = $receiver_users = array();
        foreach ($posts as $post) {
            $sender_users[] = $post->getUserId();
            $receiver_users[] = $post->getToId();
        }
        return array('sender' => $sender_users, 'receiver' => $receiver_users); // Replaces multiple hyphens with single one.
    }

    /**
     * finding the user relations 
     * @param array $users_array
     * 
     */
    public function getUserFriendShipRelations($users_array) {
        $data = array();
        $sender_users = $users_array['sender'];
        $receiver_users = $users_array['receiver'];
        $em = $this->em;
        $users = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkUserFriendShipRelations($sender_users, $receiver_users);
        foreach ($users as $user) { 
            $sender   = $user['from_user'];
            $receiver = $user['to_user'];
            $personal_status     = $user['personal_status'];
            $professional_status = $user['professional_status'];
            if ($personal_status == 1) {
                $data[self::PERSONAL][$sender][] = $receiver;
                $data[self::PERSONAL][$receiver][] = $sender;
                $data[self::PERSONAL][$sender]   =  array_unique($data[self::PERSONAL][$sender]);
                $data[self::PERSONAL][$receiver] =  array_unique($data[self::PERSONAL][$receiver]);
            } 
            if ($professional_status == 1) {
                $data[self::PROFESSIONAL][$sender][] = $receiver;
                $data[self::PROFESSIONAL][$receiver][] = $sender;
                $data[self::PROFESSIONAL][$sender] = array_unique($data[self::PROFESSIONAL][$sender]);
                $data[self::PROFESSIONAL][$receiver] = array_unique($data[self::PROFESSIONAL][$receiver]);
            } 
        }
        return $data;
    }
    
}
