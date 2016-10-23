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
 * Controller managing the accesstoken of the user
 *
 * @author Abhishek Gupta <abhishek.gupta@daffodilsw.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class UserAccesstokenV1ControllerTest extends WebTestCase
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
     * Get access token success case
     * response code 101
     * URL: phpunit -c app/ --filter="testaccesstokensuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenV1ControllerTest.php
     */
    public function testaccesstokensuccess()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3jnvbt8en5kw0og08s8ocw0oo004wwcswwscocooc0cwo0g8ko", "client_secret":"2xqb0ykndkowkscsggsk8kcoo0cko404480k0k4cko04owwswc","grant_type":"client_credentials",
"username":"sunil","password":"123456"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }

    /**
     * Get access token missed parameter case
     * response code 1001
     * URL: phpunit -c app/ --filter="testaccesstokenmissedparameter" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenV1ControllerTest.php
     */
    public function testaccesstokenmissedparameter()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3jnvbt8en5kw0og08s8ocw0oo004wwcswwscocooc0cwo0g8koq", "client_secret":"2xqb0ykndkowkscsggsk8kcoo0cko404480k0k4cko04owwswc","grant_type":"client_credentials",
"username":"sunil","password1":"123456"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(1001, $response->code);
    }
    
    /**
     * Get access token missed parameter case
     * response code 1001
     * URL: phpunit -c app/ --filter="testaccesstokeninvalidrequest" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenV1ControllerTest.php
     */
    public function testaccesstokeninvalidrequest()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3jnvbt8en5kw0og08s8ocw0oo004wwcswwscocooc0cwo0g8ko11", "client_secret":"2xqb0ykndkowkscsggsk8kcoo0cko404480k0k4cko04owwswc","grant_type":"client_credentials",
"username":"sunil","password":"123456"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(1040, $response->code);
    }
    
     /**
     * Get access token disable user case
     * response code 100
     * URL: phpunit -c app/ --filter="testaccesstokendisableduser" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenV1ControllerTest.php
     */
    public function testaccesstokendisableduser()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3ofdwe6u02kgg4ock4os4okc4ss4gckc80ccw000kkc8wo4gsc","client_secret":"4mjnllttzpycgss4og8koc40gk8ocskko8kc4888c08wkc4s8g","grant_type":"password","username":"verify@mailinator.com","password":"e10adc3949ba59abbe56e057f20f883e"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(100, $response->code);
    }
    
    /**
     * Get access token invalid user case i.e.invalid username or password
     * response code 10
     * URL: phpunit -c app/ --filter="testaccesstokendisableduser" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenV1ControllerTest.php
     */
    public function testaccesstokeninvaliduser()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3ofdwe6u02kgg4ock4os4okc4ss4gckc80ccw000kkc8wo4gsc","client_secret":"4mjnllttzpycgss4og8koc40gk8ocskko8kc4888c08wkc4s8g","grant_type":"password","username":"verify@mailinator.com","password":"e10adc3949ba59abbe56e057f20f88 "}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(10, $response->code);
    }
}
