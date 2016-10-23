<?php

namespace Paypal\PaypalIntegrationBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SpecialShopOfferesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SpecialShopOfferesRepository extends EntityRepository
{
    
    /**
     *  function for getting if the shop comes under the special previalage
     * @param type $shop_id
     * @return type
     */
    public function getPaypalTransactionFeeStatus($shop_id) {
        $paypal_paystatus = false;
        $qb = $this->createQueryBuilder('s');
              $qb->where('s.shopId =:store_id')		
                ->andWhere('s.status=:status')
		->setParameter('store_id',$shop_id)
		->setParameter('status',1);
        $query = $qb->getQuery();
        $result = $query->getResult();
        
        if(count($result) > 0) {
            $shop_details = $result[0];
            $paypal_paystatus = $shop_details->getPaypalPayment();
        }
        return $paypal_paystatus;
    }
}
