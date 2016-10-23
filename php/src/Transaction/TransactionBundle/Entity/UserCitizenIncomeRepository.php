<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserCitizenIncomeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserCitizenIncomeRepository extends EntityRepository
{
    /**
     * finding the citizen income in
     * @param int $user_id
     * @param int $limit_start
     * @param int $limit_size
     */
    public function getCitizenIncome($user_id, $limit_start, $limit_size) {
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.id, c.userId as user_id, c.citizenIncomeAmount as amount ,c.date')
                ->where('c.userId =:userId')
                ->orderBy('c.date', 'DESC')
                ->setFirstResult($limit_start)
                ->setMaxResults($limit_size)
                ->setParameter('userId', $user_id)
                ->getQuery();
        $response = $query->getResult();
        return $response;        
    }
    
    /**
     * finding the citizen income in count
     * @param int $user_id
     * @return int count
     */
    public function getCitizenIncomeCount($user_id) {
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('count(c.id)')
                ->where('c.userId =:userId')
                ->setParameter('userId', $user_id)
                ->getQuery();
        $response = $query->getSingleScalarResult();
        return $response;        
    }
    
    /**
     * 
     * @param type $user_id
     * @param type $limit_start
     * @param type $limit_size
     */
    public function getCitizenTranaction($user_id, $limit_start, $limit_size){
       $result = array();
       $sql = " select s.* from (SELECT ug.date, ug.user_id, ug.gift_card_amount as amount,1 as type
                FROM UserGiftCardPurchased as ug
                WHERE ug.user_id =".$user_id."
                UNION ALL
                SELECT uc.date, uc.user_id, uc.citizen_income_amount as amount,2 as type
                FROM UserCitizenIncome as uc
                WHERE uc.user_id =".$user_id."
                ) s order by s.date DESC LIMIT ".$limit_start.",".$limit_size;
       
        $stmt = $this->getEntityManager()
              ->getConnection()
              ->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(); 
      return $result;
    }
    
    /**
     * 
     * @param type $user_id
     * @return type
     */
     public function getCitizenTranactionCount($user_id){
       $result = array();
       $sql = " select s.* from (SELECT ug.date, ug.user_id, ug.gift_card_amount,1 as type
                FROM UserGiftCardPurchased as ug
                WHERE ug.user_id =".$user_id."
                UNION ALL
                SELECT uc.date, uc.user_id, uc.citizen_income_amount as citizen_income,2 as type
                FROM UserCitizenIncome as uc
                WHERE uc.user_id =".$user_id."
                ) s";
       
        $stmt = $this->getEntityManager()
              ->getConnection()
              ->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(); 
      
      return count($result);
    }
    
    
    public function getAllUsers($limit_start,$limit_size) {
        $sql = " select id from fos_user_user order by id asc limit $limit_size offset $limit_start";
       
        $stmt = $this->getEntityManager()
              ->getConnection()
              ->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(); 
      return $result;
    }
}
