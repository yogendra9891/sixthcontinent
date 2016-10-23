<?php

namespace PostFeeds\PostFeedsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Response;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;

/**
 * manage the tagging object information
 */
class PostFeedsTaggingController extends Controller {

    private $element_type = array('media', 'comment', 'post');

    CONST POST = 'POST';
    CONST COMMENT = 'COMMENT';
    CONST MEDIA = 'MEDIA';

    /**
     * get document manager object
     * @return object
     */
    private function _getDocumentManager() {
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * utility service
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }

    /**
     * post feed service
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds'); //PostFeeds\PostFeedsBundle\Services\PostFeedsService
    }

    /**
     * Get Media feed service
     * @return type
     */
    private function getMediaFeedService() {
        return $this->container->get('post_feeds.MediaFeeds'); //call media feed service
    }

    /**
     * function for finding the tagging for posts, comments, media, of users, shops, clubs.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetTaggingInfoAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postGetTaggingInfo]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id', 'element_type', 'element_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], $result['data']);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postGetTaggingInfo] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $element_type = $data['element_type'];
        $element_id = $data['element_id'];

        //check if passed element type is valid element type
        $allowed_element_type = $this->element_type;
        //check if element_type is invalid 
        if (!in_array($element_type, $allowed_element_type)) {
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postGetTaggingInfo] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }

        $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));

        switch ($element_type_const) {
            case self::POST:
                $response = $this->postTaggingInfo($data);
                break;
            case self::MEDIA:
                $response = $this->mediaTaggingInfo($data);
                break;
            case self::COMMENT:
                $response = $this->commentTaggingInfo($data);
                break;
        }
        //out put the response             
        Utility::createResponse($response);
    }

    /**
     * Get Post Rating
     * @param type $data
     * @return array
     */
    public function postTaggingInfo($data) {
        $post_id = $data['element_id'];
        $user_id = $data['user_id'];
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postTaggingInfo]', array());
        $post_obj = $postFeedsService->getPostObject($post_id); //get post object

        if (!$post_obj) {
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array()); //NO_POST_EXIST
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postTaggingInfo] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        $all_tagged_users   = $postFeedsService->getCommentEntityObject(array($post_obj)); //get entity objects
        $user_ids = $all_tagged_users['users'];
        $shop_ids = $all_tagged_users['shops'];
        $club_ids = $all_tagged_users['clubs'];
        $objects_info = $this->getObjects($user_ids, $shop_ids, $club_ids);
        $user_object = $objects_info['users'];
        $shop_object = $objects_info['shops'];
        $club_object = $objects_info['clubs'];
        $response = $this->getTaggingCommonInfo($post_obj, $user_id, $user_object, $shop_object, $club_object);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postTaggingInfo] with response: ' . (string) $response);
        return $response;
    }

    /**
     * Get Comment tagging info
     * @param type $data
     * @return array 
     */
    public function commentTaggingInfo($data)
    {
        $comment_id = $data['element_id'];
        $user_id    = $data['user_id'];
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [commentTaggingInfo]', array());
        //get mongo doctrine object
        $dm = $this->_getDocumentManager();
        //get media object
        $comment_obj = $dm->getRepository('PostFeedsBundle:PostFeeds')
                          ->getSingleComment($comment_id); //$element_id 
        
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1116)->getCode(), Msg::getMessage(1116)->getMessage(), array()); //NO_COMMENT_FOUND
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [commentTaggingInfo] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
         }
        $all_tagged_users   = $postFeedsService->getCommentEntityObject(array($comment_obj)); //get entity objects
        $user_ids = $all_tagged_users['users'];
        $shop_ids = $all_tagged_users['shops'];
        $club_ids = $all_tagged_users['clubs'];
        $objects_info = $this->getObjects($user_ids, $shop_ids, $club_ids);
        $user_object = $objects_info['users'];
        $shop_object = $objects_info['shops'];
        $club_object = $objects_info['clubs'];
        $response = $this->getTaggingCommonInfo($comment_obj, $user_id, $user_object, $shop_object, $club_object);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [postTaggingInfo] with response: ' . (string) $response);
        return $response;
    }
    
    /**
     * getting media tagging info
     * @param type $data
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function mediaTaggingInfo($data)
    {
        $media_id = $data['element_id'];
        $user_id = $data['user_id'];
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [mediaTaggingInfo]', array());
        $media_obj = $mediaFeedsService->getMediaObject($media_id);//get media object
        if(!$media_obj){
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO_MEDIA_FOUND
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [mediaTaggingInfo] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        $all_tagged_users   = $postFeedsService->getCommentEntityObject(array($media_obj)); //get entity objects
        $user_ids = $all_tagged_users['users'];
        $shop_ids = $all_tagged_users['shops'];
        $club_ids = $all_tagged_users['clubs'];
        $objects_info = $this->getObjects($user_ids, $shop_ids, $club_ids);
        $user_object = $objects_info['users'];
        $shop_object = $objects_info['shops'];
        $club_object = $objects_info['clubs'];
        $response = $this->getTaggingCommonInfo($media_obj, $user_id, $user_object, $shop_object, $club_object);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsTaggingController] and function [mediaTaggingInfo] with response: ' . (string) $response);
        return $response;
    }
    
    /**
     * get objects of entities
     * @param array $user_ids
     * @param array $shop_ids
     * @param array $club_ids
     * @return array  
     */
    public function getObjects($user_ids, $shop_ids, $club_ids) {
        $postFeedsService = $this->getPostFeedsService();
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        return array('users'=>$user_objects, 'shops'=>$shop_objects, 'clubs'=>$club_objects);
    }
    
   /**
    * finding the tagged entity objects
    * @param object $entity_obj
    * @param int $user_id
    * @param array $user_object
    * @param array $shop_object
    * @param array $club_object
    * @return \Utility\UtilityBundle\Utils\Response
    */
    public function getTaggingCommonInfo($entity_obj, $user_id, $user_object, $shop_object, $club_object)
    {
        $postFeedsService  = $this->getPostFeedsService();
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [getTaggingCommonInfo]', array());
        $tagg_obj = $postFeedsService->getTagObjects($entity_obj, $user_object, $shop_object, $club_object);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $tagg_obj);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [getTaggingCommonInfo] with msg :' . (string) $resp_data, array());
        return $resp_data;
    }
}
