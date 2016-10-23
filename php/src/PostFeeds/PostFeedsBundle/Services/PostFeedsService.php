<?php

namespace PostFeeds\PostFeedsBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\PostFeeds;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Document\TaggingFeeds;
use PostFeeds\PostFeedsBundle\Document\ShopTagFeeds;
use PostFeeds\PostFeedsBundle\Document\ClubTagFeeds;
use PostFeeds\PostFeedsBundle\Document\UserTagFeeds;
use PostFeeds\PostFeedsBundle\Document\CommentFeeds;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

// service method class for seller user handling.
class PostFeedsService {

    protected $em;
    protected $dm;
    protected $container;

    CONST PERSONAL_POST = 1;
    CONST PROFESSIONAL_POST = 2;
    CONST PUBLIC_POST = 3;
    CONST PERSONAL_FRIEND = 1;
    CONST PROFESSIONAL_FRIEND = 2;
    CONST POST_ACTIVE = 1;
    CONST POST_COMMENT = 'POST_COMMENT';
    CONST POST_COMMENT_SIZE = 5;
    CONST IS_COUNT = 0;
    CONST USER_POST = 'USER';
    CONST SHOP_POST = 'SHOP';
    CONST CLUB_POST = 'CLUB';
    CONST SOCIAL_PROJECT_POST = 'SOCIAL_PROJECT';
    CONST MEDIA_COMMENT = 'MEDIA_COMMENT';

    protected $group_media_path = '/uploads/groups/original/';
    protected $group_media_path_thumb = '/uploads/groups/thumb/';

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
     *  function 
     * @param type $tag_object
     * @param type $friend
     */
    public function manageTagging($tag_object, $tagged_frnds) {
        $time = new \DateTime("now");
        $is_tagged = 0;
        $tagged_frnds = json_encode($tagged_frnds, JSON_NUMERIC_CHECK);
        $tagged_frnds = json_decode($tagged_frnds);
        $tagged_frnds = (array) $tagged_frnds;
        $user_friends = isset($tagged_frnds['user']) ? $tagged_frnds['user'] : array();
        $store_friends = isset($tagged_frnds['shop']) ? $tagged_frnds['shop'] : array();
        $club_friends = isset($tagged_frnds['club']) ? $tagged_frnds['club'] : array();
        if (count($user_friends) > 0 || count($store_friends) > 0 || count($club_friends) > 0) {
            $is_tagged = 1;
        }
//        $tagging_feeds = new TaggingFeeds();
//        $tag_object->addTag($tagging_feeds);
//        if (count($user_friends) > 0) {
//            $user_service = $this->container->get('user_object.service');
//            $users_data = $user_service->MultipleUserObjectService($user_friends);
//            foreach ($users_data as $value) {
//                $user_tag = new UserTagFeeds();
//                $user_tag->setUserInfo($value);
//                $user_tag->setCreatedAt($time);
//                $user_tag->setUpdatedAt($time);
//                $tagging_feeds->addUser($user_tag);
//                
//            }
//        }
//        if (count($store_friends) > 0) {
//            $user_service = $this->container->get('user_object.service');
//            $store_data = $user_service->getMultiStoreObjectService($store_friends);
//            foreach ($store_data as $value) {
//                $store_tag = new ShopTagFeeds();
//                $store_tag->setShopInfo($value);
//                $store_tag->setCreatedAt($time);
//                $store_tag->setUpdatedAt($time);
//                $tagging_feeds->addShop($store_tag);
//                
//            }
//        }
        $tag_object->setTagUser($user_friends);
        $tag_object->setTagShop($store_friends);
        $tag_object->setTagClub($club_friends);
        $tag_object->setIsTag($is_tagged);
        return $tag_object;
    }

    public function checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id) {
        $em = $this->em;
        $friends_results = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkPersonalProfessionalFriendship($user_id, $to_id);
        $friends_array = array(self::PUBLIC_POST);
        if (count($friends_results) > 0) {
            foreach ($friends_results as $friends_result) {
                $status = $friends_result['status'];
                if ($status == self::PERSONAL_FRIEND) { //personal
                    $friends_array[] = self::PERSONAL_POST;
                } else if ($status == self::PROFESSIONAL_FRIEND) { //professional
                    $friends_array[] = self::PROFESSIONAL_POST;
                }
            }
        }
        $friend_unique_array = array_unique($friends_array); //both
        $allow_privacy_setting = array_unique(array_merge($allow_other_user_wall_privacy_setting, $allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $friend_unique_array));
        return $allow_privacy_setting;
    }

    /**
     *  function for removing the tagging from a post
     * @param type $tag_object
     * @param type $tag_type
     * @param type $friend_id
     */
    public function removeTagging($tag_object, $tag_type, $friend_id) {

        //check tag_type for get to know which type of user is untagged
        if (Utility::matchString('user_tag', $tag_type)) {
            $tagged_friends = $tag_object->getTagUser();
            $new_tagged_friends = $this->removeTaggedFriend($tagged_friends, $friend_id);
            $tag_object = $tag_object->setTagUser($new_tagged_friends);
        }
        if (Utility::matchString('shop_tag', $tag_type)) {
            $tagged_friends = $tag_object->getTagShop();
            $new_tagged_friends = $this->removeTaggedFriend($tagged_friends, $friend_id);
            $tag_object = $tag_object->setTagShop($new_tagged_friends);
        }
        if (Utility::matchString('club_tag', $tag_type)) {
            $tagged_friends = $tag_object->getTagClub();
            $new_tagged_friends = $this->removeTaggedFriend($tagged_friends, $friend_id);
            $tag_object = $tag_object->setTagClub($new_tagged_friends);
        }

        $tagged_users = $tag_object->getTagUser();
        $tagged_stores = $tag_object->getTagShop();
        $tagged_clubs = $tag_object->getTagClub();

        //if all the tagging is removed then set is_tag status to 0
        if (count($tagged_users) == 0 && count($tagged_stores) == 0 && count($tagged_clubs) == 0) {
            $tag_object->setIsTag(0);
        }

        return $tag_object;
    }

    /**
     * function for removing the tagged friend
     * @param type $tagged_friends
     * @param type $friend_id
     */
    private function removeTaggedFriend($tagged_friends, $friend_id) {
        $index = array_search($friend_id, $tagged_friends);
        if ($index !== false) {
            $index = array_search($friend_id, $tagged_friends);
            unset($tagged_friends[$index]);
        }

        return array_values($tagged_friends);
    }

    /**
     * Craete post
     * @param type $data
     */
    public function createMediaPost($data) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [createMediaPost] with data :' . Utility::encodeData($data), array());
        $dm = $this->dm;
        //get postid
        $media_id = $data['item_id'];
        //$post_obj = $this->getPostIdFromMediaId($media_id);
        //get media object
        $mediaFeedService = $this->container->get('post_feeds.MediaFeeds');
        $media_obj = $mediaFeedService->getMediaObject($media_id);
        $time = new \DateTime("now");
        $user_id = $media_obj->getUserId();
        $to_id = $media_obj->getUserId();
        $post_type = $data['comment_type'];
        try {
            $post_feeds = new PostFeeds();
            $post_feeds->setUserId($user_id);
            $post_feeds->setToId($to_id);
            $post_feeds->setTitle('');
            $post_feeds->setDescription('');
            $post_feeds->setLinkType('');
            $post_feeds->setIsActive(self::POST_ACTIVE);
            $post_feeds->setPrivacySetting(1);
            $post_feeds->setCreatedAt($time);
            $post_feeds->setUpdatedAt($time);
            $post_feeds->setPostType($post_type);
            $post_feeds->setTypeInfo('');
            $post_feeds->setIsComment(1);
            $post_feeds->setIsRate(0);
            $post_feeds->setIsTag(0);
            $post_feeds->setIsMedia(0);
            $dm->persist($post_feeds); //storing the post data.
            $dm->flush();
            //update ACL for a user
            $this->updateAclAction($user_id, $post_feeds);
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [createMediaPost] with SUCCESS');
            return $post_feeds;
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [createMediaPost] with exception :' . $ex->getMessage(), array());
            return false;
        }
    }

    /**
     * Add Comment
     * @param object $post_obj
     * @param array $data
     */
    public function addComment($post_obj, $data, $type = null,$tagged_data) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [addComment] with data :' . Utility::encodeData($data), array());
        $dm = $this->dm;
        $user_id = $data['user_id'];
        $item_id = $data['item_id']; //media id
        $is_media = 0;
        $media_ids = (isset($data['media_id'])) ? $data['media_id'] : array();
        $tagging = (isset($data['tagging'])) ? $data['tagging'] : array();
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($user_id);
        $text = $data['description'];
        $time = new \DateTime("now");
        $comment = new CommentFeeds();
        $comment->setUserId($user_id);
        $comment->setText($text);
        $comment->setUpdatedAt($time);
        $comment->setCreatedAt($time);
        $comment->setIsActive(1);
        $comment->setUserInfo($user_info);
        //add media collection
        foreach ($media_ids as $media_id) {
            $feed_media = $dm->getRepository('PostFeedsBundle:MediaFeeds')
                    ->findOneBy(array('id' => $media_id));
            $comment->addMedia($feed_media);
            $is_media = 1;
        }
        $comment->setIsMedia($is_media);
        $comment = $this->manageTagging($comment, $tagging);
        $post_obj->addComment($comment);
        try {
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
            //mark the post as is_comment true
            $this->markPostComment($post_obj->getId(), 1); //mark post as commented
            $this->updateAclAction($user_id, $comment);
            
            $this->container->get('post_feeds.notificationFeeds')->sendCommentNotification($user_info, $type, $item_id, $tagging, $post_obj,$tagged_data);
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [addComment] with SUCCESS');
            return $comment;
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [addComment] with exception :' . $ex->getMessage(), array());
            return false;
        }
    }

    /**
     * Get Post Object
     * @param string $post_id
     * @return object
     */
    public function getPostObject($post_id) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostObject] with post_id :' . $post_id, array());
        $dm = $this->dm;
        $post_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('id' => $post_id));
        if (count($post_obj) == 0) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostObject] with message: No Post found', array());
            return false;
        }
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostObject] with message: Success', array());
        return $post_obj;
    }

    /**
     * Get post from media id
     * @param string $media_id
     */
    public function getPostIdFromMediaId($media_id) {
        $dm = $this->dm;
        $post_obj = $dm
                ->getRepository('PostFeedsBundle:PostFeeds')
                ->getPostByMediaId($media_id);
        return $post_obj;
    }

    /**
     * Mark post is_comment status
     * @param string $post_id
     * @param int $status
     * @return boolean
     */
    public function markPostComment($post_id, $status) {
        $dm = $this->dm;
        $post_obj = $this->getPostObject($post_id);
        $post_obj->setIsComment($status);
        try {
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [markPostComment] with exception :' . $ex->getMessage(), array());
        }
        return true;
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.postfeeds_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    /**
     * return post detail by post id
     * @param type $post_id
     * @return array
     */
    public function getPostFeedById($post_id, $user_id) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostFeedById] with data :' . $post_id, array());
        $dm = $this->dm;
        $post_data = array();
        $result_data = $user_ids_array = $shop_ids_array = $club_ids_array = $social_project_ids_array = array();
        $service_media_obj = $this->container->get('post_feeds.MediaFeeds');
        $post_feeds = $dm->getRepository('PostFeedsBundle:PostFeeds')
                ->findOneBy(array('id' => $post_id));
        $post_data = $this->getPostFeeds(array($post_feeds), $user_id, $user_ids_array, $shop_ids_array, $club_ids_array, $social_project_ids_array);
        if (count($post_data) > 0) {
            return $post_data[0];
        }
        return $post_data;
        /*
          if ($post_feeds) {
          $post_feeds_media = $post_feeds->getMedia();
          $post_media_obj = $service_media_obj->getGalleryMedia($post_feeds_media);
          $post_tag_obj = $this->getPostTagObj($post_feeds);
          $post_comments = $post_feeds->getComments();
          $post_comments_arr = array();
          $count = self::IS_COUNT;
          if (count($post_comments)) {
          foreach ($post_comments as $post_comment) {
          if (self::POST_COMMENT_SIZE == $count) {
          break;
          }
          $comment_obj = $this->getSingleCommentObj($post_comment);
          $post_comments_arr[] = $comment_obj;
          $count++;
          }
          }
          $result_data = array(
          'id' => $post_feeds->getId(),
          'user_id' => $post_feeds->getUserId(),
          'to_id' => $post_feeds->getToId(),
          'title' => $post_feeds->getTitle(),
          'description' => $post_feeds->getDescription(),
          'link_type' => $post_feeds->getLinkType(),
          'is_active' => $post_feeds->getIsActive(),
          'privacy_setting' => $post_feeds->getPrivacySetting(),
          'created_at' => $post_feeds->getCreatedAt(),
          'updated_at' => $post_feeds->getUpdatedAt(),
          'post_type' => $post_feeds->getPostType(),
          'type_info' => $post_feeds->getTypeInfo(),
          'is_comment' => $post_feeds->getIsComment(),
          'is_rate' => $post_feeds->getIsRate(),
          'is_tag' => $post_feeds->getIsTag(),
          'is_media' => $post_feeds->getIsMedia(),
          'post_media' => $post_media_obj,
          'user_tag' => $post_tag_obj['user'],
          'shop_tag' => $post_tag_obj['shop'],
          'club_tag' => $post_tag_obj['club'],
          'comments' => $post_comments_arr
          );
          }
          $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostFeedById] with data Success :' . Utility::encodeData($result_data), array());
          return $result_data;
         */
    }

    /**
     * get Post media object
     * @param type $post_feeds_media
     * @return type
     */
    public function getPostMediaObj($post_feeds_media) {
        $media_arr = array();
        if ($post_feeds_media) {
            foreach ($post_feeds_media as $media_record) {
                $feed_media = array(
                    'id' => $media_record->getId(),
                    'item_id' => $media_record->getItemId(),
                    'media_name' => $media_record->getMediaName(),
                    'media_type' => $media_record->getMediaType(),
                    'type' => $media_record->getType(),
                    'status' => $media_record->getStatus(),
                    'is_featured' => $media_record->getIsFeatured(),
                    'created_at' => $media_record->getCreatedAt(),
                    'updated_at' => $media_record->getUpdatedAt(),
                );
                $media_arr[] = $feed_media;
            }
        }
        return $media_arr;
    }

    /**
     * get Post tag object
     * @param type $post_feeds_tag
     * @return type
     */
    public function getPostTagObj($post_feeds) {
        $tag_arr = array();
        $user_service = $this->container->get('user_object.service');
        $tag_users_object = $user_service->MultipleUserObjectService($post_feeds->getTagUser());
        $tag_store_details = $user_service->getMultiStoreObjectService($post_feeds->getTagShop());
        $tag_club_details = $this->getMultiGroupObjectService($post_feeds->getTagClub());
        if ($post_feeds) {
            $tag_arr = array(
                'user' => array_values($tag_users_object),
                'shop' => array_values($tag_store_details),
                'club' => array_values($tag_club_details),
            );
        }
        return $tag_arr;
    }

    /**
     * return group detatil object by group id
     * @param type $group_id
     * @return array
     */
    public function getGroupInfoById($group_id) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getGroupInfoById] with data  :' . $group_id, array());
        $dm = $this->dm;
        $result_data = array();
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));

        if ($group) {
            $result_data = array(
                'id' => $group->getId(),
                'title' => $group->getTitle(),
                'description' => $group->getDescription(),
                'group_status' => $group->getGroupStatus(),
                'group_owner_id' => $group->getOwnerId(),
                'is_delete' => $group->getIsDelete(),
                'vote_count' => $group->getVoteCount(),
                'vote_sum' => $group->getVoteSum(),
                'averate_rating' => $group->getAvgRating(),
                'created_at' => $group->getCreatedAt(),
                'updated_at' => $group->getUpdatedAt(),
            );
        }
        $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getGroupInfoById] with data success :' . Utility::encodeData($result_data), array());
        return $result_data;
    }

    /**
     * get club object
     * @param array $group_id
     * return array
     */
    public function getMultiGroupObjectService($group_id) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultiGroupObjectService] with data:' . Utility::encodeData($group_id), array());
        $dm = $this->dm;
        $group_obj_arr = array();
        $group_objs = array();
        //get group object
        $group_objs = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getAllGroupInfo($group_id);

        if ($group_objs) {
            $group_profile_info = $this->getGroupProfileImages($group_id); //getting group profile images info
            foreach ($group_objs as $group_obj) {
                $current_group_id = $group_obj->getId();

                $group_obj_arr[$current_group_id] = array(
                    'id' => $group_obj->getId(),
                    'title' => $group_obj->getTitle(),
                    'description' => $group_obj->getDescription(),
                    'group_status' => $group_obj->getGroupStatus(),
                    'group_owner_id' => $group_obj->getOwnerId(),
                    'is_delete' => $group_obj->getIsDelete(),
                    'vote_count' => $group_obj->getVoteCount(),
                    'vote_sum' => $group_obj->getVoteSum(),
                    'averate_rating' => $group_obj->getAvgRating(),
                    'created_at' => $group_obj->getCreatedAt(),
                    'updated_at' => $group_obj->getUpdatedAt(),
                );
                if (isset($group_profile_info[$current_group_id])) {
                    $group_obj_arr[$current_group_id]['profile_images'] = $group_profile_info[$current_group_id];
                } else {
                    $group_obj_arr[$current_group_id]['profile_images'] = null;
                }
            }
        }
        $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultiGroupObjectService] with data success:' . Utility::encodeData($group_obj_arr), array());
        return $group_obj_arr;
    }

    /**
     * 
     * @param type $data_obj
     * @param type $tagging
     */
    public function addCommentTag($data_obj, $tagging) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [addCommentTag] with tag id :' . Utility::encodeData($tagging), array());
        $dm = $this->dm;
        $post_feeds_service_obj = $this->container->get('post_feeds.postFeeds'); //call media feed service
        $data_obj = $post_feeds_service_obj->manageTagging($data_obj, $tagging);
        try {
            $dm->persist($data_obj); //storing the post data.
            $dm->flush();
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [addCommentTag] with exception :' . $ex->getMessage(), array());
        }
        return true;
    }

    /**
     * get single object from comment object
     * @param type $post_comment
     * @return array
     */
    public function getSingleCommentObj($post_comment) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSingleCommentObj] with data:' . Utility::encodeData($post_comment), array());
        $comment_obj = array();
        if ($post_comment) {
            $service_media_obj = $this->container->get('post_feeds.MediaFeeds');
            $comment_media_obj = $service_media_obj->getGalleryMedia($post_comment->getMedia());
            $comment_tag_obj = $this->getPostTagObj($post_comment);
            $comment_obj = array(
                'id' => $post_comment->getId(),
                'user_id' => $post_comment->getUserId(),
                'comment_user_info' => $post_comment->getUserInfo(),
                'comment_text' => $post_comment->getText(),
                'is_active' => $post_comment->getIsActive(),
                'create_date' => $post_comment->getCreatedAt(),
                'updated_at' => $post_comment->getUpdatedAt(),
                'user_tag' => $comment_tag_obj['user'],
                'shop_tag' => $comment_tag_obj['shop'],
                'club_tag' => $comment_tag_obj['club'],
                'comment_media_info' => $comment_media_obj
            );
        }
        $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSingleCommentObj] with data success:' . Utility::encodeData($comment_obj), array());
        return $comment_obj;
    }

    /**
     * function for checking if the user who is removing tag is velid user or not 
     * @param type $tag_type
     * @param type $remove_tag_id
     */
    public function checkSecurityForTagRemoving($user_id, $tag_type, $remove_tag_id) {

        $tag_type = Utility::getTrimmedString($tag_type);
        $tag_type_compare = Utility::getUpperCaseString($tag_type);

        switch ($tag_type_compare) {
            case "USER_TAG" :
                $check = $this->checkUserSecurity($user_id, $remove_tag_id);
                break;
            case "SHOP_TAG" :
                $check = $this->checkShopSecurity($user_id, $remove_tag_id);
                break;
            case "CLUB_TAG" :
                $check = $this->checkClubSecurity($user_id, $remove_tag_id);
                break;
        }
        return $check;
    }

    /**
     *  check if the current user is the removing its own tag
     * @param type $user_id
     * @param type $remove_tag_id
     * @return boolean
     */
    public function checkUserSecurity($user_id, $remove_tag_id) {
        //check if current user_id and remove tag id is same 
        if ($user_id == $remove_tag_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  check if the current user is the owner of the store
     * @param type $user_id
     * @param type $remove_tag_id
     * @return boolean
     */
    public function checkShopSecurity($user_id, $remove_tag_id) {
        //check if current user_id is owner of store id 
        $store_service = $this->container->get('store_manager_store.storeUpdate');
        $store_obj = $store_service->checkStoreOwner($remove_tag_id, $user_id);
        if (!$store_obj) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *  check if the current user is the owner of the club
     * @param type $user_id
     * @param type $remove_tag_id
     * @return boolean
     */
    public function checkClubSecurity($user_id, $remove_tag_id) {
        //check if current user_id is owner of club id 
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [checkClubSecurity]', array());
        $dm = $this->dm;
        $result_data = array();
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $remove_tag_id, 'owner_id' => $user_id));
        if (!$group) {
            return false;
        }
        return true;
    }

    /**
     * get Post media object
     * @param type $projects
     * @return type
     */
    public function getProjectObj($projects) {
        $project_arr = array();
        $user_service = $this->container->get('user_object.service');
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        if ($projects) {
            foreach ($projects as $project) {
                $address = $project->getAddress() ? $project->getAddress() : array();
                $addres_data = $this->getAddress($address);
                $cover_data = $project->getCoverImg() ? $project->getCoverImg() : array();
                $cover_info = $this->getCoverImageinfo($cover_data);
                $medias = $project->getMedias() ? $project->getMedias() : array();
                $gallery_info = $service_obj->getGalleryMedia($medias);
                $owner_id = $project->getOwnerId();
                $owner_info = $user_service->UserObjectService($owner_id);
                $we_want = $project->getWeWant();

                $project_data = array(
                    'project_id' => $project->getId(),
                    'project_title' => $project->getTitle(),
                    'email' => $project->getEmail(),
                    'website' => $project->getWebsite(),
                    'project_desc' => $project->getDescription(),
                    'created_on' => $project->getCreatedAt(),
                    'address' => $addres_data,
                    'project_owner' => $owner_info,
                    'we_want_count' => $we_want,
                    'cover_img' => $cover_info,
                    'gallery_info' => $gallery_info,
                    'we_wanted' => 0, //set default 0, it will over write in controller after getting actual data from collection
                    'is_delete' =>$project->getIsDelete()
                );
                $project_arr[] = $project_data;
            }
        }
        return $project_arr;
    }

    public function getAddress($address) {
        $address_id = $address[0]->getId();
        if ($address_id) {
            $address_data = array(
                'location' => $address[0]->getLocation(),
                'city' => $address[0]->getCity(),
                'country' => $address[0]->getCountry(),
                'longitude' => $address[0]->getLongitude(),
                'latitude' => $address[0]->getLatitude()
            );
            return $address_data;
        }
    }

    /**
     * 
     * @param type $medias
     * @return string
     */
    public function getCoverImageinfo($medias) {
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        //$media_data = array();
        foreach ($medias as $media) {
            $media_name = $media->getImageName();
            $x = $media->getX();
            $y = $media->getY();
            $cover_id = '';
            $cover_name = '';
            foreach ($media_name as $res) {
                $cover_id = $res->getId();
            }
            $feed_media = $this->dm->getRepository('PostFeedsBundle:MediaFeeds')
                    ->findBy(array('id' => $cover_id));
            $id = '';
            foreach ($feed_media as $media) {
                $id = $media->getId();
                $cover_name = $media->getMediaName();
            }
            $ori_image = $aws_path . $this->container->getParameter('media_path') . $cover_name;
            $thumb_image = $aws_path . $this->container->getParameter('social_project_cover_path') . $cover_name;
            $media_data = array(
                'cover_id' => $id,
                'ori_image' => $ori_image,
                'thum_image' => $thumb_image,
                'x_cord' => $x,
                'y-cord' => $y
            );
        }
        return $media_data;
    }

    /**
     * creating the ACL 1
     * for the entity for a user
     * @param object $sender_user
     * @param object $dashboard_comment_entity
     * @return none
     */
    public function updateAclAction($user_id, $object_entity) {
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($object_entity);
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
        return true;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Get User role for store
     * @param int $comment_id
     * @param int $user_id
     * @return int
     */
    public function userCommentRole($comment_obj, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        try {
            $aclProvider = $this->container->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($comment_obj); //entity
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            return false;
        }
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
     * Edit Comment
     * @param object $post_obj
     * @param object $comment_obj
     * @param array $data
     * @return boolean
     */
    public function editComment($post_obj, $comment_obj, $data) {
        $dm = $this->dm;
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [editComment] with data :' . Utility::encodeData($data), array());
        $user_id = $data['user_id'];
        $tagging = (isset($data['tagging'])) ? $data['tagging'] : array();
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($user_id);
        $text = $data['description'];
        $time = new \DateTime("now");
        $comment_obj->setUserId($user_id);
        $comment_obj->setText($text);
        $comment_obj->setUpdatedAt($time);
        $comment_obj->setIsActive(1);
        $comment_obj->setUserInfo($user_info);
        $comment_obj = $this->manageTagging($comment_obj, $tagging);
        try {
            $dm->persist($comment_obj); //storing the post data.
            $dm->flush();
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [editComment] with SUCCESS');
            return $comment_obj;
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsService] and function [editComment] with exception :' . $ex->getMessage(), array());
            return false;
        }
    }

    /**
     * Delete Comment
     * @param object $post_obj
     * @param object $comment_obj
     * @return boolean
     */
    public function deleteComment($post_obj, $comment_obj) {
        $dm = $this->dm;
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [deleteComment] ', array());
        try {
            $post_obj->removeComment($comment_obj);
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
            //get remain comment count
            $available_comments = $post_obj->getComments();
            if (count($available_comments) == 0) {
                $this->markPostComment($post_obj->getId(), 0); //mark post as not commented
            }
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [deleteComment] with SUCCESS');
            return $comment_obj;
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsService] and function [deleteComment] with exception :' . $ex->getMessage(), array());
            return false;
        }
    }

    /**
     * get post feeds lists
     * @param object array $posts
     * @param inr $user_id
     * @param array $user_ids_array
     * @param array $shop_ids_array
     * @param array $club_ids_array
     * @param array $social_project_ids_array
     * @return type
     */
    public function getPostFeeds($posts, $user_id, $user_ids_array, $shop_ids_array, $club_ids_array, $social_project_ids_array) {
        
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostFeeds]', array());
        $dm = $this->dm;
        $result_data = array();
        $comment_count = 0;
        $service_media_obj = $this->container->get('post_feeds.MediaFeeds');
        if (count($posts)) {
            //get user, shop, club, social_project objects.
            $objects = $this->getEntityObjects($posts, $user_ids_array, $shop_ids_array, $club_ids_array, $social_project_ids_array);
            $user_objects = $objects['users'];
            $shop_objects = $objects['shops'];
            //get shop owner
            $shop_owners = $this->getShopOwners($shop_objects);
            foreach($shop_objects as $key => $value){
                $shop_objects[$key]['owner_id'] = (isset($shop_owners[$key])) ? $shop_owners[$key] : 0;
            }
            $club_objects = $objects['clubs'];
            $social_project_objects = $objects['social_projects'];
            foreach ($posts as $post_feeds) {
                $post_feeds_media = $post_feeds->getMedia();
                $post_media_obj = $service_media_obj->getGalleryMedia($post_feeds_media);
                $post_tag_obj = $this->getTagObjects($post_feeds, $user_objects, $shop_objects, $club_objects); //get tagged entity objects
                $post_rated_users = $this->getRatedUsers($post_feeds, $user_id, $user_objects, $shop_objects, $club_objects);
                $post_comments = $post_feeds->getComments();
                $comment_count = count($post_comments);
                $post_comments_arr = $service_media_obj->getCommentsBySize($post_comments, $user_objects, $shop_objects, $club_objects, $user_id);
                //get post type info
                $post_info = $this->getPostInfo($post_feeds, $user_objects, $shop_objects, $club_objects, $social_project_objects);
                $object_type = $post_feeds->getShareObjectType();
                $object_id = $post_feeds->getShareObjectId();
                $object_info = $this->prepareObjectInfo($object_type,$object_id);
                $content_share = $post_feeds->getContentShare();
                if(is_array($content_share)){
                    $content_share = (count($content_share) == 0) ? null : $content_share;
                }
                $result_data[] = array(
                    'id' => $post_feeds->getId(),
                    'user_id' => $post_feeds->getUserId(),
                    'user_info' => $user_objects[$post_feeds->getUserId()],
                    'to_info' => $post_info['to_id'],
                    'title' => $post_feeds->getTitle(),
                    'description' => $post_feeds->getDescription(),
                    'link_type' => $post_feeds->getLinkType(),
                    'is_active' => $post_feeds->getIsActive(),
                    'privacy_setting' => $post_feeds->getPrivacySetting(),
                    'created_at' => $post_feeds->getCreatedAt(),
                    'updated_at' => $post_feeds->getUpdatedAt(),
                    'post_type' => $post_feeds->getPostType(),
                    'type_info' => $post_info['type_info'],
                    'vote_count' => $post_feeds->getVoteCount(),
                    'vote_sum' => $post_feeds->getVoteSum(),
                    'avg_rating' => $post_feeds->getAvgRating(),
                    'is_comment' => $post_feeds->getIsComment(),
                    'is_rate' => $post_feeds->getIsRate(),
                    'is_tag' => $post_feeds->getIsTag(),
                    'is_media' => $post_feeds->getIsMedia(),
                    'media' => $post_media_obj,
                    'user_tag' => $post_tag_obj['user'],
                    'shop_tag' => $post_tag_obj['shop'],
                    'club_tag' => $post_tag_obj['club'],
                    'rated_users' => $post_rated_users['rated_users'],
                    'is_rated' => $post_rated_users['is_rated'],
                    'current_user_rate' => $post_rated_users['current_user_rate'],
                    'comments' => $post_comments_arr,
                    'comment_count' => $comment_count,
                    'share_type' => $post_feeds->getShareType(),
                    'content_share'=> $content_share,
                    'object_type'=> $object_type,
                    'object_info'=> $object_info,
                );
            }
        }
        $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getPostFeeds] with data Success :' . Utility::encodeData($result_data), array());
        return $result_data;
    }

    /**
     * get entity objects
     * @param type $posts
     * @param type $user_ids_array
     * @param type $shop_ids_array
     * @param type $club_ids_array
     * @param type $social_project_ids_array
     */
    public function getEntityObjects($posts, $user_ids_array, $shop_ids_array, $club_ids_array, $social_project_ids_array) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getEntityObjects]', array());
        $dm = $this->dm;
        $users = $clubs = $shops = $social_projects = array();
        $user_ids = $shop_ids = $club_ids = $social_project_ids = array();
        $social_project_objects = array();
        foreach ($posts as $post) {
            $users[] = $post->getUserId();
            if (count($post->getTagUser())) { //find tag users
                foreach ($post->getTagUser() as $tag_user) {
                    $users[] = $tag_user;
                }
            }
            if (count($post->getTagShop())) { //find tag shop
                foreach ($post->getTagShop() as $tag_shop) {
                    $shops[] = $tag_shop;
                }
            }
            if (count($post->getTagClub())) { //find tag clubs
                foreach ($post->getTagClub() as $tag_club) {
                    $clubs[] = $tag_club;
                }
            }
            if (count($post->getRate())) { //find rated users
                foreach ($post->getRate() as $rate) {
                    $users[] = $rate->getUserId();
                }
            }
            if (Utility::getUpperCaseString(Utility::getTrimmedString($post->getPostType())) == Utility::getUpperCaseString(Utility::getTrimmedString(self::USER_POST))) {
                $users[] = $post->getToId();
            } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post->getPostType())) == Utility::getUpperCaseString(Utility::getTrimmedString(self::SHOP_POST))) {
                $shops[] = $post->getToId();
            } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post->getPostType())) == Utility::getUpperCaseString(Utility::getTrimmedString(self::CLUB_POST))) {
                $type_info = $post->getTypeInfo();
                $clubs[] = isset($type_info['id']) ? $type_info['id'] : '';
            } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post->getPostType())) == Utility::getUpperCaseString(Utility::getTrimmedString(self::SOCIAL_PROJECT_POST))) {
                $type_info = $post->getTypeInfo();
                $social_projects[] = isset($type_info['id']) ? $type_info['id'] : '';
            }

            $comments = $post->getComments();
            $comments_entity = $this->getCommentEntityObject($comments);
            $users[] = $comments_entity['users'];
            $shops[] = $comments_entity['shops'];
            $clubs[] = $comments_entity['clubs'];
            $social_projects[] = $comments_entity['social_projects'];
        }
        foreach ($users as $user) { //for users
            if (is_array($user)) {
                foreach ($user as $id)
                    $user_ids[] = $id;
            } else {
                $user_ids[] = $user;
            }
        }
        foreach ($shops as $shop) { //for shops
            if (is_array($shop)) {
                foreach ($shop as $id)
                    $shop_ids[] = $id;
            } else {
                $shop_ids[] = $shop;
            }
        }
        foreach ($clubs as $club) { //for clubs
            if (is_array($club)) {
                foreach ($club as $id)
                    $club_ids[] = $id;
            } else {
                $club_ids[] = $club;
            }
        }
        foreach ($social_projects as $social_project) { //for social_projects
            if (is_array($social_project)) {
                foreach ($social_project as $id)
                    $social_project_ids[] = $id;
            } else {
                $social_project_ids[] = $social_project;
            }
        }
        $user_objects = $this->getMultipleUserObjects(Utility::getUniqueArray($user_ids));
        $shop_objects = $this->getMultipleShopObjects(Utility::getUniqueArray($shop_ids));
        $club_objects = $this->getMultiGroupObjectService(Utility::getUniqueArray($club_ids));
        $social_project_objects = $this->getMultipleSocialProjectObjects(Utility::getUniqueArray($social_project_ids));
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getEntityObjects]', array());
        return array('users' => $user_objects, 'shops' => $shop_objects, 'clubs' => $club_objects, 'social_projects' => $social_project_objects);
    }

    /**
     * getting the comment entity object
     * @param object $comments
     */
    public function getCommentEntityObject($comments) {
        $dm = $this->dm;
        $users = $shops = $clubs = array();
        foreach ($comments as $comment) {
            $users[] = $comment->getUserId();

            if (count($comment->getTagUser())) { //find tag users
                foreach ($comment->getTagUser() as $tag_user) {
                    $users[] = $tag_user;
                }
            }
            if (count($comment->getTagShop())) { //find tag shop
                foreach ($comment->getTagShop() as $tag_shop) {
                    $shops[] = $tag_shop;
                }
            }
            if (count($comment->getTagClub())) { //find tag clubs
                foreach ($comment->getTagClub() as $tag_club) {
                    $clubs[] = $tag_club;
                }
            }
            if (count($comment->getRate())) { //find rated users
                foreach ($comment->getRate() as $rate) {
                    $users[] = $rate->getUserId();
                }
            }
        }
        return array('users' => Utility::getUniqueArray($users), 'shops' => Utility::getUniqueArray($shops),
            'clubs' => Utility::getUniqueArray($clubs), 'social_projects' => Utility::getUniqueArray(array()));
    }

    /**
     * return the user objects
     * @param array $user_ids
     * @return array $users_object
     */
    public function getMultipleUserObjects($user_ids) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleUserObjects]', array());
        $user_service = $this->container->get('user_object.service');
        $users_object = $user_service->MultipleUserObjectService($user_ids);
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleUserObjects]', array());
        return $users_object;
    }

    /**
     * return the shop objects
     * @param array $shop_ids
     * @return array $shop_object
     */
    public function getMultipleShopObjects($shop_ids) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleShopObjects]', array());
        $user_service = $this->container->get('user_object.service');
        $shop_object = $user_service->getMultiStoreObjectService($shop_ids);
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleShopObjects]', array());
        return $shop_object;
    }

    /**
     * get tag entity object
     * @param type $feed
     * @param array $user_objects
     * @param array $shop_objects
     * @param array $club_objects
     * @return array $tag_arr
     */
    public function getTagObjects($feed, $user_objects, $shop_objects, $club_objects) {
        $tag_arr = $users = $shops = $clubs = array();
        $user_service = $this->container->get('user_object.service');
        $tag_users = $feed->getTagUser();
        $tag_shops = $feed->getTagShop();
        $tag_clubs = $feed->getTagClub();
        if(!empty($tag_users)){
            foreach ($feed->getTagUser() as $tag_user) { //get tagged users
                $users[] = isset($user_objects[$tag_user]) ? $user_objects[$tag_user] : null;
            }
        }
        if(!empty($tag_shops)){
            foreach ($feed->getTagShop() as $tag_shop) { //get tagged shops
                $shops[] = isset($shop_objects[$tag_shop]) ? $shop_objects[$tag_shop] : null;
            }
        }
        if(!empty($tag_clubs)){
            foreach ($feed->getTagClub() as $tag_club) { //get tagged clubs
                $clubs[] = isset($club_objects[$tag_club]) ? $club_objects[$tag_club] : null;
            }
        }
        $tag_arr = array('user' => $users, 'shop' => $shops, 'club' => $clubs);
        return $tag_arr;
    }

    /**
     * getting the post info
     * @param string $post_feed
     * @param array $user_objects
     * @param array $shop_objects
     * @param array $club_objects
     * @param array $social_project_objects
     * @return array
     */
    public function getPostInfo($post_feed, $user_objects, $shop_objects, $club_objects, $social_project_objects) {
        $to_user_info = $type_info = array();
        $post_type = $post_feed->getPostType();

        if (Utility::getUpperCaseString(Utility::getTrimmedString($post_type)) == Utility::getUpperCaseString(Utility::getTrimmedString(self::USER_POST))) {
            $to_id = $post_feed->getToId();
            $to_user_info = isset($user_objects[$to_id]) ? $user_objects[$to_id] : null;
            $type_info = isset($user_objects[$to_id]) ? $user_objects[$to_id] : null;
        } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post_type)) == Utility::getUpperCaseString(Utility::getTrimmedString(self::SHOP_POST))) {
            $to_id = $post_feed->getToId();
            $to_user_info = isset($shop_objects[$to_id]) ? $shop_objects[$to_id] : null;
            $type_info = isset($shop_objects[$to_id]) ? $shop_objects[$to_id] : null;
        } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post_type)) == Utility::getUpperCaseString(Utility::getTrimmedString(self::CLUB_POST))) {
            $type = $post_feed->getTypeInfo();
            $to_id = isset($type['id']) ? $type['id'] : '';
            $to_user_info = isset($club_objects[$to_id]) ? $club_objects[$to_id] : null;
            $type_info = isset($club_objects[$to_id]) ? $club_objects[$to_id] : null;
        } else if (Utility::getUpperCaseString(Utility::getTrimmedString($post_type)) == Utility::getUpperCaseString(Utility::getTrimmedString(self::SOCIAL_PROJECT_POST))) {
            $type = $post_feed->getTypeInfo();
            $to_id = isset($type['id']) ? $type['id'] : '';
            $to_user_info = isset($social_project_objects[$to_id]) ? $social_project_objects[$to_id] : null;
            $type_info = isset($social_project_objects[$to_id]) ? $social_project_objects[$to_id] : null;
        }
        return array('to_id' => $to_user_info, 'type_info' => $type_info);
    }

    /**
     * get single object from comment object
     * @param object $post_comment
     * @param int $user_id
     * @param array $user_objects
     * @param array $shop_objects
     * @param array $club_objects
     * @return array
     */
    public function getSingleCommentObject($post_comment, $user_id, $user_objects, $shop_objects, $club_objects) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSingleCommentObject] with data:' . Utility::encodeData($post_comment), array());
        $comment_obj = array();
        if ($post_comment) {
            $service_media_obj = $this->container->get('post_feeds.MediaFeeds');
            $comment_media_obj = $service_media_obj->getGalleryMedia($post_comment->getMedia());
            $comment_tag_obj = $this->getTagObjects($post_comment, $user_objects, $shop_objects, $club_objects); //get tagged entity
            $comment_rated_users = $this->getRatedUsers($post_comment, $user_id, $user_objects, $shop_objects, $club_objects); //get rated users
            $comment_obj = array(
                'id' => $post_comment->getId(),
                'user_id' => $post_comment->getUserId(),
                'comment_user_info' => $post_comment->getUserInfo(),
                'comment_text' => $post_comment->getText(),
                'is_active' => $post_comment->getIsActive(),
                'create_date' => $post_comment->getCreatedAt(),
                'updated_at' => $post_comment->getUpdatedAt(),
                'user_tag' => $comment_tag_obj['user'],
                'shop_tag' => $comment_tag_obj['shop'],
                'club_tag' => $comment_tag_obj['club'],
                'rated_user' => $comment_rated_users['rated_users'],
                'is_rated' => $comment_rated_users['is_rated'],
                'current_user_rate' => $comment_rated_users['current_user_rate'],
                'no_of_votes' => $post_comment->getVoteCount(),
                'vote_sum' => $post_comment->getVoteSum(),
                'avg_rate' => $post_comment->getAvgRating(),
                'comment_media_info' => $comment_media_obj
            );
        }
        $this->__createLog('Exiting in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSingleCommentObject] with data success:' . Utility::encodeData($comment_obj), array());
        return $comment_obj;
    }

    /**
     * get the rated users
     * @param object $feed
     * @param int $user_id
     * @param array $user_objects
     * @param array $shop_objects
     * @param array $club_objects
     * @return type
     */
    public function getRatedUsers($feed, $user_id, $user_objects, $shop_objects, $club_objects) {
        $is_rated = 0;
        $current_user_rate = 0;
        $users = array();
        foreach ($feed->getRate() as $rate) { //get rated users
            $rated_user_id = $rate->getUserId();
            $user_rate = $rate->getRate();
            $user_objects[$rated_user_id]['rate'] = $user_rate;
            $users[] = isset($user_objects[$rated_user_id]) ? $user_objects[$rated_user_id] : null;

            if ($is_rated == 0) { //check if a user id rated in this entity
                if ($user_id == $rated_user_id) {
                    $is_rated = 1;
                    $current_user_rate = $rate->getRate(); //current user rate
                }
            }
        }
        return array('rated_users' => $users, 'is_rated' => $is_rated, 'current_user_rate' => $current_user_rate);
    }

    /**
     * get Social projects object
     * @param array $projects
     * @return array $project_objects
     */
    public function getSocialProjectObjects($projects) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSocialProjectObjects]', array());
        $project_objects = array();
        $user_service = $this->container->get('user_object.service');
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        if (count($projects)) {
            foreach ($projects as $project) {
                $address = $project->getAddress() ? $project->getAddress() : array();
                $addres_data = $this->getAddress($address);
                $cover_data = $project->getCoverImg() ? $project->getCoverImg() : array();
                $cover_info  = $this->getCoverImageinfo($cover_data); // Need to uncomment
                //$cover_info = array();
                $medias = $project->getMedias() ? $project->getMedias() : array();
                $gallery_info = $service_obj->getGalleryMedia($medias);
                $owner_id = $project->getOwnerId();
                $owner_info = $user_service->UserObjectService($owner_id);
                $we_want = $project->getWeWant();
                $project_id = $project->getId();

                $project_objects[$project_id] = array(
                    'id' => $project_id,
                    'project_title' => $project->getTitle(),
                    'email' => $project->getEmail(),
                    'website' => $project->getWebsite(),
                    'project_desc' => $project->getDescription(),
                    'created_on' => $project->getCreatedAt(),
                    'address' => $addres_data,
                    'project_owner' => $owner_info,
                    'we_want_count' => $we_want,
                    'cover_img' => $cover_info,
                    'gallery_info' => $gallery_info
                );
            }
        }
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getSocialProjectObjects]', array());
        return $project_objects;
    }

    /**
     * get multiple social projects object
     * @param array $project_ids
     * @param boolean $multiple
     * @return array
     */
    public function getMultipleSocialProjectObjects($project_ids, $multiple = true) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleSocialProjectObjects]', array());
        $dm = $this->dm;
        $postFeedService = $this->container->get('post_feeds.postFeeds');
        $project_ids_array = is_array($project_ids) ? $project_ids : array($project_ids);
        $projects = $dm->getRepository('PostFeedsBundle:SocialProject')->getSocialProjects($project_ids_array);
        $results = $postFeedService->getSocialProjectObjects($projects);
        if (!$multiple) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleSocialProjectObjects]', array());
            return isset($results[$project_ids]) ? $results[$project_ids] : array();
        }
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsService] and function [getMultipleSocialProjectObjects]', array());
        return $results;
    }

    /**
     * finding the multiple group profile images
     * @param array $group_ids
     * @return array
     */
    public function getGroupProfileImages($group_ids) {
        $dm = $this->dm;
        $group_media = array();
        $aws_path    = $this->getAwspath();
        $group_medias_profile_img = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                       ->getGroupProfileMediasInfo($group_ids);
        foreach ($group_ids as $group_id) {
            if (isset($group_medias_profile_img[$group_id])) {
                $album_id = $group_medias_profile_img[$group_id]->getAlbumid();
                $media_id = $group_medias_profile_img[$group_id]->getId();
                $x = $group_medias_profile_img[$group_id]->getX();
                $y = $group_medias_profile_img[$group_id]->getY();
                $media_name = $group_medias_profile_img[$group_id]->getMediaName();
                if ($album_id) {
                    $profile_img_original = $aws_path . $this->group_media_path . $group_id . '/' . $album_id . '/' . $media_name;
                    $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id . '/' . $album_id . '/' . $media_name;
                    // $profile_img_cover = $aws_path . $this->group_cover_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                    $profile_img_cover = $aws_path . $this->group_media_path_thumb . $group_id . '/coverphoto/' . $media_name;
                } else {
                    $profile_img_original = $aws_path . $this->group_media_path . $group_id . '/' . $media_name;
                    $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id . '/' . $media_name;
                    // $profile_img_cover = $aws_path . $this->group_cover_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
                    $profile_img_cover = $aws_path . $this->group_media_path_thumb . $group_id . '/coverphoto/' . $media_name;
                }
            } else {
                $album_id = $media_id = $x = $y = $media_name = $profile_img_original = $profile_img_thumb = $profile_img_cover = '';
            }
            $group_media[$group_id] = array('original_path'=>$profile_img_original, 'thumb_path'=>$profile_img_thumb, 'cover_path'=>$profile_img_cover, 'x_cord'=>$x, 'y_cord'=>$y, 'media_id'=>$media_id);
        }
        return $group_media;
    }

    /**
     * getting aws bucket path
     * @return string
     */
    public function getAwspath() {
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        return $aws_path;
    }
    
    /**
     * Service to get shop owners
     * @param type $shops
     * @return array
     */
    public function getShopOwners($shops)
    {
        $id = array();
        foreach($shops as $shop){
            $id[] = $shop['id'];
        }
        $owner = array();    
        try{
            if(!empty($id)){
                $em = $this->em;   
                $store_owners = $em
                        ->getRepository('StoreManagerStoreBundle:UserToStore')
                        ->findBy(array('storeId'=>$id));
                if($store_owners){
                    foreach($store_owners as $store_owner){
                        $shop_id = $store_owner->getStoreId();
                        $owner[$shop_id] = $store_owner->getUserId();
                    }
                    return $owner;
                }
            }
        }catch(\Exception $e){
            
        }
        return $owner;
    }
    
     /**
     *  function for preparing the object info for the socail sharing 
     * @param type $object_type
     * @param type $object_id
     * @return array
     */
    private function prepareObjectInfo($object_type, $object_id) {
        $object_type = Utility::getUpperCaseString($object_type);
        $user_service = $this->container->get('user_object.service');
        $object_info = array();
        switch ($object_type) {
            case 'CLUB':
                $post_service = $this->container->get('post_feeds.postFeeds');
                $club_ids = array($object_id);
                $clubs_info = $post_service->getMultiGroupObjectService($club_ids);
                $object_info = isset($clubs_info[$object_id]) ? $clubs_info[$object_id] : array();
                break;
            case 'SHOP' :
                $object_info = $user_service->getStoreObjectService($object_id);
                break;
            
            case 'OFFER' :
                $applane_service = $this->container->get('appalne_integration.callapplaneservice');
                $object_info = $applane_service->getOffersDetails($object_id);
                break;
            
            case 'SOCIAL_PROJECT' :
                $post_service = $this->container->get('post_feeds.postFeeds');
                $object_info = $post_service->getMultipleSocialProjectObjects($object_id, false);
                break;

            case 'EXTERNAL' :
                $post_service = $this->container->get('post_feeds.postFeeds');
                $object_info = null;
                break;
            
            case 'BCE' :
                $applane_service = $this->container->get('appalne_integration.callapplaneservice');
                $object_info = $applane_service->getOffersDetails($object_id);
                break;
            
            default:
                $object_info = null;
                break;
        }
        
        $final_data = array("id" => $object_id, 'info' => $object_info);
        return $final_data;
    }
}
