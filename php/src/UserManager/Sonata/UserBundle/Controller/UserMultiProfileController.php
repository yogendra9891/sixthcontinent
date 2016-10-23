<?php

namespace UserManager\Sonata\UserBundle\Controller;

use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use Symfony\Component\Locale\Locale;
use Media\MediaBundle\Document\UserMedia;
use UserManager\Sonata\UserBundle\Document\UserPhoto;
use UserManager\Sonata\UserBundle\Document\StudyList;
use UserManager\Sonata\UserBundle\Entity\UserActiveProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\BrokerUser;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
class UserMultiProfileController extends FOSRestController {

   // image path
    protected $user_media_album_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path_thumb_cover = '/uploads/users/media/thumb_crop/';
    protected $user_media_album_path = '/uploads/users/media/original/';
    protected $suggestion_limit = 20;
    /**
     * MultiProfile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postRegistermultiprofilesAction(Request $request) {

        //initilise the data array
        $id = 0;
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        $first_name = $de_serialize['first_name'];
        $last_name = $de_serialize['last_name'];
        $gender = $de_serialize['gender'];
        $bdate = $de_serialize['birthday'];
        $phone = $de_serialize['phone'];
        $ccode = $de_serialize['country'];
        $profile_setting = $de_serialize['profile_setting'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }

        //get user email
        $user_email = $this->getUserEmail($user_id);
        //check if ccode is valid
        $countryLists = Locale::getCountries();
        if (!in_array($ccode, $countryLists)) {
            $data = array('code' => 129, 'message' => 'INVALID_COUNTRY_CODE', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        //check for male and female
        $allow_gender = array('m', 'f');
        if (!in_array($gender, $allow_gender)) {
            $data = array('code' => 130, 'message' => 'INVALID_GENDER_TYPE', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        //check for profile setting 
        //check for male and female
        $allow_setting = array('1', '2', '3');
        if (!in_array($profile_setting, $allow_setting)) {
            $data = array('code' => 133, 'message' => 'INVALID_PROFILE_SETTING', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        $street = $de_serialize['street'];
        $profile_type = $de_serialize['profile_type'];

        //check for profile type
        $allow_profile_type = array('22', '24'); //user can only create citizen and broker profile
        if (!in_array($profile_type, $allow_profile_type)) {
            $data = array('code' => 132, 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $user_multiprofile = new UserMultiProfile();
        //check if user has already ctreated the profile for profile type
        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findOneBy(array('userId' => $user_id, 'profileType' => $profile_type));

        //check if user edit his profile
        if (count($results) > 0) {
            $id = $results->getId();
            $user_multiprofile = $em
                    ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                    ->findOneBy(array('id' => $id));
        }

        $user_multiprofile->setUserId($user_id);
        $user_multiprofile->setEmail($user_email);
        if ($first_name != "") {
            $user_multiprofile->setFirstName($first_name);
        }
        if ($last_name != "") {
            $user_multiprofile->setLastName($last_name);
        }
        if ($gender != "") {
            $user_multiprofile->setGender($gender);
        }
        //get birth date date object
        $btime = new \DateTime($bdate);
        if (!$btime) {
            $data = array('code' => 131, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
        if ($bdate != "") {
            $user_multiprofile->setBirthDate($btime);
        }
        if ($phone != "") {
            $user_multiprofile->setPhone($phone);
        }
        if ($ccode != "") {
            $user_multiprofile->setCountry($ccode);
        }
        if ($street != "") {
            $user_multiprofile->setStreet($street);
        }
        if ($profile_type != "") {
            $user_multiprofile->setProfileType($profile_type);
        }
        if ($profile_type == 24 || $profile_type == 25) {
            $user_multiprofile->setIsActive(0);
        } else {
            $user_multiprofile->setIsActive(1);
        }
        $time = new \DateTime("now");
        if ($id == 0) {
            $user_multiprofile->setCreatedAt($time);
        }
        $user_multiprofile->setUpdatedAt($time);

        if ($profile_setting != "") {
            $user_multiprofile->setProfileSetting($profile_setting);
        }

        $em->persist($user_multiprofile);
        $em->flush();

        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo $this->encodeData($data);
        exit;
    }

   /**
    * Get user profile
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postViewmultiprofilesAction(Request $request) {
        
        
        $data = array();
         $em = $this->getDoctrine()->getManager();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
       
        $profile_type = $de_serialize['profile_type'];
        $user_id = $de_serialize['user_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }

        //check for profile type
        $allow_profile_type = array('1', '2' ,'4', '5');
        if (!in_array($profile_type, $allow_profile_type)) {
            $data = array('code' => 132, 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        if (!array_key_exists('lang_code', $de_serialize)) {
            $de_serialize['lang_code'] = 'en';
        }
        
        $results = array();
        
        if($profile_type == 4){
            $results = $this->getBasicProfile($user_id, $de_serialize['lang_code']);
        }
        
        if($profile_type == 1){
            $results = $this->getCitizenProfile($user_id);
        }
        
        if($profile_type == 2){
            $results = $this->getBrokerProfile($user_id);
        }
        
        //common profile for base and citizen
        if($profile_type == 5){
             $results_baseprofile = $this->getBasicProfile($user_id, $de_serialize['lang_code']);
             
             $results_citizenprofile = $this->getCitizenShortProfile($user_id);

             $result_citizen_skills = $this->getSkills($user_id);
         
             $results = array_merge($results_baseprofile, $results_citizenprofile, $result_citizen_skills);
             
            
        }
        
        // facebook info with profile info
        $fbInfo = array('id'=>'', 'expires'=>'', 'status'=>0);
        try{
            $fbResults = $em
                    ->getRepository('UserManagerSonataUserBundle:FacebookUser')
                    ->findOneBy(array('userId' => $user_id));
            if($fbResults){
                $exTime = $fbResults->getExpiryTime();
                $curTime = time();
                $dateDiff = $exTime-$curTime;
                $daysLeft = $dateDiff>0 ? floor($dateDiff/(60*60*24)) : 0;
                $fbInfo['id'] = $fbResults->getFacebookId();
                $fbInfo['expires'] = $daysLeft;
                $fbInfo['publish_actions'] = $fbResults->getPublishActions();
                $fbStatus = $fbResults->getSyncStatus()>0 ? 1 :0;
                if($daysLeft==0){
                    $fbResults->setSyncStatus(0);
                    $em->persist($fbResults);
                    $em->flush();
                    $fbStatus = 0;
                }
                $fbInfo['status'] = $fbStatus;
            }
        }catch(\Exception $e){
            
        }
        $results['facebook_profile'] = $fbInfo;
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $results);
        echo $this->encodeData($data);
        exit;
        
    }
    
    /**
     * Get base profile
     * @param type $user_id
     * @return array
     */
    public function getBasicProfile($user_id,$lang_code = 'en')
    {
        //get country code with country name
        //@return arry
        $countryLists = Locale::getDisplayCountries('en');
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('id'=>$user_id));
        
        $educationDetails = $em
                ->getRepository('UserManagerSonataUserBundle:EducationDetails')
                ->getEducationDetails($user_id);
       
        $jobDetails = $em
                ->getRepository('UserManagerSonataUserBundle:JobsDetails')
                ->getJobDetails($user_id);
        $categoryKeywords = $em
                ->getRepository('UserManagerSonataUserBundle:UserCategoryKeywords')
                ->getCategoryKeywords($user_id,$lang_code);
        
        $userRelatives = $em
                ->getRepository('UserManagerSonataUserBundle:Relatives')
                ->getRelatives($user_id);
             
        $app_url = array();
        $user_service = $this->get('user_object.service');
        $app_url = $user_service->getAppUrlForShop($user_id);
        $id = $results->getId();
        $email = $results->getEmail();
        $user_name = $results->getUsername();
        $firstname = $results->getFirstname();
        $lastname = $results->getLastname();
        $dob = $results->getDateOfBirth();
        $gender = $results->getGender();
        $base_profile_img_id = $results->getProfileImg();
        $base_cover_img_id = $results->getCoverImg();
        $base_profile_img = $this->getUserProfileImage($base_profile_img_id, $user_id);
        //$base_cover_img = $this->getUserProfileImage($base_cover_img_id, $user_id);
        $base_cover_img = $this->getUserCoverImage($base_cover_img_id, $user_id);
        $country = $results->getCountry();
        $state = $results->getState();
        $citizen_profile = $results->getCitizenProfile();
        $broker_profile = $results->getBrokerProfile();
        $store_profile = $results->getStoreProfile();
        $broker_profile_is_active = $results->getBrokerProfileActive();
        $hobbies = $results->getHobbies();
        $relationship = $results->getRelationship();
        $about_me = $results->getAboutMe();
        $city_born = $results->getCityBorn();
        $current_language = $results->getCurrentLanguage();
		
        //get shop payment status
        $shop_results = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getUserShop($user_id);
        $payment_status = 3; // if no shop, then payment status will be 3
        if($shop_results){
            $payment_status = 1; //payment status is  done.
            foreach($shop_results as $shop_result){
                $shop_payment_status = $shop_result['paymentStatus'];
                if($shop_payment_status == 0){
                    $payment_status = 0; //payment status not done
                }
                
            }
        }
        //making the array for broker profile.
        $broker_profile_array = array('broker_profile_exists'=>$broker_profile, 'is_active'=>$broker_profile_is_active);
                
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

        $user_base_info = array('user_id'=>$id, 
            'email'=> $email, 
            'username'=>$user_name,
            'firstname'=>$firstname, 
            'lastname'=>$lastname, 
            'date_of_birth' =>$dob, 
            'gender' =>$gender,
            'city_born' =>$city_born,			
            'country' =>$country_name,
            'country_name' => $cc_name,
            'country_code' =>$cc_code,
            'state' =>$state,
            'profile_img' => $base_profile_img,
            'profile_cover_img' => $base_cover_img,
            'hobbies' => $hobbies,
            'relationship' => $relationship,
            'about_me' => $about_me,
            'citizen_profile' => $citizen_profile,
            'broker_profile' => $broker_profile_array,
            'store_profile' => $store_profile,
            'payment_status' =>$payment_status,
            'app_url' =>$app_url,
            'educationDetail' =>$educationDetails,
            'jobDetails'=>$jobDetails,
            'categoryKeywords'=>$categoryKeywords,
            'userRelatives'=>$userRelatives,
            'current_language'=>$current_language,
            'profile_image' => $base_profile_img['original'],
            'profile_image_thumb' => $base_profile_img['thumb'],
            'cover_image' => $base_cover_img['original'],
            'cover_image_thumb' => $base_cover_img['thumb']
            );
            //adding contract param if shop exists
            if($shop_results){
                foreach($shop_results as $shop_result){
                    $new_conract_status=  $shop_result['newContractStatus']; 
                    $new_store_id=$shop_result['id'];
                } 
                $user_base_info['new_conract_status']= (int) $new_conract_status; 
                $user_base_info['shop_id']=  $new_store_id;
            }
    
          return $user_base_info;
    }

    /**
     * Get citizen profile
     * @param int $user_id
     * @return arry
     */
    public function getCitizenProfile($user_id)
    {
       
        $citizen_user_info = array();
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $citizenUser = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId'=>$user_id));
       if(!$citizenUser){
           $data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
           echo $this->encodeData($data);
           exit;
       }
        //get user info
        $id = $citizenUser->getId();
        $user_id = $citizenUser->getUserId();
        $role_id = $citizenUser->getRoleId();
        $region = $citizenUser->getRegion();
        $city = $citizenUser->getCity();
        $address = $citizenUser->getAddress();
        $zip = $citizenUser->getZip();
        $map_place = $citizenUser->getMapPlace();
        $latitude = $citizenUser->getLatitude();
        $longitude = $citizenUser->getLongitude();
        $citizen_profile_img_id = $citizenUser->getProfileImg();
        $citizen_profile_img = $this->getUserProfileImage($citizen_profile_img_id, $user_id);
        $citizen_cover_img = $this->getUserCoverImage($citizen_profile_img_id, $user_id);
        
        //get citizen refferer
        $citizen_affiliater = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                ->findOneBy(array('toId' =>$user_id));
        if($citizen_affiliater){
        //get affliliater id
        $affiliater_id = $citizen_affiliater->getFromId();
        //get affiliater object
        $user_service           = $this->get('user_object.service');
        $user_object            = $user_service->UserObjectService($affiliater_id);
        } else {
            $user_object = array();
        }
        
        //finding the user district,block and position
        $position_result = $this->getDistrictBlockPosition($user_id);
        
        //get basic profile
        $basic_profile_results = $this->getBasicProfile($user_id);
        //create citizen info array
        $citizen_user_info = array('id'=>$id,
            'user_id' => $user_id,
            'role_id' => $role_id,
            'region' => $region,
            'city' => $city,
            'address' => $address,
            'zip' => $zip,
            'map_place' => $map_place,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'profile_img' => $citizen_profile_img,
            'district_number' => $position_result->district,
            'block_group_number' => $position_result->block,
            'position_in_block' => $position_result->position,
            'referral_info' => $user_object,
            'baisc_profile_info' => $basic_profile_results,
            'profile_image' => $citizen_profile_img['original'],
            'profile_image_thumb' => $citizen_profile_img['thumb'],
            // 'cover_img'=>$citizen_cover_img
             );
        
        return $citizen_user_info;
    }
    
    /**
     * Get Citizen short info to show on edit profile page
     * @param int $user_id
     * @return array
     */
    public function getCitizenShortProfile($user_id)
    {
        $citizen_user_info = array();
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $citizenUser = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId'=>$user_id));
       if(!$citizenUser){
           $data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
           echo $this->encodeData($data);
           exit;
       }
        //get user info
        $id = $citizenUser->getId();
        $user_id = $citizenUser->getUserId();
        $role_id = $citizenUser->getRoleId();
        $region = $citizenUser->getRegion();
        $city = $citizenUser->getCity();
        $address = $citizenUser->getAddress();
        $zip = $citizenUser->getZip();
        $map_place = $citizenUser->getMapPlace();
        $latitude = $citizenUser->getLatitude();
        $longitude = $citizenUser->getLongitude();
        $citizen_profile_img_id = $citizenUser->getProfileImg();
        $citizen_profile_img = $this->getUserProfileImage($citizen_profile_img_id, $user_id);
        $citizen_cover_img = $this->getUserCoverImage($citizen_profile_img_id, $user_id);
        
        //get citizen refferer
        $citizen_affiliater = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                ->findOneBy(array('toId' =>$user_id));
        if($citizen_affiliater){
        //get affliliater id
        $affiliater_id = $citizen_affiliater->getFromId();
        //get affiliater object
        $user_service           = $this->get('user_object.service');
        $user_object            = $user_service->UserObjectService($affiliater_id);
        } else {
            $user_object = array();
        }
        
        //finding the user district,block and position
        $position_result = $this->getDistrictBlockPosition($user_id);
        
        //get basic profile
        $basic_profile_results = $this->getBasicProfile($user_id);
        //create citizen info array
        $citizen_user_info = array(
            'region' => $region,
            'city' => $city,
            'address' => $address,
            'zip' => $zip,
            'map_place' => $map_place,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'referral_info' => $user_object
             );
        
        return $citizen_user_info;
    }
    /**
     * Get broker profile
     * @param type $user_id
     * @return array
     */
    public function getBrokerProfile($user_id)
    {
        $broker_user_info = array();
        //define the ssn_link and idcard_link fileds..
        $ssn_link = $idcard_link = '';
        $data = array();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $brokerUser = $em
                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->findOneBy(array('userId'=>$user_id));
        //check if a user have his broker profile or not
        if (!$brokerUser) {
           $data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
           echo $this->encodeData($data);
           exit;
        }
        //get user info
        $id = $brokerUser->getId();
        $user_id = $brokerUser->getUserId();
        $role_id = $brokerUser->getRoleId();
        $phone = $brokerUser->getPhone();
        $vat_number = $brokerUser->getVatNumber();
        $fiscal_code = $brokerUser->getFiscalCode();
        $iban = $brokerUser->getIban();
        $map_place = $brokerUser->getMapPlace();
        $latitude = $brokerUser->getLatitude();
        $longitude = $brokerUser->getLongitude();
        $is_active = $brokerUser->getIsActive();//add new field for inactive, active of broker profile.
        $broker_profile_img_id = $brokerUser->getProfileImg();
        $broker_profile_img = $this->getUserProfileImage($broker_profile_img_id, $user_id);
       // $base_cover_img = $this->getUserProfileImage($base_cover_img_id, $user_id);
        
        $ssn    = $brokerUser->getSsn();
        $idcard = $brokerUser->getIdCard();
        //getting the base url...
        $base_url = $this->getS3BaseUri();
        
        if ($ssn != '' AND $idcard != '') {
            $ssn_link    = $base_url.'/uploads/users/contract/'.$user_id.'/'.$ssn;
            $idcard_link = $base_url.'/uploads/users/contract/'.$user_id.'/'.$idcard ;
        }
        //finding the user district,block and position
        $position_result = $this->getDistrictBlockPosition($user_id);
        
        //get broker refferer
        $broker_affiliater = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationBroker')
                ->findOneBy(array('toId' =>$user_id));
        if($broker_affiliater){
        //get affliliater id
        $affiliater_id = $broker_affiliater->getFromId();
        //get affiliater object
        $user_service           = $this->get('user_object.service');
        $user_object            = $user_service->UserObjectService($affiliater_id);
        } else {
            $user_object = array();
        }
        
        //create broker info array
        $broker_user_info = array('id'=>$id,
            'user_id' => $user_id,
            'role_id' => $role_id,
            'phone' => $phone,
            'vat_number' => $vat_number,
            'fiscal_code' => $fiscal_code,
            'iban' => $iban,
            'map_place' => $map_place,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_active'=> $is_active,
            'profile_img' => $broker_profile_img,
            'district_number' => $position_result->district,
            'block_group_number' => $position_result->block,
            'position_in_block' => $position_result->position,
            'ssn'=>$ssn_link,
            'idcard'=>$idcard_link,
            'referral_info' => $user_object
            );
        
        return $broker_user_info;
    }
    
    /**
     * Update User Jobs
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateuserjobsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','company','title','start_date','end_date','currently_working','headline','location','description','visibility_type');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params
        $requited_fields = array('user_id','type','company','title','start_date','end_date','visibility_type' );
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       $visibility_type = (int) $de_serialize['visibility_type'];
       $allowed_visibility_type = array(1,2,3);
       if(!in_array($visibility_type, $allowed_visibility_type)){
          $res_data = array('code' => '133', 'message' => 'INVALID_VISIBILITY_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       if(isset($de_serialize['currently_working'])){
            $currently_working = (int) $de_serialize['currently_working'];
            $allowed_currently_working_type = array(0,1);
            if(!in_array($currently_working, $allowed_currently_working_type)){
               $res_data = array('code' => '134', 'message' => 'INVALID_VISIBILITY_TYPE', 'data' => array());
               echo json_encode($res_data);
             exit;
            }
       } else {
           $de_serialize['currently_working'] = 0;
       }

        $sdate = $de_serialize['start_date'];  
        $edate = $de_serialize['end_date'];  
                
        if($sdate != ""){ $this->checkDateFormat($sdate); }
        if($edate != ""){ $this->checkDateFormat($edate); }

        $em = $this->getDoctrine()->getManager();
        $job = $em->getRepository('UserManagerSonataUserBundle:JobsDetails');
        $InsertedJobDetails = $job->InsertJobsDetails($de_serialize);
        $jobDetails = $em
                ->getRepository('UserManagerSonataUserBundle:JobsDetails')
                ->getJobDetails($user_id);
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $jobDetails);
        echo json_encode($res_data);
        exit;
        
    }
    
    /**
     * Update User Interestd Category keywords
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateusercategorykeywordsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','category_id','keywords','lang_code');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params
        $requited_fields = array('user_id','type','category_id','keywords','lang_code' );
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
    if(!$de_serialize['keywords']){
                
        $res_data = array('code' => '130', 'message' => 'KEYWORDS_REQUIRED', 'data' => array() );
        echo json_encode($res_data);
        exit;
        
    }      
        $em = $this->getDoctrine()->getManager();
        $categoryKeywords = $em->getRepository('UserManagerSonataUserBundle:UserCategoryKeywords');
        $InsertedUserCategoryKeywords = $categoryKeywords->InsertCategoryKeywords($de_serialize);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $InsertedUserCategoryKeywords);
        
        // call applane service    
        //$appalne_data = $de_serialize;
        //get dispatcher object
        //$event = new FilterDataEvent($appalne_data);
        //$dispatcher = $this->container->get('event_dispatcher');
        //$dispatcher->dispatch('citizen.updatekeyword', $event);
        //end of applane service
        
        echo json_encode($res_data);
        exit;
        
    }
    
    /**
     * Update User relatives
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateuserrelativesAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','relative_id','relation');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params 
        $requited_fields = array('user_id','type','relative_id','relation' );
        
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
    
        $em = $this->getDoctrine()->getManager();
        $relatives = $em->getRepository('UserManagerSonataUserBundle:Relatives');
        $InsertedRelatives = $relatives->InsertRelativeDetails($de_serialize);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $InsertedRelatives);
        echo json_encode($res_data);
        exit;
        
    }
    
    
    
    /**
     * Update User Education
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateusereducationsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');
       
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','school','start_date','end_date','currently_attending','degree','field_of_study','grade','activities','desc','visibility_type');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params  
        if($de_serialize['currently_attending']){
            $requited_fields = array('user_id','type','school','start_date','degree','visibility_type' );
        } else {
            $requited_fields = array('user_id','type','school','start_date','end_date','degree','visibility_type' );
        }
        
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       $allowed_visibility_type = array(1,2,3);
       $user_visibility_type = $de_serialize['visibility_type'];
       if(!in_array($user_visibility_type, $allowed_visibility_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_VISIBILITY_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       /*
        $sdate = $de_serialize['start_date'];  
        $edate = $de_serialize['end_date'];  
                
        if($sdate != ""){ $this->checkDateFormat($sdate); }
        if($edate != ""){ $this->checkDateFormat($edate); }
        */
        $em = $this->getDoctrine()->getManager();
        $education = $em->getRepository('UserManagerSonataUserBundle:EducationDetails');
        $InsertedEducationDetails = $education->InsertEducationDetails($de_serialize);
        $educationDetails = $em
                ->getRepository('UserManagerSonataUserBundle:EducationDetails')
                ->getEducationDetails($user_id);
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $educationDetails);
        echo json_encode($res_data);
        exit;
        
    }
    
    /**
     * Update User Education Visibility
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateusereducationvisibilitiesAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');
       
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','visibility_type');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params       
        $requited_fields = array('user_id','type','id','visibility_type');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       
       $allowed_visibility_type = array(1,2,3);
       if(!in_array($de_serialize['visibility_type'], $allowed_visibility_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_VISIBILITY_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
        $em = $this->getDoctrine()->getManager();
        $job = $em->getRepository('UserManagerSonataUserBundle:EducationDetails');
        $SetEducationDetails = $job->SetEducationDetailVisibility($de_serialize);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
    /**
     * Update User Job Visibility
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdateuserjobvisibilitiesAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');
       
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id','visibility_type');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params       
        $requited_fields = array('user_id','type','id','visibility_type');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       $allowed_visibility_type = array(1,2,3);
       if(!in_array($de_serialize['visibility_type'], $allowed_visibility_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_VISIBILITY_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
        $em = $this->getDoctrine()->getManager();
        $job = $em->getRepository('UserManagerSonataUserBundle:JobsDetails');
        $SetJobDetails = $job->SetJobDetailVisibility($de_serialize);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
     /**
     * Delete User Education
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postDeleteusereducationsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params       
        $requited_fields = array('user_id','type','id');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
      
        $em = $this->getDoctrine()->getManager();
        $education = $em->getRepository('UserManagerSonataUserBundle:EducationDetails');
        $DeletedEducationDetails = $education->DeleteEducationDetails($de_serialize['id'], $user_id);
        
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
    
    /**
    * Delete User Job
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return array
    */
    public function postDeleteuserjobsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params       
        $requited_fields = array('user_id','type','id');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
      
        $em = $this->getDoctrine()->getManager();
        $job = $em->getRepository('UserManagerSonataUserBundle:JobsDetails');
        $DeletedJobDetails = $job->DeleteJobDetails($de_serialize['id'], $user_id);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
    /**
    * Delete User Interested Category
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return array
    */
    public function postDeleteusercategorykeywordsAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params       
        $requited_fields = array('user_id','type','id');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
      
        $em = $this->getDoctrine()->getManager();
        $userKeywords = $em->getRepository('UserManagerSonataUserBundle:UserCategoryKeywords');
        $DeletedKeywords = $userKeywords->DeleteUserCategory($de_serialize['id'], $user_id);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
    
    /**
    * Delete User Relatives
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return array
    */
    public function postDeleteuserrelativesAction(Request $request)
    {
           
        //get request object
       $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = $de_serialize['user_id'];
        
        //check required params
        $required_params =  array('user_id','type','id');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params        
        $requited_fields = array('user_id','type','id');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
      
        $em = $this->getDoctrine()->getManager();
        $relatives = $em->getRepository('UserManagerSonataUserBundle:Relatives');
        $DeletedRelatives = $relatives->DeleteRelatives($de_serialize['id'], $user_id);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    /**
     * Update user profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postUpdatemultiprofilesAction(Request $request)
    {
        //get request object

       $freq_obj = $request->get('reqObj');
       
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //check required params
        //$required_params = array('user_id','type','firstname','lastname','birthday','gender','country','state','region','map_place','referral_id','zip','city','city_born','address','latitude','longitude','hobbies','relationship','about_me');
        $required_params = array('user_id','type');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        $user_id = $de_serialize['user_id'];
             
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
 
       $user_id = $de_serialize['user_id'];
        
       //get citizen user object
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       $em = $this->container->get('doctrine')->getManager();
       //get user object
       $base_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('id' => $user_id));
       
       //basic user info
       $de_serialize['firstname'] = isset($de_serialize['firstname']) ? $de_serialize['firstname'] : '';
       $de_serialize['lastname'] = isset($de_serialize['lastname']) ? $de_serialize['lastname'] : '';
       $de_serialize['birthday'] = isset($de_serialize['birthday']) ? $de_serialize['birthday'] : '';
       $de_serialize['gender'] = isset($de_serialize['gender']) ? $de_serialize['gender'] : '';
       $de_serialize['country'] = isset($de_serialize['country']) ? $de_serialize['country'] : '';
       $de_serialize['state'] = isset($de_serialize['state']) ? $de_serialize['state'] : '';
       $de_serialize['hobbies'] = isset($de_serialize['hobbies']) ? $de_serialize['hobbies'] : '';
       $de_serialize['relationship'] = isset($de_serialize['relationship']) ? $de_serialize['relationship'] : '';
       $de_serialize['about_me'] = isset($de_serialize['about_me']) ? $de_serialize['about_me'] : '';
       $de_serialize['city_born'] = isset($de_serialize['city_born']) ? $de_serialize['city_born'] : '';
       
       //get citizen profile object
       $citizen_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId' => $user_id));
       
       if(!$citizen_user_result){
            $res_data = array('code' => '137', 'message' => 'INVALID_USER', 'data' => array());
            echo json_encode($res_data);
            exit; 
       }
       
       //citizen info
       $de_serialize['region'] = isset($de_serialize['region']) ? $de_serialize['region'] : '';
       $de_serialize['city'] = isset($de_serialize['city']) ? $de_serialize['city'] : '';
       $de_serialize['address'] = isset($de_serialize['address']) ? $de_serialize['address'] : '';
       $de_serialize['zip'] = isset($de_serialize['zip']) ? $de_serialize['zip'] : '';
       $de_serialize['map_place'] = isset($de_serialize['map_place']) ? $de_serialize['map_place'] : '';
       $de_serialize['latitude'] = isset($de_serialize['latitude']) ? $de_serialize['latitude'] : '';
       $de_serialize['longitude'] = isset($de_serialize['longitude']) ? $de_serialize['longitude'] : '';
       //$referral_id = $desrialize['referral_id'];
     
        $store_latitude = (strtolower($de_serialize['latitude']) == 'undefined') ? '' : $de_serialize['latitude'];
        $store_longitude = (strtolower($de_serialize['longitude']) == 'undefined') ? '' : $de_serialize['longitude'];
        
        $de_serialize['latitude'] = $store_latitude;
        $de_serialize['longitude'] = $store_longitude;
       //for citizen
       if($type == 1)
       { 
        $de_serialize['referral_id'] = '';
        $referral_id  = $de_serialize['referral_id'];
        //get citizen referral id
        $citizen_referral_id = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                ->findOneBy(array('toId' => $user_id));
        
        if($citizen_referral_id){
            $referral_id = $citizen_referral_id->getFromId();
            $de_serialize['referral_id'] = $referral_id;
        }
        
        //check for birth date
        $birthday = $de_serialize['birthday'];

       if($birthday != ""){
           
           //get birth date date object
        try{
        $btime = new \DateTime($birthday);
        }catch(\Exception $e) {
    
            $data = array('code' => 131, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;

       }
       }
       
           $response = $this->editCitizenProfile($de_serialize);
           if($response == 7)
           {
               $res_data = array('code' => '137', 'message' => 'INVALID_USER', 'data' => array());
               echo json_encode($res_data);
               exit; 
           }
           
           //get user object
          $um = $this->container->get('fos_user.user_manager');
          //get user detail
          $user_detail = $um->findUserBy(array('id' => $user_id));
          $user_email = $user_detail->getEmail();
          $user_password = $user_detail->getPassword();
          
          $register_id = $de_serialize['user_id'];
          $firstname   = $de_serialize['firstname'];
          $lastname    = $de_serialize['lastname'];
          $email       = $user_email;
          $cell        = '';
          $password    = $user_password;
          $manager     = 'N';
          $step        = 'Citizen Update';
   
          $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
          // BLOCK SHOPPING PLUS
          //$shoppingplus_obj->updateCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);
          
          
          //$data = $this->prepareApplaneData($de_serialize, $register_id, $friend_data, $follower_data);
          $appalne_data = $de_serialize;
          $appalne_data['register_id'] = $register_id;
          //get dispatcher object
          $event = new FilterDataEvent($appalne_data);
          $dispatcher = $this->container->get('event_dispatcher');
          $dispatcher->dispatch('citizen.update', $event);
          $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
          echo json_encode($res_data);
          exit; 
       }
       
       //for broker
       if($type == 2)
       {
           
         //get user object
          $um = $this->container->get('fos_user.user_manager');
          //get user detail
          $user_detail = $um->findUserBy(array('id' => $user_id));
          $first_name = $user_detail->getFirstName();
          $last_name  = $user_detail->getLastName();
          $user_email = $user_detail->getEmail();
          $user_phone = $user_detail->getPhone();
          $user_password = $user_detail->getPassword();
          
         //get entity manager object
            $em = $this->container->get('doctrine')->getManager();
            //get broker profile object
            $broker_user_result = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')
                    ->findOneBy(array('userId' => $user_id));

            if (!$broker_user_result) {
                $res_data = array('code' => '161', 'message' => 'BROKER_DOES_NOT_EXIT', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            $user_media_ssn_name    = $user_media_idcard_name = '';
            $user_media_ssn_name    = $broker_user_result->getSsn();
            $user_media_idcard_name = $broker_user_result->getIdCard();
            if ($user_media_ssn_name == '' or $user_media_idcard_name == '') { // making the check if a broker already uploaded the documents
                //check for contract docs file uploading..
                if (!isset($_FILES['ssn'])) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $original_ssn_name = @$_FILES['ssn']['name'];
                if (empty($original_ssn_name)) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $original_idcard_name = @$_FILES['idcard']['name'];
                if (empty($original_idcard_name)) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $file_object = $_FILES; //print_r($file_object); exit;
                $user_media_ssn_name = time() . strtolower(str_replace(' ', '', $_FILES['ssn']['name']));
                $user_media_idcard_name = time() . strtolower(str_replace(' ', '', $_FILES['idcard']['name']));

                $this->uploadSsnDocs($file_object, $user_id, $user_media_ssn_name);
                $this->uploadIdCardDocs($file_object, $user_id, $user_media_idcard_name);
            }
            
            $de_serialize['ssn'] = $user_media_ssn_name;
            $de_serialize['id_card'] = $user_media_idcard_name;
            //ssn/id_card doc upload code finish
            
            $response = $this->editBrokerProfile($de_serialize);
            
            $register_id = $user_id;
            $firstname   = $first_name;
            $lastname    = $last_name;
            $email       = $user_email;
            $cell        = $user_phone;
            $password    = $user_password;
            $manager     = 'N';
            $step        = 'Broker Update';
            $referral_id = $de_serialize['referral_id'];

           $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
           // BLOCK SHOPPING PLUS
           //$shoppingplus_obj->updateCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);

          if($response == 7)
           {
               $res_data = array('code' => '137', 'message' => 'INVALID_USER', 'data' => array());
               echo json_encode($res_data);
               exit; 
           }
           // for 
           $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
               echo json_encode($res_data);
               exit; 
       }
      
    } 
    
    // used to check varify dte format
    public function checkDateFormat($date){

        try{
            $btime = new \DateTime($date);
        }catch(\Exception $e) {
            $data = array('code' => 131, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        } 
    }

    /**
     * Edit multi profiles
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postEditmultiprofilesAction(Request $request)
    {
      
        //get request object
        $freq_obj = $request->get('reqObj');
       
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
    
       
        $user_id = $de_serialize['user_id'];
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }
        
       $type = $de_serialize['type'];
       
       $allow_type = array(1,2);
       if(!in_array($type, $allow_type)){
          $res_data = array('code' => '132', 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
          echo json_encode($res_data);
        exit;
       }
       
       //for citizen
       if($type == 1)
       {
           
        $referral_id = $de_serialize['referral_id'];
           //check for birth date
        $birthday = $de_serialize['birthday'];
       if($birthday != ""){
           //get birth date date object
        try{
        $btime = new \DateTime($birthday);
        }catch(\Exception $e) {
    
            $data = array('code' => 131, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;

       }
       }
       
           $response = $this->editCitizenProfile($de_serialize);
           if($response == 7)
           {
               $res_data = array('code' => '137', 'message' => 'INVALID_USER', 'data' => array());
               echo json_encode($res_data);
               exit; 
           }
           
           //get user object
          $um = $this->container->get('fos_user.user_manager');
          //get user detail
          $user_detail = $um->findUserBy(array('id' => $user_id));
          $user_email = $user_detail->getEmail();
          $user_password = $user_detail->getPassword();
          
          $register_id = $de_serialize['user_id'];
          $firstname   = $de_serialize['firstname'];
          $lastname    = $de_serialize['lastname'];
          $email       = $user_email;
          $cell        = '';
          $password    = $user_password;
          $manager     = 'N';
          $step        = 'Citizen Update';
   
          $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
          // BLOCK SHOPPING PLUS
          //$shoppingplus_obj->updateCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);
          
          foreach($de_serialize['studies'] as $Details){
            
            $Details['user_id'] = $user_id;
            $education = $this->getDoctrine()->getRepository('UserManagerSonataUserBundle:EducationDetails');
            $education->InsertEducationDetails($Details);
            
          }
         
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit; 
       }
       
       //for broker
       if($type == 2)
       {
           
         //get user object
          $um = $this->container->get('fos_user.user_manager');
          //get user detail
          $user_detail = $um->findUserBy(array('id' => $user_id));
          $first_name = $user_detail->getFirstName();
          $last_name  = $user_detail->getLastName();
          $user_email = $user_detail->getEmail();
          $user_phone = $user_detail->getPhone();
          $user_password = $user_detail->getPassword();
          
         //get entity manager object
            $em = $this->container->get('doctrine')->getManager();
            //get broker profile object
            $broker_user_result = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')
                    ->findOneBy(array('userId' => $user_id));

            if (!$broker_user_result) {
                $res_data = array('code' => '161', 'message' => 'BROKER_DOES_NOT_EXIT', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            $user_media_ssn_name    = $user_media_idcard_name = '';
            $user_media_ssn_name    = $broker_user_result->getSsn();
            $user_media_idcard_name = $broker_user_result->getIdCard();
            if ($user_media_ssn_name == '' or $user_media_idcard_name == '') { // making the check if a broker already uploaded the documents
                //check for contract docs file uploading..
                if (!isset($_FILES['ssn'])) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $original_ssn_name = @$_FILES['ssn']['name'];
                if (empty($original_ssn_name)) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $original_idcard_name = @$_FILES['idcard']['name'];
                if (empty($original_idcard_name)) {
                    return array('code' => 95, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => array());
                }
                $file_object = $_FILES; //print_r($file_object); exit;
                $user_media_ssn_name = time() . strtolower(str_replace(' ', '', $_FILES['ssn']['name']));
                $user_media_idcard_name = time() . strtolower(str_replace(' ', '', $_FILES['idcard']['name']));

                $this->uploadSsnDocs($file_object, $user_id, $user_media_ssn_name);
                $this->uploadIdCardDocs($file_object, $user_id, $user_media_idcard_name);
            }
            
            $de_serialize['ssn'] = $user_media_ssn_name;
            $de_serialize['id_card'] = $user_media_idcard_name;
            //ssn/id_card doc upload code finish
            
            $response = $this->editBrokerProfile($de_serialize);
            
            $register_id = $user_id;
            $firstname   = $first_name;
            $lastname    = $last_name;
            $email       = $user_email;
            $cell        = $user_phone;
            $password    = $user_password;
            $manager     = 'N';
            $step        = 'Broker Update';
            $referral_id = $de_serialize['referral_id'];

           $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
           // BLOCK SHOPPING PLUS
           //$shoppingplus_obj->updateCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);

          if($response == 7)
           {
               $res_data = array('code' => '137', 'message' => 'INVALID_USER', 'data' => array());
               echo json_encode($res_data);
               exit; 
           }
           // for 
           $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
               echo json_encode($res_data);
               exit; 
       }
      
    } 
       
    
    /**
     * Return the country list
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int
     */
    public function countrylistAction(Request $request) {
        
        $allowed_country_code = array('US','IT'); //get allowed contry code
        $result_country = array();
        $countryLists = Locale::getDisplayCountries('en');
        
        foreach($countryLists as $key =>$value) {
            if(!in_array($key, $allowed_country_code)) {
                unset($countryLists[$key]);
            }
        }
        //make the country list array.
        foreach ($countryLists as $key=>$value) {
            $result_country[] = array('code'=>$key, 'name'=>$value);
        }
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $result_country);
        echo $this->encodeData($data);
        exit;
    }

    /**
     * Upload profile image
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|array
     */
    public function postUploadprofilephotosAction(Request $request) {
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



        //get user id
        $user_id = $de_serialize['user_id'];

        //profile type
        $profile_type = $de_serialize['profile_type'];

        //check for response
        $allow_res = array('1', '2');

        if (!in_array($profile_type, $allow_res)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }


        //check if file is valid or not
        $images_upload = $_FILES['user_media'];
        $original_media_name = "";
        $original_media_name = @$_FILES['user_media']['name'];
       if (empty($original_media_name)) {
           return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
       }
      
        $file_error = $this->checkFileType($images_upload);
        if ($file_error == 1) {
            $resp_data = array('code' => '128', 'message' => 'INVALID_IMAGE', 'data' => array());
            return $resp_data;
        }
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');


            if ($_FILES['user_media']['name'] != "") {
                $file_name = time() . $_FILES['user_media']['name'];
                $file_tmp = $_FILES['user_media']['tmp_name'];
                $file_type = $_FILES['user_media']['type'];
                $media_type = explode('/', $file_type);
                $actual_media_type = $media_type[0];

                $userMedia = new UserMedia();
                $userMedia->setUserid($user_id);
                $userMedia->setContenttype($actual_media_type);
                $userMedia->setName($file_name);
                $userMedia->setAlbumid('');
                $time = new \DateTime("now");
                // $userMedia->setCreated($time);
                $album_id = 0;
                $userMedia->profileImageUpload($user_id, $file_name ); //uploading the files.
                $dm->persist($userMedia);
                $dm->flush();


                //get image directory path
                $file_path = __DIR__ . "/../../../../../web/uploads/users/media/original/" . $user_id . "/";
                if ($_FILES['user_media']['name'] != "") {
                    //create the thumbnail of the image
                    $this->createThumbnail($file_name, $file_path, $user_id);
                }
            $img_id = $userMedia->getId();
        }

        //set photo as profile photo
        $this->setProfilePhoto($user_id, $img_id, $profile_type);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Set profile photo
     * @param type $user_id
     * @param type $img_id
     */
    public function setProfilePhoto($user_id, $img_id, $profile_type) {

        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $userPhoto = new UserPhoto();

        //remove the previous image
        $result = $dm
                ->getRepository('UserManagerSonataUserBundle:UserPhoto')
                ->findOneBy(array("user_id" => (int) $user_id, "profile_type" => (int) $profile_type));
        if ($result) {
            $dm->remove($result);
            $dm->flush();
        }

        //set photo as profile photo
        $userPhoto->setUserId($user_id);
        $userPhoto->setPhotoId($img_id);
        $time = new \DateTime("now");
        $userPhoto->setCreatedAt($time);
        $userPhoto->setProfileType($profile_type);
        $dm->persist($userPhoto);
        $dm->flush();
        return true;
    }

    /**
     * Set profile photo
     * @param type $user_id
     * @param type $img_id
     */
    public function postSetuserprofilephotosAction(Request $request) {
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



        //get user id
        $user_id = $de_serialize['user_id'];

        //profile type
        $profile_type = $de_serialize['profile_type'];

        //check for response
        $allow_res = array('1', '2');

        if (!in_array($profile_type, $allow_res)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //image id
        $img_id = $de_serialize['image_id'];


        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $userPhoto = new UserPhoto();

        //remove the previous image
        $result = $dm
                ->getRepository('UserManagerSonataUserBundle:UserPhoto')
                ->findOneBy(array("user_id" => (int) $user_id, "profile_type" => (int) $profile_type));
        if ($result) {
            $dm->remove($result);
            $dm->flush();
        }

        //set photo as profile photo
        $userPhoto->setUserId($user_id);
        $userPhoto->setPhotoId($img_id);
        $time = new \DateTime("now");
        $userPhoto->setCreatedAt($time);
        $userPhoto->setProfileType($profile_type);
        $dm->persist($userPhoto);
        $dm->flush();
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createThumbnail($filename, $media_original_path, $userId) {

        $pre_upload_media_dir = __DIR__ . "/../../../../../web/uploads/users/media/thumb/";
        $upload_media_dir = $pre_upload_media_dir . '/' . $userId . '/';
        $path_to_thumbs_directory = $upload_media_dir;
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = 100;
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
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileType($images_upload) {

        $file_error = 0;
            $file_name = basename($images_upload['name']);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($images_upload['type'] == 'image/jpeg' || $images_upload['type'] == 'image/jpg' ||
                        $images_upload['type'] == 'image/gif' || $images_upload['type'] == 'image/png'))))) {
                    $file_error = 1;
                }
            }

        return $file_error;
    }

    /**
     * Set the active profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string|array
     */
    public function postSetuseractiveprofilesAction(Request $request)
    {
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

        //get user id
        $user_id = $de_serialize['user_id'];

        //profile type
        $profile_type = $de_serialize['profile_type'];
        
         //check for response
        $allow_type = array('user', 'store');
        if (!in_array($profile_type, $allow_type)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //profile id
        $profile_id = $de_serialize['profile_id'];
        
        //get UserActiveProfile object
        
        
        //check if user record exist
        $user_active_profile = $em
                ->getRepository('UserManagerSonataUserBundle:UserActiveProfile')
                ->findOneBy(array("userId" => $user_id));
        
        if($user_active_profile){
        $user_active_profile->setUserId($user_id);
        $user_active_profile->setProfileType($profile_id);
        $user_active_profile->setType($profile_type);
        
        
        $em->persist($user_active_profile);
        $em->flush();
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo $this->encodeData($data);
        exit;
        
        }
        
        $user_active_profile = new UserActiveProfile();
        $user_active_profile->setUserId($user_id);
        $user_active_profile->setProfileType($profile_id);
        $user_active_profile->setType($profile_type);
        
        
        $em->persist($user_active_profile);
        $em->flush();
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo $this->encodeData($data);
        exit;
    }
    
    /**
     *  List the active profile for the user
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postActiveprofilelistsAction(Request $request)
    {
        //initilise the array
        $data = array();
        $profiles = array();
        $stores = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //get user id
        $user_id = $de_serialize['user_id'];
        
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //get active profile list
        //check if user record exist
        $user_multi_profiles = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findBy(array("userId" => $user_id));
        
        if($user_multi_profiles){
        foreach($user_multi_profiles as $user_multi_profile){

            $id = $user_multi_profile->getId();
            $user_id = $user_multi_profile->getUserId();
            $profile_type = $user_multi_profile->getProfileType();
            $profiles[] = array('id'=>$id, 'user_id'=>$user_id, 'profile_type'=>$profile_type);
        }
        }
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getProfileStores($user_id);
        if($stores){
        foreach($stores as $store){
            $id = $store['id'];
            $title = $store['title'];
            $desc = $store['description'];
            $stores_list[] = array('store_id'=>$id, 'title'=>$title, 'description'=>$desc);
        }
        }
        $multi_profiles = array('user'=>$profiles, 'stores'=>$stores_list);
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $multi_profiles);
        echo $this->encodeData($data);
        exit;
        
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
     * Get email
     * @param int $uid
     * @return boolean
     */
    public function getUserEmail($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_email = $user->getEmail();

        return $user_email;
    }

    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
     * 
     * @param type $user_id
     * @param type $profile_type
     * @return string
     */
    public function getUserProfileImage($image_id, $user_id)
    {
       
        $image_id = $image_id;
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get image from table
        $user_profile_image = $dm
                ->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array("id" => $image_id));
        if(!$user_profile_image){
           $profile_img = array('original' => '', 'thumb' => '');
           return $profile_img;
        }
        
        $media_name = $user_profile_image->getName();
        $album_id =  $user_profile_image->getAlbumid();
       // $x_cord = $user_profile_image->getX();
       // $y_cord = $user_profile_image->getY();
        if($album_id == ""){
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$media_name;
        }else{
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
        }
        $profile_img = array('original' => $mediaPath, 'thumb' =>$thumbDir);
        return $profile_img;
    }
    
    public function getUserCoverImage($image_id, $user_id)
    {
       
        $image_id = $image_id;
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get image from table
        $user_profile_image = $dm
                ->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array("id" => $image_id));
        if(!$user_profile_image){
           $profile_img = array('original' => '', 'thumb' => '');
           return $profile_img;
        }
        
        $media_name = $user_profile_image->getName();
        $album_id =  $user_profile_image->getAlbumid();
        $x_cord = $user_profile_image->getX();
        $y_cord = $user_profile_image->getY();
        
        if($album_id == ""){
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
       // $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb_cover . $user_id . '/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id .'/'.$media_name;
        }else{
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
       // $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb_cover . $user_id . '/'.$album_id.'/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
        
        }
        
        $profile_img = array('original' => $mediaPath, 'thumb' =>$thumbDir,
                       'x_cord'=>$x_cord,'y_cord'=>$y_cord,'media_id'=>$image_id);
       
        
       return $profile_img;
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
    * Function to retrieve current applications base URI 
    */
     public function getBaseUri()
     {
         // get the router context to retrieve URI information
         $context = $this->get('router')->getContext();
         // return scheme, host and base URL
         return $context->getScheme().'://'.$context->getHost().$context->getBaseUrl();
     }
     
     /**
      * Edit citizen profile
      * @param type $desrialize
      * @return array
      */
     public function editCitizenProfile($desrialize)
     {
       $user_id = $desrialize['user_id'];

       //get entity manager object
       $em = $this->container->get('doctrine')->getManager();
       
       //get user base profile object
       $base_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('id' => $user_id));
       if(!$base_user_result){
           return 7;
       }
       
       $firstname = $desrialize['firstname'];
       $lastname = $desrialize['lastname'];
       $birthday = $desrialize['birthday'];
       $gender = $desrialize['gender'];
       $country = $desrialize['country'];
       $state = $desrialize['state'];
       
       
       //citizen info
       $region = $desrialize['region'];
       $city = $desrialize['city'];
       $address = $desrialize['address'];
       $zip = $desrialize['zip'];
       $map_place = $desrialize['map_place'];
       $latitude = $desrialize['latitude'];
       $longitude = $desrialize['longitude'];
       $referral_id = $desrialize['referral_id'];
       $hobbies = $desrialize['hobbies'];
       $relationship = $desrialize['relationship'];
       $aboutme = $desrialize['about_me'];
       $city_born = $desrialize['city_born'];

       if($firstname != ""){
          $base_user_result->setFirstName($firstname); 
       }
       if($lastname != ""){
          $base_user_result->setLastName($lastname); 
       }
       if($birthday != ""){
           //get birth date date object
        $btime = new \DateTime($birthday);
        
        if (!$btime) {
            $data = array('code' => 131, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

          $base_user_result->setDateOfBirth($btime); 
       }
       if($gender != ""){
          $base_user_result->setGender($gender); 
       }
       if($country != ""){
          $base_user_result->setCountry($country); 
       }
       if($state != ""){
          $base_user_result->setState($state); 
       }
       if($hobbies != ""){
          $base_user_result->setHobbies($hobbies); 
       }
       if($relationship != ""){
          $base_user_result->setRelationship($relationship); 
       }
       if($aboutme != ""){
          $base_user_result->setAboutMe($aboutme); 
       }
       if($city_born != ""){
          $base_user_result->setCityBorn($city_born); 
       }

       //$base_user_result->set
       //get citizen profile object
       $citizen_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId' => $user_id));
       
       if(!$citizen_user_result){
           return 7;
       }
       
       if($region != ""){
          $citizen_user_result->setRegion($region); 
       }
       if($city != ""){
          $citizen_user_result->setCity($city); 
       }
       if($address != ""){
          $citizen_user_result->setAddress($address); 
       }
       if($zip != ""){
          $citizen_user_result->setZip($zip); 
       }
       if($map_place != ""){
          $citizen_user_result->setMapPlace($map_place); 
       }
       if($latitude != ""){
          $citizen_user_result->setLatitude($latitude); 
       }
       if($longitude != ""){
          $citizen_user_result->setLongitude($longitude); 
       }
       
       // $referral_id = "";
        if(isset($deserialize['referral_id'])){
           $referralid = $deserialize['referral_id'];
        }
        
      /* if($referral_id != ""){
       $em = $this->container->get('doctrine')->getEntityManager();            
               // checking for v08 // the user that affiliates this user is present in fos_user_user table or not.
                $affiliateuser = $this->container->get('fos_user.user_manager')->findUserBy(array('id'=>$referral_id));
                if(!($affiliateuser)){
                   $res_data = array('code' => '139', 'message' => 'AFFILIATER_DOES_NOT_EXIT', 'data' => array());
                   echo json_encode($res_data);
                   exit;   
                }
       }
       */
       $time = new \DateTime("now");
       $citizen_user_result->setUpdatedAt($time);//set the updated time for a citizen profile.
       $em->persist($base_user_result);
       $em->persist($citizen_user_result);
       $em->flush();
       return 1;
       
     }
     
     /**
      * Edit broker profile
      * @param type $desrialize
      * @return array
      */
     public function editBrokerProfile($desrialize)
     {
       $user_id = $desrialize['user_id'];

       //get entity manager object
       $em = $this->container->get('doctrine')->getManager();
       //get broker profile object
       $broker_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->findOneBy(array('userId' => $user_id));
       
       //if not found then add the new record for the broker.
       if(!$broker_user_result){
           $broker_user_result = new BrokerUser();
           //get birth date date object
           $time = new \DateTime('now');
           $broker_user_result->setCreatedAt($time);
           $broker_user_result->setProfileImg('');
       }
       //broker info
       $phone = $desrialize['phone'];
       $vat_number = $desrialize['vat_number'];
       $fiscal_code = $desrialize['fiscal_code'];
       $iban = $desrialize['iban'];
       $map_place = $desrialize['map_place'];
       $latitude = $desrialize['latitude'];
       $longitude = $desrialize['longitude'];
       $referral_id = $desrialize['referral_id'];
       
       $broker_user_result->setUserId($user_id);
       if($phone != ""){
          $broker_user_result->setPhone($phone); 
       }
       if($vat_number != ""){
          $broker_user_result->setVatNumber($vat_number); 
       }
       if($fiscal_code != ""){
          $broker_user_result->setFiscalCode($fiscal_code); 
       }
       if($iban != ""){
          $broker_user_result->setIban($iban); 
       }
        if($map_place != ""){
          $broker_user_result->setMapPlace($map_place); 
       }
        if($latitude != ""){
          $broker_user_result->setLatitude($latitude); 
       }
        if($longitude != ""){
          $broker_user_result->setLongitude($longitude); 
       }
        // $referral_id = "";
        if(isset($deserialize['referral_id'])){
           $referral_id = $deserialize['referral_id'];
        }
        
       $broker_user_result->setRoleId(24);
       
       //for ssn and idcard
       $broker_user_result->setSsn($desrialize['ssn']);
       $broker_user_result->setIdCard($desrialize['id_card']);
       
       $em->persist($broker_user_result);
       $em->flush();
       return 1;
     }
     
     public function saveDataOnRemoteServer()
     {
         
     }

     /**
      * Finding the district,block and position of a user from userPosition entity
      * @param type $user_id
      * @return array
      */
     public function getDistrictBlockPosition($user_id)
     {
       //get entity manager object
       $em = $this->container->get('doctrine')->getManager();
       //get user poition object
       $user_position_result = $em->getRepository('UserManagerSonataUserBundle:UserPosition')
                                  ->findOneBy(array('userId' => $user_id));
       
       $result = array();
       //result exists.
       if ($user_position_result) {
           $result = (object) array(
             'district'  =>$user_position_result->getDistrict(),
             'block'     =>$user_position_result->getCircle(),
             'position'  =>$user_position_result->getPosition()
           );
       } else { //otherwise passing -1 for each.
           $result = (object) array(
             'district'  =>-1,
             'block'     =>-1,
             'position'  =>-1
           );
       }
       return $result;
     }
     
    /**
     * uplaod the image for user contract of broker profile.
     * @param type $file
     * @param type $user_id
     * @param type $file_name
     * @return boolean
     */
    public function uploadSsnDocs($file, $user_id, $file_name)
    {
        $source= $_FILES['ssn']['tmp_name'];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../../web/uploads/users/contract/";
        $upload_media_dir     = $pre_upload_media_dir.$user_id.'/';  

        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } else { 
          $destination = \mkdir($pre_upload_media_dir.$user_id.'/', 0777, true);
          move_uploaded_file($source, $upload_media_dir.$file_name);
        }
        
        $s3imagepath = "uploads/users/contract/" . $user_id ;
        $image_local_path = $upload_media_dir.$file_name;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $file_name);
    }
    
    /**
     * uplaod the image for Idcard user contract of broker profile.
     * @param type $file
     * @param type $user_id
     * @param type $file_name
     * @return boolean
     */
    public function uploadIdCardDocs($file, $user_id, $file_name)
    {
        $source= $_FILES['idcard']['tmp_name'];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../../web/uploads/users/contract/";
        $upload_media_dir     = $pre_upload_media_dir.$user_id.'/';  

        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } else { 
          $destination = \mkdir($pre_upload_media_dir.$user_id.'/', 0777, true);
          move_uploaded_file($source, $upload_media_dir.$file_name);
        }
        
        $s3imagepath = "uploads/users/contract/" . $user_id ;
        $image_local_path = $upload_media_dir.$file_name;
        //upload on amazon
        $this->s3imageUpload($s3imagepath, $image_local_path, $file_name);
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
    * Profile Suggestion
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postSuggestionmultiprofilesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        if(!isset($de_serialize['name']) || !isset($de_serialize['type'])){
            $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
            echo json_encode($res_data);
            exit; 
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        // mongo odm
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $postStudyAll = array();
        // Set data for search
        $searchText = $object_info->name;
        $searchType = $object_info->type;
        $postStudyAll = $dm->getRepository('UserManagerSonataUserBundle:StudyList')->getSuggestionSearch($searchText, $searchType);
        $study_data = array();
        if(!empty($postStudyAll)){
            foreach ($postStudyAll as $key => $value) {
                $study_data[] = array(
                    'id' => $value->getId(),
                    'name' => $value->getName(),
                    'type' => $value->getType(),
                    'limit' => $this->suggestion_limit,
                    );
            }
        }
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $study_data);
        echo json_encode($final_data);
        exit;
    }
    
    /**
    * Get Non Relational User List
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function getNonRelationalUserAction(Request $request) {
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
        $username = $object_info->user_name;
        $userId   = $object_info->user_id;
        $offset = 0;
        $limit = $this->suggestion_limit;
        $em = $this->getDoctrine()->getManager();
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->searchNonRelationalUser($userId, $username, $offset, $limit);
        $study_data = array();
        if(!empty($results)){
            foreach ($results as $key => $value) {
                $study_data['users']['user_info'][] = array(
                    'user_id' => $value['id'],
                    'firstname' => $value['firstname'],
                    'lastname' => $value['lastname'],
                    'email' => $value['email']
                    );
            }
        } else {
            $study_data['users']['user_info'] = array();
        }
        
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $study_data);
        echo json_encode($final_data);
        exit;
    }
    
    /**
     * Get Citizen skills
     * @param int $user_id
     * @return array
     */
    public function getSkills($user_id)
    {
        $skills_result = array();
         $em = $this->getDoctrine()->getManager();
            $skillData = $em
                    ->getRepository('UserManagerSonataUserBundle:UserSkills')
                    ->findOneBy(array('userId' => $user_id));
            // Set data for search
            if(!empty($skillData)){
                $skills_result['skills']['user_id'] = $skillData->getUserId();
                $skills_result['skills']['skills'] = $skillData->getSkills();
            } else {
                $skills_result['skills'] = array();
            }
            return $skills_result;
    }

    /**
    * Checking Required Params In json
    * @param $user_params array json array send by user
    * @param $required_params array required params array
    */
    public function checkRequiredParams($user_params, $required_params) {
        
        foreach($required_params as $param){
            if (!array_key_exists($param, $user_params)) {   
                $final_data = array('code' => 130, 'message' => 'PARAMS_MISSING'.$param, 'data' => array());
                echo json_encode($final_data);
                exit; 
            }  
        }
        
    }
}
