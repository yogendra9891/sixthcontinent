<?php
namespace Payment\TransactionProcessBundle\Factory;
use Payment\TransactionProcessBundle\Factory\ICredit;
use Payment\TransactionProcessBundle\Services\TransactionManagerService;

/**
 * Class for Premimum position
 */
class PremiumPosition implements ICredit {
    
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
        $balance_amount = $transaction_manager_object->balance_amount;
        $shop_open_premimum = $transaction_manager_object->open_shop_premium;
        
        //check for premium position
        if($premimum_position > $shop_open_premimum){
            $premimum_position = $shop_open_premimum; //assign the shop premimum position to the shop
            $transaction_manager_object->premimum = $premimum_position;
        }
        //Get balance amount
        if ($balance_amount > $premimum_position) {
        $balance_amount = $balance_amount-$premimum_position;
        $used_premium_positions = $premimum_position;     
        } else {
           $used_premium_positions = $balance_amount;
           $balance_amount  =  0;    
        }
        
        //update transaction manager object
        $transaction_manager_object->balance_amount = $balance_amount;
        $transaction_manager_object->used_premium_positions = $used_premium_positions;
    }
}

