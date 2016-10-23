<?php

namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\IUtility;
use Utility\UtilityBundle\Utils\Utility;

class CardSoldNotificationsCommand extends ContainerAwareCommand implements ApplaneConstentInterface {

    private $_container;

    protected function configure() {
        $this
                ->setName('cardsold:notifications')
                ->setDescription('notification for seller about sold cards to approve');
    }

    /**
     *  function that will execute on console command fophp app/console ci:notification
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        $this->_container = $this->getContainer();
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        set_error_handler(array($this, 'errorHandler'), E_ALL ^ E_NOTICE);
        $is_send = 1;
        try {
            $limit = $this->getContainer()->getParameter('appalne_record_fatch_limit');
        } catch (\Exception $ex) {
            $limit = 500;
        }
        try {
            $skip = $this->getContainer()->getParameter('appalne_record_fatch_offset');
        } catch (\Exception $ex) {
            $skip = 0;
        }

        $is_applane_data = 1;

        $url_query = self::CARD_SOLD_TODAY_BY_SHOP_OWNER;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        //get service for getting the admin id(loads in Utility/ApplaneIntegrationBundle/Resources/config/services.yml)
        $admin_service = $this->getContainer()->get('user.admin');
        //get admin id 
        $admin_id = $admin_service->getAdminId();
        $user_service = $this->getContainer()->get('user_object.service');
        $sender_data = $user_service->UserObjectService($admin_id);
        $postService = $this->getContainer()->get('post_detail.service');
        $users_array = array();
        $info = array();
        $store_array = array();
        $postService = $this->_container->get('post_detail.service');
        $user_service = $this->getContainer()->get('user_object.service');

        do {
            $users_array = array();
            $shop_owner_card_info = array();
            $shop_owner_purchase_card_info = array();
            $info = array();
            $final_data = $this->getCardsInfo($limit, $skip);
            $this->_log("[Parameters for the Query that hits the applane is :" . json_encode($final_data));
            $this->_log('sending the notification to shop owners from:' . $skip . ',to:' . ($skip + $limit));
            $skip = $skip + $limit;
            $applane_resp = $applane_service->callApplaneServiceWithParams($url_query, $final_data);
            $this->_log("[Data from applane is : $applane_resp]");
            $data_appalne = json_decode($applane_resp);

            $data_appalnes = isset($data_appalne->response) ? $data_appalne->response : (object) array();

            try{
                if (count($data_appalnes) > 0) {
                foreach ($data_appalnes as $data_appalne) {
                    if (isset($data_appalne->shopowner->_id)) {
                        $users_array[] = $data_appalne->shopowner->_id;
                        $shop_owner_card_info[$data_appalne->shopowner->_id] = $this->prepareShopOwnerData($data_appalne);
                        $shop_owner_purchase_card_info[$data_appalne->shopowner->_id] = $this->prepareShopOwnerPurchaseCardData($data_appalne);
                        $total_credit[$data_appalne->shopowner->_id] = $data_appalne->total_sold_cards_value;
                    }
                }
                $users_data = $user_service->MultipleUserObjectService($users_array);
                if (count($users_data) > 0) {
                    try {
                        $postService->sendCardSoldNotifications($users_data, $shop_owner_card_info, $shop_owner_purchase_card_info, $total_credit);
                    } catch (\Exception $ex) {
                        $this->_log("Expection occure:" . json_encode($ex->getMessage()));
                    }
                }
            } else {
                $is_applane_data = 0;
                $this->_log("[No responce from appalne : Job is completed]");
            }
            } catch (\Exception $ex) {

            }
            
        } while ($is_applane_data);
    }

    /**
     * prepare the 100 % shopping card data for a shop owner
     * @param type $data_appalne
     * @return type
     */
    public function prepareShopOwnerData($data_appalne) {

        $card_purchased = isset($data_appalne->children) ? $data_appalne->children : array();
        $card_data = array();
        $i = 0;
        foreach ($card_purchased as $card) {
            $card_data[$i]['card_number'] = isset($card->card_no) ? $card->card_no : '';
            $card_data[$i]['card_value'] = isset($card->credit) ? $card->credit : 0;
            $card_data[$i]['shop_name'] = isset($card->shop_id->name) ? $card->shop_id->name : '';
            $i++;
        }
        return $card_data;
    }

    /**
     * prepare the 100 % shopping card  Ci used data for a shop owner
     * @param type $data_appalne
     * @return type
     */
    public function prepareShopOwnerPurchaseCardData($data_appalne) {
        $purchase_cards = isset($data_appalne->purchase_cards) ? $data_appalne->purchase_cards : array();
        $purchase_card_data = array();
        $card_data = array();
        $i = 0;
        foreach ($purchase_cards as $card) {
            $card_data[$i]['card_number'] = isset($card->card_no) ? $card->card_no : '';
            $card_data[$i]['card_value'] = isset($card->credit) ? $card->credit : 0;
            $card_data[$i]['shop_name'] = isset($card->shop_id->name) ? $card->shop_id->name : '';
            $i++;
        }
        return $card_data;
    }

    /**
     *  prepare appalane data for the card sold info
     * @param type $limit
     * @param type $skip
     * @return type
     */
    private function getCardsInfo($limit, $skip) {

        $query_params = array();
        $query_params['limit'] = $limit;
        $query_params['skip'] = $skip;
        return $query_params;
    }

    protected function _request($data, $api, $queryParam) {

        $response = $call_type = '';
        try {
            $call_type = $this->_container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {
            
        }

        if ($call_type == 'APPLANE') {
            try {
                $applane_user_token = $this->_container->getParameter('applane_user_token');
                $serviceUrl = $this->_container->getParameter('base_applane_url') . $api;
                $client = $this->_container->get('utility_curl_request.service');
                $this->_log('Request applane url :' . $serviceUrl . '?code=' . $applane_user_token . '&' . $queryParam . '=' . $data);
                $response = $client->setUrl($serviceUrl)
                        ->setRequestType('POST')
                        ->setHeader('content-type', 'application/x-www-form-urlencoded')
                        ->setParam('code', $applane_user_token)
                        ->setParam($queryParam, $data)
                        ->send()
                        ->getResponse();
                $this->_log(self::SUCCESS . " : " . $response);
            } catch (Exception $e) {
                $this->_log(self::ERROR . " (" . $e->getCode() . "): " . $e->getMessage());
            }
        } else {
            $this->_log(self::ERROR . ":  call_type != APPLANE");
        }
        return $response;
    }

    /**
     *  function for writting the logs
     * @param type $sMessage
     */
    public function _log($sMessage) {
        $monoLog = $this->_container->get('monolog.logger.cardsold_notifications');
        $monoLog->info($sMessage);
    }

    function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new \Exception($errstr, $errno);
    }

}
