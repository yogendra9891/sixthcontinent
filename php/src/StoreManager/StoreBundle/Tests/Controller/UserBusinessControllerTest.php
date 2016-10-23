<?php

namespace StoreManager\StoreBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;

class UserBusinessControllerTest extends WebTestCase {
    
    public function testAddBusinessKeywordsIsJson(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/addbusinesskeywords?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response, true);
        $this->assertInternalType('array', $response, 'Invalid JSON');
        if(isset($response['error']['code'])){
            $this->assertTrue(!isset($response['error']['code']), $response['error']['message']);
        }
    }
    
    /**
     * @depends testAddBusinessKeywordsIsJson
     */
    public function testAddBusinessKeywordsMissedUserId(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/addbusinesskeywords?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"keyword":"test keyword"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response);
        $this->assertTrue(preg_match('/(\_PARAMETER\_USER\_ID)$/', $response->message) ? true : false, 'Expect: User Id Missing');
    }
    
    /**
     * @depends testAddBusinessKeywordsIsJson
     */
    public function testAddBusinessKeywordsMissedKeyword(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/addbusinesskeywords?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24276"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response);
        $this->assertTrue(preg_match('/(\_PARAMETER\_KEYWORD)$/', $response->message) ? true : false, 'Expect: Keyword Missing');
    }
    
    /**
     * @depends testAddBusinessKeywordsIsJson
     */
    public function testAddBusinessKeywordsSuccess(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/addbusinesskeywords?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24276","keyword":"test keyword"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}
