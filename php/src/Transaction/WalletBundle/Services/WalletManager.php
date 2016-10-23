<?php

namespace Transaction\WalletBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Transaction\WalletBundle\Repository\WalletCitizen;

class WalletManager {

    protected $_em;
    protected $_dm;
    protected $_container;
    public static $OFFSET = 0;
    public static $RANGE = 7;
    public static $CIGAINED_TYPE = 6721;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em = null, DocumentManager $dm = null, Container $container = null) {
        $this->_em = $em;
        $this->_dm = $dm;
        $this->_container = $container;
        //$this->request   = $request;
    }

    public function getBuyerCurrency($buyerId) {
        return 'EUR';
    }

    public function getCurrencyCode($code) {
        $amount = 123456;
        $formatter = new \NumberFormatter('en', \NumberFormatter::CURRENCY);
        $string = $formatter->formatCurrency($amount, $code);
        return $this->get_currency_symbol($string);
    }

    public function get_currency_symbol($string) {
        $symbol = '';
        $length = mb_strlen($string, 'utf-8');
        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($string, $i, 1, 'utf-8');
            if (!ctype_digit($char) && !ctype_punct($char))
                $symbol .= $char;
        }
        return $symbol;
    }

    public function convertCurrency($amount) {
        if ($amount) {
            return $amount / 100;
        }
    }

    /**
     * Get Detail of Single Record
     * @param array $param : array('buyer_id', 'record_id', 'record_type_id')
     * record_type_id : it is six_c trc id if it is a transaction
     * if it is c.i it's only  
     *              
     * @return type
     */
    public function getRecordDetail($param) {
        $record_type = $param["record_type_id"];
        $em = $this->_em;
        $currency_lable = "EUR";
        $currency_simble = $this->getCurrencyCode($currency_lable);
        if ($record_type == self::$CIGAINED_TYPE) {
            $repo_wallet = $em->getRepository("WalletBundle:WalletCitizen");
            $range = $this->getStartEndTimeBaseOnDate($param["record_id"]);
            $param["start_time"] = $range["start_time"];
            $param["end_time"] = $range["end_time"];
            $ci_array = $repo_wallet->getCiGainedByDay($param);
            $record = $this->groupCiDetail($ci_array, $em, $currency_simble, $currency_lable);
        } else {
            $repo_trs = $em->getRepository("TransactionSystemBundle:Transaction"); // nyhow i needd
            $trs_array = $repo_trs->getTransactionHistory($param);
            $record = $this->groupTransactionDetail($trs_array, $currency_simble, $currency_lable);
        }
        $return["code"] = "101";
        $return["message"] = "SUCCESS";
        $return["dataInfo"] = array();
        $return["response"] = $record;
        return $return;
    }

    /**
     * 
     * @param array $param array('buyer_id', 'numberofdays', 'startday')
     * @return type
     */
    public function giveHistoryWalletCitizenList($param) {
        $number_of_days = isset($param["numberofdays"]) ? $param["numberofdays"] : self::$OFFSET;
        $offset = isset($param["startday"]) ? $param["startday"] : self::$RANGE;
        $range = $this->getStartEndTime($offset, $number_of_days);
        $param["start_time"] = $range["start_time"];
        $param["end_time"] = $range["end_time"];


        $transaction_repo = $this->_em->getRepository("TransactionSystemBundle:Transaction");
        $array_transaction = $transaction_repo->getTransactionHistory($param);

        $wallet_citizen_repo = $this->_em->getRepository("WalletBundle:WalletCitizen");
        $array_ci = $wallet_citizen_repo->getCiGainedByDay($param);

        $dates = $this->createArrayNumberOfDays($offset, $number_of_days);

        $history = $this->orderWalletHistoryByDay($dates, $array_ci, $array_transaction);

        $date_info = $this->getPages($offset, $number_of_days, $param);        
        $return["code"] = "101";
        $return["message"] = "SUCCESS";
        $return["dataInfo"] = $date_info;
        $return["response"] = $history;
        return $return;
    }

    /**
     * Order transaction by day
     * @param type $dates
     * @param type $array_ci
     * @param type $array_transaction
     * @return type
     */
    public function orderWalletHistoryByDay($dates, $array_ci, $array_transaction) {
        //Add the parameter date
        $ci_cashback = $array_ci["ci_cashback"];
        $ci_citizen_affiliated = $array_ci["ci_citizen_affiliated"];
        $ci_shop_affiliated = $array_ci["ci_shop_affiliated"];
        $ci_all_nation = $array_ci["ci_all_nation"];
        if (count($ci_cashback) > 0) {
            foreach ($ci_cashback as $key => $value) {
                $datetime = date("d-m-Y", $ci_cashback[$key]["cashback_time"]);
                $ci_cashback[$key]["date"] = $datetime;
                $ci_cashback[$key]["datetime"] = $datetime;
            }
        }
        if (count($ci_citizen_affiliated) > 0) {
            foreach ($ci_citizen_affiliated as $key => $value) {
                $datetime = date("d-m-Y", $ci_citizen_affiliated[$key]["citizen_affiliated_time"]);
                $ci_citizen_affiliated[$key]["date"] = $datetime;
                $ci_citizen_affiliated[$key]["datetime"] = $datetime;
            }
        }
        if (count($ci_shop_affiliated) > 0) {
            foreach ($ci_shop_affiliated as $key => $value) {
                $datetime = date("d-m-Y", $ci_shop_affiliated[$key]["shop_affiliated_time"]);
                $ci_shop_affiliated[$key]["date"] = $datetime;
                $ci_shop_affiliated[$key]["datetime"] = $datetime;
            }
        }
        if (count($ci_all_nation) > 0) {
            foreach ($ci_all_nation as $key => $value) {
                $datetime = date("d-m-Y", $ci_all_nation[$key]["all_time"]);
                $ci_all_nation[$key]["date"] = $datetime;
                $ci_all_nation[$key]["datetime"] = $datetime;
            }
        }
        
        //Add the parameter date
        foreach ($array_transaction as $key => $value) {
            $array_transaction[$key]["date"] = date("d-m-Y", $array_transaction[$key]["time_close"]);
            $array_transaction[$key]["datetime"] = date("d-m-Y H:i:s", $array_transaction[$key]["time_close"]);
        }
        $result = array();
        $currency_simble = $this->getCurrencyCode("EUR");
        foreach ($dates as $date) {
            $result = $this->findTransaction($date, $array_transaction, $result, $currency_simble );
            $result[] = $this->findCi($date, $ci_cashback, $ci_citizen_affiliated, $ci_shop_affiliated, $ci_all_nation, $currency_simble);
        }
        return $result;
    }

    /**
     * IT finds and group c.i based on the tthe date
     * @param type $date
     * @param type $array_ci
     * @return type
     */
    public function findCi($date, $ci_cashback, $ci_citizen_affiliated, $ci_shop_affiliated, $ci_all_nation ,$currency_simble) {
        $records = array();
        $records["record"]["id"] = $date;
        $records["record"]["datetime"] = $date;
        $records["record"]["type"] = "citizen income";
        $records["record"]["record_type_id"] = (string) self::$CIGAINED_TYPE;
        $records["record"]["record_label"] = "CIGAINED";
        $records["record"]["record_id"] = "";
        $records["record"]["record_sixc_id"] = "";
        $records["seller"] = array();
        $records["id"] = $date;
        $records["record_detail"]["currency_simble"] = $currency_simble;
        $records["record_detail"]["currency_label"] = "EUR";
        $records["record_detail"]["out"] = "0";

        $in = 0;
        foreach ($ci_cashback as $key => $value) {
            if (isset($ci_cashback[$key]["date"]) && $ci_cashback[$key]["date"] == $date) {
                $in+= ($ci_cashback[$key]["ci_cashback_share"] > 0 ) ? $ci_cashback[$key]["ci_cashback_share"] : 0;
            }
        }
        foreach ($ci_citizen_affiliated as $key => $value) {
            if (isset($ci_citizen_affiliated[$key]["date"]) && $ci_citizen_affiliated[$key]["date"] == $date) {
                $in+= ($ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"] > 0 ) ? $ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"] : 0;
            }
        }
        foreach ($ci_shop_affiliated as $key => $value) {
            if (isset($ci_shop_affiliated[$key]["date"]) && $ci_shop_affiliated[$key]["date"] == $date) {
                $in+= ($ci_shop_affiliated[$key]["ci_shop_affiliated_share"] > 0 ) ? $ci_shop_affiliated[$key]["ci_shop_affiliated_share"] : 0;
            }
        }
        foreach ($ci_all_nation as $key => $value) {
            if (isset($ci_all_nation[$key]["date"]) && $ci_all_nation[$key]["date"] == $date) {
                $in+= ($ci_all_nation[$key]["ci_all_share"] > 0 ) ? $ci_all_nation[$key]["ci_all_share"] : 0;
            }
        }
        $records["record_detail"]["in"] = (string) number_format($in / 100, 2, '.', '');
        return $records;
    }

    /**
     * IT finds transaction of one day 
     * @param type $date
     * @param type $array_transaction
     * @param type $result
     * @return type
     */
    public function findTransaction($date, $array_transaction, $result, $currency_simble) {
        $records = array();
        foreach ($array_transaction as $key => $value) {
            if ($array_transaction[$key]["date"] == $date) {
                $records["record"]["id"] = $array_transaction[$key]["sixc_id"];
                $records["record"]["type"] = "transaction";
                $records["record"]["record_type_id"] = $array_transaction[$key]["record_type_id"];
                $records["record"]["record_id"] = $array_transaction[$key]["id"];
                $records["record"]["record_sixc_id"] = $array_transaction[$key]["sixc_id"];
                $records["record"]["datetime"] = $array_transaction[$key]["datetime"];
                $records["seller"] = array();
                $records["id"] = $date;
                $records["record_detail"]["out"] = (string) number_format($array_transaction[$key]["price"] / 100, 2, '.', '');
                $cashback_amount = $array_transaction[$key]["cashback_amount"];
                $redistribution_status = $array_transaction[$key]["redistribution_status"];
                $cashback_amount = (string) number_format($cashback_amount / 100, 2, '.', '');
                $amount_cash_back = ( $redistribution_status > 0 ) ? $cashback_amount : "0";
                $records["record_detail"]["in"] = $amount_cash_back;
                $records["record_detail"]["currency_simble"] = $currency_simble;
                $records["record_detail"]["currency_label"] = "EUR";
                $records["seller"]["seller_type_id"] = $array_transaction[$key]["record_type_id"];
                $type_result = $this->recordDetaleBasedOnType($array_transaction[$key]);
                $records["record"]["record_label"] = $type_result["record_label"];
                $records["seller"]["type_label"] = $type_result["seller_type_label"];
                $records["seller"]["business_name"] = $type_result["name"];
                $records["seller"]["redirect_id"] = $type_result["redirect_id"];

                $result[] = $records;
            }
        }
        return $result;
    }

    //   static $PAY_IN_SHOP = "1"; // Payment in Shop
//    static $PAY_OFFER = "2"; // 
//    static $PAY_ONCE = "3"; // Sixthcontinent Connect
//    static $PAYPAL_ONCE = "4"; // Shopping Card
//    static $PAY_ONCE_OFFER = "5"; // Special offer 
//        $type_result["record_object"] ="";   
    function recordDetaleBasedOnType($trasaction) {

        $type_result["record_object"] = "";
        if ($trasaction["record_type_id"] == 1) {
            $type_result["record_label"] = "PAYINSHOP";
            $type_result["seller_type_label"] = "SHOP";
            $type_result["name"] = $trasaction["business_name"];
            $type_result["redirect_id"] = array("seller_id" => (string) $trasaction["seller_id"], "promotion_id" => "");
        } elseif ($trasaction["record_type_id"] == 3) {
            $type_result["record_label"] = "SIXTHCONTINENTCONNECT";
            $type_result["seller_type_label"] = "BUSINESSPROFILE";
            $type_result["name"] = "CarreraJeans.com Ecommerce";
            $type_result["redirect_id"] = array("seller_id" => "APP-2345DERT", "promotion_id" => "");
        } else if ($trasaction["record_type_id"] == 4) {
            $type_result["record_label"] = "SHOPPINGCARD";
            $type_result["seller_type_label"] = "BUSINESSPROFILE";
            $type_result["name"] = "Shopping Card " . $trasaction["business_name"];
            $type_result["redirect_id"] = array("seller_id" => (string) $trasaction["seller_id"], "promotion_id" => "");
        } else if ($trasaction["record_type_id"] == 5) {
            $type_result["record_label"] = "SPECIALOFFER";
            $type_result["seller_type_label"] = "BUSINESSPROFILE";
            $em = $this->_em;
            $trs = $em->getRepository("SixthContinentConnectBundle:Sixthcontinentconnecttransaction")
                    ->getSinglePurchaseOffer($trasaction["buyer_id"] , $trasaction["id"]  );
            if(isset($trs["cpt_description"])){
                $type_result["name"] = $trs["cpt_description"];
            }else{
                $type_result["name"] = "Tamoil Voucher";
            }
            $type_result["redirect_id"] = array("seller_id" => "50916", "promotion_id" => "5");
        } else {
            $type_result["record_label"] = "PAYINSHOP";
            $type_result["seller_type_label"] = "SHOP";
            $type_result["name"] = $trasaction["business_name"];
            $type_result["redirect_id"] = array("seller_id" => (string) $trasaction["seller_id"], "promotion_id" => "");
        }
        return $type_result;
    }

    /**
     * Create an array with days
     * 
     * @param int $offset
     * @param int $number_of_days
     * @return array
     */
    function createArrayNumberOfDays($offset, $number_of_days) {
        $the_date = array();
        for ($i = $offset; $i < $offset + $number_of_days; $i++) {
            $timestamp = time();
            $tm = 86400 * $i; // 60 * 60 * 24 = 86400 = 1 day in seconds
            $tm = $timestamp - $tm;

            $the_date[] = date("d-m-Y", $tm);
        }
        return $the_date;
    }

    /**
     * IT gives the filter in time stamp based on start (n.of days ago)
     * and $number_of_days (range of dayse)
     * 
     * @param int $offset
     * @param int $number_of_days
     * @return array
     */
    public function getStartEndTime($offset = 0, $number_of_days = 1, $date = null) {

        $start_day = $offset + $number_of_days - 1;
        $end_day = $offset;
        $data["start_time"] = strtotime("-$start_day day midnight");
        $data["end_time"] = strtotime("-$end_day day midnight") + 86399; // 86399 = (60 * 60 * 24) -1  = 86400 = 1 day in seconds -1

        return $data;
    }

    /**
     * It gives start date and end date 
     * Start date at 00:00
     * End Date at 23:59 
     * 
     * @param string $date_start
     * @param string $date_end
     * @return array
     */
    public function getStartEndTimeBaseOnDate($date_start, $date_end = null) {
        $time_stamp_start = strtotime($date_start);
        $time_stamp_end = ($date_end != null) ? strtotime($date_end) : $time_stamp_start;

        $data["start_time"] = $time_stamp_start;
        $data["end_time"] = $time_stamp_end + 86399; // 86399 = (60 * 60 * 24) -1  = 86400 = 1 day in seconds -1

        return $data;
    }

    /**
     * 
     * @param type $array_ci
     */
    public function groupCiDetail($array_ci, EntityManager $em, $currency_simble, $currency_lable, $give_sum = false) {
        $repo_trs = $em->getRepository("TransactionSystemBundle:Transaction");
        $ci_cashback = $array_ci["ci_cashback"];
        $ci_citizen_affiliated = $array_ci["ci_citizen_affiliated"];
        $ci_shop_affiliated = $array_ci["ci_shop_affiliated"];
        $ci_all_nation = $array_ci["ci_all_nation"];
        //Add the parameter date
        $amount_cash_back = 0;
        $amount_affiliated_citizen = 0;
        $amount_affiliated_shop = 0;
        $amount_all_nation = 0;
        $amount_all_connections = 0;
        $sum = 0;
        foreach ($ci_cashback as $key => $value) {
            if (isset($ci_cashback[$key]["ci_cashback_share"]) && $ci_cashback[$key]["ci_cashback_share"] > 0) {
                $amount_cash_back += $ci_cashback[$key]["ci_cashback_share"];
                $sum += $ci_cashback[$key]["ci_cashback_share"];
            }
        }
        foreach ($ci_citizen_affiliated as $key => $value) {
            if (isset($ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"]) && $ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"] > 0) {
                $amount_affiliated_citizen += $ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"];
                $sum += $ci_citizen_affiliated[$key]["ci_citizen_affiliated_share"];
            }
        }
        foreach ($ci_all_nation as $key => $value) {
            if (isset($ci_all_nation[$key]["ci_all_share"]) && $ci_all_nation[$key]["ci_all_share"] > 0) {
                $amount_all_nation += $ci_all_nation[$key]["ci_all_share"];
                $sum += $ci_all_nation[$key]["ci_all_share"];
            }
        }
        foreach ($ci_shop_affiliated as $key => $value) {
            if (isset($ci_shop_affiliated[$key]["ci_shop_affiliated_share"]) && $ci_shop_affiliated[$key]["ci_shop_affiliated_share"] > 0) {
                $amount_affiliated_shop += $ci_shop_affiliated[$key]["ci_shop_affiliated_share"];
                $param["record_id"] = $ci_shop_affiliated[$key]["sixc_id"];
                $trs_detail = $repo_trs->getTransactionHistory($param);
                $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable,"value"=>$ci_shop_affiliated[$key]["ci_shop_affiliated_share"] , "amount" => " + " . (string) number_format($ci_shop_affiliated[$key]["ci_shop_affiliated_share"] / 100, 2, '.', ''), "description" => "Negozio affiliato " . $trs_detail[0]["business_name"], "type" => "SHOPAFFILIATED");
                $sum += $ci_shop_affiliated[$key]["ci_shop_affiliated_share"];
            }
        }

        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable,"value"=>$amount_cash_back , "amount" => " + " . (string) number_format($amount_cash_back / 100, 2, '.', ''), "description" => "Cashback", "type" => "CASHBACK");
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "value"=>$amount_affiliated_citizen ,"amount" => " + " . (string) number_format($amount_affiliated_citizen / 100, 2, '.', ''), "description" => "Cittadini Affiliati", "type" => "CITIZENAFFILIATED");
        if ($amount_affiliated_shop == 0) {
            $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "value"=>$amount_affiliated_shop ,"amount" => " + " . (string) number_format($amount_affiliated_shop / 100, 2, '.', ''), "description" => "Negozi affiliati", "type" => "SHOPAFFILIATED");
        }
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable,"value"=>$amount_all_nation , "amount" => " + " . (string) number_format($amount_all_nation / 100, 2, '.', ''), "description" => "Cittadini nel Mondo", "type" => "ALLNATION");
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "value"=>$amount_all_connections ,"amount" => " + " . (string) number_format($amount_all_connections / 100, 2, '.', ''), "description" => "Amici , Followers", "type" => "CONNECTIONS");

        if ($give_sum) {
            $result = $sum;
        }
        return $result;
    }

    /**
     * 
     * @param type $array_ci
     */
    public function groupTransactionDetail($trs_array, $currency_simble, $currency_lable) {
        $init_price = $trs_array[0]["init_price"];
        $cashback_amount = $trs_array[0]["cashback_amount"];
        $red_amount = $trs_array[0]["cit_amount"] + $trs_array[0]["conn_amount"] + $trs_array[0]["shop_amount"] + $trs_array[0]["all_amount"];
        $redistribution_status = $trs_array[0]["redistribution_status"]; //redistribution status
        $price = $trs_array[0]["price"];
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "amount" => " " . (string) number_format($init_price / 100, 2, '.', ''), "description" => "Costo iniziale", "type" => "INITPRICE", "curreny");
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "amount" => "- " . (string) number_format($price / 100, 2, '.', ''), "description" => "Pagato", "type" => "CASH");
        $cashback_amount = (string) number_format($cashback_amount / 100, 2, '.', '');
        $red_amount = (string) number_format($red_amount / 100, 2, '.', '');
        $description_cash_back = ( $redistribution_status > 0 ) ? "Cashback" : "Cashback non ancora ottenuto";
        $description_red = ( $redistribution_status > 0 ) ? "Ridistribuito" : "Ridistribuzione non ancora avvenuta";
        $amount_cash_back = ( $redistribution_status > 0 ) ? "+ " . $cashback_amount : $cashback_amount;
        $amount_red = ( $redistribution_status > 0 ) ? "+ " . $red_amount : $red_amount;
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "amount" => $amount_cash_back, "description" => $description_cash_back, "type" => "CASHBACK");
        $result[] = array("currency_simble" => $currency_simble, "currency_label" => $currency_lable, "amount" => $amount_red, "description" => $description_red, "type" => "REDISTRIBUTION");

        return $result;
    }

    public function getUserWithFullWallet($limit, $skip, $start_date = null, $end_date = null) {
        $range = $this->getStartEndTimeBaseOnDate($start_date, $end_date);
        $param["start_time"] = $range["start_time"];
        $param["end_time"] = $range["end_time"];
        $em = $this->_em;
        $repo_wallet = $em->getRepository("WalletBundle:WalletCitizen");
        $wallets = $repo_wallet->getWalletWithCi($param, $skip, $limit);
        $currency_simble = $currency_label = "null";
        $records = array();
        foreach ($wallets as $key => $value) {
            $ci_value = 0 ;
            if (!isset($wallets[$key]["buyer_id"])) {
                break;
            }
            $param["buyer_id"] = $wallets[$key]["buyer_id"];
            $ci_array = $repo_wallet->getCiGainedByDay($param);
            $ci_value = $this->groupCiDetail($ci_array, $em, $currency_simble, $currency_label , true);

            $records[] = array("buyer_id" => $param["buyer_id"], "amount"=> $ci_value ,"citizen_income" =>  (string) number_format($ci_value / 100, 2, '.', ''));
        }
        return $records;
    }
    
    public function getPages( $offset, $number_of_days  , $data) {
        $buyer_id = $data["buyer_id"];
        $wcr = $this->_em->getRepository("WalletBundle:WalletCitizen");
        $results_model = $wcr->getWalletData($buyer_id);
        $wallet_model = $results_model[0];
        $start_date = $wallet_model->getTimeCreate();
        $time_end = time();
        $count_days =  ceil(($time_end - $start_date)/(60 * 60 * 24));
        $return["pages"] = ceil($count_days/$number_of_days);
        $return["hasNext"]  = (($count_days - $number_of_days)  > $offset )?true:false;
        return $return;
     
    }

}
