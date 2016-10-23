<?php

namespace Dashboard\DashboardManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentsMedia;
use UserManager\Sonata\UserBundle\Entity\UserConnection;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\UserActiveProfile;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostMedia;
use Notification\NotificationBundle\Document\UserNotifications;

/**
 * Class for handling the posts on dashboard.
 */
class PostV1Controller extends Controller {

    protected $miss_param = '';
    protected $youtube = 'youtube';
    protected $dashboard_post_media_path = '/uploads/documents/dashboard/post/original/';
    protected $dashboard_post_media_path_thumb = '/uploads/documents/dashboard/post/thumb/';
    protected $dashboard_post_media_path_thumb_crop = '/uploads/documents/dashboard/post/thumb_crop/';
    protected $comment_media_path = '/uploads/documents/dashboard/comments/original/';
    protected $comment_media_path_thumb = '/uploads/documents/dashboard/comments/thumb/';
    protected $image_width = 100;
    protected $post_comment_limit = 5;
    protected $post_comment_offset = 0;
    protected $user_profile_type_code = 22;
    protected $profile_type_code = 'user';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $dashboard_post_thumb_image_width = 654;
    protected $dashboard_post_thumb_image_height = 360;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    protected $allowed_share = array('external_share', 'internal_share');
    protected $allowed_object_type = array('club','shop', 'offer', 'social_project', 'voucher');
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * adding the posts and uploading the files.
     * @param request object
     * @return json
     */
    public function postDashboardpostsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];

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

        $required_parameter = array('user_id', 'to_id', 'link_type', 'post_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        if (isset($_FILES['postfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 301, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            }
        }

        //(previously)1 for public, 2 for friend, 3 for private(only me)
        //(currently) 1 for personal post , 2 for professional post , 3 for public
        //check for privacy setting value for personal friend(personal and public) post
        $allow_personal_friend_privacy_setting = array();
        //check for privacy setting value for professional friend(professional and public) post
        $allow_professional_friend_privacy_setting = array();
        //check for privacy setting value for self post
        $allow_self_privacy_setting = array('1', '2', '3');
        $allow_other_user_wall_privacy_setting = array('3');
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
    
        // if user post on his own wall or Dashboard
        if ($object_info->user_id == $object_info->to_id) {
            $allow_privacy_setting = $allow_self_privacy_setting;
        } else { //if user post on other user wall
            $allow_privacy_setting = $this->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $object_info->user_id, $object_info->to_id);
        }


        $user_id = $object_info->user_id;
        $post_type = $object_info->post_type;
        $object_info->post_id = (isset($object_info->post_id) ? $object_info->post_id : '');
        $object_info->youtube_url = (isset($object_info->youtube_url) ? $object_info->youtube_url : '');
        $object_info->title = (isset($object_info->title) ? $object_info->title : '' );
        $object_info->description = (isset($object_info->description) ? $object_info->description : '' );
        // by default every post will be public in nature
        $object_info->privacy_setting = (isset($object_info->privacy_setting) ? $object_info->privacy_setting : 3);

        if (isset($object_info->tagged_friends)) {
            if (trim($object_info->tagged_friends)) {
                $object_info->tagged_friends = explode(',', $object_info->tagged_friends);
            } else {
                $object_info->tagged_friends = array();
            }
        } else {
            $object_info->tagged_friends = array();
        }
        //check if post get published
        if ($object_info->post_type == 1) {
            if (!in_array($object_info->privacy_setting, $allow_privacy_setting)) {
                return array('code' => 153, 'message' => 'INVALID_PRIVACY_SETTING', 'data' => $data);
            }
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        $time = new \DateTime("now");

        if ($post_type == 0) {
            if ($object_info->post_id == '') {
                $dashboard_post = new DashboardPost();
                $dashboard_post->setUserId($object_info->user_id);
                $dashboard_post->setToId($object_info->to_id); //assign the to id(current user or friend id)
                $dashboard_post->setTitle($object_info->title);
                $dashboard_post->setDescription($object_info->description);
                $dashboard_post->setLinkType($object_info->link_type);
                $dashboard_post->setCreatedDate($time);
                $dashboard_post->setIsActive(0); // 0=>disabled, 1=>enabled //first time disabled..
                $dashboard_post->setTaggedFriends($object_info->tagged_friends);
                $dm->persist($dashboard_post); //storing the post data.
                $dm->flush();
                $post_id = $dashboard_post->getId(); //getting the last inserted id of posts.
                //update ACL for a user
                $this->updateAclAction($sender_user, $dashboard_post);
            } else {
                $post_id = $object_info->post_id;
            }
            $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->find($post_id);
            if (!$post_res) {
                return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            }
            $current_post_media = array();
            $dashboard_post_media_id = 0;
            //getting the image name clean service object.
            $clean_name = $this->get('clean_name_object.service');
            $image_upload = $this->get('amazan_upload_object.service');
            //for file uploading...
            if (isset($_FILES['postfile'])) {
                foreach ($_FILES['postfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['postfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['postfile']['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $post_thumb_image_width = $this->dashboard_post_thumb_image_width;
                    $post_thumb_image_height = $this->dashboard_post_thumb_image_height;

                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                        $file_tmp = $_FILES['postfile']['tmp_name'][$key];
                        $file_type = $_FILES['postfile']['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];

                        //find media information 
                        $image_info = getimagesize($_FILES['postfile']['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1];

                        //call service to get image type. Basis of this we save data 3,2,1 in db
                        $image_type_service = $this->get('user_object.service');
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $post_thumb_image_width, $post_thumb_image_height);

                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboard_post_media = new DashboardPostMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboard_post_media->setIsFeatured(1);
                        else
                            $dashboard_post_media->setIsFeatured(0);
                        $dashboard_post_media->setPostId($post_id);
                        $dashboard_post_media->setMediaName($file_name);
                        $dashboard_post_media->setType($actual_media_type);
                        $dashboard_post_media->setCreatedDate($time);
                        $dashboard_post_media->setPath('');
                        $dashboard_post_media->setImageType($image_type);
                        $dashboard_post_media->setMediaStatus(0); //making it unpublish..
                        $dm->persist($dashboard_post_media);
                        $dm->flush();

                        //get the dashboard media id
                        $dashboard_post_media_id = $dashboard_post_media->getId();
                        //update ACL for a user 
                        $this->updateAclAction($sender_user, $dashboard_post_media);
                        //generating the path for the local and s3 images 
                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path') . $post_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path') . $post_id . '/';
                        // $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path_thumb_crop') . $post_id . "/";
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path_thumb') . $post_id . "/";
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path_thumb_crop') . $post_id . "/";
                        //$resize_original_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('dashboard_post_media_path') . $post_id . '/';
                        $s3_post_media_path = $this->container->getParameter('s3_post_media_path') . $post_id;
                        $s3_post_media_thumb_path = $this->container->getParameter('s3_post_media_thumb_path') . $post_id;
                        //$s3_post_media_thumb_crop_path = $this->container->getParameter('s3_post_media_thumb_crop_path');
                        //calling service method for image uploading
                        $image_upload->imageUploadService($_FILES['postfile'], $key, $post_id, 'dashboard_post', $file_name, $pre_upload_media_dir, $media_original_path, $thumb_dir, $thumb_crop_dir, $s3_post_media_path, $s3_post_media_thumb_path);
                    }
                }
            }
            //handling og youtube url.
            if (!empty($object_info->youtube_url)) {
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $dashboard_post_media = new DashboardPostMedia();
                $dashboard_post_media->setIsFeatured(0);
                $dashboard_post_media->setPostId($post_id);
                $dashboard_post_media->setMediaName('');
                $dashboard_post_media->setType($this->youtube);
                $dashboard_post_media->setCreatedDate($time);
                $dashboard_post_media->setPath($object_info->youtube_url);
                $dashboard_post_media->setMediaStatus(1);
                $dm->persist($dashboard_post_media);
                $dm->flush();
                //update ACL for a user 
                $this->updateAclAction($sender_user, $dashboard_post_media);
            }

            //finding the cureent media data.
            $post_media_data = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->find($dashboard_post_media_id);
            if (!$post_media_data) {
                return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            }
            $post_media_name = $post_media_link = $post_media_thumb = $post_image_type = ''; //initialize blank variables.
            if ($post_media_data) {
                $post_image_type = $post_media_data->getImageType();
                $post_media_name = $post_media_data->getMediaName();
                $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
            }
            //sending the current media and post data.
            $data = array(
                'id' => $post_id,
                'media_id' => $dashboard_post_media_id,
                'media_link' => $post_media_link,
                'media_thumb_link' => $post_media_thumb,
                'image_type' => $post_image_type
            );
            $media_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_data_array);
            exit;
        } else { //finding the post and making the post publish.
            $tagged_friends = array();

            if ($object_info->post_id) {
                $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->find($object_info->post_id);
                if (!$post) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }

                if (is_array($post->getTaggedFriends())) {
                    $tagged_friends = $post->getTaggedFriends();
                }

                $post_data = $this->getPostObject($object_info); //finding the post object.
            } else {
                $post_data = $this->getPostObjectWithoutImages($object_info, $sender_user); //finding the post object.
            }

            $postService = $this->container->get('post_detail.service');
            //update in notification table / send email
            if (count($object_info->tagged_friends)) {
                if ($object_info->post_id) {
                    $fid = array_diff($object_info->tagged_friends, $tagged_friends);
                } else {
                    $fid = $object_info->tagged_friends;
                }
                if (count($fid)) {

                    $email_template_service = $this->container->get('email_template.service'); //email template service.
                    $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
                    $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

                    $sender = $postService->getUserData($user_id);
                    $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                    $msgtype = 'TAGGED_IN_POST';
                    $msg = 'tagging';

//                    foreach ($fid as $id) {
//                        //update notification
//                        $notification_id = $this->saveUserNotification($user_id, $id, $msgtype, $msg, $post_data['id']);
//                    }
                    $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $post_data['id'], true, true, $sender_name);
                    $receivers = $postService->getUserData($fid, true);
                    $receiversByLang = $postService->getUsersByLanguage($receivers);

                    foreach ($receiversByLang as $lang=>$receivers){
                        $locale = $lang===0 ? $this->container->getParameter('locale') : $lang;
                        $language_const_array = $this->container->getParameter($locale);
                        $mail_text = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_MAIL_TEXT'], ucwords($sender_name));
                        $href = $angular_app_hostname . 'post/' . $post_data['id']; //href for friend profile
                        $bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                        $subject = sprintf($language_const_array['YOU_GOT_TAGGED_IN_POST'], ucwords($sender_name));
                        $mail_body = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_BODY'], ucwords($sender_name));

                        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                    }
                }
            }
                        
            $postService->sendPostNotificationEmail($post_data, 'dashboard', true, true);
            $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
            echo json_encode($final_data);
            exit;
        }
    }

    /**
     * Finding the post object.save the post and send post object.
     * @param type $post_id
     * @return array $postdata
     */
    public function getPostObjectWithoutImages($object_data, $sender_user) {
        //code for responding the current post data..
        $post_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_id = $object_data->post_id;
        $time = new \DateTime('now');
        $post = new DashboardPost();
        $post->setUserId($object_data->user_id);
        $post->setToId($object_data->to_id); //assign the to id(current user or friend id)
        $post->setTitle($object_data->title);
        $post->setDescription($object_data->description);
        $post->setLinkType($object_data->link_type);
        $post->setTaggedFriends($object_data->tagged_friends);
        $post->setCreatedDate($time);
        $post->setIsActive(1);
        $post->setprivacySetting($object_data->privacy_setting);
        $dm->persist($post);
        $dm->flush();

        //update ACL for a user
        $this->updateAclAction($sender_user, $post);

        $sender_user_info = array();
        $reciver_user_info = array();
        $user_service = $this->get('user_object.service');

        $post_id = $post->getId();
        $post_user_id = $post->getUserId(); //sender 

        $post_to_id = $post->getToId(); //receiver
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findBy(array('post_id' => $post_id));

        $sender_user_info = $user_service->UserObjectService($post_user_id); //sender user object
        $reciver_user_info = $user_service->UserObjectService($post_to_id);  //receiver user object
        //code for user active profile check
        $post_media_result = array();
        if ($post_media) {
            foreach ($post_media as $post_media_data) {
                $post_media_id = $post_media_data->getId();
                $post_media_type = $post_media_data->getType();
                $post_media_name = $post_media_data->getMediaName();
                $post_media_status = $post_media_data->getMediaStatus();
                $post_media_is_featured = $post_media_data->getIsFeatured();
                $post_media_created_at = $post_media_data->getCreatedDate();
                $post_image_type = $post_media_data->getImageType();
                if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $post_media_link = $post_media_data->getPath();
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

        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
        $comments = array_reverse($comments);
        $comment_data = array();
        $comment_user_info = array();
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();

                $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id));
                $comment_media_result = array();
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
                        'image_type' => $comment_image_type
                    );
                }

                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'comment_user_info' => $comment_user_info,
                    'status' => $comment->getIsActive(),
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result);
            }
        }

        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $post->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object

        /** fetch rating of current user * */
        $current_rate = 0;
        $is_rated = false;
        $rate_data_obj = $post->getRate();
        if (count($rate_data_obj) > 0) {
            foreach ($rate_data_obj as $rate) {
                if ($rate->getUserId() == $post_user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
        }
        //finding the comments end.
        //prepare the data.
        $post_data = array(
            'id' => $post_id,
            'user_id' => $post->getUserId(),
            'to_id' => $post->getToId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'link_type' => $post->getLinkType(),
            'is_active' => $post->getIsActive(),
            'created_at' => $post->getCreatedDate(),
            'avg_rate' => round($post->getAvgRating(), 1),
            'no_of_votes' => $post->getVoteCount(),
            'current_user_rate' => $current_rate,
            'is_rated' => $is_rated,
            'user_info' => $sender_user_info,
            'reciver_user_info' => $reciver_user_info,
            'privacy_setting' => $post->getPrivacySetting(),
            'media_info' => $post_media_result,
            'comments' => $comment_data,
            'tagged_friends_info' => $tagged_friends_info
        );
        //code end for responding the current post data.
        return $post_data;
    }

    /**
     * Finding the post object. update the post and send post object.
     * @param type $post_id
     * @return array $postdata
     */
    public function getPostObject($object_data) {
        //code for responding the current post data..
        $post_data = array();
        $media_ids_array = array();
        $object_data->media_id = (isset($object_data->media_id) ? $object_data->media_id : $media_ids_array);
        $media_array = $object_data->media_id;

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_id = $object_data->post_id;
        $time = new \DateTime('now');
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        // updating the post data, making the post publish.
        $post->setUserId($object_data->user_id);
        $post->setToId($object_data->to_id); //assign the to id(current user or friend id)
        $post->setTitle($object_data->title);
        $post->setDescription($object_data->description);
        $post->setLinkType($object_data->link_type);
        $post->setTaggedFriends($object_data->tagged_friends);
        $post->setCreatedDate($time);
        $post->setIsActive(1);
        $post->setprivacySetting($object_data->privacy_setting);
        $dm->persist($post);
        $dm->flush();

        $sender_user_info = array();
        $reciver_user_info = array();
        $user_service = $this->get('user_object.service');

        $post_id = $post->getId();
        $post_user_id = $post->getUserId(); //sender 

        $post_to_id = $post->getToId(); //receiver
        //making the media publish..
        if (count($media_array)) {
            $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->publishMedia($post_id, $media_array);
        }
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findBy(array('post_id' => $post_id, 'media_status' => 1));

        $sender_user_info = $user_service->UserObjectService($post_user_id); //sender user object
        $reciver_user_info = $user_service->UserObjectService($post_to_id);  //receiver user object
        //code for user active profile check
        $post_media_result = array();
        if ($post_media) {
            foreach ($post_media as $post_media_data) {
                $post_media_id = $post_media_data->getId();
                $post_media_type = $post_media_data->getType();
                $post_media_name = $post_media_data->getMediaName();
                $post_media_status = $post_media_data->getMediaStatus();
                $post_media_is_featured = $post_media_data->getIsFeatured();
                $post_media_created_at = $post_media_data->getCreatedDate();
                $post_image_type = $post_media_data->getImageType();
                if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $post_media_link = $post_media_data->getPath();
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

        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
        $comments = array_reverse($comments);
        $comment_data = array();
        $comment_user_info = array();
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();

                $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id));
                $comment_media_result = array();
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
                        'image_type' => $comment_image_type
                    );
                }

                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'comment_user_info' => $comment_user_info,
                    'status' => $comment->getIsActive(),
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result);
            }
        }

        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $post->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object

        /** fetch rating of current user * */
        $current_rate = 0;
        $is_rated = false;
        $rate_data_obj = $post->getRate();
        if (count($rate_data_obj) > 0) {
            foreach ($rate_data_obj as $rate) {
                if ($rate->getUserId() == $post_user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
        }
        //finding the comments end.
        //prepare the data.
        $post_data = array(
            'id' => $post_id,
            'user_id' => $post->getUserId(),
            'to_id' => $post->getToId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'link_type' => $post->getLinkType(),
            'tagged_friends' => $post->getTaggedFriends(),
            'is_active' => $post->getIsActive(),
            'created_at' => $post->getCreatedDate(),
            'avg_rate' => round($post->getAvgRating(), 1),
            'no_of_votes' => $post->getVoteCount(),
            'current_user_rate' => $current_rate,
            'is_rated' => $is_rated,
            'user_info' => $sender_user_info,
            'reciver_user_info' => $reciver_user_info,
            'privacy_setting' => $post->getPrivacySetting(),
            'media_info' => $post_media_result,
            'comments' => $comment_data,
            'tagged_friends_info' => $tagged_friends_info
        );
        //code end for responding the current post data.
        return $post_data;
    }

    /**
     * remove the posts and removing the files.
     * @param request object
     * @return json
     */
    public function postRemovedashboardpostsAction(Request $request) {

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
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER' . $this->miss_param, 'data' => $data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $dashboard_post = new DashboardPost();

        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($object_info->post_id);
        if (!$post_res) {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
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
        //for DashBoard Post ACL     
        $do_action = 0;
        $group_mask = $this->userPostRole($object_info->post_id, $object_info->user_id);

        //check for Access Permission
        //only owner can delete the dashboard post
        $allow_group = array('15');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        if ($do_action) {
            $dm->remove($post_res);
            $dm->flush();

            //remove notification related to particular post 
            $post_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')->findBy(array("item_id" => "$object_info->post_id"));
            if ($post_notifications) {
                foreach ($post_notifications as $notification) {
                    $dm->remove($notification);
                    $dm->flush();
                }
            }

            //removing the dashboard post media..
            $dashboard_post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->removePostsMedia($object_info->post_id);

            if ($dashboard_post_media) {
                //removing the images from directory
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $post_images_location = $file_location . $this->dashboard_post_media_path . $object_info->post_id;
                $post_thumb_images_location = $file_location . $this->dashboard_post_media_path_thumb . $object_info->post_id;

                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (@file_exists($post_images_location)) { //remove original images.
                    // array_map('unlink', glob($post_images_location . '/*')); //remove the directory recursively.
                    // rmdir($post_images_location);
                }
                if (@file_exists($post_thumb_images_location)) { //remove thumb images.
                    //  array_map('unlink', glob($post_thumb_images_location . '/*')); //remove the directory recursively.
                    //  rmdir($post_thumb_images_location);
                }
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            } else {
                return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            }
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
    }

    /**
     * edit the post for dashboard and uploading the files.
     * @param request object
     * @return json
     */
    public function postDashboardeditpostsAction(Request $request) {
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

        $required_parameter = array('post_id', 'user_id', 'to_id');
        $data = array();
        //checking for parameter missing.

        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        //(previously)1 for public, 2 for friend, 3 for private(only me)
        //(currently) 1 for personal post , 2 for professional post , 3 for public
        //check for privacy setting value for personal friend(personal and public) post
        $allow_personal_friend_privacy_setting = array();
        //check for privacy setting value for professional friend(professional and public) post
        $allow_professional_friend_privacy_setting = array();
        //check for privacy setting value for self post
        $allow_self_privacy_setting = array('1', '2', '3');
        $allow_other_user_wall_privacy_setting = array('3');
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        // if user post on his own wall or Dashboard
        if ($object_info->user_id == $object_info->to_id) {
            $allow_privacy_setting = $allow_self_privacy_setting;
        } else { //if user post on other user wall
            $allow_privacy_setting = $this->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $object_info->user_id, $object_info->to_id);
        }

        if (isset($_FILES['postfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            }
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($object_info->post_id);
        if (!$post_res) {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $post_type = (isset($object_info->post_type) ? $object_info->post_type : 1);
        $object_info->youtube_url = (isset($object_info->youtube_url) ? $object_info->youtube_url : '');
        $object_info->title = (isset($object_info->title) ? $object_info->title : '' );
        $object_info->description = (isset($object_info->description) ? $object_info->description : '' );
        // by default any post will be public in nature
        $object_info->privacy_setting = (isset($object_info->privacy_setting) ? $object_info->privacy_setting : 3 );

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.  
        if (isset($object_info->tagged_friends)) {
            if (trim($object_info->tagged_friends)) {
                $object_info->tagged_friends = explode(',', $object_info->tagged_friends);
            } else {
                $object_info->tagged_friends = array();
            }
        } else {
            $object_info->tagged_friends = array();
        }

        //check if privacy setting is in defined array
        if (!in_array($object_info->privacy_setting, $allow_privacy_setting)) {
            return array('code' => 153, 'message' => 'INVALID_PRIVACY_SETTING', 'data' => $data);
        }

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

        //for DashBoard Post ACL     
        $do_action = 0;
        $group_mask = $this->userPostRole($object_info->post_id, $object_info->user_id);

        //check for Access Permission
        //only owner can delete the dashboard post
        $allow_group = array('15');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        $time = new \DateTime("now");
        if ($do_action) {
            if ($post_type == 0) {

                $post_id = $object_info->post_id;

                $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->find($post_id);
                if (!$post_res) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }
                $current_post_media = array();
                $dashboard_post_media_id = 0;
                //for file uploading...
                if (isset($_FILES['postfile'])) {
                    foreach ($_FILES['postfile']['tmp_name'] as $key => $tmp_name) {
                        $original_file_name = $_FILES['postfile']['name'][$key];
                        $file_name = time() . strtolower(str_replace(' ', '', $_FILES['postfile']['name'][$key]));
                        $post_thumb_image_width = $this->dashboard_post_thumb_image_width;
                        $post_thumb_image_height = $this->dashboard_post_thumb_image_height;

                        if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                            $file_tmp = $_FILES['postfile']['tmp_name'][$key];
                            $file_type = $_FILES['postfile']['type'][$key];
                            $media_type = explode('/', $file_type);
                            $actual_media_type = $media_type[0];

                            //find media information 
                            $image_info = getimagesize($_FILES['postfile']['tmp_name'][$key]);
                            $orignal_mediaWidth = $image_info[0];
                            $original_mediaHeight = $image_info[1];

                            //call service to get image type. Basis of this we save data 3,2,1 in db
                            $image_type_service = $this->get('user_object.service');
                            $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $post_thumb_image_width, $post_thumb_image_height);

                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
                            $dashboard_post_media = new DashboardPostMedia();
                            if (!$key) //consider first image the featured image.
                                $dashboard_post_media->setIsFeatured(1);
                            else
                                $dashboard_post_media->setIsFeatured(0);
                            $dashboard_post_media->setPostId($post_id);
                            $dashboard_post_media->setMediaName($file_name);
                            $dashboard_post_media->setType($actual_media_type);
                            $dashboard_post_media->setCreatedDate($time);
                            $dashboard_post_media->setPath('');
                            $dashboard_post_media->setImageType($image_type);
                            $dashboard_post_media->setMediaStatus(0); //making the unpublish th media.
                            $dashboard_post_media->upload($post_id, $key, $file_name); //uploading the files.
                            $dm->persist($dashboard_post_media);
                            $dm->flush();

                            //get the dashboard media id
                            $dashboard_post_media_id = $dashboard_post_media->getId();
                            //update ACL for a user 
                            $this->updateAclAction($sender_user, $dashboard_post_media);
                            if ($actual_media_type == 'image') {
                                $media_original_path = $this->getBaseUri() . $this->dashboard_post_media_path . $post_id . '/';
                                $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb_crop . $post_id . "/";
                                $thumb_dir = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb . $post_id . "/";
                                $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb_crop . $post_id . "/";

                                $resize_original_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/post/original/" . $post_id . '/';
                                //resize the original image..
                                $this->resizeOriginal($file_name, $media_original_path, $resize_original_dir, $post_id);
                                //first resize the post image into crop folder
                                $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $post_id);
                                //crop the image from center
                                $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $post_id);
                            }
                        }
                    }
                }
                //handling og youtube url.
                if (!empty($object_info->youtube_url)) {
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $dashboard_post_media = new DashboardPostMedia();
                    $dashboard_post_media->setIsFeatured(0);
                    $dashboard_post_media->setPostId($post_id);
                    $dashboard_post_media->setMediaName('');
                    $dashboard_post_media->setType($this->youtube);
                    $dashboard_post_media->setCreatedDate($time);
                    $dashboard_post_media->setPath($object_info->youtube_url);
                    $dashboard_post_media->setMediaStatus(1);
                    $dm->persist($dashboard_post_media);
                    $dm->flush();
                    //update ACL for a user 
                    $this->updateAclAction($sender_user, $dashboard_post_media);
                }

                //finding the current media data.
                $post_media_data = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                        ->find($dashboard_post_media_id);
                if (!$post_media_data) {
                    return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }
                $post_media_name = $post_media_link = $post_media_thumb = $post_image_type = ''; //initialize blank variables.
                if ($post_media_data) {
                    $post_image_type = $post_media_data->getImageType();
                    $post_media_name = $post_media_data->getMediaName();
                    $post_media_link = $this->getS3BaseUri() . $this->dashboard_post_media_path . $post_id . '/' . $post_media_name;
                    $post_media_thumb = $this->getS3BaseUri() . $this->dashboard_post_media_path_thumb . $post_id . '/' . $post_media_name;
                }
                //sending the current media and post data.
                $data = array(
                    'id' => $post_id,
                    'media_id' => $dashboard_post_media_id,
                    'media_link' => $post_media_link,
                    'media_thumb_link' => $post_media_thumb,
                    'image_type' => $post_image_type
                );
                $media_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($media_data_array);
                exit;
            } else { //finding the post and making the post update.
                $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                        ->find($object_info->post_id);
                if (!$post) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }

                if (is_array($post->getTaggedFriends())) {
                    $tagged_friends = $post->getTaggedFriends();
                } else {
                    $tagged_friends = array();
                }

                $post_data = $this->getEditPostObject($object_info); //finding the post object.
                //update in notification table / send email
                if (count($object_info->tagged_friends)) {
                    if ($object_info->post_id) {
                        $fid = array_diff($object_info->tagged_friends, $tagged_friends);
                    } else {
                        $fid = $object_info->tagged_friends;
                    }
                    if (count($fid)) {
                        //find user object service..
                        $user_service = $this->get('user_object.service');
                        //get user profile and cover images..
                        $users_object_array = $user_service->MultipleUserObjectService($user_id);

                        $postService = $this->container->get('post_detail.service');
                        $email_template_service = $this->container->get('email_template.service'); //email template service.
                        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
                        $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

                        $sender = $postService->getUserData($user_id);
                        $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                        $msgtype = 'TAGGED_IN_POST';
                        $msg = 'tagging';
//                        foreach ($fid as $id) {
//                            //update notification
//                            $notification_id = $this->saveUserNotification($user_id, $id, $msgtype, $msg, $post_data['id']);
//                        }
                        $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $post_data['id'], true, true, $sender_name);

                        $receivers = $postService->getUserData($fid, true);
                        $receiversByLang = $postService->getUsersByLanguage($receivers);

                        foreach ($receiversByLang as $lang=>$receivers){
                            $locale = $lang===0 ? $this->container->getParameter('locale') : $lang;
                            $language_const_array = $this->container->getParameter($locale);
                            $mail_text = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_MAIL_TEXT'], ucwords($sender_name));
                            $href = $angular_app_hostname . 'post/' . $post_data['id']; //href for friend profile
                            $bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href, $locale); //making the link html from service
                            $subject = sprintf($language_const_array['YOU_GOT_TAGGED_IN_POST'], ucwords($sender_name));
                            $mail_body = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_BODY'], ucwords($sender_name));

                            $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                        }
                    }
                }

                $final_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                echo json_encode($final_data_array);
                exit;
            }
        } else {
            $final_data_array = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($final_data_array);
            exit;
        }
    }

    /**
     * Finding the post object on editing the post
     * @param type $post_id
     * @return array $postdata
     */
    public function getEditPostObject($object_data) {

        //code for responding the current post data..
        $post_data = array();
        $media_ids_array = array();
        $object_data->media_id = (isset($object_data->media_id) ? $object_data->media_id : $media_ids_array);
        $media_array = $object_data->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_id = $object_data->post_id;
        $time = new \DateTime('now');
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        // updating the post data.
        $post->setUserId($object_data->user_id);
        $post->setToId($object_data->to_id); //assign the to id(current user or friend id)
        $post->setTitle($object_data->title);
        $post->setDescription($object_data->description);
        $post->setTaggedFriends($object_data->tagged_friends);
        $post->setprivacySetting($object_data->privacy_setting);
        //$post->setLinkType($object_data->link_type);
        //$post->setCreatedDate($time); 
        $post->setIsActive(1);
        $dm->persist($post);
        $dm->flush();

        $sender_user_info = array();
        $reciver_user_info = array();
        $user_service = $this->get('user_object.service');

        $post_id = $post->getId();
        $post_user_id = $post->getUserId(); //sender 

        $post_to_id = $post->getToId(); //receiver
        //making the media publish..
        if (count($media_array)) {
            $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->publishMedia($post_id, $media_array);
        }

        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findBy(array('post_id' => $post_id, 'media_status' => 1));

        $sender_user_info = $user_service->UserObjectService($post_user_id); //sender user object
        $reciver_user_info = $user_service->UserObjectService($post_to_id);  //receiver user object
        //code for user active profile check
        $post_media_result = array();
        if ($post_media) {
            foreach ($post_media as $post_media_data) {
                $post_media_id = $post_media_data->getId();
                $post_media_type = $post_media_data->getType();
                $post_media_name = $post_media_data->getMediaName();
                $post_media_status = $post_media_data->getMediaStatus();
                $post_media_is_featured = $post_media_data->getIsFeatured();
                $post_media_created_at = $post_media_data->getCreatedDate();
                $post_image_type = $post_media_data->getImageType();
                if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $post_media_link = $post_media_data->getPath();
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

        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
        $comments = array_reverse($comments);
        $comment_data = array();
        $comment_user_info = array();
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();

                $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id));
                $comment_media_result = array();
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
                        'image_type' => $comment_image_type
                    );
                }

                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'comment_user_info' => $comment_user_info,
                    'status' => $comment->getIsActive(),
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result);
            }
        }

        /** fetch rating of current user * */
        $current_rate = 0;
        $is_rated = false;
        $rate_data_obj = $post->getRate();
        if (count($rate_data_obj) > 0) {
            foreach ($rate_data_obj as $rate) {
                if ($rate->getUserId() == $post_user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
        }
        //finding the comments end.
        //prepare the data.
        $post_data = array(
            'id' => $post_id,
            'user_id' => $post->getUserId(),
            'to_id' => $post->getToId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'link_type' => $post->getLinkType(),
            'is_active' => $post->getIsActive(),
            'created_at' => $post->getCreatedDate(),
            'avg_rate' => round($post->getAvgRating(), 1),
            'no_of_votes' => $post->getVoteCount(),
            'current_user_rate' => $current_rate,
            'is_rated' => $is_rated,
            'user_info' => $sender_user_info,
            'privacy_setting' => $post->getprivacySetting(),
            'reciver_user_info' => $reciver_user_info,
            'media_info' => $post_media_result,
            'comments' => $comment_data
        );
        //code end for responding the current post data.
        return $post_data;
    }

    /**
     * remove the media for post of dashboard.
     * @param string postid
     * @param string post media id
     * @return json
     */
    public function postRemovemediapostAction(Request $request) {
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

        $required_parameter = array('post_media_id', 'user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $post_media_id = $object_info->post_media_id;
        $user_id = $object_info->user_id;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->find($object_info->post_media_id);

        if (!$post_media) {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        //for store ACL    
        $do_action = 0;
        $group_mask = $this->userPostMediaRole($post_media_id, $object_info->user_id);

        //check for Access Permission
        //only owner can delete the post media of dashboard.
        $allow_group = array('15');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        if ($do_action) {
            $media_type = $post_media->getType();
            $media_name = $post_media->getMediaName();
            $post_id = $post_media->getPostId();

            $dm->remove($post_media);
            $dm->flush();
            if ($media_type == 'image' || $media_type == 'video') {
                //unlink the file..
                $media_path = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path . $post_id . '/' . $media_name;
                $media_thumb_path = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb . $post_id . '/' . $media_name;
                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (@file_exists($media_path)) { //remove original image.
                    //   @\unlink($media_path);
                }
                if (@file_exists($media_thumb_path)) { //remove thumb image.
                    //  @\unlink($media_thumb_path);
                }
            }
            return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
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
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
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
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        $file_error = 0;
        foreach ($_FILES['postfile']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['postfile']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['postfile']['type'][$key] == 'image/jpg' || $_FILES['postfile']['type'][$key] == 'image/jpeg' ||
                        $_FILES['postfile']['type'][$key] == 'image/gif' || $_FILES['postfile']['type'][$key] == 'image/png'))) || (preg_match('/^.*\.(mp3|mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
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
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParams($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && (!empty($converted_array[$param]) || $converted_array[$param] == 0)) {
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
     * Get User role for dashboard post
     * @param int $post_id
     * @param int $user_id
     * @return int
     */
    public function userPostRole($post_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('DashboardManagerBundle:DashboardPost')
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
     * Get User role for dashboard post media
     * @param int $post_media_id
     * @param int $user_id
     * @return int
     */
    public function userPostMediaRole($post_media_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findOneBy(array('id' => $post_media_id)); //@TODO Add group owner id in AND clause.

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
     * @param object $dashboard_post_entity
     * @return none
     */
    public function updateAclAction($sender_user, $dashboard_post_entity) {
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($dashboard_post_entity);
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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $post_id) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->dashboard_post_thumb_image_width;
        $thumb_height = $this->dashboard_post_thumb_image_height;
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

        $s3_image_path = "uploads/documents/dashboard/post/thumb_crop/" . $post_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3_image_path, $image_local_path, $filename);
    }

    /**
     * Finding the post feeds of a friend
     * @param int user_id
     * @param int friend_id
     * @return json string
     */
    public function postGetfriendfeedsAction(Request $request) {
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
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $friend_id = $object_info->friend_id;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;

        $see_profile = 0;
        /*
          //find the profile visibility of friend(public, private , friends only)
          $profile_visibility_result = $this->checkProfileVisibilty($friend_id);
          //this is for the time being this will be changed according to the profile difference
          //we have checked if a profile type is public then we are sending the post feeds.
          foreach ($profile_visibility_result as $profile_result) {
          if ($profile_result == 1) {
          $see_profile = 1;
          break;
          } else if ($profile_result == 2) {
          $see_profile = 0;
          } else if ($profile_result == 3) {
          $see_profile = 2; //need to be check for friendship.
          break;
          } else {

          }
          }

          $do_action = 0;
          if ($see_profile == 1) { //if any profile is set public
          $do_action = 1;
          } else if ($see_profile == 2) { //if need for check friendship.
          //check for friendship.
          $freindship_result = $this->checkForFriend($user_id, $friend_id);
          if ($freindship_result) {
          $do_action = 1;
          }
          }
         */
        $do_action = 0;
        $freindship_result = $this->checkForFriend($user_id, $friend_id);
        if ($freindship_result) {
            $do_action = 1;
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_data = array();
        $user_service = $this->get('user_object.service');
        if ($do_action) {
            $posts = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->findBy(array('to_id' => $friend_id), array('created_date' => 'DESC'), $limit, $offset);
            $post_data_count = count($dm->getRepository('DashboardManagerBundle:DashboardPost')
                            ->findBy(array('to_id' => $friend_id)));
            $sender_user_info = array();
            $reciver_user_info = array();
            $user_service = $this->get('user_object.service');
            foreach ($posts as $post) {
                $post_id = $post->getId();
                $post_user_id = $post->getUserId(); //sender 

                $post_to_id = $post->getToId(); //receiver
                $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                        ->findBy(array('post_id' => $post_id));


                $sender_user_info = $user_service->UserObjectService($post_user_id); //sender 
                $reciver_user_info = $user_service->UserObjectService($post_to_id); //receiver
                //code for user active profile check
                $post_media_result = array();
                if ($post_media) {
                    foreach ($post_media as $post_media_data) {
                        $post_media_id = $post_media_data->getId();
                        $post_media_type = $post_media_data->getType();
                        $post_media_name = $post_media_data->getMediaName();
                        $post_media_status = $post_media_data->getMediaStatus();
                        $post_media_is_featured = $post_media_data->getIsFeatured();
                        $post_media_created_at = $post_media_data->getCreatedDate();
                        $post_image_type = $post_media_data->getImageType();
                        if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                            $post_media_link = $post_media_data->getPath();
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

                //finding the comments start.
                $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                        ->findBy(array('post_id' => $post_id), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
                $comments = array_reverse($comments);
                $comment_data = array();
                $comment_user_info = array();
                if ($comments) {
                    foreach ($comments as $comment) {
                        $comment_id = $comment->getId();
                        $comment_user_id = $comment->getUserId();

                        $comment_user_info = $user_service->UserObjectService($comment_user_id);
                        $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                                ->findBy(array('comment_id' => $comment_id));
                        $comment_media_result = array();
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
                                'image_type' => $comment_image_type
                            );
                        }

                        $comment_data[] = array(
                            'id' => $comment_id,
                            'post_id' => $comment->getPostId(),
                            'comment_text' => $comment->getCommentText(),
                            'user_id' => $comment->getUserId(),
                            'comment_user_info' => $comment_user_info,
                            'status' => $comment->getIsActive(),
                            'create_date' => $comment->getCreatedAt(),
                            'comment_media_info' => $comment_media_result);
                    }
                }

                //finding the comments end.
                $post_data [] = array(
                    'id' => $post_id,
                    'user_id' => $post->getUserId(),
                    'to_id' => $post->getToId(),
                    'title' => $post->getTitle(),
                    'description' => $post->getDescription(),
                    'link_type' => $post->getLinkType(),
                    'is_active' => $post->getIsActive(),
                    'created_at' => $post->getCreatedDate(),
                    'user_info' => $sender_user_info,
                    'reciver_user_info' => $reciver_user_info,
                    'media_info' => $post_media_result,
                    'comments' => $comment_data
                );
            }
            $data['post'] = $post_data;
            $data['count'] = $post_data_count;
            $final_data_array = array('code' => 100, 'message' => 'SUCCESS.', 'data' => $data);
            echo json_encode($final_data_array);
            exit;
        } else {
            return array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
        }
    }

    /**
     * Finding all comments list of a post.
     * @param int user_id
     * @param int friend_id
     * @return json string
     */
//    public function postGetdashboardcommentsAction(Request $request) {
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
//        $required_parameter = array('post_id');
//        $data = array();
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        $post_id = $object_info->post_id;
//        $limit   = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
//        $offset  = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
//        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
//                ->find($post_id);
//        if (!$post_res) {
//            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
//        }
//        $comments_count = 0;
//        //finding the comments start.
//        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
//                ->findBy(array('post_id' => $post_id, 'is_active'=>1), array('created_at' => 'ASC'), $limit, $offset);
//        if (count($comments)) {
//            $comments_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
//                        ->findBy(array('post_id' => $post_id, 'is_active'=>1)));
//        }
//        $comment_data = array();
//        $comment_user_info = array();
//        $user_service = $this->get('user_object.service');
//        if ($comments) {
//            foreach ($comments as $comment) {
//                $comment_id = $comment->getId();
//                $comment_user_id = $comment->getUserId();
//
//                $comment_user_info = $user_service->UserObjectService($comment_user_id);
//                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
//                        ->findBy(array('comment_id' => $comment_id));
//                $comment_media_result = array();
//                foreach ($comment_media as $comment_media_data) {
//                    $comment_media_id = $comment_media_data->getId();
//                    $comment_media_type = $comment_media_data->getType();
//                    $comment_media_name = $comment_media_data->getMediaName();
//                    $comment_media_status = $comment_media_data->getIsActive();
//                    $comment_media_is_featured = $comment_media_data->getIsFeatured();
//                    $comment_media_created_at = $comment_media_data->getCreatedAt();
//                    if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                        $comment_media_link  = $post_media_data->getPath();
//                        $comment_media_thumb = '';
//                    } else {
//                        $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
//                        $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
//                    }
//
//                    $comment_media_result[] = array(
//                        'id' => $comment_media_id,
//                        'media_link' => $comment_media_link,
//                        'media_thumb_link' => $comment_media_thumb,
//                        'status' => $comment_media_status,
//                        'is_featured' => $comment_media_is_featured,
//                        'create_date' => $comment_media_created_at);
//                }
//
//                $comment_data[] = array(
//                    'id' => $comment_id,
//                    'post_id' => $comment->getPostId(),
//                    'comment_text' => $comment->getCommentText(),
//                    'user_id' => $comment->getUserId(),
//                    'status' => $comment->getIsActive(),
//                    'comment_user_info' => $comment_user_info,
//                    'create_date' => $comment->getCreatedAt(),
//                    'comment_media_info' => $comment_media_result);
//            }
//        }
//        $data['comment'] = $comment_data;
//        $data['count']   = $comments_count;
//        $final_data_array =  array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
//        echo json_encode($final_data_array);
//        exit;
//    }

    /**
     * Checking for a friendship.
     * @param int $user_id
     * @param int $friend_id
     * @return int 
     */
    private function checkForFriend($user_id, $friend_id) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User 

        $results = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendShip($user_id, $friend_id);

        if ($results > 0) {
            //friend request already sent
            return 1;
        }

        //new friend request.
        return 0;
    }

    /**
     * check profile visibilty for a user
     * @param int $friend_id
     * @return int visibilty type(public/private/only friends only)
     */
    private function checkProfileVisibilty($friend_id) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in UserMultiple Repository
        $results = $em->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findBy(array('userId' => $friend_id));
        $profile_visiblity_array = array();
        foreach ($results as $result) {
            switch ($result->getprofileType()) {
                case 22 :
                    $profile_visiblity_array['citizen_profile_status'] = $result->getProfileSetting();
                    break;
                case 23 :
                    $profile_visiblity_array['citizen_writer_profile_status'] = $result->getProfileSetting();
                    break;
                case 24 :
                    $profile_visiblity_array['broker_profile_status'] = $result->getProfileSetting();
                    break;
                case 25 :
                    $profile_visiblity_array['ambassdor_profile_status'] = $result->getProfileSetting();
                    break;
                default :
                    break;
            }
        }
        return $profile_visiblity_array;
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
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $post_id) {

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb . $post_id . "/";
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
        $crop_image_width = $this->dashboard_post_thumb_image_width;
        $crop_image_height = $this->dashboard_post_thumb_image_height;

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
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75); //100 is quality

        $s3_image_path = "uploads/documents/dashboard/post/thumb/" . $post_id;
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
        $this->s3imageUpload($s3_image_path, $image_local_path, $original_filename);
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

        $s3imagepath = "uploads/documents/dashboard/post/original/" . $post_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
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
     * Finding all comments list of a post.
     * @param int user_id
     * @param int friend_id
     * @return json string
     */
    public function postGetdashboardcommentsAction(Request $request) {
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
        $data = $comment_user_ids = $users_array = $comments_media = $comment_data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $post_id = $object_info->post_id;
        $user_id = $object_info->user_id;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);
        //check if post is exists or not.
        if (!$post_res) {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $comments_count = 0;
        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id, 'is_active' => 1), array('created_at' => 'ASC'), $limit, $offset);

        //if there is any comments for this post then find the other things.
        if (count($comments)) {
            $comments_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
                            ->findBy(array('post_id' => $post_id, 'is_active' => 1))); //find total count of active comments.
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                return "{$comment_data->getId()}";
            }, $comments);

            //comments user ids.    
            $comment_user_ids = array_map(function($comment_data) {
                return "{$comment_data->getUserId()}";
            }, $comments);

            //finding the comments media.
            $comments_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                    ->findCommentMedia($comment_ids);
            //making user ids array unique.
            $users_array = array_unique($comment_user_ids);
            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);

            //iterate the object if comments is exists.
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();
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
                        //prepare the comment media result.
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

                //comment user info.
                $comment_user_info = isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array();
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
                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'status' => $comment->getIsActive(),
                    'comment_user_info' => $comment_user_info,
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result,
                    'avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated
                );
            }
        }
        //prepare the response.
        $data['comment'] = $comment_data;
        $data['count'] = $comments_count;
        $final_data_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data_array);
        exit;
    }

    /**
     * Remove tag from dashboard post and images 
     * @param object request
     * @return json string
     */
    public function postRemovetaggedusersAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check required params
        $required_params = array('user_id', 'untag_user_id', 'post_id');
        $this->checkRequiredParams($de_serialize, $required_params);

        //validating params
        $requited_fields = array('user_id', 'untag_user_id', 'post_id');
        foreach ($requited_fields as $field) {
            if ($de_serialize[$field] == '') {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($de_serialize['post_id']);

        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }

        $creater_id = $post->getUserId();
        $tagged_user_ids = $post->getTaggedFriends();

        if (!$tagged_user_ids) {
            $tagged_user_ids = array();
        }

        if ($de_serialize['user_id'] != $creater_id) {
            if ($de_serialize['user_id'] != $de_serialize['untag_user_id']) {
                $res_data = array('code' => 302, 'message' => 'ACTION_NOT_PERMITED', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }

        if (count($tagged_user_ids)) {
            $users = $tagged_user_ids;
        } else {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $index = '';

        if (in_array($de_serialize['untag_user_id'], $users)) {

            $index = array_search($de_serialize['untag_user_id'], $users);
            unset($users[$index]);
            //$new_tagged_user_ids = implode(',',array_values($users));    
            $post->setTaggedFriends(array_values($users));

            $dm->persist($post); //storing the post data.
            $dm->flush();

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit;
        } else {
            $res_data = array('code' => 302, 'message' => 'USER_ALREADY_UNTAGGED', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
    }

    /**
     * Checking Required Params In json
     * @param $user_params array json array send by user
     * @param $required_params array required params array
     */
    public function checkRequiredParams($user_params, $required_params) {

        foreach ($required_params as $param) {
            if (!array_key_exists($param, $user_params)) {
                $final_data = array('code' => 130, 'message' => 'PARAMS_MISSING', 'data' => array());
                echo json_encode($final_data);
                exit;
            }
        }
    }

    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $msgtype, $msg, $item_id = 0) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId($item_id);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $dm->persist($notification);
        $dm->flush();
        return $notification->getId();
    }

    /*
     * function for checking friendship type
     */

    public function checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id) {

        $em = $this->getDoctrine()->getManager();
        $friends_results = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkPersonalProfessionalFriendship($user_id, $to_id);
        $friends_array = array(3);
        foreach ($friends_results as $friends_result) {
            $status = $friends_result['status'];
            if ($status == 1) { //personal
                $friends_array[] = 1;
                //$friends_array[] = 3;
            } else if ($status == 2) { //professional
                $friends_array[] = 2;
               // $friends_array[] = 3;
        }
        } 
        $friend_unique_array = array_unique($friends_array); //both
        $allow_privacy_setting = array_unique(array_merge($allow_other_user_wall_privacy_setting, $allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $friend_unique_array));
      //  echo "<pre>"; print_r($allow_privacy_setting); exit;
        return $allow_privacy_setting;
    }
    
    /**
     * Share the Items as Post
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postShareitemsAction(Request $request)
    {
        $this->__createLog('[Entering in Dashboard\DashboardManagerBundle\Controller\PostController->Shareitems(Request)]');
        $data = array();
        $required_parameter = array('user_id', 'to_id','post_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Registersellers] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $object_info = (object)$de_serialize;
        $allow_personal_friend_privacy_setting = array(); //check for privacy setting value for personal friend(personal and public) post
        $allow_professional_friend_privacy_setting = array();//check for privacy setting value for professional friend(professional and public) post
        $allow_self_privacy_setting = array('1', '2', '3'); //check for privacy setting value for self post
        $allow_other_user_wall_privacy_setting = array('3');
        $object_info->link_type = (isset($object_info->link_type)) ? $object_info->link_type : 1;
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
         if (!in_array(Utility::getLowerCaseString($object_info->share_type), $this->allowed_share)) {
                $resp_data = new Resp(Msg::getMessage(1129)->getCode(), Msg::getMessage(1129)->getMessage(), $data); //INVALID_SHARE_TYPE
                $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\PostController->Shareitems] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }  
         if (!in_array(Utility::getLowerCaseString($object_info->object_type), $this->allowed_object_type)) {
                $resp_data = new Resp(Msg::getMessage(1130)->getCode(), Msg::getMessage(1130)->getMessage(), $data); //INVALID_OBJECT_TYPE
                $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\PostController->Shareitems] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            } 
        $object_info->object_type = Utility::getLowerCaseString($object_info->object_type);
        $object_info->share_type = Utility::getLowerCaseString($object_info->share_type);
        // if user post on his own wall or Dashboard
        if ($object_info->user_id == $object_info->to_id) {
            $allow_privacy_setting = $allow_self_privacy_setting;
        } else { //if user post on other user wall
            $allow_privacy_setting = $this->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $object_info->user_id, $object_info->to_id);
        }
        $user_id = $object_info->user_id;
        $post_type = $object_info->post_type;
        $object_info->post_id = (isset($object_info->post_id) ? $object_info->post_id : '');
        $object_info->youtube_url = (isset($object_info->youtube_url) ? $object_info->youtube_url : '');
        $object_info->title = (isset($object_info->title) ? $object_info->title : '' );
        $object_info->description = (isset($object_info->description) ? $object_info->description : '' );
        // by default every post will be public in nature
        $object_info->privacy_setting = (isset($object_info->privacy_setting) ? $object_info->privacy_setting : 3);

        if (isset($object_info->tagged_friends)) {
            if (trim($object_info->tagged_friends)) {
                $object_info->tagged_friends = explode(',', $object_info->tagged_friends);
            } else {
                $object_info->tagged_friends = array();
            }
        } else {
            $object_info->tagged_friends = array();
        }
        //check if post get published
        if ($object_info->post_type == 1) {
            if (!in_array($object_info->privacy_setting, $allow_privacy_setting)) {
                $resp_data = new Resp(Msg::getMessage(153)->getCode(), Msg::getMessage(153)->getMessage(), $data); //INVALID_PRIVACY_SETTING
                $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\PostController->Shareitems] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        $data1 = '';
        if ($sender_user == '') {
            $data1 = "USER_ID_IS_INVALID";
        }
        if (!empty($data1)) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        $time = new \DateTime("now");
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        $post_data = $this->sharePostObject($object_info, $sender_user); //finding the post object.
        $postService = $this->container->get('post_detail.service');
            //update in notification table / send email
            if (count($object_info->tagged_friends)) {
                if ($object_info->post_id) {
                    $fid = array_diff($object_info->tagged_friends, $tagged_friends);
                } else {
                    $fid = $object_info->tagged_friends;
                }
                if (count($fid)) {

                    $msgtype = 'TAGGED_IN_POST';
                    $msg = 'tagging';
                    
                    $email_template_service = $this->container->get('email_template.service'); //email template service.
                    
                    $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
                    $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

                    $sender = $postService->getUserData($user_id);
                    $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                    $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $post_data['id'], true, true, $sender_name);
                    $receivers = $postService->getUserData($fid, true);
                    $receiversByLang = $postService->getUsersByLanguage($receivers);

                    foreach ($receiversByLang as $lang=>$receivers){
                        $locale = $lang===0 ? $this->container->getParameter('locale') : $lang;
                        $language_const_array = $this->container->getParameter($locale);
                        $mail_text = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_MAIL_TEXT'], ucwords($sender_name));
                        $href = $angular_app_hostname . 'post/' . $post_data['id']; //href for friend profile
                        $bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                        $subject = sprintf($language_const_array['YOU_GOT_TAGGED_IN_POST'], ucwords($sender_name));
                        $mail_body = sprintf($language_const_array['FRIEND_TAGGING_IN_POST_BODY'], ucwords($sender_name));
                        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                    }
                    
                    
                }
            }
            $postService->sendPostNotificationEmail($post_data, 'dashboard', true, true);
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $post_data); //SUCCESS
            $this->__createLog('Exiting from [Dashboard\DashboardManagerBundle\Controller\PostController->Shareitems] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
    }
    
    /**
     * Finding the post object.save the post and send post object.
     * @param type $post_id
     * @return array $postdata
     */
    public function sharePostObject($object_data, $sender_user) {
        //code for responding the current post data..
        $post_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_id = $object_data->post_id;
        $time = new \DateTime('now');
        $post = new DashboardPost();
        $post->setUserId($object_data->user_id);
        $post->setToId($object_data->to_id); //assign the to id(current user or friend id)
        $post->setTitle($object_data->title);
        $post->setDescription($object_data->description);
        $post->setLinkType($object_data->link_type);
        $post->setTaggedFriends($object_data->tagged_friends);
        $post->setCreatedDate($time);
        $post->setIsActive(1);
        $post->setprivacySetting($object_data->privacy_setting);
        $post->setShareType($object_data->share_type);
        $post->setContentShare($object_data->content_share);
        $post->setShareObjectId($object_data->object_id);
        $post->setShareObjectType($object_data->object_type);
        $dm->persist($post);
        $dm->flush();

        //update ACL for a user
        $this->updateAclAction($sender_user, $post);

        $sender_user_info = array();
        $reciver_user_info = array();
        $user_service = $this->get('user_object.service');

        $post_id = $post->getId();
        $post_user_id = $post->getUserId(); //sender 

        $post_to_id = $post->getToId(); //receiver
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findBy(array('post_id' => $post_id));

        $sender_user_info = $user_service->UserObjectService($post_user_id); //sender user object
        $reciver_user_info = $user_service->UserObjectService($post_to_id);  //receiver user object
        //code for user active profile check
        $post_media_result = array();
        if ($post_media) {
            foreach ($post_media as $post_media_data) {
                $post_media_id = $post_media_data->getId();
                $post_media_type = $post_media_data->getType();
                $post_media_name = $post_media_data->getMediaName();
                $post_media_status = $post_media_data->getMediaStatus();
                $post_media_is_featured = $post_media_data->getIsFeatured();
                $post_media_created_at = $post_media_data->getCreatedDate();
                $post_image_type = $post_media_data->getImageType();
                if ($post_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                    $post_media_link = $post_media_data->getPath();
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

        //finding the comments start.
        $comments = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id), array('created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
        $comments = array_reverse($comments);
        $comment_data = array();
        $comment_user_info = array();
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getUserId();

                $comment_user_info = $user_service->UserObjectService($comment_user_id);
                $comment_media = $dm->getRepository('DashboardManagerBundle:DashboardCommentsMedia')
                        ->findBy(array('comment_id' => $comment_id));
                $comment_media_result = array();
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
                        'image_type' => $comment_image_type
                    );
                }

                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'user_id' => $comment->getUserId(),
                    'comment_user_info' => $comment_user_info,
                    'status' => $comment->getIsActive(),
                    'create_date' => $comment->getCreatedAt(),
                    'comment_media_info' => $comment_media_result);
            }
            
            $comments_count = count($dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->findBy(array('post_id' => $post_id, 'is_active' => 1)));
            
        } else {
            $comments_count = 0;
        }

        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $post->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object

        /** fetch rating of current user * */
        $current_rate = 0;
        $is_rated = false;
        $rate_data_obj = $post->getRate();
        if (count($rate_data_obj) > 0) {
            foreach ($rate_data_obj as $rate) {
                if ($rate->getUserId() == $post_user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
        }
        //finding the comments end.
        //prepare the data.
        $post_data = array(
            'id' => $post_id,
            'user_id' => $post->getUserId(),
            'to_id' => $post->getToId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'link_type' => $post->getLinkType(),
            'is_active' => $post->getIsActive(),
            'created_at' => $post->getCreatedDate(),
            'avg_rate' => round($post->getAvgRating(), 1),
            'no_of_votes' => $post->getVoteCount(),
            'current_user_rate' => $current_rate,
            'is_rated' => $is_rated,
            'user_info' => $sender_user_info,
            'reciver_user_info' => $reciver_user_info,
            'privacy_setting' => $post->getPrivacySetting(),
            'media_info' => $post_media_result,
            'comments' => $comment_data,
            'tagged_friends_info' => $tagged_friends_info,
            'comment_count' => $comments_count,
            'share_type'=> $post->getShareType(),
            'content_share'=> $post->getContentShare(),
            'object_type'=> $post->getShareObjectType(),
            'object_id'=> $post->getShareObjectId(),
        );
        //code end for responding the current post data.
        return $post_data;
    }
    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.share_post_log');
        $applane_service->writeAllLogs($handler, $monolog_req,$monolog_response);
        return true;
    }

}
