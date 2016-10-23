<?php

namespace PostFeeds\PostFeedsBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * SocialProjectMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SocialProjectMongoRepository extends DocumentRepository
{
    
     /**
    * listing of user's project
    * @param type $user_id
    * @param type $limit
    * @param type $offset
    * @return type
    */ 
    
    public function getProjects($user_id,$limit,$offset){
        $qb     = $this->createQueryBuilder('Project');
        $result = $qb->field('owner_id')->equals($user_id)
                    ->field('status')->equals(1)
                    ->field('is_delete')->equals(0)
                        ->sort('created_at', 'DESC')
                        ->limit($limit)
                        ->skip($offset)
                        ->getQuery()
                        ->execute()
                        ->toArray('false');
        return $result;
    }
   /**
    * listing of friend's project
    * @param type $friend_id
    * @param type $offset
    * @param type $limit
    * @return type
    */ 
    public function getOwnerProjects($friend_id, $offset, $limit){
        $qb     = $this->createQueryBuilder('Project');
        $result = $qb->field('owner_id')->equals($friend_id)
                        ->sort('created_at', 'DESC')
                        ->limit($limit)
                        ->skip($offset)
                        ->getQuery()
                        ->execute()
                        ->toArray('false');
        return $result;
    }
    
    /**
     * serahc of social projects
     */
    public function getSearchedProject($owner_id, $txt_search, $offset, $limit, $country, $city,$sort_type){
       $results = array();
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $qb->field('status')->equals(1)
           ->field('is_delete')->equals(0);
                    if ($owner_id != '') {
                        $qb->field('owner_id')->equals($owner_id);
                    }
                    if ($txt_search != '') {
                        $qb->addOr(
                               $qb->expr()->field('title')->equals(new \MongoRegex('/.*' . $txt_search . '.*/i'))
                         )
                        ->addOr(
                                $qb->expr() ->field('description')->equals(new \MongoRegex('/.*' . $txt_search . '.*/i')) 
                         );
                    }
                    if ($country != '') {
                        $qb->field('address.country')->equals(new \MongoRegex('/.*' .$country. '.*/i'));
                    }
                    if ($city != '') {
                        $qb->field('address.city')->equals(new \MongoRegex('/.*' . $city . '.*/i'));
                    }
                    if($sort_type == 1){
                      $qb->sort('created_at', 'DESC');  
                    } else {
                      $qb->sort('we_want', 'DESC');    
                    }
          $results =   $qb->limit($limit)
                          ->skip($offset)
                          ->getQuery()
                          ->execute()
                          ->toArray(false);
        return $results;
    }
    
    /**
     * search of social projects
     */
    public function getSearchedProjectCount($txt_search,$country, $city, $owner_id){
       $results = array();
        //get all groups assigned to user
        $qb = $this->createQueryBuilder();
        $qb->field('status')->equals(1)
            ->field('is_delete')->equals(0);
        if ($owner_id != '') {
            $qb->field('owner_id')->equals($owner_id);
        }
        if ($txt_search != '') {
            $qb->addOr(
                            $qb->expr()->field('title')->equals(new \MongoRegex('/.*' . $txt_search . '.*/i'))
                    )
                    ->addOr(
                            $qb->expr()->field('description')->equals(new \MongoRegex('/.*' . $txt_search . '.*/i'))
            );
        }
        if ($country != '') {
            $qb->field('address.country')->equals(new \MongoRegex('/.*' . $country . '.*/i'));
        }
        if ($city != '') {
            $qb->field('address.city')->equals(new \MongoRegex('/.*' . $city . '.*/i'));
        }
        $results =   $qb->getQuery()
                        ->execute()
                        ->toArray(false);
        $res=count($results);
        return $res;
    }
    
   /**
    * getting the projects by ids
    * @param array $project_ids
    * @return array objects
    */ 
    public function getSocialProjects($project_ids) {
        $qb     = $this->createQueryBuilder('Project');
        $result = $qb->field('id')->in($project_ids)
                     ->getQuery()
                     ->execute()
                     ->toArray('false');
        return $result;
    }
}