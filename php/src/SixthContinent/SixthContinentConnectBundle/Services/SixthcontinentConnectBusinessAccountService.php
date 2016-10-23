<?php

namespace SixthContinent\SixthContinentConnectBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Symfony\Component\Locale\Locale;

// validate the data.like iban, vatnumber etc
class SixthcontinentConnectBusinessAccountService {

    protected $em;
    protected $dm;
    protected $container;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }

    /**
     * Create connect app log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.connect_app_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    /**
     * prepare the object of application business account
     * @param type $business_account_info
     */
    public function getBusinessAccountInfo($business_account_info) {
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectBusinessAccountService] function [getBusinessAccountInfo]');
        $app_secret = $business_account_info['application_secret'];
        $app_id  = $business_account_info['application_id'];
        $user_id = $business_account_info['user_id'];
        $repres_dob = ($business_account_info['repres_dob'] != NULL) ? $business_account_info['repres_dob'] : null;
        $created_at = ($business_account_info['created_at'] != NULL) ? $business_account_info['created_at'] : null;
        $is_active = $business_account_info['is_active'];
        $is_deleted = $business_account_info['is_deleted'];
        $profile_image = $business_account_info['profile_image'];
        $user_service = $this->container->get('user_object.service');
        $user_ids = array($user_id);
        //find the user info
        $users_object = $user_service->MultipleUserObjectService($user_ids);
        $user_info = isset($users_object[$user_id]) ? $users_object[$user_id] : null;
        
        //find profile images object
        $profile_images_object = $this->getApplicationCoverImage($profile_image);
        $cover_info = $this->ApplicationCoverImageArray();
        $cover_image = isset($profile_images_object[$app_id]) ? $profile_images_object[$app_id] : $cover_info;
        $country = $this->getCountry($business_account_info['business_country']);
        $result = $this->prepareApplicationBusinessAccount($business_account_info, $country, $repres_dob, $created_at, $user_info);
        $result['cover_image'] = $cover_image;
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectBusinessAccountService] function [getBusinessAccountInfo] with respponse: '. Utility::encodeData($result));
        return $result;
    }

    /**
     * prepare the application business account
     * @param array $business_account_info
     * @param string $country
     * @param object $repres_dob
     * @param object $created_at
     * @param array $user_info
     * @return array
     */
    public function prepareApplicationBusinessAccount($business_account_info, $country, $repres_dob, $created_at, $user_info) {
        $result = array(
            'app_id' => $business_account_info['application_id'],
            'app_name' => $business_account_info['application_name'],
            'app_url' => $business_account_info['application_url'],
            'email' => $business_account_info['email'],
            'description' => $business_account_info['description'],
            'name' => $business_account_info['name'],
            'business_name' => $business_account_info['business_name'],
            'business_type' => $business_account_info['business_type'],
            'business_country' => $country,
            'business_region' => $business_account_info['business_region'],
            'business_city' => $business_account_info['business_city'],
            'business_address' => $business_account_info['business_address'],
            'phone' => $business_account_info['phone'],
            'zip' => $business_account_info['zip'],
            'province' => $business_account_info['province'],
            'vat_number' => $business_account_info['vat_number'],
            'iban' => $business_account_info['iban'],
            'fiscal_code' => $business_account_info['fiscal_code'],
            'repres_fiscal_code' => $business_account_info['repres_fiscal_code'],
            'repres_first_name' => $business_account_info['repres_first_name'],
            'repres_last_name' => $business_account_info['repres_last_name'],
            'repres_place_of_birth' => $business_account_info['repres_place_of_birth'],
            'repres_dob' => $repres_dob,
            'repres_email' => $business_account_info['repres_email'],
            'repres_phone_number' => $business_account_info['repres_phone_number'],
            'repres_address' => $business_account_info['repres_address'],
            'repres_province' => $business_account_info['repres_province'],
            'repres_city' => $business_account_info['repres_city'],
            'repres_zip' => $business_account_info['repres_zip'],
            'created_at' => $created_at,
            'latitude' => $business_account_info['latitude'],
            'longitude' => $business_account_info['longitude'],
            'map_place' => $business_account_info['map_place'],
            'user_info' => $user_info
        );
        return $result;
    }
    /**
     * find the total revenue of the app
     * @param string $app_id
     * @return float $total_revenue
     */
    public function getAppTransactionRevenue($app_id) {
        $em = $this->em;
        $total_revenue = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                            ->getAppTransactionsRevenue($app_id);
        return $total_revenue;
    }

    /**
     * find the total count of transaction the app
     * @param string $app_id
     */
    public function getAppTransactionCount($app_id) {
        $em = $this->em;
        $count = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                    ->getAppTransactionCount($app_id);
        return $count;
    }

    /**
     * chck the application is exists
     * @param string $app_id
     */
    public function getApplicationProfile($app_id) {
        $em = $this->em;
        $app_data = $em->getRepository('SixthContinentConnectBundle:Application')
                       ->findOneBy(array('applicationId' => $app_id));
        if (!$app_data) {
            return false;
        }
        return $app_data;
    }

    /**
     * get country name form code
     * @param string $country
     * @return string
     */
    public function getCountry($country) {
        $cc_name = '';
        $countryLists = Locale::getDisplayCountries('en');
        //create country array
        if (array_key_exists($country, $countryLists)) {
            $country_name = array('name' => $countryLists[$country], 'code' => $country);
            $cc_name = $countryLists[$country];
        } else {
            $country_name = array('name' => $country, 'code' => '');
            $cc_name = $country;
        }
        return $cc_name;
    }

    /**
     * getting the applicationn business account
     * @param string $app_id
     * @return object
     */
    public function getApplicationBusinessAccount($app_id) {
        $em = $this->em;
        $app_business_data = $em->getRepository('SixthContinentConnectBundle:ApplicationBusinessAccount')
                                ->findOneBy(array('applicationId' => $app_id, 'isActive'=>1, 'isDeleted'=>0));
        if (!$app_business_data) {
            return false;
        }
        return $app_business_data;
    }
    
    /**
     * search the application
     * @param int $current_user_id
     * @param string $search_string
     * @param int $limit_start
     * @param int $limit_size
     */
    public function searchApplications($current_user_id, $search_string, $limit_start, $limit_size) {
        $em = $this->em;
        $user_service = $this->container->get('user_object.service');
        $connect_app_service = $this->_getSixcontinentAppService();
        $results = $em->getRepository('SixthContinentConnectBundle:Application')
                      ->searchApplication($search_string, $limit_start, $limit_size);
        $result_count = $em->getRepository('SixthContinentConnectBundle:Application')
                           ->searchApplicationCount($search_string);
        if ($result_count == 0) {
            return array('records'=>array(), 'count'=>0);
        }
        $users = array();
        $apps  = array();
        $profile_images = array();
        $search_data = array();
        foreach ($results as $result) {
            $users[] = $result['user_id'];
            $apps[]  = $result['application_id'];
            $profile_images[] = $result['profile_image'];
        }
        $users = Utility::getUniqueArray($users);
        $apps  = Utility::getUniqueArray($apps);
        $profile_images = Utility::getUniqueArray($profile_images);
        //find profile images object
        $profile_images_object = $this->getApplicationCoverImage($profile_images);
        
        //find the user info
        $users_object = $user_service->MultipleUserObjectService($users);
        if ($results) { //if we have records then need to fetch those transactions revenue and count
            $transaction_data = $this->applicationTransactionAmount($apps);
        }
        $data = $this->applicationInfo($results, $users_object, $current_user_id, $transaction_data, $profile_images_object);
        $final_result = array('records'=>$data, 'count'=>Utility::getIntergerValue($result_count));
        return $final_result;
    }
    
    /**
     * Prepare the application profile data
     * @param array $results
     * @param array $users_object
     * @param int $current_user_id
     * @param array $transaction_data
     * @return int
     */
    public function applicationInfo($results, $users_object, $current_user_id, $transaction_data, $profile_images_object) {
        $search_data = array();
        $connect_app_service = $this->_getSixcontinentAppService();
        $cover_info = $this->ApplicationCoverImageArray();
        foreach ($results as $business_account_info) {
            $is_owner = 0;
            $application_id = $business_account_info['application_id'];
            $user_id    = $business_account_info['user_id'];
            $repres_dob = ($business_account_info['repres_dob'] != NULL) ? $business_account_info['repres_dob'] : null;
            $created_at = ($business_account_info['created_at'] != NULL) ? $business_account_info['created_at'] : null;
            $user_info  = isset($users_object[$user_id]) ? $users_object[$user_id] : null;
            $is_active  = $business_account_info['is_active'];
            $is_deleted = $business_account_info['is_deleted'];
            $country = $this->getCountry($business_account_info['business_country']);
            if ($current_user_id == $user_id) {
                $is_owner = 1;
            }
            $account_data = $this->prepareApplicationBusinessAccount($business_account_info, $country, $repres_dob, $created_at, $user_info);
            $account_data['total_transaction'] = isset($transaction_data[$application_id]) ? Utility::getIntergerValue($transaction_data[$application_id]['transaction_count']) : 0;
            $account_data['total_transaction_amount'] = isset($transaction_data[$application_id]) ? $connect_app_service->changeRoundAmountCurrency(($transaction_data[$application_id]['revenue'])) : 0;
            $account_data['is_owner'] = $is_owner;
            $account_data['cover_image'] = isset($profile_images_object[$application_id]) ? $profile_images_object[$application_id] : $cover_info;
            $search_data[] = $account_data;
        }
        return $search_data;
    }
    
    /**
     * find the application transaction amount and count
     * @param array $app_ids
     */
    public function applicationTransactionAmount($app_ids) {
        $em = $this->em;
        $data = array();
        $revenue_result = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')
                             ->getAppTransactionsRevenueCount($app_ids);
        foreach ($revenue_result as $result) {
            $data[$result['application_id']] = array('revenue'=>$result['revenue'], 'transaction_count'=>$result['transaction_count']);
        }
        return $data;
    }
    
    /**
     * find the application cover images object
     * @param object|array|mixed $profile_images
     * @return array
     */
    public function getApplicationCoverImage($profile_images) {
        if (is_object($profile_images) === true) { // is an object convert to array
            $profile_images = (array)$profile_images;
        }
        if (is_array($profile_images) !== true) { //is not array convert to array
            $profile_images = (array)$profile_images;
        }
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        $dm = $this->dm;
        $images_result = array();
        $images = $dm->getRepository('SixthContinentConnectBundle:ApplicationCoverImg')->getApplicationimages($profile_images);
        foreach ($images as $image) {
            $app_id = $image->getAppId();
            $x = $image->getX();
            $y = $image->getY();
            $image_reference = $image->getImageName();
            $main_image  = $image_reference[0]; //I know that there will a single image for a cover
            $image_id = $main_image->getId();
            $image_name = $main_image->getMediaName();
            $type = $main_image->getType();
            $original_path = $aws_path . $this->container->getParameter('media_path') .$image_name;
            $thumb_path    = $aws_path . $this->container->getParameter('media_path_thumb') .$image_name;
            $images_result[$app_id] = array('id'=>$image_id, 'original'=>$original_path, 'thumb'=>$thumb_path, 'x_cord'=>$x, 'y_cord'=>$y, 'type'=>$type);
        }
        return $images_result;
    }
    
    public function ApplicationCoverImageArray() {
        return array('id'=>'', 'original'=>'', 'thumb'=>'', 'x_cord'=>'', 'y_cord'=>'', 'type'=>'');
    }
}
