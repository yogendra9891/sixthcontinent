<?php

namespace Paypal\PaypalIntegrationBundle\Model;

interface PaypalConstentInterface {
    
    CONST DEFAULT_CI_PAYER_SHOP = 'EACHRECEIVER';
    
    CONST CHAINED_PAYMENT_FEE_PAYER = 'CHAINED_PAYMENT_FEE_PAYER';
    
    CONST CI_RETURN_FEE_PAYER = 'CI_RETURN_FEE_PAYER';
    
    CONST ITEM_TYPE_SHOP = 'SHOP';
}
