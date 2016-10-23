<?php

namespace Acme\GiftBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Acme\GiftBundle\Entity\Movimen;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Acme\GiftBundle\AcmeGiftBundle;
use WalletManagement\WalletBundle\Entity\UserShopCredit;
use Acme\GiftBundle\Document\MovimenLogs;

// service method  class
class GiftService {

    protected $em;
    protected $dm;
    //define the required params
    protected $params = array('IDMOVIMENTO', 'IDCARD', 'IDPDV', 'IMPORTODIGITATO', 'CREDITOSTORNATO', 'DATA', 'RCUTI', 'SHUTI', 'PSUTI', 'GCUTI', 'GCRIM', 'MOUTI');

    public function __construct(EntityManager $em, DocumentManager $dm) {
        $this->em = $em;
        $this->dm = $dm;
    }

   /**
    * Soap service handling for shopping plus server.
    * @param object $data
    * @return int|string
    */
    public function MOVIMENTOADD($data) {
        $request_data = $data; 
        $json_decode_array = (array) $request_data;
        $chk_params = $this->params;
        //check for the parameteres existence and its values.
        foreach ($chk_params as $param) {
            $trimed_data = trim($json_decode_array[$param]);
            if (array_key_exists($param, $json_decode_array) && ($trimed_data != '')) {
                $check_error = 0;
            } else {
                $check_error = 1;
                $miss_param = $param;
                break;
            }
        }

        //if clause if there is not any error
        if (!$check_error) {
            //check for date format.
            try {
                $dt = new \DateTime($request_data->DATA);
            } catch (\Exception $d) {
                //saving the wrong date format logs.
                $this->saveMovimenLogsData($request_data->IDMOVIMENTO, $d->getMessage());
                $object = array('STATUS' => 100, 'MESSAGE' => 'KO', 'ERROR' => $d->getMessage());
                return $object;
            }

            try {
                $movimen = new Movimen();
                $request_data->IDPDV  = (int)$request_data->IDPDV;
                $request_data->IDCARD =  (int)$request_data->IDCARD;
                $movimen->setIDMOVIMENTO($request_data->IDMOVIMENTO);
                $movimen->setIDCARD($request_data->IDCARD);
                $movimen->setIDPDV($request_data->IDPDV);
                $movimen->setIMPORTODIGITATO($request_data->IMPORTODIGITATO);
                $movimen->setCREDITOSTORNATO($request_data->CREDITOSTORNATO);
                $movimen->setDATA(new \DateTime($request_data->DATA));
                $movimen->setRCUTI($request_data->RCUTI);
                $movimen->setSHUTI($request_data->SHUTI);
                $movimen->setPSUTI($request_data->PSUTI);
                $movimen->setGCUTI($request_data->GCUTI);
                $movimen->setGCRIM($request_data->GCRIM);
                $movimen->setMOUTI($request_data->MOUTI);
                $this->em->persist($movimen);
                $this->em->flush();
                
                //making the updates for shots, discount position and cards for users and shops.
                $this->userShopCredit($movimen);
                //make success logs for a transaction.
                $this->saveMovimenLogsData($request_data->IDMOVIMENTO, 'success');
                $object = array('STATUS' => 0, 'MESSAGE' => 'OK');
                return $object;
            } catch (\Doctrine\DBAL\DBALException $e) {
                //saving the wrong date format logs.
                $this->saveMovimenLogsData($request_data->IDMOVIMENTO, $e->getMessage());
                $object = array('STATUS' => 100, 'MESSAGE' => 'KO', 'ERROR' => $e->getMessage());
                return $object;
            }
        } else {
            //make logs for missing parameter
            $message = 'you have missed the following parameter value ' . $miss_param;
            $this->saveMovimenLogsData($request_data->IDMOVIMENTO, $message);
            $object = array('STATUS' => 100, 'MESSAGE' => 'KO', 'ERROR' => 'you have missed the following parameter value ' . $miss_param);
            return $object;
        }
    }
    
    /**
     * update the records into shop and user discount
     * @param type $movimen_object
     */
    public function userShopCredit($movimen_object) {
        $user_id = $movimen_object->getIDCARD(); //user id
        $shop_id = $movimen_object->getIDPDV(); //shop id
        $balance_dp = $movimen_object->getPSUTI(); //utilized dp in a transaction
        $citizen_income = $movimen_object->getCREDITOSTORNATO(); //citizen income remaining
        $gcuti = $movimen_object->getGCUTI(); //gift card utilized.
        $gcrim = $movimen_object->getGCRIM(); //gift card remaining
        $shuti = $movimen_object->getSHUTI();
        $mouti = $movimen_object->getMOUTI(); //momosy card utilized.
        //user discount position.
        $user_discount_position = $this->getUserDiscountPosition($user_id);
        if (count($user_discount_position)) {
            $user_discount_position_data = $user_discount_position[0]; //finding the first records.

            $user_discount_position_total = $user_discount_position_data->getTotalDp();
            $remaining_user_dp = $user_discount_position_total - $balance_dp; // total dp of user - utilized dp in transaction
            //update the citizen income and balance dp.
            $user_discount_position_data->setBalanceDp($remaining_user_dp);
            $user_discount_position_data->setCitizenIncome($citizen_income);
            $this->em->persist($user_discount_position_data);
            $this->em->flush();
        }

        //shop discount position.
        $shop_discount_position = $this->getShopDiscountPosition($shop_id);
        if (count($shop_discount_position)) {
            $shop_discount_position_data = $shop_discount_position; //finding the first records.

            $shop_discount_position_total = $shop_discount_position_data->getTotalDp();
            $remaining_shop_dp = $shop_discount_position_total - $balance_dp; // total dp of shop - utilized dp in transaction
            //update the citizen income and balance dp.
            $shop_discount_position_data->setBalanceDp($remaining_shop_dp);
            $this->em->persist($shop_discount_position_data);
            $this->em->flush();
        }

        //user shop credit.
        $user_shop_credit = $this->getUserShopCredit($user_id, $shop_id);
        if (count($user_shop_credit)) { //when a user making the transaction again from a shop.
            $user_shop_credit_data = $user_shop_credit[0];
            $balance_shots = $user_shop_credit_data->getBalanceShots() - $shuti;
            $total_gift_card = $user_shop_credit_data->getTotalGiftCard() + $gcuti;
            $balance_momosy_card = $user_shop_credit_data->getBalanceMomosyCard() - $mouti;

            $user_shop_credit_data->setBalanceShots($balance_shots);
            $user_shop_credit_data->setTotalGiftCard($total_gift_card);
            $user_shop_credit_data->setBalanceGiftCard($gcrim);
            $user_shop_credit_data->setTotalMomosyCard(0);
            $user_shop_credit_data->setBalanceMomosyCard($balance_momosy_card);
            $this->em->persist($user_shop_credit_data);
            $this->em->flush();
        } else { //user making the transaction from a shop first time.
            $created_at = new \DateTime('now');
            $user_shop_credit_entity = new UserShopCredit();
            $user_shop_credit_entity->setUserId($user_id);
            $user_shop_credit_entity->setShopId($shop_id);
            $user_shop_credit_entity->setTotalShots(0);
            $user_shop_credit_entity->setBalanceShots(0);
            $user_shop_credit_entity->setTotalGiftCard($gcuti);
            $user_shop_credit_entity->setBalanceGiftCard($gcrim);
            $user_shop_credit_entity->setTotalMomosyCard(0);
            $user_shop_credit_entity->setBalanceMomosyCard(0);
            $user_shop_credit_entity->setCreatedAt($created_at);
            $this->em->persist($user_shop_credit_entity);
            $this->em->flush();
        }
    }

    /**
     * finding the user discount position record.
     * @param int $user_id
     * @return array object
     */
    public function getUserDiscountPosition($user_id) {
        //finding the user discount position.
        $user_discount_position = $this->em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                ->findBy(array('userId' => $user_id));
        return $user_discount_position;
    }

    /**
     * finding the shop discount position record from store table.
     * @param int $shop_id
     * @return array object
     */
    public function getShopDiscountPosition($shop_id) {
        //finding the user discount position.
        $shop_discount_position = $this->em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        return $shop_discount_position;
    }

    /**
     * get the shop user credit deatil..
     * @param int $user_id
     * @param int $shop_id
     * @return object array
     */
    public function getUserShopCredit($user_id, $shop_id) {
        //finding the user discount position.
        $shop_user_credit = $this->em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                ->findBy(array('userId' => $user_id, 'shopId' => $shop_id));
        return $shop_user_credit;
    }
   
    /**
     * saving the logs for movimen wrong data formate
     * @params string $transaction_id
     * @return boolean true
     */
    public function saveMovimenLogsData($transaction_id, $message)
    {
        $transaction_id = $transaction_id;
        $created_at = new \DateTime(date('Y-m-d H:i:s'));
        $dm = $this->dm;
        $movimen_log = new MovimenLogs();
        $movimen_log->setTransactionId($transaction_id);
        $movimen_log->setReason($message);
        $movimen_log->setCreatedAt($created_at);
        $dm->persist($movimen_log);
        $dm->flush();
        return true;
    }
}
