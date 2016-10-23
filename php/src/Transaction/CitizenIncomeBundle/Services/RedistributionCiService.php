<?php

namespace Transaction\CitizenIncomeBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionsPayment;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;
use Transaction\TransactionSystemBundle\Entity\Transaction;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\ApplaneIntegrationBundle\Document\TransactionPaymentNotificationLog;
use Utility\ApplaneIntegrationBundle\Document\SubscriptionPaymentNotificationLog;
use Utility\ApplaneIntegrationBundle\Document\TransactionNotificationLog;

// service method  class

class RedistributionCiService {

    private $_repository = null;
    private $_type_redistribution = null;
    private $_time_init_transaction = null;
    private $_time_end_transaction = null;
    private $_sixc_transaction_id = null;
    protected $_em;
    protected $_dm;
    protected $_container;
    static $CiFromCashBack = 2;
    static $CiFromCitizenAffiliated = 3;
    static $CiFromShopAffiliated = 4;
    static $CiFromAllNation = 5;
    static $CiFromProfPersFriendsFollower = 6;
    static $CiFromRedistribution = 7;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->_em = $em;
        $this->_dm = $dm;
        $this->_container = $container;
        //$this->request   = $request;
    }

    public function initRedistributionAction($type, $sixc_transaction_id , $time_clos ) {
        $this->_time_init_transaction = 0;
        $this->_time_end_transaction = $time_clos;
        $this->_type_redistribution = $type;
        $this->_sixc_transaction_id = $sixc_transaction_id;
        switch ($this->_type_redistribution) {
            case 2 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromCashBack');
                break;
            case 3 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromCitizenAffiliated');
                break;
            case 4 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromShopAffiliated');
                break;
            case 5 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromAllNation');
                break;
            case 6 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromProfPersFriendsFollower');
                break;
            case 7 :
                $this->_repository = $this->_em->getRepository('CitizenIncomeBundle:CiFromRedistribution');
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
        if ($total_user["total_user"] == 0 ) {
            $this->_repository->increaseParentRedistribution($this->_sixc_transaction_id);
        } else {
            //Get total amount to share
            $data = $this->getTotalAmountBaseCurrency();


            $total_amount_base_currency = $data[0]['totoal_amount_base_currency'];
            //get share for single user and remainder for next trs purpos

            $single_share = $this->getSingleShare($total_amount_base_currency, $total_user['total_user']);


            $this->updateCitizenWallet($single_share, $total_user);
        }
        /*
         * I no 
         */
            $tranasction_repo = $this->_em->getRepository('TransactionSystemBundle:Transaction');
            $tranasction_repo->updateRedistributionStatus($this->_sixc_transaction_id);
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
                ->getTotalUsers($this->_time_end_transaction, $this->_sixc_transaction_id);
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
                        ->getTotalAmountBaseCurrency($this->_time_init_transaction, $this->_time_end_transaction, $this->_sixc_transaction_id);
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
        $data['total_amount'] = $total_amount;
        return $data;
    }

    public function updateCitizenWallet($single_share, $total_user) {
        return $this->_repository
                        ->updateCitizenWallet($single_share, $total_user, $this->_time_init_transaction, $this->_time_end_transaction, $this->_sixc_transaction_id);
    }

    /**
     * 
     * @param int $sellerId
     * @param int $id_transaction
     * @param int $time_close
     * @param int $codTrans
     */
    public function updateSuccessRecurring($sellerId, $id_transaction, $time_close, $transactionGateWayReference , $update_all = false) {
        $transaction_rep = $this->_em->getRepository("TransactionSystemBundle:Transaction");
        
        $transaction_ids = $transaction_rep->updateSuccessRecurring($sellerId, $id_transaction, $time_close, $transactionGateWayReference , $update_all);
            
        $id_transaction_to_redistribuate = ($update_all)?null:$id_transaction;
        $trasnaction_model = $transaction_rep->getTransactionToRedistribuate($transactionGateWayReference , $id_transaction_to_redistribuate);
        $this->redistribuateCiForLoop($trasnaction_model, self::$CiFromCashBack);
        $this->redistribuateCiForLoop($trasnaction_model, self::$CiFromCitizenAffiliated);
        $this->redistribuateCiForLoop($trasnaction_model, self::$CiFromShopAffiliated);
    }

    /**
     * Give the redistribution to all users
     * @param Transaction $trasnaction_model
     * @param type $type_redistribution
     */
    public function redistribuateCiForLoop($transaction_model, $type_redistribution) {
        if( $transaction_model != null &&  count($transaction_model) > 0){
            foreach ($transaction_model as $key => $value) {
                $this->initRedistributionAction($type_redistribution, $value->getSixcTransactionId() , $value->getTimeClose());
            }
        }
        return true;
    }
    
    /**
     * Reredistribution service
     */
    
    public function reredistributionCi() {
        $em = $this->_em;
        $trs_repo = $em->getRepository("TransactionSystemBundle:Transaction");
        $users = $trs_repo->getTrasnactionDoneInMonth();
        $time = time();
        $to_exclude = array(62948,79593,64139,60109,14023,60567,15218,1707,12533,64327,66523,61649,75989);
        foreach ($users as $user) {
            if( ! in_array($user["buyerId"] , $to_exclude) ){
                $ci_cash_back_repo = $em->getRepository('CitizenIncomeBundle:CiFromCashBack');
                $total_user["users"][0]["id"] = $user["buyerId"] ;
                $single_share["single_share"] = 3900;
                    $ci_cash_back_repo->updateCitizenWallet($single_share , $total_user,$time , $time, "RIREDISTRIBUTIONDICEMBER");
            }
        
        }
        return true;
        
        //Ge user that has done transaction in this month
        
        
    }

}
