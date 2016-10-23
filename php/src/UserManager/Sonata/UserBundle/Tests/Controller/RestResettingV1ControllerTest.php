<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RestResettingV1ControllerTest extends WebTestCase
{
   protected $web_uri = 'localhost/sixthcontinent_symfony/php/web/';

   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    /**
     * Forget password success case
     * response code 101
     * URL: phpunit -c app/ --filter="testforgetPasswordsuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testforgetPasswordsuccess()
    {
        $reques_obj = '{"reqObj":{"username":"ankit.jain@daffodilsw.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/forgetpassword';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
 
    /**
     * Forget password invalid user name case
     * response code 101
     * URL: phpunit -c app/ --filter="testforgetPasswordinvalidusername" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testforgetPasswordinvalidusername()
    {
        $reques_obj = '{"reqObj":{"username":"ankit.jain@daffodilsw1111.com"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/forgetpassword';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1030, $response->code);
    }
    
    /**
     * Forget password user is not active case
     * response code 101
     * URL: phpunit -c app/ --filter="testforgetPasswordInactiveUser" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testforgetPasswordInactiveUser()
    {
        $reques_obj = '{"reqObj":{"username":"ankit.jain@daffodilsw.com"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/forgetpassword';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1003, $response->code);
    }
    
    /**
     * Forget password user is request in 24 hours again case
     * response code 101
     * URL: phpunit -c app/ --filter="testforgetPasswordRequestAgainUser" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testforgetPasswordRequestAgainUser()
    {
        $reques_obj = '{"reqObj":{"username":"ankit.jain@daffodilsw.com"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/forgetpassword';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1031, $response->code);
    }
    
    
    /**
     * Reset Password success case
     * response code 101
     * URL: phpunit -c app/ --filter="testresetsuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testresetsuccess()
    {
        $reques_obj = '{"reqObj":{"token":"oirouVLnB857d7UWmG29s7aLpncGN-MS0g0_qQYHONw","password":"123456"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/reset';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * Reset Password inavalid password case
     * response code 101
     * URL: phpunit -c app/ --filter="testresetinvalidpassword" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testresetinvalidpassword()
    {
        $reques_obj = '{"reqObj":{"token":"testy1","password":""}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/reset';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1036, $response->code);
    }
    
    /**
     * Reset Password inavalid token case
     * response code 101
     * URL: phpunit -c app/ --filter="testresetinvalidtoken" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testresetinvalidtoken()
    {
        $reques_obj = '{"reqObj":{"token":"dasd","password":"sdfdsfdd"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/reset';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1037, $response->code);
    }
    
    /**
     * Reset Password account is not active case
     * response code 101
     * URL: phpunit -c app/ --filter="testresetaccountinactive" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testresetaccountinactive()
    {
        $reques_obj = '{"reqObj":{"token":"oirouVLnB857d7UWmG29s7aLpncGN-MS0g0_qQYHONw","password":"sdfdsfdd"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/reset';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1003, $response->code);
    }
    
    /**
     * Reset Password account is not exists for this token case
     * response code 101
     * URL: phpunit -c app/ --filter="testresettokennotbelonguser" src/UserManager/Sonata/UserBundle/Tests/Controller/RestResettingV1ControllerTest.php
     */
    public function testresettokennotbelonguser()
    {
        $reques_obj = '{"reqObj":{"token":"testy1","password":"sdfdsfdd"}}';
        $baseUrl    = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/reset';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1038, $response->code);
    }
}
