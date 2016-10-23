<?php
namespace Payment\PaymentProcessBundle\Factory;
use Payment\PaymentProcessBundle\Factory\Coupon;
use Payment\PaymentProcessBundle\Factory\PremiumPosition;
use Payment\PaymentProcessBundle\Factory\GiftCard;
use Payment\PaymentProcessBundle\Factory\MomosyCard;

class CreditFactory{
    
    /**
     * 
     * @param string $type
     * @return \Payment\PaymentProcessBundle\Factory\Coupon
     * @return \Payment\PaymentProcessBundle\Factory\PremiumPosition
     * @return \Payment\PaymentProcessBundle\Factory\GiftCard
     * @return \Payment\PaymentProcessBundle\Factory\MomosyCard
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

