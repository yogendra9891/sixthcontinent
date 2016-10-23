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
use StoreManager\StoreBundle\Entity\Storeoffers;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class RestStoreV1Controller extends Controller {

    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $citizen_writer_profile = 23;
    protected $citizen_profile = 22;
    protected $miss_param = '';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $resize_image_width = 200;
    protected $resize_image_height = 200;
    protected $cover_crop_image_width = 902;
    protected $cover_crop_image_height = 320;
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;
    const VAT_MODE = 'live';
    CONST UNDEFINED = "UNDEFINED";
    
    /**
     * Create group
     * @param Request $request
     * @return array;
     */
    public function postCreatestoresAction(Request $request) {
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

        $required_parameter = array('user_id', 'business_name', 'email', 'description', 'phone', 'legal_status', 'business_type', 'business_country', 'business_region', 'business_city',
            'business_address', 'zip', 'province', 'iban', 'map_place', 'latitude', 'longitude', 'name');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => $data);
        }

        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //getting the referal user id
//        $referral_id = $de_serialize['referral_id'];
//        //checking this referal user is exists..
//        $brokeruser = $em
//                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
//                ->findOneBy(array('userId' => $referral_id, 'isActive' => 1));
//
//        if (!($brokeruser)) {
//            $res_data = array('code' => 1016, 'message' => 'BROKER_DOES_NOT_EXIST', 'data' => array());
//            echo json_encode($res_data);
//            exit;
//        }

        if ($referral_id != "") {
                // checking V12 is broker Id or not
                $citizenuser = $em
                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->checkActiveCitizen($referral_id);
                if (!($citizenuser)) {
                    $res_data = array('code' => 1015, 'message' => 'CITIZEN_DOES_NOT_EXIST', 'data' => array());
                    echo json_encode($res_data);
                    exit;
                }
        }
        $data_validate = $this->container->get('export_management.validate_data'); //get validation service object    
        //parameter check end
        //get store data fileds from request
        $store_business_name = $de_serialize['business_name'];
        $store_email = $de_serialize['email'];
        $store_description = $de_serialize['description'];
        $store_phone = $de_serialize['phone'];
        $store_legal_status = $de_serialize['legal_status'];
        $store_business_type = $de_serialize['business_type'];
        $store_business_country = $de_serialize['business_country'];
        $store_business_region = $de_serialize['business_region'];
        $store_business_city = $de_serialize['business_city'];
        $store_business_address = $de_serialize['business_address'];
        $store_zip = $de_serialize['zip'];
        $store_province = $de_serialize['province'];
        //getting the utility service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $store_utility->checkVATFiscal($de_serialize); //check for vat number and fiscal code check
        //add validation
        $de_serialize['vat_number'] = (isset($de_serialize['vat_number'])) ? $de_serialize['vat_number'] : '';
        $de_serialize['fiscal_code'] = (isset($de_serialize['fiscal_code'])) ? $de_serialize['fiscal_code'] : '';
        $store_vat_number = $store_utility->trimString($de_serialize['vat_number']);
        if ($store_vat_number != '') { //if vat number is not blank.
            $valid_vatnumber = $data_validate->checkVatNumber($store_vat_number); //call service
            $vat_mode = '';
            try {
               $vat_mode = strtolower($this->container->getParameter('vat_mode')); //get vat mode from parameter file               
            } catch (\Exception $ex) {

            }
            if((!$valid_vatnumber) && ($vat_mode == self::VAT_MODE)){
                //if not valid vat number
                $res_data = array('code' => 1025, 'message' => 'VAT_NUMBER_NOT_VALID', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }
        $store_iban = $de_serialize['iban'];
        $valid_iban = $data_validate->varfyIban($store_iban);//call service
        if((!$valid_iban) && ($vat_mode == self::VAT_MODE)){
            //if not valid iban number
            $res_data = array('code' => 1026, 'message' => 'IBAN_NOT_VALID', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        $store_map_place = $de_serialize['map_place'];
        $store_latitude = $de_serialize['latitude'];
        $store_longitude = $de_serialize['longitude'];
        $store_name = $de_serialize['name'];

        
        //get store owner id
        $store_owner_id = $de_serialize['user_id'];

        if ($store_owner_id == "") {
            $res_data = array('code' => 1032, 'message' => 'STORE_OWNER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

        if ($store_business_name == "") {
            $res_data = array('code' => 1033, 'message' => 'STORE_BUSINESS_NAME_IS_REQUIRED', 'data' => array());
            return $res_data;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($store_owner_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 1003, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //new fields added on 15 Jan 2015
        $fiscal_code = $de_serialize['fiscal_code'];
        $sale_country = $de_serialize['sale_country'];
        $sale_region = $de_serialize['sale_region'];
        $sale_city = $de_serialize['sale_city'];
        $sale_province = $de_serialize['sale_province'];
        $sale_zip = $de_serialize['sale_zip'];
        $sale_email = $de_serialize['sale_email'];
        $sale_phone_number = $de_serialize['sale_phone_number'];
        $sale_catid = $de_serialize['sale_catid'];
        //as discussed on 23 Jan, subcat id will be optional.
        $sale_subcatid = isset($de_serialize['sale_subcatid'])?$de_serialize['sale_subcatid']:null;
        $sale_description = $de_serialize['sale_description'];
        $sale_address = $de_serialize['sale_address'];
        $sale_map = $de_serialize['sale_map'];
        $repres_fiscal_code = $de_serialize['repres_fiscal_code'];
        $repres_first_name = $de_serialize['repres_first_name'];
        $repres_last_name = $de_serialize['repres_last_name'];
        $repres_place_of_birth = $de_serialize['repres_place_of_birth'];
        $repres_dob = $de_serialize['repres_dob'];
        $repres_email = $de_serialize['repres_email'];
        $repres_phone_number = $de_serialize['repres_phone_number'];
        $repres_address = $de_serialize['repres_address'];
        $repres_province = $de_serialize['repres_province'];
        $repres_city = $de_serialize['repres_city'];
        $repres_zip = $de_serialize['repres_zip'];
        $shop_keyword = $de_serialize['shop_keyword'];
        
        try{
        $btime = new \DateTime($repres_dob);
        if(!$btime){
            $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
       }catch(\Exception $e){
           $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
       }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check vat number should be unique
//        $store_vat = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->findOneBy(array('vatNumber' => $store_vat_number, 'isActive' => 1));
//        $store_vate_number = count($store_vat);
//        if ($store_vate_number > 0) {
//            $res_data = array('code' => 1027, 'message' => 'VAT_NUMBER_ALREADY_EXIST', 'data' => array());
//            echo json_encode($res_data);
//            exit;
//        }
        
        

        //get store object
        $store = new Store();

        //set store fields
        $store->setName($store_name);
        $store->setBusinessName($store_business_name);
        $store->setEmail($store_email);
        $store->setDescription($store_description);
        $store->setPhone($store_phone);
        $store->setLegalStatus($store_legal_status);
        $store->setBusinessType($store_business_type);
        $store->setBusinessCountry($store_business_country);
        $store->setBusinessRegion($store_business_region);
        $store->setBusinessCity($store_business_city);
        $store->setBusinessAddress($store_business_address);
        $store->setZip($store_zip);
        $store->setProvince($store_province);
        $store->setVatNumber($store_vat_number);
        $store->setIban($store_iban);
        $store->setMapPlace($store_map_place);
        $store->setLatitude($store_latitude);
        $store->setLongitude($store_longitude);
        $store->setParentStoreId(0); //for parent store
        $store->setIsActive(1);
        $store->setIsAllowed(1);
        $time = new \DateTime("now");
        $store->setCreatedAt($time);
        $store->setUpdatedAt($time);
        $store->setStoreImage('');
        
        //added new field on 15 jan 2015 
        $store->setFiscalCode($fiscal_code);
        $store->setSaleCountry($sale_country);
        $store->setSaleRegion($sale_region);
        $store->setSaleCity($sale_city);
        $store->setSaleProvince($sale_province);
        $store->setSaleZip($sale_zip);
        $store->setSaleAddress($sale_address);
        $store->setSaleEmail($sale_email);
        $store->setSalePhoneNumber($sale_phone_number);
        $store->setSaleCatid($sale_catid);
        $store->setSaleSubcatid($sale_subcatid);
        $store->setSaleDescription($sale_description);
        $store->setSaleMap($sale_map);
        $store->setRepresFiscalCode($repres_fiscal_code);
        $store->setRepresFirstName($repres_first_name);
        $store->setRepresLastName($repres_last_name);
        $store->setRepresPlaceOfBirth($repres_place_of_birth);
        $store->setRepresDob($btime);
        $store->setRepresEmail($repres_email);
        $store->setRepresPhoneNumber($repres_phone_number);
        $store->setRepresAddress($repres_address);
        $store->setRepresProvince($repres_province);
        $store->setRepresCity($repres_city);
        $store->setRepresZip($repres_zip);
        $store->setShopKeyword($shop_keyword);
        $store->setNewContractStatus(1);
//        if ($referral_id != "") {
//             $store->setAffiliationStatus(1);
//        }
        //or use this service to perform $store->setNewContractStatus(1)
        //$update_store = $this->container->get('store_manager_store.storeUpdate');
        //$updated_store=$update_store->setStoreContractStatus($store_id);

        //persist the store object
        $em->persist($store);
        //save the store info
        $em->flush();
        //assign the user in UserTostore Table
        //get usertostore object
        $usertostore = new UserToStore();
        $usertostore->setStoreId($store->getId());
        $usertostore->setUserId($store_owner_id);
        $usertostore->setChildStoreId(0); // set child store id as 0 for parent store
        $usertostore->setRole(15); //15 for owner
        $time = new \DateTime("now");
        $usertostore->setCreatedAt($time);

        //persist the group object
        $em->persist($usertostore);
        //save the group info
        $em->flush();

//        //check for referal id 
//        if ($referral_id != "") {
//            $em = $this->container->get('doctrine')->getManager();
//            //get citizen user object
//            $broker_affiliation = new AffiliationShop();
//            $time = new \DateTime('now');
//            $broker_affiliation->setFromId($referral_id);
//            $broker_affiliation->setToId($store_owner_id);
//            $broker_affiliation->setShopId($store->getId());
//            $broker_affiliation->setCreatedAt($time);
//            $em->persist($broker_affiliation);
//            $em->flush();
//        }

        //set the shop profile
        $userManager = $this->getUserManager();
        $usershopObj = $userManager->findUserBy(array('id' => $store_owner_id));
        $usershopObj->setStoreProfile(1);
        $userManager->updateUser($usershopObj);

        //get ACL object from service
        $acl_obj = $this->get("store_manager_store.acl");

        $store_owner_acl_code = $acl_obj->getStoreOwnerAclCode();
        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $store_owner_id));

        $aclManager = $this->get('problematic.acl_manager');
        $aclManager->setObjectPermission($store, $store_owner_acl_code, $user_obj);
        //$aclManager->addObjectPermission($group, MaskBuilder::MASK_OWNER);
        // Create store on social bees
        // BLOCK SHOPPING PLUS
        //$this->createStoreOnSocialBees($de_serialize, $store->getId());
        
         //update to applane
        $appalne_data = $de_serialize;
        $appalne_data['store_id'] = $store->getId();

        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.create', $event);
        //end of update
        
        $waiver_service = $this->container->get('card_management.waiver');
        $waiver_service->checkRegistrationWaiverStatus($store->getId()); //for registration fee waiver
        //$waiver_service->checkSubscriptionWaiverStatus($store->getId()); //for subscription fee waiver
        
                //check for referal id 
        if ($referral_id != "") {     
        $store_service = $this->container->get('store_manager_store.storeUpdate');
        //check if store is already affiliated
        $response = $store_service->updateStoreAffiliation($store_owner_id, $referral_id, $store->getId(), 1);
        }
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('store_id' => $store->getId()));      
        echo json_encode($resp_data);
        exit;
    }

//    /**
//     * List user's store
//     * @param Request $request
//     * @return array;
//     */
//    public function postGetuserstoresAction(Request $request) {
//        //initilise the array
//        $data = array();
//        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//
//        //parameter check start
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id', 'store_type');
//        $data = array();
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        //parameter check end
//        //Code repeat end
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //get store type
//        $store_type = (int) $de_serialize['store_type'];
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//        //get limit size
//        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
//            $limit_size = (int) $de_serialize['limit_size'];
//            if ($limit_size == "") {
//                $limit_size = 20;
//            }
//            //get limit offset
//            $limit_start = (int) $de_serialize['limit_start'];
//            if ($limit_start == "") {
//                $limit_start = 0;
//            }
//        } else {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
//        }
//        // get documen manager object
//        $em = $this->getDoctrine()->getManager();
//
//        $stores = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->getStores($user_id, $limit_start, $limit_size, $store_type);
//
//       
//        if (!$stores) {
//            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
//            return $res_data;
//        }
//        //get record count
//        $stores_count = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->getStoresCount($user_id, $store_type);
//
//        $final_result = array();
//        foreach ($stores as $store_data) {
//            $current_store_id = $store_data['id'];
//            $current_store_profile_image_id = $store_data['storeImage'];
//            //$current_user_id        = $store_data['userId'];
//            //get store owner id
//            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
//                    ->findOneBy(array('storeId' => $current_store_id, 'role' => 15));
//
//            $store_owner_id = $store_obj->getUserId();
//            $user_service = $this->get('user_object.service');
//            $user_object = $user_service->UserObjectService($store_owner_id);
//
//            $store_data['user_info'] = $user_object;
//            $store_profile_image_path = '';
//            $store_profile_image_thumb_path = '';
//            if (!empty($store_data['storeImage'])) {
//                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
//                        ->find($current_store_profile_image_id);
//                if ($store_profile_image) {
//                    $album_id = $store_profile_image->getalbumId();
//                    $image_name = $store_profile_image->getimageName();
//                    if (!empty($album_id)) {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $album_id . '/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/' . $album_id . '/' . $image_name;
//                    } else {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/original/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $current_store_id . '/thumb/' . $image_name;
//                    }
//                }
//            }
//            $store_data['profile_image_original'] = $store_profile_image_path;
//            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
//            $final_result[] = $store_data;
//        }
//
//        //check if user is member or owner of any shop.
////        $is_shop_member = 0;
////        $shop_member = $em
////                ->getRepository('StoreManagerStoreBundle:Store')
////                ->checkIfUserHasShop($user_id, $store_type);
////        if($shop_member){
////            $is_shop_member = 1;
////        }
//        
//        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result, 'size' => $stores_count));
//        echo json_encode($res_data);
//        exit();
//    }
    
    
    /**
     * List user's store
     * @param Request $request
     * @return array;
     */
    public function postGetuserstoresAction(Request $request) {
        //initilise the array
      
       echo "postGetuserstoresAction";

       die;
       
        $data = array();
        //get request object
        $bucket_path = $this->getS3BaseUri();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //Code repeat end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get store type
        $store_type = (int) $de_serialize['store_type'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'it';

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
        }
        
        //get store type
        $filter_type = isset($de_serialize['filter_type'])?$de_serialize['filter_type']:0;
        $allowed_array = array(0,1,2);
        if (!in_array($filter_type, $allowed_array)) {
            return array('code' => 100, 'message' => 'INVALID_STORE_FILTER_TYPE', 'data' => $data);
        }
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //getting the citizen income from the shopping plus
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        //logic for getting users friend list
        $friend_name = '';
        $results_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriendsCount($user_id, $friend_name);
        //fire the query in User Repository
        $response = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllFriendsType($user_id, $friend_name, 0, $results_count);
        
        $userIds = array();
        foreach($response as $_result){
            array_push($userIds, $_result['user_id']);
        }
        $friendsIds = array_unique($userIds);
        
        //getting the store list
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getStores($user_id, $limit_start, $limit_size, $store_type,$bucket_path,$filter_type,$citizen_income,$language_code,$friendsIds);
        
        //check if store exist
        if (!$stores) {
            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            return $res_data;
        }
        
        //getting the store ids.
        $store_ids = array_map(function($store) {
            return "{$store['id']}";
        }, $stores);
        
        //call applane service to calculate shop revenue
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_info_store = $applane_service->getUserCreditOnStore($store_ids,$user_id);
        
        //loop for adding the users credit in store array
        foreach($stores as $store) {
            $store['total_credie_available'] = isset($user_info_store[$store['id']]) ? $user_info_store[$store['id']] : 0;
            $store_result[] = $store;
        }
        
        $stores = $store_result;
         //getting the posts sender ids.
        $store_user_ids = array_map(function($store) {
            return "{$store['userId']}";
        }, $stores);
        
        //getting the users unique array
        $users_array = array_unique($store_user_ids);
        
        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        //array for final result
        $final_result = array();
        //loop for adding the users info into the final array
        foreach($stores as $store) {
            $store['user_info'] = isset($users_object_array[$store['userId']]) ? $users_object_array[$store['userId']] : array();
            $final_result[] = $store;
        }

        //get record count
        $stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getStoresCount($user_id, $store_type);
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result, 'size' => $stores_count));
        echo json_encode($res_data);
        exit();
    }

    /**
     * Edit shop
     * @param Request $request
     * @return array;
     */
    public function postEditstoresAction(Request $request) {
       
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
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id', 'business_name', 'email', 'description', 'phone', 'legal_status', 'business_type', 'business_country', 'business_region', 'business_city',
            'business_address', 'zip', 'province', 'iban', 'map_place', 'latitude', 'longitude', 'name');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //allow_access set default value 0.
        $de_serialize['allow_access'] = (isset($de_serialize['allow_access']) ? $de_serialize['allow_access'] : 0);

        //getting the referal user id
  //      $referral_id = $de_serialize['referral_id'];
        //checking this referal user is exists..
        $em = $this->getDoctrine()->getManager();
//        $brokeruser = $em
//                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
//                ->findOneBy(array('userId' => $referral_id));
//
//        if (!($brokeruser)) {
//            $res_data = array('code' => '137', 'message' => 'BROKER_DOES_NOT_EXIT', 'data' => array());
//            echo json_encode($res_data);
//            exit;
//        }
        //get store id
        $data_validate = $this->container->get('export_management.validate_data'); //get validation service object   
        $store_id = $de_serialize['store_id'];

        //get store data fileds from request
        $store_business_name = $de_serialize['business_name'];
        $store_email = $de_serialize['email'];
        $store_description = $de_serialize['description'];
        $store_phone = $de_serialize['phone'];
        $store_legal_status = $de_serialize['legal_status'];
        $store_business_type = $de_serialize['business_type'];
        $store_business_country = $de_serialize['business_country'];
        $store_business_region = $de_serialize['business_region'];
        $store_business_city = $de_serialize['business_city'];
        $store_business_address = $de_serialize['business_address'];
        $store_zip = $de_serialize['zip'];
        $store_province = $de_serialize['province'];
        //getting the utility service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $store_utility->checkVATFiscal($de_serialize); //check for vat number and fiscal code check
        $de_serialize['vat_number']  = (isset($de_serialize['vat_number'])) ? $de_serialize['vat_number'] : '';
        $de_serialize['fiscal_code'] = (isset($de_serialize['fiscal_code'])) ? $de_serialize['fiscal_code'] : '';
        //add validation
        $store_vat_number = $store_utility->trimString($de_serialize['vat_number']);
        if ($store_vat_number != '') { //if vat number is not blank.
            $valid_vatnumber = $data_validate->checkVatNumber($store_vat_number); //call service
            $vat_mode = '';
            try {
               $vat_mode = strtolower($this->container->getParameter('vat_mode')); //get vat mode from parameter file               
            } catch (\Exception $ex) {

            }
            if((!$valid_vatnumber) && ($vat_mode == self::VAT_MODE)){
                //if not valid vat number and vat mode is live
                $res_data = array('code' => 1025, 'message' => 'VAT_NUMBER_NOT_VALID', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }
        $store_iban = $de_serialize['iban'];
        $valid_iban = $data_validate->varfyIban($store_iban);//call service
        if((!$valid_iban) && ($vat_mode == self::VAT_MODE)){
            //if not valid iban number and vat mode is live
            $res_data = array('code' => 1026, 'message' => 'IBAN_NOT_VALID', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
       
        $store_map_place = $de_serialize['map_place'];
        $store_latitude = $de_serialize['latitude'];
        $store_longitude = $de_serialize['longitude'];
        $store_name = $de_serialize['name'];

        //new fields added on 15 Jan 2015
        $fiscal_code = $de_serialize['fiscal_code'];
        $sale_country = $de_serialize['sale_country'];
        $sale_region = $de_serialize['sale_region'];
        $sale_city = $de_serialize['sale_city'];
        $sale_province = $de_serialize['sale_province'];
        $sale_zip = $de_serialize['sale_zip'];
        $sale_email = $de_serialize['sale_email'];
        $sale_phone_number = $de_serialize['sale_phone_number'];
        $sale_catid = $de_serialize['sale_catid'];
        $sale_subcatid = $de_serialize['sale_subcatid'];
        $sale_description = $de_serialize['sale_description'];
        $sale_address = $de_serialize['sale_address'];
        $sale_map = $de_serialize['sale_map'];
        $repres_fiscal_code = $de_serialize['repres_fiscal_code'];
        $repres_first_name = $de_serialize['repres_first_name'];
        $repres_last_name = $de_serialize['repres_last_name'];
        $repres_place_of_birth = $de_serialize['repres_place_of_birth'];
        $repres_dob = $de_serialize['repres_dob'];
        $repres_email = $de_serialize['repres_email'];
        $repres_phone_number = $de_serialize['repres_phone_number'];
        $repres_address = $de_serialize['repres_address'];
        $repres_province = $de_serialize['repres_province'];
        $repres_city = $de_serialize['repres_city'];
        $repres_zip = $de_serialize['repres_zip'];
        $shop_keyword = $de_serialize['shop_keyword'];
        
        try{
        $btime = new \DateTime($repres_dob);
        if(!$btime){
            $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
       }catch(\Exception $e){
           $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
       }
       
        
        $em = $this->container->get('doctrine')->getManager();
        // checking vat number of shop
        $store_vat = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id));

        $current_vat = $store_vat->getVatNumber();
        // if entered vat number is not equal to shop vat number
//        if ($current_vat != $store_vat_number) {
//            //check vat number should be unique
//            $store_vat = $em
//                    ->getRepository('StoreManagerStoreBundle:Store')
//                    ->findOneBy(array('vatNumber' => $store_vat_number, 'isActive' => 1));
//            $store_vate_number = count($store_vat);
//            if ($store_vate_number > 0) {
//                $res_data = array('code' => 1027, 'message' => 'VAT_NUMBER_ALREADY_EXIST', 'data' => array());
//                echo json_encode($res_data);
//                exit;
//            }
//        }

        //allow store forum access
        $store_allow_access = $de_serialize['allow_access'];
        $forum_access_array = array('0', '1');
        if (!in_array($store_allow_access, $forum_access_array)) {
            $resp_data = array('code' => 1034, 'message' => 'INVALID_STORE_FORUM_TYPE', 'data' => array());
            return $resp_data;
        }
        //get store owner id
        $store_owner_id = $de_serialize['user_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($store_owner_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 1003, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get User Role
        $mask_id = $this->userStoreRole($store_id, $store_owner_id);

        //check for Access Permission
        //only owner can edit the group
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id, 'isActive' => 1));
        //if store not found
        if (!$store) {
            $res_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //set store object
        $store->setName($store_name);
        $store->setBusinessName($store_business_name);
        $store->setEmail($store_email);
        $store->setDescription($store_description);
        $store->setPhone($store_phone);
        $store->setLegalStatus($store_legal_status);
        $store->setBusinessType($store_business_type);
        $store->setBusinessCountry($store_business_country);
        $store->setBusinessRegion($store_business_region);
        $store->setBusinessCity($store_business_city);
        $store->setBusinessAddress($store_business_address);
        $store->setZip($store_zip);
        $store->setProvince($store_province);
        $store->setVatNumber($store_vat_number);
        $store->setIban($store_iban);
        $store->setMapPlace($store_map_place);
        $store->setLatitude($store_latitude);
        $store->setLongitude($store_longitude);
        $store->setIsActive(1);
        $store->setIsAllowed($store_allow_access);
        $time = new \DateTime("now");
        $store->setUpdatedAt($time); //set the update time..
        
        //added new field on 15 jan 2015
        $store->setFiscalCode($fiscal_code);
        $store->setSaleCountry($sale_country);
        $store->setSaleRegion($sale_region);
        $store->setSaleCity($sale_city);
        $store->setSaleProvince($sale_province);
        $store->setSaleZip($sale_zip);
        $store->setSaleAddress($sale_address);
        $store->setSaleEmail($sale_email);
        $store->setSalePhoneNumber($sale_phone_number);
        $store->setSaleCatid($sale_catid);
        $store->setSaleSubcatid($sale_subcatid);
        $store->setSaleDescription($sale_description);
        $store->setSaleMap($sale_map);
        $store->setRepresFiscalCode($repres_fiscal_code);
        $store->setRepresFirstName($repres_first_name);
        $store->setRepresLastName($repres_last_name);
        $store->setRepresPlaceOfBirth($repres_place_of_birth);
        $store->setRepresDob($btime);
        $store->setRepresEmail($repres_email);
        $store->setRepresPhoneNumber($repres_phone_number);
        $store->setRepresAddress($repres_address);
        $store->setRepresProvince($repres_province);
        $store->setRepresCity($repres_city);
        $store->setRepresZip($repres_zip);
        $store->setShopKeyword($shop_keyword);
        //$store->setNewContractStatus(1);
        
        //or use this service to perform $store->setNewContractStatus(1)
        //$update_store = $this->container->get('store_manager_store.storeUpdate');
        //$updated_store=$update_store->setStoreContractStatus($store_id);
        //create store on applane
        
        //persist the group object
        $em->persist($store);
        //save the group info
        $em->flush();
        //create store on applane
        //update to applane
        $appalne_data = $de_serialize;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.update', $event);
        //end of update
        // Create store on social bees
        // BLOCK SHOPPING PLUS
        //$this->editStoreOnSocialBees($de_serialize, $store_id);
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Upload the image for store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int|string|array
     */
    public function postUploadstoremediaalbumsAction(Request $request) {
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

        $required_parameter = array('user_id', 'store_id', 'post_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get store id
        $store_id = $de_serialize['store_id'];



        //get post type
        $post_type = $de_serialize['post_type'];

        //get user id
        $store_owner_id = $de_serialize['user_id'];

        //get album_id =
        $store_album_id = (isset($de_serialize['album_id']) ? $de_serialize['album_id'] : '');

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($store_owner_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get User Role
        $mask_id = $this->userStoreRole($store_id, $store_owner_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        if (isset($_FILES['store_media'])) {
            //check if file is valid or not
            $images_upload = $_FILES['store_media'];
            $file_error = $this->checkFileType($images_upload);
            if ($file_error == 1) {
                $resp_data = array('code' => '128', 'message' => 'INVALID_IMAGE', 'data' => array());
                return $resp_data;
            }
        }

        //get the image name clean service..
        $clean_name = $this->get('clean_name_object.service');

        if ($post_type == 0) {
            $i = 0;
            $store_media_id = 0;
            $image_upload = $this->get('amazan_upload_object.service');
            foreach ($_FILES['store_media']['tmp_name'] as $key => $tmp_name) {
                $original_media_name = $_FILES['store_media']['name'][$key];
                $album_thumb_image_width  = $this->resize_image_width;
                $album_thumb_image_height = $this->resize_image_height;
                if ($original_media_name) {
                    if ($_FILES['store_media']['name'][$key] != "") {
                        $file_name = time() . strtolower(str_replace(' ', '', $_FILES['store_media']['name'][$key]));
                        $file_name = $clean_name->cleanString($file_name); //rename the file name, clean the image name.
                        $file_tmp = $_FILES['store_media']['tmp_name'][$key];
                        $file_type = $_FILES['store_media']['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];                        
                        
                        $image_info = getimagesize($_FILES['store_media']['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1];
                        //call service to get image type. Basis of this we save data 3,2,1 in db
                        $image_type_service = $this->get('user_object.service');
                        $image_type         = (int)$image_type_service->CheckImageType($orignal_mediaWidth,$original_mediaHeight,$album_thumb_image_width,$album_thumb_image_height);
                        
                        $em = $this->getDoctrine()->getManager();
                        $storeMedia = new StoreMedia();
                        if (!empty($store_album_id)) {
                            //get store members
                            //checking if album exits for this login user in album database then
                            //upload media in that album
                            $em = $this->getDoctrine()->getManager();
                            $store_album = $em
                                    ->getRepository('StoreManagerStoreBundle:Storealbum')
                                    ->findBy(array('id' => $store_album_id, 'storeId' => $store_id));
                            // get entity manager object
                            if ($store_album) {
                                $storeMedia->setStoreId($store_id);
                                $storeMedia->setImageName($file_name);
                                $storeMedia->setAlbumId($store_album_id);
                                $storeMedia->setMediaStatus(0);
                                $storeMedia->setImageType((int)$image_type);
                                $time = new \DateTime("now");
                                $storeMedia->setCreatedAt($time);
                                if ($i == 0) {
                                    $storeMedia->setIsFeatured(1);
                                } else {
                                    $storeMedia->setIsFeatured(0);
                                }
                                $em->persist($storeMedia);
                                $em->flush();
                                $store_media_id = $storeMedia->getId();
                            } else {
                                return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
                            }
                        } else {

                            $storeMedia->setStoreId($store_id);
                            $storeMedia->setImageName($file_name);
                            $storeMedia->setAlbumId('');
                            $time = new \DateTime("now");
                            $storeMedia->setCreatedAt($time);
                            $storeMedia->setImageType($image_type);
                            if ($i == 0) {
                                $storeMedia->setIsFeatured(1);
                            } else {
                                $storeMedia->setIsFeatured(0);
                            }
                            $em->persist($storeMedia);
                            $em->flush();
                        }
                        $i++;
                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_album_media_path').$store_id.'/original/'.$store_album_id.'/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('store_album_media_path') .$store_id.'/original/'.$store_album_id.'/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_album_media_path') .$store_id.'/thumb/'.$store_album_id.'/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_album_media_path') .$store_id.'/thumb/'.$store_album_id.'/';
                        $s3_post_media_path = $this->container->getParameter('s3_store_album_media_path'). $store_id . "/original";
                        $s3_post_media_thumb_path = $this->container->getParameter('s3_store_album_media_path'). $store_id . "/thumb";
                        $image_upload->imageUploadService($_FILES['store_media'],$key,$store_id,'store_album',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_post_media_path,$s3_post_media_thumb_path,$store_album_id);
                    }
                }
            }

            //get store images
            $store_images = $em
                    ->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->findOneBy(array('id' => $store_media_id));

            $comment_media_name = $comment_media_link = $comment_media_thumb = $album_image_type = ''; //initialize blank variables.
            if ($store_images) {
                $comment_media_name = $store_images->getImageName();
                $album_image_type = $store_images->getImageType();
                $comment_media_link = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_album_id . '/' . $comment_media_name;
                $comment_media_thumb = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/' . $comment_media_name;
            }

            //sending the current media and post data.
            $data = array(
                'media_id' => $store_media_id,
                'media_link' => $comment_media_link,
                'media_thumb_link' => $comment_media_thumb,
                'image_type' =>$album_image_type
            );

            $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        } else {
            //get media id array
            $media_id_arr = $object_info->media_id;
            //get store images
            $em = $this->getDoctrine()->getManager();
            if (!empty($media_id_arr)) {
                $store_images = $em
                        ->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->publishAlbumMedia($media_id_arr);
            }
            $all_publish_media = $em
                    ->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->getAllPublishAlbumMedia($media_id_arr);
            $result_array = array();
            if ($all_publish_media) {
                foreach ($all_publish_media as $record) {
                    $comment_media_name = $comment_media_link = $comment_media_thumb = $album_image_type =''; //initialize blank variables.
                    if ($record) {
                        $comment_media_name = $record->getImageName();
                        $album_image_type = $record->getImageType();
                        $comment_media_link = $this->getS3BaseUri() . $this->store_media_path . $record->getStoreId() . '/original/' . $record->getAlbumId() . '/' . $comment_media_name;
                        $comment_media_thumb = $this->getS3BaseUri() . $this->store_media_path . $record->getStoreId() . '/thumb/' . $record->getAlbumId() . '/' . $comment_media_name;
                    }
                    $result_array = array(
                        'media_id' => $record->getId(),
                        'media_link' => $comment_media_link,
                        'media_thumb_link' => $comment_media_thumb,
                        'image_type' =>$album_image_type
                    );
                    $data[] = $result_array;
                }
            }
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
    }

    /**
     * Get store detail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|array
     */
//    public function postStoredetailsAction(Request $request) {
//        //initilise the array
//        $data = array();
//        $store_images = array();
//        $store_detail = array();
//        $img_path = array();
//        $user_info = array();
//        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//        //Code repeat end
//        //parameter check start
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id', 'store_id');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        //parameter check end
//        //get store id
//        $store_id = $de_serialize['store_id'];
//
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//
//        // get entity manager object
//        $em = $this->getDoctrine()->getManager();
//
//        //get store owner id
//        $store_obj = $em
//                ->getRepository('StoreManagerStoreBundle:UserToStore')
//                ->findOneBy(array('storeId' => $store_id, 'role' => 15));
//
//        if (!$store_obj) {
//            $res_data = array('code' => 160, 'message' => 'STORE_ID_NOT_VALID', 'data' => $data);
//            return $res_data;
//        }
//
//        $store_owner_id = $store_obj->getUserId();
//
//
//        //get store members
//        $store_members = $em
//                ->getRepository('StoreManagerStoreBundle:UserToStore')
//                ->findBy(array('storeId' => $store_id));
//
//        foreach ($store_members as $store_member) {
//            $user_id = $store_member->getUserId();
//            $role = $store_member->getRole();
//            $user_service = $this->get('user_object.service');
//            $user_object = $user_service->UserObjectService($user_id);
//            $user_object['role'] = $role;
//            $user_info[] = $user_object;
//        }
//
//        //get store images
//        $store_images = $em
//                ->getRepository('StoreManagerStoreBundle:StoreMedia')
//                ->findBy(array('storeId' => $store_id));
//        if (!$store_images) {
//            $store_images = array();
//        }
//        foreach ($store_images as $store_image) {
//
//            $img_path[] = array('image_id' => $store_image->getId(),
//                'path' => $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $store_id . "/original/" . $store_image->getimageName(),
//                'thumb_path' => $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $store_id . "/thumb/" . $store_image->getimageName(),
//                'is_featured' => $store_image->getIsFeatured());
//        }
//        //get group detail
//        $store_detail = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->findOneBy(array('id' => $store_id, 'isActive' => 1));
//
//        if (!$store_detail) {
//            return array('code' => '121', 'message' => 'NO_STORE_FOUND', 'data' => $data);
//        }
//        $store_id = $store_detail->getId();
//        //prepare store info array
//        $store_data = array(
//            'id' => $store_id,
//            'name' => $store_detail->getName(),
//            'payment_status' => $store_detail->getPaymentStatus(),
//            'business_name' => $store_detail->getBusinessName(),
//            'email' => $store_detail->getEmail(),
//            'description' => $store_detail->getDescription(),
//            'phone' => $store_detail->getPhone(),
//            'legal_status' => $store_detail->getLegalStatus(),
//            'business_type' => $store_detail->getBusinessType(),
//            'business_country' => $store_detail->getBusinessCountry(),
//            'business_region' => $store_detail->getBusinessRegion(),
//            'business_city' => $store_detail->getBusinessCity(),
//            'business_address' => $store_detail->getBusinessAddress(),
//            'zip' => $store_detail->getZip(),
//            'province' => $store_detail->getProvince(),
//            'vat_number' => $store_detail->getVatNumber(),
//            'iban' => $store_detail->getIban(),
//            'map_place' => $store_detail->getMapPlace(),
//            'latitude' => $store_detail->getLatitude(),
//            'longitude' => $store_detail->getLongitude(),
//            'parent_store_id' => $store_detail->getParentStoreId(), //for parent store
//            'is_active' => (int) $store_detail->getIsActive(),
//            'is_allowed' => (int) $store_detail->getIsAllowed(),
//            'created_at' => $store_detail->getCreatedAt(),
//            'owner_id' => $store_owner_id,
//            'shop_status' => $store_detail->getShopStatus(),
//            'credit_card_status' => $store_detail->getCreditCardStatus()
//        );
//        $current_store_profile_image_id = $store_detail->getStoreImage();
//        $store_profile_image_path = '';
//        $store_profile_image_thumb_path = '';
//        $store_profile_image_cover_thumb_path = '';
//        if (!empty($current_store_profile_image_id)) {
//            $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
//                    ->find($current_store_profile_image_id);
//            if ($store_profile_image) {
//                $album_id = $store_profile_image->getalbumId();
//                $image_name = $store_profile_image->getimageName();
//                if (!empty($album_id)) {
//                    $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
//                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
//                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/coverphoto/' . $image_name;
//                } else {
//                    $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
//                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
//                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $image_name;
//                }
//            }
//        }
//        $store_data['profile_image_original'] = $store_profile_image_path;
//        $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
//        $store_data['cover_image_path'] = $store_profile_image_cover_thumb_path;
//
//        $store_data['members'] = $user_info; //assign the members info
//        $store_data['media_info'] = $img_path;  //assign the media info.
//        //get store revenue
//        $stores_revenue = $em
//                ->getRepository('StoreManagerStoreBundle:Transactionshop')
//                ->getShopsRevenue($store_id);
//        $store_data['revenue'] = $stores_revenue;
//
//
//        //get store affliater
//        $store_affiliater = $em
//                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
//                ->findOneBy(array('shopId' => $store_id, 'toId' => $store_owner_id));
//        if ($store_affiliater) {
//            //get affliliater is
//            $affiliater_id = $store_affiliater->getFromId();
//            //get affiliater object
//            $user_service = $this->get('user_object.service');
//            $user_object = $user_service->UserObjectService($affiliater_id);
//        } else {
//            $user_object = array();
//        }
//        $store_data['referral_info'] = $user_object;
//        //return data
//        $resp_msg = array('code' => '101', 'message' => 'SUCCESS', 'data' => $store_data);
//        echo json_encode($resp_msg);
//        exit();
//    }
    
    
    /**
     * Get store detail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|array
     */
    public function postStoredetailsAction(Request $request) {
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

        $required_parameter = array('user_id', 'store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get store id
        $store_id = $de_serialize['store_id'];

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        //get store owner id
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id, 'role' => 15));

        if (!$store_obj) {
            $res_data = array('code' => 160, 'message' => 'STORE_ID_NOT_VALID', 'data' => $data);
            return $res_data;
        }

        $store_owner_id = $store_obj->getUserId();

        //get store members
        $store_members = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findBy(array('storeId' => $store_id));

        //getting the posts sender ids.
        $store_members_ids = array_map(function($store) {
            return "{$store->getUserId()}";
        }, $store_members);
        
        //getting the users unique array
        $users_array = array_unique($store_members_ids);
        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $user_object = array();
        foreach ($store_members as $store_member) {
            $user_id = $store_member->getUserId();
            $role = $store_member->getRole();
            $user_object = isset($users_object_array[$user_id]) ? $users_object_array[$user_id] : array();
            $user_object['role'] = $role;
            $user_info[] = $user_object;
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
                'is_featured' => $store_image->getIsFeatured(),
                'image_type' =>$store_image->getImageType()
                    );
        } 
        //get group detail
        $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getStoreDetail((int) $de_serialize['user_id'], $store_id);
        
        if (!$store_detail) {
            return array('code' => '121', 'message' => 'NO_STORE_FOUND', 'data' => $data);
        }
        
        /** calculate the pending amount of shop **/
        $store_pending_amount = 0;
        $payment_status = $store_detail[0]['payment_status'];
        $store_pending_amount = $em
                               ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                               ->getShopPendingAmount($store_id); 
        $recurring_pending_amount = 0;
        $total_pending_amount = 0;
        if($store_pending_amount>0) {
            $recurring_pending_amount = $store_pending_amount/1000000;
        }
        $reg_fee_newshop        = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');
        $reg_fee = 0;
        
        /**logic for old_shop parameter **/
        $ctore_compare_date = new \DateTime('2014-11-14');
        if($store_detail[0]['created_at'] < $ctore_compare_date) {
            $old_shop = 1;
            $reg_fee = $reg_fee_oldshop;
        } else {
            $old_shop = 0;
            $reg_fee = $reg_fee_newshop;
        }
        
        $reg_fee_euro = $reg_fee/100;
        if($payment_status == 1) {
            $total_pending_amount = $recurring_pending_amount; 
        }else if($payment_status == 0) {
            $total_pending_amount = $reg_fee_euro + $recurring_pending_amount;
        }
        
        
        $store_id = $store_detail[0]['id'];

        //prepare store info array
        $store_data = array(
            'id' => $store_id,
            'name' => $store_detail[0]['name'],
            'payment_status' => $store_detail[0]['payment_status'],
            'business_name' => $store_detail[0]['business_name'],
            'email' => $store_detail[0]['email'],
            'description' => $store_detail[0]['description'],
            'phone' => $store_detail[0]['phone'],
            'legal_status' => $store_detail[0]['legal_status'],
            'business_type' => $store_detail[0]['business_type'],
            'business_country' => $store_detail[0]['business_country'],
            'business_region' => $store_detail[0]['business_region'],
            'business_city' => $store_detail[0]['business_city'],
            'business_address' => $store_detail[0]['business_address'],
            'zip' => $store_detail[0]['zip'],
            'province' => $store_detail[0]['province'],
            'vat_number' => $store_detail[0]['vat_number'],
            'iban' => $store_detail[0]['iban'],
            'map_place' => $store_detail[0]['map_place'],
            'latitude' => $store_detail[0]['latitude'],
            'longitude' => $store_detail[0]['longitude'],
            'parent_store_id' => $store_detail[0]['parent_store_id'], //for parent store
            'is_active' => (int) $store_detail[0]['is_active'],
            'is_allowed' => (int) $store_detail[0]['is_allowed'],
            'created_at' => $store_detail[0]['created_at'],
            'owner_id' => $store_owner_id,
            'shop_status' => $store_detail[0]['shop_status'],
            'credit_card_status' => $store_detail[0]['credit_card_status'],
            'pending_amount' =>$total_pending_amount,
            'old_shop' =>$old_shop,
            'fiscal_code' => $store_detail[0]['fiscal_code'],
            'sale_country' => $store_detail[0]['sale_country'],
            'sale_region' => $store_detail[0]['sale_region'],
            'sale_city' => $store_detail[0]['sale_city'],
            'sale_province' => $store_detail[0]['sale_province'],
            'sale_zip' => $store_detail[0]['sale_zip'],
            'sale_email' => $store_detail[0]['sale_email'],
            'sale_phone_number' => $store_detail[0]['sale_phone_number'],
            'sale_catid' => $store_detail[0]['sale_catid'],
            'sale_subcatid' => $store_detail[0]['sale_subcatid'],
            'sale_description' => $store_detail[0]['sale_description'],
            'sale_address' => $store_detail[0]['sale_address'],
            'sale_map' => $store_detail[0]['sale_map'],
            'repres_fiscal_code' => $store_detail[0]['repres_fiscal_code'],
            'repres_first_name' => $store_detail[0]['repres_first_name'],
            'repres_last_name' => $store_detail[0]['repres_last_name'],
            'repres_place_of_birth' => $store_detail[0]['repres_place_of_birth'],
            'repres_dob' => $store_detail[0]['repres_dob'],
            'repres_email' => $store_detail[0]['repres_email'],
            'repres_phone_number' => $store_detail[0]['repres_phone_number'],
            'repres_address' => $store_detail[0]['repres_address'],
            'repres_province' => $store_detail[0]['repres_province'],
            'repres_city' => $store_detail[0]['repres_city'],
            'repres_zip' => $store_detail[0]['repres_zip'],
            'shop_keyword' => $store_detail[0]['shop_keyword'],
            'shop_rating' => $store_detail[0]['avg_rate'],
            'vote_count' => $store_detail[0]['vote_count'],
            'is_fav' => $store_detail[0]['is_fav']
        );
        $current_store_profile_image_id = $store_detail[0]['store_image'];
        $store_profile_image_path = '';
        $store_profile_image_thumb_path = '';
        $store_profile_image_cover_thumb_path = '';
        if (!empty($current_store_profile_image_id)) {
            $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->find($current_store_profile_image_id);
            if ($store_profile_image) {
                $album_id = $store_profile_image->getalbumId();
                $image_name = $store_profile_image->getimageName();
                if (!empty($album_id)) {
                    $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/coverphoto/' . $image_name;
                } else {
                    $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $image_name;
                }
            }
        }
        $store_data['profile_image_original'] = $store_profile_image_path;
        $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
        $store_data['cover_image_path'] = $store_profile_image_cover_thumb_path;

        $store_data['members'] = $user_info; //assign the members info
        $store_data['media_info'] = $img_path;  //assign the media info.
        //get store revenue
        /*$stores_revenue = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getShopsRevenue($store_id);
         */
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $stores_revenue = $applane_service->getShopRevenueFromApplane($store_id);
        
        $store_data['revenue'] = $stores_revenue;


        //get store affliater
        $store_affiliater = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
                ->findOneBy(array('shopId' => $store_id, 'toId' => $store_owner_id));
        if ($store_affiliater) {
            //get affliliater is
            $affiliater_id = $store_affiliater->getFromId();
            //get affiliater object
            $user_service = $this->get('user_object.service');
            $user_object = $user_service->UserObjectService($affiliater_id);
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
     * Set the featured image for the store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postSetstorefeaturedimagesAction(Request $request) {
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
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id', 'image_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get store id
        $store_id = $de_serialize['store_id'];

        //get image id
        $image_id = $de_serialize['image_id'];

        //get user login id
        $store_owner_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($store_owner_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get User Role
        $mask_id = $this->userStoreRole($store_id, $store_owner_id);

        //check for Access Permission
        //only owner has the permission
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }


        $em = $this->getDoctrine()->getManager();

        //remove the featured image from the store id
        $storeMedia = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->removeFeaturedImage($store_id);

        $storeMedia = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findOneBy(array('id' => $image_id));
        if (!$storeMedia) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        $storeMedia->setIsFeatured(1);
        $em->persist($storeMedia);
        $em->flush();
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Store owner can send store join invitation
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int|string|array
     */
    public function postInvitestoreusersAction(Request $request) {
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
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id', 'friend_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get store id
        $store_id = $de_serialize['store_id'];
        //get friend id
        $friend_id = (int) $de_serialize['friend_id'];

        //user can not send the request to himself
        if ($user_id == $friend_id) {
            $resp_data = array('code' => '100', 'message' => 'ERROR_OCCURED', 'data' => array());
            return $resp_data;
        }
        //only group owner or group admin can invite the user
        //get User Role
        $mask_id = $this->userStoreRole($store_id, $user_id);
        //check for Access Permission. Only owner can send the store join invitation.
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //end to check permission
        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check if invitation has already sent
        //check if already invited
        $check_join = $em
                ->getRepository('StoreManagerStoreBundle:StoreJoinNotification')
                ->findOneBy(array('receiverId' => $friend_id, 'storeId' => $store_id));

        if (count($check_join) > 0) {
            $res_data = array('code' => 118, 'message' => 'REQUEST_IS_PENDING_FOR_USER_APPROVAL', 'data' => $data);
            return $res_data;
        }
        //get invitaion object
        $joinNotification = new StoreJoinNotification();

        $joinNotification->setSenderId($user_id);
        $joinNotification->setReceiverId($friend_id);
        $joinNotification->setStoreId($store_id);
        $time = new \DateTime("now");
        $joinNotification->setCreatedAt($time);

        $em->persist($joinNotification);
        //save the group info
        $em->flush();

        //check if store is parent or not
        $store_response = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id));

        $store_name = $store_response->getName();
        if ($store_name == '') {
            $store_name = $store_response->getBusinessName();
        }
        
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;
        
        $from_id = $user_id;
        $to_id = $friend_id;
        //get store profile thumb..
        $current_store_profile_image_id = $store_response->getStoreImage();
        $store_profile_image_thumb_path = '';
        if (!empty($current_store_profile_image_id)) {
            $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->find($current_store_profile_image_id);
            if ($store_profile_image) {
                $album_id = $store_profile_image->getalbumId();
                $image_name = $store_profile_image->getimageName();
                if (!empty($album_id)) {
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                } else {
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                }
            }
        }
        
        //for mail template..
        $email_template_service =  $this->container->get('email_template.service'); //email template service.
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
        $shop_profile_url     = $this->container->getParameter('shop_profile_url'); //shop profile url
        $postService = $this->container->get('post_detail.service');
        $sender = $postService->getUserData($from_id);
        $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
        
        $receiver = $postService->getUserData($to_id, true);
        //get locale
        $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        $href = $angular_app_hostname.$shop_profile_url.'/'.$store_id;
        $mail_text = sprintf($lang_array['STORE_JOIN_REQUEST_TEXT'], ucwords($sender_name), ucwords($store_name));
        $bodyData =  $mail_text."<br><br>".$email_template_service->getLinkForMail($href,$locale); //making the link html from service
        
        $mail_sub  = sprintf($lang_array['STORE_JOIN_REQUEST_SUBJECT']);
        $mail_body = sprintf($lang_array['STORE_JOIN_REQUEST_BODY'], ucwords($sender_name), ucwords($store_name));
        
        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $store_profile_image_thumb_path, 'STORE_JOIN_REQUEST');
        //return success
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * List notifications
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
//    public function postStorenotificationsAction(Request $request) {
//        //initilise the array
//        $data = array();
//        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//        //Code repeat end
//        //parameter check start
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id');
//        $data = array();
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        //parameter check end
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//        //@TODOcheck for active member
//        // get entity manager object
//        $em = $this->getDoctrine()->getManager();
//        $store_notifications = $em
//                ->getRepository('StoreManagerStoreBundle:StoreJoinNotification')
//                ->getStoreJoinNotifications($user_id);
//
//        if (count($store_notifications) == 0) {
//            //no notification found
//            //return success
//            $res_data = array('code' => 119, 'message' => 'NO_NOTIFICATION', 'data' => $data);
//            return $res_data;
//        }
//        $final_data = array();
//        foreach ($store_notifications as $notification_data) {
//            $current_store_profile_image_id = $notification_data['storeImage'];
//            $store_id = $notification_data['store_id'];
//            $store_profile_image_path = '';
//            $store_profile_image_thumb_path = '';
//            if (!empty($current_store_profile_image_id)) {
//                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
//                        ->find($current_store_profile_image_id);
//                if ($store_profile_image) {
//                    $album_id = $store_profile_image->getalbumId();
//                    $image_name = $store_profile_image->getimageName();
//                    if (!empty($album_id)) {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
//                    } else {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
//                    }
//                }
//            }
//
//            $notification_data['profile_image_original'] = $store_profile_image_path;
//            $notification_data['profile_image_thumb'] = $store_profile_image_thumb_path;
//            $final_data[] = $notification_data;
//        }
//        $notifications = $final_data;
//        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $final_data);
//        echo json_encode($res_data);
//        exit();
//    }
    
    
    /**
     * List notifications
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postStorenotificationsAction(Request $request) {
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
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //@TODOcheck for active member
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $store_notifications = $em
                ->getRepository('StoreManagerStoreBundle:StoreJoinNotification')
                ->getStoreJoinNotifications($user_id);

        if (count($store_notifications) == 0) {
            //no notification found
            //return success
            $res_data = array('code' => 119, 'message' => 'NO_NOTIFICATION', 'data' => $data);
            return $res_data;
        }
        $final_data = array();
        foreach ($store_notifications as $notification_data) {
            $current_store_profile_image_id = $notification_data['storeImage'];
            $store_id = $notification_data['store_id'];
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $notification_data['imageName'];
                if ($store_profile_image) {
                    $album_id = $notification_data['albumId'];
                    $image_name = $notification_data['imageName'];
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                    }
                }
            }

            $notification_data['profile_image_original'] = $store_profile_image_path;
            $notification_data['profile_image_thumb'] = $store_profile_image_thumb_path;
            $final_data[] = $notification_data;
        }
        $notifications = $final_data;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $final_data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Get notification list
     * @param Request $request
     */
    public function postResponsestorejoinsAction(Request $request) {
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
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'request_id', 'store_id', 'response');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get request id
        $request_id = $de_serialize['request_id'];


        //get Store id
        $store_id = $de_serialize['store_id'];
        //get sender id
        //$sender_id = $de_serialize['sender_id'];
        //response 
        //1 for accept. 2 for deny
        $response = $de_serialize['response'];
        //check for response
        $allow_res = array('1', '2');

        if (!in_array($response, $allow_res)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //get store admin id
        $store_admin = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id, 'role' => '15'));

        if (!$store_admin) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        $store_admin_id = $store_admin->getUserId();
        if ($store_admin_id == $user_id) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }
        $notification_from_id = $user_id;
        $notification_to_id = $store_admin_id;

        //get store detail
        $store_detail = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => $store_id));
        //get store profile thumb..
        $current_store_profile_image_id = $store_detail->getStoreImage();
        $store_profile_image_thumb_path = '';
        if (!empty($current_store_profile_image_id)) {
            $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                    ->find($current_store_profile_image_id);
            if ($store_profile_image) {
                $album_id = $store_profile_image->getalbumId();
                $image_name = $store_profile_image->getimageName();
                if (!empty($album_id)) {
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                } else {
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                }
            }
        }
        
        $email_template_service =  $this->container->get('email_template.service'); //email template service.
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
        $shop_profile_url       = $this->container->getParameter('shop_profile_url'); //shop profile url
        $thumb_path   = $store_profile_image_thumb_path;
        
        //response request
        if ($response == 2) {
            //reject the request
            $store_response = $em
                    ->getRepository('StoreManagerStoreBundle:StoreJoinNotification')
                    ->findOneBy(array('id' => $request_id));

            $em->remove($store_response);
            $em->flush();

            //update in notification table
            $msgtype = 'shop_response';
            $msg = 'reject';
            $add_notification = $this->saveUserNotification($notification_from_id, $notification_to_id, $store_id, $msgtype, $msg);

            //send mail
            $from_id = $user_id;
            $to_id = $store_admin_id;

            $store_id = $store_detail->getId();
            $store_name = $store_detail->getName();
            //get shop name
            if ($store_name == "") {
                $store_name = $store_detail->getBusinessName();
            }
            $postService = $this->container->get('post_detail.service');
            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
            
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            
            $href   = $angular_app_hostname.$shop_profile_url.'/'.$store_id;
            $link   = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                    
            $pushText = sprintf($lang_array['PUSH_STORE_REJECT_REQUEST'], $sender_name, $store_name);
            $postService->sendUserNotifications($notification_from_id, $notification_to_id, $msgtype, $msg, $store_id, false, true, $pushText);
            
            $mail_sub  = sprintf($lang_array['STORE_REJECT_REQUEST_SUBJECT']);
            $mail_body = sprintf($lang_array['STORE_REJECT_REQUEST_BODY'], ucwords($sender_name), ucwords($store_name));
            $mail_text = sprintf($lang_array['STORE_REJECT_REQUEST_TEXT'], ucwords($sender_name), ucwords($store_name));
            $bodyData = $mail_text."<br><br>".$link;
            
            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'STORE_REJECT_REQUEST');

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        if ($response == 1) {
            //accept the request
            //assign the user in UserToStore Table
            //get usertogroup object
            $usertostore = new UserToStore();
            $usertostore->setUserId($user_id);
            $usertostore->setStoreId($store_id);

            //get admin ACL role
            //get ACL object from service
            $acl_obj = $this->get("store_manager_store.acl");
            $store_admin_acl_code = $acl_obj->getStoreAdminAclCode();
            $usertostore->setRole($store_admin_acl_code);
            $usertostore->setChildStoreId(0); // set child store id as 0 for parent store
            $time = new \DateTime("now");
            $usertostore->setCreatedAt($time);
            //persist the group object
            $em->persist($usertostore);
            //save the group info
            $em->flush();
            //remove the notification
            $store_response = $em
                    ->getRepository('StoreManagerStoreBundle:StoreJoinNotification')
                    ->findOneBy(array('id' => $request_id));

            $em->remove($store_response);
            $em->flush();

            //add the user as admin of the store
            $assigned_user_id = $user_id;
            $this->assignStoreAdminRole($store_id, $assigned_user_id);

            //update in notification table
            $msgtype = 'shop_response';
            $msg = 'accept';
            $add_notification = $this->saveUserNotification($notification_from_id, $notification_to_id, $store_id, $msgtype, $msg);

            //send mail
            $from_id = $user_id;
            $to_id = $store_admin_id;

            $store_id = $store_detail->getId();
            $store_name = $store_detail->getName();
            //get shop name
            if ($store_name == "") {
                $store_name = $store_detail->getBusinessName();
            }
            
            $postService = $this->container->get('post_detail.service');
            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
 
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            
            $href   = $angular_app_hostname.$shop_profile_url.'/'.$store_id;
            $link   = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                        
            $pushText = sprintf($lang_array['PUSH_STORE_ACCEPT_REQUEST'], $sender_name, $store_name);
            $postService->sendUserNotifications($notification_from_id, $notification_to_id, $msgtype, $msg, $store_id, false, true, $pushText);
            
            //for mail template..
            $mail_sub  = sprintf($lang_array['STORE_ACCEPT_REQUEST_SUBJECT']);
            $mail_body = sprintf($lang_array['STORE_ACCEPT_REQUEST_BODY'], ucwords($sender_name), ucwords($store_name));
            $mail_text = sprintf($lang_array['STORE_ACCEPT_REQUEST_TEXT'], ucwords($sender_name), ucwords($store_name));
            $bodyData = $mail_text."<br><br>".$link;
            
            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'STORE_ACCEPT_REQUEST');

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Delete the store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postDeletestoresAction(Request $request) {
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
        //request object end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id', 'store_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];

        //get Store id
        $store_id = $de_serialize['store_id'];

        //get store type
        $store_type = $de_serialize['store_type'];
        $allow_store_type = array('1', '2');
        if (!in_array($store_type, $allow_store_type)) {
            $resp_data = array('code' => '124', 'message' => 'INVALID_STORE_TYPE', 'data' => array());
            return $resp_data;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get User Role
        $mask_id = $this->userStoreRole($store_id, $user_id);
        //check for Access Permission. only owner can delete the store
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //check if store is parent or not

        $store_response = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id));

        if (!$store_response) {
            $resp_data = array('code' => '125', 'message' => 'INVALID_STORE', 'data' => array());
            return $resp_data;
        }
        //check if store is parent or not
        $parent_store_id = $store_response->getParentStoreId();
        //check for the parent and child store id. If user select child id then 
        //parent store id can not be 0.
        if ($parent_store_id == 0 && $store_type == 2) {
            $resp_data = array('code' => '100', 'message' => 'ERROR_OCCURED', 'data' => array());
            return $resp_data;
        }

        if ($parent_store_id != 0 && $store_type == 1) {
            $resp_data = array('code' => '100', 'message' => 'ERROR_OCCURED', 'data' => array());
            return $resp_data;
        }

        $store_response->setIsActive(0); //digital delete the store
        $em->persist($store_response);
        //save the store info
        $em->flush();

        //digital delete the child stores also. Only if the parent store removed
        if ($store_type != 2) {
            $store_response = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->deleteChildStores($store_id);
        }
        //code for delete the shop on shopping plus(deactivate)
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        // BLOCK SHOPPING PLUS
        //$shoppingplus_obj->changeStoreStatusOnShoppingPlus($store_id, 'D');

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Delete the store image
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postDeletestoresmediasAction(Request $request) {
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
        //request object end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id', 'image_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];

        //get Store id
        $store_id = $de_serialize['store_id'];

        //get Store id
        $image_id = $de_serialize['image_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get User Role
        $mask_id = $this->userStoreRole($store_id, $user_id);
        //check for Access Permission. only owner can delete the store
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $store_media = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findOneBy(array('id' => $image_id));
        if (!$store_media) {
            $res_data = array('code' => 127, 'message' => 'NO_IMAGE_FOUND', 'data' => $data);
            return $res_data;
        }

        $em->remove($store_media);
        $em->flush();

        //@TODO also remove the image from folder 
        /*         * * remove corresponding media from folder also***** */
        $mediaName = $store_media->getImageName();
        $document_root = $request->server->get('DOCUMENT_ROOT');
        $BasePath = $request->getBasePath();
        $file_location = $document_root . $BasePath; // getting sample directory path
        $mediaFileLocation = $file_location . '/uploads/documents/store/gallery/' . $store_id . '/';
        $mediaToBeDeleted = $mediaFileLocation . $mediaName;

        // Commenting these line becauase images are not present on s3 Amazon server.
        //Since in push images folder are not used
        /* if ($mediaToBeDeleted) {
          //  $deleted = unlink($mediaToBeDeleted);
          if ($deleted) {
          return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
          } else {
          return array('code' => 100, 'message' => 'IMAGE_FILE_UNABLE_TO_DELETE', 'data' => $data);
          }
          }
         */

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * search the store by business name of store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int|array
     */
//    public function postSearchstoresAction(Request $request) {
//        //initilise the array
//        $data = array();
//        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//
//        //parameter check start
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id');
//        $data = array();
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//        }
//        //parameter check end
//        //get limit size
//        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
//            $limit_size = (int) $de_serialize['limit_size'];
//            if ($limit_size == "") {
//                $limit_size = 20;
//            }
//            //get limit offset
//            $limit_start = (int) $de_serialize['limit_start'];
//            if ($limit_start == "") {
//                $limit_start = 0;
//            }
//        } else {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
//        }
//
//        //Code repeat end
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //get store title
//        $store_business_name = $de_serialize['business_name'];
//
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//        //@TODOcheck for active member
//        // get entity manager object
//        $em = $this->getDoctrine()->getManager();
//        $search_stores = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->searchStores($store_business_name, $limit_start, $limit_size);
//        if (!$search_stores) {
//            $res_data = array('code' => 121, 'message' => 'NO_STORE_FOUND', 'data' => $data);
//            return $res_data;
//        }
//
//        //get search store count
//        $search_stores_count = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->countSearchStores($store_business_name);
//        $search_results = array();
//        foreach ($search_stores as $search_store) {
//            //create search result array
//            $store_id = $search_store->getId();
//
//            //get store owner id
//            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
//                    ->findOneBy(array('storeId' => $store_id, 'role' => 15));
//
//            $store_owner_id = $store_obj->getUserId();
//            $user_service = $this->get('user_object.service');
//            $user_object = $user_service->UserObjectService($store_owner_id);
//
//            $store_data = array(
//                'id' => $store_id,
//                'name' => $search_store->getName(),
//                'payment_status' => $search_store->getPaymentStatus(),
//                'businessName' => $search_store->getBusinessName(),
//                'email' => $search_store->getEmail(),
//                'description' => $search_store->getDescription(),
//                'phone' => $search_store->getPhone(),
//                'legal_status' => $search_store->getLegalStatus(),
//                'business_type' => $search_store->getBusinessType(),
//                'business_country' => $search_store->getBusinessCountry(),
//                'business_region' => $search_store->getBusinessRegion(),
//                'business_city' => $search_store->getBusinessCity(),
//                'business_address' => $search_store->getBusinessAddress(),
//                'zip' => $search_store->getZip(),
//                'province' => $search_store->getProvince(),
//                'vat_number' => $search_store->getVatNumber(),
//                'iban' => $search_store->getIban(),
//                'map_place' => $search_store->getMapPlace(),
//                'latitude' => $search_store->getLatitude(),
//                'longitude' => $search_store->getLongitude(),
//                'parent_store_id' => $search_store->getParentStoreId(), //for parent store
//                'is_active' => $search_store->getIsActive(),
//                'is_allowed' => $search_store->getIsAllowed(),
//                'created_at' => $search_store->getCreatedAt(),
//                'shop_status' => $search_store->getShopStatus(),
//                'credit_card_status' => $search_store->getCreditCardStatus(),
//                'user_info' => $user_object
//            );
//            $current_store_profile_image_id = $search_store->getStoreImage();
//            $store_profile_image_path = '';
//            $store_profile_image_thumb_path = '';
//            if (!empty($current_store_profile_image_id)) {
//                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
//                        ->find($current_store_profile_image_id);
//                if ($store_profile_image) {
//                    $album_id = $store_profile_image->getalbumId();
//                    $image_name = $store_profile_image->getimageName();
//                    if (!empty($album_id)) {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
//                    } else {
//                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
//                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
//                    }
//                }
//            }
//            $store_data['profile_image_original'] = $store_profile_image_path;
//            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
//
//            $search_results[] = $store_data;
//        }
//
//        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $search_results, 'size' => $search_stores_count);
//        echo json_encode($res_data);
//        exit();
//    }
    
    
    /**
     * search the store by business name of store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int|array
     */
    public function postSearchstoresAction(Request $request) {
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER limit', 'data' => $data);
        }

        //Code repeat end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get store title
        $store_business_name = isset($de_serialize['business_name'])?$de_serialize['business_name']:'';

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        
        $bucket_path = $this->getS3BaseUri();
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'it';
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        
  
        
        //@TODOcheck for active member
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        //logic for getting users friend list
        $friend_name = '';
        $results_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriendsCount($user_id, $friend_name);
        //fire the query in User Repository
        $response = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllFriendsType($user_id, $friend_name, 0, $results_count);
        
        $userIds = array();
        foreach($response as $_result){
            array_push($userIds, $_result['user_id']);
        }
        $friendsIds = array_unique($userIds);
        $search_stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->searchStores($user_id,$store_business_name, $limit_start, $limit_size,$bucket_path,$language_code,$citizen_income,$friendsIds);
        if (!$search_stores) {
            $res_data = array('code' => 121, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            return $res_data;
        }
        
        //getting the store ids.
        $store_ids = array_map(function($store) {
            return "{$store['id']}";
        }, $search_stores);
        
        //call applane service to calculate shop revenue
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_info_store = $applane_service->getUserCreditOnStore($store_ids,$user_id);
        
         //getting the posts sender ids.
        $store_user_ids = array_map(function($search_stores) {
            return "{$search_stores['userId']}";
        }, $search_stores);
        
        //get search store count
        $search_stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->countSearchStores($store_business_name);
                
         //getting the users unique array
        $users_array = array_unique($store_user_ids);
        
        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($users_array);
        $search_results = array();
        $user_object = array();
        foreach ($search_stores as $search_store) {
            //create search result array
            $store_id = $search_store['id'];
            $store_owner_id = $search_store['userId'];
            $user_object = isset($users_object_array[$store_owner_id]) ? $users_object_array[$store_owner_id] : array();

            $store_data = array(
                'id' => $store_id,
                'name' => $search_store['name'],
                'payment_status' => $search_store['paymentStatus'],
                'businessName' => $search_store['businessName'],
                'email' => $search_store['email'],
                'description' => $search_store['description'],
                'phone' => $search_store['phone'],
                'legal_status' => $search_store['legalStatus'],
                'business_type' => $search_store['businessType'],
                'business_country' => $search_store['businessCountry'],
                'business_region' => $search_store['businessRegion'],
                'businessCity' => $search_store['businessCity'],
                'business_address' => $search_store['businessAddress'],
                'zip' => $search_store['zip'],
                'province' => $search_store['province'],
                'vat_number' => $search_store['vatNumber'],
                'iban' => $search_store['iban'],
                'mapPlace' => $search_store['mapPlace'],
                'latitude' => $search_store['latitude'],
                'longitude' => $search_store['longitude'],
                'parent_store_id' => $search_store['parentStoreId'], //for parent store
                'is_active' => $search_store['isActive'],
                'is_allowed' => $search_store['isAllowed'],
                'created_at' => $search_store['createdAt'],
                'shop_status' => $search_store['shopStatus'],
                'credit_card_status' => $search_store['creditCardStatus'],
                'catogory_id' => $search_store['catogory_id'],
                'sub_category_id' => $search_store['sub_category_id'],
                'shop_category' => $search_store['shop_category'],
                'shopRating' => $search_store['shop_rating'],
                'vote_count' => $search_store['vote_count'],
                'shop_sub_category' => $search_store['shop_sub_category'],
                'credit_available' => $search_store['credit_available'],
                'friend_count' => $search_store['friend_count'],
                'user_info' => $user_object
            );
            $current_store_profile_image_id = $search_store['storeImage'];
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            if (!empty($current_store_profile_image_id)) {
                    $album_id = $search_store['albumId'];
                    $image_name = $search_store['imageName'];
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                    }
            }
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
            $store_data['total_credie_available'] = isset($user_info_store[$store_id]) ? $user_info_store[$store_id] : 0;

            $search_results[] = $store_data;
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $search_results, 'size' => $search_stores_count);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Assign group role
     * @param int $store_id
     * @param int $user_id
     */
    public function assignStoreAdminRole($store_id, $user_id) {
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id)); //@TODO Add group owner id in AND clause.
        //get ACL code for admin
        //get ACL object from service
        $acl_obj = $this->get("store_manager_store.acl");
        $store_acl_code = $acl_obj->getStoreAdminAclCode();
        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $user_id));
        $aclManager = $this->get('problematic.acl_manager');
        $aclManager->setObjectPermission($store, $store_acl_code, $user_obj);
        return true;
    }

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
     * Get User role for group
     * @param int $store_id
     * @param int $user_id
     * @return int
     */
    public function userStoreRole($store_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id)); //@TODO Add group owner id in AND clause.
        //if group not found
        if (!$store) {
            return $mask;
        }
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
     * Checking for file extension
     * @param $images_upload
     * @return int $file_error
     */
    private function checkFileType($images_upload) {

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
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $store_id, $album_id) {
        //get image thumb width
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_height;
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/" . $album_id . '/';
        //   $path_to_thumbs_directory = $thumb_dir;
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

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
            //check if new width is less than minimum width
            if ($new_width < $thumb_width) {
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
            }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
            //check if new height is less than minimum height
            if ($new_height < $thumb_height) {
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
            }
        }

        $nx = $new_width;
        $ny = $new_height;

        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
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
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $store_id, $album_id = null) {

        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/" . $album_id . '/';
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


        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/original";
        if ($album_id != "") {
            $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/original/" . $album_id;
        }
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename) {
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
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }

    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $store_id, $album_id) {

        //image crop size
        $crop_image_width = $this->crop_image_width;
        $crop_image_height = $this->crop_image_width;

        $imagename = $filename;
        //get the thumb_crop directory path
        $path_to_thumbs_crop_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/" . $album_id . '/';
        $filename = $path_to_thumbs_crop_directory . $filename;

        //get the thumb directory path
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb/" . $album_id . '/';

        //get the image center 
        $path_to_thumbs_center_image_path = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb/" . $album_id . '/' . $imagename;

        if (preg_match('/[.](jpg)$/', $imagename)) {
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
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
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
        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/thumb/" . $album_id;
        $image_local_path = $path_to_thumbs_center_image_path;
        $url = $this->s3imageUpload($s3imagepath, $image_local_path, $imagename);
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
     * Change citizen role to citizen writter
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postAffiliatesAction(Request $request) {
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
        //request object end  
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get Store id
        $store_id = $de_serialize['store_id'];
        // get store cartetion date
        // $store_affi_date = $de_serialize['affi_date'];
        // $time = new \DateTime("now");
        $time = date("Y-m-d");
        $store_affi_date = $time;

        $start_date_month = date('Y-m-01'); // hard-coded '01' for first day
        $end_date_month = date('Y-m-t');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $affi = new Affiliation();
        $affi->setUserId($user_id);
        $affi->setStoreId($store_id);
        $affi->setAffiliationDate($store_affi_date);
        $dm->persist($affi);
        //save the store info
        $dm->flush();

        $from = new \DateTime($start_date_month);
        $to = new \DateTime($end_date_month);

        $affiliations = $this->get('doctrine_mongodb')->getRepository('StoreManagerStoreBundle:Affiliation')
                ->countAffiStore($user_id, $from, $to);
        $num_affiliate = count($affiliations);
        if ($num_affiliate > 3) {
            // since every registered user will be by default citizen
            // citizen profile type is =22
            $citizen_profile_type = 22;
            $em = $this->getDoctrine()->getManager();

            $citizen_writer = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                    ->findOneBy(array('userId' => $user_id, 'roleId' => $citizen_profile_type));

            if ($citizen_writer) {
                $citizen_writer->getRoleId();
                $id = $citizen_writer->getId();

                $citizen_writer_to_updated = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->find($id);

                //update profile type from 22 to 23 
                //i.e citizen becomes citizen writer
                $citizen_writer_to_updated->setRoleId(23);
                //persist the group object
                $em->persist($citizen_writer_to_updated);
                //save the profile info
                $em->flush();
            }

            $res_data = array('code' => 101, 'message' => 'YOU_ARE_UPDATE_TO_CITIZEN_WRITTER', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        $res_data = array('code' => 101, 'message' => 'THANKS_TO_BECOME_AFFILIATED_MEMBER', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Change citizen writter role  to citizen
     * @param request object
     * @return json string
     */
    public function postCitizenwrittertocitizensAction(Request $request) {
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

        $writers_em = $this->getDoctrine()->getManager();
        $citizen_writer_users = $writers_em->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findBy(array('profileType' => $this->citizen_writer_profile));
        $user_arr = array();
        foreach ($citizen_writer_users as $value) {
            $arr_to_write = array('id' => $value->getId(), 'uid' => $value->getUserId());
            $user_arr[] = $arr_to_write;
        }
        $start_date_month = date('Y-m-01'); // hard-coded '01' for first day
        $end_date_month = date('Y-m-t');
        $from = new \DateTime($start_date_month);
        $to = new \DateTime($end_date_month);

        foreach ($user_arr as $uid) {

            $affiliations = $this->get('doctrine_mongodb')->getRepository('StoreManagerStoreBundle:Affiliation')
                    ->countAffiStore($uid['uid'], $from, $to);
            $num_affiliate = count($affiliations);
            if ($num_affiliate < 4) {
                $em_citizen = $this->getDoctrine()->getManager();
                $citizen_writer_to_updated = $em_citizen->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                        ->find($uid['id']);
                if ($citizen_writer_to_updated) {

                    $citizen_writer_to_updated->setProfileType($this->citizen_profile);
                    //persist the group object
                    $em_citizen->persist($citizen_writer_to_updated);
                    //save the profile info
                    $em_citizen->flush();
                }
            }
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Create album for store
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postCreatestorealbumsAction(Request $request) {
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
        //request object end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id', 'album_name');

        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get Store id
        $store_id = $de_serialize['store_id'];
        //get album name
        $album_name = $de_serialize['album_name'];
        $album_desc = "";
        if (isset($de_serialize['album_desc'])) {
            //get album desc
            $album_desc = $de_serialize['album_desc'];
        }
        //getting the privacy setting array
        $privacy_setting_constant = $this->get('privacy_setting_object.service');
        // $privacy_setting_constant_result = $privacy_setting_constant->PrivacySettingService();
        $privacy_setting_constant_result = $privacy_setting_constant->AlbumPrivacySettingService();
        
        //default privacy setting for album is public
        $privacy_setting = $object_info->privacy_setting = (isset($object_info->privacy_setting) ? ($object_info->privacy_setting) : 3);
       
        if (!in_array($privacy_setting, $privacy_setting_constant_result)) {
            return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
        }

        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $store_album = new StoreAlbum();
        $store_album->setStoreAlbumName($album_name);
        $store_album->setStoreAlbumDesc($album_desc);
        $store_album->setStoreId($store_id);
        $store_album->setPrivacySetting($privacy_setting);
        $time = new \DateTime("now");
        $store_album->setStoreAlbumCreted($time);
        $store_album->setStoreAlbumUpdated($time);
        $em->persist($store_album);
        $em->flush();
        $album_id = $store_album->getId();
        $document_root = $request->server->get('DOCUMENT_ROOT');
        $BasePath = $request->getBasePath();
        $file_location = $document_root . $BasePath; // getting sample directory path
        $store_album_location = $file_location . '/uploads/documents/store/gallery/' . $store_id . '/original/' . $album_id;
        $thumbnail_album_location = $file_location . '/uploads/documents/store/gallery/' . $store_id . '/thumb/' . $album_id;
        if (!file_exists($store_album_location)) {
            \mkdir($store_album_location, 0777, true);
            \mkdir($thumbnail_album_location, 0777, true);
            $res_data = array('code' => 101, 'message' => 'STORE_ALBUM_IS_CREATED_SUCCESSFULLY', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 100, 'failure' => 'STORE_ALBUM_IS_NOT_CREATED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * deleting the album of store.
     * @param request object
     * @param json
     */
    public function postDeletestorealbumsAction(Request $request) {
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

        $required_parameter = array('store_id', 'album_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //  $user_id     =  $de_serialize['user_id']; 
        //get Album id
        $store_album_id = $de_serialize['album_id'];
        //get Store id
        $store_id = $de_serialize['store_id'];
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
        $store_album = $em
                ->getRepository('StoreManagerStoreBundle:Storealbum')
                ->findOneBy(array('id' => $store_album_id, 'storeId' => $store_id));
        if (!$store_album) {
            return array('code' => 100, 'message' => 'STORE_ALBUM_DOES_NOT_EXISTS', 'data' => $data);
        }
        //remove from storealbum table
        $em->remove($store_album);
        $em->flush();

        /*         * * remove corresponding storemedia**** */
        $album_media = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->deleteAlbumFromStore($store_album_id);
        if ($album_media) {
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $BasePath = $request->getBasePath();
            $file_location = $document_root . $BasePath; // getting sample directory path

            $image_album_location = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/original/' . $store_album_id . '/';
            $thumbnail_album_location = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/thumb/' . $store_album_id . '/';
            // Commenting these line becauase images are not present on s3 Amazon server.
            //Since in push images folder are not used
            if (file_exists($image_album_location)) {
                // array_map('unlink', glob($image_album_location . '/*'));
                // rmdir($image_album_location);
            }
            if (file_exists($thumbnail_album_location)) {
                // array_map('unlink', glob($thumbnail_album_location . '/*'));
                // rmdir($thumbnail_album_location);
            }
        }
        $res_data = array('code' => 101, 'message' => 'ALBUM_IS_DELETED_SUCCESSFULLY', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * deleting the media of album of user.
     * @param request object
     * @param json
     */
    public function postDeletestorealbummediasAction(Request $request) {
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
        //request object end
        //get user login id
        // $user_id = (int) $de_serialize['user_id'];
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id', 'media_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get Store id
        $store_id = $de_serialize['store_id'];

        //get Store id
        $media_id = $de_serialize['media_id'];
        //get album id
        $store_album_id = (isset($de_serialize['album_id']) ? $de_serialize['album_id'] : '');

        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $store_album_media = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->find($media_id);
        if (!$store_album_media) {
            $res_data = array('code' => 127, 'message' => 'NO_IMAGE_FOUND', 'data' => $data);
            return $res_data;
        }

        $em->remove($store_album_media);
        $em->flush();

        //@TODO also remove the image from folder 
        /*         * * remove corresponding media from folder also***** */
        $mediaName = $store_album_media->getImageName();
        $document_root = $request->server->get('DOCUMENT_ROOT');
        $BasePath = $request->getBasePath();
        $file_location = $document_root . $BasePath; // getting sample directory path
        if ($store_album_id) {
            $mediaToBeDeleted = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/original/' . $store_album_id . '/' . $mediaName;
            $mediaThumbToBeDeleted = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/thumb/' . $store_album_id . '/' . $mediaName;
        } else {
            $mediaToBeDeleted = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/original/' . $mediaName;
            $mediaThumbToBeDeleted = $file_location . '/uploads/documents/stores/gallery/' . $store_id . '/thumb/' . $mediaName;
        }
        if (file_exists($mediaToBeDeleted)) {
            // unlink($mediaToBeDeleted);
        }
        if (file_exists($mediaThumbToBeDeleted)) {
            // unlink($mediaThumbToBeDeleted);
        }
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * View album of a store.
     * @param request object
     * @param json
     */
    public function postViewstorealbumsAction(Request $request) {
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
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id', 'album_id','user_id');

        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get Store id
        $store_id = $de_serialize['store_id'];
        //get user id
        $user_id=$de_serialize['user_id'];
        
        //get album id
        $store_album_id = $de_serialize['album_id'];

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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        $store_album_info = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                ->find($store_album_id);
        
        
        if(!$store_album_info) {
             return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
        }
        
        
        
        $album_name = $store_album_info->getStoreAlbumName();
        $album_desc = $store_album_info->getStoreAlbumDesc();
        
        $album_current_rate = 0;
        $album_is_rated = false;
             
        $dm=$this->get('doctrine.odm.mongodb.document_manager');
        $album_rating=$dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$store_album_id,'item_type'=>'store_album'));

        if($album_rating){
        foreach($album_rating->getRate() as $rate){
            if($rate->getUserId() == $user_id ) {
                $album_current_rate = $rate->getRate();
                $album_is_rated = true;
                break;
            }
            
        }
            $album_avg_rate=round($album_rating->getAvgRating(), 1);
            $no_of_votes_album=(int) $album_rating->getVoteCount();
        }else{

            $album_avg_rate=0;
            $no_of_votes_album=0;
        }
        

        $album_info = array(
            'title' => $album_name,
            'description' =>$album_desc,
            'avg_rate'=>$album_avg_rate,
            'no_of_votes'=> $no_of_votes_album,
            'current_user_rate'=>$album_current_rate,
            'is_rated' => $album_is_rated
        );

        $store_album_medias = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findBy(array('albumId' => $store_album_id, 'storeId' => $store_id, 'mediaStatus' => 1), null, $limit_size, $limit_start);
        $store_album_medias_count = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->getUserAlbumMediaCount($store_album_id, $store_id);
        $media_data = array();
        $count_record = 0;
        if ($store_album_medias_count) {
            $count_record = $store_album_medias_count;
        }
        if ($store_album_medias) {

            foreach ($store_album_medias as $album_media) {
                $media_id = $album_media->getId();
                $media_name = $album_media->getImageName();
                $album_image_type = $album_media->getImageType();
                //  $media_type  = $album_media->getContenttype();
                $mediaPath = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_album_id . '/' . $media_name;
                $thumbDir = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/' . $media_name;
                
                $currentrate=0;
                $is_rated=false;
                $dm=$this->get('doctrine.odm.mongodb.document_manager');
                $media_rating=$dm->getRepository('StoreManagerPostBundle:ItemRating')
                        ->findOneBy(array('item_id'=>(string)$media_id,'item_type'=>'store_media'));
                
                if($media_rating){
                foreach($media_rating->getRate() as $rate){
                    if($rate->getUserId() == $user_id ) {
                        $currentrate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }      
                $votecount = $media_rating->getVoteCount();
                $avg_rate=$media_rating->getAvgRating();
                }else{  
                    $votecount = 0;
                    $avg_rate=0;
                }
                $media_data[] = array('id' => $media_id,
                    'media_name' => $media_name,
                    'media_path' => $mediaPath,
                    'thumb_path' => $thumbDir,
                    'image_type' =>$album_image_type,
                    'create_on' => $album_media->getCreatedAt(),
                    'avg_rate'=>$avg_rate,
                    'no_of_votes'=>$votecount,
                    'current_user_rate'=>$currentrate,
                    'is_rated'=>$is_rated,
                );
            }
        }
        $data = array('media' => $media_data, 'size' => $count_record,'album'=> $album_info);
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * View all album of a store.
     * @param request object
     * @param json
     */
    public function postStorealbumlistsAction(Request $request) {
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

        $required_parameter = array('store_id','user_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get Store id
        $store_id = $de_serialize['store_id'];
        $user_id=$de_serialize['user_id'];

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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $store_albums = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                ->findBy(array('storeId' => $store_id), null, $limit_size, $limit_start);
        $store_albums_count = $em->getRepository('StoreManagerStoreBundle:Storealbum')
                ->getUserAlbumCount($store_id);
        $album_datas = array();
        $record_count = 0;
        if ($store_albums_count) {
            $record_count = $store_albums_count;
        }
        if ($store_albums) {

            foreach ($store_albums as $store_album) {
                $album_id = $store_album->getId();
                $album_name = $store_album->getStoreAlbumName();
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $media_dir = $file_location . $this->store_media_path . $store_id . '/original/' . $album_id;
                $thumb_dir = $file_location . $this->store_media_path . $store_id . '/thumb/' . $album_id;
                $album_desc = $store_album->getStoreAlbumDesc();
                //count total number of media in particular album
                $album_medias = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->findBy(array('albumId' => $album_id, 'storeId' => $store_id, 'mediaStatus' => 1));
                $total_media_in_album = count($album_medias);

                //get featured image of album to make cover image of that album
                $featured_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->findOneBy(array('albumId' => $album_id, 'storeId' => $store_id, 'mediaStatus' => 1), array('id' => 'ASC'), 1, 0);
                if ($featured_image) {
                    $featured_image_name = $featured_image->getImageName();
                    $featured_image_type = $featured_image->getImageType();
                    $featured_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $featured_image_name;
                } else {
                    $featured_image_name = '';
                    $featured_thumb_path = '';
                    $featured_image_type = '';
                }
                $currentrate=0;
                $is_rated=false;
                $dm=$this->get('doctrine.odm.mongodb.document_manager');
                $album_rating=$dm->getRepository('StoreManagerPostBundle:ItemRating')
                        ->findOneBy(array('item_id'=>(string)$album_id,'item_type'=>'store_album'));
                if($album_rating){
                foreach($album_rating->getRate() as $rate){
                    if($rate->getUserId() == $user_id ) {
                        $currentrate = $rate->getRate();
                        $is_rated = true;
                        break;
                    }
                }
                
                $votecount = $album_rating->getVoteCount();
                $avg_rate=$album_rating->getAvgRating();
                }else{  
                    $votecount = 0;
                    $avg_rate=0;
                }
                $album_datas[] = array('id' => $album_id,
                    'album_name' => $album_name,
                    'created_at' => $store_album->getStoreAlbumCreted(),
                    'media_in_album' => $total_media_in_album,
                    'album_featured_image' => $featured_thumb_path,
                    'image_type' =>$featured_image_type,
                    'album_description' => $album_desc,
                    'avg_rate'=>$avg_rate,
                    'no_of_votes'=>$votecount,
                    'current_user_rate'=>$currentrate,
                    'is_rated'=>$is_rated,
                    
                );
            }
        }
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('album' => $album_datas, 'size' => $record_count));
        echo json_encode($res_data);
        exit();
    }

    /**
     * uploading the store profile image.
     * @param request object
     * @return json
     */
    public function postUploadstoreprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
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

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileExtensionType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }

        if (!isset($_FILES['store_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['store_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }

        //check for image size
        $getfilename = $_FILES['store_media']['tmp_name'];
        list($width, $height) = getimagesize($getfilename);
        $check_resize_width = $this->crop_image_width;
        $check_resize_height = $this->crop_image_width;
        if ($width < $check_resize_width or $height < $check_resize_height) {
            return array('code' => 140, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE_WITH_WIDTH_GREATER_THAN_200_AND_HEIGHT_GREATER_THAN_200', 'data' => $data);
        }
        //end to check

        $user_id = $object_info->user_id;
        $store_id = $object_info->store_id;

        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        $store = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($store_id); //@TODO Add group owner id in AND clause.
        //if store not found
        if (!$store) {
            $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
            return $res_data;
        }
        //get User Role
        $mask_id = $this->userStoreRole($store_id, $user_id);

        //check for Access Permission
        //only owner of store can upload the profile pic for store
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        
        //get the image name clean service..
        $clean_name = $this->get('clean_name_object.service');
        
        if (!empty($original_media_name)) { //if file name is not exists means file is not present.
            $store_media_name = time() . strtolower(str_replace(' ', '', $_FILES['store_media']['name']));
            $store_media_name = $clean_name->cleanString($store_media_name); //rename the file name, clean the image name.
            $store_media_type = $_FILES['store_media']['type'];
            $store_media_type = explode('/', $store_media_type);
            $store_media_type = $store_media_type[0];
            $em = $this->getDoctrine()->getManager();
            $storeMedia = new StoreMedia();
            $storeMedia->setStoreId($store_id);
            $storeMedia->setImageName($store_media_name);
            $storeMedia->setAlbumId('');
            $time = new \DateTime("now");
            $storeMedia->setCreatedAt($time);
            $storeMedia->setIsFeatured(0);
            $storeMedia->stroeProfileImageUpload($store_id, $store_media_name); //uploading the files.
            $em->persist($storeMedia);
            $em->flush();

            $store_media_id = $storeMedia->getId();
            if ($store_media_type == 'image') {

                $mediaOriginalPath = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/original/';
                $mediaOriginalPathTOBeCroped = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/thumb_crop/';
                $mediaOriginalPathTOBeCoveredCroped = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/thumb_cover_crop/';

                //$thumbDir = $this->getBaseUri() . $this->store_media_path . $store_id . '/thumb/';
                $thumbCropDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
                $thumbCoverCropDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_cover_crop/";
                $thumbDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb/";
                $thumbCoverDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb/coverphoto/";


                //rotate the image if orientaion is not actual.
                if (preg_match('/[.](jpg)$/', $store_media_name) || preg_match('/[.](jpeg)$/', $store_media_name)) {
                    $image_rotate_service = $this->get('image_rotate_object.service');
                    $image_rotate = $image_rotate_service->ImageRotateService($mediaOriginalPath . $store_media_name);
                }
                //end of image rotate

                $resizeOriginalDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/";

                //resize the original image..
                $this->resizeOriginal($store_media_name, $mediaOriginalPath, $resizeOriginalDir, $store_id);

                //generate a thumb from original image
                $this->createThumbFromCrop($store_media_name, $mediaOriginalPath, $thumbCropDir, $store_id);
                //crop an image from thumb_crop of passed
                $this->cropFromRemote($store_media_name, $mediaOriginalPathTOBeCroped, $thumbDir, $store_id);
                //create the thumb for cover image
                $this->createCoverThumbFromCrop($store_media_name, $mediaOriginalPath, $thumbCoverCropDir, $store_id);
                //crop an image from thumb__cover_crop of passed
                $this->cropCoverFromRemote($store_media_name, $mediaOriginalPathTOBeCoveredCroped, $thumbCoverDir, $store_id);
                //$this->createStoreProfileImageThumbnail($store_media_name, $mediaOriginalPath, $thumbDir, $store_id);
            }
            //below line should be uncommented
            $store->setStoreImage($store_media_id);
            //persist the store object
            $em->persist($store);
            $em->flush();
            //making the path of the uploaded image,.
            $store_profile_cover_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $store_media_name;
            $store_profile_image_thumb_path_app = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_media_name;
            $store_profile_original_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_media_name;
            $data = array(
                'original_image_path' => $store_profile_original_image_path,
                'cover_image_path' => $store_profile_cover_image_thumb_path
            );
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
           
            // call applane service  
            $appalne_data = array('profile_img' =>$store_profile_image_thumb_path_app, 'shop_id'=> $store_id);
      
            //get dispatcher object
            $event = new FilterDataEvent($appalne_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.updateprofileimg', $event);
             //end of applane service
            
            echo json_encode($res_data);
            exit();
        }
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
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileExtensionType() {
        $file_error = 0;
        if (!isset($_FILES['store_media'])) {
            return $file_error;
        }
        $file_name = basename($_FILES['store_media']['name']);
        //$filecheck = basename($_FILES['imagefile']['name']);
        if (!empty($file_name)) {
            $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
            //for video and images.

            if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                    ($_FILES['store_media']['type'] == 'image/jpeg' ||
                    $_FILES['store_media']['type'] == 'image/jpg' ||
                    $_FILES['store_media']['type'] == 'image/gif' ||
                    $_FILES['store_media']['type'] == 'image/png'))))) {
                $file_error = 1;
            }
        }
        return $file_error;
    }

    /**
     * create thumbnail for  a store profile image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $store_id
     */
    public function createStoreProfileImageThumbnail($filename, $media_original_path, $thumb_dir, $store_id) {
        $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb/";
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = 200;
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
     * set the store profile image from a album
     * @param request object
     * @return json
     */
    public function postSetstoreprofileimagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'media_id', 'store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $store_media_id = $object_info->media_id;
        $store_id = $object_info->store_id;
        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        $store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($store_id);
        //if store not found
        if (!$store_info) {
            $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
            return $res_data;
        }
        $store_media = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->find($store_media_id);
        if (!$store_media) {
            $res_data = array('code' => 100, 'message' => 'STORE_MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            return $res_data;
        }
        //get album id
        $store_album_id = $store_media->getAlbumId();
        $store_media_name = $store_media->getImageName();
        $store_gallary_path = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/";

        // $media_original      = $store_gallary_path. $store_id . "/original/";
        //  $media_original_crop = $store_gallary_path . $store_id . '/thumb_crop/';
        //  $media_original_cover_crop = $store_gallary_path . $store_id . '/thumb_cover_crop/';
        // $thumb_crop          = $store_gallary_path . $store_id . "/thumb_crop/";
        // $thumb_cover_crop    = $store_gallary_path . $store_id . "/thumb_cover_crop/";
        //  $thumb               = $store_gallary_path . $store_id . '/thumb/';

        $media_original = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/";
        $media_original_crop = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/thumb_crop/';
        $media_original_cover_crop = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/thumb_cover_crop/';
        $thumb_crop = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $thumb_cover_crop = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_cover_crop/";
        $thumb = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . '/thumb/';

        $resizeOriginalDir = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/" . $store_album_id . '/';

        if ($store_album_id > 0) { //inside a album
            $media_original_path = $media_original . $store_album_id . '/';
            $media_original_path_croped = $media_original_crop . $store_album_id . '/';
            $media_original_path_covered_croped = $media_original_cover_crop . $store_album_id . '/';
            $thumb_crop_dir = $thumb_crop . $store_album_id . '/';
            $thumb_dir = $thumb . $store_album_id . '/';
            $thumb_cover_crop_dir = $thumb_cover_crop . $store_album_id . '/';
            $thumb_cover_dir = $thumb . $store_album_id . '/coverphoto/';
        } else { //without album
            $media_original_path = $media_original;
            $media_original_path_croped = $media_original_crop;
            $media_original_path_covered_croped = $media_original_cover_crop;
            $thumb_crop_dir = $thumb_crop;
            $thumb_dir = $thumb;
            $thumb_cover_crop_dir = $thumb_cover_crop;
            $thumb_cover_dir = $thumb . '/coverphoto/';
        }


        //below line should be uncommented
        $store_info->setStoreImage($store_media_id);
        //persist the store object
        $em->persist($store_info);
        //update the store info
        $em->flush();

        $this->resizeOriginal($store_media_name, $media_original_path, $resizeOriginalDir, $store_id, $store_album_id);
        //generate a thumb from original image
        $this->createThumbFromCrop($store_media_name, $media_original_path, $thumb_crop_dir, $store_id);
        //crop an image from thumb of passed
        $this->cropFromRemote($store_media_name, $media_original_path_croped, $thumb_dir, $store_id);
        //create the thumb for cover image
        $this->createCoverThumbFromCrop($store_media_name, $media_original_path, $thumb_cover_crop_dir, $store_id);
        //crop an image from thumb_cover_crop of passed
        $this->cropCoverFromRemote($store_media_name, $media_original_path_covered_croped, $thumb_cover_dir, $store_id, $store_album_id);
        //echo $store_profile_cover_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'.$store_album_id.'/coverphoto/'. $store_media_name; 
//making the path of the uploaded image,.
        if ($store_album_id != '') {
            $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/' . $store_media_name;
            $store_profile_cover_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/coverphoto/' . $store_media_name;
            $store_profile_original_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_album_id . '/' . $store_media_name;
        } else {
            $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_media_name;
            $store_profile_cover_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $store_media_name;
            $store_profile_original_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_media_name;
        }

        $data = array(
            'original_image_path' => $store_profile_original_image_path,
            'thumb_image_path' => $store_profile_image_thumb_path,
            'cover_image_path' => $store_profile_cover_image_thumb_path
        );
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
        
        // call applane service  
        $appalne_data = array('profile_img' =>$store_profile_image_thumb_path, 'shop_id'=> $store_id);
      
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.updateprofileimg', $event);
        //end of applane service
            
        echo json_encode($res_data);
        exit();
    }

    /**
     * List user's store
     * @param Request $request
     * @return array;
     */
    public function postGetuserallstoresAction(Request $request) {
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

        $required_parameter = array('user_id');

        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];

        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //check limit
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            //get limit size
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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllStores($user_id, $limit_start, $limit_size);

        if (!$stores) {
            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            return $res_data;
        }
        //get record count
        $stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllStoresCount($user_id);

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $stores, 'size' => $stores_count));
        echo json_encode($res_data);
        exit();
    }

    /**
     * Create store on social bees
     * @param Request $request, int $store_id
     * @return boolean;
     */
    public function createStoreOnSocialBees($de_serialize, $store_id) {

        $user_email = "";
        $password = "";
        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        //get user object
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $de_serialize['user_id']));
        if ($user) {
            $user_email = $user->getEmail();
            $password = $user->getPassword();
        }
        //after store registration we have to also update the profile on shoppingplus server.
        $store_email = $de_serialize['email'];
        
        
        $legal_status = $de_serialize['legal_status'];
        $business_name = $de_serialize['business_name'];
        $business_address = $de_serialize['business_address'];
        $zip = $de_serialize['zip'];
        $business_city = $de_serialize['business_city'];
        $provience = $de_serialize['province'];
        $phone = $de_serialize['phone'];
        $user_email = $user_email; // (fos_user_user email)
        $description = $de_serialize['description'];
        $vat_number = $de_serialize['vat_number']; //vat_number ( this should be unique)
        $user_password = $password; // (fos_user_user password)
        $referral_id = $referral_id;//fos_fos_user.id which is broker
        $type = 3;
        $virtual_status = "N";
        $importopdv_amount = 0;
        $step = 'Shop Registeration';
        $shop_status_shopping_plus = 'D';
          
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        // BLOCK SHOPPING PLUS
//        $shoppingplus_obj ->registerShopShopingplus($store_id,$business_name,$business_address,$zip,$business_city,
//                                                      $provience,$phone,$store_email,$description,$vat_number,$user_password,
//                                                      $referral_id,$virtual_status,$importopdv_amount,$type,$step,
//                                                      $shop_status_shopping_plus
//                                                    );
    }

    /**
     * Edit store on social bees
     * @param Request $request, int $store_id
     * @return boolean;
     */
    public function editStoreOnSocialBees($de_serialize, $store_id) {

        $user_email = "";
        $password = "";
        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        //get user object
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $de_serialize['user_id']));
        if ($user) {
            $user_email = $user->getEmail();
            $password = $user->getPassword();
        }
        
        $store_email = $de_serialize['email'];
        
        $legal_status = $de_serialize['legal_status'];
        $business_name = $de_serialize['business_name'];
        $business_address = $de_serialize['business_address'];
        $zip = $de_serialize['zip'];
        $business_city = $de_serialize['business_city'];
        $provience = $de_serialize['province'];
        $phone = $de_serialize['phone'];
        $user_email = $user_email; // (fos_user_user email)
        $description = $de_serialize['description'];
        $vat_number = $de_serialize['vat_number']; //vat_number ( this should be unique)
        $user_password = $password; // (fos_user_user password)
        $referral_id = $referral_id;//fos_fos_user.id which is broker
        $type = 3;
        $virtual_status = "N";
        $importopdv_amount = 0;
        $step = 'Shop Update';
          
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        // BLOCK SHOPPING PLUS
//        $shoppingplus_obj ->updateShopShopingplus($store_id,$business_name,$business_address,$zip,$business_city,
//                                                      $provience,$phone,$store_email,$description,$vat_number,$user_password,
//                                                      $referral_id,$virtual_status,$importopdv_amount,$type,$step
//                                                );
    }

    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $store_id
     */
    public function cropFromRemote($filename, $media_original_path, $thumb_dir, $store_id) {

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
        $crop_image_width = $this->crop_image_width;
        $crop_image_height = $this->crop_image_height;

        //left/top for crop the image from x,y
        $left = 0;
        $top = 0;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        if (preg_match('/[.](jpg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            imagepng($canvas, $path_to_thumbs_center_image_path, 9);
        }
        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/thumb";
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }

    /**
     * create thumbnail for  a store profile image from original image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $store_id
     */
    public function createThumbFromCrop($filename, $media_original_path, $thumb_dir, $store_id) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = $this->crop_image_width;
        $final_height_of_image = $this->crop_image_height;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = $width = imagesx($im);
        $oy = $height = imagesy($im);

        $original_aspect = $width / $height;
        $thumb_aspect = $final_width_of_image / $final_height_of_image;
        //maintain the aspect ratio
        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $final_height_of_image;
            $new_width = $width / ($height / $final_height_of_image);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $final_width_of_image;
            $new_height = $height / ($width / $final_width_of_image);
        }

        $nx = $new_width;
        $ny = $new_height;
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
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

        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/thumb_crop";
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * create thumbnail for  a store cover profile image from original image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_crop_dir
     * @param string $store_id
     */
    public function createCoverThumbFromCrop($filename, $media_original_path, $thumb_crop_dir, $store_id) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_crop_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = $this->cover_crop_image_width;
        $final_height_of_image = $this->cover_crop_image_height;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = $width = imagesx($im);
        $oy = $height = imagesy($im);

        $original_aspect = $width / $height;
        $thumb_aspect = $final_width_of_image / $final_height_of_image;
        //maintain the aspect ratio
        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $final_height_of_image;
            $new_width = $width / ($height / $final_height_of_image);
            if ($new_width < $final_width_of_image) {
                $new_width = $final_width_of_image;
                $new_height = $height / ($width / $final_width_of_image);
            }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $final_width_of_image;
            $new_height = $height / ($width / $final_width_of_image);
            if ($new_height < $final_height_of_image) {
                $new_height = $final_height_of_image;
                $new_width = $width / ($height / $final_height_of_image);
            }
        }

        $nx = $new_width;
        $ny = $new_height;
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
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

        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/thumb_cover_crop";
        $image_local_path = $path_to_thumbs_directory . $filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * crop cover image from x, y for a image, currently from center.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumbCoverDir
     * @param string $store_id
     */
    public function cropCoverFromRemote($filename, $media_original_path, $thumbCoverDir, $store_id, $album_id = null) {

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = $thumbCoverDir;
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
        $crop_image_width = $this->cover_crop_image_width;
        $crop_image_height = $this->cover_crop_image_height;

        //left/top for crop the image from x,y
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
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        if (preg_match('/[.](jpg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            imagepng($canvas, $path_to_thumbs_center_image_path, 9);
        }

        $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . "/thumb/coverphoto";
        if ($album_id != "") {
            $s3imagepath = "uploads/documents/stores/gallery/" . $store_id . '/thumb/' . $album_id . '/coverphoto';
        }
        $image_local_path = $path_to_thumbs_center_directory . $original_filename;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }

    /**
     * Return app url login
     * @param Request $request
     * @return array;
     */
    public function postAppurlloginsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $login_app_url   = $this->container->getParameter('login_app_url'); 
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //parmeter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get store id
        $store_id = (int) $de_serialize['store_id'];

        // get document manager object
        $em = $this->getDoctrine()->getManager();

        //check for parent store is exists or not.
        $store_detail = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id,'isActive'=>1));

        if (!$store_detail) {
            return array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
        }
        $vat_number = $store_detail->getVatNumber();
        $store_user_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $store_id));
        $store_owner_id = $store_user_obj->getUserId();

        //get user manager
        $um = $this->container->get('fos_user.user_manager');
        //get user detail
        $user = $um->findUserBy(array('id' => $store_owner_id));

        if (!$user) {
            return array('code' => 100, 'message' => 'USER_DOES_NOT_EXIT', 'data' => $data);
        }
        $md5_password = $user->getPassword();

        $login_url = "$login_app_url" . urlencode(base64_encode($vat_number . '|' . $md5_password));
        $url_to_return = '<a class="btn btn-primary" href="javascript:void(location.href=\'' . $login_url . '\');" onclick="Popup=window.open(this.href, \'Popup\', \'toolbar=no,status=no,menubar=no,scrollbars=yes,resizable=no, width=800,height=600,left=screen.width/2,top=screen.height/2\'); return false;" target="_blank">APP SHOP</a>';


        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('url' => $url_to_return));
        echo json_encode($res_data);
        exit();
    }

    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_email, $to_email, $mail_body) {
        $sixthcontinent_admin_email = 
        array(
            $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
        );
        $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($sixthcontinent_admin_email)
                ->setTo(array($to_email))
                ->setBody($mail_body, 'text/html');

        if ($this->container->get('mailer')->send($notification_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotificationFromId($mail_sub, $from_id, $to_id, $mail_body) {
        $userManager = $this->getUserManager();
        $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
        $to_user = $userManager->findUserBy(array('id' => (int) $to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');

        $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($sixthcontinent_admin_email)
                ->setTo(array($to_user->getEmail()))
                ->setBody($mail_body, 'text/html');

        if ($this->container->get('mailer')->send($notification_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $sender_id, $item_id, $msgtype, $msg) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($sender_id);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($item_id);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * Return get all map stores
     * @param Request $request
     * @return array;
     */
    public function postGetmapstoresAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','512M');
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
        $object_info = (object) $de_serialize;

        $required_parameter = array('user_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $user_id = (int) $de_serialize['user_id'];
        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllMapStores();
        $count = 0;
        if ($stores) {
            $count = count($stores);

            foreach ($stores as $store_obj) {
                $user_service = $this->get('user_object.service');
                $store_detail = $user_service->getStoreObjectService($store_obj['id']);
                //get if dp is exist for user
                $dp_status = $this->checkDPShotsForShop($store_obj['id'], 'dp', $user_id);
                //check if shot is exist on shop for user
                $shot_status = $this->checkDPShotsForShop($store_obj['id'], 's', $user_id);
                //check if gift card is exist on shop for user
                $gift_card_status = $this->checkDPShotsForShop($store_obj['id'], 'gc', $user_id);
                $credit_status = 0;
                //get total credit available for user on shop
                $credit_available = $this->getTotalCreditAvailableForUser($user_id, $store_obj['id']);
                if($dp_status == 1 || $shot_status == 1 || $gift_card_status == 1 ) {
                    $credit_status = 1;
                }
                //get DP amount available for user on shop
                //$dp_amount = $this->getDPAmount($store_obj['id'], $user_id);
                //get Shot amount available for user on shop
                //$shot_amount = $this->getShotAmount($store_obj['id'], $user_id);
                //get CI  available for user on shop
                //$ci_amount = $this->getCIAmount($store_obj['id'], $user_id);
                //get gift card available for user on shop
                //$gc_amount = $this->getGCAmount($store_obj['id'], $user_id);
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
                    'shot_status' => $shot_status,
                    'card_status' => $gift_card_status,
                    'credit_available' => array(
                        'total_credit' => $credit_available
                    ),
                    'credit_status' => $credit_status,
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
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data, 'count' => $count);
        echo json_encode($res_data);
        exit();
    }


  /**
     * function for getting the store list without calculation
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function postGetmapstoreslistsAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','512M');
        $bucket_path = $this->getS3BaseUri();
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
        $object_info = (object) $de_serialize;

        $required_parameter = array('user_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $user_id = (int) $de_serialize['user_id'];
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'it';
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        //$citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        $citizen_income = 0;
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllMapStoreOptimize($user_id,$citizen_income,$bucket_path,$language_code);
        $count = count($stores);
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $stores, 'count' => $count);

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
    private function checkDPShotsForShop($shop_id, $type, $user_id) {
        $dm = $this->getDoctrine()->getManager();
        if ($type == 'dp') {
            $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->checkDPForShop($shop_id, $user_id);
        } elseif ($type == 's') {
            $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->checkShotsForShop($shop_id, $user_id);
        } elseif($type == 'gc') {
            $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->checkGiftCardForShop($shop_id, $user_id);
        } elseif($type == 'ci') {
            $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->checkCitizenIncomeForShop($shop_id, $user_id);
        }
        return $result;
    }
    
    /**
     * function for getting the total credit available
     * @param type $user_id
     * @param type $shop_id
     * @return type
     */
    private function getTotalCreditAvailableForUser($user_id, $shop_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getTotalCreditAvailableForUser($user_id, $shop_id);
        return $result;
    }
    
    /**
     * function for getting the DP amount
     * @param type $shop_id
     * @param type $user_id
     * @return type
     */
    private function getDPAmount($shop_id, $user_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getDPAmount($user_id, $shop_id);
        return $result;
    }
    
    /**
     * function for getting the shot amount
     * @param type $shop_id
     * @param type $user_id
     * @return type
     */
    private function getShotAmount($shop_id, $user_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getShotAmount($user_id, $shop_id);
        return $result;
    }
    
    /**
     * function for getting the CI amount
     * @param type $shop_id
     * @param type $user_id
     * @return type
     */
    private function getCIAmount($shop_id, $user_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getCIAmount($user_id, $shop_id);
        return $result;
    }
    
    /**
     * function for getting the Gift card amount
     * @param type $shop_id
     * @param type $user_id
     * @return type
     */
    private function getGCAmount($shop_id, $user_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                    ->getRepository('StoreManagerStoreBundle:Storeoffers')
                    ->getGCAmount($user_id, $shop_id);
        return $result;
    }
    
    /**
     * temp script for maintain shop acl
     */
     public function postShopaclsAction(Request $request){
        
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $limit_start = (int) $de_serialize['limit_start'];
        $limit_size = (int) $de_serialize['limit_size'];
         // get documen manager object
        $em = $this->getDoctrine()->getManager();  
        $stores_res = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllStoresResult($limit_start,$limit_size);
        
        if($stores_res) {
            foreach($stores_res as $record){
                $store_id = $record->getId();
                //if($store_id ==21021){
                      //get ACL object from service
                $acl_obj = $this->get("store_manager_store.acl");
                $store_owner_acl_code = $acl_obj->getStoreOwnerAclCode();
                
                $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                           ->findOneBy(array('storeId'=>$store_id));  
  
              
                    if($store_obj){

                        //Acl Operation
                        $id = $store_obj->getUserId();
                        
                        $user_obj = $this->container->get('fos_user.user_manager')->findUserBy(array('id'=>$id));
                        
                        if($user_obj){

                            $aclManager = $this->get('problematic.acl_manager');
                            $aclManager->setObjectPermission($record, $store_owner_acl_code, $user_obj);

                        }
                    }
               // }
            }
        }
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit;    
        
    }
    
    /**
     * Search shop on map
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array
     */
    public function postSearchshoponmapsAction(Request $request)
    {   
        $data = array();
        $final_result = array();
        $bucket_path = $this->getS3BaseUri();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //parmeter check start
        $object_info = (object) $de_serialize;

        $required_parameter = array('user_id','shops');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $shop_array = $object_info->shops;
        $user_id = $object_info->user_id;
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'it';
        // get entity manager object
        $em = $this->getDoctrine()->getManager();
                $friend_name = '';
        $results_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriendsCount($user_id, $friend_name);
        //fire the query in User Repository
        $response = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllFriendsType($user_id, $friend_name, 0, $results_count);
        
        $userIds = array();
        foreach($response as $_result){
            array_push($userIds, $_result['user_id']);
        }
        $friendsIds = array_unique($userIds);
        
        //call applane service to calculate shop revenue
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_info_store = $applane_service->getUserCreditOnStore($shop_array,$user_id);
       
        
       foreach($shop_array as $shop){
        $shop_id = $shop;
        $store_data = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getMapStoresDetail($shop_id,$user_id,$bucket_path,$citizen_income,$language_code,$friendsIds);
        if($store_data){
            
            $current_store_id = $store_data[0]['id'];
            $current_user_id        = $store_data[0]['userId']; // shop owner id
            //get store owner id
//            $store_obj = $em->getRepository('StoreManagerStoreBundle:UserToStore')
//                    ->findOneBy(array('storeId' => $current_store_id, 'role' => 15));

            //$store_owner_id = $store_obj->getUserId();
            
            $store_owner_id = $current_user_id;
            
            $user_service = $this->get('user_object.service');
            $user_object = $user_service->UserObjectService($store_owner_id);

            $store_data[0]['user_info'] = $user_object;
            $store_data[0]['total_credie_available'] = isset($user_info_store[$shop_id]) ? $user_info_store[$shop_id] : 0;
            $final_result[] = $store_data[0];
        }
       }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result));
        echo json_encode($res_data);
        exit();
        
    }
    
    /**
     * Get citizen income
     * @param int $citizen_id
     * @return array
     */
    public function getCitizenincome($citizen_id)
    {
            $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
            $response = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($citizen_id);
            
            return $response;
    }
    
    /**
     * Get Contract fields for shop
     * @param int $shop_id
     */
    public function postGetshopcontractinfosAction(Request $request)
    {
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //parmeter check start
        $object_info = (object) $de_serialize;

        $required_parameter = array('shop_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $shop_id = $object_info->shop_id;
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $shops_info = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id'=>$shop_id));
        if(!$shops_info){
           $res_data = array('code' => 160, 'message' => 'STORE_ID_NOT_VALID', 'data' => $data);
           echo json_encode($res_data);
           exit();
        }
        
       //get shop info
       $shop_name = $shops_info->getName();
       $shop_business_name = $shops_info->getBusinessName();
       $shop_email = $shops_info->getEmail();
       $shop_business_country = $shops_info->getBusinessCountry();
       $shop_business_region = $shops_info->getBusinessRegion();
       $shop_business_city = $shops_info->getBusinessCity();
       $shop_business_address = $shops_info->getBusinessAddress();
       
       $response = array('id'=>$shop_id, 
           'shopName'=>$shop_name, 
           'BusinessName' => $shop_business_name, 
           'ShopEmail' => $shop_email);
       
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $response);
        echo json_encode($res_data);
        exit(); 
    }
    
    /**
     * View all album of a store.
     * @param request object
     * @param json
     */
    public function postStorelatestalbumlistsAction(Request $request) { 
        //initilise the array
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get Store id
        $store_id = $de_serialize['store_id'];
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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        // Get Store Album
        $store_album_medias = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findBy(array('storeId' => $store_id, 'mediaStatus' => 1), array('id' => 'DESC'), $limit_size, $limit_start);
        $media_data = array();
        if ($store_album_medias) {
            foreach ($store_album_medias as $album_media) {
                $media_id = $album_media->getId();
                $media_name = $album_media->getImageName();
                $album_image_type = $album_media->getImageType();
                $store_album_id = $album_media->getAlbumId();
                $mediaPath = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $store_album_id . '/' . $media_name;
                $thumbDir = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $store_album_id . '/' . $media_name;
                if($store_album_id != 0){
                    $media_data[] = array('id' => $media_id,
                        'media_name' => $media_name,
                        'media_path' => $mediaPath,
                        'thumb_path' => $thumbDir,
                        'image_type' =>$album_image_type,
                        'create_on' => $album_media->getCreatedAt(),
                    );
                }
            }
        }
        $data = array('media' => $media_data);
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }
    
    
    /**
     * search store based on the filters
     * @param request object
     * @param json
     */
    public function postSearchstoreonfiltersAction(Request $request) { 
        //initilise the array
        $data = array();
        //get request object
        $bucket_path = $this->getS3BaseUri();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //Code repeat end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get search name
        $search_text = isset($de_serialize['search_text'])?$de_serialize['search_text']:'';
        //get search city
        $search_city = isset($de_serialize['city'])?$de_serialize['city']:'';
        //get category
        $category = isset($de_serialize['category'])?$de_serialize['category']:array();
        //get lat,long
        $lat_long = isset($de_serialize['lat_long'])?$de_serialize['lat_long']:array();
        //get radius from parameters 
        $default_radius = $this->container->getParameter('store_search_radius');
        $radius = isset($de_serialize['radius'])?$de_serialize['radius']:$default_radius;
        //get sort type
        $sort_type = isset($de_serialize['sort_type'])?$de_serialize['sort_type']:0;
        //get sort type
        $limits = isset($de_serialize['limits'])?$de_serialize['limits']:array();
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'en';
        $favorite_shop = isset($de_serialize['only_in_favorite'])?$de_serialize['only_in_favorite']:0;
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 85, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        
        //check for valid sorting type 0:distance/1:shop_rating/2:credit_available
        $allowed_array = array(0,1,2);
        if (!in_array($sort_type, $allowed_array)) {
            return array('code' => 401, 'message' => 'INVALID_SORT_TYPE', 'data' => $data);
        }
        
        
        
        //check for valid language code if valid otherwise set en as default
        $allowed_array = array('it','en');
        if (!in_array($language_code, $allowed_array)) {
            $language_code = 'en';                  
        }
        
        //check if the fevorite parameter in valid or not
        $allowed_favorite = array(0,1);      
        if (!in_array($favorite_shop, $allowed_favorite)) {
            $res_data = array('code' => 402, 'message' => 'INVALID_FAVORITE_TYPE', 'data' => $data);
            echo json_encode($res_data,JSON_NUMERIC_CHECK);
            exit();               
        }
        
        //get store type
        $store_type = isset($de_serialize['store_type'])?$de_serialize['store_type']:1;
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //getting the citizen income from the shopping plus
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        //getting the store list
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getFilterStore($user_id,$search_text,$search_city,$category,$lat_long,$radius,$sort_type,$limits,$citizen_income,$bucket_path,$store_type,$language_code,$favorite_shop);
        
        //check if store exist
        if (!$stores) {
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            return $res_data;
        }
        
        //getting the store ids.
        $store_ids = array_map(function($store) {
            return "{$store['id']}";
        }, $stores);
        
        //call applane service to calculate shop revenue
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $user_info_store = $applane_service->getUserCreditOnStore($store_ids,$user_id);
        
        //loop for adding the users credit in store array
        foreach($stores as $store) {
            $store['total_credie_available'] = isset($user_info_store[$store['id']]) ? $user_info_store[$store['id']] : 0;
            $store_result[] = $store;
        }
        
        $stores = $store_result;
          
        $final_result = $stores;
//         //getting the posts sender ids.
//        $store_user_ids = array_map(function($store) {
//            return "{$store['userId']}";
//        }, $stores);
//        
//        //getting the users unique array
//        $users_array = array_unique($store_user_ids);
//        
//        //find user object service..
//        $user_service = $this->get('user_object.service');
//        //get user profile and cover images..
//        $users_object_array = $user_service->MultipleUserObjectService($users_array);
//        //array for final result
//        $final_result = array();
//        //loop for adding the users info into the final array
//        foreach($stores as $store) {
//            $store['user_info'] = isset($users_object_array[$store['userId']]) ? $users_object_array[$store['userId']] : array();
//            $final_result[] = $store;
//        }

        //get record count
        //set 1 for getting all stores
        $stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getFilterStoresCount($user_id,$search_text,$search_city,$category,$lat_long,$radius,$sort_type,$limits,$citizen_income,$bucket_path,$store_type,$language_code,$favorite_shop);
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result, 'size' => $stores_count));
        echo json_encode($res_data,JSON_NUMERIC_CHECK);
        exit();
    }
    
    public function postGetstoredetailsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $bucket_path = $this->getS3BaseUri();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //Code repeat end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get search name
        $store_id = (int) $de_serialize['store_id'];
         
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 85, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        //getting the citizen income from the shopping plus
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        //getting the store list
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getStoreDetails($user_id,$store_id,$citizen_income,$bucket_path);
        
        //check if store exist
        if (!$stores) {
            $res_data = array('code' => 121, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            return $res_data;
        }
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $stores);
        echo json_encode($res_data,JSON_NUMERIC_CHECK);
        exit();
    }
    
    public function testcase() {
        $container = NManagerNotificationBundle::getContainer();
        
        echo $container->getParameter('store_search_radius');
        die;
    }
    
}