<?php

namespace PostFeeds\PostFeedsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use PostFeeds\PostFeedsBundle\Document\PostFeeds;
use Symfony\Component\HttpFoundation\Response;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class CommentFeedsController extends Controller {
    
    CONST POST_COMMENT = 'POST_COMMENT';
    CONST MEDIA_COMMENT = 'MEDIA_COMMENT';
    CONST ALLOW_GROUP = 15;
    /**
     * Edit Comment
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postEditCommentAction(Request $request) 
    {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editComment]', array());
        $utilityService = $this->getUtilityService();
        $data = array(); //initialize
        $required_parameter = array('user_id', 'item_id', 'comment_id', 'comment_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
        $de_serialize['description'] = (isset($de_serialize['description'])) ? Utility::getTrimmedString($de_serialize['description']) : "";
        $comment_type = Utility::getLowerCaseString($de_serialize['comment_type']);
        $post_type_check = array(Utility::getLowerCaseString(self::POST_COMMENT), Utility::getLowerCaseString(self::MEDIA_COMMENT));
        if (!in_array(Utility::getLowerCaseString($comment_type), $post_type_check)) {
            $resp_data = new Resp(Msg::getMessage(1094)->getCode(), Msg::getMessage(1094)->getMessage(), $data);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $this->editPostComment($de_serialize);
       
    }
    
    /**
     * Edit post comment
     * @param type $de_serialize
     */
    public function editPostComment($de_serialize)
    {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment]', array());
        $data = array();
        $mediaFeedService = $this->getMediaFeedService();
        $comment_id = $de_serialize['comment_id'];
        $post_id = $de_serialize['item_id'];
        $user_id = $de_serialize['user_id'];
        $comment_type = Utility::getLowerCaseString($de_serialize['comment_type']);
        if($comment_type == Utility::getLowerCaseString(self::MEDIA_COMMENT) ){
            $media_obj = $mediaFeedService->getMediaObject($post_id); //get media object
            $media_post_obj = $media_obj->getPost();
            $post_id = $media_post_obj[0]->getId();
            $post_obj = $postFeedsService->getPostObject($post_id); //get post object
        }elseif($comment_type == Utility::getLowerCaseString(self::POST_COMMENT) ){
            $post_obj = $postFeedsService->getPostObject($post_id); //get post object
        }
       //check for post object
        if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), $data); //NO_POST_EXIST
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $dm = $this->_getDocumentManager();
        $comment_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->getSingleCommentForPost($post_id, $comment_id);
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1099)->getCode(), Msg::getMessage(1099)->getMessage(), $data); //ERROR_IN_SAVING_COMMENT
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $group_mask = $postFeedsService->userCommentRole($comment_obj, $user_id); //get mask id
        //only owner and admin can edit the group
        $allow_group = array(self::ALLOW_GROUP);
        if (!in_array($group_mask, $allow_group)) {
            $resp_data = new Resp(Msg::getMessage(1043)->getCode(), Msg::getMessage(1043)->getMessage(), $data); //INVALID_GRANT
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        
        $comment_obj = $postFeedsService->editComment($post_obj, $comment_obj, $de_serialize);
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $comment_obj); //SUCCESS
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostComment] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * Delete comment
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postDeleteCommentAction(Request $request)
    {
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedService = $this->getMediaFeedService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment]', array());
        $utilityService = $this->getUtilityService();
        $data = array(); //initialize
        $required_parameter = array('user_id', 'item_id', 'comment_id', 'comment_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
        //remove comment
        $comment_id = $de_serialize['comment_id'];
        $post_id = $de_serialize['item_id'];
        $user_id = $de_serialize['user_id'];
        $comment_type = Utility::getLowerCaseString($de_serialize['comment_type']);
        if($comment_type == Utility::getLowerCaseString(self::MEDIA_COMMENT) ){
            $media_obj = $mediaFeedService->getMediaObject($post_id); //get media object
            $media_post_obj = $media_obj->getPost();
            $post_id = $media_post_obj[0]->getId();
            $post_obj = $postFeedsService->getPostObject($post_id); //get post object
        }elseif($comment_type == Utility::getLowerCaseString(self::POST_COMMENT) ){
            $post_obj = $postFeedsService->getPostObject($post_id); //get post object
        }
        //check for post object
        if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), $data); //NO_POST_EXIST
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $dm = $this->_getDocumentManager();
        $comment_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->getSingleCommentForPost($post_id, $comment_id);
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1099)->getCode(), Msg::getMessage(1099)->getMessage(), $data); //ERROR_IN_SAVING_COMMENT
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $owner_id = 0;
        //get post owner id
        $post_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('comments.id' => $comment_id));
        if($post_obj){
        $owner_id =  $post_obj->getUserId();
        }
        $group_mask = $postFeedsService->userCommentRole($comment_obj, $user_id); //get mask id
        //only owner and admin can edit the group
        $allow_group = array(self::ALLOW_GROUP);
        if ($user_id != $owner_id) {
            if (!in_array($group_mask, $allow_group)) {
                $resp_data = new Resp(Msg::getMessage(1043)->getCode(), Msg::getMessage(1043)->getMessage(), $data); //INVALID_GRANT
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        $comment_obj = $postFeedsService->deleteComment($post_obj, $comment_obj, $de_serialize);
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $comment_obj); //SUCCESS
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [DeleteComment] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * Get Post feed service
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds');
    }
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }
    
    /**
     * Get document manager
     * @return object
     */
     private function _getDocumentManager() {
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }

     /**
     * Get Media feed service
     * @return type
     */
    private function getMediaFeedService() {
        return $this->container->get('post_feeds.MediaFeeds'); //call media feed service
    }
}

