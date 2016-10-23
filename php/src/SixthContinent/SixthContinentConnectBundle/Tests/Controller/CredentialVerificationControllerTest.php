<?php

namespace SixthContinent\SixthContinentConnectBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;

class CredentialVerificationControllerTest extends WebTestCase
{

   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    /**
     * application check
     * response code 1118
     * URL: phpunit -c app/ --filter="testinvalidapplication" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testinvalidapplication()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "sessionid":"1231sdwd3asae2131", "url":"https://www.sixthcontinent.com/pay?aa=2&param2=23","app_id":"APP-123451",
                        "amount":6189,"description":"3234","currency":"EUR", "transaction_id":"25", "url_post":"http://localhost/sixthcontinent_symfony/php/web/webapi/posturldata",
                        "url_back":"https://www.sixthcontinent.com/pay?d=3=",
                        "type_service":"pay", "language_id":"ITA", "mac":"b965937c7f115806bf165f7f2c30402f16c4608a"          
                        }}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/appconnecttransactioninitiation?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1118, $response->code); //INVALID_APP
    }

    /**
     * application businees account check 
     * response code 1135
     * URL: phpunit -c app/ --filter="testapplicationbusinessaccount" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testapplicationbusinessaccount()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "sessionid":"1231sdwd3asae2131", "url":"https://www.sixthcontinent.com/pay?aa=2&param2=23","app_id":"APP-54321",
                        "amount":6189,"description":"3234","currency":"EUR", "transaction_id":"25", "url_post":"http://localhost/sixthcontinent_symfony/php/web/webapi/posturldata",
                        "url_back":"https://www.sixthcontinent.com/pay?d=3=",
                        "type_service":"pay", "language_id":"ITA", "mac":"b965937c7f115806bf165f7f2c30402f16c4608a"          
                        }}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/appconnecttransactioninitiation?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1135, $response->code); //APPLICATION_BUSINESS_ACCOUNT_NOT_EXISTS
    }
    
    /**
     * application mac not matched
     * response code 1119
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testapplicationmacmatched" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testapplicationmacmatched()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "sessionid":"1231sdwd3asae2131", "url":"https://www.sixthcontinent.com/pay?aa=2&param2=23","app_id":"APP-12345",
                        "amount":6189,"description":"3234","currency":"EUR", "transaction_id":"25", "url_post":"http://localhost/sixthcontinent_symfony/php/web/webapi/posturldata",
                        "url_back":"https://www.sixthcontinent.com/pay?d=3=",
                        "type_service":"pay", "language_id":"ITA", "mac":"b965937c7f115806bf165f7f2c30402f16c4608a1"          
                        }}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/appconnecttransactioninitiation?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1119, $response->code);//MAC_NOT_MATCHED
    }
    
    /**
     * invalid amount (pass amount 0)
     * response code 1120
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testinvalidamount" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testinvalidamount()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "sessionid":"1231sdwd3asae2131", "url":"https://www.sixthcontinent.com/pay?aa=2&param2=23","app_id":"APP-12345",
                        "amount":0,"description":"3234","currency":"EUR", "transaction_id":"25", "url_post":"http://localhost/sixthcontinent_symfony/php/web/webapi/posturldata",
                        "url_back":"https://www.sixthcontinent.com/pay?d=3=",
                        "type_service":"pay", "language_id":"ITA", "mac":"8c73b3f851812ec250ae191e5e963c47eb07dd73"          
                        }}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/appconnecttransactioninitiation?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1120, $response->code);//AMOUNT_NOT_ACCEPTABLE
    }
    
    /**
     * transaction intiated successfully
     * response code 101
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactioninitiated" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactioninitiated()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "sessionid":"1231sdwd3asae2131", "url":"https://www.sixthcontinent.com/pay?aa=2&param2=23","app_id":"APP-12345",
                        "amount":1379,"description":"3234","currency":"EUR", "transaction_id":"25", "url_post":"http://localhost/sixthcontinent_symfony/php/web/webapi/posturldata",
                        "url_back":"https://www.sixthcontinent.com/pay?d=3=",
                        "type_service":"pay", "language_id":"ITA", "mac":"f7908f713afd998906731e71f61e87df99d27202"          
                        }}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/appconnecttransactioninitiation?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);//SUCCESS
    }
    
    //confirm transaction
    /**
     * transaction does not belongs to you
     * response code 1122
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactionnotbelogsyou" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactionnotbelogsyou()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":53, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1122, $response->code);//TRANSACTION_DOES_NOT_BELONGS_TO_YOU
    }
    
    /**
     * transaction does not belongs to you
     * response code 1122
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactioninvalidapp" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactioninvalidapp()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1118, $response->code);//INVALID_APP
    }
    
    /**
     * transaction application paypal account check
     * response code 1134
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactionapplicationpaypalcheck" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactionapplicationpaypalcheck()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1134, $response->code);//APPLICATION_PAYPAL_ACCOUNT_NOT_AVAILABLE
    }
    
    /**
     * transaction ci reserved on transaction system
     * response code 1134
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactioncireseved" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactioncireseved()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1123, $response->code);//CITIZEN_INCOME_IS_NOT_RESERVED_ON_TRANSACTION_SYSTEM
    }
    
    /**
     * transaction not initiated on paypal case
     * response code 1124
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactioninitiatespaypal" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactioninitiatespaypal()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1124, $response->code); //TRANSACTION_NOT_INITIATED_ON_PAYPAL
    }
    
    /**
     * transaction not initiated on paypal case
     * response code 101
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactionconfirmsuccess" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactionconfirmsuccess()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, '
                . '"return_url":"http://www.sixthcontinent.com/#/", "cancel_url":"http://www.sixthcontinent.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/connectconfirmtransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code); //TRANSACTION_NOT_INITIATED_ON_PAYPAL
    }
    
    //response of transaction.
    /**
     * response of the transaction, check the transaction type(success/canceled) 
     * response code 1128
     * right mac:  b965937c7f115806bf165f7f2c30402f16c4608a
     * URL: phpunit -c app/ --filter="testtransactiontypecheck" src/SixthContinent/SixthContinentConnectBundle/Tests/Controller/CredentialVerificationControllerTest.php
     */
    public function testtransactiontypecheck()
    {
        $reques_obj = '{"reqObj":{"user_id":"30038", "connect_transaction_id":52, "type":"success1"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/responseconnecttransaction?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1128, $response->code); //TRANSACTION_TYPE_NOT_ACCEPTABLE
    }
}
