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
 * Controller managing the create client of the user
 *
 * @author Abhishek Gupta <abhishek.gupta@daffodilsw.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class CreateClientV1ControllerTest extends WebTestCase
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
     * Create Client success case
     * response code 101
     * URL: phpunit -c app/ --filter="testcreateclientsuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/CreateClientV1ControllerTest.php
     */
    public function testcreateclientsuccess()
    {
        $reques_obj = '{"reqObj":{"redirect_url":"gh.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/createclient';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }

    /**
     * Create Client parameter miss case
     * response code 1001
     * URL: phpunit -c app/ --filter="testcreateclientparametermissed" src/UserManager/Sonata/UserBundle/Tests/Controller/CreateClientV1ControllerTest.php
     */
    public function testcreateclientparametermissed()
    {
        $reques_obj = '{"reqObj":{"redirect_url_missed":"gh.com"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/createclient';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(1001, $response->code);
    }
}
