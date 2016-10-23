<?php

namespace Message\MessageBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * MessageThreadMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MessageThreadMongoRepository extends DocumentRepository
{
    /**
    * Edit thread member
    * @param type $thread_id
    * @param type $thread_member
    * @return type
    */
   public function editThreadMember($thread_id,$thread_type,$thread_member){
       $qb = $this->createQueryBuilder()
        ->update()
        ->field('thread_type')->set($thread_type)
        ->field('group_members')->set($thread_member)
        ->field('id')->equals($thread_id)
        ->getQuery();
       if($qb->execute()){
           return true;
       } else {
           return false;
       }
   }
   
   /**
    * Edit thread member
    * @param type $thread_id
    * @param type $thread_member
    * @return type
    */
   public function getThreadData($thread_id,$user_id){
       $qb     = $this->createQueryBuilder();
       $result = $qb
                 ->field('id')->equals($thread_id)
                 ->field('group_members')->in(array($user_id))
                    ->sort('created_at','desc') 
                      ->limit($limit)
                      ->skip($offset)
                      ->getQuery()
                      ->execute()
                      ->toArray(false);
       return $result;
   }
   
   /**
    * List all Thread
    * @param type $user_id
    * @param type $limit
    * @param type $offset
    * @return type
    */
   public function listGroup($user_id,$limit,$offset){
        $qb     = $this->createQueryBuilder('User');
        $result = $qb   
            ->field('delete_by')->notIn(array($user_id))
            ->addOr($qb->expr()->field('group_members')->in(array($user_id)))
            ->addOr($qb->expr()->field('created_by')->equals((int)$user_id))
                  ->sort('updated_at','desc')            
                  ->limit($limit)
                   ->skip($offset)
                   ->getQuery()
                   ->execute()
                   ->toArray(false);
        return $result;
   }
   
   /**
    * List all Thread Count
    * @param type $user_id
    * @param type $limit
    * @param type $offset
    * @return type
    */
   public function listGroupCnt($user_id){
        $qb     = $this->createQueryBuilder('User');
        $result = $qb   
            ->field('delete_by')->notIn(array($user_id))
            ->addOr($qb->expr()->field('group_members')->in(array($user_id)))
            ->addOr($qb->expr()->field('created_by')->equals((int)$user_id))
                   ->getQuery()
                   ->execute()
                   ->toArray(false);
        return count($result);
   }
   
   /**
    * Check if single thread exist
    * @param type $user_id
    * @param type $reciever_id
    * @param type $limit
    * @param type $offset
    * @return type
    */
    public function checkSignleThreadExist($user_id,$reciever_id){
       $arrayMerge[] = (integer)$user_id;
       $text = 'single';
       $user_id_merge = array_merge($arrayMerge,$reciever_id);
       $qb     = $this->createQueryBuilder('User');
       $result = $qb   
                ->field('created_by')->in($user_id_merge)
                ->field('group_members')->in($user_id_merge)
                ->field('thread_type')->equals($text)
                    ->getQuery()
                    ->execute()
                    ->toArray(false);
       return $result;
    }
    
    /**
    * Edit thread member
    * @param type $thread_id
    * @param type $thread_member
    * @return type
    */
   public function updateThreadTime($thread_id){
       $from = new \DateTime();
       $qb = $this->createQueryBuilder()
        ->update()
        ->field('updated_at')->set($from)
        ->field('id')->equals($thread_id)
        ->getQuery();
       if($qb->execute()){
           return true;
       } else {
           return false;
       }
   }

    /**
    * List all thread id from which user has been removed
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function listGroupDeletedMemebersThreadId($user_id){
            $user_id_arr = array($user_id); 
            $qb     = $this->createQueryBuilder();
            $result = $qb
                     ->select('id')
                     ->field('group_members')->notIn($user_id_arr)
                     ->field('delete_by')->in(array($user_id))
                                ->getQuery()
                                ->execute()
                                ->toArray(false);
            return $result; 
    }
}