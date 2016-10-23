<?php

namespace Paypal\PaypalIntegrationBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Symfony\Component\Console\Input\InputInterface;

class EcommerceProductControllerTest extends WebTestCase
{
    /**
     * test case for invalid shop id
     * response code 1055 
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductInvalidShopId" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductInvalidShopId() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_shop_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"55af6871b96bccee374558f9",
        "citizen_id":'.$user_id.',
        "session_id":'.$user_id.',
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":'.$user_id.',
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":'.$shop_id.',
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"http://localhost/sixthcontinent_angular/#/order_cancel",
        "return_url":"http://localhost/sixthcontinent_angular/#/order_success",
        "product_name":"Prductttt"
        }
        }';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1055, $service_resp['code']);
    }


   /**
     * test case for invalid shop id
     * response code 1105 
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductBlockShopId" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductBlockShopId() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_blocked_shop_id');
        $offer_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_offer_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"'.$offer_id.'",
        "citizen_id":'.$user_id.',
        "session_id":'.$user_id.',
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":'.$user_id.',
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":'.$shop_id.',
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"http://localhost/sixthcontinent_angular/#/order_cancel",
        "return_url":"http://localhost/sixthcontinent_angular/#/order_success",
        "product_name":"Prductttt"
        }
        }';
        
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1105, $service_resp['code']);
    }
    
    
    /**
     * test case for invalid shop id
     * response code 1058 
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductShopPaypalNotExist" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductShopPaypalNotExist() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_shop_id_paypal_not_exist');
        $offer_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_offer_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"'.$offer_id.'",
        "citizen_id":'.$user_id.',
        "session_id":'.$user_id.',
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":'.$user_id.',
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":'.$shop_id.',
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"http://localhost/sixthcontinent_angular/#/order_cancel",
        "return_url":"http://localhost/sixthcontinent_angular/#/order_success",
        "product_name":"Prductttt"
        }
        }';
        
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1058, $service_resp['code']);
    }
    
    /**
     * test case for invalid offer id
     * response code 9 
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductInvalidOfferId" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductInvalidOfferId() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $offer_id = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_offer_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"'.$offer_id.'",
        "citizen_id":'.$user_id.',
        "session_id":'.$user_id.',
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":'.$user_id.',
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":'.$shop_id.',
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"http://localhost/sixthcontinent_angular/#/order_cancel",
        "return_url":"http://localhost/sixthcontinent_angular/#/order_success",
        "product_name":"Prductttt"
        }
        }';
        
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(9, $service_resp['code']);
    }
    
    
     /**
     * test case for Ecommerce Get payment url success
     * response code 101
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductPaypalSuccess" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductPaypalSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $offer_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_offer_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"'.$offer_id.'",
        "citizen_id":"'.$user_id.'",
        "session_id":"'.$user_id.'",
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":"'.$user_id.'",
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":"'.$shop_id.'",
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"http://localhost/sixthcontinent_angular/#/order_cancel",
        "return_url":"http://localhost/sixthcontinent_angular/#/order_success",
        "product_name":"Prductttt"
        }
        }';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(101, $service_resp['code']);
    }
    
    /**
     * test case for Ecommerce Get payment failure from paypal
     * response code 101
     * URL: phpunit -c app/ --filter="testBuyEcommerceProductPaypalRejected" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testBuyEcommerceProductPaypalRejected() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $offer_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_offer_id');
        $serviceUrl = $baseUrl . 'api/buyecommerceproduct?access_token=' . $access_token;
        $request_object = '{
        "reqObj":{
        "offer_id":"'.$offer_id.'",
        "citizen_id":"'.$user_id.'",
        "session_id":"'.$user_id.'",
        "ordr_billing_addrs":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_citizen_id":"'.$user_id.'",
        "ordr_creation":"2015-07-23T12:33:47.549Z",
        "ordr_line_item":[
        {
        "offer_id":{
        "_id":"55af6871b96bccee374558f9"
        },
        "quantity_line_item":"2"
        }
        ],
        "ordr_shipping_addres":{
        "address":"1 - 23 City Road, Southbank VIC 3006, Australia",
        "city":"Test",
        "country":"IT",
        "province":"adasdasdas",
        "region":"Italy",
        "email":"saundaraya.gupta@gmail.com",
        "first_name":"Saundaraya",
        "last_name":"Gupta",
        "tel_num1":"42342344234",
        "tel_num2":"32432432432",
        "zip":"11111"
        },
        "ordr_shop_id":"'.$shop_id.'",
        "ordr_pickastore":false,
        "ordr_shipping_detail":{
        "ordr_shipping_preferences":"558953d1eff642445f130bae",
        "ordr_shipping_costs":21,
        "ordr_free_shipping":false
        },
        "cancel_url":"order_cancel",
        "return_url":"order_success",
        "product_name":"Prductttt"
        }
        }';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1029, $service_resp['code']);
    }
    
    
     /**
     * test case for Ecommerce inavlid transaction type
     * response code 1136
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductInvalidType" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductInvalidType() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_transaction_type');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id'   => $shop_id,
            'transaction_id'   => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1136, $service_resp['code']);
    }
    
     /**
     * test case for Ecommerce inavlid transaction type
     * response code 1060
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductInvalidTransactionId" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductInvalidTransactionId() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id'   => $shop_id,
            'transaction_id'   => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1060, $service_resp['code']);
    }
    
     /**
     * test case for Ecommerce inavlid transaction type
     * response code 1061
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductInvalidTransactionIdForUser" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductInvalidTransactionIdForUser() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_transaction_id_for_login_user');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id'   => $shop_id,
            'transaction_id'   => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1061, $service_resp['code']);
    }
    
    /**
     * test case to check if transaction has alreday confirmed.
     * response code 1062
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductTransactionConfirmed" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductTransactionConfirmed() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_confirmed_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id'   => $shop_id,
            'transaction_id'   => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1062, $service_resp['code']);
    }
    
    /**
     * test case to check if transaction has alreday confirmed.
     * response code 1063
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductTransactionCanceled" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductTransactionCanceled() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_canceled_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id'   => $shop_id,
            'transaction_id'   => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1063, $service_resp['code']);
    }
    
    /**
     * test case to check if transaction has alreday confirmed.
     * response code 1055
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductInavalidShopId" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductInavalidShopId() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_invalid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id' => $shop_id,
            'transaction_id' => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1055, $service_resp['code']);
    }
    
    /**
     * test case to check if transaction has alreday confirmed.
     * response code 101
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductSuccess" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_success');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id' => $shop_id,
            'transaction_id' => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response, true);
        $this->assertEquals(101, $service_resp['code']);
    }
    
    
    /**
     * test case to check if transaction has alreday confirmed.
     * response code 1069
     * URL: phpunit -c app/ --filter="testResponseBuyEcommerceProductReject" src/Paypal/PaypalIntegrationBundle/Tests/Controller/EcommerceProductControllerTest.php
     */
    public function testResponseBuyEcommerceProductReject() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $shop_id = $this->getContainer()->getParameter('buy_ecommerce_product_valid_shop_id');
        $transaction_id = $this->getContainer()->getParameter('buy_ecommerce_product_transaction_id');
        $type = $this->getContainer()->getParameter('buy_ecommerce_product_valid_transaction_type_reject');
        $serviceUrl = $baseUrl . 'api/buyresponseecommerceproduct?access_token=' . $access_token;
        $data = array(
            'session_id' => $user_id,
            'shop_id' => $shop_id,
            'transaction_id' => $transaction_id,
            'type' => $type
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response, true);
        $this->assertEquals(1069, $service_resp['code']);
    }
    
    /**
     * function for making the final request object
     * @param type $data
     * @return type
     */
    private function madeRequestObject($data) {
        
        $final_array = array('reqObj' => $data);
        $request_object = json_encode($final_array);
        return $request_object;
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

