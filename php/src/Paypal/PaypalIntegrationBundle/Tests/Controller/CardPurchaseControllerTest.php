<?php
namespace Paypal\PaypalIntegrationBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use SixthContinent\SixthContinentConnectBundle\Model\SixthcontinentConnectConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

class CardPurchaseControllerTest extends WebTestCase {
    
    /**
     * test case for checking when shop comes under primary reciever pays the paypal transaction fee
     * response code 101
     * URL: phpunit -c app/ --filter="testChainedPaymentShopIdInPrimaryReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testChainedPaymentShopIdInPrimaryReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('chained_payment_fee_payer');
        $shop_id = $this->getContainer()->getParameter('chained_payment_primary_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('primary_reciever');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop comes under secondary reciever pays the paypal transaction fee
     * response code 101
     * URL: phpunit -c app/ --filter="testChainedPaymentShopIdInSecondaryReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testChainedPaymentShopIdInSecondaryReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('chained_payment_fee_payer');
        $shop_id = $this->getContainer()->getParameter('chained_payment_secondary_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('secondaryonly');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop comes under each reciever pays the paypal transaction fee
     * response code 101
     * URL: phpunit -c app/ --filter="testChainedPaymentShopIdInEachReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testChainedPaymentShopIdInEachReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('chained_payment_fee_payer');
        $shop_id = $this->getContainer()->getParameter('chained_payment_eachreciever_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('eachreciever');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    
    /**
     * test case for checking when shop comes under sender pays the paypal transaction fee
     * response code 101
     * URL: phpunit -c app/ --filter="testChainedPaymentShopIdInSender" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testChainedPaymentShopIdInSender()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('chained_payment_fee_payer');
        $shop_id = $this->getContainer()->getParameter('chained_payment_sender_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('sender');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    
    /**
     * test case for checking when shop not comes under any of the paypal transaction fee
     * response code 101
     * URL: phpunit -c app/ --filter="testChainedPaymentShopIdInNoCriteria" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testChainedPaymentShopIdInNoCriteria()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('chained_payment_fee_payer');
        $shop_id = $this->getContainer()->getParameter('chained_payment_non_criteria_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('default_payer');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    
    /**
     * test case for checking when shop comes under primary reciever pays the paypal transaction fee for Ci return
     * response code 101
     * URL: phpunit -c app/ --filter="testCIReturnPaymentShopIdInPrimaryReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testCIReturnPaymentShopIdInPrimaryReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('ci_return_fee_payer');
        $shop_id = $this->getContainer()->getParameter('ci_return_primary_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('primary_reciever');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop comes under secondary reciever pays the paypal transaction fee for Ci return
     * response code 101
     * URL: phpunit -c app/ --filter="testCIReturnPaymentShopIdInSecondaryReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testCIReturnPaymentShopIdInSecondaryReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('ci_return_fee_payer');
        $shop_id = $this->getContainer()->getParameter('ci_return_secondary_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('secondaryonly');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop comes under each reciever pays the paypal transaction fee for Ci return
     * response code 101
     * URL: phpunit -c app/ --filter="testCIReturnPaymentShopIdInEachReciever" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testCIReturnPaymentShopIdInEachReciever()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('ci_return_fee_payer');
        $shop_id = $this->getContainer()->getParameter('ci_return_eachreciever_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('eachreciever');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop comes under sander pays the paypal transaction fee for Ci return
     * response code 101
     * URL: phpunit -c app/ --filter="testCIReturnPaymentShopIdInSender" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testCIReturnPaymentShopIdInSender()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('ci_return_fee_payer');
        $shop_id = $this->getContainer()->getParameter('ci_return_sender_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('sender');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
     * test case for checking when shop doen not comes under any criteria for the paypal transaction fee for Ci return
     * response code 101
     * URL: phpunit -c app/ --filter="testCIReturnPaymentShopIdUnderNoCriteria" src/Paypal/PaypalIntegrationBundle/Tests/Controller/CardPurchaseControllerTest.php
     */
    public function testCIReturnPaymentShopIdUnderNoCriteria()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $type = $this->getContainer()->getParameter('ci_return_fee_payer');
        $shop_id = $this->getContainer()->getParameter('ci_return_non_criteria_shop_id');
        $item_type = $this->getContainer()->getParameter('item_type_shop');
        $paypal_service = $this->getContainer()->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $expected_fee_payer = $this->getContainer()->getParameter('default_payer');
        
        $this->assertEquals($expected_fee_payer,$fee_payer);
    }
    
    /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
}
