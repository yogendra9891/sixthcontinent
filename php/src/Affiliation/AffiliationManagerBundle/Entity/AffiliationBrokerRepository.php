<?php

namespace Affiliation\AffiliationManagerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AffiliationBrokerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AffiliationBrokerRepository extends EntityRepository {
    
    /**
     * find broker affiliated array
     * @param type $user_id
     * @param type $offset
     * @param type $limit
     * @return array 
     */
    public function findBrokerAffiliationUsers($user_id, $offset, $limit)
    {
        $response = array();
        $qb = $this->createQueryBuilder('c');
        $query = $qb
                ->select('c.toId')
                ->innerJoin('UserManagerSonataUserBundle:User', 'co', 'WITH', 'c.fromId = co.id')
                ->where('c.fromId =:sfrom','co.enabled =:isactive', 'co.brokerProfileActive =:brokeractive')
                ->setParameter('sfrom', $user_id)
                ->setParameter('isactive', '1')
                ->setParameter('brokeractive', '1')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery();

        $response = $query->getResult();
        return $response;
    }
    
    /**
     * finding the total count of affiliates of a broker
     * @param type $user_id
     * @return int 
     */
    public function findBrokerAffiliationUsersCount($user_id)
    {
        $qb = $this->createQueryBuilder('c');
        $query = $qb
                ->select('c.toId')
                ->innerJoin('UserManagerSonataUserBundle:User', 'co', 'WITH', 'c.fromId = co.id')
                ->where('c.fromId =:sfrom','co.enabled =:isactive', 'co.brokerProfileActive =:brokeractive')
                ->setParameter('sfrom', $user_id)
                ->setParameter('isactive', '1')
                ->setParameter('brokeractive', '1')
                ->getQuery();

        $response_count = count($query->getResult());
        return $response_count;
    }
}
