<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;

/**
 * user follow test cases
 */
class UserFollowV1ControllerTest extends WebTestCase {
    
   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    /**
     * User Follow success case
     * response code 101
     * URL: phpunit -c app/ --filter="testfollowusersuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserFollowV1ControllerTest.php
     */
    public function testfollowusersuccess()
    {
        $reques_obj = '{"reqObj":{"sender_id":"70001", "to_id":"70000"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/v1/followusers?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * User Follow success case
     * response code 101
     * URL: phpunit -c app/ --filter="testunfollowusersuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserFollowV1ControllerTest.php
     */
    public function testunfollowusersuccess()
    {
        $reques_obj = '{"reqObj":{"user_id":"70000", "friend_id":"70001"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/v1/unfollowusers?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }
    
    
}