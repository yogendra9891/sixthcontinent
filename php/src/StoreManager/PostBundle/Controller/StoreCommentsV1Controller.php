<?php

namespace StoreManager\PostBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use UserManager\Sonata\UserBundle\Document\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use StoreManager\PostBundle\Document\StoreComments;
use StoreManager\PostBundle\Document\StoreCommentsMedia;
use StoreManager\PostBundle\Document\StorePosts;
use StoreManager\PostBundle\Document\StorePostsMedia;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Class for handling the comments on store posts
 */
class StoreCommentsV1Controller extends Controller {

    protected $miss_param = '';
    protected $youtube = 'youtube';
    protected $comment_media_path = '/uploads/documents/store/comments/original/';
    protected $comment_media_path_thumb = '/uploads/documents/store/comments/thumb/';
    protected $comment_media_path_thumb_crop = '/uploads/documents/store/comments/thumb_crop/';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $store_comment_thumb_image_width = 654;
    protected $store_comment_thumb_image_height = 360;
    protected $original_resize_image_width  = 910;
    protected $original_resize_image_height = 910;
    /**
     * index action
     * @param type $name
     * @return type
     */
    public function indexAction($name) {
        return $this->render('StoreManagerPostBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }


    /**
     * saving a new comment and getting the comment object.
     * @param type $object_info
     * @return array
     */
    public function getCommentObjectWithoutImage($object_info, $sender_user) {
        // get doctrine entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $user_service = $this->get('user_object.service');
        $user_info = array();
        $data = array();
        $time = new \DateTime("now");
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($object_info->post_id);
        
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        //code for adding the comment object
        $comment = new StoreComments(); //making the entity object
        $comment->setPostId($object_info->post_id);
        $comment->setCommentAuthor($object_info->comment_author);
        $comment->setCommentText($object_info->comment_text);
        $comment->setCommentCreatedAt($time);
        $comment->setCommentUpdatedAt($time);
        $comment->setStatus(1); //1=>enabled
        $comment->setTagging($tagging);
        $dm->persist($comment); //storing the comment data.
        $dm->flush();
        //update ACL for a user
        $this->updateAclAction($sender_user, $comment);
        //getting the comment media data..
        $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                ->findBy(array('store_comment_id' => $object_info->comment_id, 'media_status' => 1));

        $comment_media_data = array();
        foreach ($comment_media as $media) { //finding the comment media from comment id.
            $media_id = $media->getId();
            $store_comment_id = $media->getStoreCommentId();
            $media_name = $media->getMediaName();
            $media_type = $media->getMediaType();
            $media_created_at = $media->getMediaCreated();
            $media_updated_at = $media->getMediaUpdated();
            $media_status = $media->getMediaStatus();
            $media_path = $media->getPath();
            $media_is_featued = $media->getIsFeatured();
            $comment_image_type = $media->getImageType();
            if ($media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                $media_link = $media_path;
                $media_thumb = '';
            } else {
                $media_link  = $this->getS3BaseUri() . $this->comment_media_path . $store_comment_id . '/' . $media_name;
                $media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $store_comment_id . '/' . $media_name;
            }
            $comment_media_data[] = array('id' => $media_id,
                'comment_id' => $store_comment_id,
                'media_path' => $media_link,
                'media_thumb' => $media_thumb,
                'media_type' => $media_type,
                'media_created_at' => $media_created_at,
                'media_status' => $media_status,
                'media_is_featured' => $media_is_featued,
                'image_type' =>$comment_image_type
            );
        }
        $user_info = $user_service->UserObjectService($comment->getCommentAuthor());
        //prepare the comment array with comment media(assign in comment_media_data variable)
        $comment_data = array('id' => $comment->getId(),
            'post_id' => $comment->getPostId(),
            'comment_text' => $comment->getCommentText(),
            'comment_author' => $comment->getCommentAuthor(),
            'comment_user_info' => $user_info,
            'comment_created_at' => $comment->getCommentCreatedAt(),
            'comment_updated_at' => $comment->getCommentUpdatedAt(),
            'comment_status' => $comment->getStatus(),
            'comment_media_info' => $comment_media_data,
            'avg_rate'=>   0,
            'no_of_votes'=> 0,
            'current_user_rate'=>0,
            'is_rated' => false
        );
        //prepare the data for response.
        $data['post']    = $post;
        $data['comment'] = $comment_data;
        return $data;
    }

    /**
     * getting the comment object and making it as publish.
     * @param type $object_info
     * @return array
     */
    public function getCommentObject($object_info) {
        // get doctrine entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $user_service = $this->get('user_object.service');
        $user_info = array();
        $data = array();
        $media_ids_array = array();
        $object_info->media_id = (isset($object_info->media_id) ? $object_info->media_id : $media_ids_array);
        $media_array           = $object_info->media_id;
        $time = new \DateTime("now");
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($object_info->post_id);
        
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        //code for adding the comment object returning
        $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($object_info->comment_id);
        $comment->setPostId($object_info->post_id);
        $comment->setCommentAuthor($object_info->comment_author);
        $comment->setCommentText($object_info->comment_text);
        $comment->setCommentCreatedAt($time);
        $comment->setCommentUpdatedAt($time);
        $comment->setStatus(1); //1=>enabled
        $comment->setTagging($tagging);
        $dm->persist($comment); //storing the comment data.
        $dm->flush();

        //making the media publish..
        if (count($media_array)) {
            $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                    ->publishMedia($object_info->comment_id, $media_array);
        }
        //getting the comment media data..
        $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                ->findBy(array('store_comment_id' => $object_info->comment_id, 'media_status' => 1));

        $comment_media_data = array();
        foreach ($comment_media as $media) { //finding the comment media from comment id.
            $media_id = $media->getId();
            $store_comment_id = $media->getStoreCommentId();
            $media_name = $media->getMediaName();
            $media_type = $media->getMediaType();
            $media_created_at = $media->getMediaCreated();
            $media_updated_at = $media->getMediaUpdated();
            $media_status = $media->getMediaStatus();
            $media_path = $media->getPath();
            $media_is_featued = $media->getIsFeatured();
            $comment_image_type = $media->getImageType();
            if ($media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                $media_link = $media_path;
                $media_thumb = '';
            } else {
                $media_link  = $this->getS3BaseUri() . $this->comment_media_path . $store_comment_id . '/' . $media_name;
                $media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $store_comment_id . '/' . $media_name;
            }
            $comment_media_data[] = array('id' => $media_id,
                'comment_id' => $store_comment_id,
                'media_path' => $media_link,
                'media_thumb' => $media_thumb,
                'media_type' => $media_type,
                'media_created_at' => $media_created_at,
                'media_status' => $media_status,
                'media_is_featured' => $media_is_featued,
                'image_type' =>$comment_image_type
            );
        }
        $user_info = $user_service->UserObjectService($comment->getCommentAuthor());
        //prepare the comment array with comment media(assign in comment_media_data variable)
        $comment_data = array('id' => $comment->getId(),
            'post_id' => $comment->getPostId(),
            'comment_text' => $comment->getCommentText(),
            'comment_author' => $comment->getCommentAuthor(),
            'comment_user_info' => $user_info,
            'comment_created_at' => $comment->getCommentCreatedAt(),
            'comment_updated_at' => $comment->getCommentUpdatedAt(),
            'comment_status' => $comment->getStatus(),
            'comment_media_info' => $comment_media_data,
            'avg_rate'=>   0,
            'no_of_votes'=> 0,
            'current_user_rate'=>0,
            'is_rated' => false
        );
        //prepare the data for response.
        $data['post']    = $post;
        $data['comment'] = $comment_data;
        return $data;
    }

    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && ($converted_array[$param] != '')) {
                $check_error = 0;
            } else {
                $check_error = 1;
                $this->miss_param = $param;
                break;
            }
        }
        return $check_error;
    }

    /**
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        $file_error = 0;
        foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['commentfile']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['commentfile']['type'][$key] == 'image/jpeg' || $_FILES['commentfile']['type'][$key] == 'image/jpg' ||
                        $_FILES['commentfile']['type'][$key] == 'image/gif' || $_FILES['commentfile']['type'][$key] == 'image/png'))) || (preg_match('/^.*\.(mp3|mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }



    
    public function postListstorecommentsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('post_id', 'user_id');
        $data = array();
        $post_data = array();
        $comment_data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //finding the post data
        $post_data = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($object_info->post_id);
        if (!$post_data) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        
        $comment_user_id = $object_info->user_id;
        $post_id = $object_info->post_id;

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $comment_user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $store_id = $post_data->getStoreId();

        //for store ACL
        $do_action = 0;
        $group_mask = $this->userGroupRole($store_id, $comment_user_id);
        //check for Access Permission
        //for owner and admin
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        //checking store is public or not for this comment
        if ($do_action == 0) {
            //for store guest ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $store_id)); //@TODO Add group owner id in AND clause.

            $is_store_allow = $store->getIsAllowed();
            
            if ($is_store_allow == 1) {
                $do_action = 1;
            }
        }
        $user_service = $this->get('user_object.service');
        $user_info = array();
        $post_id = $object_info->post_id;
        $user_id = $object_info->user_id;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        //finding the comment data
        $comment_res = $comment_original = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->findBy(array('post_id' => $object_info->post_id, 'status' => 1), array('comment_created_at' => 'ASC'), $limit, $offset);
        
        //if there is any comments for this post then find the other things.
        if (count($comment_res)) {
            //finding the comment count
            $comment_count = $comment_original = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->findBy(array('post_id' => $object_info->post_id, 'status' => 1));
            
            //comments ids
            $comment_ids = array_map(function($comment_data) {
               return "{$comment_data->getId()}";
            }, $comment_res);
            
            //comments user ids.    
            $comment_user_ids = array_map(function($comment_data) {
                return "{$comment_data->getCommentAuthor()}";
            }, $comment_res);
            
            //finding the comments media.
            $comments_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                ->getCommentMedia($comment_ids);
            
            //making user ids array unique.
            $users_array = array_unique($comment_user_ids);
            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
            //iterate the object if comments is exists.
        foreach ($comment_res as $comment) {
            $comment_id = $comment->getId();
            $comment_user_id = $comment->getCommentAuthor();
            $comment_media_data = array();
            //ittrate comment media
            foreach ($comments_media as $media) {
                if ($media->getStoreCommentId() == $comment_id) {
                    $media_id = $media->getId();
                    $store_comment_id = $media->getStoreCommentId();
                    $media_name = $media->getMediaName();
                    $media_type = $media->getMediaType();
                    $media_created_at = $media->getMediaCreated();
                    $media_updated_at = $media->getMediaUpdated();
                    $media_status = $media->getMediaStatus();
                    $media_path = $media->getPath();
                    $media_is_featued = $media->getIsFeatured();
                    $comment_image_type = $media->getImageType();
                    if ($media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $media_link = $media_path;
                    $media_thumb = '';
                    } else {
                        $media_link  = $this->getS3BaseUri() . $this->comment_media_path . $store_comment_id . '/' . $media_name;
                        $media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $store_comment_id . '/' . $media_name;
                    }
                    $comment_media_data[] = array('id' => $media_id,
                        'comment_id' => $store_comment_id,
                        'media_path' => $media_link,
                        'media_thumb' => $media_thumb,
                        'media_type' => $media_type,
                        'media_created_at' => $media_created_at,
                        'media_status' => $media_status,
                        'media_is_featured' => $media_is_featued,
                        'image_type' =>$comment_image_type
                );
                }
            }
            $current_rate = 0;
            $is_rated = false;
           
            foreach($comment->getRate() as $rate) {
                if($rate->getUserId() == $comment_user_id ) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
       
            //comment user info.
            $user_info = isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array();
            //prepare the comment array with comment media(assign in comment_media_data variable)
            
            $current_rate = 0;
            $is_rated = false;
            $rate_data_obj = $comment->getRate();
            if(count($rate_data_obj) > 0) {
                foreach($rate_data_obj as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
            }
            $comment_data[] = array('id' => $comment->getId(),
                'post_id' => $comment->getPostId(),
                'comment_text' => $comment->getCommentText(),
                'comment_author' => $comment->getCommentAuthor(),
                'comment_user_info' => $user_info,
                'comment_created_at' => $comment->getCommentCreatedAt(),
                'comment_updated_at' => $comment->getCommentUpdatedAt(),
                'comment_status' => $comment->getStatus(),
                'comment_media_info' => $comment_media_data,
                'avg_rate'=>round($comment->getAvgRating(),1),
                'no_of_votes' => (int)$comment->getVoteCount(),
                'current_user_rate'=>$current_rate,
                'is_rated' =>$is_rated
            );
        }
        }
        
        $data['post'] = $post_data;
        $data['comment'] = $comment_data;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Function to retrieve current applications base URI(hostname/project/web)
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
    }
    
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $full_path     = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
 

    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * Get User role for store
     * @param int $store_id
     * @param int $user_id
     * @return int
     */
    public function userGroupRole($store_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $store = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id)); //@TODO Add group owner id in AND clause.

        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($store); //entity

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
     * @param object $store_comment_entity
     * @return none
     */
    public function updateAclAction($sender_user, $store_comment_entity) {
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($store_comment_entity);
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
     * Get User role for comments
     * @param int $comment_id
     * @param int $user_id
     * @return int
     */
    /*
     * We are checking that user is either creater of this post or not
     */
    public function userStoreGuestRole($comment_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $comment = $dm
                ->getRepository('StoreManagerPostBundle:StoreComments')
                ->findOneBy(array('id' => $comment_id)); //@TODO Add group owner id in AND clause.

        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($comment); //entity

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
     * Get User role for post, We are checking that user is either creater of this post or not
     * @param int $post_id
     * @param int $user_id
     * @return int
     * 
     */
    public function userPostGuestRole($post_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('StoreManagerPostBundle:StorePosts')
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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        //$path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/store/comments/thumb/" . $comment_id . "/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width  = $this->store_comment_thumb_image_width;
        $thumb_height = $this->store_comment_thumb_image_height;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
            if ($new_width < $thumb_width) {
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
            }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
            if ($new_height < $thumb_height) {
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
            }
        }

        $nx = $new_width;
        $ny = $new_height;
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        //  imagejpeg($nm, $path_to_thumbs_directory . $filename);
        if (preg_match('/[.](jpg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
        
        $s3imagepath = "uploads/documents/store/comments/thumb_crop/" . $comment_id ;
        $image_local_path = $path_to_thumbs_directory.$filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    
    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $comment_id) {
        //get image thumb width
        $thumb_width  = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory  = $media_original_path;
        //$final_width_of_image = 200;
        if(preg_match('/[.](jpg)$/', $filename)) {  
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);  
        } else if (preg_match('/[.](jpeg)$/', $filename)) {  
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);  
        } else if (preg_match('/[.](gif)$/', $filename)) {  
            $im = imagecreatefromgif($path_to_image_directory . $filename);  
        } else if (preg_match('/[.](png)$/', $filename)) {  
            $im = imagecreatefrompng($path_to_image_directory . $filename);  
        }
        $ox = imagesx($im);
        $oy = imagesy($im);
        //check a image is less than defined size.. 
        if ($ox > $thumb_width || $oy > $thumb_height) {
            //getting aspect ratio
            $original_aspect = $ox / $oy;
            $thumb_aspect    = $thumb_width / $thumb_height;

            if ($original_aspect >= $thumb_aspect) {
                // If image is wider than thumbnail (in aspect ratio sense)
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
                //check if new width is less than minimum width
                 if($new_width > $thumb_width){
                           $new_width = $thumb_width;
                           $new_height = $oy / ($ox / $thumb_width);
                   }
            } else {
                // If the thumbnail is wider than the image
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
                 //check if new height is less than minimum height
                if($new_height > $thumb_height){
                           $new_height = $thumb_height;
                           $new_width = $ox / ($oy / $thumb_height);
                   }
            }
            $nx = $new_width;
            $ny = $new_height;
        } else {
            $nx  = $ox;
            $ny  = $oy;
        }
               
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
        if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename, 9);
        }

        $s3imagepath = "uploads/documents/store/comments/original/" . $comment_id ;
        $image_local_path = $path_to_thumbs_directory.$filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }
    
    /**
     * Uplaod on s3 server
     * @param string $s3imagepath
     * @param string $image_local_path
     * @param string $filename
     * @return string $image_url
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename)
    {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }
    
}