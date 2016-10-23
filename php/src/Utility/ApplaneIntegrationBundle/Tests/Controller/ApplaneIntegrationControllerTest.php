<?php

namespace Utility\ApplaneIntegrationBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;

class ApplaneIntegrationControllerTest extends WebTestCase {

    protected $user_favorite_store = array();

    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }

    /**
     * Test case for getting the Accesstoken
     * phpunit -c app/phpunit.xml.dist --filter="testgetAccessToken" src/Utility/ApplaneIntegrationBundle/Tests/Controller/ApplaneIntegrationControllerTest.php
     */
    public function testgetAccessToken() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'webapi/getaccesstoken';
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = '{"reqObj":{"client_id":"' . $client_id . '","client_secret":"' . $client_secret . '","grant_type":"password","username":"' . $user_name . '","password":"' . $password . '"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data = $response->data;
        $this->assertEquals(101, $response->code);
    }

    /**
     * Test case for getting the Accesstoken
     * phpunit -c app/phpunit.xml.dist --filter="testgetAccessToken" src/Utility/ApplaneIntegrationBundle/Tests/Controller/ApplaneIntegrationControllerTest.php
     * @return access token of a user
     */
    public function getAccessToken() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'webapi/getaccesstoken';
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = '{"reqObj":{"client_id":"' . $client_id . '","client_secret":"' . $client_secret . '","grant_type":"password","username":"' . $user_name . '","password":"' . $password . '"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data = $response->data;
        $access_token = $data->access_token;
        return $access_token;
    }

    /**
     * Test case for user login
     * phpunit -c app/phpunit.xml.dist --filter="testLoginUser" src/Utility/ApplaneIntegrationBundle/Tests/Controller/ApplaneIntegrationControllerTest.php
     * @return type
     */
    public function testLoginUser() {

        $access_token = $this->getAccessToken();
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/logins?access_token='.$access_token;
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = '{"reqObj":{"username":"' . $user_name . '","password":"' . $password . '"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * function for getting the user id after login user
     * @return user id
     */
    public function getLoginUser() {

        $access_token = $this->getAccessToken();
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl . 'api/logins?access_token='.$access_token;
        $user_name = $this->getContainer()->getParameter('user_name');
        $password = base64_encode($this->getContainer()->getParameter('password'));
        $client_id = $this->getContainer()->getParameter('client_id');
        $client_secret = $this->getContainer()->getParameter('client_secret');
        $data = '{"reqObj":{"username":"' . $user_name . '","password":"' . $password . '"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();
        $response = json_decode($response);
        $data = $response->data;
        $user_id = $data->id;
        $data_info['user_id'] = $user_id;
        $data_info['access_token'] = $access_token;
        return $data_info;
    }
    
    /**
     *  get store id from applane
     * @param type $store_id
     */
    public function getStoreIdFromApplane($store_id) {
        
        $appalne_base = $this->getContainer()->getParameter('base_applane_url');
        $applane_shop_collection = $this->getContainer()->getParameter('shop_collection');
        $access_code =  $this->getContainer()->getParameter('applane_user_token');
        $data = '{"$collection":"'.$applane_shop_collection.'","$limit":2000,"$filter":{"_id":"'.$store_id.'"}}';
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        $applane_resp = $applane_service->callApplaneService($data,'query','query');
        $appalne_data = json_decode($applane_resp);
        $result = $appalne_data->response;
        $result = $result->result;
        $applane_store_id = isset($result[0]->_id) ? $result[0]->_id : 0;
        return $applane_store_id;
    }
    
    /**
     * Get User infor from applane
     * @param int $user_id
     * @return json $applane_resp
     */
    public function getUserInfoFromApplane($user_id)
    {
        //$final_data = '{"$collection":"sixc_citizens","$limit":1,"$filter":{"_id":"65135"}}';
        $final_data = $this->prepareCitizenSearchCollection($user_id);
        $url_update = 'query';
        $query_update = 'query';
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        return $applane_resp;
    }
    
     /**
     * Prepare citizen search collection
     * @param int $user_id
     * @return string
     */
    public function prepareCitizenSearchCollection($user_id)
    {
        $filter_data = (object)array('_id' => (string)$user_id);
        $final_data = array(
            '$collection' => 'sixc_citizens',
            '$filter' => $filter_data,
            '$limit' => 1
        );
       return json_encode($final_data);
    }

}
