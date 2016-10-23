<?php
namespace Utility\UniversalNotificationsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;

class NotificationsControllerTest extends WebTestCase{
    private $dm;
    private $em;
    
    public function setUp(){
        parent::setUp();
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }
    
    /**
     * Test case for getting the Accesstoken
     * @return access token of a user
     */
    public function testGetAccessToken() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'webapi/getaccesstoken';
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        
        $data = json_encode(array(
            "reqObj"=>array(
                "client_id"=>$client_id,
                "client_secret"=>$client_secret,
                "grant_type"=>"password",
                "username"=>$user_name,
                "password"=>$password
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(0, is_null($response), 'Invalid Json');
        $this->assertEquals(101, $response->code);
        
        return $response->data->access_token;
    }

    /**
     * Test case for user login
     * @depends testGetAccessToken
     * @return type
     */
    public function testLoginUser($accessToken) {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/logins?access_token='.$accessToken;
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = json_encode(array(
            "reqObj"=>array(
                "username"=>$user_name,
                "password"=>$password
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code);
        
        return (array)$response->data;
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testGetTransactionNotificationsMissedParam($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/get_transaction_notifications?access_token='.$accessToken;
        
        $data = json_encode(array(
            "reqObj"=>array(
                "is_view"=>1,
                'limit_start'=>0,
                'limit_size'=>1
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertNotEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testGetTransactionNotifications($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/get_transaction_notifications?access_token='.$accessToken;
        
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "is_view"=>1,
                'limit_start'=>0,
                'limit_size'=>1
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}
