<?php

namespace PostFeeds\PostFeedsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Response;
use PostFeeds\PostFeedsBundle\Document\SocialProject;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;
use PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg;
use PostFeeds\PostFeedsBundle\Document\SocialProjectAddress;
use PostFeeds\PostFeedsBundle\Document\MediaFeeds;

class PostFeedsMediaController extends Controller {

    private $element_type = array('media');
    CONST COMMENT_SIZE = 2;
    CONST POST_MEDIA = 'POST_MEDIA';
    CONST COMMENT_MEDIA = 'COMMENT_MEDIA';
    CONST MEDIA = 'MEDIA';
    /**
     * function for adding the tagging for user,store,clubs on the media
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAddFeedTagAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction]', array());
        $utilityService = $this->getUtilityService();
        
        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');
        
        $requiredParams = array('user_id','element_type','element_id','tagging');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){  
            $resp_data = new Resp($result['code'], $result['message'], $result['data']);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $element_type = $data['element_type'];
        $element_id = $data['element_id'];
        $tagging = isset($data['tagging']) ? $data['tagging'] : array() ;
        
        //check if passed element type is valid element type
        $allowed_element_type = $this->element_type;
        //check if element_type is invalid 
        if (!in_array($element_type, $allowed_element_type)) {
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        $nToTagged = array();
        $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));
            //Match the case 
            switch ($element_type_const) {
                case 'MEDIA':
                    $mediaService = $this->getMediaFeedService();
                    $media_obj = $mediaService->getMediaObject($element_id);
                    if($media_obj == false) {
                        $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array());
                        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with msg :' . (string) $resp_data, array());
                        Utility::createResponse($resp_data);
                    }
                    //check if user is the media upload owner
                    if($user_id != $media_obj->getUserId()) {
                        $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
                        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with msg :' . (string) $resp_data, array());
                        Utility::createResponse($resp_data);
                    }
                    $existingTagged['club'] = $media_obj->getTagClub();
                    $existingTagged['user'] = $media_obj->getTagUser();
                    $existingTagged['shop'] = $media_obj->getTagShop();
                    $nToTagged['club'] = array_diff($tagging['club'], is_array($existingTagged['club']) ? $existingTagged['club'] : array());
                    $nToTagged['user'] = array_diff($tagging['user'], is_array($existingTagged['user']) ? $existingTagged['user'] : array());
                    $nToTagged['shop'] = array_diff($tagging['shop'], is_array($existingTagged['shop']) ? $existingTagged['shop'] : array());
                    
                    /** call for tagging function * */
                    $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
                    $feeds_tag = $post_feeds_service_obj->manageTagging($media_obj, $tagging);
                    break;
            }
            try{
                $dm->persist($feeds_tag);
                $dm->flush();
                $nFeedService = $this->container->get('post_feeds.notificationFeeds');
                $nFeedService->mediaFeedTagNotification($nToTagged, $user_id, $element_id, $element_type);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            } catch (\Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [postAddFeedTagAction] with exception :' . $ex->getMessage(), array());
                Utility::createResponse($resp_data);
            }
    }
    
    /**
     * Get Media Details
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetMediaDetailAction(Request $request)
    {
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [GetMediaDetail]', array());
        $utilityService = $this->getUtilityService();
        $data = array(); //initialize
        $required_parameter = array('user_id', 'media_id',);
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [GetMediaDetail] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        //get media object
        $media_id = $data['media_id'];
        $user_id = $data['user_id'];
        //get project id from media 
        $project_obj = $mediaFeedsService->getProjectExistsByMediaId($media_id);
       
        if($project_obj){
            //check if social project is deleted
            $social_project_id = $project_obj->getId();
            $social_project_service = $this->getPostFeedsSocialProjectService();
            $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkSocialProject] with social project id: '. $social_project_id);
            //fetch not deleted project
            $result = $social_project_service->checkSocialProject($social_project_id);
           if (!$result) {
                $resp_data = new Resp(Msg::getMessage(1104)->getCode(), Msg::getMessage(1104)->getMessage(), array()); //SOCIAL_PROJECT_DOES_NOT_EXISTS
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkSocialProject] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }

        $media_obj = $mediaFeedsService->getMediaObject($media_id);
        if(!$media_obj){
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO MEDIA FOUND
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [GetMediaDetail] with response :' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $media_objs = array($media_obj);
        $media_image = $mediaFeedsService->getGalleryMedia($media_objs); //get media images
        $media_comments = $mediaFeedsService->getMediaComment($media_obj); //get media comments
        
        //get Media tagged users
        $media_tags = $mediaFeedsService->getMediaTaggedInfo($media_objs); //get media tagged users
        $comment_tags = $mediaFeedsService->getCommentTaggedInfo($media_comments); //get comment tagged users
        $all_tagged_users = array_merge_recursive($media_tags, $comment_tags); //merge the tagged users
        $user_ids = $all_tagged_users['users'];
        $shop_ids = $all_tagged_users['shops'];
        $club_ids = $all_tagged_users['clubs'];
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        $comment_count = count($media_comments); //get total media count
        $comments_array = $mediaFeedsService->getCommentsBySize($media_comments, $user_objects, $shop_objects, $club_objects, $user_id); //get rcecent comments
        $media_tag_obj = $postFeedsService->getTagObjects($media_obj, $user_objects, $shop_objects, $club_objects); //get tagged entity objects
        $media_rated_users = $postFeedsService->getRatedUsers($media_obj, $user_id, $user_objects, $shop_objects, $club_objects); //get rated users
        //prepare media response object        
        $media_final_obj = $mediaFeedsService->prepareMediaData($media_rated_users, $media_tag_obj, $media_obj, $comments_array, $comment_count);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $media_final_obj);
        $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [GetMediaDetail] with msg :' . (string) $resp_data, array());
        Utility::createResponse($resp_data);
    }
    
    
    /**
     * Get Media Details
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postDeleteMediaAction(Request $request)
    {
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [DeleteMedia]', array());
        $utilityService = $this->getUtilityService();
        $data = array(); //initialize
        $required_parameter = array('user_id', 'media_id', 'item_id', 'item_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [DeleteMedia] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
        $item_type = $de_serialize['item_type'];
        $item_type = Utility::getLowerCaseString($item_type);
        $post_type_check = array(Utility::getLowerCaseString(self::POST_MEDIA), Utility::getLowerCaseString(self::COMMENT_MEDIA), Utility::getLowerCaseString(self::MEDIA));
        if (!in_array(Utility::getLowerCaseString($item_type), $post_type_check)) {
            $resp_data = new Resp(Msg::getMessage(1114)->getCode(), Msg::getMessage(1114)->getMessage(), array()); //ITEM_TYPE_IS_INVALID
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
         switch($item_type){
           CASE Utility::getLowerCaseString(self::POST_MEDIA):
               $this->deletePostMedia($de_serialize);
               break;
           CASE Utility::getLowerCaseString(self::COMMENT_MEDIA):
               $this->deleteCommentMedia($de_serialize);
               break;
            CASE Utility::getLowerCaseString(self::MEDIA):
               $this->deleteMedia($de_serialize);
               break;
        }
        return true;
    }
    
    /**
     * Delete Post Media
     * @param array $de_serialize
     */
    public function deletePostMedia($de_serialize)
    {
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia]', array());
        $item_id = $de_serialize['item_id'];
        $media_id = $de_serialize['media_id'];
        $data = array();
        $post_obj = $postFeedsService->getPostObject($item_id);
        if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), $data); //NO_POST_EXIST
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $media_obj = $mediaFeedsService->getMediaObject($media_id);
        if($media_obj == false) {
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO_MEDIA_FOUND
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
       $media_obj = $mediaFeedsService->deleteMedia($post_obj, $media_obj, $de_serialize);
       if($media_obj == false){
           $resp_data = new Resp(Msg::getMessage(1115)->getCode(), Msg::getMessage(1115)->getMessage(), array()); //ERROR_IN_REMOVE
           $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with response: ' . (string)$resp_data);
           Utility::createResponse($resp_data);
       }
       $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array()); //SUCCESS
       $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with response: ' . (string)$resp_data);
       Utility::createResponse($resp_data);
    }
    
    
    /**
     * Delete Comment Media
     * @param array $de_serialize
     */
    public function deleteCommentMedia($de_serialize)
    {
        $dm = $this->_getDocumentManager();
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deleteCommentMedia]', array());
        $comment_id = $de_serialize['item_id'];
        $media_id = $de_serialize['media_id'];
        $data = array();
        $comment_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->getSingleComment($comment_id);
      
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1116)->getCode(), Msg::getMessage(1116)->getMessage(), $data); //COMMENT_NOT_FOUND
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $media_obj = $mediaFeedsService->getMediaObject($media_id);
        if($media_obj == false) {
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO_MEDIA_FOUND
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
       $media_obj = $mediaFeedsService->deleteMedia($comment_obj, $media_obj, $de_serialize);
       if($media_obj == false){
           $resp_data = new Resp(Msg::getMessage(1115)->getCode(), Msg::getMessage(1115)->getMessage(), array()); //ERROR_IN_REMOVE
           $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with response: ' . (string)$resp_data);
           Utility::createResponse($resp_data);
       }
       $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array()); //SUCCESS
       $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deletePostMedia] with response: ' . (string)$resp_data);
       Utility::createResponse($resp_data);
    }
    
    
    /**
     * Delete Media
     * @param array $de_serialize
     */
    public function deleteMedia($de_serialize)
    {
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deleteMedia]', array());
        $item_id = $de_serialize['item_id'];
        $media_id = $de_serialize['media_id'];
        $user_id = $de_serialize['user_id'];
        $data = array();
       
        $media_obj = $mediaFeedsService->deleteMediaObject($media_id, $user_id);
        if($media_obj == false) {
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO_MEDIA_FOUND
            $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deleteMedia] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
       $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array()); //SUCCESS
       $mediaFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsMediaController] and function [deleteMedia] with response: ' . (string)$resp_data);
       Utility::createResponse($resp_data);
    }
    
    
    private function _getDocumentManager(){
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService(){
        return $this->container->get('store_manager_store.storeUtility');
    }
    
    /**
     * 
     * @return type
     */
    protected function getPostFeedsService(){
        return $this->container->get('post_feeds.postFeeds');
    }
    
    /**
     * Get Media feed service
     * @return type
     */
    private function getMediaFeedService() {
        return $this->container->get('post_feeds.MediaFeeds'); //call media feed service
    } 
    
    /**
     * get post feed service
     * @return type
     */
    protected function getPostFeedsSocialProjectService() {
        return $this->container->get('post_feeds.socialProjects'); //PostFeeds\PostFeedsBundle\Services\PostFeedsService
    }
    
    

}
