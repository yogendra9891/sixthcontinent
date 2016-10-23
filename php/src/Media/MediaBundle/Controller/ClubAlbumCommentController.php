<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserManager\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Media\MediaBundle\Document\ClubAlbumComment;
use Media\MediaBundle\Document\ClubAlbumCommentMedia;
use UserManager\Sonata\UserBundle\Document\GroupAlbum;
use UserManager\Sonata\UserBundle\Document\Group;
use Media\MediaBundle\Document\AlbumComment;
use Media\MediaBundle\Document\AlbumCommentMedia;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Media\MediaBundle\Controller\AlbumCommentController;




class ClubAlbumCommentController extends Controller
{

    protected $clubalbum_comment_thumb_image_width = 654;
    protected $clubalbum_comment_thumb_image_height = 360;
    protected $clubalbum_comment_original_resize_image_width = 910;
    protected $clubalbum_comment_original_resize_image_height = 910;
    protected $club_album_comment_msg ='COMMENT';
    protected $club_album_comment_type ='CLUB_ALBUM_COMMENT';
    protected $club_album_comment_on_commented_type ='CLUB_ALBUM_COMMENT_ON_COMMENTED';

    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        $container = NManagerNotificationBundle::getContainer();
        //finding the base path of aws and bucket name
        $aws_base_path = $container->getParameter('aws_base_path');
        $aws_bucket    = $container->getParameter('aws_bucket');
        $full_path     = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        $container = NManagerNotificationBundle::getContainer();
        return $container->get('fos_user.user_manager');
    }
    /*
     * comment for club album
     * @param object
     */
    public function createClubAlbumComment($object_info){

        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;
        $album_type = $object_info->album_type;
        $comment_type = $object_info->comment_type;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $time = new \DateTime("now");
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();

        /** check for club album **/
        $album_comment = new AlbumCommentController();

        //Code for ACL checking
        $userManager = $this->getUserManager();

        $sender_user = $userManager->findUserBy(array('id' => $user_id));


        //get container object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get group album object and fetch group id
        $club_album  = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')->find($album_id);
        $club_id = $club_album->getGroupId();

        //get group  object and fetch group owner id
        $club = $dm->getRepository('UserManagerSonataUserBundle:Group')->find($club_id);
        $club_album_owner_id = $club_owner_id = $club ->getOwnerId();
       $clubStatus = $club->getGroupStatus();
       $clubName = $club->getTitle();
       $albumTitle = $club_album->getAlbumName();

        if (!$club_album) {
            $error_res =  array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
            echo json_encode($error_res);
            exit;
        }
        if ($comment_type == 0) {
            if($object_info->comment_id == 0){
                $club_album_comment = new ClubAlbumComment();
                $club_album_comment->setAlbumId($album_id);
                $club_album_comment->setCommentAuthor($user_id);
                $club_album_comment->setCommentText($comment_body);
                $club_album_comment->setCommentCreatedAt($time);
                $club_album_comment->setCommentUpdatedAt($time);
                $club_album_comment->setTagging($tagging);
                $club_album_comment->setStatus(0); // 0=>disabled, 1=>enabled
                $club_album_comment->setAlbumType($album_type); // album type = user, club, store

                $dm->persist($club_album_comment); //storing the comment data.
                $dm->flush();
                $comment_id = $club_album_comment->getId(); //getting the last inserted id of comments.
                 //update ACL for a user
                $album_comment->updateAclAction($sender_user, $club_album_comment);


            } else {
                $comment_id = $object_info->comment_id;
            }
            $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                                   ->find($comment_id);

            if (!$comment_res) {
                $error_res =  array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                echo json_encode($error_res);
                exit;
            }
            $current_comment_media = array();
            $clubalbum_comment_media_id = 0;

            //getting the image name clean service object.
            $clean_name = $container->get('clean_name_object.service');


            //for file uploading...
            $image_upload = $container->get('amazan_upload_object.service');

            if (isset($_FILES['commentfile'])) {
                //for file uploading...
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $clubalbum_comment_thumb_image_width  = $this->clubalbum_comment_thumb_image_width;
                    $clubalbum_comment_thumb_image_height = $this->clubalbum_comment_thumb_image_height;

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
                        $image_type_service = $container->get('user_object.service');
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$clubalbum_comment_thumb_image_width,$clubalbum_comment_thumb_image_height);


                        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                        $clubalbum_comment_media = new ClubAlbumCommentMedia();
                        if (!$key) //consider first image the featured image.
                            $clubalbum_comment_media->setIsFeatured(1);
                        else
                            $clubalbum_comment_media->setIsFeatured(0);
                        $clubalbum_comment_media->setCommentId($comment_id);
                        $clubalbum_comment_media->setMediaName($file_name);
                        $clubalbum_comment_media->setMediaType($actual_media_type);
                        $clubalbum_comment_media->setCreatedAt($time);
                        $clubalbum_comment_media->setUpdatedAt($time);
                        $clubalbum_comment_media->setPath('');
                        $clubalbum_comment_media->setIsActive(0);
                        $clubalbum_comment_media->setImageType($image_type);
                        $dm->persist($clubalbum_comment_media);
                        $dm->flush();

                        //get the clubalbum comment media id
                        $clubalbum_comment_media_id = $clubalbum_comment_media->getId();

                        //update ACL for a club
                        //  $album_comment->updateAclAction($sender_user, $club_album_comment);
                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $container->getParameter('clubalbum_comment_media_path'). $comment_id . '/';
                        $media_original_path = __DIR__ . "/../../../../web" . $container->getParameter('clubalbum_comment_media_path') . $comment_id . '/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $container->getParameter('clubalbum_comment_media_path_thumb') . $comment_id . '/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $container->getParameter('clubalbum_comment_media_path_thumb_crop') . $comment_id . "/";
                        $s3_post_media_path = $container->getParameter('s3_clubalbum_comment_media_path'). $comment_id ;
                        $s3_post_media_thumb_path = $container->getParameter('s3_clubalbum_comment_media_thumb_path'). $comment_id;
                        $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'clubalbum_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);

                    }
                }
            }

            //finding the current media data.
            $comment_media_data = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                                     ->find($clubalbum_comment_media_id);
            $comment_media_name = $comment_media_link = $comment_media_thumb = $comment_image_type=''; //initialize blank variables.
            if ($comment_media_data) {
                $comment_image_type = $comment_media_data->getImageType();
                $comment_media_name = $comment_media_data->getMediaName();

                $comment_media_link  = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path') . $comment_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path_thumb') . $comment_id . '/' . $comment_media_name;
            }
            //sending the current media and post data.
            $data = array(
                'id' => $comment_id,
                'media_id' => $clubalbum_comment_media_id,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' =>$comment_image_type
            );

            $media_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($media_array);
            exit;

        } else {

            $ClubAlbumCommentId = $object_info->comment_id;
            $media_id = $object_info->media_id;

            //calling rating notification service
            $notification_obj = $container->get('post_detail.service');

            if (!empty($media_id)) {
                $media_update_status = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                                          ->publishClubAlbumCommentMediaImage($media_id);
            }
            if ($ClubAlbumCommentId) {
                //finding the comment and making the comment publish.
                $comment = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                               ->find($object_info->comment_id);
                if (!$comment) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }


                $comment_data = $this->getCommentObject($object_info); //finding the post object.
                $this->sendCommentNotifications($user_id, $club_album_owner_id, $club_id, $clubStatus, $object_info->comment_id, $album_id, $albumTitle, $tagging, $clubName);
                $final_array = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                echo json_encode($final_array);
                exit;
            } else {
                $comment_data = $this->getCommentWithoutImageObject($object_info,$sender_user); //finding the post object.
                $this->sendCommentNotifications($user_id, $club_album_owner_id, $club_id, $clubStatus, $comment_data['id'], $album_id, $albumTitle, $tagging, $clubName);
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
        //get container object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_id = $object_info->comment_id;
        $time = new \DateTime('now');
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        $comment = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                      ->find($comment_id);

        // updating the post data, making the post publish.

        $body = '';
        if (isset($object_info->body)) {
            $body = $object_info->body;
        }
        $comment->setAlbumId($object_info->album_id);
        $comment->setCommentAuthor($object_info->user_id);
        $comment->setCommentText($body);
        $comment->setCommentCreatedAt($time);
        $comment->setCommentUpdatedAt($time);
        $comment->setTagging($tagging);
        $comment->setStatus(1); // 0=>disabled, 1=>enabled
        $comment->setAlbumType($object_info->album_type); // album type = user, club, store

        $dm->persist($comment);
        $dm->flush();

        $sender_user_info = array();
        $user_service = $container->get('user_object.service');

        $comment_id = $comment->getId();
        $comment_user_id = $comment->getCommentAuthor(); //Id of persona who has commented for this album

        $comment_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                            ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));

        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object

        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getMediaType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getIsActive();
                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                $comment_media_created_at = $comment_media_data->getCreatedAt();
                $comment_image_type = $comment_media_data->getImageType();
//                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                    $comment_media_link = '';
//                    $comment_media_thumb = '';
//                } else {
                    $comment_media_link = $this->getS3BaseUri() . $container->getParameter('useralbum_comment_media_path') . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri().$container->getParameter('clubalbum_comment_media_path_thumb') . $comment_id . '/' . $comment_media_name;
//                }

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_path' => $comment_media_link,
                    'media_thumb' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'is_featured' => $comment_media_is_featured,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'club_album_id' => $object_info->album_id,
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getCommentAuthor(),
            'status' => $comment->getStatus(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment->getCommentCreatedAt(),
            'album_type'=> $comment->getAlbumType(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false,
            'tagging'=>$comment->getTagging()
        );
        $commentdata = $data;

        return $commentdata;
    }

    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_info
     * @return array $commentdata
     */
    public function getCommentWithoutImageObject($object_info,$sender_user) {
        //code for responding the current post data..
        $comment_data = array();
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $album_comment = new AlbumCommentController();

        $time = new \DateTime('now');
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();

        $body = '';
        if (isset($object_info->body)) {
            $body = $object_info->body;
        }
        // updating the post data, making the post publish.
        $comment = new ClubAlbumComment();
        $comment->setAlbumId($object_info->album_id);
        $comment->setCommentAuthor($object_info->user_id);
        $comment->setCommentText($body);
        $comment->setCommentCreatedAt($time);
        $comment->setCommentUpdatedAt($time);
        $comment->setTagging($tagging);
        $comment->setStatus(1); // 0=>disabled, 1=>enabled
        $comment->setAlbumType($object_info->album_type); // album type = user, club, store

        $dm->persist($comment);
        $dm->flush();

        $album_comment->updateAclAction($sender_user, $comment);
        $sender_user_info = array();
        $user_service = $container->get('user_object.service');

        $comment_id = $comment->getId();
        $comment_user_id = $comment->getCommentAuthor(); //sender

        $comment_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                            ->findBy(array('comment_id' => $comment_id, 'is_active' => 1));

        $sender_user_info = $user_service->UserObjectService($comment_user_id); //sender user object
        //code for user active profile check
        $comment_media_result = array();
        if ($comment_media) {
            foreach ($comment_media as $comment_media_data) {
                $comment_media_id = $comment_media_data->getId();
                $comment_media_type = $comment_media_data->getMediaType();
                $comment_media_name = $comment_media_data->getMediaName();
                $comment_media_status = $comment_media_data->getIsActive();
                $comment_media_is_featured = $comment_media_data->getIsFeatured();
                $comment_media_created_at = $comment_media_data->getCreatedAt();
                $comment_image_type = $comment_media_data->getImageType();
//                if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                    $comment_media_link = '';
//                    $comment_media_thumb = '';
//                } else {
                    $comment_media_link = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path') . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $this->getS3BaseUri(). $container->getParameter('clubalbum_comment_media_path_thumb') . $comment_id . '/' . $comment_media_name;
//                }

                $comment_media_result[] = array(
                    'id' => $comment_media_id,
                    'media_path' => $comment_media_link,
                    'media_thumb' => $comment_media_thumb,
                    'status' => $comment_media_status,
                    'is_featured' => $comment_media_is_featured,
                    'create_date' => $comment_media_created_at,
                    'image_type' =>$comment_image_type,
                    'comment_media_type'=>$comment_media_type
                );
            }
        }
        $data = array(
            'id' => $comment_id,
            'club_album_id' => $object_info->album_id,
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getCommentAuthor(),
            'status' => $comment->getStatus(),
            'comment_user_info' => $sender_user_info,
            'create_date' => $comment->getCommentCreatedAt(),
            'album_type'=> $comment->getAlbumType(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false,
            'tagging'=>$comment->getTagging()
        );
        $commentdata = $data;

        return $commentdata;
    }


    /**
     * Update comment of a club album.........
     * @param request object
     * @return json string
     */

    public function editClubAlbumComment($object_info){

        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;
        $album_type = $object_info->album_type;
        $comment_body = (isset($object_info->body) ? $object_info->body : '');
        $comment_id = $object_info->comment_id;
        $time = new \DateTime("now");

        $taggingRequestData = (isset($object_info->tagging) and !empty($object_info->tagging)) ? $object_info->tagging : array();
        $tagging = is_array($taggingRequestData) ? $taggingRequestData : json_decode($taggingRequestData, true);

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get group album object and fetch group id
        $club_album  = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')->find($album_id);
        $club_id = $club_album->getGroupId();
        $albumTitle = $club_album->getAlbumName();

        //get group  object and fetch group owner id
        $club = $dm->getRepository('UserManagerSonataUserBundle:Group')->find($club_id);
        $club_album_owner_id = $club_owner_id = $club ->getOwnerId();
        $club_name = $club->getTitle();

        $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                          ->find($comment_id);
        if (!$comment_res) {
            $resp_data =  array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($resp_data);
            exit;
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

        //for Club ACL
        $do_action = 0;
        $group_mask = $this->userCommentRole($comment_id, $user_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');

        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        //ACL
        if ($do_action) {
            //checking for active profile end.

            $comment_res->setCommentText($comment_body);
            $comment_res->setCommentUpdatedAt($time);
            $comment_res->setStatus(1); // 0=>disabled, 1=>enabled
            $comment_res->setTagging($tagging);
            $dm->persist($comment_res); //storing the comment data.
            $dm->flush();

            $comment_id = $object_info->comment_id; //getting the last inserted id of comments.
            if ($comment_id) {
               // $dm_obj = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                $comment_obj = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                                      ->find($comment_id);
            }

            $comment_media_result = array();
            $comment_user_info = array();
            if ($comment_obj) {
               // $dm_obj_media = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                $comment_obj_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                                                  ->findBy(array('comment_id' => $comment_id));

                $album_id = $comment_obj->getAlbumId();
                if ($album_id) {
                   // $dm_post_object = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                    $post_obj_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                                                   ->find($album_id);
                }
                if ($comment_obj_media) {
                    foreach ($comment_obj_media as $comment_media_data) {
                        $comment_media_id = $comment_media_data->getId();
                        $comment_media_type = $comment_media_data->getMediaType();
                        $comment_media_name = $comment_media_data->getMediaName();
                        $comment_media_status = $comment_media_data->getIsActive();
                        $comment_media_is_featured = $comment_media_data->getIsFeatured();
                        $comment_media_created_at = $comment_media_data->getCreatedAt();
                        $comment_image_type = $comment_media_data->getImageType();

//                        if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                            if ($post_obj_res) {
//                                $comment_media_link = $post_obj_res->getPath();
//                                $comment_media_thumb = '';
//                            }
//                        } else {
                            $comment_media_link = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path') . $comment_id . '/' . $comment_media_name;
                            $comment_media_thumb = $this->getS3BaseUri().$container->getParameter('clubalbum_comment_media_path_thumb') . $comment_id . '/' . $comment_media_name;
//                        }

                        $comment_media_result[] = array(
                            'id' => $comment_media_id,
                            'media_link' => $comment_media_link,
                            'media_thumb_link' => $comment_media_thumb,
                            'status' => $comment_media_status,
                            'is_featured' => $comment_media_is_featured,
                            'create_date' => $comment_media_created_at,
                            'image_type' =>$comment_image_type,
                            'comment_media_type'=>$comment_media_type
                           );
                    }
                }

                $user_service = $container->get('user_object.service');
                $comment_user_info = $user_service->UserObjectService($object_info->user_id);
            }

            $data_obj = array(
                'id' => $comment_id,
                'club_album_id' => $object_info->album_id,
                'comment_text' => $comment_obj->getCommentText(),
                'user_id' => $comment_obj->getCommentAuthor(),
                'status' => $comment_obj->getStatus(),
                'comment_user_info' => $comment_user_info,
                'create_date' => $comment_obj->getCommentCreatedAt(),
                'album_type'=> $comment_obj->getAlbumType(),
                'comment_media_info' => $comment_media_result,
                'tagging'=>$comment_obj->getTagging()
            );
            $data = $data_obj;
            $final_data_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            $postService = $this->getContainer()->get('post_detail.service');
            $email_template_service = $this->getContainer()->get('email_template.service');
            $link_url = $email_template_service->getDashboardAlbumUrl(array('friendId'=>$club_album_owner_id, 'albumId'=> $album_id, 'albumName'=> $albumTitle));
            if(!empty($newTagging)){
                $postService->commentTaggingNotifications($tagging, $club_album_owner_id, $album_id, $link_url, 'CLUB_ALBUM', true, array('album_title'=>$albumTitle, 'album_owner'=>$club_album_owner_id), false, array(), array($club_name));
            }
            echo json_encode($final_data_array);
            exit;
        } else {
            $res_data = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
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
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                      ->find($comment_id);

        $aclProvider = $container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($comment); //entity

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
    * Finding list of comments
    * @param request object
    * @return json string
    */
    public function listClubAlbumComment($object_info){
        $data = array();
        $album_data = array();
        $comment_data = array();
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        // $comment_user_id = $object_info->user_id;
        $album_id        = $object_info->album_id;
        $user_id        = $object_info->user_id;
        $limit = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        $offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        //finding the club album data
        $club_album_data  = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                                ->find($album_id);
        if (!$club_album_data) {
            $res =  array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res);
            exit;

        }

        $user_service = $container->get('user_object.service');
        $user_info = array();
//        $comment_count =  $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
//                            ->findBy(array('album_id' => $album_id, 'status' => 1));
//         $total_comment = count($comment_count);
//        //finding the comment data
//        $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
//                           ->findBy(array('album_id' => $album_id, 'status' => 1), array('comment_created_at' => '1'), $limit, $offset);
        $_comments = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                           ->getAlbumComments($album_id, $limit, $offset, true, true);
        $comment_res = $_comments['result'];
        $total_comment = $_comments['count'];
        // echo count($comment_res);
        if (count($comment_res)) {
            //comments ids
            $comment_ids = array_map(function($comment_data) {
               return "{$comment_data->getId()}";
            }, $comment_res);

            //comments user ids.
            $comment_user_ids = array_map(function($comment_data) {
                return "{$comment_data->getCommentAuthor()}";
            }, $comment_res);

            //finding the comments media.
            $comments_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                                 ->getClubAlbumCommentMedia($comment_ids);

            //making user ids array unique.
            $users_array = array_values(array_unique($comment_user_ids));
            //find user object service..
            $user_service = $container->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($users_array);

            foreach ($comment_res as $comment) {
                $comment_id = $comment->getId();
                $comment_user_id = $comment->getCommentAuthor();
                $comment_media_data = array();
                //ittrate comment media
                foreach ($comments_media as $media) {
                    if ($media->getCommentId() == $comment_id) {

                        $media_id = $media->getId();
                        $clubalbum_comment_id = $media->getCommentId();
                        $media_name = $media->getMediaName();
                        $media_type = $media->getMediaType();
                        $media_created_at = $media->getCreatedAt();
                        $media_status = $media->getIsActive();
                       // $media_path = $media->getPath();
                        $media_is_featued = $media->getIsFeatured();
                        $comment_image_type = $media->getImageType();
                       /* if ($media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                        $media_link = $media_path;
                        $media_thumb = '';
                        } else {
                        *
                        */
                            $media_link  = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path') . $clubalbum_comment_id . '/' . $media_name;
                            $media_thumb = $this->getS3BaseUri() . $container->getParameter('clubalbum_comment_media_path_thumb') . $clubalbum_comment_id . '/' . $media_name;
                       //  }
                        $comment_media_data[] = array('id' => $media_id,
                            'comment_id' => $clubalbum_comment_id,
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

                //comment user info.
                $user_info = isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array();
                //prepare the comment array with comment media(assign in comment_media_data variable)
                $comment_data[] = array('id' => $comment->getId(),
                    'album_id' => $comment->getAlbumId(),
                    'comment_text' => $comment->getCommentText(),
                    'comment_author' => $comment->getCommentAuthor(),
                    'comment_user_info' => $user_info,
                    'comment_created_at' => $comment->getCommentCreatedAt(),
                    'comment_updated_at' => $comment->getCommentUpdatedAt(),
                    'comment_status' => $comment->getStatus(),
                    'comment_media_info' => $comment_media_data,
                    'avg_rate' => round($comment->getAvgRating(), 1),
                    'no_of_votes' => (int) $comment->getVoteCount(),
                    'current_user_rate' => $current_rate,
                    'is_rated' => $is_rated,
                    'tagging'=>$comment->getTagging()
                );
        }

        }

        $data['album'] = $album_data;
        $data['comment'] = $comment_data;
        $data['count']= $total_comment;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Delete comment with media
     * @param request object
     * @return json string
     */
    public function deleteClubAlbumComment($object_info){
        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $comment_id = $object_info->comment_id;

        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                          ->find($comment_id);

        if (!$comment_res) {
            $res_data = array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        //for store ACL
        $do_action = 0;
        $group_mask = $this->userCommentRole($object_info->comment_id, $object_info->user_id);

        //check for Access Permission
        $albumId = $comment_res->getAlbumId();
        $clubAlbum = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                          ->find($albumId);
        $club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                          ->find($clubAlbum->getGroupId());
        if($club and $club->getOwnerId()==$object_info->user_id){
            $do_action = 1;
        }
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        //ACL
        if ($do_action) {
               $dm->remove($comment_res);
               $dm->flush();

            $comment_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                                ->removeClubAlbumCommentsMedia($object_info->comment_id);
            if ($comment_media) {
               $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
               echo json_encode($res_p);
               exit();
            }
        } else {
            $res = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res);
            exit();
        }
    }

    /**
     * Delete media for comment on club album
     * @param request object
     * @return json string
     */
    public function deleteClubAlbumCommentMedia($object_info) {

        $data = array();
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $object_info->user_id));

        // get entity manager object
        $media_id = $object_info->item_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_media = $dm->getRepository('MediaMediaBundle:ClubAlbumCommentMedia')
                ->find($media_id);
        if (!$comment_media) {
            $res_p = array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_p);
            exit();
        }
        //for store ACL
    /*    $do_action = 0;
        $group_mask = $this->userCommentMediaRole($object_info->item_id, $object_info->user_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
    */
        //ACL

      //  if ($do_action) {

            if ($comment_media) {
                $dm->remove($comment_media);
                $dm->flush();
                $res_p = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_p);
            exit();
            }
    /*    } else {
            $res_p =  array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_p);
            exit();
        }
     */
    }

    public function sendCommentNotifications($from, $owner, $club_id, $clubSatus, $comment_id, $album_id, $albumTitle, $tagging, $club_name){
        $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $postService = $this->getContainer()->get('post_detail.service');
        $email_template_service = $this->getContainer()->get('email_template.service');
        $angular_app_hostname   = $this->getContainer()->getParameter('angular_app_hostname'); //angular app host
        $club_album_url     = $this->getContainer()->getParameter('club_album_url'); //store album url
        $link_url = $angular_app_hostname . $club_album_url . '/' . $club_id. '/'.$album_id. '/'.$clubSatus.'/'.$albumTitle;

        $message = $this->club_album_comment_msg;
        $ownerMessageType = $this->club_album_comment_type;
        $commentAuthorMessageType = $this->club_album_comment_on_commented_type;

        $commentedAuthors = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')->getCommentedUserIds($album_id);
        $commentedAuthors = array_diff($commentedAuthors, array($from, $owner));
        $uniqueAuthors = array_unique($commentedAuthors);
        $sender = $postService->getUserData($from);
        $senderName = trim(ucwords($sender['first_name']. ' '.$sender['last_name']));
        // web and push notification for photo owner
        if($from!=$owner){
            $postService->sendUserNotifications($from, $owner, $ownerMessageType, $message, $album_id, true, true, array($senderName, $club_name), 'CITIZEN', array('album_title'=>$albumTitle, 'club_id'=>$club_id), 'U', array('comment_id'=>$comment_id, 'club_id'=>$club_id));
            $ownerInfo = $postService->getUserData($owner);

            $locale = !empty($ownerInfo['current_language']) ? $ownerInfo['current_language'] : $this->getContainer()->getParameter('locale');
            $lang_array = $this->getContainer()->getParameter($locale);
            $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
            $subject = sprintf($lang_array['CLUB_ALBUM_COMMENT_SUBJECT'],$senderName, $club_name);
            $mail_link = sprintf($lang_array['CLUB_ALBUM_COMMENT_LINK'],$senderName, $club_name);
            $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['CLUB_ALBUM_COMMENT_CLICK_HERE'],$href);
            $bodyTitle = sprintf($lang_array['CLUB_ALBUM_COMMENT_BODY'],$senderName, $club_name);
            // HOTFIX NO NOTIFY MAIL
            //$postService->sendMail(array($ownerInfo), $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'CLUB_ALBUM_COMMENT_NOTIFICATION');
        }
        // notification for commented authors
        if(!empty($uniqueAuthors)){
            $authors = $postService->getUserData($uniqueAuthors, true);
            $recieverByLanguage = $postService->getUsersByLanguage($authors);
            foreach($recieverByLanguage as $lng=>$receivers){
                $locale = $lng===0 ? $this->getContainer()->getParameter('locale') : $lng;
                $lang_array = $this->getContainer()->getParameter($locale);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $subject = sprintf($lang_array['CLUB_ALBUM_ON_COMMENTED_SUBJECT'],$senderName);
                $mail_link = sprintf($lang_array['CLUB_ALBUM_ON_COMMENTED_LINK'],$senderName);
                $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['CLUB_ALBUM_ON_COMMENTED_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array['CLUB_ALBUM_ON_COMMENTED_BODY'],$senderName);
                // HOTFIX NO NOTIFY MAIL
                //$postService->sendMail($receivers, $bodyData, $bodyTitle, $subject, $sender['profile_image_thumb'], 'CLUB_ALBUM_COMMENT_NOTIFICATION');
            }
            $postService->sendUserNotifications($from, $uniqueAuthors, $commentAuthorMessageType, $message, $album_id, true, true, $senderName, 'CITIZEN', array('album_title'=>$albumTitle, 'club_id'=>$club_id), 'U', array('comment_id'=>$comment_id, 'club_id'=>$club_id));
        }

        if(!empty($tagging)){
            $postService->commentTaggingNotifications($tagging, $from, $album_id, $link_url, 'CLUB_ALBUM', true, array('album_title'=>$albumTitle, 'club_id'=>$club_id), false, array(), array($club_name));
        }
        return true;
    }

    private function getContainer(){
        return NManagerNotificationBundle::getContainer();
    }
}
