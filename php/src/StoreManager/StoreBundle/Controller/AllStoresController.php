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
use Transaction\WalletBundle\Entity\WalletCitizen;
use StoreManager\StoreBundle\Document\Affiliation;
#use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\BusinessCategory;
use StoreManager\StoreBundle\Entity\StoreJoinNotification;
use Notification\NotificationBundle\Document\UserNotifications;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationShop;
use StoreManager\StoreBundle\Entity\Storeoffers;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use StoreManager\PostBundle\Document\ItemRating;
use Transaction\CommercialPromotionBundle\Entity\CommercialPromotion;

class AllStoresController extends Controller {

    protected $profile_image_path = '/uploads/documents/stores/gallery/';
    protected $shoppingcart_image_path = '/uploads/scard100/m_';
    protected $coupon_image_path = '/uploads/coupon/m_';

    /**
     * search store based on the filters
     * @param request object
     * @param json
     */
    public function allstoresdetailsAction(Request $request) {
        // initilise the array
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

        $object_info = $de_serialize; // convert an array into object.
        $em = $this->getDoctrine()->getManager();
        //getting the store list
        $allstores = array();
        
        // Get All stores result 
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllStoresDetails($object_info, $bucket_path);
         
       // Get All store count 
        
        $storecount = 0;
        $storecount = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllStoresDetails($de_serialize, $bucket_path, true);
        
        if (!empty($storecount)) {
        
           $storecount = $storecount[0]['totalcount'];
         }


        $wallet_repo = $em->getRepository('WalletBundle:WalletCitizen');
        $wallet = $wallet_repo->getavailablecitizenincome($de_serialize["user_id"]);
        $ci = isset($wallet["0"]["citizenIncomeAvailable"])?$wallet["0"]["citizenIncomeAvailable"]:0;


        $repostoremedia = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia');

       $storeprofileimages = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory');
       

        foreach ($stores as $str_details) {

            $str_details['shopRating'] = (float) $str_details['shopRating'];
            $str_details['latitude'] = (float) $str_details['latitude'];
            $str_details['longitude'] = (float) $str_details['longitude'];
            $str_details['isActive'] = (bool) $str_details['isActive'];
            $str_details['paymentStatus'] = (int) $str_details['paymentStatus'];
            $str_details['shopStatus'] = (int) $str_details['shopStatus'];
            $str_details['new_contract_status'] = (int) $str_details['new_contract_status'];
             
             $cat_thmb_data = $repostoremedia->getCategoryAndMediaImage($str_details['catogory_id']);
             $str_details['image'] = (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['image'] : '';
             $str_details['image_thumb'] =  (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['image_thumb'] : '';
             $str_details['shop_category'] = (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['catname']: '';
                    
             $media_img  =  $repostoremedia->findBy(array('storeId' => $str_details['storeId']));

             if(empty($media_img)){ 
               $store_profile_images = $storeprofileimages->getCategoryImageFromStoreIds($str_details['storeId']);
               $img = $store_profile_images[$str_details['storeId']]['thumb_image'];
               
               if($img == ""){
                  $img =  $this->getS3BaseUri().'/uploads/businesscategory/thumb/default_store.png';
                }
              } 

            // Get thumb and original images from stores result 

            $profile_image_path_thumb = (!empty($media_img)) ? $this->getS3BaseUri() . $this->profile_image_path . $str_details['storeId'] . '/thumb/' . $media_img[0]->getimageName() : $img;

            $profile_image_path_original = (!empty($media_img)) ? $this->getS3BaseUri() . $this->profile_image_path . $str_details['storeId'] . '/original/' . $media_img[0]->getimageName() : $img;

            $str_details['profile_image_thumb'] = $profile_image_path_thumb;
            $str_details['profile_image_original'] = $profile_image_path_original;

            // Get citizen credit available   

            $str_details['credit_available'] = "0.00";
            
            /*
            $credit_available = $em
                    ->getRepository('WalletBundle:WalletCitizen')
                    ->getCreditAvailableInShop($str_details['id'], $str_details['storeId']);
            */
            $sc_value = 0;
            $cp_value = 0;
            $amt_value = 0;
            /*

            if ($credit_available['citizen_income']) {

                $ci = $credit_available['citizen_income']['citizenIncomeAvailable'];
            }

            foreach ($credit_available['shopping_card'] as $sc_data) {

                $sc_value += $sc_data->getavailableAmount();
            }

            if (!empty($credit_available['coupon'])) {

                $cp_value = $credit_available['coupon']['availableAmount'];
            }

            if (!empty($credit_available['credit_postion'])) {

                $amt_value = $credit_available['credit_postion']['creditPositionAvailable'];
            }
            */
            $total = $ci + $sc_value + $cp_value + $amt_value;

            $str_details['credit_available'] = number_format($total / 100, 2, '.', '');

            
            /*  Get all affilate chargers */
            
            // $str_details['citizenAffCharge'] =  $str_details['citizen_aff_charge'];
            // $str_details['shopAffCharge'] =  $str_details['shop_aff_charge'];            
            // $str_details['friendsFollowerCharge'] =  $str_details['friends_follower_charge'];               
            // $str_details['buyerCharge'] =  $str_details['buyer_charge'];              
            // $str_details['sixcCharge'] =  $str_details['sixc_charge'];                
            // $str_details['allCountryCharge'] = $str_details['all_country_charge'];  


            // Count Favourite of shop  

            $favourite_results = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->favouriteStore($str_details['storeId'], $str_details['id']);
            $str_details['is_fav'] = (!empty($favourite_results)) ? 1 : 0;
            $str_details['type'] = "store";


            // Count Friend of shop owner which purches from his shop 
            $friendcount_results = 0;
            /*
            $friendcount_results = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->friendCount($str_details['id'], $str_details['storeId']);
            */
            $str_details['friend_count'] = (!empty($friendcount_results)) ? $friendcount_results : 0;

            $allstores[] = $str_details;
        }


        // Calculate Paginataion 

        $limits = (isset($de_serialize['limits']) ? $de_serialize['limits'] : '');

        $offset = (isset($limits['limit_start']) ? $limits['limit_start'] : '');

        $limit_size = (isset($limits['limit_size']) ? $limits['limit_size'] : '');

        if ($offset == 0) {

            $check_status = $limit_size;
        } else {

            $check_status = ($offset * $limit_size);
        }

        if ($check_status > $storecount) {

            $hasNext['hasNext'] = array('hasNext' => false);
        } else {

            $hasNext['hasNext'] = array('hasNext' => true);
        }

        $size['size'] = (isset($de_serialize["seller_id"]) && $de_serialize["seller_id"] > 0)?1:(int) $storecount;

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores' => $allstores) + $hasNext + $size);

        echo json_encode($res_data);
        exit();
    }

    /**
     * search store city based on the filters
     * @param request object
     * @param json
     */
    public function citylistAction(Request $request) {


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
        $object_info = $de_serialize; // convert an array into object.

        $em = $this->getDoctrine()->getManager();

        //getting the store list
        $citylist = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getcitylist($object_info);

        $res_data = array('response' => array('result' => $citylist));
        echo json_encode($res_data);
        exit();
    }

    /**
     * search store city based on the filters
     * @param request object
     * @param json
     */
    public function getAllShopListAction(Request $request) {

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

        // Parameter check start
        
        $object_info = $de_serialize; // convert an array into object.
        $em = $this->getDoctrine()->getManager();


        $friend_name = '';
         
        // Get voucher  

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $repo_commercial_offer = $em->getRepository("CommercialPromotionBundle:CommercialPromotion");
        $voucher_detail = $repo_commercial_offer->searchCommercialPromotionAllStore($friend_name , $dm); 
   
        // getting the store list
        
        $city_list = $em
                ->getRepository('StoreManagerStoreBundle:Store');
               
        $citylist  = $city_list->getShopList($object_info);


        foreach($citylist as $getshoplist) {
       
            $getshoplist['promotionType'] = 'shop'; 
            $getshoplist['label'] = 'Shop'; 
            $citylistnew[] = $getshoplist; 

          }


         foreach ($voucher_detail as $getdata) {
                $getdata['shopname'] = $getdata['description'];
                $getdata['label'] = 'Commercial offer'; 
                $citylistnew[] = $getdata;
           } 

        $res_data = array('response' => array('result' => $citylistnew));


     
        echo json_encode($res_data);
        exit();
    }

   /**
     * search shopping and coupon list based on the filters
     * @param request object
     * @param json
     */
    public function getallshoppingcardsAction(Request $request) {

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
        $object_info = $de_serialize; // convert an array into object.


        $em = $this->getDoctrine()->getManager();

        $alldata = array();

        // Get the all shop list

        $allshoplist = $em
                ->getRepository('WalletBundle:ShoppingCard')
                ->getallshoppingcardslist($object_info);

        // Get All records count 

        $total_count = $em
                ->getRepository('WalletBundle:ShoppingCard')
                ->getallshoppingcardsCount($object_info, $bucket_path);

        if (!empty($total_count)) {
            $total_count = $total_count[0]['totalcount'];
        }
        
        $wallet_repo = $em->getRepository('WalletBundle:WalletCitizen');
        $comm_promotion_repo = $em->getRepository('CommercialPromotionBundle:CommercialPromotion');
        
        $wallet_array = $wallet_repo->getavailablecitizenincome($de_serialize["user_id"]);

        $ci_available =  $wallet_array[0]['citizenIncomeAvailable'];
        
        if(isset($de_serialize['type']) && (  $de_serialize['type']=="shoppingcard" || $de_serialize['type']=="voucher" || $de_serialize['type']=="genericvoucher")){
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
        }
        
     
        $repostoremedia = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia');

        $storeprofileimages = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory');
      
     
        foreach ($allshoplist as $get_shop_details) {
                                 
            $get_shop_details['credit_available'] = number_format($ci_available / 100, 2, '.', '');
            

            // Count Favourite of shop  

            $favourite_results = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->favouriteStore($get_shop_details['shopid'], $get_shop_details['shopownerid']);
            $get_shop_details['is_fav'] = (!empty($favourite_results)) ? 1 : 0;

            // Get price for you 
            $ci_available  = $ci_available ;
            $discount  = $get_shop_details["discount"];
            $init_amount = $get_shop_details["price"];


             $cat_thmb_data = $repostoremedia->getCategoryAndMediaImage($get_shop_details['catogory_id']);

             $get_shop_details['image'] = (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['image'] : '';
             $get_shop_details['image_thumb'] =  (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['image_thumb'] : '';
             $get_shop_details['catname'] = (!empty($cat_thmb_data)) ? $cat_thmb_data[0]['catname']: '';
  
              // Get shop thumb image 

              $store_images  =  $repostoremedia->findBy(array('storeId' => $get_shop_details['shopid']));

                if (empty($store_images)) {
                 
                 $store_profile_images = $storeprofileimages->getCategoryImageFromStoreIds($get_shop_details['shopid']);
                  $shop_thumb_img = $store_profile_images[$get_shop_details['shopid']]['thumb_image'];
                 if($shop_thumb_img == ""){
                    $shop_thumb_img =  $this->getS3BaseUri().'/uploads/businesscategory/thumb/default_store.png';
                   }
           
               }
               
                else 
                {
                  $shop_thumb_img = $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $get_shop_details['shopid'] . "/thumb/" . $store_images[0]->getimageName();
                }                 
           
            
            $price_for_you = $comm_promotion_repo->calculatePriceForYou($ci_available ,$discount ,$init_amount ,  $wallet_repo , $get_shop_details['cp_type_id']);
            
            $shop_id['shop_id'] = array(
                'category_id' => array(
                    '_id' => $get_shop_details['catogory_id'],
                    'name' => $get_shop_details['catname']
                ),
                'country' => array(
                    '_id' => '',
                    'countryname' => $get_shop_details['country']
                ),
                'shopowner_id' => array(
                    '_id' => $get_shop_details['shopownerid'],
                    'name' => ($get_shop_details['promotion_type']=="voucher" || $get_shop_details['promotion_type']=="genericvoucher"  )?substr($get_shop_details['cpt_description'],0,36):$get_shop_details['name']
                ),
                '_id' => $get_shop_details['_id'],
                'shopid' => $get_shop_details['shopid'],
                'is_fav' => $get_shop_details['is_fav'],
                'credit_available' => $get_shop_details['credit_available'],
                'shopRating' => (float) $get_shop_details['shopRating'],
                'address_l1' => $get_shop_details['address_l1'],
                'address_l2' => $get_shop_details['address_l2'],
                'name' => ($get_shop_details['promotion_type']=="voucher" || $get_shop_details['promotion_type']=="genericvoucher" )?substr($get_shop_details['cpt_description'],0,36):$get_shop_details['name'],
                'is_shop_deleted' => $get_shop_details['is_shop_deleted'],
                'latitude' => (float) $get_shop_details['latitude'],
                'longitude' => (float) $get_shop_details['longitude'],
                'shop_thumbnail_img' => $shop_thumb_img,
               
                "total_votes" => $get_shop_details['total_votes'],
                "promotion_type" => $get_shop_details['promotion_type'],
                "cp_type_id" => $get_shop_details['cp_type_id']
            );
            if($get_shop_details['promotion_type']=="shoppingcard" || $get_shop_details['promotion_type']=="voucher" || $get_shop_details['promotion_type']=="genericvoucher" ){
                $search["id"] = $get_shop_details['_id'];
                $search["commercialPromotionTypeId"] = $get_shop_details["cp_type_id"];
                $get_shop_details_new["extra_information"] = $comm_promotion_repo->getExtraInfoForCommercialPromotion( $search , $dm);
            }


            // Count Friend of shop owner which purches from his shop 
            $friendcount_results = 0;
            /**
            $friendcount_results = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->friendCount($get_shop_details['shopownerid'], $get_shop_details['shopid']);
             * 
             */
            $get_shop_details_new['friend_count'] = (!empty((int) $friendcount_results)) ? $friendcount_results : 0;


            $get_shop_details_new['_id'] = $get_shop_details['_id'];
            $get_shop_details_new['descriptions'] = $get_shop_details['descriptions'];
            $get_shop_details_new['max_usage_init_price'] = $get_shop_details['max_usage_init_price'];
            $get_shop_details_new['discount'] =  $price_for_you['discounted_value_dp'];
            $get_shop_details_new['end_date'] = date('d-m-Y', strtotime($get_shop_details['end_date']));
            $get_shop_details_new['start_date'] = date('d-m-Y', strtotime($get_shop_details['start_date']));
            $get_shop_details_new['keywords'] = $get_shop_details['keywords'];
            $get_shop_details_new['value'] = $price_for_you["init_amount_dp"];
            $get_shop_details_new['to_avail'] = $get_shop_details['to_avail'];
            $get_shop_details_new['price_for_you'] = $price_for_you['cashpayment_dp'];

            $get_shop_details_new['six_contribution'] = $price_for_you['sixthcontinent_contribution_dp'];

            if ($de_serialize['type'] != "coupon") {

                $get_shop_details_new['imageurl'] = $get_shop_details['defaultimg'];
            } else {

                $get_shop_details_new['imageurl'] = $this->getS3BaseUri() . $this->coupon_image_path . ($get_shop_details['price'] / 100) . '.png';
            }

            $alldata[] = $get_shop_details_new + $shop_id ;
        }


        // Calculate Paginataion 

        $offset = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : '');
        $limit = (isset($de_serialize['limit_end']) ? $de_serialize['limit_end'] : '');

        if ($offset == 0) {

            $check_status = $limit;
        } else {

            $check_status = ($offset * $limit);
        }

        if ($check_status > $total_count) {

            $hasNext['hasNext'] = array('hasNext' => false);
        } else {

            $hasNext['hasNext'] = array('hasNext' => true);
        }

        $size['size'] = (int) $total_count;

        $res_data = array('status' => 'ok', 'code' => '101', 'response' => array('result' => $alldata) + $hasNext + $size);

        echo json_encode($res_data);
        exit();
    }

    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
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
  
   
}

?>