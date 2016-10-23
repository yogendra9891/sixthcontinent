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

/**
 * Controller managing the accesstoken of the user
 *
 * @author Abhishek Gupta <abhishek.gupta@daffodilsw.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class UserAccesstokenV1ControllerTest extends WebTestCase
{
   /**
    * get container
    * @return type
    */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }
    
     
    /**
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testaccesstokeninvalidrequest" src/UserManager/Sonata/UserBundle/Tests/Controller/UserAccesstokenControllerTest.php
     */
    public function testaccesstokendisableduserrequest()
    {
        $reques_obj = '{"reqObj":{"client_id":"1_3jnvbt8en5kw0og08s8ocw0oo004wwcswwscocooc0cwo0g8ko11", "client_secret":"2xqb0ykndkowkscsggsk8kcoo0cko404480k0k4cko04owwswc","grant_type":"client_credentials",
"username":"sunil","password":"123456"}}';
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $action     = $baseUrl.'webapi/v1/getaccesstoken';
        $curl_object = new CurlTestCaseController();
        $response    = $curl_object->curlTestAction($reques_obj, $action); 
        $this->assertEquals(1040, $response->code);
    }
}
