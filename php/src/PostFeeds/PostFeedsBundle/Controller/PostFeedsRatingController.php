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

class PostFeedsRatingController extends Controller
{
    
    private $element_type = array('post','media','post_comment','media_comment');
    private $tagged_element_type = array('post','media','comment');
    private $rating_value = array(1,2,3,4,5);
    const SOCIAL_PROJECT_POST = "SOCIAL_PROJECT";
    const SP_POST_MEDIA_COMMENT = "SP_POST_MEDIA_COMMENT";
    const SP_MEDIA_COMMENT = "SP_MEDIA_COMMENT";
    const SP_MEDIA = "SP_MEDIA";
    const SP_POST_MEDIA = "SP_POST_MEDIA";
    
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }

    /**
     * 
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds');
    }

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
    
    /**
     * 
     * @return type
     */
    protected function getPostFeedsRatingService() {
        return $this->container->get('post_feeds.PostFeedsRating');
    }
    
    /**
     * creating the rating of different type
     * @param request object
     * @return json
     */
    public function postAddFeedRatingAction(Request $request) {

        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addFeedRating]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id','element_type', 'element_id','reference_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addFeedRating] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $rate = $data['rate'];
        $element_id = $data['element_id'];
        $reference_id = $data['reference_id'];
        $element_type = Utility::getLowerCaseString($data['element_type']);
        $allowed_element_type = $this->element_type;
        $allowed_rating_value = $this->rating_value;
        
        //check if element_type is invalid 
        if (!in_array($element_type, $allowed_element_type)) {
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addFeedRating] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        
        //check if rating_value is invalid 
        if (!in_array($rate, $allowed_rating_value)) {
            $resp_data = new Resp(Msg::getMessage(1108)->getCode(), Msg::getMessage(1108)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addFeedRating] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        
        $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));
        try {
            //Match the case 
            switch ($element_type_const) {
                case 'POST':                 
                    $response = $this->addRatingToPost($user_id,$data,$element_id,$element_type);
                    break;
                case 'MEDIA':
                    $response = $this->addRatingToMedia($user_id,$data,$element_id,$element_type);
                    break;
                case 'POST_COMMENT': 
                    $response = $this->addRatingToPostComment($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
                case 'MEDIA_COMMENT':
                    $response = $this->addRatingToMediaComment($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
            }
            //out put the response             
            Utility::createResponse($response);
        } catch (\Exception $ex) {
           
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addFeedRating] with exception :' . $ex->getMessage(), array());
            Utility::createResponse($resp_data);
        }
        
    }
    
     /**
     * function for adding rating to post
     * @param type $user_id
     * @param type $element_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function addRatingToPost($user_id,$data,$element_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $post = $postFeedsService->getPostObject($element_id);
            //check of post does not exist for 
            if (!$post) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            /** code here for add rate to post **/
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->addRating($post,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
                'current_user_rate' =>$data['rate']
            );
            
            //send notifications
            $projectData = $post->getTypeInfo();
            if($projectData){
                $notificationService = $this->container->get('post_feeds.notificationFeeds');
                $notificationService->sendRateNotifications(array(
                    'project_id'=>$projectData['id'],
                    'from_id'=>$user_id,
                    'to_id'=>$post->getUserId(),
                    'project'=>$projectData['project_title'],
                    'project_owner_id'=>$projectData['project_owner']['id'],
                    'rate'=>$data['rate'],
                    'item_id'=>$element_id
                ), 'SP_POST', true, true, true);
            }
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
     /**
     * function for adding rating to media
     * @param type $user_id
     * @param type $element_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function addRatingToMedia($user_id,$data,$element_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
           //get media object
            $media_obj = $mediaService->getMediaObject($element_id);
            if(!$media_obj){
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addRatingToMedia] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            /** code here for add rate to post **/              
            
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->addRating($media_obj,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
                'current_user_rate' =>$data['rate']
            );
            
            //send notifications
            $notificationService = $this->container->get('post_feeds.notificationFeeds');
            $projectData = $mediaService->getProjectByMediaId($element_id);
            if($projectData){
                $notificationService->sendRateNotifications(array(
                    'project_id'=>$projectData->getId(),
                    'from_id'=>$user_id,
                    'to_id'=>$projectData->getOwnerId(),
                    'project'=>$projectData->getTitle(),
                    'rate'=>$data['rate'],
                    'project_owner_id'=>$projectData->getOwnerId(),
                    'item_id'=>$element_id
                ), self::SP_MEDIA, true, true, true);
            }else{
                $post = $this->getPostFeedsService()->getPostIdFromMediaId($element_id);
                if($post){
                    $postType = $post->getPostType();
                    $postTypeInfo = $post->getTypeInfo();
                    switch(strtoupper($postType)){
                        case self::SOCIAL_PROJECT_POST:
                            $projectData = $this->_getDocumentManager()->getRepository('PostFeedsBundle:SocialProject')->find($postTypeInfo['id']);
                            $notificationService->sendRateNotifications(array(
                                'project_id'=>$projectData->getId(),
                                'from_id'=>$user_id,
                                'to_id'=>$post->getUserId(),
                                'project'=>$projectData->getTitle(),
                                'project_owner_id'=>$projectData->getOwnerId(),
                                'rate'=>$data['rate'],
                                'item_id'=>$element_id
                            ), self::SP_POST_MEDIA, true, true, true);
                            break;
                    }
                }
            }
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
     /**
     * function for adding rating to post comment
     * @param type $user_id
     * @param type $element_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function addRatingToPostComment($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $post = $postFeedsService->getPostObject($element_id);
            //check of post does not exist for 
            if (!$post) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            
            $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    ->getSingleCommentForPost($element_id, $reference_id); 
            //check of post does not exist for 
            if (!$comment_obj) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToPost] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            
            /** code here for add rate to post **/
               
           // echo $comment_obj->getId(); exit;
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->addRating($comment_obj,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
                'current_user_rate' =>$data['rate']
            );
            
            //send notifications
            $projectData = $post->getTypeInfo();
            if($projectData){
                $notificationService = $this->container->get('post_feeds.notificationFeeds');
                $notificationService->sendRateNotifications(array(
                    'project_id'=>$projectData['id'],
                    'from_id'=>$user_id,
                    'to_id'=>$comment_obj->getUserId(),
                    'project'=>$projectData['project_title'],
                    'project_owner_id'=>$projectData['project_owner']['id'],
                    'rate'=>$data['rate'],
                    'item_id'=>$element_id
                ), 'SP_POST_COMMENT', true, true, true);
            }
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
     /**
     * function for adding rating to media comment
     * @param type $user_id
     * @param type $element_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function addRatingToMediaComment($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
           //get media object
           $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    //->getSingleCommentForPost($element_id, $reference_id); //$element_id 
                      ->getSingleComment($reference_id); //$element_id 
            if(!$comment_obj){
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addRatingToMedia] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            /** code here for add rate to post **/               
            
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->addRating($comment_obj,$data,$element_type);
         
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
                'is_rate' =>$add_rate_res->getIsRate(),
                'current_user_rate' =>$data['rate']
            );
            
            // send notifications
            $projectData = $this->getMediaFeedService()->getProjectByMediaId($element_id);
            $notificationService = $this->container->get('post_feeds.notificationFeeds');
            if($projectData){
                $notificationService->sendRateNotifications(array(
                    'project_id'=>$projectData->getId(),
                    'from_id'=>$user_id,
                    'to_id'=>$comment_obj->getUserId(),
                    'project'=>$projectData->getTitle(),
                    'project_owner_id'=>$projectData->getOwnerId(),
                    'rate'=>$data['rate'],
                    'item_id'=>$element_id
                ), self::SP_MEDIA_COMMENT, true, true, true);
            }else{
                $post = $this->getPostFeedsService()->getPostIdFromMediaId($element_id);
                if($post){
                    $postType = $post->getPostType();
                    $postTypeInfo = $post->getTypeInfo();
                    switch(strtoupper($postType)){
                        case self::SOCIAL_PROJECT_POST:
                            $projectData = $this->_getDocumentManager()->getRepository('PostFeedsBundle:SocialProject')->find($postTypeInfo['id']);
                            $notificationService->sendRateNotifications(array(
                                'project_id'=>$projectData->getId(),
                                'from_id'=>$user_id,
                                'to_id'=>$comment_obj->getUserId(),
                                'project'=>$projectData->getTitle(),
                                'project_owner_id'=>$projectData->getOwnerId(),
                                'rate'=>$data['rate'],
                                'item_id'=>$element_id
                            ), self::SP_POST_MEDIA_COMMENT, true, true, true);
                            break;
                    }
                }
            }
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [addRatingToMedia] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
     /**
     * delete rating
     * @param request object
     * @return json
     */
    public function postDeleteFeedRatingAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteFeedRating]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id','element_type', 'element_id','reference_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteFeedRating] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $element_id = $data['element_id'];
        $reference_id = $data['reference_id'];
        $element_type = Utility::getLowerCaseString($data['element_type']);
        $allowed_element_type = $this->element_type;
        
        //check if element_type is invalid 
        if (!in_array($element_type, $allowed_element_type)) {
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteFeedRating] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));
        try {
            //Match the case 
            switch ($element_type_const) {
                case 'POST':                 
                    $response = $this->deleteRatingToPost($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
                case 'MEDIA':
                    $response = $this->deleteRatingToMedia($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
                case 'POST_COMMENT':
                    $response = $this->deleteRatingToPostComment($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
                case 'MEDIA_COMMENT':
                    $response = $this->deleteRatingToMediaComment($user_id,$data,$element_id,$reference_id,$element_type);
                    break;
            }
            //out put the response             
            Utility::createResponse($response);
        } catch (\Exception $ex) {
           
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteFeedRating] with exception :' . $ex->getMessage(), array());
            Utility::createResponse($resp_data);
        }
    }
    
    public function deleteRatingToPost($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPost]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $post = $postFeedsService->getPostObject($element_id);
            //check of post does not exist for 
            if (!$post) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPost] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            /** code here for add rate to post **/
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->deleteRating($post,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
            );
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPost] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPost] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     * function for delete rating to media
     * @param type $user_id
     * @param type $data
     * @param type $element_id
     * @param type $reference_id
     * @param type $element_type
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function deleteRatingToMedia($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMedia]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
           //get media object
            $media_obj = $mediaService->getMediaObject($element_id);
            if(!$media_obj){
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deleteRatingToMedia] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            /** code here for add rate to post **/              
            
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->deleteRating($media_obj,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
            );
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMedia] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMedia] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
     /**
      * function for delete rating to post comment
      * @param type $user_id
      * @param type $data
      * @param type $element_id
      * @param type $reference_id
      * @param type $element_type
      * @return \Utility\UtilityBundle\Utils\Response
      */
    public function deleteRatingToPostComment($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPostComment]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
           //get media object
           $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    ->getSingleCommentForPost($element_id, $reference_id);
            if(!$comment_obj){
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deleteRatingToPostComment] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            /** code here for add rate to post **/
               
            
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->deleteRating($comment_obj,$data,$element_type);
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
            );
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPostComment] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToPostComment] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
     /**
      * function for delete rating to media comment
      * @param type $user_id
      * @param type $data
      * @param type $element_id
      * @param type $reference_id
      * @param type $element_type
      * @return \Utility\UtilityBundle\Utils\Response
      */
    public function deleteRatingToMediaComment($user_id,$data,$element_id,$reference_id,$element_type) {
        $utilityService = $this->getUtilityService();
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $mediaService = $this->getMediaFeedService();
        $postFeedsRatingService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMediaComment]');
        try {
            
           
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
           //get media object
           $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    //->getSingleCommentForPost($element_id, $reference_id);
                      ->getSingleComment($reference_id);
            if(!$comment_obj){
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deleteRatingToMediaComment] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            /** code here for add rate to post **/               
            
            $return_res = array();
            $add_rate_res = $postFeedsRatingService->deleteRating($comment_obj,$data,$element_type);
         
            $return_res = array(
                'vote_sum' =>$add_rate_res->getVoteSum(),
                'avg_rating' =>$add_rate_res->getAvgRating(),
                'vote_count' =>$add_rate_res->getVoteCount(),
                'is_rate' =>$add_rate_res->getIsRate(),
            );
            
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMediaComment] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [deleteRatingToMediaComment] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     * Get rating info
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetRatingInfoAction(Request $request)
    {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [GetRatingInfo]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id','element_type', 'element_id');
        $response = $utilityService->checkRequest($request, $requiredParams); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [GetRatingInfo] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $element_id = $data['element_id'];
        $data['limit_start'] = (isset($data['limit_start'])) ? $data['limit_start'] : 0;
        $data['limit_size'] = (isset($data['limit_size'])) ? $data['limit_size'] : 20;
        $element_type = Utility::getLowerCaseString($data['element_type']);
        $allowed_element_type = $this->tagged_element_type;
        
        //check if element_type is invalid 
        if (!in_array($element_type, $allowed_element_type)) {
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [GetRatingInfo] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));
            //Match the case 
            switch ($element_type_const) {
                case 'POST':                 
                    $response = $this->postRatingInfo($data);
                    break;
                case 'MEDIA':
                    $response = $this->mediaRatingInfo($data);
                    break;
                case 'COMMENT':
                    $response = $this->commentRatingInfo($data);
                    break;
            }
            //out put the response             
            Utility::createResponse($response);
    }
    
    /**
     * Get Post Rating
     * @param type $data
     */
    public function postRatingInfo($data)
    {
        $post_id = $data['element_id'];
        $user_id = $data['user_id'];
        $offset = $data['limit_start'];
        $size = $data['limit_size'];
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $postFeedsRatingService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [postRatingInfo]', array());
        $post_obj = $postFeedsService->getPostObject($post_id);//get post object
        
        if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array()); //NO_POST_EXIST
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [postRatingInfo] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //get rate object
        $users_rated = $post_obj->getRate();
        if(count($users_rated) == 0){
            $resp = array('rated_users'=>array(), 'is_rated' => 0, 'current_user_rate' => 0);
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $resp);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [postRatingInfo] with msg :' . (string) $resp_data, array());
            return $resp_data;
        }
        $response = $this->getRatingCommonInfo($users_rated, $post_obj, $user_id, $users_rated, $offset, $size);
        return $response;
    }
    
    /**
     * Get Media Rating
     * @param type $data
     */
    public function mediaRatingInfo($data)
    {
        $media_id = $data['element_id'];
        $user_id = $data['user_id'];
        $offset = $data['limit_start'];
        $size = $data['limit_size'];
        $mediaFeedsService = $this->getMediaFeedService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $media_obj = $mediaFeedsService->getMediaObject($media_id);//get media object
        if(!$media_obj){
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()); //NO_MEDIA_FOUND
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [mediaRatingInfo] with msg :' . (string) $resp_data, array());
            Utility::createResponse($resp_data);
        }
        //get rate object
        $users_rated = $media_obj->getRate();
        if(count($users_rated) == 0){
            $resp = array('rated_users'=>array(), 'is_rated' => 0, 'current_user_rate' => 0);
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $resp);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [mediaRatingInfo] with msg :' . (string) $resp_data, array());
            return $resp_data;
        }
        $response = $this->getRatingCommonInfo($users_rated, $media_obj, $user_id, $users_rated, $offset, $size);
        return $response;
    }
    
    /**
     * Get Comment Rating
     * @param type $data
     */
    public function commentRatingInfo($data)
    {
        $comment_id = $data['element_id'];
        $user_id = $data['user_id'];
        $offset = $data['limit_start'];
        $size = $data['limit_size'];
        $postFeedsService = $this->getMediaFeedService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        //get mongo doctrine object
        $dm = $this->_getDocumentManager();
        //get media object
        $comment_obj = $dm
            ->getRepository('PostFeedsBundle:PostFeeds')
             ->getSingleComment($comment_id); //$element_id 
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1116)->getCode(), Msg::getMessage(1116)->getMessage(), array()); //NO_COMMENT_FOUND
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [commentRatingInfo] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
         }
        //get rate object
        $users_rated = $comment_obj->getRate();
        if(count($users_rated) == 0){
            $resp = array('rated_users'=>array(), 'is_rated' => 0, 'current_user_rate' => 0);
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $resp);
            $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [commentRatingInfo] with msg :' . (string) $resp_data, array());
            return $resp_data;
        }
        $response = $this->getRatingCommonInfo($users_rated, $comment_obj, $user_id, $users_rated, $offset, $size);
        return $response;
    }
    
    /**
     * 
     * @param type $users_rated
     * @param type $post_obj
     * @param type $user_id
     * @param type $user_obj
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function getRatingCommonInfo($users_rated, $post_obj, $user_id, $user_obj, $offset, $size)
    {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsRatingService = $this->getPostFeedsRatingService();
        $user_obj = $postFeedsRatingService->getRatedUsers($users_rated);
        $rate_obj = $postFeedsService->getRatedUsers($post_obj, $user_id, $user_obj, array(), array());
        $input = $rate_obj['rated_users'];
        $total = count($input);
        $output['rated_users'] = array_slice($input, $offset, $size);
        $output['size'] = $total;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $output);
        $postFeedsRatingService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingController] and function [getRatingCommonInfo] with msg :' . (string) $resp_data, array());
        return $resp_data;
    }
}
