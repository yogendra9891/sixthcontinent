<?php

namespace StoreManager\StoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;

class RestFriendsControllerTest extends WebTestCase {
    
    protected $user_favorite_store = array();

    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    /**
     * test for getting the correct responce
     */
    public function testSearchstoresSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"business_name":"Laura Deangelis","limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }

    /**
     * test for No store found
     */
    public function testSearchstoresNoStoreFound() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"business_name":"HELL YA","limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(121, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoresMissedParameteruser_id() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"business_name":"HELL YA","limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoresMissedParameterBusiness_name() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoresMissedParameterLimit_start() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"business_name":"HELL YA","limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoresMissedParameterLimit_size() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"business_name":"HELL YA","limit_start":0}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
     /**
     * test for missed parameter
     */
    public function testSearchstoresUserNotActive() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":60798,"business_name":"Laura Deangelis","limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"Ristoranti","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "it"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersUserIdMiss() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"search_text":"Ristoranti","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "it"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(300, $response->code);
    }
    
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersNonActiveUser() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
         $data = '{"reqObj":{"user_id":60798,"search_text":"Ristoranti","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "it"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(85, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersInvalidSortType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"Ristoranti","sort_type":3,"limits" :{"limit_size":"10","limit_start":0},"lang_code" : "it"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(401, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersNoStoreFound() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"HEll Ya","sort_type":1,"limits" :{"limit_size":"10","limit_start":0},"lang_code" : "it"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for getting the correct responce
     */
    public function testGetuserstoresSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_type":1,"limit_start":1540,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for No store found
     */
    public function testGetuserstoresNoStoreFound() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
       $data = '{"reqObj":{"user_id":23599,"store_type":1,"limit_start":2000,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
     * test for No store found
     */
    public function testGetuserstoresMissUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
       $data = '{"reqObj":{"store_type":1,"limit_start":0,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
  /**
     * test for No store found
     */
    public function testGetuserstoresMissStoreType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"limit_start":1540,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
      /**
     * test for No store found
     */
    public function testGetuserstoresMissLimitStart() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_type":1,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
         /**
     * test for No store found
     */
    public function testGetuserstoresMissLimitSize() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_type":1,"limit_start":1540}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
     /**
     * test for getting the correct responce
     */
    public function testGetuserstoresInvalidStoreType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getuserstores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_type":3,"limit_start":1540,"limit_size":15}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersForWrongLanguageCode() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"Ristoranti","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "am"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test for missed parameter
     */
    public function testSearchstoreonfiltersForWrongLanguageCodeDefaultEnglish() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":123,"search_text":"Restaurants","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "am"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
    * Searchstoreonfilters for wrong favourite type
    * response code 402
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testSearchstoreonfiltersForWrongFavoriteType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"HELL YA","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "em","only_in_favorite":3}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(402, $response->code);
    }
    
    
    /**
    * Searchstoreonfilters for check if all valid favorite 
    * count must be 0
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testSearchstoreonfiltersCheckValidResponce() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/searchstoreonfilters?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"search_text":"","limits" :{"limit_size":"10","limit_start":0},"lang_code" : "em","only_in_favorite":1}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data_object = $response->data;
        $data_object = $data_object->stores;
        $search_stores_id = array_map(function($o) {
            return $o->id;
        }, $data_object);
        //call for getting the favourite stores of a user
        $serviceUrl = $baseUrl . 'api/myfavouritestores?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data_object = $response->data;
        //$data_object = $data_object->stores;
        $favorite_stores_id = array_map(function($o) {
            return $o->id;
        }, $data_object);
        $diff = array_diff($favorite_stores_id,$search_stores_id);       
        $this->assertEquals(0, count($diff));
    }
    
    /**
    * Getstoredetails Missing parameter (store_id)
    * response code 300
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testGetstoredetailsMissStoreId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getstoredetails?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(300, $response->code);
    }
    
    /**
    * Getstoredetails Missing parameter (store_id)
    * response code 300
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testGetstoredetailsMissUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getstoredetails?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"store_id":"3813"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(300, $response->code);
    }
    
    /**
    * Getstoredetails for non active user
    * response code 85
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testGetstoredetailsNonActiveUser() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getstoredetails?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":12345678,"store_id":"3813"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(85, $response->code);
    }
    
        /**
    * Getstoredetails for no store found
    * response code 121
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testGetstoredetailsNoStoreFound() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getstoredetails?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_id":"3813123456"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(121, $response->code);
    }
    
    /**
    * Getstoredetails for Sucess
    * response code 121
    * phpunit -c app/phpunit.xml.dist src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
    */
    public function testGetstoredetailsSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/getstoredetails?access_token=ZDM0MjM0NDFkZDMzMDNhMjU5NzdhMjA1MTY5ZjY3OGEyNDk1MGNiYjczODE2NWIzMTRkZjk0ZDU4YWQ2MTZhYQ';
        $data = '{"reqObj":{"user_id":23599,"store_id":"3813"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    
    /**
     * Test case for user login
      * phpunit -c app/phpunit.xml.dist --filter="testCreateStoreSucess" src/StoreManager/StoreBundle/Tests/Controller/RestStoreControllerTest.php
     * @return user id
     */
    public function testCreateStoreSucess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/createstores?access_token='.$access_token;
        $data = ' {
        "reqObj":{
        "user_id":"'.$user_id.'",
        "name":"shopforall",
        "business_name":"Business store by postman",
        "description":"desc is here",
        "email":"ankurshop@gmail.com",
        "phone":"9891508595",
        "legal_status":"ok",
        "business_type":"test",
        "business_country":"US",
        "business_region":"NCR",
        "business_city":"NOIDA",
        "business_address":"sec-30",
        "zip":"12200",
        "province":"HN",
        "vat_number":"'.$vat_number.'",
        "iban":"IT51Y03069094841200000005893",
        "map_place":"gurgaon",
        "latitude":"255",
        "longitude":"255",
        "referral_id": "'.$user_id.'",
        "call_type":"2",
        "fiscal_code":"fiscal_code",
        "sale_country":"sale_country",
        "sale_region":"sale_region",
        "sale_city":"sale_city",
        "sale_province":"sale_province",
        "sale_zip":"sale_zip",
        "sale_email":"sale_email",
        "sale_phone_number":"sale_phone_number",
        "sale_catid":"5",
        "sale_subcatid":"sale_subcatid",
        "sale_description":"sale_description",
        "sale_address":"sale_address",
        "sale_map":"sale_map",
        "repres_fiscal_code":"repres_fiscal_code",
        "repres_first_name":"repres_first_name",
        "repres_last_name":"repres_last_name",
        "repres_place_of_birth":"repres_place_of_birth",
        "repres_dob":"1988-03-05",
        "repres_email":"repres_email",
        "repres_phone_number":"repres_phone_number",
        "repres_address":"repres_address",
        "repres_province":"repres_province",
        "repres_city":"repres_city",
        "repres_zip":"repres_zip",
        "shop_keyword":"shop_keyword"
        }
        }';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);      
        $data = $response->data;
        $store_id = $data->store_id;
        //get store id from applane by query 
        $appalane_store_id = $applane_integration->getStoreIdFromApplane($store_id);
        $this->assertEquals($store_id, $appalane_store_id);
    }

}
