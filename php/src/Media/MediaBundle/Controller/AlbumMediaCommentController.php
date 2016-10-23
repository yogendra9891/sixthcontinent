<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Media\MediaBundle\Document\UserAlbum;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Media\MediaBundle\Document\AlbumMediaComment;
use Media\MediaBundle\Document\PhotoCommentMedia;

class AlbumMediaCommentController extends Controller
{

    protected $miss_param = '';
    protected $image_width = 100;
    protected $media_comment_limit = 4;
    protected $media_comment_offset = 0;
    protected $media_comment_thumb_image_width = 654;
    protected $media_comment_thumb_image_height = 360;
    protected $media_comment_original_resize_image_width = 910;
    protected $media_comment_original_resize_image_height = 910;
    protected $user_album_media_comment_msg = "COMMENT";
    protected $user_album_media_comment_type = "USER_ALBUM_MEDIA_COMMENT";
    protected $user_album_media_comment_on_commented_type = "USER_ALBUM_MEDIA_COMMENT_ON_COMMENTED";
    protected $group_media_album_path_thumb = '/uploads/groups/thumb/';
    protected $group_media_album_path = '/uploads/groups/original/';
    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $dashboardpost_media_comment_thumb_image_width = 654;
    protected $dashboardpost_media__comment_thumb_image_height = 360;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    protected $dashboardpost_media_comment = "COMMENT";
    protected $dashboardpost_media_comment_type = "DASHBOARD_POST_MEDIA_COMMENT";
    protected $dashboardpost_media_comment_on_commented_type = "DASHBOARD_POST_MEDIA_COMMENT_ON_COMMENTED";
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
     *
     * @param type $req_obj
     * @return type
     */
    public function decodeDataAction($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Function to retrieve current applications base URI
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        //return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }


   /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename)
    {
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
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $full_path     = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
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
                        ($_FILES['commentfile']['type'][$key] == 'image/jpeg' ||
                        $_FILES['commentfile']['type'][$key] == 'image/jpg' ||
                        $_FILES['commentfile']['type'][$key] == 'image/gif' ||
                        $_FILES['commentfile']['type'][$key] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }

    /**
     *
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeDataAction($content);

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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        $container = NManagerNotificationBundle::getContainer();
        return $container->get('fos_user.user_manager');
        // return $this->container->get('fos_user.user_manager');
    }


    /**
     * creating the ACL 1
     * for the entity for a user
     * @param object $sender_user
     * @param object $dashboard_comment_entity
     * @return none
     */
    public function updateAclAction($sender_user, $dashboard_comment_entity) {
        $container = NManagerNotificationBundle::getContainer();
        $aclProvider = $container->get('security.acl.provider');

       // $aclProvider = $this->get('security.acl.provider');
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
     * Call api/createalbumcomments action
     * @param Request $request
     * @return array
     */

    public function postCreatealbummediacommentsAction(Request $request) {

        $data = array();
//
//        //Code start for getting the request
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeObjectAction($freq_obj);
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
        //Code end for getting the request

        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);
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

         if (isset($_FILES['commentfile'])) {
             $required_parameter = array('user_id','item_id','parent_id','album_type','item_type','comment_type');
         } else {
             $required_parameter = array('user_id','item_id','parent_id','album_type','item_type','comment_media_id','comment_type');
         }


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

        /** check for album type **/
        $type_array = array('shop','club','user','dashboard_post','club_wall_post', 'shop_wall_post');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }

        switch ($album_type) {

            case 'user':
                $this->createUserAlbumMediaComment($object_info);
                break;
            case 'club':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->createClubAlbumMediaComment($object_info);
                break;
            case 'shop':
                //get store album comment class object
                $store_album_comment = new StoreAlbumMediaCommentController();
                $store_album_comment->createStoreAlbumMediaComment($object_info);
                break;
            case 'dashboard_post':
                $this->createdashboardPostMediaComment($object_info);
                break;
            case 'club_wall_post':
                $club_wall_post_comment = new ClubAlbumMediaCommentController();
                $club_wall_post_comment->createClubWallPostMediaComment($object_info);
                break;
            case 'shop_wall_post':
                $shop_wall_post_comment = new StoreAlbumMediaCommentController();
                $shop_wall_post_comment->createShopWallPostMediaComment($object_info);
                break;
        }



    }

    /*
     * create comment for user album
     * @param type $object_info
     */
    public function createUserAlbumMediaComment($object_info) {

        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->parent_id;
        $media_id = $object_info->item_id;
        $item_type = $object_info->item_type;
        $comment_type = $object_info->comment_type;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $comment_id = (isset($object_info->comment_id) ? $object_info->comment_id : '');
        $time = new \DateTime("now");
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();

        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

       /** check for user album **/
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $album = $dm->getRepository('MediaMediaBundle:UserAlbum')
                    ->find($album_id);

        if (!$album) {
            $res =  array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $media = $dm->getRepository('MediaMediaBundle:UserMedia')
                    ->find($media_id);

        if (!$media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $album_owner_id = $album->getUserId();

        if ($comment_type == 0) {
            if ($comment_id == '') {

                $user_album_comments = new AlbumMediaComment();
                $user_album_comments->setAlbumId($album_id);
                $user_album_comments->setCommentAuthor($user_id);
                $user_album_comments->setCommentText($comment_body);
                $user_album_comments->setCommentCreatedAt($time);
                $user_album_comments->setCommentUpdatedAt($time);
                $user_album_comments->setStatus(0); // 0=>disabled, 1=>enabled
                $user_album_comments->setTagging($tagging);
                $media->addComment($user_album_comments);

                $dm->persist($media); //storing the comment data.
                $dm->flush();

                $comment_id = $user_album_comments->getId(); //getting the last inserted id of comments.
                //update ACL for a user
                $this->updateAclAction($sender_user, $user_album_comments);
            }

            // echo $comment_id;
            $mediaComments = $media->getComment();

            $commentExists = false;
            foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comment_res = $comment;
                    $commentExists = true;
                }
            }

            if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
            }

            $current_comment_media = array();
            $useralbum_media_comment_media_id = 0;
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
                    $useralbum_comment_thumb_image_width  = $this->media_comment_thumb_image_width;
                    $useralbum_comment_thumb_image_height = $this->media_comment_thumb_image_height;

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
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$useralbum_comment_thumb_image_width,$useralbum_comment_thumb_image_height);


                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $useralbum_comment_media = new PhotoCommentMedia();

                        $useralbum_comment_media->setParentId($album_id);
                        $useralbum_comment_media->setItemId($media_id);
                        $useralbum_comment_media->setCommentId($comment_id);
                        $useralbum_comment_media->setItemType($item_type);
                        $useralbum_comment_media->setMediaName($file_name);
                        $useralbum_comment_media->setType($actual_media_type);
                        $useralbum_comment_media->setCreatedDate($time);
                        $useralbum_comment_media->setPath('');
                        $useralbum_comment_media->setMediaStatus(0);
                        $useralbum_comment_media->setImageType($image_type);

                        $dm->persist($useralbum_comment_media);
                        $dm->flush();

                        //get the useralbum comment media id
                        $useralbum_media_comment_media_id = $useralbum_comment_media->getId();

                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path') .$media_id . '/'. $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path') .$media_id . '/' . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path_thumb') .$media_id . '/' . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path_thumb_crop') .$media_id . '/' . $comment_id . "/";
                        $s3_post_media_path = $this->container->getParameter('s3_media_comment_media_path'). $media_id . '/' . $comment_id ;
                        $s3_post_media_thumb_path = $this->container->getParameter('s3_media_comment_media_thumb_path') .$media_id . '/' . $comment_id;
                        $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'useralbum_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);

                    }

                }
            }

            // get all media ids uploaded on specific comment
            $comment_media_ids = $dm->getRepository('MediaMediaBundle:UserMedia')->getCommentedMedias($media_id, $comment_id);

            $comment_media_ids[] = $useralbum_media_comment_media_id;
            $comment_res->setMedias($comment_media_ids);

            $dm->persist($comment_res);
            $dm->flush();

            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
            $comment_image_type = $image_type;
            $comment_media_name = $file_name;

            $comment_media_link  = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') .$media_id . '/' . $comment_id . '/' . $comment_media_name;
            $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') .$media_id . '/' . $comment_id . '/' . $comment_media_name;

            //sending the current media and post data.
            $data = array(
                'id' => $comment_id,
                'media_id' => $useralbum_media_comment_media_id,
               // 'media_ids'=>$comment_media_ids,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' =>$comment_image_type
            );

            $media_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_array);
            exit;
        } else {

            $mediaComments = $media->getComment();
            $commentExists = false;
            foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comment_res = $comment;
                    $commentExists = true;
                }
            }

            $UserAlbumMediaCommentId = isset($object_info->comment_id)? $object_info->comment_id : "" ;
            $media_id = $object_info->item_id;
            $album_id = $object_info->parent_id;

            // $comment_media_ids = $comment_res->getMedias();
           // $comment_media_ids[] = $useralbum_media_comment_media_id;

            if($commentExists){

                $comment_res->setCommentText($comment_body);
                $comment_res->setTagging($tagging);
                $comment_res->setCommentUpdatedAt($time);
                $comment_res->setStatus(1);

                $dm->persist($comment_res); //storing the comment data.
                $dm->flush();

                $comment_media_ids = $comment_res->getMedias();
            } else {
                $media_comment = new AlbumMediaComment();

                $media_comment->setCommentAuthor($user_id);
                $media_comment->setAlbumId($album_id);
                $media_comment->setMediaId($media_id);
                $media_comment->setCommentText($comment_body);
                $media_comment->setCommentCreatedAt($time);
                $media_comment->setCommentUpdatedAt($time);
                $media_comment->setTagging($tagging);
                $media_comment->setStatus(1);

                $media->addComment($media_comment);

                $dm->persist($media); //storing the comment data.
                $dm->flush();

                $comment_id = $media_comment->getId();
                $comment_media_ids = array();

            }

            //calling rating notification service
            $notification_obj = $this->get('post_detail.service');
            //publish comment media
            if (!empty($comment_media_ids)) {
                $media_update_status = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                          ->publishCommentMediaImage($comment_media_ids);
            }
            if ($UserAlbumMediaCommentId) {

                $this->sendCommentNotifications($user_id, $album_owner_id, $media_id, $object_info->comment_id, $album_id, $tagging);

                $comment_data = $this->getCommentObject($object_info); //finding the post object.
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {
                $this->sendCommentNotifications($user_id, $album_owner_id, $media_id, $comment_id, $album_id, $tagging);
                $comment_data = $this->getCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            }
        }

    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_info
     * @return array $commentdata
     */
    public function getCommentObject($object_info) {
        //code for responding the current post data..

        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->item_id;

        $time = new \DateTime('now');

        $media = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);

        if (!$media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $mediaComments = $media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        $sender_user_info = array();
        $user_service = $this->get('user_object.service');

        $comment_user_id = $comment_res->getCommentAuthor(); //Id of persona who has commented for this album

        $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')->findBy(array('comment_id' => $comment_id, 'media_status' => 1));

        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object

        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getItemType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getMediaStatus();
                $comment_media_created_at = $comment_media_data->getCreatedDate();
                $comment_image_type = $comment_media_data->getImageType();

                $comment_media_link =  $this->getS3BaseUri().$this->container->getParameter('media_comment_media_path') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri().$this->container->getParameter('media_comment_media_path_thumb') . $media_id . '/' .$comment_id . '/' . $comment_media_name;


                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }

        $data = array(
            'id' => $comment_id,
            'user_album_id' => $object_info->parent_id,
            'comment_text' => $comment_res->getCommentText(),
            'user_id' => $comment_res->getCommentAuthor(),
            'status' => $comment_res->getStatus(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment_res->getCommentCreatedAt(),
            'album_type'=> $comment_res->getAlbumType(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false,
            'tagging'=>$comment_res->getTagging()
        );
        $commentdata = $data;

        return $commentdata;
    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_info
     * @return array $commentdata
     */
    public function getCommentWithoutImageObject($object_info,$comment_id) {
        //code for responding the current post data..
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $media_id = $object_info->item_id;

        $time = new \DateTime('now');

        $media = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);

        if (!$media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $comment_data);
            echo json_encode($res);
            exit;
        }

        $mediaComments = $media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        $sender_user_info = array();
        $user_service = $this->get('user_object.service');

        $comment_id = $comment_res->getId();
        $comment_user_id = $comment_res->getCommentAuthor(); //sender

        $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')->findBy(array('comment_id' => $comment_id, 'media_status' => 1));

        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getItemType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getMediaStatus();
                $comment_media_created_at = $comment_media_data->getCreatedDate();
                $comment_image_type = $comment_media_data->getImageType();

                $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri(). $this->container->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'user_album_id' => $object_info->parent_id,
            'comment_text' => $comment_res->getCommentText(),
            'user_id' => $comment_res->getCommentAuthor(),
            'status' => $comment_res->getStatus(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment_res->getCommentCreatedAt(),
            'album_type'=> $comment_res->getAlbumType(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false,
            'tagging'=>$comment_res->getTagging()
        );
        $commentdata = $data;
        return $commentdata;
    }


    /**
    * Get media details
    * @param request object
    * @return json string
    */
    public function postMediadetailsAction(Request $request)
    {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for ogetting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('parent_id','media_id','user_id','album_type');
       //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        /** check for album type **/
        $type_array = array('shop','club','user','dashboardpost_media');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }
        switch ($album_type) {
            case 'user':
                $this->UserAlbumMediaDetails($object_info);
                break;
            case 'club':
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->ClubAlbumMediaDetails($object_info);
                break;
            case 'shop':

                $storeAlbumMediaComment = new StoreAlbumMediaCommentController();
                $storeAlbumMediaComment->ShopAlbumMediaDetails($object_info);
                break;
        }
    }

    /**
    * Get media details
    * @param request object
    * @return json string
    */
    public function postSinglephotomediadetailsAction(Request $request)
    {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        if(!key_exists("album_id", $de_serialize)){
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_ALBUM_ID', 'data' => $data);
        }else{
            $de_serialize["parent_id"] = $de_serialize["album_id"];
        }
        //Code end for ogetting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('media_id','user_id','album_type');
       //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        /** check for album type **/
        $type_array = array('shop','club','user','dashboard_post','club_wall_post', 'shop_wall_post');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }
        switch ($album_type) {
            case 'user':
                $this->UserAlbumMediaDetails($object_info);
                break;
            case 'club':
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->ClubAlbumMediaDetails($object_info);
                break;
            case 'shop':
                $storeAlbumMediaComment = new StoreAlbumMediaCommentController();
                $storeAlbumMediaComment->ShopAlbumMediaDetails($object_info);
                break;
            case 'dashboard_post':
                $this->dashboardPostMediaDetails($object_info);
                break;
            case 'club_wall_post':
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->clubwallPostMediaDetails($object_info);
                break;
            case 'shop_wall_post':
                $shop_post_media_comment = new StoreAlbumMediaCommentController();
                $shop_post_media_comment->storePostMediaDetails($object_info);
                break;
        }
    }

    /**
    * user Album Media details
    * @param request object
    * @return json string
    */
    public function UserAlbumMediaDetails($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $album_id = $object_info->parent_id;


        //finding the embedded comment data with
        $mediaComments = $dm->getRepository('MediaMediaBundle:UserMedia')->getCommentsOfMedia($media_id, 5, true);
        $media_res = $mediaComments['result'];
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        $users_array = array();
        $mediaOwner = $media_res->getUserid();
        $users_array[] = $mediaOwner;

        if (is_array($media_res->getTaggedFriends())) {
            $tagged_user_ids = $media_res->getTaggedFriends();
        } else {
            $tagged_friend = $media_res->getTaggedFriends();
            if (trim($tagged_friend)) {
                $tagged_user_ids = explode(',', $tagged_friend);
            } else {
                $tagged_user_ids = array();
            }
        }

        $comments = $media_res->getComment();

        //comments user ids
        $comment_user_ids = array();
        $comment_ids = array();

        foreach($comments as $comment){
           $comment_user_ids[] = $comment->getCommentAuthor();
           $comment_ids[] = $comment->getId();
        }

        //finding the comments media.
        $comments_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                    ->findCommentMedia($comment_ids);

        $users_array[] = $comment_user_ids;
        $users_array[] = $tagged_user_ids;

        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['content_type'] = $media_res->getContentType();
        $media_info['status'] = $media_res->getEnabled();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['created_at'] = $media_res->getCreatedAt();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->container->getParameter('user_album_media_path') . $mediaOwner . '/' . $album_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->container->getParameter('user_album_media_path_thumb') . $mediaOwner . '/' .$album_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $media_res->getUserId();
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments as $comment) {
            if($comment->getStatus() != 0 ){
                $comment_id = $comment->getId();
                $comment_media = $comment->getMedias();
                $comment_media = isset($comment_media)? $comment_media : array() ;
                $comment_txt = $comment->getCommentText();
                $status = $comment->getStatus();
                $comment_author_id = $comment->getCommentAuthor();
                $comment_created_at = $comment->getCommentCreatedAt();
                $comment_author_info = isset($users_object_array[$comment_author_id])? $users_object_array[$comment_author_id] : array() ;
                $comment_media_result = array();
                foreach ($comments_media as $comment_media_data) {
                    if ($comment_media_data->getCommentId() == $comment_id) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getMediaStatus();
                        $comment_media_created_at = $comment_media_data->getCreatedDate();
                        $comment_image_type = $comment_media_data->getImageType();
                        $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'create_date' => $comment_media_created_at,
                            'image_type' => $comment_image_type
                        );
                    }
                }
                $current_rate = 0;
                $is_rated = false;
                foreach ($comment->getRate() as $rate) {
                    if ($rate->getUserId() == $user_id) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                $comments_info[] = array(
                    'id' => $comment_id,
                    'comment_text' => $comment_txt,
                    'comment_author_id' => $comment_author_id,
                    'status' => $status,
                    'create_date' => $comment_created_at,
                    'comment_user_info' => $comment_author_info,
                    'comment_media_info' => $comment_media_result,
                    'avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'tagging'=>$comment->getTagging()
                );
            }
            $i++;

        }
        $comment_count = $i;
        $tagged_friends_info = array();
        if (count($tagged_user_ids)) {
            foreach ($tagged_user_ids as $tagged_user_id) {
                $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
            }
        }
        $current_rate = 0;
        $is_rated = false;
        foreach ($media_res->getRate() as $rate) {
            if ($rate->getUserId() == $user_id) {
                $current_rate = $rate->getRate();
                $is_rated = true;
                break;
            }
        }

        $total_comment = $mediaComments['size'];
        $data['comments'] = $comments_info;
        $data['tagged_friends_info'] = $tagged_friends_info;
        // $data['comment_count'] = $comment_count;
        $data['count'] = $total_comment;
        $data['avg_rate'] = round($media_res->getAvgRating(), 1);
        $data['no_of_votes'] = (int) $media_res->getVoteCount();
        $data['current_user_rate'] = $current_rate;
        $data['is_rated'] = $is_rated;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }


    /**
    * Finding list of comments
    * @param request object
    * @return json string
    */
    public function postAlbummediacommentlistsAction(Request $request)
    {
        $data = array();

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

        $required_parameter = array('parent_id','album_type','media_id','user_id','owner_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        /** check for album type **/
        $type_array = array('shop','club','user', 'shop_wall_post');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }

        switch ($album_type) {

            case 'user':
                $this->listUserAlbummediaComment($object_info);
                break;
            case 'club':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->listClubAlbumMediaComment($object_info);
                break;
            case 'shop':
                //get store album comment class object
                $store_album_comment = new StoreAlbumMediaCommentController();
                $store_album_comment->listStoreAlbumMediaComment($object_info);
                break;
            case 'shop_wall_post':
                $store_album_media_comment = new StoreAlbumMediaCommentController();
                $store_album_media_comment->listStorePostMediaComment($object_info);
                break;

        }

    }



    /**
    * Finding list of comments
    * @param request object
    * @return json string
    */
    public function listUserAlbummediaComment($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $album_id = $object_info->parent_id;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;


        //finding the embedded comment data with
        $mediaComments = $dm->getRepository('MediaMediaBundle:UserMedia')
                          ->getCommentsOfMedia($media_id,$limit, true, $offset);
        $media_res = $mediaComments['result'];
        $totalComment = $mediaComments['size'];
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $comments = $media_res->getComment();
        $users_array = array();
        $users_array[] = $media_res->getUserId();

        if (is_array($media_res->getTaggedFriends())) {
            $tagged_user_ids = $media_res->getTaggedFriends();
        } else {
            $tagged_friend = $media_res->getTaggedFriends();
            if (trim($tagged_friend)) {
                $tagged_user_ids = explode(',', $tagged_friend);
            } else {
                $tagged_user_ids = array();
            }
        }

        //comments user ids
        $comment_user_ids = array();
        $comment_ids = array();

        foreach($comments as $comment){
           $comment_user_ids[] = $comment->getCommentAuthor();
           $comment_ids[] = $comment->getId();
        }

        //finding the comments media.
        $comments_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                    ->findCommentMedia($comment_ids);

        $users_array[] = $comment_user_ids;
        $users_array[] = $tagged_user_ids;

        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['content_type'] = $media_res->getContentType();
        $media_info['status'] = $media_res->getEnabled();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['created_at'] = $media_res->getCreatedAt();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->container->getParameter('user_album_media_path') . $friend_id . '/' . $album_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->container->getParameter('user_album_media_path_thumb') . $friend_id . '/' .$album_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $media_res->getUserId();
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments as $comment) {
            if($comment->getStatus() != 0 ){
                $comment_id = $comment->getId();
                $comment_media = $comment->getMedias();
                $comment_media = isset($comment_media)? $comment_media : array() ;
                $comment_txt = $comment->getCommentText();
                $status = $comment->getStatus();
                $comment_author_id = $comment->getCommentAuthor();
                $comment_created_at = $comment->getCommentCreatedAt();
                $comment_author_info = isset($users_object_array[$comment_author_id])? $users_object_array[$comment_author_id] : array() ;
                $comment_media_result = array();
                foreach ($comments_media as $comment_media_data) {
                    if ($comment_media_data->getCommentId() == $comment_id) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getMediaStatus();
                        $comment_media_created_at = $comment_media_data->getCreatedDate();
                        $comment_image_type = $comment_media_data->getImageType();
                        $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'create_date' => $comment_media_created_at,
                            'image_type' => $comment_image_type
                        );
                    }
                }
                $current_rate = 0;
                $is_rated = false;
                foreach ($comment->getRate() as $rate) {
                    if ($rate->getUserId() == $user_id) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                $comments_info[] = array(
                    'id' => $comment_id,
                    'comment_text' => $comment_txt,
                    'comment_author_id' => $comment_author_id,
                    'status' => $status,
                    'create_date' => $comment_created_at,
                    'comment_user_info' => $comment_author_info,
                    'comment_media_info' => $comment_media_result,
                    'avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'tagging'=>$comment->getTagging()
                );
            }
            $i++;

        }
        $comment_count = $i;
        $tagged_friends_info = array();
        if (count($tagged_user_ids)) {
            foreach ($tagged_user_ids as $tagged_user_id) {
                $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
            }
        }
        $current_rate = 0;
        $is_rated = false;
        foreach ($media_res->getRate() as $rate) {
            if ($rate->getUserId() == $user_id) {
                $current_rate = $rate->getRate();
                $is_rated = true;
                break;
            }
        }
        $data['comments'] = $comments_info;
        $data['tagged_friends_info'] = $tagged_friends_info;
        $data['count'] = $totalComment;
        $data['avg_rate'] = round($media_res->getAvgRating(), 1);
        $data['no_of_votes'] = (int) $media_res->getVoteCount();
        $data['current_user_rate'] = $current_rate;
        $data['is_rated'] = $is_rated;
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
     * Delete comment with media
     * @param request object
     * @return json string
     */

    public function postDeletemediacommentsAction(Request $request) {

        $data = array();

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

        $required_parameter = array('user_id','comment_id','type','media_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        /** check for album type **/
        $type_array = array('shop','club','user','dashboard_post','club_wall_post','shop_wall_post');
        $album_type = $object_info->type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }

        switch ($album_type) {

            case 'user':
                $this->deleteUserAlbumMediaComment($object_info);
                break;
            case 'club':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->deleteClubAlbumMediaComment($object_info);
                break;
            case 'shop':
                //get store album comment class object
                $store_album_comment = new StoreAlbumMediaCommentController();
                $store_album_comment->deleteStoreAlbumMediaComment($object_info);
                break;
            case 'dashboard_post':
                $this->deleteDashboardPostMediaComment($object_info);
                break;
            case 'club_wall_post':
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->deleteClubWallPostMediaComment($object_info);
            case 'shop_wall_post':
                //get store album comment class object
                $store_album_comment = new StoreAlbumMediaCommentController();
                $store_album_comment->deleteStorePostMediaComment($object_info);
                break;
        }
    }

    /**
     * Delete UserAlbum comment  with media
     * @param request object
     * @return json string
     */

    public function deleteUserAlbumMediaComment($object_info){

        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->media_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //finding the post data
        $album_media = $dm->getRepository('MediaMediaBundle:UserMedia')
                         ->findOneBy(array('id' => $media_id, 'enabled' => 1));

        if (!$album_media) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;

        }

       $mediaComments = $album_media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }

        if ($commentExists) {
            $album_media->removeComment($comment_res); // Remove comment ,
            //call it on DashboardPostMedia object (due to embeded document ortherwise simple $dm->remove($comment_res))
            $dm->flush();
            // Remove media of this comment
            $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                ->removeDashboardPostCommentsMedia($object_info->comment_id);
            if ($comment_media) {
               $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
               echo json_encode($res_p);
               exit();
            }
        }
    }


    /**
     * Delete media of comments
     * @param request object
     * @return json string
     */

    public function postDeletecommentmediasAction(Request $request) {
        $data = array();

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

        $required_parameter = array('user_id','comment_id','album_type','item_id','comment_media_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        /** check for album type **/
        $type_array = array('shop','club','user','dashboard_post','club_wall_post');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }

        switch ($album_type) {

            case 'user':
                $this->deleteUserAlbumCommentMedia($object_info);
                break;
            case 'club':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->deleteClubAlbumCommentMedia($object_info);
                break;
            case 'shop':
                //get store album comment class object
                $store_album_comment = new StoreAlbumCommentController();
                $store_album_comment->deleteStoreAlbumCommentMedia($object_info);
                break;
            case 'dashboard_post':
                //get store album comment class object
                $this->deleteDashboardPostMediaCommentMedia($object_info);
                break;
            case 'club_wall_post':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->deleteClubWallPostMediaCommentMedia($object_info);
        }
    }
    /**
     * Call api/createalbumcomments action
     * @param Request $request
     * @return array
     */
    public function postEditmediacommentsAction(Request $request) {

        $data = array();

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

         if (isset($_FILES['commentfile'])) {
             $required_parameter = array('user_id','item_id','album_type');
         } else {
             $required_parameter = array('user_id','item_id','album_type','body','comment_id');
         }


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

        /** check for album type **/
        $type_array = array('shop','club','user','dashboard_post','club_wall_post', 'shop_wall_post');
        $album_type = $object_info->album_type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data);
        }

        switch ($album_type) {

            case 'user':
                $this->editUserAlbumMediaComment($object_info);
                break;
            case 'club':
                //get club album comment class object
                $club_album_comment = new ClubAlbumMediaCommentController();
                $club_album_comment->editClubAlbumMediaComment($object_info);
                break;
            case 'shop':
                //get store album comment class object
                $store_album_comment = new StoreAlbumMediaCommentController();
                $store_album_comment->editStoreAlbumMediaComment($object_info);
                break;
            case 'dashboard_post':
                $this->editDashboardPostMediaComment($object_info);
                break;
            case 'club_wall_post':
                $club_wall_post_comment = new ClubAlbumMediaCommentController();
                $club_wall_post_comment->editClubWallPostMediaComment($object_info);
                break;
            case 'shop_wall_post':
                $shop_wall_post_comment = new StoreAlbumMediaCommentController();
                $shop_wall_post_comment->editStoreWallPostMediaComment($object_info);
                break;
        }
    }

    /**
     * Update comment of a user album.........
     * @param request object
     * @return json string
     */

    public function editUserAlbumMediaComment($object_info){

        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->parent_id;
        $media_id = $object_info->item_id;
       // $item_type = $object_info->item_type;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $comment_id = (isset($object_info->comment_id) ? $object_info->comment_id : '');
        $time = new \DateTime("now");
        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album = $dm->getRepository('MediaMediaBundle:UserAlbum')
                    ->find($album_id);
        if (!$album) {
            $res =  array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        $albumOwner = $album->getUserId();
        //finding the post data
        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        $mediaComments = $media_res->getComment();
        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }

        if ($commentExists) {
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
            //set updated text body
            $comment_res->setCommentText($comment_body);
            $comment_res->setCommentUpdatedAt($time);
            $comment_res->setTagging($tagging);
            $dm->persist($comment_res); //storing the edited comment data.
            $dm->flush();
            $comment_data = $this->getCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
            $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
            if(!empty($newTagging)){
                $email_template_service = $this->container->get('email_template.service');
                $postService = $this->container->get('post_detail.service');
                $link_url = $email_template_service->getPageUrl(array('supportId'=>$albumOwner, 'parentId'=> $album_id, 'mediaId'=>$media_id, 'albumType'=>'user'),'single_image_page');
                $postService->commentTaggingNotifications($newTagging, $user_id, $media_id, $link_url, 'USER_ALBUM_MEDIA', true, array('album_id'=>$album_id), true, array('comment_id'=>$comment_id, 'album_id'=>$album_id));
            }
            echo json_encode($final_array);
            exit;
        }
    }
    /**
     * Get User role for store
     * @param int $comment_id
     * @param int $user_id
     * @return int
     */
    public function userCommentRole($comment_id, $user_id,$media_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //finding the post data
        $album_media = $dm->getRepository('MediaMediaBundle:UserMedia')
                         ->findOneBy(array('id' => $media_id, 'enabled' => 1));

       $comments = $album_media->getComment();
       foreach($comments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
            }
        }

        $aclProvider = $container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($comment_res); //entity

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }

        //Acl Operation
        $um = $container->get('fos_user.user_manager');
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
     * Delete media of comment on user album media
     * @param request object
     * @return json string
     */
    public function deleteUserAlbumCommentMedia($object_info) {
        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->item_id;
        $comment_media_id = $object_info->comment_media_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //finding the post data
        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $mediaComments = $media_res->getComment();
        $commentExists = false;

        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
              // $comments_medias_ids =  $comment->getMedias();
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }
        if ($commentExists) {
           // remove embedded media id
           // first remove  specific media id from media array ,then persists remaing array
           foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comments_medias_ids =  $comment->getMedias();
                    if (in_array($comment_media_id, $comments_medias_ids))
                    {
                        unset($comments_medias_ids[array_search($comment_media_id,$comments_medias_ids)]);
                    }
                    $comment_res = $comment;
                }
            }
            $comment_res->setMedias($comments_medias_ids);
            $dm->persist($comment_res);
            $dm->flush();

            $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                ->find($comment_media_id);
            $dm->remove($comment_media);
            $dm->flush();
            $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_p);
            exit();
        }
    }


    public function sendCommentNotifications($from, $owner, $media_id, $comment_id, $album_id, $tagging){
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $postService = $this->get('post_detail.service');
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
        $link_url = $email_template_service->getPageUrl(array('supportId'=>$owner, 'parentId'=> $album_id, 'mediaId'=>$media_id, 'albumType'=>'user'),'single_image_page');

        $message = $this->user_album_media_comment_msg;
        $ownerMessageType = $this->user_album_media_comment_type;
        $commentAuthorMessageType = $this->user_album_media_comment_on_commented_type;

        $commentedAuthors = $dm->getRepository('MediaMediaBundle:UserMedia')->getCommentedUserIds($media_id);
        $commentedAuthors = array_diff($commentedAuthors, array($from, $owner));
        $uniqueAuthors = array_unique($commentedAuthors);
        $sender = $postService->getUserData($from);
        $senderName = trim(ucwords($sender['first_name']. ' '.$sender['last_name']));
        // web and push notification for photo owner
        if($from!=$owner){
            $postService->sendUserNotifications($from, $owner, $ownerMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('album_id'=>$album_id), 'U', array('comment_id'=>$comment_id, 'album_id'=>$album_id));
            $ownerInfo = $postService->getUserData($owner);

            $locale = !empty($ownerInfo['current_language']) ? $ownerInfo['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
            $subject = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENTED_SUBJECT'],$senderName);
            $mail_link = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENTED_LINK'],$senderName);
            $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['USER_ALBUM_MEDIA_COMMENTED_CLICK_HERE'],$href);
            $bodyTitle = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENTED_BODY'],$senderName);
            // HOTFIX NO NOTIFY MAIL
            //$postService->sendMail(array($ownerInfo), $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'USER_ALBUM_MEDIA_COMMENT_NOTIFICATION');
        }
        // notification for commented authors
        if(!empty($uniqueAuthors)){
            $authors = $postService->getUserData($uniqueAuthors, true);
            $recieverByLanguage = $postService->getUsersByLanguage($authors);
            foreach($recieverByLanguage as $lng=>$receivers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $subject = sprintf($lang_array['USER_ALBUM_MEDIA_ON_COMMENTED_SUBJECT'],$senderName);
                $mail_link = sprintf($lang_array['USER_ALBUM_MEDIA_ON_COMMENTED_LINK'],$senderName);
                $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['USER_ALBUM_MEDIA_ON_COMMENTED_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array['USER_ALBUM_MEDIA_ON_COMMENTED_BODY'],$senderName);
                // HOTFIX NO NOTIFY MAIL
                //$postService->sendMail($receivers, $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'USER_ALBUM_MEDIA_COMMENT_NOTIFICATION');
            }
            $postService->sendUserNotifications($from, $uniqueAuthors, $commentAuthorMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('album_id'=>$album_id), 'U', array('comment_id'=>$comment_id, 'album_id'=>$album_id));
        }

        if(!empty($tagging)){
            $postService->commentTaggingNotifications($tagging, $from, $media_id, $link_url, 'USER_ALBUM_MEDIA', true, array('album_id'=>$album_id), false, array('comment_id'=>$comment_id, 'album_id'=>$album_id));
        }
        return true;
    }
    /*
     * create comment for user album
     * @param type $object_info
     */
    public function createdashboardPostMediaComment($object_info) {

        $data = array();
        $time = new \DateTime("now");
        $comment_user_id = $object_info->user_id;
        $post_id = $object_info->parent_id;
        $comment_type = $object_info->comment_type;
        $item_type = $object_info->item_type;
        $post_media_id = $object_info->item_id;
        $comment_id = $object_info->comment_id;
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
        $post_owner_id = $post->getUserId();
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                         ->find($post_media_id);

        // checking dashboard post media
        if (!$post_media) {
            return array('code' => 310, 'message' => 'DASHBOARD_POST_MEDIA_DOES_NOT_EXITS', 'data' => $data);
        }
        $comment_media_ids = array();
        if($comment_type == 0){
             if ($object_info->comment_id == '') {
                $dashboard_post_media_comments = new  AlbumMediaComment();
                $dashboard_post_media_comments->setAlbumId('');
                $dashboard_post_media_comments->setCommentAuthor($comment_user_id);
                $dashboard_post_media_comments->setCommentText($body);
                $dashboard_post_media_comments->setCommentCreatedAt($time);
                $dashboard_post_media_comments->setCommentUpdatedAt($time);
                $dashboard_post_media_comments->setStatus(0); // 0=>disabled, 1=>enabled
                $dashboard_post_media_comments->setTagging($tagging);
                $post_media->addComment($dashboard_post_media_comments);// save embedded comment data in dashboardpostmedia document
                $dm->persist($dashboard_post_media_comments); //storing the comment data.
                $dm->flush();
                $comment_id = $dashboard_post_media_comments->getId(); //getting the last inserted id of comments.
                //update ACL for a user
                $this->updateAclAction($sender_user, $dashboard_post_media_comments);
             }

            $mediaComments = $post_media->getComment();

            $commentExists = false;
            foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comment_res = $comment;
                    $commentExists = true;
                }
            }

            if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
            }


            $current_comment_media = array();
            $dashboardpost_media_comment_id = 0;
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
                    $dashboardpost_media_comment_thumb_image_width  = $this->dashboardpost_media_comment_thumb_image_width;
                    $dashboardpost_media_thumb_image_height = $this->dashboardpost_media__comment_thumb_image_height;

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
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$dashboardpost_media_comment_thumb_image_width,$dashboardpost_media_thumb_image_height);
                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboardpost_media_comment_media = new PhotoCommentMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboardpost_media_comment_media->setIsFeatured(1);
                        else
                            $dashboardpost_media_comment_media->setIsFeatured(0);
                        $dashboardpost_media_comment_media->setParentId($post_id);
                        $dashboardpost_media_comment_media->setItemId($post_media_id);
                        $dashboardpost_media_comment_media->setCommentId($comment_id);
                        $dashboardpost_media_comment_media->setItemType($item_type);
                        $dashboardpost_media_comment_media->setMediaName($file_name);
                        $dashboardpost_media_comment_media->setType($actual_media_type);
                        $dashboardpost_media_comment_media->setCreatedDate($time);
                       // $dashboardpost_media_comment_media->setUpdatedAt($time);
                        $dashboardpost_media_comment_media->setPath('');
                        $dashboardpost_media_comment_media->setMediaStatus(0);
                        $dashboardpost_media_comment_media->setImageType($image_type);

                        $dm->persist($dashboardpost_media_comment_media);
                        $dm->flush();

                        //get the dashboardpost media comment media id
                         $dashboardpost_media_comment_id = $dashboardpost_media_comment_media->getId();

                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path') .$post_media_id . '/'. $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_comment_media_path_thumb_crop') .$post_media_id . '/' . $comment_id . "/";
                        $s3_post_media_path = $this->container->getParameter('s3_media_comment_media_path'). $post_media_id . '/' . $comment_id ;
                        $s3_post_media_thumb_path = $this->container->getParameter('s3_media_comment_media_thumb_path') .$post_media_id . '/' . $comment_id;

                        $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'dashboardpost_media_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                    }
                }
            }

            // get all media ids uploaded on specific comment
            $comment_media_ids = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->getCommentedMedias($post_media_id, $comment_id);
            //put current media id in array
            $comment_media_ids[] = $dashboardpost_media_comment_id;
            //persists all media ids
            $comment_res->setMedias($comment_media_ids);
            $dm->persist($comment_res);
            $dm->flush();

            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
            $comment_image_type = $image_type;
            $comment_media_name = $file_name;

            $comment_media_link  = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
            $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

            //sending the current media and post data.
            $data = array(
                'id' => $comment_id,
                'media_id' => $dashboardpost_media_comment_id,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' =>$comment_image_type
            );

            $media_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_array);
            exit;
        } else {

            $mediaComments = $post_media->getComment();
            $commentExists = false;
            foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comment_res = $comment;
                    $commentExists = true;
                }
            }

            if($commentExists){

                $comment_res->setCommentText($body);
                $comment_res->setCommentUpdatedAt($time);
                $comment_res->setStatus(1);
                $comment_res->setTagging($tagging);
                $dm->persist($comment_res); //storing the comment data.
                $dm->flush();
                $comment_media_ids = $comment_res->getMedias();
            } else {

                $dashboard_post_media_comments = new  AlbumMediaComment();
                $dashboard_post_media_comments->setAlbumId('');
                $dashboard_post_media_comments->setCommentAuthor($comment_user_id);
                $dashboard_post_media_comments->setCommentText($body);
                $dashboard_post_media_comments->setCommentCreatedAt($time);
                $dashboard_post_media_comments->setCommentUpdatedAt($time);
                $dashboard_post_media_comments->setStatus(1); // 0=>disabled, 1=>enabled
                $dashboard_post_media_comments->setTagging($tagging);
                $post_media->addComment($dashboard_post_media_comments);// save embedded comment data in dashboardpostmedia document
                $dm->persist($dashboard_post_media_comments); //storing the comment data.
                $dm->flush();

                $this->updateAclAction($sender_user, $dashboard_post_media_comments);
                $comment_id = $dashboard_post_media_comments->getId();
                $comment_media_ids = array();

            }

            $postService = $this->get('post_detail.service');
            $dashboardpost_media_comment_id = $object_info->comment_id;
            if (!empty($comment_media_ids)) {
                   $media_update_status = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                             ->publishCommentMediaImage($comment_media_ids);
               }
            if ($dashboardpost_media_comment_id) {
                $this->sendCommentNotificationsDashboardPostMedia($comment_user_id, $post_owner_id, $post_media_id, $object_info->comment_id, $post_id, $tagging);
                $comment_data = $this->getDashboardPostMediaCommentWithImageObject($object_info); //finding the post object.
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {
                $this->sendCommentNotificationsDashboardPostMedia($comment_user_id, $post_owner_id, $post_media_id, $comment_id, $post_id, $tagging);
                $comment_data = $this->getDashboardPostMediaCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
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
    public function getDashboardPostMediaCommentWithoutImageObject($object_info, $comment_id) {

        $comment_data = array();
        $data = array();
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_media_id = $object_info->item_id;

        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->find($post_media_id);

        if (!$post_media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $mediaComments = $post_media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        $sender_user_info = array();
        $user_service = $container->get('user_object.service');
        $comment_user_id = $comment_res->getCommentAuthor(); //sender
        $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')->findBy(array('comment_id' => $comment_id, 'media_status' => 1));
        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getItemType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getMediaStatus();
                $comment_media_created_at = $comment_media_data->getCreatedDate();
                $comment_image_type = $comment_media_data->getImageType();

                $comment_media_link  = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }
        $data = array(
                        'id' => $comment_id,
                        'comment_text' => $comment_res->getCommentText(),
                        'user_id' => $comment_res->getCommentAuthor(),
                        'status' => $comment_res->getStatus(),
                        'comment_user_info' => $sender_user_info,
                        'create_date' => $comment_res->getCommentCreatedAt(),
                        'comment_media_info' => $comment_media_result,
                        'avg_rate'=>0,
                        'no_of_votes' =>0,
                        'current_user_rate'=>0,
                        'is_rated' =>false,
                        'tagging'=>$comment_res->getTagging()

                    );
        $commentdata = $data;

        return $commentdata;
    }


    public function getDashboardPostMediaCommentWithImageObject($object_info) {

        //code for responding the current post data..
        $comment_data = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_info->comment_id;
        $post_media_id = $object_info->item_id;

        $time = new \DateTime('now');

        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->find($post_media_id);

        if (!$post_media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $mediaComments = $post_media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        $sender_user_info = array();
        $user_service = $this->get('user_object.service');
        $comment_user_id = $comment_res->getCommentAuthor(); //Id of persona who has commented for this dashboard post media
        $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')->findBy(array('comment_id' => $comment_id, 'media_status' => 1));
        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object

        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getItemType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getMediaStatus();
                $comment_media_created_at = $comment_media_data->getCreatedDate();
                $comment_image_type = $comment_media_data->getImageType();

                $comment_media_link  = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }

        $data = array(
            'id' => $comment_id,
            'dashboard_post_id' => $object_info->parent_id,
            'comment_text' => $comment_res->getCommentText(),
            'user_id' => $comment_res->getCommentAuthor(),
            'status' => $comment_res->getStatus(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment_res->getCommentCreatedAt(),
            'album_type'=> $comment_res->getAlbumType(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false,
            'tagging'=>$comment_res->getTagging()
        );
        $comment_data = $data;
        return $comment_data;
    }

    /**
     *
     * @param type $from
     * @param type $owner
     * @param type $media_id
     * @param type $comment_id
     * @param type $post_id
     * @param type $tagging
     * @return boolean
     */
    public function sendCommentNotificationsDashboardPostMedia($from, $owner, $media_id, $comment_id, $post_id, $tagging){
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $postService = $this->get('post_detail.service');
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
        $dashboard_post_url =  $this->container->getParameter('dashboard_post_url').'/'.$post_id;
        $link_url = $angular_app_hostname.$dashboard_post_url;

        $message = $this->dashboardpost_media_comment;
        $ownerMessageType = $this->dashboardpost_media_comment_type;
        $commentAuthorMessageType = $this->dashboardpost_media_comment_on_commented_type;

        $commentedAuthors = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->getCommentedUserIds($media_id);
        $commentedAuthors = array_diff($commentedAuthors, array($from, $owner));
        $uniqueAuthors = array_unique($commentedAuthors);
        $sender = $postService->getUserData($from);
        $senderName = trim(ucwords($sender['first_name']. ' '.$sender['last_name']));
        // web and push notification for photo owner
        if($from!=$owner){
            $postService->sendUserNotifications($from, $owner, $ownerMessageType, $message, $post_id, true, true, $senderName, 'CITIZEN', array(), 'U', array('comment_id'=>$comment_id));
            $ownerInfo = $postService->getUserData($owner);

            $locale = !empty($ownerInfo['current_language']) ? $ownerInfo['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
            $subject = sprintf($lang_array['DASHBOARD_POST_MEDIA_COMMENTED_SUBJECT'],$senderName);
            $mail_link = sprintf($lang_array['DASHBOARD_POST_MEDIA_COMMENTED_LINK'],$senderName);
            $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['DASHBOARD_POST_MEDIA_COMMENTED_CLICK_HERE'],$href);
            $bodyTitle = sprintf($lang_array['DASHBOARD_POST_MEDIA_COMMENTED_BODY'],$senderName);
            // HOTFIX NO NOTIFY MAIL
            //$postService->sendMail(array($ownerInfo), $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'DASHBOARD_POST_MEDIA_COMMENT_NOTIFICATION');
        }

        // notification for commented authors
        if(!empty($uniqueAuthors)){
            $authors = $postService->getUserData($uniqueAuthors, true);
            $recieverByLanguage = $postService->getUsersByLanguage($authors);
            foreach($recieverByLanguage as $lng=>$receivers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $subject = sprintf($lang_array['DASHBOARD_POST_MEDIA_ON_COMMENTED_SUBJECT'],$senderName);
                $mail_link = sprintf($lang_array['DASHBOARD_POST_MEDIA_ON_COMMENTED_LINK'],$senderName);
                $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['DASHBOARD_POST_MEDIA_ON_COMMENTED_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array['DASHBOARD_POST_MEDIA_ON_COMMENTED_BODY'],$senderName);
                // HOTFIX NO NOTIFY MAIL
                //$postService->sendMail($receivers, $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'DASHBOARD_PODT_MEDIA_COMMENT_NOTIFICATION');
            }
            $postService->sendUserNotifications($from, $uniqueAuthors, $commentAuthorMessageType, $message, $post_id, true, true, $senderName, 'CITIZEN', array(), 'U', array('comment_id'=>$comment_id));
        }

        if(!empty($tagging)){
            $postService->commentTaggingNotifications($tagging, $from, $post_id, $link_url, 'DASHBOARD_POST_MEDIA', true, array(), false, array('comment_id'=>$comment_id));
        }
        return true;
    }

    /**
     * edit comments of dashboard media comment
     * @param type $object_info
     */
    public function editDashboardPostMediaComment($object_info){
        $data = array();
        $user_id = (int) $object_info->user_id;
        $post_id = $object_info->parent_id;
        $media_id = $object_info->item_id;
       // $item_type = $object_info->item_type;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $comment_id = (isset($object_info->comment_id) ? $object_info->comment_id : '');
        $time = new \DateTime("now");
        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);

        // get entity manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);
        $post_owner_id = $post->getUserId();
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                         ->find($media_id);

        // checking dashboard post media
        if (!$post_media) {
            return array('code' => 310, 'message' => 'DASHBOARD_POST_MEDIA_DOES_NOT_EXITS', 'data' => $data);
        }
        $mediaComments = $post_media->getComment();
        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }

        if ($commentExists) {
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
            //set updated text body
            $comment_res->setCommentText($comment_body);
            $comment_res->setCommentUpdatedAt($time);
            $comment_res->setTagging($tagging);
            $dm->persist($comment_res); //storing the edited comment data.
            $dm->flush();
            $comment_data = $this->getDashboardPostMediaCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
            $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
            if(!empty($newTagging)){
              //  $email_template_service = $this->container->get('email_template.service');
                $postService = $this->container->get('post_detail.service');
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url =  $this->container->getParameter('dashboard_post_url').'/'.$post_id;
                $link_url = $angular_app_hostname.$dashboard_post_url;
                $postService->commentTaggingNotifications($newTagging, $user_id, $media_id, $link_url, 'DASHBOARD_POST_MEDIA_COMMENT', true, array('post_id'=>$post_id), true, array('comment_id'=>$comment_id, 'post_id'=>$post_id));
            }
            echo json_encode($final_array);
            exit;
        }
    }

    /**
     * Delete media used in comment on dashboard post comment
     * @param type $object_info
     */
     public function deleteDashboardPostMediaCommentMedia($object_info) {
        $data = array();
       // $userManager = $this->getUserManager();
        // $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->item_id;
        $comment_media_id = $object_info->comment_media_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //finding the post data
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                         ->findOneBy(array('id' => $media_id, 'media_status' => 1));

        if (!$post_media) {
            $res =  array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;

        }

       $mediaComments = $post_media->getComment();

        $commentExists = false;

        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }

        if($commentExists){
           // remove embedded media id
           // first remove array specific media id from media array ,then persists remaing array
           foreach($mediaComments as $comment){
                if($comment->getId() == $comment_id){
                    $comments_medias_ids =  $comment->getMedias();
                    if (in_array($comment_media_id, $comments_medias_ids))
                    {
                        unset($comments_medias_ids[array_search($comment_media_id,$comments_medias_ids)]);
                    }
                    $comment_res = $comment;
                }
            }
            $comment_res->setMedias($comments_medias_ids);
            $dm->persist($comment_res);
            $dm->flush();

            $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                ->find($comment_media_id);
            $dm->remove($comment_media);
            $dm->flush();
            $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_p);
            exit();
        }

     }

     /**
     * Delete comment with media
     * @param request object
     * @return json string
     */

    public function deleteDashboardPostMediaComment($object_info){
        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->media_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //finding the post data
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                         ->findOneBy(array('id' => $media_id, 'media_status' => 1));

        if (!$post_media) {
            $res =  array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;

        }

       $mediaComments = $post_media->getComment();

        $commentExists = false;
        foreach($mediaComments as $comment){
            if($comment->getId() == $comment_id){
                $comment_res = $comment;
                $commentExists = true;
            }
        }

        if (!$commentExists) {
                $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }
        if ($commentExists) {
            $post_media->removeComment($comment_res); // Remove comment ,
            //call it on DashboardPostMedia object (due to embeded document ortherwise simple $dm->remove($comment_res))
            $dm->flush();
            // Remove media of this comment
            $comment_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                ->removeDashboardPostCommentsMedia($object_info->comment_id);
            if ($comment_media) {
               $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
               echo json_encode($res_p);
               exit();
            }
        }
    }

    public function dashboardPostMediaDetails($object_info) {

        $data = array();
        $comment_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;
        $post_id = $object_info->parent_id;

        //finding the embedded comment data with
        $mediaComments = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->getCommentsOfMedia($media_id, 5, true);
        $media_res = $mediaComments['result'];
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')->find($post_id);
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $users_array = array();
        $users_array[] = $post->getUserId();

        if (is_array($post->getTaggedFriends())) {
            $tagged_user_ids = $post->getTaggedFriends();
        } else {
            $tagged_friend = $post->getTaggedFriends();
            if (trim($tagged_friend)) {
                $tagged_user_ids = explode(',', $tagged_friend);
            } else {
                $tagged_user_ids = array();
            }
        }

        $comments = $media_res->getComment();

        //comments user ids
        $comment_user_ids = array();
        $comment_ids = array();

        foreach($comments as $comment){
           $comment_user_ids[] = $comment->getCommentAuthor();
           $comment_ids[] = $comment->getId();
        }

        //finding the comments media.
        $comments_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                    ->findCommentMedia($comment_ids);

        $users_array[] = $comment_user_ids;
        $users_array[] = $tagged_user_ids;

        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getMediaName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['content_type'] = $media_res->getType();
        $media_info['status'] = $media_res->getMediaStatus();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['created_at'] = $media_res->getCreatedDate();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->container->getParameter('dashboard_post_media_path'). $post_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->container->getParameter('dashboard_post_media_path_thumb').$post_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $post->getUserId();
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments as $comment) {
            if($comment->getStatus() != 0 ){
                $comment_id = $comment->getId();
                $comment_media = $comment->getMedias();
                $comment_media = isset($comment_media)? $comment_media : array() ;
                $comment_txt = $comment->getCommentText();
                $status = $comment->getStatus();
                $comment_author_id = $comment->getCommentAuthor();
                $comment_created_at = $comment->getCommentCreatedAt();
                $comment_author_info = isset($users_object_array[$comment_author_id])? $users_object_array[$comment_author_id] : array() ;
                $comment_media_result = array();
                foreach ($comments_media as $comment_media_data) {
                    if ($comment_media_data->getCommentId() == $comment_id) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getMediaStatus();
                        $comment_media_created_at = $comment_media_data->getCreatedDate();
                        $comment_image_type = $comment_media_data->getImageType();
                        $comment_media_link = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->container->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'create_date' => $comment_media_created_at,
                            'image_type' => $comment_image_type
                        );
                    }
                }
                $current_rate = 0;
                $is_rated = false;
                foreach ($comment->getRate() as $rate) {
                    if ($rate->getUserId() == $user_id) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                $comments_info[] = array(
                    'id' => $comment_id,
                    'comment_text' => $comment_txt,
                    'comment_author_id' => $comment_author_id,
                    'status' => $status,
                    'create_date' => $comment_created_at,
                    'comment_user_info' => $comment_author_info,
                    'comment_media_info' => $comment_media_result,
                    'avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'tagging'=>$comment->getTagging()
                );
            }
            $i++;

        }
        $comment_count = $i;
        $tagged_friends_info = array();
        if (count($tagged_user_ids)) {
            foreach ($tagged_user_ids as $tagged_user_id) {
                $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
            }
        }
        $current_rate = 0;
        $is_rated = false;
        foreach ($media_res->getRate() as $rate) {
            if ($rate->getUserId() == $user_id) {
                $current_rate = $rate->getRate();
                $is_rated = true;
                break;
            }
        }

        $total_comment = $mediaComments['size'];
        $data['comments'] = $comments_info;
        $data['tagged_friends_info'] = $tagged_friends_info;
        // $data['comment_count'] = $comment_count;
        $data['count'] = $total_comment;
//        $data['img_avg_rate'] = round($media_res->getAvgRating(), 1);
//        $data['img_no_of_votes'] = (int) $media_res->getVoteCount();
//        $data['img_current_user_rate'] = $current_rate;
//        $data['img_is_rated'] = $is_rated;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }
}
