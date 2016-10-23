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
class StoreCommentsController extends Controller {

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
     * adding the comments and uploading the files.
     * @param request object
     * @return json
     */
    public function postStorecommentsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];
        /*if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }*/
        if ($device_request_type == 'mobile') {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
        //'post_type' => '0'/'1'.
        $required_parameter = array('post_id', 'comment_author', 'post_type');
        $data = array();
        //checking for parameter missing.

        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            }
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $time = new \DateTime("now");
        $comment_user_id = $object_info->comment_author;
        $post_id = $object_info->post_id;
        $post_type = $object_info->post_type;
        $object_info->comment_id = (isset($object_info->comment_id) ? $object_info->comment_id : '');
        $object_info->youtube_url = (isset($object_info->youtube_url) ? $object_info->youtube_url : '');
        $object_info->comment_text = (isset($object_info->comment_text) ? $object_info->comment_text : '');
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $comment_user_id));
        $data1 = '';
        if ($sender_user == '') {
            $data1 = "User Id is invalid";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'User Id is invalid', 'data' => $data);
        }
        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);
        if (!$post) {
            return array('code' => 100, 'message' => 'POST_RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $store_id = $post->getStoreId();

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

        if ($do_action) {
            if ($post_type == 0) { //check for a image uploading
                if ($object_info->comment_id == '') { //for first call...
                    $store_comments = new StoreComments();
                    $store_comments->setPostId($object_info->post_id);
                    $store_comments->setCommentAuthor($object_info->comment_author);
                    $store_comments->setCommentText($object_info->comment_text);
                    $store_comments->setCommentCreatedAt($time);
                    $store_comments->setCommentUpdatedAt($time);
                    $store_comments->setStatus(0); // 0=>disabled, 1=>enabled
                    $store_comments->setTagging($tagging);
                    $dm->persist($store_comments); //storing the comment data.
                    $dm->flush();
                    $comment_id = $store_comments->getId(); //getting the last inserted id of comments.
                    //update ACL for a user 
                    $this->updateAclAction($sender_user, $store_comments);
                } else {
                    $comment_id = $object_info->comment_id;
                }
                $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                        ->find($comment_id);
                if (!$comment_res) {
                    return array('code' => 302, 'message' => 'RECORD_COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                }
                $current_comment_media = array();
                
                $comment_media_id = 0;
                $store_comment_thumb_image_width = $this->store_comment_thumb_image_width;
                $store_comment_thumb_image_height = $this->store_comment_thumb_image_height;
                
                //get the image name clean service..
                $clean_name = $this->get('clean_name_object.service');
                $image_upload = $this->get('amazan_upload_object.service');
                //for file uploading...
                if (isset($_FILES['commentfile'])) {
                    foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                        //find media size information 
                        $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1]; 

                        //call service to get image type. Basis of this we save data 3,2,1 in db
                        $image_type_service = $this->get('user_object.service');
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$store_comment_thumb_image_width,$store_comment_thumb_image_height);
                        
                        $original_file_name = $_FILES['commentfile']['name'][$key];
                        $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                        $file_name = $clean_name->cleanString($file_name); //rename the file name, clean the image name.
                        if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                            $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                            $file_type = $_FILES['commentfile']['type'][$key];
                            $media_type = explode('/', $file_type);
                            $actual_media_type = $media_type[0];

                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
                            $store_comment_media = new StoreCommentsMedia();
                            if (!$key) //consider first image the featured image.
                                $store_comment_media->setIsFeatured(1);
                            else
                                $store_comment_media->setIsFeatured(0);
                            $store_comment_media->setStoreCommentId($comment_id);
                            $store_comment_media->setMediaName($file_name);
                            $store_comment_media->setMediaType($actual_media_type);
                            $store_comment_media->setMediaCreated($time);
                            $store_comment_media->setMediaUpdated($time);
                            $store_comment_media->setPath('');
                            $store_comment_media->setMediaStatus(0); //making first time unpublish..
                            $store_comment_media->setImageType($image_type);
                            $dm->persist($store_comment_media);
                            $dm->flush();

                            //getting the comment media id..
                            $comment_media_id = $store_comment_media->getId();
                            //update ACL for a user 
                            $this->updateAclAction($sender_user, $store_comment_media);
                            $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_comments_media_path'). $comment_id . '/';
                            $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('store_comments_media_path') . $comment_id . '/';
                            $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_comments_media_path_thumb') . $comment_id . '/';
                            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_comments_media_path_thumb_crop') . $comment_id . "/";
                            $s3_post_media_path = $this->container->getParameter('s3_store_comments_media_path'). $comment_id;
                            $s3_post_media_thumb_path = $this->container->getParameter('s3_store_comments_media_thumb_path'). $comment_id;
                            $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'store_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                        }
                    }
                }
                //handling og youtube url.
                if (!empty($object_info->youtube_url)) {
                    $store_comment_media = new StoreCommentsMedia();
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $store_comment_media = new StoreCommentsMedia();
                    $store_comment_media->setIsFeatured(0);
                    $store_comment_media->setStoreCommentId($comment_id);
                    $store_comment_media->setMediaName('');
                    $store_comment_media->setMediaType($this->youtube);
                    $store_comment_media->setMediaCreated($time);
                    $store_comment_media->setMediaUpdated($time);
                    $store_comment_media->setPath($object_info->youtube_url);
                    $store_comment_media->setMediaStatus(1);
                    $store_comment_media->setImageType($image_type);
                    $dm->persist($store_comment_media);
                    $dm->flush();

                    //update ACL for a user 
                    $this->updateAclAction($sender_user, $store_comment_media);
                }
                //finding the cureent media data.
                $comment_media_data = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                        ->find($comment_media_id);
                $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
                if ($comment_media_data) {
                    $comment_image_type = $comment_media_data->getImageType();
                    $comment_media_name  = $comment_media_data->getMediaName();
                    $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }
                //sending the current media and comment id.
                $data = array(
                    'comment_id' => $comment_id,
                    'media_id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'image_type' =>$comment_image_type,
                    'avg_rate'=>   0,
                    'no_of_votes'=> 0,
                    'current_user_rate'=>0,
                    'is_rated' => false
                    
                );
                $res_data =  array('code' => 101, 'message' => 'success', 'data' => $data);
                echo json_encode($res_data);
                exit();
            } else { //else part for final call for saving the comment.
            
                $postService = $this->get('post_detail.service');
                //code for adding the comment object returning
                if ($object_info->comment_id != '') {
                    $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                            ->find($object_info->comment_id);
                    if (!$comment) {
                        return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                    }
                    //code end for comment object data
                    $comment_data = $this->getCommentObject($object_info);
                    $postService->sendCommentNotificationEmail($post_id,$comment_user_id, 'store', $comment_data['comment']['id'], true, $tagging);
                } else { //fresh post without a media.
                    //code end for comment object data
                    $comment_data = $this->getCommentObjectWithoutImage($object_info, $sender_user);
                    $postService->sendCommentNotificationEmail($post_id,$comment_user_id, 'store', $comment_data['comment']['id'], true, $tagging);
                }
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
               echo json_encode($res_data);
                exit();
            }
        } else {
            $res_data =  array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
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

    /**
     * remove the comments and removing the files.
     * @param request object
     * @return json
     */
    public function postRemovestorecommentsAction(Request $request) {
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

        $required_parameter = array('comment_id', 'user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $store_comments = new StoreComments();
        //$store_comments->setPostId($object_info->comment_id);

        $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($object_info->comment_id);
        if (!$comment_res) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        //Now we are getting information (store_id) of this post
        $post_id = $comment_res->getPostId();

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->findOneBy(array("id" => $post_id));
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $store_id = $post->getStoreId();

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }

        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userGroupRole($store_id, $object_info->user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        if ($do_action == 0) {
            //for store friend ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $store_id));
            
            $is_store_allow = $store->getIsAllowed();

            if ($is_store_allow == 1) {
                // checking mask using acl. if user is ownere of comment
                //post mask return value 15,7 i.e it is either author or creator of this post
                // if it is creator of this post it will update his post

                $post_mask = $this->userPostGuestRole($post_id, $object_info->user_id);
                $allow_friend = array('15', '7');
                if (in_array($post_mask, $allow_friend)) {
                    $do_action = 1;
                }
                if ($do_action == 0) {
                    $comment_mask = $this->userStoreGuestRole($object_info->comment_id, $object_info->user_id);
                    $allow_friend = array('15', '7');
                    if (in_array($comment_mask, $allow_friend)) {
                        $do_action = 1;
                    }
                }
            }
        }

        if ($do_action) {
            $dm->remove($comment_res);
            $dm->flush();

            //removing the store comment media..
            $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                    ->removeCommentsMedia($object_info->comment_id);

            if ($comment_media) {
                //removing the images from directory
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $comment_images_location = $file_location . $this->comment_media_path . $object_info->comment_id;
                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (file_exists($comment_images_location)) {
                   // array_map('unlink', glob($comment_images_location . '/*')); //remove the directory recursively.
                  //  rmdir($comment_images_location);
                }
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit();
            } else {
                $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
        } else {
            $res_data = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * list of comments with media
     * @param request object
     * @return json
     */
//    public function postListstorecommentsAction(Request $request) {
//        //Code start for getting the request
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeObjectAction($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//        //Code end for getting the request
//
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('post_id', 'user_id');
//        $data = array();
//        $post_data = array();
//        $comment_media = array();
//        $comment_data = array();
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//        //finding the post data
//        $post_data = $dm->getRepository('StoreManagerPostBundle:StorePosts')
//                ->find($object_info->post_id);
//        if (!$post_data) {
//            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
//        }
//        //finding the comment data
//        $store_comments = new StoreComments();
//        $comment_res = $comment_original = $dm->getRepository('StoreManagerPostBundle:StoreComments')
//                ->findBy(array('post_id' => $object_info->post_id, 'status' => 1));
//
//        $comment_user_id = $object_info->user_id;
//        $post_id = $object_info->post_id;
//
//        //Code for ACL checking
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $comment_user_id));
//
//        $data1 = '';
//        if ($sender_user == '') {
//            $data1 = "USER_ID_IS_INVALID";
//        }
//        if (!empty($data1)) {
//            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
//        }
//        // get entity manager object
//        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//
//        $store_id = $post_data->getStoreId();
//
//        //for store ACL
//        $do_action = 0;
//        $group_mask = $this->userGroupRole($store_id, $comment_user_id);
//        //check for Access Permission
//        //for owner and admin
//        $allow_group = array('15', '7');
//        if (in_array($group_mask, $allow_group)) {
//            $do_action = 1;
//        }
//
//        //checking store is public or not for this comment
//        if ($do_action == 0) {
//            //for store guest ACL
//            $em = $this->getDoctrine()->getManager();
//            $store = $em
//                    ->getRepository('StoreManagerStoreBundle:Store')
//                    ->findOneBy(array("id" => $store_id)); //@TODO Add group owner id in AND clause.
//
//            $is_store_allow = $store->getIsAllowed();
//            if ($is_store_allow == 1) {
//                $do_action = 1;
//            }
//        }
//        $user_service = $this->get('user_object.service');
//        $user_info = array();
//        foreach ($comment_original as $comment) { //iterate the multiple comments for a post if comments are exists.
//            $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
//                    ->findBy(array('store_comment_id' => $comment->getId(), 'media_status' => 1));
//
//            $comment_media_data = array();
//            foreach ($comment_media as $media) { //finding the comment media from comment id.
//                $media_id = $media->getId();
//                $store_comment_id = $media->getStoreCommentId();
//                $media_name = $media->getMediaName();
//                $media_type = $media->getMediaType();
//                $media_created_at = $media->getMediaCreated();
//                $media_updated_at = $media->getMediaUpdated();
//                $media_status = $media->getMediaStatus();
//                $media_path = $media->getPath();
//                $media_is_featued = $media->getIsFeatured();
//                if ($media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                    $media_link = $media_path;
//                    $media_thumb = '';
//                } else {
//                    $media_link  = $this->getS3BaseUri() . $this->comment_media_path . $store_comment_id . '/' . $media_name;
//                    $media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $store_comment_id . '/' . $media_name;
//                }
//                $comment_media_data[] = array('id' => $media_id,
//                    'comment_id' => $store_comment_id,
//                    'media_path' => $media_link,
//                    'media_thumb' => $media_thumb,
//                    'media_type' => $media_type,
//                    'media_created_at' => $media_created_at,
//                    'media_status' => $media_status,
//                    'media_is_featured' => $media_is_featued
//                );
//            }
//            $user_info = $user_service->UserObjectService($comment->getCommentAuthor());
//            //prepare the comment array with comment media(assign in comment_media_data variable)
//            $comment_data[] = array('id' => $comment->getId(),
//                'post_id' => $comment->getPostId(),
//                'comment_text' => $comment->getCommentText(),
//                'comment_author' => $comment->getCommentAuthor(),
//                'comment_user_info' => $user_info,
//                'comment_created_at' => $comment->getCommentCreatedAt(),
//                'comment_updated_at' => $comment->getCommentUpdatedAt(),
//                'comment_status' => $comment->getStatus(),
//                'comment_media_info' => $comment_media_data
//            );
//        }
//        //prepare the data for response.
//        $data['post'] = $post_data;
//        $data['comment'] = $comment_data;
//        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
//        echo json_encode($res_data);
//        exit();
//    }
    
    
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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
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
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
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
     * edit the comments and uploading the files.
     * @param request object
     * @return json
     */
    public function postStoreeditcommentsAction(Request $request) {
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

        $required_parameter = array('post_id', 'user_id', 'comment_id', 'comment_author');
        $data = array();
        //checking for parameter missing.

        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            }
        }

        $time = new \DateTime("now");
        $post_type = (isset($object_info->post_type) ? $object_info->post_type : 1);
        $object_info->comment_text = (isset($object_info->comment_text) ? $object_info->comment_text : '');

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //finding the comment data
        $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($object_info->comment_id);
        if (!$comment_res) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        
        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);
        
        $existingTagging = $comment_res->getTagging();
        $newTagging = array();
        if(!empty($tagging)){
            $userTagging = !empty($existingTagging['user']) ? $existingTagging['user'] : array();
            $clubTagging = !empty($existingTagging['club']) ? $existingTagging['club'] : array();
            $shopTagging = !empty($existingTagging['shop']) ? $existingTagging['shop'] : array();
            $newTagging['user'] = array_diff($tagging['user'], $userTagging);
            $newTagging['club'] = array_diff($tagging['club'], $clubTagging);
            $newTagging['shop'] = array_diff($tagging['shop'], $shopTagging);
        }
        
        //ACL
        //Now we are getting information (store_id) of this post
        $post_id = $comment_res->getPostId();

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($object_info->post_id);
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $store_id = $post->getStoreId();

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userGroupRole($store_id, $object_info->user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        if ($do_action == 0) {
            //for store friend ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $store_id));
            
            $is_store_allow = $store->getIsAllowed();

            if ($is_store_allow == 1) {
                // checking mask using acl. if user is ownere of comment
                //post mask return value 15,7 i.e it is either author or creator of this post
                // if it is creator of this post it will update his post

                $post_mask = $this->userPostGuestRole($post_id, $object_info->user_id);
                $allow_friend = array('15', '7');
                if (in_array($post_mask, $allow_friend)) {
                    $do_action = 1;
                }
                if ($do_action == 0) {
                    $comment_mask = $this->userStoreGuestRole($object_info->comment_id, $object_info->user_id);
                    $allow_friend = array('15', '7');
                    if (in_array($comment_mask, $allow_friend)) {
                        $do_action = 1;
                    }
                }
            }
        }

        //ACL if have permission.
        if ($do_action) {
            if ($post_type == 0) { //if post type is 0
                $comment_res->setPostId($object_info->post_id);
                $comment_res->setCommentAuthor($object_info->comment_author);
                $comment_res->setCommentText($object_info->comment_text);
                $comment_res->setCommentUpdatedAt($time);
                $comment_res->setStatus(1); // 0=>disabled, 1=>enabled
                $comment_res->setTagging($tagging);
                $dm->persist($comment_res); //storing the comment data.
                $dm->flush();
                $comment_id = $object_info->comment_id; //getting the last inserted id of comments.
                $current_comment_media = array();
                $store_comment_media_id = 0;
                //for file uploading...
                if (isset($_FILES['commentfile'])) {
                    foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                        $original_file_name = $_FILES['commentfile']['name'][$key];
                        $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                        $store_comment_thumb_image_width = $this->store_comment_thumb_image_width;
                        $store_comment_thumb_image_height = $this->store_comment_thumb_image_height;
                        if (!empty($original_file_name)) {
                            $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                            $file_type = $_FILES['commentfile']['type'][$key];
                            $media_type = explode('/', $file_type);
                            $actual_media_type = $media_type[0];
                            
                            //find media information 
                            $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                            $orignal_mediaWidth = $image_info[0];
                            $original_mediaHeight = $image_info[1];
                            
                            //call service to get image type. Basis of this we save data 3,2,1 in db
                            $image_type_service = $this->get('user_object.service');
                            $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $store_comment_thumb_image_width, $store_comment_thumb_image_height);
                            
                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
                            $store_comment_media = new StoreCommentsMedia();
                            if (!$key) //consider first image the featured image.
                                $store_comment_media->setIsFeatured(1);
                            else
                                $store_comment_media->setIsFeatured(0);
                            $store_comment_media->setStoreCommentId($comment_id);
                            $store_comment_media->setMediaName($file_name);
                            $store_comment_media->setMediaType($actual_media_type);
                            $store_comment_media->setMediaCreated($time);
                            $store_comment_media->setMediaUpdated($time);
                            $store_comment_media->setPath('');
                            $store_comment_media->setImageType($image_type);
                            $store_comment_media->setMediaStatus(0); //set comment media status unpublished.
                            $store_comment_media->upload($comment_id, $key, $file_name); //uploading the files.
                            $dm->persist($store_comment_media);
                            $dm->flush();
                            if ($actual_media_type == 'image') {
                                $media_original_path = __DIR__ . "/../../../../web/uploads/documents/store/comments/original/" . $comment_id . '/';
                                $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->comment_media_path_thumb_crop . $comment_id . "/";
                                $thumb_dir = __DIR__ . "/../../../../web" . $this->comment_media_path_thumb . $comment_id . "/";
                                $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->comment_media_path_thumb_crop . $comment_id . "/";
                                //rotate the image if orientaion is not actual.
                                if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                                    $image_rotate_service  =  $this->get('image_rotate_object.service');
                                    $image_rotate          =  $image_rotate_service->ImageRotateService($media_original_path .$file_name);
                                }
                                //end of image rotate                                
                                
                                $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $comment_id);
                                //crop the image from center
                                $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $comment_id);
                            }
                            //get the comment media id
                            $store_comment_media_id = $store_comment_media->getId();
                        }
                    }
                }

                $object_info->youtube_url = (isset($object_info->youtube_url) ? $object_info->youtube_url : '');
                //handling og youtube url.
                if (!empty($object_info->youtube_url)) {
                    $store_comment_media = new StoreCommentsMedia();
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $store_comment_media = new StoreCommentsMedia();
                    $store_comment_media->setIsFeatured(0);
                    $store_comment_media->setStoreCommentId($comment_id);
                    $store_comment_media->setMediaName('');
                    $store_comment_media->setMediaType($this->youtube);
                    $store_comment_media->setMediaCreated($time);
                    $store_comment_media->setMediaUpdated($time);
                    $store_comment_media->setPath($object_info->youtube_url);
                    $store_comment_media->setMediaStatus(1);
                    $dm->persist($store_comment_media);
                    $dm->flush();
                }

                //finding the cureent media data.
                $comment_media_data = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                        ->find($store_comment_media_id);
                if (!$comment_media_data) {
                    return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }
                $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type =''; //initialize blank variables.
                if ($comment_media_data) {
                    $comment_image_type = $comment_media_data->getImageType();
                    $comment_media_name  = $comment_media_data->getMediaName();
                    $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }
                //sending the current media and post data.
                $data = array(
                    'comment_id' => $comment_id,
                    'media_id' => $store_comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'image_type' =>$comment_image_type
                );
                //code end for current media data
                $res_data =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit();
            } else { //if post_type is 1.
                //finding the comment data
                $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                        ->find($object_info->comment_id);
                if (!$comment_res) {
                    return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                } 
                $comment_data = $this->getEditCommentObject($object_info, $tagging); //finding the comment object.
                if(!empty($newTagging)){
                    $postService = $this->container->get('post_detail.service');
                    $postLink = $postService->getStoreClubUrl(array('storeId'=>$store_id, 'postId'=>$comment_res->getPostId()), 'store');
                    $postService->commentTaggingNotifications($newTagging, $comment_res->getCommentAuthor(), $comment_res->getId(), $postLink, 'store', true);
                }
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data); 
                echo json_encode($res_data);
                exit();
            }
        } else {
            $res_data = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * saving the comment data, and returning the comment object.
     * @param type $object_info
     * @return array
     */
    public function getEditCommentObject($object_info, $tagging=array()) {
        // get doctrine entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //code for adding the comment object returning
        $comment = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($object_info->comment_id);

        //Now we are getting information (post_id) of this post
        $post_id = $comment->getPostId();

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        
        $time = new \DateTime('now');
        //updating the comment..
        $comment->setPostId($object_info->post_id);
        $comment->setCommentAuthor($object_info->comment_author);
        $comment->setCommentText($object_info->comment_text);
        $comment->setCommentUpdatedAt($time);
        $comment->setStatus(1); //1=>enabled
        $comment->setTagging($tagging);
        $dm->persist($comment); //storing the comment data.
        $dm->flush();
        
        $user_service = $this->get('user_object.service');
        $user_info = array();
        $media_ids_array = array();
        $object_info->media_id = (isset($object_info->media_id) ? $object_info->media_id : $media_ids_array);
        $media_array = $object_info->media_id;
        //making the media publish..
        if (count($media_array)) {
            $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                    ->publishMedia($object_info->comment_id, $media_array);
        }

        //getting the comment media data..
        $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                ->findBy(array('store_comment_id' => $comment->getId(), 'media_status' => 1));

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
            $comment_image_type = $media->getImageType();
            $media_is_featued = $media->getIsFeatured();
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
            'comment_media_info' => $comment_media_data
        );
        //prepare the data for response.
        $data['post'] = $post;
        $data['comment'] = $comment_data;
        return $data;
    }

    /**
     * remove the media for comment.
     * @param string coomentid
     * @param string comment media id
     * @return json
     */
    public function postRemovemediacommentsAction(Request $request) {
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

        $required_parameter = array('comment_id', 'comment_media_id', 'user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $comment_media_id = $object_info->comment_media_id;
        $store_comment_id = $object_info->comment_id;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                ->find($object_info->comment_media_id);

        if (!$comment_media) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($object_info->comment_id);
        //ACL
        //Now we are getting information (store_id) of this post
        $post_id = $comment_res->getPostId();

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->findOneBy(array("id" => $post_id));
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $store_id = $post->getStoreId();

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userGroupRole($store_id, $object_info->user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        if ($do_action == 0) {
            //for store friend ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $store_id));
            
            $is_store_allow = $store->getIsAllowed();

            if ($is_store_allow == 1) {
                // checking mask using acl. if user is ownere of comment
                //post mask return value 15,7 i.e it is either author or creator of this post
                // if it is creator of this post it will update his post

                $post_mask = $this->userPostGuestRole($post_id, $object_info->user_id);
                $allow_friend = array('15', '7');
                if (in_array($post_mask, $allow_friend)) {
                    $do_action = 1;
                }
                if ($do_action == 0) {
                    $comment_mask = $this->userStoreGuestRole($object_info->comment_id, $object_info->user_id);
                    $allow_friend = array('15', '7');
                    if (in_array($comment_mask, $allow_friend)) {
                        $do_action = 1;
                    }
                }
            }
        }

        //ACL

        if ($do_action) {
            $media_type = $comment_media->getMediaType();
            $media_name = $comment_media->getMediaName();

            $dm->remove($comment_media);
            $dm->flush();
            if ($media_type == 'image' || $media_type == 'video') {
                //unlink the file..
                $media_path = __DIR__ . "/../../../../web" . $this->comment_media_path . $store_comment_id . '/' . $media_name;
                $media_path_thumb = __DIR__ . "/../../../../web" . $this->comment_media_path . $store_comment_id . '/' . $media_name;
                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (@file_exists($media_path)) {//remove original image.
                   //  @\unlink($media_path);
                }
                if (@file_exists($media_path_thumb)) {//remove thumb image.
                  //  @\unlink($media_path_thumb);
                }
            }
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 500, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
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
     * upload the groups files uploading the files.
     * @param request object
     * @return json
     */
    public function postUploadgroupsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        // return $de_serialize;
        $object_info = (object) $de_serialize; //convert an array into object.

        $filedata = $_FILES['group_media']['tmp_name'];
        $filename = $_POST['filename'];
        $original_file_name = $filename;
        $file_name = time() . $filename;
        if (!empty($original_file_name)) {
            $file_tmp = $filedata;
            //  $file_type = $_FILES['filedata']['type'];
            //   $media_type = explode('/', $file_type);
            //   $actual_media_type = $media_type[0];

            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $store_comment_media = new StoreCommentsMedia();

            //  $store_comment_media->upload(1, $key, $file_name); //uploading the files.
            $pre_upload_media_dir = __DIR__ . "/../../../../web/uploads/documents/store/comments/original/";
            //   copy($filedata,$pre_upload_media_dir.$file_name);
            $store_comment_media->upload1(1, 1, $file_name); //uploading the files.
        }
        return $object_info;
    }

    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/documents/store/comments/thumb/" . $comment_id . "/";
        //thumbnail image name with path
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory . $filename;

        $filename = $media_original_path . $filename; //original image name with path

        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width = $this->store_comment_thumb_image_width;
        $crop_image_height = $this->store_comment_thumb_image_height;

        //login for crop the image from center
        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75); //100 is quality
        
        $s3imagepath      = "uploads/documents/store/comments/thumb/" . $comment_id ;
        $image_local_path = $path_to_thumbs_center_directory.$original_filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
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
    
    /**
     * list of comments with media
     * @param request object
     * @return json
     */
    public function externalliststorecommentsAction(Request $request) {
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

        $required_parameter = array('post_id');
        $data = array();
        $post_data = array();
        $comment_media = array();
        $comment_data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_obj =  array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_obj);
            exit();
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //finding the post data
        $post_data = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($object_info->post_id);
        if (!$post_data) {
            $res_obj =  array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_obj);
            exit();
        }

        //finding the comment data
        $store_comments = new StoreComments();
        $comment_res = $comment_original = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->findBy(array('post_id' => $object_info->post_id, 'status' => 1));

        //$comment_user_id = $object_info->user_id;
        $post_id = $object_info->post_id;

        //Code for ACL checking
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $comment_user_id));

        $data1 = '';
//        if ($sender_user == '') {
//            $data1 = "USER_ID_IS_INVALID";
//        }
//        if (!empty($data1)) {
//            $res_obj = array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
//            echo json_encode($res_obj);
//            exit();
//        }
        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $store_id = $post_data->getStoreId();

        //for store ACL
        $do_action = 0;
        //$group_mask = $this->userGroupRole($store_id, $comment_user_id);
        //check for Access Permission
        //for owner and admin
//        $allow_group = array('15', '7');
//        if (in_array($group_mask, $allow_group)) {
//            $do_action = 1;
//        }

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
        foreach ($comment_original as $comment) { //iterate the multiple comments for a post if comments are exists.
            $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                    ->findBy(array('store_comment_id' => $comment->getId(), 'media_status' => 1));

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
                    'image_type'=>$comment_image_type
                );
            }
            $user_info = $user_service->UserObjectService($comment->getCommentAuthor());
            //prepare the comment array with comment media(assign in comment_media_data variable)
            $comment_data[] = array('id' => $comment->getId(),
                'post_id' => $comment->getPostId(),
                'comment_text' => $comment->getCommentText(),
                'comment_author' => $comment->getCommentAuthor(),
                'comment_user_info' => $user_info,
                'comment_created_at' => $comment->getCommentCreatedAt(),
                'comment_updated_at' => $comment->getCommentUpdatedAt(),
                'comment_status' => $comment->getStatus(),
                'comment_media_info' => $comment_media_data
            );
        }
        //prepare the data for response.
        $data['post'] = $post_data;
        $data['comment'] = $comment_data;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }
}