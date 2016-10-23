<?php

namespace Utility\MasterDataBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use SixthContinent\SixthContinentConnectBundle\Model\SixthcontinentConnectConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

class MasterDataControllerTest extends WebTestCase {

    /**
     * test case for checking the valid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidCountryCodeSuccess" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidCountryCodeSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('valid_country_code_caps');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidCountryCode($language_code);

        $this->assertTrue($valid_language_code);
    }
    
    /**
     * test case for checking the valid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidCountryCodeCaseInsensitiveSuccess" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidCountryCodeCaseInsensitiveSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('valid_country_code_small');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidCountryCode($language_code);

        $this->assertTrue($valid_language_code);
    }
    
    /**
     * test case for checking the invalid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidCountryCodeInvalid" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidCountryCodeInvalid() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('invalid_country_code');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidCountryCode($language_code);

        $this->assertFalse($valid_language_code);
    }
    
    /**
     * test case for checking the invalid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidLanguageCodeSuccess" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidLanguageCodeSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('valid_country_code_caps');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidLanguageCode($language_code);

        $this->assertEquals($language_code,$valid_language_code);
    }
    
    /**
     * test case for checking the invalid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidLanguageCodeCaseInsensitiveSuccess" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidLanguageCodeCaseInsensitiveSuccess() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('valid_country_code_small');
        $language_code_caps = $this->getContainer()->getParameter('valid_country_code_caps');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidLanguageCode($language_code);

        $this->assertEquals($language_code_caps,$valid_language_code);
    }
    
    /**
     * test case for checking the invalid language code Success
     * response code 101
     * URL: phpunit -c app/ --filter="testCheckValidLanguageCodeInvalid" src/Utility/MasterDataBundle/Tests/Controller/MasterDataControllerTest.php
     */
    public function testCheckValidLanguageCodeInvalid() {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $language_code = $this->getContainer()->getParameter('invalid_country_code');
        $default_language_code = $this->getContainer()->getParameter('default_language_code');
        
        $masterdata_service = $this->getContainer()->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidLanguageCode($language_code);

        $this->assertEquals($default_language_code,$valid_language_code);
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
