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

// validate the data.like iban, vatnumber etc
class SixthcontinentConnectAppTransactionService {

    protected $em;
    protected $dm;
    protected $container;
    CONST PAY_ONCE = 'PAY_ONCE';
    CONST PAYPAL = 'PAYPAL';
    CONST PAY_ONCE_CI = 'PAY_ONCE_CI';

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
        $handler = $this->container->get('monolog.logger.connect_app_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
    
    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }
    
    /**
     * getting the application transaction ids
     * @param string $app_id
     * @param int $limit_start
     * @param int $limit_size
     * @param int $count
     */
    public function getApplicationTransaction($app_id, $limit_start, $limit_size, $count = 0) {
        $em = $this->em;
        $transaction_ids = $user_ids = array();
        $transactions = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                           ->getAppTransactions($app_id, $limit_start, $limit_size);
        foreach ($transactions as $transaction) {
            $transaction_ids[] = $transaction->getId();
            $user_ids[] = $transaction->getUserId(); 
        }
        $transaction_array = array('transactions'=>$transactions, 'transaction_ids'=>$transaction_ids, 'user_ids'=>$user_ids);
        return $transaction_array;
    }
    
    /**
     * find the paypal transaction records
     * @param array $transaction_ids
     */
    public function getApplicationPaypalRecordsTransaction($transaction_ids) {
        $em = $this->em;
        $paypal_transactions = $em->getRepository('SixthContinentConnectBundle:SixthcontinentconnectPaymentTransaction')
                                  ->getAppPaypalTransactions($transaction_ids);
        return $paypal_transactions;
    }
    
    /**
     * getting the count of the transactions of a app
     * @param type $app_id
     * return the count
     */
    public function getApplicationTransactionCount($app_id) {
        $em = $this->em;
        $count = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                    ->getAppTransactionCount($app_id);
        return $count;
    }
    
    /**
     * prepare the object for the transaction history
     * @param object array $transactions
     * @param object array $paypal_transaction_records
     * @param array $user_ids
     * @param object array $app_data
     */
    public function prepareTransactionObject($transactions, $paypal_transaction_records, $user_ids, $app_data) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectAppTransactionService] function [prepareTransactionObject]');
        $connect_app_service = $this->_getSixcontinentAppService();
        if (count($transactions) == 0) {
            return array('transaction_records'=>array());
        }
        $data = array();
        $user_service = $this->container->get('user_object.service');
        //find the user info
        $users_object = $user_service->MultipleUserObjectService($user_ids);
        $paypal_transactions = $this->preparePaypalTransactionRecords($paypal_transaction_records, $users_object);
        $app_name     = ucwords($app_data->getApplicationName());
        $app_owner_id = $app_data->getUserId();
        
        foreach ($transactions as $transaction) {
            $id = $transaction->getId();
           $app_id   = $transaction->getApplicationId();
           $date = $transaction->getDate();
           $user_id = $transaction->getUserId();
           $total_amount = $connect_app_service->changeRoundAmountCurrency($transaction->getTransactionValue());
           $discount = $connect_app_service->changeRoundAmountCurrency($transaction->getDiscount());
           $cash_amount = $connect_app_service->changeRoundAmountCurrency($transaction->getPaybleValue());
           $ci_used = $connect_app_service->changeRoundAmountCurrency($transaction->getUsedCi());
           $checkout_value = $connect_app_service->changeRoundAmountCurrency($transaction->getCheckoutValue());
           $transaction_type = $transaction->getTransactionType();
           $total_available_ci = $connect_app_service->changeRoundAmountCurrency($transaction->getTotalAvailableCi());
           $paypal_id       = $transaction->getPaypalTransactionId();
           $paypal_id_array = Utility::decodeData($paypal_id);
           $user_info = isset($users_object[$user_id]) ? $users_object[$user_id] : null;
           $pay_once = isset($paypal_transactions[self::PAY_ONCE][$id]) ? $paypal_transactions[self::PAY_ONCE][$id] : null;
           $pay_once_ci = isset($paypal_transactions[self::PAY_ONCE_CI][$id]) ? $paypal_transactions[self::PAY_ONCE_CI][$id] : null;
           $paypal_transaction_records = array('pay_once'=>$pay_once, 'pay_once_ci'=>$pay_once_ci);
           $data[] = array(
               'app_id' => $app_id,
               'sixthcontinent_transaction_id' => $id,
               'app_name' => $app_name,
               'date' => $date,
               'total_amount' => $total_amount,
               'discount' => $discount,
               'cash_amount' => $cash_amount,
               'ci_used' => $ci_used,
               'checkout_value' => $checkout_value,
               'transaction_type' => $transaction_type,
               'total_available_ci' => $total_available_ci,
               'paypal_id' => $paypal_id_array,
               'user_info' => $user_info,
               'paypaltransactionrecords' => $paypal_transaction_records
           );
        }
        $result = array('transaction_records'=>$data);
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectAppTransactionService] function [prepareTransactionObject] with response: '.Utility::encodeData($result));
        return $result;
    }
    
    /**
     * 
     * @param object array $paypal_transaction_records
     */
    public function preparePaypalTransactionRecords($paypal_transaction_records, $users_object) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectAppTransactionService] function [preparePaypalTransactionRecords]');
        $connect_app_service = $this->_getSixcontinentAppService();
        $paypal_transactions = array();
        $pay_once = self::PAY_ONCE;
        $pay_once_ci = self::PAY_ONCE_CI;
        foreach ($paypal_transaction_records as $paypal_transaction_record) {
            $user_id = $paypal_transaction_record->getUserId();
            $reason  = $paypal_transaction_record->getReason();
            $connect_id = $paypal_transaction_record->getSixthcontinentConnectId();
            $app_id     = $paypal_transaction_record->getAppId();
            $payment_status = $paypal_transaction_record->getPaymentStatus();
            $payment_via = $paypal_transaction_record->getPaymentVia();
            $error_code  = $paypal_transaction_record->getErrorCode();
            $error_desc  = $paypal_transaction_record->getErrorDescription();
            $pay_key     = $paypal_transaction_record->getTransactionReference();
            $date        = $paypal_transaction_record->getDate();
            $total_amount = $connect_app_service->changeRoundAmountCurrency($paypal_transaction_record->getTransactionValue());
            $vat_amount   = $connect_app_service->changeRoundAmountCurrency($paypal_transaction_record->getVatAmount());
            $contract_id  = $paypal_transaction_record->getContractId();
            $paypal_id    = $paypal_transaction_record->getPaypalId();
            $paypal_id_array = Utility::decodeData($paypal_id);
            $ci_transaction_system_id = $paypal_transaction_record->getCiTransactionId();
            $ci_used = $connect_app_service->changeRoundAmountCurrency($paypal_transaction_record->getCiUsed());
            $user_info = isset($users_object[$user_id]) ? $users_object[$user_id] : null;
            $record_data = array(
                'app_id'=>$app_id,
                'sixthcontinent_connect_id'=>$connect_id,
                'payment_status'=>$payment_status,
                'payment_via'=>$payment_via,
                'transaction_reference'=>$pay_key,
                'total_amount'=>$total_amount,
                'vat_amount'=>$vat_amount,
                'date' => $date,
                'paypal_id'=>$paypal_id_array,
                'ci_used'=>$ci_used,
                'ci_transaction_id'=>$ci_transaction_system_id,
                'error_code'=>$error_code,
                'user_info'=>$user_info
            );
            if (Utility::getUpperCaseString($reason) == Utility::getUpperCaseString($pay_once)) { //pay once type
                $paypal_transactions[$pay_once][$connect_id] = $record_data;
            } else if(Utility::getUpperCaseString($reason) == Utility::getUpperCaseString($pay_once_ci)) { //pay once ci type
                $paypal_transactions[$pay_once_ci][$connect_id] = $record_data;
            }
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectAppTransactionService] function [preparePaypalTransactionRecords] with response: '.Utility::encodeData($paypal_transactions));
        return $paypal_transactions;
    }
}
