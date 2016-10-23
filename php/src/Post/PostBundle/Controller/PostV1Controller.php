<?php

namespace Post\PostBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
// use Acme\DemoBundle\Entity\UserMedia;
use Post\PostBundle\Document\Post;
use Post\PostBundle\Document\PostMedia;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use UserManager\Sonata\UserBundle\Document\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;
//edit by pradeep
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Post\PostBundle\PostPostBundle;
use Post\PostBundle\Document\CommentMedia;
use Post\PostBundle\Document\Comments;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Dashboard\DashboardManagerBundle\Utils\MessageFactory as Msg;


class PostV1Controller extends Controller
{
    protected $miss_param = '';
    protected $youtube = '';
    protected $post_media_path = 'uploads/documents/groups/posts/original/';
    protected $post_media_path_thumb = 'uploads/documents/groups/posts/thumb/';
    protected $post_media_path_thumb_crop = 'uploads/documents/groups/posts/thumb_crop/';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $post_thumb_image_width = 654;
    protected $post_thumb_image_height = 360;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    protected $post_comment_limit = 4;
    protected $post_comment_offset = 0;
    protected $comment_media_path = '/uploads/documents/groups/comments/original/';
    protected $comment_media_path_thumb = 'uploads/documents/groups/comments/thumb/';
    protected $allowed_share = array('external_share', 'internal_share');
    protected $allowed_object_type = array('club','shop', 'offer', 'social_project', 'external', 'bce');
    CONST INTERNAL_SHARE = 'internal_share';
    CONST EXTERNAL_SHARE = 'external_share';
    /**
    * Get User Manager of FOSUSER bundle
    * @return Obj
    */
   protected function getUserManager()
   {
           return $this->container->get('fos_user.user_manager');
   }
   
   /**
        * 
        * @param type $request
        * @return type
        */
        public function getAppData(Request $request)
        {
	      $content = $request->getContent();
             $dataer = (object)$this->decodeDataAction($content);

             $app_data = $dataer->reqObj;
             $req_obj = $app_data; 
             return $req_obj;
        }
     
    /**
     * Checking for file extension
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        
        $file_error = 0;
        foreach ($_FILES['post_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['post_media']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.
               if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['post_media']['type'][$key] == 'image/jpeg'  || 
                         $_FILES['post_media']['type'][$key] == 'image/jpg'  ||
                         $_FILES['post_media']['type'][$key] == 'image/gif'    || 
                         $_FILES['post_media']['type'][$key] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }
    
    /**
    * Functionality decoding data
    * @param json $req_obj	
    * @return array
    */
    public function decodeDataAction($req_obj)
    {
         //get serializer instance
         $serializer = new Serializer(array(), array(
                         'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                         'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
         ));
         $jsonContent = $serializer->decode($req_obj, 'json');
         return $jsonContent;
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
    * Call api/post action
    * @param Request $request	
    * @return array
    */
    
    public function postUserpostsAction(Request $request)
    { 
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($freq_obj['device_request_type']) and $freq_obj['device_request_type'] == 'mobile') {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
       
       //Code end for getting the request
       $object_info = (object)$de_serialize; //convert an array into object.
       
       $required_parameter = array('group_id','user_id','post_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //check for share type
        if(isset($object_info->share_type)){
            if (!in_array(Utility::getLowerCaseString($object_info->share_type), $this->allowed_share)) {
                $resp_data = new Resp(Msg::getMessage(1129)->getCode(), Msg::getMessage(1129)->getMessage(), $data); //INVALID_SHARE_TYPE
                $this->__createLog('[Post\PostBundle\ControllerPostController->Userposts] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            $object_info->object_type = (isset($object_info->object_type)) ? $object_info->object_type : '';
            if (!in_array(Utility::getLowerCaseString($object_info->object_type), $this->allowed_object_type)) {
                $resp_data = new Resp(Msg::getMessage(1130)->getCode(), Msg::getMessage(1130)->getMessage(), $data); //INVALID_OBJECT_TYPE
                $this->__createLog('Exiting from [Post\PostBundle\ControllerPostController->Userposts] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
        }
        //check for link_type
        if (isset($object_info->link_type)){
            $link_type = $object_info->link_type;
        } else {
            $link_type = 0;
        }
        
        if ($this->getRequest()->getMethod() === 'POST')
        {
            if(isset($_FILES['post_media'])){
                $file_error = $this->checkFileTypeAction(); //checking the file type extension.
                if ($file_error) {
                    return array('code'=>100, 'message'=>'ONLY_IMAGES_AND_VIDEO_ARE_ALLOWED', 'data'=>$data);
                }
            }
                
               
                $post_type       =  $object_info->post_type;
                $postId          =  (isset($object_info->post_id)    ? $object_info->post_id:'');
                $postTitle       =  (isset($object_info->post_title) ? $object_info->post_title: '');
                $postDesc        =  (isset($object_info->post_desc)  ? $object_info->post_desc :'');
                $postyoutube     =  (isset($object_info->youtube)    ? $object_info->youtube : '');
                $postUserGroupId =  $object_info->group_id;
                $postUserId      =  $object_info->user_id;
                // $mediaId         =  (isset($object_info->media_id)?$object_info->media_id:'');
                if (isset($object_info->tagged_friends)) {
                    if (trim($object_info->tagged_friends)) {
                        $object_info->tagged_friends = explode(',', $object_info->tagged_friends);
                    } else {
                        $object_info->tagged_friends = array();
                    }
                } else {
                    $object_info->tagged_friends = array();
                }
                
                $time  = new \DateTime("now");
                $dm = $this->get('doctrine.odm.mongodb.document_manager');

                //Code for ACL checking
                $userManager = $this->getUserManager();
                $sender_user = $userManager->findUserBy(array('id' => $postUserId));

                if($sender_user=='')
                {            
                    $data[] = "USER_ID_IS_INVALID";
                }
                if(!empty($data))
                {
                    return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
                }
              
                //for group ACL    
                $do_action = 0;
              
                $group_mask = $this->userGroupRole($postUserGroupId,$postUserId);
                //check for Access Permission
                //only owner and admin can edit the group
                $allow_group = array('15','7');
                if(in_array($group_mask,$allow_group)){
                    $do_action = 1;
                }

                if($do_action == 0){
                //for group friend ACL
                   $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
                   $friend_res = $friend_dm
                           ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                           ->isActiveMember((int)$postUserId,$postUserGroupId); 

                   if($friend_res){            
                       $do_action = 1;
                   }
                }

                if($do_action==1)
                {
                    if($post_type == 0){
                        
                        if ($postId == ''){
                            $UserPost = new Post();
                            $UserPost->setPostTitle($postTitle);
                            $UserPost->setPostDesc($postDesc);
                            $UserPost->setLinkType($link_type);
                            $UserPost->setPostAuthor($postUserId);
                            // $time  = new \DateTime("now");
                            $UserPost->setPostCreated($time);
                            $UserPost->setPostUpdated($time);
                            $UserPost->setPostStatus(0);
                            $UserPost->setTaggedFriends($object_info->tagged_friends);
                            $UserPost->setPostGid($postUserGroupId);
                            // $UserPost->setPostGroupOwnerId(1);
                            $dm->persist($UserPost);
                            $dm->flush(); 
                            $postId= $UserPost->getId(); //getting the last inserted id of posts.  
                            $this->updateAclAction($sender_user, $UserPost);
                        } else {
                            $postId = $object_info->post_id;
                            $post_res = $dm->getRepository('PostPostBundle:Post')
                                            ->find($postId);
                            if (!$post_res) {
                                return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                            }
                       }
                    /*********** post media data*************************/
                        $current_post_media       = array();                               
                        $post_thumb_image_width = $this->post_thumb_image_width;
                        $post_thumb_image_height = $this ->post_thumb_image_height;
                        
                        $club_post_media_id       = 0;
                        //service object for the image upload
                        $image_upload = $this->get('amazan_upload_object.service');
                        if(isset($_FILES['post_media'])){
                            foreach($_FILES['post_media']['tmp_name'] as $key => $tmp_name )
                            {
                                //find media information 
                                $image_info = getimagesize($_FILES['post_media']['tmp_name'][$key]);
                                $orignal_mediaWidth = $image_info[0];
                                $original_mediaHeight = $image_info[1]; 
                                
                                //call service to get image type. Basis of this we save data 3,2,1 in db
                                $image_type_service = $this->get('user_object.service');
                                $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$post_thumb_image_width,$post_thumb_image_height);
                                
                                $original_media_name = $_FILES['post_media']['name'][$key];
                                if (!empty($original_media_name)) { //if file name is not exists means file is not present.
                                    $postMediaName = time() . strtolower(str_replace(' ', '', $_FILES['post_media']['name'][$key]));
                                    
                                    //clean image name
                                    $clean_name = $this->get('clean_name_object.service');
                                    $postMediaName = $clean_name->cleanString($postMediaName);
                                    //end image name
                    
                                    $postMediatype =  $_FILES['post_media']['type'][$key];
                                    $Mediatype     = explode('/',$postMediatype);
                                    $mediatypeName = $Mediatype[0];
                                    
                                    $PostMedia = new PostMedia();
                                    $PostMedia->setPostId($postId);
                                    $PostMedia->setMediaName($postMediaName);
                                    $PostMedia->setMediaType($postMediatype);
                                    $PostMedia->setMediaCreated($time);
                                    $PostMedia->setMediaUpdated($time);
                                    $PostMedia->setMediaStatus(0);
                                    $PostMedia->setImageType($image_type);
                                    if (!$key) { //consider first image the featured image.
                                      $PostMedia->setIsFeatured(1);
                                    } else {
                                      $PostMedia->setIsFeatured(0);
                                    }
    
                                    $dm->persist($PostMedia);
                                    $dm->flush();
                                    //get the club media id
                                    $club_post_media_id = $PostMedia->getId();
                                    //update ACL for a user 
                                    $this->updateAclAction($sender_user, $PostMedia);                                    
                                    $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_post_media_path'). $postId . '/';
                                    $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('club_post_media_path') . $postId . '/';
                                    $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_post_media_path_thumb') . $postId . '/';
                                    $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_post_media_path_thumb_crop') . $postId . "/";
                                    $s3_post_media_path = $this->container->getParameter('s3_club_post_media_path'). $postId;
                                    $s3_post_media_thumb_path = $this->container->getParameter('s3_club_post_media_thumb_path'). $postId;
                                    $image_upload->imageUploadService($_FILES['post_media'],$key,$postId,'club_post',$postMediaName,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                            }
                        }
                        }
                        
                    if(!empty($postyoutube)){
                      $dm = $this->get('doctrine.odm.mongodb.document_manager');
                      $PostMedia = new PostMedia();
                      $PostMedia->setPostId($postId);
                      // make media name blank for youtube 
                      $PostMedia->setMediaName('');
                      $PostMedia->setMediaType('youtube');
                      $PostMedia->setYoutube($postyoutube);
                      $PostMedia->setMediaCreated($time);
                      $PostMedia->setMediaUpdated($time);
                      $PostMedia->setIsFeatured(0);
                      $PostMedia->setMediaStatus(0);
                      $PostMedia->setImageType($image_type);
                      $dm->persist($PostMedia);
                      $dm->flush();

                      //update ACL for a user 
                      $this->updateAclAction($sender_user, $PostMedia); 
                    }
                     $post_media_data = $dm->getRepository('PostPostBundle:PostMedia')
                                           ->find($club_post_media_id);
                     $post_media_name  = $post_media_link = $post_media_thumb = $post_image_type ='';//initialize blank variables.
                              
                    if ($post_media_data) {
                        $post_media_name  = $post_media_data->getMediaName();
                        $post_image_type = $post_media_data->getImageType();
                        $post_media_link  = $this->getS3BaseUri() . "/".$this->post_media_path . $postId . '/'.$post_media_name;
                        $post_media_thumb = $this->getS3BaseUri() . "/".$this->post_media_path_thumb . $postId . '/'.$post_media_name;
                    }
                    //sending the current media and post data.
                    $data = array(
                        'id' => $postId,
                        'media_id' => $club_post_media_id,
                        'media_link' => $post_media_link,
                        'media_thumb_link' => $post_media_thumb,
                        'image_type' => $post_image_type
                    );
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                    echo json_encode($res_data);
                    exit();
                    } else {
                        $tagged_friends=array();
                        //$postId = $object_info->post_id;
                        $postService = $this->get('post_detail.service');
                        if($postId)
                        {
                            $post = $dm->getRepository('PostPostBundle:Post')
                             ->find($postId);
                            if (!$post) {
                                return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                            }
                            $post_data = $this->getLastPostDetail($object_info); //finding the post object.
                            $res_data =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                            
                            $postService->sendPostNotificationEmail($post_data, 'club', true, true);
                        } else {
                            $post_data = $this->getPostWithoutImageObject($object_info, $sender_user); //finding the post object.
                            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                            
                            $postService->sendPostNotificationEmail($post_data, 'club', true, true);
                        }
                        
                        //update in notification table / send email
                        if (count($object_info->tagged_friends)) {
                            if ($object_info->post_id) {
                                $fid = array_diff($object_info->tagged_friends, $tagged_friends);
                            } else {
                                $fid = $object_info->tagged_friends;
                            }
                            $club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                                    ->findOneBy(array('id' => $postUserGroupId));
                            if (count($fid)) {

                                $msgtype = 'TAGGED_IN_CLUB_POST';
                                $msg = 'tagging';
                                $postId = $post_data['post_id'];
                                $club_id = $club->getId();
                                $clubStatus = $club->getGroupStatus();
                                $clubName = $club->getTitle();
                                $email_template_service = $this->container->get('email_template.service'); //email template service.
                                $href = $postService->getStoreClubUrl(array('clubId'=>$club_id, 'postId'=>$postId), 'club');
                                $sender = $postService->getUserData($postUserId);
                                $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));

                                $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $postId, true, true, array($sender_name, $clubName), 'CITIZEN', array('club_id'=>$club_id, 'club_status'=>$clubStatus));
                                $receivers = $postService->getUserData($fid, true);
                                $receiversByLang = $postService->getUsersByLanguage($receivers);

                                foreach ($receiversByLang as $lang=>$receivers){
                                    $locale = $lang===0 ? $this->container->getParameter('locale') : $lang;
                                    $language_const_array = $this->container->getParameter($locale);
                                    $mail_text = sprintf($language_const_array['TAGGED_IN_CLUB_POST_TEXT'], ucwords($sender_name), $clubName);
                                    $bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                                    $subject = sprintf($language_const_array['TAGGED_IN_CLUB_POST_SUBJECT'], ucwords($sender_name), $clubName);
                                    $mail_body = sprintf($language_const_array['TAGGED_IN_CLUB_POST_BODY'], ucwords($sender_name), $clubName);
                                    $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                                }


                            }
                        }
                        
                        echo json_encode($res_data);
                        exit();
                         
                    }
                } else {
                        return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data);
                   }	  
        } 
		
    }
    
    
    /**
     * Finding the post object. update the post and send post object.
     * @param type $object_info
     * @return array $postdata
     */
    public function getPostWithoutImageObject($object_info, $sender_user) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
         //code for responding the current post data..
        $object_data = $object_info;
        if(isset($object_data->share_type)){
        $object_data->object_type = (isset($object_data->object_type)) ? Utility::getLowerCaseString($object_data->object_type) : '';
        $object_data->share_type = (isset($object_data->share_type)) ? Utility::getLowerCaseString($object_data->share_type) : '';
        $object_data->object_id = (isset($object_data->object_id)) ? $object_data->object_id : 0;
        $content_share_data = array();
        $object_data->content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        $content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        //prepare the data for the contant share if te data is not present intilizing it to the default value
        $content_share_data['url'] = isset($content_share['url']) ? $content_share['url'] : '';
        $content_share_data['pageUrl'] = isset($content_share['pageUrl']) ? $content_share['pageUrl'] : '';
        $content_share_data['canonicalUrl'] = isset($content_share['canonicalUrl']) ? $content_share['canonicalUrl'] : '';
        if(isset($content_share['images']) && is_array($content_share['images'])) {
            $content_share_data['images'] = $content_share['images'];
        } else {
            $content_share_data['images'] = array();
        }
        $content_share_data['description'] = isset($content_share['description']) ? $content_share['description'] : '';
        $content_share_data['title'] = isset($content_share['title']) ? $content_share['title'] : '';
        $content_share_data['video'] = isset($content_share['video']) ? $content_share['video'] : '';
        $content_share_data['videoIframe'] = isset($content_share['videoIframe']) ? $content_share['videoIframe'] : '';
        $object_data->content_share = $content_share_data;
        }else{
            $object_data->object_type = '';
            $object_data->share_type = '';
            $object_data->object_id = '';
            $object_data->content_share = null;
        }
        
       
       // $post_id = $object_info->post_id;
        
        //get store post entity

        $postTitle       =  $object_info->post_title;
        $postDesc        =  $object_info->post_desc;
       // $postyoutube     =  $object_info->youtube;
        $postUserGroupId =  $object_info->group_id;
        $postUserId      =  $object_info->user_id;
        
        //check for link_type
        if (isset($object_info->link_type)){
            $link_type = $object_info->link_type;
        } else {
            $link_type = 0;
        }
        //get group post entity
        $UserPost = new Post();
        $UserPost->setPostTitle($postTitle);
        $UserPost->setPostDesc($postDesc);
        $UserPost->setLinkType($link_type);
        $UserPost->setPostAuthor($postUserId);
        $time  = new \DateTime("now");
        $UserPost->setPostCreated($time);
        $UserPost->setPostUpdated($time);
        $UserPost->setPostStatus(1);
        $UserPost->setPostGid($postUserGroupId);
        $UserPost->setTaggedFriends($object_info->tagged_friends);
        $UserPost->setShareType($object_data->share_type);
        $UserPost->setContentShare($object_data->content_share);
        $UserPost->setShareObjectId($object_data->object_id);
        $UserPost->setShareObjectType($object_data->object_type);
        $dm->persist($UserPost);
        $dm->flush();
        //update the ACL....
        $this->updateAclAction($sender_user, $UserPost); 
        $post_id = $UserPost->getId();
        $posts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                             ->find($post_id);
        $postDetail = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                      ->findBy(array('post_id' => $post_id));
        
        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $posts->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object
        
        $mediaData = array();
        //get author object
        $post_auth    =  $posts->getPostAuthor();
        $user_info    =  $user_service->UserObjectService($post_auth);
        $post_created =   $posts->getPostCreated();
        $object_type = $posts->getShareObjectType();
        $object_id = $posts->getShareObjectId();
        $object_info = $this->prepareObjectInfo($object_type,$object_id);
        $postDetail = array('post_id'=>$post_id,
                             'post_title'=>$posts->getPostTitle(),
                             'post_description'=>$posts->getPostDesc(),
                             'post_author'=>$posts->getPostAuthor(),
                             'link_type' =>(int)$posts->getLinkType(),
                             'post_created'=>$post_created,
                             'post_status'=>$posts->getPostStatus(),
                             'post_gid' => $posts->getPostGid(),
                             'media_info'=>$mediaData,
                             'user_profile'=> $user_info,
                            'avg_rate'=>0,
                            'no_of_votes' =>0,
                            'current_user_rate'=>0,
                            'is_rated' =>false,
                            'tagged_friends_info' => $tagged_friends_info,
                            'comment_count' =>0,
                            'share_type'=> $posts->getShareType(),
                            'content_share'=> $posts->getContentShare(),
                            'object_type'=> $posts->getShareObjectType(),
                            'object_info'=> $object_info
                         );
                      
         return $postDetail;
    }
    
    /**
     * creating the ACL for the entity for a user
     * @param object $sender_user
     * @param object $post_entity
     * @return none
     */
    public function updateAclAction($sender_user, $post_entity) {
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($post_entity);
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
    * Function to retrieve current applications base URI 
    */
     public function getBaseUri()
     {
         // get the router context to retrieve URI information
         $context = $this->get('router')->getContext();
         // return scheme, host and base URL
         return $context->getScheme().'://'.$context->getHost().$context->getBaseUrl().'/';
     }
	/**
	 * deleting the post on user_id and post_id basis.
	 * @param request object
	 * @param json
	 */
	public function postDeletepostsAction(Request $request)
	{
            //Code start for getting the request
            $data = array();
            $freq_obj = $request->get('reqObj');
            $fde_serialize = $this->decodeDataAction($freq_obj);

            if(isset($fde_serialize)){
               $de_serialize = $fde_serialize;
            } else {
               $de_serialize = $this->getAppData($request);
            }
            //Code end for getting the request
            $object_info       = (object)$de_serialize; //convert an array into object.
            
            $required_parameter = array('post_id', 'user_id');
            //checking for parameter missing.
            $chk_error = $this->checkParamsAction($required_parameter, $object_info);
            if ($chk_error) {
                    return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            }

            $postId            =  $object_info->post_id;
            $postUserId        =  $object_info->user_id;
         //   $postAuthorId      =  $object_info->author_id;
         //   $postgroupOwnerId  =  $object_info->group_owner_id;
         //   
            //Code for ACL checking
            $userManager = $this->getUserManager();
            $sender_user = $userManager->findUserBy(array('id' => $postUserId));

            if($sender_user=='')
            {            
                $data[] = "USER_ID_IS_INVALID";
            }
            if(!empty($data))
            {
                return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
            }
            $group_id = 0;
            $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $post_res = $post_dm
                    ->getRepository('PostPostBundle:Post')
                    ->findOneBy(array("id" =>$postId));            

            if($post_res)
            {
                $group_id = $post_res->getPostGid();
            }else{
                return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
            }
     
            //for group ACL    
            $do_action = 0;
            $group_mask = $this->userGroupRole($group_id,$postUserId);
            //check for Access Permission
            //only owner and admin can edit the group
            $allow_group = array('15','7');
            if(in_array($group_mask,$allow_group)){
                $do_action = 1;
            }
            $thread_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $thread_res = $thread_dm->getRepository('PostPostBundle:Post')->findOneBy(array("id" =>$postId)); 
            if($do_action == 0){
            //for group friend ACL
               $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
               $friend_res = $friend_dm
                       ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                       ->isActiveMember((int)$postUserId,$group_id); 

               if($friend_res){            
                    $post_mask = $this->userGroupFriendRole($postId,$postUserId);
                    $allow_friend = array('15','7');
                    if(in_array($post_mask,$allow_friend)){
                        $do_action = 1;
                    }
               }
            }
            
            if($do_action == 1)
            {
                    
                /*** remove posts*****/
                 $thread_dm->remove($thread_res);
                 $thread_dm->flush();
                 
                /*** remove corresponding media*****/
                $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                                   ->removePostsMedia($postId);
                
                if ($mediaposts) {
                    
                  //  $thread_dm->remove($mediaposts);
                   // $thread_dm->flush();

                    $document_root = $request->server->get('DOCUMENT_ROOT');
                    $BasePath = $request->getBasePath();
                    $file_location = $document_root.$BasePath; // getting sample directory path
                    $post_media_location = $file_location.'/'.$this->post_media_path.$postId.'/';
                    $post_media_thum_location = $file_location.'/'.$this->post_media_path_thumb.$postId.'/';
                    
                    //as image will not exist, so commented the code
                    if(file_exists($post_media_location))
                    {
                      //array_map('unlink', glob($post_media_location.'/*'));
                      //rmdir($post_media_location); 
                   }
                   if(file_exists($post_media_thum_location))
                   {
                     //array_map('unlink', glob($post_media_thum_location.'/*'));
                     //rmdir($post_media_thum_location); 
                   }
                   
                   $res_data =  array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                   echo json_encode($res_data);
                   exit();
               }
                 $res_data = array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                 echo json_encode($res_data);
                 exit();
             } else {
                    return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data);
             }
       
    }
    
    
     /**
    * deleting the media of post on basis of media_id and post_id basis.
    * @param request object
    * @param json
    */
	public function postDeletegrouppostmediasAction(Request $request)
	{
            $data          = array();
            $freq_obj = $request->get('reqObj');
            $fde_serialize = $this->decodeDataAction($freq_obj);
            if(isset($fde_serialize)){
               $de_serialize = $fde_serialize;
            } else {
               $de_serialize = $this->getAppData($request);
            }
            //Code end for getting the request
            $object_info = (object)$de_serialize; //convert an array into object.
            
            $required_parameter = array('post_id', 'media_id');
            //checking for parameter missing.
            $chk_error = $this->checkParamsAction($required_parameter, $object_info);
            if ($chk_error) {
                    return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            }

            $postId        = $object_info->post_id;
	    $postMediaId   = $object_info->media_id;
          //  $userId        = $object_info->user_id; 
            $threadDm      = $this->container->get('doctrine.odm.mongodb.document_manager');
            $threadRes     = $threadDm ->getRepository('PostPostBundle:PostMedia')
                                       ->find($postMediaId);
                if (!$threadRes) {
                    return array('code'=>100, 'message'=>'MEDIA_DOES_NOT_EXISTS', 'data'=>$data);
                }

                if ($threadRes) {
                   /*** remove corresponding media*****/
                    $threadDm ->remove($threadRes);
                    $threadDm ->flush();
                  /***** remove corresponding media from folder also********/
                    $mediaName = $threadRes->getMediaName();
                    $document_root = $request->server->get('DOCUMENT_ROOT');
                    $BasePath = $request->getBasePath();
                    $file_location = $document_root.$BasePath; // getting sample directory path 
                    $mediaFileLocation = $file_location.'/'.$this->post_media_path.$postId.'/'; 
                    $mediaToBeDeleted = $mediaFileLocation.$mediaName; 
                    $mediaThumbLocation = $file_location.'/'.$this->post_media_path_thumb.$postId.'/';
                    $thumbToBeDeleted =  $mediaThumbLocation.$mediaName;
                    
                    //as image will not exist, so commented the code
                    if(file_exists($mediaToBeDeleted))
                    { 
                     //unlink($mediaToBeDeleted);
                    }
                    if (file_exists($thumbToBeDeleted)) //remove thumb image.
                    {
                    //unlink($thumbToBeDeleted);
                    }
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                    echo json_encode($res_data);
                    exit();
                   }
           
    }
	
	/**
     * Functionality return Post list
     * @param json $request
     * @return array
     */
    /* previous funciton backup
    public function postListpostsAction(Request $request)
    { 
        
        //Code start for getting the request
       $data = array();
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeDataAction($freq_obj);

       if(isset($fde_serialize)){
          $de_serialize = $fde_serialize;
       } else {
          $de_serialize = $this->getAppData($request);
       }
       //Code end for getting the request
       
        $object_info = (object)$de_serialize; //convert an array into object.
        
        $required_parameter = array('user_group_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id       = $object_info->user_id;
        $group_id      = $object_info->user_group_id;
        $limit_start   = (isset($object_info->limit_start)?(int)$object_info->limit_start: 0) ;
        $limit_size    = (isset($object_info->limit_size) ? (int)$object_info->limit_size : 0 );
        
        //ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        
        $group_dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $group_dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));
       
        if($group)
        {
            $group_status = $group->getGroupStatus();
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findOneBy(array("user_id" =>(int)$user_id,"group_id"=>$group_id)); 
            if(!$friend_res && $group_status==2)
            {
                $resp_data = array('code'=>'500','message'=>'PERMISSION_DENIED', 'data'=>array());
                return $resp_data;
            }
        }else{
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        $posts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                ->findBy(array('post_gid' => $object_info->user_group_id,'post_status'=>1),array('post_created'=>'DESC'),$limit_size,$limit_start);
        
        $postsCount = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                     ->findBy(array('post_gid' => $object_info->user_group_id,'post_status'=>1));

        $post_detail = array();
        $totalCount = count($postsCount);
        
        //get user object
        $user_service = $this->get('user_object.service');
        
        
       foreach($posts as $post)
       {
           $post_id = $post->getId();
           $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                         ->findBy(array('post_id' => $post_id));
           $media_data= array();
           foreach($mediaposts as $mediadata)
           {
              
               $mediaId    = $mediadata->getId();
               $mediaName  = $mediadata->getMediaName();
               $mediatype  = $mediadata->getMediaType();
               $media_created_at = $mediadata->getMediaCreated();
              // $mediadirec = $mediadata->getUploadDir();
               $isfeatured = $mediadata->getIsFeatured();
               $mediastatus  = $mediadata->getMediaStatus();
               $ImageType  = $mediadata->getImageType(); // bais of this show original image or thumbnail
               $youtube    = $mediadata->getYoutube();
               
               $post_id    = $post->getId();
               $mediaDir    = $this->getS3BaseUri() . "/".$this->post_media_path . $post_id . '/'.$mediaName;
               $thumbDir    = $this->getS3BaseUri() . "/".$this->post_media_path_thumb . $post_id . '/'.$mediaName;
               $media_data[] = array('id'=>$mediaId,
                                    'media_name'=>$mediaName,
                                    'media_type'=>$mediatype,
                                    'media_created_at' => $media_created_at,
                                    'is_featured'=>$isfeatured,
                                    'media_status'=>$mediastatus,
                                    'youtube'=>$youtube,
                                    'media_path'=>$mediaDir,
                                    'media_thumb_path'=>$thumbDir,
                                    'post_image_type'=>$ImageType
                                   );
             
           }
           //finding the comments start.
            $comments = $dm->getRepository('PostPostBundle:Comments')
                    ->findBy(array('post_id' => $post_id, 'status' => 1), array('comment_created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
            $comments = array_reverse($comments);
            $comment_data = array();
            $comment_user_info = array();
            $data_count = 0;
            if ($comments) {
                $comment_count_data = $dm->getRepository('PostPostBundle:Comments')
                ->listingTotalComments($post_id);
                if($comment_count_data){
                     $data_count = count($comment_count_data);
                }else{
                     $data_count = 0;
                }
                foreach ($comments as $comment) {
                    $comment_id = $comment->getId();
                    $comment_user_id = $comment->getcommentAuthor();                   
                    //code for user active profile check                        
                    $comment_user_info = $user_service->UserObjectService($comment_user_id);
                    $comment_media = $dm->getRepository('PostPostBundle:CommentMedia')
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
                                'create_date' => $comment_media_created_at);
                        }
                    }

                    $comment_data[] = array(
                        'id' => $comment_id,
                        'post_id' => $comment->getPostId(),
                        'comment_text' => $comment->getCommentText(),
                        'user_id' => $comment->getCommentAuthor(),
                        'comment_user_info' => $comment_user_info,
                        'status' => $comment->getStatus(),
                        'create_date' => $comment->getCommentCreatedAt(),
                        'comment_media_info' => $comment_media_result,
                            );
                }  
            }
           
          
           
           
           
           //get author object
           $post_auth = $post->getPostAuthor();
           $user_info    = $user_service->UserObjectService($post_auth);
           
           $post_detail[] = array('post_id'=>$post_id,
                                'post_title'=>$post->getPostTitle(),
                                'post_created'=>$post->getPostCreated(),
                                'post_description'=>$post->getPostDesc(),
                                'post_author'=>$post->getPostAuthor(),
                                'link_type' => (int)$post->getLinkType(),
                                'media_info'=>$media_data,
                                'user_profile'=> $user_info,
                                'comments' => $comment_data,
                                 'comment_count'=>$data_count,
                            );
       } 
       $res_data = array('code'=>'101','message'=>'SUCCESS','data'=>$post_detail,'total'=>$totalCount);
       echo json_encode($res_data);
       exit();
    }
	*/
    /**
     * Functionality return Post list
     * @param json $request
     * @return array
     */
    public function postListpostsAction(Request $request)
    { 
        
        //Code start for getting the request
       $data = array();
       $post = array();
       $comment_user_ids = array();
       $post_sender_user_ids = array();
       $post_ids = array();
       $comment_ids = array();
       
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeDataAction($freq_obj);

       if(isset($fde_serialize)){
          $de_serialize = $fde_serialize;
       } else {
          $de_serialize = $this->getAppData($request);
       }
       //Code end for getting the request
       
        $object_info = (object)$de_serialize; //convert an array into object.
        
        $required_parameter = array('user_group_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id       = $object_info->user_id;
        $group_id      = $object_info->user_group_id;
        $limit_start   = (isset($object_info->limit_start)?(int)$object_info->limit_start: 0) ;
        $limit_size    = (isset($object_info->limit_size) ? (int)$object_info->limit_size : 0 );
        $last_post_id = (isset($object_info->last_post_id) ? $object_info->last_post_id : '');
        
        //ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        
        $group_dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $group_dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));
       
        if($group)
        {
            $group_status = $group->getGroupStatus();
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->isActiveMember((int)$user_id,$group_id); 
            if(!$friend_res && $group_status==2)
            {
                $resp_data = array('code'=>'500','message'=>'PERMISSION_DENIED', 'data'=>array());
                return $resp_data;
            }
        }else{
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_id = $object_info->user_group_id;
        $posts_data = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                ->listPosts($group_id, $limit_size, $limit_start, $last_post_id);
               // ->findBy(array('post_gid' => $object_info->user_group_id,'post_status'=>1),array('post_created'=>'DESC'),$limit_size,$limit_start);
        
        $postsCount = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                     ->findBy(array('post_gid' => $object_info->user_group_id,'post_status'=>1));

        $post_detail = array();
        $post_data_count = 0;
        $totalCount = count($postsCount);
        $post_data_count = $totalCount;
        //get user object
        $user_service = $this->get('user_object.service');
        
        //getting the posts ids.
        $post_ids = array_map(function($posts) {
            return "{$posts->getId()}";
        }, $posts_data);
        
        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($posts) {
            return "{$posts->getPostAuthor()}";
        }, $posts_data);
        
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
        
        //prepare all the data..
        if ($posts_data) {
            foreach ($posts_data as $post_data) {
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
                            $post_media_link = $this->getS3BaseUri() .'/'. $this->post_media_path . $post_id . '/' . $post_media_name;
                            $post_media_thumb = $this->getS3BaseUri() .'/'. $this->post_media_path_thumb . $post_id . '/' . $post_media_name;
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
                                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                    $comment_media_thumb = $this->getS3BaseUri() .'/'. $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
                        
                        $current_rate = 0;
                        $is_rated = false;
                        foreach($comment->getRate() as $rate) {
                            if($rate->getUserId() == $user_id ) {
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
                                        'status' => $comment->getStatus(),
                                        'create_date' => $comment->getCommentCreatedAt(),
                                        'comment_media_info' => $comment_media_result,
                                        'avg_rate'=>round($comment->getAvgRating(), 1),
                                        'no_of_votes'=> (int) $comment->getVoteCount(),
                                        'current_user_rate'=>$current_rate,
                                        'is_rated' => $is_rated
                                        
                                    );
                        }
                        $i++;
                    }    
                }
                
                $current_rate = 0;
                $is_rated = false;
                foreach($post_data->getRate() as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                $comment_count = $i;
                //comment code finish.
                $sender_id   = $post_data->getPostAuthor();
                #$receiver_id = $post_data->getToId();
                $user_info         = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
                #$reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();

                $post [] = array(
                    'post_id' => $post_data->getId(),
                    'post_title' => $post_data->getPostTitle(),
                    'post_created' => $post_data->getPostCreated(),
                    'post_description' => $post_data->getPostDesc(),                    
                    'post_author' => $sender_id,
                    'link_type' => $post_data->getLinkType(),
                    'media_info' => $post_media_result,
                    'user_profile' => $user_info,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'avg_rate'=>round($post_data->getAvgRating(), 1),
                    'no_of_votes'=> (int) $post_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' => $is_rated
                );
            }            
            
        }
        
        //$data['post'] = $post;
        
        $final_data = array('code' => "101", 'message' => 'SUCCESS', 'data' => $post,'total'=>$post_data_count);
        echo json_encode($final_data);            
        exit();
        
    }
	
    /**
    * Call api/updatepost action
    * @param Request $request	
    * @return array
    */
    public function postUpdatepostsAction(Request $request)
    {
         //Code start for getting the request
        $data = array();
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($freq_obj['device_request_type']) and $freq_obj['device_request_type'] == 'mobile') {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
       //Code end for getting the request
       
        $object_info = (object)$de_serialize; //convert an array into object.
        
        $required_parameter = array('post_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
    
        
        $postId            =  $object_info->post_id;
        $postUserId        =  $object_info->user_id;
        $postTitle         =  (isset($object_info->post_title) ? $object_info->post_title : '') ;
        $postDesc          =  (isset($object_info->post_desc)  ? $object_info->post_desc  : '');
        $postyoutube       =  (isset($object_info->youtube) ? $object_info->youtube : '');
        if (isset($object_info->tagged_friends)) {
            if (trim($object_info->tagged_friends)) {
                $object_info->tagged_friends = explode(',', $object_info->tagged_friends);
            } else {
                $object_info->tagged_friends = array();
            }
        } else {
            $object_info->tagged_friends = array();
        }
        
        
        if(isset($_FILES['post_media'])){
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code'=>100, 'message'=>'YOU_MUST_CHOOSE_A_IMAGE', 'data'=>$data);
            }
        }
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $postUserId));

        if($sender_user=='')
        {            
            $data[] = "USER_ID_IS_INVALID";
        }
        if(!empty($data))
        { 
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
        }
        $group_id = 0;
        $post_acl_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $post_acl_res = $post_acl_dm
                ->getRepository('PostPostBundle:Post')
                ->findOneBy(array("id" =>$postId));            

        if($post_acl_res)
        {
            $group_id = $post_acl_res->getPostGid();
        }else{
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        //for group ACL    
        $do_action = 0;
        $group_mask = $this->userGroupRole($group_id,$postUserId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15','7');
        if(in_array($group_mask,$allow_group)){
            $do_action = 1;
        }
        $thread_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $thread_res = $thread_dm->getRepository('PostPostBundle:Post')->findOneBy(array("id" =>$postId)); 
        if($do_action == 0){
        //for group friend ACL
           $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
           $friend_res = $friend_dm
                   ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                   ->isActiveMember((int)$postUserId,$group_id); 

           if($friend_res){            
                $post_mask = $this->userGroupFriendRole($postId,$postUserId);
                $allow_friend = array('15','7');
                if(in_array($post_mask ,$allow_friend)){
                    $do_action = 1;
                }
           }
        }
        
        if($do_action == 1)
        {
            $time = new \DateTime("now");
            $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            //$UserPost = new Post();
            $UserPost = $post_dm->getRepository('PostPostBundle:Post')->findOneBy(array("id" =>$postId));
            
            if (is_array($UserPost->getTaggedFriends())) {
                $tagged_friends = $UserPost->getTaggedFriends();
            } else {
                $tagged_friends = array();
            }
            
            $UserPost->setPostTitle($postTitle);
            $UserPost->setPostDesc($postDesc);
            $UserPost->setPostAuthor($postUserId);
            $UserPost->setPostCreated($time);
            $UserPost->setPostUpdated($time);
            $UserPost->setPostStatus(0);
            $UserPost->setPostGroupOwnerId(1);
            $UserPost->setTaggedFriends($object_data->tagged_friends);
            $post_dm->persist($UserPost);
            $post_dm->flush();
            
            $post_res = $post_dm->getRepository('PostPostBundle:Post')->findOneBy(array("id" =>$postId));
            
                if($post_res)
                {
                  // media section updation
                  $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
		          ->findBy(array('post_id' => $postId));
                  
                        $i=0;
                        $post_thumb_image_width = $this->post_thumb_image_width;
                        $post_thumb_image_height = $this ->post_thumb_image_height;
                        if(isset($_FILES['post_media'])){
                            foreach($_FILES['post_media']['tmp_name'] as $key => $tmp_name )
                            {
                              $original_media_name = $_FILES['post_media']['name'][$key];
                              if (!empty($original_media_name)) { //if file name is not exists means file is not present.
                                    $postMediaName = time() . strtolower(str_replace(' ', '', $_FILES['post_media']['name'][$key]));
                                    $postMediatype =  $_FILES['post_media']['type'][$key];
                                    $Mediatype     = explode('/',$postMediatype);
                                    $mediatypeName = $Mediatype[0];
                                     //find media information 
                                    $image_info = getimagesize($_FILES['post_media']['name'][$key]);
                                    $orignal_mediaWidth = $image_info[0];
                                    $original_mediaHeight = $image_info[1]; 
                                    
                                    //call service to get image type. Basis of this we save data 3,2,1 in db
                                    $image_type_service = $this->get('user_object.service');
                                    $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$post_thumb_image_width,$post_thumb_image_height);
                                    
                                    $PostMedia = new PostMedia();
                                    $PostMedia->setPostId($postId);
                                    $PostMedia->setMediaName($postMediaName);
                                    $PostMedia->setMediaType($postMediatype);
                                    $PostMedia->setImageType($image_type);

                                    //there are more than one images make first image fetaured image
                                    // this would be treat like post featured image 
                                    if($i==0){
                                     $PostMedia->setIsFeatured(1);
                                    } else {
                                        $PostMedia->setIsFeatured(0);
                                    } 
                                    $PostMedia->upload($postId,$key,$postMediaName);
                                    $post_dm->persist($PostMedia);
                                    $post_dm->flush();	
                                    $i++;
                                if($mediatypeName =='image'){    
                                    $mediaOriginalPath = __DIR__ . "/../../../../web/" . $this->post_media_path . $postId . '/';
                                    $thumbDir          = __DIR__ . "/../../../../web/" . $this->post_media_path_thumb . $postId . '/';
                                   // $this->createThumbnail($postMediaName, $mediaOriginalPath, $thumbDir, $postId);                  
                                    //crop the image from center
                                    $this->createCenterThumbnail($postMediaName, $mediaOriginalPath, $thumbDir, $postId);
                               }
                            }
                        }
                        }
                        
                    if(!empty($postyoutube)){
                      $PostMedia = new PostMedia();
                      $PostMedia->setPostId($postId);
                      // make media name blank for youtube 
                      $PostMedia->setMediaName('');
                      $PostMedia->setMediaType('youtube');
                      $PostMedia->setYoutube($postyoutube);
                      $post_dm->persist($PostMedia);
                      $post_dm->flush();
                    }
                     $post_detail = $this->getEditClubPostObject($object_info);
                     
                     //update in notification table / send email
                    if (count($object_info->tagged_friends)) {
                        if ($object_info->post_id) {
                            $fid = array_diff($object_info->tagged_friends, $tagged_friends);
                        } else {
                            $fid = $object_info->tagged_friends;
                        }
                        $club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                                ->findOneBy(array('id' => $group_id));
                        if (count($fid)) {

                            $msgtype = 'TAGGED_IN_CLUB_POST';
                            $msg = 'tagging';
                            $club_id = $club->getId();
                            $clubStatus = $club->getGroupStatus();
                            $clubName = $club->getTitle();
                            $email_template_service = $this->container->get('email_template.service'); //email template service.
                            $href = $postService->getStoreClubUrl(array('clubId'=>$club_id, 'postId'=>$postId), 'club');
                            $sender = $postService->getUserData($postUserId);
                            $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));

                            $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $postId, true, true, array($sender_name, $clubName), 'CITIZEN', array('club_id'=>$club_id, 'club_status'=>$clubStatus));
                            $receivers = $postService->getUserData($fid, true);
                            $receiversByLang = $postService->getUsersByLanguage($receivers);

                            foreach ($receiversByLang as $lang=>$receivers){
                                $locale = $lang===0 ? $this->container->getParameter('locale') : $lang;
                                $language_const_array = $this->container->getParameter($locale);
                                $mail_text = sprintf($language_const_array['TAGGED_IN_CLUB_POST_TEXT'], ucwords($sender_name), $clubName);
                                $bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                                $subject = sprintf($language_const_array['TAGGED_IN_CLUB_POST_SUBJECT'], ucwords($sender_name), $clubName);
                                $mail_body = sprintf($language_const_array['TAGGED_IN_CLUB_POST_BODY'], ucwords($sender_name), $clubName);
                                $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TAGGED_NOTIFICATION');
                            }


                        }
                    }
                    
                     $res_data = array('code'=>'101','message'=>'SUCCESS','data'=>$post_detail);
                     echo json_encode($res_data);
                     exit();
                     
                } else {
                        $res_data = array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                        echo json_encode($res_data);
                        exit();
                }
        }else{
            return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data);
        }
       
    }
    
    /**
     * Get Post detail
     * @param int $object_info
     * @return array
     */
   public function getEditClubPostObject($object_info)
    {
        //Code start for getting the request
        $data = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $post_id = $object_info->post_id;
        
        // putting all the images in array which we have to publish
       /* $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
        $media_array = json_encode($object_info->media_id);
        * 
        */
        $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
        $media_array             =  json_decode($object_info->media_id);

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $posts = $dm->getRepository('PostPostBundle:Post')
                      ->find($post_id);
        
        $posts->setPostTitle($object_info->post_title);
        $posts->setPostDesc($object_info->post_desc);
        $posts->setPostAuthor($object_info->user_id);
        $time  = new \DateTime("now");
        $posts->setPostCreated($time);
        $posts->setPostStatus(1);
        $posts->setTaggedFriends($object_data->tagged_friends);
        $dm->persist($posts);
        $dm->flush();
        
        $postDetail = array();
        $postId = $posts->getId();
        
        //making the media publish.. when we make post publish
        if (count($media_array)>0) {
            $dm->getRepository('PostPostBundle:PostMedia')
                ->publishClubPostImage($post_id, $media_array);
        }
            
        $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                      ->findBy(array('post_id' => $postId,'media_status'=> 1));
        $mediaData= array();
        if($mediaposts){
          
            foreach($mediaposts as $mediadata)
            {
            $mediaId    = $mediadata->getId();
            $mediaName  = $mediadata->getMediaName();
            $mediatype  = $mediadata->getMediaType();
            $isfeatured = $mediadata->getIsFeatured();
            $youtube    = $mediadata->getYoutube();
            $mediaStatus = $mediadata->getMediaStatus();
            $image_type = $mediadata->getImageType();
            // $postId    = $post->getId();
           if($mediaName == ''){
             $mediaDir = '';  
             $thumbDir ='';
           }
            $mediaDir    = $this->getS3BaseUri() . $this->post_media_path . $postId . '/'.$mediaName;
            $thumbDir    = $this->getS3BaseUri() . $this->post_media_path_thumb . $postId . '/'.$mediaName;

            $mediaData[] = array('id'=>$mediaId,
                                   'media_name'=>$mediaName,
                                   'media_type'=>$mediatype,
                                   'media_path'=>$mediaDir,
                                   'media_thumb_path'=>$thumbDir,
                                   'is_featured'=>$isfeatured,
                                   'media_status' => $mediaStatus,
                                   'youtube'=>$youtube,
                                    'image_type' =>$image_type
                                  );

           }
        }
        
        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $posts->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object
        
        //get user object
        $user_service = $this->get('user_object.service'); 
        $post_auth    =  $posts->getPostAuthor();
        $user_info    =  $user_service->UserObjectService($post_auth);
        $post_created =   $posts->getPostCreated();
        //$sd = $post_created->format('Y-m-d H:i:s');
        $postDetail = array('post_id'=>$postId,
                             'post_title'=>$posts->getPostTitle(),
                             'post_description'=>$posts->getPostDesc(),
                             'post_author'=>$posts->getPostAuthor(),
                             'post_status'=>$posts->getPostStatus(),
                             'post_created'=>$post_created,
                             'media_info'=>$mediaData,
                             'user_profile'=> $user_info,
                             'tagged_friends_info' => $tagged_friends_info,
                             'comment_count'=>0
                         );
                      
         return $postDetail;
    }
  
	/**
     * searching media 
     * @param object $request for search)
     * @return array
     */
    public function postSearchpostAction(Request $request)
    { 

       //Code start for getting the request
       $data = array();
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeDataAction($freq_obj);

       if(isset($fde_serialize)){
          $de_serialize = $fde_serialize;
       } else {
          $de_serialize = $this->getAppData($request);
       }
       //Code end for getting the request
       
        $object_info = (object)$de_serialize; //convert an array into object.

        $limit    = $object_info->limit_size; 
    	$offset   = $object_info->limit_start; 
    	$text     = $object_info->post_title;
    	$user_id  = (int)$object_info->user_id;	
    	$group_id  = $object_info->group_id;
        
        //ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if($sender_user=='')
        {            
            $data[] = "USER_ID_IS_INVALID";
        }
        if(!empty($data))
        {
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
        }
        $group_dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $group_dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));
        
        if($group)
        {
            $group_status = $group->getGroupStatus();
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->isActiveMember((int)$user_id,$group_id); 
            if(!$friend_res && $group_status==2)
            {
                $resp_data = array('code'=>'500','message'=>'PERMISSION_DENIED', 'data'=>array());
                return $resp_data;
            }
        }else{
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        
        
    	$dm     =   $this->get('doctrine.odm.mongodb.document_manager');
    	$posts   =  $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
    	           ->searchByPostTitleOrOther($group_id,$text,$offset,$limit);
        /* @var $data_count type */
        $data_count = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
    	             ->searchPostTitleOrOtherCount($text);
        
        $postscount   =  $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
    	           ->searchByPostTitleOrOther($group_id,$text,0,10000);
        $countTotal = (int)count($postscount);
        $post_detail= array();
        foreach($posts as $post){
            $post_id = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                          ->findBy(array('post_id' => $post_id));
             $media_data= array();
             foreach($mediaposts as $mediadata)
            {
                $mediaId    = $mediadata->getId();
                $mediaName  = $mediadata->getMediaName();
                $mediatype  = $mediadata->getMediaType();
                $mediadirec = $mediadata->getUploadDir();
                $post_id    = $post->getId();
                $mediaPath  = $this->getS3BaseUri().$mediadirec.'/'.$post_id.'/'.$mediaName;
                $media_data[] = array('id'=>$mediaId,
                                       'media_name'=>$mediaName,
                                       'media_type'=>$mediatype,
                                       'media_path'=>$mediaPath,
                                      );
            }  

            $post_detail[] = array('post_id'=>$post_id,
                                 'post_title'=>$post->getPostTitle(),
                                 'post_description'=>$post->getPostDesc(),
                                 'post_author'=>$post->getPostAuthor(),
                                 'media_info'=>$media_data
                             );
        }
    	$res_data = array('code'=>'101','message'=>'SUCCESS','data'=>$post_detail,'total'=>$countTotal,'count'=>count($post_detail));
        echo json_encode($res_data);
        exit();
    }
    /**
     * Get User role for group
     * @param int $group_id
     * @param int $user_id
     * @return int
     */
    public function userGroupRole($group_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO Add group owner id in AND clause.

        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($group); //entity

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
     * Get User role for group
     * @param int $post_id
     * @param int $user_id
     * @return int
     */
    public function userGroupFriendRole($post_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('PostPostBundle:Post')
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
     * create thumbnail for  a image.original image is resized and create thumbnail
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
   /* public function createThumbnail($filename, $media_original_path, $thumb_dir, $post_id) {  
        $path_to_thumbs_directory = __DIR__."/../../../../web/uploads/documents/groups/posts/thumb/".$post_id."/";
     //   $path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	$final_width_of_image = 100;  
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
        imagecopyresized($nm, $im, 0,0,0,0,$nx,$ny,$ox,$oy);  
        if(!file_exists($path_to_thumbs_directory)) {  
          if(!mkdir($path_to_thumbs_directory, 0777, true)) {  
               die("There was a problem. Please try again!");  
          }  
           }  
        imagejpeg($nm, $path_to_thumbs_directory . $filename);  
    }
    */
    
    /**
     * Get Post detail
     * @param int $object_info
     * @return array
     */
    public function getLastPostDetail($object_info)
    {
        //code for responding the current post data..
        $object_data = $object_info;
        if(isset($object_data->share_type)){
        $object_data->object_type = (isset($object_data->object_type)) ? Utility::getLowerCaseString($object_data->object_type) : '';
        $object_data->share_type = (isset($object_data->share_type)) ? Utility::getLowerCaseString($object_data->share_type) : '';
        $object_data->object_id = (isset($object_data->object_id)) ? $object_data->object_id : 0;
        $content_share_data = array();
        $object_data->content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        $content_share = (isset($object_data->content_share)) ? $object_data->content_share : array();
        //prepare the data for the contant share if te data is not present intilizing it to the default value
        $content_share_data['url'] = isset($content_share['url']) ? $content_share['url'] : '';
        $content_share_data['pageUrl'] = isset($content_share['pageUrl']) ? $content_share['pageUrl'] : '';
        $content_share_data['canonicalUrl'] = isset($content_share['canonicalUrl']) ? $content_share['canonicalUrl'] : '';
        if(isset($content_share['images']) && is_array($content_share['images'])) {
            $content_share_data['images'] = $content_share['images'];
        } else {
            $content_share_data['images'] = array();
        }
        $content_share_data['description'] = isset($content_share['description']) ? $content_share['description'] : '';
        $content_share_data['title'] = isset($content_share['title']) ? $content_share['title'] : '';
        $content_share_data['video'] = isset($content_share['video']) ? $content_share['video'] : '';
        $content_share_data['videoIframe'] = isset($content_share['videoIframe']) ? $content_share['videoIframe'] : '';
        $object_data->content_share = $content_share_data;
        }else{
            $object_data->object_type = '';
            $object_data->share_type = '';
            $object_data->object_id = '';
            $object_data->content_share = null;
        }
        //Code start for getting the request
        $data = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $post_id = $object_info->post_id;
        
        // putting all the images in array which we have to publish
       /* $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
        $media_array = json_encode($object_info->media_id);
        * 
        */
        $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
        $media_array             =  $object_info->media_id;
        //check for link_type
        if (isset($object_info->link_type)){
            $link_type = $object_info->link_type;
        } else {
            $link_type = 0;
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $posts = $dm->getRepository('PostPostBundle:Post')
                      ->find($post_id);
        
        $posts->setPostTitle($object_info->post_title);
        $posts->setPostDesc($object_info->post_desc);
        $posts->setPostAuthor($object_info->user_id);
        $posts->setLinkType($link_type);
        $time  = new \DateTime("now");
        $posts->setPostCreated($time);
        $posts->setPostStatus(1);
        $posts->setTaggedFriends($object_info->tagged_friends);
        $dm->persist($posts);
        $dm->flush();
        
        $postDetail = array();
        $postId = $posts->getId();
        
        //making the media publish.. when we make post publish
        if (count($media_array)>0) {
            $dm->getRepository('PostPostBundle:PostMedia')
                ->publishClubPostImage($post_id, $media_array);
        }
            
        $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                      ->findBy(array('post_id' => $postId,'media_status'=> 1));
        $mediaData= array();
        if($mediaposts){
          
            foreach($mediaposts as $mediadata)
            {
            $mediaId    = $mediadata->getId();
            $mediaName  = $mediadata->getMediaName();
            $mediatype  = $mediadata->getMediaType();
            $isfeatured = $mediadata->getIsFeatured();
            $youtube    = $mediadata->getYoutube();
            $mediaStatus = $mediadata->getMediaStatus();
            $image_type = $mediadata->getImageType();
            // $postId    = $post->getId();
           if($mediaName == ''){
             $mediaDir = '';  
             $thumbDir ='';
           }
            $mediaDir    = $this->getS3BaseUri() . "/".$this->post_media_path . $postId . '/'.$mediaName;
            $thumbDir    = $this->getS3BaseUri() . "/".$this->post_media_path_thumb . $postId . '/'.$mediaName;

            $mediaData[] = array('id'=>$mediaId,
                                   'media_name'=>$mediaName,
                                   'media_type'=>$mediatype,
                                   'media_path'=>$mediaDir,
                                   'media_thumb_path'=>$thumbDir,
                                   'is_featured'=>$isfeatured,
                                   'media_status' => $mediaStatus,
                                   'youtube'=>$youtube,
                                    'image_type' =>$image_type
                                  );

           }
        }
        
        $user_friend_service = $this->get('user_friend.service');
        $tagged_user_ids = $posts->getTaggedFriends();
        $tagged_friends_info = $user_friend_service->getTaggedUserInfo(implode(',', $tagged_user_ids)); //sender user object
        
        //get user object
        $user_service = $this->get('user_object.service'); 
        $post_auth    =  $posts->getPostAuthor();
        $user_info    =  $user_service->UserObjectService($post_auth);
        $post_created =   $posts->getPostCreated();
        //$sd = $post_created->format('Y-m-d H:i:s');
         $object_type = $posts->getShareObjectType();
        $object_id = $posts->getShareObjectId();
        $object_info = $this->prepareObjectInfo($object_type,$object_id);
        $postDetail = array('post_id'=>$postId,
                            'post_title'=>$posts->getPostTitle(),
                            'post_description'=>$posts->getPostDesc(),
                            'post_author'=>$posts->getPostAuthor(),
                            'post_status'=>$posts->getPostStatus(),
                            'post_created'=>$post_created,
                            'post_gid' => $posts->getPostGid(),
                            'media_info'=>$mediaData,
                            'user_profile'=> $user_info,
                            'link_type'=>$link_type,
                            'avg_rate'=>0,
                            'no_of_votes' =>0,
                            'current_user_rate'=>0,
                            'is_rated' =>false,
                            'tagged_friends_info' => $tagged_friends_info,
                            'comment_count' =>0,
                            'share_type'=> $posts->getShareType(),
                            'content_share'=> $posts->getContentShare(),
                            'object_type'=> $posts->getShareObjectType(),
                            'object_info'=> $object_info
                         );
                      
         return $postDetail;
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
        $path_to_thumbs_center_directory  = __DIR__."/../../../../web/uploads/documents/groups/posts/thumb/".$post_id."/";
        //thumbnail image name with path
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;

        $filename = $media_original_path.$filename; //original image name with path
      
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
        $crop_image_width  = $this->post_thumb_image_width;
        $crop_image_height = $this->post_thumb_image_height;

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
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);//100 is quality
        
        //upload on amazon
       $s3imagepath = "uploads/documents/groups/posts/thumb/".$post_id;

       $image_local_path = $path_to_thumbs_center_image_path;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }
    
    /**
     * resize original image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $post_id) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width  = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
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
	
        //check if image size is less than defined limit size
        if($ox > $thumb_width || $oy > $thumb_height){
	//getting aspect ratio
	$original_aspect = $ox / $oy;
	$thumb_aspect = $thumb_width / $thumb_height;

	if ($original_aspect >= $thumb_aspect) {
		// If image is wider than thumbnail (in aspect ratio sense)
		$new_height = $thumb_height;
		$new_width = $ox / ($oy / $thumb_height);
		if($new_width > $thumb_width){
			$new_width = $thumb_width;
			$new_height = $oy / ($ox / $thumb_width);
		}
	} else {
		// If the thumbnail is wider than the image
		$new_width = $thumb_width;
		$new_height = $oy / ($ox / $thumb_width);
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
                die("There was a problem. Please try again!");
            }
        }
      //  imagejpeg($nm, $path_to_thumbs_directory . $filename);
        if (preg_match('/[.](jpg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename,9);
        }
        $s3imagepath = $this->post_media_path . $post_id;

        $image_local_path = $path_to_thumbs_directory.$filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
        
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
        $thumb_width  = $this->post_thumb_image_width;
        $thumb_height = $this->post_thumb_image_height;
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
     * Functionality return single Post
     * @param json $request
     * @return array
     */
    public function postGetclubpostdetailsAction(Request $request)
    { 
        
        //Code start for getting the request
       $data = array();
       $post = array();
       $comment_user_ids = array();
       $post_sender_user_ids = array();
       $post_ids = array();
       $comment_ids = array();
       
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeDataAction($freq_obj);

       if(isset($fde_serialize)){
          $de_serialize = $fde_serialize;
       } else {
          $de_serialize = $this->getAppData($request);
       }
       //Code end for getting the request
       
        $object_info = (object)$de_serialize; //convert an array into object.
        
        $required_parameter = array('user_group_id', 'user_id', 'post_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id       = $object_info->user_id;
        $group_id      = $object_info->user_group_id;
        $postId = $object_info->post_id;
        //ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        
        $group_dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $group_dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));
       
        if($group)
        {
            $group_status = $group->getGroupStatus();
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->isActiveMember((int)$user_id,$group_id); 
            $is_group_member = $friend_res ? 1 : 0;
            if(!$friend_res && $group_status==2)
            {
                //$resp_data = array('code'=>'500','message'=>'PERMISSION_DENIED', 'data'=>array());
               // return $resp_data;
            }
        }else{
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        $posts_data = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:Post')
                ->findBy(array('post_gid' => $object_info->user_group_id,'post_status'=>1, 'id'=>$postId));

        $post_detail = array();
        $post_data_count = count($posts_data);
        //get user object
        $user_service = $this->get('user_object.service');
        
        //getting the posts ids.
        $post_ids = array_map(function($posts) {
            return "{$posts->getId()}";
        }, $posts_data);
        
        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($posts) {
            return "{$posts->getPostAuthor()}";
        }, $posts_data);
        
        if (count($post_ids)) {
            $post_media = $dm->getRepository('PostPostBundle:PostMedia')
                    ->findPostsMedia($post_ids);
            
            //finding the posts comments.
            $comments = $dm->getRepository('PostPostBundle:Comments')
                    ->findPostsComments($post_ids);
           
//            $comments = array_reverse($comments);
            
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
        
        //prepare all the data..
        if ($posts_data) {
            foreach ($posts_data as $post_data) {
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
                            $post_media_link = $this->getS3BaseUri() .'/'. $this->post_media_path . $post_id . '/' . $post_media_name;
                            $post_media_thumb = $this->getS3BaseUri() .'/'. $this->post_media_path_thumb . $post_id . '/' . $post_media_name;
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
                                    $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                    $comment_media_thumb = $this->getS3BaseUri() .'/'. $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
                        
                        $current_rate = 0;
                        $is_rated = false;
                        foreach($comment->getRate() as $rate) {
                            if($rate->getUserId() == $user_id ) {
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
                                        'status' => $comment->getStatus(),
                                        'create_date' => $comment->getCommentCreatedAt(),
                                        'comment_media_info' => $comment_media_result,
                                        'avg_rate'=>round($comment->getAvgRating(), 1),
                                        'no_of_votes'=> (int) $comment->getVoteCount(),
                                        'current_user_rate'=>$current_rate,
                                        'is_rated' => $is_rated
                                        
                                    );
                        }
                        $i++;
                    }    
                }
                
                $current_rate = 0;
                $is_rated = false;
                foreach($post_data->getRate() as $rate) {
                    if($rate->getUserId() == $user_id ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                $comment_count = $i;
                //comment code finish.
                $sender_id   = $post_data->getPostAuthor();
                #$receiver_id = $post_data->getToId();
                $user_info         = isset($users_object_array[$sender_id]) ? $users_object_array[$sender_id] : array();
                #$reciver_user_info = isset($users_object_array[$receiver_id]) ? $users_object_array[$receiver_id] : array();
                $object_type = $post_data->getShareObjectType();
                $object_id = $post_data->getShareObjectId();
                $object_info = $this->prepareObjectInfo($object_type,$object_id);
                $content_share = $post_data->getContentShare();
                if(is_array($content_share)){
                    $content_share = (count($content_share) == 0) ? null : $content_share;
                }
                $post [] = array(
                    'post_id' => $post_data->getId(),
                    'post_title' => $post_data->getPostTitle(),
                    'post_created' => $post_data->getPostCreated(),
                    'post_description' => $post_data->getPostDesc(),                    
                    'post_author' => $sender_id,
                    'link_type' => $post_data->getLinkType(),
                    'media_info' => $post_media_result,
                    'user_profile' => $user_info,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'avg_rate'=>round($post_data->getAvgRating(), 1),
                    'no_of_votes'=> (int) $post_data->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' => $is_rated,
                    'is_member'=>$is_group_member,
                    'share_type'=>$post_data->getShareType(),
                    'content_share'=> $content_share,
                    'object_type'=> $object_type,
                    'object_info'=> $object_info,
                );
            }            
            
        }
        
        //$data['post'] = $post;
        
        $final_data = array('code' => "101", 'message' => 'SUCCESS', 'data' => $post,'total'=>$post_data_count);
        echo json_encode($final_data);            
        exit();
        
    }
    
   /** 
     *  function for preparing the object info for the socail sharing 
     * @param type $object_type
     * @param type $object_id
     * @return array
     */
    private function prepareObjectInfo($object_type, $object_id) {
        $object_type = Utility::getUpperCaseString($object_type);
        $user_service = $this->get('user_object.service');
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