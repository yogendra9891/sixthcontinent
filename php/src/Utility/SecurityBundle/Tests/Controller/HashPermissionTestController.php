<?php

namespace Utility\SecurityBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;



class HashPermissionTestController extends WebTestCase
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
     * test case in case access token is missing
     * response code 1037
     * URL: phpunit -c app/ --filter="testaccesstokenmissing" src/Utility/SecurityBundle/Tests/Controller/HashPermissionTestController.php
     */
    public function testaccesstokenmissing()
    {
        $reques_obj = '{"reqObj":{"user_id":24018,"profile_type":4}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/viewmultiprofiles';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(1037, $response->code);
    }
}
