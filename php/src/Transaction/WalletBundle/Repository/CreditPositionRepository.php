<?php

namespace Transaction\WalletBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Transaction\TransactionSystemBundle\Interfaces\ICredit;
use Transaction\WalletBundle\Entity\CreditPosition;
use Transaction\TransactionSystemBundle\Services\TransactionManager;
use \Transaction\CommercialPromotionBundle\Interfaces\IPromotion;
/**
 * CreditPositionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CreditPositionRepository extends EntityRepository implements ICredit,  IPromotion {

    private $_CPMaxUsageInitPrice = 50;

    public function getCreditPosition($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.sellerId=:sellerId AND c.walletCitizenId=:walletCitizenId')
                ->setParameter('sellerId', $data['seller_id'])
                ->setParameter('walletCitizenId', $data['buyer_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    public function getSellerCreditPosition($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('SUM(c.amount) AS totalCreditPosition')
                ->where('c.sellerId=:sellerId AND c.walletCitizenId=:walletCitizenId AND c.amount > 0')
                ->setParameter('sellerId', $data['seller_id'])
                ->setParameter('walletCitizenId', $data['wallet_citizen_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    public function getUsageCredits($data, $returnData = null) {
        $response[0]['creditPositionAvailable'] = 0;
        $BusinessResponse[0]['premiumPosition'] = 0;

        $response = $this->getEntityManager()
                ->createQuery("SELECT u.id, u.creditPositionAvailable 
                            FROM Transaction\WalletBundle\Entity\WalletCitizen u
                             where u.buyerId = :buyerId
                             ")
                ->setParameter(":buyerId", $data['buyer_id'])
                ->getResult();

        $BusinessResponse = $this->getEntityManager()
                ->createQuery("SELECT u.id, u.premiumPosition 
                                FROM Transaction\WalletBundle\Entity\WalletBusiness u
                                 where u.sellerId = :sellerId
                                 ")
                ->setParameter(":sellerId", $data['seller_id'])
                ->getResult();

        if (!empty($response)) {
            if ($response[0]['creditPositionAvailable'] > $BusinessResponse[0]['premiumPosition']) {
                $response[0]['available_amount'] = $BusinessResponse[0]['premiumPosition'];
            } else {
                $response[0]['available_amount'] = $response[0]['creditPositionAvailable'];
            }

            $response[0]['maxUsageInitPrice'] = $this->_CPMaxUsageInitPrice;
            return $response[0];
        }
    }

    public function updateCredits($data) {
        /* $response = $this->getEntityManager()
          ->createQuery("UPDATE Transaction\WalletBundle\Entity\WalletCitizen u
          SET u.creditPositionAvailable = u.creditPositionAvailable - :amountUsed
          where u.buyerId = :buyerId
          ")
          ->setParameter(':amountUsed', $data['amount_used'])
          ->setParameter(':buyerId', $data['buyer_id'])
          ->getResult(); */

        $response = $this->getEntityManager()
                ->createQuery("UPDATE Transaction\WalletBundle\Entity\WalletBusiness u
                             SET u.premiumPosition = u.premiumPosition - :amountUsed
                             where u.sellerId = :sellerId
                             ")
                ->setParameter(':amountUsed', $data['amount_used'])
                ->setParameter(':sellerId', $data['seller_id'])
                ->getResult();

        if ($data['amount_used'] > 0) {
            /* generate Cards */
            $em = $this->getEntityManager();
            $TrManager = new TransactionManager();
            $dateTime = new \DateTime('now');
            $Timestamp = strtotime(date('Y-m-d H:i:s'));

            /* Insert new generated card detail */
            $cardPost = new CreditPosition();
            $cardPost->setpremiumId($TrManager->getTransactionIdToken(10));
            $cardPost->settimeCreatedH($dateTime);
            $cardPost->settimeCreated($Timestamp);
            $cardPost->setcurrency($TrManager->getBuyerCurrency($data['buyer_id']));
            $cardPost->setmaxUsageInitPrice($this->_CPMaxUsageInitPrice);
            $cardPost->setsellerId($data['seller_id']);
            $cardPost->setwalletCitizenId($data['wallet_citizen_id']);
            $cardPost->setamount($data['amount_used']);

            $em->persist($cardPost);
            $em->flush();
            $em->clear();
            
            if($cardPost->getId()) {
                $returnArr = array(
                    'id' => $cardPost->getId(),
                    'amount_used' => $data['amount_used']
                );
                return $returnArr;
            }
        }
    }

    /**
     * Create the commercial promotion of type coupon
     * 
     * @param array $param
     * @return bolleant
     */
    public function createPromotion($param) {
        $validParam = $this->validParamPromtion($param);
        if ($validParam["status"]) {
            $em = $this->getEntityManager();

            $date = new \DateTime("now");
            $model = new \Transaction\CommercialPromotionBundle\Entity\CommercialPromotion;
            $model->setInitQuantity(1);
            $model->setAvailableQuantity(1);

            
            $model->setDiscountAmount($this->_CPMaxUsageInitPrice);
            $model->setCommercialPromotionTypeId($param["promotion_type"]);
            $model->setSellerId($param["seller_id"]);
            $model->setSexFemale($param["sex_female"]);
            $model->setExtraInfo(FALSE);
            $model->setPrice($param["price"] * 100);


            $time_obj = new \DateTime("Now");
            $model->setTimeCreateH($time_obj);
            $model->setTimeCreate($time_obj->getTimestamp());
            
            $model->setTimeStartH($time_obj);
            $model->setTimeStart($time_obj->getTimestamp());
            $model->setTimeEndH($time_obj);
            $model->setTimeEnd($time_obj->getTimestamp());
            $model->setMaxUsageInitPrice($this->_CPMaxUsageInitPrice);
            $model->setStatus(1);

            $em->persist($model);
            $em->flush();
            $em->clear();
            if($model->getId() > 0){
                $this->increaseCredits($model->getPrice() , $model->getSellerId());
            }
            return $model;
        } else {
            return $validParam;
        }
    }

    /**
     * Check if the comerrcial promotion alues are all well defined
     * @param type $check_param
     */
    public function validParamPromtion($check_param) {
        return $message = array("status" => true, "code" => "You are late");
    }
    
    /**
     * 
     * @param type Commercial Promotion
     */
    public function increaseCredits($amount_used,$seller_id) {
        //
        $data['amount_used'] = - $amount_used;
        $data['seller_id'] = $seller_id ;
        $this->updateCredits($data);
        return ;
        
    }
}
