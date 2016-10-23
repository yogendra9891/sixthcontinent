<?php

namespace Post\PostBundle\Tests\Controller;

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
     * URL: phpunit -c app/ --filter="testShareInvalidObjectType" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShareInvalidObjectType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/userposts?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => $user_id,
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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
     * response code 1129
     * URL: phpunit -c app/ --filter="testShareInvalidShareType" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShareInvalidShareType()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/userposts?access_token='.$access_token;
        
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => $user_id,
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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
        $this->assertEquals(1129,$service_resp['code']);
    }
    
    /**
     * test case to share club on dashboard
     * response code 101
     * URL: phpunit -c app/ --filter="testClubShareOnClubDashboard" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testClubShareOnClubDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/userposts?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => '21167',
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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
     * URL: phpunit -c app/ --filter="testShopShareOnClubDashboard" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testShopShareOnClubDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/userposts?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => $user_id,
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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
     * URL: phpunit -c app/ --filter="testOfferShareOnDashboard" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testOfferShareOnDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/userposts?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => $user_id,
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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
     * URL: phpunit -c app/ --filter="testSocialProjectShareOnDashboard" src/Post/PostBundle/Tests/Controller/ShareControllerTest.php
     */
    public function testSocialProjectShareOnDashboard()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/dashboardposts?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'group_id'   => $this->getContainer()->getParameter('club_id'),
            'post_title'   => "title",
            'post_desc' => 'description',
            'youtube' => '',
            'to_id' => $user_id,
            'link_type' =>'1',
            'post_id' => '',
            'post_type' => 1,
            'media_id' => array(),
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

