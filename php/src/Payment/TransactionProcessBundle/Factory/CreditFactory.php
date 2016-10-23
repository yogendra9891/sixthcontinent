<?php
namespace Payment\TransactionProcessBundle\Factory;
use Payment\TransactionProcessBundle\Factory\Coupon;
use Payment\TransactionProcessBundle\Factory\PremiumPosition;
use Payment\TransactionProcessBundle\Factory\GiftCard;
use Payment\TransactionProcessBundle\Factory\MomosyCard;

/**
 * class for Credit factory.
 */
class CreditFactory{
    
    /**
     * 
     * @param string $type
     * @return \Payment\TransactionProcessBundle\Factory\Coupon
     * @return \Payment\TransactionProcessBundle\Factory\PremiumPosition
     * @return \Payment\TransactionProcessBundle\Factory\GiftCard
     * @return \Payment\TransactionProcessBundle\Factory\MomosyCard
     */
    static public function get($type)
    {

        $instance = null;
        switch ($type) {
                case 'coupon':
                    //Shots
                    $instance = new Coupon();
                    break;
                case 'premium_position':
                    //Purchase position/Discount position
                    $instance = new PremiumPosition();
                    break;
                case 'gift_card':
                    //Giftcard
                    $instance = new GiftCard();
                    break;
                case 'momosy_card':
                    //Momosy Card
                    $instance = new MomosyCard();
                    break;
		}			
       return $instance;

    }
    
}

