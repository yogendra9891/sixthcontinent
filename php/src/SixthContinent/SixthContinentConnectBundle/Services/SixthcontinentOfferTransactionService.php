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

// validate the data.like iban, vatnumber etc
class SixthcontinentOfferTransactionService {

    protected $em;
    protected $dm;
    protected $container;
    CONST OFFER_PURCHASE_POST_URL = 'webapi/offerpurchaseback';
    CONST TAMOIL_OFFER_CODE = 'TO';
    CONST PAYMENT_SERVICE = 'paga_rico';

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

    /**
     * get offer detail
     * @param type $user_id
     * @param type $offer_record
     */
    public function getOfferDetail($user_id, $offer_record) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [getOfferDetail]');
        $result_data = array('_id'=>'');
        $handler = $this->container->get('monolog.logger.offer_purchasing_log');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $connect_app_service = $this->_getSixcontinentAppService();
        $id = $offer_record->getId();
        $offer_value   = $this->roundAmount($offer_record->getPrice());
        $discount_percentage = $offer_record->getPercentage();
        $citizen_income_data = $applane_service->getCitizenIncome($user_id); //get citizen income
        $available_income = $citizen_income_data['credit']; //available citizen income
        $useable_citizen_income = $this->getUseableCitizenIncome($available_income); //max citizen income can be used 
        $discount_reduced_amount = $this->reduceDiscount($offer_value, $discount_percentage);
        $amount_after_discount = $discount_reduced_amount['amount'];
        $discount_amount = $discount_reduced_amount['discount'];
        $max_income_useable = $this->getCitizenIncomePercentage($offer_value); //maximum income can be used in this transaction amount
        $useable_citizen_income = $this->changeAmountCurrency($useable_citizen_income); 
        if ($useable_citizen_income >= $max_income_useable) { 
            $ci_used = $this->getUseableCitizenIncome($max_income_useable);
        } else {
            $ci_used = $useable_citizen_income;
        }
        $checkout_value = $this->getCheckoutValue($offer_value);
        $checkout_vat   = $this->roundAmount($this->checkoutVat($checkout_value));
        $checkout_with_vat = $this->roundAmount($checkout_value + $checkout_vat);
        $cash_amount = $this->roundAmount($amount_after_discount-$ci_used);
        $available_income = $this->changeAmountCurrency($available_income);
        $result_data = array('_id'=>$id, 'total_value'=>$offer_value,'cash_amount'=>$cash_amount, 'checkout_with_vat'=>$checkout_with_vat, 'checkout_vat'=>$checkout_vat, 
            'available_ci'=>$available_income, 'ci_used'=>$ci_used, 'discount'=>$discount_amount);
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [getOfferDetail] with data: '.Utility::encodeData($result_data));
        return $result_data;
    }
    
    /**
     * get usable citizen income
     * @param float $available_income
     */
    public function getUseableCitizenIncome($available_income) {
        $this->__createLog('Citizen income is coming: '.$available_income);
        $card_value = $this->getOfferCardValue();
        $income = intval($available_income / $card_value); //getting interger value
        $result = ($income * $card_value);
        $this->__createLog('Useable citizen income: '.$result);
        return $result;
    }
    
     public function getOfferCardValue() {
        return $this->container->getParameter('offer_card_value');
    }
    
    /**
     * reduce the discount
     * @param type $offer_value
     * @param type $discount_percentage
     * @return float
     */
    public function reduceDiscount($offer_value, $discount_percentage) {
        $discount = ($offer_value*$discount_percentage)/100;
        $remain_amount = $offer_value-$discount;
        return array('amount' => $remain_amount, 'discount' => $discount);
    }
    
    /**
     * getting the usable citizen income amount
     * @param float $amount
     * @return float
     */
    public function getCitizenIncomePercentage($amount) {
        $ci_percentage = $this->getCiPercentage();
        $percent_amount = ($amount * $ci_percentage) / 100;
        return $percent_amount;
    }
    
    public function getCiPercentage() {
        return $this->container->getParameter('offer_ci_percentage');//max 50% of amount
    }
    
    /**
     * getting the checkout value
     * @param float $amount
     * @return float $checkout_value
     */
    public function getCheckoutValue($amount) {
        $chekout_percentage = $this->getOfferCheckoutPercentage();
        return ($amount * $chekout_percentage) / 100;
    }
    public function getOfferCheckoutPercentage() {
        return $this->container->getParameter('offer_checkout_percentage');
    }
    /**
     * checkout vat value
     * @param float $checkout_value
     * @return type
     */
    public function checkoutVat($checkout_value) {
        $vat = $this->container->getParameter('vat');
        $vat_amount = ($checkout_value * $vat) / 100;
        return $vat_amount;
    }
    
    public function roundAmount($amount) {
        $round = $this->getConnectCurrencyroundPlace();
        return round($amount, $round);
    }
  
    /**
     * change the amount by diving on 100 and round
     * @param float $amount
     * @return float
     */
    public function changeRoundAmountCurrency($amount) {
        $amount_currency = $this->getConnectAmountCurrency();
        $round_place = $this->getConnectCurrencyroundPlace();
        return round(($amount/$amount_currency), $round_place);
    }
    
    /**
     * change the amount by diving on 100 and round
     * @param float $amount
     * @return float
     */
    public function changeAmountCurrency($amount) {
        $amount_currency = $this->getConnectAmountCurrency();
        return ($amount*$amount_currency);
    }
    
    public function getConnectCurrencyroundPlace() {
        return $this->container->getParameter('connect_amount_round');
    }
    
    public function getConnectAmountCurrency() {
        return $this->container->getParameter('connect_amount_currency');
    }
    /**
     * Frezze the citizen income on transaction system.
     * @param int $user_id
     * @param float $ci_used
     */
    public function registerTransactionOnApplane($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [registerTransactionOnApplane]');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $ci_used = $this->changeRoundAmountCurrency($ci_used);
        $checkout_value = $this->changeRoundAmountCurrency($checkout_value);
        $total_amount = $this->changeRoundAmountCurrency($total_amount);
        $payble_amount = $this->changeRoundAmountCurrency($payble_amount);
        $applane_response = $applane_service->initiateTamoilOfferPurchaseTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status);
        $transaction_id = $applane_response;
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [registerTransactionOnApplane]');
        return $transaction_id;
    }
    
    /**
     * prepare the array for the offer transaction 
     * @param type $user_id
     * @param array $offer_detail
     */
    public function prepareOfferTransactionData($user_id, $offer_detail) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $transaction_type = ApplaneConstentInterface::TAMOIL_OFFER_PURCHASE_CODE;
        $currency = ApplaneConstentInterface::TAMOIL_OFFER_CURRENCY;
        $status = Utility::getUpperCaseString(ApplaneConstentInterface::INITIATED);
        $shop_id = isset($offer_detail['shop_id']) ? $offer_detail['shop_id'] : 0;
        $application_id = isset($offer_detail['application_id']) ? $offer_detail['application_id'] : '';
        $transaction_value = isset($offer_detail['total_value']) ? $offer_detail['total_value'] : 0;
        $discount = isset($offer_detail['discount']) ? $offer_detail['discount'] : 0;
        $payble_value = isset($offer_detail['cash_amount']) ? $offer_detail['cash_amount'] : 0;
        $vat = isset($offer_detail['checkout_vat']) ? $offer_detail['checkout_vat'] : 0;
        $checkout_value = isset($offer_detail['checkout_with_vat']) ? $offer_detail['checkout_with_vat'] : 0;
        $total_available_ci = isset($offer_detail['available_ci']) ? $offer_detail['available_ci'] : 0;
        $used_ci = isset($offer_detail['ci_used']) ? $offer_detail['ci_used'] : 0;
        $data = array('transaction_type'=>$transaction_type, 'user_id'=>$user_id, 'currency'=>$currency, 'status'=>$status, 'transaction_value'=>$transaction_value,
            'discount'=>$discount, 'payble_value'=>$payble_value, 'vat'=>$vat, 'checkout_value'=>$checkout_value,
            'total_available_ci'=>$total_available_ci,'used_ci'=>$used_ci, 'application_id'=>$application_id, 'shop_id'=>$shop_id);
        return $data;
    }
    /**
     * prepare the array for the offer transaction 
     * @param type $user_id
     * @param array $offer_detail
     */
    public function prepareOfferTransactionDataV2($user_id, $offer_detail) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $transaction_type = ApplaneConstentInterface::TAMOIL_OFFER_PURCHASE_CODE;
        $currency = ApplaneConstentInterface::TAMOIL_OFFER_CURRENCY;
        $status = Utility::getUpperCaseString(ApplaneConstentInterface::INITIATED);
        $shop_id = isset($offer_detail['shop_id']) ? $offer_detail['shop_id'] : 0;
        $application_id = isset($offer_detail['application_id']) ? $offer_detail['application_id'] : '';
        $transaction_value = isset($offer_detail['total_value']) ? $offer_detail['total_value'] : 0;
        $discount = isset($offer_detail['discount']) ? $offer_detail['discount'] : 0;
        $payble_value = isset($offer_detail['cash_amount']) ? $offer_detail['cash_amount'] : 0;
        $vat = isset($offer_detail['checkout_vat']) ? $offer_detail['checkout_vat'] : 0;
        $checkout_value = isset($offer_detail['checkout_with_vat']) ? $offer_detail['checkout_with_vat'] : 0;
        $total_available_ci = isset($offer_detail['available_ci']) ? $offer_detail['available_ci'] : 0;
        $used_ci = isset($offer_detail['ci_used']) ? $offer_detail['ci_used'] : 0;
        $data = array('transaction_type'=>$transaction_type, 'user_id'=>$user_id, 'currency'=>$currency, 'status'=>$status, 'transaction_value'=>$transaction_value,
            'discount'=>$discount, 'payble_value'=>$payble_value, 'vat'=>$vat, 'checkout_value'=>$checkout_value,
            'total_available_ci'=>$total_available_ci,'used_ci'=>$used_ci, 'application_id'=>$application_id, 'shop_id'=>$shop_id);
        return $data;
    }
    
    /**
     * update the transaction of sixthcontinent with transaction system id and status(pending)
     * @param int $connect_transaction_id
     * @param string $ci_transaction_system_id
     * @param string $status
     * @param array $payment_url
     * @param string $ci_transaction_id
     */
    public function updateOfferTransaction($connect_transaction_id, $ci_transaction_system_id, $status, $payment_url, $ci_transaction_id) {
        $em = $this->em;
        $connect_payment = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                              ->findOneBy(array('id'=>$connect_transaction_id));
        try {
            $connect_payment->setTransactionId($ci_transaction_id);
            $connect_payment->setCiTransactionSystemId($ci_transaction_system_id);
            $connect_payment->setStatus($status);
            $connect_payment->setMac($payment_url['mac']);
            $connect_payment->setDescription($payment_url['description']);
            $connect_payment->setPaypalTransactionReference($payment_url['transaction_code']);
            $connect_payment->setUrl($payment_url['return_url']);
            $connect_payment->setUrlPost($payment_url['url_post']);
            $connect_payment->setUrlBack($payment_url['url_back']);
            $em->persist($connect_payment);
            $em->flush();
        } catch (\Exception $ex) {
            
        }
        return true;
    }
    
    /**
     * create the offer purchase url
     * @param int $amount
     * @param int $user_id
     * @param int $connect_transaction_id
     * @param string $return_url
     * @param string $cancel_url
     * @return string $url
     */
    public function createOfferPurchaseUrl($amount, $user_id, $connect_transaction_id, $return_url, $cancel_url) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [createOfferPurchaseUrl] with transaction id:'.$connect_transaction_id);
        // code for chiave 
        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');
        // code for alias
        $prod_alias = $this->container->getParameter('prod_alias');
        $payment_type_send = self::TAMOIL_OFFER_CODE;; //TAMOIL OFFER
        $payment_type = ApplaneConstentInterface::TAMOIL_OFFER_CARTISI_PURCHASE_CODE;
        //$amount = 1;
        //amount is coming in multiple of 100 already.
        $dec_amount = (float)sprintf("%01.2f", $amount);
        $amount = 1 ;
//        $amount = $dec_amount;
        //code for codTrans
        $codTrans = "6THCH" . time() . $user_id . $payment_type_send;
        // code for divisa
        $currency_code = ApplaneConstentInterface::TAMOIL_OFFER_CURRENCY;
        //code for string for live
        $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$prod_payment_mac_key";
        //code for sting for test - fix
        //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$test_payment_mac_key";
        //code for mac
        $mac = sha1($string);
        //$prod_alias = $test_alias;
        //code for symfony url hit by payment gateway
        $base_url = $this->container->getParameter('symfony_base_url');
        $urlpost = $base_url.self::OFFER_PURCHASE_POST_URL;
        //$urlpost = 'http://php-sg1234.rhcloud.com/ipn_test.php';
        $urlpost = $this->createUrls($urlpost, $connect_transaction_id);
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        
        
        $contract_number = ApplaneConstentInterface::TAMOIL_CONTRACT_CONSTANT.$user_id.'_'.time();
        $cancel_url = $this->createUrls($cancel_url, $connect_transaction_id);
        
        $return_url = $this->createUrls($return_url, $connect_transaction_id);
        //code for session id
        $session_id = $user_id;
        //code for descrizione
        $description_amount = $amount/100;
        $description = "payment_for_".$description_amount."_euro_with_type_".$payment_type."_to_Sixthcontinent";
        //code for url that is angular js url for payment success and failure
        $url = $return_url;
        //code for tipo_servizio (type service)
        $type_service = self::PAYMENT_SERVICE;
        //code for final url to return
        $final_url = $this->container->getParameter('oneclick_pay_url')."?session_id=$session_id&alias=$prod_alias&urlpost=$urlpost&tipo_servizio=$type_service&mac=$mac&divisa=$currency_code&importo=$amount&codTrans=$codTrans&url=$url&url_back=$cancel_url&num_contratto=$contract_number&descrizione=$description";
        $result_data =  array('url'=>$final_url, 'transaction_code'=>$codTrans, 'mac'=>$mac, 'description'=>$description, 'url_back'=>$cancel_url, 'url_post'=>$urlpost, 'return_url'=>$return_url);
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [createOfferPurchaseUrl] with transaction id:'.Utility::encodeData($result_data));
        return $result_data;
    }
    
    /**
     * prepare the url
     * @param string $url
     * @param int $txn_id
     * @return string $encoded_url
     */
    public function createUrls($url, $txn_id) {
        $tamoil_url_const = ApplaneConstentInterface::TAMOIL_OFFER_URL_CONSTANT;
        $url = $url."?".$tamoil_url_const."=".$txn_id;
        $encoded_url = Utility::urlEncode($url);
        return $encoded_url;
    }
    
    /**
     * get offer response
     * @param int $offer_id
     * @return mixed $offer_record|boolean
     */
    public function getOfferPurchaseRecord($offer_id) {
        $em = $this->em;
        $offer_record = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                              ->findOneBy(array('id'=>$offer_id ));
        
        if (!$offer_record) {
            return false;
        }
        return $offer_record;
    }
    
    /**
     * getting the transaction record.
     * @param int $transaction_id
     */
    public function getTransactionObject($transaction_id) {
        $em = $this->em;
        $transaction_record = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                                 ->findOneBy(array('id'=>$transaction_id));
        if (!$transaction_record) {
            return false;
        }
        return $transaction_record;
    }
    
    /**
     * update the transactions status
     * @param object array $transaction
     * @param string $status
     */
    public function updateTransactionStatus($transaction, $status) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateTransactionStatus]');
        $id = $transaction->getId();
        $em = $this->em;

        $transaction = $em->getRepository("SixthContinentConnectBundle:Sixthcontinentconnecttransaction")
                ->findOneBy(array( 'id'=>$id));
        try {
            $transaction->setStatus($status);
            $em->persist($transaction);
            $em->flush();
            $this->__createLog('Transaction is updated with id:'.$id. ' and status:'.$status);
        } catch (\Exception $ex) {
            $this->__createLog('Some error occured when transaction updated with status:'.$status .' Error is: '.$ex->getMessage());
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateTransactionStatus]');
        return true;
    }
    
    /**
     * update the transaction system status
     * @param string $ci_transaction_system_id
     * @param string $status
     */
    public function updateTransactionSystemStatus($transaction, $status) {
        
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateTransactionSystemStatus]');
//        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $em = $this->em;
        $data["ci_used"]= $transaction->getUsedCi();
        $data["buyer_id"] = $transaction->getUserId();
        $SixcTransactionId = $em->getRepository('WalletBundle:WalletCitizen')
                ->increaseWalletCitizenIncome($data);
        
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateTransactionSystemStatus]');
        return true;
    }
    
    /**
     * prepare the offer object
     * @param object array $offer_record
     * @param type $offer_detail
     * @return array $offer_object
     */
    public function getOfferObject($offer_record, $offer_detail) {
        $offer_object = array();
        $id   = $offer_record->getId();
        $shop_id = $offer_record->getShopId();
        $type = $offer_record->getType();
        $name = $offer_record->getName();
        $desc = $offer_record->getDescription();
        $image = $offer_record->getImage();
        $image_thumb = $offer_record->getImageThumb();
        $price = $this->changeRoundAmountCurrency($offer_detail['total_value']);
        $cash_amount = $this->changeRoundAmountCurrency($offer_detail['cash_amount']);
        $used_ci = $this->changeRoundAmountCurrency($offer_detail['ci_used']);
        $discount = $this->changeRoundAmountCurrency($offer_detail['discount']);
        $offer_object = array('id'=>$id, 'name'=>$name, 'image'=>$image, 'image_thumb'=>$image_thumb, 'shop_id'=>$shop_id, 'type'=>$type, 'price'=>$price, 'discount'=>$discount, 'used_ci'=>$used_ci, 'payble'=>$cash_amount, 'description'=>$desc);
        return $offer_object;
    }
    
    /**
     * get offer point of sale.
     * @param int $offer_id
     * @param int $limit_start
     * @param int $limit_size
     */
    public function getOfferPointofSale($offer_id, $limit_start, $limit_size) {
        $em = $this->em;
        $data = array();
        if ($limit_size == -1) { //need to send all records.
            $count = $em->getRepository('SixthContinentConnectBundle:OfferPointofSale')
                        ->getOfferPointofSaleCount($offer_id);
            $limit_start = 0;
            $limit_size  = $count; 
        }
        $sales_points = $em->getRepository('SixthContinentConnectBundle:OfferPointofSale')
                           ->getOfferPointofSale($offer_id, $limit_start, $limit_size);
        $count = $em->getRepository('SixthContinentConnectBundle:OfferPointofSale')
                           ->getOfferPointofSaleCount($offer_id);
        foreach ($sales_points as $sale_point) {
            $data[] = array('offer_id'=>$sale_point['offer_id'], 'name'=>$sale_point['offer_name'], 'image'=>$sale_point['image'], 'image_thumb'=>$sale_point['image_thumb'],'country'=>$sale_point['country'], 'place'=>$sale_point['place'],
                'region'=>$sale_point['region'], 'province'=>$sale_point['province'], 'zip'=>$sale_point['zip'], 'address'=>$sale_point['address'], 'latitude'=>$sale_point['latitude'], 'longitude'=>$sale_point['longitude']);
        }
        
        return array('records'=>(count($data) > 0 ? $data : null), 'count'=>  Utility::getIntergerValue($count));
    }
    
    /**
     * get transaction record.
     * @param object array $transaction
     */
    public function getOfferPurchaseData($transaction) {
        $offer_id = $transaction->getApplicationId();
        $transaction_id = $transaction->getId();
        $offer_value = $this->changeRoundAmountCurrency($transaction->getTransactionValue());
        $discount = $this->changeRoundAmountCurrency($transaction->getDiscount());
        $used_ci  = $this->changeRoundAmountCurrency($transaction->getUsedCi());
        $cash_amount = $this->changeRoundAmountCurrency($transaction->getPaybleValue());
        return array('offer_id'=>$offer_id, 'transaction_id'=>$transaction_id,'offer_value'=>$offer_value, 'discount'=>$discount, 'used_ci'=>$used_ci, 'cash_amount'=>$cash_amount);
    }
    
    /**
     * check citizen ssn
     * @param int $user_id
     * @return int
     */
    public function checkCitizenSsn($user_id) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkCitizenSsn] with userid: '.$user_id);
        $em = $this->em;
        $citizen_ssn = '';
        $user_id = Utility::getIntergerValue($user_id);
        $citizen_user = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                           ->findOneBy(array('userId'=>$user_id));
        if ($citizen_user) {
            $citizen_ssn = $citizen_user->getSsn();
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkCitizenSsn] with userid: '.$user_id. ' and citizen ssn: '.$citizen_ssn);
        return ($citizen_ssn != '' ? 1 : 0);
    }
    
    /**
     * update user ssn
     * @param int $user_id
     * @param string $ssn
     */
    public function updateSsn($user_id, $ssn) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateSsn] with userid and ssn: '.$user_id. ' '.$ssn);
        $em = $this->em;
        $new_ssn = '';
        $user_id = Utility::getIntergerValue($user_id);
        $citizen_user = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                           ->findOneBy(array('userId'=>$user_id));
        if ($citizen_user) {
            try {
                $citizen_user->setSsn($ssn);
                $em->persist($citizen_user);
                $em->flush();
            } catch (\Exception $ex) {
                $this->__createLog('Exiting from class and function with error: '.$ex->getMessage());
            }
            $new_ssn = $citizen_user->getSsn();
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [updateSsn] with userid and ssn: '.$user_id. ' '.$ssn);
        return $new_ssn;
    }
    
    /**
     * checking the available coupon 
     * @param int $user_id
     * @param int $offer_id
     * @return int $count
     */
    public function checkAvailableCoupn($user_id, $offer_id) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkAvailableCoupn] with userid: '.$user_id. ' and offerid:'.$offer_id);
        $em = $this->em;
        $count = 0;
        $coupons = $em->getRepository('SixthContinentConnectBundle:CouponToActive')
                      ->findOneBy(array('offerId'=>$offer_id, 'is_active'=>1, 'isDeleted'=>0));
        if ($coupons) {
            $count = 1;
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkAvailableCoupn] with userid: '.$user_id.' and offer id: '.$offer_id. ' and count: '.$count);
        return $count;
    }
    /**
     * checking the available voucher 
     * @param int $user_id
     * @param int $offer_id
     * @return int $count
     */
    public function checkAvailableCommercialPromotion($user_id, $offer_id) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkAvailableCoupn] with userid: '.$user_id. ' and offerid:'.$offer_id);
        $em = $this->em;
        $count = 0;
        $commercial_promotion = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                      ->getComercialPromotion($offer_id);
        if ($commercial_promotion["response"]["0"]["id"] > 0 ) {
            $count = 1;
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService] and function [checkAvailableCoupn] with userid: '.$user_id.' and offer id: '.$offer_id. ' and count: '.$count);
        return $count;
    }
    
    /**
     * public offer detail
     * @param object array $offer_record
     */
    public function getPublicOfferDetail($offer_record) {
        $data = array();
        $id = $offer_record->getId();
        $name = $offer_record->getName();
        $image = $offer_record->getImage();
        $price = $this->changeRoundAmountCurrency($offer_record->getPrice());
        $type  = $offer_record->getType();
        $discount = $this->changeRoundAmountCurrency($offer_record->getPercentage());
        $description = $offer_record->getDescription();
        $shop_id = $offer_record->getShopId();
        $image_thumb = $offer_record->getImageThumb();
        $data = array('id'=>$id, 'name'=>$name, 'description'=>$description, 'type'=>$type,'shop_id'=>$shop_id, 'price'=>$price, 'discount'=>$discount,'image'=>$image, 'image_thumb'=>$image_thumb);
        return $data;
    }
}
