<?php
namespace Media\MediaBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use s3;
require_once __DIR__ . '/../Resources/lib/S3.php';

// service method class for user object.
class ImageS3UploadService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    protected $image_width = 100;
    protected $post_comment_limit = 5;
    protected $post_comment_offset = 0;
    protected $user_profile_type_code = 22;
    protected $profile_type_code = 'user';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $dashboard_post_thumb_image_width = 654;
    protected $dashboard_post_thumb_image_height = 360;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    protected $album_image_thumb_width = 200;
    protected $album_image_thumb_height = 200;
    protected $shop_offer_original_resize_image_width = 608;
    protected $shop_offer_original_resize_image_height = 283;
    protected $shop_offer_thumb_width = 300;
    protected $shop_offer_thumb_height = 160;
    protected $resize_cover_image_width = 910;
    protected $resize_cover_image_height = 410;
    //define the required params

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
   
    /**
     * uploading image to amazan s3 server
     * @param string $dir_path (path on s3 server)
     * @param string $target_file_path (full path including name on our local server)
     * @param string $file_name (timestamp_userid_thumb$i/original/resize ...)
     * @return string
     */
   public function ImageS3UploadService($dir_path,$target_file_path,$file_name)
   {
        //AWS access info
        $aws_key = $this->container->getParameter('aws_key');
        $aws_secret_key = $this->container->getParameter('aws_secret_key');
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        
        //define('awsAccessKey', $aws_key);
        //define('awsSecretKey', $aws_secret_key);      
        
        // code for uploading image to amazan s3 server.
        $s3Object = new S3($aws_key, $aws_secret_key);  
        $image_url= '';
        if ($s3Object->putObjectFile($target_file_path, $aws_bucket, $dir_path.'/'.$file_name,S3::ACL_PUBLIC_READ)) {
            $image_url = $aws_base_path.'/'.$aws_bucket.'/'.$dir_path.'/'.$file_name;
            $path = __DIR__ . "/../../../../web/uploads/imagelogs.txt";
                $image_url = $aws_base_path.'/'.$aws_bucket.'/'.$dir_path.'/'.$file_name;
                $myfile = fopen($path, "a");
                fwrite($myfile,$image_url."\n");
               return $image_url;
        }
        else {
            
            $path = __DIR__ . "/../../../../web/uploads/imagelogs.txt";
                $image_url = $aws_base_path.'/'.$aws_bucket.'/'.$dir_path.'/'.$file_name;
                $myfile = fopen($path, "a");
                fwrite($myfile,$image_url."\n");
            return null;
        }
   }
   
   /**
     * uploading image to amazan s3 server using $_FILES
     * @param string $dir_path (path on s3 server)
     * @param string $target_file_path (full path including name on our local server)
     * @param string $file_name (timestamp_userid_thumb$i/original/resize ...)
    *  @param sting key of $_FILES array
     * @return string
     */
   public function ImageS3UploadFileService($dir_path,$file_name,$key)
   {
        //AWS access info
        $aws_key = $this->container->getParameter('aws_key');
        $aws_secret_key = $this->container->getParameter('aws_secret_key');
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        
        //define('awsAccessKey', $aws_key);
        //define('awsSecretKey', $aws_secret_key);      
        
        // code for uploading image to amazan s3 server.
        $s3Object = new S3($aws_key, $aws_secret_key);  

        $image_url= '';
        if(isset($_FILES[$key])){
            if ($s3Object->putObjectFile($_FILES[$key]["tmp_name"], $aws_bucket, $dir_path.'/'.$file_name,S3::ACL_PUBLIC_READ)) {
           
                $image_url = $aws_base_path.'/'.$aws_bucket.'/'.$dir_path.'/'.$file_name;
                return $image_url;
            }
            else {
                return false;
            }
        }else{
            return false;
        }
        
   }
   
    /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->container->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }
    
    /**
     * service for fetching the request object 
     * @param Request
     * @return object array
     */
    public function requestfetch()
    {
        $request    = $this->container->get('request');
        $freq_obj  = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
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
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
     * Service for uploading the file on the post comment
     * @param type $file file object need to be upload
     * @param type $key key of the array
     * @param type $post_id if it is uploaded for the post 
     * @param type $type define if it is for post or comment
     * @param type $comment_id if it is uploaded fir a comment
     */
    public function imageUploadService($file, $key, $post_comment_id = null, $type,$file_name, $pre_upload_media_dir, $media_original_path, $thumb_dir, $thumb_crop_dir, $s3_post_media_path, $s3_post_media_thumb_path, $album_id = null) {
        //getting the orignal file name
        $key = (string)$key;      
        if($key != ''){
           //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'][$key];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            // since post id is string so directory name would be string type

            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'][$key];
            $file_type = $file['type'][$key];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        } else {
            //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            // since post id is string so directory name would be string type

            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'];
            $file_type = $file['type'];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        }
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) {
            move_uploaded_file($source, $upload_media_dir . $file_name);
        } else {
            $destination = \mkdir($upload_media_dir, 0777, true);
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
        //check if media type is image 
        // check the type for which the post or coment is uploaded then set the path accordingly 
        if ($actual_media_type == 'image') {
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }
            //end of image rotate                                
            //resize the original image..
            $image_path_array = array();
            $image_path_array[''] = $this->resizeOriginal($file_name, $media_original_path, $media_original_path, $post_comment_id, $s3_post_media_path, $album_id);

            //first resize the post image into crop folder
            $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $post_comment_id, $album_id);
            //crop the image from center
            $this->createCenterThumbnail($file_name, $thumb_crop_dir, $thumb_dir, $post_comment_id, $s3_post_media_thumb_path, $album_id);
        } elseif ($actual_media_type == 'video') {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        } else {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        }
    }

    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $post_id, $s3imagepath, $album_id = null) {
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
      /*  if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        } */
        //new code for getting the image.
        $image_data = file_get_contents($path_to_image_directory . $filename);
        $im = imagecreatefromstring($image_data);
        
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
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
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

        $s3imagepath = $s3imagepath;
        //check if album id is not null
        if ($album_id != "") {
            $s3imagepath = $s3imagepath . "/" . $album_id;
        }
        $image_local_path = $path_to_thumbs_directory . $filename;

        //upload on amazon
        $s3_image_path = $this->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $s3_image_path;
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $post_id, $album_id = null) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->dashboard_post_thumb_image_width;
        $thumb_height = $this->dashboard_post_thumb_image_height;
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
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
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
     * @param string $post_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $post_id, $s3imagepath, $album_id = null) {
       

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = $thumb_dir;
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
        $crop_image_width = $this->dashboard_post_thumb_image_width;
        $crop_image_height = $this->dashboard_post_thumb_image_height;

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
        //code for png start
	$background = imagecolorallocate($canvas, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($canvas, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($canvas, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($canvas, true);
        //code end for png end
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
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
        
      //  imagejpeg($canvas, $path_to_thumbs_center_image_path, 75); //100 is quality
        //$s3_image_path    =  "uploads/documents/dashboard/post/thumb/". $post_id ;
        $s3_image_path = $s3imagepath;
        //check if album id is not null
        if ($album_id != "") {
            $s3_image_path = $s3imagepath . "/" . $album_id;
        }
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
        $s3_image_path = $this->ImageS3UploadService($s3_image_path, $image_local_path, $original_filename);
        return $s3_image_path;
    }

    /**
     * Rotate the image if it get orientation
     * @param string $source_image_path
     */
    public function ImageRotateService($source_image_path) {

        $exif = @exif_read_data($source_image_path);
        if (!empty($exif['Orientation'])) {
            $image = imagecreatefromjpeg($source_image_path);
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;

                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }

            imagejpeg($image, $source_image_path, 90);
        }
    }

    /**
     * finding the privacy setting array object.
     * @param $string
     * @return array
     */
    public function cleanString($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9.\-]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }
    
   /**
    * image download from s3 server
    * @param string $file_local_path
    * @param type $s3_path
    * @param type $item_id
    * @param type $file_name
    * @param type $album_id
    */
    public function ImageS3DownloadService($file_local_path, $s3_path, $item_id, $file_name, $album_id = '', $entity=null)
    {  
        $dir_path = $file_local_path;
        $file_local_path = $file_local_path.$file_name;

         if (!file_exists($dir_path)) {
         \mkdir($dir_path, 0777, true);
         }
        //AWS access info
        $aws_key        = $this->container->getParameter('aws_key');
        $aws_secret_key = $this->container->getParameter('aws_secret_key');
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket     = $this->container->getParameter('aws_bucket'); 
        $aws_final_path = $s3_path; //prepatre the s3 server image path
        if($entity != 'shop'){        
        $aws_final_path = $s3_path.$item_id; //prepatre the s3 server image path
        }
        if ($album_id != '') { //if album id is exists.
            $aws_final_path = $aws_final_path.'/'.$album_id.'/'.$file_name;
        } else {
            $aws_final_path = $aws_final_path.'/'.$file_name;
        }
      
        //getting s3 object
        $s3Object = new S3($aws_key, $aws_secret_key);
        //download image from s3
        try {
            $s3Object->getObject($aws_bucket, $aws_final_path, $file_local_path);
        } catch (\Exception $e) {

        }
        return true;
   }
   
  /**
   * 
   * @param type $nm
   * @param type $im
   * @param type $x
   * @param type $y
   * @param type $w
   * @param type $y
   * @param type $nx
   * @param type $ny
   * @param type $ox
   * @param type $oy
   */

    
   public function createPngImage($nm, $im, $nx, $ny, $ox, $oy) {
       
        //  $nm = imagecreatetruecolor($nx, $ny);
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
       
   }
   
   /**
     * Service for uploading the file on the album
     * @param type $file file object need to be upload
     * @param type $key key of the array
     * @param type $post_id if it is uploaded for the post 
     * @param type $type define if it is for post or comment
     * @param type $comment_id if it is uploaded fir a comment
     */
    public function albumimageUploadService($file, $key, $post_comment_id = null, $type,$file_name, $pre_upload_media_dir, $media_original_path, $thumb_dir, $thumb_crop_dir, $s3_post_media_path, $s3_post_media_thumb_path, $album_id = null) {
        //getting the orignal file name
        $key = (string)$key;      
        if($key != ''){
           //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'][$key];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            $upload_media_dir = $pre_upload_media_dir;
            //getting the file media type
            $source = $file['tmp_name'][$key];
            $file_type = $file['type'][$key];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        } else {
            //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            // since post id is string so directory name would be string type

            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'];
            $file_type = $file['type'];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        }
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) {
            move_uploaded_file($source, $upload_media_dir . $file_name);
        } else {
            $destination = \mkdir($upload_media_dir, 0777, true);
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
        //check if media type is image 
        // check the type for which the post or coment is uploaded then set the path accordingly 
        if ($actual_media_type == 'image') {
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }
            //end of image rotate                                
            //resize the original image..
            $image_path_array = array();
            $image_path_array[''] = $this->resizeOriginal($file_name, $media_original_path, $media_original_path, $post_comment_id, $s3_post_media_path, $album_id);

            //first resize the album image into crop folder
            $this->createThumbnailAlbumMedia($file_name, $media_original_path, $thumb_crop_dir, $post_comment_id, $album_id);
            //crop the image from center
            $this->createCenterThumbnailAlbum($file_name, $thumb_crop_dir, $thumb_dir, $post_comment_id, $s3_post_media_thumb_path, $album_id);
        } elseif ($actual_media_type == 'video') {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        } else {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        }
    }
    
    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createCenterThumbnailAlbum($filename, $media_original_path, $thumb_dir, $post_id, $s3imagepath, $album_id = null) {
       

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = $thumb_dir;
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
        $crop_image_width = $this->album_image_thumb_width;
        $crop_image_height = $this->album_image_thumb_width;

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
        //code for png start
	$background = imagecolorallocate($canvas, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($canvas, $background);
        // turning off alpha blending (to ensure alpha channel information 
        imagealphablending($canvas, false);
        // turning on alpha channel information saving (to ensure the full range of transparency is preserved)
        imagesavealpha($canvas, true);
        //code end for png end
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);
        
        //create the directory of album if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
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
        
        $s3_image_path = $s3imagepath;
        //check if album id is not null
        if ($album_id != "") {
            $s3_image_path = $s3imagepath . "/" . $album_id;
        }
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
        $s3_image_path = $this->ImageS3UploadService($s3_image_path, $image_local_path, $original_filename);
        return $s3_image_path;
    }
    
    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createThumbnailAlbumMedia($filename, $media_original_path, $thumb_dir, $post_id, $album_id = null) {
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->album_image_thumb_width;
        $thumb_height = $this->album_image_thumb_height;
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
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
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
     * function to delete media from s3 Server
     * @param type $s3_path
     */
    public function deleteS3media($s3_path){
        
        //AWS access info
        $aws_key        = $this->container->getParameter('aws_key');
        $aws_secret_key = $this->container->getParameter('aws_secret_key');
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket     = $this->container->getParameter('aws_bucket'); 
        $aws_final_path = $s3_path; //prepatre the s3 server image path
        //getting s3 object
        $s3Object = new S3($aws_key, $aws_secret_key);
        //delete image from s3
        try {
            $s3Object->deleteObject($aws_bucket, $aws_final_path);
        } catch (\Exception $e) {
              //  echo $e->getMessage();
        }
        return true;
    }
    
    /**
     * 
     * @param type $file
     * @param type $key
     * @param type $post_comment_id
     * @param type $type
     * @param type $file_name
     * @param type $pre_upload_media_dir
     * @param type $media_original_path
     * @param type $thumb_dir
     * @param type $thumb_crop_dir
     * @param type $s3_post_media_path
     * @param type $s3_post_media_thumb_path
     * @param type $album_id
     */
    public function shopofferimageUploadService($file,$key,$type,$file_name, $pre_upload_media_dir, $media_original_path, $thumb_dir, $thumb_crop_dir, $s3_post_media_path, $s3_post_media_thumb_path) {
        //getting the orignal file name
        $key = (string)$key;      
        if($key != ''){
           //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'][$key];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            $upload_media_dir = $pre_upload_media_dir;
            //getting the file media type
            $source = $file['tmp_name'][$key];
            $file_type = $file['type'][$key];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        } else {
            //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            // since post id is string so directory name would be string type

            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'];
            $file_type = $file['type'];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        }
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) {
            move_uploaded_file($source, $upload_media_dir . $file_name);
        } else {
            $destination = \mkdir($upload_media_dir, 0777, true);
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
        //check if media type is image 
        // check the type for which the post or coment is uploaded then set the path accordingly 
        if ($actual_media_type == 'image') {
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }
            //end of image rotate                                
            //resize the original image..
            $image_path_array = array();
            $image_path_array[''] = $this->resizeOriginalOfferMedia($file_name, $media_original_path, $media_original_path, $s3_post_media_path);

            //first resize the album image into crop folder
            $this->createThumbnailShopOfferMedia($file_name, $media_original_path, $thumb_crop_dir);
            //crop the image from center
            $this->createCenterThumbnailShopOfferMedia($file_name, $thumb_crop_dir, $thumb_dir,$s3_post_media_thumb_path);
        } elseif ($actual_media_type == 'video') {
            $s3_image_path = $s3_post_media_path;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        } else {
            $s3_image_path = $s3_post_media_path;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        }
    }
    
    /**
     * resize original for  a shop offer image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * 
     */
    public function resizeOriginalOfferMedia($filename, $media_original_path, $thumb_dir,$s3imagepath) {
        //get image thumb width
        $thumb_width = $this->shop_offer_original_resize_image_width;
        $thumb_height = $this->shop_offer_original_resize_image_width;
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        //new code for getting the image.
        $image_data = file_get_contents($path_to_image_directory . $filename);
        $im = imagecreatefromstring($image_data);
        
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
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
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

        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $s3_image_path = $this->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $s3_image_path;
    }
    
   /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function createThumbnailShopOfferMedia($filename, $media_original_path, $thumb_dir) {
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->shop_offer_thumb_width;
        $thumb_height = $this->shop_offer_thumb_height;
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
        //code for png start
        $background = imagecolorallocate($nm, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($nm, $background);

        // turning off alpha blending (to ensure alpha channel information 
        // is preserved, rather than removed (blending with the rest of the 
        // image in the form of black))
        imagealphablending($nm, false);

        // turning on alpha channel information saving (to ensure the full range 
        // of transparency is preserved)
        imagesavealpha($nm, true);       
        //code for png end.
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
     *
     */
    public function createCenterThumbnailShopOfferMedia($filename, $media_original_path, $thumb_dir,$s3imagepath) {
       

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = $thumb_dir;
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
        $crop_image_width = $this->shop_offer_thumb_width;
        $crop_image_height = $this->shop_offer_thumb_height;

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
        //code for png start
	$background = imagecolorallocate($canvas, 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($canvas, $background);
        // turning off alpha blending (to ensure alpha channel information 
        imagealphablending($canvas, false);
        // turning on alpha channel information saving (to ensure the full range of transparency is preserved)
        imagesavealpha($canvas, true);
        //code end for png end
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);
        
        //create the directory of album if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
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
        
       // $s3_image_path = $s3imagepath;

        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
         $s3_image_path = $this->ImageS3UploadService($s3imagepath, $image_local_path, $original_filename);
        return $s3_image_path;
    }
    
    /**
     * Service for uploading the file on the post comment
     * @param type $file file object need to be upload
     * @param type $key key of the array
     * @param string $file_name  
     * @return array
     */
    public function imageAllUploadService($file, $key, $file_name) {
        $pre_upload_media_dir =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path');
        $media_original_path =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path');
        $s3_post_media_path = $this->container->getParameter('s3_media_path');
        $thumb_crop_dir =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path_thumb_crop');
        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_path_thumb');
        $s3_media_thumb_path = $this->container->getParameter('s3_media_thumb_path');
        //getting the orignal file name
        $key = (string)$key;      
        if($key != ''){
           //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'][$key];
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'][$key];
            $file_type = $file['type'][$key];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        } else {
            //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'];
            $file_type = $file['type'];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        }
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) {
            move_uploaded_file($source, $upload_media_dir . $file_name);
        } else {
            $destination = \mkdir($upload_media_dir, 0777, true);
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
        
        //check if media type is image 
        // check the type for which the post or coment is uploaded then set the path accordingly 
        if ($actual_media_type == 'image') {
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }
            //end of image rotate                                
            //resize the original image..
            $image_path_array = array();
            $image_path_array['original'] = $this->resizeOriginal($file_name, $media_original_path, $media_original_path, $post_comment_id = null, $s3_post_media_path, $album_id = null);

            //first resize the post image into crop folder
            $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $post_comment_id, $album_id = null);
            //crop the image from center
            $image_path_array['thumb'] = $this->createCenterThumbnail($file_name, $thumb_crop_dir, $thumb_dir, $post_comment_id, $s3_media_thumb_path, $album_id = null);
        } elseif ($actual_media_type == 'video') {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        } else {
            echo $s3_image_path = $s3_post_media_path ;
            echo $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        }
        return $image_path_array;
    }
    
    /**
     * 
     * @param type $file
     * @param type $key
     * @param type $file_name
     * @return type
     */
    public function coverAlbumUploadService($file, $key, $file_name,$type = null) {
        
        $pre_upload_media_dir =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path');
        $media_original_path =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path');
        $s3_post_media_path = $this->container->getParameter('s3_media_path');
        $thumb_crop_dir =  __DIR__ . "/../../../../web" . $this->container->getParameter('media_path_thumb_crop');
        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('media_path_thumb');
        $s3_media_thumb_path = $this->container->getParameter('s3_media_thumb_path');
        //getting the orignal file name
        $key = (string)$key;      
        if($key != ''){
           //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'][$key];
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'][$key];
            $file_type = $file['type'][$key];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        } else {
            //check if the key passed is null(Ex. in case of user profile image upload)
            $original_file_name = $file['name'];
            $file_name = $file_name;
            //cleaning the file name
            $file_name = $this->cleanString($file_name);
            // create directory having title of postId. 
            $upload_media_dir = $pre_upload_media_dir;
            //$image_path_s3 = $this->s3_post_media_path . $post_id;
            //getting the file media type
            $source = $file['tmp_name'];
            $file_type = $file['type'];
            $media_type = explode('/', $file_type);
            $actual_media_type = $media_type[0];
        }
        $actual_media_type = !empty($actual_media_type) ? $media_type[0]  : "image";
       
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) {
            move_uploaded_file($source, $upload_media_dir . $file_name);
        } else {
            $destination = \mkdir($upload_media_dir, 0777, true);
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
        
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }

            //resize the original image..
            $image_path_array = array();
            if($type == 'cover'){
                 $image_path_array['original'] = $this->resizeOriginalCoverPhoto($file_name, $media_original_path, $media_original_path);
                 $image_path_array['cover'] = $this->createProjectCoverImage($file_name, $media_original_path, $thumb_dir);  
                 return $image_path_array;
            }
        // check the type for which the post or coment is uploaded then set the path accordingly 
        if ($actual_media_type == 'image') {
            //rotate the image if orientaion is not actual.
            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                $image_rotate = $this->ImageRotateService($media_original_path . $file_name);
            }
            //end of image rotate                                

            $image_path_array['original'] = $this->resizeOriginal($file_name, $media_original_path, $media_original_path, $post_comment_id = null, $s3_post_media_path, $album_id = null);

            //first resize the album image into crop folder
            $this->createThumbnailAlbumMedia($file_name, $media_original_path, $thumb_crop_dir, $post_comment_id = null, $album_id = null);
            //crop the image from center
            $image_path_array['thumb'] = $this->createCenterThumbnailAlbum($file_name, $thumb_crop_dir, $thumb_dir, $post_comment_id = null, $s3_media_thumb_path, $album_id = null);
        } elseif ($actual_media_type == 'video') {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        } else {
            $s3_image_path = $s3_post_media_path . $post_comment_id;
            $local_file_path = $media_original_path . $file_name;
            $this->ImageS3UploadService($s3_image_path, $local_file_path, $file_name);
        }
        return $image_path_array;
    }
    
    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $org_resize_dir
     * @param string $project_id
     */
    public function resizeOriginalCoverPhoto($filename, $media_original_path, $org_resize_dir) {
          $path_to_thumbs_directory = $org_resize_dir;
         $path_to_image_directory = $media_original_path;

        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
   
        $image_data = file_get_contents($path_to_image_directory . $filename);
        $im = imagecreatefromstring($image_data);
        $ox = imagesx($im);
        $oy = imagesy($im);
        
        //check if image size is less than defined limit size
       // if($ox > $thumb_width || $oy > $thumb_height){
        if($ox > $thumb_width && $oy > $thumb_height){
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
        //code for create png
        $image_upload = $this->container->get('amazan_upload_object.service');
        $image_upload->createPngImage($nm, $im, $nx, $ny, $ox, $oy);
        
        //imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename,9);
        }
       
      // $s3imagepath = "uploads/documents/socialproject/original";
        $s3imagepath = "uploads/documents/images/original";
       $image_local_path = $path_to_thumbs_directory.$filename;
       //upload on amazon
       $s3_image_path = $this->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
       return $s3_image_path;
    }
    
    /**
     * 
     * @param type $filename
     * @param type $media_original_path
     * @param type $thumb_dir
     * @param type $project_id
     */
    public function createProjectCoverImage($filename, $media_original_path, $thumb_dir)
    {
        $path_to_thumbs_directory = __DIR__."/../../../../web/uploads/documents/images/coverphoto/";
	$path_to_image_directory  = $media_original_path;
	//get thumb image width and height
        $thumb_width = $this->resize_cover_image_width;
        $thumb_height = $this->resize_cover_image_height;
        
        $image_data = file_get_contents($path_to_image_directory . $filename);
        $im = imagecreatefromstring($image_data);
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
        //code for create png
        $image_upload = $this->container->get('amazan_upload_object.service');
        $image_upload->createPngImage($nm, $im, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
        
       //upload on amazon
       $s3imagepath = "uploads/documents/images/coverphoto" ; 
       $image_local_path = $path_to_thumbs_directory.$filename;
       $url = $this->ImageS3UploadService($s3imagepath, $image_local_path, $filename); 
       return $url;
    }
}
