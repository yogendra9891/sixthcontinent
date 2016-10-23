<?php
namespace UserManager\Sonata\UserBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use SixthContinent\SixthContinentConnectBundle\Model\SixthcontinentConnectConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

class RestFriendsControllerTest extends WebTestCase {
    
    public function testFriendRequestDetailsIsJson(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/friendrequestdetails?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setData('{"reqObj":{"user_id":"24276", "friend_id":"24278"}}')
                ->setRequestType('POST')
                ->send()
                ->getResponse();
        $this->assertInternalType('array', json_decode($response, true), 'Invalid JSON');
    }
    
    public function testFriendRequestDetailsSuccess(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/friendrequestdetails?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        $data = '{"reqObj":{"user_id":"24276", "friend_id":"24278"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                    ->getResponse();
        $response = json_decode($response);
        $this->assertEquals(101, $response->code);
    }
    
    public function testFriendRequestDetailsMissedUserId(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/friendrequestdetails?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        $data = '{"reqObj":{"friend_id":"24278"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                    ->getResponse();
        $response = json_decode($response);
        $this->assertRegExp('/(\_PARAMETER\_USER\_ID)$/', $response->message);
    }
    public function testFriendRequestDetailsMissedFriendId(){
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $serviceUrl = $baseUrl.'api/friendrequestdetails?access_token=NDc4NmY3NmIzNzE5OTAzODRiNTEwMWM0YmZkMGIzMzI3ZjM2ZTU0OTg2MzllYjZkODc1ZmMyMmYyNzU2NGE3Yg';
        $data = '{"reqObj":{"user_id":"24276"}}';
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                    ->getResponse();
        $response = json_decode($response);
        $this->assertRegExp('/(\_PARAMETER\_FRIEND\_ID)$/', $response->message);
    }
    
    
    /**
     * Register success case
     * response code 101
     * URL: phpunit -c app/ --filter="testResponseFriendRequestsApplaneSuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testResponseFriendRequestsApplaneSuccess()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        //$access_token = $user_info['access_token'];
        $access_token = "65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg";
        //$user_id = $user_info['user_id'];
        $user_id = "65132";
        $reques_obj = '{"reqObj":{"user_id":"'.$user_id.'","friend_id":"65133","action":"1", "request_type": "1"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/responsefriendrequests?access_token='.$access_token;
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
    
        //transaction services will be call when user register successfully to check success
        if($this->assertEquals(101, $response->code)){
        $user_id = $response->data->user_id;
        $applane_user_id = $this->getUserInfoFromApplane($user_id);
        $this->assertEquals($user_id, $applane_user_id);
        }
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileSocialProject" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileSocialProject()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_title');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileSocialProject" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileSocialProjectByTitle()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_title');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = true;
        foreach($only_social_project as $project) {
            
            if(!(strpos(Utility::getLowerCaseString($project['name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileSocialProjectByDescription" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileSocialProjectByDescription()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_description');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = true;
        foreach($only_social_project as $project) {
            
            if(!(strpos(Utility::getLowerCaseString($project['business_name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileNoSocialProjectFound" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileNoSocialProjectFound()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_not_description');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        $is_passes = false;
        if(count($only_social_project) == 0) {
            $is_passes = true;
        }
        $this->assertTrue($is_passes);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsSocialProject" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsSocialProject()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_title');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 5,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsSocialProjectByTitle" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsSocialProjectByTitle()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_title');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 5,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = true;
        foreach($only_social_project as $project) {
            
            if(!(strpos(Utility::getLowerCaseString($project['name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsSocialProjectByDescription" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsSocialProjectByDescription()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_description');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 5,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = true;
        foreach($only_social_project as $project) {
            
            if(!(strpos(Utility::getLowerCaseString($project['business_name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsSocialProjectCheckCountAndData" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsSocialProjectCheckCountAndData()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_description');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 5,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $total_count = $service_resp['data']['total_count'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = false;
        if(count($only_social_project) <= $total_count) {
            $is_pass = true;
        }
        $this->assertTrue($is_pass);
    }
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsNoSocialProjectFound" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsNoSocialProjectFound()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('project_search_not_description');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 5,
            "limit_start"   => 0,
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $total_count = $service_resp['data']['total_count'];
        $only_social_project = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = false;
        if(count($only_social_project) == 0 && $total_count == 0 ) {
            $is_pass = true;
        }
        $this->assertTrue($is_pass);
    }
    
    /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    /**
     *  function for making the final request object
     * @param type $data
     * @return type
     */
    private function madeRequestObject($data) {
        
        $final_array = array('reqObj' => $data);
        $request_object = json_encode($final_array);
        return $request_object;
    }
    
    /**
     *  get results by type
     * @param type $results
     */
    private function getResultByType($results,$type = 'SP') {
        $final_result = array();
        foreach($results as $result) {
            if($result['type'] == $type) {
                $final_result[] = $result;
            }
        }
        return $final_result;
    }
    
    /**
     * test case for checking appliaction search success
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileApplication" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileApplication()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_name');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
    /**
     * test case for checking valid search record for the application 
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileApplicationByname" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileApplicationByname()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_name');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_applications = $this->getResultByType($resp_data,  SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        //check if
        $is_pass = true;
        foreach($only_applications as $application) {
            if(!(strpos(Utility::getLowerCaseString($application['name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    /**
     * test case for searching application by bussiness name
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileApplicationByBussinessName" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileApplicationByBussinessName()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_bussiness_name');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_application = $this->getResultByType($resp_data, SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        //check if
        $is_pass = true;
        foreach($only_application as $application) {
            
            if(!(strpos(Utility::getLowerCaseString($application['business_name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    
    /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testSearchAllProfileNoApplicationFound" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testSearchAllProfileNoApplicationFound()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_not_found');
        $serviceUrl = $baseUrl . 'api/searchallprofiles?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'friend_name'   => $search_title,
            'limit_start'   => 0,
            'limit_size'   => 16,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data'];
        $only_application = $this->getResultByType($resp_data,SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        $is_passes = false;
        if(count($only_application) == 0) {
            $is_passes = true;
        }
        $this->assertTrue($is_passes);
    }
    
    
    /**
     * test case for ceck success in case of application search page
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsApplication" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsApplication()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_name');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 6,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $this->assertEquals(101,$service_resp['code']);
    }
    
  /**
     * test case for cehcking valid application search by the name
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsApplicationByName" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsApplicationByName()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_name');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 6,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $only_application = $this->getResultByType($resp_data, SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        //check if
        $is_pass = true;
        foreach($only_application as $application) {
            
            if(!(strpos(Utility::getLowerCaseString($application['name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    
  /**
     * test case for checking valid application search by the business name
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsApplicationByBussinessname" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsApplicationByBussinessname()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_bussiness_name');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 6,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $only_application = $this->getResultByType($resp_data,  SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        //check if
        $is_pass = true;
        foreach($only_application as $application) {
            
            if(!(strpos(Utility::getLowerCaseString($application['business_name']), Utility::getLowerCaseString($search_title)) !== false)) {
                $is_pass = false;
            }
        }
        $this->assertTrue($is_pass);
    }
    
    /**
     * test case for checking valid application search by the business name and count
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsApplicationCheckCountAndData" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsApplicationCheckCountAndData()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_name');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 6,
            "limit_start"   => "0",
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $total_count = $service_resp['data']['total_count'];
        $only_application = $this->getResultByType($resp_data,  SixthcontinentConnectConstentInterface::SEARCH_TYPE);
        
        //check if
        $is_pass = false;
        if(count($only_application) <= $total_count) {
            $is_pass = true;
        }
        $this->assertTrue($is_pass);
    }
    
  /**
     * test case for checking valid user
     * response code 101
     * URL: phpunit -c app/ --filter="testGetAllSearchRecordsNoApplicationFound" src/UserManager/Sonata/UserBundle/Tests/Controller/RestFriendsControllerTest.php
     */
    public function testGetAllSearchRecordsNoApplicationFound()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $search_title = $this->getContainer()->getParameter('application_not_found');
        $serviceUrl = $baseUrl . 'api/getallsearchrecords?access_token='.$access_token;
        $data = array(
            'user_id' => $user_id,
            'search_text'   => $search_title,
            'search_type'   => 6,
            "limit_start"   => 0,
            'limit_size'   => 12,
        );
        
        $request_object = $this->madeRequestObject($data);
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $request_object)
                ->getResponse();
        
        $service_resp = json_decode($response,true);
        $resp_data = $service_resp['data']['results'];
        $total_count = $service_resp['data']['total_count'];
        $only_application = $this->getResultByType($resp_data,'SP');
        
        //check if
        $is_pass = false;
        if(count($only_application) == 0 && $total_count == 0 ) {
            $is_pass = true;
        }
        $this->assertTrue($is_pass);
    }
    
}
