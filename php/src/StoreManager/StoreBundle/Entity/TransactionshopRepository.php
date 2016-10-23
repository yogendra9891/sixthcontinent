<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * TransactionshopRepository
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionshopRepository extends EntityRepository
{
    /**
     * Get top shope revenue
     * @param int $limit_start
     * @param int $limit_size
     * @return array
     */
    public function getTopShopsPerRevenue($limit_start, $limit_size)
    {
      $result = array();
      $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->setParameter('isactive', 1)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->setFirstResult($limit_start)
              ->setMaxResults($limit_size)
              ->getQuery();
             
         $result = $qb->getResult();
        
         return $result; 
    }
    
    /**
     * Get transaction of yesterday date
     * @param date $yesterday_date
     * @return array
     */
    public function getYesterdayTransactionShop()
    {
        $today          =  new \DateTime('now');
        $start_date     =  strtotime($today->format('Y-m-d'));
        $next_day       =  new \DateTime('tomorrow');
        $end_date       =  strtotime($next_day->format('Y-m-d'));
        
        $qb = $this->createQueryBuilder('u');
        $qb->andWhere('u.dataJob >=:create_at', 'u.dataJob <:end_at','u.paymentStatus =:pay_status' )
            ->setParameter('create_at', $start_date)
            ->setParameter('end_at', $end_date)
            ->setParameter('pay_status', 0);
            $query = $qb->getQuery();
            $result = $query->getResult();
            return $result;
    }
    
    /**
     * 
     * @param type $transaction_shop_ids
     * @return boolean
     */
    public function updateTransactionShopStatus($transaction_shop_ids){
        //create the query
        $qb = $this->createQueryBuilder('sm');
        $query =   $qb->update()
                 ->set('sm.paymentStatus', '1')
                  ->where(
                        $qb->expr()->In('sm.id', ':sid')
                    )
                  ->setParameter('sid', $transaction_shop_ids)
                 ->getQuery();
        $response = $query->getResult();
        return true;
    }
    
    
    /**
     * Get revenue for the shop
     * @param int $shop_id
     * @return array
     */
    public function getShopsRevenue($shop_id)
    {
        $result_blank = array('tot_fatturato'=>"0");
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, sm.userId, co.storeId')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->where('co.storeId =:sid')
              ->setParameter('sid', $shop_id)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         
         if(count($result)>0){
         return $result[0]; 
         }
         return $result_blank;
    }
    
     /**
     * Get shop with revenue before specified date
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateEnd($time, $start_amount, $end_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt <=:created_at')
              ->having('tot_fatturato >=:startamount')
              ->andHaving('tot_fatturato <:endamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 0)
              ->setParameter('created_at', $time)
              ->setParameter('startamount', $start_amount)
              ->setParameter('endamount', $end_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
    /**
     * Get shop with revenue in date range
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateRange($start_time, $end_time, $start_amount, $end_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_start')
              ->andWhere('s.createdAt <:created_end')
              ->having('tot_fatturato >=:startamount')
              ->andHaving('tot_fatturato <:endamount')

              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_start', $start_time)
              ->setParameter('created_end', $end_time)
              ->setParameter('startamount', $start_amount)
              ->setParameter('endamount', $end_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
    /**
     * Get shop with revenue after specified date
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateStart($time, $start_amount, $end_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_at')
              ->having('tot_fatturato >=:startamount')
              ->andHaving('tot_fatturato <:endamount')

              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_at', $time)

              ->setParameter('startamount', $start_amount)
              ->setParameter('endamount', $end_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
    
     /**
     * Get shop with revenue before specified date
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateEndGreater($time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt <:created_at')
              ->having('tot_fatturato >=:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 0)
              ->setParameter('created_at', $time)
              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
    /**
     * Get shop with revenue in date range
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateRangeGreater($start_time, $end_time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_start')
              ->andWhere('s.createdAt <:created_end')
              ->having('tot_fatturato >=:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_start', $start_time)
              ->setParameter('created_end', $end_time)
              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    

    
    /**
     * Get shop with revenue after specified date
     * @param type $time
     * @return array sa
     */
    public function getReminderShopsRevenueInDateStartGreater($time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_at')
              ->having('tot_fatturato >=:startamount')

              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_at', $time)


              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
     /**
     * Get shop with revenue in date range
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueInDateRangeAllShop($start_time, $end_time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_start')
              ->andWhere('s.createdAt <:created_end')
              ->having('tot_fatturato >=:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 0)
              ->setParameter('created_start', $start_time)
              ->setParameter('created_end', $end_time)
              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
     /**
     * Get shop with revenue before specified date
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueForOldShop($time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt,s.paymentStatus')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt <:created_at')
              ->having('tot_fatturato >:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_at', $time)
              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
     /**
     * Get shop with revenue before specified date
     * @param type $time
     * @return array
     */
    public function getReminderShopsRevenueForNewShop($time, $start_amount)
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
              ->select('sum(sm.totDare/1000000) AS tot_fatturato, co.userId, co.storeId, s.createdAt,s.paymentStatus')
              ->innerJoin('StoreManagerStoreBundle:UserToStore', 'co', 'WITH', 'sm.userId = co.storeId')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'co.storeId = s.id')
              ->where('s.isActive =:isactive')
              ->andWhere('s.shopStatus =:shopstatus')
              ->andWhere('s.creditCardStatus =:cardstatus')
              ->andWhere('s.createdAt >=:created_at')
              ->having('tot_fatturato >:startamount')
              ->setParameter('isactive', 1)
              ->setParameter('shopstatus', 1)
              ->setParameter('cardstatus', 1)
              ->setParameter('created_at', $time)
              ->setParameter('startamount', $start_amount)
              ->groupBy('sm.userId')
              ->orderBy('tot_fatturato','desc')
              ->getQuery();
             
         $result = $qb->getResult();
         return $result;
    }
    
     /**
     * Get revenue for the shop
     * @param int $shop_id
     * @return array
     */
    public function getShopsPendingAmount($shop_id)
    {
      
        $qb = $this->createQueryBuilder('sm')
              //->select('sum(sm.totQuota/1000000) AS tot_quota, sm.userId')             
              ->where('sm.userId =:sid')
              ->andWhere('sm.paymentStatus =:payStatus')
              ->setParameter('sid', $shop_id)
              ->setParameter('payStatus', 0)
              ->getQuery();
             
        $result = $qb->getResult();
        return $result; 
    }
}
