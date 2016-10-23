<?php

namespace Transaction\CitizenIncomeBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Transaction\CitizenIncomeBundle\Entity\CiFromAllNation;
use Transaction\CitizenIncomeBundle\Interfaces\ICitizenIncome;
use Transaction\WalletBundle\Entity\WalletCitizen;

/**
 * CiFromAllNationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CiFromAllNationRepository extends EntityRepository implements ICitizenIncome {

    public static $TRS_NOT_TO_REDISTRIBUTE = 0; // if aa transaction has not been approved for redistribution
    public static $TRS_READY_TO_REDISTRIBUTE = 8; // if the sixthcontinent charge hass be taken
//    public static $TRS_TO_REDISTRIBUTE_NEXT_DAY = 2 ;//  if a transaction has 
    public static $TRS_REDISTRIBUTED = 9; // if the transaction has already been DISTRIBUTED
    public static $TRS_COMPLETED = "COMPLETED";

    /**
     * 
     * @param int $time_end_transaction (users that when the last transaction done they were registered)
     * @return type
     */
    public function getTotalUsers($time_end_transaction, $transaction_id = null) {
        $date = new \DateTime;

        $date->setTimestamp($time_end_transaction);

        return $this->getEntityManager()
                        ->createQuery("SELECT u.id   FROM UserManager\Sonata\UserBundle\Entity\User u
                             where u.createdAt <= :createdAt
                             ")
                        ->setParameter(":createdAt", $date)
                        ->getResult();
    }

    /**
     * 
     * @param int $init_time
     * @param int $end_time
     * @param array $transaction_id
     */
    public function getTotalAmountBaseCurrency($init_time, $end_time, $transaction_id = null) {
        return $this->getEntityManager()
                        ->createQuery("
                             SELECT sum(t.allCountryCharge) as totoal_amount_base_currency FROM Transaction\TransactionSystemBundle\Entity\Transaction t
                             where  t.status = :status
                             and    t.redistributionStatus = :redistributionStatus
                             and    t.timeInit >  :timeInit                             
                             and    t.timeClose <=  :timeClose                             
                            ")
                        ->setParameter(":status", self::$TRS_COMPLETED)
                        ->setParameter(":redistributionStatus", self::$TRS_READY_TO_REDISTRIBUTE)
                        ->setParameter(":timeInit", $init_time)
                        ->setParameter(":timeClose", $end_time)
                        ->getResult();
    }

    /**
     * @param array $single_shares :
     *      single_share amout to give to each wallet
     *      remainder remainder for next transaction
     * @param array $total_user :
     *      total_user : count users
     *      users :users array 
     *      
     * @param int $type
     * @param string $base_currency
     */
    public function updateCitizenWallet($single_share, $total_user, $time_init_transaction, $time_end_transaction, $sixc_transaction_id) {
        $amount_to_increase = $single_share["single_share"];
        $ids = "";
        foreach ($total_user["users"] as $key => $value) {
            $ids.=$value["id"] . ",";
        }
        $ids = substr($ids, 0, -1);


        $walletcitizen_repository = $this->getEntityManager()
                ->getRepository('WalletBundle:WalletCitizen');
        $result = $walletcitizen_repository->updateWalletBasedToConvertionRation($ids, $amount_to_increase);

        if ($result) {
            $hasBeenShared = TRUE;
            $this->updateHistory($hasBeenShared, $single_share, $total_user, $time_init_transaction, $time_end_transaction ,$sixc_transaction_id );
        }
    }

    /**
     * 
     * @param array $single_share
     * @param array $total_user
     * @param int $time_init_transaction : init transaction time 
     * @param int $time_end_transaction : end transaction time
     * @param string  $base_currency : EUR in general is the most valauable currency
     */
    public function updateHistory($hasBeenShared, $single_share, $total_user, $time_init_transaction, $time_end_transaction, $sixc_transaction_id) {
        $em = $this->getEntityManager();
        $currency_repo = $em->getRepository('WalletBundle:Currency');

        $model = new CiFromAllNation;
        $model->setTimeInitTransaction($time_init_transaction);
        $model->setTimeEndTransaction($time_end_transaction);

        //Time objct creation
        $time_obj = new \DateTime;

        //Set up init time woth same obj
        $time_obj->setTimestamp($time_init_transaction);
        $model->setTimeInitTransactionH($time_obj);

        //Set up end time woth same obj
        $time_obj->setTimestamp($time_end_transaction);
        $model->setTimeEndTransactionH($time_obj);

        //Set up now time with same timestamp
        $now = time();
        $model->setTimeRedistribution($now);
        $time_obj->setTimestamp($now);
        $model->setTimeRedistributionH($time_obj);

        $model->setTotalUser($total_user["total_user"]);
        $model->setTotalAmountBaseCurrency($single_share["total_amount"]);
        $model->setAmountNotRedistributedBaseCurrency($single_share["remainder"]);
        $model->setSingleShareBaseCurrency($single_share["single_share"]);
        $model->setSingleShareUsd(ceil($currency_repo->getValueBaseCurrency("USD", $single_share["single_share"])));
        $model->setSingleShareEur(ceil($currency_repo->getValueBaseCurrency("EUR", $single_share["single_share"])));
        $model->setSingleShareInr(ceil($currency_repo->getValueBaseCurrency("INR", $single_share["single_share"])));
        $model->setSingleShareGbp(ceil($currency_repo->getValueBaseCurrency("GBP", $single_share["single_share"])));
        $model->setSingleShareYen(ceil($currency_repo->getValueBaseCurrency("YEN", $single_share["single_share"])));
        $model->setSingleShareDkk(ceil($currency_repo->getValueBaseCurrency("DKK", $single_share["single_share"])));
        $model->setSingleShareChf(ceil($currency_repo->getValueBaseCurrency("CHF", $single_share["single_share"])));
        $model->setSingleShareSek(ceil($currency_repo->getValueBaseCurrency("SEK", $single_share["single_share"])));
        $model->setHasBeenShared($hasBeenShared);
        $em->persist($model);
        $em->flush();
        $em->clear();
        return $model;
    }

}
