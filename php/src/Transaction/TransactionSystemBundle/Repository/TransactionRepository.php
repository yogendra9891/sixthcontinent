<?php

namespace Transaction\TransactionSystemBundle\Repository;

use Transaction\TransactionSystemBundle\Entity\Transaction;
use Transaction\TransactionSystemBundle\Entity\BookTransaction;
use Transaction\TransactionSystemBundle\Entity\TransactionType;
use Transaction\TransactionSystemBundle\Services\TransactionManager;
use StoreManager\StoreBundle\Entity\Store;
use Transaction\WalletBundle\Entity\WalletCitizen;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class TransactionRepository extends EntityRepository {

    public static $TRANSACTION_COMPLETE_SUCCESFULY = "COMPLETED";
    static $PAY_IN_SHOP = "1"; // Payment in Shop
    static $PAY_OFFER = "2"; // 
    static $PAY_ONCE = "3"; // Sixthcontinent Connect
    static $PAYPAL_ONCE = "4"; // Shopping Card
    static $PAY_ONCE_OFFER = "5"; // Special offer 
    static $DEFAULT_CHARGE = "0.01";
    private $container;
    public static $TRNS_GATEWAY_REFERENCE_OFFER= "ALREADYPAID";

    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    
    public function updateProcessTransaction($data) {
        $q = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', '?1')
                ->set('u.timeUpdateStatusH', '?2')
                ->set('u.timeUpdateStatus', '?3')
                ->set('u.timeCloseH', '?4')
                ->set('u.timeClose', '?5')
                ->where('u.id=?6')
                ->setParameter(1, $data['status'])
                ->setParameter(2, $data['time_update_status_h'])
                ->setParameter(3, $data['time_update_status'])
                ->setParameter(4, $data['time_close_h'])
                ->setParameter(5, $data['time_close'])
                ->setParameter(6, $data['transaction_id']);
        if(isset($data["transactionGatewayReference"])){
                $q->set('u.transactionGateWayReference','?7')
                ->setParameter(7, $data['transactionGatewayReference']);
        }
        $query = $q->getQuery();
        $reponse = $query->getResult();

        if ($reponse) {
            return true;
        } else {
            return false;
        }
    }

    public function updateTransactionSerializeString($data) {
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.transactionSerialize', '?1')
                ->where('u.id=?2')
                ->setParameter(1, $data['transaction_serialize'])
                ->setParameter(2, $data['transaction_id'])
                ->getQuery();
        $reponse = $query->getResult();

        if ($reponse) {
            return true;
        } else {
            return false;
        }
    }

    public function shiftedTodayEconomy() {
        $qb = $this->createQueryBuilder('c');
        $qb->select('SUM(c.finalPrice) as fp, SUM(c.citizenIncomeUsed) as ci ')
                ->where('c.status=:status')
                // ->where('c.timeInitH>=:currentDate AND c.status=:status')
                //->setParameter('currentDate', date('Y-m-d').' 00:00:00')
                ->setParameter('status', 'COMPLETED');
        $query = $qb->getQuery();
        $response = $query->getResult();
        return $response;
    }

    public function getTotalTurnOverShop($id) {

        $qb = $this->createQueryBuilder('c');
        $qb->select("SUM(c.finalPrice + c.citizenIncomeUsed) as initPrice")
                ->where('c.sellerId=:sellerId')
                ->andwhere('c.status=:status')
                ->setParameter('sellerId', $id)
                ->setParameter('status', 'COMPLETED');
        $query = $qb->getQuery();
        $response = $query->getResult();

        $revenue = "0.00";

        if ($response[0]['initPrice']) {
            $revenue = $response[0]['initPrice'];
            $revenue = number_format($revenue / 100, 2, '.', '');
        }
        $response[0]['revenue'] = $revenue;
        return $response;
    }

    /*
     * Process transaction
     * @param request Obj $de_serialize
     */

    public function getProcessTransaction($de_serialize, $calcData) {
        /* Get transaction manager service */

        $TrManager = new TransactionManager();

        /* Set transaction label */
        if ($de_serialize['do_transaction'] == 'without_credit' || $de_serialize['do_transaction'] == 'with_credit') {
            $TrTypeId = self::$PAY_IN_SHOP;
        } elseif ($de_serialize['do_transaction'] == 'PAY_ONCE_OFFER') {
            $TrTypeId = self::$PAY_ONCE_OFFER;
        } elseif ($de_serialize['do_transaction'] == 'paypal_once') {
            $TrTypeId = self::$PAYPAL_ONCE;
        } elseif ($de_serialize['do_transaction'] == 'PAY_OFFER') {
            $TrTypeId = self::$PAY_OFFER;
        } elseif ($de_serialize['do_transaction'] == 'PAY_ONCE') {
            $TrTypeId = self::$PAY_ONCE;
        }

        $em = $this->getEntityManager();
        $buyer_id = $de_serialize['buyer_id'];

        $time = new \DateTime('now');
        $timestamp = strtotime(date('Y-m-d H:i:s'));

        /* Process Transaction Type Request */
        $amount = $TrManager->getPriceFormat($de_serialize['amount']);
        $finalPrice = $TrManager->getPriceFormat($de_serialize['amount']);

        /* Setup final price for the transaction */
        if ($de_serialize['do_transaction'] == 'with_credit') {
            $discountUsed = $calcData['discount'];
            $finalPrice = $calcData['cashpayment'];
            $ciUsed = $calcData['usable_citizen_income'];
            $serializeString = serialize($calcData['transaction_serialize']);
        } elseif ($de_serialize['do_transaction'] == 'paypal_once') {
            $discountUsed = $de_serialize['discount_value'];
            $finalPrice = $de_serialize['cashpayment'];
            $ciUsed = $de_serialize['sixthcontinent_contribution'];
            $serializeString = serialize($de_serialize['transaction_serialize']);
            $amount = $de_serialize['amount'];
        } elseif($de_serialize['do_transaction'] == 'PAY_ONCE_OFFER' || $de_serialize['do_transaction'] == 'PAY_ONCE') {
            $discountUsed = $de_serialize['discount'];
            $finalPrice = $de_serialize['cashpayment'];
            $ciUsed = $de_serialize['citizen_income_used'];
            $amount = $de_serialize['init_price'];
            $serializeString = NULL;
        } else {
            $discountUsed = 0;
            $ciUsed = 0;
            $serializeString = NULL;
        }

        /* Get the affilation charges */
        if($de_serialize['do_transaction'] == 'PAY_ONCE_OFFER' || $de_serialize['do_transaction'] == 'PAY_ONCE') {
            $sellerPc = $de_serialize['seller_pc'];
            $affCharge['citizen_aff_charge'] = $de_serialize['citizen_aff_charge'];
            $affCharge['shop_aff_charge'] = $de_serialize['shop_aff_charge'];
            $affCharge['friends_follower_charge'] = $de_serialize['friends_follower_charge'];
            $affCharge['buyer_charge'] = $de_serialize['buyer_charge'];
            $affCharge['sixc_charge'] = $de_serialize['sixc_charge'];
            $affCharge['all_country_charge'] = $de_serialize['all_country_charge'];
        } else {
            $affCharge['default_charge'] = self::$DEFAULT_CHARGE;
            $sellerPcCharge = $this->getSellerPC($de_serialize['seller_id']);
            $sellerPc = $sellerPcCharge['sellerPc'];
            $affArr = array(
                'init_amount' => $amount,
                'cash_payment' => $finalPrice,
                'citizen_income_used' => $ciUsed,
                'aff_data' => $sellerPcCharge
            );
            $affCharge = $this->affChargesofTransaction($calcData, $affArr);
        }
        
        /*get sixc amount percentage, or from external sources or from shop prams*/

        $sixcAmountPc = ( isset($de_serialize["sixc_amount_pc"])) ? $de_serialize["sixc_amount_pc"] : $this->getSixcAmountPC($de_serialize['seller_id'], $finalPrice, $ciUsed, $sellerPc);
        $sixcAmountPcVat = ( isset($de_serialize["sixc_amount_pc_vat"])) ? $de_serialize["sixc_amount_pc_vat"] : $this->getSixcAmountpcVat($de_serialize['seller_id'], $finalPrice, $ciUsed, $sellerPc);
        $post_data = new Transaction();
        $post_data->setStatus('PENDING');
        $post_data->setSixcTransactionId($TrManager->getTransactionIdToken('60'));
        $post_data->setSellerId($de_serialize['seller_id']);
        $post_data->setBuyerCurrency($TrManager->getBuyerCurrency($de_serialize['buyer_id']));
        $post_data->setSellerCurrency($TrManager->getSellerCurrency($de_serialize['seller_id']));
        $post_data->setBOverSCurrencyRation(($de_serialize['do_transaction'] == 'without_credit') ? '0' : '1'); //0 = without_credit, 1 = with_credit
        $post_data->setInitPrice($amount);
        $post_data->setFinalPrice($finalPrice);
        $post_data->setWithCredit(($de_serialize['do_transaction'] == 'without_credit') ? '0' : '1');
        $post_data->setDiscountUsed($discountUsed);
        $post_data->setCitizenIncomeUsed((!empty($ciUsed)) ? $ciUsed : '0');
        $post_data->setCouponUsed((!empty($calcData['coupon_used'])) ? $calcData['coupon_used'] : '0');
        $post_data->setCreditpayment((!empty($calcData['credit_position_used'])) ? $calcData['credit_position_used'] : '0');
        $post_data->setShoppingCardUsed((!empty($calcData['card_used'])) ? $calcData['card_used'] : '0');
        $post_data->setTimeInitH($time);
        $post_data->setTimeUpdateStatusH(NULL);
        $post_data->setTimeCloseH(NULL);
        $post_data->setTimeInit($timestamp);
        $post_data->setTimeUpdateStatus(NULL);
        $post_data->setTimeClose(NULL);
        $post_data->setBuyerId($de_serialize['buyer_id']);
        $post_data->setTransactionFee('0');
        $post_data->setSixcAmountPc($sixcAmountPc);
        $post_data->setSixcAmountPcVat($sixcAmountPcVat);
        $post_data->setSellerPc((!empty($sellerPc)) ? $sellerPc : 0);
        $post_data->setTransactionTypeId($TrTypeId);
        $post_data->setRedistributionStatus('0');
        $post_data->setCitizenAffCharge((!empty($affCharge)) ? $affCharge['citizen_aff_charge'] : $affCharge['default_charge']);
        $post_data->setShopAffCharge((!empty($affCharge)) ? $affCharge['shop_aff_charge'] : $affCharge['default_charge']);
        $post_data->setFriendsFollowerCharge((!empty($affCharge)) ? $affCharge['friends_follower_charge'] : $affCharge['default_charge']);
        $post_data->setBuyerCharge((!empty($affCharge)) ? $affCharge['buyer_charge'] : $affCharge['default_charge']);
        $post_data->setSixcCharge((!empty($affCharge)) ? $affCharge['sixc_charge'] : $affCharge['default_charge']);
        $post_data->setAllCountryCharge((!empty($affCharge)) ? $affCharge['all_country_charge'] : $affCharge['default_charge']);
        $post_data->settransactionSerialize($serializeString);

        $em->persist($post_data);
        $em->flush();
        $em->clear();
        $TransactionId = $post_data->getId();

        /* Update booking */
        $checkArr = array('paypal_once', 'PAY_ONCE_OFFER', 'PAY_ONCE');
        if ($TransactionId && !in_array($de_serialize['do_transaction'], $checkArr)) {
            $time = date('Y-m-d H:i:s');
            $timestamp = strtotime(date('Y-m-d H:i:s'));

            $bookingData = array(
                'booking_id' => $de_serialize['booking_id'],
                'transaction_id' => $TransactionId,
                'status' => '2',
                'time_update_status_h' => $time,
                'time_update_status' => $timestamp
            );
            $updateData = $em->getRepository('TransactionSystemBundle:BookTransaction')
                    ->updateBookingOnProcessTransaction($bookingData);
        }
        return $TransactionId;
    }

    /*
     * Calculate affilation charges for transaction
     */

    public function affChargesofTransaction($calcData, $affArr) {
        $cashPayment = $affArr['cash_payment'];
        //Maybe is the right one testing
        //$ciUsed = (!empty($calcData['transaction_serialize']['citizen_income_data']))? $calcData['transaction_serialize']['citizen_income_data']['availableAmount'] : 0;
        $ciUsed = (isset($affArr['citizen_income_used']))?$affArr['citizen_income_used']:0;
        $total = $cashPayment + $ciUsed;
        $returnData = array(
            'citizen_aff_charge' => floor($affArr['aff_data']['citizenAffCharge'] * $total),
            'shop_aff_charge' => floor($affArr['aff_data']['shopAffCharge'] * $total),
            'friends_follower_charge' => floor($affArr['aff_data']['friendsFollowerCharge'] * $total),
            'buyer_charge' => floor($affArr['aff_data']['buyerCharge'] * $total),
            'sixc_charge' => round($affArr['aff_data']['sixcCharge'] * $total),
            'all_country_charge' => floor($affArr['aff_data']['allCountryCharge'] * $total),
            'default_charge' => floor(self::$DEFAULT_CHARGE * $total)
        );
        return $returnData;
    }

    /*
     * Get seller pc
     * @param shop_id
     */

    public function getSellerPC($shop_id) {
        $response = $this->getEntityManager()
                ->createQuery("SELECT c.citizenAffCharge, c.shopAffCharge, c.friendsFollowerCharge, c.buyerCharge, c.sixcCharge, c.allCountryCharge, SUM(c.citizenAffCharge + c.shopAffCharge + c.friendsFollowerCharge + c.buyerCharge + c.sixcCharge + c.allCountryCharge) AS sellerPc 
                            FROM StoreManager\StoreBundle\Entity\Store c
                            WHERE c.id = :shopId
                            ")
                ->setParameter(":shopId", $shop_id)
                ->getResult();
        if (!empty($response)) {
            $response[0]['sellerPc'] = $response[0]['sellerPc'];
            return $response[0];
        }
    }

    /*
     * Get sixc seller amount percentage
     */

    public function getSixcAmountPC($shop_id, $finalPrice, $citizenIncome, $sellerPC) {
        $TrManager = new TransactionManager();
        $sixc_amount_pc = round(($finalPrice + $citizenIncome) * $sellerPC);
        return $sixc_amount_pc;
    }

    /*
     * Get sixc seller amount percentage with vat
     */

    public function getSixcAmountPCVat($shop_id, $finalPrice, $citizenIncome, $sellerPC) {
        $TrManager = new TransactionManager();
        $sixc_amount_pc = $this->getSixcAmountPC($shop_id, $finalPrice, $citizenIncome, $sellerPC);
        $vat = $TrManager->getCountryVat();
        $sixcVat = round(($finalPrice + $citizenIncome)*$sellerPC*$vat );
        return $sixcVat;
    }

    public function updateTransactionData($data) {
        $timeStamp = strtotime(date('Y-m-d H:i:s'));
        $query = $this->createQueryBuilder('u')
                ->update()
                ->set('u.status', ':status')
                ->set('u.timeUpdateStatusH', ':timeUpdateStatusH')
                ->set('u.timeUpdateStatus', ':timeUpdateStatus')
                ->set('u.timeCloseH', ':timeCloseH')
                ->set('u.timeClose', ':timeClose')
                ->where('u.id= :transactionId')
                ->setParameter("status", $data['status'])
                ->setParameter("transactionId", $data['transaction_id'])
                ->setParameter("timeUpdateStatusH", date('Y-m-d H:i:s'))
                ->setParameter("timeUpdateStatus", $timeStamp)
                ->setParameter("timeCloseH", date('Y-m-d H:i:s'))
                ->setParameter("timeClose", strtotime(date('Y-m-d H:i:s')))
                ->getQuery();
        $reponse = $query->getResult();
                
        if ($reponse) {
            $returnArr = array(
                'processed' => true,
                'time_close' => $timeStamp
            );
            return $returnArr;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param int $sellerId
     * @param type $id_transation
     * @return type
     */
    public function getTotalSixthContinentCheckout($sellerId, $transaction_id, $time_close) {

        $qb = $this->createQueryBuilder('c');
        $qb->select("SUM(c.sixcAmountPcVat) AS sixcAmountPcVat")
                ->where('c.sellerId=:sellerId and c.status = :status '
                        . ' and c.id <=:transaction_id and c.timeClose <= :timeClose '
                        . 'and c.redistributionStatus=:redistributionStatus and c'
                        . '.transactionGateWayReference IS NULL')
                ->setParameter('sellerId', $sellerId)
                ->setParameter('status', self::$TRANSACTION_COMPLETE_SUCCESFULY)
                ->setParameter('redistributionStatus', 0)
                ->setParameter('transaction_id', $transaction_id)
                ->setParameter('timeClose', $time_close);
        $query = $qb->getQuery();
        $response = $query->getResult();
        if ($response[0]['sixcAmountPcVat']) {
            $revenue = $response[0]['sixcAmountPcVat'];
        } else {
            $revenue = 0;
        }

        return $revenue;
    }

    /**
     * It updates the transaction that has been succesfull
     * with the gateway reference
     * 
     * @param type $sellerId
     * @param type $id_transaction
     * @param type $time_close
     * @param type $codTrans
     */
    public function updateSuccessRecurring($sellerId, $transaction_id, $timeClose, $transactionGateWayReference , $update_all=false) {
        $qb = $this->createQueryBuilder('c')
                ->update()
                ->set('c.transactionGateWayReference', ':transactionGateWayReference')
                ->set('c.redistributionStatus', 1)
                ->where('c.sellerId=:sellerId and c.status = :status and c.timeClose <= :timeClose and c.redistributionStatus=:redistributionStatus')
                ->setParameter('sellerId', $sellerId)
                ->setParameter('transaction_id', $transaction_id)
                ->setParameter('status', self::$TRANSACTION_COMPLETE_SUCCESFULY)
                ->setParameter('transactionGateWayReference', $transactionGateWayReference)
                ->setParameter('timeClose', $timeClose)
                ->setParameter('redistributionStatus', 0);
                if($update_all==false){
                   $qb->andWhere("c.id =:transaction_id ");
                }else{
                    $qb->andWhere(" c.id <=:transaction_id ");
                }
        $query = $qb->getQuery();
        $response = $query->getResult();

        if ($response[0]['sixcAmountPcVat']) {
            $revenue = $response[0]['sixcAmountPcVat'];
        } else {
            $revenue = 1;
        }

        return $revenue;
    }

    /**
     * It gets all the transaction that has to be redistribuited 
     * @param string $transactionGateWayReference
     * @return $Transaction obj
     */
    public function getTransactionToRedistribuate($transactionGateWayReference, $id_transaction=null) {
        $response = null;
        $qb = $this->createQueryBuilder('c')
                ->select("c")
                ->where('c.transactionGateWayReference=:transactionGateWayReference and c.redistributionStatus = 1');
        if ($id_transaction != null) {
            $qb->andWhere('c.id = :id_transaction')
            ->setParameter('id_transaction', $id_transaction);
        }
        $qb ->setParameter('transactionGateWayReference', $transactionGateWayReference);
        $query = $qb->getQuery();
        $result = $query->getResult();

        if (isset($result[0])) {
            $response = $result;
        }

        return $response;
    }

    /**
     * Increase the  value of the c.i 
     * @param int $id_transaction
     * @return boolean
     */
    public function updateRedistributionStatus($id_transaction) {
        $query = $this->createQueryBuilder('t')
                ->update()
                ->set('t.redistributionStatus', 't.redistributionStatus + 1')
                ->where('t.sixcTransactionId=:sixcTransactionId')
                ->setParameter('sixcTransactionId', $id_transaction)
                ->getQuery();
        $reponse = $query->getResult();

        if ($reponse) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Get transaction row response
     * @param $Transactionid 
     */

    public function getTransactionResponse($TransactionId) {
        if ($TransactionId) {
            /* Get transaction data */
            $em = $this->getEntityManager();
            $TransactionDetail = $em->getRepository("TransactionSystemBundle:Transaction")
                    ->find($TransactionId);

            /* make return response */
            $TrnsDetail = array(
                'id' => $TransactionDetail->getId(),
                'status' => $TransactionDetail->getstatus(),
                'sixc_transaction_id' => $TransactionDetail->getsixcTransactionid(),
                'seller_id' => $TransactionDetail->getsellerId(),
                'buyer_currency' => $TransactionDetail->getbuyerCurrency(),
                'seller_currency' => $TransactionDetail->getsellerCurrency(),
                'b_over_s_currency_ration' => $TransactionDetail->getbOverSCurrencyRation(),
                'init_price' => $TransactionDetail->getinitPrice(),
                'final_price' => $TransactionDetail->getfinalPrice(),
                'with_credit' => $TransactionDetail->getwithCredit(),
                'discount_used' => $TransactionDetail->getdiscountUsed(),
                'citizen_income_used' => $TransactionDetail->getcitizenincomeUsed(),
                'time_init_h' => $TransactionDetail->gettimeInitH(),
                'time_update_status_h' => $TransactionDetail->gettimeUpdateStatusH(),
                'time_close_h' => $TransactionDetail->gettimeCloseH(),
                'time_init' => $TransactionDetail->gettimeInit(),
                'time_update_status' => $TransactionDetail->gettimeUpdateStatus(),
                'time_close' => $TransactionDetail->gettimeClose(),
                'buyer_id' => $TransactionDetail->getbuyerId(),
                'transaction_fee' => $TransactionDetail->gettransactionFee(),
                'sixc_amount_pc' => $TransactionDetail->getsixcAmountPc(),
                'sixc_amount_pc_vat' => $TransactionDetail->getSixcAmountPCVat(),
                'seller_pc' => $TransactionDetail->getsellerPc(),
                'transaction_type_id' => $TransactionDetail->gettransactionTypeId(),
                'redistribution_status' => $TransactionDetail->getredistributionStatus(),
                'citizen_aff_charge' => $TransactionDetail->getcitizenAffCharge(),
                'shop_aff_charge' => $TransactionDetail->getshopAffCharge(),
                'friends_follower_charge' => $TransactionDetail->getfriendsFollowerCharge(),
                'buyer_charge' => $TransactionDetail->getbuyerCharge(),
                'sixc_charge' => $TransactionDetail->getsixcCharge(),
                'all_country_charge' => $TransactionDetail->getallCountryCharge(),
                'transaction_serialize' => $TransactionDetail->gettransactionSerialize(),
                'transaction_gateway_reference' => $TransactionDetail->gettransactionGatewayReference()
            );
            return $TrnsDetail;
        } else {
            return json_encode(array('code' => 1029, 'message' => 'FAILURE', 'data' => 'TRANSACTION_ID_MISSING'));
        }
    }

    /*
     * Create transaction record for PAY_ONCER_OFFER
     * @params object $data
     */

    public function createTransactionRecord($data) {
        /* Calculate affiliation charge */
        $cashPayment = $data['payble_value'];
        $ciUsed = (!empty($data['used_ci'])) ? $data['used_ci'] : 0;
        $total = $cashPayment + $ciUsed;

        $citizen_aff_charge = floor($data['citizen_aff_charge'] * $total);
        $shop_aff_charge = floor($data['shop_aff_charge'] * $total);
        $friends_follower_charge = floor($data['friends_follower_charge'] * $total);
        $buyer_charge = floor($data['buyer_charge'] * $total);
        $sixc_charge = round($data['sixc_charge'] * $total);
        $all_country_charge = floor($data['all_country_charge'] * $total);
        $default_charge = floor(self::$DEFAULT_CHARGE * $total);

        /* Creating postdata object to add record */
        $de_serialize = array();
        if(isset($data['sixc_amount_pc'])) {
            $de_serialize["sixc_amount_pc"] = $data['sixc_amount_pc'];
        }
        
        if(isset($data['sixc_amount_pc_vat'])) {
            $de_serialize["sixc_amount_pc_vat"] = $data['sixc_amount_pc_vat'];
        }
        $de_serialize['do_transaction'] = $data['transaction_type'];
        $de_serialize['seller_id'] = $data['shop_id'];
        $de_serialize['buyer_id'] = $data['buyer_id'];
        $de_serialize['init_price'] = $de_serialize['amount'] = $data['transaction_value'];
        $de_serialize['cashpayment'] = $cashPayment;
        $de_serialize['discount'] = $data['discount'];
        $de_serialize['citizen_income_used'] = $data['used_ci'];
        $de_serialize['citizen_aff_charge'] = $citizen_aff_charge;
        $de_serialize['shop_aff_charge'] = $shop_aff_charge;
        $de_serialize['friends_follower_charge'] = $friends_follower_charge;
        $de_serialize['buyer_charge'] = $buyer_charge;
        $de_serialize['sixc_charge'] = $sixc_charge;
        $de_serialize['all_country_charge'] = $all_country_charge;
        $de_serialize['default_charge'] = $default_charge;
        $de_serialize['seller_pc'] = $data['citizen_aff_charge'] + $data['shop_aff_charge'] + $data['friends_follower_charge'] + $data['buyer_charge'] + $data['sixc_charge'] + $data['all_country_charge'];

        $TransactionId = $this->getProcessTransaction($de_serialize, $calcData = '');

        if ($TransactionId) {
            /* Reduce CI */
            $updateArr = array(
                'buyer_id' => $de_serialize['buyer_id'],
                'ci_used' => $de_serialize['citizen_income_used']
            );
            $em = $this->getEntityManager();
            $em->getRepository('WalletBundle:WalletCitizen')
                    ->reduceWalletCitizenIncome($updateArr);

            $response = $this->getTransactionResponse($TransactionId);
            return $response;
        } else {
            return json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
    }

    /*
     * Update transaction record for PAY_ONCER_OFFER
     * @params object $data
     */

    public function updateTransactionRecord($data) {
        /* Update Transaction */
        $time_h = new \DateTime("Now");
        $timestamp = time();
        $updateData = array(
            'transaction_id' => $data['transaction_id'],
            'status' => $data['status'],
            'time_update_status_h' => $time_h,
            'time_close_h' => $time_h,
            'time_update_status' => $timestamp,
            'time_close' => $timestamp,
            'transactionGatewayReference' => self::$TRNS_GATEWAY_REFERENCE_OFFER
        );
        $em = $this->getEntityManager();
        $updateRes = $this->updateProcessTransaction($updateData);
        
        /* Update Seller Total Revenue 
        if($data['status'] == 'COMPLETED') {
            /* Get transaction information 
            $TransactionData = $em->getRepository()
                                    ->find($data['transaction_id']);
            
            $revenue = $TransactionData->getfinalPrice() + $TransactionData->getcitizenIncomeUsed();
            $businessData = array(
                'revenue' => $revenue,
                'seller_id' => $TransactionData->getsellerId()
            );
            $em ->getRepository('WalletBundle:WalletBusiness')
                    ->updateSellerTotalRevenue($businessData);
        }
         * 
         */
        
        if ($updateRes) {
            $em = $this->getEntityManager();
            $TransactionDetail = $this->find($data['transaction_id']);
            $response = $this->getTransactionResponse($TransactionDetail->getId());
            return $response;
        } else {
            return json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
    }

    /*
     * Process CI Redistribution after confirmation of OFFER_PURCHASE transaction
     * @param object $distributionObj
     */

    public function processCIRedistribution($distributionObj) {
        $sellerId = $distributionObj['seller_id'];
        $id_transaction = $distributionObj['transaction_id'];
        $time_close = $distributionObj['time_close'];
        $currency = $distributionObj['currency'];

        /**
         * It makes the c.i redistribution only of offer purchase
         */
        $redistribution_ci  = $this->container->get('redistribution_ci');
        $transactionGatewayReference  = self::$TRNS_GATEWAY_REFERENCE_OFFER;
        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
   }
     

   public function getStoreOwner($data) {
           $response = $this->getEntityManager()
                ->createQuery("SELECT c.userId
                            FROM StoreManager\StoreBundle\Entity\UserToStore c
                            WHERE c.storeId = :storeId
                            ")
                ->setParameter(":storeId", $data['store_id'])
                ->getResult();
           
            if (!empty($response)) {
                return $response[0]['userId'];
            }
     }

    
    /**
     * 
     * $data
     * 
     */
    public function getTransactionHistory($param) {
        $results = array();
        $qb = $this->createQueryBuilder('tr');
        $qb->select('tr.id , tr.buyerId as buyer_id, tr.sixcTransactionId as sixc_id , tr.transactionTypeId as record_type_id , tr.redistributionStatus as redistribution_status   ,'
                . ' tr.timeClose as time_close, tr.initPrice init_price , tr.finalPrice price , st.name as business_name  '
                . ', st.id as seller_id '
                . ', tr.buyerCharge as cashback_amount , tr.citizenAffCharge as cit_amount , tr.friendsFollowerCharge as conn_amount , tr.shopAffCharge as shop_amount , tr.allCountryCharge  as all_amount '
                . '     ')
                ->leftJoin('StoreManagerStoreBundle:Store', 'st', 'WITH', 'st.id=tr.sellerId ')  //'a.id', 'a.userId as user_id', 'a.applicationId as application_id'
//                ->leftJoin('SixthContinentConnectBundle:Application', 'app', 'WITH', ' tr.transactionTypeId = :transactionTypeId ')
//                ->leftJoin('CommercialPromotionBundle:CommercialPromotion', 'cp', 'WITH', ' cp.id = :transactionTypeId ')
                ->orderBy('tr.id', 'Desc')
                ->where('tr.status = :status ')
//                ->setParameter('transactionTypeId', self::$PAY_ONCE)
                ->setParameter('status', self::$TRANSACTION_COMPLETE_SUCCESFULY);
                if(isset($param["record_id"])){
                    $qb->andWhere("tr.sixcTransactionId = :sixcTransactionId ")   
                    ->setParameter('sixcTransactionId', $param["record_id"]);
                }else{
                    $qb->andWhere(" tr.timeClose BETWEEN  :start_time  AND :end_time and tr.buyerId = :buyerId  ")   
                    ->setParameter('start_time', $param["start_time"])
                    ->setParameter('end_time', $param["end_time"])
                    ->setParameter('buyerId', $param["buyer_id"]);
                }
        $results = $qb->getQuery()->getArrayResult();
            
        return $results;
    }
    
    /**
     * Get people that has done transaction within one monthe
     */
    
    public function getTrasnactionDoneInMonth() {
        $results = array();
        $start_time = strtotime("2015-12-01 00:00:01");
        $end_time = strtotime("2015-12-31 23:59:59");
        $qb = $this->createQueryBuilder('q');
        $qb->select(' q.buyerId , SUM(q.initPrice) as total_month ')
                    ->andWhere(" q.status = :status and q.timeClose BETWEEN  :start_time  AND :end_time   ")   
                    ->having(' total_month >= 28000 ') 
                    ->groupBy(" q.buyerId ")
                    ->setParameter('status', self::$TRANSACTION_COMPLETE_SUCCESFULY)
                    ->setParameter('start_time', $start_time)
                    ->setParameter('end_time', $end_time);   
        $results = $qb->getQuery()->getArrayResult();
            
        return $results;
    }
}
