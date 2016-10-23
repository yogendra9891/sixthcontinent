<?php

namespace Utility\UniversalNotificationsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\IUtility;

class ShareControllerTest extends WebTestCase implements ApplaneConstentInterface
{
    private $dm;
    private $em;
    
    public function setUp(){
        parent::setUp();
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }
    
    /**
     * Test case for getting the Accesstoken
     * @return access token of a user
     */
    public function testGetAccessToken() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'webapi/getaccesstoken';
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        
        $data = json_encode(array(
            "reqObj"=>array(
                "client_id"=>$client_id,
                "client_secret"=>$client_secret,
                "grant_type"=>"password",
                "username"=>$user_name,
                "password"=>$password
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(0, is_null($response), 'Invalid Json');
        $this->assertEquals(101, $response->code);
        
        return $response->data->access_token;
    }

    /**
     * Test case for user login
     * @depends testGetAccessToken
     * @return type
     */
    public function testLoginUser($accessToken) {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/logins?access_token='.$accessToken;
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = json_encode(array(
            "reqObj"=>array(
                "username"=>$user_name,
                "password"=>$password
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code);
        
        return (array)$response->data;
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareUserPost($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $post = $this->dm->getRepository("DashboardManagerBundle:DashboardPost")
                ->createQueryBuilder('post')
                ->limit(1)
                ->getQuery()
                ->execute()
                ->getSingleResult();
        $postId = $post ? $post->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$postId,
                'item_type'=>'user_post',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareClubPost($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $post = $this->dm->getRepository("PostPostBundle:Post")
                ->createQueryBuilder('post')
                ->limit(1)
                ->getQuery()
                ->execute()
                ->getSingleResult();
        $postId = $post ? $post->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$postId,
                'item_type'=>'club_post',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareShopPost($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $post = $this->dm->getRepository("StoreManagerPostBundle:StorePosts")
                ->createQueryBuilder('post')
                ->limit(1)
                ->getQuery()
                ->execute()
                ->getSingleResult();
        $postId = $post ? $post->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$postId,
                'item_type'=>'shop_post',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareShop($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $shop = $this->em->getRepository("StoreManagerStoreBundle:Store")
                ->createQueryBuilder('shop')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        $shopId = $shop ? $shop->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$shopId,
                'item_type'=>'shop',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareSocialProject($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $project = $this->dm->getRepository("PostFeedsBundle:SocialProject")
                ->createQueryBuilder('project')
                ->limit(1)
                ->getQuery()
                ->execute()
                ->getSingleResult();
        $projectId = $project ? $project->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$projectId,
                'item_type'=>'social_project',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     */
    public function testShareSocialProjectPost($accessToken, $user){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $post = $this->dm->getRepository("PostFeedsBundle:PostFeeds")
                ->createQueryBuilder('post')
                ->field('post_type')->equals('social_project')
                ->limit(1)
                ->getQuery()
                ->execute()
                ->getSingleResult();
        $postId = $post ? $post->getId() : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$postId,
                'item_type'=>'social_project_post',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    public function testGetOffers() {
        $data = array();
        
       // $today = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));
        $todayMidnight = Utility::getDate(IUtility::DATE_FORMAT_ISO, IUtility::DATE_TODAY);
        $tomorrowMidnight = Utility::getDate(IUtility::DATE_FORMAT_ISO, IUtility::DATE_TOMORROW);
       
        $data['coupons'] = array(
            '$collection'=> self::OFFERS_COLLECTION,
            '$filter'=> array(
                'offer_type'=> self::OFFERS_TYPE_COUPONS,
                //'start_date'=>(object)array('$lt'=>$tomorrowMidnight),
                'end_date'=>(object)array('$gte'=>$todayMidnight),
                'is_deleted'=>array(
                    '$in'=>array(false,null)
                ),
                'shop_id.is_shop_deleted'=>array(
                    '$in'=>array(false,null)
                        )
            ),
            '$sort'=> array(
                '__history.__createdOn'=> -1
            ),
            '$limit'=> 1
        );
        
        $data['cards'] = array(
            '$collection'=> self::OFFERS_COLLECTION,
            '$filter'=> array(
                'offer_type'=> self::OFFERS_TYPE_CARDS,
//                 'start_date'=>(object)array('$lt'=>$tomorrowMidnight),
                'end_date'=>(object)array('$gte'=>$todayMidnight),
                'is_deleted'=>array(
                    '$in'=>array(false,null)
                ),
                'shop_id.is_shop_deleted'=>array(
                    '$in'=>array(false,null)
                        )
            ),
            '$sort'=> array(
                '__history.__createdOn'=> -1
            ),
            '$limit'=> 1
        );
        $queryData = json_encode($data);
        $api = self::QUERY_BATCH;
        $queryParam = self::URL_QUERY;
        
        $offersJson = $this->makeApplaneRequest($queryData, $api, $queryParam);
        $offersObj = json_decode($offersJson);
        $this->assertEquals(1, isset($offersObj->response), 'Invalid Offer Response From Applane');
        $offers = array(
            'coupon'=> isset($offersObj->response->coupons->result[0])? $offersObj->response->coupons->result[0] : array(),
            'card'=> isset($offersObj->response->cards->result[0])? $offersObj->response->cards->result[0] : array()
        );
        return $offers;
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     * @depends testGetOffers
     */
    public function testShareOfferCards($accessToken, $user, $offers){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $card = $offers['card'];
        $cardId = is_object($card) ? $card->_id : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$cardId,
                'item_type'=>'offer',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    /**
     * @depends testGetAccessToken
     * @depends testLoginUser
     * @depends testGetOffers
     */
    public function testShareCoupons($accessToken, $user, $offers){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/share/post?access_token='.$accessToken;
        
        $coupon = $offers['coupon'];
        $couponId = is_object($coupon) ? $coupon->_id : 0;
        $data = json_encode(array(
            "reqObj"=>array(
                "user_id"=>$user['id'],
                "item_id"=>$couponId,
                'item_type'=>'coupon',
                'receivers'=>array(
                    array(
                        'name'=>'Akhtar Khan',
                        'email'=>'akhtar.khan@daffodilsw.com'
                    )
                )
            )
        ));
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(1, isset($response->code), 'Invalid Response');
        $this->assertEquals(101, $response->code, $response->message);
    }
    
    protected function makeApplaneRequest($data, $api, $queryParam) {
        $applane_user_token = $this->getContainer()->getParameter('applane_user_token');
        $serviceUrl = $this->getContainer()->getParameter('base_applane_url'). $api;
        $client = new CurlRequestService();
        $response =  $client->setUrl($serviceUrl)
                            ->setRequestType('POST')
                            ->setHeader('content-type', 'application/x-www-form-urlencoded')
                            ->setParam('code', $applane_user_token)
                            ->setParam($queryParam, $data)
                            ->send()
                            ->getResponse();
        
        return $response;
    }
    
    protected function getContainer(){
        $client = static::createClient();
        return $client->getContainer();
    }
}
