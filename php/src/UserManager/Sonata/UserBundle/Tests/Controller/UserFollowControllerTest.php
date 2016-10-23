<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;

/**
 * user follow test cases
 */
class UserFollowControllerTest extends WebTestCase {
    
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
     * URL: phpunit -c app/ --filter="testfollowusersuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserFollowControllerTest.php
     */
    public function testfollowusersuccess()
    {
        $reques_obj = '{"reqObj":{"sender_id":"70000", "to_id":"70001"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/followusers?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * User Follow success case
     * response code 101
     * URL: phpunit -c app/ --filter="testunfollowusersuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserFollowControllerTest.php
     */
    public function testunfollowusersuccess()
    {
        $reques_obj = '{"reqObj":{"user_id":"70002", "friend_id":"70000"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/unfollowusers?access_token=MWQ0NmJiNjAyNjBhMmQ2NjZhMWEwOGNkOWVmODU1ZWFlNWRmMDlmMTg3ZDM3NmZhZDI0ODcyNTcxZTc2ZjM3OA';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * User Follow success case check from applane
     * response code 101
     * URL: phpunit -c app/ --filter="testfollowuserfromapplanesuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserFollowControllerTest.php
     */
    public function testfollowuserfromapplanesuccess()
    {
        $user_id      = 70000;
        $follwer_id   = 70003;
        $result       = 0;
        $applane_controller = new ApplaneIntegrationControllerTest();
        $applane_resp = $applane_controller->getUserInfoFromApplane($user_id);
        $decoded_data = json_decode($applane_resp);
        if (isset($decoded_data->response->result[0]->followers)) {
            $followers = $decoded_data->response->result[0]->followers;
            foreach ($followers as $follower) {
                $result = ($follower->_id == $follwer_id ? 1 : 0);
                if ($result) { break; }
            }
        }
        $this->assertEquals(1, $result);
    }
    
}