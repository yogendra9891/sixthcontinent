<?php

namespace Dashboard\DashboardManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
class PostControllerTest extends WebTestCase
{
    /**
     * Dashboard post remove test case..
     */
     public function testPostRemovedashboardpostsAction() {
        $data = array(
            'user_id'=>1,
            'post_id'=>''
        );
        $data = '{"reqObj":{"user_id":"1","post_id":"22"}}';
        $client = static::createClient();
         
        $client->request('POST',
                '/api/removedashboardposts?access_token=YmI2NWY0ZTNjYzgxZjhmNmQ2M2Q4YjJjYjdiNTFjYWNmN2Y3NjY3Zjk0ZmI4YTdmYTdmZDQ5ZjJkZmZiOWY5Mg',
                array(), array(), array(),
                $data
                );
        $response = json_decode($client->getResponse()->getContent());
        //$this->assertSame(201, $response);
        
        $this->assertEquals(100, $response->code);
     }
     
     /**
      * dashboard post edit test case..
      */
     public function testPostDashboardeditpostsAction() {

        $data = '{"reqObj":{"user_id":"1","post_id":"540807a5e552933808000029", "title":"test case run", "description":"description is here","link_type":"0", "to_id":"1", "post_type":"0"}}';
        $client = static::createClient();
        
        $path = __DIR__ . "/../../../../../web/";
        $filename = 'email.jpg';
        $mimeType = 'image/jpeg';
 
        $file = new UploadedFile (
            $path . $filename, 
            $filename,
            $mimeType 
        );
        
        $client->request('POST',
                '/api/dashboardeditposts?access_token=YmI2NWY0ZTNjYzgxZjhmNmQ2M2Q4YjJjYjdiNTFjYWNmN2Y3NjY3Zjk0ZmI4YTdmYTdmZDQ5ZjJkZmZiOWY5Mg',
                array(), array('postfile[]'=>$file), array(),
                $data
                );
        $response = json_decode($client->getResponse()->getContent());
        //$this->assertSame(201, $response);
        $this->assertEquals(100, $response->code);
     }
     
   /*
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingToId" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingToId()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            "post_type" => "1",
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(1001,$service_resp['code']);
    }
    
    /**
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingPosttype" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingPosttype()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'to_id'   => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(1001,$service_resp['code']);
    }
    
    /**
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingPosttype" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingInvalidShareType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'to_id'   => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            'post_type' => '1',
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "share_type" => "SPONG",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(1129,$service_resp['code']);
    }
    
    /**
     * test case for checking valid object type
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingInvalidObjectType" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingInvalidObjectType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'to_id'   => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            'post_type' => '1',
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club123",
            "object_id" => "2",
            "share_type" => "external_share",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(1130,$service_resp['code']);
    }
    
    
    /**
     * test case for checking valid Privacy setting
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingInvalidObjectType" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingInvalidPrivacySetting()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'to_id'   => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            'post_type' => '1',
            "media_id" => array(),
            "privacy_setting" => 5,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "share_type" => "external_share",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(153,$service_resp['code']);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testShareitemsMissingInvalidObjectType" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testShareitemsMissingInvalidUserId()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/shareitems?access_token='.$access_token;
        $data = array(
            'user_id' => 'asdf',
            'to_id'   => 'asdf',
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            'post_type' => '1',
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "share_type" => "external_share",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(100,$service_resp['code']);
    }
    
    /**
     * test case for checking club share
     * response code 101
     * URL: phpunit -c app/ --filter="testClubShare" src/Dashboard/DashboardManagerBundle/Tests/Controller/PostControllerTest.php
     */
    public function testClubShare()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $data = array(
            'user_id' => $user_id,
            'to_id'   => $user_id,
            'title'   => "Not in use on frontend",
            'description'   => 'testing',
            'youtube_url'   => "",
            'link_type' => 1,
            'post_id' => "",
            'post_type' => '1',
            "media_id" => array(),
            "privacy_setting" => 3,
            "tagged_friends" => "1495",
            "object_type" => "club",
            "object_id" => "2",
            "share_type" => "external_share",
            "content_share" => array('link' => "abc",'images' => array(),'description' => 'desc','title' => "hello all"),
        );
    }
    
    /**
     *  function for making the final request object
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
