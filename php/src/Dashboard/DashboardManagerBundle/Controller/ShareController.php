<?php

namespace Dashboard\DashboardManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Dashboard\DashboardManagerBundle\Utils\MessageFactory as Msg;
/**
 * Class for handling the posts on dashboard.
 */
class ShareController extends Controller {
    
    const SHOP = 'shop';
    const CLUB = 'club';
    const OFFER = 'offer';
    const SOCIAL_PROJECT = 'social_project';
    const VOUCHER = 'voucher'; //For TAMOIL
    const GENERIC_VOUCHER = 'genericvoucher';
    /**
     * Get shared object info
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetSharedObjectAction(Request $request)
    {
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\ShareController->GetSharedObject(Request)]');
        $data = array();
        $required_parameter = array('object_id', 'object_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [Dashboard\DashboardManagerBundle\Controller\ShareController] and function [GetSharedObject] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $object_type = Utility::getLowerCaseString($de_serialize['object_type']);
        $object_id = $de_serialize['object_id'];
        $user_id = $de_serialize['user_id'];
        switch($object_type){
            case self::SHOP:
                $shared_info = $this->getShopInfo($object_id);
                break;
            case self::CLUB:
                $shared_info = $this->getClubInfo($object_id);
                break;
            case self::OFFER:
                $shared_info = $this->getOfferInfo($object_id, $user_id);
                break;
            case self::SOCIAL_PROJECT:
                $shared_info = $this->getSocialProjectInfo($object_id);
                break;
            case self::VOUCHER:
                $shared_info = $this->getOfferInfo($object_id, $user_id);
                break;
            default:
                $resp_data = new Resp(Msg::getMessage(1130)->getCode(), Msg::getMessage(1130)->getMessage(), $data); //INVALID_OBJECT_TYPE
                $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->GetSharedObject] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);     
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $shared_info); //SUCCESS
        $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController] with response' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * Get shop info
     * @param int $shop_id
     * @return array
     */
    private function getShopInfo($shop_id)
    {   
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo]');
        $object_info = array();
        $user_service = $this->get('user_object.service');
        $object_info = $user_service->getStoreObjectService($shop_id);
        if(!$object_info){
            $resp_data = new Resp(Msg::getMessage(1132)->getCode(), Msg::getMessage(1132)->getMessage(), array()); //INVALID_OBJECT_ID
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $title = (isset($object_info['name'])) ? $object_info['name'] : '';
        $url = '';
        $pageUrl = '';
        $canonicalUrl = '';
        $images = (isset($object_info['thumb_path']) && $object_info['thumb_path'] != '') ? array($object_info['thumb_path']): null;
        $description = (isset($object_info['description'])) ? $object_info['description'] : '';
        $video = "no";
        $videoIframe = null;
        $response = $this->prepareResponseData($title, $url, $pageUrl, $canonicalUrl, $images, $description, $video, $videoIframe);
        $this->__createLog('[Exiting from Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response: '.Utility::encodeData($response));
        return $response;
    }

    /**
     * Get club info
     * @param string $club_id
     * @return array
     */
    private function getClubInfo($club_id)
    {
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\ShareController->getClubInfo]');
        $object_info = array();
        $post_service = $this->container->get('post_feeds.postFeeds');
        $club_ids = array($club_id);
        $clubs_info = $post_service->getMultiGroupObjectService($club_ids);
        $object_info = isset($clubs_info[$club_id]) ? $clubs_info[$club_id] : array();
        if(!$object_info){
            $resp_data = new Resp(Msg::getMessage(1132)->getCode(), Msg::getMessage(1132)->getMessage(), array()); //INVALID_OBJECT_ID
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $title = (isset($object_info['title'])) ? $object_info['title'] : '';
        $url = '';
        $pageUrl = '';
        $canonicalUrl = '';
        $images = (isset($object_info['profile_images']['thumb_path']) && $object_info['profile_images']['thumb_path'] != '') ? array($object_info['profile_images']['thumb_path']) : null ;
        $description = (isset($object_info['description'])) ? $object_info['description'] : '';
        $video = "no";
        $videoIframe = null;
        $response = $this->prepareResponseData($title, $url, $pageUrl, $canonicalUrl, $images, $description, $video, $videoIframe);
        $this->__createLog('[Exiting from Dashboard\DashboardManagerBundle\Controller\ShareController->getClubInfo] with response: '.Utility::encodeData($response));
        return $response;    
    }
    
    /**
     * Get offer info
     * @param string $offer_id
     * @return array
     */
    private function getOfferInfo($offer_id, $user_id)
    {  
        $shoppingcardImage = '';
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\ShareController->getOfferInfo]');
        $object_info = array();
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        
        /* Get offer detail */
        $em = $this->getDoctrine()->getManager();
        $OfferDetail = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                          ->find($offer_id);

        if(!$OfferDetail) {
            $resp_data = new Resp(Msg::getMessage(1132)->getCode(), Msg::getMessage(1132)->getMessage(), array()); //INVALID_OBJECT_ID
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }

        /* Get commercial promotion detail */
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $param = array(
            'buyer_id' => $user_id,
            'seller_id' => $OfferDetail->getsellerId(),
            'offer_id' => $offer_id
          );
        $object_info = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                          ->getCommercialPromotionDetail($param, $dm);

        if($object_info['result']['promotion_type']['promotionType'] == self::GENERIC_VOUCHER) {
          $shoppingcardImage = (isset($object_info['result']['extra_information']['landscape_image'])) ? $object_info['result']['extra_information']['landscape_image'] : '';
        } else {
          $shoppingcardImage = (isset($object_info['result']['extra_information']['images'][0])) ? $object_info['result']['extra_information']['images'][0]['thumb'] : '';
        }

        if(!$object_info){
            $resp_data = new Resp(Msg::getMessage(1132)->getCode(), Msg::getMessage(1132)->getMessage(), array()); //INVALID_OBJECT_ID
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //get user object
        $userService = $this->container->get('user_object.service');
        $user_obj = $userService->UserObjectService($user_id);
        $lang = 'it'; //Default
         if($user_obj){
             $lang = $user_obj['current_language'];
         }

        /* Get offer image param */
        $offerParam = array(
              'imageurl' => '',
              'shop_id' => $OfferDetail->getsellerId(),
              'shop_cat_id' => $object_info['result']['seller_information']['saleCatid']
        );

        /* Offer image */
        $offer_image = $this->getOfferImage($offerParam, $lang);
        if($shoppingcardImage != '') {
          $offer_image = $shoppingcardImage;
        } else {
          $offer_image = $offer_image;
        }
        
        $offer_name = (isset($object_info['result']['promotion_type']['description'])) ? trim($object_info['result']['promotion_type']['description']) : '';
        $shop_name = (isset($object_info['result']['seller_information']['businessName'])) ? $object_info['result']['seller_information']['businessName'] : '';
        $title = ($offer_name != "") ? $offer_name : $shop_name;
        //$title = (isset($object_info['shop_id']['name'])) ? $object_info['shop_id']['name'] : '';
        $url = '';
        $pageUrl = '';
        $canonicalUrl = '';
        $images = ($offer_image != '') ? array($offer_image) : null;
        $description = (isset($object_info['result']['extra_information']['description'])) ? $object_info['result']['extra_information']['description'] : '';
        $video = "no";
        $videoIframe = null;
        $response = $this->prepareResponseData($title, $url, $pageUrl, $canonicalUrl, $images, $description, $video, $videoIframe);
        $this->__createLog('[Exiting from Dashboard\DashboardManagerBundle\Controller\ShareController->getOfferInfo] with response: '.Utility::encodeData($response));
        return $response;        
    }
     
    /**
     * Get Social Project
     * @param string $project_id
     * @return array
     */
    private function getSocialProjectInfo($project_id)
    {
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\ShareController->getSocialProjectInfo]');
        $object_info = array();
        $post_service = $this->container->get('post_feeds.postFeeds');
        $object_info = $post_service->getMultipleSocialProjectObjects($project_id, false);
        if(!$object_info){
            $resp_data = new Resp(Msg::getMessage(1132)->getCode(), Msg::getMessage(1132)->getMessage(), array()); //INVALID_OBJECT_ID
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\ShareController->getShopInfo] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $title = (isset($object_info['project_title'])) ? $object_info['project_title'] : '';
        $url = '';
        $pageUrl = '';
        $canonicalUrl = '';
        $images = (isset($object_info['cover_img']['thum_image']) && $object_info['cover_img']['thum_image'] != '') ? array($object_info['cover_img']['thum_image']) : null;
        $description = (isset($object_info['project_desc'])) ? $object_info['project_desc'] : '';
        $video = "no";
        $videoIframe = null;
        $response = $this->prepareResponseData($title, $url, $pageUrl, $canonicalUrl, $images, $description, $video, $videoIframe);
        $this->__createLog('[Exiting from Dashboard\DashboardManagerBundle\Controller\ShareController->getSocialProjectInfo] with response: '.Utility::encodeData($response));
        return $response;   
    }
    
    
    /**
     * Prepare response
     * @param string $title
     * @param string $url
     * @param string $pageUrl
     * @param string $canonicalUrl
     * @param array $images
     * @param string $description
     * @param string $video
     * @param string $videoIframe
     * @return array
     */
    private function prepareResponseData($title, $url, $pageUrl, $canonicalUrl, $images, $description, $video, $videoIframe)
    {
        $data = array('title' => $title,
            'url' => $url,
            'pageUrl' => $pageUrl,
            'canonicalUrl' => $canonicalUrl,
            'images' => $images,
            'description' => $description,
            'video' => $video,
            'videoIframe' => $videoIframe
            );
         return $data;
    }
    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.share_post_log');
        $applane_service->writeAllLogs($handler, $monolog_req,$monolog_response);
        return true;
    }
    
    /**
     * Get Offer Image
     * @param array $offer_image
     */
    public function getOfferImage($card, $lang)
    {
        $userService = $this->container->get('user_object.service');
        $cardImg = isset($card['imageurl']) ? trim($card['imageurl']) : '';
        if($cardImg != ""){
            $cardImg_array = explode(",", $cardImg);
            $cardImg = $cardImg_array[0];
        }
        //check for shop image
        if($cardImg == '')
        {  
            //get shop id
            $shop_id = (isset($card['shop_id'])) ? $card['shop_id'] : 0;
            if($shop_id){
                //check for shop image
                 $shop = $userService->getStoreObjectService($shop_id);
                 $cardImg = isset($shop['thumb_path']) ? trim($shop['thumb_path']) : '';
                 if($cardImg == ''){
                  $catId = (isset($card['shop_cat_id'])) ? $card['shop_cat_id'] : 0;
                  if($catId){
                     //check for category image
                      $catIds = array($catId);
                      $businessCatService = $this->container->get('business_category.service');
                      $category = $businessCatService->getBusinessCategoriesByLangAndIds($lang, $catIds);
                      //get category image
                      $cardImg = isset($category[$catId]['image_thumb']) ? trim($category[$catId]['image_thumb']) : '';
                  }
                 }
            }
        }
        return $cardImg;
    }
    
}