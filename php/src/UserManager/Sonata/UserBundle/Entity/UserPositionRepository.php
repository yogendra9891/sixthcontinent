<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserPositionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserPositionRepository extends EntityRepository
{
    /**
     * Get total number of user in UserPoistion table
     * @return array
     */
    public function getMaxCountValue($limit, $offset) {
    
        //initialise the array
        $result = array();
        $result_res = array();
        //create the query
        $query = $this->createQueryBuilder('c');
        $result = $query->select('c.count')
                        ->orderBy('c.id', 'DESC')
                        ->setMaxResults($limit)
                        ->setFirstResult($offset)
                        ->getQuery();
        $result_res = $result->getResult();
        if(!empty($result_res)){
            $last_user_count = $result_res[0]['count'];   
        } else {
           $last_user_count = 0; 
        }
        return $last_user_count;
    }
    
}