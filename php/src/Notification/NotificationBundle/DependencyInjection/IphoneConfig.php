<?php

namespace Notification\NotificationBundle\DependencyInjection;

/**
 * This is the class that define the Iphone configuration
 *
 * To learn more see {@link http://www.appgamekit.com/documentation/guides/push_notifications_iphone.htm}
 */

class IphoneConfig
{
    /**
     * {@inheritDoc}
     */
	// we have to change this(gateway url) according to the enviornment.
	//sandbox url ::     ssl://gateway.sandbox.push.apple.com:2195
	//Production url ::  ssl://gateway.push.apple.com:2195
	public $gateway_url = "ssl://gateway.sandbox.push.apple.com:2195";
        public $prod_gateway_url = "ssl://gateway.push.apple.com:2195";
	//pem file should be same according to @NManagerNotificationBundle/DependencyInjection/Certificates.pem
//	public $pem_file = 'Certificates.pem';
        public $shop_pem_file = 'Certificates_Shoper.pem';
//        public $pem_file_prod = 'Certificates_citizen_prod.pem';
        public $shop_pem_file_prod = 'apns_prod_shop.pem';
	public $badge = "0";
	public $sound = "default";
        public $pem_file = 'pushcert.pem';
        public $pem_file_prod = 'pushcert.pem';
}
