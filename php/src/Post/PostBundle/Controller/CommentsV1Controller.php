<?php

namespace Post\PostBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\MessageBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use JMS\Serializer\SerializerBuilder as JMSR;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\Form\FormTypeInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use FOS\MessageBundle\EntityManager\ThreadManager;
use Doctrine\ORM\EntityManager;
use Post\PostBundle\Document\Comments;
use Post\PostBundle\Document\Post;
use Post\PostBundle\Document\PostMedia;
use Post\PostBundle\PostPostBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use UserManager\Sonata\UserBundle\Document\Group;
use Post\PostBundle\Document\CommentMedia;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\UserActiveProfile;


class CommentsV1Controller extends Controller
{
    protected $post_media_path = '/uploads/documents/groups/posts/original/';
    protected $post_media_path_thumb = '/uploads/documents/groups/posts/thumb/';
    protected $comment_media_path = '/uploads/documents/groups/comments/original/';
    protected $comment_media_path_thumb = '/uploads/documents/groups/comments/thumb/';
    protected $comment_club_media_path_thumb_crop = '/uploads/documents/groups/comments/thumb_crop/';
    protected $user_profile_type_code = 22;
    protected $profile_type_code = 'user';
    protected $youtube = 'youtube';
    protected $miss_param = '';
    protected $club_comment_thumb_image_width = 654;
    protected $club_comment_thumb_image_height = 360;
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
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename)
    {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }
    /**
    * Get User Manager of FOSUSER bundle
    * @return Obj
    */
   protected function getUserManager()
   {
           return $this->container->get('fos_user.user_manager');
   }
    /**
    * Update comment
    * @param Request object	
    * @return json string
    */
    public function postCommentupdatesAction(Request $request)
    {
        //initilise the data array
        $data = array();
        $data_obj = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $time = new \DateTime("now");
        $required_parameter = array('session_id','comment_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = $de_serialize['session_id'] ;
        $comment_id = $de_serialize['comment_id'] ;
        $body =  (isset($de_serialize['body']) ? $de_serialize['body'] : '');
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $user_id));
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;

        if($user=='')
        {            
            $data[] = "USER_ID_IS_INVALID";
        }
        if(!empty($data))
        {
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
        }
        //$container = MessageMessageBundle::getContainer();
        if(isset($_FILES['commentfile'])){
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }
        
        $comment_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $comment_res = $comment_dm
                ->getRepository('PostPostBundle:Comments')
                ->findOneBy(array("id" =>$comment_id));
        $group_id = 0;
        if($comment_res)
        {
            $post_id = $comment_res->getPostId();
            $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $post_res = $post_dm
                    ->getRepository('PostPostBundle:Post')
                    ->findOneBy(array("id" =>$post_id));            
          
            if($post_res)
            {
                $group_id = $post_res->getPostGid();
            }
         }else{
            $data[] = "COMMENT_ID_DOES_NOT_EXIST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
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
         
        //for group ACL      
       $group_mask = $this->userGroupRole($group_id,$user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15','7');
        $do_action = 0;
        if(in_array($group_mask,$allow_group)){
            $do_action = 1;
        }
 
        if($do_action == 0){
         //for group friend ACL
            $post_mask = $this->userGroupFriendRole($post_id,$user_id);
            $allow_friend = array('15','7');

            if(in_array($post_mask,$allow_friend)){
                $do_action = 1;                   
            }
            
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                    ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                    ->isActiveMember((int)$user_id,$group_id); 

            if($friend_res){  
                $comment_mask = $this->userCommentsFriendRole($comment_id,$user_id);
                $allow_comment_friend = array('15','7');
                if(in_array($comment_mask,$allow_comment_friend)){
                    $do_action = 1;
                }
                
            }
        }
        
        if($do_action==1)
        {
               
            if($comment_res)
            {
                $comment_res->setCommentText($body);
                $comment_res->setCommentUpdatedAt($time);
                $comment_res->setTagging($tagging);
                $comment_dm->flush();
                
                $comment_thumb_image_width  = $this->club_comment_thumb_image_width;
                $comment_thumb_image_height = $this->club_comment_thumb_image_height;
                 //for file uploading...
             
                if(isset($_FILES['commentfile'])){
                    foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                        
                         //find media information 
                        $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1]; 

                        //call service to get image type. Basis of this we save data 3,2,1 in db
                        $image_type_service = $this->get('user_object.service');
                        $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$comment_thumb_image_width,$comment_thumb_image_height);
                                
                        $original_file_name = $_FILES['commentfile']['name'][$key];
                        $file_name = time().strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                        if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                            $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                            $file_type = $_FILES['commentfile']['type'][$key];
                            $media_type = explode('/', $file_type);
                            $actual_media_type = $media_type[0];

                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
                            $dashboard_comment_media = new CommentMedia();
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
                            $dashboard_comment_media->setIsActive(1);
                            $dashboard_comment_media->setImageType($image_type);
                            $dashboard_comment_media->upload($comment_id, $key, $file_name); //uploading the files.
                            $dm->persist($dashboard_comment_media);
                            $dm->flush();

                            if ($actual_media_type == 'image') {
                                $media_original_path = $this->getBaseUri() . $this->comment_media_path . $comment_id . '/';
                                $thumb_dir           = $this->getBaseUri() . $this->comment_media_path_thumb . $comment_id . '/';
                                //$this->createThumbnail($file_name, $media_original_path, $thumb_dir, $comment_id);
                                $this->createCenterThumbnail($file_name, $media_original_path, $thumb_dir, $comment_id);
                            }         
                        }
                    }
                }
                $comment_id = $comment_res->getId();
                if($comment_res->getId()){
                    $dm_obj = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                    $comment_obj = $dm_obj->getRepository('PostPostBundle:Comments')
                            ->find($comment_id);
                }
                $comment_media_result = array();
                $comment_user_info = array();
                if($comment_obj){
                    $dm_obj_media = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
                    $comment_obj_media = $dm_obj_media->getRepository('PostPostBundle:CommentMedia')
                            ->findBy(array('comment_id' => $comment_id,'is_active'=>1));
                    if($comment_obj_media){
                        foreach ($comment_obj_media as $comment_media_data) {
                            $comment_media_id = $comment_media_data->getId();
                            $comment_media_type = $comment_media_data->getType();
                            $comment_media_name = $comment_media_data->getMediaName();
                            $comment_media_status = $comment_media_data->getIsActive();
                            $comment_media_is_featured = $comment_media_data->getIsFeatured();
                            $comment_media_created_at = $comment_media_data->getCreatedAt();
                            $comment_image_type = $comment_media_data->getImageType();
                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                    $comment_media_link = $post_res->getPath();
                                    $comment_media_thumb = '';
                            } else {
                                    $comment_media_link = $aws_path . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                    $comment_media_thumb = $aws_path . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                    'id' => $comment_media_id,
                                    'media_link' => $comment_media_link,
                                    'media_thumb_link' => $comment_media_thumb,
                                    'status' => $comment_media_status,
                                    'is_featured' => $comment_media_is_featured,
                                    'create_date' => $comment_media_created_at,
                                    'image_type' =>$comment_image_type,
                                    );
                            }
                    }

                    $user_service = $this->get('user_object.service');
                    $comment_user_info = $user_service->UserObjectService($user_id);                           

                }

                $data_obj[] = array(
                    'id' => $comment_id,
                    'post_id' => $post_res->getId(),
                    'comment_text' => $comment_obj->getCommentText(),
                    'user_id' => $comment_obj->getCommentAuthor(),
                    'status' => $comment_obj->getStatus(),
                    'comment_user_info'=>$comment_user_info,
                    'create_date' => $comment_obj->getCommentCreatedAt(),
                    'comment_media_info' => $comment_media_result
                );    
                $data = $data_obj;  
                /*
                 $data = array(
                    'id'=>$comment_res->getId(),
                    'post_id'=>$comment_res->getPostId(),
                );
                 */
                
                if(!empty($newTagging)){
                    $postService = $this->container->get('post_detail.service');
                    $postLink = $postService->getStoreClubUrl(array('clubId'=>$post_res->getPostGid(), 'postId'=>$post_res->getId()), 'club');
                    $postService->commentTaggingNotifications($newTagging, $comment_obj->getCommentAuthor(), $comment_id, $postLink, 'club', true);
                }
                $res_data = array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                echo json_encode($res_data);
                exit();
            }else{
                $data[] = "COMMENT_ID_DOES_NOT_EXIST";
                $res_data = array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                echo json_encode($res_data);
                exit();
            }    
        }else{
            return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data);
        }
         
    }
    /**
    * Finding list of comments
    * @param request object	
    * @return json string
    */
    /* previous old function
    public function postCommentlistsAction(Request $request)
    {
        //initilise the data array
        $data = array();
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id','post_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = (int)$de_serialize['session_id']; 
        
        $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:20);
        $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);
        
        $post_id = $de_serialize['post_id']; 
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $user_id));        
        if($user=='')
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
                ->findOneBy(array("id" =>$post_id));            
        
        if($post_res)
        {
            $group_id = $post_res->getPostGid();
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
                    ->findOneBy(array("user_id" =>(int)$user_id,"group_id"=>$group_id)); 
          
            if(!$friend_res && $group_status==2)
            {
                $resp_data = array('code'=>'500','msg'=>'PERMISSION_DENIED', 'data'=>array());
                return $resp_data;
            }
        }else{
            $data[] = "GROUP_DOES_NOT_EXIT_FOR_THIS_POST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
        }
        
      
        $comment_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $comment_data = $comment_dm->getRepository('PostPostBundle:Comments')
        ->listingComments($post_id,$user_id,$limit,$offset);
       $comment_final_data = array();
       $sender_main_user_detail = array();
       $user_service = $this->get('user_object.service');
       if($comment_data){
           foreach($comment_data as $comment_loop) {
                $comment_sub_data = array();
              
                $comment_id = $comment_loop->getId();
                $comment_media_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
                $comment_media_data = $comment_dm->getRepository('PostPostBundle:CommentMedia')
                ->findBy(array('comment_id'=>$comment_id,'is_active'=>1));
                $message_media_link = '';
                $message_media_thumb = '';
                $media_info_arr = array();
                if($comment_media_data){                      
                    foreach($comment_media_data as $value){
                        $message_media_name = $value->getMediaName();
                        if($message_media_name){
                            $message_media_link = $aws_path . $this->comment_media_path . $comment_id . '/' . $message_media_name;
                            $message_media_thumb = $aws_path . $this->comment_media_path_thumb . $comment_id . '/' . $message_media_name;
//                            $media_info_arr_temp = array(
//                                'media_original_path'=>$message_media_link,
//                                'media_thumb_path' =>$message_media_thumb
//                            );
                            $media_info_arr[] = array(
                                'id' => $value->getId(),
                                'media_link' => $message_media_link,
                                'media_thumb_link' => $message_media_thumb,
                                'status' => $value->getIsActive(),
                                'is_featured' => $value->getIsFeatured(),
                                'create_date' => $value->getCreatedAt()
                            );
                        }
                        
                    }
                    

                }
                
                $sender_main_user_detail = $user_service->UserObjectService($comment_loop->getCommentAuthor());
           
                $comment_sub_data = array(
                    'id'=>$comment_loop->getId(),
                    'post_id'=>$comment_loop->getPostId(),
                    'comment_text'=>$comment_loop->getCommentText(),
                    'comment_user_info'=>$sender_main_user_detail,
                    'comment_created_at'=>$comment_loop->getCommentCreatedAt(),
                    'comment_updated_at'=>$comment_loop->getCommentUpdatedAt(),
                    'status'=>$comment_loop->getStatus(),
                    'comment_media_info'=>$media_info_arr,
                );
                $comment_final_data[] = $comment_sub_data;
            }  
       }
        
      
        $comment_count_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $comment_count_data = $comment_count_dm->getRepository('PostPostBundle:Comments')
        ->listingTotalComments($post_id);
        if($comment_count_data){
             $data_count = count($comment_count_data);
        }else{
             $data_count = 0;
        }
       
        $mediaposts = $this->get('doctrine_mongodb')->getRepository('PostPostBundle:PostMedia')
                         ->findBy(array('post_id' => $post_id));
      
        $media_data= array();
        if($mediaposts){
            foreach($mediaposts as $mediadata)
            {
                $mediaId    = $mediadata->getId();
                $mediaName  = $mediadata->getMediaName();
                $mediatype  = $mediadata->getMediaType();
                $mediadirec = $mediadata->getUploadDir();
                // $mediaPath  = $this->getBaseUri().$mediadirec.'/'.$post_id.'/'.$mediaName;
                $thumbDir    = $aws_path . $this->post_media_path_thumb . $post_id . '/'.$mediaName;
                $media_data = array('id'=>$mediaId,
                                       'media_name'=>$mediaName,
                                       'media_type'=>$mediatype,
                                       'media_path'=>$thumbDir,
                                     );

            } 
        }
         
        $post_detail = array();
        $post_author_detials = array();
        $post_author_detials = $user_service->UserObjectService($post_res->getPostAuthor());
        $post_detail[] = array('post_id'=>$post_id,
                             'post_title'=>$post_res->getPostTitle(),
                             'post_created'=>$post_res->getPostCreated(),
                             'post_description'=>$post_res->getPostDesc(),
                             'post_author'=>$post_author_detials,
                             'media_info'=>$media_data
                         );
        $final_arr = array();
        $final_arr['post'] = $post_detail;
        $final_arr['comments'] = $comment_final_data;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $final_arr,'total'=>$data_count);
        echo json_encode($res_data);
        exit();
        
    }
    */
    
    /**
    * Finding list of comments
    * @param request object	
    * @return json string
    */
    public function postCommentlistsAction(Request $request)
    {
        //initilise the data array
        $data = array();
        $comment_user_ids = array();
        $users_array    = array();
        $comments_media = array();
        $comment_data   = array();
        $comment_ids = array();
        $comment_user_ids = array();
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id','post_id');
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = (int)$de_serialize['session_id']; 
        
        $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:20);
        $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);
        
        $post_id = $de_serialize['post_id']; 
        
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        
        
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $user_id));        
        if($user=='')
        {            
            $data[] = array();
        }
        if(!empty($data))
        {
            return array('code'=>99, 'message'=>'USER_ID_IS_INVALID','data'=>$data); 
        }
        $group_id = 0;
        $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $post_res = $post_dm
                ->getRepository('PostPostBundle:Post')
                ->findOneBy(array("id" =>$post_id));            
        
        if($post_res)
        {
            $group_id = $post_res->getPostGid();
        }else {
            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
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
                $resp_data = array('code'=>'500','msg'=>'PERMISSION_DENIED', 'data'=>array());
                return $resp_data;
            }
        }else{
            $data[] = array();
            return array('code'=>100, 'message'=>'GROUP_DOES_NOT_EXIT_FOR_THIS_POST','data'=>$data);
        }
        
        $comments_count = 0;
        //finding the comments start.
        $comments = $dm->getRepository('PostPostBundle:Comments')
                ->findBy(array('post_id' => $post_id, 'status'=>1), array('comment_created_at' => 'ASC'), $limit, $offset);
        
        
        
        //if there is any comments for this post then find the other things.
        if (count($comments)) {
            $comments_count = count($dm->getRepository('PostPostBundle:Comments')
                        ->findBy(array('post_id' => $post_id, 'status'=>1))); //find total count of active comments.
            
            //comments ids
            $comment_ids = array_map(function($comment_data) {
                    return "{$comment_data->getId()}";
                }, $comments);

            //comments user ids.    
            $comment_user_ids = array_map(function($comment_data) {
                    return "{$comment_data->getCommentAuthor()}";
            }, $comments);  

            //finding the comments media.
            $comments_media = $dm->getRepository('PostPostBundle:CommentMedia')
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
                $comment_user_id = $comment->getCommentAuthor();
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
                            $comment_media_link  = $comment_media_data->getPath();
                            $comment_media_thumb = '';
                        } else {
                            $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
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
                
                //comment user info.
                $comment_user_info = isset($users_object_array[$comment_user_id]) ? $users_object_array[$comment_user_id] : array();
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
                $comment_data[] = array(
                    'id' => $comment_id,
                    'post_id' => $comment->getPostId(),
                    'comment_text' => $comment->getCommentText(),
                    'comment_user_info' => $comment_user_info,
                    'create_date' => $comment->getCommentCreatedAt(),
                    'updated_date' => $comment->getCommentUpdatedAt(),
                    'status' => $comment->getStatus(),
                    'comment_media_info' => $comment_media_result,
                    'avg_rate'=>round($comment->getAvgRating(),1),
                    'no_of_votes' => (int)$comment->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'is_rated' =>$is_rated
                );                
                
            }
        }
        $data['comments'] = $comment_data;
        $final_data_array =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data,'total'=>$comments_count);
        echo json_encode($final_data_array);
        exit;        
        
        
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
    * delete comment
    * @param request object	
    * @return json string
    */
    public function postCommentdeletesAction(Request $request)
    {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id','comment_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $user_id = $de_serialize['session_id'] ;
        $comment_id = $de_serialize['comment_id'] ;
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $user_id));        
        if($user=='')
        {            
            $data[] = "USER_ID_IS_INVALID";
        }
        if(!empty($data))
        {
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
        }
        //$container = MessageMessageBundle::getContainer();
        $comment_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $comment_res = $comment_dm
                ->getRepository('PostPostBundle:Comments')
                ->findOneBy(array("id" =>$comment_id));
        $group_id = 0;
        if($comment_res)
        {
            $post_id = $comment_res->getPostId();
            $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $post_res = $post_dm
                    ->getRepository('PostPostBundle:Post')
                    ->findOneBy(array("id" =>$post_id));            
       
            if($post_res)
            {
                $group_id = $post_res->getPostGid();
            }
         }else{
            $data[] = "COMMENT_ID_DOES_NOT_EXIST";
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
         }
         
         
        //for group ACL      
        $group_mask = $this->userGroupRole($group_id,$user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15','7');
        $do_action = 0;
        if(in_array($group_mask,$allow_group)){
            $do_action = 1;
        }
  
        if($do_action == 0){
         //for group friend ACL
           
            $post_mask = $this->userGroupFriendRole($post_id,$user_id);
            $allow_friend = array('15','7');

            if(in_array($post_mask,$allow_friend)){
                $do_action = 1;
            }
            
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                    ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                    ->isActiveMember((int)$user_id,$group_id); 

            if($friend_res){            
                $comment_mask = $this->userCommentsFriendRole($comment_id,$user_id);
                $allow_comment_friend = array('15','7');
                if(in_array($comment_mask,$allow_comment_friend)){
                    $do_action = 1;
                }
            }
        }
        
    	if($do_action==1)
        {
            if($comment_res)
            {
                $comment_dm->remove($comment_res);
                $comment_dm->flush();
                
                $comment_media_delete_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
                $comment_media_res = $comment_media_delete_dm
                        ->getRepository('PostPostBundle:CommentMedia')
                        ->findBy(array("comment_id" =>$comment_id)); 
                
                if($comment_media_res){
                    foreach($comment_media_res as $comment_media_res_del)
                    $comment_media_delete_dm->remove($comment_media_res_del);
                    $comment_media_delete_dm->flush();
                }
                $res_data = array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                echo json_encode($res_data);
                exit();
            }else{
                $data[] = "Comment Id does not exist";
                $res_data = array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                echo json_encode($res_data);
                exit();
            } 
        }else{
            return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data);
        }
            
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
    * Create comment
    * @param request object	
    * @return json string
    */
    public function postCreatecommentsAction(Request $request)
    {
        
        //initilise the data array
        $data = array();
        $data_obj = array();

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else{
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        $time = new \DateTime("now");
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('postid','session_id','comment_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $post_id = $de_serialize['postid'];
        $comment_author = $de_serialize['session_id'];
        $comment_type = $object_info->comment_type;
        $tagging = isset($object_info->tagging) ? $object_info->tagging : array();
        $comment_text = (isset($de_serialize['body']) ? $de_serialize['body'] :'');
        
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $comment_author));
        
        $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $post_res = $post_dm
                ->getRepository('PostPostBundle:Post')
                ->findOneBy(array("id" =>$post_id)); 
        $group_id = 0;
       
        if($post_res)
        {
            $group_id = $post_res->getPostGid();
        }
     
        if($sender_user=='')
        {            
            $data[] = "USER_ID_IS_INVALID";
        }
        
        if(!empty($data))
        {
            return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
        }
        
        if(isset($_FILES['commentfile'])){
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }
       
        //for group ACL      
        $group_mask = $this->userGroupRole($group_id, $comment_author);
        //check for Access Permission
        
        //only owner and admin can edit the group
        $allow_group = array('15','7');
        $do_action = 0;
        if(in_array($group_mask,$allow_group)){
            $do_action = 1;
        }
  
        if($do_action == 0){
         //for group friend ACL
            $friend_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $friend_res = $friend_dm
                    ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                    ->isActiveMember((int)$comment_author,$group_id); 
           
            if($friend_res){  
                    $do_action = 1;                
            }
        }
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        $container = PostPostBundle::getContainer();    
        if($do_action == 1)
        {
            if ($comment_type == 0) {
                if ($object_info->comment_id == '') {
                   
                    $comment_dm = $container->get('doctrine.odm.mongodb.document_manager');

                    $comment = new Comments();
                    $comment->setPostId($post_id);
                    $comment->setCommentText($comment_text);
                    $comment->setCommentAuthor($comment_author);
                    $comment->setCommentCreatedAt($time);
                    $comment->setCommentUpdatedAt($time);
                    $comment->setStatus(0); 
                    $comment->setTagging($tagging);
                    $comment_dm->persist($comment);
                    $comment_dm->flush();
                    $comment_id = $comment->getId(); //getting the last inserted id of comment.
                    
                    //code for ACL
                    $aclProvider = $this->get('security.acl.provider');
                    $objectIdentity = ObjectIdentity::fromDomainObject($comment);
                    $acl = $aclProvider->createAcl($objectIdentity);

                    // retrieving the security identity of the currently logged-in user
                    $user = $sender_user;
                    $securityIdentity = UserSecurityIdentity::fromAccount($user);

                    $builder = new MaskBuilder();
                    $builder
                        ->add('view')
                        ->add('edit')
                        ->add('create')
                        ->add('delete');
                    $mask = $builder->get();
                    // grant owner access
                    $acl->insertObjectAce($securityIdentity, $mask);
                    $aclProvider->updateAcl($acl); 
                    
                }else{
                    $comment_id  = $object_info->comment_id;
                }
                $dm = $container->get('doctrine.odm.mongodb.document_manager');
                $comment_res = $dm->getRepository('PostPostBundle:Comments')
                           ->find($comment_id);
                if (!$comment_res) {
                    return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                }
                $current_comment_media       = array();
                $group_comment_media_id  = 0;

                //get the image name clean service..
                $clean_name = $this->get('clean_name_object.service');
                $image_upload = $this->get('amazan_upload_object.service');
                //for file uploading...
                if(isset($_FILES['commentfile'])){
                    foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                        $original_file_name = $_FILES['commentfile']['name'][$key];
                        $file_name = time().strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                        $file_name = $clean_name->cleanString($file_name); //rename the file name, clean the image name.
                        if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                            $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                            $file_type = $_FILES['commentfile']['type'][$key];
                            $comment_thumb_image_width  = $this->club_comment_thumb_image_width;
                            $comment_thumb_image_height = $this->club_comment_thumb_image_height;
                            $media_type = explode('/', $file_type);
                            $actual_media_type = $media_type[0];
                            //find media information 
                        
                            $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                            $orignal_mediaWidth = $image_info[0];
                            $original_mediaHeight = $image_info[1];
                            //call service to get image type. Basis of this we save data 3,2,1 in db
                            $image_type_service = $this->get('user_object.service');
                            $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$comment_thumb_image_width,$comment_thumb_image_height);
                            
                            $dm = $this->get('doctrine.odm.mongodb.document_manager');
                            $dashboard_comment_media = new CommentMedia();
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
                            $dashboard_comment_media->setImageType($image_type);
                            $dashboard_comment_media->setIsActive(0);
                            $dashboard_comment_media->upload($comment_id, $key, $file_name); //uploading the files.
                            $dm->persist($dashboard_comment_media);
                            $dm->flush();
                            //get the group comment media id
                            $group_comment_media_id = $dashboard_comment_media->getId();
                            $group_comment_media_id = $dashboard_comment_media->getId();
                            $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_comments_media_path'). $comment_id . '/';
                            $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('club_comments_media_path') . $comment_id . '/';
                            $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_comments_media_path_thumb') . $comment_id . '/';
                            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('club_comments_media_path_thumb_crop') . $comment_id . "/";
                            $s3_post_media_path = $this->container->getParameter('s3_club_comments_media_path'). $comment_id;
                            $s3_post_media_thumb_path = $this->container->getParameter('s3_club_comments_media_thumb_path'). $comment_id;
                            $image_upload->imageUploadService($_FILES['commentfile'],$key,$comment_id,'club_comment',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);        
                        }
                    }
                }
                
                //finding the cureent media data.
                $comment_media_data = $dm->getRepository('PostPostBundle:CommentMedia')
                                      ->find($group_comment_media_id);
                $comment_media_name  = $comment_media_link = $comment_media_thumb = $comment_image_type = '';//initialize blank variables.
                if ($comment_media_data) {
                    $comment_media_name  = $comment_media_data->getMediaName();
                    $comment_image_type = $comment_media_data->getImageType();
                    $comment_media_link  = $aws_path . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                    $comment_media_thumb = $aws_path . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                }
                //sending the current media and post data.
                $data = array(
                    'id' => $comment_id,
                    'media_id' => $group_comment_media_id,
                    'media_link' => $comment_media_link,
                    'media_thumb_link' => $comment_media_thumb,
                    'image_type' =>$comment_image_type
                );
              
                return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                
            }else{
                $media_id = $object_info->media_id;
                $PostCommentId = $object_info->comment_id;
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $media_id_arr = array();
                $media_id_arr_temp = array();
                if($media_id!=""){
                    //$media_id_arr_temp = json_decode($media_id);
                    $media_id_arr = $media_id;
                }
                if(!empty($media_id_arr)){
                    $media_update_status = $dm->getRepository('PostPostBundle:CommentMedia')
                               ->publishCommentMediaImage($media_id_arr);
                }
                
                $postService = $this->get('post_detail.service');
                if($PostCommentId){
                    //finding the comment and making the comment publish.
                    $comment = $dm->getRepository('PostPostBundle:Comments')
                               ->find($object_info->comment_id);
                    if (!$comment) {
                        return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                    }
                    $comment_data = $this->getCommentObject($object_info); //finding the post object.
                    
                    $postService->sendCommentNotificationEmail($post_id,$comment_author, 'club', $object_info->comment_id, true, $tagging);
                    
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data); 
                    echo json_encode($res_data);
                    exit();
                } else{
                    
                    $comment_data = $this->getCommentWithoutImageObject($object_info,$sender_user); //finding the post object.
                    $postService->sendCommentNotificationEmail($post_id,$comment_author, 'club', $comment_data['id'], true, $tagging);
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $comment_data);
                    echo json_encode($res_data);
                    exit();
                }
            }
            
        }else{
            return array('code'=>'500', 'message'=>'PERMISSION_DENIED','data'=>$data); 
        }
        
    }  
    
     /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_data
     * @return array $commentdata
     */
    public function getCommentWithoutImageObject($object_data ,$sender_user)
    {
        //code for responding the current post data..
            $comment_data = array();
            $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
            
            $time    = new \DateTime('now');
          
            // updating the post data, making the post publish.
            //code for aws s3 server path
            $aws_base_path  = $this->container->getParameter('aws_base_path');
            $aws_bucket    = $this->container->getParameter('aws_bucket');
            $aws_path = $aws_base_path.'/'.$aws_bucket;
            
            
            if(!isset($object_data->body)){
                $body = "";
            }else{
                $body = $object_data->body;
            }
            $tagging = isset($object_data->tagging) ? $object_data->tagging : array();
            $comment = new Comments();
            $comment->setPostId($object_data->postid);
            $comment->setCommentText($body);
            $comment->setCommentAuthor($object_data->session_id);
            $comment->setCommentCreatedAt($time);
            $comment->setCommentUpdatedAt($time);
            $comment->setStatus(1); 
            $comment->setTagging($tagging);
            
            $dm->persist($comment);
            $dm->flush();
            
            //code for ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($comment);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $user = $sender_user;
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            $builder = new MaskBuilder();
            $builder
                ->add('view')
                ->add('edit')
                ->add('create')
                ->add('delete');
            $mask = $builder->get();
            // grant owner access
            $acl->insertObjectAce($securityIdentity, $mask);
            $aclProvider->updateAcl($acl); 
            
            $sender_user_info  = array();
            $user_service      = $this->get('user_object.service');

            $comment_id      = $comment->getId();
            $comment_user_id = $comment->getCommentAuthor(); //sender 

            $comment_media = $dm->getRepository('PostPostBundle:CommentMedia')
                             ->findBy(array('comment_id' => $comment_id,'is_active'=>1));
            
             // get entity manager object
            $post_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
            $post = $dm->getRepository('PostPostBundle:Post')
                    ->find($object_data->postid);
            $sender_user_info  = $user_service->UserObjectService($comment_user_id); //sender user object
            
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
                        $comment_media_link = $aws_path . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $aws_path . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
         $data = array(
            'id' => $comment_id,
            'post_id' => $object_data->postid,                 
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getCommentAuthor(),
            'status' => $comment->getStatus(),
            'comment_user_info'=>$sender_user_info,
            'create_date' => $comment->getCommentCreatedAt(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false
         );    
        $commentdata = $data;    
         
        return $commentdata;
    }
    
    /**
     * Finding the comment object. update the comment and send comment object.
     * @param array $object_data
     * @return array $commentdata
     */
    public function getCommentObject($object_data)
    {
        //code for responding the current post data..
            $comment_data = array();
            $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
            $comment_id = $object_data->comment_id;
            $time    = new \DateTime('now');
            $comment    = $dm->getRepository('PostPostBundle:Comments')
                          ->find($comment_id);

            // updating the post data, making the post publish.
            if(!isset($object_data->body)){
                $body = "";
            }else{
                $body = $object_data->body;
            }
            
            $tagging = isset($object_data->tagging) ? $object_data->tagging : array();
            
            $comment->setPostId($object_data->postid);
            $comment->setCommentText($body);
            $comment->setCommentAuthor($object_data->session_id);
            $comment->setCommentCreatedAt($time);
            $comment->setCommentUpdatedAt($time);
            $comment->setStatus(1);
            $comment->setTagging($tagging);
            
            $dm->persist($comment);
            $dm->flush();
            //code for aws s3 server path
            $aws_base_path  = $this->container->getParameter('aws_base_path');
            $aws_bucket    = $this->container->getParameter('aws_bucket');
            $aws_path = $aws_base_path.'/'.$aws_bucket;
            $sender_user_info  = array();
            $user_service      = $this->get('user_object.service');

            $comment_id      = $comment->getId();
            $comment_user_id = $comment->getCommentAuthor(); //sender 

            $comment_media = $dm->getRepository('PostPostBundle:CommentMedia')
                             ->findBy(array('comment_id' => $comment_id,'is_active'=>1));
            
             // get entity manager object
            $post_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
            $post = $dm->getRepository('PostPostBundle:Post')
                    ->find($object_data->postid);
            $sender_user_info  = $user_service->UserObjectService($comment_user_id); //sender user object
            
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
                        $comment_media_link = $aws_path . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                        $comment_media_thumb = $aws_path . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
         $data = array(
            'id' => $comment_id,
            'post_id' => $object_data->postid,                 
            'comment_text' => $comment->getCommentText(),
            'user_id' => $comment->getCommentAuthor(),
            'status' => $comment->getStatus(),
            'comment_user_info'=>$sender_user_info,
            'create_date' => $comment->getCommentCreatedAt(),
            'comment_media_info' => $comment_media_result,
            'avg_rate'=>0,
            'no_of_votes' =>0,
            'current_user_rate'=>0,
            'is_rated' =>false
         );    
        $commentdata = $data;    
         
        return $commentdata;
    }
    
    
     /**
    * 
    * @param type $request
    * @return type
    */
    public function getAppData(Request$request)
    {
          $content = $request->getContent();
         $dataer = (object)$this->decodeData($content);

         $app_data = $dataer->reqObj;
         $req_obj = $app_data; 
         return $req_obj;
    }
    /**
    * Functionality decoding data
    * @param json $object	
    * @return array
    */
    public function decodeData($req_obj)
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
     * Get Group Owner ACL code
     * @return int
     */
    public function getGroupOwnerAclCode()
    {
        $builder = new MaskBuilder();
            $builder
                ->add('view')
                ->add('create')
                ->add('delete')
                ->add('edit');
            return $builder->get();
    }
    
    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getGroupAdminAclCode()
    {
        $builder = new MaskBuilder();
            $builder
                ->add('view')
                ->add('create')
                ->add('edit');
            return $builder->get();
    }
    
    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getGroupFriendAclCode()
    {
        $builder = new MaskBuilder();
            $builder
                ->add('view');
            return $builder->get();
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
     * Get User role for comment
     * @param int $comment_id
     * @param int $user_id
     * @return int
     */
    public function userCommentsFriendRole($comment_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $post = $dm
                ->getRepository('PostPostBundle:Comments')
                ->findOneBy(array('id' => $comment_id)); //@TODO Add group owner id in AND clause.

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
     * resize original image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $comment_id) {  
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
        //uploads/documents/groups/comments/original/".$comment_id."/"
            //upload on amazon
        $s3imagepath = "uploads/documents/groups/comments/original/".$comment_id;
        $image_local_path =  $path_to_thumbs_directory . $filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }
    
    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {  
        $path_to_thumbs_directory = __DIR__."/../../../../web/uploads/documents/groups/comments/thumb_crop/".$comment_id."/";
     
        $path_to_image_directory = $media_original_path;
        $thumb_width  = $this->club_comment_thumb_image_width;
        $thumb_height = $this->club_comment_thumb_image_height;
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
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        $imagename = $filename;
        $filename = $media_original_path.$filename;
        
        
        if(preg_match('/[.](jpg)$/', $imagename)) {  
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

        $crop_image_width  = $this->club_comment_thumb_image_width;
        $crop_image_height = $this->club_comment_thumb_image_height;
        
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
        
        
        $path_to_thumbs_center_directory = __DIR__."/../../../../web/uploads/documents/groups/comments/thumb/".$comment_id."/";
        $path_to_thumbs_center_image_path = __DIR__."/../../../../web/uploads/documents/groups/comments/thumb/".$comment_id."/".$imagename;
        //   $path_to_thumbs_directory = $thumb_dir;
        //$path_to_image_directory = $media_original_path;
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        //upload on amazon
        $s3imagepath = "uploads/documents/groups/comments/thumb/".$comment_id;
        $image_local_path = $path_to_thumbs_center_image_path;
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
           $return_data = array('user_id'=>$user_profile_data->getUserId(),
               'first_name'=>$user_profile_data->getFirstName(),
               'last_name'=>$user_profile_data->getLastName(),
               'email'=>$user_profile_data->getEmail(),
               'gender'=>$user_profile_data->getGender(),
               'birth_date'=>$user_profile_data->getBirthDate(),
               'phone'=>$user_profile_data->getPhone(),
               'country'=>$user_profile_data->getCountry(),
               'street'=>$user_profile_data->getStreet(),
               'profile_type'=>$user_profile_data->getProfileType(),
               'created_at'=>$user_profile_data->getCreatedAt(),
               'is_active'=>$user_profile_data->getIsActive(),
               'updated_at'=>$user_profile_data->getUpdatedAt(),
               'profile_setting'=>$user_profile_data->getProfileSetting(),
               'type'=>'user'
           );
       }
       return $return_data;
   }
    /**
    * getting the store info object
    * @param int $store_id
    * @return array
    */
   private function getStoreInfo($store_id,$user_id) {
       
        //get entity manager object
       $em = $this->getDoctrine()->getManager();

       //fire the query in Store Repository
       $return_store_profile_data = $em
                            ->getRepository('StoreManagerStoreBundle:Store')
                            ->findBy(array('id'=>$store_id));
       $return_data = array();
       if (count($return_store_profile_data)) {
           $store_profile_data = $return_store_profile_data[0];
           $return_data = array(
               'user_id'=>$user_id,
               'store_id'=>$store_profile_data->getId(),
               'parent_store_id'=>$store_profile_data->getParentStoreId(),
               'title'=>$store_profile_data->getTitle(),
               'email'=>$store_profile_data->getEmail(),
               'url'=>$store_profile_data->getUrl(),
               'description'=>$store_profile_data->getDescription(),
               'address'=>$store_profile_data->getAddress(),
               'contact_number'=>$store_profile_data->getContactNumber(),
               'created_at'=>$store_profile_data->getCreatedAt(),
               'is_active'=>$store_profile_data->getIsActive(),
               'is_allowed'=>$store_profile_data->getIsAllowed(),
               'type'=>'store'
           );
       }
       return $return_data;
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
}
