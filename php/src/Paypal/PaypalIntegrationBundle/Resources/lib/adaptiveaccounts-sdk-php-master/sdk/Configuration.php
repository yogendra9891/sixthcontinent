<?php 
use Notification\NotificationBundle\NManagerNotificationBundle;

class Configuration
{
	// For a full list of configuration parameters refer in wiki page (https://github.com/paypal/sdk-core-php/wiki/Configuring-the-SDK)
	public static function getConfig()
	{
                $container = NManagerNotificationBundle::getContainer();
                $paypal_mode = $container->getParameter('paypal_mode');
                if($paypal_mode == 'sandbox') {
                    $mode = 'sandbox';
                } else {
                    $mode = 'live';
                }
		$config = array(
				// values: 'sandbox' for testing
				//		   'live' for production
				"mode" => $mode
	
				// These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
				// "http.ConnectionTimeOut" => "5000",
				// "http.Retry" => "2"
		);
		return $config;
	}
	
		// Creates a configuration array containing credentials and other required configuration parameters.
	public static function getAcctAndConfig($paypal_acct_username,$paypal_acct_password,$paypal_acct_signature,$paypal_acct_appid,$paypal_acct_email_address)
	{
		$config = array(
				// Signature Credential
				"acct1.UserName" => $paypal_acct_username,
				"acct1.Password" => $paypal_acct_password,
				"acct1.Signature" => $paypal_acct_signature,
				"acct1.AppId" => $paypal_acct_appid,
				
				// Sample Certificate Credential
				// "acct1.UserName" => "certuser_biz_api1.paypal.com",
				// "acct1.Password" => "D6JNKKULHN3G5B8A",
				// Certificate path relative to config folder or absolute path in file system
				// "acct1.CertPath" => "cert_key.pem",
				// "acct1.AppId" => "APP-80W284485P519543T"
		
				// Sandbox Email Address
				//"service.SandboxEmailAddress" => $paypal_acct_email_address
				);
		
		return array_merge($config, self::getConfig());
	}

}