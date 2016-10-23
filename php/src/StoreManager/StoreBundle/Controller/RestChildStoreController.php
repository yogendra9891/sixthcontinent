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
use StoreManager\StoreBundle\Entity\StoreJoinNotification;
use StoreManager\StoreBundle\Entity\Transactionshop;
use StoreManager\StoreBundle\Entity\Storeoffers;
use Notification\NotificationBundle\Document\UserNotifications;

class RestChildStoreController extends Controller {

    protected $store_media_path = '/uploads/documents/stores/gallery/';

    /**
     * Create group
     * @param Request $request
     * @return array;
     */
    public function postCreatechildstoresAction(Request $request) {
        //initilise the array
        $referral_id = '';
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'parent_store_id', 'business_name', 'email', 'description', 'phone', 'legal_status', 'business_type', 'business_country', 'business_region', 'business_city',
            'business_address', 'zip', 'province', 'vat_number', 'iban', 'map_place', 'latitude', 'longitude', 'name');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get parent store id
        $parent_store_id = $de_serialize['parent_store_id'];

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
        $store_vat_number = $de_serialize['vat_number'];
        $store_iban = $de_serialize['iban'];
        $store_map_place = $de_serialize['map_place'];
        $store_latitude = $de_serialize['latitude'];
        $store_longitude = $de_serialize['longitude'];
        $store_name = $de_serialize['name'];
        $referral_id = isset($de_serialize['referral_id']) ? $de_serialize['referral_id'] : '';
        //get store owner id
        $store_owner_id = $de_serialize['user_id'];


        if ($store_owner_id == "") {
            $res_data = array('code' => 115, 'message' => 'STORE_OWNER_ID_IS_REQUIRED', 'data' => array());
            return $res_data;
        }

        if ($parent_store_id == "") {
            $res_data = array('code' => 123, 'message' => 'PARENT_STORE_ID_IS_REQUIRED', 'data' => array());
            return $res_data;
        }

        if ($store_business_name == "") {
            $res_data = array('code' => 116, 'message' => 'STORE_BUSINESS_NAME_IS_REQUIRED', 'data' => array());
            return $res_data;
        }

        // get document manager object
        $em = $this->getDoctrine()->getManager();

        //check for parent store is exists or not.
        $parent_store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($parent_store_id);
        if (!$parent_store_info) {
            return array('code' => 100, 'message' => 'PARENT_STORE_DOES_NOT_EXISTS', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($store_owner_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get User Role
        $mask_id = $this->userStoreRole($parent_store_id, $store_owner_id);

        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //get ACL object from service
        $acl_obj = $this->get("store_manager_store.acl");

        $store_owner_acl_code = $acl_obj->getStoreOwnerAclCode();

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
        $store->setParentStoreId($parent_store_id); //for parent store
        $store->setIsActive(1);
        $store->setIsAllowed(1);
        $time = new \DateTime("now");
        $store->setCreatedAt($time);
        $store->setUpdatedAt($time);
        $store->setStoreImage('');

        //persist the store object
        $em->persist($store);
        //save the store info
        $em->flush();

        //assign the user in UserToGroup Table
        //get usertostore object
        $usertostore = new UserToStore();
        $usertostore->setStoreId($store->getId());
        $usertostore->setUserId($store_owner_id);
        $usertostore->setChildStoreId(0); // set child store id as 0 for parent store
        $usertostore->setRole($store_owner_acl_code); //15 for owner
        $time = new \DateTime("now");
        $usertostore->setCreatedAt($time);

        //persist the usertostore object
        $em->persist($usertostore);
        //save the usertostore info
        $em->flush();

        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $store_owner_id));

        $aclManager = $this->get('problematic.acl_manager');
        $aclManager->setObjectPermission($store, $store_owner_acl_code, $user_obj);
        // Create store on social bees
        // BLOCK SHOPPING PLUS
        //$this->createStoreOnSocialBees($de_serialize, $store->getId());
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }

    /**
     * List child store
     * @param Request $request
     * @return array;
     */
    public function postGetchildstoresAction(Request $request) {
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

        $required_parameter = array('user_id', 'parent_store_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //parameter check end
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get store type
        $store_id = $de_serialize['parent_store_id'];

        // get document manager object
        $em = $this->getDoctrine()->getManager();

        //check for parent store is exists or not.
        $parent_store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($store_id);
        if (!$parent_store_info) {
            return array('code' => 100, 'message' => 'PARENT_STORE_DOES_NOT_EXISTS', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

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
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        // get documen manager object
        $em = $this->getDoctrine()->getManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getChildStores($user_id, $limit_start, $limit_size, $store_id);

        if (!$stores) {
            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            return $res_data;
        }

        $final_result = array();
        foreach ($stores as $store_data) {
            $current_store_id = $store_data['id'];
            $current_store_profile_image_id = $store_data['storeImage'];
//            $current_user_id        = $store_data['userId'];
//            $user_service           = $this->get('user_object.service');
//            $user_object            = $user_service->UserObjectService($current_user_id);
//            $store_data['user_info'] = $user_object;
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

        //get record count
        $stores_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getChildStoresCount($user_id, $store_id);

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $final_result, 'size' => $stores_count));
        echo json_encode($res_data);
        exit();
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
     * Decode tha data
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
     * @param int $group_id
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
     * Create store on social bees
     * @param Request $request, int $store_id
     * @return boolean;
     */
    public function createStoreOnSocialBees($de_serialize, $store_id) {

        $user_email = "";
        $password = "";
        //get user object
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $de_serialize['user_id']));
        if ($user) {
            $user_email = $user->getEmail();
            $password = $user->getPassword();
        }

        $legal_status = $de_serialize['legal_status'];
        $business_address = $de_serialize['business_address'];
        $zip = $de_serialize['zip'];
        $business_city = $de_serialize['business_city'];
        $provience = $de_serialize['province'];
        $phone = $de_serialize['phone'];
        $user_email = $user_email; // (fos_user_user email)
        $description = $de_serialize['description'];
        $vat_number = $de_serialize['vat_number']; //vat_number ( this should be unique)
        $user_password = $password; // (fos_user_user password)
        $referral_id = $de_serialize['referral_id']; //fos_fos_user.id which is broker
        $type = 3;
        $virtual_status = "N";
        $importopdv_amount = 0;
        $step = 'Shop Registeration';
        $shop_status_shopping_plus = 'D';

        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
//        $shoppingplus_obj->registerShopShopingplus($store_id, $legal_status, $business_address, $zip, $business_city, $provience, $phone, $user_email, $description, $vat_number, $user_password, $referral_id, $virtual_status, $importopdv_amount, $type, $step, $shop_status_shopping_plus
//        );
    }

    /**
     * Edit store on social bees
     * @param Request $request, int $store_id
     * @return boolean;
     */
    public function editStoreOnSocialBees($de_serialize, $store_id) {

        $user_email = "";
        $password = "";
        //get user object
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $de_serialize['user_id']));
        if ($user) {
            $user_email = $user->getEmail();
            $password = $user->getPassword();
        }

        $legal_status = $de_serialize['legal_status'];
        $business_address = $de_serialize['business_address'];
        $zip = $de_serialize['zip'];
        $business_city = $de_serialize['business_city'];
        $provience = $de_serialize['province'];
        $phone = $de_serialize['phone'];
        $user_email = $user_email; // (fos_user_user email)
        $description = $de_serialize['description'];
        $vat_number = $de_serialize['vat_number']; //vat_number ( this should be unique)
        $user_password = $password; // (fos_user_user password)
        $referral_id = $de_serialize['referral_id']; //fos_fos_user.id which is broker
        $type = 3;
        $virtual_status = "N";
        $importopdv_amount = 0;
        $step = 'Shop Update';

        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        // BLOCK SHOPPING PLUS
//        $shoppingplus_obj->updateShopShopingplus($store_id, $legal_status, $business_address, $zip, $business_city, $provience, $phone, $user_email, $description, $vat_number, $user_password, $referral_id, $virtual_status, $importopdv_amount, $type, $step
//        );
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
     * Get top shops
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function topshopsperrevenueAction(Request $request) {
        //initilise the array
        $data = array();
        $top_shop_array = array();
        $store_info = array();
        
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $em = $this->getDoctrine()->getManager();
        //end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        if (isset($de_serialize['limit_size'])) {
            // $limit_start = $de_serialize['limit_start'];
            $limit_size = $de_serialize['limit_size'];

            //set dafault limit
            if ($limit_size == "") {
                $limit_size = 9;
            }

            //set default offset
            // if ($limit_start == "") {
            $limit_start = 0;
            // }
        } else {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $response = $call_type = '';
        
        try {
          $call_type = $this->container->getParameter('tx_system_call');
        } catch (\Exception $ex) {

        }
        
        // Call applane if call_type is set in parameters
        if($call_type == "APPLANE"){
            $offer = $em->getRepository('SixthContinentConnectBundle:Offer')
                                     ->findOneBy(array('isActive'=>1, 'isDeleted'=>0));
            $skipOfferShop= '';
            if($offer){
                $skipOfferShop = '"shop_id._id":{"$ne":"'.$offer->getShopId().'"}, ';
            }
            $api = 'query';
            $queryParam = 'query';
          //  $data = '{"$collection":"sixc_transactions","$filter":{"shop_id.is_shop_deleted":{"$in":[false,null]},"transaction_type_id":{"$in":["553209267dfd81072b176bba","553209267dfd81072b176bbc","553209267dfd81072b176bc0"]}},"$group":{"_id":"$shop_id._id","total_income":{"$sum":"$total_income"},"$sort":{"total_income":-1},"$fields":false,"$filter":{"total_income":{"$gt":0}}},"$limit":'.$limit_size.'}';
            $data = '{"$collection":"sixc_transactions","$filter":{"status":"Approved", '. $skipOfferShop .'"shop_id.is_shop_deleted":{"$in":[false,null]},"transaction_type_id":{"$in":["553209267dfd81072b176bba","553209267dfd81072b176bbc","553209267dfd81072b176bc0"]}},"$group":{"_id":"$shop_id._id","shop":{"$first":"$shop_id"},"total_income":{"$sum":"$total_income"},"$sort":{"total_income":-1},"$filter":{"total_income":{"$gt":0}}},"$limit":'.$limit_size.'}';
            
            //call applane service 
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            $response = $applane_service->callApplaneService($data, $api, $queryParam);
            $response = json_decode($response);
            
            if( isset($response->status) && $response->status == 'ok' ){
                $output =  array();
                
            //getting the store ids.
            $store_ids = array();
            foreach($response->response->result as $results){
               $store_ids[] = $results->_id;
            }

            $user_service  = $this->get('user_object.service');
            $store_info = $user_service->getMultiStoreObjectService($store_ids);
            //get category image store wise 
            $store_profile_images = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                                     ->getCategoryImageFromStoreIds($store_ids);
                foreach($response->response->result as $results){
                    
   
                    if(array_key_exists($results->_id, $store_info)){  

                        $store_id = $results->_id;
                        // $shop_thumb = isset($results->shop_thumbnail_img) ? $results->shop_thumbnail_img : ('http://www.sixthcontinent.com/app/assets/images/footerstore.jpg');
                       // if not get image from applane then get image from our database
                        //$shop_profile_image_thumb = !empty($store_info[$store_id]['thumb_path']) ? $store_info[$store_id]['thumb_path'] : ('https://www.sixthcontinent.com/app/assets/images/footerstore.jpg') ;
                        //check for store image or image with category
                        if(!empty($store_info[$store_id]['thumb_path'])) {
                            $shop_profile_image_thumb = $store_info[$store_id]['thumb_path'];
                        } else {
                            if(isset($store_profile_images[$store_id]['thumb_image']) && $store_profile_images[$store_id]['thumb_image'] != null) {
                                $shop_profile_image_thumb = $store_profile_images[$store_id]['thumb_image'];
                            } else {
                                $shop_profile_image_thumb = 'https://www.sixthcontinent.com/app/assets/images/footerstore.jpg';
                            }
                        }
                        
                        $shop_detail =array( 'id' => $store_id,
                                            'name' => $store_info[$store_id]['name'],
                                            'payment_status' => $store_info[$store_id]['paymentStatus'],
                                            'business_name' => $store_info[$store_id]['businessName'],
                                            'email' => $store_info[$store_id]['email'],
                                            'description' => $store_info[$store_id]['description'],
                                            'phone' => $store_info[$store_id]['phone'],
                                            'legal_status' => $store_info[$store_id]['legalStatus'],
                                            'business_type' => $store_info[$store_id]['businessType'],
                                            'business_country' => $store_info[$store_id]['businessCountry'],
                                            'business_region' => $store_info[$store_id]['businessRegion'],
                                            'business_city' => $store_info[$store_id]['businessCity'],
                                            'business_address' => $store_info[$store_id]['businessAddress'],
                                            'zip' => $store_info[$store_id]['zip'],
                                            'province' => $store_info[$store_id]['province'],
                                            'vat_number' => $store_info[$store_id]['vatNumber'],
                                            'iban' => $store_info[$store_id]['iban'],
                                            'map_place' => $store_info[$store_id]['mapPlace'],
                                            'latitude' => $store_info[$store_id]['latitude'],
                                            'longitude' => $store_info[$store_id]['longitude'],
                                            'parent_store_id' => $store_info[$store_id]['parentStoreId'], //for parent store
                                            'is_active' => $store_info[$store_id]['isActive'],
                                            'is_allowed' => $store_info[$store_id]['isAllowed'],
                                            'created_at' => $store_info[$store_id]['createdAt'],
                                            'shop_status' => $store_info[$store_id]['shop_status'],
                                            'credit_card_status' => $store_info[$store_id]['credit_card_status'],
                                            'profile_image_original' => $store_info[$store_id]['original_path'],
                                            'profile_image_thumb' => $shop_profile_image_thumb,
                                            'cover_image_path' => $store_info[$store_id]['cover_thumb_path'],
                                            'revenue' => (float) $results->total_income
                                        );

                        $output[] = $shop_detail;
                    }
                }
                
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $output);
                echo json_encode($res_data);
                exit;
            
            } else {
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        $topstores = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getTopShopsPerRevenue($limit_start, $limit_size);
        if (!$topstores) {
            $res_data = array('code' => 100, 'message' => 'NO_STORE_FOUND', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //getting the store ids.
        $store_ids = array_map(function($stores) {
             return "{$stores['storeId']}";
        }, $topstores);
        
        $user_service  = $this->get('user_object.service');
        $store_info = $user_service->getMultiStoreObjectService($store_ids);
        
        foreach ($topstores as $shop) {
            if(array_key_exists($shop['storeId'], $store_info)){

                //prepare store info array
                $store_data = array(
                    'id' => $store_info[$shop['storeId']]['id'],
                    'name' => $store_info[$shop['storeId']]['name'],
                    'payment_status' => $store_info[$shop['storeId']]['paymentStatus'],
                    'business_name' => $store_info[$shop['storeId']]['businessName'],
                    'email' => $store_info[$shop['storeId']]['email'],
                    'description' => $store_info[$shop['storeId']]['description'],
                    'phone' => $store_info[$shop['storeId']]['phone'],
                    'legal_status' => $store_info[$shop['storeId']]['legalStatus'],
                    'business_type' => $store_info[$shop['storeId']]['businessType'],
                    'business_country' => $store_info[$shop['storeId']]['businessCountry'],
                    'business_region' => $store_info[$shop['storeId']]['businessRegion'],
                    'business_city' => $store_info[$shop['storeId']]['businessCity'],
                    'business_address' => $store_info[$shop['storeId']]['businessAddress'],
                    'zip' => $store_info[$shop['storeId']]['zip'],
                    'province' => $store_info[$shop['storeId']]['province'],
                    'vat_number' => $store_info[$shop['storeId']]['vatNumber'],
                    'iban' => $store_info[$shop['storeId']]['iban'],
                    'map_place' => $store_info[$shop['storeId']]['mapPlace'],
                    'latitude' => $store_info[$shop['storeId']]['latitude'],
                    'longitude' => $store_info[$shop['storeId']]['longitude'],
                    'parent_store_id' => $store_info[$shop['storeId']]['parentStoreId'], //for parent store
                    'is_active' => $store_info[$shop['storeId']]['isActive'],
                    'is_allowed' => $store_info[$shop['storeId']]['isAllowed'],
                    'created_at' => $store_info[$shop['storeId']]['createdAt'],
                    'shop_status' => $store_info[$shop['storeId']]['shop_status'],
                    'credit_card_status' => $store_info[$shop['storeId']]['credit_card_status'],
                    'profile_image_original' => $store_info[$shop['storeId']]['original_path'],
                    'profile_image_thumb' => $store_info[$shop['storeId']]['thumb_path'],
                    'cover_image_path' => $store_info[$shop['storeId']]['cover_thumb_path'],
                    'revenue' => $shop['tot_fatturato']
                );
                
                $top_shop_array[] = $store_data;
            }
            
        }
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $top_shop_array);
        echo json_encode($res_data);
        exit;
    }

    /**
     * Get top citizen per income
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function topcitizenperincomeAction(Request $request) {
        
        //initilise the array
        $data = array();
        $response = array();
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
        //end
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        if (isset($de_serialize['limit_size'])) {
            $limit_start = isset($de_serialize['limit_start'])?$de_serialize['limit_start']:0;
            $limit_size = isset($de_serialize['limit_size'])?$de_serialize['limit_size']:9;

            //set dafault limit
            if ($limit_size == "") {
                $limit_size = 9;
            }
        } else {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $response = $call_type = '';
        
        try {
          $call_type = $this->container->getParameter('tx_system_call');
        } catch (\Exception $ex) {

        }
        
        // Call applane if call_type is set in parameters
        if($call_type == "APPLANE"){
           
            $api = 'query';
            $queryParam = 'query';
   //        $data = '{"$collection":"sixc_bucks_wallet","$group":{"_id":"$citizen_id._id","total_amount":{"$sum":"$credit"},"name":{"$first":"$citizen_id.name"},"email_id":{"$first":"$citizen_id.email_id"},"user_thumbnail_image":{"$first":"$citizen_id.user_thumbnail_image"},"$sort":{"total_amount":-1}},"$filter":{"citizen_id.is_master":{"$in":[null, false]},"record_from":"CI"},"$limit":'.$limit_size.'}';
            $data = '{"$collection":"sixc_citizens","$fields":{"credit":1},"$filter":{"is_master":{"$in":[null, false]}},"$sort":{"credit":-1},"$limit":'.$limit_size.'}'; 
            //call applane service 
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            $response = $applane_service->callApplaneService($data, $api, $queryParam);
            $response = json_decode($response);
                        
            if( isset($response->status) && $response->status == 'ok' ){
                $output =  array();

                //getting the citizen ids.
                $citizen_ids = array();
                foreach($response->response->result as $results){
                    $citizen_ids[] = $results->_id;
                }
               
                $user_service  = $this->get('user_object.service');
                $user_object = $user_service->MultipleUserObjectService($citizen_ids);
                
                foreach($response->response->result as $results){
                    $citizen_id = $results->_id;
                    if(array_key_exists($results->_id, $user_object)){
                        $user_thumb = $user_object[$citizen_id]['profile_image_thumb'];
                       
                        $profile_image_thumb = !empty($user_thumb) ? $user_thumb : ('https://www.sixthcontinent.com/app/assets/images/profile-image.jpg') ;

                        $user_info = array( "id" => $results->_id,
                            "username" => $user_object[$citizen_id]['email'],
                            "email" => $user_object[$citizen_id]['email'],
                            "first_name" => $user_object[$citizen_id]['first_name'],
                            "last_name" => $user_object[$citizen_id]['last_name'],
                            "gender" => $user_object[$citizen_id]['gender'],
                            "phone" =>$user_object[$citizen_id]['phone'],
                            "date_of_birth" =>$user_object[$citizen_id]['date_of_birth'],
                            "country" => $user_object[$citizen_id]['country'],
                            "country_name" => $user_object[$citizen_id]['country_name'],
                            "country_code" => $user_object[$citizen_id]['country_code'],
                            "profile_type" => $user_object[$citizen_id]['profile_type'],
                            "citizen_profile" => $user_object[$citizen_id]['citizen_profile'],
                            "store_profile" => $user_object[$citizen_id]['store_profile'],
                            "active" => $user_object[$citizen_id]['active'],
                            "profile_image_original" => $user_object[$citizen_id]['profile_image'],
                            "profile_image_thumb" => $profile_image_thumb,
                           // "user_thumbnail_image"=>$profile_image_thumb,
                           // "profile_image_thumb" => $results->user_thumbnail_image,
                            "cover_image" => $user_object[$citizen_id]['cover_image'],
                            "cover_image_thumb" => $user_object[$citizen_id]['cover_image_thumb'],
                            "album_id" => $user_object[$citizen_id]['album_id'],
                            "current_language" => $user_object[$citizen_id]['current_language']
                        );

                        $total_income = $results->credit;

                        $user_detail['user_info'] = $user_info;
                        $user_detail['totalone'] = $total_income;
                        $output[] = $user_detail; 
                    }
                }
                
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $output);
                echo json_encode($res_data);
                exit;
            
            } else {
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        try {
            $ci_calls = $this->container->getParameter('CI_calls'); //get server
        } catch (\Exception $e) {
            $ci_calls = 'SP';
        }
        //check from where we have to call the ser ice for CI
        if ($ci_calls == 'SP') {
            $topcitizen = $em
                    ->getRepository('StoreManagerStoreBundle:TotoCitizenIncome')
                    ->getTopCitizenPerIncome($limit_start, $limit_size);
            if (!$topcitizen) {
                $res_data = array('code' => 100, 'message' => 'NO_CITIZEN_FOUND', 'data' => $data);
                echo json_encode($res_data);
                exit;
            }

            //getting the citizen ids.
            $citizen_ids = array_map(function($citizen) {
                 return "{$citizen->getIdentityId()}";
            }, $topcitizen);
            
            $user_service  = $this->get('user_object.service');
            $user_info = $user_service->MultipleUserObjectService($citizen_ids);
            
            foreach ($topcitizen as $citizen) {
                //get info
                $id = $citizen->getId();
                $user_id = $citizen->getIdentityId();
                $numero_affilati = $citizen->getNumeroAffiliati();
                $total_economy = $citizen->getTotaleEconomia();
                $guadagno_affiliazioni = $citizen->getGuadagnoAffiliazioni();
                $data_update = $citizen->getDataUpdate();
                $totalone = $citizen->getTotalone();

                if (array_key_exists($user_id,$user_info)) {
                    $response[] = array('user_info' => $user_info[$user_id], 'numero_affilati' => $numero_affilati,
                        'total_economy' => $total_economy, 'guadagno_affiliazioni' => $guadagno_affiliazioni,
                        'data_update' => $data_update, 'totalone' => $totalone);
                }
            }
        } else {
            $topcitizen = $em
                    ->getRepository('StoreManagerStoreBundle:TotoCitizenIncome')
                    ->getTopCitizenPerIncomeFromDB($limit_start, $limit_size);
            if (!$topcitizen) {
                $res_data = array('code' => 100, 'message' => 'NO_CITIZEN_FOUND', 'data' => $data);
                echo json_encode($res_data);
                exit;
            }
            
            //getting the citizen ids.
            $citizen_ids = array_map(function($citizen) {
                 return "{$citizen['userId']}";
            }, $topcitizen);
            
            $user_service  = $this->get('user_object.service');
            $user_info = $user_service->MultipleUserObjectService($citizen_ids);
            
            foreach ($topcitizen as $citizen) {
                //get info
                $user_id = $citizen['userId'];
                $totalone = self::convertToEuro($citizen['totalCitizenIncome']);

                if (array_key_exists($user_id,$user_info)) {
                    $response[] = array('user_info' => $user_info[$user_id], 'totalone' => $totalone);
                }
            }
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $response);
        echo json_encode($res_data);
        exit;
    }

    /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
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
     *  Function for converting the amount  to EURO
     * @param type $money
     * @return type
     */
    static function convertToEuro($money) {
       return $money/1000000;
   }

}
