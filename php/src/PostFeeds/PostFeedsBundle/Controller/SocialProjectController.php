<?php

namespace PostFeeds\PostFeedsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Response;
use PostFeeds\PostFeedsBundle\Document\SocialProject;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;
use PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg;
use PostFeeds\PostFeedsBundle\Document\SocialProjectAddress;
use Utility\UtilityBundle\Utils\Response as Resp;
use PostFeeds\PostFeedsBundle\Document\MediaFeeds;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class SocialProjectController extends Controller
{
    
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $resize_image_width = 200;
    protected $resize_image_height = 200;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    protected $max_media_upload = 10;
    protected $resize_cover_image_width = 910;
    protected $resize_cover_image_height = 410;
    protected $limit_start = 0;
    protected $limit_size = 20;
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService(){
        return $this->container->get('store_manager_store.storeUtility');
    }
    
    private function _getDocumentManager(){
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }
    
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    /**
     * 
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds');
    }
    
    /**
     * Create social project
     * @param Request $request
     */
    
    
    public function postCreateSocialProjectAction(Request $request)
    {
        
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        
        $requiredParams = array('project_title','project_desc');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $time = new \DateTime("now");
        $project = array(
                    'user_id'=> (int)$data['user_id'],
                    'project_title'=> isset($data['project_title']) ? $data['project_title'] : '',
                    'project_desc'=> isset($data['project_desc']) ? $data['project_desc'] : '',
                    'location'=> isset($data['project_loc']) ? $data['project_loc'] : '',
                    'latitude'=> isset($data['latitude']) ? $data['latitude'] : '',
                    'longitude'=> isset($data['longitude']) ? $data['longitude'] : '',
                    'website'=> isset($data['website']) ? $data['website'] : '',
                    'email'=> isset($data['email']) ? $data['email'] : '',
                    'city'=> isset($data['project_city']) ? $data['project_city'] : '',
                    'country'=> isset($data['project_country']) ? $data['project_country'] : '',
                    'x'=> isset($data['x']) ? $data['x'] : '',
                    'y'=> isset($data['y']) ? $data['y'] : '',
                    'gallery_medias'=>isset($data['gallery_medias']) ? (is_array($data['gallery_medias']) ? $data['gallery_medias'] : (array)$data['gallery_medias']) : array(),
                    'cover_medias'=>isset($data['cover_medias'])? (is_array($data['cover_medias']) ? $data['cover_medias'] : (array)$data['cover_medias']) : array()
                    
                );
        try{
            $social = new SocialProject();
            $social->setTitle($project['project_title']);
            $social->setDescription($project['project_desc']);
            $social->setOwnerId($project['user_id']);
            $social->setStatus(0);
            $social->setIsDelete(0);
            $social->setWeWant(0);
            $social->setCreatedAt($time);
            $social->setUpdatedAt($time);
            $social->setEmail($project['email']);
            $social->setWebsite($project['website']);

            // save address in embedded document
            $project_address = new SocialProjectAddress();
            $project_address->setCity($project['city']);
            $project_address->setCountry($project['country']);
            $project_address->setLocation($project['location']);
            $project_address->setLatitude($project['latitude']);
            $project_address->setLongitude($project['longitude']);
            $social->addAddress($project_address);
      
            $cover_ids = $project['cover_medias'];
            //set cover image
            $cover_image_info = $this->setCoverImage($cover_ids, $project['x'], $project['y']);
            $social->addCoverImg($cover_image_info);
            
            //gallery image uploading
            $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service    
            $gallery_medias = $project['gallery_medias'];
            foreach($gallery_medias as $gallery_media){
                //$media_id = $gallery_media['id'];
                $feed_media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                                                ->find(array('id' =>$gallery_media));     
                $social->addMedia($feed_media);
            }
            
            // save project
            $social->setStatus(1);
            $dm->persist($social); //storing the address data.
            $dm->flush();
            
            //fetch address data    
            $address = $social->getAddress(); 
            $address_data = $this->getAddress($address);
            
            // get cover image data
            $cover_img_data = $social->getCoverImg();
            $cover_data = $this->getCoverImageinfo($cover_img_data);

            //get gallery media information
            $medias = $social->getMedias();
            $gallery_info = $service_obj->getGalleryMedia($medias);
            
            
            } catch(Exception $e){
            $code = 100;
            $result =   array(
                            'error_code'=>$e->getCode(),
                            'error_message'=>$e->getMessage()
                        );
            Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $result));
        }
        $code = 101;
        $result =   array(
                            'project_id'=>$social->getId(),
                            'project_title'=>$social->getTitle(),
                            'email' =>$social->getEmail(),
                            'website' =>$social->getWebsite(),
                            'project_owner'=>$social->getOwnerId(),
                            'project_desc'=>$social->getDescription(),
                            'address'=>$address_data,
                            'cover_img'=>$cover_data,
                            'gallery_info' => $gallery_info,
                            'created_on'=>$social->getCreatedAt()
                            
                        );
        Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $result));
    }
    
    
    /**
     * Listing on projects
     * @param Request $request
     */
    public function postListSocialProjectAction(Request $request){
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $requiredParams = array('user_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_service = $this->get('user_object.service');
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $project = array(
                        'user_id'=> (int)$data['user_id'],
                        'limit'=> isset($data['limit_size']) ? $data['limit_size'] : 20,
                        'offset'=> isset($data['limit_start']) ? $data['limit_start'] : 0
                       );
        $user_id = $project['user_id'];
        $limit_size = $project['offset'];
        $limit_start = $project['limit'];
        $total_projects = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:SocialProject')
                                                  ->findBy(array('owner_id'=>$user_id));
        $project_count = count($total_projects);
        $projects = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:SocialProject')
                                                  ->getProjects($user_id,$limit_size, $limit_start);
        $project_data = array();
        $feed_service = $this->getPostFeedsService();
        $project_data = $feed_service->getProjectObj($projects);
        
        // check if we_want by current user
        try{
            $projectIds = array_map(function($proj){
                return $proj['project_id'];
            }, $project_data);
            $weWantedDetail = !empty($projectIds) ? 
                    $dm->getRepository('VotesVotesBundle:Votes')->isVotedForManyItems($user_id, 'citizen', $projectIds, 'social_project')
                    : array();
            foreach ($project_data as &$pdata){
                $pdata['we_wanted'] = isset($weWantedDetail[$pdata['project_id']]) ? $weWantedDetail[$pdata['project_id']] : 0;
            }
        } catch(\Exception $e){
            
        }
        // end setting we_wanted parameter for project
        
        $code = 101;
        $project_info = array('project'=> $project_data,'size'=>$project_count );
        Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $project_info));
    }
    
    public function uploadMedia($images)
    {
        $post_images = $images;
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $resp = $service_obj->uploadMedia($post_images); //call media upload
        return $resp;
    }
    
    public function getAddress($address){
        $address_id = $address[0]->getId();
            if($address_id){
                    $address_data = array(
                                   'location'=>$address[0]->getLocation(),
                                   'city'=>$address[0]->getCity(),
                                   'country'=>$address[0]->getCountry(),
                                   'longitude'=>(double)$address[0]->getLongitude(),
                                   'latitude'=>(double)$address[0]->getLatitude()
                                );
                    return $address_data;
            }
    }
    
    /**
     * 
     * @param Request $request
     */
    public function postUploadCoverMediasAction(Request $request)
    {
        
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $service_obj->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias]', array());
        $data = array();
        $required_parameter = array('user_id','type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $store_utility->getDeSerializeDataFromRequest($request);
        $type = $data['type'];
        $user_id = (int)$data['user_id'];
        $project_id = isset($data['project_id']) ? $data['project_id'] : '';
        $xC="";
        $yC="";

        //check for images upload
        if (isset($_FILES['social_media'])) {
            $images = $_FILES['social_media'];
            $file_error = $service_obj->checkFileTypeAction($images); //checking the file type extension.
            if ($file_error) {
                 $resp_data = new Resp(Msg::getMessage(301)->getCode(), Msg::getMessage(301)->getMessage(), $data); //YOU_MUST_CHOOSE_AN_IMAGE
                 $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string)$resp_data);
                 Utility::createResponse($resp_data);
            }
        }
        $post_images = $_FILES['social_media'];
        
        $resp = $service_obj->uploadOtherMedia($post_images, $user_id, null, $type); //call media upload
        try{
            if(!empty($project_id) and $type=="cover"){
                $dm = $this->_getDocumentManager();
                $social = $dm
                        ->getRepository('PostFeedsBundle:SocialProject')
                        ->findOneBy(array('id'=>$project_id ,'status'=>1,'owner_id' =>$user_id,'is_delete' =>0 ));
                if($social){
                    $cover_image_info = $this->setCoverImage(array($resp[0]['id']), $xC, $yC);
                    $social->addCoverImg($cover_image_info);
                    $dm->persist($social);
                    $dm->flush();
                }
            }
        }  catch (\Exception $e){
            
        }
        
        
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $resp); //SUCCESS
        $service_obj->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsController] and function [uploadMedias] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
    public function setCoverImage($media_ids,$x,$y){
        $media_ids = is_array($media_ids) ? $media_ids : array();
        $dm = $this->_getDocumentManager();
        $cover_image = new SocialProjectCoverImg();
        //$social = new SocialProject();
        foreach($media_ids as $media_id){
                $cover_image = new SocialProjectCoverImg();
                $feed_media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                                                ->find(array('id' =>$media_id));
                if($feed_media){
                    $cover_image->addImageName($feed_media);
                }
                $cover_image->setX($x); 
                $cover_image->setY($y);
        }
        return $cover_image;  
    }
    
    /**
     * 
     * @param type $medias
     * @return string
     */
    public function getCoverImageinfo($medias){
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        $media_data = array();
        foreach($medias as $media ){
            try{
                $media_name =  $media->getImageName();
                $x = $media->getX();
                $y = $media->getY();
                $cover_id=null;
                foreach($media_name as $res){
                    $cover_id = $res->getId();
                }
                $feed_media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                                                ->findBy(array('id' =>$cover_id));
                $cover_name = '';
                $id = '';
                foreach ($feed_media as $media){
                   $id = $media->getId();
                   $cover_name = $media->getMediaName();
                }
               $ori_image = $aws_path . $this->container->getParameter('media_path') .$cover_name;
               $thumb_image = $aws_path . $this->container->getParameter('social_project_cover_path') .$cover_name;
               $media_data  = array(
                                     'cover_id' =>$id,
                                     'ori_image' =>$ori_image,
                                     'thum_image'=>$thumb_image,
                                     'x_cord'=>$x,
                                     'y_cord'=>$y
                                   );
            }catch(\Exception $e){
                
            }
        }   
         return $media_data;
    }
    
    public function postViewSocialProjectAction(Request $request){
        
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');
        $feed_service = $this->getPostFeedsService();
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $requiredParams = array('user_id','project_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $user_id = (int)$data['user_id'];
        $project_id = $data['project_id'];
        $projects = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:SocialProject')
                                                ->findBy(array('id'=>$project_id ,'status'=>1,'is_delete' =>0));
        if(!$projects){
            Utility::createResponse(new Response(Msg::getMessage(1104)->getCode(), Msg::getMessage(1104)->getMessage(), array()));
        }
        foreach ($projects as $project){
            $projectId = $project->getID(); 
            if($projectId ==$project_id)
            {
                $title = $project->getTitle();
                $desc = $project->getDescription();
                $address = $project->getAddress() ? $project->getAddress() : array();
                $addres_data = $this->getAddress($address);
                $cover_data = $project->getCoverImg() ? $project->getCoverImg() : array();
                $cover_info = $this->getCoverImageinfo($cover_data);
                $medias = $project->getMedias() ? $project->getMedias() : array();
                $gallery_info = $service_obj->getGalleryMedia($medias);
                $owner_id = $project->getOwnerId();
                $owner_info = $user_service->UserObjectService($owner_id);
                $we_want = $project->getWeWant();
            } 
        }
        $code = 101;
        $isWeWanted = $dm->getRepository('VotesVotesBundle:Votes')->isVoted($user_id, 'citizen', $project_id, 'social_project');
        $result = array(
                            'project_id'=>$project_id,
                            'project_title'=>$title,
                            'email' =>$project->getEmail(),
                            'website' =>$project->getWebsite(),
                            'project_desc'=>$desc,
                            'created_on'=>$project->getCreatedAt(),
                            'project_owner'=>$owner_info,
                            'we_want_count'=>$we_want,
                            'address'=>$addres_data,
                            'cover_img'=>$cover_info,
                            'gallery_info' => $gallery_info,
                            'we_wanted'=>$isWeWanted
                        );
        Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $result));
    }
    
    /**
     * 
     */
   public function postSearchSocialProjectAction(Request $request){
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');
        $feed_service = $this->getPostFeedsService();
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service
        $requiredParams = array('user_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $project = array(
                        'user_id'=> (int)$data['user_id'],
                        'owner_id'=> (int)(isset($data['owner_id'])) ? (int)$data['owner_id'] : '',
                        'limit'=> (int)(isset($data['limit_size'])) ? (int)$data['limit_size'] : (int)$this->limit_size,
                        'offset'=> (int)(isset($data['limit_start'])) ? (int)$data['limit_start'] :(int) $this->limit_start,
                        'text'=> isset($data['text']) ? $data['text'] : '',
                        'project_country'=> isset($data['project_country']) ? $data['project_country'] : '',
                        'project_city'=> isset($data['project_city']) ? $data['project_city'] : '',
                        'sort_type'=> (int)(isset($data['sort_type'])) ? $data['sort_type'] : 1,
                       );
        
        $user_id = $project['user_id'];
        $owner_id = $project['owner_id'];
        $limit_start = $project['offset'];
        $limit_size = $project['limit'];
        $search_text = $project['text'];
        $country = $project['project_country'];
        $city = $project['project_city'];
        $sort_type = $project['sort_type'];
         // listing by searched key with latest created project
        $projects = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:SocialProject')
                                                  ->getSearchedProject($owner_id, $search_text, $limit_start, $limit_size, $country, $city ,$sort_type);
        
        $project_data = array();
        $project_data = $feed_service->getProjectObj($projects);
        
        // check if we_want by current user
        try{
            $projectIds = array_map(function($proj){
                return $proj['project_id'];
            }, $project_data);
            $weWantedDetail = !empty($projectIds) ? 
                    $dm->getRepository('VotesVotesBundle:Votes')->isVotedForManyItems($user_id, 'citizen', $projectIds, 'social_project')
                    : array();
            foreach ($project_data as &$pdata){
                $pdata['we_wanted'] = isset($weWantedDetail[$pdata['project_id']]) ? $weWantedDetail[$pdata['project_id']] : 0;
            }
        } catch(\Exception $e){
            
        }
        // end setting we_wanted parameter for project
        
        $projects_count = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:SocialProject')
                                                  ->getSearchedProjectCount($search_text,$country, $city, $owner_id);
        $code = 101;
        $project_info = array('project'=> $project_data ,'size' => $projects_count);
        Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $project_info));
   }
   
   /**
    * Delete media of social project at the time of uploading
    * @param Request $request
    */
    public function postDeleteSocialMediaAction(Request $request){
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $requiredParams = array('media_id','user_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $media_id = $data['media_id'];
        $result = array();
        $media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                       ->find(array('id' => $media_id));
        if (!$media) {
            Utility::createResponse(new Response(Msg::getMessage(1102)->getCode(), Msg::getMessage(1102)->getMessage(), array()));
        }
        if ($media) {
                $dm->remove($media);
                $dm->flush();
                $code = 101;
                Utility::createResponse(new Response(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $result));
            }
    }
   
    public function postSetProjectCoverCoordinatesAction(Request $request){
       $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $requiredParams = array('session_id', 'project_id', 'media_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $data['x'] = key_exists('x', $data) ? (string)$data['x'] : '';
        $data['y'] = key_exists('y', $data) ? (string)$data['y'] : '';
        
        $project = $dm->getRepository('PostFeedsBundle:SocialProject')
                ->findOneBy(array('id'=>$data['project_id'] ,'status'=>1));
        if(!$project){
            Utility::createResponse(new Response(Msg::getMessage(1104)->getCode(), Msg::getMessage(1104)->getMessage(), array()));
        }
        try{
            $cover_id=$data['media_id'];		
            $coverImages = $this->setCoverImage(array($cover_id), $data['x'], $data['y']);
            $project->addCoverImg($coverImages);
            $dm->persist($project);
            $data['x'] = $coverImages->getX();
            $data['y'] = $coverImages->getY();
        } catch(\Exception $e){
               
        }
        $dm->flush();
        
        
        $response = array(
            'media_id'=>$data['media_id'],
            'x_cord'=>$data['x'],
            'y_cord'=>$data['y']
        );
        
        Utility::createResponse(new Response(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $response));
    }
   
    public function postEditSocialProjectAction(Request $request)
    {
        $utilityService = $this->getUtilityService();
        $dm = $this->_getDocumentManager();
        $user_service = $this->get('user_object.service');
       
        $requiredParams = array('user_id','project_id', 'project_title', 'project_desc');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            Utility::createResponse(new Response(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage(), array()));
        }
        
        $requestData = $utilityService->getDeSerializeDataFromRequest($request);
        
        $data = array(
                    'user_id'=> (int)$requestData['user_id'],
                    'project_id'=> $requestData['project_id'],
                    'project_title'=> isset($requestData['project_title']) ? $requestData['project_title'] : '',
                    'project_desc'=> isset($requestData['project_desc']) ? $requestData['project_desc'] : '',
                    'project_loc'=> isset($requestData['project_loc']) ? $requestData['project_loc'] : '',
                    'latitude'=> isset($requestData['latitude']) ? $requestData['latitude'] : '',
                    'longitude'=> isset($requestData['longitude']) ? $requestData['longitude'] : '',
                    'website'=> isset($requestData['website']) ? $requestData['website'] : '',
                    'email'=> isset($requestData['email']) ? $requestData['email'] : '',
                    'project_city'=> isset($requestData['project_city']) ? $requestData['project_city'] : '',
                    'project_country'=> isset($requestData['project_country']) ? $requestData['project_country'] : '',
                    'x'=> isset($requestData['x']) ? $requestData['x'] : '',
                    'y'=> isset($requestData['y']) ? $requestData['y'] : '',
                    'gallery_medias'=>isset($requestData['gallery_medias']) ? (is_array($requestData['gallery_medias']) ? $requestData['gallery_medias'] : (array)$requestData['gallery_medias']) : array(),
                    'cover_medias'=>isset($requestData['cover_medias'])? (is_array($requestData['cover_medias']) ? $requestData['cover_medias'] : (array)$requestData['cover_medias']) : array()
                );
        
        $user_id = (int)$data['user_id'];
        $project_id = $data['project_id'];
        
        $project = $dm->getRepository('PostFeedsBundle:SocialProject')
                ->findOneBy(array('id'=>$project_id ,'status'=>1));
        if(!$project){
            Utility::createResponse(new Response(Msg::getMessage(1104)->getCode(), Msg::getMessage(1104)->getMessage(), array()));
        }
        //user id project owner or not
        $sp_owner_id = (int)$project->getOwnerId();
        if($user_id != $sp_owner_id){
                Utility::createResponse(new Response(Msg::getMessage(1131)->getCode(), Msg::getMessage(1131)->getMessage(), array()));
        }
        try{
            
            $project->setTitle($data['project_title']);
            $project->setDescription($data['project_desc']);
            if(key_exists('website', $requestData)){
                $project->setWebsite($data['website']);
            }
            
            if(key_exists('email', $requestData)){
                $project->setEmail($data['email']);
            }
            
             // save address in embedded document
            $project_address = $project->getAddress();
            foreach($project_address as $address){
                if(key_exists('project_city', $requestData)){
                    $address->setCity($data['project_city']);
                }
                if(key_exists('project_country', $requestData)){
                    $address->setCountry($data['project_country']);
                }
                if(key_exists('project_loc', $requestData)){
                    $address->setLocation($data['project_loc']);
                }
                if(key_exists('latitude', $requestData)){
                    $address->setLatitude($data['latitude']);
                }
                if(key_exists('longitude', $requestData)){
                    $address->setLongitude($data['longitude']);
                }
                
                break;
            }
            
            if(!empty($data['gallery_medias'])){
                $gallery_medias = is_array($data['gallery_medias']) ? $data['gallery_medias'] : (array)$data['gallery_medias'];
                $existingMedia = $project->getMedias();
                $g_media_count = count($existingMedia)+ count($gallery_medias);
                if($g_media_count < 1 or $g_media_count > 10) {
                  Utility::createResponse(new Response(Msg::getMessage(1121)->getCode(), Msg::getMessage(1121)->getMessage(), array()));  
                }
                $existingMediaIds = array();
                foreach($existingMedia as $eMedia){
                    array_push($existingMediaIds, $eMedia->getId());
                }
                
                foreach($gallery_medias as $gallery_media){
                    if(in_array($gallery_media, $existingMediaIds)){
                        continue;
                    }
                    $feed_media = $this->get('doctrine_mongodb')->getRepository('PostFeedsBundle:MediaFeeds')
                                                    ->find(array('id' =>$gallery_media));     
                    if($feed_media){
                        $project->addMedia($feed_media);
                    }
                }
            }
            
            if(!empty($data['cover_medias'])){
                $cover_image_info = $this->setCoverImage($data['cover_medias'], $data['x'], $data['y']);
                $coverMedias = $project->getCoverImg();
                foreach($coverMedias as $media ){
                    $project->removeCoverImg($media);
                }
                $project->addCoverImg($cover_image_info);
            }
            
            $dm->persist($project);
            
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }        
        $dm->flush();
        
        $service_obj = $this->container->get('post_feeds.MediaFeeds'); //call media feed service    
        $address = $project->getAddress(); 
        $address_data = $this->getAddress($address);
        $cover_img_data = $project->getCoverImg();
        $cover_data = $this->getCoverImageinfo($cover_img_data);
        $medias = $project->getMedias();
        $gallery_info = $service_obj->getGalleryMedia($medias);
        $response =   array(
                'project_id'=>$project->getId(),
                'project_title'=>$project->getTitle(),
                'email' =>$project->getEmail(),
                'website' =>$project->getWebsite(),
                'project_owner'=>$project->getOwnerId(),
                'project_desc'=>$project->getDescription(),
                'address'=>$address_data,
                'cover_img'=>$cover_data,
                'gallery_info' => $gallery_info,
                'created_on'=>$project->getCreatedAt()

            );        
        Utility::createResponse(new Response(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $response));
    }
}
