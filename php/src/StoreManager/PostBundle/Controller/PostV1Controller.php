<?php

namespace StoreManager\PostBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use StoreManager\PostBundle\Document\StorePosts;
use StoreManager\PostBundle\Document\StorePostsMedia;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
//acl comopnent
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use StoreManager\PostBundle\Document\StoreCommentsMedia;
use StoreManager\PostBundle\Document\StoreComments;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class PostV1Controller extends Controller {

    protected $miss_param = '';
    protected $youtube = '';
    protected $post_media_path = '/uploads/stores/posts/original/';
    protected $post_media_path_thumb = 'uploads/stores/posts/thumb/';
    protected $post_media_path_thumb_crop = 'uploads/stores/posts/thumb_crop/';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $store_post_thumb_image_width = 654;
    protected $store_post_thumb_image_height = 360;
    protected $comment_media_path = '/uploads/documents/store/comments/original/';
    protected $comment_media_path_thumb = '/uploads/documents/store/comments/thumb/';
    protected $post_comment_limit = 4;
    protected $post_comment_offset = 0;
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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Function to retrieve current applications base URI 
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
     * Checking for file extension
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        $file_error = 0;
        foreach ($_FILES['store_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['store_media']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.
                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['store_media']['type'][$key] == 'image/jpeg' ||
                        $_FILES['store_media']['type'][$key] == 'image/jpg' ||
                        $_FILES['store_media']['type'][$key] == 'image/gif' ||
                        $_FILES['store_media']['type'][$key] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
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
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeDataAction($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * create store post
     * @param Request $request	
     * @return array
     */
    public function postStorepostsAction(Request $request) {    
        //Code start for getting the request    
        $data = array();
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
        $object_info = (object) $de_serialize; //convert an array into object

        $required_parameter = array('store_id', 'user_id', 'post_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
 
        //check for link_type
        if (isset($object_info->link_type)) {
            $link_type = $object_info->link_type;
        } else {
            $link_type = 0;
        }
        
        $share_type = isset($object_info->share_type)?$object_info->share_type:'';
        $share_type = strtoupper(trim($share_type));
        $transaction_id = isset($object_info->transaction_id)?$object_info->transaction_id :'';
        //check for valid share type
        if(!($share_type == 'TXN' || $share_type == '')) {
          $res_data = array('code' => 410, 'message' => 'INVALID_SHARE_TYPE', 'data' => $data);
          echo json_encode($res_data);
          exit();
        }
        //check if share_type is TXN then customer_voting is must
        if($share_type == 'TXN' && !(isset($object_info->customer_voting))) {
          $res_data = array('code' => 411, 'message' => 'CUSTOMER_RATING_REQUIRED', 'data' => $data);
          echo json_encode($res_data);
          exit();
        }
        
        //check if share_type is TXN then customer_voting is must
        if($share_type == 'TXN' && !(isset($object_info->transaction_id))) {
          $res_data = array('code' => 1050, 'message' => 'TRANSACTION_ID_REQUIRED', 'data' => $data);
          echo json_encode($res_data);
          exit();
        }
        
        
        //getting the customer review if send other wise set to 0 and type cast to int
        $customer_voting = (int) isset($object_info->customer_voting)?$object_info->customer_voting:0;
         //check for valid transaction rating
        $allow_ratings = array(0,1,2,3,4,5);
        if(!in_array($customer_voting, $allow_ratings)) {
          $res_data = array('code' => 412, 'message' => 'INVALID_RATING', 'data' => $data);
          echo json_encode($res_data);
          exit();
        }
        $post_type = $de_serialize['post_type'];
        $allow_post_type = array('0', '1');
        $post_type = $object_info->post_type;

        //check for post type
        if (!in_array($post_type, $allow_post_type)) {
            return array('code' => 100, 'message' => 'INVALID_POST_TYPE', 'data' => $data);
        }
  
        if ($this->getRequest()->getMethod() === 'POST') {
           
            $data = array();
            if (isset($_FILES['store_media'])) {
                $file_error = $this->checkFileTypeAction(); //checking the file type extension.
                if ($file_error) {
                    return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
                }
            }
            $store_id = $object_info->store_id;

            $post_id = (isset($object_info->post_id) ? $object_info->post_id : '');
            $StorePostTitle = (isset($object_info->post_title) ? $object_info->post_title : '');
            $StorePostDesc = (isset($object_info->post_desc) ? $object_info->post_desc : '');
            $postyoutube = (isset($object_info->youtube) ? $object_info->youtube : '');
            $StorePostUserId = (int) $object_info->user_id;
    
            //Code for ACL checking
            $userManager = $this->getUserManager();
            $sender_user = $userManager->findUserBy(array('id' => $StorePostUserId));

            if ($sender_user == '') {
                $data[] = "User Id is invalid";
            }
            if (!empty($data)) {
                return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            }
            //for group ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array("id" => $store_id)); //@TODO Add group owner id in AND clause.

            if(!$store) {
               $res_data = array('code' => 413, 'message' => 'INVALID_STORE', 'data' => $data);
               echo json_encode($res_data);
               exit();
            }
            $do_action = 0;
            $group_mask = $this->userStoreRole($store_id, $StorePostUserId);
            //check for Access Permission
            //only owner and admin can edit the group
            $allow_group = array('15', '7');
            if (in_array($group_mask, $allow_group)) {
                $do_action = 1;
            }

            if ($do_action == 0) {
                //for group guest ACL
                
                //get store avg_rating and vote count at customer review time
                $store_avg_rating = $store->getAvgRating();
                $store_vote_count = $store->getVoteCount();
                $is_store_allow = $store->getIsAllowed();
                if ($is_store_allow == 1) {
                    $do_action = 1;
                }
            }
            if ($do_action == 1) {
                
                $dm = $this->get('doctrine.odm.mongodb.document_manager');

                if ($post_type == 0) {
                    if ($post_id == "") {
                        $StorePost = new StorePosts();
                        $StorePost->setStoreId($store_id);
                        $StorePost->setStorePostTitle($StorePostTitle);
                        $StorePost->setStorePostDesc($StorePostDesc);
                        $StorePost->setLinkType($link_type);
                        $StorePost->setStorePostAuthor($StorePostUserId);
                        $time = new \DateTime("now");
                        $StorePost->setStorePostCreated($time);
                        $StorePost->setStorePostUpdated($time);
                        $StorePost->setStorePostStatus(0);
                        if($share_type == 'TXN') {
                          $StorePost->setShareType($share_type);
                          $StorePost->setCustomerVoting($customer_voting);
                          $StorePost->setStoreVotingAvg($store_avg_rating);
                          $StorePost->setStoreVotingCount($store_vote_count);
                          $StorePost->setTransactionId($transaction_id);
                        }
                        $dm->persist($StorePost);
                        $dm->flush();

                        //get post id
                        $store_post_id = $StorePost->getId();
                        //Set ACL for post object of store
                        $aclProvider = $this->get('security.acl.provider');
                        $objectIdentity = ObjectIdentity::fromDomainObject($StorePost);
                        $acl = $aclProvider->createAcl($objectIdentity);

                        // retrieving the security identity of the currently logged-in user

                        $securityIdentity = UserSecurityIdentity::fromAccount($sender_user);

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

                        //get last insert post data
                        $StorePostId = $StorePost->getId();
                        
                    } else {
                       
                        $StorePostId = $store_post_id = $object_info->post_id;
                    }

                    /*                     * ********* post media data************************ */
                    $stote_post_media_id = 0;
                    $store_post_thumb_image_width = $this->store_post_thumb_image_width;
                    $store_post_thumb_image_height = $this->store_post_thumb_image_height;

                    $i = 0;
                    //get the image name clean service..
                    $clean_name = $this->get('clean_name_object.service');
                    $image_upload = $this->get('amazan_upload_object.service');
                    if (isset($_FILES['store_media'])) {
                        foreach ($_FILES['store_media']['tmp_name'] as $key => $tmp_name) {
                            //find media information 
                            $image_info = getimagesize($_FILES['store_media']['tmp_name'][$key]);
                            $orignal_mediaWidth = $image_info[0];
                            $original_mediaHeight = $image_info[1];

                            //call service to get image type. Basis of this we save data 3,2,1 in db
                            $image_type_service = $this->get('user_object.service');
                            $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $store_post_thumb_image_width, $store_post_thumb_image_height);
                            $original_media_name = $_FILES['store_media']['name'][$key];
                            if (!empty($original_media_name)) { //if file name is not exists means file is not present.
                                // $storeMediaName = time().$_FILES['store_media']['name'][$key]; 
                                $storeMediaName = time() . strtolower(str_replace(' ', '', $_FILES['store_media']['name'][$key]));
                                $storeMediaName = $clean_name->cleanString($storeMediaName); //rename the file name, clean the image name.
                                $storeMediatype = $_FILES['store_media']['type'][$key];
                                $Mediatype = explode('/', $storeMediatype);
                                $mediatypeName = $Mediatype[0];
                                $StorePostMedia = new StorePostsMedia();
                                $StorePostMedia->setPostId($StorePostId);
                                $StorePostMedia->setMediaName($storeMediaName);
                                $StorePostMedia->setMediaType($storeMediatype);
                                $StorePostMedia->setMediaStatus(0);
                                $StorePostMedia->setImageType($image_type);
                                //there are more than one images make first image fetaured image
                                // this would be treat like post featured image 
                                if ($i == 0) {
                                    $StorePostMedia->setIsFeatured(1);
                                } else {
                                    $StorePostMedia->setIsFeatured(0);
                                }
                                $dm->persist($StorePostMedia);
                                $dm->flush();

                                //get last inserted post media id
                                $stote_post_media_id = $StorePostMedia->getId();
                                $i++;
                                $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path'). $StorePostId . '/';;
                                $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path') . $StorePostId . '/';
                                $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path_thumb') . $StorePostId . '/';
                                $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path_thumb_crop') . $StorePostId . "/";
                                $s3_post_media_path = $this->container->getParameter('s3_store_post_media_path'). $StorePostId;
                                $s3_post_media_thumb_path = $this->container->getParameter('s3_store_post_media_thumb_path'). $StorePostId;
                                $image_upload->imageUploadService($_FILES['store_media'],$key,$StorePostId,'store_post',$storeMediaName,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path);
                            }
                        }
                    }
 
                    if (!empty($postyoutube)) {
                        $StorePostMedia = new StorePostsMedia();
                        $StorePostMedia->setPostId($StorePostId);
                        // make media name blank for youtube 
                        $StorePostMedia->setMediaName('');
                        $StorePostMedia->setMediaType('youtube');
                        $StorePostMedia->setYoutube($postyoutube);
                        $StorePostMedia->setImageType($image_type);
                        $dm->persist($StorePostMedia);
                        $dm->flush();
                    }

                    //finding the cureent media data.
                    $post_media_data = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                            ->find($stote_post_media_id);
                    if (!$post_media_data) {
                        return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                    }
                    $post_media_name = $post_media_link = $post_media_thumb = $post_image_type =''; //initialize blank variables.
                    if ($post_media_data) {
                        $post_image_type = $post_media_data->getImageType();
                        $post_media_name = $post_media_data->getMediaName();
                        $post_media_link = $this->getS3BaseUri() . $this->post_media_path . $store_post_id . '/' . $post_media_name;
                        $post_media_thumb = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $store_post_id . '/' . $post_media_name;
                    }
                    //sending the current media and post data.
                    $data = array(
                        'post_id' => $store_post_id,
                        'media_id' => $stote_post_media_id,
                        'media_link' => $post_media_link,
                        'media_thumb_link' => $post_media_thumb,
                        'image_type' =>$post_image_type,
                        'avg_rate'=>   0,
                        'no_of_votes'=> 0,
                        'share_type'=> $share_type,
                        'customer_voting' => $customer_voting,
                        'store_voting_avg' => $store_avg_rating,
                        'store_voting_count' => $store_vote_count,
                        'current_user_rate'=>0,
                        'is_rated' => 'false' 
                    );
                    
                    
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                    echo json_encode($res_data);
                    exit();
                } else {
                    
                    $postService = $this->get('post_detail.service');
                    $StorePostId = $object_info->post_id;
                    if ($StorePostId != "") {
                        
                        // publish the post with required info
                        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                                ->findOneBy(array('id' => $StorePostId));
                        if (!$posts) {
                            return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                        }

                        $post_data = $this->getPostObject($object_info); //finding the post object.
                        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                        
                        $postService->sendPostNotificationEmail($post_data, 'store', true);
                        echo json_encode($res_data);
                        exit();
                    } else {
                       
                        $post_data = $this->getPostWithoutImageObject($object_info,$share_type,$customer_voting,$store_avg_rating,$store_vote_count); //finding the post object.
                        
                      
                        
                        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                        
                        $postService->sendPostNotificationEmail($post_data, 'store', true);
                        if($share_type == 'TXN') {
                        //update to applane
                        $appalne_data = $de_serialize;
                        //get dispatcher object
                        $event = new FilterDataEvent($appalne_data);
                        $dispatcher = $this->container->get('event_dispatcher');
                        $dispatcher->dispatch('transaction.sharerating', $event);
                        //end dispacher event
                         }
                        echo json_encode($res_data);
                        exit();
                    }
                }
            } else {
                $res_data = array('code' => 100, 'message' => 'PERMISSION_DENIED', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
        }
    }

    /**
     * Finding the post object. update the post and send post object.
     * @param type $post_id
     * @return array $postdata
     */
    public function getPostObject($object_data) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $StorePostId = $object_data->post_id;
        $StorePost = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findOneBy(array('id' => $StorePostId));


        $store_id = $object_data->store_id;
        $StorePostTitle = $object_data->post_title;
        $StorePostDesc = $object_data->post_desc;
        $StorePostUserId = $object_data->user_id;

        //check for link_type
        if (isset($object_data->link_type)) {
            $link_type = $object_data->link_type;
        } else {
            $link_type = 0;
        }

        $StorePost->setStoreId($store_id);
        $StorePost->setStorePostTitle($StorePostTitle);
        $StorePost->setStorePostDesc($StorePostDesc);
        $StorePost->setLinkType($link_type);
        $StorePost->setStorePostAuthor($StorePostUserId);
        $time = new \DateTime("now");
        $StorePost->setStorePostCreated($time);
        $StorePost->setStorePostUpdated($time);
        $StorePost->setStorePostStatus(1);
        $dm->persist($StorePost);
        $dm->flush();

        //publish the post images

        $media_id = (isset($object_data->media_id) ? $object_data->media_id : '');
        $images = $media_id;


        if (count($images) > 0) {
            $publish_image = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->publishStorePostImage($images);
        }

        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('id' => $StorePostId));

        $postDetail = array();

        //get user object
        $user_service = $this->get('user_object.service');
        foreach ($posts as $post) {

            $postId = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findBy(array('post_id' => $postId, 'media_status' => 1));
            $mediaData = array();
            foreach ($mediaposts as $mediadata) {
                $mediaId = $mediadata->getId();
                $mediaName = $mediadata->getMediaName();
                $mediatype = $mediadata->getMediaType();
                $isfeatured = $mediadata->getIsFeatured();
                $youtube = $mediadata->getYoutube();
                $postId = $post->getId();
                $post_image_type = $mediadata->getImageType();

                $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $StorePostId . '/' . $mediaName;
                $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $StorePostId . '/' . $mediaName;

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

            //get author object
            $post_auth = $post->getStorePostAuthor();
            $user_info = $user_service->UserObjectService($post_auth);

            $postDetail = array('post_id' => $postId,
                'store_id' => $post->getStoreId(),
                'store_post_title' => $post->getStorePostTitle(),
                'store_post_desc' => $post->getStorePostDesc(),
                'store_post_author' => $post->getStorePostAuthor(),
                'store_post_created' => $post->getStorePostCreated(),
                'media_info' => $mediaData,
                'user_profile' => $user_info,
                'avg_rate'=>   0,
                'no_of_votes'=> 0,
                'current_user_rate'=>0,
                'share_type'=>$post->getShareType(),
                'customer_voting'=>$post->getCustomerVoting(),
                'store_voting_avg'=>$post->getStoreVotingAvg(),
                'store_voting_count'=>$post->getStoreVotingCount(),
                'is_rated' => 'false' 
            );
        }
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $postDetail);
    }

    /**
     * Finding the post object. update the post and send post object.
     * @param type $post_id
     * @param int $customer_voting voting given by customer on sharing (mobile)
     * @param int $store_avg_rating avg rating of the store at share time (mobile)
     * @param int $store_vote_count vote count of the store at share time (mobile)
     * @return array $postdata
     */
    public function getPostWithoutImageObject($object_data,$share_type='',$customer_voting=0 ,$store_avg_rating=0,$store_vote_count=0) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $StorePostId = $object_data->post_id;
        //get store post entity
        $StorePost = new StorePosts();
        $share_type = $share_type;
        $store_id = $object_data->store_id;
        $StorePostTitle = $object_data->post_title;
        $StorePostDesc = $object_data->post_desc;
        $StorePostUserId = $object_data->user_id;
        $transaction_id = isset($object_info->transaction_id)?$object_info->transaction_id :'';
        //check for link_type
        if (isset($object_data->link_type)) {
            $link_type = $object_data->link_type;
        } else {
            $link_type = 0;
        }

        $StorePost->setStoreId($store_id);
        $StorePost->setStorePostTitle($StorePostTitle);
        $StorePost->setStorePostDesc($StorePostDesc);
        $StorePost->setLinkType($link_type);
        $StorePost->setStorePostAuthor($StorePostUserId);
        $time = new \DateTime("now");
        $StorePost->setStorePostCreated($time);
        $StorePost->setStorePostUpdated($time);
         if($share_type == 'TXN') {
           $StorePost->setShareType($share_type);
           $StorePost->setCustomerVoting($customer_voting);
           $StorePost->setStoreVotingAvg($store_avg_rating);
           $StorePost->setStoreVotingCount($store_vote_count);
           $StorePost->setTransactionId($transaction_id);
        }
        
        $StorePost->setStorePostStatus(1);
        $dm->persist($StorePost);
        $dm->flush();

        //Set ACL for post object of store
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($StorePost);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $StorePostUserId));

        $securityIdentity = UserSecurityIdentity::fromAccount($sender_user);

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
        //end of ACL

        $StorePostId = $StorePost->getId();
        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('id' => $StorePostId));
        $postDetail = array();

        //get user object
        $user_service = $this->get('user_object.service');
        foreach ($posts as $post) {
            
            $postId = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findBy(array('post_id' => $postId, 'media_status' => 1));
            $mediaData = array();
            //get author object
            $post_auth = $post->getStorePostAuthor();
            $user_info = $user_service->UserObjectService($post_auth);

            $postDetail = array('post_id' => $postId,
                'store_id'=>$post->getStoreId(),
                'store_post_title' => $post->getStorePostTitle(),
                'store_post_desc' => $post->getStorePostDesc(),
                'store_post_author' => $post->getStorePostAuthor(),
                'store_post_created' => $post->getStorePostCreated(),
                'avg_rate'=>0,
                'no_of_votes'=>0,
                'avg_rate'=>0,
                'current_user_rate'=>0,
                'share_type'=>$post->getShareType(),
                'customer_voting'=>$post->getCustomerVoting(),
                'store_voting_avg'=>$post->getStoreVotingAvg(),
                'store_voting_count'=>$post->getStoreVotingCount(),
                'is_rated'=>false,
                'link_type' => (int) $post->getLinkType(),
                'media_info' => $mediaData,
                'user_profile' => $user_info
            );
        }
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $postDetail);
    }

    /**
     * Functionality return Post lists of a store
     * @param json $request
     * @return array
     */
//    public function postListstorepostsAction(Request $request) {
//        $data = array();
//        //Code start for getting the request
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeDataAction($freq_obj);
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
//        $required_parameter = array('store_id', 'user_id');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//
//        $userId = $object_info->user_id;
//        $storeId = $object_info->store_id;
//        $limit_start = (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
//        $limit_size = (isset($object_info->limit_size) ? (int) $object_info->limit_size : 20);
//        //Code for ACL checking
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $userId));
//
//        if ($sender_user == '') {
//            $data[] = "USER_ID_IS_INVALID";
//        }
//        if (!empty($data)) {
//            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
//        }
//
//        $em = $this->getDoctrine()->getManager();
//        $store = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
//
//        $is_store_active = $store->getIsActive();
//        if ($is_store_active != 1) {
//            return array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
//        }
//
//
//        //for store ACL    
//        $do_action = 0;
//        $group_mask = $this->userStoreRole($storeId, $userId);
//        //check for Access Permission
//        //only owner and admin can edit the group
//        $allow_group = array('15', '7');
//        if (in_array($group_mask, $allow_group)) {
//            $do_action = 1;
//        }
//
//        if ($do_action == 0) {
//            //for group guest ACL
//            $em = $this->getDoctrine()->getManager();
//            $store = $em
//                    ->getRepository('StoreManagerStoreBundle:Store')
//                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
//
//            $is_store_allow = $store->getIsAllowed();
//            if ($is_store_allow == 1) {
//                $do_action = 1;
//            }
//        }
//
//        //  if($do_action){
//        $dm = $this->get('doctrine.odm.mongodb.document_manager');
//
//        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
//                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1), array('store_post_created' => 'DESC'), $limit_size, $limit_start);
//        $postDetail = array();
//        
//        $postsCount = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
//                     ->findBy(array('store_id' => $storeId, 'store_post_status' => 1));
//
//        $post_detail = array();
//        $totalCount = 0;
//        if($postsCount){
//            $totalCount = count($postsCount);
//        }
//        
//        
//        //get user object
//        $user_service = $this->get('user_object.service');
//        foreach ($posts as $post) {
//            $postId = $post->getId();
//            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
//                    ->findBy(array('post_id' => $postId, 'media_status'=>1));
//            $mediaData = array();
//            foreach ($mediaposts as $mediadata) {
//                $mediaId = $mediadata->getId();
//                $mediaName = $mediadata->getMediaName();
//                $mediatype = $mediadata->getMediaType();
//                $isfeatured = $mediadata->getIsFeatured();
//                $youtube = $mediadata->getYoutube();
//                $postId = $post->getId();
//
//                $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
//                $thumbDir = $this->getS3BaseUri() .'/'. $this->post_media_path_thumb . $postId . '/' . $mediaName;
//
//                $mediaData[] = array('id' => $mediaId,
//                    'media_name' => $mediaName,
//                    'media_type' => $mediatype,
//                    'media_path' => $mediaDir,
//                    'media_thumb_path' => $thumbDir,
//                    'is_featured' => $isfeatured,
//                    'youtube' => $youtube,
//                );
//            }
//            
//            //finding the comments start.
//            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
//                    ->findBy(array('post_id' => $postId, 'status' => 1), array('comment_created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
//            $comments = array_reverse($comments);
//            $comment_data = array();
//            $comment_user_info = array();
//            $data_count = 0;
//            if ($comments) {
//                $comment_count_data = $dm->getRepository('StoreManagerPostBundle:StoreComments')
//                ->listingTotalComments($postId);
//                if($comment_count_data){
//                     $data_count = count($comment_count_data);
//                }else{
//                     $data_count = 0;
//                }
//                foreach ($comments as $comment) {
//                    $comment_id = $comment->getId();
//                    $comment_user_id = $comment->getcommentAuthor();                   
//                    //code for user active profile check                        
//                    $comment_user_info = $user_service->UserObjectService($comment_user_id);
//                    $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
//                            ->findBy(array('store_comment_id' => $comment_id, 'media_status' => 1));
//                    $comment_media_result = array();
//                    
//                    if ($comment_media) {                       
//                        foreach ($comment_media as $comment_media_data) {
//                            $comment_media_id = $comment_media_data->getId();
//                            $comment_media_type = $comment_media_data->getMediaType();
//                            $comment_media_name = $comment_media_data->getMediaName();
//                            $comment_media_status = $comment_media_data->getMediaStatus();
//                            $comment_media_is_featured = $comment_media_data->getIsFeatured();
//                            $comment_media_created_at = $comment_media_data->getMediaCreated();
//                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
//                                $comment_media_link = $comment_media_data->getPath();
//                                $comment_media_thumb = '';
//                            } else {
//                                $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
//                                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
//                            }
//
//                            $comment_media_result[] = array(
//                                'id' => $comment_media_id,
//                                'media_path' => $comment_media_link,
//                                'media_thumb' => $comment_media_thumb,
//                                'status' => $comment_media_status,
//                                'is_featured' => $comment_media_is_featured,
//                                'create_date' => $comment_media_created_at);
//                        }
//                    }
//
//                    $comment_data[] = array(
//                        'id' => $comment_id,
//                        'post_id' => $comment->getPostId(),
//                        'comment_text' => $comment->getCommentText(),
//                        'user_id' => $comment->getCommentAuthor(),
//                        'comment_user_info' => $comment_user_info,
//                        'status' => $comment->getStatus(),
//                        'comment_created_at' => $comment->getCommentCreatedAt(),
//                        'comment_media_info' => $comment_media_result,
//                            );
//                }  
//            }
//            
//            //get author object
//            $post_auth = $post->getStorePostAuthor();
//            $user_info = $user_service->UserObjectService($post_auth);
//
//            $postDetail[] = array('post_id' => $postId,
//                'store_post_title' => $post->getStorePostTitle(),
//                'store_post_desc' => $post->getStorePostDesc(),
//                'store_post_author' => $post->getStorePostAuthor(),
//                'store_post_created' => $post->getStorePostCreated(),
//                'link_type' => (int)$post->getLinkType(),
//                'media_info' => $mediaData,
//                'user_profile' => $user_info,
//                'comments' => $comment_data,
//                'comment_count' =>$data_count,
//             );
//        }
//        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail,'count'=>$totalCount);
//        echo json_encode($res_data);
//        exit();
//        /*   } else {
//          return array('code'=>100, 'message'=>'Access denied','data'=>$data);
//          }
//         */
//    }

    /**
     * Functionality return Post lists of a store
     * @param json $request
     * @return array
     */
    public function postListstorepostsAction(Request $request) { 
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

        $required_parameter = array('store_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $userId = $object_info->user_id;
        $storeId = $object_info->store_id;
        $limit_start = (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
        $limit_size = (isset($object_info->limit_size) ? (int) $object_info->limit_size : 20);
        $last_post_id = (isset($object_info->last_post_id) ? $object_info->last_post_id : '');
        
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $userId));

        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
                //
        //check for a valid store id
        if(!$store) {
            return array('code' => 413, 'message' => 'INVALID_STORE', 'data' => $data);
        }
        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            return array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
        }


        //for store ACL    
        $do_action = 0;
        $group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        if ($do_action == 0) {
            //for group guest ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.

            $is_store_allow = $store->getIsAllowed();
           
            if ($is_store_allow == 1) {
                $do_action = 1;
            }
        }

        //  if($do_action){
        $totalCount = $comment_count = 0;
        $postDetail = array(); //final array of post data...
        $post_detail = $post_sender_user_ids = $comment_user_ids = $mediaData = $comment_data = $comment_media_result = $postsCount = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        // get posts for a store 
        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                //->findBy(array('store_id' => $storeId, 'store_post_status' => 1), array('store_post_created' => 'DESC'), $limit_size, $limit_start);
                ->listStorePosts($storeId, $limit_size, $limit_start, $last_post_id);
        
        //get post count
        if (count($posts) > 0) {
            $postsCount = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                    ->findBy(array('store_id' => $storeId, 'store_post_status' => 1));
        }

        //get total count of post
        if ($postsCount) {
            $totalCount = count($postsCount);
        }

        //get posts id
        $postsIds = array_map(function($o) {
            return $o->getId();
        }, $posts);

        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($post) {
            return "{$post->getStorePostAuthor()}";
        }, $posts);

        if (count($postsIds)) {
            //get post media from the posts ids
            $post_medias = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->getPostMedia($postsIds);

            //get all coments for the post ids
            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                    ->getPostComments($postsIds);
//            $comments = array_reverse($comments);
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
       
        if (count($posts) > 0) {
            foreach ($posts as $post) {
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

                        $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
                        $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/' . $mediaName;

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
                                        $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                        $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
                            $current_rate = 0;
                            $is_rated = false;
                            foreach($comment->getRate() as $rate) {
                                if($rate->getUserId() == $userId ) {
                                    $current_rate = $rate->getRate();
                                    $is_rated = true;
                                    break;
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
                                'current_user_rate'=>$current_rate,
                                
                                'is_rated' => $is_rated
                            );
                        }
                        $comment_media_result = array();
                        $comment_count++;
                    }
                }
                
                $current_rate = 0;
                $is_rated = false;
                foreach($post->getRate() as $rate) {
                    if($rate->getUserId() == $userId ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                
                $post_auth = $post->getStorePostAuthor();
                $user_info = isset($users_object_array[$post_auth]) ? $users_object_array[$post_auth] : array();
                $postDetail[] = array('post_id' => $postId,
                    'store_post_title' => $post->getStorePostTitle(),
                    'store_post_desc' => $post->getStorePostDesc(),
                    'store_post_author' => $post->getStorePostAuthor(),
                    'store_post_created' => $post->getStorePostCreated(),
                    'link_type' => (int) $post->getLinkType(),
                    'media_info' => $mediaData,
                    'user_profile' => $user_info,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'avg_rate'=>round($post->getAvgRating(), 1),
                    'no_of_votes'=> (int) $post->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'share_type'=> $post->getShareType(),
                    'customer_voting'=> $post->getCustomerVoting(),
                    'store_voting_avg'=> $post->getStoreVotingAvg(),
                    'store_voting_count'=> $post->getStoreVotingCount(),
                    'is_rated' => $is_rated
                );
                $mediaData = array();
                $comment_data = array();
                $comment_count = 0;
            }
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail, 'count' => $totalCount);
        echo json_encode($res_data);
        exit();
        /*   } else {
          return array('code'=>100, 'message'=>'Access denied','data'=>$data);
          }
         */
    }

    /**
     * Functionality return Post lists of a store
     * Get external shop profile post.
     */
    public function listpublicstorepostsAction(Request $request) {
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

        $required_parameter = array('store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        //$userId = $object_info->user_id;
        $storeId = $object_info->store_id;
        $limit_start = (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
        $limit_size = (isset($object_info->limit_size) ? (int) $object_info->limit_size : 20);
        //Code for ACL checking
        $userManager = $this->getUserManager();

        if (!empty($data)) {
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
        if (!$store) {
            $res_data = array('code' => 100, 'message' => 'STORE_NOT_EXIST', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            $res_data = array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        $do_action = 0;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1), array('store_post_created' => 'DESC'), $limit_size, $limit_start);
        $postDetail = array();

        $postsCount = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1));

        $post_detail = array();
        $totalCount = 0;
        if ($postsCount) {
            $totalCount = count($postsCount);
        }

        //get user object
        $user_service = $this->get('user_object.service');
        foreach ($posts as $post) {
            $postId = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findBy(array('post_id' => $postId, 'media_status' => 1));
            $mediaData = array();
            foreach ($mediaposts as $mediadata) {
                $mediaId = $mediadata->getId();
                $mediaName = $mediadata->getMediaName();
                $mediatype = $mediadata->getMediaType();
                $isfeatured = $mediadata->getIsFeatured();
                $youtube = $mediadata->getYoutube();
                $postId = $post->getId();
                $post_image_type = $mediadata->getImageType();

                $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
                $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/' . $mediaName;

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

            //finding the comments start.
            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                    ->findBy(array('post_id' => $postId, 'status' => 1), array('comment_created_at' => 'DESC'), $this->post_comment_limit, $this->post_comment_offset);
            $comments = array_reverse($comments);
            $comment_data = array();
            $comment_user_info = array();
            $data_count = 0;
            if ($comments) {
                $comment_count_data = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                        ->listingTotalComments($postId);
                if ($comment_count_data) {
                    $data_count = count($comment_count_data);
                } else {
                    $data_count = 0;
                }
                foreach ($comments as $comment) {
                    $comment_id = $comment->getId();
                    $comment_user_id = $comment->getcommentAuthor();
                    //code for user active profile check                        
                    $comment_user_info = $user_service->UserObjectService($comment_user_id);
                    $comment_media = $dm->getRepository('StoreManagerPostBundle:StoreCommentsMedia')
                            ->findBy(array('store_comment_id' => $comment_id, 'media_status' => 1));
                    $comment_media_result = array();

                    if ($comment_media) {
                        foreach ($comment_media as $comment_media_data) {
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
                                $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                'id' => $comment_media_id,
                                'media_path' => $comment_media_link,
                                'media_thumb' => $comment_media_thumb,
                                'status' => $comment_media_status,
                                'is_featured' => $comment_media_is_featured,
                                'create_date' => $comment_media_created_at,
                                'image_type'=>$comment_image_type
                                    );
                        }
                    }

                    $comment_data[] = array(
                        'id' => $comment_id,
                        'post_id' => $comment->getPostId(),
                        'comment_text' => $comment->getCommentText(),
                        'user_id' => $comment->getCommentAuthor(),
                        'comment_user_info' => $comment_user_info,
                        'status' => $comment->getStatus(),
                        'comment_created_at' => $comment->getCommentCreatedAt(),
                        'comment_media_info' => $comment_media_result,
                    );
                }
            }

            //get author object
            $post_auth = $post->getStorePostAuthor();
            $user_info = $user_service->UserObjectService($post_auth);

            $postDetail[] = array('post_id' => $postId,
                'store_post_title' => $post->getStorePostTitle(),
                'store_post_desc' => $post->getStorePostDesc(),
                'store_post_author' => $post->getStorePostAuthor(),
                'store_post_created' => $post->getStorePostCreated(),
                'link_type' => (int) $post->getLinkType(),
                'media_info' => $mediaData,
                'user_profile' => $user_info,
                'comments' => $comment_data,
                'comment_count' => $data_count,
            );
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail, 'count' => $totalCount);
        echo json_encode($res_data);
        exit();
    }

    /**
     * deleting the post on user_id and post_id basis.
     * @param request object
     * @param json
     */
    public function postDeletestorepostsAction(Request $request) {
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

        $required_parameter = array('post_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $postId = $object_info->post_id;
        $userId = $object_info->user_id;
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $userId));

        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        // we are fetching the information of post. Eithere post exits or not for that store
        // here StorePosts is the table/collection and we are fetching information regarding this postId.
        // if post exits regarding this postId then ok, otherwise we show error message(record doesnot 
        // exits)

        $thread_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $thread_res = $thread_dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->findOneBy(array("id" => $postId));
        if (!$thread_res) {
            return array('code' => 100, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
        }
        //Now we are getting information (store_id) of this post
        $storeId = $thread_res->getStoreId();
        //for store ACL     
        $do_action = 0;
        $group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        if ($do_action == 0) {
            //for group friend ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $storeId));
            
            $is_store_allow = $store->getIsAllowed();
           
            if ($is_store_allow == 1) {
                // checking mask using acl. if user($StorePostUserId) is ownere of post ($postId)
                //post mask return value 15,7 i.e it is either author or creator of this post
                // if it is creator of this post it will update his post
                $post_mask = $this->userStoreGuestRole($postId, $userId);
                $allow_friend = array('15', '7');
                if (in_array($post_mask, $allow_friend)) {
                    $do_action = 1;
                }
            }
        }

        if ($do_action == 1) {

            /*             * * remove posts**** */
            $thread_dm->remove($thread_res);
            $thread_dm->flush();
            /*             * * remove corresponding media**** */

            $mediaposts = $this->get('doctrine_mongodb')
                    ->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->removePostsMedia($postId);
            if ($mediaposts) {
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $image_album_location = $file_location . $this->post_media_path . $postId;
                $thumbnail_album_location = $file_location . '/' . $this->post_media_path_thumb . $postId;
                // Commenting these line becauase images are not present on s3 Amazon server.
                //Since in push images folder are not used
                if (file_exists($image_album_location)) {
                    //  array_map('unlink', glob($image_album_location . '/*'));
                    //  rmdir($image_album_location);
                }
                if (file_exists($thumbnail_album_location)) {
                    //  array_map('unlink', glob($thumbnail_album_location . '/*'));
                    //  rmdir($thumbnail_album_location);
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
            $res_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Call api/updateStorePost action
     * @param Request $request	
     * @return array
     */
    public function postUpdatestorepostsAction(Request $request) {
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

        $required_parameter = array('post_id', 'store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $postId = $object_info->post_id;
        $storeId = $object_info->store_id;
        $postType = (isset($object_info->post_type) ? $object_info->post_type : 1); //default value will be 1.
        $StorePostTitle = (isset($object_info->post_title) ? $object_info->post_title : '');
        $StorePostDesc = (isset($object_info->post_desc) ? $object_info->post_desc : '');
        $postyoutube = (isset($object_info->youtube) ? $object_info->youtube : '');
        $userId = $object_info->user_id;
        if (isset($_FILES['store_media'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            }
        }


        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $userId));

        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        //for group ACL    
        $do_action = 0;
        $group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }
        if ($do_action == 0) {
            //for group friend ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
            $is_store_allow = $store->getIsAllowed();
            
            if ($is_store_allow == 1) {
                // checking mask using acl. if user($StorePostUserId) is ownere of post ($postId)
                //post mask return value 15,7 i.e it is either author or creator of this post
                // if it is creator of this post it will update his post
                $post_mask = $this->userStoreGuestRole($postId, $userId);
                $allow_friend = array('15', '7');
                if (in_array($post_mask, $allow_friend)) {
                    $do_action = 1;
                }
            }
        }

        if ($do_action == 1) {
            $post_dm = $this->container->get('doctrine.odm.mongodb.document_manager');

            $post_res = $post_dm->getRepository('StoreManagerPostBundle:StorePosts')
                    ->findOneBy(array("id" => $postId));
            $post_res->setStorePostTitle($StorePostTitle);
            $post_res->setStorePostDesc($StorePostDesc);
            $post_res->setStorePostAuthor($userId);
            $time = new \DateTime("now");
            $post_res->setStorePostUpdated($time);
            $post_res->setStorePostStatus(1);
            $post_dm->persist($post_res);
            $post_dm->flush();

            if ($post_res) {

                if ($postType == '0') {
                    $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                            ->findBy(array('post_id' => $postId));
                    /*                     * ********* post media data************************ */
                    $i = 0;
                    //get the image name clean service..
                    $clean_name = $this->get('clean_name_object.service');
                    $store_post_thumb_image_width = $this->store_post_thumb_image_width;
                    $store_post_thumb_image_height = $this->store_post_thumb_image_height;
                    
                    if (isset($_FILES['store_media'])) {
                        foreach ($_FILES['store_media']['tmp_name'] as $key => $tmp_name) {
                            $original_file_name = $_FILES['store_media']['name'][$key];
                            if (!empty($original_file_name)) {
                                // $storeMediaName = time().$_FILES['store_media']['name'][$key];
                                $storeMediaName = time() . strtolower(str_replace(' ', '', $_FILES['store_media']['name'][$key]));
                                $storeMediaName = $clean_name->cleanString($storeMediaName); //rename the file name, clean the image name.
                                $storeMediatype = $_FILES['store_media']['type'][$key];
                                $mediatype = explode('/', $storeMediatype);
                                
                                //find media information 
                                $image_info = getimagesize($_FILES['store_media']['tmp_name'][$key]);
                                $orignal_mediaWidth = $image_info[0];
                                $original_mediaHeight = $image_info[1]; 
                                
                                //call service to get image type. Basis of this we save data 3,2,1 in db
                                $image_type_service = $this->get('user_object.service');
                                $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$store_post_thumb_image_width,$store_post_thumb_image_height);
                                
                                $mediatypeName = $mediatype[0];
                                $StorePostMedia = new StorePostsMedia();
                                $StorePostMedia->setPostId($postId);
                                $StorePostMedia->setMediaName($storeMediaName);
                                $StorePostMedia->setMediaType($storeMediatype);
                                $StorePostMedia->setMediaStatus(0);
                                $StorePostMedia->setImageType($image_type);
                                //there are more than one images make first image fetaured image
                                // this would be treat like post featured image 
                                if ($i == 0) {
                                    $StorePostMedia->setIsFeatured(1);
                                } else {
                                    $StorePostMedia->setIsFeatured(0);
                                }
                                $StorePostMedia->upload($postId, $key, $storeMediaName);
                                $post_dm->persist($StorePostMedia);
                                $post_dm->flush();

                                $stote_post_media_id = $StorePostMedia->getId();
                                $i++;
                                if ($mediatypeName == 'image') {
                                    $mediaOriginalPath = $this->getS3BaseUri() . $this->post_media_path . $postId . '/';
                                    $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/';
                                    //$this->createThumbnail($storeMediaName, $mediaOriginalPath, $thumbDir, $postId);
                                    //crop the image from center
                                    $this->createCenterThumbnail($storeMediaName, $mediaOriginalPath, $thumbDir, $postId);
                                }
                            }
                        }
                    }

                    if (!empty($postyoutube)) {
                        $StorePostMedia = new StorePostsMedia();
                        $StorePostMedia->setPostId($postId);
                        // make media name blank for youtube 
                        $StorePostMedia->setMediaName('');
                        $StorePostMedia->setMediaType('youtube');
                        $StorePostMedia->setYoutube($postyoutube);
                        $post_dm->persist($StorePostMedia);
                        $post_dm->flush();
                    }


                    //get post image data
                    //sending the current media and post data.
                    //finding the cureent media data.
                    $post_media_data = $post_dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                            ->find($stote_post_media_id);
                    $post_media_name = $post_media_link = $post_media_thumb = $post_image_type =''; //initialize blank variables.
                    if ($post_media_data) {
                        $post_image_type = $post_media_data->getImageType();
                        $post_media_name = $post_media_data->getMediaName();
                        $post_media_link = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $post_media_name;
                        $post_media_thumb = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/' . $post_media_name;
                    }


                    $data = array(
                        'post_id' => $postId,
                        'media_id' => $stote_post_media_id,
                        'media_link' => $post_media_link,
                        'media_thumb_link' => $post_media_thumb,
                        'image_type' =>$post_image_type
                    );
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                    echo json_encode($res_data);
                    exit();
                } else {
                    // publish the post with required info
                    $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                            ->findOneBy(array('id' => $postId));
                    if (!$posts) {
                        return array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
                    }
                    $post_data = $this->getEditPostObject($object_info); //finding the post object.
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $post_data);
                    echo json_encode($res_data);
                    exit();
                }
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $postDetail);
                echo json_encode($res_data);
                exit();
            } else {
                $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
        } else {
            $res_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Finding the post object. update the post and send post object.
     * @param type $post_id
     * @return array $postdata
     */
    public function getEditPostObject($object_data) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $StorePostId = $object_data->post_id;
        $StorePost = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findOneBy(array('id' => $StorePostId));


        $store_id = $object_data->store_id;
        $StorePostTitle = $object_data->post_title;
        $StorePostDesc = $object_data->post_desc;
        $StorePostUserId = $object_data->user_id;

        $StorePost->setStoreId($store_id);
        $StorePost->setStorePostTitle($StorePostTitle);
        $StorePost->setStorePostDesc($StorePostDesc);
        $StorePost->setStorePostAuthor($StorePostUserId);
        $time = new \DateTime("now");
        $StorePost->setStorePostCreated($time);
        $StorePost->setStorePostUpdated($time);
        $StorePost->setStorePostStatus(1);
        $dm->persist($StorePost);
        $dm->flush();

        //publish the post images
        // $imgarray = array();
        // $images = (isset($object_data->media_id) ? $object_data->media_id : $imgarray);

        $media_id = (isset($object_data->media_id) ? $object_data->media_id : '');
        $images = json_decode($media_id);

        if (count($images) > 0) {
            $publish_image = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->publishStorePostImage($images);
        }

        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('id' => $StorePostId));

        $postDetail = array();

        //get user object
        $user_service = $this->get('user_object.service');
        foreach ($posts as $post) {

            $postId = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findBy(array('post_id' => $postId, 'media_status' => 1));
            $mediaData = array();
            foreach ($mediaposts as $mediadata) {
                $mediaId = $mediadata->getId();
                $mediaName = $mediadata->getMediaName();
                $mediatype = $mediadata->getMediaType();
                $isfeatured = $mediadata->getIsFeatured();
                $youtube = $mediadata->getYoutube();
                $post_image_type = $mediadata->getImageType();
                $postId = $post->getId();

                $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $StorePostId . '/' . $mediaName;
                $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $StorePostId . '/' . $mediaName;

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

            //get author object
            $post_auth = $post->getStorePostAuthor();
            $user_info = $user_service->UserObjectService($post_auth);

            $postDetail = array('post_id' => $postId,
                'store_post_title' => $post->getStorePostTitle(),
                'store_post_desc' => $post->getStorePostDesc(),
                'store_post_author' => $post->getStorePostAuthor(),
                'store_post_created' => $post->getStorePostCreated(),
                'media_info' => $mediaData,
                'user_profile' => $user_info
            );
        }
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $postDetail);
    }

    /**
     * deleting the media of post on basis of media_id and post_id basis.
     * @param request object
     * @param json
     */
    public function postDeletepostmediasAction(Request $request) {
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

        $required_parameter = array('post_id', 'media_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $postId = $object_info->post_id;
        $postMediaId = $object_info->media_id;
        // $userId        = $object_info->user_id; 
        $threadDm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $threadRes = $threadDm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                ->find($postMediaId);
        if (!$threadRes) {
            return array('code' => 100, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
        }

        if ($threadRes) {
            /*             * * remove corresponding media**** */
            $threadDm->remove($threadRes);
            $threadDm->flush();
            /*             * *** remove corresponding media from folder also******* */
            $mediaName = $threadRes->getMediaName();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            $mediaFileLocation = $file_location . $this->post_media_path . $postId . '/';
            $mediaToBeDeleted = $mediaFileLocation . $mediaName;
            $mediaThumbLocation = $file_location . '/' . $this->post_media_path_thumb . $postId . '/';
            $thumbToBeDeleted = $mediaThumbLocation . $mediaName;

            // Commenting these line becauase images are not present on s3 Amazon server.
            //Since in push images folder are not used
            if (file_exists($mediaToBeDeleted)) {
                //  unlink($mediaToBeDeleted);
            }
            if (file_exists($thumbToBeDeleted)) { //remove thumb image.
                //  unlink($thumbToBeDeleted);
            }
            $res_data = array('code' => 101, 'message' => 'success', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Get User role for group
     * @param int $store_id
     * @param int $user_id
     * @return int
     */
    public function userStoreRole($store_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object(since store data is stored in ORM
        // so we get datafrom EntityManager()
        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
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
     * Get User role for store
     * @param int $post_id
     * @param int $user_id
     * @return int
     */
    /*
     * We are checking that user is either creater of this post or not
     */
    public function userStoreGuestRole($post_id, $user_id) {
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
     * @param string $post_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $post_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/stores/posts/thumb_crop/" . $post_id . "/";

        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->store_post_thumb_image_width;
        $thumb_height = $this->store_post_thumb_image_height;
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

        $s3imagepath = "uploads/stores/posts/thumb_crop/" . $post_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
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
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/stores/posts/thumb/" . $post_id . "/";
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
        $crop_image_width = $this->store_post_thumb_image_width;
        $crop_image_height = $this->store_post_thumb_image_height;

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

        $s3imagepath = "uploads/stores/posts/thumb/" . $post_id;
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
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
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $post_id) {
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
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

        $s3imagepath = "uploads/stores/posts/original/" . $post_id;
        $image_local_path = $path_to_thumbs_directory . $filename;
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
    public function s3imageUpload($s3imagepath, $image_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }
    
    /**
     * Functionality return Post detail of a store
     * @param json $request
     * @return array
     */
    public function postGetstorepostdetailsAction(Request $request) {
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

        $required_parameter = array('store_id', 'user_id', 'post_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $userId = $object_info->user_id;
        $storeId = $object_info->store_id;
        $post_id = $object_info->post_id;
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $userId));

        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.

        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            return array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
        }


        //for store ACL    
        $do_action = 0;
        $group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        if ($do_action == 0) {
            //for group guest ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.

            $is_store_allow = $store->getIsAllowed();
           
            if ($is_store_allow == 1) {
                $do_action = 1;
            }
        }

        //  if($do_action){
        $totalCount = $comment_count = 0;
        $postDetail = array(); //final array of post data...
        $post_detail = $post_sender_user_ids = $comment_user_ids = $mediaData = $comment_data = $comment_media_result = $postsCount = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        // get posts for a store 
        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1, 'id'=>$post_id));

        //get post count
        if (count($posts) > 0) {
            $totalCount = count($posts);
        }

        //get posts id
        $postsIds = array_map(function($o) {
            return $o->getId();
        }, $posts);

        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($post) {
            return "{$post->getStorePostAuthor()}";
        }, $posts);

        if (count($postsIds)) {
            //get post media from the posts ids
            $post_medias = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->getPostMedia($postsIds);

            //get all coments for the post ids
            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                    ->getPostComments($postsIds);
//            $comments = array_reverse($comments);
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
        if (count($posts) > 0) {
            foreach ($posts as $post) {
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

                        $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
                        $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/' . $mediaName;

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
                                        $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                        $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
                            
                            $c_current_rate = 0;
                            $c_is_rated = false;
                            foreach($comment->getRate() as $rate) {
                                if($rate->getUserId() == $userId ) {
                                    $c_current_rate = $rate->getRate();
                                    $c_is_rated = true;
                                    break;
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
                                'current_user_rate'=>$c_current_rate,
                                'is_rated' => $c_is_rated
                            );
                        }
                        $comment_media_result = array();
                        $comment_count++;
                    }
                }
                $post_auth = $post->getStorePostAuthor();
                $user_info = isset($users_object_array[$post_auth]) ? $users_object_array[$post_auth] : array();
                $current_rate = 0;
                $is_rated = false;
                foreach($post->getRate() as $rate) {
                    if($rate->getUserId() == $userId ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                $postDetail[] = array('post_id' => $postId,
                    'store_post_title' => $post->getStorePostTitle(),
                    'store_post_desc' => $post->getStorePostDesc(),
                    'store_post_author' => $post->getStorePostAuthor(),
                    'store_post_created' => $post->getStorePostCreated(),
                    'link_type' => (int) $post->getLinkType(),
                    'media_info' => $mediaData,
                    'user_profile' => $user_info,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'avg_rate'=>round($post->getAvgRating(), 1),
                    'no_of_votes'=> (int) $post->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'share_type'=>$post->getShareType(),
                    'customer_voting'=>$post->getCustomerVoting(),
                    'store_voting_avg'=>$post->getStoreVotingAvg(),
                    'store_voting_count'=>$post->getStoreVotingCount(),
                    'is_rated' => $is_rated
                );
                $mediaData = array();
                $comment_data = array();
                $comment_count = 0;
            }
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail, 'count' => $totalCount);
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * Functionality return Post lists of a store
     * @param json $request
     * @return array
     */
    public function postListcustomersreviewsAction(Request $request) { 
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

        $required_parameter = array('store_id', 'user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $userId = $object_info->user_id;
        $storeId = $object_info->store_id;
        $limit_start = (isset($object_info->limit_start) ? (int) $object_info->limit_start : 0);
        $limit_size = (isset($object_info->limit_size) ? (int) $object_info->limit_size : 20);
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $userId));

        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
         
        //check for a valid store id
        if(!$store) {
            return array('code' => 413, 'message' => 'INVALID_STORE', 'data' => $data);
        }
        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            return array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
        }


        //for store ACL    
        $do_action = 0;
        $group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');
        if (in_array($group_mask, $allow_group)) {
            $do_action = 1;
        }

        if ($do_action == 0) {
            //for group guest ACL
            $em = $this->getDoctrine()->getManager();
            $store = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.

            $is_store_allow = $store->getIsAllowed();
           
            if ($is_store_allow == 1) {
                $do_action = 1;
            }
        }

        //  if($do_action){
        $totalCount = $comment_count = 0;
        $postDetail = array(); //final array of post data...
        $post_detail = $post_sender_user_ids = $comment_user_ids = $mediaData = $comment_data = $comment_media_result = $postsCount = array();
        //get user object
        $user_service = $this->get('user_object.service');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        // get posts for a store 
        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1,'share_type' => 'TXN'), array('store_post_created' => 'DESC'), $limit_size, $limit_start);
        
        //get post count
        if (count($posts) > 0) {
            $postsCount = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                    ->findBy(array('store_id' => $storeId, 'store_post_status' => 1,'share_type' => 'TXN'));
        }

        //get total count of post
        if ($postsCount) {
            $totalCount = count($postsCount);
        }

        //get posts id
        $postsIds = array_map(function($o) {
            return $o->getId();
        }, $posts);

        //getting the posts sender ids.
        $post_sender_user_ids = array_map(function($post) {
            return "{$post->getStorePostAuthor()}";
        }, $posts);

        if (count($postsIds)) {
            //get post media from the posts ids
            $post_medias = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->getPostMedia($postsIds);

            //get all coments for the post ids
            $comments = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                    ->getPostComments($postsIds);
//            $comments = array_reverse($comments);
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
       
        if (count($posts) > 0) {
            foreach ($posts as $post) {
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

                        $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
                        $thumbDir = $this->getS3BaseUri() . '/' . $this->post_media_path_thumb . $postId . '/' . $mediaName;

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
                                        $comment_media_link = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                        $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
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
                            $current_rate = 0;
                            $is_rated = false;
                            foreach($comment->getRate() as $rate) {
                                if($rate->getUserId() == $userId ) {
                                    $current_rate = $rate->getRate();
                                    $is_rated = true;
                                    break;
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
                                'current_user_rate'=>$current_rate,
                                
                                'is_rated' => $is_rated
                            );
                        }
                        $comment_media_result = array();
                        $comment_count++;
                    }
                }
                
                $current_rate = 0;
                $is_rated = false;
                foreach($post->getRate() as $rate) {
                    if($rate->getUserId() == $userId ) {
                        $current_rate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                
                $post_auth = $post->getStorePostAuthor();
                $user_info = isset($users_object_array[$post_auth]) ? $users_object_array[$post_auth] : array();
                $postDetail[] = array('post_id' => $postId,
                    'store_post_title' => $post->getStorePostTitle(),
                    'store_post_desc' => $post->getStorePostDesc(),
                    'store_post_author' => $post->getStorePostAuthor(),
                    'store_post_created' => $post->getStorePostCreated(),
                    'link_type' => (int) $post->getLinkType(),
                    'media_info' => $mediaData,
                    'user_profile' => $user_info,
                    'comments' => array_reverse($comment_data),
                    'comment_count' => $comment_count,
                    'avg_rate'=>round($post->getAvgRating(), 1),
                    'no_of_votes'=> (int) $post->getVoteCount(),
                    'current_user_rate'=>$current_rate,
                    'share_type'=>$post->getShareType(),
                    'customer_voting'=>$post->getCustomerVoting(),
                    'store_voting_avg'=>$post->getStoreVotingAvg(),
                    'store_voting_count'=>$post->getStoreVotingCount(),
                    'is_rated' => $is_rated
                );
                $mediaData = array();
                $comment_data = array();
                $comment_count = 0;
            }
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail, 'count' => $totalCount);
        echo json_encode($res_data);
        exit();
        /*   } else {
          return array('code'=>100, 'message'=>'Access denied','data'=>$data);
          }
         */
    }

}
