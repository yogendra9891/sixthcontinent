<?php

namespace LinkPreview\LinkPreviewBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Symfony\Component\Console\Input\InputInterface;

class LinkPreviewTest extends WebTestCase
{
   /**
     * test case for missing title type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewTitle" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewTitle()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('title',$resp['title']);
        
    }
    
    /**
     * test case for missing url type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewUrl" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewUrl()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('url',$resp['url']);
        
    }
    
    /**
     * test case for missing PageUrl type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewPageUrl" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewPageUrl()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('pageUrl',$resp['pageUrl']);
    }
    
    
     /**
     * test case for missing canonicalUrl type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewCanonicalUrl" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewCanonicalUrl()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('canonicalUrl',$resp['canonicalUrl']);
    }
    
    /**
     * test case for missing description type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewDescription" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewDescription()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('description',$resp['description']);
    }
    
     /**
     * test case for missing images type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewImages" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewImages()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('images',$resp['images']);
    }
    
    /**
     * test case for missing video type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewVideo" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewVideo()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('video',$resp['video']);
    }
    
     /**
     * test case for missing video type
     * response code 1130
     * URL: phpunit -c app/ --filter="testgetLinkPreviewVideoIframe" src/LinkPreview/LinkPreviewBundle/Tests/Controller/LinkPreviewTest.php
     */
    public function testgetLinkPreviewVideoIframe()
    {   
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'webapi/getlinkpreview?access_token='.$access_token;
        $data = array(
            'text' => 'http://book.cakephp.org/3.0/en/views/helpers.html',
            'imagequantity'   => '-1',
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_resp = json_decode($response,true);
        foreach($service_resp as $key => $value){
            $resp[$key] = $key;
        }
        $this->assertEquals('videoIframe',$resp['videoIframe']);
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
?>