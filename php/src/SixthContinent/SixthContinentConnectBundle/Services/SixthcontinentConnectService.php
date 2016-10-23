<?php

namespace SixthContinent\SixthContinentConnectBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use SixthContinent\SixthContinentConnectBundle\Entity\SixthcontinentconnectPaymentTransaction;

// validate the data.like iban, vatnumber etc
class SixthcontinentConnectService {

    protected $em;
    protected $dm;
    protected $container;

    CONST QUESTION_MARK = '?';
    CONSt AM_PERSAND = '&';
    CONST CANCELED = 'CANCELED';
    CONST PAY_ONCE = 'PAY_ONCE';

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

    /**
     * prepare the data for returing the url.
     * @param array $data
     * @return array $data
     */
    public function prepareResponseUrlData($data) {
        $return_data = array();
        $url = isset($data['url']) ? $data['url'] : '';
        $url_post = $data['url_post'];
        $session_id = isset($data['session_id']) ? $data['session_id'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $amount = $data['amount'];
        $currency = $data['currency'];
        $transaction_id = $data['transaction_id'];
        $secret = 'Ag8FwDQeQvCMvFZSnhyj7aprwQG9Ao1YwXVngiJN85CuZhH7m6r4gEYD';
        $sixthcontinent_trs_id = '345687';
        $citizen_income = 24;
        $today = new \DateTime('now');
        $today_date = $today->format('Ymd');
        $time = $today->format('his'); //120312(12 hours, 03 minutes, 12 seconds)
        $status = 'COMPLETED';
        $mac_string = "transaction_id=" . $transaction_id . "sixthcontinent_trs_id=" . $sixthcontinent_trs_id .
                "currency=" . $currency . "amount=" . $amount . "citizen_income=" . $citizen_income . "date=" . $today_date . "time=" . $time .
                "status=" . $status . $secret;
        $mac = sha1($mac_string);
        $return_data = array(
            'amount' => $amount,
            'citizen_income' => $citizen_income,
            'currency' => $currency,
            'transaction_id' => $transaction_id,
            'language_id' => 'ITA',
            // 'mac' => $mac,
            'date' => $today_date,
            'time' => $time,
            'email' => 'mine@gmail.com',
            'user_id' => 30038,
            'first_name' => 'yogendra',
            'status' => $status,
            'last_name' => 'singh',
                //'sixthcontinent_trs_id' => $sixthcontinent_trs_id,
                // 'paypal_trs_id' => '0U992533FM897915K,34C7150235262061R'
        );
        $post_return_data = array(
            'amount' => $amount,
            'citizen_income' => $citizen_income,
            'currency' => $currency,
            'transaction_id' => $transaction_id,
            'language_id' => 'ITA',
            'mac' => $mac,
            'date' => $today_date,
            'time' => $time,
            'email' => 'mine@gmail.com',
            'user_id' => 30038,
            'first_name' => 'yogendra',
            'status' => $status,
            'last_name' => 'singh',
            'sixthcontinent_trs_id' => $sixthcontinent_trs_id,
            'paypal_trs_id' => '0U992533FM897915K,34C7150235262061R'
        );
        if ($session_id != '')
            $return_data['session_id'] = $session_id;
        if ($description)
            $return_data['description'] = $description;
        $post_data_query = $this->postdataQuery($post_return_data);
        $this->sendPostUrl($url_post, $post_data_query); //send data to post back url.
        $final_url = $this->prepareQueryString($url, $return_data); //prepare the query string with url
        return $final_url;
    }

    /**
     * prepare the query string with url.
     * @param string $url
     * @param array $data
     */
    public function prepareQueryString($url, $data) {
        $req = '';
        if (count($data) > 0) { //prepare the string for url
            if (function_exists('get_magic_quotes_gpc')) {
                $get_magic_quotes_exists = true;
            }
            foreach ($data as $key => $value) {
                if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                    $value = urlencode(stripslashes($value));
                } else {
                    $value = urlencode($value);
                }
                $req .= "&$key=$value";
            }
        }
        $url = Utility::getRightTrimString($url, self::QUESTION_MARK);
        $parse_url = parse_url($url);
        $query_string = isset($parse_url['query']) ? $parse_url['query'] : '';
        if ($query_string == '') {
            $url = $url . self::QUESTION_MARK;
            $req = Utility::getLeftTrimString($req, self::AM_PERSAND);
        }
        $final_url = $url . $req;
        return $final_url;
    }

    /**
     * prepare the query with out url.
     * @param type $post_return_data
     * @return type
     */
    public function postdataQuery($post_return_data) {
        $req = '';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        if (count($post_return_data) > 0) { //prepare the string for url
            foreach ($post_return_data as $key => $value) {
                if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                    $value = urlencode(stripslashes($value));
                } else {
                    $value = urlencode($value);
                }
                $req .= "&$key=$value";
            }
        }
        $req = Utility::getLeftTrimString($req, self::AM_PERSAND);
        return $req;
    }

    /**
     * send a call to url post data.
     * @param string $url_post
     * @param string $post_data_query
     */
    public function sendPostUrl($url_post, $post_data_query) {
        $ch = curl_init($url_post);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_query);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        try {
            curl_exec($ch);
        } catch (\Exception $ex) {
            
        }
        curl_close($ch);
        return true;
    }

    /**
     * verify the mac
     * @param type $mac_params
     * @param type $raw_mac
     */
    public function verifyMac($mac_params, $raw_mac, $secret) {
        $mac = '';
        foreach ($mac_params as $key => $value) {
            $mac .= $key . '=' . $value;
        }
        $mac .= $secret;
        $mac = sha1($mac);
        if ($raw_mac == $mac) {
            return true;
        }
        return false;
    }

    /**
     * calculating the transacion break up with credit
     * @param float $amount
     * @param int $user_id
     */
    public function getTransactionBreakupWithCredit($amount, $user_id) {
        $connect_app_service = $this->container->get('sixth_continent_connect.connect_app');
        $amount_with_decimal = $amount;
        $ci_used = $is_ci_used = 0;
        $amount = $this->changeAmountCurrency($amount_with_decimal);
        $wallet_citizen = $this->em->getRepository("WalletBundle:WalletCitizen")
                ->getavailablecitizenincome($user_id);

        $citizen_income_data['credit'] = floor($wallet_citizen[0]["citizenIncomeAvailable"])/100;//available citizen income

        $available_income = $citizen_income_data['credit']; //available citizen income
        
        $useable_citizen_income = $this->getUseableCitizenIncome($available_income);
        $discount_reduced_amount = $this->reduceDiscount($amount);
        $discount_after_amount = $discount_reduced_amount['amount'];
        $discount_amount = $discount_reduced_amount['discount'];
        $max_income_useable = $this->getCitizenIncomePercentage($discount_after_amount);

        if ($useable_citizen_income >= $max_income_useable) {
            $ci_used = $this->getUseableCitizenIncome($max_income_useable);
        } else {
            $ci_used = $useable_citizen_income;
        }
        if ($ci_used > 0) {
            $is_ci_used = 1;
        }        
        $checkout_value = $this->getCheckoutValue($discount_after_amount);
        $checkout_vat = $this->checkoutVat($checkout_value);
        $chekout_with_vat = $checkout_value + $checkout_vat;
        $secondry_user_amount = $this->roundAmount($chekout_with_vat);
        $cash_amount = $this->roundAmount($discount_after_amount - $ci_used);
        $ci_used     = $this->roundAmount($ci_used);
        $available_income = $this->roundAmount($available_income);
        $discount_amount  = $this->roundAmount($discount_amount);
        $checkout_vat     = $this->roundAmount($checkout_vat);
        $amount = $this->roundAmount($amount);
        $final_array = array('cash' => $cash_amount, 'secondry_user_amount' => $secondry_user_amount, 'ci_used' => $ci_used, 'available_ci' => $available_income, 'checkout_value' => $checkout_value, 'discount' => $discount_amount, 'total_amount' => $amount, 'checkout_vat'=>$checkout_vat, 'is_ci_used' => $is_ci_used);
        $this->__createLog('Transaction Break up: ' . Utility::encodeData($final_array));
        return $final_array;
    }

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
     * calculating the transacion break up with out credit
     * @param float $amount
     * @param int $user_id
     */
    public function getTransactionBreakupWithOutCredit($amount, $user_id) {
        $amount_with_decimal = $amount;
        $ci_used = 0;
        $amount = $this->changeAmountCurrency($amount_with_decimal);
        $discount_reduced_amount = $this->reduceDiscount($amount);
        $discount_after_amount = $discount_reduced_amount['amount'];
        $discount_amount = $discount_reduced_amount['discount'];
        $cash_amount = $discount_after_amount;
        return array('cash' => $cash_amount, 'ci_used' => $ci_used, 'discount' => $discount_amount, 'total_amount' => $amount_with_decimal, 'is_ci_used' => 0);
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

    /**
     * reduce the discount from amount
     * @param float $amount
     * return float 
     */
    public function reduceDiscount($amount) {
        $discount = $this->getConnectDiscount();
        return array('amount' => ($amount - $discount), 'discount' => $discount);
    }

    /**
     * get usable citizen income
     * @param float $available_income
     */
    public function getUseableCitizenIncome($available_income) {
        $connect_app_service = $this->container->get('sixth_continent_connect.connect_app');
        $this->__createLog('Citizen income is coming: '.$available_income);
        $card_value = $this->getConnectCardValue();
        $result = 0;
        if($available_income > 0 ){
        $income = intval($available_income / $card_value); //getting interger value
        $result = ($income * $card_value);
        }
        $this->__createLog('Useable citizen income: '.$result);
        return $result;
    }

    /**
     * change the amount by diving on 100
     * @param float $amount
     * @return float
     */
    public function changeAmountCurrency($amount) {
        $amount_currency = $this->getConnectAmountCurrency();
        return $amount / $amount_currency;
    }

    /**
     * initiate the transaction for sixthcontinent connect
     * @param array $data
     */
    public function initiateConnectTransation($data) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateConnectTransation]');
        $em = $this->em;
        $id = 0;
        $today = new \DateTime('now');
        $today_format = $today->format('Y-m-d H:i:s');
        $shop_id = isset($data['shop_id']) ? $data['shop_id'] : 0;
        $transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : '';
        $application_id = isset($data['application_id']) ? $data['application_id'] : '';
        $application_user_id = isset($data['application_user_id']) ? $data['application_user_id'] : 0;
        $card_preference = isset($data['card_preference']) ? $data['card_preference'] : '';
        $transaction_type = isset($data['transaction_type']) ? $data['transaction_type'] : '';
        $currency = isset($data['currency']) ? $data['currency'] : '';
        $language_id = isset($data['language_id']) ? $data['language_id'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $transaction_value = isset($data['transaction_value']) ? $data['transaction_value'] : 0;
        $discount = isset($data['discount']) ? $data['discount'] : 0;
        $payble_value = isset($data['payble_value']) ? $data['payble_value'] : 0;
        $vat = isset($data['vat']) ? $data['vat'] : 0;
        $checkout_value = isset($data['checkout_value']) ? $data['checkout_value'] : 0;
        $description = isset($data['description']) ? $data['description'] : '';
        $url = isset($data['url']) ? $data['url'] : '';
        $url_post = isset($data['url_post']) ? $data['url_post'] : '';
        $url_back = isset($data['url_back']) ? $data['url_back'] : '';
        $type_service = isset($data['type_service']) ? $data['type_service'] : '';
        $mac = isset($data['mac']) ? $data['mac'] : '';
        $paypal_transaction_id = isset($data['paypal_transaction_id']) ? $data['paypal_transaction_id'] : '';
        $ci_transaction_system_id = isset($data['ci_transaction_system_id']) ? $data['ci_transaction_system_id'] : '';
        $total_available_ci = isset($data['total_available_ci']) ? $data['total_available_ci'] : 0;
        $used_ci = isset($data['used_ci']) ? $data['used_ci'] : 0;
        $paypal_transaction_reference = isset($data['paypal_transaction_reference']) ? $data['$paypal_transaction_reference'] : '';
        $session_id = isset($data['session_id']) ? $data['session_id'] : '';
        $time_stamp = strtotime($today_format);
        try {
            $connect_transaction = new Sixthcontinentconnecttransaction();
            $connect_transaction->setUserId($data['user_id']);
            $connect_transaction->setShopId($shop_id);
            $connect_transaction->setTransactionId($transaction_id);
            $connect_transaction->setApplicationId($application_id);
            $connect_transaction->setCardPreference($card_preference);
            $connect_transaction->setDate($today);
            $connect_transaction->setTransactionType($transaction_type);
            $connect_transaction->setCurrency($currency);
            $connect_transaction->setLanguageId($language_id);
            $connect_transaction->setStatus($status);
            $connect_transaction->setTransactionValue($transaction_value);
            $connect_transaction->setDiscount($discount);
            $connect_transaction->setPaybleValue($payble_value);
            $connect_transaction->setVat($vat);
            $connect_transaction->setCheckoutValue($checkout_value);
            $connect_transaction->setDescription($description);
            $connect_transaction->setUrl($url);
            $connect_transaction->setUrlPost($url_post);
            $connect_transaction->setUrlBack($url_back);
            $connect_transaction->setTypeService($type_service);
            $connect_transaction->setMac($mac);
            $connect_transaction->setPaypalTransactionId($paypal_transaction_id);
            $connect_transaction->setCiTransactionSystemId($ci_transaction_system_id);
            $connect_transaction->setTotalAvailableCi($total_available_ci);
            $connect_transaction->setUsedCi($used_ci);
            $connect_transaction->setPaypalTransactionReference($paypal_transaction_reference);
            $connect_transaction->setBusinessAccountUserId($application_user_id);
            $connect_transaction->setSessionId($session_id);
            $connect_transaction->setTimeStamp($time_stamp);
            $em->persist($connect_transaction);
            $em->flush();
            $id = $connect_transaction->getId();
        } catch (\Exception $ex) {
            $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateConnectTransation] with connecttransaction table id: ' . $ex->getMessage());
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateConnectTransation] with connecttransaction table id: ' . $id);
        return $id;
    }

    /**
     * prepare the back url
     * @param arrray $data
     * $return string $back_url
     */
    public function prepareBackUrl($data) {
        $url_back = $data['url_back'];
        $query_data = array(
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'transaction_id' => $data['transaction_id'],
            'status' => self::CANCELED
        );
        $back_url = $this->prepareQueryString($url_back, $query_data);
        return $back_url;
    }

    /**
     * Frezze the citizen income on transaction system.
     * @param int $user_id
     * @param float $ci_used
     */
    public function registerTransactionOnApplane($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $this->__createLog('Entering in to class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [registerTransactionOnApplane]');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_response = $applane_service->initiateConnectTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status);
        $transaction_id = $applane_response;
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [registerTransactionOnApplane]');
        return $transaction_id;
    }

    /**
     * update the transaction for key reference
     * @param object array $connect_transaction
     * @param string $pay_key
     * @param string $ci_transaction_system_id
     * @param string $status
     */
    public function updateConnectTransaction($connect_transaction, $pay_key, $ci_transaction_system_id, $status) {
        $em = $this->em;
        $id = 0;
        $connect_transaction->setPaypalTransactionReference($pay_key);
        $connect_transaction->setStatus($status);
        $connect_transaction->setCiTransactionSystemId($ci_transaction_system_id);
        try {
            $em->persist($connect_transaction);
            $em->flush();
            $id = $connect_transaction->getId();
        } catch (\Exception $ex) {
            
        }
        return $id;
    }

    /**
     * update table [sixthcontinentconnecttransaction] the transaction status and paypal ids
     * @param object $connect_transaction
     * @param string $status
     * @param string $paypal_transaction_ids
     */
    public function updateConnectTransactionStatus($connect_transaction, $status, $paypal_transaction_ids = '') {
        $this->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [updateConnectTransactionStatus] with status: '.$status);
        $em = $this->em;
        $id = $connect_transaction->getId();
        $connect_transaction->setStatus($status);
        $connect_transaction->setPaypalTransactionId($paypal_transaction_ids);
        try {
            $em->persist($connect_transaction);
            $em->flush();
        } catch (\Exception $ex) {
            
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [updateConnectTransactionStatus] with status: '.$status);
        return $id;
    }

    /**
     * prepare the mac for response data in url and url post
     * @param type $transaction_id
     * @param type $sixthcontinent_trs_id
     * @param type $currency
     * @param type $amount
     * @param type $citizen_income
     * @param type $today_date
     * @param type $time
     * @param type $status
     * @param type $secret
     * @return type
     */
    public function prePareResponseMac($transaction_id, $sixthcontinent_trs_id, $currency, $amount, $citizen_income, $today_date, $time, $status, $secret) {
        $mac_string = "transaction_id=" . $transaction_id . "sixthcontinent_trs_id=" . $sixthcontinent_trs_id .
                "currency=" . $currency . "amount=" . $amount . "citizen_income=" . $citizen_income . "date=" . $today_date . "time=" . $time .
                "status=" . $status . $secret;
        $mac = sha1($mac_string);
        return $mac;
    }

    /**
     * get the application data
     * @param type $app_id
     * @return type object
     */
    public function getApplicationData($app_id) {
        $em = $this->em;
        $app_data = $em->getRepository('SixthContinentConnectBundle:Application')
                ->findOneBy(array('applicationId' => $app_id));
        return $app_data;
    }

    /**
     * Prepare the get array for connect url.
     * @return array $get_data
     */
    public function prepareGetArray($amount, $ci_used, $currency, $transaction_id, $language_id, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $session_id='', $description='') {
        $get_data = array('amount' => $amount, 'citizen_income' => $ci_used, 'currency' => $currency, 'transaction_id' => $transaction_id, 'language_id' => $language_id,
            'date' => $today_date, 'time' => $time, 'email' => $user_email, 'user_id' => $user_id, 'first_name' => $first_name, 'status' => $status,
            'last_name' => $last_name);
        if ($session_id != '') {
            $get_data['session_id'] = $session_id;
        }
        if ($description != '') {
            $get_data['description'] = $description;
        }
        return $get_data;
    }

    /**
     * Prepare the post array for connect url.
     * @return array $post_data
     */
    public function preparePostArray($amount, $ci_used, $currency, $transaction_id, $language_id, $result_mac, $today_date, $time, $user_email, $user_id, $first_name, $status, $last_name, $connect_transaction_id, $paypal_transaction_id, $session_id='', $description='') {
        $post_data = array('amount' => $amount, 'citizen_income' => $ci_used, 'currency' => $currency, 'transaction_id' => $transaction_id, 'language_id' => $language_id,
            'mac' => $result_mac, 'date' => $today_date, 'time' => $time, 'email' => $user_email, 'user_id' => $user_id, 'first_name' => $first_name, 'status' => $status,
            'last_name' => $last_name, 'sixthcontinent_trs_id' => $connect_transaction_id, 'paypal_trs_id' => $paypal_transaction_id);
        if ($session_id != '') {
            $post_data['session_id'] = $session_id;
        }
        if ($description != '') {
            $post_data['description'] = $description;
        }
        return $post_data;
    }

    /**
     * getting the checkout value
     * @param float $amount
     * @return float $checkout_value
     */
    public function getCheckoutValue($amount) {
        $chekout_percentage = $this->getConnectChekoutPercentage();
        return ($amount * $chekout_percentage) / 100;
    }

    public function getConnectAmountCurrency() {
        return $this->container->getParameter('connect_amount_currency');
    }

    public function getConnectCardValue() {
        return $this->container->getParameter('connect_card_value');
    }

    public function getConnectDiscount() {
        return $this->container->getParameter('connect_discount');
    }

    public function getCiPercentage() {
        return $this->container->getParameter('connect_ci_percentage');
    }

    public function getConnectChekoutPercentage() {
        return $this->container->getParameter('connect_checkout_percentage');
    }

    public function upDateConnectApplane($transaction_system_id, $applane_status) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        if ($transaction_system_id != '') { //check for if transaction id is not blank.
            $applane_service->UpdateConnectTransactionStatus($transaction_system_id, $applane_status);
        }
        return true;
    }

    public function getConnectPaypalEmail() {
        return $this->container->getParameter('connect_paypal_email');
    }

    public function getConnectCurrencyroundPlace() {
        return $this->container->getParameter('connect_amount_round');
    }
    
    /**
     * prepare the array for connect payment transaction
     * @param int $connect_transaction_id
     * @param int $user_id
     * @param string $app_id
     * @param string $reason
     * @param string $payment_via
     * @param string $status
     * @param string $pay_key
     * @param int $total_amount
     * @param string $ci_transaction_id
     * @param int $cash_amount
     * @return type
     */
    public function prepareConnectPaymentTransactionData($connect_transaction_id, $user_id, $app_id, $reason, $payment_via, $status, $pay_key, $total_amount, $ci_transaction_id, $cash_amount) {
        $result_array = array(
            'connect_transaction_id' => $connect_transaction_id,
            'app_id' => $app_id,
            'user_id' => $user_id,
            'reason' => $reason,
            'payment_via' => $payment_via,
            'status' => $status,
            'pay_key' => $pay_key,
            'cash_amount' => $total_amount,
            'ci_transaction_id' => $ci_transaction_id,
            'ci_used' => $cash_amount
        );
        return $result_array;
    }

    /**
     * initiate transaction in sixthcontinent payment transaction
     * @param type $connect_payment_transaction_data
     * @return type
     */
    public function initiateSixthcontinentPaymentTransaction($connect_payment_transaction_data) {
        $this->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateSixthcontinentPaymentTransaction]');
        $connect_payment_transaction = new SixthcontinentconnectPaymentTransaction();
        $em = $this->em;
        $id = 0;
        $date = new \DateTime('now');
        $today_format = $date->format('Y-m-d H:i:s');
        $connect_transaction_id = isset($connect_payment_transaction_data['connect_transaction_id']) ? $connect_payment_transaction_data['connect_transaction_id'] : '';
        $user_id = isset($connect_payment_transaction_data['user_id']) ? $connect_payment_transaction_data['user_id'] : 0;
        $reason = isset($connect_payment_transaction_data['reason']) ? $connect_payment_transaction_data['reason'] : '';
        $app_id = isset($connect_payment_transaction_data['app_id']) ? $connect_payment_transaction_data['app_id'] : '';
        $payment_via = isset($connect_payment_transaction_data['payment_via']) ? $connect_payment_transaction_data['payment_via'] : '';
        $status = isset($connect_payment_transaction_data['status']) ? $connect_payment_transaction_data['status'] : '';
        $error_code = isset($connect_payment_transaction_data['error_code']) ? $connect_payment_transaction_data['error_code'] : '';
        $error_description = isset($connect_payment_transaction_data['error_description']) ? $connect_payment_transaction_data['error_description'] : '';
        $pay_key = isset($connect_payment_transaction_data['pay_key']) ? $connect_payment_transaction_data['pay_key'] : '';
        $transaction_value = isset($connect_payment_transaction_data['cash_amount']) ? $connect_payment_transaction_data['cash_amount'] : 0;
        $vat_amount = isset($connect_payment_transaction_data['vat_amount']) ? $connect_payment_transaction_data['vat_amount'] : 0;
        $contract_id = isset($connect_payment_transaction_data['contract_id']) ? $connect_payment_transaction_data['contract_id'] : '';
        $paypal_id = isset($connect_payment_transaction_data['paypal_id']) ? $connect_payment_transaction_data['paypal_id'] : '';
        $ci_transaction_id = isset($connect_payment_transaction_data['ci_transaction_id']) ? $connect_payment_transaction_data['ci_transaction_id'] : '';
        $ci_used = isset($connect_payment_transaction_data['ci_used']) ? $connect_payment_transaction_data['ci_used'] : 0;
        $time_stamp = strtotime($today_format);

        $connect_payment_transaction->setSixthcontinentConnectId($connect_transaction_id);
        $connect_payment_transaction->setUserId($user_id);
        $connect_payment_transaction->setReason($reason);
        $connect_payment_transaction->setAppId($app_id);
        $connect_payment_transaction->setPaymentVia($payment_via);
        $connect_payment_transaction->setPaymentStatus($status);
        $connect_payment_transaction->setErrorCode($error_code);
        $connect_payment_transaction->setErrorDescription($error_description);
        $connect_payment_transaction->setTransactionReference($pay_key);
        $connect_payment_transaction->setDate($date);
        $connect_payment_transaction->setTransactionValue($transaction_value);
        $connect_payment_transaction->setVatAmount($vat_amount);
        $connect_payment_transaction->setContractId($contract_id);
        $connect_payment_transaction->setPaypalId($paypal_id);
        $connect_payment_transaction->setCiTransactionId($ci_transaction_id);
        $connect_payment_transaction->setCiUsed($ci_used);
        $connect_payment_transaction->setTimeStamp($time_stamp);
        try {
            $em->persist($connect_payment_transaction);
            $em->flush($connect_payment_transaction);
            $id = $connect_payment_transaction->getId();
        } catch (\Exception $ex) {
          $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateSixthcontinentPaymentTransaction]: '.$ex->getMessage());  
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [initiateSixthcontinentPaymentTransaction] with id: '.$id);
        return $id;
    }

    /**
     * update table [sixthcontinentconnectpaymenttransaction] connect payment transacion status
     * @param type $connect_transaction_id
     * @param type $status
     * @param type $paypal_ids_object
     * @return boolean
     */
    public function updateConnectPaymentTransactionStatus($connect_transaction_id, $status, $paypal_ids_object) {
        $em = $this->em;
        $connect_payment = $em->getRepository('SixthContinentConnectBundle:SixthcontinentconnectPaymentTransaction')
                              ->findOneBy(array('sixthcontinentConnectId' => $connect_transaction_id, 'reason'=>self::PAY_ONCE));
        if (!$connect_payment) {
            return true;
        }
        try {
            $connect_payment->setPaymentStatus($status);
            $connect_payment->setPaypalId($paypal_ids_object);
            $em->persist($connect_payment);
            $em->flush();
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * update table [sixthcontinentconnectpaymenttransaction] table connect transaction status by id
     * @param type $connect_payment_transaction
     * @param type $status
     * @param type $paypal_ids_object
     * @return boolean
     */
    public function updateConnectPaymentTransactionStatusById($connect_payment_transaction, $status, $paypal_ids_object) {
        $this->__createLog('Enter into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [updateConnectPaymentTransactionStatusById] with status: '.$status);
        $em = $this->em;
        try {
            $connect_payment_transaction->setPaymentStatus($status);
            $connect_payment_transaction->setPaypalId($paypal_ids_object);
            $em->persist($connect_payment_transaction);
            $em->flush();
            $id = $connect_payment_transaction->getId();
        } catch (\Exception $ex) {
            
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService] and function [updateConnectPaymentTransactionStatusById] with id: '.$id.' and status: '.$status);
        return true;
    }

    /**
     * getting paypal email of application.
     * @param string $app_id
     */
    public function getApplicationPaypalEmail($app_id) {
        $em = $this->em;
        $app_paypal_email = '';
        $app_paypal_data = $em->getRepository('SixthContinentConnectBundle:ApplicationPaypalInformation')
                              ->findOneBy(array('appId' => $app_id));
        if (!$app_paypal_data) {
            return $app_paypal_email;
        }
        $app_paypal_email = $app_paypal_data->getEmail();
        return $app_paypal_email;
    }

    /**
     * change the amount by multiply by 100
     * @param float $amount
     * @return int
     */
    public function makeAmountCurrency($amount) {
        $amount_currency = $this->getConnectAmountCurrency();
        return ($amount * $amount_currency);
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
}
