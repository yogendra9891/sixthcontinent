<?php

namespace PostFeeds\PostFeedsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;


class SocialProjectControllerTest extends WebTestCase
{
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
    /**
     *  function for calling the curl request
     * @param type $remoteUrl
     * @param type $data
     * @return type
     */
    public function curlCall($remoteUrl,$data) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        $output1 = json_decode($output);
        return $output1;
    }
    
    /**
     * test case for upload cover media 
     */
    public function testUploadCoverMediasAction() {
      
        $data = ' { "user_id": 22, "type":"cover" }';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postuploadcovermedias?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                            ->setParam('reqObj', $data)
                            ->setFile('social_media[]','C:/Users/Public/Pictures/Sample Pictures/Tulips.jpg')
                            ->setRequestType('POST')
                            ->send()
                            ->getResponse();
       // var_dump($response); exit;
        $response = json_decode($response);
       
        $this->assertEquals(101, $response->code);
    }
     /**
     * test case for upload media in gallery
     */
    public function testUploadGalleryMediasAction() {
      
        $data = ' { "user_id": 22, "type":"gallery" }';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postuploadcovermedias?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                            ->setParam('reqObj', $data)
                            ->setFile('social_media[]','C:/Users/Public/Pictures/Sample Pictures/nature-desktop-wallpaper-avantzone.jpg')
                            ->setRequestType('POST')
                            ->send()
                            ->getResponse();
       // var_dump($response); exit;
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test case for create social project
     */
    public function testCreateSocialProjectsAction(){
        
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/createsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "98",
                                "project_title": "testcase project",
                                "project_desc": "testcase project",
                                "project_loc": "GN",
                                "project_city":"GN",
                                "project_country":"IN",
                                "x":"",
                                "y":"",
                                "cover_medias":["558a4b616fe21e1011000029"],
                                "gallery_medias":["558a4cbd6fe21e101100002a"],
                                "longitude" : "98.98",
                                "latitude"  : "98.98",
                                "website" : "www.test.com",
                                "email" : "test@gmail.com"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)
                    ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /*
     * test case for missing parameters in create social project
     */
    public function testMissingUserIdAction(){
        
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/createsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "22",
                                "project_title": "",
                                "project_desc": "testcase project",
                                "project_loc": "IN",
                                "project_city":"IN",
                                "project_country":"IN",
                                "x":"",
                                "y":"",
                                "cover_medias":[],
                                "gallery_medias":[],
                                "longitude" : "22.22",
                                "latitude"  : "33.33",
                                "website" : "www.test.com",
                                "email" : "test@gmail.com"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $f_response = json_decode($response, true);
        $message = $f_response['message'];
        $this->assertEquals(1001, $f_response['code'], $message);
       // $this->assertNotEquals(1001, $f_response['code'], $message);
    }
    
    /**
     * test case for project details
     */
    public function testProjectDetailAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/viewsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "project_id" : "55704d4a6fe21ebc11000029"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $f_response = json_decode($response, true);
        $message = $f_response['message'];
        $this->assertEquals(101, $f_response['code'], $message);
    }
    /**
     * missing required parametrs in project details
     */
      public function testMissingParametrProjectDetailAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/viewsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "project_id" : ""
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $f_response = json_decode($response, true);
        $message = $f_response['message'];
        $this->assertEquals(1001, $f_response['code'], $message);
    }
    /**
     * Search social project with user id only
     */
    public function testserachsocialprojectAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postsearchsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "owner_id" : "",
                                "limit" : "",
                                "offset" : "",
                                "text" : "",
                                "project_country" : "",
                                "project_city" : "",
                                "sort_type" : ""
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $f_response = json_decode($response, true);
        $message = $f_response['message'];
        $this->assertEquals(101, $f_response['code'], $message);
    }
    /**
     * Search social project with text only
     */
    public function testSerachSocialProjectWithTextAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postsearchsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "owner_id" : "",
                                "limit" : "test",
                                "offset" : "",
                                "text" : "",
                                "project_country" : "",
                                "project_city" : "",
                                "sort_type" : ""
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    /**
     * Search social project with text only
     */
    public function testSerachSocialProjectWithCountryAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postsearchsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "owner_id" : "",
                                "limit" : "test",
                                "offset" : "",
                                "text" : "",
                                "project_country" : "IN",
                                "project_city" : "",
                                "sort_type" : ""
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    /**
     * Search social project with limit and offset 
     */
    public function testSerachSocialProjectWithLimitAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postsearchsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "owner_id" : "",
                                "limit" : 0,
                                "offset" : 5,
                                "text" : "",
                                "project_country" : "",
                                "project_city" : "",
                                "sort_type" : ""
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    /**
     * Search social project with limit and offset 
     */
    public function testSerachSocialProjectWithSortingAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/postsearchsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "89",
                                "owner_id" : "",
                                "limit" : 0,
                                "offset" : 5,
                                "text" : "",
                                "project_country" : "",
                                "project_city" : "",
                                "sort_type" : "1"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * test case for check user is owner of this or not Social projet
     */
        public function testProjectOwnerAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/editsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "22",
                                "project_id": "558aab3e6fe21e101100003d",
                                "project_title": "testcase project1",
                                "project_desc": "testcase project2",
                                "project_loc": "IN",
                                "project_city":"IN",
                                "project_country":"GN",
                                "x":"",
                                "y":"",
                                "cover_medias":[],
                                "gallery_medias":[],
                                "longitude" : "98.98",
                                "latitude"  : "98.98",
                                "website" : "www.test.com",
                                "email" : "test@test.com"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $f_response = json_decode($response, true);
        $message = $f_response['message'];
        $this->assertEquals(1131, $f_response['code'], $message);
    }
     /**
     * test case for edit Social projet
     */
        public function testEditSocialProjectAction(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl .'api/editsocialproject?access_token=ZmMyMDA4YWE3MDMzMjI4YmEzNDk0ZDkyZWI4OTY0ZDEwODVhZGQ5NWY4NTNkOWZiZmY2ZGQ4ZTI0OWVhNTU2Ng';
        $client = new CurlRequestService();
        $data = ' {
                        "reqObj":{
                                "user_id": "98",
                                "project_id": "558a51056fe21e101100002b",
                                "project_title": "testcase project1",
                                "project_desc": "testcase project2",
                                "project_loc": "IN",
                                "project_city":"IN",
                                "project_country":"GN",
                                "x":"",
                                "y":"",
                                "cover_medias":["558a86036fe21e101100002f"],
                                "gallery_medias":["558a4cbd6fe21e101100002a"],
                                "longitude" : "98.98",
                                "latitude"  : "98.98",
                                "website" : "www.test.com",
                                "email" : "test@test.com"
                         }
                    }';
        $response = $client->send('POST', $serviceUrl, array(), $data)->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
}
