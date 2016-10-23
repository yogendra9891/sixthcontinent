<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;
use Notification\NotificationBundle\NManagerNotificationBundle;
/**
 * Controller managing the user registration
 *
 */
class RestRegistrationV1ControllerTest extends WebTestCase
{
   protected $web_uri = 'localhost/sixthcontinent_symfony/php/web/';

   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
    
    /**
     * register success case
     * response code 101
     * URL: phpunit -c app/ --filter="testregistersuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/RestRegistrationV1ControllerTest.php
     */
    public function testregistersuccess()
    {
        $reques_obj = '{"reqObj":{"email":"yiipaaa@daffodilsw.com","password":"111111","firstname":"chandelaman","lastname":"test", "birthday":"23-05-1988","gender":"m","country":"US", "type":"1", "referral_id":2}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/register';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * Register success case
     * response code 101
     * URL: phpunit -c app/ --filter="testregisterapplanesuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/RestRegistrationV1ControllerTest.php
     */
    public function testregisterapplanesuccess()
    {
        $reques_obj = '{"reqObj":{"email":"yiipaaa@daffodilsw.com","password":"111111","firstname":"chandelaman","lastname":"test", "birthday":"23-05-1988","gender":"m","country":"US", "type":"1", "referral_id":2}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/register';
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
     * Get User id
     * @param int $user_id
     * @return type
     */
    public function getUserInfoFromApplane($user_id)
    {
        //$final_data = '{"$collection":"sixc_citizens","$limit":1,"$filter":{"_id":"65135"}}';
        $final_data = $this->prepareCitizenSearchCollection($user_id);
        $url_update = 'query';
        $query_update = 'query';
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        
        //decode response
        $decoded_resp = json_decode($applane_resp);
        $applane_user_id = null;
        if(isset($decoded_resp->response->result[0]->_id)){
            $applane_user_id = $decoded_resp->response->result[0]->_id;
        }
        return $applane_user_id;
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
