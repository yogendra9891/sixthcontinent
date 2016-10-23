<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ShopRegPaymentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ShopRegPaymentRepository extends EntityRepository
{
    /**
     * get the daily registration payment info for shop
     * @return object array
     */
    public function getDailyShopRegistartionInfo()
    {
        $yesterday      =  new \DateTime('yesterday');
        $start_date     =  $yesterday->format('Y-m-d');
        $today          =  new \DateTime('today');
        $end_date       =  $today->format('Y-m-d');

        //create the query
        $query = $this->createQueryBuilder('c');
        $query->select('c.id, c.shopId as shop_id, c.regFee as registration_fee, c.vat as registration_vat,  c.created_at as transaction_time, c.transactionCode as trasaction_code, s.businessName as business_name')
              ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'c.shopId = s.id');

        //check the payment type is R(registration) or T(registration type + pending amounts)
        $query->where('c.transactionType =:transaction_type')
                ->setParameter('transaction_type', 'R'); //registration type

        $query->orWhere('c.transactionType =:transaction_type1')
                ->setParameter('transaction_type1', 'T'); //registration type + pending amounts
        
        $query->andWhere('c.created_at >=:create_at', 'c.created_at <:end_at')
              ->setParameter('create_at', $start_date)
              ->setParameter('end_at', $end_date);

        $result     = $query->getQuery();
        $result_res = $result->getResult();
        return $result_res;
    }
    
     /**
      * find the trnsaction that are paid to sixthcontinent
      * @param type $transaction_data
      * @return $object array
      */
    public function getShopDailyPayToSixthContinent()
    {
        //for current day.
        $yesterday      =  new \DateTime('yesterday');
        $start_date     =  $yesterday->format('Y-m-d'); //yesterday date
        $today          =  new \DateTime('today');
        $end_date       =  $today->format('Y-m-d'); //today day date,,
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.id, c.shopId as shop_id, c.pendingAmount as amount, c.pendingAmountVat as pending_vat, c.status as status, c.created_at, c.transactionCode as trasaction_code,  s.businessName as business_name')
           ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'c.shopId = s.id');
        
        //check the payment type is R(registration)
        $qb->where('c.transactionType =:transaction_type')
                ->setParameter('transaction_type', 'P'); //pending type

        $qb->orWhere('c.transactionType =:transaction_type1')
                ->setParameter('transaction_type1', 'T'); //registration type + pending amounts
        
        $qb->andWhere('c.created_at >=:start_at', 'c.created_at <:end_at')
           ->setParameter('start_at', $start_date)
           ->setParameter('end_at', $end_date); 
        
        $query    = $qb->getQuery();
        $response = $query->getResult();
        return $response;        
    }
    
    
     /**
      * find the transaction that are paid to sixthcontinent by shop (Registration fee + pending amount)
      * @param none
      * @return $object array
      */
    public function getDailyShopRegistartionFeeReceivedInfo()
    {
        //for current day.
        $yesterday      =  new \DateTime('yesterday');
        $start_date     =  $yesterday->format('Y-m-d'); //yesterday date
        $next_date      =  new \DateTime('today');
        $end_date       =  $next_date->format('Y-m-d'); //today day date,,
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.id, c.shopId as shop_id, c.amount as amount, c.regFee as registration_fee, c.pendingAmount as pending_amount, c.vat as registration_vat, c.pendingAmountVat as pending_vat, c.transactionType as transaction_type,  c.status as status, c.created_at, c.transactionCode as trasaction_code,  s.businessName as business_name')
           ->innerJoin('StoreManagerStoreBundle:Store', 's', 'WITH', 'c.shopId = s.id');
        
        //check the payment type is R(registration)
        $qb->where('c.transactionType =:transaction_type')
                ->setParameter('transaction_type', 'R'); //registration fee

        $qb->orWhere('c.transactionType =:transaction_type1')
                ->setParameter('transaction_type1', 'T'); //registration type + pending amounts
        
        $qb->orWhere('c.transactionType =:transaction_type2')
                ->setParameter('transaction_type2', 'P'); //pending amounts
        
        $qb->andWhere('c.created_at >=:start_at', 'c.created_at <:end_at')
           ->setParameter('start_at', $start_date)
           ->setParameter('end_at', $end_date);
        $query    = $qb->getQuery();
        $response = $query->getResult();
        return $response;        
    }
    
    /**
     * get previous payment history of shop
     * @param type $shop_id
     * @return type
     */
    public function getPreviousPaymentOfShop($shop_id,$limit_start,$limit_size) {
       
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
       
         $qb->where('c.shopId =:shop_id')
            ->andWhere(
                $qb->expr()->In('c.transactionType', ':sid')
            )
         ->setParameter('shop_id', $shop_id)
         ->setParameter('sid', array('P','T','R'));
        $query    = $qb->setFirstResult($limit_start)
                ->setMaxResults($limit_size)
                ->getQuery();    
        $response = $query->getResult();  
        return $response; 
    }
    
     /**
     * get previous payment history of shop count
     * @param type $shop_id
     * @return type
     */
    public function getPreviousPaymentOfShopCount($shop_id) {
       
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
       
         $qb->where('c.shopId =:shop_id')
            ->andWhere(
                $qb->expr()->In('c.transactionType', ':sid')
            )
         ->setParameter('shop_id', $shop_id)
         ->setParameter('sid', array('P','T','R'));
        $query    = $qb->getQuery();    
        $response = $query->getResult();  
        return count($response);
    }
    
    /**
     * 
     * @param type $shop_id
     * @return type
     */
    function getAllPaymentGivenToSix($shop_id) {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
            ->select('sum(sm.amount) AS total_amount')
            ->where('sm.shopId =:sid')
            ->setParameter('sid', $shop_id)
            ->getQuery();
             
        $result = $qb->getResult();
        if(isset($result[0]['total_amount'])) {
            return $result[0]['total_amount']; 
        }else{
            return 0;
        }
         
    }
}
