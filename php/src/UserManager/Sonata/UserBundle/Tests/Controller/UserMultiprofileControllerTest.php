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
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;

/**
 * Controller managing the user registration
 *
 */
class UserMultiProfileControllerTest extends WebTestCase
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
     * URL: phpunit -c app/ --filter="testupdatemultiprofilessuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserMultiprofileV1ControllerTest.php
     */
    public function testupdatemultiprofilessuccess()
    {
        $reques_obj = '{"reqObj":{"user_id":65132,"type":1,"relationship":"Single","about_me":" ","address":"swerfe","latitude":"-34.4794653","longitude":"150.85275120000006","map_place":"Gura St, Berkeley NSW 2506, Australia","firstname":"chandelas","lastname":"gupta","birthday":"14-9-1992","gender":"m","country":"IT","region":"Italy","state":"sdfe","referral_id":"2","zip":"56789","city":"sef","city_born":"serfe","hobbies":" "}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/register';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }
    
    /**
     * Forget register success case
     * response code 101
     * URL: phpunit -c app/ --filter="testupdatemultiprofilesapplanesuccess" src/UserManager/Sonata/UserBundle/Tests/Controller/UserMultiprofileV1ControllerTest.php
     */
    public function testupdatemultiprofilesapplanesuccess()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $reques_obj = '{"reqObj":{"user_id":"'.$user_id.'","type":1,"relationship":"Single","about_me":" ","address":"swerfe","latitude":"-34.4794653","longitude":"150.85275120000006","map_place":"Gura St, Berkeley NSW 2506, Australia","firstname":"mouseman","lastname":"gupta","birthday":"14-9-1992","gender":"m","country":"IT","region":"Italy","state":"sdfe","referral_id":"2","zip":"56789","city":"sef","city_born":"serfe","hobbies":" "}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'api/updatemultiprofiles?access_token='.$access_token;
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action);
       
        //transaction services will be call when user register successfully to check success
        $applane_user_id = $applane_integration->getUserInfoFromApplane($user_id);
        $this->assertEquals($user_id, $applane_user_id);
     
    }
    
 
   
}
