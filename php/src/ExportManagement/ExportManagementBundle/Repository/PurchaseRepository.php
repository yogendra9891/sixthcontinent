<?php

namespace ExportManagement\ExportManagementBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Utility\UtilityBundle\Utils\Utility;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

/**
 * PurchaseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PurchaseRepository extends EntityRepository
{
    /**
     * getting the purchase transaction 
     * @param none
     * @return array
     */
    public function getPurchaseTransaction() {
       
        //$today          =  new \DateTime('now');
        // $start_date     =  $today->format('Y-m-d');
        // $tomorrow       =  new \DateTime('tomorrow');
        // $end_date       =  $tomorrow->format('Y-m-d');

        // //create the query
        // $query = $this->createQueryBuilder('c');
        // $query->select();
        //       // ->Where('c.createdAt >=:create_at', 'c.createdAt <:end_at')
        //       // ->setParameter('create_at', $start_date)
        //       // ->setParameter('end_at', $end_date);

        // $result     = $query->getQuery();
        // $result_res = $result->getResult();
        // return $result_res;

        $response = $this->getEntityManager()
            ->createQuery("SELECT crd.id,crd.cardId,crd.initAmount,crd.timeCreatedH,crd.maxUsageInitPrice,crd.sellerId,crd.walletCitizenId FROM  Transaction\WalletBundle\Entity\Card crd")
           ->getResult();
    
          $data = array();
        
           foreach ($response as $getdata) {

               $res = $this->getEntityManager()

               ->createQuery("SELECT str_to_usr.storeId , str_to_usr.userId
                        FROM StoreManager\StoreBundle\Entity\UserToStore str_to_usr
                        WHERE str_to_usr.storeId = ".($getdata['sellerId'])." ")
                ->getResult();
          
                  if(!empty($res)){
                     $getdata['buyerid'] = $res[0]['userId'];
                  }
                  else{
                     $getdata['buyerid'] = '';
                   }

                   $res1 = $this->getEntityManager()
                   ->createQuery("SELECT sx.ciTransactionSystemId,sx.applicationId,sx.paypalTransactionId
                            FROM SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction sx
                            WHERE sx.ciTransactionSystemId = ".($getdata['id'])." ")
                    ->getResult();
              
                   if(!empty($res1)){

                       $getdata['card'] = '1';
                       $getdata['ciTransactionSystemId'] = $res1[0]['ciTransactionSystemId'];
                     
                      }
                     
                    else{
                     
                        $getdata['card'] = '0';
                        $getdata['ciTransactionSystemId'] = '';
                    
                     }
            
               $data[] = $getdata;
            } 

         return $data;
    }
    
    /**
     * get counter of yesterday purchase
     */
    public function getPurchaseCounter() {
        $today          =  new \DateTime('now');
        $start_date     =  $today->format('Y-m-d');
        $counter = 0;
        //create the query
        $query = $this->createQueryBuilder('c');
        $query->select()
              ->Where('c.createdAt =:create_at')
              ->orderBy('c.id', 'DESC')
              ->setMaxResults(1)
              ->setParameter('create_at', $start_date);

        $result     = $query->getQuery();
        $result_res = $result->getResult();
        if (count($result_res)) {
            $result = $result_res[0];
            $counter_string = $result->getNumeroQuietanza();
            $purchase_counter = ApplaneConstentInterface::PURCHASE_COUNTER;
            $counter_trimmed_string = Utility::getRightSubString($counter_string, $purchase_counter);
            $counter  = Utility::getIntergerValue($counter_trimmed_string);
        }
        return $counter;       
    }
}
