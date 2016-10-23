<?php
namespace PostFeeds\PostFeedsBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\MediaFeeds;
use Utility\UtilityBundle\Utils\Utility;

class MediaFeedsService {

    protected $em;
    protected $dm;
    protected $container;
    CONST dashboard_post_thumb_image_width = 654;
    CONST dashboard_post_thumb_image_height = 360;
    CONST resize_cover_image_width = 910;
    CONST resize_cover_image_height = 410;
    CONST COMMENT_SIZE = 5;
   
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    /**
     * Upload media
     * @param array $images
     */
    public function uploadMedia($images, $item_id=null, $user_id) 
    {
        $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] With image array:'.Utility::encodeData($images), array());
        $clean_name = $this->container->get('clean_name_object.service');
        $image_upload = $this->container->get('amazan_upload_object.service');
        //call service to get image type. Basis of this we save data 3,2,1 in db
        $image_type_service = $this->container->get('user_object.service');
        $time = new \DateTime("now");
        $dm = $this->dm;
        if (!isset($images)) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] with message: No image found.', array());
            return false; //no images exist
        }
        $media = array();
        foreach ($images['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $images['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $images['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $post_thumb_image_width = self::dashboard_post_thumb_image_width;
                    $post_thumb_image_height = self::dashboard_post_thumb_image_height;

                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                        $file_tmp = $images['tmp_name'][$key];
                        $file_type = $images['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];

                        //find media information 
                        $image_info = getimagesize($images['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1];
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $post_thumb_image_width, $post_thumb_image_height);
                        $media_obj = new MediaFeeds();
                        if (!$key){ //consider first image the featured image.
                            $media_obj->setIsFeatured(1);
                        }
                        else{
                             $media_obj->setIsFeatured(0);
                        }
                        $media_obj->setItemId($item_id);
                        $media_obj->setMediaName($file_name);
                        $media_obj->setType($image_type);
                        $media_obj->setCreatedAt($time);
                        $media_obj->setUpdatedAt($time);
                        $media_obj->setMediaType($actual_media_type);
                        $media_obj->setStatus(1);
                        $media_obj->setUserId($user_id);
                        try{
                            $dm->persist($media_obj);
                            $dm->flush();
                        } catch (\Exception $ex) {
                             $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] with exception :'.$ex->getMessage(), array());
                             return false;
                        }
                        $media_id = $media_obj->getId(); //get the dashboard media id
                       $image_path = $image_upload->imageAllUploadService($images, $key, $file_name);//calling service method for image uploading
                    }
                    $media[] = array('id'=>$media_id, 'url'=>$image_path);
                }
                $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia]:Media_array: '. Utility::encodeData($media), array());
                return $media;
    }

    /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLog($monolog_req, $monolog_response = array())
    {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.media_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);  
        return true;
    }
    
    /**
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    public function checkFileTypeAction($images) 
    {
        $file_error = 0;
        foreach ($images['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($images['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($images['type'][$key] == 'image/jpg' || $images['type'][$key] == 'image/jpeg' ||
                        $images['type'][$key] == 'image/gif' || $images['type'][$key] == 'image/png'))) || (preg_match('/^.*\.(mp3|mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }else{
                 return 1; //return true
            }
        }
        return $file_error;
    }
    
    /**
     * get refrencial data
     * @param type $medias
     * @return string
     */
    public function getGalleryMedia($medias){
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        $media_data = array();
        foreach($medias as $media ){
            try{
               $media_id = $media->getID();
               $media_name =  $media->getMediaName();
               $type       =  $media->getType();
               $ori_image = $aws_path . $this->container->getParameter('media_path') .$media_name;
               $thumb_image = $aws_path . $this->container->getParameter('media_path_thumb') .$media_name;
               $media_data[]  = array(
                                     'media_id' =>$media_id,
                                     'ori_image' =>$ori_image,
                                     'thum_image'=>$thumb_image,
                                     'type' =>$type
                                   );
            }  catch (\Exception $e){
                
            }
        }   
         return $media_data;
        
    }
    
    /**
     * Upload media
     * @param array $images
     */
    public function uploadOtherMedia($images, $user_id, $item_id=null,$type = null) 
    {
        $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] With image array:'.Utility::encodeData($images), array());
        $clean_name = $this->container->get('clean_name_object.service');
        $image_upload = $this->container->get('amazan_upload_object.service');
        //call service to get image type. Basis of this we save data 3,2,1 in db
        $image_type_service = $this->container->get('user_object.service');
        $time = new \DateTime("now");
        $dm = $this->dm;
        if (!isset($images)) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] with message: No image found.', array());
            return false; //no images exist
        }
        $media = array();
        foreach ($images['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $images['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $images['name'][$key]));
                    $file_name = $clean_name->cleanString($file_name);
                    $post_thumb_image_width = self::resize_cover_image_width;
                    $post_thumb_image_height = self::resize_cover_image_height;

                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                        $file_tmp = $images['tmp_name'][$key];
                        $file_type = $images['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];

                        //find media information 
                        $image_info = getimagesize($images['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1];
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $post_thumb_image_width, $post_thumb_image_height);
                        $media_obj = new MediaFeeds();
                        if (!$key){ //consider first image the featured image.
                            $media_obj->setIsFeatured(1);
                        }
                        else{
                             $media_obj->setIsFeatured(0);
                        }
                        $media_obj->setItemId($item_id);
                        $media_obj->setMediaName($file_name);
                        $media_obj->setType($image_type);
                        $media_obj->setCreatedAt($time);
                        $media_obj->setUpdatedAt($time);
                        $media_obj->setMediaType($actual_media_type);
                        $media_obj->setStatus(1);
                        $media_obj->setUserId($user_id);
                        try{
                            $dm->persist($media_obj);
                            $dm->flush();
                        } catch (\Exception $ex) {
                             $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia] with exception :'.$ex->getMessage(), array());
                             return false;
                        }
                        $media_id = $media_obj->getId(); //get the dashboard media id
                       $image_path = $image_upload->coverAlbumUploadService($images, $key, $file_name,$type);//calling service method for image uploading
                    }
                    $media[] = array('id'=>$media_id, 'url'=>$image_path);
                }
                $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [uploadMedia]:Media_array: '. Utility::encodeData($media), array());
                return $media;
    }
    
    /**
     * Get media object
     * @param type $media_id
     * @return boolean
     */
    public function getMediaObject($media_id)
    {
         $dm = $this->dm;
         $media_obj = $dm
                    ->getRepository('PostFeedsBundle:MediaFeeds')
                    ->findOneBy(array('id' => $media_id));
        if (count($media_obj) == 0) {
            return false;
        }
        return $media_obj;
         
    }
    
    /**
     * 
     * @param type $media_obj
     */
    public function getMediaComment($media_obj)
    {
        $commnet_obj = array();
        $post_objs = $media_obj->getPost();
        if(!$post_objs){
            return $commnet_obj; //no comment found
        }
        //get comment
        foreach($post_objs as $post_obj){
        $commnet_obj = $post_obj->getComments();
        }
        return $commnet_obj;
    }
    
    /**
     * Get media tagged users
     * @param type $media_obj
     */
    public function getMediaTaggedInfo($media_obj)
    {
        $post_feeds_service = $this->container->get('post_feeds.PostFeeds'); //call media feed service
        $response = $post_feeds_service->getCommentEntityObject($media_obj);
        return $response;
    }
    
     /**
     * Get media tagged users
     * @param type $media_obj
     */
    public function getCommentTaggedInfo($comment_obj)
    {
        $post_feeds_service = $this->container->get('post_feeds.PostFeeds'); //call media feed service
        $response = $post_feeds_service->getCommentEntityObject($comment_obj);
        return $response;
        
    }
    
    /**
     * Preapare media data in array
     * @param type $media_rated_users
     * @param type $media_tag_obj
     * @param type $media_obj
     * @param type $comments_array
     */
    public function prepareMediaData($media_rated_users, $media_tag_obj, $media_obj, $comments_array, $count)
    {
        $media_objs = array($media_obj);
        $user_data = array();
        $media_image = $this->getGalleryMedia($media_objs);
        $media_owner_id = $media_obj->getUserId();
        //get user Object
        $user_service = $this->container->get('user_object.service');
        if($media_owner_id != ''){
        $user_data = $user_service->UserObjectService($media_owner_id);
        }
        $media_final_obj = array(
            'image_owner'=>$user_data,
            'is_rated'=> $media_rated_users['is_rated'],
            'vote_count' => $media_obj->getVoteCount(),
            'vote_sum' => $media_obj->getVoteSum(),
            'avg_rating' => $media_obj->getAvgRating(),
            'current_user_rate' => $media_rated_users['current_user_rate'],
            'media' => $media_image[0],
            'user_tag' => $media_tag_obj['user'],
            'shop_tag' => $media_tag_obj['shop'],
            'club_tag' => $media_tag_obj['club'],
            'rated_users' => $media_rated_users['rated_users'],
            'comments' => $comments_array,
            'comment_count' => $count
        );
        return $media_final_obj;
    }
    
    /**
     * Get Comment by size
     * @param type $media_comments
     * @param type $user_objects
     * @param type $shop_objects
     * @param type $club_objects
     */
    public function getCommentsBySize($comments, $user_objects, $shop_objects, $club_objects, $user_id) 
    {
        $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [getCommentsBySize]', array());
        $postFeedsService = $this->container->get('post_feeds.postFeeds');
        $comments_array = array();
        $media_comments_array = array();
        $media_comments = array();
        foreach ($comments as $media_comment) {
            $media_comments_array[] = $media_comment;
        }
        $media_comments = array_reverse($media_comments_array);
        $count = 0;
        foreach ($media_comments as $media_comment) {
            if ($count < self::COMMENT_SIZE) {
                $comments_array[] = $postFeedsService->getSingleCommentObject($media_comment, $user_id, $user_objects, $shop_objects, $club_objects); //get media comments
                $count = $count + 1;
            } else {
                break;
            }
        }
        $comments_array = array_reverse($comments_array);
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Services\MediaFeedsService] and function [getCommentsBySize] with comments array:'.Utility::encodeData($comments_array), array());
        return $comments_array;
    }
    
    /**
     * Delete Media
     * @param type $post_obj
     * @param type $media_obj
     * @param type $de_serialize
     */
    public function deleteMedia($post_obj, $media_obj, $de_serialize)
    {
        $dm = $this->dm;
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\MediaFeedsService] and function [deleteMedia] ', array());
        try {
            $post_obj->removeMedia($media_obj);
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
            //get remain media count
            $available_media = $post_obj->getMedia();
            if(count($available_media) == 0){
                $this->markPostMedia($post_obj, 0); //mark post as no media
            }
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\MediaFeedsService] and function [deleteMedia] with SUCCESS');
            return $media_obj;
        } catch (\Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\MediaFeedsService] and function [deleteMedia] with exception :' . $ex->getMessage(), array());
            return false;
        }
    }
    
    /**
     * Mark post is_comment status
     * @param string $post_id
     * @param int $status
     * @return boolean
     */
    public function markPostMedia($post_obj, $status) 
    {
        $dm = $this->dm;
        //$postFeedsService = $this->container->get('post_feeds.postFeeds');
        $//post_obj = $postFeedsService->getPostObject($post_id);
        $post_obj->setIsMedia($status);
        try {
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\MediaFeedsService] and function [markPostMedia] with exception :' . $ex->getMessage(), array());
        }
        return true;
    }
    
    /**
     * Delete origin media. Only image owner can delete media
     * @param int $media_id
     * @return boolean
     */
    public function deleteMediaObject($media_id, $user_id)
    {
        $dm = $this->dm;
        $media_obj = $dm
                    ->getRepository('PostFeedsBundle:MediaFeeds')
                    ->findOneBy(array('id' => $media_id, 'user_id' => $user_id));
        if (count($media_obj) == 0) {
            return false;
        }
        $dm->remove($media_obj);
        $dm->flush();
        $this->deleteMediaReferenceFromSocialProject($media_obj->getId());
        return $media_obj;
    }
    
    /**
     * Get Social Project details usin media_id
     * @param type $mediaId
     * @return object
     */
    public function getProjectByMediaId($mediaId){
        try{
        $projectData = $this->dm->getRepository('PostFeedsBundle:SocialProject')->findOneBy(array('medias.$id'=> new \MongoId($mediaId),'is_delete'=> 0));
        }catch(\Exception $e){
            $projectData = array();
        }
        return $projectData;
    }
    
    
    /**
     * Get Social Project details usin media_id
     * @param type $mediaId
     * @return object
     */
    public function getProjectExistsByMediaId($mediaId){
        try{
        $projectData = $this->dm->getRepository('PostFeedsBundle:SocialProject')->findOneBy(array('medias.$id'=> new \MongoId($mediaId)));
        }catch(\Exception $e){
            $projectData = array();
        }
        return $projectData;
    }
    
    /**
     * Delete Media Reference from SocialProject usin media_id
     * @param type $mediaId
     * @return type
     */
    public function deleteMediaReferenceFromSocialProject($mediaId){
        $sproject = $this->getProjectByMediaId($mediaId);
        if(!$sproject){
            return;
        }
        try{
        $medias = $sproject->getMedias();
            foreach($medias as $media){
                if($mediaId==$media->getId()){
                    $sproject->removeMedia($media);
                    break;
                }
            }
            $this->dm->persist($sproject);
            $this->dm->flush();
        }catch(\Exception $e){
            
        }
        
        return;
    }
}