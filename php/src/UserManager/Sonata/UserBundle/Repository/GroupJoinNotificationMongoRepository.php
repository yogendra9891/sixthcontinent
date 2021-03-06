<?php

namespace UserManager\Sonata\UserBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * GroupJoinNotificationMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupJoinNotificationMongoRepository extends DocumentRepository {

    /**
     * Get notification list
     * @param int $user_id
     * @return array
     */
    public function getGroupJoinNotifications($user_id) {
        $response = array();
        $results = array();
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('receiver_id')->equals($user_id)
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }


        return $response;
    }
    
    /**
     * Get notification list
     * @param int $user_id
     * @return array
     */
    public function getAllGroupRequests($user_id) {
        $response = array();
        $results = array();
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = //$qb->field('receiver_id')->equals($user_id)
                $qb->addOr($qb->expr()->field('receiver_id')->equals($user_id))
                ->addOr($qb->expr()->field('sender_id')->equals($user_id))
                ->sort('created_at', 'desc')
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }


        return $response;
    }

    /**
     * Get Group specific notifications
     * @param type $group_id
     * @return type
     */
    public function getGroupNotifications($group_id, $group_owner_id) {
        $response = array();
        $results = array();
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('group_id')->equals($group_id)
                ->field('sender_id')->notEqual($group_owner_id)
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }


        return $response;
    }

    /**
     * find the group notificaion
     * @param int $cuser_id
     * @param array $group_ids
     */
    public function getGroupNotification($cuser_id, $group_ids) {
        $response = array();
        $results = array();
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('group_id')->in($group_ids)
                ->field('sender_id')->equals($cuser_id)
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[$result->getGroupId()] = $result;
        }
        return $response;       
    }
    /*****************************************************************************/
     /**
     * Get notification list
     * @param int $user_id
     * @return array
     */
    public function getGroupJoinNotificationsNew($user_id,$limit,$offset) {
        $response = array();
        $results = array();
        $from = new \DateTime();        
        $to =   new \DateTime();
        $from->sub(new \DateInterval('P7D'));
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('receiver_id')->equals($user_id)
                ->field('created_at')->range($from, $to)
                ->field('is_view')->equals('1')
               ->limit($limit)
                ->skip($offset)
                ->sort('created_at', -1)
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }


        return $response;
    }
    
    public function getGroupJoinNotificationsNewCount($user_id) {
        $response = array();
        $results = array();
        $from = new \DateTime();        
        $to =   new \DateTime();
        $from->sub(new \DateInterval('P7D'));
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('receiver_id')->equals($user_id)
                ->field('created_at')->range($from, $to)
                ->field('is_view')->equals('1')               
                ->getQuery()
                ->execute()
                ->toArray(false);

        foreach ($results as $result) {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }


        return $response;
    }
    /**
     * Get All count group notification Lists
     * @param int $user_id
     * @return array
     */
    public function getisviewcountGroupNotification($user_id) 
            {
        
        $response = array();
        $results = array();
        $user_id = (int)$user_id;
        //check if user is the member of the group
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $results = $qb->field('receiver_id')->equals($user_id)
                ->field('is_view')->equals(0)
                ->getQuery()
                ->execute()
                ->toArray(false);
        foreach ($results as $result) 
        {
            $response[] = array('group_id' => $result->getGroupId(),
                'request_id' => $result->getId(),
                'sender_id' => $result->getSenderId(),
                'receiver_id' => $result->getReceiverId()
            );
        }        
        return $response;
    }
   
    
    /**
     * Mark notification as Viewed
     * @param int $user_id
     * @param int $is_view
     * @return boolean
     */
    public function getisviewUpdateGroupNotification($user_id, $is_view)
    {
         $user_id = (int)$user_id;                
         $qb = $this->createQueryBuilder()
               ->update()
               ->multiple(true)
               ->field('is_view')->set($is_view)
               ->field('receiver_id')->equals($user_id)
               //->field('id')->equals($is_view)
               ->getQuery()
               ->execute();
           return true;
    }
    /*****************************************************************************/
}

