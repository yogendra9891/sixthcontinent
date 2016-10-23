<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Sonata\AdminBundle\Security\Acl\Permission\MaskBuilder;
use Media\MediaBundle\Document\StoreAlbumMediaComment;
use Media\MediaBundle\Document\PhotoCommentMedia;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Media\MediaBundle\Document\AlbumMediaComment;

class StoreAlbumMediaCommentController extends Controller
{
    protected $miss_param = '';
    protected $image_width = 100;
    protected $media_comment_thumb_image_width = 654;
    protected $media_comment_thumb_image_height = 360;
    protected $media_comment_original_resize_image_width = 910;
    protected $media_comment_original_resize_image_height = 910;
    protected $store_album_media_comment_msg = "COMMENT";
    protected $store_album_media_comment_type = "STORE_ALBUM_MEDIA_COMMENT";
    protected $store_album_media_comment_on_commented_type = "STORE_ALBUM_MEDIA_COMMENT_ON_COMMENTED";
    protected $storewall_post_media_comment_type = "STORE_POST_MEDIA_COMMENT";
    protected $storewall_post_media_comment_on_commented_type = "STORE_POST_MEDIA_COMMENT_ON_COMMENTED";
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
        $context = $this->getContainer()->get('router')->getContext();
        //return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }


   /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename)
    {
        $amazan_service = $this->getContainer()->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }

    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->getContainer()->getParameter('aws_base_path');
        $aws_bucket    = $this->getContainer()->getParameter('aws_bucket');
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
        return $this->getContainer()->get('fos_user.user_manager');
        // return $this->getContainer()->get('fos_user.user_manager');
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
        $aclProvider = $this->getContainer()->get('security.acl.provider');

       // $aclProvider = $this->getContainer()->get('security.acl.provider');
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



    /*
     * create comment for user album
     * @param type $object_info
     */
    public function createStoreAlbumMediaComment($object_info) {

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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $album = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                    ->find($album_id);

        if (!$album) {
            $res =  array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $media = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->find($media_id);

        if (!$media) {
            $res =  array('code' => 100, 'message' => 'PHOTO_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        if ($comment_type == 0) {
            if ($comment_id == '') {

                $user_album_comments = new StoreAlbumMediaComment();
                $user_album_comments->setAlbumId($album_id);
                $user_album_comments->setCommentAuthor($user_id);
                $user_album_comments->setCommentText($comment_body);
                $user_album_comments->setCommentCreatedAt($time);
                $user_album_comments->setCommentUpdatedAt($time);
                $user_album_comments->setMediaId($media_id);
                $user_album_comments->setTagging($tagging);
                $user_album_comments->setStatus(0); // 0=>disabled, 1=>enabled

                $dm->persist($user_album_comments); //storing the comment data.
                $dm->flush();

                $comment_id = $user_album_comments->getId(); //getting the last inserted id of comments.
                //update ACL for a user
                $this->updateAclAction($sender_user, $user_album_comments);
                $comment_res = $user_album_comments;
            }else{
                $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->find($comment_id);
            }

            $current_comment_media = array();
            $useralbum_media_comment_media_id = 0;
            //getting the image name clean service object.
            $clean_name = $this->getContainer()->get('clean_name_object.service');

            //for file uploading...
            $image_upload = $this->getContainer()->get('amazan_upload_object.service');
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
                        $image_type_service = $this->getContainer()->get('user_object.service');
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$useralbum_comment_thumb_image_width,$useralbum_comment_thumb_image_height);


                        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
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

                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path') .$media_id . '/'. $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path') .$media_id . '/' . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$media_id . '/' . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path_thumb_crop') .$media_id . '/' . $comment_id . "/";
                        $s3_post_media_path = $this->getContainer()->getParameter('s3_media_comment_media_path'). $media_id . '/' . $comment_id ;
                        $s3_post_media_thumb_path = $this->getContainer()->getParameter('s3_media_comment_media_thumb_path') .$media_id . '/' . $comment_id;
                        $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'storealbum_media_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);

                    }

                }
            }

            $comment_media_ids = $comment_res->getMedias();
            $comment_media_ids[] = $useralbum_media_comment_media_id;
            $comment_res->setMedias($comment_media_ids);
            $dm->persist($comment_res);
            $dm->flush();

            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
            $comment_image_type = $image_type;
            $comment_media_name = $file_name;

            $comment_media_link  = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') .$media_id . '/' . $comment_id . '/' . $comment_media_name;
            $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$media_id . '/' . $comment_id . '/' . $comment_media_name;

            //sending the current media and post data.
            $data = array(
                'id' => $comment_id,
                'media_id' => $useralbum_media_comment_media_id,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' =>$comment_image_type
            );

            $media_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_array);
            exit;
        } else {

            $UserAlbumMediaCommentId = $object_info->comment_id ? $object_info->comment_id : "" ;
            $media_id = $object_info->item_id;
            $album_id = $object_info->parent_id;
             $comment_media_ids = $object_info->comment_media_id;
            $comment_res = '';
            if(!empty($UserAlbumMediaCommentId)){
                $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->find($UserAlbumMediaCommentId);
            }

            if($comment_res){

                $comment_res->setCommentText($comment_body);
                $comment_res->setCommentUpdatedAt($time);
                $comment_res->setMediaId($media_id);
                $comment_res->setMedias($comment_media_ids);
                $comment_res->setTagging($tagging);
                $comment_res->setStatus(1);

                $dm->persist($comment_res); //storing the comment data.
                $dm->flush();
            } else {
                $media_comment = new StoreAlbumMediaComment();

                $media_comment->setCommentAuthor($user_id);
                $media_comment->setAlbumId($album_id);
                $media_comment->setMediaId($media_id);
                $media_comment->setMedias($comment_media_ids);
                $media_comment->setCommentText($comment_body);
                $media_comment->setCommentCreatedAt($time);
                $media_comment->setCommentUpdatedAt($time);
                $media_comment->setTagging($tagging);
                $media_comment->setStatus(1);

                $dm->persist($media_comment); //storing the comment data.
                $dm->flush();

                $comment_id = $media_comment->getId();
                //$comment_media_ids = array();

                $comment_res = $media_comment;
            }
            $store_id = $album->getStoreId();
            $storeInfo = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId'=>$store_id, 'role'=>15));
            $album_owner_id = $storeInfo ? $storeInfo->getUserId() : '';
            //calling rating notification service
            $notification_obj = $this->getContainer()->get('post_detail.service');
            //publish comment media
            if (!empty($comment_media_ids)) {

                $media_update_status = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                          ->publishCommentMediaImage($comment_media_ids);
            }
            if ($UserAlbumMediaCommentId) {
                $this->sendCommentNotifications($user_id, $album_owner_id, $media_id, $object_info->comment_id, $album_id, $store_id, $tagging);

                $comment_data = $this->getCommentObject($object_info); //finding the post object.
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {
                $this->sendCommentNotifications($user_id, $album_owner_id, $media_id, $comment_id, $album_id, $store_id, $tagging);
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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->item_id;

        $time = new \DateTime('now');


        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->find($comment_id);
        $sender_user_info = array();
        $user_service = $this->getContainer()->get('user_object.service');

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

                $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri(). $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;

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
            'store_album_id' => $object_info->parent_id,
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

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_info
     * @return array $commentdata
     */
    public function getCommentWithoutImageObject($object_info,$comment_id) {
        //code for responding the current post data..
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $media_id = $object_info->item_id;

        $time = new \DateTime('now');

        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->find($comment_id);

        $sender_user_info = array();
        $user_service = $this->getContainer()->get('user_object.service');

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

                $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri(). $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;

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
            'store_album_id' => $object_info->parent_id,
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


    /**
    * Finding list of comments
    * @param request object
    * @return json string
    */
    public function listUserAlbummediaComment($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $album_id = $object_info->parent_id;
        $limit = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $offset = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;


        //finding the embedded comment data with
        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')
                          ->getTotalCommentsOfMedia($media_id,$limit,$offset);


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
        $user_service = $this->getContainer()->get('user_object.service');
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
        $media_info['media_link'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('user_album_media_path') . $friend_id . '/' . $album_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('user_album_media_path_thumb') . $friend_id . '/' .$album_id.'/'. $media_info['media_name'];
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
                        $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
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
                    'comment_avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'comment_current_user_rate' => $current_rate,
                    'comment_is_rated' => $is_rated
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
        $data['comment_count'] = $comment_count;
        $data['img_avg_rate'] = round($media_res->getAvgRating(), 1);
        $data['img_no_of_votes'] = (int) $media_res->getVoteCount();
        $data['img_current_user_rate'] = $current_rate;
        $data['img_is_rated'] = $is_rated;
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
     * Delete Store Album comment  with media
     * @param request object
     * @return json string
     */

    public function deleteStoreAlbumMediaComment($object_info){

        $data = array();
        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->media_id;
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $this->getContainer()->get('doctrine')->getManager();
        //finding the embedded comment data with
        $media_res = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->find($media_id);

        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        //finding the post data
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                         ->findOneBy(array('id' => $comment_id, 'status' => 1));

        if (!$comment_res) {
            $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
        }

        if($comment_res) {
            $dm->remove($comment_res);
            $dm->flush();
            // Remove media of this comment
            $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                ->removeDashboardPostCommentsMedia($object_info->comment_id);
            $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_p);
            exit();
        }
    }

    /**
    * user Shop Media details
    * @param request object
    * @return json string
    */
    public function ShopAlbumMediaDetails($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $album_id = $object_info->parent_id;

        //finding the embedded comment data with
        $media_res = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->find($media_id);


        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        $users_array = array();
        $users_array[] = $friend_id;

        $_storeMedia = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                            ->findOneBy(array('item_id'=>(string)$media_id,'item_type'=>'store_media'));
        $taggedUsers = array();
        if($_storeMedia){
            $taggedUsers = $_storeMedia->getTaggedFriends();
            $taggedUsers = array_unique($taggedUsers);
        }
        $users_array[] = $taggedUsers;
        $comments = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->getRecentComments($media_id, 5, true);
        $total_comment = $comments['size'];
        //comments user ids
        $comment_user_ids = array();
        $comment_ids = array();

        foreach($comments['result'] as $comment){
           $comment_user_ids[] = $comment->getCommentAuthor();
           $comment_ids[] = $comment->getId();
        }
        //finding the comments media.
        $comments_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                    ->findCommentMedia($comment_ids);


        $users_array[] = $comment_user_ids;
        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->getContainer()->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getImageName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['status'] = $media_res->getMediaStatus();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['created_at'] = $media_res->getCreatedAt();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_album_media_path') . $media_res->getStoreId() . '/original'. '/' . $album_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_album_media_path') . $media_res->getStoreId(). '/thumb' . '/' .$album_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $friend_id;
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $taggedUserFriends = array();
        if(count($taggedUsers)>0){
            foreach ($taggedUsers as $tagged){
                if(isset($users_object_array[$media_user_id])){
                    $taggedUserFriends[] = $users_object_array[$media_user_id];
                }
            }
        }
        $data['tagged_friends_info'] = $taggedUserFriends;
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments['result'] as $comment) {
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
                        $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
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

        $media_rating=$dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$media_id,'item_type'=>'store_media'));
        $current_rate = 0;
        $is_rated = false;
        $avgRate=0;
        $voteCount=0;
        if($media_rating){
            foreach ($media_rating->getRate() as $rate) {
                if ($rate->getUserId() == $user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            $avgRate = round($media_rating->getAvgRating(), 1);
            $voteCount = (int) $media_rating->getVoteCount();
        }

        $data['comments'] = $comments_info;
        $data['count'] = $total_comment;
        $data['avg_rate'] = $avgRate;
        $data['no_of_votes'] = $voteCount;
        $data['current_user_rate'] = $current_rate;
        $data['is_rated'] = $is_rated;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }
    /**
     * Update comment of a user album.........
     * @param request object
     * @return json string
     */

    public function editStoreAlbumMediaComment($object_info){

        $data = array();
        $user_id = (int) $object_info->user_id;
        $media_id = $object_info->item_id;
        $album_id = $object_info->parent_id;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $comment_id = (isset($object_info->comment_id) ? $object_info->comment_id : '');
        $time = new \DateTime("now");
        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);
        // get entity manager object
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $album = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                    ->find($album_id);
        if (!$album) {
            $res =  array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        //finding the embedded comment data with
        $media_res = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->find($media_id);
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        //finding the post data
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                         ->findOneBy(array('id' => $comment_id, 'status' => 1));
        $store_id = $album->getStoreId();
        $storeInfo = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId'=>$store_id, 'role'=>15));
        $albumOwner = $storeInfo ? $storeInfo->getUserId() : '';

        if($comment_res){
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
         if(!empty($newTagging)){
                $email_template_service = $this->getContainer()->get('email_template.service');
                $postService = $this->getContainer()->get('post_detail.service');
                $link_url = $email_template_service->getPageUrl(array('supportId'=>$albumOwner, 'parentId'=> $album_id, 'mediaId'=>$media_id, 'albumType'=>'shop'),'single_image_page');
                $postService->commentTaggingNotifications($newTagging, $user_id, $media_id, $link_url, 'STORE_ALBUM_MEDIA', true, array('album_id'=>$album_id, 'store_id'=>$store_id,'owner'=>$albumOwner), true, array('comment_id'=>$comment_id, 'album_id'=>$album_id, 'store_id'=>$store_id));
            }
         $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
         echo json_encode($final_array);
         exit;
        } else{
         $error_res =  array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
         echo json_encode($error_res);
         exit;
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

        $container = NManagerNotificationBundle::getContainer();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //finding the post data
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                         ->findOneBy(array('id' => $comment_id, 'status' => 1));

        $aclProvider = $this->getContainer()->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($comment_res); //entity

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }

        //Acl Operation
        $um = $this->getContainer()->get('fos_user.user_manager');
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
     * Delete media for comment on dashboard post media
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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

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

        //for store ACL
        $do_action = 0;
        $group_mask = $this->userCommentRole($object_info->comment_id, $object_info->user_id,$object_info->item_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        //ACL
        if ($do_action) {
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
        } else {
            $res = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res);
            exit();
        }
    }


    private function getContainer(){
        return NManagerNotificationBundle::getContainer();
    }

    public function sendCommentNotifications($from, $owner, $media_id, $comment_id, $album_id, $store_id, $tagging){
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $postService = $this->getContainer()->get('post_detail.service');
        $email_template_service = $this->getContainer()->get('email_template.service');
        $angular_app_hostname   = $this->getContainer()->getParameter('angular_app_hostname'); //angular app host
        $link_url = $email_template_service->getPageUrl(array('supportId'=>$owner, 'parentId'=> $album_id, 'mediaId'=>$media_id, 'albumType'=>'shop'),'single_image_page');

        $message = $this->store_album_media_comment_msg;
        $ownerMessageType = $this->store_album_media_comment_type;
        $commentAuthorMessageType = $this->store_album_media_comment_on_commented_type;

        $commentedAuthors = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')->getCommentedUserIds($media_id);
        $commentedAuthors = array_diff($commentedAuthors, array($from, $owner));
        $uniqueAuthors = array_unique($commentedAuthors);
        $sender = $postService->getUserData($from);
        $senderName = trim(ucwords($sender['first_name']. ' '.$sender['last_name']));
        // web and push notification for photo owner
        if($from!=$owner){
            $postService->sendUserNotifications($from, $owner, $ownerMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('album_id'=>$album_id, 'store_id'=>$store_id,'owner'=>$owner), 'U', array('comment_id'=>$comment_id, 'album_id'=>$album_id, 'store_id'=>$store_id));
            $ownerInfo = $postService->getUserData($owner);

            $locale = !empty($ownerInfo['current_language']) ? $ownerInfo['current_language'] : $this->getContainer()->getParameter('locale');
            $lang_array = $this->getContainer()->getParameter($locale);
            $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
            $subject = sprintf($lang_array['STORE_ALBUM_MEDIA_COMMENTED_SUBJECT'],$senderName);
            $mail_link = sprintf($lang_array['STORE_ALBUM_MEDIA_COMMENTED_LINK'],$senderName);
            $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['STORE_ALBUM_MEDIA_COMMENTED_CLICK_HERE'],$href);
            $bodyTitle = sprintf($lang_array['STORE_ALBUM_MEDIA_COMMENTED_BODY'],$senderName);

            // HOTFIX NO NOTIFY MAIL
            //$postService->sendMail(array($ownerInfo), $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'STORE_ALBUM_MEDIA_COMMENT_NOTIFICATION');
        }
        // notification for commented authors
        if(!empty($uniqueAuthors)){
            $authors = $postService->getUserData($uniqueAuthors, true);
            $recieverByLanguage = $postService->getUsersByLanguage($authors);
            foreach($recieverByLanguage as $lng=>$receivers){
                $locale = $lng===0 ? $this->getContainer()->getParameter('locale') : $lng;
                $lang_array = $this->getContainer()->getParameter($locale);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $subject = sprintf($lang_array['STORE_ALBUM_MEDIA_ON_COMMENTED_SUBJECT'],$senderName);
                $mail_link = sprintf($lang_array['STORE_ALBUM_MEDIA_ON_COMMENTED_LINK'],$senderName);
                $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['STORE_ALBUM_MEDIA_ON_COMMENTED_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array['STORE_ALBUM_MEDIA_ON_COMMENTED_BODY'],$senderName);

                // HOTFIX NO NOTIFY MAIL
                //$postService->sendMail($receivers, $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'STORE_ALBUM_MEDIA_COMMENT_NOTIFICATION');
            }
            $postService->sendUserNotifications($from, $uniqueAuthors, $commentAuthorMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('album_id'=>$album_id, 'store_id'=>$store_id,'owner'=>$owner), 'U', array('comment_id'=>$comment_id, 'album_id'=>$album_id, 'store_id'=>$store_id));
        }

        if(!empty($tagging)){
            $postService->commentTaggingNotifications($tagging, $from, $media_id, $link_url, 'STORE_ALBUM_MEDIA', true, array('album_id'=>$album_id, 'store_id'=>$store_id,'owner'=>$owner), false, array('comment_id'=>$comment_id, 'album_id'=>$album_id, 'store_id'=>$store_id));
        }
        return true;
    }

    /*
     * create comment for user album
     * @param type $object_info
     */
    public function createShopWallPostMediaComment($object_info) {

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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $post_owner_id = $post->getStorePostAuthor();
        $store_id = $post->getStoreId();
        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                         ->find($post_media_id);

        // checking dashboard post media
        if (!$post_media) {
            return array('code' => 310, 'message' => 'STORE_POST_MEDIA_DOES_NOT_EXITS', 'data' => $data);
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
            $clean_name = $this->getContainer()->get('clean_name_object.service');

            //for file uploading...
            $image_upload = $this->getContainer()->get('amazan_upload_object.service');

            if (isset($_FILES['commentfile'])) {
                //for file uploading...
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $dashboardpost_media_comment_thumb_image_width  = $this->media_comment_thumb_image_width;
                    $dashboardpost_media_thumb_image_height = $this->media_comment_thumb_image_height;

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
                        $image_type_service = $this->getContainer()->get('user_object.service');
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$dashboardpost_media_comment_thumb_image_width,$dashboardpost_media_thumb_image_height);
                        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
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

                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path') .$post_media_id . '/'. $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->getContainer()->getParameter('media_comment_media_path_thumb_crop') .$post_media_id . '/' . $comment_id . "/";
                        $s3_post_media_path = $this->getContainer()->getParameter('s3_media_comment_media_path'). $post_media_id . '/' . $comment_id ;
                        $s3_post_media_thumb_path = $this->getContainer()->getParameter('s3_media_comment_media_thumb_path') .$post_media_id . '/' . $comment_id;

                        $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'storewall_post_media_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                    }
                }
            }

            // get all media ids uploaded on specific comment
            $comment_media_ids = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->getCommentedMedias($post_media_id, $comment_id);
            //put current media id in array
            $comment_media_ids[] = $dashboardpost_media_comment_id;
            //persists all media ids
            $comment_res->setMedias($comment_media_ids);
            $dm->persist($comment_res);
            $dm->flush();

            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
            $comment_image_type = $image_type;
            $comment_media_name = $file_name;

            $comment_media_link  = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
            $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

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

            $postService = $this->getContainer()->get('post_detail.service');
            $dashboardpost_media_comment_id = $object_info->comment_id;
            if (!empty($comment_media_ids)) {
                   $media_update_status = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                                             ->publishCommentMediaImage($comment_media_ids);
               }
            if ($dashboardpost_media_comment_id) {

                $comment_data = $this->getStorePostMediaCommentWithImageObject($object_info); //finding the post object.
                $this->sendPostCommentNotifications($comment_user_id, $post_owner_id, $post_media_id, $comment_data['id'], $post_id, $store_id, $tagging);
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {

                $comment_data = $this->getStorePostMediaCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
                $this->sendPostCommentNotifications($comment_user_id, $post_owner_id, $post_media_id, $comment_data['id'], $post_id, $store_id, $tagging);
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
    public function getStorePostMediaCommentWithoutImageObject($object_info, $comment_id) {

        $comment_data = array();
        $data = array();
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_media_id = $object_info->item_id;

        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($post_media_id);

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

                $comment_media_link  = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

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
        $commentdata = array(
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

        return $commentdata;
    }


    public function getStorePostMediaCommentWithImageObject($object_info) {

        //code for responding the current post data..
        $comment_data = array();
        $data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_info->comment_id;
        $post_media_id = $object_info->item_id;

        $time = new \DateTime('now');

        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($post_media_id);

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
                break;
            }
        }
        $sender_user_info = array();
        $user_service = $this->getContainer()->get('user_object.service');
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

                $comment_media_link  = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') .$post_media_id . '/' . $comment_id . '/' . $comment_media_name;

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
            'store_post_id' => $object_info->parent_id,
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

    public function sendPostCommentNotifications($from, $owner, $media_id, $comment_id, $post_id, $store_id, $tagging){

        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $postService = $this->getContainer()->get('post_detail.service');
        $email_template_service = $this->getContainer()->get('email_template.service');
        $angular_app_hostname   = $this->getContainer()->getParameter('angular_app_hostname'); //angular app host
        $link_url = $postService->getStoreClubUrl(array('storeId'=>$store_id, 'postId'=>$post_id), 'store');

        $message = $this->store_album_media_comment_msg;
        $ownerMessageType = $this->storewall_post_media_comment_type;
        $commentAuthorMessageType = $this->storewall_post_media_comment_on_commented_type;

        $commentedAuthors = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->getCommentedUserIds($media_id);
        $commentedAuthors = array_diff($commentedAuthors, array($from, $owner));
        $uniqueAuthors = array_unique($commentedAuthors);
        $sender = $postService->getUserData($from);
        $senderName = trim(ucwords($sender['first_name']. ' '.$sender['last_name']));
        // web and push notification for photo owner
        if($from!=$owner){

            $postService->sendUserNotifications($from, $owner, $ownerMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('post_id'=>$post_id, 'store_id'=>$store_id), 'U', array('comment_id'=>$comment_id, 'post_id'=>$post_id, 'store_id'=>$store_id));
            $ownerInfo = $postService->getUserData($owner);
            $locale = !empty($ownerInfo['current_language']) ? $ownerInfo['current_language'] : $this->getContainer()->getParameter('locale');
            $lang_array = $this->getContainer()->getParameter($locale);
            $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
            $subject = sprintf($lang_array['STORE_POST_MEDIA_COMMENTED_SUBJECT'],$senderName);
            $mail_link = sprintf($lang_array['STORE_POST_MEDIA_COMMENTED_LINK'],$senderName);
            $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['STORE_POST_MEDIA_COMMENTED_CLICK_HERE'],$href);
            $bodyTitle = sprintf($lang_array['STORE_POST_MEDIA_COMMENTED_BODY'],$senderName);

            // HOTFIX NO NOTIFY MAIL
            //$postService->sendMail(array($ownerInfo), $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'STORE_POST_MEDIA_COMMENT_NOTIFICATION');
        }
        // notification for commented authors
        if(!empty($uniqueAuthors)){
            $authors = $postService->getUserData($uniqueAuthors, true);

            $recieverByLanguage = $postService->getUsersByLanguage($authors);
            foreach($recieverByLanguage as $lng=>$receivers){
                $locale = $lng===0 ? $this->getContainer()->getParameter('locale') : $lng;
                $lang_array = $this->getContainer()->getParameter($locale);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $subject = sprintf($lang_array['STORE_POST_MEDIA_ON_COMMENTED_SUBJECT'],$senderName);
                $mail_link = sprintf($lang_array['STORE_POST_MEDIA_ON_COMMENTED_LINK'],$senderName);
                $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['STORE_POST_MEDIA_ON_COMMENTED_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array['STORE_POST_MEDIA_ON_COMMENTED_BODY'],$senderName);

                // HOTFIX NO NOTIFY MAIL
                //$postService->sendMail($receivers, $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'STORE_POST_MEDIA_COMMENT_NOTIFICATION');
            }
            $postService->sendUserNotifications($from, $uniqueAuthors, $commentAuthorMessageType, $message, $media_id, true, true, $senderName, 'CITIZEN', array('post_id'=>$post_id, 'store_id'=>$store_id), 'U', array('comment_id'=>$comment_id, 'post_id'=>$post_id, 'store_id'=>$store_id));
        }

        if(!empty($tagging)){
            $postService->commentTaggingNotifications($tagging, $from, $media_id, $link_url, 'STORE_POST_MEDIA', true, array('post_id'=>$post_id, 'store_id'=>$store_id), false, array('comment_id'=>$comment_id, 'post_id'=>$post_id, 'store_id'=>$store_id));
        }
        return true;
    }

    /**
     * edit comments of dashboard media comment
     * @param type $object_info
     */
    public function editStoreWallPostMediaComment($object_info){
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
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        $store_id = $post->getStoreId();

        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
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
                break;
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
            $comment_data = $this->getStorePostMediaCommentWithoutImageObject($object_info, $comment_id); //finding the post object.
            $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
            if(!empty($newTagging)){
              //  $email_template_service = $this->getContainer()->get('email_template.service');
                $postService = $this->getContainer()->get('post_detail.service');
                $angular_app_hostname   = $this->getContainer()->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url =  $this->getContainer()->getParameter('dashboard_post_url').'/'.$post_id;
                $link_url = $postService->getStoreClubUrl(array('storeId'=>$store_id, 'postId'=>$post_id), 'store');
                $postService->commentTaggingNotifications($newTagging, $user_id, $media_id, $link_url, 'STORE_POST_MEDIA', true, array('post_id'=>$post_id, 'store_id'=>$store_id), true, array('comment_id'=>$comment_id, 'post_id'=>$post_id, 'store_id'=>$store_id));
            }
            echo json_encode($final_array);
            exit;
        }
    }

    /**
     * Delete comment with media
     * @param request object
     * @return json string
     */

    public function deleteStorePostMediaComment($object_info){
        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;
        $media_id = $object_info->media_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //finding the post data
        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
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
                break;
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

    public function storePostMediaDetails($object_info) {

        $data = array();
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;
        $post_id = $object_info->parent_id;

        //finding the embedded comment data with
        $mediaComments = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->getCommentsOfMedia($media_id, 5, true);
        $media_res = $mediaComments['result'];
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')->find($post_id);
        //checking post
        if (!$post) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }

        $users_array = array();
        $users_array[] = $post->getStorePostAuthor();

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
        $user_service = $this->getContainer()->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getMediaName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['content_type'] = $media_res->getMediaType();
        $media_info['status'] = $media_res->getMediaStatus();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_post_media_path'). $post_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_post_media_path_thumb').$post_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $post->getStorePostAuthor();
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments as $comment) {
            if($comment->getStatus() != 0 ){
                $comment_id = $comment->getId();
                $comment_media = $comment->getMedias();
                $comment_media = isset($comment_media)? $comment_media : array() ;
                $comment_text = $comment->getCommentText();
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
                        $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
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
                    'comment_txt' => $comment_txt,
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
        $data['count'] = $total_comment;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }

    /**
    * Finding list of comments
    * @param request object
    * @return json string
    */
    public function listStorePostMediaComment($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $post_id = $object_info->parent_id;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;

        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                          ->find($post_id);
         if (!$post) {
            $res =  array('code' => 100, 'message' => 'POST_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        //finding the embedded comment data with
        $mediaResult = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                          ->getCommentsOfMedia($media_id,$limit, true, $offset);

        $media_res = $mediaResult['result'];
        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }


        $comments = $media_res->getComment();
        $users_array = array();
        $users_array[] = $post->getStorePostAuthor();

//        if (is_array($media_res->getTaggedFriends())) {
//            $tagged_user_ids = $media_res->getTaggedFriends();
//        } else {
//            $tagged_friend = $media_res->getTaggedFriends();
//            if (trim($tagged_friend)) {
//                $tagged_user_ids = explode(',', $tagged_friend);
//            } else {
//                $tagged_user_ids = array();
//            }
//        }

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
        //$users_array[] = $tagged_user_ids;

        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->getContainer()->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getMediaName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['content_type'] = $media_res->getMediaType();
        $media_info['status'] = $media_res->getMediaStatus();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_post_media_path'). $post_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_post_media_path_thumb'). $post_id.'/'.$media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $post->getStorePostAuthor();
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
//        $tagged_friends_info = array();
//        if (count($tagged_user_ids)) {
//            foreach ($tagged_user_ids as $tagged_user_id) {
//                $tagged_friends_info[] = isset($users_object_array[$tagged_user_id]) ? $users_object_array[$tagged_user_id] : array();
//            }
//        }
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
        //$data['tagged_friends_info'] = $tagged_friends_info;
        $data['count'] = $mediaResult['size'];
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
    public function listStoreAlbumMediaComment($object_info){
        $data = array();
        $comment_data = array();
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $user_id = $object_info->user_id;
        $friend_id = $object_info->owner_id;
        $media_id = $object_info->media_id;

        $album_id = $object_info->parent_id;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;

        //finding the embedded comment data with
        $media_res = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->find($media_id);


        if (!$media_res) {
            $res =  array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;
        }
        $users_array = array();
        $users_array[] = $friend_id;

        $comments = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                            ->getCommentsOfMedia($media_id, $offset, $limit, true);
        $total_comment = $comments['size'];
        //comments user ids
        $comment_user_ids = array();
        $comment_ids = array();

        foreach($comments['result'] as $comment){
           $comment_user_ids[] = $comment->getCommentAuthor();
           $comment_ids[] = $comment->getId();
        }
        //finding the comments media.
        $comments_media = $dm->getRepository('MediaMediaBundle:PhotoCommentMedia')
                    ->findCommentMedia($comment_ids);


        $users_array[] = $comment_user_ids;
        $users_array = $this->array_flatten($users_array);

        //find user object service..
        $user_service = $this->getContainer()->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $comment_data = array();
        $media_info = array();

        //finding the media array of current post.
        $media_info['id'] = $media_res->getId();
        $media_info['media_name'] = $media_res->getImageName();
        $media_info['image_type'] = $media_res->getImageType();
        $media_info['status'] = $media_res->getMediaStatus();
        $media_info['is_featured'] = $media_res->getIsFeatured();
        $media_info['created_at'] = $media_res->getCreatedAt();
        $media_info['media_link'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_album_media_path') . $media_res->getStoreId() . '/original'. '/' . $album_id.'/'.$media_info['media_name'];
        $media_info['media_thumb'] = $this->getS3BaseUri() . $this->getContainer()->getParameter('store_album_media_path') . $media_res->getStoreId(). '/thumb' . '/' .$album_id.'/'. $media_info['media_name'];
        $data['media_info'] = $media_info;
        $media_user_id = $friend_id;
        $data['user_info'] = isset($users_object_array[$media_user_id]) ? $users_object_array[$media_user_id] : array();
        $i = 0;
        $comments_info = array();

        //finding the comments..
        foreach ($comments['result'] as $comment) {
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
                        $comment_media_link = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path') . $media_id . '/'. $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->getContainer()->getParameter('media_comment_media_path_thumb') . $media_id . '/' . $comment_id . '/' . $comment_media_name;
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

        $media_rating=$dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$media_id,'item_type'=>'store_media'));
        $current_rate = 0;
        $is_rated = false;
        $avgRate=0;
        $voteCount=0;
        if($media_rating){
            foreach ($media_rating->getRate() as $rate) {
                if ($rate->getUserId() == $user_id) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            $avgRate = round($media_rating->getAvgRating(), 1);
            $voteCount = (int) $media_rating->getVoteCount();
        }

        $data['comments'] = $comments_info;
        $data['count'] = $total_comment;
        $data['avg_rate'] = $avgRate;
        $data['no_of_votes'] = $voteCount;
        $data['current_user_rate'] = $current_rate;
        $data['is_rated'] = $is_rated;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit();
    }


}
