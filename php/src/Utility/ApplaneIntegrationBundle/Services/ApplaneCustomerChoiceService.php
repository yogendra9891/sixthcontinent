<?php

namespace Utility\ApplaneIntegrationBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

// service method  class
class ApplaneCustomerChoiceService implements ApplaneConstentInterface {

    protected $em;
    protected $dm;
    protected $container;

    CONST FOLLOW = 'FOLLOW';
    CONST FAVOURITE = 'FAVOURITE';

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
        //$this->request   = $request;
    }

    /**
     * update customer choice on applane
     */
    public function updateCustomerChoice($filter) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [updateCustomerChoice]', array());
        try {
            $limit = $this->container->getParameter('appalne_record_fatch_limit');
        } catch (\Exception $ex) {
            $limit = 500;
        }
        try {
            $offset = $this->container->getParameter('appalne_record_fatch_offset');
        } catch (\Exception $ex) {
            $offset = 0;
        }
        $is_local_users = 1;
        if ($filter == self::FOLLOW) {
            do {
                $shop_followers = $this->getShopFollowers($offset, $limit); //get all shop followers
                if (count($shop_followers) > 0) {
                    foreach ($shop_followers as $shop_follower) {
                        $customer_choice_id = $this->getCustomerChoice($shop_follower);
                        if ($customer_choice_id == "0") {
                            $response = $this->prepareApplaneDataStoreFollow($shop_follower);
                            $applane_reponse = $this->shopChoiceCreate($response);
                        } else {
                            $data = $this->prepareApplaneDataFollowUpdate($shop_follower);
                            $applane_reponse = $this->shopChoiceUpdate($data);
                        }
                    }
                    $offset = $offset + $limit;
                } else {
                    $is_local_users = 0;
                }
            } while ($is_local_users);
        }
        if ($filter == self::FAVOURITE) {
            do {
                $shop_favs = $this->getShopFavs($offset, $limit); //get all shop favourite 
                if (count($shop_favs) > 0) {
                    foreach ($shop_favs as $shop_fav) {
                        $customer_choice_id = $this->getCustomerChoice($shop_fav);
                        if ($customer_choice_id == "0") {
                            $response = $this->prepareApplaneDataStoreFav($shop_fav);
                            $applane_reponse = $this->shopChoiceCreate($response);
                        } else {
                            $data = $this->prepareApplaneDataFavUpdate($shop_fav);
                            $applane_reponse = $this->shopChoiceUpdate($data);
                        }
                    }
                    $offset = $offset + $limit;
                } else {
                    $is_local_users = 0;
                }
            } while ($is_local_users);
        }

        exit('done');
    }

    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreFollow($data) {
        $id = $data['id'];
        $response_data = array(
            '_id' => (string) $id,
            'citizen_id' => (object) array('_id' => (string) $data['citizen_id']),
            'is_following' => true,
            'shop_id' => (object) array('_id' => (string) $data['shop_id']),
        );
        return $response_data;
    }

    /**
     * Create shop follow
     * @param type $data
     */
    public function shopChoiceCreate($data) {
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [shopChoiceCreate]', array());
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $collection = self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, $collection, $action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $this->__createLog('Request Object:' . $final_data, array());
        $this->__createLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [shopChoiceCreate]:' . $applane_resp, array());
    }

    /**
     * Get Shop Followers
     * @param array $shop_ids
     */
    public function getShopFollowers($offset, $limit) {
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getShopFollowers]', array());
        $em = $this->em;
        $shop_followers_array = array();
        $followers = array();
        $shop_followers_array = $em->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->findBy(array(), null, $limit, $offset);
        if ($shop_followers_array) {
            foreach ($shop_followers_array as $shop_followers_single) {
                $shop_id = $shop_followers_single->getShopId();
                $user_id = $shop_followers_single->getUserId();
                $data['shop_id'] = $shop_id;
                $data['citizen_id'] = $user_id;
                $data['id'] = $shop_id . "_" . $user_id;
                $followers[] = $data;
            }
        }
        $this->__createLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getShopFollowers] with response:' . Utility::encodeData($followers), array());
        return $followers;
    }

    /**
     * Get SHop followers
     * @return type
     */
    public function getCustomerChoice($data) {
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getCustomerChoice] with data' . Utility::encodeData($data), array());
        $data = $this->prepareGetCustomerChoiceInfoData($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $applane_resp = $applane_service->callApplaneService($data, $api, $queryParam);
        $this->__createLog('Customer choice request Object:' . $data, array());
        $this->__createLog('Customer choice list:' . $applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $cc_id = $this->getCustomerChoiceId($appalne_decode_resp);
        $this->__createLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getCustomerChoice] with data:' . $cc_id, array());
        return $cc_id;
    }

    /**
     * Get User Ids from applane
     * @param array $response
     * @return type
     */
    public function getCustomerChoiceId($response) {
        $applane_customer_choice_id = "0";
        $applane_shops = array();
        $final_data = array();
        if (count($response->response->result) > 0) {
            foreach ($response->response->result as $single_result) {
                $applane_customer_choice_id = (isset($single_result->_id)) ? $single_result->_id : 0;
            }
        }
        return $applane_customer_choice_id;
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.applane_shop_fav_follow_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetCustomerChoiceInfoData($data) {
        $id = $data['id'];
        $collection_data = self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object) array('_id' => (string) $id);

        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
        return json_encode($final_data);
    }

    /**
     * Update shop follow
     * @param type $data
     */
    public function shopChoiceUpdate($data) {
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [shopChoiceUpdate]', array());
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::SIX_CONTINENT_CUSTOMER_CHOICE, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $this->__createLog('Request Object:' . $final_data, array());
        $this->__createLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [shopChoiceUpdate]:' . $applane_resp, array());
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status) ? $appalne_decode_resp->status : 'error';
    }

    /**
     * Prepare applane data
     * @param type $data
     */
    public function prepareApplaneDataFollowUpdate($data) {
        $response_data = array(
            '_id' => (string) $data['id'],
            '$set' => array(
                'is_following' => true
        ));
        return $response_data;
    }

    /**
     * Get list of shop favs
     * @return string
     */
    public function getShopFavs($offset, $limit) {
        $this->__createLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getShopFavs]', array());
        $em = $this->em;
        $shop_favs_array = array();
        $favs = array();
        $shop_favs_array = $em->getRepository('StoreManagerStoreBundle:Favourite')
                ->findBy(array(), null, $limit, $offset);

        if ($shop_favs_array) {
            foreach ($shop_favs_array as $shop_favs_single) {
                $shop_id = $shop_favs_single->getStoreId();
                $user_id = $shop_favs_single->getUserId();
                $data['shop_id'] = $shop_id;
                $data['citizen_id'] = $user_id;
                $data['id'] = $shop_id . "_" . $user_id;
                $favs[] = $data;
            }
        }
        $this->__createLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\ApplaneCustomerChoiceService] and function [getShopFavs] with response:' . Utility::encodeData($favs), array());
        return $favs;
    }

    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreFav($data) {
        $id = $data['id'];
        $response_data = array(
            '_id' => (string) $id,
            'citizen_id' => (object) array('_id' => (string) $data['citizen_id']),
            'is_favourate' => true,
            'shop_id' => (object) array('_id' => (string) $data['shop_id']),
        );
        return $response_data;
    }

    /**
     * Prepare applane data
     * @param type $data
     */
    public function prepareApplaneDataFavUpdate($data) {
        $response_data = array(
            '_id' => (string) $data['id'],
            '$set' => array(
                'is_favourate' => true
        ));
        return $response_data;
    }

}
