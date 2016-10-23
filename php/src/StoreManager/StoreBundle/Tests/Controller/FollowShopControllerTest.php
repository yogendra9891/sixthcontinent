<?php

namespace StoreManager\StoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;

class FollowShopControllerTest extends WebTestCase
{
    
    /**
     * Test follow shops
     */
    public function testFollowShop(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/followshops?access_token=OTE3OTdkMmIyZGMyNjBmY2FkODQ3OWNhMzExMTZhZjMwMWUyNzM3YmRjZDMxYWI2ODFmZjNiMGFmYzllOWQ0ZQ';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24168","shop_id":"1495"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response, true);
        $this->assertInternalType('array', $response, 'Invalid JSON');
        if(isset($response['error']['code'])){
            $this->assertTrue(!isset($response['error']['code']), $response['error']['message']);
        }
        
        if(isset($response['error_description'])){
            echo $response['error_description']; 
            exit;
        }
        
        $this->assertNotEquals(1001, $response['code'], $response['message']);
        $this->assertEquals(101, $response['code'], $response['message']);
    }
    
    /**
     * Test Unfollow shops
     */
    public function testUnfollowShop(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/unfollowshops?access_token=OTE3OTdkMmIyZGMyNjBmY2FkODQ3OWNhMzExMTZhZjMwMWUyNzM3YmRjZDMxYWI2ODFmZjNiMGFmYzllOWQ0ZQ';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24168","shop_id":"1495"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response, true);
        $this->assertInternalType('array', $response, 'Invalid JSON');
        if(isset($response['error']['code'])){
            $this->assertTrue(!isset($response['error']['code']), $response['error']['message']);
        }
        
        if(isset($response['error_description'])){
            echo $response['error_description']; 
            exit;
        }
        
        $this->assertNotEquals(1001, $response['code'], $response['message']);
        $this->assertEquals(101, $response['code'], $response['message']);
    }
    
    /**
     * Test Unfollow shops
     */
    public function testUserfollowedShop(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/userfollowedshops?access_token=OTE3OTdkMmIyZGMyNjBmY2FkODQ3OWNhMzExMTZhZjMwMWUyNzM3YmRjZDMxYWI2ODFmZjNiMGFmYzllOWQ0ZQ';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24168"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response, true);
        $this->assertInternalType('array', $response, 'Invalid JSON');
        if(isset($response['error']['code'])){
            $this->assertTrue(!isset($response['error']['code']), $response['error']['message']);
        }
        
        if(isset($response['error_description'])){
            echo $response['error_description']; 
            exit;
        }
        
        $this->assertNotEquals(1001, $response['code'], $response['message']);
        $this->assertEquals(101, $response['code'], $response['message']);
    }

    /**
     * Test users following shops
     */
    public function testUserfollowingShops(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/userfollowingshops?access_token=OTE3OTdkMmIyZGMyNjBmY2FkODQ3OWNhMzExMTZhZjMwMWUyNzM3YmRjZDMxYWI2ODFmZjNiMGFmYzllOWQ0ZQ';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"shop_id":"24168"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $response = json_decode($response, true);
        $this->assertInternalType('array', $response, 'Invalid JSON');
        if(isset($response['error']['code'])){
            $this->assertTrue(!isset($response['error']['code']), $response['error']['message']);
        }
        
        if(isset($response['error_description'])){
            echo $response['error_description']; 
            exit;
        }
        
        $this->assertNotEquals(1001, $response['code'], $response['message']);
        $this->assertNotEquals(102, $response['code'], $response['message']);
        $this->assertEquals(101, $response['code'], $response['message']);
    }
    
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}
