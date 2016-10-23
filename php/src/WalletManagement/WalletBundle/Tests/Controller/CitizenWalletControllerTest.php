<?php

namespace WalletManagement\WalletBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CitizenWalletControllerTest extends WebTestCase {

    /**
     * Test case for citizen wallet
     */
    public function testpostCitizenwalletsAction() {

        $data = '{
       "reqObj":{
        "user_id":"18867",
        "shots_needed":"1",
        "purchase_card_needed":"1",
        "momosy_card_needed":"1",
        "total_credit_available_needed":"1",
        "total_citizen_income_needed":"1",
        "discount_position_needed":"1",
        "purchase_card_limit_start":"0",
        "purchase_card_limit_size":"2",
        "shots_card_limit_start":"0",
        "shots_card_limit_size":"1",
        "momosy_card_limit_start":"0",
        "momosy_card_limit_size":"7"
    }
}';
        $client = static::createClient();
        $client->request('POST', '/api/citizenwallets?access_token=65018_ZjZhNGZmZjcyZGExNGYxYTIzYTczMjk4NDJjZjUzNWQ1MDlhNjRmOTA1YjM0ZGM0YTQyYzc2MjA2YjMyNTYwMg', array(), array(), array(), $data
        );
        $response = $client->getResponse()->getContent();
        $response_decode = json_decode($response);
        $this->assertEquals(101, $response_decode->code);
    }

}
