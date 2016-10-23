<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Media\MediaBundle\Document\UserMedia;
use Media\MediaBundle\Document\UserAlbum;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserManager\Sonata\UserBundle\Entity\User;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\BrokerUser;
use Notification\NotificationBundle\Document\UserNotifications;
use UserManager\Sonata\UserBundle\Entity\UserConnection;

class DemoV1Controller extends Controller {

    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = '/uploads/users/media/thumb/';
    // album path
    protected $user_media_album_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path_thumb_crop = '/uploads/users/media/thumb_crop/';
    protected $user_media_album_path = '/uploads/users/media/original/';
    protected $group_media_path = '/uploads/groups/original/';
    protected $group_media_path_thumb = 'uploads/groups/thumb/';
    protected $miss_param = '';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $resize_image_width = 200;
    protected $resize_image_height = 200;
    protected $cover_image_width = 902;
    protected $cover_image_height = 320;
    protected $club_cover_image_width = 902;
    protected $club_cover_image_height = 320;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
   

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
        foreach ($_FILES['user_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['user_media']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['user_media']['type'][$key] == 'image/jpeg' ||
                        $_FILES['user_media']['type'][$key] == 'image/jpg' ||
                        $_FILES['user_media']['type'][$key] == 'image/gif' ||
                        $_FILES['user_media']['type'][$key] == 'image/png'))) ||
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
     * Call api/upload action
     * @param Request $request	
     * @return array
     */
    public function postMediauploadsAction(Request $request) {

        //Code start for getting the request
        $data = array();
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeDataAction($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
         $de_serialize = $this->getRequestobj($request);
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','album_id','post_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = (int) $object_info->user_id;
        $album_id = isset($object_info->album_id) ? $object_info->album_id : '';

        $post_type = $de_serialize['post_type'];
        $allow_post_type = array('0','1');
        
        if ($this->getRequest()->getMethod() === 'POST') {
           if($post_type == 0){
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'ONLY_IMAGES_AND_VIDEO_ARE_ALLOWED', 'data' => $data);
            }
           
           
            //user media data
            $i = 0;
            $image_upload = $this->get('amazan_upload_object.service');
            foreach ($_FILES['user_media']['tmp_name'] as $key => $tmp_name) {

                $original_media_name = $_FILES['user_media']['name'][$key];
                
                $album_thumb_image_width  = $this->resize_image_width;
                $album_thumb_image_height = $this->resize_image_height;
                if (!empty($original_media_name)) { //if file name is not exists means file is not present.
                    $user_media_name = time() . strtolower(str_replace(' ', '', $_FILES['user_media']['name'][$key]));
                     //clean image name
                    $clean_name = $this->get('clean_name_object.service');
                    $user_media_name = $clean_name->cleanString($user_media_name);
                    
                    //end image name
                    $user_media_type = $_FILES['user_media']['type'][$key];
                    $user_media_type = explode('/', $user_media_type);
                    $user_media_type = $user_media_type[0];
                    
                    $image_info = getimagesize($_FILES['user_media']['tmp_name'][$key]);
                    $orignal_mediaWidth = $image_info[0];
                    $original_mediaHeight = $image_info[1];
                    //call service to get image type. Basis of this we save data 3,2,1 in db
                    $image_type_service = $this->get('user_object.service');
                    $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$album_thumb_image_width,$album_thumb_image_height);
                    
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $user_media = new UserMedia();
                    //checking if album exits for this login user in album database then
                    //upload media in that album
                    if (!(empty($album_id))) {
                        $album = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                                ->findBy(array('id' => $album_id, 'user_id' => $user_id));
                        if ($album) {
                            $user_media->setUserid($user_id);
                            $user_media->setName($user_media_name);
                            $user_media->setContenttype($user_media_type);
                            $user_media->setAlbumid($album_id);
                            $user_media->setEnabled(0);
                            $user_media->setImageType($image_type);

                            //there are more than one images make first image fetaured image
                            // this would be treat like Album featured image 
                            if ($i == 0) {
                                $user_media->setIsFeatured(1);
                            } else {
                                $user_media->setIsFeatured(0);
                            }
                            $i++;
                            $dm->persist($user_media);
                            $dm->flush();
                            
                            //get media id
                            $last_media_id = $user_media->getId();
                        } else {
                            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
                        }
                    } else { // upload the media in user id folder
                        $user_media->setUserid($user_id);
                        $user_media->setName($user_media_name);
                        $user_media->setContenttype($user_media_type);
                        $user_media->setAlbumid('');
                        $user_media->setEnabled(0);
                        $user_media->setImageType($image_type);
                        $user_media->upload($user_id, $key, $user_media_name, $album_id);
                        $dm->persist($user_media);
                        $dm->flush();
                        //get media id
                        $last_media_id = $user_media->getId();
                    }
                    $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_album_media_path').  $user_id . "/" . $album_id . '/';
                    $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('user_album_media_path') .  $user_id . "/" . $album_id . '/';
                    $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_album_media_path_thumb') . $user_id . "/" . $album_id . '/';
                    $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_album_media_path_thumb_crop') . $user_id . "/" . $album_id . '/';
                    $s3_post_media_path = $this->container->getParameter('s3_user_album_media_path'). $user_id;
                    $s3_post_media_thumb_path = $this->container->getParameter('s3_user_album_media_thumb_path'). $user_id;
                    $image_upload->imageUploadService($_FILES['user_media'],$key,$user_id,'user_album',$user_media_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path,$album_id);
                    
                    
                    //get media object
                     $medias = $dm->getRepository('MediaMediaBundle:UserMedia')
                                ->findOneBy(array('id' => $last_media_id));
                     $media_thumb = '';
                     $media_image_type = '';
                     if($medias){
                        $media_name = $medias->getName();
                        $media_image_type = $medias->getImageType();
                        $user_id  = $medias->getUserid();
                        $media_link  = $this->getS3BaseUri().$this->user_media_path.$user_id.'/'.$album_id."/".$media_name;
                        $media_thumb = $this->getS3BaseUri() . $this->user_media_path_thumb . $user_id . '/'. $album_id.'/'. $media_name;
                     }
                    //sending the current media and post data.
                    $data = array(
                        'media_id' => $last_media_id,
                        'media_link' => $media_link,
                        'media_thumb_link' => $media_thumb,
                        'image_type' =>$media_image_type
                    );
                    $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                    echo json_encode($resp_data);
                    exit();
                    
                     
                }
            }    
            }else{
                $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
                
                if(isset($object_info->tagged_friends))
                {
                    if(trim($object_info->tagged_friends))
                    {
                        $object_info->tagged_friends =  explode(',',$object_info->tagged_friends);
                    } else {
                        $object_info->tagged_friends = array();
                    }
                } else {
                    $object_info->tagged_friends = array();
                }

                //get media array
                $media_array = $object_info->media_id;
                $tagged_friends = $object_info->tagged_friends;
                
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                                
                $media_details = $dm->getRepository('MediaMediaBundle:UserMedia')
                                ->getMediaDetail($media_array);
                //getting media details
                if($album_id)
                {
                    $album_detail = $dm->getRepository('MediaMediaBundle:UserAlbum')->find($album_id);
                    $album_name = $album_detail->getAlbumName();
                    $album_user_id = $album_detail->getUserId();
                }
                
                
                //update in notification table / send email
                foreach($media_details as $media)
                {
                    if( is_array($object_info->tagged_friends))
                    {
                        if( is_array($media->getTaggedFriends()) && count($media->getTaggedFriends())){ 
                            $fid = array_diff($tagged_friends, $media->getTaggedFriends());
                          
                        } else {
                            $fid = $object_info->tagged_friends;
                        }

                        if(count($fid))
                        { 
                            //find user object service..
                            $user_service = $this->get('user_object.service');
                            //get user profile and cover images..
                            $users_object_array = $user_service->MultipleUserObjectService($user_id);

                            $msgtype = 'tagging';
                            $msg = 'TAGGED_IN_PHOTO';
                            
                            $media_id = $media->getid();
                           
//                            foreach($fid as $id)
//                            {
//                                //update notification
//                                $add_notification = $this->saveUserNotification($user_id, $id, $msgtype, $msg, $media_id);
//                            }
                            $postService = $this->container->get('post_detail.service');
                            $email_template_service = $this->container->get('email_template.service'); //email template service.
                            $locale = $this->container->getParameter('locale');
                            $language_const_array = $this->container->getParameter($locale);
                            $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
                            $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

                            $sender = $postService->getUserData($user_id);
                            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                            
                            $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $media_id, true, true, $sender_name);

                            $receivers = $postService->getUserData($fid, true);
                            $recieverByLanguage = $postService->getUsersByLanguage($receivers);
                            $emailResponse = '';
                            foreach($recieverByLanguage as $lng=>$reciever){
                            
                                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                                $lang_array = $this->container->getParameter($locale);
                                
                                $mail_text = sprintf($lang_array['FRIEND_TAGGING_IN_PHOTO_MAIL_TEXT'], ucwords($sender_name));
                                $href =  $email_template_service->getDashboardAlbumUrl(array('friendId'=>$album_user_id, 'albumId'=> $album_id, 'albumName'=> $album_name), true);
                                $bodyData =  $mail_text."<br><br>".$email_template_service->getLinkForMail($href,$locale); //making the link html from service
                                $subject = sprintf($lang_array['YOU_GOT_TAGGED_IN_PHOTO'], ucwords($sender_name));
                                $mail_body = sprintf($lang_array['FRIEND_TAGGING_IN_PHOTO_BODY'], ucwords($sender_name));

                                $emailResponse = $email_template_service->sendMail($reciever, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                            
                            }
                        }
                    }
                }

                //publish the images
                $medias = $dm->getRepository('MediaMediaBundle:UserMedia')
                                ->publishAlbumImage($media_array,$tagged_friends);
                $data = array(
                    'avg_rate'=>0,
                    'no_of_votes' =>0,
                    'current_user_rate'=>0,
                    'is_rated' =>false
                );
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit();
                
            }
            

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Functionality return Media list
     * @param json $request
     * @return array
     */
    public function postListusermediasAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id     =  (int) $object_info->user_id;
        $limit_start =  (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
        $limit_size  =  (isset($object_info->limit_size)  ? (int) $object_info->limit_size : 20);
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        // $medias = $this->get('doctrine_mongodb')->getRepository('AcmeDemoBundle:UserMedia')->findAll();
        $medias = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                ->findBy(array('userid' => (int) $object_info->user_id), null, $limit_size, $limit_start);
        $mediaData = array();
        foreach ($medias as $mediadata) {
            $media_id = $mediadata->getId();
            $media_name = $mediadata->getName();
            $media_type = $mediadata->getContenttype();
            //  $mediaPath  = $this->getBaseUri().$mediadirec.'/'.$postId.'/'.$mediaName;
            $thumb_dir = $this->getS3BaseUri() . $this->user_media_path_thumb . $user_id . '/' . $media_name;
            $mediaData[] = array('id' => $media_id,
                'media_name' => $media_name,
                'media_type' => $media_type,
                'media_path' => $thumb_dir,
            );
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $mediaData);
        echo json_encode($res_data);
        exit();
    }

    /**
     * deleting the media on user_id and media_id basis.
     * @param request object
     * @param json
     */
    public function postDeleteusermediasAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','media_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $user_id = $object_info->user_id;
        $media_id = $object_info->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $medias = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);
        if (!$medias) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }
        if ($medias) {
            //remove from media table
            $dm->remove($medias);
            $dm->flush();
            //Remove media files from directory
            $user_media = $medias->getName();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            $image_album_location = $file_location . '/uploads/users/media/original/' . $user_id . '/' . $user_media;
            $thumbnail_album_location = $file_location . '/uploads/users/media/thumb/' . $user_id . '/' . $user_media;
           
            //as image will not exist, so commented the code
            if (file_exists($thumbnail_album_location)) {
              //unlink($thumbnail_album_location);
            }
            if (file_exists($image_album_location)) {
              //unlink($image_album_location);
            }
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }


    /**
     * Crete Album for the media on user_id.
     * @param request object
     * @param json
     */
    public function postCreateuseralbumsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','album_name');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = (int) $object_info->user_id;
        $album_name = $object_info->album_name;
        $album_desc = (isset($object_info->album_desc) ? $object_info->album_desc : '');
        //default privacy setting for album is public
        $privacy_setting = $object_info->privacy_setting = (isset($object_info->privacy_setting) ? ($object_info->privacy_setting) : 3);
        
        //getting the privacy setting array
        $privacy_setting_constant        = $this->get('privacy_setting_object.service');
        $privacy_setting_constant_result = $privacy_setting_constant->AlbumPrivacySettingService();
        

        if (!in_array($privacy_setting, $privacy_setting_constant_result)) {
            return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
        }
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $user_album = new UserAlbum();
        $user_album->setAlbumName($album_name);
        $user_album->setAlbumDesc($album_desc);
        $user_album->setUserId($user_id);
        $user_album->setPrivacySetting($privacy_setting);
        $user_album->setCreatedAt(time());
        $dm->persist($user_album);
        $dm->flush();
        $album_id = $user_album->getId();
        $document_root = $request->server->get('DOCUMENT_ROOT');
        $BasePath = $request->getBasePath();
        $file_location = $document_root . $BasePath; // getting sample directory path
        $image_album_location = $file_location . '/uploads/users/media/original/' . $user_id . '/' . $album_id;
        $thumbnail_album_location = $file_location . '/uploads/users/media/thumb/' . $user_id . '/' . $album_id;
        if (!file_exists($image_album_location)) {
            \mkdir($image_album_location, 0777, true);
            \mkdir($thumbnail_album_location, 0777, true);
            $data = array(
                'avg_rate'=>0,
                'no_of_votes' =>0,
                'current_user_rate'=>0,
                'is_rated' =>false
            );
            $res_data = array('code' => 101, 'message' => 'ALBUM_IS_CREATED_SUCCESSFULLY', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * deleting the album of user.
     * @param request object
     * @param json
     */
    public function postDeleteuseralbumsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','album_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $album = $dm->getRepository('MediaMediaBundle:UserAlbum')->find($album_id);

        if (!$album) {
            return array('code' => 1008, 'message' => 'ALBUM_DOES_NOT_EXISTS', 'data' => $data);
        }
        if ($album) {
            //remove from media table
            $dm->remove($album);
            $dm->flush();
            //remove corresponding media

            $album_media = $dm->getRepository('MediaMediaBundle:UserMedia')
                    ->removeAlbumMedia($album_id);
            if ($album_media) {
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $image_album_location = $file_location . '/uploads/users/media/original/' . $user_id . '/' . $album_id;
                $thumbnail_album_location = $file_location . '/uploads/users/media/thumb/' . $user_id . '/' . $album_id;

                //as image will not exist, so commented the code
                if (file_exists($image_album_location)) {
                  // array_map('unlink', glob($image_album_location . '/*'));
                  //rmdir($image_album_location);
                }
                if (file_exists($thumbnail_album_location)) {
                  //  array_map('unlink', glob($thumbnail_album_location . '/*'));
                  //  rmdir($thumbnail_album_location);
                }
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
        }
    }

    /**
     * deleting the media of album of user.
     * @param request object
     * @param json
     */
    public function postDeletealbummediasAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','media_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $media_id = $object_info->media_id;
        $album_id = isset($object_info->album_id) ? $object_info->album_id : '';
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $medias = $dm->getRepository('MediaMediaBundle:UserMedia')->find($media_id);
        if (!$medias) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }
        if ($medias) {
            //remove from media table
            $dm->remove($medias);
            $dm->flush();
            //Remove media files from directory
            $user_media = $medias->getName();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            if ($album_id) {
                $image_location = $file_location . '/uploads/users/media/original/' . $user_id . '/' . $album_id . '/' . $user_media;
                $thumbnail_location = $file_location . '/uploads/users/media/thumb/' . $user_id . '/' . $album_id . '/' . $user_media;
            } else {
                $image_location = $file_location . '/uploads/users/media/original/' . $user_id . '/' . $user_media;
                $thumbnail_location = $file_location . '/uploads/users/media/thumb/' . $user_id . '/' . $user_media;
            }
            
            //as image will not exist, so commented the code
            if (file_exists($thumbnail_location)) {
              //unlink($thumbnail_location);
            }
            if (file_exists($image_location)) {
              //unlink($image_location);
            }
            
            //remove notification related to particular image 
            $media_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')->findBy(array("item_id"=>"$media_id"));
            if($media_notifications)
            {
                foreach($media_notifications as $notification)
                {
                    $dm->remove($notification);
                    $dm->flush();
                }
            }
            
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
            
        }
    }

    /**
     * Functionality return Album list
     * @param json $request
     * @return array
     */
   
    /*
    public function postListuseralbumsAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id     = (int)$object_info->user_id;
        $limit_start =(isset($object_info->limit_start) ? (int)$object_info->limit_start : 0 );
        $limit_size  =(isset($object_info->limit_size)  ?  (int)$object_info->limit_size : 20);
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        $tagged_photos= $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                         ->getTaggedPhotos($user_id);
        
        $tagged_photos_count = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                         ->getTaggedPhotosCount($user_id);
       
        $photo_of_you = array();
        if(count($tagged_photos)){
            foreach($tagged_photos as $tagged_photo){
                
                $fetaured_media_name = $tagged_photo->getName();
                $album_id = $tagged_photo->getAlbumid();
                $fetaured_media_path = $this->getS3BaseUri() . $this->user_media_path_thumb . $user_id . '/' . $album_id . '/' . $fetaured_media_name;
                
                $photo_of_you['album_name'] = "Photo Of You";
                $photo_of_you['media_in_album'] = $tagged_photos_count;
                $photo_of_you['featured_media_path'] = $fetaured_media_path;
                
            }
        }
        
        //get total count
        $albums_count = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                         ->getUserAlbumCount($user_id);
        $albums = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                ->findBy(array('user_id' => $user_id), null, $limit_size, $limit_start);
        $album_datas = array();
        $album_ids_array = array();
        foreach ($albums as $album_data) {
            //preapare album array
            $album_ids_array[] = $album_data->getId();
        }
        
        //get media count featured image
        $album_media_details = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                    ->getAlbumMediaInfo($album_ids_array ,$user_id );
       
            
        foreach ($albums as $album_data) {
            $album_medias = array();
            $featured_media = array();
            $album_id = $album_data->getId();
            $album_name = $album_data->getAlbumName();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            $media_dir = $file_location . $this->user_media_album_path . $user_id . '/' . $album_id;
            $thumb_dir = $file_location . $this->user_media_album_path_thumb . $user_id . '/' . $album_id;
            $album_desc = $album_data->getAlbumDesc();

//            $album_medias = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
//                    ->findBy(array('albumid' => $album_id, 'userid' => $user_id, 'enabled'=>1));
            
            if(isset($album_media_details[$album_id])){
            $album_medias = $album_media_details[$album_id];
            }
            $media_in_album = count($album_medias);

            //$featured_media = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
             //       ->findOneBy(array('albumid' => $album_id, 'userid' => $user_id, 'is_featured' => 1,'enabled'=>1));
            
            if(isset($album_media_details[$album_id])){
            $featured_album_media = $album_media_details[$album_id];
            foreach($featured_album_media as $featured_album_single_media){
                $is_featured = $featured_album_single_media->getIsFeatured();
                if($is_featured){
                    $featured_media = $featured_album_single_media;
                }
            }
            }
            
            
            $current_rate = 0;
            $is_rated = false;
            $rate_data_obj = $album_data->getRate();
            if(count($rate_data_obj) > 0) {
                foreach($rate_data_obj as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
            }
        
            
            if ($featured_media) {
                $fetaured_media_name = $featured_media->getName();
                $fetaured_image_type = $featured_media->getImageType();
                $fetaured_media_path = $this->getS3BaseUri() . $this->user_media_path_thumb . $user_id . '/' . $album_id . '/' . $fetaured_media_name;
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $album_data->getCreatedAt(),
                    'album_path' => '',
                    'thumb_path' => '',
                    'media_in_album' => $media_in_album,
                    'featured_media_path' => $fetaured_media_path,
                    'image_type' =>$fetaured_image_type,
                    'album_description' => $album_desc,
                    'avg_rate'=>round($album_data->getAvgRating(),1),
                    'no_of_votes' => (int) $album_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' =>$is_rated
                );
            } else {
                $fetaured_media_name = '';
                $fetaured_media_path = '';
                $fetaured_image_type = '';
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $album_data->getCreatedAt(),
                    'album_path' => '',
                    'thumb_path' => '',
                    'media_in_album' => $media_in_album,
                    'featured_media_path' => $fetaured_media_path,
                    'image_type' =>$fetaured_image_type,
                    'media_in_album' => $media_in_album,
                    'album_description' => $album_desc,
                    'avg_rate'=>round($album_data->getAvgRating(),1),
                    'no_of_votes' => (int) $album_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' =>$is_rated
                );
            }
            //$album_datas['total'] = $albums_count;
        }

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('albums' =>$album_datas, 'size'=> $albums_count, 'tagged_photo'=>$photo_of_you ));
        echo json_encode($resp_data);
        exit();
    }
    */
    
    /**
     * Functionality return Album list
     * @param json $request
     * @return array
     */
    
    public function postListuseralbumsAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','friend_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id     = (int)$object_info->user_id;
        $friend_id   = (int)$object_info->friend_id;
        $limit_start =(isset($object_info->limit_start) ? (int)$object_info->limit_start : 0 );
        $limit_size  =(isset($object_info->limit_size)  ?  (int)$object_info->limit_size : 20);
        //getting document manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        // checking user is on his wall or another user wall
        
        $is_personal_friend = 0;
        $is_professional_friend = 0;
        
        if($user_id == $friend_id){ // user is seeing his album list (personal, professional and public all type of albums)
            
            $albums = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                           ->findBy(array('user_id' => $user_id), null, $limit_size, $limit_start);
            //get total count
            $albums_count = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                         ->getUserAlbumCount($user_id);
                       
        } else { // user is seeing another user album list
            //check search user is personal friend of current user
            $personal_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                                      ->checkPersonalFriendShip($user_id, $friend_id);
            //check sercahc user is professional friend of current user
           
            $professional_friend_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                            ->checkProfessionalFriendShip($user_id, $friend_id);
            $_friend_type = 0;
            if ($personal_friend_check) {
                // personal=1
                $_friend_type += 1;
                
            }
            if($professional_friend_check) {
                // professional=2
                $_friend_type += 2;
            }
            
            $albums = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                                ->getFriendAlbums($friend_id,$_friend_type,$limit_size,$limit_start);
            $albums_count = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                                ->getFriendAlbumsCount($friend_id,$_friend_type);
            
        }
        
        $tagged_photos= $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                         ->getTaggedPhotos($friend_id);
        
        $tagged_photos_count = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                         ->getTaggedPhotosCount($friend_id);
       
        $photo_of_you = array();
        if(count($tagged_photos)){
            foreach($tagged_photos as $tagged_photo){
                
                $fetaured_media_name = $tagged_photo->getName();
                $album_id = $tagged_photo->getAlbumid();
                $image_owner = $tagged_photo->getUserid();
                $fetaured_media_path = $this->getS3BaseUri() . $this->user_media_path_thumb . $image_owner . '/' . $album_id . '/' . $fetaured_media_name;
                
                $photo_of_you['album_name'] = "Photo Of You";
                $photo_of_you['media_in_album'] = $tagged_photos_count;
                $photo_of_you['featured_media_path'] = $fetaured_media_path;
                
            }
        }
        
        $album_datas = array();
        $album_ids_array = array();
        foreach ($albums as $album_data) {
            //preapare album array
            $album_ids_array[] = $album_data->getId();
        }
        
        //get media count featured image
        if($user_id == $friend_id){
            $album_media_details = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                    ->getAlbumMediaInfo($album_ids_array ,$user_id );
        } else {

            $album_media_details = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                         ->getFriendAlbumMediaInfo($album_ids_array ,$friend_id );
           
        }
        foreach ($albums as $album_data) {

            $album_medias = array();
            $featured_media = array();
            $album_id = $album_data->getId();
            $album_name = $album_data->getAlbumName();
            $album_privacy = $album_data->getPrivacySetting();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            $media_dir = $file_location . $this->user_media_album_path . $friend_id . '/' . $album_id;
            $thumb_dir = $file_location . $this->user_media_album_path_thumb . $friend_id . '/' . $album_id;
            $album_desc = $album_data->getAlbumDesc();

//            $album_medias = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
//                    ->findBy(array('albumid' => $album_id, 'userid' => $user_id, 'enabled'=>1));
            
            if(isset($album_media_details[$album_id])){
            $album_medias = $album_media_details[$album_id];
            }
            
            $media_in_album = count($album_medias);
           

            //$featured_media = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
             //       ->findOneBy(array('albumid' => $album_id, 'userid' => $user_id, 'is_featured' => 1,'enabled'=>1));
            
            if(isset($album_media_details[$album_id])){
            $featured_album_media = $album_media_details[$album_id];
            foreach($featured_album_media as $featured_album_single_media){
                $is_featured = $featured_album_single_media->getIsFeatured();
                if($is_featured){
                    $featured_media = $featured_album_single_media;
                }
            }
            }
            
            /** fetch rating of current user **/
            $current_rate = 0;
            $is_rated = false;
            $rate_data_obj = $album_data->getRate();
            if(count($rate_data_obj) > 0) {
                foreach($rate_data_obj as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
            }
        
            
            if ($featured_media) {
                $fetaured_media_name = $featured_media->getName();
                $fetaured_image_type = $featured_media->getImageType();
                $fetaured_media_path = $this->getS3BaseUri() . $this->user_media_path_thumb . $friend_id . '/' . $album_id . '/' . $fetaured_media_name;
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $album_data->getCreatedAt(),
                    'album_path' => '',
                    'thumb_path' => '',
                    'media_in_album' => $media_in_album,
                    'featured_media_path' => $fetaured_media_path,
                    'image_type' =>$fetaured_image_type,
                    'album_description' => $album_desc,
                    'avg_rate'=>round($album_data->getAvgRating(),1),
                    'no_of_votes' => (int) $album_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' =>$is_rated,
                    'album_privacy' =>$album_privacy
                );
            } else {
                $fetaured_media_name = '';
                $fetaured_media_path = '';
                $fetaured_image_type = '';
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $album_data->getCreatedAt(),
                    'album_path' => '',
                    'thumb_path' => '',
                    'media_in_album' => $media_in_album,
                    'featured_media_path' => $fetaured_media_path,
                    'image_type' =>$fetaured_image_type,
                    'media_in_album' => $media_in_album,
                    'album_description' => $album_desc,
                    'avg_rate'=>round($album_data->getAvgRating(),1),
                    'no_of_votes' => (int) $album_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' =>$is_rated,
                    'album_privacy' =>$album_privacy
                );
            }
            //$album_datas['total'] = $albums_count;
        }
           
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('albums' =>$album_datas, 'size'=> $albums_count, 'tagged_photo'=>$photo_of_you ));
        echo json_encode($resp_data);
        exit();
    }
    
    

    /**
     * Functionality return Album view
     * @param json $request
     * @return array
     */
    public function postViewuseralbumsAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $album_name = '';
        $album_desc = '';
        $album_info = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id', 'album_id', 'friend_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_ ' . $this->miss_param, 'data' => $data);
        }
        
        $limit_start =(isset($object_info->limit_start) ? (int)$object_info->limit_start : 0 );
        $limit_size  =(isset($object_info->limit_size)  ?  (int)$object_info->limit_size : 20);
        
        $user_id = (int) $object_info->user_id;
        $album_user_id = (int) $object_info->friend_id;
        $album_id = $object_info->album_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $albums = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserAlbum')
                ->findOneBy(array('id' => $album_id, 'user_id' => $album_user_id));
        if ($albums) {
            //get album info
            $album_name = $albums->getAlbumName();
            $album_desc = $albums->getAlbumDesc();
            $album_current_rate = 0;
            $album_is_rated = false;
            foreach($albums->getRate() as $rate) {
                if($rate->getUserId() == $user_id ) {
                    $album_current_rate = $rate->getRate();
                    $album_is_rated = true;
                    break;
                }
            }
            //prepare album  info array
            $album_info = array(
                'title' => $album_name,
                'description' =>$album_desc,
                'avg_rate'=>round($albums->getAvgRating(), 1),
                'no_of_votes'=> (int) $albums->getVoteCount(),
                'current_user_rate'=>$album_current_rate,
                'is_rated' => $album_is_rated
                );
            
            $album_medias_count = $dm->getRepository('MediaMediaBundle:UserMedia')
                    ->getUserAlbumMediaCount($album_id);
            
            $user_friend_service      = $this->get('user_friend.service');
                        
            $album_medias = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                    ->findBy(array('albumid' => $album_id, 'enabled'=>1), null, $limit_size, $limit_start);
                    //->findBy(array('albumid' => $album_id), null, $limit_size, $limit_start);
           // echo "<pre>"; print_r($album_medias); exit;
            $media_data = array();
            foreach ($album_medias as $album_media) {
                $media_id = $album_media->getId();
                $media_name = $album_media->getName();
                $media_type = $album_media->getContenttype();
                $media_image_type = $album_media->getImageType();
                $mediaPath = $this->getS3BaseUri() . $this->user_media_album_path . $album_user_id . '/' . $album_id . '/' . $media_name;
                $thumbDir = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $album_user_id . '/' . $album_id . '/' . $media_name;
                                
                if(count($album_media->getTaggedFriends())){ 
                    $tagged_friends_info  = $user_friend_service->getTaggedUserInfo(implode(',',$album_media->getTaggedFriends()));
                } else {
                    $tagged_friends_info  = array();
                }
                
                $current_rate = 0;
                $is_rated = false;
                foreach($album_media->getRate() as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                $media_data[] = array('id' => $media_id,
                    'media_name' => $media_name,
                    'media_type' => $media_type,
                    'media_path' => $mediaPath,
                    'thumb_path' => $thumbDir,
                    'image_type' =>$media_image_type,
                    'tagged_friends_info' => $tagged_friends_info,
                    'avg_rate'=>round($album_media->getAvgRating(), 1),
                    'no_of_votes'=> (int) $album_media->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' => $is_rated
                );
            } 
            $data = array('media' =>$media_data, 'size' =>$album_medias_count, 'album'=> $album_info);
            $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        } else {
            $resp_data = array('code' => 1011, 'message' => 'NO_RECORDS_EXISTS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createThumbnail_old($filename, $media_original_path, $thumb_dir, $user_id, $album_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/';
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = 200;
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
        $nx = $final_width_of_image;
        $ny = floor($oy * ($final_width_of_image / $ox));
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        imagejpeg($nm, $path_to_thumbs_directory . $filename);
    }
    
    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail_old($filename, $media_original_path, $thumb_dir, $user_id, $album_id) {
        //image crop size
        $crop_image_width = 200;
        $crop_image_height = 200;
        
        $imagename = $filename;
        $filename = $media_original_path.$filename;
        //get the thumb directory path
        $path_to_thumbs_center_directory =  __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/';
         //get the image center 
        $path_to_thumbs_center_image_path = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/'.$imagename;
        
        if(preg_match('/[.](jpg)$/', $imagename)) {  
            $image = imagecreatefromjpeg($filename);  
        } else if (preg_match('/[.](jpeg)$/', $imagename)) {  
            $image = imagecreatefromjpeg($filename);  
        } else if (preg_match('/[.](gif)$/', $imagename)) {  
            $image = imagecreatefromgif($filename);  
        } else if (preg_match('/[.](png)$/', $imagename)) {  
            $image = imagecreatefrompng($filename);  
        }
 
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width = imagesx($image);
        $height = imagesy($image);

        

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
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $current_width, $current_height);
        
      
        //   $path_to_thumbs_directory = $thumb_dir;
        //$path_to_image_directory = $media_original_path;
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
    }

    /**
     * uploading the user profile image.
     * @param request object
     * @return json
     */
    public function postUploaduserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }

        if (!isset($_FILES['user_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['user_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $user_id = $object_info->user_id;
        
        if (!empty($original_media_name)) { //if file name is not exists means file is not present.
            $user_media_name = time() . strtolower(str_replace(' ', '', $_FILES['user_media']['name']));
            //clean image name
            $clean_name = $this->get('clean_name_object.service');
            $user_media_name = $clean_name->cleanString($user_media_name);
            //end image name
            $user_media_type = $_FILES['user_media']['type'];
            $user_media_type = explode('/', $user_media_type);
            $user_media_type = $user_media_type[0];
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $user_media = new UserMedia();
            //checking if album exits for this login user in album database then
            $user_media->setUserid($user_id);
            $user_media->setName($user_media_name);
            $user_media->setContenttype($user_media_type);
            $user_media->setAlbumid('');
            $dm->persist($user_media);
            $dm->flush();

            $media_id = $user_media->getId();
            $image_upload = $this->get('amazan_upload_object.service');
            $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_profile_image_media_path'). $user_id . "/";
            $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('user_profile_image_media_path') . $user_id . "/";
            $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_profile_image_media_path_thumb') . $user_id . "/";
            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('user_profile_image_media_path_thumb_crop') . $user_id . "/";
            $s3_post_media_path = $this->container->getParameter('s3_user_profile_image_media_path'). $user_id;
            $s3_post_media_thumb_path = $this->container->getParameter('s3_user_profile_image_media_thumb_path'). $user_id;
            $image_upload->imageUploadService($_FILES['user_media'],null,$user_id,'user_album',$user_media_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                    
            if ($user_media_type == 'image') {
                $mediaOriginalPath = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/";
                $thumbDir = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";

                $resizeOriginalDir = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/";
               
                //rotate the image if orientaion is not actual.
                if (preg_match('/[.](jpg)$/', $user_media_name) || preg_match('/[.](jpeg)$/', $user_media_name)) {
                $image_rotate_service        = $this->get('image_rotate_object.service');
                $image_rotate = $image_rotate_service->ImageRotateService($mediaOriginalPath . $user_media_name);
                }
                //end of image rotate

                $this->resizeOriginal($user_media_name, $mediaOriginalPath, $resizeOriginalDir, $user_id); 
                $this->createProfileImageThumbnail($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
                $this->cropProfileImage($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
            }

            if ($media_id) {
                //get user object
                $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
                if (!$user) {
                    return array('code' => 100, 'message' => 'USER_DOES_NOT_EXIT', 'data' => $data);
                }
                $user->setProfileImg($media_id);
                //set album id for users 
                $user->setAlbumId(0);
                //save profile image in the fos table
                $user->setProfileImageName($user_media_name);
                $this->container->get('fos_user.user_manager')->updateUser($user);
                $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                
                 //get s3 image url
                $user_service = $this->get('user_object.service');
                $user_object = $user_service->UserObjectService($user_id);
                $user_object_profile_img = $user_object['profile_image_thumb'];
                // call applane service  
                $appalne_data = array('profile_img' =>$user_object_profile_img, 'user_id'=> $user_id);
      
                //get dispatcher object
                $event = new FilterDataEvent($appalne_data);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('citizen.updateprofileimg', $event);
                //end of applane service
                
                echo json_encode($resp_data);
                exit();
            } else {
                $resp_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
                echo json_encode($resp_data);
                exit();
            }
        } else {
            $resp_data = array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
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
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileType() {
        $file_error = 0;
        if (!isset($_FILES['user_media'])) {
            return $file_error;
        }
        $file_name = basename($_FILES['user_media']['name']);
        //$filecheck = basename($_FILES['imagefile']['name']);
        if (!empty($file_name)) {
            $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
            //for video and images.

            if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                    ($_FILES['user_media']['type'] == 'image/jpeg' ||
                    $_FILES['user_media']['type'] == 'image/jpg' ||
                    $_FILES['user_media']['type'] == 'image/gif' ||
                    $_FILES['user_media']['type'] == 'image/png'))))) {
                $file_error = 1;
            }
        }
        return $file_error;
    }

    /**
     * create thumbnail for  a user profile image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createProfileImageThumbnail_old($filename, $media_original_path, $thumb_dir, $user_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = 200;
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
        $nx = $final_width_of_image;
        $ny = floor($oy * ($final_width_of_image / $ox));
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        imagejpeg($nm, $path_to_thumbs_directory . $filename);
    }

    /**
     * set the user profile image from a album.
     * @param request object
     * @return json
     */
    public function postSetuserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'media_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $media_id = $object_info->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //get image from table
        $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($media_id);
        if (!$media_info) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }
        //get user object
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
        if (!$user) {
            return array('code' => 100, 'message' => 'USER_ID_IS_INVALID', 'data' => $data);
        }
        

         //get media info
      
        $filename = $media_info->getName();
        $album_id = $media_info->getAlbumid();
        
        //below line should be uncommented.
        $user->setProfileImg($media_id);
         //set album id for users 
        $user->setAlbumId($album_id);
         //save profile image in the fos table
        $user->setProfileImageName($filename);
        $this->container->get('fos_user.user_manager')->updateUser($user);
        
        //uploads/users/media/original/
      
        $mediaOriginalPath = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/". $album_id . '/';
      
        $this->createUseralbumThumbnail($filename, $mediaOriginalPath, $user_id, $album_id);
        $this->cropAlbumProfileImage($filename, $mediaOriginalPath,$user_id, $album_id);
        
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        
        echo json_encode($resp_data);
        exit();
    }

    /**
     * uploading the citizen user profile image.
     * @param request object
     * @return json
     */
    public function postUploadcitizenuserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }

        if (!isset($_FILES['user_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['user_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $user_id = $object_info->user_id;
        if (!empty($original_media_name)) { //if file name is not exists means file is not present.
            $user_media_name = time() . strtolower(str_replace(' ', '', $_FILES['user_media']['name']));
            $user_media_type = $_FILES['user_media']['type'];
            $user_media_type = explode('/', $user_media_type);
            $user_media_type = $user_media_type[0];
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $user_media = new UserMedia();
            //checking if album exits for this login user in album database then
            $user_media->setUserid($user_id);
            $user_media->setName($user_media_name);
            $user_media->setContenttype($user_media_type);
            $user_media->setAlbumid('');
            $user_media->profileImageUpload($user_id, $user_media_name);
            $dm->persist($user_media);
            $dm->flush();

            $media_id = $user_media->getId();
            if ($user_media_type == 'image') {
                $mediaOriginalPath = $this->getBaseUri() . $this->user_media_album_path . $user_id . '/';
                $thumbDir = $this->getBaseUri() . $this->user_media_path_thumb . $user_id . '/';
                $this->createProfileImageThumbnail($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
            }

            if ($media_id) { // if media upload and inserted in database.
                // get entity manager object
                $em = $this->getDoctrine()->getManager();
                $citizen_user_info = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')->findBy(array('userId' => $user_id));
                if (!$citizen_user_info) {
                    return array('code' => 100, 'message' => 'USER_PROFILE_DOES_NOT_EXISTS', 'data' => $data);
                }
                $citizen_user = $citizen_user_info[0];
                //below line should be uncommented
                $citizen_user->setProfileImg($media_id);
                $em->persist($citizen_user);
                $em->flush();
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            } else {
                return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
    }

    /**
     * set the citizen user profile image from a album.
     * @param request object
     * @return json
     */
    public function postSetcitizenuserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'media_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $media_id = $object_info->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //get image from table
        $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($media_id);
        if (!$media_info) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $citizen_user_info = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')->findBy(array('userId' => $user_id));
        if (!$citizen_user_info) {
            return array('code' => 100, 'message' => 'USER_PROFILE_DOES_NOT_EXISTS', 'data' => $data);
        }
        $citizen_user = $citizen_user_info[0];
        //below line should be uncommented
        $citizen_user->setProfileImg($media_id);
        $em->persist($citizen_user);
        $em->flush();
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
    }
    
    /**
     * uploading the broker user profile image.
     * @param request object
     * @return json
     */
    public function postUploadbrokeruserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }

        if (!isset($_FILES['user_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['user_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $user_id = $object_info->user_id;
        if (!empty($original_media_name)) { //if file name is not exists means file is not present.
            $user_media_name = time() . strtolower(str_replace(' ', '', $_FILES['user_media']['name']));
            $user_media_type = $_FILES['user_media']['type'];
            $user_media_type = explode('/', $user_media_type);
            $user_media_type = $user_media_type[0];
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $user_media = new UserMedia();
            //checking if album exits for this login user in album database then
            $user_media->setUserid($user_id);
            $user_media->setName($user_media_name);
            $user_media->setContenttype($user_media_type);
            $user_media->setAlbumid('');
            $user_media->profileImageUpload($user_id, $user_media_name);
            $dm->persist($user_media);
            $dm->flush();

            $media_id = $user_media->getId();
            if ($user_media_type == 'image') {
                $mediaOriginalPath = $this->getBaseUri() . $this->user_media_album_path . $user_id . '/';
                $thumbDir = $this->getBaseUri() . $this->user_media_path_thumb . $user_id . '/';
                //$this->createProfileImageThumbnail($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
                  $this->createProfileImageThumbnail($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
                  $this->cropProfileImage($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
            }

            if ($media_id) { // if media upload and inserted in database.
                // get entity manager object
                $em = $this->getDoctrine()->getManager();
                $broker_user_info = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')->findBy(array('userId' => $user_id));
                if (!$broker_user_info) {
                    return array('code' => 100, 'message' => 'USER_PROFILE_DOES_NOT_EXISTS', 'data' => $data);
                }
                $broker_user = $broker_user_info[0];
                //below line should be uncommented
                $broker_user->setProfileImg($media_id);
                $em->persist($broker_user);
                $em->flush();
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            } else {
                return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
    }
    
    /**
     * set the broker user profile image from a album.
     * @param request object
     * @return json
     */
    public function postSetbrokeruserprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'media_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $media_id = $object_info->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //get image from table
        $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($media_id);
        if (!$media_info) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $broker_user_info = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')->findBy(array('userId' => $user_id));
        if (!$broker_user_info) {
            return array('code' => 100, 'message' => 'USER_PROFILE_DOES_NOT_EXISTS', 'data' => $data);
        }
        $broker_user = $broker_user_info[0];
        //below line should be uncommented
        $broker_user->setProfileImg($media_id);
        $em->persist($broker_user);
        $em->flush();
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
    }
    
    /**
     * set the club profile image from a album.
     * @param request object
     * @return json
     */
    public function postSetclubprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('group_id', 'media_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        
        $group_id = $object_info->group_id;
        $media_id = $object_info->media_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
       
        // reset all group media
        $updated_result = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                             ->removeFeaturedImage($group_id);
        
        $media_info = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->find($media_id);
        $media_original_path = '';
        $thumb_dir           = __DIR__ . "/../../../../web/" . $this->group_media_path_thumb . $group_id . '/';
        
        //Resize selected media image
        if($media_info){
            $album_id = $media_info->getAlbumId();
            $aws_original_path = '';
            if($album_id){
                $media_original_path = __DIR__ . "/../../../../web" . $this->group_media_path . $group_id . '/'.$album_id.'/';
                $aws_original_path = $this->group_media_path . $group_id . '/'.$album_id.'/';
            }else{
                $media_original_path = __DIR__ . "/../../../../web" . $this->group_media_path . $group_id . '/';
                $aws_original_path = $this->group_media_path . $group_id . '/';
            }
            $group_media_name = $media_info->getMediaName();
            $this->createClubProfileImageThumbnail($group_media_name, $media_original_path, $thumb_dir, $group_id);
            $this->cropClubProfileImage($group_media_name, $media_original_path, $thumb_dir, $group_id); 

           //set cover image
           $this->createClubCoverImageThumbnail($group_media_name, $media_original_path, $thumb_dir, $group_id);
           $this->cropClubCoverProfileImage($group_media_name, $media_original_path, $thumb_dir, $group_id);
        }
        //set it profile image for club
        $media_info->setProfileImage(1);
        $dm->persist($media_info);
        $dm->flush();
        $original_img_path = $aws_path."".$aws_original_path . ''.$group_media_name;
        $cover_img_path = $aws_path."/".$this->group_media_path_thumb. "".$group_id."/coverphoto/".$group_media_name;
        $thumb_img_path = $aws_path."/".$this->group_media_path_thumb. "".$group_id."/".$group_media_name;
        $data = array(
          'original'=>$original_img_path,
          'cover'=>$cover_img_path,
          'thumb' =>$thumb_img_path
        );
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }

	/**
     * create thumbnail for  a user profile image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function createClubProfileImageThumbnail($filename, $media_original_path, $thumb_dir, $group_id) {
        //thumb progile image path
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/groups/thumb_crop/".$group_id."/";
        $path_to_image_directory = $media_original_path;
      
        $original_img = $path_to_image_directory . $filename;
        //get crop image width and height
        $thumb_width = $this->crop_image_width;
        $thumb_height = $this->crop_image_height;

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
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
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
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }
    
     /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function cropClubProfileImage($filename, $media_original_path, $thumb_dir, $group_id ) {
        $x = 0;
        $y = 0;
        $width_crop = 200;
        $height_crop = 200;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/groups/thumb/".$group_id."/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;
        
        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../web/uploads/groups/thumb_crop/".$group_id."/";
        
        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path
      
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
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path,9);
        }
        
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        //upload on amazon
        $s3imagepath = "uploads/groups/thumb/".$group_id;
        $image_local_path = $filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
        
    }

    /**
     * create thumbnail for  a user profile image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createProfileImageThumbnail($filename, $media_original_path, $thumb_dir, $user_id) {
        //thumb progile image path
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/";
        $path_to_image_directory = $media_original_path;
      
        $original_img = $path_to_image_directory . $filename;
        //get crop image width and height
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_height;

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
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }
        
        $nx = $new_width;
        $ny = $new_height;
        
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }
    
    
     /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function cropProfileImage($filename, $media_original_path, $thumb_dir, $user_id) {
        $x = 0;
        $y = 0;
        $width_crop = $this->crop_image_width;
        $height_crop = $this->crop_image_height;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;
        
        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/";
        
        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path
      
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
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        
        //upload on amazon
       $s3imagepath = "uploads/users/media/thumb/" . $user_id; 
       $image_local_path = $path_to_thumbs_center_image_path;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
       
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createUseralbumThumbnail($filename, $media_original_path, $user_id, $album_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/" . $album_id . '/';
        //original image path
        $path_to_image_directory = $media_original_path;
      
        $original_img = $path_to_image_directory . $filename;
        //get resize image width and height
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_height;

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
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }
        
        $nx = $new_width;
        $ny = $new_height;
        
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }
    
    
    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function cropAlbumProfileImage($filename, $media_original_path, $user_id, $album_id) {
        $x = 0;
        $y = 0;
        //get crop image width and height
        $width_crop = $this->crop_image_width;
        $height_crop = $this->crop_image_height;
        
        $original_filename = $filename;

        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/';
        //thumbnail image name with path
        //$path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;
        
        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/" . $album_id . '/';
        
        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path
     
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
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        
        //upload on amazon
       $s3imagepath = "uploads/users/media/thumb/" . $user_id . "/" . $album_id; 
       $image_local_path = $path_to_thumbs_center_directory.$original_filename;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
    }

    
    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function resizeOriginal($filename, $media_original_path, $org_resize_dir, $user_id, $album_id=null) {
        //$path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/" . $album_id . '/';
        $path_to_thumbs_directory = $org_resize_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        
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
        
        //check if image size is less than defined limit size
        if($ox > $thumb_width || $oy > $thumb_height){
        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

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
        }else{
        //set original image size
        $nx = $ox;
        $ny = $oy;
        }
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename,9);
        }
  
        $s3imagepath = "uploads/users/media/original/" . $user_id;
        
        //check if album id is not null
        if($album_id != ""){
        $s3imagepath = "uploads/users/media/original/" . $user_id . "/" . $album_id;
        }
        
       $image_local_path = $path_to_thumbs_directory.$filename;
       
       //upload on amazon
       $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $user_id, $album_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/" . $album_id . '/';
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
        //get image thumb width
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_height;
        
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
        
        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
             //check if new width is less than minimum width
             if($new_width < $thumb_width){
                       $new_width = $thumb_width;
                       $new_height = $oy / ($ox / $thumb_width);
               }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
             //check if new height is less than minimum height
            if($new_height < $thumb_height){
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
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }
  
    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $user_id, $album_id) {
        //image crop size
        $crop_image_width = $this->crop_image_width;
        $crop_image_height = $this->crop_image_height;
        
        $imagename = $filename;
        $path_to_thumbs_crop_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/" . $album_id . '/';
        $filename = $path_to_thumbs_crop_directory.$filename;
        //get the thumb directory path
        $path_to_thumbs_center_directory =  __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/';
         //get the image center 
        $path_to_thumbs_center_image_path = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/" . $album_id . '/'.$imagename;
        
        if(preg_match('/[.](jpg)$/', $imagename)) {  
            $image = imagecreatefromjpeg($filename);  
        } else if (preg_match('/[.](jpeg)$/', $imagename)) {  
            $image = imagecreatefromjpeg($filename);  
        } else if (preg_match('/[.](gif)$/', $imagename)) {  
            $image = imagecreatefromgif($filename);  
        } else if (preg_match('/[.](png)$/', $imagename)) {  
            $image = imagecreatefrompng($filename);  
        }
 
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width = imagesx($image);
        $height = imagesy($image);       

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
        
      
        //   $path_to_thumbs_directory = $thumb_dir;
        //$path_to_image_directory = $media_original_path;
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);
        if (preg_match('/[.](jpg)$/', $imagename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $imagename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $imagename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $imagename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 9);
        }
        
        //upload on amazon
       $s3imagepath = "uploads/users/media/thumb/" . $user_id . "/" . $album_id;
       $image_local_path = $path_to_thumbs_center_image_path;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $imagename);
       
    }

     /**
     * set the user cover image.
     * @param request object
     * @return json
     */
    public function postSetusercoverimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
       
        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }

        if (!isset($_FILES['user_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['user_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $user_id = $object_info->user_id;
        
        if (!empty($original_media_name)) { //if file name is not exists means file is not present.
            
            /*
            //check for image size
            $getfilename = $_FILES['user_media']['tmp_name'];
            list($width, $height) = getimagesize($getfilename);  
            $check_resize_width = $this->resize_image_width;
            $check_resize_height = $this->resize_image_height;
            if($width<$check_resize_width or $height<$check_resize_height){
                 return array('code' => 140, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE with width greater than 200 and height greater than 200', 'data' => $data);
            }
            //end to check
            */
            $user_media_name = time() . strtolower(str_replace(' ', '', $_FILES['user_media']['name']));
            
            //clean image name
            $clean_name = $this->get('clean_name_object.service');
            $user_media_name = $clean_name->cleanString($user_media_name);
            //end image name
            
            $user_media_type = $_FILES['user_media']['type'];
            $user_media_type = explode('/', $user_media_type);
            $user_media_type = $user_media_type[0];
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $user_media = new UserMedia();
            //checking if album exits for this login user in album database then
            $user_media->setUserid($user_id);
            $user_media->setName($user_media_name);
            $user_media->setContenttype($user_media_type);
            $user_media->setAlbumid('');
            $user_media->profileImageUpload($user_id, $user_media_name);
            $dm->persist($user_media);
            $dm->flush();

            $media_id = $user_media->getId();
            if ($user_media_type == 'image') {
                $mediaOriginalPath = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/";
                $thumbDir = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";
                $resizeOriginalDir = __DIR__ . "/../../../../web/uploads/users/media/original/" . $user_id . "/";
                
                //rotate the image if orientaion is not actual.
                if (preg_match('/[.](jpg)$/', $user_media_name) || preg_match('/[.](jpeg)$/', $user_media_name)) {
                    $image_rotate_service = $this->get('image_rotate_object.service');
                    $image_rotate = $image_rotate_service->ImageRotateService($mediaOriginalPath . $user_media_name);
                }
                //end of image rotate
                
                $this->resizeOriginal($user_media_name, $mediaOriginalPath, $resizeOriginalDir, $user_id); 
                $this->createCoverImageThumbnail($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
                $this->cropCoverImage($user_media_name, $mediaOriginalPath, $thumbDir, $user_id);
            }

            if ($media_id) {
                //get user object
                $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));
                if (!$user) {
                    return array('code' => 100, 'message' => 'USER_DOES_NOT_EXIT', 'data' => $data);
                }
                $user->setCoverImg($media_id);
                $this->container->get('fos_user.user_manager')->updateUser($user);
                $user_service = $this->get('user_object.service');
                $user_info = $user_service->UserObjectService($user_id);
                $data = array('user_info'=>$user_info);
                $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($resp_data);
                exit();
            } else {
                $resp_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
                echo json_encode($resp_data);
                exit();
            }
        } else {
            $resp_data = array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
    }
    
     /**
     * create thumbnail for  a user cover image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createCoverImageThumbnail($filename, $media_original_path, $thumb_dir, $user_id) {
        //thumb progile image path
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/";
        $path_to_image_directory = $media_original_path;
      
        $original_img = $path_to_image_directory . $filename;
        //get crop image width and height
        $thumb_width = $this->cover_image_width;
        $thumb_height = $this->cover_image_height;

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
            if($new_width < $thumb_width){
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
            }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
            if($new_height < $thumb_height){
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
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }
    
    
     /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function cropCoverImage($filename, $media_original_path, $thumb_dir, $user_id) {
        $x = 0;
        $y = 0;
        $width_crop = $this->cover_image_width;
        $height_crop = $this->cover_image_height;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb/" . $user_id . "/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;
        
        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../web/uploads/users/media/thumb_crop/" . $user_id . "/";
        
        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path
      
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
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y       

        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        
         //upload on amazon
       $s3imagepath = "uploads/users/media/thumb/" . $user_id; 
       $image_local_path = $path_to_thumbs_center_directory.$original_filename;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }

   /**
     * Create club cover image
     * @param type $filename
     * @param type $media_original_path
     * @param type $thumb_dir
     * @param type $group_id
     */
    public function createClubCoverImageThumbnail($filename, $media_original_path, $thumb_dir, $group_id)
    {
         $path_to_thumbs_directory = __DIR__."/../../../../web/uploads/groups/thumb_cover_crop/".$group_id."/";
      
     //   $path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	//get crop image width and height
        $thumb_width = $this->club_cover_image_width;
        $thumb_height = $this->club_cover_image_height;

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
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }
        
        $nx = $new_width;
        $ny = $new_height;
        
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
        
    }
    
    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function cropClubCoverProfileImage($filename, $media_original_path, $thumb_dir, $group_id) {
        $x = 0;
        $y = 0;
        $width_crop = $this->club_cover_image_width;
        $height_crop = $this->club_cover_image_height;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__."/../../../../web/uploads/groups/thumb/".$group_id."/coverphoto/";
        
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;
        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../web/uploads/groups/thumb_cover_crop/" . $group_id . "/";
       
        
        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path
      
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
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

         //left/top for crop the image from x,y       

        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        //upload on amazon
        $s3imagepath = "uploads/groups/thumb/".$group_id."/coverphoto";
        $image_local_path = $filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename); 
    }
      /**
     * set the user cover image.
     * @param request object
     * @return json
     */
    public function postAmazanuploadsAction(Request $request) {
        
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
       
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadFileService('sunil/test', 'test.jpg', 'test');
        $data = array(
            'url'=>$image_url
        );
        return array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
    }
    
    
    /**
     * Remove tag from Album images 
     * @param object request
     * @return json string
     */
    public function postRemovealbumphototaggedusersAction(Request $request) 
    {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        //check required params
        $required_parameter =  array('user_id','untag_user_id','media_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        //validating params
        $requited_fields = array('user_id','untag_user_id','media_id' );
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $media = $dm->getRepository('MediaMediaBundle:UserMedia')
                   ->find($de_serialize['media_id']);
        if (!$media) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        $creater_id = $media->getUserId();
        $tagged_user_ids = $media->getTaggedFriends();
        
        if( $de_serialize['user_id'] != $creater_id ){
           if($de_serialize['user_id'] != $de_serialize['untag_user_id']){
                $res_data = array('code' => 302, 'message' => 'ACTION_NOT_PERMITED', 'data' => array());
                echo json_encode($res_data);
                exit;   
           }
        }

        if(count($tagged_user_ids)){
            $users = $tagged_user_ids;
        } else {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $index = '';
        
        if( in_array($de_serialize['untag_user_id'], $users)){ 
            
            $index = array_search($de_serialize['untag_user_id'], $users);
            unset($users[$index]); 
            $new_tagged_user_ids = array_values($users);    
            $media->setTaggedFriends($new_tagged_user_ids);
            
            $dm->persist($media); //storing the post data.
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
     * Get Tagged photos's from diff user albums 
     * @param object request
     * @return json string
     */
    public function postGettaggedphotosAction(Request $request) 
    {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id     =  (int) $object_info->user_id;
        $limit_start =  (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
        $limit_size  =  (isset($object_info->limit_size)  ? (int) $object_info->limit_size : 20);
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        $medias = $this->get('doctrine_mongodb')->getRepository('MediaMediaBundle:UserMedia')
                ->getTaggedPhotos( $user_id ,$limit_start, $limit_size );
        $mediaData = array();
        foreach ($medias as $mediadata) {
            $media_id = $mediadata->getId();
            $album_id = $mediadata->getAlbumId();
            $media_name = $mediadata->getName();
            $media_type = $mediadata->getContenttype();
            $creater_id = $mediadata->getUserid();
            $media_path  = $this->getS3BaseUri() . $this->user_media_album_path . $creater_id . '/' . $album_id . '/' . $media_name;
            $thumb_path = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $creater_id . '/' . $album_id . '/' . $media_name;

            $mediaData[] = array('id' => $media_id,
                'media_name' => $media_name,
                'media_type' => $media_type,
                'media_path' => $media_path,
                'thumb_path' => $thumb_path,
                'creater_id' => $creater_id,
            );
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $mediaData);
        echo json_encode($res_data);
        exit();
                
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
        return true;
    }
    
    /**
     * 
     * @param type $obj
     * @return type
     */
    public function getRequestobj($obj)
    {
        //Code start for getting se request
        $freq_obj = $obj->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];

        if($device_request_type == 'mobile') 
        {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } 
        else 
        { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($obj);
            }
        }
        return $de_serialize;
            //Code end for getting the request
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

}
