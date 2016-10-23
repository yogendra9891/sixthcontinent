<?php
namespace Payment\TransactionProcessBundle\Factory;
use Payment\TransactionProcessBundle\Factory\ICredit;
use Payment\TransactionProcessBundle\Services\TransactionManagerService;

/**
 * class for gift cards
 */
class GiftCard implements ICredit {
    
     protected $GC_use_percentage = 100;
    /**
     * Apply credit
     * @param \Payment\PaymentProcessBundle\Services\TransactionManagerService $transaction_manager_object
     */
    public function applyCredit(TransactionManagerService $transaction_manager_object)
    {
        //get transaction object 

        $gift_card = $transaction_manager_object->total_citizen_income_available; //this is citizen income 
        $total_citizen_income = $transaction_manager_object->total_citizen_income_available;
        $amount = $transaction_manager_object->amount;
        $gift_card = $this->getUsableGC($amount);
        $balance_amount = $transaction_manager_object->balance_amount;
        $remaining_GC = $transaction_manager_object->remaining_gift_cards;
        //$is_choice  = 1; //default 1 for above minimum, 0 for below maximum
        $is_choice = $transaction_manager_object->gift_card_setting;
        if($remaining_GC > 0){
            if($balance_amount >= $remaining_GC){
                $balance_amount = $balance_amount - $remaining_GC;
                $used_GC = $remaining_GC;
                $balanced_GC = 0;
            }else {
                $balance_amount = $remaining_GC - $balance_amount;
                $used_GC = $balance_amount;
                $balanced_GC = $remaining_GC-$balance_amount;
            }
            $transaction_manager_object->used_remaining_gift_cards = (int)$used_GC;
            $transaction_manager_object->remaining_gift_cards = $balanced_GC;
        }
       
        //update the balance amount and used_amount 55/22
        if ($balance_amount > $gift_card) {
            $amount_CI = $gift_card;
            //if amount is greater than CI then it always be below maximum
            $gift_cards_packets   = $this->getGiftCard($amount_CI, $is_choice, $transaction_manager_object);
            $gift_cards_sum = array_sum($gift_cards_packets);
            
            if($gift_cards_sum > $total_citizen_income){
                 $is_choice = 0;
                 $gift_cards_packets   = $this->getGiftCard($amount_CI, $is_choice, $transaction_manager_object);
                 $gift_cards_sum = array_sum($gift_cards_packets);
            }
            if(!$gift_cards_sum){
                $transaction_manager_object->remaining_gift_cards = 0;
            }
            $used_gift_cards   = $gift_cards_sum;   
            $balance_amount = $balance_amount - $gift_cards_sum; //to be passed to momosy card
        } else {
            $amount_CI = $balance_amount; //20
            //check if max of $amount_CI is less citizen income
            if($is_choice == 1){
                //get max of  $amount_CI
               
                $amount_ci_max = $this->getMinimumMaximumTarget($amount_CI, $is_choice);
               
                if($amount_ci_max > $gift_card){
                //ex: case if is_choice = 1, balance_amount = 45, citizen_income = 43.
                    $is_choice = 0; 
                }
            }
            //CI 40
            $gift_cards_packets   = $this->getGiftCard($amount_CI, $is_choice, $transaction_manager_object);
            $gift_cards_sum = array_sum($gift_cards_packets);
            $used_gift_cards   = $gift_cards_sum;  
            $balance_amount  = 0;
        }
        
        //update transaction manager object
        $transaction_manager_object->balance_amount = $balance_amount;
        $transaction_manager_object->used_gift_cards   = $used_gift_cards;
        $transaction_manager_object->gift_card_packets = $gift_cards_packets;
    }
    
    /**
     * Get gift card
     * @param int $amount
     * @return array
     */
    public function getGiftCard($amount, $is_choice, TransactionManagerService $transaction_manager_object)
    { 
        $target_res = $this->getMinimumMaximumTarget($amount, $is_choice); 
        $packet_arr = $this->getPossiblePacket($target_res);
        $packet_res =  $this->sum_up($packet_arr, $target_res); 
        //get difference if $is_choice = 1, that is upper minimum
        $sum_packets = array_sum($packet_res);
        if($is_choice == 1 && $sum_packets > 0){
            $remaining_gift_cards = $target_res-$amount;
            $transaction_manager_object->remaining_gift_cards = $remaining_gift_cards;
        }
        return $packet_res;
    }
    
    /**
     * 
     * @param type $target
     * @param type $is_choice
     * @return type
     */
    public function getMinimumMaximumTarget($target,$is_choice) {

            $remi =  0 ;            
            if($target%10000000 == 0) {
                return $target;
            }
            if($is_choice == 1) { 
                $remi = $target %10000000;
                return $target+(10000000-$remi);               
            }else {
                $remi = $target %10000000;
                return ($target-$remi);                
            }
    }
    
    /**
     * Get packet array
     * @param type $target
     * @return int
     */
    public function getPossiblePacket($target) {
        $total_packet = array();
        $set = array(100000000,50000000,30000000,20000000);
        
        foreach($set as $set_item) {
            $count = floor($target/$set_item);
            for($i = 1;$i<=$count;$i++) {
                $total_packet[] = $set_item;
            }
        }
        return $total_packet;
    }
    
     /**
     * 
     * @param type $numbers
     * @param type $target
     * @return type
     */
    public function sum_up($numbers, $target) {
        $result = array();
        return $this->sum_up_recursive($numbers, $target, $result);
    }
    
    /**
     * Recursive methods
     * @param int $numbers
     * @param int $target
     * @param int $partial
     * @return array
     */
    public function sum_up_recursive($numbers, $target, $partial) {        
        $s = 0;

        foreach ($partial as $x) {
            $s += $x;
        }

        if ($s == $target) { 
            return $partial;
        }

        if ($s >= $target) { 
            return;
        }

        for ($i = 0; $i < count($numbers); $i++) {
            $remaining = array();
            $n = $numbers[$i];
            for ($j = $i + 1; $j < count($numbers); $j++) {
                $remaining[] = $numbers[$j];
            }
            $partial_rec = $partial;
            $partial_rec[] = $n;
            $res = $this->sum_up_recursive($remaining, $target, $partial_rec);
            if(is_array($res)) {
                return $res;
            }
        }
    }
    
    /**
     * Get Gift card usable amount according to the percentage used.
     * @param int $amount
     * @return int
     */
     public function getUsableGC($amount)
    {
        $GCcoupon_use_percentage = $this->GC_use_percentage;
        $GC = ($amount*$GCcoupon_use_percentage)/100;
        return $GC;
    }
    
}

