<?php

// src/AppBundle/Command/GreetCommand.php

namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\IUtility;


class OfferNotificationsCommand extends ContainerAwareCommand implements ApplaneConstentInterface {

    private $_container;
    protected function configure() {
        $this
            ->setName('offer:notifications')
            ->setDescription('notification for 6 offers to users');
    }

    /**
     *  function that will execute on console command fophp app/console offer:notifications
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->_container = $this->getApplication()->getKernel()->getContainer();
        //set_time_limit(0);
        ini_set('memory_limit', '2048M');
        //set_error_handler(array($this, 'errorHandler'), E_ALL ^ E_NOTICE);
        try{
            $this->_log('Offers  getting process start');
            $postService = $this->_container->get('post_detail.service');
            $offersJson = $this->getOffers();
            $offers = json_decode($offersJson, true);
            if(!$offers){
                $this->_log('Invalid offers returned by applane: '. $offersJson);
            }
                      
            $coupons = !empty($offers['response']['coupons']['result']) ? $offers['response']['coupons']['result'] : array();
            $cards = !empty($offers['response']['cards']['result']) ? $offers['response']['cards']['result'] : array();
           
            if(count($coupons)>0 or count($cards)>0){
                $offers = array('coupons'=>$coupons, 'cards'=>$cards);
                $users = $this->getOfferReceivers();
                $shops = $this->getShopsDetail($offers);
                $usersByLanguage = $postService->getUsersByLanguage($users);
                foreach ($usersByLanguage as $lang=>$_users) {
                    $cats = $this->_getShopCategoriesForOffers($offers, $lang);
                    $postService->sendOfferNotifications($_users, $offers, $lang, $cats, $shops);
                }
                // Blocked offer updation on Applane for testing purpose. NEED TO UNCOMMENT IT BEFORE GOING LIVE
                $this->updateOffersOnApplane(array('coupons'=>$coupons, 'cards'=>$cards));
            }else{
                $this->_log('Email could not sent due to insufficient offers : (coupons: '.count($coupons).', cards: '.count($cards).')');
            }
        } catch (\Exception $e){
            $this->_log('Error ('. $e->getCode().'): '. $e->getMessage());
        }
    }

    public function getOffers() {
        $data = array();
        
       // $today = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));
        $todayMidnight = Utility::getDate(IUtility::DATE_FORMAT_ISO, IUtility::DATE_TODAY);
        $tomorrowMidnight = Utility::getDate(IUtility::DATE_FORMAT_ISO, IUtility::DATE_TOMORROW);
       
        $data['coupons'] = array(
            '$collection'=> self::OFFERS_COLLECTION,
            '$filter'=> array(
                'offer_type'=> self::OFFERS_TYPE_COUPONS,
                //'is_mail_sent'=>false,
                //'start_date'=>(object)array('$gte'=>$todayMidnight, '$lt'=>$tomorrowMidnight),
                'start_date'=>(object)array('$lt'=>$tomorrowMidnight),
                'end_date'=>(object)array('$gte'=>$todayMidnight),
                'is_deleted'=>array(
                    '$in'=>array(false,null)
                ),
                'shop_id.is_shop_deleted'=>array(
                    '$in'=>array(false,null)
                        )
            ),
            '$sort'=> array(
                '__history.__createdOn'=> -1
            ),
            '$limit'=> 3
        );
        
        $today = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y'))));

        $data['cards'] = array(
            '$collection'=> self::OFFERS_COLLECTION,
            '$filter'=> array(
                'offer_type'=> self::OFFERS_TYPE_CARDS,
                //'is_mail_sent'=>false,
                //'start_date'=>(object)array('$gte'=>$todayMidnight, '$lt'=>$tomorrowMidnight),
                 'start_date'=>(object)array('$lt'=>$tomorrowMidnight),
                'end_date'=>(object)array('$gte'=>$todayMidnight),
                'is_deleted'=>array(
                    '$in'=>array(false,null)
                ),
                'shop_id.is_shop_deleted'=>array(
                    '$in'=>array(false,null)
                        )
            ),
            '$sort'=> array(
                '__history.__createdOn'=> -1
            ),
            '$limit'=> 3
        );
        $queryData = json_encode($data);
        $api = self::QUERY_BATCH;
        $queryParam = self::URL_QUERY;
        
        return $this->_request($queryData, $api, $queryParam);
    }
    
    public function updateOffersOnApplane($offers) {
        $this->_log('Updating applane database');
        $data = array(
            '$collection'=> self::OFFERS_COLLECTION
            );
        foreach($offers['coupons'] as $coupon){
            $data['$update'][] = array(
                '_id'=>$coupon['_id'],
                '$set'=>array(
                    'is_mail_sent'=>true
                )
            );
        }
        
        foreach($offers['cards'] as $coupon){
            $data['$update'][] = array(
                '_id'=>$coupon['_id'],
                '$set'=>array(
                    'is_mail_sent'=>true
                )
            );
        }
        
        
        $queryData = json_encode($data);
        
        $api = self::QUERY_UPDATE;
        $queryParam = self::URL_UPDATE;
        
        return $this->_request($queryData, $api, $queryParam);
    }
    
    public function getOfferReceivers(){
        $result = array();
        try{
            $receiversLimit = $this->_container->getParameter('offer_receivers_limit');
            $userService = $this->_container->get('user_object.service');
            $result = $userService->getRandomUsers($receiversLimit);
            $this->_log('Offer receivers found : '.count($result));
        } catch (Exception $e){ 
            $this->_log('Error ('.$e->getCode().'): '. $e->getMessage());
        }
        return $result;
    }
    
    protected function _request($data, $api, $queryParam) {
        
        $response = $call_type = '';
        try {
            $call_type = $this->_container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {
        }
         
        if ($call_type == 'APPLANE') {
            try{
                $applane_user_token = $this->_container->getParameter('applane_user_token');
                $serviceUrl = $this->_container->getParameter('base_applane_url'). $api;
                $client = $this->_container->get('utility_curl_request.service');
                $this->_log('Applane url '. $serviceUrl);
                $this->_log('Applane access token '. $applane_user_token);
                $this->_log('Query params '.$queryParam.' : '. $data);
                $response =  $client->setUrl($serviceUrl)
                                    ->setRequestType('POST')
                                    ->setHeader('content-type', 'application/x-www-form-urlencoded')
                                    ->setParam('code', $applane_user_token)
                                    ->setParam($queryParam, $data)
                                    ->send()
                                    ->getResponse();
                $this->_log(self::SUCCESS." : ". $response);
            } catch(Exception $e){
                $this->_log(self::ERROR ." (".$e->getCode()."): ".$e->getMessage());
            }
        }else{
            $this->_log(self::ERROR .":  call_type != APPLANE");
        }
        return $response;
    }
        
    /**
     *  function for writting the logs
     * @param type $sMessage
     */
    public function _log($sMessage) {
        $monoLog = $this->_container->get('monolog.logger.offer_notifications');
        $monoLog->info($sMessage);
    }

    function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new \Exception($errstr, $errno);
    }
    
    private function _getShopCategoriesForOffers($offers, $lang){
        $results = array();
        try{
            $couponCats = array_map(function($offer){
                return isset($offer['shop_id']['category_id']) ? $offer['shop_id']['category_id']['_id'] : '';
            }, $offers['coupons']);
            $cardCats = array_map(function($offer){
                return isset($offer['shop_id']['category_id']) ? $offer['shop_id']['category_id']['_id'] : '';
            }, $offers['cards']);

            $catIds = array_filter(array_unique(array_merge($couponCats, $cardCats)));
            $this->_log('Getting category names for '. json_encode($catIds));
            $businessCatService = $this->_container->get('business_category.service');
            $results = $businessCatService->getBusinessCategoriesByLangAndIds($lang, $catIds);
            $this->_log('Found category names for '. json_encode($results));
            
           // print_r($catIds);
            
        }catch(\Exception $e){
            $this->_log("Exception [Business Category]: ". $e->getMessage());
        }
        return $results;
    }
    
    private function getShopsDetail($offers){
        $results = array();
        try{
            $couponShops = array_map(function($offer){
                return isset($offer['shop_id']['_id']) ? $offer['shop_id']['_id'] : '';
            }, $offers['coupons']);
            $cardShops = array_map(function($offer){
                return isset($offer['shop_id']['_id']) ? $offer['shop_id']['_id'] : '';
            }, $offers['cards']);

            $shopIds = array_filter(array_unique(array_merge($couponShops, $cardShops)));
            $this->_log('Getting shop details for '. json_encode($shopIds));
            $userService = $this->_container->get('user_object.service');
            $results = $userService->getMultiStoreObjectService($shopIds);
            $this->_log('Found shop details '. json_encode($results));
            
           // print_r($catIds);
            
        }catch(\Exception $e){
            $this->_log("Exception [Store]: ". $e->getMessage());
            $this->_log('Error ('. $e->getCode().'): '. $e->getTraceAsString());
        }
        return $results;
    }

}
