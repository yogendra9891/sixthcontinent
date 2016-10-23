<?php

namespace StoreManager\StoreBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\Storealbum;
use StoreManager\StoreBundle\Document\Affiliation;
#use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use StoreManager\StoreBundle\Entity\StoreJoinNotification;
use Notification\NotificationBundle\Document\UserNotifications;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationShop;

class RestStoreExternalProfileController extends Controller {

     protected $store_media_path = '/uploads/documents/stores/gallery/';
     
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');

        return $jsonContent;
    }

    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
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
     * List user's store
     * @param Request $request
     * @return array;
     */
    public function listpublicuserstoresAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        //$required_parameter = array('user_id', 'store_type');
        $data = array();
        //get store type
        $store_type = (int) 1;
        //get limit size
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getPublicStores($limit_start, $limit_size, $store_type);

        if (!$stores) {
            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        //get record count
        $stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getPublicStoresCount($store_type);

        $final_result = array();
        foreach ($stores as $store_data) {
            $current_store_id = $store_data['id'];
            $current_store_profile_image_id = $store_data['storeImage'];
            //$current_user_id        = $store_data['userId'];
            //get store owner id
            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId' => $current_store_id, 'role' => 15));

            $store_owner_id = $store_obj->getUserId();
            $user_service = $this->get('user_object.service');
            $user_object = $user_service->UserObjectService($store_owner_id);

            $store_data['user_info'] = $user_object;
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            if (!empty($store_data['storeImage'])) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/' . $album_id . '/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/' . $image_name;
                    }
                }
            }
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
            $final_result[] = $store_data;
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result, 'size' => $stores_count));
        echo json_encode($res_data);
        exit();
    }
    
    
    /**
     * View album of a store.
     * @param request object
     * @param json
     */
    public function viewpublicstorealbumsAction(Request $request) {
        //initilise the array
       // $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id','album_id');
        
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        //parameter check end
        
        //get Store id
        $store_id = $de_serialize['store_id'];
        //get album id
        $store_album_id = $de_serialize['album_id'];
        
        //get limit size
        if(isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])){
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        }else {
             $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
             echo json_encode($res_data);
             exit();
        }
        
        
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $store_album_medias = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findBy(array('albumId' => $store_album_id, 'storeId' => $store_id,'mediaStatus'=>1), null, $limit_size, $limit_start);
        $store_album_medias_count = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->getUserAlbumMediaCount($store_album_id, $store_id);
        $media_data = array();
        $count_record = 0;
        if($store_album_medias_count){
             $count_record = $store_album_medias_count;
        }
        if($store_album_medias){
           
            foreach ($store_album_medias as $album_media) {
                $media_id = $album_media->getId();
                $media_name = $album_media->getImageName();
                //  $media_type  = $album_media->getContenttype();
                $mediaPath = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_album_id . '/' . $media_name;
                $thumbDir =  $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/' . $media_name;
                $media_data[] = array('id' => $media_id,
                    'media_name' => $media_name,
                    'media_path' => $mediaPath,
                    'thumb_path' => $thumbDir,
                    'create_on' => $album_media->getCreatedAt(),
                );
            }
        }
            $data = array('media' =>$media_data, 'size' =>$count_record );
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
    }
    
    /**
     * View all album of a store.
     * @param request object
     * @param json
     */
    public function listpublicstorealbumsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
         //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit();   
        }
        //parameter check end
        
        //get Store id
        $store_id = $de_serialize['store_id'];
        
         //get limit size
        if(isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])){
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        }else {
             $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
             echo json_encode($res_data);
             exit(); 
        }
 
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $store_albums = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                ->findBy(array('storeId' => $store_id), null, $limit_size, $limit_start);
        $store_albums_count = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                ->getUserAlbumCount($store_id);
        $album_datas = array();
        $record_count = 0;
        if($store_albums_count){
            $record_count = $store_albums_count;
        }
        if($store_albums){
            
            foreach ($store_albums as $store_album) {
                $album_id = $store_album->getId();
                $album_name = $store_album->getStoreAlbumName();
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $media_dir = $file_location . $this->store_media_path . $store_id . '/original/' . $album_id;
                $thumb_dir = $file_location . $this->store_media_path . $store_id . '/thumb/' . $album_id;

                //count total number of media in particular album
                $album_medias = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->findBy(array('albumId' => $album_id, 'storeId' => $store_id,'mediaStatus'=>1));
                $total_media_in_album = count($album_medias);

                //get featured image of album to make cover image of that album
                $featured_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->findOneBy(array('albumId' => $album_id, 'storeId' => $store_id),array('id'=>'ASC'),1,0);
                if ($featured_image) {
                    $featured_image_name = $featured_image->getImageName();
                    $featured_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $featured_image_name;
                } else {
                    $featured_image_name = '';
                    $featured_thumb_path = '';
                }
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $store_album->getStoreAlbumCreted(),
                    'media_in_album' => $total_media_in_album,
                    'album_featured_image' => $featured_thumb_path
                );
            }
        }
           $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('album' =>$album_datas, 'size'=> $record_count));
            echo json_encode($res_data);
            exit();
    }
    
    
    /**
     * Get store detail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|array
     */
    public function viewpublicstoredetailsAction(Request $request) {
        //initilise the array
        $data = array();
        $store_images = array();
        $store_detail = array();
        $img_path = array();
        $user_info = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        
         //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        
             echo json_encode($res_data);
             exit(); 
        }
        //parameter check end
        
        //get store id
        $store_id = $de_serialize['store_id'];

        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        //get store owner id
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id, 'role' => 15));
        
        if(!$store_obj){
             $res_data = array('code' => 160, 'message' => 'STORE_ID_NOT_VALID', 'data' => $data);
             echo json_encode($res_data);
             exit(); 
        }
        
        $store_owner_id = $store_obj->getUserId();
        

        //get store members
        $store_members = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findBy(array('storeId' => $store_id));

        foreach ($store_members as $store_member) {
            $user_id = $store_member->getUserId();
            $role = $store_member->getRole();
            $user_service           = $this->get('user_object.service');
            $user_object            = $user_service->UserObjectService($user_id);
            $user_object['role']    = $role;
            $user_info[]            = $user_object;
        }

        //get store images
        $store_images = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findBy(array('storeId' => $store_id));
        if (!$store_images) {
            $store_images = array();
        }
        foreach ($store_images as $store_image) {

            $img_path[] = array('image_id' => $store_image->getId(),
                'path' => $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $store_id . "/original/" . $store_image->getimageName(),
                'thumb_path' => $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $store_id . "/thumb/" . $store_image->getimageName(),
                'is_featured' => $store_image->getIsFeatured());
        }
        //get group detail
        $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id, 'isActive'=>1));

        if (!$store_detail) {
            return array('code' => '121', 'message' => 'NO_STORE_FOUND', 'data' => $data);
        }
        $store_id = $store_detail->getId();
        //prepare store info array
        $store_data = array(
            'id'=>$store_id,
            'name'=>$store_detail->getName(),
            'payment_status'=>$store_detail->getPaymentStatus(),
            'business_name'=>$store_detail->getBusinessName(),
            'email'=>$store_detail->getEmail(),
            'description'=>$store_detail->getDescription(),
            'phone'=>$store_detail->getPhone(),
            'legal_status'=>$store_detail->getLegalStatus(),
            'business_type'=>$store_detail->getBusinessType(),
            'business_country'=>$store_detail->getBusinessCountry(),
            'business_region'=>$store_detail->getBusinessRegion(),
            'business_city'=>$store_detail->getBusinessCity(),
            'business_address'=>$store_detail->getBusinessAddress(),
            'zip'=>$store_detail->getZip(),
            'province'=>$store_detail->getProvince(),
            'vat_number'=>$store_detail->getVatNumber(),
            'iban'=>$store_detail->getIban(),
            'map_place'=>$store_detail->getMapPlace(),
            'latitude'=>$store_detail->getLatitude(),
            'longitude'=>$store_detail->getLongitude(),
            'parent_store_id'=>$store_detail->getParentStoreId(), //for parent store
            'is_active'=>(int)$store_detail->getIsActive(),
            'is_allowed'=>(int)$store_detail->getIsAllowed(),
            'created_at'=>$store_detail->getCreatedAt(),
            'owner_id'=>$store_owner_id
        );
        $current_store_profile_image_id = $store_detail->getStoreImage();
        $store_profile_image_path = '';
        $store_profile_image_thumb_path = '';
        $store_profile_image_cover_thumb_path = '';
        if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                                     ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id   = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path             = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path       = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'. $album_id . '/'. $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'.$album_id.'/coverphoto/'. $image_name;
                    } else {
                        $store_profile_image_path             = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path       = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'. $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/'. $image_name;
                    }
                }
        }
        $store_data['profile_image_original'] = $store_profile_image_path;
        $store_data['profile_image_thumb']    = $store_profile_image_thumb_path;
        $store_data['cover_image_path']       = $store_profile_image_cover_thumb_path;
         
        $store_data['members']    = $user_info; //assign the members info
        $store_data['media_info'] = $img_path;  //assign the media info.
 
        //get store revenue
        $stores_revenue = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getShopsRevenue($store_id);
        $store_data['revenue']       = $stores_revenue;
             
      
        //get store affliater
        $store_affiliater = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
                ->findOneBy(array('shopId' => $store_id, 'toId' =>$store_owner_id));
        if($store_affiliater){
        //get affliliater is
        $affiliater_id = $store_affiliater->getFromId();
        //get affiliater object
        $user_service           = $this->get('user_object.service');
        $user_object            = $user_service->UserObjectService($affiliater_id);
        } else {
            $user_object = array();
        }
        $store_data['referral_info'] = $user_object;
        //return data
        $resp_msg = array('code' => '101', 'message' => 'SUCCESS', 'data' => $store_data);
        echo json_encode($resp_msg);
        exit();
    }
    
    
    /**
     * Functionality return Post lists of a store
     */
    public function listpublicstorepostsAction(Request $request) {
        die;
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
        //$sender_user = $userManager->findUserBy(array('id' => $userId));

//        if ($sender_user == '') {
//            $data[] = "USER_ID_IS_INVALID";
//        }
        if (!empty($data)) {
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit(); 
        }

        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.

        $is_store_active = $store->getIsActive();
        if ($is_store_active != 1) {
            $res_data = array('code' => 100, 'message' => 'STORE_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }


        //for store ACL    
        $do_action = 0;
        //$group_mask = $this->userStoreRole($storeId, $userId);
        //check for Access Permission
        //only owner and admin can edit the group
//        $allow_group = array('15', '7');
//        if (in_array($group_mask, $allow_group)) {
//            $do_action = 1;
//        }
//
//        if ($do_action == 0) {
//            //for group guest ACL
//            $em = $this->getDoctrine()->getEntityManager();
//            $store = $em
//                    ->getRepository('StoreManagerStoreBundle:Store')
//                    ->findOneBy(array("id" => $storeId)); //@TODO Add group owner id in AND clause.
//
//            $is_store_allow = $store->getIsAllowed();
//            if ($is_store_allow == 1) {
//                $do_action = 1;
//            }
//        }

        //  if($do_action){
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $posts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                ->findBy(array('store_id' => $storeId, 'store_post_status' => 1), array('store_post_created' => 'DESC'), $limit_size, $limit_start);
        $postDetail = array();
        
        $postsCount = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePosts')
                     ->findBy(array('store_id' => $storeId, 'store_post_status' => 1));

        $post_detail = array();
        $totalCount = 0;
        if($postsCount){
            $totalCount = count($postsCount);
        }
        
        
        //get user object
        $user_service = $this->get('user_object.service');
        foreach ($posts as $post) {
            $postId = $post->getId();
            $mediaposts = $this->get('doctrine_mongodb')->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findBy(array('post_id' => $postId, 'media_status'=>1));
            $mediaData = array();
            foreach ($mediaposts as $mediadata) {
                $mediaId = $mediadata->getId();
                $mediaName = $mediadata->getMediaName();
                $mediatype = $mediadata->getMediaType();
                $isfeatured = $mediadata->getIsFeatured();
                $youtube = $mediadata->getYoutube();
                $postId = $post->getId();

                $mediaDir = $this->getS3BaseUri() . $this->post_media_path . $postId . '/' . $mediaName;
                $thumbDir = $this->getS3BaseUri() .'/'. $this->post_media_path_thumb . $postId . '/' . $mediaName;

                $mediaData[] = array('id' => $mediaId,
                    'media_name' => $mediaName,
                    'media_type' => $mediatype,
                    'media_path' => $mediaDir,
                    'media_thumb_path' => $thumbDir,
                    'is_featured' => $isfeatured,
                    'youtube' => $youtube,
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
                            if ($comment_media_type == $this->youtube) { //in case media youtube then media path will be youtube link else our imagelink
                                $comment_media_link = $comment_media_data->getPath();
                                $comment_media_thumb = '';
                            } else {
                                $comment_media_link  = $this->getS3BaseUri() . $this->comment_media_path . $comment_id . '/' . $comment_media_name;
                                $comment_media_thumb = $this->getS3BaseUri() . $this->comment_media_path_thumb . $comment_id . '/' . $comment_media_name;
                            }

                            $comment_media_result[] = array(
                                'id' => $comment_media_id,
                                'media_path' => $comment_media_link,
                                'media_thumb' => $comment_media_thumb,
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
                'link_type' => (int)$post->getLinkType(),
                'media_info' => $mediaData,
                'user_profile' => $user_info,
                'comments' => $comment_data,
                'comment_count' =>$data_count,
             );
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $postDetail,'count'=>$totalCount);
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * search the store by business name of store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int|array
     */
    public function searchexternalstoresAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array();
        $data = array();
        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            $resp_data =  array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//            echo json_encode($resp_data);
//            exit();
//            }
        //parameter check end
        //get limit size
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            $resp_data =  array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
             echo json_encode($resp_data);
            exit();
        }

        //Code repeat end
        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //get store title
           $store_business_name = $de_serialize['business_name'];
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            echo json_encode($res_data);
//            exit();
//        }
        //@TODOcheck for active member
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $search_stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->searchStores($store_business_name, $limit_start, $limit_size);
        if (!$search_stores) {
            $res_data = array('code' => 121, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        //get search store count
        $search_stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->countSearchStores($store_business_name);
        $search_results = array();
        foreach ($search_stores as $search_store) {
            //create search result array
            $store_id = $search_store->getId();

            //get store owner id
            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId' => $store_id, 'role' => 15));

            $store_owner_id = $store_obj->getUserId();
            $user_service = $this->get('user_object.service');
            $user_object = $user_service->UserObjectService($store_owner_id);

            $store_data = array(
                'id' => $store_id,
                'name' => $search_store->getName(),
                'payment_status' => $search_store->getPaymentStatus(),
                'businessName' => $search_store->getBusinessName(),
                'email' => $search_store->getEmail(),
                'description' => $search_store->getDescription(),
                'phone' => $search_store->getPhone(),
                'legal_status' => $search_store->getLegalStatus(),
                'business_type' => $search_store->getBusinessType(),
                'business_country' => $search_store->getBusinessCountry(),
                'business_region' => $search_store->getBusinessRegion(),
                'business_city' => $search_store->getBusinessCity(),
                'business_address' => $search_store->getBusinessAddress(),
                'zip' => $search_store->getZip(),
                'province' => $search_store->getProvince(),
                'vat_number' => $search_store->getVatNumber(),
                'iban' => $search_store->getIban(),
                'map_place' => $search_store->getMapPlace(),
                'latitude' => $search_store->getLatitude(),
                'longitude' => $search_store->getLongitude(),
                'parent_store_id' => $search_store->getParentStoreId(), //for parent store
                'is_active' => $search_store->getIsActive(),
                'is_allowed' => $search_store->getIsAllowed(),
                'created_at' => $search_store->getCreatedAt(),
                'user_info' => $user_object
            );
            $current_store_profile_image_id = $search_store->getStoreImage();
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                    }
                }
            }
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;

            $search_results[] = $store_data;
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $search_results, 'size' => $search_stores_count);
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user_check_enable;
    }

    /**
     * Return get all map stores
     * @param Request $request
     * @return array;
     */
    public function getmapstoresAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //parmeter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array();        
     
        
        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllMapStores();
        
        $count = 0;
        if($stores){
            $count = count($stores);
            $user_service = $this->get('user_object.service');
            foreach($stores as $store_obj){
                $dp_status = $this->checkDPShotsForShop($store_obj['id'], 'dp');
                $store_detail = $user_service->getStoreObjectService($store_obj['id']);
                $data_obj = array(
                    'id' => $store_obj['id'],
                    'parentstoreid' => $store_obj['parentStoreId'],
                    'businessname' => $store_obj['businessName'],
                    'businesstype' => $store_obj['businessType'],
                    'isallowed' => $store_obj['isAllowed'],
                    'paymentstatus' => $store_obj['paymentStatus'],
                    'name' => $store_obj['name'],
                    'latitude' => $store_obj['latitude'],
                    'longitude' => $store_obj['longitude'],
                    'email' => $store_obj['email'],
                    'description' => $store_obj['description'],
                    'phone' => $store_obj['phone'],
                    'legalstatus' => $store_obj['legalStatus'],
                    'businessregion' => $store_obj['businessRegion'],
                    'businesscity' => $store_obj['businessCity'],
                    'businessaddress' => $store_obj['businessAddress'],
                    'zip' => $store_obj['zip'],
                    'province' => $store_obj['province'],
                    'vatnumber' => $store_obj['vatNumber'],
                    'iban' => $store_obj['iban'],
                    'mapplace' => $store_obj['mapPlace'],
                    'storeimage' => $store_obj['storeImage'],
                    'createdat' => $store_obj['createdAt'],
                    'isactive' => $store_obj['isActive'],
                    'shop_status' => $store_obj['shopStatus'],
                    'original_path' => $store_detail['original_path'],
                    'thumb_path' => $store_detail['thumb_path'],
                    'credit_card_status' => $store_obj['creditCardStatus'],
                    'dp_status' => $dp_status,
                    'credit_status' => $dp_status,
                );
                if ($store_obj['contract_id'] != NULL) {
                    $data_obj['credit_card_info'] = array(
                        'contractnumber' => $store_obj['contractNumber'],
                        'registrationtime' => $store_obj['registrationTime'],
                        'mail' => $store_obj['mail'],
                        'pan' => $store_obj['pan'],
                        'brand' => $store_obj['brand'],
                        'expirationpan' => $store_obj['expirationPan'],
                        'alias' => $store_obj['alias'],
                        'nationality' => $store_obj['nationality'],
                        'sessionid' => $store_obj['sessionId'],
                        'producttype' => $store_obj['productType'],
                        'languagecode' => $store_obj['languageCode'],
                        'region' => $store_obj['region'],
                        //'paytype' => $store_obj['paymentType'],
                        'createtime' => $store_obj['createTime']
                    );
                } else {
                    $data_obj['credit_card_info'] = array();
                }
                
                $data[] = $data_obj;
            }
        }
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data,'count'=>$count);
        echo json_encode($res_data);
        exit();
    }
    
    /**
     * function for finding if DP or shots or gift card is available for a shop
     * @param Int $shop_id 
     * @param String $type 'dp' for DP and 's' for Shot, 'gc' for giftcard
     * @param type $user_id
     * @return Int 0/1 based on present or not
     */
    private function checkDPShotsForShop($shop_id, $type) {
        $dm = $this->getDoctrine()->getManager();
        if ($type == 'dp') {
            $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->checkDPForShopExternal($shop_id);
        }
        return $result;
    }
}