<?php

namespace Utility\RatingBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
//use Utility\RatingBundle\Controller\StoreRatingController;

class DefaultControllerTest extends WebTestCase
{
    /**
     * test case for testing success of add rate
     */
    public function teststorepostratingsuccessAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
"type_id":"550bb2bcc03986f21c8b4567", "rate":3}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
     /**
     *  function for calling the curl request
     * @param type $remoteUrl
     * @param type $data
     * @return type
     */
    public function curlCall($remoteUrl,$data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);
        return $output;
    }
     /**
     * test case for  parameter miss of add rate
     */
    public function teststorepostratingparammissAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "types":"store_post",
"type_id":"550bb2bcc03986f21c8b4567", "rate":3}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    /**
     * test case for testing rating type of add rate
     */
    public function teststorepostratingtypeAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store",
"type_id":"550bb2bcc03986f21c8b4567", "rate":3}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
     /**
     * test case for testing rating value of add rate
     */
    public function teststorepostratingvalueAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
"type_id":"550bb2bcc03986f21c8b4567", "rate":6}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    
     /**
     * test case for testing rating value of add rate
     */
    public function teststorepostraterecordexistenceAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
        "type_id":"550bb2bcc03986f21c8b45", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
     /**
     * test case for testing already exist user
     */
    public function teststorepostrateuserexistAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
        "type_id":"550bb2bcc03986f21c8b4567", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    
     /**
     * test case for testing edit rate
     */
    public function teststoreposteditratesuccessAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/editsuccessrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
        "type_id":"550bb2bcc03986f21c8b4567", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
      /**
     * test case for testing edit rate param miss
     */
    public function testeditrateparammissAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/editrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "types":"store_post",
        "type_id":"550bb2bcc03986f21c8b4567", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
     /**
     * test case for edit rate rating type
     */
    public function testeditrateratingtypeAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/editrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_posts",
        "type_id":"550bb2bcc03986f21c8b4567", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    
     /**
     * test case for edit rate record exists
     */
    public function testeditraterecordexistsAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/editrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_posts",
        "type_id":"550bb2bcc03986f21c8b4567", "rate":4}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    /**
     * test case for edit rate rating value
     */
    public function testdeleteratesuccessAction(){
        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/deleterates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
        $data='{"reqObj":{"user_id":24017, "type":"store_post",
        "type_id":"550bb2bcc03986f21c8b4567"}}';
        $response=$this->curlcall($remoteUrl,$data);
        $this->assertEquals(101,$response->code);
    }
    
    
    
    
    
    
     /**
     * test case for testing addStorePostRate function success
     */
//    public function testaddstorepostratesuccessAction(){
//        $obj=new StoreRatingController();
//        $response= $obj->addStorePostRate('store_post','550bb2bcc03986f21c8b4567',4,24017);
//        echo "<pre>"; print_r($response); exit;
//        $remoteUrl='localhost/sixthcontinent_symfony/php/web/app_dev.php/api/addrates?access_token=NTE5YTBhOWMzMTYxNzZkYzUxN2NmOTcwMjc1ZDA1YmRkNWQ1YWI1YTA5NWFmMGM3MDMyYTY2NjlhM2QzYTBjZg';
//        $data='{"reqObj":{"user_id":24017, "type":"store_post",
//    "type_id":"550bb2bcc03986f21c8b4567", "rate":6}}';
//        $response=$this->curlcall($remoteUrl,$data);
//        $this->assertEquals(101,$response);
//     }
}
