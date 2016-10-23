<?php

namespace Affiliation\AffiliationManagerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Utility\RequestHandlerBundle\Controller\CurlTestCaseController;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Tests\Controller\ApplaneIntegrationControllerTest;

class AffiliationControllerTest extends WebTestCase
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
     * URL: phpunit -c app/ --filter="testsendcitizenregistrationinvitationrequest" src/Affiliation/AffiliationManagerBundle/Tests/Controller/AffiliationControllerTest.php
     */
    public function testsendcitizenregistrationinvitationfirsttimerequest()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/sendaffiliationlinks?access_token='.$access_token;
        $data = ' {
        "reqObj":{
        "user_id":'.$user_id.',
        "to_emails":["shubham.jolly12345678@daffodilsw.com"],
        "affiliation_type":"1",
        "url":"http://45.33.28.108/sixthcontinent_angular/#/citizen_affiliation/30085/1"
        }
        }';
        
        $email_ids = array('shubham.jolly12345678@daffodilsw.com');
        $affiliation_type = 1;
        
        $affiliation_service = $this->getContainer()->get('affiliation_affiliation_manager.user');
        
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_response = json_decode($response);
        $mail_status = $service_response->data[0]->status;
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->getAlreadyAffiliatedUsers($user_id,$email_ids,$affiliation_type);
        
        $affiliated_users = $affiliated_users[0];
        $final_output = false;
        if($affiliated_users->getEmail() == $email_ids[0] && $affiliated_users->getStatus() == 0 && $affiliated_users->getCount() == 1 && $affiliated_users->getAffiliationType() == $affiliation_type && $mail_status == 1) {
            $final_output = true;
        }
        
        $this->assertTrue($final_output);
    }
    
    /**
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testsendcitizenregistrationinvitationrequest" src/Affiliation/AffiliationManagerBundle/Tests/Controller/AffiliationControllerTest.php
     */
    public function testsendalreadyinviteduserbutnotregisteredrequest()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/sendaffiliationlinks?access_token='.$access_token;
        $data = ' {
        "reqObj":{
        "user_id":'.$user_id.',
        "to_emails":["shubham.jolly12345678@daffodilsw.com"],
        "affiliation_type":"1",
        "url":"http://45.33.28.108/sixthcontinent_angular/#/citizen_affiliation/30085/1"
        }
        }';
        
        $email_ids = array('shubham.jolly12345678@daffodilsw.com');
        $affiliation_type = 1;
        
        $affiliation_service = $this->getContainer()->get('affiliation_affiliation_manager.user');
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->getAlreadyAffiliatedUsers($user_id,$email_ids,$affiliation_type);
        $previous_result = $affiliated_users[0];
        
        $previous_count = $previous_result->getCount();
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_response = json_decode($response);
        $mail_status = $service_response->data[0]->status;
        
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $after_affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->getAlreadyAffiliatedUsers($user_id,$email_ids,$affiliation_type);
        
        $after_affiliate = $after_affiliated_users[0];
        $after_affiliate_count = $after_affiliate->getCount();
        $final_output = false;
        if($after_affiliate_count == $previous_count + 1 && $mail_status == 1) {
            $final_output = true;
        }
        
        $this->assertTrue($final_output);
    }
    
    
    /**
     * Get access token disable user case
     * response code 101
     * URL: phpunit -c app/ --filter="testsendInvitedUserAlreadyRegisteredrequest" src/Affiliation/AffiliationManagerBundle/Tests/Controller/AffiliationControllerTest.php
     */
    public function testsendInvitedUserAlreadyRegisteredRequest()
    {
        $applane_integration = new ApplaneIntegrationControllerTest();
        $user_info = $applane_integration->getLoginUser();
        $access_token = $user_info['access_token'];
        $user_id = $user_info['user_id'];
        $baseUrl = $this->getContainer()->getParameter('symfony_base_url');
        $vat_number =  $this->getContainer()->getParameter('store_vat');
        $serviceUrl = $baseUrl . 'api/sendaffiliationlinks?access_token='.$access_token;
        $data = ' {
        "reqObj":{
        "user_id":'.$user_id.',
        "to_emails":["ankit.jain@daffodilsw.com"],
        "affiliation_type":"1",
        "url":"http://45.33.28.108/sixthcontinent_angular/#/citizen_affiliation/30085/1"
        }
        }';
        
        $email_ids = array('ankit.jain@daffodilsw.com');
        $affiliation_type = 1;
        
        $affiliation_service = $this->getContainer()->get('affiliation_affiliation_manager.user');
        
        $client = new CurlRequestService();
        $response = $client->send('POST', $serviceUrl, array(), $data)
                ->getResponse();

        $service_response = json_decode($response);
        $mail_status = $service_response->data[0]->status;
        
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->getAlreadyAffiliatedUsers($user_id,$email_ids,$affiliation_type);
        
        $user_affiliate = $affiliated_users[0];
        $affiliate_count = $user_affiliate->getCount();
        $affiliation_status = $user_affiliate->getStatus();
        $final_output = false;
        if($mail_status == 0 && $affiliation_status == 2) {
            $final_output = true;
        }
        
        $this->assertTrue($final_output);
    }
    
}
