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
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\Storealbum;
use Symfony\Component\Locale\Locale;
// service method class for user object.
class UserFriendService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path = '/uploads/users/media/original/';
    protected $store_media_path = '/uploads/documents/stores/gallery/';
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
     * finding the user object with user profile.
     * @param int $user_id
     * @return array
     */
   public function UserObjectService($logged_in_user_id, $user_id, $lang_code)
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
            $cover_image_id = $user->getCoverImg();
            $img_path       = '';            
            $img_thumb_path = '';
            $cover_img_path = '';
            $cover_img_thumb_path = '';
            $x_cord = 0;
            $y_cord = 0;
            if (!empty($profile_image_id)) {
               $dm = $this->dm;
               $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                             ->find($profile_image_id);
                if ($media_info) {
                    $album_id   = $media_info->getAlbumId();
                    $media_name = $media_info->getName();
                    if (!empty($album_id)) {
                      $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
                      $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
                    } else {
                      $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
                      $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$media_name; 
                    }
                }
            }
            if (!empty($cover_image_id)) {
               $cover_dm = $this->dm;
               $cover_media_info = $cover_dm->getRepository('MediaMediaBundle:UserMedia')
                             ->find($cover_image_id);

                if ($cover_media_info) {
                    $x_cord = $cover_media_info->getX();
                    $y_cord = $cover_media_info->getY();
                    $cover_album_id   = $cover_media_info->getAlbumId();
                    $cover_media_name = $cover_media_info->getName();
                    if (!empty($cover_album_id)) {
                      $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                      $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                    } else {
                      $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_media_name;
                      $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$cover_media_name; 
                    }
                }
            }
            
            //get citizen details
            $citezen_details = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')->getExternalProfileCitizen($user_id);
            if($citezen_details){
                $city = $citezen_details['city']; 
            } else {
                $city = 'default';
            }
            
            $visibility_type = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getFriendRelationType($logged_in_user_id, $user_id);
            
            if($visibility_type == '2' || $visibility_type == '3')
            {
                $skills = $em
                    ->getRepository('UserManagerSonataUserBundle:UserSkills')
                    ->findOneBy(array('userId' => $user_id));
                if($skills){
                    $skills = $skills->getSkills();
                } else {
                    $skills = "";
                }
            } else {
                $skills = "";
            }
            
            
            $educationDetails = $em
                ->getRepository('UserManagerSonataUserBundle:EducationDetails')
                ->getFriendEducationDetails($visibility_type, $user_id);
       
            $jobDetails = $em
                    ->getRepository('UserManagerSonataUserBundle:JobsDetails')
                    ->getFriendJobDetails($visibility_type, $user_id);

            if($visibility_type == '1' || $visibility_type == '3'){
                $categoryKeywords = $em
                        ->getRepository('UserManagerSonataUserBundle:UserCategoryKeywords')
                        ->getCategoryKeywords($user_id,$lang_code);

                $userRelatives = $em
                        ->getRepository('UserManagerSonataUserBundle:Relatives')
                        ->getRelatives($user_id);    
            } else {
                $categoryKeywords = array();
                $userRelatives = array();
            }
            
            $personal_pending = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
            ->checkPendingRequestStatus($logged_in_user_id, $user_id, 'personal' );
        
            $professional_pending = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
            ->checkPendingRequestStatus($logged_in_user_id, $user_id, 'professional' );
            
            //get country code
            //get country code with country name
            //@return arry
            $countryLists = Locale::getDisplayCountries('en');
            $country = $user->getCountry();
            if ($country != '') {
                //create country array
                if (array_key_exists($country, $countryLists)) {
                    $country_name = array('name' => $countryLists[$country], 'code' => $country);
                    $cc_name = $countryLists[$country];
                    $cc_code = $country;
                } else {
                    $country_name = array('name' => $country, 'code' => '');
                    $cc_name = $country;
                    $cc_code = '';
                }
            } else {
                $country_name = array('name' => $country, 'code' => '');
                $cc_name = $country;
                $cc_code = '';
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
                'country_name' => $cc_name,
                'country_code' =>$cc_code,
                'profile_type'=>$user->getProfileType(),
                'citizen_profile'=>$user->getCitizenProfile(),
                'broker_profile'=>$user->getBrokerProfile(),
                'store_profile'=>$user->getStoreProfile(),
                'active'=>$user->isEnabled(),
                'profile_image'=>$img_path,
                'profile_image_thumb'=>$img_thumb_path,
                'cover_image'=>$cover_img_path,
                'cover_image_thumb'=>$cover_img_thumb_path,
                'state' => $user->getState(),
                'city' => $city,
                'hobbies' => $user->getHobbies(),
                'relationship' => $user->getRelationship(),
                'about_me' => $user->getAboutMe(),
                'city_born' => $user->getCityBorn(),
                'friend_type' => $visibility_type,
                'educationDetail' =>$educationDetails,
                'jobDetails'=>$jobDetails,
                'categoryKeywords'=>$categoryKeywords,
                'userRelatives'=>$userRelatives,
                'skills'=>$skills,
                'personal_pending'=>$personal_pending,
                'professional_pending'=>$professional_pending,
                'media_id'=>$cover_image_id,
                'x_cord'=>$x_cord,
                'y_cord'=>$y_cord
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
    

    /*
     * method for return image type
     */
    public function CheckImageType($orignalImageWidth,$originalImageHeight,$thumbnailWidth,$thumbnailHeight){
        
        if(($orignalImageWidth <= $thumbnailWidth) && ($originalImageHeight <= $thumbnailHeight)){
          return 3;  
        } elseif($orignalImageWidth <= $thumbnailWidth ){
          return 2 ;
        } elseif($originalImageHeight <= $thumbnailHeight ){
          return 1 ;
        } else {
          return 0;
        }
    }

     /**
     * get store object
     * @param type $shop_id
     * return array
     */
    public function getStoreObjectService($shop_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $store_obj_arr = array(); 
        //get store object
        $store_obj = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $shop_id));
        $store_profile_image_path = '';
        $store_profile_image_thumb_path = '';
        if($store_obj) {            
            $current_store_id = $shop_id;
             $current_store_profile_image_id = $store_obj->getStoreImage();
             if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                                     ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id   = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path       = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/'. $album_id . '/'. $image_name;
                    } else {
                        $store_profile_image_path       = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/'. $image_name;
                    }
                }
            }
             $store_obj_arr = array(
                'id'=>$store_obj->getId(),
                'parentStoreId'=>$store_obj->getParentStoreId(),
                'email'=>$store_obj->getEmail(),
                'description'=>$store_obj->getDescription(),
                'phone'=>$store_obj->getPhone(),
                'businessName'=>$store_obj->getBusinessName(),
                'legalStatus'=>$store_obj->getLegalStatus(),
                'businessType'=>$store_obj->getBusinessType(),
                'paymentStatus'=>$store_obj->getPaymentStatus(),
                'businessCountry'=>$store_obj->getBusinessCountry(),
                'businessRegion'=>$store_obj->getBusinessRegion(),
                'businessCity'=>$store_obj->getBusinessCity(),
                'businessAddress'=>$store_obj->getBusinessAddress(),
                'zip'=>$store_obj->getZip(),
                'province'=>$store_obj->getProvince(),
                'vatNumber'=>$store_obj->getVatNumber(),
                'iban'=>$store_obj->getIban(),
                'mapPlace'=>$store_obj->getMapPlace(),
                'latitude'=>$store_obj->getLatitude(),
                'longitude'=>$store_obj->getLongitude(),
                'name'=>$store_obj->getName(),
                'storeImage'=>$store_obj->getStoreImage(),
                'createdAt'=>$store_obj->getCreatedAt(),
                'isActive'=>$store_obj->getIsActive(),
                'isAllowed'=>$store_obj->getIsAllowed(),
                'original_path'=>$store_profile_image_path,
                'thumb_path'=>$store_profile_image_thumb_path,
                'shop_status'=>$store_obj->getShopStatus(),
            );
        }
       
        return $store_obj_arr;
    }
    
    
    /**
     * get store object
     * @param type $shop_id
     * return array
     */
    public function getMultiStoreObjectService($shop_id) {
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $store_obj_arr = array(); 
        $store_objs = array();
        //get store object
        $store_objs = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->getAllStoreObject($shop_id);
        $store_profile_image_path = '';
        $store_profile_image_thumb_path = '';
        if($store_objs) {
            foreach($store_objs as $store_obj){
            $current_store_id = $store_obj->getId();
             $current_store_profile_image_id = $store_obj->getStoreImage();
             if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                                     ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id   = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path       = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/'. $album_id . '/'. $image_name;
                    } else {
                        $store_profile_image_path       = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/'. $image_name;
                    }
                }
            }
             $store_obj_arr[$current_store_id] = array(
                'id'=>$store_obj->getId(),
                'parentStoreId'=>$store_obj->getParentStoreId(),
                'email'=>$store_obj->getEmail(),
                'description'=>$store_obj->getDescription(),
                'phone'=>$store_obj->getPhone(),
                'businessName'=>$store_obj->getBusinessName(),
                'legalStatus'=>$store_obj->getLegalStatus(),
                'businessType'=>$store_obj->getBusinessType(),
                'paymentStatus'=>$store_obj->getPaymentStatus(),
                'businessCountry'=>$store_obj->getBusinessCountry(),
                'businessRegion'=>$store_obj->getBusinessRegion(),
                'businessCity'=>$store_obj->getBusinessCity(),
                'businessAddress'=>$store_obj->getBusinessAddress(),
                'zip'=>$store_obj->getZip(),
                'province'=>$store_obj->getProvince(),
                'vatNumber'=>$store_obj->getVatNumber(),
                'iban'=>$store_obj->getIban(),
                'mapPlace'=>$store_obj->getMapPlace(),
                'latitude'=>$store_obj->getLatitude(),
                'longitude'=>$store_obj->getLongitude(),
                'name'=>$store_obj->getName(),
                'storeImage'=>$store_obj->getStoreImage(),
                'createdAt'=>$store_obj->getCreatedAt(),
                'isActive'=>$store_obj->getIsActive(),
                'isAllowed'=>$store_obj->getIsAllowed(),
                'original_path'=>$store_profile_image_path,
                'thumb_path'=>$store_profile_image_thumb_path,
                'shop_status'=>$store_obj->getShopStatus(),
            );
            }
        }
       
        return $store_obj_arr;
    }
    
    /**
     * 
     * @param type $user_id
     * @return array
     */
    public function getAppUrlForShop($user_id) {
        $app_url = array();
        // get document manager object
        $em = $this->em;
        $login_app_url   = $this->container->getParameter('login_app_url'); 
        //check for parent store is exists or not.
        $user_store_detail = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findBy(array('userId' => $user_id));
        if($user_store_detail) {
            foreach($user_store_detail as $user_to_store_record) {
                $store_id = $user_to_store_record->getStoreId();
                $store_owner_id = $user_to_store_record->getUserId();
                $store_detail = $em->getRepository('StoreManagerStoreBundle:Store')
                                ->findOneBy(array('id' => $store_id,'isActive'=>1));
                if($store_detail) {
                    $user = $em->getRepository('UserManagerSonataUserBundle:User')->findBy(array('id'=>$store_owner_id));
                    if($user) {
                        $md5_password = $user[0]->getPassword();
                        $vat_number = $store_detail->getVatNumber();
                        $login_url = "$login_app_url" . urlencode(base64_encode($vat_number . '|' . $md5_password));
                        $url_to_return = '<a class="btn btn-primary" href="javascript:void(location.href=\'' . $login_url . '\');" onclick="Popup=window.open(this.href, \'Popup\', \'toolbar=no,status=no,menubar=no,scrollbars=yes,resizable=no, width=800,height=600,left=screen.width/2,top=screen.height/2\'); return false;" target="_blank">APP SHOP</a>';
                        $app_url[] = $url_to_return;
                    }else{
                        continue;
                    }
                }
            }
        }else {
            return $app_url;
        }
        
        return $app_url;
    }
    
    /**
     * finding the user object with user profile.
     * @param array $user_id
     * @return array
     */
   public function MultipleUserObjectService($user_ids)
   { 
        $user_info = array();
        if (!empty($user_ids)) {
            $em = $this->em;
            $user_object = $em->getRepository('UserManagerSonataUserBundle:User')->getMultiUserObject($user_ids);
            if (!$user_object) {
             return $user_info ;
            }
            
            //getting profgile image id.
            $user_profile_media_ids = array_map(function($user_object_data) {
                return "{$user_object_data->getProfileImg()}";
                }, $user_object);
                
            //getting user cover image.
            $user_cover_media_ids = array_map(function($user_object_data) {
                return "{$user_object_data->getCoverImg()}";
                }, $user_object);
                
            //user profile image id array.
            $user_with_profile_media = array_diff($user_profile_media_ids, array('',0)); //remove blank info from array.
            //user cover image array.
            $user_with_cover_media   = array_diff($user_cover_media_ids, array('', 0)); //remove blank info from array.
            //making the media ids array.
            $user_media_array        = array_unique(array_merge($user_with_profile_media, $user_with_cover_media)); //merge both array..
            
            $dm = $this->dm;
            //finding the user media information..
            $user_media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                                  ->findUserProfileMediaInfo($user_media_array);
            //making the user object..
            foreach($user_object as $user){
                $user_id = $user->getId();
                $profile_image_id = $user->getProfileImg();
                $cover_image_id = $user->getCoverImg();
                $img_path       = '';            
                $img_thumb_path = '';
                $cover_img_path = '';
                $cover_img_thumb_path = '';
                //check for profile image...
                if ($profile_image_id != '' && $profile_image_id != 0) {
                   if  (in_array($profile_image_id, $user_profile_media_ids)) {
                        foreach ($user_media_info as $media_information) {
                            if ($media_information->getId() == $profile_image_id) {
                                $album_id   = $media_information->getAlbumId();
                                $media_name = $media_information->getName();
                                if (!empty($album_id)) {
                                  $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
                                  $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
                                } else {
                                  $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
                                  $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$media_name; 
                                }
                                break;
                            }
                        }
                   }
                }
                //check for cover image..
                if ($cover_image_id != '' && $cover_image_id != 0) {
                    if (in_array($cover_image_id, $user_cover_media_ids)) {
                        foreach ($user_media_info as $cover_media_information) {
                            if ($cover_media_information->getId() == $cover_image_id) {
                                $cover_album_id   = $cover_media_information->getAlbumId();
                                $cover_media_name = $cover_media_information->getName();
                                if (!empty($cover_album_id)) {
                                  $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                                  $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                                } else {
                                  $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_media_name;
                                  $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$cover_media_name; 
                                }
                            break;    
                        }
                    }   
                    }
                }
                $user_info[$user->getId()] = array(
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
                    'profile_image_thumb'=>$img_thumb_path,
                    'cover_image'=>$cover_img_path,
                    'cover_image_thumb'=>$cover_img_thumb_path
                );
            }
        }
        return $user_info;
   }
   
   /**
     * finding the tagged user friends info.
     * @param string $user_ids
     * @return array
     */
    public function getTaggedUserInfo($user_ids)
    { 
        if(trim($user_ids))
        {
            $user_ids = explode(",",$user_ids);
                       
            $em = $this->em;
            $dm = $this->dm;
            
            foreach($user_ids as $user_id){
                $user = $em->getRepository('UserManagerSonataUserBundle:User')->findOneBy(array('id'=>$user_id));
                if (!$user) {
                    //$users_info[] = array();
                } else {
                    
                    $profile_image_id = $user->getProfileImg();
                    $cover_image_id = $user->getCoverImg();
                    $img_path       = '';            
                    $img_thumb_path = '';
                    $cover_img_path = '';
                    $cover_img_thumb_path = '';

                    if (!empty($profile_image_id)) {

                       $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                                     ->find($profile_image_id);
                        if ($media_info) {
                            $album_id   = $media_info->getAlbumId();
                            $media_name = $media_info->getName();
                            if (!empty($album_id)) {
                              $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
                              $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
                            } else {
                              $img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
                              $img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$media_name; 
                            }
                        }
                    }
                    if (!empty($cover_image_id)) {

                       $cover_media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                                     ->find($cover_image_id);
                        if ($cover_media_info) {
                            $cover_album_id   = $cover_media_info->getAlbumId();
                            $cover_media_name = $cover_media_info->getName();
                            if (!empty($cover_album_id)) {
                              $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                              $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                            } else {
                              $cover_img_path       =  $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$cover_media_name;
                              $cover_img_thumb_path =  $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$cover_media_name; 
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
                    'profile_image_thumb'=>$img_thumb_path,
                    'cover_image'=>$cover_img_path,
                    'cover_image_thumb'=>$cover_img_thumb_path,
                    'state' => $user->getState(),
                    'hobbies' => $user->getHobbies(),
                    'relationship' => $user->getRelationship(),
                    'about_me' => $user->getAboutMe(),
                    'city_born' => $user->getCityBorn() );
                    
                    $users_info[] = $user_info;
                }
            }
        } else {
            return array();
        }
        return $users_info;
    }
   
}
