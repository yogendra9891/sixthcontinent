<?php

namespace SixthContinent\SixthContinentConnectBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Symfony\Component\Locale\Locale;

//handling the tamoil offer pending transaction 
class SixthcontinentTamoilOfferTransactionService {

    protected $em;
    protected $dm;
    protected $container;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }

    /**
     * Create connect app log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.offer_purchasing_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    protected function _getSixthcontinentOfferTransactionService() {
        return $this->container->get('sixth_continent_connect.purchasing_offer_transaction'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService
    }
    
    /**
     * finding the tamoil pending transaction and mark these as canceled and release the CI
     */
    public function checkTransactionStatus() {
        $em = $this->em;
        $offer_transaction_service = $this->_getSixthcontinentOfferTransactionService();
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentTamoilOfferTransactionService] and function [checkTransactionStatus]');
        $transactions = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                           ->getSpecialOfferTransactionPending();
        $rejected_status = ApplaneConstentInterface::REJECTED;
        $canceled_status = ApplaneConstentInterface::CANCELED;
        $pending_status  = ApplaneConstentInterface::PENDING;
        $time_difference_constant = 15*60; //15 mins
        foreach ($transactions as $transaction ) {
            $id   = $transaction->getId();
            $date = $transaction->getDate();
            $transaction_time_format = $this->convertToDateFormat($date);
            $transaction_time = $this->convertToTime($transaction_time_format);
            $ci_transaction_system_id = $transaction->getCiTransactionSystemId();
            $current_status = $transaction->getStatus();
            $current = new \DateTime('now');
            $current_time_format = $this->convertToDateFormat($current);
            $current_time = $this->convertToTime($current_time_format);
            $time_diff = $current_time-$transaction_time;
            
            if (Utility::getUpperCaseString($pending_status) == Utility::getUpperCaseString($current_status)) { //if transaction is in pending mode
                if ($time_diff >= $time_difference_constant) { //time difference check.
                    $this->__createLog('Time difference is coming for transaction id:'.$id. ' time is: '.$time_diff/60 .' minutes amount: '.$transaction->getUsedCi());
                    $offer_transaction_service->updateTransactionStatus($transaction, $canceled_status); 
                    $offer_transaction_service->updateTransactionSystemStatus($transaction, $rejected_status);  
                }
            }
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentTamoilOfferTransactionService] and function [checkTransactionStatus]');
    }
    
    /**
     * convert a date to specific format
     * @param date object $date
     */
    public function convertToDateFormat($date) {
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * convert to strtotime function
     * @param object $time
     * @return int seconds
     */
    public function convertToTime($time) {
        return strtotime($time);
    }
}
