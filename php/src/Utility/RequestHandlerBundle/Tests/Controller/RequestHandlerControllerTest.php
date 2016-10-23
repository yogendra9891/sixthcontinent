<?php

namespace Utility\RequestHandlerBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;

/**
 * class for check for the test cases for request handling class
 */
class RequestHandlerControllerTest extends WebTestCase
{
    protected $web_uri = 'localhost/sixthcontinent_symfony/php/web/';
    
    public function indexAction($name)
    {
        return $this->render('UtilityRequestHandlerBundle:Default:index.html.twig', array('name' => $name));
    }
    
   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    /**
     * Success case.
     * response code 101
     * URL: phpunit -c app/ --filter="testSaverequestsuccess" src/Utility/RequestHandlerBundle/Tests/Controller/RequestHandlerControllerTest.php
     */
    public function testSaverequestsuccess() {
        $reques_obj = '{"reqObj":{"response_code":"200", "page_name":"", "action_name":"editprofile","request_object":"",'
                      . '"response_object":"", "request_object_type":"", "response_object_type":"", "header_str":""}}';
        $baseUrl     = $this->getContainer()->getParameter('symfony_base_url');
        $action      = $baseUrl.'webapi/saverequest';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * Error case.
     * response code 1029 (FAILURE)
     * URL: phpunit -c app/ --filter="testSaverequestfailure" src/Utility/RequestHandlerBundle/Tests/Controller/RequestHandlerControllerTest.php
     */
    public function testSaverequestfailure() {
        $reques_obj = '{"reqObj":{"response_code":"", "page_name":"", "action_name":"","request_object":"",'
                     . '"response_object":"", "request_object_type":"", "response_object_type":"", "header_str":"", "header_str1":"231sdad"}}';
        $baseUrl     = $this->getContainer()->getParameter('symfony_base_url');
        $action      = $baseUrl.'webapi/saverequest';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1029, $response->code);
    }
    
    /**
     * get records Success case.
     * response code 101
     * URL: phpunit -c app/ --filter="testgetrequestrecordssuccess" src/Utility/RequestHandlerBundle/Tests/Controller/RequestHandlerControllerTest.php
     */
    public function testgetrequestrecordssuccess() {
        $reques_obj = '{"reqObj":{}}';
        $baseUrl     = $this->getContainer()->getParameter('symfony_base_url');
        $action      = $baseUrl.'webapi/getrequestrecords';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
}
