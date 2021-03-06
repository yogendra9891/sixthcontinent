<?php

namespace Utility\ApplaneIntegrationBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ShopTransactionDetailRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ShopTransactionDetailRepository extends EntityRepository
{
    /**
     * finding the shop transaction 
     * @return object array
     */
    public function getShopTransaction() {
        $start_date = date('Y-m-d');
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
           ->where('c.created_at =:date','c.status =:status')
           ->setParameter('status', 0)
           ->setParameter('date', $start_date);
        $query    = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }
    
    /**
     * finding the shop pending transaction 
     * @return object array
     */
    public function getShopPedningTransaction($shop_id,$transaction_id) {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('sm')
                ->where('sm.shopId =:sid','sm.status =:status','sm.id !=:txn_id')
                ->setParameter('sid', $shop_id)
                ->setParameter('status', 0)
                ->setParameter('txn_id', $transaction_id)
                ->getQuery();

           return $result = $qb->getResult();   
    }
    
    /**
     * finding the shop pending transaction 
     * @return object array
     */
    public function getShopTotalRevenue($shop_id) {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('sum(sm.amount) AS total_revenue')
                ->where('sm.shopId =:sid')
                ->setParameter('sid', $shop_id)
                ->getQuery();

        $result = $qb->getResult();        
        return $result[0]['total_revenue'];   
    }
    /**
     * mark transaction 
     * @return object array
     */
    public function setMultiTransactionStatus($pending_transaction_ids,$status) {
       $pending_transaction_ids_array = explode(",",$pending_transaction_ids);
       
        //create the query 
        $qb = $this->createQueryBuilder('s');
        $query = $qb->update()
            ->set('s.status', ':status')
            ->where(
                    $qb->expr()->In('s.id', ':txn_id')
                )
            ->setParameter('status',1)
            ->setParameter('txn_id', $pending_transaction_ids_array)
            ->getQuery();
        $response = $query->getResult();
        return true;
    }
    
    /**
     * Get all shops those have total revenue greater than 200 
     * and have not paid the registration fee.
     */
    public function getAllShopTotalRevenue()
    {
         $result = array();
         $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.amount) AS total_revenue, co.userId as store_owner, co.storeId, s.createdAt, s.paymentStatus')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'sm.shopId = s.id')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 's.id = co.storeId')
              ->where('s.isActive =:isactive')
              ->andWhere('s.paymentStatus =:paymentstatus')
              ->andWhere('s.shopStatus =:shopstatus')
              ->having('total_revenue >:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('paymentstatus', 0)
              ->setParameter('shopstatus', 1)
              ->setParameter('startamount', 200)
              ->groupBy('sm.shopId')
              ->orderBy('total_revenue','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
    /**
     * Get all shops that have pending payment
     */
    public function getAllPedningTransaction() {
        $response = array();
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('max(sm.id) as id')
                ->where('sm.status =:status')
                ->setParameter('status', 0)
                ->groupBy('sm.shopId')
                ->getQuery();

        $results = $qb->getResult();


        if ($results) {
            foreach ($results as $result) {
                $id[] = $result['id'];
            }
            
            //create the query 
            $qb = $this->createQueryBuilder('c');
            $query = $qb->select('c')
                    ->where(
                            $qb->expr()->In('c.id', ':txn_id')
                    )
                    ->setParameter('txn_id', $id)
                    ->getQuery();
            $response = $query->getResult();
        }
        return $response;
    }

    /**
     * finding the shop pending transaction 
     * @return object array
     */
    public function getShopAllPedningTransaction($shop_id, $limit_start, $limit_size) {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('sm')
                ->where('sm.shopId =:sid','sm.status =:status')
                ->setParameter('sid', $shop_id)
                ->setParameter('status', 0)
                ->setFirstResult($limit_start)
                ->setMaxResults($limit_size)
                ->getQuery();

           return $result = $qb->getResult();   
    }
    
    /**
     * finding the shop pending transaction 
     * @return object array
     */
    public function getShopAllPedningTransactionCount($shop_id) {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('sm')
                ->where('sm.shopId =:sid','sm.status =:status')
                ->setParameter('sid', $shop_id)
                ->setParameter('status', 0)
                ->getQuery();

          $result = $qb->getResult(); 
          return $result;
    }
}
