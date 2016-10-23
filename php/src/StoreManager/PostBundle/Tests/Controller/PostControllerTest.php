<?php

namespace UserManager\Sonata\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;

class RestFriendsControllerTest extends WebTestCase {

    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }

    public function testListstorepostsInvalidGrant() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $acess_token = 'habchchd';
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals('invalid_grant', $response->error);
    }

    public function testStorepostsMobileSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","post_title":"currently Not in used from frontend","post_desc":"adcdscs","user_id":1,"post_id":"","post_type":"1","youtube":"","media_id":[],"link_type":"0","device_request_type":"mobile"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }

    public function testStorepostsWebSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","post_title":"currently Not in used from frontend","post_desc":"sdcscscdc","user_id":1,"post_id":"","post_type":"1","youtube":"","media_id":[],"link_type":"0"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }

    public function testStorepostsMobileTransactionTypeSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":"txn","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    public function testStorepostsMissUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","post_title":"currently Not in used from frontend","post_desc":"adcdscs","post_id":"","post_type":"1","youtube":"","media_id":[],"link_type":"0","share_type":"TXN","device_request_type":"mobile"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    public function testStorepostsMissStoreId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"post_title":"currently Not in used from frontend","post_desc":"adcdscs","user_id":1,"post_id":"","post_type":"1","youtube":"","media_id":[],"link_type":"0","share_type":"TXN","device_request_type":"mobile"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    public function testStorepostsMissPostType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","post_title":"currently Not in used from frontend","post_desc":"adcdscs","user_id":1,"post_id":"","youtube":"","media_id":[],"link_type":"0","share_type":"TXN","device_request_type":"mobile"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    public function testStorepostsInvalidPostType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","post_title":"currently Not in used from frontend","post_desc":"adcdscs","user_id":1,"post_id":"","post_type":3,"youtube":"","media_id":[],"link_type":"0","share_type":"TXN","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    public function testStorepostsPermissionDenied() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"2186","post_title":"currently Not in used from frontend","post_desc":"adcdscs","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":"0","share_type":"TXN","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
    * Storeposts for checking if share_type is txn then customer rating is required
    * response code 411
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsCustomerRatingRequiredOnShare() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":"txn","device_request_type":"mobile"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(411, $response->code);
    }
    
    /**
    * Storeposts for checking for a valid share type
    * response code 410
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsInvalidShareType() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":"fgdj","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(410, $response->code);
    }
    
    /**
    * Storeposts for checking share_type with left and right spaces
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsShareTypeWithSpaces() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
    * Storeposts for checking share_type with left and right spaces
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsShareCustomerRatingRequired() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
     /**
    * Storeposts for checking for invalid rating by user
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsShareInvalidRating() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":6}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(412, $response->code);
    }
    
    /**
    * Storeposts for checking for invalid store id
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsInvalidStoreId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":14951234567,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":5}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(413, $response->code);
    }
    
    /**
    * Storeposts for checking for invalid rating by user
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsCheckShareTypeAfterDBIsection() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":5}}';
        $data_array = json_decode($data);
        $data_array = $data_array->reqObj;
        $share_type = strtoupper(trim($data_array->share_type));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $response_data = $response->data;
        $this->assertEquals($share_type, $response_data->share_type);
    }
    
    /**
    * Storeposts for checking for customer rating in response
    * response code 101
    * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
    */
    public function testStorepostsCheckCustomerVotingAfterDBIsection() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":1495,"post_title":"currently Not in used from frontend","post_desc":"live news","user_id":1,"post_id":"","post_type":1,"youtube":"","media_id":[],"link_type":0,"share_type":" txn ","device_request_type":"mobile","customer_voting":5}}';
        $data_array = json_decode($data);
        $data_array = $data_array->reqObj;
        $customer_voting = $data_array->customer_voting;
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $response_data = $response->data;
        $this->assertEquals($customer_voting, $response_data->customer_voting);
    }
    
    /**
      * liststoreposts sucess
      * response code 411
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListStorePostsSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
      * liststoreposts Miss user id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListStorePostsUserIdMiss() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * liststoreposts Miss store id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListStorePostsStoreIdMiss() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * liststoreposts invalid user id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListStorePostsInvalidUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","user_id":1123456789,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * liststoreposts invalid store id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListStorePostsInvalidStoreId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495123456","user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(413, $response->code);
    }
    
    
    /**
      * Listcustomersreviews sucess
      * response code 411
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
      * Listcustomersreviews Miss user id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsUserIdMiss() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * Listcustomersreviews Miss store id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsStoreIdMiss() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * Listcustomersreviews invalid user id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsInvalidUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495","user_id":1123456789,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(100, $response->code);
    }
    
    /**
      * Listcustomersreviews invalid store id
      * response code 100
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsInvalidStoreId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/liststoreposts?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"1495123456","user_id":1,"limit_start":0,"limit_size":10}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(413, $response->code);
    }
    
    
    /**
      * Listcustomersreviews valid only_friends filter value
      * response code 101
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testListcustomersreviewsFriendFilterSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/listcustomersreviews?access_token='.$acess_token;
        $data = '{"reqObj":{"store_id":"2146","user_id":23599,"limit_start":0,"limit_size":10,"friends_ids":["355","1"]}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
      * Getfriendboughtonstores test case for frien bought on store sucess
      * response code 101
      * phpunit -c app/phpunit.xml.dist src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testGetfriendboughtonstoresSucess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/getfriendboughtonstores?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":23599,"shop_id":1495,"limits" :{"limit_size":"10","limit_start":0}}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
      * Getfriendboughtonstores test case for missing user id param
      * response code 1001
      * phpunit -c app/phpunit.xml.dist --filter="testGetfriendboughtonstoresMissUserId" src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testGetfriendboughtonstoresMissUserId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/getfriendboughtonstores?access_token='.$acess_token;
        $data = '{"reqObj":{"shop_id":1495,"limits" :{"limit_size":"10","limit_start":0}}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1001, $response->code);
    }
    
    /**
      * Getfriendboughtonstores test case for missing store id param
      * response code 101
      * phpunit -c app/phpunit.xml.dist --filter="testGetfriendboughtonstoresMissShopId" src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testGetfriendboughtonstoresMissShopId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/getfriendboughtonstores?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":23599,"limits" :{"limit_size":"10","limit_start":0}}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1001, $response->code);
    }
    
    /**
      * Getfriendboughtonstores test case for invalid store id param
      * response code 413
      * phpunit -c app/phpunit.xml.dist --filter="testGetfriendboughtonstoresInvalidShopId" src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testGetfriendboughtonstoresInvalidShopId() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/getfriendboughtonstores?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":23599,"shop_id":149512345,"limits" :{"limit_size":"10","limit_start":0}}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(413, $response->code);
    }
    
    /**
      * Getfriendboughtonstores test case for Zero limit or for 0 records
      * response code 413
      * phpunit -c app/phpunit.xml.dist --filter="testGetfriendboughtonstoresWithZeroLimit" src/StoreManager/PostBundle/Tests/Controller/PostControllerTest.php
      */
    public function testGetfriendboughtonstoresWithZeroLimit() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $acess_token = $this->getContainer()->getParameter('access_token');
        $serviceUrl = $baseUrl . 'api/getfriendboughtonstores?access_token='.$acess_token;
        $data = '{"reqObj":{"user_id":23599,"shop_id":1495,"limits" :{"limit_size":"0","limit_start":0}}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data = $response->data;
        $friends = $data->friends;
        $this->assertEquals(0, count($friends));
    }

}
