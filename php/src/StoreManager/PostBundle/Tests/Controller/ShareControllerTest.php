<?php

namespace StoreManager\PostBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Symfony\Component\Console\Input\InputInterface;

class ShareControllerTest extends WebTestCase
{
    
    /**
     * test case to check invalid object type for dashboard
     * response code 1130
     * URL: phpunit -c app/ --filter="testShareInvalidObjectType" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShareInvalidObjectType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => $this->getContainer()->getParameter('share_invalid_object_type'),
            'object_id' => $this->getContainer()->getParameter('share_invalid_object_id'),
            'share_type' => $this->getContainer()->getParameter('valid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response,true);
        $this->assertEquals(1130,$service_resp['code']);
    }
    
    /**
     * test case to share invalid share type for dashboard
     * response code 410
     * URL: phpunit -c app/ --filter="testShareInvalidShareType" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShareInvalidShareType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => $this->getContainer()->getParameter('share_valid_object_type'),
            'object_id' => $this->getContainer()->getParameter('share_valid_object_id'),
            'share_type' => $this->getContainer()->getParameter('invalid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );

        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response,true);
        $this->assertEquals(410,$service_resp['code']);
    }
    
    /**
     * test case to share club on dashboard
     * response code 101
     * URL: phpunit -c app/ --filter="testClubShareOnShopDashboard" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testClubShareOnShopDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
         $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => 'CLUB',
            'object_id' => $this->getContainer()->getParameter('share_club_id'),
            'share_type' => $this->getContainer()->getParameter('valid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    /**
     * test case to share shop on dashboard
     * response code 101
     * URL: phpunit -c app/ --filter="testShopShareOnShopDashboard" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShopShareOnShopDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => 'SHOP',
            'object_id' => $this->getContainer()->getParameter('share_shop_id'),
            'share_type' => $this->getContainer()->getParameter('valid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    /**
     * test case to share offer on dashboard
     * response code 101
     * URL: phpunit -c app/ --filter="testOfferShareOnShopDashboard" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testOfferShareOnShopDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => 'OFFER',
            'object_id' => $this->getContainer()->getParameter('share_offer_id'),
            'share_type' => $this->getContainer()->getParameter('valid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    
    /**
     * test case to share offer on dashboard
     * response code 101
     * URL: phpunit -c app/ --filter="testSocialProjectShareOnShopDashboard" src/StoreManager/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testSocialProjectShareOnShopDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/storeposts?access_token='.$access_token;
        $data = array(
            'store_id' =>  $this->getContainer()->getParameter('shop_id'),
            'post_title'   => 'test title',
            'post_desc' => 'description',
            'user_id' => $user_id,
            'post_id' => '',
            'post_type' =>'1',
            'youtube' => '',
            'media_id' => array(),
            'link_type' => '',
            'tagged_friends' => '',
            'device_request_type' => 'web',
            'object_type' => 'SOCIAL_PROJECT',
            'object_id' => $this->getContainer()->getParameter('share_social_project_id'),
            'share_type' => $this->getContainer()->getParameter('valid_share_type'),
            'content_share'=>array(
                'url' => 'url',
                'pageUrl' => 'page_url',
                'canonicalUrl' => 'canonical_url',
                'images' => array('im1', 'im2'),
                'title' => 'title',
                'description' => 'desc',
                'video' => "no",
                'videoIframe' => null
            )
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();

        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    /**
     * function for making the final request object
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

