<?php
namespace Payment\PaymentProcessBundle\Factory;
use Payment\PaymentProcessBundle\Factory\ICredit;
use Payment\PaymentProcessBundle\Services\TransactionManagerService;
/**
 * class for momosy card.
 */
class MomosyCard implements ICredit {
    
    /**
     * Apply credit
     * @param \Payment\PaymentProcessBundle\Services\TransactionManagerService $transaction_manager_object
     */
    public function applyCredit(TransactionManagerService $transaction_manager_object)
    {
        //get transaction object 
        $coupons = $transaction_manager_object->coupons;
        $premimum_position = $transaction_manager_object->premimum;
        $gift_card = $transaction_manager_object->gift_card;
        $momosy_card = $transaction_manager_object->momosy_cards;
        $total_citizen_income = $transaction_manager_object->total_citizen_income;
        $amount = $transaction_manager_object->amount;
        $balance_amount = $transaction_manager_object->balance_amount;
        $used_coupons   = $transaction_manager_object->used_coupons;  
        
        //update the balance amount and used_amount
        if ($balance_amount > $momosy_card) {
            $used_momosy_cards   = $momosy_card; 
            $balance_amount = $balance_amount - $momosy_card;
        } else {
            $used_momosy_cards   = $balance_amount;
            $balance_amount  = 0;
        }
        
        //update transaction manager object
        $transaction_manager_object->balance_amount = $balance_amount;
        $transaction_manager_object->used_momosy_cards = $used_momosy_cards;
    }
}

