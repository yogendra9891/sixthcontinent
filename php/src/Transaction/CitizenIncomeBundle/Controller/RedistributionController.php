<?php

namespace Transaction\CitizenIncomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

/**
 * 
 * Redistribution of Credtis
 * 
 * 
 *  
 */
class RedistributionController extends Controller {
    
    private $_em  = null;
    private $_repository  = null;
    private $_type_redistribution = null;
    private $_time_init_transaction = null;
    private $_time_end_transaction = null;
    private $_sixc_transaction_id = null;

    public function indexAction($type,$sixc_transaction_id =null) {
        echo $this->initRedistributionAction($type , $sixc_transaction_id);
        exit;
    }
    private function _getGatRecurringPayment() {
       return $this->container->get('recurring_shop.payment');  
    }

    public function initRedistributionAction($type , $sixc_transaction_id) {
        $this->_time_init_transaction = 0;
        $this->_time_end_transaction = time();
        $this->_type_redistribution = $type;
        $this->_sixc_transaction_id  = $sixc_transaction_id;
        $this->_em = $this->get('doctrine')->getEntityManager();
        switch ($this->_type_redistribution) {
            case 1 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromAllNation');
                break;
            case 2 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromCitizenAffiliated');
                break;
            case 3 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromShopAffiliated');
                break;
            case 4 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromProfPersFriendsFollower');
                break;
            case 5 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromRedistributionRepository');
                break;
            case 6 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromCashBack');
                break;

            default:
                return "the case selected do not exist";
        }
        return $this->startCiRedistribution();
    }

    /**
     * 
     * @param int  $time_init_transaction timestamp()
     * @param int  $time_end_transaction timestamp()
     * 
     * Condition, $time_end_transaction > $time_init_transaction
     */
    public function startCiRedistribution() {
        //Get user to share c.i
        $total_user = $this->getTotalUsers();
        
        //Get total amount to share
        $data = $this->getTotalAmountBaseCurrency();
        
        
        $total_amount_base_currency = $data[0]['totoal_amount_base_currency'];
        //get share for single user and remainder for next trs purpos
        
        $single_share = $this->getSingleShare($total_amount_base_currency, $total_user['total_user'] );
        
        
        $this->updateCitizenWallet($single_share ,$total_user );
        exit("ok");
    }

    /**
     * 
     * @param type $type of redistribution $integer
     */
    public function checkTimeLimitation($time_init_transaction, $time_end_transaction, $type) {
        
    }

    /**
     * 
     * @param type $integer
     * Get toal users to home he redistribution has to be given
     * 
     */
    public function getTotalUsers() {
        $getAllUsers = $this->_repository
                        ->getTotalUsers( $this->_time_end_transaction , $this->_sixc_transaction_id);
        $data = array("total_user" => count($getAllUsers), "users" => $getAllUsers);
        return $data;
    }
    /**
     * 
     * @param array $sixc_transaction_id
     * @return array
     */
    public function getTotalAmountBaseCurrency() {
        return $this->_repository
                ->getTotalAmountBaseCurrency($this->_time_init_transaction,$this->_time_end_transaction , $this->_sixc_transaction_id);
    }

    /**
     * 
     * @param type $total_amount in basic currency to redistribuate
     * @param type $total_people
     * @param type $type
     * @return array int :
     * single_share amount to share
     * remainder amount that will be shared in the next redistribution  
     */
    public function getSingleShare($total_amount, $total_people) {
        /* Get Store Detail */
        $data['single_share'] = floor($total_amount / $total_people);
        $data['remainder'] = $total_amount % $total_people;
        $data['total_amount'] = $total_amount ;
        return $data;

    }
    
    public function updateCitizenWallet($single_share ,$total_user  ){
        return $this->_repository
                ->updateCitizenWallet($single_share ,$total_user  , $this->_time_init_transaction, $this->_time_end_transaction , $this->_sixc_transaction_id  );
        
    }
    
    public function payRecurringAction($id_transaction) {
        $sellerId  =  50398;
        $id_transaction = 405;
        $time_close = 1445512088;
        $currency = 'EUR';

        $redistribution_ci  = $this->container->get('redistribution_ci');
        $transactionGatewayReference  = "ALREADYPAID";
        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
//        $recurring_service = $this->_getGatRecurringPayment();
//        $sellerId  =  50398;
//        $id_transaction = 370;
//        $time_close = 1445352561;
//        $recurring_service->paySingleRecurrinTransaction($sellerId , $id_transaction ,$time_close, "EUR");
        exit("finished");
    }
    
    public function testNotificationAction(){
//        $from_id = "9732";
//        $to_id = "12530";
//        $postService = $this->container->get('post_detail.service');
//        $postService->sendUserNotifications($from_id, $to_id, "FRIEND_REQUEST", "request", $from_id, false, true);
        /*
        $postService->sendUserNotifications($from_id, $to_id, "TXN_TXN_CUST_RATING", "request", $from_id, false, true);
         * 
         */
        
        $sellerId  =  50398;
        $id_transaction = 405;
        $time_close = 1445512088;
        $currency = 'EUR';

        $redistribution_ci  = $this->container->get('redistribution_ci');
        $transactionGatewayReference  = "ALREADYPAID";
        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
    }
    
    public function redistribuateToAllCitizenAction( Request $request  ) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('single_share', 'time_init', 'time_end' ,"password");
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        if ($data["password"] == "Password1") {
            echo " Redistribution Strted";

            $em = $this->get('doctrine')->getEntityManager();

            $total_user["users"] = $em->getRepository("WalletBundle:WalletCitizen")
                    ->getAllWAllet();
            $not_used = "non utilizzato";
            $total_user["total_user"] = count($total_user["users"]);
            $single_share["total_amount"] = $data["single_share"] *$total_user["total_user"] ;
            $single_share["single_share"] = $data["single_share"] ;
            $single_share["remainder"] =  0 ;
            $em->getRepository("CitizenIncomeBundle:CiFromAllNation")
                    ->updateCitizenWallet($single_share , $total_user, $data["time_init"], $data["time_end"], $not_used);
            
            $total_economy_shifted = $em->getRepository("TransactionSystemBundle:EconomyShifted")
                    ->find(1);
            $amount_shifted = $total_user["total_user"] * 33 *$single_share["single_share"] + $total_economy_shifted->getTotalEconomyAmount();
            $total_economy_shifted->setTotalEconomyAmount($amount_shifted);
            $em->persist($total_economy_shifted);
            $em->flush();
            
            echo " Finish ";
            exit();
        } else {
            
        }
    }
    
    /**
     * utility service
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }
    
    /**
     * I am using it to start some trasanction
     * @param Request $request
     */
    public function makePaymentsAction( Request $request  ) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $recurring_shop_payment  = $this->container->get('recurring_shop.payment');
        $transactions  = $data["transactions"];
        if ($data["password"] == "Password1") {
            echo " Started <br>";
            $time_close = date("Y-m-d H:i:s");
            $counter = 0;
            foreach ($transactions as  $trasanction) {
                 $counter ++;
                $shop_id = 0;
                $amount = 0;
                $shop_id = $trasanction["shop_id"];
                $amount = $trasanction["amount"];
                $id_transaction = $shop_id."_".$counter."_".time();
                $result = $recurring_shop_payment->justRecurrinPay($shop_id, $id_transaction , $amount, $time_close);
                echo " $counter -> " . print_r($result) . " ;<br>";
//                echo " $key <br>";
            }
            echo " end ";
        }
        exit();
        /*
         * 
        if ($total_user["password"] = "Password1") {
            $data = 

        } else {
            
        }
         * 
         */
    }
    
    public function addAmilonAction() {
        
        $amilon_card_add  = $this->container->get('amilon_offer.card');
        $em = $this->get('doctrine')->getEntityManager();
        $transactionId = 6083 ;
        $SixcConect = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                ->findOneBy(array( 'id'=>$transactionId));
        $Transaction = $em->getRepository('TransactionSystemBundle:Transaction')
                ->findOneBy(array( 'id'=>$SixcConect->getCiTransactionSystemId()));
        $amilon_card_add->saveAmilonOffer($SixcConect , $Transaction);
        
    }
    public function getAmilonUrlAction(Request $request) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        $amilon_card_add  = $this->container->get('amilon_offer.card');
        $em = $this->get('doctrine')->getEntityManager();
        $amilon = $em->getRepository("WalletBundle:AmilonCard")
                ->findOneBy(array( 'id'=>$data["id"]));
        $amilon_return = $amilon_card_add->getAmilonCard( $amilon );
        echo "yess  ".$amilon_return->getLink();
        exit();
        
        
        
        
        
    }
    public function updateTrsAction(){
       $data = array();
       $export_connect_service = $this->get('sixth_continent_connect.tamoil_offer_transaction');
       $export_connect_service->checkTransactionStatus();
    }

}
    