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

// service method class for user object.
class MediaObjectService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path = '/uploads/users/media/original/';
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
   
    public function CreateMediathumb(){
               $path_to_thumbs_directory = __DIR__ . "/../../../../web" . $this->dashboard_post_media_path_thumb . $post_id . "/";
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = $this->image_width;
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
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($nm, $path_to_thumbs_directory . $filename); 
    }
    /**
     * finding the user object with user profile.
     * @param int $user_id
     * @return array
     */
   public function UserObjectService($user_id)
   {
        $user_info = array();
        if (!empty($user_id)) {
            $em = $this->em;
            $user_object = $em->getRepository('UserManagerSonataUserBundle:User')->findBy(array('id'=>$user_id));
            if (!$user_object) {
             return $user_info ;
            }
            $user = $user_object[0];
            if (!$user) {
             return $user_info ;
            }
            $profile_image_id = $user->getProfileImg();
            $img_path       = '';
            $img_thumb_path = '';
            if (!empty($profile_image_id)) {
               $dm = $this->dm;
               $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                             ->find($profile_image_id);
                if ($media_info) {
                    $album_id   = $media_info->getAlbumId();
                    $media_name = $media_info->getName();
                    if (!empty($album_id)) {
                      $img_path       =  $this->getBaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
                      $img_thumb_path =  $this->getBaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
                    } else {
                      $img_path       =  $this->getBaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
                      $img_thumb_path =  $this->getBaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$media_name; 
                    }
                }
            }
            $user_info = array(
                'id'=>$user->getId(),
                'username'=>$user->getUserName(),
                'email'=>$user->getEmail(),
                'first_name'=>$user->getFirstName(),
                'last_name'=>$user->getLastName(),
                'gender'=>$user->getGender(),
                'phone'=>$user->getPhone(),
                'date_of_birth'=>$user->getDateOfBirth(),
                'country'=>$user->getCountry(),
                'profile_type'=>$user->getProfileType(),
                'citizen_profile'=>$user->getCitizenProfile(),
                'broker_profile'=>$user->getBrokerProfile(),
                'store_profile'=>$user->getStoreProfile(),
                'active'=>$user->isEnabled(),
                'profile_image'=>$img_path,
                'profile_image_thumb'=>$img_thumb_path
            );
        }
        return $user_info;
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
    
}
