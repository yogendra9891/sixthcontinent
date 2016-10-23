<?php

namespace Affiliation\AffiliationManagerBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ORM\EntityRepository;

/**
 * InvitationSendMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InvitationSendMongoRepository extends DocumentRepository {

    public function getAlreadyAffiliatedUsers($from_id, $email_ids, $affiliation_status) {
        $qb = $this->createQueryBuilder('m');
        $results = $qb->field('from_id')->equals($from_id)
                     ->field('affiliation_type')->equals((int)$affiliation_status)
                     ->field('email')->in($email_ids)
                ->getQuery()
                ->execute()
                ->toArray(false);
        
        return $results;
    }
    
    /**
     *  function for getting the user invitation
     * @param type $user_id
     * @param type $limit
     * @param type $offset
     * @return type
     */
    public function getUsersInvitation($user_id, $limit, $offset,$affiliation_type) {
        $qb = $this->createQueryBuilder('m');
        $query = $qb->field('from_id')->equals($user_id)
                    ->field('affiliation_type')->equals($affiliation_type);
        if (is_numeric($limit) && is_numeric($offset)) {
            $query->limit($limit);
            $query->skip($offset);
        }
        $results = $query->sort('updated_at', 'DESC')
                ->getQuery()
                ->execute()
                ->toArray(false);
        return $results;
    }
    
    
    /**
     *  function for getting the 
     * @param type $user_id
     * @param type $limit
     * @param type $offset
     * @return type
     */
    public function getUsersInvitationCount($user_id,$affiliation_type) {
        $qb = $this->createQueryBuilder('m');
        $query = $qb->field('from_id')->equals($user_id)
                ->field('affiliation_type')->equals($affiliation_type);
        $results = $query->getQuery()
                ->execute()->count();
        return $results;
    }
    
}