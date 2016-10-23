<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UserManager\Sonata\UserBundle\Document\Group;
use UserManager\Sonata\UserBundle\Document\GroupMedia;
use UserManager\Sonata\UserBundle\Document\GroupAlbum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

class RestGroupAlbumV1Controller extends Controller
{
    
    // album path
    protected $group_media_album_path_thumb = '/uploads/groups/thumb/';
    protected $group_media_album_path = '/uploads/groups/original/';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $resize_image_width = 200;
    protected $resize_image_height = 200;
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
     * encode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->encode($req_obj, 'json');
        return $jsonContent;
    }
    
     /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        // return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
        
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
     * 
     * @return int $file_error
     */
    private function checkFileTypeAction() {
       
        $file_error = 0;
        foreach ($_FILES['group_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['group_media']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.
                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['group_media']['type'][$key] == 'image/jpeg'   || 
                         $_FILES['group_media']['type'][$key] == 'image/jpg'    || 
                         $_FILES['group_media']['type'][$key] == 'image/gif'    || 
                         $_FILES['group_media']['type'][$key] == 'image/png'))) ||
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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     * @param string $album_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $group_id,$album_id) {  
        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/".$album_id."/";
        //$path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	//$final_width_of_image = 200;
        //image thumb size
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_width;
        
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
        
        
        //$ox = imagesx($im);  
        //$oy = imagesy($im);  
        //$nx = $final_width_of_image;  
        //$ny = floor($oy * ($final_width_of_image / $ox));  
        $nm = imagecreatetruecolor($nx, $ny);  
        imagecopyresampled($nm, $im, 0,0,0,0,$nx,$ny,$ox,$oy);  
        if(!file_exists($path_to_thumbs_directory)) {  
          if(!mkdir($path_to_thumbs_directory, 0777, true)) {  
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
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $group_id, $album_id) {
        
        //image crop size
        $crop_image_width = $this->crop_image_width;
        $crop_image_height = $this->crop_image_width;
  
        $imagename = $filename;
        //get the thumb_crop directory path
        $path_to_thumbs_crop_directory = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/".$album_id."/";
        $filename = $path_to_thumbs_crop_directory.$filename;
        
        //get the thumb directory path
        $path_to_thumbs_center_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/".$album_id."/";
        
        //get the image center 
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory . '/'.$imagename;
        
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
                die("There was a problem. Please try again!");
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
        $s3imagepath = "uploads/groups/thumb/".$group_id."/".$album_id;
        $image_local_path = $path_to_thumbs_center_image_path;
        $this->s3imageUpload($s3imagepath, $image_local_path, $imagename);
    }
    
    
    /**
     * Create album for group
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postCreategroupalbumsAction(Request $request) { 
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object
        
        $required_parameter = array('group_id','album_name');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //getting the privacy setting array
       $privacy_setting_constant        = $this->get('privacy_setting_object.service');
       $privacy_setting_constant_result = $privacy_setting_constant->PrivacySettingService();
       
        //default privacy setting for album is public
       $album_privacy_setting = (isset($object_info->album_privacy_setting) ? ($object_info->album_privacy_setting) : 3);

       if (!in_array($album_privacy_setting, $privacy_setting_constant_result)) {
           return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
       }
       
        $group_id   = $object_info->group_id;
        $album_name = $object_info->album_name;
        $album_desc = (isset($object_info->album_desc) ? $object_info->album_desc : '');

        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_album = new GroupAlbum();
        if(!(empty($group_id))){
            $group_album->setAlbumName($album_name);
            $group_album->setAlbumDesc($album_desc);
            $group_album->setGroupId($group_id);
            $group_album->setPrivacySetting($album_privacy_setting);
            $group_album->setCreatedAt(time());
            $dm->persist($group_album);
            //save group album info in table
            $dm->flush();
            $album_id = $group_album->getId();
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            $image_album_location = $file_location .  $this->group_media_album_path . $group_id . '/' . $album_id;
            $thumbnail_album_location = $file_location . $this->group_media_album_path_thumb . $group_id . '/' . $album_id;
            if (!file_exists($image_album_location)) {
                \mkdir($image_album_location, 0777, true);
                \mkdir($thumbnail_album_location, 0777, true);
                
                $data = array(
                    'avg_rate'=>0,
                    'no_of_votes' =>0,
                    'current_user_rate'=>0,
                    'is_rated' =>false
                );
                $resp_data = array('code' => 101, 'message' => 'ALBUM_IS_CREATED_SUCCESSFULLY', 'data' => $data);
                echo json_encode($resp_data);
                exit();
            }
        } else {
            $resp_data = array('code' => 101, 'message' => 'THIS_GROUP_DOES_NOT_EXISTS_IN_OUR_RECORD', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
    }
    

    
    /**
     * Call api/upload action
     * @param Request $request	
     * @return array
     */
     public function postUploadgroupmediaalbumsAction(Request $request) {

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
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('group_id','post_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $time = new \DateTime("now");
        $group_id   =  $object_info->group_id;
        $album_id   =  $object_info->album_id;
        $post_type  =  $object_info->post_type;
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        
        if ($this->getRequest()->getMethod() === 'POST') {
            if($post_type == 0) {  // start uploading the image  
            //$post_type reprsents here that media is uploaing till submit button is pressed(ie media_status = 0)
               // media uploading
                        $i = 0;
                        $image_upload = $this->get('amazan_upload_object.service');
                        foreach ($_FILES['group_media']['tmp_name'] as $key => $tmp_name) {
                            
                            $original_media_name = $_FILES['group_media']['name'][$key];
                            $album_thumb_image_width  = $this->resize_image_width;
                            $album_thumb_image_height = $this->resize_image_height;
                            if (!empty($original_media_name)) {  //if file name is not exists means file is not present.
                                //checking the file type extension.
                                $file_error = $this->checkFileTypeAction(); 
                                if ($file_error) {
                                    return array('code' => 100, 'message' => 'ONLY_IMAGES_AND_VIDEO_ARE_ALLOWED', 'data' => $data);
                                }
                                
                                
                                $image_info = getimagesize($_FILES['group_media']['tmp_name'][$key]);
                                $orignal_mediaWidth = $image_info[0];
                                $original_mediaHeight = $image_info[1];
                                //call service to get image type. Basis of this we save data 3,2,1 in db
                                $image_type_service = $this->get('user_object.service');
                                $image_type         = $image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$album_thumb_image_width,$album_thumb_image_height);
                                
                              //  $group_media_name = time().$_FILES['group_media']['name'][$key];
                                $group_media_name = time() . strtolower(str_replace(' ', '', $_FILES['group_media']['name'][$key]));
                                //clean image name
                                $clean_name = $this->get('clean_name_object.service');
                                $group_media_name = $clean_name->cleanString($group_media_name);
                                //end image name
                                $group_media_type = $_FILES['group_media']['type'][$key];
                                $group_media_type = explode('/', $group_media_type);
                                $group_media_type = $group_media_type[0];
                                $group_media = new GroupMedia();
                                //checking if album exits for this group in album database then
                                //upload media in that album
                                if (!empty($album_id)) {
                                    $album = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                                            ->findBy(array('id' => $album_id,'group_id'=>$group_id));
                                    if ($album) {
                                        $group_media->setGroupId($group_id);
                                        $group_media->setMediaName($group_media_name);
                                        $group_media->setMediaType($group_media_type);
                                        $group_media->setAlbumid($album_id);
                                        $group_media->setProfileImage(0);
                                        $group_media->setMediaStatus(0);
                                        $group_media->setX(''); // initally save null in database for x and y coordinate
                                        $group_media->setY('');
                                        $group_media->setCreatedAt($time);
                                        $group_media->setImageType($image_type);
                                        //there are more than one images make first image fetaured image
                                        // this would be treat like Album featured image 
                                        if ($i == 0) {
                                            $group_media->setIsFeatured(1);
                                        } else {
                                            $group_media->setIsFeatured(0);
                                        }
                                        $i++; 
                                       $dm->persist($group_media);
                                       $dm->flush();
                                       $club_album_media_id = $group_media->getId();
                                    } else {
                                        return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
                                    }
                                } else { // upload the media in user id folder
                                    $group_media->setGroupId($group_id);
                                    $group_media->setMediaName($group_media_name);
                                    $group_media->setMediaType($group_media_type);
                                    $group_media->setAlbumid('');
                                    $group_media->setMediaStatus(0);
                                    $group_media->setX(''); // initally save null in database for x and y coordinate
                                    $group_media->setY('');
                                    $group_media->setImageType($image_type);
                                    $group_media->albummediaupload($group_id, $key, $group_media_name, $album_id);

                                    $dm->persist($group_media);
                                    $dm->flush();
                                    $club_album_media_id = $group_media->getId();
                                }
                                if ($group_media_type == 'image') {
                                    if($album_id){
                          
                                        $pre_upload_media_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path'). $group_id . "/" . $album_id . '/';
                                        $media_original_path = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path') . $group_id . "/" . $album_id . '/';
                                        $thumb_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path_thumb') . $group_id . "/" . $album_id . '/';
                                        $thumb_crop_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path_thumb_crop') . $group_id . "/" . $album_id . '/';
                                        $s3_post_media_path = $this->container->getParameter('s3_club_album_media_path'). $group_id;
                                        $s3_post_media_thumb_path = $this->container->getParameter('s3_club_album_media_thumb_path'). $group_id;
                                    } else {                                       
                                        $pre_upload_media_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path'). $group_id . '/' ;
                                        $media_original_path = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path') . $group_id . '/' ;
                                        $thumb_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path_thumb') . $group_id . '/' ;
                                        $thumb_crop_dir = __DIR__ . "/../../../../../web" . $this->container->getParameter('club_album_media_path_thumb_crop') . $group_id . '/' ;
                                        $s3_post_media_path = $this->container->getParameter('s3_club_album_media_path'). $group_id;
                                        $s3_post_media_thumb_path = $this->container->getParameter('s3_club_album_media_thumb_path'). $group_id;
                                    }
                                    //$image_upload->imageUploadService($_FILES['group_media'],$key,$group_id,'club_album',$group_media_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path,$album_id);
                                    $image_upload->albumimageUploadService($_FILES['group_media'],$key,$group_id,'club_album',$group_media_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path,$album_id);
                                }
                            }
                        }
                        
                         $album_media_data = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                               ->find($club_album_media_id);
                         
                         $album_media_name  = $album_media_link = $album_media_thumb = $album_image_type= '';//initialize blank variables.

                        if ($album_media_data) {
                            $album_image_type = $album_media_data->getImageType();
                            $album_media_name  = $album_media_data->getMediaName();
                            $album_media_link  = $aws_path . $this->group_media_album_path . $group_id . '/' . $album_id . '/'.$album_media_name; 
                            $album_media_thumb = $aws_path . $this->group_media_album_path_thumb . $group_id . '/' . $album_id . '/'.$album_media_name;
                        }
                           //sending the current media and post data
                          $data = array(
                            'club_album_id' => $album_id,
                            'media_id' => $club_album_media_id,
                            'media_link' => $album_media_link,
                            'media_thumb_link' => $album_media_thumb,
                            'image_type' =>$album_image_type
                        );
                        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);             
                } else { // media uplaoding is stop and now submit button is pressed 
                    // i.e media is published media_status = 1
                   $object_info->media_id   = (isset($object_info->media_id) ? $object_info->media_id : '');
                    //get media array
                    $media_array = $object_info->media_id;
                   
                     $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    //publish the images i.e make  media_status becomes 1 for all the images those are uploaded and unpublished
                    $medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                    ->publishClubAlbumImage($media_array);
                    
                    // now get all the media whose media_status becomes 1.
                    $group_album_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                            ->findBy(array('albumid' => $album_id, 'group_id' => $group_id,'media_status'=>1));
                    $media_data = array();
                    foreach ($group_album_medias as $album_media) {
                        $album_media_name   = $album_media_link = $album_media_thumb = '';
                        $album_media_id     = $album_media->getId();
                        $album_media_name   = $album_media->getMediaName();
                        $album_media_type   = $album_media->getMediaType();
                        $album_media_status = $album_media->getMediaStatus();
                        $album_image_type   = $album_media->getImageType();
                        if($album_media_type == 'image'){
                             $album_media_name  = $album_media->getMediaName();
                             $album_media_link  = $aws_path . $this->group_media_album_path . $group_id . '/' . $album_id . '/'.$album_media_name; 
                             $album_media_thumb = $aws_path . $this->group_media_album_path_thumb . $group_id . '/' . $album_id . '/'.$album_media_name;
                        } else{
                          $album_media_link='';
                          $album_media_thumb ='';
                        }

                        $media_data[] = array('id' => $album_media_name,
                                            'media_name' => $album_media_name,
                                            'media_path' => $album_media_link,
                                            'thumb_path' => $album_media_thumb,
                                            'media_status' => $album_media_status,
                                            'image_type' =>$album_image_type,
                                            'avg_rate'=>0,
                                            'no_of_votes' =>0,
                                            'current_user_rate'=>0,
                                            'is_rated' =>false
                                             );
                    }
                   
                    $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $media_data); 
                    echo json_encode($resp_data);
                    exit();
           }
           
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
     * deleting the group album.
     * @param request object
     * @param json
     */
    public function postDeletegroupalbumsAction(Request $request) {
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
        
        $required_parameter = array('group_id','album_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        $group_id = $object_info->group_id;
        $album_id = $object_info->album_id;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $album = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                ->find($album_id);

        if (!$album) {
            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
        }
        if ($album) {
            //remove from media table
            $dm->remove($album);
            $dm->flush();
            
            //remove corresponding media

             $album_media = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                           ->removeAlbumMedia($album_id);
            
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path
            if ($album_media) {
                $image_album_location = $file_location . $this->group_media_album_path . $group_id . '/' . $album_id;
                $thumbnail_album_location = $file_location . $this->group_media_album_path_thumb . $group_id . '/' . $album_id;
                
                //as image will not exist, so commented the code
                if (file_exists($image_album_location)) {
                   //array_map('unlink', glob($image_album_location . '/*'));
                   //rmdir($image_album_location);
                }
                if (file_exists($thumbnail_album_location)) {
                  //array_map('unlink', glob($thumbnail_album_location . '/*'));
                  //rmdir($thumbnail_album_location);
                }
                $resp_data =  array('code' => 101, 'message' => 'ALBUM_IS_DELETED_SUCCESSFULLY', 'data' => $data);
                echo json_encode($resp_data);
                exit();
            }
        }
    }
    
    
    /**
     * deleting the media of album of group.
     * @param request object
     * @param json
     */
    public function postDeletegroupalbummediasAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //request object end

        $required_parameter = array('group_id','media_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //get user login id
        // $user_id = (int) $de_serialize['user_id'];
        
        //get Group id
        $group_id = $de_serialize['group_id'];

        //get Media id
        $media_id = $de_serialize['media_id'];
        //get album id
        $group_album_id = (isset($de_serialize['album_id']) ? $de_serialize['album_id'] : '');

        // get documen manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_album_media = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                             ->find($media_id);   
        if (!$group_album_media) {
            $res_data = array('code' => 127, 'message' => 'NO_IMAGE_FOUND', 'data' => $data);
            return $res_data;
        }

        $dm->remove($group_album_media);
        $dm->flush();

        //@TODO also remove the image from folder 
        //remove corresponding media from folder also
        $mediaName = $group_album_media->getMediaName();
        $document_root = $request->server->get('DOCUMENT_ROOT');
        $BasePath = $request->getBasePath();
        $file_location = $document_root . $BasePath; // getting sample directory path
        if ($group_album_id) {
           $mediaToBeDeleted = $file_location .$this->group_media_album_path . $group_id  .'/'. $group_album_id . '/' . $mediaName;
           $mediaThumbToBeDeleted = $file_location . $this->group_media_album_path_thumb . $group_id . '/'. $group_album_id . '/' . $mediaName;
        } else {
            $mediaToBeDeleted = $file_location . $this->group_media_album_path . $group_id .'/'. $mediaName;
            $mediaThumbToBeDeleted = $file_location . $this->group_media_album_path_thumb . $group_id .'/'. $mediaName;
        }
        
        //as image will not exist, so commented the code
        if (file_exists($mediaToBeDeleted)) {
          //unlink($mediaToBeDeleted);
        }
        if (file_exists($mediaThumbToBeDeleted)) {
          //unlink($mediaThumbToBeDeleted);
        }
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }
    
     /**
     * View album of a store.
     * @param request object
     * @param json
     */
    public function postViewgroupalbumsAction(Request $request) {
        //initilise the array
         $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        
        $required_parameter = array('group_id','album_id','user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
               
        //get Store id
        $group_id = $de_serialize['group_id'];
        //get album id
        $group_album_id = $de_serialize['album_id'];
        $current_user_id = $de_serialize['user_id'];
        $limit_start = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        $limit_size  = (isset($de_serialize['limit_size'])  ? $de_serialize['limit_size']  : 20) ;
        
        if (!$group_album_id) {
            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
        }
        // get documen manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        //for album info name and description....
        $group_album_info = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                           ->find($group_album_id);
        if (!$group_album_info) {
            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
        }
        $album_name = $group_album_info->getAlbumName();
        $album_desc = $group_album_info->getAlbumDesc();
        $album_current_rate = 0;
        $album_is_rated = false;
        foreach($group_album_info->getRate() as $rate) {
            if($rate->getUserId() == $current_user_id ) {
                $album_current_rate = $rate->getRate();
                $album_is_rated = true;
                break;
            }
        }
        $album_info = array(
            'title' => $album_name,
            'description' =>$album_desc,
            'avg_rate'=>round($group_album_info->getAvgRating(), 1),
            'no_of_votes'=> (int) $group_album_info->getVoteCount(),
            'current_user_rate'=>$album_current_rate,
            'is_rated' => $album_is_rated
        );
        
        //for album media
        $album_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                           ->findBy(array('albumid' => $group_album_id, 'group_id' => $group_id, 'media_status' => 1));
            
        $total_media_in_album = count($album_medias);
            
            
        $group_album_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                 ->findBy(array('albumid' => $group_album_id, 'group_id' => $group_id, 'media_status' => 1),null, $limit_size, $limit_start);
        $media_data = array();
        foreach ($group_album_medias as $album_media) {
            $media_id = $album_media->getId();
            $media_name = $album_media->getMediaName();
            $media_type  = $album_media->getMediaType();
            $album_image_type  = $album_media->getImageType();
            if($media_type == 'image'){
                $mediaPath = $aws_path . $this->group_media_album_path . $group_id  .'/'. $group_album_id . '/' . $media_name;
                $thumbDir  = $aws_path . $this->group_media_album_path_thumb . $group_id . '/'. $group_album_id . '/' . $media_name;
            } else{
              $mediaPath='';
              $thumbDir ='';
            }
          
            $current_rate = 0;
            $is_rated = false;
            foreach($album_media->getRate() as $rate) {
                if($rate->getUserId() == $current_user_id ) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            
            $media_data[] = array('id' => $media_id,
                                  'media_name' => $media_name,
                                  'media_path' => $mediaPath,
                                  'thumb_path' => $thumbDir,
                                  'image_type' =>$album_image_type,
                                  'avg_rate'=>round($album_media->getAvgRating(), 1),
                                  'no_of_votes'=> (int) $album_media->getVoteCount(),
                                  'current_user_rate'=>$current_rate,
                                  'is_rated' => $is_rated
                                 );
        }
        $album_information = array();
        $album_information = array('media' => $media_data, 'album'=> $album_info,
                                    'size' =>$total_media_in_album
                                 );
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $album_information);
        
        echo json_encode($resp_data);
        exit();
    }
    
    /**
     * View all album of a Group.
     * @param request object
     * @param json
     */
//    public function postGroupalbumlistsAction(Request $request) {
//     
//        //initilise the array
//        $data = array();
//        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeDataAction($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//        
//        //code for aws s3 server path
//        $aws_base_path  = $this->container->getParameter('aws_base_path');
//        $aws_bucket    = $this->container->getParameter('aws_bucket');
//        $aws_path = $aws_base_path.'/'.$aws_bucket;
//        
//        $required_parameter = array('group_id');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
//        }
//        
//        //getting the privacy setting array
//       $privacy_setting_constant        = $this->get('privacy_setting_object.service');
//       $privacy_setting_constant_result = $privacy_setting_constant->PrivacySettingService();
//
//       //default privacy setting for album is public
//       $album_privacy_setting = (isset($object_info->album_privacy_setting) ? ($object_info->album_privacy_setting) : 1);
//       if (!in_array($album_privacy_setting, $privacy_setting_constant_result)) {
//           return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
//       }
//        
//        //get Store id
//        $group_id    = $de_serialize['group_id'];
//        $limit_start = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
//        $limit_size  = (isset($de_serialize['limit_size'])  ? $de_serialize['limit_size']  : 20) ;
//        // get documen manager object
//        $dm = $this->get('doctrine.odm.mongodb.document_manager');
//        
//        $total_group_albums = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
//                                 ->findBy(array('group_id' => $group_id));
//        
//        $total_num_group_albums = count($total_group_albums);
//        
//        $group_albums = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
//                           ->findBy(array('group_id' => $group_id), null, $limit_size, $limit_start);
//        $album_datas = array();
//        foreach ($group_albums as $group_album) {
//            $album_id = $group_album->getId();
//            $album_name = $group_album->getAlbumName();
//            $album_description = $group_album->getAlbumDesc();
//            //count total number of media in particular album
//            $dm = $this->get('doctrine.odm.mongodb.document_manager');
//            $group_album_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                                     ->findBy(array('albumid' => $album_id, 'group_id' => $group_id, 'media_status'=>1));
//            
//            $total_media_in_album = count($group_album_medias);
//
//
//            //get featured image of album to make cover image of that album
//            $featured_image = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                                  ->findOneBy(array('albumid' => $album_id, 'group_id' => $group_id, 'media_status' => 1),array('id'=>'ASC'),$limit_size,$limit_start);
//            
//            if ($featured_image) {
//                $featured_image_name = $featured_image->getMediaName();
//                $featured_thumb_path = $aws_path . $this->group_media_album_path_thumb . $group_id . '/' . $album_id . '/' . $featured_image_name;
//            } else {
//                $featured_image_name = '';
//                $featured_thumb_path = '';
//            }
//            $album_datas[] = array('id' => $album_id,
//                                    'album_name' => $album_name,
//                                    'created_at' => $group_album->getCreatedAt(),
//                                    'media_in_album' => $total_media_in_album,
//                                    'album_featured_image' => $featured_thumb_path,
//                                    'album_description' => $album_description,
//                                    
//                                );
//        }
//         $album_information = array();
//         $album_information[] = array('media' => $album_datas,
//                                      'size' =>$total_num_group_albums
//                                      );
//        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $album_information);
//        
//        echo json_encode($resp_data);
//        exit();
//        
//    }
    
    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $org_resize_dir, $group_id, $album_id=null) {
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
       
        $s3imagepath = "uploads/groups/original/".$group_id;
        //check if album id is not null
        if($album_id != ""){
        $s3imagepath = "uploads/groups/original/" . $group_id . "/" . $album_id;
        }
       $image_local_path = $path_to_thumbs_directory.$filename;
       //upload on amazon
       $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }
    
    /**
     * View all album of a Group.
     * @param request object
     * @param json
     */
    public function postGroupalbumlistsAction(Request $request) {
     
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeDataAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        
        $required_parameter = array('group_id','user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //getting the privacy setting array
       $privacy_setting_constant        = $this->get('privacy_setting_object.service');
       $privacy_setting_constant_result = $privacy_setting_constant->PrivacySettingService();

       //default privacy setting for album is public
       $album_privacy_setting = (isset($object_info->album_privacy_setting) ? ($object_info->album_privacy_setting) : 3);
       if (!in_array($album_privacy_setting, $privacy_setting_constant_result)) {
           return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
       }
        
        //get Store id
        $group_id    = $de_serialize['group_id'];
        $current_user_id    = $de_serialize['user_id'];
        $limit_start = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        $limit_size  = (isset($de_serialize['limit_size'])  ? $de_serialize['limit_size']  : 20) ;
        // get documen manager object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        $total_group_albums = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                                 ->findBy(array('group_id' => $group_id));

        $total_num_group_albums = count($total_group_albums);
        
        $group_albums = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                           ->findBy(array('group_id' => $group_id), null, $limit_size, $limit_start);
        
        //get the group album ids.
        $group_album_ids =  array_map(function($group_album_results) {
            return $group_album_results->getId();
            }, $group_albums);
        
        //get all the media of album.
        $group_albums_media = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                 ->getAlbumsMedia($group_album_ids);
        $album_datas = array();
        foreach ($group_albums as $group_album) {
            $album_id   = $group_album->getId();
            $album_name = $group_album->getAlbumName();
            $album_description = $group_album->getAlbumDesc();
            //count total number of media in particular album
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            //get the total media of a album
            $group_album_medias = isset($group_albums_media[$album_id]) ? $group_albums_media[$album_id] : array();
            
            $total_media_in_album = count($group_album_medias);


            //get featured image of album to make cover image of that album
            $featured_image = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                  ->findOneBy(array('albumid' => $album_id, 'group_id' => $group_id, 'media_status' => 1),array('id'=>'ASC'),$limit_size,$limit_start);
            $featured_thumb_path = '';
            $featured_image_type = '';
            if ($featured_image) {
                $featured_image_name = $featured_image->getMediaName();
                $featured_image_type = $featured_image->getImageType();
                $featured_thumb_path = $aws_path . $this->group_media_album_path_thumb . $group_id . '/' . $album_id . '/' . $featured_image_name;
            }
            
            $current_rate = 0;
            $is_rated = false;
            foreach($group_album->getRate() as $rate) {
                if($rate->getUserId() == $current_user_id ) {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            
            $album_datas[] = array('id' => $album_id,
                                    'album_name' => $album_name,
                                    'created_at' => $group_album->getCreatedAt(),
                                    'media_in_album' => $total_media_in_album,
                                    'album_featured_image' => $featured_thumb_path,
                                    'image_type' =>$featured_image_type,
                                    'album_description' => $album_description,
                                    'avg_rate'=>round($group_album->getAvgRating(), 1),
                                    'no_of_votes'=> (int) $group_album->getVoteCount(),
                                    'current_user_rate'=>$current_rate,
                                    'is_rated' => $is_rated
                                );
        }
         $album_information = array();
         $album_information = array('media' => $album_datas,
                                      'size' =>$total_num_group_albums
                                      );
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $album_information);
        
        echo json_encode($resp_data);
        exit();
        
    }
}
