<?php
namespace Media\MediaBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
class AlbumMediaCommentControllerTest extends WebTestCase 
{
    public function testSinglePhotoMediaDetailsIsJson()
    {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/singlephotomediadetails?access_token=ZjdjZDQzY2FmOGFlODc2ZTI4ZjQxNDY2MjQ1YjFhNjA2OGMwMTNkM2E4NTJhM2ZkMTAzMzRmY2ZhODZmYzAwOA';
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"media_id":"551914ded574f8f3348b4567","user_id":20809,"album_type":"user","album_id":"54fe8b26d574f86f268b4567"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $this->assertInternalType('array', json_decode($response, true), 'Invalid JSON');
    }
    public function testSinglePhotoMediaDetailsSuccess(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/singlephotomediadetails?access_token=ZjdjZDQzY2FmOGFlODc2ZTI4ZjQxNDY2MjQ1YjFhNjA2OGMwMTNkM2E4NTJhM2ZkMTAzMzRmY2ZhODZmYzAwOA';
        $data = '{"reqObj":{"media_id":"551914ded574f8f3348b4567","user_id":20809,"album_type":"user","album_id":"54fe8b26d574f86f268b4567"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                    ->getResponse();
        $response = json_decode($response);        
        $this->assertEquals(101, $response->code);
    }
    
     protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}