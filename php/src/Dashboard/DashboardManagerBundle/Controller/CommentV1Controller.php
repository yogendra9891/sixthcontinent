<?php

namespace Dashboard\DashboardManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sonata\UserBundle\Admin\Model as ald;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentsMedia;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostMedia;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Notification\NotificationBundle\Document\UserNotifications;
use Dashboard\DashboardManagerBundle\Document\DashboardPostRating;

class CommentV1Controller extends Controller {

    protected $miss_param = '';
    protected $youtube = 'youtube';
    protected $dashboard_post_media_path = '/uploads/documents/dashboard/post/original/';
    protected $dashboard_post_media_path_thumb = '/uploads/documents/dashboard/post/thumb/';
    protected $comment_media_path = '/uploads/documents/dashboard/comments/original/';
    protected $comment_media_path_thumb = '/uploads/documents/dashboard/comments/thumb/';
    protected $comment_post_media_path_thumb_crop = '/uploads/documents/dashboard/comments/thumb_crop/';
    protected $image_width = 100;
    protected $user_profile_type_code = 22;
    protected $profile_type_code = 'user';
    protected $post_comment_limit = 4;
    protected $post_comment_offset = 0;
    protected $dashboard_comment_thumb_image_width = 654;
    protected $dashboard_comment_thumb_image_height = 360;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;

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
     * 
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
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
     * Functionality decoding data
     * @param json $object	
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Gets the provider service
     *
     * @return ProviderInterface
     */
    protected function getProvider() {
        return $this->container->get('fos_message.provider');
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

                if (!(((($ext == 'jpeg' || $ext == 'jpg' || $ext == 'gif' || $ext == 'png') &&
                        ($_FILES['commentfile']['type'][$key] == 'image/jpg' || $_FILES['commentfile']['type'][$key] == 'image/jpeg' ||
                        $_FILES['commentfile']['type'][$key] == 'image/gif' || $_FILES['commentfile']['type'][$key] == 'image/png'))) || (preg_match('/^.*\.(mp3|mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }

    /**
     * creating the ACL 1
     * for the entity for a user
     * @param object $sender_user
     * @param object $dashboard_comment_entity
     * @return none
     */
    public function updateAclAction($sender_user, $dashboard_comment_entity) {
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($dashboard_comment_entity);
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
     * Function to retrieve current applications base URI(hostname/project/web)
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
    }

    /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }

    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }

    /**
     * Dashboard create comment...
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postDashboardcommentsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];

        if ($device_request_type == 'mobile') { //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else {
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $time = new \DateTime("now");
        $required_parameter = array('postid', 'user_id', 'comment_type');
        $data = array();
        $data_obj = array();
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


        $comment_user_id = $object_info->user_id;
        $post_id = $object_info->postid;
        $comment_type = $object_info->comment_type;
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        $body = '';
        if (isset($object_info->body)) {
            $body = $object_info->body;
        }

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $comment_user_id));

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        // comment for now only
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $time = new \DateTime("now");
        if ($comment_type == 0) {
            if ($object_info->comment_id == '') {

                $dashboard_comments = new DashboardComments();
                $dashboard_comments->setPostId($object_info->postid);
                $dashboard_comments->setUserId($object_info->user_id);
                $dashboard_comments->setCommentText($body);
                $dashboard_comments->setCreatedAt($time);
                $dashboard_comments->setUpdatedAt($time);
                $dashboard_comments->setIsActive(0); // 0=>disabled, 1=>enabled
                $dashboard_comments->setTagging($tagging);
                $dm->persist($dashboard_comments); //storing the comment data.
                $dm->flush();
                $comment_id = $dashboard_comments->getId(); //getting the last inserted id of posts.
                //update ACL for a user
                $this->updateAclAction($sender_user, $dashboard_comments);
            } else {
                $comment_id = $object_info->comment_id;
            }
            $comment_res = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                    ->find($comment_id);
            if (!$comment_res) {
                return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            }
            $current_comment_media = array();
            $dashboard_comment_media_id = 0;
            //getting the image name clean service object.
            $clean_name = $this->get('clean_name_object.service');

            //for file uploading...
            $image_upload = $this->get('amazan_upload_object.service');
            if (isset($_FILES['commentfile'])) {
                //for file uploading...
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $comment_thumb_image_width = $this->dashboard_comment_thumb_image_width;
                    $comment_thumb_image_height = $this->dashboard_comment_thumb_image_height;
                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
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
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $comment_thumb_image_width, $comment_thumb_image_height);

                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboard_comment_media = new DashboardCommentsMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboard_comment_media->setIsFeatured(1);
                        else
                            $dashboard_comment_media->setIsFeatured(0);
                        $dashboard_comment_media->setCommentId($comment_id);
                        $dashboard_comment_media->setMediaName($file_name);
                        $dashboard_comment_media->setType($actual_media_type);
                        $dashboard_comment_media->setCreatedAt($time);
                        $dashboard_comment_media->setUpdatedAt($time);
                        $dashboard_comment_media->setPath('');
                        $dashboard_comment_media->setIsActive(0);
                        $dashboard_comment_media->setImageType($image_type);
                        $dm->persist($dashboard_comment_media);
                        $dm->flush();

                        //get the dashboard media id
                        $dashboard_comment_media_id = $dashboard_comment_media->getId();

                        //update ACL for a user 
                        $this->updateAclAction($sender_user, $dashboard_comment_media);
                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_comment_media_path') . $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_comment_media_path') . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_comment_media_path_thumb') . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_comment_media_path_thumb_crop') . $comment_id . "/";
                        $s3_post_media_path = $this->container->getParameter('s3_dashboard_comment_media_path') . $comment_id;
                        $s3_post_media_thumb_path = $this->container->getParameter('s3_dashboard_comment_media_thumb_path') . $comment_id;
                        $image_upload->imageUploadService($_FILES['commentfile'], $key, $comment_id, 'dashboard_comment', $file_name, $pre_upload_media_dir, $media_original_path, $thumb_dir, $thumb_crop_dir, $s3_post_media_path, $s3_post_media_thumb_path);
                    }
                }
            }

            //finding the cureent media data.
            $comment_media_data = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->find($dashboard_comment_media_id);
            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type = ''; //initialize blank variables.
            if ($comment_media_data) {
                $comment_image_type = $comment_media_data->getImageType();
                $comment_media_name = $comment_media_data->getMediaName();

                $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
            }
            //sending the current media and post data.
            $data = array(
                'id' => $comment_id,
                'media_id' => $dashboard_comment_media_id,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' => $comment_image_type
            );
            $media_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_array);
            exit;
        } else {
            $postService = $this->get('post_detail.service');

            $DashboardCommentId = $object_info->comment_id;
            $media_id = $object_info->media_id;
            if (!empty($media_id)) {
                $media_update_status = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->publishCommentMediaImage($media_id);
            }
            if ($DashboardCommentId) {
                //finding the comment and making the comment publish.
                $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                        ->find($object_info->comment_id);
                if (!$comment) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }

                $postService->sendCommentNotificationEmail($post_id, $comment_user_id, 'dashboard', $object_info->comment_id, true, $tagging);

                $comment_data = $this->getCommentObject($object_info); //finding the post object.
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {

                $comment_data = $this->getCommentWithoutImageObject($object_info, $sender_user); //finding the post object.
                $postService->sendCommentNotificationEmail($post_id, $comment_user_id, 'dashboard', $comment_data['id'], true, $tagging);
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            }
        }
    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_data
     * @return array $commentdata
     */
    public function getCommentWithoutImageObject($object_data, $sender_user) {
        //code for responding the current post data..
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $time = new \DateTime('now');
        $body = '';
        if (isset($object_data->body)) {
            $body = $object_data->body;
        }
        $tagging = isset($object_data->tagging) ? $object_data->tagging : array();
        // updating the post data, making the post publish.
        $comment = new DashboardComments();
        $comment->setPostId($object_data->postid);
        $comment->setUserId($object_data->user_id);
        $comment->setCommentText($body);
        $comment->setCreatedAt($time);
        $comment->setUpdatedAt($time);
        $comment->setIsActive(1); // 0=>disabled, 1=>enabled
        $comment->setTagging($tagging);
        $dm->persist($comment);
        $dm->flush();
        $this->updateAclAction($sender_user, $comment);
        $sender_user_info = array();
        $user_service = $this->get('user_object.service');

        $comment_id = $comment->getId();
        $comment_user_id = $comment->getUserId(); //sender 

        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));

        // get entity manager object
        $post_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($object_data->postid);
        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getIsActive();
                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                $comment_media_created_at = $comment_media_data->getCreatedAt();
                $comment_image_type = $comment_media_data->getImageType();
                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $comment_media_link = '';
                    $comment_media_thumb = '';
                } else {
                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'is_featured' => $comment_media_is_featured,
                    'create_date' => $comment_media_created_at,
                    'image_type' => $comment_image_type
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'post_id' => $object_data->postid,
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getUserId(),
            'status' => $comment->getIsActive(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment->getCreatedAt(),
            'comment_media_info' => $comment_media_result,
            'avg_rate' => 0,
            'no_of_votes' => 0,
            'current_user_rate' => 0,
            'is_rated' => false
        );
        $commentdata = $data;

        return $commentdata;
    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_data
     * @return array $commentdata
     */
    public function getCommentObject($object_data) {
        //code for responding the current post data..
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_data->comment_id;
        $time = new \DateTime('now');
        $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($comment_id);

        // updating the post data, making the post publish.

        $body = '';
        if (isset($object_data->body)) {
            $body = $object_data->body;
        }
        $tagging = isset($object_data->tagging) ? $object_data->tagging : array();
        $comment->setPostId($object_data->postid);
        $comment->setUserId($object_data->user_id);
        $comment->setCommentText($body);
        $comment->setCreatedAt($time);
        $comment->setUpdatedAt($time);
        $comment->setIsActive(1); // 0=>disabled, 1=>enabled
        $comment->setTagging($tagging);
        $dm->persist($comment);
        $dm->flush();

        $sender_user_info = array();
        $user_service = $this->get('user_object.service');

        $comment_id = $comment->getId();
        $comment_user_id = $comment->getUserId(); //sender 

        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));

        // get entity manager object
        $post_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($object_data->postid);
        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getIsActive();
                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                $comment_media_created_at = $comment_media_data->getCreatedAt();
                $comment_image_type = $comment_media_data->getImageType();
                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $comment_media_link = '';
                    $comment_media_thumb = '';
                } else {
                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'is_featured' => $comment_media_is_featured,
                    'create_date' => $comment_media_created_at,
                    'image_type' => $comment_image_type
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'post_id' => $object_data->postid,
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getUserId(),
            'status' => $comment->getIsActive(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment->getCreatedAt(),
            'comment_media_info' => $comment_media_result,
            'avg_rate' => 0,
            'no_of_votes' => 0,
            'current_user_rate' => 0,
            'is_rated' => false
        );
        $commentdata = $data;

        return $commentdata;
    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_data
     * @return array $commentdata
     */
    public function getCommentEditObject($object_data, $post_id) {
        //code for responding the current post data..
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_data->comment_id;
        $time = new \DateTime('now');
        $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($comment_id);

        // updating the post data, making the post publish.

        $body = '';
        if (isset($object_data->comment_text)) {
            $body = $object_data->comment_text;
        }
        $comment->setPostId($post_id);
        $comment->setUserId($object_data->user_id);
        $comment->setCommentText($body);
        $comment->setCreatedAt($time);
        $comment->setUpdatedAt($time);
        $comment->setIsActive(1); // 0=>disabled, 1=>enabled

        $dm->persist($comment);
        $dm->flush();

        $sender_user_info = array();
        $user_service = $this->get('user_object.service');

        $comment_id = $comment->getId();
        $comment_user_id = $comment->getUserId(); //sender 

        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));

        // get entity manager object
        $post_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);
        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getIsActive();
                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                $comment_media_created_at = $comment_media_data->getCreatedAt();
                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $comment_media_link = '';
                    $comment_media_thumb = '';
                } else {
                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'is_featured' => $comment_media_is_featured,
                    'create_date' => $comment_media_created_at
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'post_id' => $post_id,
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getUserId(),
            'status' => $comment->getIsActive(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment->getCreatedAt(),
            'comment_media_info' => $comment_media_result
        );
        $commentdata = $data;

        return $commentdata;
    }

    /**
     * Delete media for comment on dashboard
     * @param request object	
     * @return json string
     */
    public function postDashboardmedaideletecommentsAction(Request $request) {
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


        $required_parameter = array('user_id', 'image_id');
        $data = array();
        $data_obj = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $media_id = $object_info->image_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                ->find($media_id);


        if (!$comment_media) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userCommentMediaRole($object_info->image_id, $object_info->user_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        //ACL

        if ($do_action) {

            if ($comment_media) {
                //removing the images from directory

                $comment_id = $comment_media->getCommentId();
                $media_type = $comment_media->getType();
                $media_name = $comment_media->getMediaName();
                $dm->remove($comment_media);
                $dm->flush();

                if ($media_type == 'image' || $media_type == 'video') {
                    //unlink the file..
                    $media_path = __DIR__ . "/../../../../web" . $this->comment_media_path . $comment_id . '/' . $media_name;
                    // Commenting these line becauase images are not present on s3 Amazon server.
                    //Since in push images folder are not used
                    if (@file_exists($media_path)) {
                        //  @\unlink($media_path);
                    }
                    $media_path_thumb = __DIR__ . "/../../../../web" . $this->comment_media_path_thumb . $comment_id . '/' . $media_name;
                    if (@file_exists($media_path_thumb)) {
                        // @\unlink($media_path_thumb);
                    }
                }
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            }
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
    }

    /**
     * Delete comment with media
     * @param request object	
     * @return json string
     */
    public function postDashboarddeletecommentsAction(Request $request) {
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


        $required_parameter = array('user_id', 'comment_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }


        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($comment_id);

        if (!$comment_res) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userCommentRole($object_info->comment_id, $object_info->user_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        //ACL

        if ($do_action) {
            $dm->remove($comment_res);
            $dm->flush();

            $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->removeDashboardCommentsMedia($object_info->comment_id);

            if ($comment_media) {
                //removing the images from directory
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $comment_images_location = $file_location . $this->comment_media_path . $object_info->comment_id;
                $comment_images_thumb_location = $file_location . $this->comment_media_path_thumb . $object_info->comment_id;
                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (file_exists($comment_images_location)) {
                    // array_map('unlink', glob($comment_images_location . '/*')); //remove the directory recursively.
                    //  rmdir($comment_images_location);
                }
                if (file_exists($comment_images_thumb_location)) {
                    //  array_map('unlink', glob($comment_images_thumb_location . '/*')); //remove the directory recursively.
                    //  rmdir($comment_images_thumb_location);
                }
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            }
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
    }

    /**
     * Finding list of comment for dashboard
     * @param request object	
     * @return json string
     */
    public function postGetcommentlistsAction(Request $request) {
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
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => $data);
        }

        $post_id = $object_info->post_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);
        if (!$post_res) {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->getMyDashboardCommentList($post_id, $limit, $offset);

        $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
                        ->getMyDashboardCommentCount($post_id));
        $comment_data = array();
        $comment_user_info = array();
        $user_service = $this->get('user_object.service');
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();
                $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));
                $comment_media_result = array();
                if ($comment_media) {
                    foreach ($comment_media as $comment_media_data) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getIsActive();
                        $comment_media_is_featured = $comment_media_data->getIsFeatured();
                        $comment_media_created_at = $comment_media_data->getCreatedAt();
                        $comment_image_type = $comment_media_data->getImageType();
                        if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            $comment_media_link = $post_media_data->getPath();
                            $comment_media_thumb = '';
                        } else {
                            $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                            $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                        }

                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'is_featured' => $comment_media_is_featured,
                            'create_date' => $comment_media_created_at,
                            'image_type' => $comment_image_type);
                    }
                }

                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'status' => $comment->getIsActive(),
                    'comment_user_info' => $comment_user_info,
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result);
            }
        }
        $data['comment'] = $comment_data;
        $data['count'] = $post_data_count;
        $final_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data_array);
        exit;
    }

    /**
     * Finding list of post with comments for dashboard
     * @param request object
     * @return json string
     */
//    public function postGetdashboardfeedsAction(Request $request) {
//        //Code start for getting the request
//        $freq_obj      = $request->get('reqObj');
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
//
//        $required_parameter = array('user_id', 'friend_id');
//        $data   = array();
//        $limit  = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
//        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);
//
//
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        
//        $friend_id       = $object_info->friend_id;
//        $current_user_id = $object_info->user_id;
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));
//
//        // get entity manager object
//        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//        $posts = $dm->getRepository('DashboardManagerBundle:DashboardPost')
//                ->findAll();
//
//        $em_user = $this->getDoctrine()->getManager();
//        //fire the query in User Repository
//        $user_info = $em_user
//                ->getRepository('UserManagerSonataUserBundle:User')
//                ->findBy(array('id' => $object_info->friend_id));
//
//        if ($user_info) {
//            $user_country_code = $user_info[0]->getCountry();
//        }
//
//        $user_ids_arr = array();
//        $user_ids_arr[] = "{$object_info->friend_id}";
//        if ($posts) {
//            foreach ($posts as $key => $value) {
//
//                $curr_user_id = $value->getToId();
//                $em = $this->getDoctrine()->getManager();
//                //fire the query in User Repository
//                $results = $em
//                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
//                        ->getUserRole($curr_user_id, $user_country_code);
//                if ($results) {
//                    $user_ids_arr[] = "{$results[0]['userId']}";
//                }
//            }
//        }
//
//        //get entity manager object
//        $em = $this->getDoctrine()->getManager();
//
//        //fire the query in User Repository
//        // get my friends's user ids
//        $results = $em
//                ->getRepository('UserManagerSonataUserBundle:UserConnection')
//                ->getMyFriends($object_info->friend_id);
//
//        $friends_user_arr = array();
//        $friends_user_arr_unique = array();
//
//        //finding the following user ids.
//        $follwings_users = $em->getRepository('UserManagerSonataUserBundle:UserFollowers')
//                ->getFollowings($object_info->friend_id, null, null); //null pass for all record in repository limit and offset
//        // are used so we are using the same function thats why we have to pass null
//        $following_user_ids = array();
//        if ($follwings_users) {
//            foreach ($follwings_users as $follwings_user) {
//                $following_user_ids[] = "{$follwings_user['id']}";
//            }
//        }
//        //end code for finding the followings users...
//
//        if ($results) {
//            foreach ($results as $record) {
//                $friends_user_arr[] = "{$record['connectTo']}";
//            }
//        }
//        $friends_user_arr_unique = array_unique($friends_user_arr);
//
//        $user_ids_arr_final = array_unique(array_merge($user_ids_arr, $friends_user_arr_unique, $following_user_ids));
//
//        $posts_data = array();
//        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        if ($current_user_id == $friend_id) { // if a user seeing his dashboard.
//          $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
//                               ->getDashboardPosts($user_ids_arr, $friends_user_arr_unique, $following_user_ids, $user_ids_arr, $limit, $offset);  
//        } else { //user seing other user dasboard
//          $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
//                               ->getNonFriendDashboardPosts($user_ids_arr, $friends_user_arr_unique, $following_user_ids, $user_ids_arr, $limit, $offset);   
//        }
//        
//        $post_data_count = 0;        
//        $post_data = array(); //final array of post data...
//        if (count($posts_data) > 0) {
//            if ($current_user_id == $friend_id) { // if a user seeing his dashboard.
//                $post_data_count = $dm->getRepository('DashboardManagerBundle:DashboardPost')
//                            ->getDashboardPostsCount($user_ids_arr, $friends_user_arr_unique, $following_user_ids, $user_ids_arr);
//            } else { //user seing other user dashboard
//                $post_data_count = $dm->getRepository('DashboardManagerBundle:DashboardPost')
//                            ->getNonFriendDashboardPostsCount($user_ids_arr, $friends_user_arr_unique, $following_user_ids, $user_ids_arr);
//            }
//            
//        }
//        $user_info = array();
//        $reciver_user_info = array();
//        $user_service = $this->get('user_object.service');
//        if ($posts_data) {
//            foreach ($posts_data as $post) {
//                $post_id = $post->getId();
//                $post_user_id = $post->getUserId(); //sender
//
//                $post_to_id = $post->getToId(); //receiver
//
//                $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
//                        ->findBy(array('post_id' => $post_id, 'media_status' => 1));
//
//                $user_info = $user_service->UserObjectService($post_user_id); //sender
//                $reciver_user_info = $user_service->UserObjectService($post_to_id); //receiver
//
//                $post_media_result = array();
//                if ($post_media) {
//                    foreach ($post_media as $post_media_data) {
//                        $post_media_id = $post_media_data->getId();
//                        $post_media_type = $post_media_data->getType();
//                        $post_media_name = $post_media_data->getMediaName();
//                        $post_media_status = $post_media_data->getMediaStatus();
//                        $post_media_is_featured = $post_media_data->getIsFeatured();
//                        $post_media_created_at = $post_media_data->getCreatedDate();
//                        if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                            $post_media_link = $post_media_data->getPath();
//                            $post_media_thumb = '';
//                        } else {
//                            $post_media_link  = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
//                            $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
//                        }
//                        $post_media_result[] = array(
//                            'id' => $post_media_id,
//                            'media_link' => $post_media_link,
//                            'media_thumb_link' => $post_media_thumb,
//                            'status' => $post_media_status,
//                            'is_featured' => $post_media_is_featured,
//                            'create_date' => $post_media_created_at);
//                    }
//                }
//
//                $comment_count = 0;
//                //finding the comments start.
//                $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
//                        ->findBy(array('post_id' => $post_id, 'is_active' => 1), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
//                $comments = array_reverse($comments);
//                if (count($comments)) { // if a post has comments then we need to count total counts.
//                    $comment_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
//                                     ->findBy(array('post_id' => $post_id, 'is_active' => 1)));    
//                }
//                $comment_data = array();
//                $comment_user_info = array();
//                if ($comments) {
//                    foreach ($comments as $comment) {
//                        $comment_id = $comment->getId();
//                        $comment_user_id = $comment->getUserId();
//                        $comment_user_profile_type = $comment->getProfileType();
//                        //code for user active profile check                        
//                        $comment_user_info = $user_service->UserObjectService($comment_user_id);
//                        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
//                                ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));
//                        $comment_media_result = array();
//                        if ($comment_media) {
//                            foreach ($comment_media as $comment_media_data) {
//                                $comment_media_id = $comment_media_data->getId();
//                                $comment_media_type = $comment_media_data->getType();
//                                $comment_media_name = $comment_media_data->getMediaName();
//                                $comment_media_status = $comment_media_data->getIsActive();
//                                $comment_media_is_featured = $comment_media_data->getIsFeatured();
//                                $comment_media_created_at = $comment_media_data->getCreatedAt();
//                                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                                    $comment_media_link = $post_media_data->getPath();
//                                    $comment_media_thumb = '';
//                                } else {
//                                    $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
//                                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
//                                }
//
//                                $comment_media_result[] = array(
//                                    'id' => $comment_media_id,
//                                    'media_link' => $comment_media_link,
//                                    'media_thumb_link' => $comment_media_thumb,
//                                    'status' => $comment_media_status,
//                                    'is_featured' => $comment_media_is_featured,
//                                    'create_date' => $comment_media_created_at);
//                            }
//                        }
//
//                        $comment_data[] = array(
//                            'id' => $comment_id,
//                            'post_id' => $comment->getPostId(),
//                            'comment_text' => $comment->getCommentText(),
//                            'user_id' => $comment->getUserId(),
//                            'comment_user_info' => $comment_user_info,
//                            'status' => $comment->getIsActive(),
//                            'create_date' => $comment->getCreatedAt(),
//                            'comment_media_info' => $comment_media_result);
//                    }
//                }
//
//                //finding the comments end.
//                $post_data [] = array(
//                    'id' => $post_id,
//                    'user_id' => $post->getUserId(),
//                    'to_id' => $post->getToId(),
//                    'title' => $post->getTitle(),
//                    'description' => $post->getDescription(),
//                    'link_type' => $post->getLinkType(),
//                    'is_active' => $post->getIsActive(),
//                    'privacy_setting'=> $post->getPrivacySetting(),
//                    'created_at' => $post->getCreatedDate(),
//                    'user_info' => $user_info,
//                    'receiver_user_info' => $reciver_user_info,
//                    'media_info' => $post_media_result,
//                    'comments' => $comment_data,
//                    'comment_count'=>$comment_count
//                );
//            }
//        }
//
//        $data['post'] = $post_data;
//        $data['count'] = $post_data_count;
//        $final_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
//        echo json_encode($final_data);
//        exit();
//    }

    /**
     * Update comment of a post.........
     * @param request object	
     * @return json string
     */
    public function postDashboardeditcommentsAction(Request $request) {

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


        $required_parameter = array('user_id', 'comment_id');
        $data = array();
        $data_obj = array();
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

        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($comment_id);


        if (!$comment_res) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        
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
        
        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userCommentRole($comment_id, $object_info->user_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');

        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        $comment_body = '';
        if (isset($object_info->comment_text)) {
            $comment_body = $object_info->comment_text;
        }
        //ACL
        if ($do_action) {
            //checking the active profile start.
            //get entity manager object
            $em = $this->getDoctrine()->getManager();

            //checking for active profile end.

            $comment_res->setCommentText($comment_body);
            $comment_res->setUpdatedAt(time());
            $comment_res->setIsActive(1); // 0=>disabled, 1=>enabled
            $comment_res->setTagging($tagging);
            $dm->persist($comment_res); //storing the comment data.
            $dm->flush();
            $comment_id = $object_info->comment_id; //getting the last inserted id of comments.
            if (isset($_FILES['commentfile'])) {
                //for file uploading...
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    $comment_thumb_image_width = $this->dashboard_comment_thumb_image_width;
                    $comment_thumb_image_height = $this->dashboard_comment_thumb_image_height;
                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
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
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $comment_thumb_image_width, $comment_thumb_image_height);

                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboard_comment_media = new DashboardCommentsMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboard_comment_media->setIsFeatured(1);
                        else
                            $dashboard_comment_media->setIsFeatured(0);
                        $dashboard_comment_media->setCommentId($comment_id);
                        $dashboard_comment_media->setMediaName($file_name);
                        $dashboard_comment_media->setType($actual_media_type);
                        $dashboard_comment_media->setCreatedAt(time());
                        $dashboard_comment_media->setUpdatedAt(time());
                        $dashboard_comment_media->setPath('');
                        $dashboard_comment_media->setIsActive(1);
                        $dashboard_comment_media->setImageType($image_type);
                        $dashboard_comment_media->upload($comment_id, $key, $file_name); //uploading the files.
                        $dm->persist($dashboard_comment_media);
                        $dm->flush();

                        //update ACL for a user 
                        $this->updateAclAction($sender_user, $dashboard_comment_media);
                        if ($actual_media_type == 'image') {
                            $media_original_path = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/original/" . $comment_id . '/';
                            $thumb_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/thumb/" . $comment_id . '/';
                            $thumb_crop_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/thumb_crop/" . $comment_id . "/";
                            $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $comment_id);
                            $this->createCenterThumbnail($file_name, $thumb_crop_dir, $thumb_dir, $comment_id);
                        }
                    }
                }
            }


            if ($comment_id) {
                $dm_obj = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                $comment_obj = $dm_obj->getRepository('DashboardManagerBundle:DashboardComments')
                        ->find($comment_id);
            }
            $comment_media_result = array();
            $comment_user_info = array();
            if ($comment_obj) {
                $dm_obj_media = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                $comment_obj_media = $dm_obj_media->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id));
                $post_id = $comment_obj->getPostId();
                if ($post_id) {
                    $dm_post_object = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                    $post_obj_res = $dm_post_object->getRepository('DashboardManagerBundle:DashboardPost')
                            ->find($post_id);
                }
                if ($comment_obj_media) {
                    foreach ($comment_obj_media as $comment_media_data) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getIsActive();
                        $comment_media_is_featured = $comment_media_data->getIsFeatured();
                        $comment_media_created_at = $comment_media_data->getCreatedAt();
                        $comment_image_type = $comment_media_data->getImageType();
                        if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            if ($post_obj_res) {
                                $comment_media_link = $post_obj_res->getPath();
                                $comment_media_thumb = '';
                            }
                        } else {
                            $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                            $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                        }

                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'is_featured' => $comment_media_is_featured,
                            'create_date' => $comment_media_created_at,
                            'image_type' => $comment_image_type
                        );
                    }
                }

                $user_service = $this->get('user_object.service');
                $comment_user_info = $user_service->UserObjectService($object_info->user_id);
            }


            $data_obj[] = array(
                'id' => $comment_id,
                'post_id' => $comment_res->getPostId(),
                'comment_text' => $comment_obj->getCommentText(),
                'user_id' => $comment_obj->getUserId(),
                'status' => $comment_obj->getIsActive(),
                'comment_user_info' => $comment_user_info,
                'create_date' => $comment_obj->getCreatedAt(),
                'comment_media_info' => $comment_media_result
            );
            $data = $data_obj;
            $final_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            if(!empty($newTagging)){
                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $postService = $this->container->get('post_detail.service');
                $postLink = $angular_app_hostname.'post/'. $comment_res->getPostId();
                $postService->commentTaggingNotifications($newTagging, $object_info->user_id, $comment_id, $postLink, 'dashboard', true);
            }
            echo json_encode($final_data_array);
            exit;
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
    }

    /**
     * Get User role for store
     * @param int $comment_id
     * @param int $user_id
     * @return int
     */
    public function userCommentRole($comment_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($comment_id);


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
     * Get User role for store
     * @param int $media_id
     * @param int $user_id
     * @return int
     */
    public function userCommentMediaRole($media_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                ->find($media_id);


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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/thumb_crop/" . $comment_id . "/";

        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->dashboard_comment_thumb_image_width;
        $thumb_height = $this->dashboard_comment_thumb_image_height;
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
                die("THERE_WAS_A_PROBLEM._PLEASE_TRY_AGAIN!");
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

        $s3imagepath = "uploads/documents/dashboard/comments/thumb_crop/" . $comment_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        $imagename = $filename;
        $filename = $media_original_path . $filename;


        if (preg_match('/[.](jpg)$/', $imagename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $imagename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $imagename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $imagename)) {
            $image = imagecreatefrompng($filename);
        }
        //$image = imagecreatefromjpeg($filename);
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width = imagesx($image);
        $height = imagesy($image);

        $crop_image_width = $this->dashboard_comment_thumb_image_width;
        $crop_image_height = $this->dashboard_comment_thumb_image_height;


        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        // This will be the final size of the image (e.g. how many pixels
        // left and down we will be going)
        $crop_width = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);


        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/thumb/" . $comment_id . "/";
        $path_to_thumbs_center_image_path = __DIR__ . "/../../../../web/uploads/documents/dashboard/comments/thumb/" . $comment_id . "/" . $imagename;
        //   $path_to_thumbs_directory = $thumb_dir;
        //$path_to_image_directory = $media_original_path;
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);

        $s3imagepath = "uploads/documents/dashboard/comments/thumb/" . $comment_id;
        $image_local_path = $path_to_thumbs_center_directory . $imagename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $imagename);
    }

    /**
     * getting the user info object
     * @param int $post_user_id
     * @param int $profile_type
     * @return array
     */
    private function getUserInfo($post_user_id, $profile_type) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in UserMultiProfile Repository
        $return_user_profile_data = $em->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->getUserProfileDetails($post_user_id, $profile_type);
        $return_data = array();
        if (count($return_user_profile_data)) {
            $user_profile_data = $return_user_profile_data[0];
            $return_data = array('user_id' => $user_profile_data->getUserId(),
                'first_name' => $user_profile_data->getFirstName(),
                'last_name' => $user_profile_data->getLastName(),
                'email' => $user_profile_data->getEmail(),
                'gender' => $user_profile_data->getGender(),
                'birth_date' => $user_profile_data->getBirthDate(),
                'phone' => $user_profile_data->getPhone(),
                'country' => $user_profile_data->getCountry(),
                'street' => $user_profile_data->getStreet(),
                'profile_type' => $user_profile_data->getProfileType(),
                'created_at' => $user_profile_data->getCreatedAt(),
                'is_active' => $user_profile_data->getIsActive(),
                'updated_at' => $user_profile_data->getUpdatedAt(),
                'profile_setting' => $user_profile_data->getProfileSetting(),
                'type' => 'user'
            );
        }
        return $return_data;
    }

    /**
     * getting the store info object
     * @param int $store_id
     * @return array
     */
    private function getStoreInfo($store_id) {

        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in Store Repository
        $return_store_profile_data = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findBy(array('id' => $store_id));
        $return_data = array();
        if (count($return_store_profile_data)) {
            $store_profile_data = $return_store_profile_data[0];
            $return_data = array('store_id' => $store_profile_data->getId(),
                'parent_store_id' => $store_profile_data->getParentStoreId(),
                'title' => $store_profile_data->getTitle(),
                'email' => $store_profile_data->getEmail(),
                'url' => $store_profile_data->getUrl(),
                'description' => $store_profile_data->getDescription(),
                'address' => $store_profile_data->getAddress(),
                'contact_number' => $store_profile_data->getContactNumber(),
                'created_at' => $store_profile_data->getCreatedAt(),
                'is_active' => $store_profile_data->getIsActive(),
                'is_allowed' => $store_profile_data->getIsAllowed(),
                'type' => 'store'
            );
        }
        return $return_data;
    }

    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $post_id, $album_id = null) {
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/" . $album_id . '/';
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
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
        //check a image is less than defined size.. 
        if ($ox > $thumb_width || $oy > $thumb_height) {
            //getting aspect ratio
            $original_aspect = $ox / $oy;
            $thumb_aspect = $thumb_width / $thumb_height;

            if ($original_aspect >= $thumb_aspect) {
                // If image is wider than thumbnail (in aspect ratio sense)
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
                //check if new width is less than minimum width
                if ($new_width > $thumb_width) {
                    $new_width = $thumb_width;
                    $new_height = $oy / ($ox / $thumb_width);
                }
            } else {
                // If the thumbnail is wider than the image
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
                //check if new height is less than minimum height
                if ($new_height > $thumb_height) {
                    $new_height = $thumb_height;
                    $new_width = $ox / ($oy / $thumb_height);
                }
            }
            $nx = $new_width;
            $ny = $new_height;
        } else {
            $nx = $ox;
            $ny = $oy;
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

        $s3imagepath = "uploads/documents/dashboard/comments/original/" . $post_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * Finding list of post with comments for dashboard for a user wall
     * @param request object	
     * @return json string
     */
//    public function postGetdashboardwallfeedsAction(Request $request) {
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
//        $required_parameter = array('user_id', 'friend_id');
//        $data = array();
//        $user_ids_arr_final = array();
//        $limit  = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
//        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);
//
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));
//
//        // get entity manager object
//        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//
//        $em_user = $this->getDoctrine()->getManager();
//
//        //get entity manager object
//        $em = $this->getDoctrine()->getManager();
//        $user_id   = $object_info->user_id;
//        $friend_id = $object_info->friend_id;
//        
//        $is_friend       = 0;
//        $post_data_count = 0;
//        if ($user_id == $friend_id) { //checking the user is seeing his wall (user_id == friend_id)
//           $own_wall = 1;    
//        } else { //other user wall.
//           $own_wall = 0;
//           //cheking the friendship.
//           $friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
//                              ->checkFriendShip($user_id, $friend_id);
//           if ($friend_check) {
//               $is_friend = 1;
//           }
//           
//        }
//        $user_ids_arr_final = array("{$object_info->friend_id}");
//        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        if ($own_wall) { //user seeing own his wall
//            $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
//                                   ->getMyWallPosts($user_ids_arr_final, $limit, $offset);
//            if (count($posts_data)) {
//                $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
//                                    ->getMyWallCountPosts($user_ids_arr_final)); //post count..
//            }
//        } else { // user seeing other user (may be friend of current user)
//            $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
//                               ->getOtherUserWallPosts($user_ids_arr_final, $is_friend, $limit, $offset);
//            if (count($posts_data)) {
//                $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
//                                            ->getOtherUserWallCountPosts($user_ids_arr_final, $is_friend)); //post count..                
//            }
//        }
//
//        $post_data = array(); //final array of post data...
//
//
//        $user_info = array();
//        $reciver_user_info = array();
//        $user_service = $this->get('user_object.service');
//        if ($posts_data) {
//            foreach ($posts_data as $post) {
//                $post_id = $post->getId();
//                $post_user_id = $post->getUserId(); //sender
//
//                $post_to_id = $post->getToId(); //receiver
//
//                $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
//                        ->findBy(array('post_id' => $post_id, 'media_status' => 1));
//
//                $user_info = $user_service->UserObjectService($post_user_id); //sender
//                $reciver_user_info = $user_service->UserObjectService($post_to_id); //receiver
//
//                $post_media_result = array();
//                if ($post_media) {
//                    foreach ($post_media as $post_media_data) {
//                        $post_media_id = $post_media_data->getId();
//                        $post_media_type = $post_media_data->getType();
//                        $post_media_name = $post_media_data->getMediaName();
//                        $post_media_status = $post_media_data->getMediaStatus();
//                        $post_media_is_featured = $post_media_data->getIsFeatured();
//                        $post_media_created_at = $post_media_data->getCreatedDate();
//                        if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                            $post_media_link = $post_media_data->getPath();
//                            $post_media_thumb = '';
//                        } else {
//                            $post_media_link  = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
//                            $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
//                        }
//                        $post_media_result[] = array(
//                            'id' => $post_media_id,
//                            'media_link' => $post_media_link,
//                            'media_thumb_link' => $post_media_thumb,
//                            'status' => $post_media_status,
//                            'is_featured' => $post_media_is_featured,
//                            'create_date' => $post_media_created_at);
//                    }
//                }
//                
//                $comment_count = 0;
//                //finding the comments start.
//                $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
//                        ->findBy(array('post_id' => $post_id, 'is_active' => 1), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
//                $comments = array_reverse($comments);
//                if (count($comments)) {
//                  $comment_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
//                                       ->findBy(array('post_id' => $post_id, 'is_active' => 1)));  
//                }
//                $comment_data = array();
//                $comment_user_info = array();
//                if ($comments) {
//                    foreach ($comments as $comment) {
//                        $comment_id = $comment->getId();
//                        $comment_user_id = $comment->getUserId();
//                        $comment_user_profile_type = $comment->getProfileType();
//                        //code for user active profile check                        
//                        $comment_user_info = $user_service->UserObjectService($comment_user_id);
//                        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
//                                ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));
//                        $comment_media_result = array();
//                        if ($comment_media) {
//                            foreach ($comment_media as $comment_media_data) {
//                                $comment_media_id = $comment_media_data->getId();
//                                $comment_media_type = $comment_media_data->getType();
//                                $comment_media_name = $comment_media_data->getMediaName();
//                                $comment_media_status = $comment_media_data->getIsActive();
//                                $comment_media_is_featured = $comment_media_data->getIsFeatured();
//                                $comment_media_created_at = $comment_media_data->getCreatedAt();
//                                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                                    $comment_media_link = $post_media_data->getPath();
//                                    $comment_media_thumb = '';
//                                } else {
//                                    $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
//                                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
//                                }
//
//                                $comment_media_result[] = array(
//                                    'id' => $comment_media_id,
//                                    'media_link' => $comment_media_link,
//                                    'media_thumb_link' => $comment_media_thumb,
//                                    'status' => $comment_media_status,
//                                    'is_featured' => $comment_media_is_featured,
//                                    'create_date' => $comment_media_created_at);
//                            }
//                        }
//
//                        $comment_data[] = array(
//                            'id' => $comment_id,
//                            'post_id' => $comment->getPostId(),
//                            'comment_text' => $comment->getCommentText(),
//                            'user_id' => $comment->getUserId(),
//                            'comment_user_info' => $comment_user_info,
//                            'status' => $comment->getIsActive(),
//                            'create_date' => $comment->getCreatedAt(),
//                            'comment_media_info' => $comment_media_result);
//                    }
//                }
//
//                //finding the comments end.
//                $post_data [] = array(
//                    'id' => $post_id,
//                    'user_id' => $post->getUserId(),
//                    'to_id' => $post->getToId(),
//                    'title' => $post->getTitle(),
//                    'description' => $post->getDescription(),
//                    'link_type' => $post->getLinkType(),
//                    'is_active' => $post->getIsActive(),
//                    'privacy_setting'=>$post->getPrivacySetting(),
//                    'created_at' => $post->getCreatedDate(),
//                    'user_info' => $user_info,
//                    'receiver_user_info' => $reciver_user_info,
//                    'media_info' => $post_media_result,
//                    'comments' => $comment_data,
//                    'comment_count'=>$comment_count
//                );
//            }
//        }
//        $data['post']  = $post_data;
//        $data['count'] = $post_data_count;
//        $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
//        echo json_encode($final_array);
//        exit;
//    }

    /**
     * Finding list of post with comments for my dashboard
     * @param request object
     * @return json string
     */
    public function postGetdashboardfeedsAction(Request $request) {
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

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);
        $last_post_id = (isset($de_serialize['last_post_id']) ? $de_serialize['last_post_id'] : '');                  

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $friend_id = $object_info->friend_id;
        $current_user_id = $object_info->user_id;
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
//        $user_info = $em->getRepository('UserManagerSonataUserBundle:User')
//                ->findBy(array('id' => $object_info->friend_id));
//        $user_country_code = ''; //intialize the country code by blank.
//        if ($user_info) {
//            $user_country_code = $user_info[0]->getCountry();
//        }

        $post = array();
        $post_user_ids_arr = array();
        $user_ids_arr = $friends_users = $following_users = $citizen_writer = array(); //intialize the array
        $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array
        $user_ids_arr[] = "{$object_info->friend_id}";

        //finding the following, friends ids.
        $followings_friend_users = $em->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowingsandFriends($object_info->friend_id); //null pass for all record in repository limit and offset

        $personal_friends_users = $professional_friends_users = $following_users = $citizen_writer = array();
        //if records.
        if (count($followings_friend_users)) {
            foreach ($followings_friend_users as $following_friend_users) {
                $status = $following_friend_users['status'];

                switch ($status) {
                    CASE 1: //personal friend users
                        $personal_friends_users[] = "{$following_friend_users['id']}";
                        break;
                    CASE 2: //professional friend users
                        $professional_friends_users[] = "{$following_friend_users['id']}";
                        break;
                    CASE 3; //following users
                        $following_users[] = "{$following_friend_users['id']}";
                        break;
                    default:
                        $citizen_writer[] = "{$following_friend_users['id']}"; //this will remain blank array because we are not finding the citizen writer.
                }
            }
        }



        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        if ($current_user_id == $friend_id) { // if a user seeing his dashboard.
            $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->getDashboardPosts($user_ids_arr, $personal_friends_users, $professional_friends_users, $following_users, $citizen_writer, $limit, $offset, $last_post_id);
        } else { //user seing other user dasboard
            $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->getNonFriendDashboardPosts($user_ids_arr, $personal_friends_users, $professional_friends_users, $following_users, $citizen_writer, $limit, $offset, $last_post_id);
        }

        $post_data_count = 0;
        $comment_count = 0;
        $post_data = array();

        if (count($posts_data) > 0) {
            if ($current_user_id == $friend_id) { // if a user seeing his dashboard.
                $post_data_count = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->getDashboardPostsCount($user_ids_arr, $personal_friends_users, $professional_friends_users, $following_users, $citizen_writer);
            } else { //user seing other user dashboard
                $post_data_count = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->getNonFriendDashboardPostsCount($user_ids_arr, $personal_friends_users, $professional_friends_users, $following_users, $citizen_writer);
            }
        }

        //getting the posts ids.
        $post_ids = array_map(function($posts) {
            return "{$posts->getId()}";
        }, $posts_data);

        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($posts) {
            return "{$posts->getUserId()}";
        }, $posts_data);

        //getting the tagged user ids.
        $post_tagged_user_ids = array_map(function($posts) {

            if (is_array($posts->getTaggedFriends())) {
                $user_ids = $posts->getTaggedFriends();
            } else {
                $tagged_friend = $posts->getTaggedFriends();
                if (trim($tagged_friend)) {
                    $user_ids = explode(',', $tagged_friend);
                } else {
                    $user_ids = array();
                }
            }

            if (count($user_ids)) {

                return $user_ids;
            } else {
                return array();
            }
        }, $posts_data);

        $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);
        //$index = array_search("", $post_tagged_user_unique_ids);
        //unset($post_tagged_user_unique_ids[$index]); 
        //$post_tagged_user_unique_ids = array_values($post_tagged_user_unique_ids); 
        //finding the posts data(media, comment) and prepare tha final data.
        if (count($post_ids)) {
            $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->findPostsMedia($post_ids);
            //finding the posts comments.
            $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                    ->findPostsComments($post_ids);         
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getUserId()}";
                }, $comments);
            }
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);

            //finding the comments media.
            $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->findCommentMedia($comment_ids);

            //merege all users array and making unique.
            //   $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

            $users_array = array_unique(array_merge($user_ids_arr, $personal_friends_users, $professional_friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);

            //prepare all the data..
            foreach ($posts_data as $post_data) {
                $post_id = $post_data->getId();
                $post_media_result = array();
                $comment_data = array();
                //finding the media array of current post.
                foreach ($post_media as $current_post_media) {
                    if ($current_post_media->getPostId() == $post_id) {
                        $post_media_id = $current_post_media->getId();
                        $post_media_type = $current_post_media->getType();
                        $post_media_name = $current_post_media->getMediaName();
                        $post_media_status = $current_post_media->getMediaStatus();
                        $post_media_is_featured = $current_post_media->getIsFeatured();
                        $post_media_created_at = $current_post_media->getCreatedDate();
                        $post_image_type = $current_post_media->getImageType();
                        if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            $post_media_link = $current_post_media->getPath();
                            $post_media_thumb = '';
                        } else {
                            $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                            $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
                        }
                        $post_media_result[] = array(
                            'id' => $post_media_id,
                            'media_link' => $post_media_link,
                            'media_thumb_link' => $post_media_thumb,
                            'status' => $post_media_status,
                            'is_featured' => $post_media_is_featured,
                            'create_date' => $post_media_created_at,
                            'image_type' => $post_image_type
                        );
                    }
                }
                $i = 0;
                //finding the comments..
                foreach ($comments as $comment) {
                    $comment_id = $comment->getId();
                    $comment_post_id = $comment->getPostId();
                    if ($comment_post_id == $post_id) {
                        $comment_user_id = $comment->getUserId();
                        $comment_user_profile_type = $comment->getProfileType();
                        //code for user active profile check                        
                        // $comment_user_info = $user_service->UserObjectService($comment_user_id);
                        $comment_media_result = array();
                        foreach ($comments_media as $comment_media_data) {
                            if ($comment_media_data->getCommentId() == $comment_id) {
                                $comment_media_id = $comment_media_data->getId();
                                $comment_media_type = $comment_media_data->getType();
                                $comment_media_name = $comment_media_data->getMediaName();
                                $comment_media_status = $comment_media_data->getIsActive();
                                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                                $comment_media_created_at = $comment_media_data->getCreatedAt();
                                $comment_image_type = $comment_media_data->getImageType();
                                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                    $comment_media_link = $comment_media_data->getPath();
                                    $comment_media_thumb = '';
                                } else {
                                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                                }

                                $comment_media_result[] = array(
                                    'id' => $comment_media_id,
                                    'media_link' => $comment_media_link,
                                    'media_thumb_link' => $comment_media_thumb,
                                    'status' => $comment_media_status,
                                    'is_featured' => $comment_media_is_featured,
                                    'create_date' => $comment_media_created_at,
                                    'image_type' => $comment_image_type
                                );
                            }
                        }

                        $current_rate = 0;
                        $is_rated = false;
                        foreach ($comment->getRate() as $rate) {
                            if ($rate->getUserId() == $current_user_id) {
                                $current_rate = $rate->getRate();
                                $is_rated = true;
                                break;
                            }
                        }

                        if ($i < $this->post_comment_limit) { //getting only 4 comments
                            $comment_data[] = array(
                                'id' => $comment_id,
                                'post_id' => $comment_post_id,
                                'comment_text' => $comment->getCommentText(),
                                'user_id' => $comment_user_id,
                                'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                                'status' => $comment->getIsActive(),
                                'create_date' => $comment->getCreatedAt(),
                                'comment_media_info' => $comment_media_result,
                                'avg_rate' => round($comment->getAvgRating(), 1),
                                'no_of_votes' => (int) $comment->getVoteCount(),
                                'current_user_rate' => $current_rate,
                                'is_rated' => $is_rated
                            );
                        }
                        $i++;
                    }
                }
                $comment_count = $i;
                //comment code finish.
                $sender_id = $post_data->getUserId();
                $receiver_id = $post_data->getToId();
                $user_info = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
                $reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();

                // checking freind ship type between user and his friend
                $friendship_type = 0;
                //cheking for personal friendship
                $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                        ->checkPersonalFriendShip($sender_id, $receiver_id);
                if ($personal_friend_check) {
                    $friendship_type = 1;
                }
                //cheking for professional friendship
                $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                        ->checkProfessionalFriendShip($sender_id, $receiver_id);
                if ($professional_friend_check) {
                    $friendship_type = 2;
                }
                // both type of friends(personal and professional)
                if ($personal_friend_check && $professional_friend_check) {
                    $friendship_type = 3;
                }

                if (is_array($post_data->getTaggedFriends())) {
                    $tagged_user_ids = $post_data->getTaggedFriends();
                } else {
                    $tagged_friend = $post_data->getTaggedFriends();
                    if (trim($tagged_friend)) {
                        $tagged_user_ids = explode(',', $tagged_friend);
                    } else {
                        $tagged_user_ids = array();
                    }
                }

                $tagged_friends_info = array();
                if (count($tagged_user_ids)) {
                    foreach ($tagged_user_ids as $tagged_user_id) {
                        if (array_key_exists($tagged_user_id,$users_object_array))
                        {
                            $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
                        }
                        
                    }
                }
//                //find current user rating
                $current_rate = 0;
                $is_rated = false;
                foreach ($post_data->getRate() as $rate) {
                    if ($rate->getUserId() == $current_user_id) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }

                $content_share = $post_data->getContentShare();
                if(is_array($content_share)){
                    $content_share = (count($content_share) == 0) ? null : $content_share;
                }
                $post_info = array(
                    'id' => $post_data->getId(),
                    'user_id' => $sender_id,
                    'to_id' => $receiver_id,
                    'title' => $post_data->getTitle(),
                    'description' => $post_data->getDescription(),
                    'link_type' => $post_data->getLinkType(),
                    'is_active' => $post_data->getIsActive(),
                    'privacy_setting' => $post_data->getPrivacySetting(),
                    'created_at' => $post_data->getCreatedDate(),
                    'user_info' => $user_info,
                    'receiver_user_info' => $reciver_user_info,
                    'media_info' => $post_media_result,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'tagged_friends_info' => $tagged_friends_info,
                    'avg_rate' => round($post_data->getAvgRating(), 1),
                    'no_of_votes' => (int) $post_data->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'friendship_type' => $friendship_type,
                    'customer_voting' => $post_data->getCustomerVoting(),
                    'share_type' => $post_data->getShareType(),
                    'store_voting_avg' => $post_data->getStoreVotingAvg(),
                    'store_voting_count' => $post_data->getStoreVotingCount(),
                    'transaction_id' => $post_data->getTransactionId(),
                    'invoice_id' => $post_data->getInvoiceId(),
                    'content_share'=> $content_share,
                    'object_type'=> $post_data->getShareObjectType(),
                    'object_id'=> $post_data->getShareObjectId(),
                    
                );
                if($post_data->getShareType() == 'TXN') {
                    $info = $post_data->getInfo();
                    $store_info = array();
                    $store_id = isset($info['store_id']) ? $info['store_id'] : 0;
                    $user_service = $this->get('user_object.service');
                    $store_info  = $user_service->getStoreObjectService($store_id);
                    $post_info['store_info'] = $store_info;
                }
                //save info in post array
                $post [] = $post_info;
            }
        }
        $data['post'] = $post;
        $data['count'] = $post_data_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }

    /**
     * Finding list of post with comments for dashboard for a user wall
     * @param request object	
     * @return json string
     */
    /*
      public function postGetdashboardwallfeedsAction(Request $request) {

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

      $required_parameter = array('user_id', 'friend_id');
      $data = array();
      $user_ids_arr_final = array();
      $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
      $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);

      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $object_info);
      if ($chk_error) {
      return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
      }

      $userManager = $this->getUserManager();
      $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

      // get entity manager object
      $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

      $em_user = $this->getDoctrine()->getManager();

      //get entity manager object
      $em = $this->getDoctrine()->getManager();
      $user_id = $object_info->user_id;
      $friend_id = $object_info->friend_id;

      $post = array();
      $post_data = array();
      $post_user_ids_arr = array();
      $user_ids_arr = $friends_users = $following_users = $citizen_writer = array(); //intialize the array
      $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array

      $is_friend = 0;
      $post_data_count = 0;
      if ($user_id == $friend_id) { //checking the user is seeing his wall (user_id == friend_id)
      $own_wall = 1;
      } else { //other user wall.
      $own_wall = 0;
      //cheking the friendship.
      $friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
      ->checkFriendShip($user_id, $friend_id);
      if ($friend_check) {
      $is_friend = 1;
      }
      }

      $user_ids_arr_final = array("{$object_info->friend_id}");

      $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
      if ($own_wall) { //user seeing own his wall
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getMyWallPosts($user_ids_arr_final, array("$user_id"), $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getMyWallCountPosts($user_ids_arr_final)); //post count..
      }
      } else { // user seeing other user (may be friend of current user)
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getOtherUserWallPosts($user_ids_arr_final,array("$friend_id"), $is_friend, $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getOtherUserWallCountPosts($user_ids_arr_final, $is_friend)); //post count..
      }
      }

      //getting the posts ids.
      $post_ids = array_map(function($posts) {
      return "{$posts->getId()}";
      }, $posts_data);

      //getting the posts sender ids.
      $post_sender_user_ids = array_map(function($posts) {
      return "{$posts->getUserId()}";
      }, $posts_data);

      //getting the tagged user ids.
      $post_tagged_user_ids = array_map(function($posts) {

      if( is_array($posts->getTaggedFriends()) ){
      $user_ids = $posts->getTaggedFriends();
      } else {
      $tagged_friend = $posts->getTaggedFriends();
      if(trim($tagged_friend)){
      $user_ids = explode(',',$tagged_friend);
      } else {
      $user_ids = array();
      }
      }

      if(count($user_ids)){

      return $user_ids;

      } else {
      return array();
      }
      }, $posts_data);

      $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);
      //$index = array_search("", $post_tagged_user_unique_ids);
      //unset($post_tagged_user_unique_ids[$index]);
      //$post_tagged_user_unique_ids = array_values($post_tagged_user_unique_ids);

      //finding the posts data(media, comment)
      if (count($post_ids)) {
      $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
      ->findPostsMedia($post_ids);
      //finding the posts comments.
      $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
      ->findPostsComments($post_ids);
      $comments = array_reverse($comments);
      if (count($comments)) {
      $comment_user_ids = array_map(function($comment_data) {
      return "{$comment_data->getUserId()}";
      }, $comments);
      }
      //comments ids
      $comment_ids = array_map(function($comment_data) {
      return "{$comment_data->getId()}";
      }, $comments);

      //finding the comments media.
      $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
      ->findCommentMedia($comment_ids);

      //merege all users array and making unique.
      $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

      //find user object service..
      $user_service = $this->get('user_object.service');
      //get user profile and cover images..
      $users_object_array = $user_service->MultipleUserObjectService($users_array);

      //prepare all the data..
      foreach ($posts_data as $post_data) {
      $post_id = $post_data->getId();
      $post_media_result = array();
      $comment_data = array();
      //finding the media array of current post.
      foreach ($post_media as $current_post_media) {
      if ($current_post_media->getPostId() == $post_id) {
      $post_media_id = $current_post_media->getId();
      $post_media_type = $current_post_media->getType();
      $post_media_name = $current_post_media->getMediaName();
      $post_media_status = $current_post_media->getMediaStatus();
      $post_media_is_featured = $current_post_media->getIsFeatured();
      $post_media_created_at = $current_post_media->getCreatedDate();
      $post_image_type = $current_post_media->getImageType();
      if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
      $post_media_link = $current_post_media->getPath();
      $post_media_thumb = '';
      } else {
      $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
      $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
      }
      $post_media_result[] = array(
      'id' => $post_media_id,
      'media_link' => $post_media_link,
      'media_thumb_link' => $post_media_thumb,
      'status' => $post_media_status,
      'is_featured' => $post_media_is_featured,
      'create_date' => $post_media_created_at,
      'image_type' =>$post_image_type
      );
      }
      }
      $i = 0;
      //finding the comments..
      foreach ($comments as $comment) {
      $comment_id      = $comment->getId();
      $comment_post_id = $comment->getPostId();
      if ($comment_post_id == $post_id ) {
      $comment_user_id = $comment->getUserId();
      $comment_user_profile_type = $comment->getProfileType();
      //code for user active profile check
      // $comment_user_info = $user_service->UserObjectService($comment_user_id);
      $comment_media_result = array();
      foreach ($comments_media as $comment_media_data) {
      if ($comment_media_data->getCommentId() == $comment_id) {
      $comment_media_id = $comment_media_data->getId();
      $comment_media_type = $comment_media_data->getType();
      $comment_media_name = $comment_media_data->getMediaName();
      $comment_media_status = $comment_media_data->getIsActive();
      $comment_media_is_featured = $comment_media_data->getIsFeatured();
      $comment_media_created_at = $comment_media_data->getCreatedAt();
      $comment_image_type = $comment_media_data->getImageType();
      if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
      $comment_media_link = $comment_media_data->getPath();
      $comment_media_thumb = '';
      } else {
      $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
      $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
      }

      $comment_media_result[] = array(
      'id' => $comment_media_id,
      'media_link' => $comment_media_link,
      'media_thumb_link' => $comment_media_thumb,
      'status' => $comment_media_status,
      'is_featured' => $comment_media_is_featured,
      'create_date' => $comment_media_created_at,
      'image_type' =>$comment_image_type
      );
      }
      }
      <<<<<<< HEAD

      /** fetch rating of current user * */
    /* $current_rate = 0;
      =======
      >>>>>>> 0821447fb4faf80360f0942110b42dd1fde861ce
      /** fetch rating of current user * */
    /*    $current_rate = 0;
      $is_rated = false;
      $rate_data_obj = $post_data->getRate();
      if(count($rate_data_obj) > 0) {
      foreach($rate_data_obj as $rate) {
      if($rate->getUserId() == $user_id ) {
      $current_rate = $rate->getRate();
      $is_rated = true;
      break;
      }
      }
      }

      $post [] = array(
      'id' => $post_data->getId(),
      'user_id' => $sender_id,
      'to_id' => $receiver_id,
      'title' => $post_data->getTitle(),
      'description' => $post_data->getDescription(),
      'link_type' => $post_data->getLinkType(),
      'is_active' => $post_data->getIsActive(),
      'privacy_setting' => $post_data->getPrivacySetting(),
      'created_at' => $post_data->getCreatedDate(),
      'avg_rate'=>round($post_data->getAvgRating(),1),
      'no_of_votes' => (int) $post_data->getVoteCount(),
      'current_user_rate'=>$current_rate,
      'is_rated' =>$is_rated,
      'user_info' => $user_info,
      'receiver_user_info' => $reciver_user_info,
      'media_info' => $post_media_result,
      'comments' => array_reverse($comment_data),
      'comment_count' => $comment_count,
      'tagged_friends_info' => $tagged_friends_info
      );
      }
      }
      $data['post'] = $post;
      $data['count'] = $post_data_count;
      $final_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
      echo json_encode($final_data);
      exit();
      }
     */

    /**
     * Finding list of post with comments for dashboard for a user wall (personal freind, professional friend and third person)
     * @param request object	
     * @return json string
     */
    /*
      public function postGetdashboardwallfeedsAction(Request $request) {
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

      $required_parameter = array('user_id', 'friend_id');
      $data = array();
      $user_ids_arr_final = array();
      $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
      $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);

      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $object_info);
      if ($chk_error) {
      return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
      }

      $userManager = $this->getUserManager();
      $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

      // get entity manager object
      $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

      $em_user = $this->getDoctrine()->getManager();

      //get entity manager object
      $em = $this->getDoctrine()->getManager();
      $user_id = $object_info->user_id;
      $friend_id = $object_info->friend_id;

      $post = array();
      $post_data = array();
      $post_user_ids_arr = array();
      $user_ids_arr = $friends_users = $following_users = $citizen_writer = array(); //intialize the array
      $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array

      $is_friend = 0;
      $ispersonal_friend = 0;
      $isprofessional_friend = 0;
      $post_data_count = 0;
      if ($user_id == $friend_id) { //checking the user is seeing his wall (user_id == friend_id)
      $own_wall = 1;
      } else { //other user wall.
      $own_wall = 0;
      //cheking the personal friendship.
      $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
      ->checkPersonalFriendShip($user_id, $friend_id);
      if ($personal_friend_check) {
      $ispersonal_friend = 1;
      }
      //cheking for professional friendship
      $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
      ->checkProfessionalFriendShip($user_id, $friend_id);
      if ($professional_friend_check) {
      $isprofessional_friend = 1;
      }

      }

      $user_ids_arr_final = array("{$object_info->friend_id}");

      $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
      if ($own_wall) { //user seeing own his wall
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getMyWallPosts($user_ids_arr_final, array("$user_id"), $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getMyWallCountPosts($user_ids_arr_final)); //post count..
      }
      } else { // user seeing other user (may be friend of current user)

      if($ispersonal_friend){
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getPersonalFriendWallPosts($user_ids_arr_final,array("$friend_id"), $ispersonal_friend, $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getPersonalFriendWallCountPosts($user_ids_arr_final, $ispersonal_friend)); //post count..
      }
      }elseif($isprofessional_friend){
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getProfessionalFriendWallPosts($user_ids_arr_final,array("$friend_id"), $isprofessional_friend, $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getProfessionalFriendWallCountPosts($user_ids_arr_final, $isprofessional_friend)); //post count..
      }
      }else {
      $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getOtherUserWallPosts ($user_ids_arr_final,array("$friend_id"), $limit, $offset);
      if (count($posts_data)) {
      $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
      ->getOtherUserWallCountPosts($user_ids_arr_final)); //post count..

      }
      }
      }

      //getting the posts ids.
      $post_ids = array_map(function($posts) {
      return "{$posts->getId()}";
      }, $posts_data);

      //getting the posts sender ids.
      $post_sender_user_ids = array_map(function($posts) {
      return "{$posts->getUserId()}";
      }, $posts_data);

      //getting the tagged user ids.
      $post_tagged_user_ids = array_map(function($posts) {

      if( is_array($posts->getTaggedFriends()) ){
      $user_ids = $posts->getTaggedFriends();
      } else {
      $tagged_friend = $posts->getTaggedFriends();
      if(trim($tagged_friend)){
      $user_ids = explode(',',$tagged_friend);
      } else {
      $user_ids = array();
      }
      }

      if(count($user_ids)){

      return $user_ids;

      } else {
      return array();
      }
      }, $posts_data);

      $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);
      //$index = array_search("", $post_tagged_user_unique_ids);
      //unset($post_tagged_user_unique_ids[$index]);
      //$post_tagged_user_unique_ids = array_values($post_tagged_user_unique_ids);

      //finding the posts data(media, comment)
      if (count($post_ids)) {
      $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
      ->findPostsMedia($post_ids);
      //finding the posts comments.
      $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
      ->findPostsComments($post_ids);
      $comments = array_reverse($comments);
      if (count($comments)) {
      $comment_user_ids = array_map(function($comment_data) {
      return "{$comment_data->getUserId()}";
      }, $comments);
      }
      //comments ids
      $comment_ids = array_map(function($comment_data) {
      return "{$comment_data->getId()}";
      }, $comments);

      //finding the comments media.
      $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
      ->findCommentMedia($comment_ids);

      //merege all users array and making unique.
      //  $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

      $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

      //find user object service..
      $user_service = $this->get('user_object.service');
      //get user profile and cover images..
      $users_object_array = $user_service->MultipleUserObjectService($users_array);

      //prepare all the data..
      foreach ($posts_data as $post_data) {
      $post_id = $post_data->getId();
      $post_media_result = array();
      $comment_data = array();
      //finding the media array of current post.
      foreach ($post_media as $current_post_media) {
      if ($current_post_media->getPostId() == $post_id) {
      $post_media_id = $current_post_media->getId();
      $post_media_type = $current_post_media->getType();
      $post_media_name = $current_post_media->getMediaName();
      $post_media_status = $current_post_media->getMediaStatus();
      $post_media_is_featured = $current_post_media->getIsFeatured();
      $post_media_created_at = $current_post_media->getCreatedDate();
      $post_image_type = $current_post_media->getImageType();
      if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
      $post_media_link = $current_post_media->getPath();
      $post_media_thumb = '';
      } else {
      $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
      $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
      }
      $post_media_result[] = array(
      'id' => $post_media_id,
      'media_link' => $post_media_link,
      'media_thumb_link' => $post_media_thumb,
      'status' => $post_media_status,
      'is_featured' => $post_media_is_featured,
      'create_date' => $post_media_created_at,
      'image_type' =>$post_image_type
      );
      }
      }
      $i = 0;
      //finding the comments..
      foreach ($comments as $comment) {
      $comment_id      = $comment->getId();
      $comment_post_id = $comment->getPostId();
      if ($comment_post_id == $post_id ) {
      $comment_user_id = $comment->getUserId();
      $comment_user_profile_type = $comment->getProfileType();
      //code for user active profile check
      // $comment_user_info = $user_service->UserObjectService($comment_user_id);
      $comment_media_result = array();
      foreach ($comments_media as $comment_media_data) {
      if ($comment_media_data->getCommentId() == $comment_id) {
      $comment_media_id = $comment_media_data->getId();
      $comment_media_type = $comment_media_data->getType();
      $comment_media_name = $comment_media_data->getMediaName();
      $comment_media_status = $comment_media_data->getIsActive();
      $comment_media_is_featured = $comment_media_data->getIsFeatured();
      $comment_media_created_at = $comment_media_data->getCreatedAt();
      $comment_image_type = $comment_media_data->getImageType();
      if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
      $comment_media_link = $comment_media_data->getPath();
      $comment_media_thumb = '';
      } else {
      $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
      $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
      }

      $comment_media_result[] = array(
      'id' => $comment_media_id,
      'media_link' => $comment_media_link,
      'media_thumb_link' => $comment_media_thumb,
      'status' => $comment_media_status,
      'is_featured' => $comment_media_is_featured,
      'create_date' => $comment_media_created_at,
      'image_type' =>$comment_image_type
      );
      }
      }

      // fetch rating of current user
      $current_rate = 0;
      <<<<<<< HEAD
      >>>>>>> conflict resolved
      =======
      >>>>>>> 0821447fb4faf80360f0942110b42dd1fde861ce
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

      if ($i < $this->post_comment_limit) { //getting only 4 comments
      $comment_data[] = array(
      'id' => $comment_id,
      'post_id' => $comment_post_id,
      'comment_text' => $comment->getCommentText(),
      'user_id' => $comment_user_id,
      'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
      'status' => $comment->getIsActive(),
      'create_date' => $comment->getCreatedAt(),
      'comment_media_info' => $comment_media_result,
      'avg_rate'=>round($comment->getAvgRating(),1),
      'no_of_votes' => (int) $comment->getVoteCount(),
      'current_user_rate'=>$current_rate,
      'is_rated' =>$is_rated
      );
      }
      $i++;
      }
      }
      $comment_count = $i;
      //comment code finish.

      if(is_array($post_data->getTaggedFriends()) ){
      $tagged_user_ids = $post_data->getTaggedFriends();
      } else {
      $tagged_friend = $post_data->getTaggedFriends();
      if(trim($tagged_friend)){
      $tagged_user_ids = explode(',',$tagged_friend);
      } else {
      $tagged_user_ids = array();
      }
      }

      $tagged_friends_info = array();
      if($tagged_user_ids){
      foreach($tagged_user_ids as $tagged_user_id){
      $tagged_friends_info[] =  isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
      }
      } else {
      $tagged_friends_info = array();
      }


      $sender_id   = $post_data->getUserId();
      $receiver_id = $post_data->getToId();
      $user_info         = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
      $reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();

      // fetch rating of current user
      /*    $current_rate = 0;
      $is_rated = false;
      $rate_data_obj = $post_data->getRate();
      if(count($rate_data_obj) > 0) {
      foreach($rate_data_obj as $rate) {
      if($rate->getUserId() == $user_id ) {
      $current_rate = $rate->getRate();
      $is_rated = true;
      break;
      }
      }
      }

      $post [] = array(
      'id' => $post_data->getId(),
      'user_id' => $sender_id,
      'to_id' => $receiver_id,
      'title' => $post_data->getTitle(),
      'description' => $post_data->getDescription(),
      'link_type' => $post_data->getLinkType(),
      'is_active' => $post_data->getIsActive(),
      'privacy_setting' => $post_data->getPrivacySetting(),
      'created_at' => $post_data->getCreatedDate(),
      'avg_rate'=>round($post_data->getAvgRating(),1),
      'no_of_votes' => (int) $post_data->getVoteCount(),
      'current_user_rate'=>$current_rate,
      'is_rated' =>$is_rated,
      'user_info' => $user_info,
      'receiver_user_info' => $reciver_user_info,
      'media_info' => $post_media_result,
      'comments' => array_reverse($comment_data),
      'comment_count' => $comment_count,
      'tagged_friends_info' => $tagged_friends_info
      );
      }
      }
      $data['post'] = $post;
      $data['count'] = $post_data_count;
      $final_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
      echo json_encode($final_data);
      exit();
      }
     */

    /**
     * Finding list of post with comments for dashboard for a user wall (personal freind, professional friend and third person)
     * @param request object	
     * @return json string
     */
    public function postGetdashboardwallfeedsAction(Request $request) {
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

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        $user_ids_arr_final = array();
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : $this->post_comment_limit);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : $this->post_comment_offset);
        $last_post_id = (isset($de_serialize['last_post_id']) ? $de_serialize['last_post_id'] : '');   
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $em_user = $this->getDoctrine()->getManager();

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $user_id = $object_info->user_id;
        $friend_id = $object_info->friend_id;

        $post = array();
        $post_data = array();
        $post_user_ids_arr = array();
        $user_ids_arr = $friends_users = $following_users = $citizen_writer = array(); //intialize the array
        $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array

        $is_friend = 0;
        $ispersonal_friend = 0;
        $isprofessional_friend = 0;
        $post_data_count = 0;
        if ($user_id == $friend_id) { //checking the user is seeing his wall (user_id == friend_id)
            $own_wall = 1;
        } else { //other user wall.
            $own_wall = 0;
            //cheking the personal friendship.
            $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->checkPersonalFriendShip($user_id, $friend_id);
            if ($personal_friend_check) {
                $ispersonal_friend = 1;
            }
            //cheking for professional friendship
            $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->checkProfessionalFriendShip($user_id, $friend_id);
            if ($professional_friend_check) {
                $isprofessional_friend = 1;
            }
        }

        $user_ids_arr_final = array("{$object_info->friend_id}");

        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        if ($own_wall) { //user seeing own his wall
            $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->getMyWallPosts($user_ids_arr_final, array("$user_id"), $limit, $offset, $last_post_id);
            if (count($posts_data)) {
                $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
                                ->getMyWallCountPosts($user_ids_arr_final)); //post count..
            }
        } else { // user seeing other user (may be friend of current user)
            if ($ispersonal_friend) {
                $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->getPersonalFriendWallPosts($user_ids_arr_final, array("$friend_id"), $ispersonal_friend, $limit, $offset, $last_post_id);
                if (count($posts_data)) {
                    $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
                                    ->getPersonalFriendWallCountPosts($user_ids_arr_final, $ispersonal_friend)); //post count..  
                }
            } elseif ($isprofessional_friend) {
                $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->getProfessionalFriendWallPosts($user_ids_arr_final, array("$friend_id"), $isprofessional_friend, $limit, $offset, $last_post_id);
                if (count($posts_data)) {
                    $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
                                    ->getProfessionalFriendWallCountPosts($user_ids_arr_final, $isprofessional_friend)); //post count..  
                }
            } else {
                $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->getOtherUserWallPosts($user_ids_arr_final, array("$friend_id"), $limit, $offset, $last_post_id);
                if (count($posts_data)) {
                    $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
                                    ->getOtherUserWallCountPosts($user_ids_arr_final)); //post count..
                }
            }
        }

        //getting the posts ids.
        $post_ids = array_map(function($posts) {
            return "{$posts->getId()}";
        }, $posts_data);

        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($posts) {
            return "{$posts->getUserId()}";
        }, $posts_data);

        //getting the tagged user ids.
        $post_tagged_user_ids = array_map(function($posts) {

            if (is_array($posts->getTaggedFriends())) {
                $user_ids = $posts->getTaggedFriends();
            } else {
                $tagged_friend = $posts->getTaggedFriends();
                if (trim($tagged_friend)) {
                    $user_ids = explode(',', $tagged_friend);
                } else {
                    $user_ids = array();
                }
            }

            if (count($user_ids)) {

                return $user_ids;
            } else {
                return array();
            }
        }, $posts_data);

        $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);
        //$index = array_search("", $post_tagged_user_unique_ids);
        //unset($post_tagged_user_unique_ids[$index]); 
        //$post_tagged_user_unique_ids = array_values($post_tagged_user_unique_ids); 
        //finding the posts data(media, comment)
        if (count($post_ids)) {
            $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->findPostsMedia($post_ids);
            //finding the posts comments.
            $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                    ->findPostsComments($post_ids);
            //$comments = array_reverse($comments);
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getUserId()}";
                }, $comments);
            }
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);

            //finding the comments media.
            $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->findCommentMedia($comment_ids);

            //merege all users array and making unique.
            //  $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

            $users_array = array_unique(array_merge($user_ids_arr, $friends_users, $following_users, $citizen_writer, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);

            //prepare all the data..
            foreach ($posts_data as $post_data) {
                $post_id = $post_data->getId();
                $post_media_result = array();
                $comment_data = array();
                //finding the media array of current post.
                foreach ($post_media as $current_post_media) {
                    if ($current_post_media->getPostId() == $post_id) {
                        $post_media_id = $current_post_media->getId();
                        $post_media_type = $current_post_media->getType();
                        $post_media_name = $current_post_media->getMediaName();
                        $post_media_status = $current_post_media->getMediaStatus();
                        $post_media_is_featured = $current_post_media->getIsFeatured();
                        $post_media_created_at = $current_post_media->getCreatedDate();
                        $post_image_type = $current_post_media->getImageType();
                        if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            $post_media_link = $current_post_media->getPath();
                            $post_media_thumb = '';
                        } else {
                            $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                            $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
                        }
                        $post_media_result[] = array(
                            'id' => $post_media_id,
                            'media_link' => $post_media_link,
                            'media_thumb_link' => $post_media_thumb,
                            'status' => $post_media_status,
                            'is_featured' => $post_media_is_featured,
                            'create_date' => $post_media_created_at,
                            'image_type' => $post_image_type
                        );
                    }
                }
                $i = 0;
                //finding the comments..
                foreach ($comments as $comment) {
                    $comment_id = $comment->getId();
                    $comment_post_id = $comment->getPostId();
                    if ($comment_post_id == $post_id) {
                        $comment_user_id = $comment->getUserId();
                        $comment_user_profile_type = $comment->getProfileType();
                        //code for user active profile check                        
                        // $comment_user_info = $user_service->UserObjectService($comment_user_id);
                        $comment_media_result = array();
                        foreach ($comments_media as $comment_media_data) {
                            if ($comment_media_data->getCommentId() == $comment_id) {
                                $comment_media_id = $comment_media_data->getId();
                                $comment_media_type = $comment_media_data->getType();
                                $comment_media_name = $comment_media_data->getMediaName();
                                $comment_media_status = $comment_media_data->getIsActive();
                                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                                $comment_media_created_at = $comment_media_data->getCreatedAt();
                                $comment_image_type = $comment_media_data->getImageType();
                                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                    $comment_media_link = $comment_media_data->getPath();
                                    $comment_media_thumb = '';
                                } else {
                                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                    $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                                }

                                $comment_media_result[] = array(
                                    'id' => $comment_media_id,
                                    'media_link' => $comment_media_link,
                                    'media_thumb_link' => $comment_media_thumb,
                                    'status' => $comment_media_status,
                                    'is_featured' => $comment_media_is_featured,
                                    'create_date' => $comment_media_created_at,
                                    'image_type' => $comment_image_type
                                );
                            }
                        }

                        /** fetch rating of current comment* */
                        $current_rate = 0;
                        $is_rated = false;
                        $rate_data_obj = $comment->getRate();
                        if (count($rate_data_obj) > 0) {
                            foreach ($rate_data_obj as $rate) {
                                if ($rate->getUserId() == $user_id) {
                                    $current_rate = $rate->getRate();
                                    $is_rated = true;
                                    break;
                                }
                            }
                        }

                        if ($i < $this->post_comment_limit) { //getting only 4 comments
                            $comment_data[] = array(
                                'id' => $comment_id,
                                'post_id' => $comment_post_id,
                                'comment_text' => $comment->getCommentText(),
                                'user_id' => $comment_user_id,
                                'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                                'status' => $comment->getIsActive(),
                                'create_date' => $comment->getCreatedAt(),
                                'comment_media_info' => $comment_media_result,
                                'avg_rate' => round($comment->getAvgRating(), 1),
                                'no_of_votes' => (int) $comment->getVoteCount(),
                                'current_user_rate' => $current_rate,
                                'is_rated' => $is_rated
                            );
                        }
                        $i++;
                    }
                }
                $comment_count = $i;
                //comment code finish.

                if (is_array($post_data->getTaggedFriends())) {
                    $tagged_user_ids = $post_data->getTaggedFriends();
                } else {
                    $tagged_friend = $post_data->getTaggedFriends();
                    if (trim($tagged_friend)) {
                        $tagged_user_ids = explode(',', $tagged_friend);
                    } else {
                        $tagged_user_ids = array();
                    }
                }

                $tagged_friends_info = array();
                if ($tagged_user_ids) {
                    foreach ($tagged_user_ids as $tagged_user_id) {
                        if (array_key_exists($tagged_user_id,$users_object_array))
                        {
                            $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
                        }
                    }
                } else {
                    $tagged_friends_info = array();
                }


                $sender_id = $post_data->getUserId();
                $receiver_id = $post_data->getToId();
                $user_info = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
                $reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();

                // checking freind ship type between user and his friend
                $friendship_type = 0;
                //cheking for personal friendship
                $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                        ->checkPersonalFriendShip($sender_id, $receiver_id);
                if ($personal_friend_check) {
                    $friendship_type = 1;
                }
                //cheking for professional friendship
                $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                        ->checkProfessionalFriendShip($sender_id, $receiver_id);
                if ($professional_friend_check) {
                    $friendship_type = 2;
                }
                // both type of friends(personal and professional)
                if ($personal_friend_check && $professional_friend_check) {
                    $friendship_type = 3;
                }
                /** fetch rating of current post * */
                $current_rate = 0;
                $is_rated = false;
                $rate_data_obj = $post_data->getRate();
                if (count($rate_data_obj) > 0) {
                    foreach ($rate_data_obj as $rate) {
                        if ($rate->getUserId() == $user_id) {
                            $current_rate = $rate->getRate();
                            $is_rated = true;
                            break;
                        }
                    }
                }

                $post_info = array(
                    'id' => $post_data->getId(),
                    'user_id' => $sender_id,
                    'to_id' => $receiver_id,
                    'title' => $post_data->getTitle(),
                    'description' => $post_data->getDescription(),
                    'link_type' => $post_data->getLinkType(),
                    'is_active' => $post_data->getIsActive(),
                    'privacy_setting' => $post_data->getPrivacySetting(),
                    'created_at' => $post_data->getCreatedDate(),
                    'avg_rate' => round($post_data->getAvgRating(), 1),
                    'no_of_votes' => (int) $post_data->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'user_info' => $user_info,
                    'receiver_user_info' => $reciver_user_info,
                    'media_info' => $post_media_result,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'tagged_friends_info' => $tagged_friends_info,
                    'friendship_type' => $friendship_type,
                    'customer_voting' => $post_data->getCustomerVoting(),
                    'share_type' => $post_data->getShareType(),
                    'store_voting_avg' => $post_data->getStoreVotingAvg(),
                    'store_voting_count' => $post_data->getStoreVotingCount(),
                    'transaction_id' => $post_data->getTransactionId(),
                    'invoice_id' => $post_data->getInvoiceId(),
                    'content_share'=> $post_data->getContentShare(),
                    'object_type'=> $post_data->getShareObjectType(),
                    'object_id'=> $post_data->getShareObjectId(),
                );
                
                if($post_data->getShareType() == 'TXN') {
                    $info = $post_data->getInfo();
                    $store_info = array();
                    $store_id = isset($info['store_id']) ? $info['store_id'] : 0;
                    $user_service = $this->get('user_object.service');
                    $store_info  = $user_service->getStoreObjectService($store_id);
                    $post_info['store_info'] = $store_info;
                }
                
                $post [] = $post_info;
            }
        }
        $data['post'] = $post;
        $data['count'] = $post_data_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }

    /*
     * Used to convert multidimensional array into single dimension
     */

    function array_flatten($array) {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[] = $value;
            }
        }

        return array_unique($result);
    }

    /**
     * Save comment notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $msgtype, $msg) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId(0);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * Finding post detail of post with comments for dashboard
     * @param request object
     * @return json string
     */
    public function postGetdashboardfeeddetailsAction(Request $request) {
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

        $required_parameter = array('user_id', 'post_id');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $current_user_id = $object_info->user_id;
        $post_id = $object_info->post_id;
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
//        $user_info = $em->getRepository('UserManagerSonataUserBundle:User')
//                ->findBy(array('id' => $object_info->friend_id));
//        $user_country_code = ''; //intialize the country code by blank.
//        if ($user_info) {
//            $user_country_code = $user_info[0]->getCountry();
//        }

        $post = array();
        $post_user_ids_arr = array();
        $user_ids_arr = $friends_users = $following_users = $citizen_writer = array(); //intialize the array
        $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array
        $user_ids_arr[] = $current_user_id;


        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        if (!$posts_data) {

            $final_data = array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($final_data);
            exit();
        }

        $post_data_count = 0;
        $comment_count = 0;
        $post_data = array();


        //getting the posts ids.
        $post_ids = array("{$posts_data->getId()}");

        //getting the posts sender ids.
        $post_sender_user_ids = array("{$posts_data->getUserId()}");

        $post_tagged_user_ids = array();
        //getting the tagged user ids.
        if (is_array($posts_data->getTaggedFriends())) {
            $post_tagged_user_ids = $posts_data->getTaggedFriends();
        } else {
            $tagged_friend = $posts_data->getTaggedFriends();
            if (trim($tagged_friend)) {
                $post_tagged_user_ids = explode(',', $tagged_friend);
            } else {
                $post_tagged_user_ids = array();
            }
        }

        $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);
        //$index = array_search("", $post_tagged_user_unique_ids);
        //unset($post_tagged_user_unique_ids[$index]); 
        //$post_tagged_user_unique_ids = array_values($post_tagged_user_unique_ids); 
        //finding the posts data(media, comment) and prepare tha final data.
        if (count($post_ids)) {
            $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->findPostsMedia($post_ids);
            //finding the posts comments.
            $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                    ->findPostsComments($post_ids);
            //$comments = array_reverse($comments);
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getUserId()}";
                }, $comments);
            }
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);

            //finding the comments media.
            $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->findCommentMedia($comment_ids);

            //merege all users array and making unique.
            $users_array = array_unique(array_merge($user_ids_arr, $post_sender_user_ids, $comment_user_ids, $post_tagged_user_unique_ids));

            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
            $post_data = $posts_data;
            //prepare all the data..

            $post_id = $post_data->getId();
            $post_media_result = array();
            $comment_data = array();
            //finding the media array of current post.
            foreach ($post_media as $current_post_media) {
                if ($current_post_media->getPostId() == $post_id) {
                    $post_media_id = $current_post_media->getId();
                    $post_media_type = $current_post_media->getType();
                    $post_media_name = $current_post_media->getMediaName();
                    $post_media_status = $current_post_media->getMediaStatus();
                    $post_media_is_featured = $current_post_media->getIsFeatured();
                    $post_media_created_at = $current_post_media->getCreatedDate();
                    $post_image_type = $current_post_media->getImageType();
                    if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                        $post_media_link = $current_post_media->getPath();
                        $post_media_thumb = '';
                    } else {
                        $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                        $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
                    }
                    $post_media_result[] = array(
                        'id' => $post_media_id,
                        'media_link' => $post_media_link,
                        'media_thumb_link' => $post_media_thumb,
                        'status' => $post_media_status,
                        'is_featured' => $post_media_is_featured,
                        'create_date' => $post_media_created_at,
                        'image_type' => $post_image_type
                    );
                }
            }
            $i = 0;
            //finding the comments..
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_post_id = $comment->getPostId();
                if ($comment_post_id == $post_id) {
                    $comment_user_id = $comment->getUserId();
                    $comment_user_profile_type = $comment->getProfileType();
                    //code for user active profile check                        
                    // $comment_user_info = $user_service->UserObjectService($comment_user_id);
                    $comment_media_result = array();
                    foreach ($comments_media as $comment_media_data) {
                        if ($comment_media_data->getCommentId() == $comment_id) {
                            $comment_media_id = $comment_media_data->getId();
                            $comment_media_type = $comment_media_data->getType();
                            $comment_media_name = $comment_media_data->getMediaName();
                            $comment_media_status = $comment_media_data->getIsActive();
                            $comment_media_is_featured = $comment_media_data->getIsFeatured();
                            $comment_media_created_at = $comment_media_data->getCreatedAt();
                            $comment_image_type = $comment_media_data->getImageType();
                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                $comment_media_link = $comment_media_data->getPath();
                                $comment_media_thumb = '';
                            } else {
                                $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                'id' => $comment_media_id,
                                'media_link' => $comment_media_link,
                                'media_thumb_link' => $comment_media_thumb,
                                'status' => $comment_media_status,
                                'is_featured' => $comment_media_is_featured,
                                'create_date' => $comment_media_created_at,
                                'image_type' => $comment_image_type
                            );
                        }
                    }

                    if ($i < $this->post_comment_limit) { //getting only 4 comments
                        $c_current_rate = 0;
                        $c_is_rated = false;
                        $rate_data_obj = $comment->getRate();
                        if (count($rate_data_obj) > 0) {
                            foreach ($rate_data_obj as $rate) {
                                if ($rate->getUserId() == $current_user_id) {
                                    $c_current_rate = $rate->getRate();
                                    $c_is_rated = true;
                                    break;
                                }
                            }
                        }
                        $comment_data[] = array(
                            'id' => $comment_id,
                            'post_id' => $comment_post_id,
                            'comment_text' => $comment->getCommentText(),
                            'user_id' => $comment_user_id,
                            'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                            'status' => $comment->getIsActive(),
                            'create_date' => $comment->getCreatedAt(),
                            'comment_media_info' => $comment_media_result,
                            'avg_rate' => round($comment->getAvgRating(), 1),
                            'no_of_votes' => (int) $comment->getVoteCount(),
                            'current_user_rate' => $c_current_rate,
                            'is_rated' => $c_is_rated,
                        );
                    }
                    $i++;
                }
            }
            $comment_count = $i;
            //comment code finish.
            $sender_id = $post_data->getUserId();
            $receiver_id = $post_data->getToId();
            $user_info = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
            $reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();


            if (is_array($post_data->getTaggedFriends())) {
                $tagged_user_ids = $post_data->getTaggedFriends();
            } else {
                $tagged_friend = $post_data->getTaggedFriends();
                if (trim($tagged_friend)) {
                    $tagged_user_ids = explode(',', $tagged_friend);
                } else {
                    $tagged_user_ids = array();
                }
            }

            $tagged_friends_info = array();
            if (count($tagged_user_ids)) {
                foreach ($tagged_user_ids as $tagged_user_id) {
                    if(isset($users_object_array[$tagged_user_id])){
                        $tagged_friends_info[] = $users_object_array[$tagged_user_id];
                    }
                }
            }

            /** fetch rating of current user * */
            $current_rate = 0;
            $is_rated = false;
            $rate_data_obj = $post_data->getRate();
            if (count($rate_data_obj) > 0) {
                foreach ($rate_data_obj as $rate) {
                    if ($rate->getUserId() == $current_user_id) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
            }

            $post_info = array(
                'id' => $post_data->getId(),
                'user_id' => $sender_id,
                'to_id' => $receiver_id,
                'title' => $post_data->getTitle(),
                'description' => $post_data->getDescription(),
                'link_type' => $post_data->getLinkType(),
                'is_active' => $post_data->getIsActive(),
                'privacy_setting' => $post_data->getPrivacySetting(),
                'created_at' => $post_data->getCreatedDate(),
                'avg_rate' => round($post_data->getAvgRating(), 1),
                'no_of_votes' => $post_data->getVoteCount(),
                'current_user_rate' => $current_rate,
                'is_rated' => $is_rated,
                'user_info' => $user_info,
                'receiver_user_info' => $reciver_user_info,
                'media_info' => $post_media_result,
                'comments' => array_reverse($comment_data),
                'comment_count' => $comment_count,
                'tagged_friends_info' => $tagged_friends_info,
                'customer_voting' => $post_data->getCustomerVoting(),
                'share_type' => $post_data->getShareType(),
                'store_voting_avg' => $post_data->getStoreVotingAvg(),
                'store_voting_count' => $post_data->getStoreVotingCount(),
                'transaction_id' => $post_data->getTransactionId(),
                'invoice_id' => $post_data->getInvoiceId()
            );
            if($post_data->getShareType() == 'TXN') {
                    $info = $post_data->getInfo();
                    $store_info = array();
                    $store_id = isset($info['store_id']) ? $info['store_id'] : 0;
                    $user_service = $this->get('user_object.service');
                    $store_info = $user_service->getStoreObjectService($store_id);
                    $post_info['store_info'] = $store_info;
                }           
            $post [] = $post_info;
            $post_data_count = 1;
        }
        $data['post'] = $post;
        $data['count'] = $post_data_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }

    /**
     * Finding post detail of post with comments for dashboard
     * @param request object
     * @return json string
     */
    public function getpublicpostfeeddetailsAction(Request $request) {
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

        $required_parameter = array('post_id','post_type');
        $data = array();

               
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $post_type = array('user','shop','club');
        
        //check for post type
        if(!in_array($object_info->post_type,$post_type)){
            return array('code' => 100, 'message' => 'INVALID_POST_TYPE', 'data' => $data);
        }
        
        $post_id = $object_info->post_id;
        $post_type = $object_info->post_type;
        
        switch ($post_type) {
            CASE 'user': //user dashboard post detail
                $this->getDashboardPostDetails($post_id);
                break;
            CASE 'shop': //shop dashboard post detail
                $this->getShopPostDetails($post_id);
                break;
            CASE 'club'; //club dashboard post detail
                $this->getClubPostDetails($post_id);
                break;
        }
    }
    
     /**
     * Getting user dashboard post details
     * @param request object
     * @return json string
     */
    public function getDashboardPostDetails($post_id){
        
        $userManager = $this->getUserManager();

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get entity manager object
        $em = $this->getDoctrine()->getManager();


        $post = array();
        $post_user_ids_arr = array();
        $friends_users = $following_users = $citizen_writer = array(); //intialize the array
        $comments_media = $posts_data = $post_media = $comments = $comment_ids = $comment_user_ids = array(); //intialize the array



        $posts_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $posts_data = $posts_dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        if (!$posts_data) {

            $final_data = array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($final_data);
            exit();
        }

        $post_data_count = 0;
        $comment_count = 0;
        $post_data = array();


        //getting the posts ids.
        $post_ids = array("{$posts_data->getId()}");

        //getting the posts sender ids.
        $post_sender_user_id = $posts_data->getUserId();

        $post_tagged_user_ids = array();
        //getting the tagged user ids.
        if (is_array($posts_data->getTaggedFriends())) {
            $post_tagged_user_ids = $posts_data->getTaggedFriends();
        } else {
            $tagged_friend = $posts_data->getTaggedFriends();
            if (trim($tagged_friend)) {
                $post_tagged_user_ids = explode(',', $tagged_friend);
            } else {
                $post_tagged_user_ids = array();
            }
        }

        $post_tagged_user_unique_ids = $this->array_flatten($post_tagged_user_ids);

        if (count($post_ids)) {
            $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->findPostsMedia($post_ids);
            //finding the posts comments.
            $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                    ->findPostsComments($post_ids);
            //$comments = array_reverse($comments);
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getUserId()}";
                }, $comments);
            }
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);

            //finding the comments media.
            $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->findCommentMedia($comment_ids);

            //merege all users array and making unique.
            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService(array($post_sender_user_id));
            
//            $citizenIncomeInfo = $em
//                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
//                        ->getCitizenIncome($post_sender_user_id);
//            $users_object_array[$post_sender_user_id]['citizen_income'] = key_exists('citizenIncome', $citizenIncomeInfo) ? $citizenIncomeInfo['citizenIncome'] : 0;
            
            $citizenIncomeInfo = $this->getuserincomedetails($post_sender_user_id);
            $users_object_array[$post_sender_user_id]['citizen_income'] = isset($citizenIncomeInfo['tot_income']) ? $citizenIncomeInfo['tot_income'] : 0;
      
            
            $post_data = $posts_data;
            //prepare all the data..

            $post_id = $post_data->getId();
            $post_media_result = array();
            $comment_data = array();
            //finding the media array of current post.
            foreach ($post_media as $current_post_media) {
                if ($current_post_media->getPostId() == $post_id) {
                    $post_media_id = $current_post_media->getId();
                    $post_media_type = $current_post_media->getType();
                    $post_media_name = $current_post_media->getMediaName();
                    $post_media_status = $current_post_media->getMediaStatus();
                    $post_media_is_featured = $current_post_media->getIsFeatured();
                    $post_media_created_at = $current_post_media->getCreatedDate();
                    $post_image_type = $current_post_media->getImageType();
                    if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                        $post_media_link = $current_post_media->getPath();
                        $post_media_thumb = '';
                    } else {
                        $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                        $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
                    }
                    $post_media_result[] = array(
                        'id' => $post_media_id,
                        'media_link' => $post_media_link,
                        'media_thumb_link' => $post_media_thumb,
                        'status' => $post_media_status,
                        'is_featured' => $post_media_is_featured,
                        'create_date' => $post_media_created_at,
                        'image_type' => $post_image_type
                    );
                }
            }
            $i = 0;
            //finding the comments..
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_post_id = $comment->getPostId();
                if ($comment_post_id == $post_id) {
                    $comment_user_id = $comment->getUserId();
                    $comment_user_profile_type = $comment->getProfileType();
                    //code for user active profile check                        
                    // $comment_user_info = $user_service->UserObjectService($comment_user_id);
                    $comment_media_result = array();
                    foreach ($comments_media as $comment_media_data) {
                        if ($comment_media_data->getCommentId() == $comment_id) {
                            $comment_media_id = $comment_media_data->getId();
                            $comment_media_type = $comment_media_data->getType();
                            $comment_media_name = $comment_media_data->getMediaName();
                            $comment_media_status = $comment_media_data->getIsActive();
                            $comment_media_is_featured = $comment_media_data->getIsFeatured();
                            $comment_media_created_at = $comment_media_data->getCreatedAt();
                            $comment_image_type = $comment_media_data->getImageType();
                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                $comment_media_link = $comment_media_data->getPath();
                                $comment_media_thumb = '';
                            } else {
                                $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                'id' => $comment_media_id,
                                'media_link' => $comment_media_link,
                                'media_thumb_link' => $comment_media_thumb,
                                'status' => $comment_media_status,
                                'is_featured' => $comment_media_is_featured,
                                'create_date' => $comment_media_created_at,
                                'image_type' => $comment_image_type
                            );
                        }
                    }

                    if ($i < $this->post_comment_limit) { //getting only 4 comments

                        $comment_data[] = array(
                            'id' => $comment_id,
                            'post_id' => $comment_post_id,
                            'comment_text' => $comment->getCommentText(),
                            'user_id' => $comment_user_id,
                            'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                            'status' => $comment->getIsActive(),
                            'create_date' => $comment->getCreatedAt(),
                            'comment_media_info' => $comment_media_result,
                            'avg_rate' => round($comment->getAvgRating(), 1),
                            'no_of_votes' => (int) $comment->getVoteCount(),
                         );
                    }
                    $i++;
                }
            }
            $comment_count = $i;
            //comment code finish.
            $sender_id = $post_data->getUserId();
            $receiver_id = $post_data->getToId();
            $user_info = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();

            if (is_array($post_data->getTaggedFriends())) {
                $tagged_user_ids = $post_data->getTaggedFriends();
            } else {
                $tagged_friend = $post_data->getTaggedFriends();
                if (trim($tagged_friend)) {
                    $tagged_user_ids = explode(',', $tagged_friend);
                } else {
                    $tagged_user_ids = array();
                }
            }

            $tagged_friends_info = array();
            if (count($tagged_user_ids)) {
                foreach ($tagged_user_ids as $tagged_user_id) {
                    $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
                }
            }

            $post_info = array(
                'id' => $post_data->getId(),
                'user_id' => $sender_id,
                'to_id' => $receiver_id,
                'title' => $post_data->getTitle(),
                'description' => $post_data->getDescription(),
                'link_type' => $post_data->getLinkType(),
                'is_active' => $post_data->getIsActive(),
                'privacy_setting' => $post_data->getPrivacySetting(),
                'created_at' => $post_data->getCreatedDate(),
                'avg_rate' => round($post_data->getAvgRating(), 1),
                'no_of_votes' => $post_data->getVoteCount(),
                'user_info' => $user_info,
                'media_info' => $post_media_result,
                'comments' => array_reverse($comment_data),
                'comment_count' => $comment_count,
                'tagged_friends_info' => $tagged_friends_info,
                'customer_voting' => $post_data->getCustomerVoting(),
                'share_type' => $post_data->getShareType(),
                'store_voting_avg' => $post_data->getStoreVotingAvg(),
                'store_voting_count' => $post_data->getStoreVotingCount(),
                'transaction_id' => $post_data->getTransactionId(),
                'invoice_id' => $post_data->getInvoiceId()
            );
            
            if($post_data->getShareType() == 'TXN') {
                    $info = $post_data->getInfo();
                    $store_info = array();
                    $store_id = isset($info['store_id']) ? $info['store_id'] : 0;
                    $user_service = $this->get('user_object.service');
                    $store_info  = $user_service->getStoreObjectService($store_id);
                    $post_info['store_info'] = $store_info;
            }
            
            $post [] = $post_info;
            $post_data_count = 1;
        }
        $data['post'] = $post;
        $data['count'] = $post_data_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }
    
    /**
     * Getting shop dashboard post details
     * @param request object
     * @return json string
     */
    public function getShopPostDetails($post_id){
        

        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $data = array();
        
        // get post
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);
     
        if (!$post) {
            $res_data = array('code' => 100, 'message' => 'POST_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
        
        $storeId = $post->getStoreId();
        
        $store = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
        
        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            $res_data = array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => array());
            echo json_encode($res_data);
            exit();
        }

        $comment_count = 0;
        $postDetail = array(); //final array of post data...
        $post_detail = $post_sender_user_ids = $comment_user_ids = $mediaData = $comment_data = $comment_media_result =  array();
        //get user object
        $user_service = $this->get('user_object.service');
 
        //get posts id
        $postsIds = array($post_id);

        //getting the posts sender ids.
        $post_sender_user_ids = array($post->getStorePostAuthor());

        if (count($postsIds)) {
            //get post media from the posts ids
            $post_medias = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->getPostMedia($postsIds);

            //get all coments for the post ids
            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                    ->getPostComments($postsIds);
            //$comments = array_reverse($comments);
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getCommentAuthor()}";
                }, $comments);
            }

            //get all comments id
            $commentsIds = array_map(function($o) {
                return $o->getId();
            }, $comments);

            //get comment media from the comment ids
            $comments_medias = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                    ->getCommentMedia($commentsIds);

            //merege all users array and making unique.
            $users_array = array_unique(array_merge($post_sender_user_ids, $comment_user_ids));

            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
        }
        
//        $citizenIncomeInfo = $em
//                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
//                        ->getCitizenIncome($post_sender_user_ids[0]);
//        $users_object_array[$post_sender_user_ids[0]]['citizen_income'] = key_exists('citizenIncome', $citizenIncomeInfo) ? $citizenIncomeInfo['citizenIncome'] : 0;
       
        $citizenIncomeInfo = $this->getuserincomedetails($post_sender_user_ids[0]);
        $users_object_array[$post_sender_user_ids[0]]['citizen_income'] = isset($citizenIncomeInfo['tot_income']) ? $citizenIncomeInfo['tot_income'] : 0;
      
        
        $postId = $post->getId();
        //get post media
        //loop for getting the post media
        foreach ($post_medias as $post_media) {
            if ($postId == $post_media->getPostId()) {
                $mediaId = $post_media->getId();
                $mediaName = $post_media->getMediaName();
                $mediatype = $post_media->getMediaType();
                $isfeatured = $post_media->getIsFeatured();
                $youtube = $post_media->getYoutube();
                $postId = $post->getId();
                $post_image_type = $post_media->getImageType();

                $mediaDir = $this->getS3BaseUri() . $this->container->getParameter('store_post_media_path'). $postId . '/' . $mediaName;
                $thumbDir = $this->getS3BaseUri() .  $this->container->getParameter('store_post_media_path_thumb') . $postId . '/' . $mediaName;

                $mediaData[] = array('id' => $mediaId,
                    'media_name' => $mediaName,
                    'media_type' => $mediatype,
                    'media_path' => $mediaDir,
                    'media_thumb_path' => $thumbDir,
                    'is_featured' => $isfeatured,
                    'youtube' => $youtube,
                    'image_type' =>$post_image_type
                );
            }
        }
        //loop for getting the post comments          
        foreach ($comments as $comment) {
            if ($postId == $comment->getPostId()) {
                if ($comment_count < $this->post_comment_limit) {
                    $comment_id = $comment->getId();
                    $comment_user_id = $comment->getcommentAuthor();

                    foreach ($comments_medias as $comment_media_data) {
                        if ($comment_id == $comment_media_data->getStoreCommentId()) {
                            $comment_media_id = $comment_media_data->getId();
                            $comment_media_type = $comment_media_data->getMediaType();
                            $comment_media_name = $comment_media_data->getMediaName();
                            $comment_media_status = $comment_media_data->getMediaStatus();
                            $comment_media_is_featured = $comment_media_data->getIsFeatured();
                            $comment_media_created_at = $comment_media_data->getMediaCreated();
                            $comment_image_type = $comment_media_data->getImageType();
                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                $comment_media_link = $comment_media_data->getPath();
                                $comment_media_thumb = '';
                            } else {
                                $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('store_comments_media_path') . $comment_id . '/' . $comment_media_name;
                                $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('store_comments_media_path_thumb') . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                'id' => $comment_media_id,
                                'media_path' => $comment_media_link,
                                'media_thumb' => $comment_media_thumb,
                                'status' => $comment_media_status,
                                'is_featured' => $comment_media_is_featured,
                                'create_date' => $comment_media_created_at,
                                'image_type' =>$comment_image_type
                                    );
                        }
                    }

                    $comment_data[] = array(
                        'id' => $comment_id,
                        'post_id' => $comment->getPostId(),
                        'comment_text' => $comment->getCommentText(),
                        'user_id' => $comment->getCommentAuthor(),
                        'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                        'status' => $comment->getStatus(),
                        'comment_created_at' => $comment->getCommentCreatedAt(),
                        'comment_media_info' => $comment_media_result,
                        'avg_rate'=>round($comment->getAvgRating(), 1),
                        'no_of_votes'=> (int) $comment->getVoteCount(),
                    );
                }
                $comment_media_result = array();
                $comment_count++;
            }
        }


        $post_auth = $post->getStorePostAuthor();
        $user_info = isset($users_object_array[$post_auth]) ? $users_object_array[$post_auth] : array();
        $postDetail[] = array('id' => $postId,
            'title' => $post->getStorePostTitle(),
            'description' => $post->getStorePostDesc(),
            'user_id' => $post->getStorePostAuthor(),
            'created_at' => $post->getStorePostCreated(),
            'link_type' => (int) $post->getLinkType(),
            'media_info' => $mediaData,
            'user_info' => $user_info,
            'comments' => array_reverse($comment_data),
            'comment_count' => $comment_count,
            'avg_rate'=>round($post->getAvgRating(), 1),
            'no_of_votes'=> (int) $post->getVoteCount(),
            'share_type'=>$post->getShareType(),
        );
        $mediaData = array();
        $comment_data = array();
        $comment_count = 0;
     
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('post'=>$postDetail));
        echo json_encode($res_data);
        exit();

    }
    
    
    /**
     * Getting club dashboard post details
     * @param request object
     * @return json string
     */
    public function getClubPostDetails($post_id)
    {
  
        //Code start for getting the request
       $data = array();
       $post = array();
       $comment_user_ids = array();
       $post_sender_user_ids = array();
       $post_ids = array();
       $comment_ids = array();
       
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $em = $this->getDoctrine()->getManager();
        
        $post_data = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                ->findOneBy(array('id' => $post_id));
        
        if (!$post_data) {
            $res_data = array('code' => 100, 'message' => 'POST_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
        
        $group_id = $post_data->getPostGid();
        
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));
        
        if(!$group)
        {
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }

        $post_detail = array();

        //get user object
        $user_service = $this->get('user_object.service');
        
        //getting the posts ids.
        $post_ids = array($post_id);
        
        //getting the posts sender ids.
        $post_sender_user_ids = array($post_data->getPostAuthor());
  
        
        if (count($post_ids)) {
            $post_media = $dm->getRepository('PostPostBundle:PostMedia')
                    ->findPostsMedia($post_ids);
            
            //finding the posts comments.
            $comments = $dm->getRepository('PostPostBundle:Comments')
                    ->findPostsComments($post_ids);
           
            //$comments = array_reverse($comments);
            
            if (count($comments)) {
                $comment_user_ids = array_map(function($comment_data) {
                      return "{$comment_data->getCommentAuthor()}";
                }, $comments);  
            }
            
             //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);
            
            //finding the comments media.
            $comments_media = $dm->getRepository('PostPostBundle:CommentMedia')
                    ->findCommentMedia($comment_ids);
            
            //merege all users array and making unique.
            $users_array = array_unique(array_merge($post_sender_user_ids, $comment_user_ids));
            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
        }
        
//        $citizenIncomeInfo = $em
//                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
//                        ->getCitizenIncome($post_data->getPostAuthor());
//        $users_object_array[$post_data->getPostAuthor()]['citizen_income'] = key_exists('citizenIncome', $citizenIncomeInfo) ? $citizenIncomeInfo['citizenIncome'] : 0;
//       
        $citizenIncomeInfo = $this->getuserincomedetails($post_data->getPostAuthor());
        $users_object_array[$post_data->getPostAuthor()]['citizen_income'] = isset($citizenIncomeInfo['tot_income']) ? $citizenIncomeInfo['tot_income'] : 0;
       
        //prepare all the data..

        $post_id = $post_data->getId();
        $post_media_result = array();
        $comment_data = array();
        //finding the media array of current post.
        foreach ($post_media as $current_post_media) {
            if ($current_post_media->getPostId() == $post_id) {
                $post_media_id = $current_post_media->getId();
                $post_media_type = $current_post_media->getMediaType();
                $post_media_name = $current_post_media->getMediaName();
                $post_media_status = $current_post_media->getMediaStatus();
                $post_media_is_featured = $current_post_media->getIsFeatured();
                $post_media_created_at = $current_post_media->getMediaCreated();
                $youtube_post_data    = $current_post_media->getYoutube();
                $post_image_type  = $current_post_media->getImageType(); 
                if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $post_media_link = $current_post_media->getPath();
                    $post_media_thumb = '';
                } else {
                    $post_media_link = $this->getS3BaseUri() .'/'. $this->container->getParameter('club_post_media_path') . $post_id . '/' . $post_media_name;
                    $post_media_thumb = $this->getS3BaseUri() .'/'. $this->container->getParameter('club_post_media_path_thumb') . $post_id . '/' . $post_media_name;
                }
                $post_media_result[] = array(
                    'id' => $post_media_id,
                    'media_name' => $post_media_name,
                    'media_type' =>$post_media_type,                            
                    'media_created_at' => $post_media_created_at,
                    'is_featured' => $post_media_is_featured,
                    'media_status' => $post_media_status,
                    'youtube' => $youtube_post_data,
                    'media_path' => $post_media_link,
                    'media_thumb_path' => $post_media_thumb,
                    'image_type' =>$post_image_type

                ); 
            }
        }
        $i = 0;
        //finding the comments..
        foreach ($comments as $comment) {
            $comment_id      = $comment->getId();
            $comment_post_id = $comment->getPostId();
            if ($comment_post_id == $post_id ) {
                $comment_user_id = $comment->getCommentAuthor();
                $comment_user_profile_type = $comment->getProfileType();
                //code for user active profile check                        
                // $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media_result = array();
                foreach ($comments_media as $comment_media_data) {
                    if ($comment_media_data->getCommentId() == $comment_id) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getIsActive();
                        $comment_media_is_featured = $comment_media_data->getIsFeatured();
                        $comment_media_created_at = $comment_media_data->getCreatedAt();
                        $comment_image_type = $comment_media_data->getImageType();
                        if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            $comment_media_link = $comment_media_data->getPath();
                            $comment_media_thumb = '';
                        } else {
                            $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('club_comments_media_path') . $comment_id . '/' . $comment_media_name;
                            $comment_media_thumb = $this->getS3BaseUri() .$this->container->getParameter('club_comments_media_path_thumb') . $comment_id . '/' . $comment_media_name;
                        }

                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'is_featured' => $comment_media_is_featured,
                            'create_date' => $comment_media_created_at,
                             'image_type'=>$comment_image_type   
                        );
                    }    
                }

                if ($i < $this->post_comment_limit) { //getting only 4 comments
                    $comment_data[] = array(
                                'id' => $comment_id,
                                'post_id' => $comment_post_id,
                                'comment_text' => $comment->getCommentText(),
                                'user_id' => $comment_user_id,
                                'comment_user_info' => isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array(),
                                'status' => $comment->getStatus(),
                                'create_date' => $comment->getCommentCreatedAt(),
                                'comment_media_info' => $comment_media_result,
                                'avg_rate'=>round($comment->getAvgRating(), 1),
                                'no_of_votes'=> (int) $comment->getVoteCount(),
                            );
                }
                $i++;
            }    
        }

        $comment_count = $i;
        //comment code finish.
        $sender_id   = $post_data->getPostAuthor();
        #$receiver_id = $post_data->getToId();
        $user_info         = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
        #$reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();
        $post [] = array(
            'id' => $post_data->getId(),
            'title' => $post_data->getPostTitle(),
            'created_at' => $post_data->getPostCreated(),
            'description' => $post_data->getPostDesc(),                    
            'user_id' => $sender_id,
            'link_type' => $post_data->getLinkType(),
            'media_info' => $post_media_result,
            'user_info' => $user_info,
            'comments' => array_reverse($comment_data),
            'comment_count' => $comment_count,
            'avg_rate'=>round($post_data->getAvgRating(), 1),
            'no_of_votes'=> (int) $post_data->getVoteCount(),
        );

        $final_data = array('code' => "101", 'message' => 'SUCCESS', 'data' => array('post'=>$post));
        echo json_encode($final_data);            
        exit();
        
    }
    
    /**
     *  function for getting the user income details from UserDiscountPosition table
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    private function getuserincomedetails($idCard) {
        //get request object
        $em = $this->getDoctrine()->getManager();
        $res_data = array();
        $call_type = '';
        try {
            $call_type = $this->container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {

        }
        if ($call_type == 'APPLANE') { //from applane
             try {
                //get the applane service for total citizen income.
                $applane_service      = $this->container->get('appalne_integration.callapplaneservice');
                $citizen_income_data = $applane_service->getCitizenIncome($idCard);
                //prepare the data
                $res_data = array(
                    'stato'=>0,
                    'descrizione'=>'',
                    'saldoc'=>'0',
                    'saldorc'=>0,
                    'saldorm'=>'0',
                    'shopping_plus_user'=>1,
                    'tot_income'=>$citizen_income_data['citizen_income']
                );         
            } catch (\Exception $ex) {
            }
        } else { //from our local database.
            $user_ci_info = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                ->findBy(array('userId' => $idCard));

            $res_data = array('tot_income'=>0);
            if (count($user_ci_info) > 0) {
                $user_ci_info = $user_ci_info[0];
                $res_data['stato'] = 0;
                $res_data['descrizione'] = '';
                $res_data['saldoc'] = $user_ci_info->getCitizenIncome();
                $res_data['saldorc'] = $user_ci_info->getTotalCitizenIncome() - $user_ci_info->getSaldorm();
                $res_data['saldorm'] = $user_ci_info->getSaldorm();
                $res_data['shopping_plus_user'] = 1;
                $res_data['tot_income'] = ($user_ci_info->getTotalCitizenIncome()) / 1000000;
            }
        } 
        return $res_data;
    }
}
