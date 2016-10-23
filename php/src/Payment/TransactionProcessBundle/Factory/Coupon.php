<?php
namespace Payment\TransactionProcessBundle\Factory;
use Payment\TransactionProcessBundle\Factory\ICredit;
use Payment\TransactionProcessBundle\Services\TransactionManagerService;

/**
 * Class for coupon
 */
class Coupon implements ICredit{
    
    /**
     * Apply credit
     * @param \Payment\PaymentProcessBundle\Services\TransactionManagerService $transaction_manager_object
     */
    public function applyCredit(TransactionManagerService $transaction_manager_object)
    {
        //get transaction object 
        //set the cards variables by available amount.
        $coupons = $transaction_manager_object->coupons;
        $premimum_position = $transaction_manager_object->premimum;
        $gift_card = $transaction_manager_object->gift_card;
        $momosy_card = $transaction_manager_object->momosy_cards;
        $total_citizen_income = $transaction_manager_object->total_citizen_income;
        $amount = $transaction_manager_object->amount;
        
        //Get balance amount
        if ($amount > $coupons) {
        $balance_amount = $amount-$coupons;
        $used_coupons = $coupons;     
        } else {
           $used_coupons = $amount; 
           $balance_amount  =  0;
           
        }
        
        //update transaction manager object
        $transaction_manager_object->balance_amount = $balance_amount;
        $transaction_manager_object->used_coupons = $used_coupons;
    }
}

