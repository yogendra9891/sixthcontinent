<?php

namespace Transaction\TransactionSystemBundle\Repository;
use Transaction\TransactionSystemBundle\Entity\TransactionPaymentInformation;
use Doctrine\ORM\EntityRepository;

/**
 * TransactionPaymentInformationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionPaymentInformationRepository extends EntityRepository
{  
    /*
     * Add transaction payment records
     * @param $paymentObj Obj
     * @param $transObj Obj
     */
    public function addtransactionRecord($paymentObj, $transObj) {
        $time = new \DateTime('now');
        $timeStap = strtotime(date('Y-m-d H:i:s'));
        $em = $this->getEntityManager();
        $TrType = new TransactionPaymentInformation();
        $TrType->setTransactionId($transObj['transaction_id']);
        $TrType->setCorrelationId($paymentObj->responseEnvelope->correlationId);
        $TrType->setBuild($paymentObj->responseEnvelope->build);
        $TrType->setPayKey($paymentObj->payKey);
        $TrType->setPaypalId($transObj['paypal_id']);
        $TrType->setPaymentExecStatus($paymentObj->paymentExecStatus);
        $TrType->setstatus($transObj['payment_status']);
        $TrType->setPrimaryUserEmail($transObj['primary_user_paypal_email']);
        $TrType->setPrimaryUserAmount($transObj['primary_user_amount']*100);
        $TrType->setSecondryUserEmail($transObj['secondry_user_paypal_email']);
        $TrType->setSecondryUserAmount($transObj['secondry_user_amount']*100);
        $TrType->setTimeInitH($time);
        $TrType->setTimeUpdatedH(NULL);
        $TrType->setTimeInit($timeStap);
        $TrType->setTimeUpdated(NULL);
        $em->persist($TrType);
        $em->flush();
        $em->clear();
        $TrTypeId = $TrType->getId();
        return $TrTypeId;
    }
    
    /*
     * Get transaction data
     */
    public function getTransactionDetail($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.transactionId=:transactionId')
                ->setParameter(':transactionId', $data['transaction_id']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        if(!empty($response)) {
            return $response[0];
        }
    }
    
    /*
     * Get transaction data by pay_key
     */
    public function getTransactionDetailBypayKey($data) {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
                ->where('c.payKey=:payKey')
                ->setParameter(':payKey', $data['pay_key']);
        $query = $qb->getQuery();
        $response = $query->getResult();
        if(!empty($response)) {
            return $response[0];
        }
    }
    
    /*
     * Update Transaction payment information
     */
    public function updateTransactionData($data) {
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', ':status')
                ->set('u.paymentSerialize', ':paymentSerialize')
                 ->set('u.timeUpdatedH', ':timeUpdatedH')
                 ->set('u.timeUpdated', ':timeUpdated')
                ->where('u.transactionId= :transactionId')
                ->setParameter("status", $data['status'])
                ->setParameter("paymentSerialize", $data['payment_serialize'])
                ->setParameter("transactionId", $data['transaction_id'])
                ->setParameter("timeUpdatedH", date('Y-m-d H:i:s'))
                ->setParameter("timeUpdated", strtotime(date('Y-m-d H:i:s')))
                ->getQuery();
        $reponse = $query->getResult();
        
        /* Initiliaze variable */
        $TrData = array();
        $TrPayInfo = array();
        $walletData = array();
        $offerInfo = array();
            
        $em = $this->getEntityManager();
        
        /* Get transaction detail */
        $TrData = $em->getRepository('TransactionSystemBundle:Transaction')
                                ->find($data['transaction_id']);

        /* Get transaction payment information */
        $TrPayInfo = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                                   ->getTransactionDetail(array('transaction_id' => $data['transaction_id']));
            
        /* Generate new shopping card */
        if($data['status'] == 'COMPLETED') {
            /* get buyer wallet data */
            $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                                         ->getWalletData($TrData->getbuyerId());
            
            $walletData = (!empty($walletData)) ? $walletData[0] : $walletData;
            
            /* Get offer information */
            $trSerialize = unserialize($TrData->gettransactionSerialize());
            $offer_id = $trSerialize['offer_id'];
            
            /* Create new shopping card */
            $param = array(
                'buyer_id' => $TrData->getbuyerId(),
                'wallet_citizen_id' => $walletData->getId(),
                'offer_id' => $offer_id,
                'sixc_transaction_id' => $TrData->getsixcTransactionId()
            );
            $shoppingCard = $em->getRepository('WalletBundle:ShoppingCard')
                                             ->addToCitizenWallet($param);
        }
        
        if($data['status'] == 'CANCELED') {
            /* Give back wallet available credits */
            $updateArr = array(
                'buyer_id' => $TrData->getbuyerId(),
                'ci_used' => $TrData->getcitizenIncomeUsed()
            );
            $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                                             ->returnWalletCitizenIncome($updateArr);
        }
        
        if ($reponse) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * Update transaction payment information from paypal IPN
     */
    public function updateTransactionFromIPN($data) {
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', ':status')
                 ->set('u.timeUpdatedH', ':timeUpdatedH')
                 ->set('u.timeUpdated', ':timeUpdated')
                ->where('u.payKey= :payKey')
                ->setParameter("status", $data['status'])
                ->setParameter("payKey", $data['pay_key'])
                ->setParameter("timeUpdatedH", date('Y-m-d H:i:s'))
                ->setParameter("timeUpdated", strtotime(date('Y-m-d H:i:s')))
                ->getQuery();
        $reponse = $query->getResult();

        if ($reponse) {
            return true;
        } else {
            return false;
        }
    }
}