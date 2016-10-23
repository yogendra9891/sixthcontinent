<?php
namespace StoreManager\StoreBundle\Tests\Services;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class FacebookAutoPostServiceTest extends WebTestCase{
    
    public function testPostAtWallSuccess(){
        $container = $this->getContainer();
            $fbService = $container->get('facebook_auto_post.service');
            $fbPostParam = $container->getParameter('facebook_post');
            $accessToken = 'CAACFzn5ZCGh8BAJ52OH4ZBwDVIejDY89AhgWOlMxODh3zovjmLRe6vZAEZArigk1aQ4bZBcqcsvx7zC9AeBfsdcgSG96z9I4vhv3Y4ymqujimdLh1iXg9EbgXRIu5iR1e4jvfii98MVBGSb1cgtK6wOaPOHeZA2q8pfNfi9LpF6nBrccLDWFGjCinYKVhyHL4ZD';
            $fbResponse = $fbService->setAccessToken($accessToken)
                     ->setTitle(isset($fbPostParam['default_title']) ? $fbPostParam['default_title'] : '')
                     ->setMessage(isset($fbPostParam['default_message']) ? $fbPostParam['default_message'] : '')
                     ->setDescription("testing")
                     ->setImageUrl('')
                     ->setTargetLink('http://local.it')
                     ->send();
            $this->assertTrue($fbResponse['code']==101 && isset($fbResponse['post_id']), $fbResponse['message']);
    }
    
    public function testExtendAccesToken(){
        $container = $this->getContainer();
            $fbService = $container->get('facebook_auto_post.service');
            $fbPostParam = $container->getParameter('facebook_post');
            $accessToken = 'CAACFzn5ZCGh8BAJ52OH4ZBwDVIejDY89AhgWOlMxODh3zovjmLRe6vZAEZArigk1aQ4bZBcqcsvx7zC9AeBfsdcgSG96z9I4vhv3Y4ymqujimdLh1iXg9EbgXRIu5iR1e4jvfii98MVBGSb1cgtK6wOaPOHeZA2q8pfNfi9LpF6nBrccLDWFGjCinYKVhyHL4ZD';
            $fbResponse = $fbService->setAccessToken($accessToken)
                     ->getExtendedAccessToken();
            $this->assertTrue($fbResponse['code']==101 && isset($fbResponse['post_id']), $fbResponse['message']);
    }
    
    public function testAccesTokenInfo(){
        $container = $this->getContainer();
            $fbService = $container->get('facebook_auto_post.service');
            $fbPostParam = $container->getParameter('facebook_post');
            $accessToken = 'CAACFzn5ZCGh8BAEoEa1T8FbrsncJZAzqZBKVn5ltx63m4w2QH7ZCQiuZBZBUYFxxiz2xyX46kDUWHkGKG6k9y7CgSc4hWksFir93xumZA1z7wrFEi9o2tyBmOJmVIngDH0KUz0sJDChiMDZB6X6rGCZALZBPxCOPjwyGS8YETxGX3BTZAx6P8OLXTyBOBlsnZBc00VMZD';
            $fbResponse = $fbService->setAccessToken($accessToken)
                     ->getAccessTokenInfo();
            $this->assertTrue($fbResponse['code']==101, $fbResponse['message']);
    }
    
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}
