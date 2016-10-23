<?php

namespace StoreManager\StoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;

class RestStoreV1ControllerTest extends WebTestCase {

    /**
     * Get COnatiner object
     * @return type
     */
    protected function getContainer() {
        $client = static::createClient();
        return $client->getContainer();
    }

    /**
     * Test case for create store
     * response code 101
     * URL: phpunit -c app/ --filter="testCreatestoreSuccess" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreSuccess() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"65032",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"02512353041161111",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(101, $response->code);
    }

    /**
     * Test case for create store when citizen not exist
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreCitizenNotExist" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreCitizenNotExist() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"6503211",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"02512353041161111",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1015, $response->code);
    }

    /**
     * Test case for create store when vat number not valid
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreVatNumberNotValid" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreVatNumberNotValid() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"6503211",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"02512353041161111",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1025, $response->code);
    }

    /**
     * Test case for create store when vat number not valid
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreIbanNumberNotValid" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreIbanNumberNotValid() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"6503211",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"02512353041161111",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1026, $response->code);
    }
    
    
    /**
     * Test case for create store when User account is not active
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreAccountNotActive" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreAccountNotActive() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"65032",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"02512353041161111",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1003, $response->code);
    }
    
    
    
    /**
     * Test case for create store when vat number not valid
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreInvalidDateFormat" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreInvalidDateFormat() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"65032",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"0251235304116114",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988032324sfdd05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1017, $response->code);
    }
    
    /**
     * Test case for create store when vat number not valid
     * response code 1003
     * URL: phpunit -c app/ --filter="testCreatestoreVatNumberExist" src/StoreManager/StoreBundle/Tests/Controller/RestStoreV1ControllerTest.php
     */
    public function testCreatestoreVatNumberExist() {
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $reques_obj = '{
"reqObj":{
"user_id":"65032",
"name":"daffo",
"business_name":"Business store by postman",
"description":"desc is here",
"email":"ankurshop@gmail.com",
"phone":"9891508595",
"legal_status":"ok",
"business_type":"test",
"business_country":"IN",
"business_region":"NCR",
"business_city":"NOIDA",
"business_address":"sec-30",
"zip":"12200",
"province":"HN",
"vat_number":"0251235304116114",
"iban":"IT51Y03069209484100000005893111",
"map_place":"gurgaon",
"latitude":"255",
"longitude":"255",
"referral_id":"149",
"call_type":"2",
"fiscal_code":"fiscal_code",
"sale_country":"sale_country",
"sale_region":"sale_region",
"sale_city":"sale_city",
"sale_province":"sale_province",
"sale_zip":"sale_zip",
"sale_email":"sale_email",
"sale_phone_number":"sale_phone_number",
"sale_catid":"sale_catid",
"sale_subcatid":"sale_subcatid",
"sale_description":"sale_description",
"sale_address":"sale_address",
"sale_map":"sale_map",
"repres_fiscal_code":"repres_fiscal_code",
"repres_first_name":"repres_first_name",
"repres_last_name":"repres_last_name",
"repres_place_of_birth":"repres_place_of_birth",
"repres_dob":"1988-03-05",
"repres_email":"repres_email",
"repres_phone_number":"repres_phone_number",
"repres_address":"repres_address",
"repres_province":"repres_province",
"repres_city":"repres_city",
"repres_zip":"repres_zip",
"shop_keyword":"shop_keyword"
}
}';
        $action = $baseUrl . 'api/v1/createstores?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg';
        $curl_object = new CurlTestCaseController();
        $response = $curl_object->curlTestAction($reques_obj, $action);
        $this->assertEquals(1027, $response->code);
    }

}
