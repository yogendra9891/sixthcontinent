<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CitizenIncomeToPayToStoreRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CitizenIncomeToPayToStoreRepository extends EntityRepository {

    /**
     * finding the shop transaction of a week interval, using in export bundle.
     * @return object array
     */
    public function getShopWeeklyTransaction() {
        //calculating previous(6 days) 7 days interval including current day.
        $yesterday      =  new \DateTime('yesterday');
        $end_date       =  (strtotime($yesterday->format('Y-m-d')) + (24*60*60-1)); //yesterday date and add 24 hours - 1 second(eg. 2014-11-28 23:59:59)
        $previous_date  =  new \DateTime('-7 days');
        $start_date     =  strtotime($previous_date->format('Y-m-d'));

        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.id, c.userId as shop_id, c.dataMovimento, sum(c.totAvere) as amount')
           ->where('c.dataJob >=:start_at', 'c.dataJob <=:end_at')
           ->groupBy('c.userId')
           ->setParameter('start_at', $start_date)
           ->setParameter('end_at', $end_date);
        $query    = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    
    }
