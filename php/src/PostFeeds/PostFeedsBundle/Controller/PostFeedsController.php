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

class PostFeedsController extends Controller {

    CONST PERSONAL_POST = 1;
    CONST PROFESSIONAL_POST = 2;
    CONST PUBLIC_POST = 3;
    CONST USER_POST = 'USER';
    CONST SHOP_POST = 'SHOP';
    CONST CLUB_POST = 'CLUB';
    CONST SOCIAL_PROJECT_POST = 'SOCIAL_PROJECT';
    CONST POST_ACTIVE = 1;
    private $tag_type = array('user_tag','shop_tag','club_tag');
    CONST MEDIA_COMMENT = 'MEDIA_COMMENT';
    CONST POST_COMMENT = 'POST_COMMENT';

    CONST ALLOW_GROUP = 15;
    CONST MASK = 21;
    CONST COMMENT_LIST_LIMIT_START = 0;
    CONST COMMENT_LIST_LIMIT_SIZE = 20;
    private $element_type = array('post','media','post_comment','media_comment');
    CONST SOCIAL_DASHBOARD = 'SOCIAL_DASHBOARD';
    CONST INTERNAL_SHARE = 'INTERNAL_SHARE';
    CONST EXTERNAL_SHARE = 'EXTERNAL_SHARE';
    CONST ALLOWED_CLUB = 'CLUB';
    CONST ALLOWED_SHOP = 'SHOP';
    CONST ALLOWED_OFFER = 'OFFER';
    CONST ALLOWED_SOCIAL_PROJECT = 'SOCIAL_PROJECT';
    CONST ALLOWED_EXTERNAL = 'EXTERNAL';
    CONST BCE = 'BCE';
    
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
        return $this->container->get('store_manager_store.storeUtility'); //StoreManager\StoreBundle\Utils\UtilityService
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
     * creating the post of different type
     * @param request object
     * @return json
     */
    public function postCreatePostFeedAction(Request $request) {

        $postFeedsService = $this->getPostFeedsService();
        $notificationFeedsService = $this->getNotificationFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id', 'to_id', 'link_type', 'post_type', 'type_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        
        //check for share type
        $object_info = (object)$data;
        if(isset($object_info->share_type)){
            $allowed_share = array(Utility::getLowerCaseString(self::INTERNAL_SHARE), Utility::getLowerCaseString(self::EXTERNAL_SHARE));
            $allowed_object_type = array(Utility::getLowerCaseString(self::ALLOWED_CLUB), Utility::getLowerCaseString(self::ALLOWED_SHOP), Utility::getLowerCaseString(self::ALLOWED_OFFER), Utility::getLowerCaseString(self::ALLOWED_SOCIAL_PROJECT), Utility::getLowerCaseString(self::ALLOWED_EXTERNAL), Utility::getLowerCaseString(self::BCE));
            if (!in_array(Utility::getLowerCaseString($object_info->share_type), $allowed_share)) {
                $resp_data = new Resp(Msg::getMessage(1129)->getCode(), Msg::getMessage(1129)->getMessage(), array()); //INVALID_SHARE_TYPE
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            $object_info->object_type = (isset($object_info->object_type)) ? $object_info->object_type : '';
            if (!in_array(Utility::getLowerCaseString($object_info->object_type), $allowed_object_type)) {
                $resp_data = new Resp(Msg::getMessage(1130)->getCode(), Msg::getMessage(1130)->getMessage(), array()); //INVALID_OBJECT_TYPE
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
        }
        
        $post_type = Utility::getLowerCaseString($data['post_type']);

        $user_id = $data['user_id'];
        $to_id = $data['to_id'];
        $youtube_url = (isset($data['youtube_url']) ? $data['youtube_url'] : '');
        $title = (isset($data['title']) ? $data['title'] : '' );
        $description = (isset($data['description']) ? $data['description'] : '' );
        $privacy_setting = (isset($data['privacy_setting']) ? $data['privacy_setting'] : self::PUBLIC_POST);
        $tagging = (isset($data['tagging']) ? $data['tagging'] : array());
        $media_ids = (isset($data['media_id']) ? $data['media_id'] : array());
        $link_type = (isset($data['link_type']) ? $data['link_type'] : '');
        $type_id = (isset($data['type_id']) ? $data['type_id'] : '');

        $allow_privacy_setting = '';
        $post_type_check = array(Utility::getLowerCaseString(self::USER_POST), Utility::getLowerCaseString(self::SHOP_POST) ,Utility::getLowerCaseString(self::CLUB_POST) ,Utility::getLowerCaseString(self::SOCIAL_PROJECT_POST));
        if (!in_array($post_type, $post_type_check)) {
            $resp_data = new Resp(Msg::getMessage(1094)->getCode(), Msg::getMessage(1094)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $type_info = '';
        if (Utility::matchString($post_type, self::USER_POST)) {
            /** 1 for personal post , 2 for professional post , 3 for public * */
            $allow_personal_friend_privacy_setting = array();
            $allow_professional_friend_privacy_setting = array();
            $allow_self_privacy_setting = array(self::PERSONAL_POST, self::PROFESSIONAL_POST, self::PUBLIC_POST);
            $allow_other_user_wall_privacy_setting = array(self::PUBLIC_POST);
            // if user post on his own wall or Dashboard            
            if ($user_id == $to_id) {
                $allow_privacy_setting = $allow_self_privacy_setting;
            } else { //if user post on other user wall
                $allow_privacy_setting = $postFeedsService->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id);
            }
            if (!in_array($privacy_setting, $allow_privacy_setting)) {
                $resp_data = new Resp(Msg::getMessage(153)->getCode(), Msg::getMessage(153)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
            $type_info = $user_service->UserObjectService($type_id);
            if (empty($type_info)) {
                $resp_data = new Resp(Msg::getMessage(1096)->getCode(), Msg::getMessage(1096)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }


        if (Utility::matchString($post_type, self::SHOP_POST)) {
            $type_info = $user_service->getStoreObjectService($type_id);
            if (empty($type_info)) {
                $resp_data = new Resp(Msg::getMessage(1096)->getCode(), Msg::getMessage(1096)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }

        if (Utility::matchString($post_type, self::CLUB_POST)) {
            $type_info = $postFeedsService->getGroupInfoById($type_id);
            if (empty($type_info)) {
                $resp_data = new Resp(Msg::getMessage(1096)->getCode(), Msg::getMessage(1096)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        
        if (Utility::matchString($post_type, self::SOCIAL_PROJECT_POST)) {
            $type_info = $postFeedsService->getMultipleSocialProjectObjects($type_id, false);
           
            if (empty($type_info)) {
                $resp_data = new Resp(Msg::getMessage(1096)->getCode(), Msg::getMessage(1096)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }

        $time = new \DateTime("now");
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //get shared object
        $shared_object = $this->getSharedObject($data);
        $post_feeds = new PostFeeds();
        $post_feeds->setUserId($user_id);
        $post_feeds->setToId($to_id);
        $post_feeds->setTitle($title);
        $post_feeds->setDescription($description);
        $post_feeds->setLinkType($link_type);
        $post_feeds->setIsActive(self::POST_ACTIVE);
        $post_feeds->setPrivacySetting($privacy_setting);
        $post_feeds->setCreatedAt($time);
        $post_feeds->setUpdatedAt($time);
        $post_feeds->setPostType($post_type);
        $post_feeds->setTypeInfo($type_info);
        $post_feeds->setIsComment(0);
        $post_feeds->setIsRate(0);
        $post_feeds->setIsTag(0);
                
        //add media collection
      
        if(count($media_ids)>0) {
            foreach ($media_ids as $media_id) {
                $feed_media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                        ->find(array('id' => $media_id));
                if($feed_media){
                    $post_feeds->addMedia($feed_media);
                    $post_feeds->setIsMedia(1);
                }else{
                    $post_feeds->setIsMedia(0);
                }                
            }
        }else{
            $post_feeds->setIsMedia(0);
        }
        

        /** call for tagging function * */
        $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
        $post_feeds = $post_feeds_service_obj->manageTagging($post_feeds, $tagging);
        $post_feeds->setShareType($shared_object->share_type);
        $post_feeds->setContentShare($shared_object->content_share);
        $post_feeds->setShareObjectId($shared_object->object_id);
        $post_feeds->setShareObjectType($shared_object->object_type);      
        try {
            $dm->persist($post_feeds); //storing the post data.
            $dm->flush();
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with exception :' . $ex->getMessage(), array());
            Utility::createResponse($resp_data);
        }
        $post_id = $post_feeds->getId();
        //update ACL for a user
        $this->updateAclAction($sender_user, $post_feeds);
        $result_data = array();

        if ($post_id) {
            $result_data = $postFeedsService->getPostFeedById($post_id, $user_id);
            
        }
        $notificationFeedsService->sendPostNotification($result_data, self::SOCIAL_DASHBOARD, $post_type); //send notification
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [createPostFeed] with response: ' . Utility::encodeData($result_data));
        Utility::createResponse($resp_data);
    }

    /**
     * Upload Media
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUploadmediasAction(Request $request) {
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $utilityService = $this->getUtilityService();
        $service_obj->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias]', array());
        $data = array();
        $required_parameter = array('user_id');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        //check for images upload
        if (isset($_FILES['postfile'])) {
            $images = $_FILES['postfile'];
            $file_error = $service_obj->checkFileTypeAction($images); //checking the file type extension.
            if ($file_error) {
                $resp_data = new Resp(Msg::getMessage(301)->getCode(), Msg::getMessage(301)->getMessage(), $data); //YOU_MUST_CHOOSE_AN_IMAGE
                $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $post_images = $_FILES['postfile'];
        $user_id =  $data['user_id'];
        $item_id = null;
        $resp = $service_obj->uploadMedia($post_images, $item_id, $user_id); //call media upload
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $resp); //SUCCESS
        $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }

    /**
     * service for removing the tagging form a post by a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postRemoveposttaggingAction(Request $request) {
        try {

            $utilityService = $this->getUtilityService();
            $postFeedsService = $this->getPostFeedsService();

            $user_service = $this->get('user_object.service');

            $requiredParams = array('user_id', 'element_type', 'element_id', 'reference_id', 'tag_type', 'remove_tag_id');
            //check if all the required params are present in request
            $response = $utilityService->checkRequest($request, $requiredParams); //check for request object
            if ($response !== true) {
                $resp_data = new Resp($response['code'], $response['message'], $response['data']);
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
            //get deseralized data from the request
            $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
            $element_type = $de_serialize['element_type'];
            $element_id = $de_serialize['element_id'];
            $referance_id = $de_serialize['reference_id'];
            $user_id = $de_serialize['user_id'];
            $tag_type = $de_serialize['tag_type'];
            $remove_tag_id = $de_serialize['remove_tag_id'];
            $tag_type_allowed = $this->tag_type;
            $allowed_element_type = $this->element_type;

            //check if element_type is invalid 
            if (!in_array($element_type, $allowed_element_type)) {
                $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }

            //check if tag_type is invalid 
            if (!in_array($tag_type, $tag_type_allowed)) {
                $resp_data = new Resp(Msg::getMessage(1097)->getCode(), Msg::getMessage(1097)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            //check for valid security for removing tag
            $post_feed_service = $this->container->get('post_feeds.postFeeds');
//            $valid_security = $post_feed_service->checkSecurityForTagRemoving($user_id,$tag_type, $remove_tag_id);
//            if(!$valid_security) {
//                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
//                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
//                Utility::createResponse($resp_data);
//            }
            $element_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($element_type));
            //Match the case 
            switch ($element_type_const) {
                case 'POST':
                    $response = $this->removeTagFromPost($user_id,$element_id, $tag_type, $remove_tag_id);
                    break;
                case 'MEDIA':
                    $response = $this->removeTagFromMedia($user_id,$element_id, $tag_type, $remove_tag_id);
                    break;
                case 'POST_COMMENT':
                    $response = $this->removeTagFromPostComment($user_id,$element_id, $referance_id,$tag_type, $remove_tag_id);
                    break;
                case 'MEDIA_COMMENT':
                    $response = $this->removeTagFromMediaComment($user_id,$element_id,$referance_id,$tag_type,$remove_tag_id);
                    break;
            }
            //out put the response             
            Utility::createResponse($response);
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with exception :' . $ex->getMessage(), array());
            Utility::createResponse($resp_data);
        }
    }

    /**
     * delete post by id
     * @param request object
     * @return json
     */
    public function postDeletePostFeedAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id', 'post_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $result_data = array();
        $user_id = $data['user_id'];
        $post_id = $data['post_id'];
        $post_data = $postFeedsService->getPostFeedById($post_id,$user_id);
        if (empty($post_data)) {
            $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //for DashBoard Post ACL     
        $do_action = 0;
        $group_mask = $this->userPostRole($post_id, $user_id);
        $allow_group = array(self::ALLOW_GROUP);
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        $post_feeds = $dm->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('id' => $post_id));
        if ($do_action == 1) {
            if ($post_feeds) {
                try {
                    $dm->remove($post_feeds);
                    $dm->flush();
                } catch (Exception $ex) {
                    $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
                    $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with exception :' . $ex->getMessage(), array());
                    Utility::createResponse($resp_data);
                }
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            } else {
                $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        } else {
            $resp_data = new Resp(Msg::getMessage(500)->getCode(), Msg::getMessage(500)->getMessage(), $result_data);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [deletePostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
    }

    /**
     * Get User role for  post
     * @param int $post_id
     * @param int $user_id
     * @return int
     */
    public function userPostRole($post_id, $user_id) {
        $mask = self::MASK; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('id' => $post_id)); //@TODO Add group owner id in AND clause.

        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($post); //entity

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }

        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $user_id));


        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($user_obj);

        foreach ($acl->getObjectAces() as $ace) {
            if ($ace->getSecurityIdentity()->equals($securityIdentity)) {
                $mask = $ace->getMask();
                break;
            }
        }
        return $mask;
    }

    /**
     * creating the ACL for the entity for a user
     * @param object $sender_user
     * @param object $post_feed
     * @return none
     */
    public function updateAclAction($sender_user, $post_feed) {
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($post_feed);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($sender_user);
        $builder = new MaskBuilder();
        $builder->add('view')
                ->add('edit')
                ->add('create')
                ->add('delete');
        $mask = $builder->get();
        // grant owner access
        $acl->insertObjectAce($securityIdentity, $mask);
        $aclProvider->updateAcl($acl);
    }

    /**
     * Add Comment
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAddCommentAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment]', array());
        $utilityService = $this->getUtilityService();
        $data = array(); //initialize
        $required_parameter = array('user_id', 'item_id', 'comment_type' );
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $utilityService->getDeSerializeDataFromRequest($request);
        $comment_type = Utility::getLowerCaseString($de_serialize['comment_type']);
        $post_type_check = array(Utility::getLowerCaseString(self::POST_COMMENT), Utility::getLowerCaseString(self::MEDIA_COMMENT));
        if (!in_array(Utility::getLowerCaseString($comment_type), $post_type_check)) {
            $resp_data = new Resp(Msg::getMessage(1094)->getCode(), Msg::getMessage(1094)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [AddComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        switch($comment_type){
           CASE Utility::getLowerCaseString(self::POST_COMMENT):
               $this->addPostComment($de_serialize);
               break;
           CASE Utility::getLowerCaseString(self::MEDIA_COMMENT):
               $this->addMediaComment($de_serialize);
               break;         
        }
        return true;
    }
    
    /**
     * Add Post Cooment
     * @param array $de_serialize
     */
    public function addPostComment($de_serialize)
    {
        $postFeedsService = $this->getPostFeedsService();
        $notificationFeedsService = $this->getNotificationFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addPostComment]', array());
        $item_id = $de_serialize['item_id'];
        $user_id = $de_serialize['user_id'];
        $tagging = (isset($de_serialize['tagging']) ? $de_serialize['tagging'] : array());
        $data = array();
        $post_obj = $postFeedsService->getPostObject($item_id);
        if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), $data); //NO_POST_EXIST
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //get tagged object info 
        $tagged_data = $postFeedsService->getPostTagObj($post_obj);
        
        $comment_obj = $postFeedsService->addComment($post_obj, $de_serialize, self::POST_COMMENT, $tagged_data);
        //$tag_comment = $postFeedsService->addCommentTag($comment_obj, $tagging); //tag comment
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1099)->getCode(), Msg::getMessage(1099)->getMessage(), $data); //ERROR_IN_SAVING_COMMENT
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addPostComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        $notification_comment_obj = $comment_obj;
        $comment_tags = $postFeedsService->getCommentEntityObject(array($comment_obj)); //get comment tagged users
        $user_ids = $comment_tags['users'];
        $shop_ids = $comment_tags['shops'];
        $club_ids = $comment_tags['clubs'];
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        //$comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
        $comment_obj = $postFeedsService->getSingleCommentObject($comment_obj, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
        //$tagging = $comment_obj->getTa
        //$notificationFeedsService->sendCommentNotification($item_id, $de_serialize['user_id'], self::SOCIAL_DASHBOARD, $notification_comment_obj);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $comment_obj); //ERROR_OCCURED
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addPostComment] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * Add media
     * @param array $de_serialize
     */
    public function addMediaComment($de_serialize)
    {
        
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment]', array());
        $mediaService = $this->getMediaFeedService();
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $item_id = $de_serialize['item_id'];
        $user_id = $de_serialize['user_id'];
        $tagging = (isset($de_serialize['tagging']) ? $de_serialize['tagging'] : array());
        $data = array();
        //get media object
        $media_obj = $mediaService->getMediaObject($item_id);
        if(!$media_obj){
            $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), $data); //NO_MEDIA_FOUND
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //get users tagged in media
        $tagged_data['club'] = $media_obj->getTagClub();
        $tagged_data['user'] = $media_obj->getTagUser();
        $tagged_data['shop'] = $media_obj->getTagShop();
        $media_post_obj = $media_obj->getPost();
        if (count($media_post_obj) == 0) {
            
            //add new post
            $post_obj = $postFeedsService->createMediaPost($de_serialize);
            if(!$post_obj){
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            //map the post object with media
            $media_obj->addPost($post_obj);
            //$media_obj = $postFeedsService->manageTagging($media_obj, $tagging); //add tag for media
            try {
                $dm->persist($media_obj); //storing the post data.
                $dm->flush();
                $comment_obj = $postFeedsService->addComment($post_obj, $de_serialize, self::MEDIA_COMMENT,$tagged_data);
                $comment_tags = $postFeedsService->getCommentEntityObject(array($comment_obj)); //get comment tagged users
                $user_ids = $comment_tags['users'];
                $shop_ids = $comment_tags['shops'];
                $club_ids = $comment_tags['clubs'];
                $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
                $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
                $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
                //$comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
                $comment_obj = $postFeedsService->getSingleCommentObject($comment_obj, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
       
                //$comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $comment_obj); //ERROR_OCCURED
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            } catch (Exception $ex) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        //get post id
        $post_id = $media_post_obj[0]->getId();
        $post_obj = $postFeedsService->getPostObject($post_id);
         if(!$post_obj){
            $resp_data = new Resp(Msg::getMessage(1098)->getCode(), Msg::getMessage(1098)->getMessage(), $data); //NO_POST_EXIST
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $comment_obj = $postFeedsService->addComment($post_obj, $de_serialize,self::MEDIA_COMMENT,$tagged_data);
        if(!$comment_obj){
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), $data); //ERROR_IN_SAVING_COMMENT
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data); 
        }
        //$comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
        $comment_tags = $postFeedsService->getCommentEntityObject(array($comment_obj)); //get comment tagged users
        $user_ids = $comment_tags['users'];
        $shop_ids = $comment_tags['shops'];
        $club_ids = $comment_tags['clubs'];
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        //$comment_obj = $postFeedsService->getSingleCommentObj($comment_obj);
        $comment_obj = $postFeedsService->getSingleCommentObject($comment_obj, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
       
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $comment_obj); //ERROR_OCCURED
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addMediaComment] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data);
   
    }

    /**
     * edit the post of different type
     * @param request object
     * @return json
     */
    public function postEditPostFeedAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('post_id', 'user_id', 'to_id', 'link_type');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $result_data = array();
        $post_id = $data['post_id'];
        $user_id = $data['user_id'];
        $to_id = $data['to_id'];
        $title = (isset($data['title']) ? $data['title'] : '' );
        $description = (isset($data['description']) ? $data['description'] : '' );
        $privacy_setting = (isset($data['privacy_setting']) ? $data['privacy_setting'] : self::PUBLIC_POST);
        $tagging = (isset($data['tagging']) ? $data['tagging'] : '');
        $link_type = (isset($data['link_type']) ? $data['link_type'] : '');

        $allow_privacy_setting = '';


        $post_data = $postFeedsService->getPostFeedById($post_id,$user_id);
        if (empty($post_data)) {
            $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $post_type = $post_data['post_type'];
        if (Utility::matchString($post_type, self::USER_POST)) {
            /** 1 for personal post , 2 for professional post , 3 for public * */
            $allow_personal_friend_privacy_setting = array();
            $allow_professional_friend_privacy_setting = array();
            $allow_self_privacy_setting = array(self::PERSONAL_POST, self::PROFESSIONAL_POST, self::PUBLIC_POST);
            $allow_other_user_wall_privacy_setting = array(self::PUBLIC_POST);
            // if user post on his own wall or Dashboard            
            if ($user_id == $to_id) {
                $allow_privacy_setting = $allow_self_privacy_setting;
            } else { //if user post on other user wall
                $allow_privacy_setting = $postFeedsService->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id);
            }
            if (!in_array($privacy_setting, $allow_privacy_setting)) {
                $resp_data = new Resp(Msg::getMessage(153)->getCode(), Msg::getMessage(153)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        
        $time = new \DateTime("now");
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //for DashBoard Post ACL     
        $do_action = 0;
        $group_mask = $this->userPostRole($post_id, $user_id);
        $allow_group = array(self::ALLOW_GROUP);
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        $post_feeds = $dm->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('id' => $post_id));
        if ($do_action == 1) {
            if ($post_feeds) {
                $post_feeds->setUserId($user_id);
                $post_feeds->setToId($to_id);
                if(isset($data['title'])) {
                    $post_feeds->setTitle($title);
                }
                
                if(isset($data['description'])) {
                    $post_feeds->setDescription($description);
                }
                
                if(isset($data['privacy_setting'])) {
                    $post_feeds->setPrivacySetting($privacy_setting);
                }
                if(isset($data['content_share'])){
                    $post_feeds->setContentShare($data['content_share']);
                }
                $post_feeds->setLinkType($link_type);
                $post_feeds->setUpdatedAt($time);
                
                /** call for tagging function * */
                $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
                $existingTagged['club'] = $post_feeds->getTagClub();
                $existingTagged['user'] = $post_feeds->getTagUser();
                $existingTagged['shop'] = $post_feeds->getTagShop();
                $nToTagged['club'] = array_diff($tagging['club'], is_array($existingTagged['club']) ? $existingTagged['club'] : array());
                $nToTagged['user'] = array_diff($tagging['user'], is_array($existingTagged['user']) ? $existingTagged['user'] : array());
                $nToTagged['shop'] = array_diff($tagging['shop'], is_array($existingTagged['shop']) ? $existingTagged['shop'] : array());
                    
                if(!empty($tagging)) {
                    $post_feeds = $post_feeds_service_obj->manageTagging($post_feeds, $tagging);
                }                
                try {
                    $dm->persist($post_feeds); //storing the post data.
                    $dm->flush();
                } catch (Exception $ex) {
                    $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
                    $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with exception :' . $ex->getMessage(), array());
                    Utility::createResponse($resp_data);
                }
                if ($post_id) {
                    $result_data = $postFeedsService->getPostFeedById($post_id,$user_id);
                }
                $notificationFeedsService = $this->getNotificationFeedsService();
                $notificationFeedsService->postFeedTagNotification($nToTagged, $user_id, $result_data);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . Utility::encodeData($resp_data));
                Utility::createResponse($resp_data);
            } else {
                $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        } else {
            $resp_data = new Resp(Msg::getMessage(500)->getCode(), Msg::getMessage(500)->getMessage(), $result_data);
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
    }

    /**
     * Get Media feed service
     * @return type
     */
    private function getMediaFeedService() {
        return $this->container->get('post_feeds.MediaFeeds'); //call media feed service
    }

    /**
     *  function for removing the Tag user from the post
     * @param type $post_id
     * @param type $tag_type
     * @param type $remove_tag_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    public function removeTagFromPost($user_id,$post_id, $tag_type, $remove_tag_id) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPost]');
        try {
            $utilityService = $this->getUtilityService();
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $post = $dm->getRepository('PostFeedsBundle:PostFeeds')
                    ->find($post_id);
            //check of post does not exist for 
            if (!$post) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }

            $creater_id = $post->getUserId();
            //check for valid security for removing tag
            $post_feed_service = $this->container->get('post_feeds.postFeeds');
            if($creater_id != $user_id) {
            $valid_security = $post_feed_service->checkSecurityForTagRemoving($user_id,$tag_type, $remove_tag_id);
            if(!$valid_security) {
                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            }
            $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
            $post_feeds = $post_feeds_service_obj->removeTagging($post, $tag_type, $remove_tag_id);
            $dm->persist($post_feeds);
            $dm->flush();
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPost] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPost] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     * function for removing the tagging from a media
     * @param type $element_id
     * @param type $tag_type
     * @param type $remove_tag_id
     * @return \Utility\UtilityBundle\Utils\Response
     */
    private function removeTagFromMedia($user_id,$element_id, $tag_type, $remove_tag_id) {
        try {
            $utilityService = $this->getUtilityService();
            $postFeedsService = $this->getPostFeedsService();
            $postFeedsService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMedia]');
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $mediaService = $this->getMediaFeedService();
            $media_obj = $mediaService->getMediaObject($element_id);
            //check of post does not exist for 
            if (!$media_obj) {
                $resp_data = new Resp(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMedia] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            $post_feed_service = $this->container->get('post_feeds.postFeeds');
           $creater_id = $media_obj->getUserId();
            if($creater_id != $user_id) {
            $valid_security = $post_feed_service->checkSecurityForTagRemoving($user_id,$tag_type, $remove_tag_id);
            if(!$valid_security) {
                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            }
            $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
            $post_feeds = $post_feeds_service_obj->removeTagging($media_obj, $tag_type, $remove_tag_id);
            $dm->persist($post_feeds);
            $dm->flush();
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMedia] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMedia] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     *  function for removing the tagging from the comment of a post
     * @param type $element_id
     * @param type $tag_type
     * @param type $remove_tag_id
     */
    public function removeTagFromPostComment($user_id,$element_id, $referance_id, $tag_type, $remove_tag_id) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPostComment] ');
        try {
            $utilityService = $this->getUtilityService();
            $postFeedsService = $this->getPostFeedsService();
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    ->getSingleCommentForPost($element_id, $referance_id);
            //check of post does not exist for 
            if (!$comment_obj) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPostComment] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            $post_feed_service = $this->container->get('post_feeds.postFeeds');
            $creater_id = $comment_obj->getUserId();
            if($creater_id != $user_id) {
            $valid_security = $post_feed_service->checkSecurityForTagRemoving($user_id,$tag_type, $remove_tag_id);
            if(!$valid_security) {
                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            }
                $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
                $post_feeds = $post_feeds_service_obj->removeTagging($comment_obj, $tag_type, $remove_tag_id);
                $dm->persist($post_feeds);
                $dm->flush();
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPostComment] with msg :' . (string) $resp_data, array());
                return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromPostComment] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     * function for removing the tag from the media comment
     * @param type $user_id
     * @param type $element_id
     * @param type $tag_type
     * @param type $remove_tag_id
     */
    private function removeTagFromMediaComment($user_id, $element_id, $referance_id, $tag_type, $remove_tag_id) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment]');
        try {
            $utilityService = $this->getUtilityService();
            $postFeedsService = $this->getPostFeedsService();
            //get mongo doctrine object
            $dm = $this->_getDocumentManager();
            //find the post object from the postfeeds
            $mediaService = $this->getMediaFeedService();
            $media_obj = $mediaService->getMediaObject($element_id);
            //check if media exist
            if (!$media_obj) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            //get media post for a media id
            $media_post = $media_obj->getPost();
            
            if (count($media_post) == 0) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            
            $post_id = $media_post[0]->getId();
            $comment_obj = $dm
                    ->getRepository('PostFeedsBundle:PostFeeds')
                    ->getSingleCommentForPost($post_id, $referance_id);
            //check of post does not exist for 
            if (!$comment_obj) {
                $resp_data = new Resp(Msg::getMessage(302)->getCode(), Msg::getMessage(302)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment] with msg :' . (string) $resp_data, array());
                return $resp_data;
            }
            $post_feed_service = $this->container->get('post_feeds.postFeeds');
            $creater_id = $comment_obj->getUserId();
            if($creater_id != $user_id) {
            $valid_security = $post_feed_service->checkSecurityForTagRemoving($user_id,$tag_type, $remove_tag_id);
            if(!$valid_security) {
                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), array());
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postRemoveposttaggingAction] with msg :' . (string) $resp_data, array());
                Utility::createResponse($resp_data);
            }
            }
            $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
            $post_feeds = $post_feeds_service_obj->removeTagging($comment_obj, $tag_type, $remove_tag_id);
            $dm->persist($post_feeds);
            $dm->flush();
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment] with msg :' . (string) $resp_data, array());
            return $resp_data;
        } catch (\Exception $ex) {
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [removeTagFromMediaComment] with exception :' . $ex->getMessage(), array());
            return $resp_data;
        }
    }
    
    /**
     * get comment list for post feed
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return boolean
     */
    public function postCommentFeedListAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedService = $this->getMediaFeedService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentFeedList]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id', 'item_id', 'comment_type');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentFeedList] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = array();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $data['limit_start'] = (isset($data['limit_start'])) ? $data['limit_start'] : self::COMMENT_LIST_LIMIT_START;
        $data['limit_size'] = (isset($data['limit_size'])) ? $data['limit_size'] : self::COMMENT_LIST_LIMIT_SIZE;

        $comment_type = Utility::getLowerCaseString($data['comment_type']);
        $post_type_check = array(Utility::getLowerCaseString(self::POST_COMMENT), Utility::getLowerCaseString(self::MEDIA_COMMENT));
        if (!in_array(Utility::getLowerCaseString($comment_type), $post_type_check)) {
            $resp_data = new Resp(Msg::getMessage(1094)->getCode(), Msg::getMessage(1094)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentFeedList] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        switch($comment_type){
           CASE Utility::getLowerCaseString(self::POST_COMMENT):
               $this->commentListForPostType($data);
               break;
           CASE Utility::getLowerCaseString(self::MEDIA_COMMENT):
               $this->commentListForMediaType($data);
               break;         
        }
        return true;
    }
    
    /**
     * Get comment list for post type
     * @param array $data
     */
    public function commentListForPostType($data) {
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedService = $this->getMediaFeedService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForPostType]', array());
        $user_id = $data['user_id'];
        $item_id = $data['item_id'];
        $comment_type = $data['comment_type'];
        $limit_start = $data['limit_start'];
        $limit_size = $data['limit_size'];
        
        $dm = $this->_getDocumentManager();
        
        $post_data = $dm->getRepository('PostFeedsBundle:PostFeeds')
                         ->findOneBy(array('id' => $item_id));      
        if (!$post_data) {
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $dm->clear();
        $dm = $this->_getDocumentManager();
        $post_comments = $dm->getRepository('PostFeedsBundle:PostFeeds')
                            ->getCommentListFromPost($item_id,$limit_start,$limit_size,TRUE);
        
        if (!$post_comments) {
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [editPostFeed] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }    
        $post_comment_count = 0;
        $comment_res_obj = array();
        if($post_comments) {
            $post_comment_count = $post_comments['size'];
            $comment_res_obj = $post_comments['result'];
        }
        $return_res = array();
        $post_comments = $comment_res_obj->getComments();
        $comment_tags = $mediaFeedService->getCommentTaggedInfo($post_comments); //get comment tagged users
        $user_ids = $comment_tags['users'];
        $shop_ids = $comment_tags['shops'];
        $club_ids = $comment_tags['clubs'];
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        $post_comments_arr = array();
        if(count($post_comments)>0) {
            foreach($post_comments as $post_comment) {
                $comment_obj = $postFeedsService->getSingleCommentObject($post_comment, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
                $post_comments_arr[] = $comment_obj;
            }
        }
        
        
        $return_res['comments'] = $post_comments_arr;
        $return_res['count'] = $post_comment_count;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res); //SUCCESS
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForPostType] with response: ' .Utility::encodeData($resp_data));
        Utility::createResponse($resp_data);
    }
    
    /**
     * 
     * @param type $data
     */
    public function commentListForMediaType($data) {
        $postFeedsService = $this->getPostFeedsService();
        $mediaFeedService = $this->getMediaFeedService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForMediaType]', array());
        $user_id = $data['user_id'];
        $item_id = $data['item_id'];
        $comment_type = $data['comment_type'];
        $limit_start = $data['limit_start'];
        $limit_size = $data['limit_size'];
        
        $dm = $this->_getDocumentManager();
        $post_data = $postFeedsService->getPostIdFromMediaId($item_id);   
        $media_obj = $dm->getRepository('PostFeedsBundle:MediaFeeds')
                ->findOneBy(array('id'=>$item_id));
        if($media_obj) {
            $media_post_obj = $media_obj->getPost();
            if(count($media_post_obj) == 0){
	       $return_res['comments'] = array();
               $return_res['count'] = 0;
               $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res); //SUCCESS
               $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForMediaType] with response: ' . (string) $resp_data);
               Utility::createResponse($resp_data); 
            }
            $post_id = $media_post_obj[0]->getId();
        }else {            
            $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForMediaType] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
//        if (empty($post_data)) {
//            $resp_data = new Resp(Msg::getMessage(1039)->getCode(), Msg::getMessage(1039)->getMessage(), array());
//            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForMediaType] with response: ' . (string) $resp_data);
//            Utility::createResponse($resp_data);
//        }
       
        $dm->clear();
        $dm = $this->_getDocumentManager();
        $post_comments = $dm->getRepository('PostFeedsBundle:PostFeeds')
                ->getCommentListFromPost($post_id,$limit_start,$limit_size,TRUE);
        $post_comment_count = 0;
        $comment_res_obj = array();
        if($post_comments) {
            $post_comment_count = $post_comments['size'];
            $comment_res_obj = $post_comments['result'];
        }
        
        $post_comments_arr = array();
        $return_res = array();
        $post_comments = $comment_res_obj->getComments();
        $comment_tags = $mediaFeedService->getCommentTaggedInfo($post_comments); //get comment tagged users
        $user_ids = $comment_tags['users'];
        $shop_ids = $comment_tags['shops'];
        $club_ids = $comment_tags['clubs'];
        $user_objects = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
        $shop_objects = $postFeedsService->getMultipleShopObjects(Utility::getUniqueArray($shop_ids)); //get shop info
        $club_objects = $postFeedsService->getMultiGroupObjectService(Utility::getUniqueArray($club_ids)); //get club info
        $post_comments_arr = array();
        if(count($post_comments)>0) {
            foreach($post_comments as $post_comment) {
                $comment_obj = $postFeedsService->getSingleCommentObject($post_comment, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
                $post_comments_arr[] = $comment_obj;
            }
        }
        $return_res['comments'] = $post_comments_arr;
        $return_res['count'] = $post_comment_count;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $return_res); //SUCCESS
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [commentListForPostType] with response: ' .Utility::encodeData($resp_data));
        Utility::createResponse($resp_data);
    }
    
    /**
     * Get notification feed service object
     * @return type
     */
    protected function getNotificationFeedsService() {
        return $this->container->get('post_feeds.notificationFeeds');
    }
    
    /**
     * Get Shared Object
     * @param array $data
     */
    public function getSharedObject($data)
    {
        $object_data = (object)$data;
        //code for responding the current post data..
        if(isset($object_data->share_type)){
        $object_data->object_type = (isset($object_data->object_type)) ? Utility::getLowerCaseString($object_data->object_type) : '';
        $object_data->share_type = (isset($object_data->share_type)) ? Utility::getLowerCaseString($object_data->share_type) : '';
        $object_data->object_id = (isset($object_data->object_id)) ? $object_data->object_id : 0;
        $content_share_data = array();
        $object_data->content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        $content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        //prepare the data for the contant share if te data is not present intilizing it to the default value
        $content_share_data['url'] = isset($content_share['url']) ? $content_share['url'] : '';
        $content_share_data['pageUrl'] = isset($content_share['pageUrl']) ? $content_share['pageUrl'] : '';
        $content_share_data['canonicalUrl'] = isset($content_share['canonicalUrl']) ? $content_share['canonicalUrl'] : '';
        if(isset($content_share['images']) && is_array($content_share['images'])) {
            $content_share_data['images'] = $content_share['images'];
        } else {
            $content_share_data['images'] = array();
        }
        $content_share_data['description'] = isset($content_share['description']) ? $content_share['description'] : '';
        $content_share_data['title'] = isset($content_share['title']) ? $content_share['title'] : '';
        $content_share_data['video'] = isset($content_share['video']) ? $content_share['video'] : '';
        $content_share_data['videoIframe'] = isset($content_share['videoIframe']) ? $content_share['videoIframe'] : '';
        $object_data->content_share = $content_share_data;
        }else{
            $object_data->object_type = '';
            $object_data->share_type = '';
            $object_data->object_id = '';
            $object_data->content_share = null;
        }
        return $object_data;
    }
}
