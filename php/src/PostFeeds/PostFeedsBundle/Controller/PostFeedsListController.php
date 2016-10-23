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

class PostFeedsListController extends Controller {

    CONST PERSONAL_POST = 1;
    CONST PROFESSIONAL_POST = 2;
    CONST PUBLIC_POST = 3;
    CONST USER_POST = 'user';
    CONST SHOP_POST = 'shop';
    CONST CLUB_POST = 'club';
    CONST POST_ACTIVE = 1;
    CONST DASHBOARD = 'DASHBOARD';
    CONST CLUB = 'CLUB';
    CONST SHOP = 'SHOP';
    CONST WALL = 'WALL';
    CONST SOCIAL_PROJECT = 'SOCIAL_PROJECT';
    
    private $tag_type = array('user_tag', 'shop_tag', 'club_tag');
    private $element_type = array('post');
    protected $dashboard_type = array('DASHBOARD', 'CLUB', 'SHOP', 'WALL', 'SOCIAL_PROJECT');
    protected $limit_start = 0;
    protected $limit_size = 20;

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
     * get post feed service
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds'); //PostFeeds\PostFeedsBundle\Services\PostFeedsService
    }

    private function _getDocumentManager() {
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }

    private function _getEntityManager() {
        return $this->getDoctrine()->getManager();
    }

    /**
     * find the post of different type
     * @param request object
     * @return json
     */
    public function postPostFeedListsAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [postPostFeedListsAction]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');
        $friend_follower_ids = array('personal_friend' => array(), 'professional_friend' => array(), 'following_users' => array());
        $friend_ship = array('is_personal'=> 0, 'is_professional' => 0);
        $posts = array();
        $is_own_wall = 0;
        $shop_id = $club_id = $social_project_id = 0;
        $friend_id = 0;
        $posts_count = 0;
        $requiredParams = array('dashboard_type', 'user_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [postPostFeedListsAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        //extract variables
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $dashboard_type = Utility::getUpperCaseString(Utility::getTrimmedString($data['dashboard_type']));
        $user_id = (int) $data['user_id'];
        $limit   = (int) (isset($data['limit_size']) ? $data['limit_size'] : $this->limit_size);
        $offset  = (int) (isset($data['limit_start']) ? $data['limit_start'] : $this->limit_start);
        $last_post_id = (isset($data['last_post_id']) ? $data['last_post_id'] : '');
        //check for dashboard type is valid
        $this->checkDashboardType($dashboard_type);

        $this->checkParams($data);
        //check for dashboard type
        if ($dashboard_type == Utility::getUpperCaseString(Utility::getTrimmedString(self::DASHBOARD))) {
            $friend_id = (int) $data['friend_id'];
            //find the friend and followers.
            $friend_follower_ids = $this->getFriendandFollowers($friend_id);
        } else if ($dashboard_type == Utility::getUpperCaseString(Utility::getTrimmedString(self::WALL))) {
            $friend_id = (int) $data['friend_id'];
            if ($user_id == $friend_id) {
                $is_own_wall = 1;
            } else { //check for friendship type
               $friend_ship = $this->checkFriendShip($user_id, $friend_id);
            }
        } else if ($dashboard_type == Utility::getUpperCaseString(Utility::getTrimmedString(self::SHOP))) {
            $shop_id = (int)$data['shop_id'];
        } else if ($dashboard_type == Utility::getUpperCaseString(Utility::getTrimmedString(self::CLUB))) {
            $club_id = (string)$data['club_id'];
        } else if ($dashboard_type == Utility::getUpperCaseString(Utility::getTrimmedString(self::SOCIAL_PROJECT))) {
            $social_project_id = (string)$data['social_project_id'];
        }
        
        $following_ids    = $friend_follower_ids['following_users'];
        $personal_friends = $friend_follower_ids['personal_friend'];
        $professional_friends = $friend_follower_ids['professional_friend'];
        $is_personal     = $friend_ship['is_personal'];
        $is_professional = $friend_ship['is_professional'];
        $user_ids_array = Utility::getUniqueArray(array_merge($following_ids, $personal_friends, $professional_friends, array($user_id), array($friend_id)));
        $shop_ids_array = Utility::getUniqueArray(array($shop_id));
        $club_ids_array = Utility::getUniqueArray(array($club_id));
        $social_project_ids_array = Utility::getUniqueArray(array($social_project_id));
        
        $posts = $dm->getRepository('PostFeedsBundle:PostFeeds')
                    ->getPosts($dashboard_type, $is_own_wall, $user_id, $friend_id, $following_ids, $personal_friends,  $professional_friends, $is_personal, $is_professional, $shop_id, $club_id, $social_project_id, $limit, $offset, 0, $last_post_id);
        $result_data = $postFeedsService->getPostFeeds($posts, $user_id, $user_ids_array, $shop_ids_array, $club_ids_array, $social_project_ids_array); //prepare the object
        if (count($result_data)) {
           $posts_count = $dm->getRepository('PostFeedsBundle:PostFeeds')
                    ->getPosts($dashboard_type, $is_own_wall, $user_id, $friend_id, $following_ids, $personal_friends,  $professional_friends, $is_personal, $is_professional, $shop_id, $club_id, $social_project_id, $limit, $offset, 1, $last_post_id); 
        }
        $res_data = array('post'=>$result_data, 'count'=>$posts_count);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $res_data);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [postPostFeedListsAction] with response: ' . Utility::encodeData($result_data));
        Utility::createResponse($resp_data);
    }

    /**
     * check dasboard type is valid
     * @param string $dashboard_type
     */
    private function checkDashboardType($dashboard_type) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkDashboardType]');
        $dashboard_types_constants = $this->dashboard_type;
        //check for dashboard type
        if (!in_array(Utility::getUpperCaseString(Utility::getTrimmedString($dashboard_type)), $dashboard_types_constants)) {
            $resp_data = new Resp(Msg::getMessage(1100)->getCode(), Msg::getMessage(1100)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkDashboardType] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        return true;
    }

    /**
     * check for needed paramteres on dashboard types parameters
     * @param array $data
     */
    private function checkParams($data) {
        $dashboard_type = Utility::getUpperCaseString(Utility::getTrimmedString($data['dashboard_type']));

        switch ($dashboard_type) {
            case self::DASHBOARD:
                $friend_id = isset($data['friend_id']) ? $this->checkFriend($data['friend_id']) : $this->checkParameter('friend_id');
                break;
            case self::WALL:
                $friend_id = isset($data['friend_id']) ? $this->checkFriend($data['friend_id']) : $this->checkParameter('friend_id');
                break;
            case self::SHOP:
                $shop_id = isset($data['shop_id']) ? $this->checkShop($data['shop_id']) : $this->checkParameter('shop_id');
                break;
            case self::CLUB:
                $club_id = isset($data['club_id']) ? $this->checkClub($data['club_id']) : $this->checkParameter('club_id');
                break;
            case self::SOCIAL_PROJECT:
                $social_project_id = isset($data['social_project_id']) ? $this->checkSocialProject($data['social_project_id']) : $this->checkParameter('social_project_id');
                break;
        }
        return true;
    }

    /**
     * check a paramter is not defined
     * @param string $parameter
     */
    private function checkParameter($parameter) {
        $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage() . Utility::getUpperCaseString($parameter), array());
        Utility::createResponse($resp_data);
    }

    /**
     * check a user is exists
     * @param int $friend_id
     */
    private function checkFriend($friend_id) {
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $friend_id));
        if (null === $user) {
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), array());
            Utility::createResponse($resp_data);
        }
        return true;
    }

    /**
     * check club is exists
     * @param string $club_id
     */
    private function checkClub($club_id) {
        $postFeedsService = $this->getPostFeedsService();
        $type_info = $postFeedsService->getGroupInfoById($club_id);
        if (empty($type_info)) { //if club does not exists
            $resp_data = new Resp(Msg::getMessage(1103)->getCode(), Msg::getMessage(1103)->getMessage(), array());
            Utility::createResponse($resp_data);
        }
        return true;
    }

    /**
     * check shop is exists
     * @param type $shop_id
     */
    private function checkShop($shop_id) {
        $seller_service = $this->container->get('user.shop.seller'); //call seller service[UserManager\Sonata\UserBundle\Services\SellerUserService]
        $store_obj = $seller_service->checkForActiveStore($shop_id);
        if (!$store_obj) { //if shop is not exists  or inactive
            $resp_data = new Resp(Msg::getMessage(413)->getCode(), Msg::getMessage(413)->getMessage(), array());
            Utility::createResponse($resp_data);
        }
        return true;
    }

    /**
     * check social project is exists
     * @param type $social_project_id
     */
    private function checkSocialProject($social_project_id) {
        $postFeedsService = $this->getPostFeedsService();
        $social_project_service = $this->getPostFeedsSocialProjectService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkSocialProject] with social project id: '. $social_project_id);
        //fetch not deleted project
        //$type_info = $dm->getRepository('PostFeedsBundle:SocialProject')->findOneBy(array('id'=>$social_project_id, 'is_delete'=>0));
        $result = $social_project_service->checkSocialProject($social_project_id);
        if (!$result) {
                $resp_data = new Resp(Msg::getMessage(1104)->getCode(), Msg::getMessage(1104)->getMessage(), array());//SOCIAL_PROJECT_DOES_NOT_EXISTS
                $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [checkSocialProject] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        return true;
    }
    
    /**
     * get personal, professional friend and following users
     * @param int $friend_id
     * @return array
     */
    private function getFriendandFollowers($friend_id) {
        $em  = $this->_getEntityManager(); //get entity manager
        //finding the following, friends ids.
        $followings_friend_users = $em->getRepository('UserManagerSonataUserBundle:UserFollowers')
                                      ->getFollowingsandFriends($friend_id); //null pass for all record in repository limit and offset
        $personal_friends_users = $professional_friends_users = $following_users = $citizen_writer = array();
        //if records.
        if (count($followings_friend_users)) {
            foreach ($followings_friend_users as $following_friend_users) {
                $status = $following_friend_users['status'];

                switch ($status) {
                    CASE 1: //personal friend users
                        $personal_friends_users[] = (int)$following_friend_users['id'];
                        break;
                    CASE 2: //professional friend users
                        $professional_friends_users[] = (int)$following_friend_users['id'];
                        break;
                    CASE 3; //following users
                        $following_users[] = (int)$following_friend_users['id'];
                        break;
                    default:
                        $citizen_writer[] = (int)$following_friend_users['id']; //this will remain blank array because we are not finding the citizen writer.
                }
            }
        }
        return array('personal_friend' => $personal_friends_users, 'professional_friend' => $professional_friends_users, 'following_users' => $following_users);
    }

    /**
     * check for friendship for a user
     * @param int $user_id
     * @param int $friend_id
     */
    private function checkFriendShip($user_id, $friend_id) {
        $em  = $this->_getEntityManager(); //get entity manager
        $is_personal_friend = $is_professional_friend = 0;
        $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                    ->checkPersonalFriendShip($user_id, $friend_id);
        if ($personal_friend_check) {
            $is_personal_friend = 1;
        }
        //cheking for professional friendship
        $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                        ->checkProfessionalFriendShip($user_id, $friend_id);
        if ($professional_friend_check) {
            $is_professional_friend = 1;
        }
        return array('is_personal'=>$is_personal_friend, 'is_professional'=>$is_professional_friend);
    }
    
    /**
     * get single post feed detail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json
     */
    public function postGetPostFeedDetailAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');

        $requiredParams = array('user_id', 'post_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = array();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = $data['user_id'];
        $post_id = $data['post_id'];
        
        $post_data = $dm->getRepository('PostFeedsBundle:PostFeeds')
                         ->findOneBy(array('id' => $post_id));      
        if (!$post_data) {
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = $postFeedsService->getPostFeedById($post_id, $user_id);
        $this->checkObjectExists($result_data);//check for the parent object is exists
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . Utility::encodeData($result_data));
        Utility::createResponse($resp_data);
       
    }
    
    
     /**
     * get single post feed detail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json
     */
    public function getPublicPostFeedDetailAction(Request $request) {
        $postFeedsService = $this->getPostFeedsService();
        $postFeedsService->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [getPublicPostFeedDetailAction]', array());
        $utilityService = $this->getUtilityService();

        $dm = $this->_getDocumentManager();

        $requiredParams = array('post_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = array();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $post_id = $data['post_id'];
        
        $post_data = $dm->getRepository('PostFeedsBundle:PostFeeds')
                         ->findOneBy(array('id' => $post_id));      
        if (!$post_data) {
            $resp_data = new Resp(Msg::getMessage(1101)->getCode(), Msg::getMessage(1101)->getMessage(), array());
            $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $user_id = $post_data->getUserId();
        $result_data = $postFeedsService->getPostFeedById($post_id, $user_id);
        $this->checkObjectExists($result_data);//check for the parent object is exists
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);
        $postFeedsService->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsListController] and function [GetPostFeedDetail] with response: ' . Utility::encodeData($result_data));
        Utility::createResponse($resp_data);
       
    }
    
    /**
     * check the parent object is exists.
     * @param array $data_object
     */
    public function checkObjectExists($data_object) {
        if (count($data_object)) {
            $post_type = isset($data_object['post_type']) ? $data_object['post_type'] : '';
            $upper_case_post_type = Utility::getUpperCaseString($post_type);
            switch ($upper_case_post_type) {
                case self::SOCIAL_PROJECT:
                    $social_project_id = isset($data_object['to_info']['id']) ? $data_object['to_info']['id'] : '';
                    $this->checkSocialProject($social_project_id);
                    break;
            }
        }
        return true;
    }

    /**
     * get post feed service
     * @return type
     */
    protected function getPostFeedsSocialProjectService() {
        return $this->container->get('post_feeds.socialProjects'); //PostFeeds\PostFeedsBundle\Services\SocialProjectService
    }    
}
