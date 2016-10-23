<?php

namespace Utility\ApplaneIntegrationBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ShopTransactionsPaymentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ShopTransactionsPaymentRepository extends EntityRepository
{
    CONST MANUAL = 'MANUAL';
    CONST SYSTEM = 'SYSTEM';
    CONST S = 'S';
    /**
     * Get transactions whose status is pending
     */
    public function getAllPedningTransaction()
    {
        $result = array();
        $qb = $this->createQueryBuilder('sm')
                ->select('sm')
                ->where('sm.status =:status')
                ->setParameter('status', 0)
                ->getQuery();
        $result = $qb->getResult();   
        return $result;
    }
    
    /**
     * Get todays paid transactions by shop
     */
    public function getShopTransactions()
    {
        $today          =  new \DateTime('now');
        $start_date     =  $today->format('Y-m-d');
        $tomorrow       =  new \DateTime('tomorrow');
        $end_date       =  $tomorrow->format('Y-m-d');
        
        $result = array();
        $qb = $this->createQueryBuilder('s')
                ->select('s')
                ->where('s.status =:status', 's.paymentDate >=:start_at', 's.paymentDate <:end_at')
                ->setParameter('status', 1)
                ->setParameter('start_at', $start_date)
                ->setParameter('end_at', $end_date)
                ->getQuery();
        $result = $qb->getResult();
        return $result;
    }    
    
    /**
     * Get system and manual paid transactions by shop
     * finding the previous day data of manually done
     * finding the current day data of system done
     */
    public function getShopManualSystemTransactions()
    {
        $today          =  new \DateTime('now');
        $start_date     =  $today->format('Y-m-d');
        $tomorrow       =  new \DateTime('tomorrow');
        $end_date       =  $tomorrow->format('Y-m-d');
        $yesterday      = new \DateTime('yesterday');
        $yesterday_date = $yesterday->format('Y-m-d');
        $manual_mode    = self::MANUAL;
        $system_mode    = self::SYSTEM;
        $result = array();
        //getting table name
        $shop_transaction_payment_table = $this->getEntityManager()->getClassMetadata('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')->getTableName();
        $sql = "SELECT * from ".
               "$shop_transaction_payment_table where ".
               "(((payment_date >= '$start_date' AND payment_date < '$end_date' AND mode = '$system_mode')".
               " OR (payment_date >= '$yesterday_date' AND payment_date < '$start_date' AND mode = '$manual_mode')) AND status = 1)";
        $stmt = $this->getEntityManager()
               ->getConnection()
               ->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }    
    
     /**
     * Get system and manual paid transactions by shop 
     * finding the subscription previous day data of manually done
     * finding the subscription current day data of system done
     */
    public function getShopSubscirptionManualSystemTransactions()
    {
        $today          =  new \DateTime('now');
        $start_date     =  $today->format('Y-m-d');
        $tomorrow       =  new \DateTime('tomorrow');
        $end_date       =  $tomorrow->format('Y-m-d');
        $yesterday      = new \DateTime('yesterday');
        $yesterday_date = $yesterday->format('Y-m-d');
        $manual_mode    = self::MANUAL;
        $system_mode    = self::SYSTEM;
        $subscription_type = self::S;
        $pay_type       = "like '%$subscription_type%'";
        $result = array();
        //getting table name
        $shop_transaction_payment_table = $this->getEntityManager()->getClassMetadata('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')->getTableName();
        $sql = "SELECT * from ".
               "$shop_transaction_payment_table where ".
               "(((payment_date >= '$start_date' AND payment_date < '$end_date' AND mode = '$system_mode')".
               " OR (payment_date >= '$yesterday_date' AND payment_date < '$start_date' AND mode = '$manual_mode')) AND status = 1 AND pay_type $pay_type)";
        
        $stmt = $this->getEntityManager()
               ->getConnection()
               ->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }
}