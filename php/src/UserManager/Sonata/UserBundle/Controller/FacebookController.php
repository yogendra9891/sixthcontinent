<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Facebook;
use UserManager\Sonata\UserBundle\Entity\FacebookUser;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationCitizen;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Utility\ApplaneIntegrationBundle\Event\FilterOrderEvent;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class FacebookController extends FOSRestController {

    /**
     * Facebook login
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function facebookloginAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('facebook_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => '100', 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //get facebook user id.
        $facebook_userid = $de_serialize['facebook_id'];
        //check if user is already registerd
        $check_fbuser = $this->checkFbUsers($facebook_userid, $de_serialize);
        if (!$check_fbuser) {
            $data = array('code' => '142', 'message' => 'THIS_FACEBOOK_ID_ALREADY_REGISTERD', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo $this->encodeData($data);
        exit;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->get('fos_user.user_manager');
    }

    /**
     * Check facebook user
     * @param type $facebook_userid
     * @param type $de_serialize
     * @return boolean
     */
    public function checkFbUsers($facebook_userid, $de_serialize) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em->getRepository('UserManagerSonataUserBundle:FacebookUser')
                      ->findOneBy(array('facebookId' => $facebook_userid));
        $de_serialize['email']    = (isset($de_serialize['email'])?$de_serialize['email']:'');
        if (count($results) == 0) {
            //$userinfo = $this->getUserInfo($register_id);
            $userinfo = array('new_user' => 'new_user');
            $data = array('code' => '99', 'message' => 'SUCCESS', 'data' => $de_serialize);
            echo $this->encodeData($data);
            exit;
            return true;
        }
        //login the user
        $uid = $results->getUserId();
        $userinfo = $this->getUserInfo($uid);
        $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $userinfo);
        echo $this->encodeData($data);
        exit;
    }

    /**
     * Get user detail
     * @param int $uid
     * @return arry
     */
    public function getUserInfo($uid) {
        //get user manager
        $um = $this->getUserManager();

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));

        $username = $user->getUsername();
        $email = $user->getEmail();
        $created_at = $user->getCreatedAt();
        $updated_at = $user->getUpdatedAt();
        $date_of_birth = $user->getDateOfBirth();
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
        $gender = $user->getGender();
        $country = $user->getCountry();
        $profile_img = $user->getProfileImg();
        $cover_img = $user->getCoverImg();
        $profile_type = $user->getProfileType();
        $citizen_profile = $user->getCitizenProfile();
        $broker_profile = $user->getBrokerProfile();
        $store_profile = $user->getStoreProfile();
        $current_language = $user->getCurrentLanguage();

        //create data array
        $data = array(
            'id' => $uid,
            'username' => $username,
            'email' => $email,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'date_of_birth' => $date_of_birth,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'gender' => $gender,
            'country' => $country,
            'profile_img' => $profile_img,
            'cover_img' => $cover_img,
            'profile_type' => $profile_type,
            'citizen_profile' => $citizen_profile,
            'broker_profile' => $broker_profile,
            'store_profile' => $store_profile,
            'current_language'=>$current_language
        );

        return $data;
    }

    /**
     * register the user
     * @param arry $de_serialize
     * @return string
     */
    public function registerUser($de_serialize) {
        //get username
        $user_name = $de_serialize['email'];

        //get email
        $email = $de_serialize['email'];

        //get password
        //$password = rand ( 1000000 , 9999999 );
          $password = $this->decodePassword($de_serialize['password']);
        //$password = $de_serialize['password'];
        
        //get first name
        $firstname = $de_serialize['firstname'];

        //get last name
        $lastname = $de_serialize['lastname'];

        //birthdate
        $birthday = $de_serialize['birthday'];

        //gender
        $gender = trim($de_serialize['gender']);

        //country
        $country = $de_serialize['country'];

        //type
        $type = $de_serialize['type'];
        
        $current_language = '';
        switch(strtolower($country)){
            case 'it':
                $current_language='it';
                break;
            default :
                $current_language='en';
                break;
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        if ($referral_id != "") {
            // checking V12 is citizen Id or not
            $citizenuser = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                              ->checkActiveCitizen($referral_id);
            if (!($citizenuser)) {
                $res_data = array('code' => '154', 'message' => 'CITIZEN_DOES_NOT_EXIT', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }

        $userManager = $this->container->get('fos_user.user_manager');

        //get user manager instance
        $user = $userManager->createUser();

        //set username
        $user->setUsername($user_name);

        //set email
        $user->setEmail($email);

        //set and encrypt password
        $user->setPlainPassword($password);
        $user->setEnabled(1);
        $user->setFirstname($firstname);

        $user->setLastname($lastname);
        //get birth date date object
        $btime = new \DateTime($birthday);
        if (!$btime) {
            $data = array('code' => '131', 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }

        $user->setDateOfBirth($btime);

        $user->setGender($gender);

        $user->setCountry($country);

        $user->setProfileType($type);
        $user->setProfileImg('');
        $user->setCoverImg('');
        $user->setCitizenProfile(1);
        $user->setBrokerProfile(0);
        $user->setStoreProfile(0);
        $user->setCurrentLanguage($current_language);
        $user->setVerificationToken('');
        // set verify link created at
        $time = new \DateTime('now');
        $user->setVerifylinkCreatedAt($time);
//        if ($referral_id != "") {
//             $user->setAffiliationStatus(1);
//        }
        
        //get email constraint object
        $emailConstraint = new EmailConstraint();
        //  $emailConstraint->message = 'Your customized error message';

        $errors = $this->container->get('validator')->validateValue(
                $email, $emailConstraint
        );

        // check email validation
        if (count($errors) > 0) {
            $res_data = array('code' => '135', 'message' => 'EMAIL_IS_INVALID', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $check_email = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('email' => $email));

        if ($check_email) {
            $resp_data = array('user_id' => $check_email->getId(), 'facebook_id' => $de_serialize['facebook_id'], 'facebook_accesstoken'=>$de_serialize['facebook_accesstoken']);
            //user exist with same email id
            $data = array('code' => '98', 'message' => 'USER_EXIST_WITH_SAME_EMAIL_ID._YOU_CAN_MAP_YOUR_ACCOUNT', 'data' => $resp_data);
            echo $this->encodeData($data);
            exit;
        }
        //handling exception
        try {
            $check_success = $userManager->updateUser($user, true);           
            //send email
            $this->sendAction($user);
            //set default citizen profile
            $register_id = $user->getId();
            $set_default_citizen = $this->setDefaultCitizenProfile($register_id);
        } catch (\Exception $e) {
            $res_data = array('code' => '1361', 'message' => 'ERROR_OCCURED : '. $e->getMessage(), 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
//        //making the entry for affiliation for citizen.
//        if ($referral_id != "") {
//            $em = $this->container->get('doctrine')->getManager();
//                //get citizen user object
//                $citizen_affiliation = new AffiliationCitizen();
//                $time = new \DateTime('now');
//                $citizen_affiliation->setFromId($referral_id);
//                $citizen_affiliation->setToId($register_id);
//                $citizen_affiliation->setCreatedAt($time);
//                $em->persist($citizen_affiliation);
//                $em->flush();
//        }
        
       //code start for shopping plus registration
        $register_id   = $user->getId();
        $s_firstname   = $user->getFirstName();
        $s_lastname    = $user->getLastName();
        $s_email       = $user->getEmail();
        $s_md5password = $user->getPassword();

        $env = $this->container->getParameter('kernel.environment');
        // BLOCK SHOPPING PLUS
//        if ($env == 'dev') { // test environment
//            $url = $this->container->getParameter('shopping_plus_get_client_url_test');
//            $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
//            $shopping_plus_password = $this->container->getParameter('social_bees_password_test');
//        } else {
//            $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
//            $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
//            $shopping_plus_password = $this->container->getParameter('social_bees_password_prod');
//        }
//        $curl_obj = $this->container->get("store_manager_store.curl");
//        $request_data = array('o' => 'CLIENTEADD',
//            'u'   => $shopping_plus_username,
//            'p'   => $shopping_plus_password,
//            'V01' => $register_id,
//            'V02' => $curl_obj->convertSpaceToHtml($s_firstname),
//            'V03' => $curl_obj->convertSpaceToHtml($s_lastname),
//            'V04' => $s_email,
//            'V05' => '', // I do not get this value from above so I left it blank
//            'V06' => $s_email,
//            'V07' => $s_md5password,
//            'V08' => $referral_id,
//            'V09' => 'N'
//        );
//        try {
//                $out_put = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
//                $decode_data = urldecode($out_put);
//                parse_str($decode_data, $final_output);
//                if(isset($final_output)){
//                   $sh_status = $final_output['stato'];
//                   $sh_error_desc = $final_output['descrizione'];
//                   $step = 'Citizen Registeration';
//                   $type = '1';
//                    if($sh_status != 0 ){
//                        $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
//                        $shopping_plus_obj->ShoppingplusStatus($register_id,$type,$status = 0,$sh_status,$sh_error_desc,$step);
//                     }
//                }
//        } catch (\Exception $ex) {
//            
//        }
        return $user->getId();
    }

    /**
     * Map facebook user with sixth continent
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function mapfacebookuserAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }


        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('facebook_id', 'user_id', 'facebook_accesstoken');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => '100', 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        //parameter check end 

        $facebook_id = $de_serialize['facebook_id'];
        $user_id     = $de_serialize['user_id'];
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check if facebook id is already registerd
        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:FacebookUser')
                ->findOneBy(array('facebookId' => $facebook_id));

        if (count($results) > 0) {
            $data = array('code' => '142', 'message' => 'THIS_FACEBOOK_ID_ALREADY_REGISTERD', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
        $fbAllow = $this->container->getParameter('facebook_post_allow');
        
        // mapping of facebook account with sixtcontinent account
        if($fbAllow)
        {
            $facebookService = $this->container->get('facebook_auto_post.service');
            $fbToken = $facebookService->setAccessToken($de_serialize['facebook_accesstoken'])
                                             ->getExtendedAccessToken();
            $extendedAccessToken = $fbToken['code']==101 ? $fbToken['access_token'] : $de_serialize['facebook_accesstoken'];
            if(empty($fbToken['expiry'])){
                $eAccessTokenInfo = $facebookService->setAccessToken($extendedAccessToken)
                                             ->getAccessTokenInfo();
                $token_expiry = isset($eAccessTokenInfo['expires_at']) ? $eAccessTokenInfo['expires_at'] : '';
                $isValid = isset($eAccessTokenInfo['is_valid']) ? $eAccessTokenInfo['is_valid'] : 0;
            }else{
                $token_expiry = $fbToken['expiry'];
                $isValid = 1;
            }
            $isPermitToPublish = $facebookService->setAccessToken($extendedAccessToken)
                    ->checkPermissions('publish_actions');
        } else {
            $extendedAccessToken = isset($de_serialize['facebook_accesstoken'])?$de_serialize['facebook_accesstoken'] : '';
            $token_expiry = 0;
            $isValid = 0;
            $isPermitToPublish = false;
        }
        
        if($fbAllow and $isPermitToPublish==false){
            $facebookService->setAccessToken($extendedAccessToken)
                    ->deletePermissions();
        }
        
        //map the user
        $facebookuser = new FacebookUser();
        $facebookuser->setUserId($user_id);
        $facebookuser->setFacebookId($facebook_id);
        $facebookuser->setFacebookAccessToken($extendedAccessToken);
        $facebookuser->setExpiryTime($token_expiry);
        $facebookuser->setSyncStatus($isValid);
        $facebookuser->setPublishActions($isPermitToPublish);
        $em->persist($facebookuser);
        $em->flush();
        
        //get user manager
        $um = $this->getUserManager();

        //get user detail
        $user = $um->findUserBy(array('id' => $user_id));
        
        if (!$user) {
            $data = array('code' => '97', 'message' => 'SUCCESS', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
        $username       = $user->getUsername();
        $email          = $user->getEmail();
        $firstname      = $user->getFirstname();
        $lastname       = $user->getLastname();
        $user_info = array('facebook_id'=>$facebook_id, 'email'=>$email, 'first_name'=>$firstname, 'last_name'=>$lastname);
        $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $user_info);
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
     * Facebook register
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function facebookregisterAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('facebook_id', 'email', 'firstname', 'lastname', 'birthday', 'country', 'type', 'facebook_accesstoken');
        
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $res_data = array('code' => '100', 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $fbAllow = $this->container->getParameter('facebook_post_allow');
        
        if($fbAllow)
        {
            $facebookService = $this->container->get('facebook_auto_post.service');
            $fbToken = $facebookService->setAccessToken($de_serialize['facebook_accesstoken'])
                                             ->getExtendedAccessToken();
            $extendedAccessToken = $fbToken['code']==101 ? $fbToken['access_token'] : $de_serialize['facebook_accesstoken'];
            if(empty($fbToken['expiry'])){
                $eAccessTokenInfo = $facebookService->setAccessToken($extendedAccessToken)
                                             ->getAccessTokenInfo();
                $token_expiry = isset($eAccessTokenInfo['expires_at']) ? $eAccessTokenInfo['expires_at'] : '';
                $isValid = isset($eAccessTokenInfo['is_valid']) ? $eAccessTokenInfo['is_valid'] : 0;
            }else{
                $token_expiry = $fbToken['expiry'];
                $isValid = 1;
            }
            $isPermitToPublish = $facebookService->setAccessToken($extendedAccessToken)
                    ->checkPermissions('publish_actions');
            
        } else {
            $extendedAccessToken = isset($de_serialize['facebook_accesstoken'])?$de_serialize['facebook_accesstoken'] : '';
            $token_expiry = 0;
            $isValid = 0;
            $isPermitToPublish = false;
        }
        
        
        if($fbAllow and $isPermitToPublish==false){
            $facebookService->setAccessToken($extendedAccessToken)
                    ->deletePermissions();
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //register on fos user table
        $register_id = $this->registerUser($de_serialize);
        $facebook_userid = $de_serialize['facebook_id'];
        $email = $de_serialize['email'];
        //register the user on facebook table
        $facebookuser = new FacebookUser();
        $facebookuser->setUserId($register_id);
        $facebookuser->setFacebookId($facebook_userid);
        $facebookuser->setFacebookAccessToken($extendedAccessToken);
        $facebookuser->setExpiryTime($token_expiry);
        $facebookuser->setSyncStatus($isValid);
        $facebookuser->setPublishActions($isPermitToPublish);
        $em->persist($facebookuser);
        $em->flush();

        $appalne_data = $de_serialize;
        $appalne_data['register_id'] = $register_id;

        //get dispatcher object
        $this->_log('begin citizen register dispacher : '.$register_id );
        $event = new FilterDataEvent($appalne_data);
        $this->_log('success fully created event object: '.$register_id);
        $dispatcher = $this->container->get('event_dispatcher');
        $this->_log('successfully get dispacher object: '.$register_id);
        $dispatcher->dispatch('citizen.register', $event);
        $this->_log('event is dispatched: '.$register_id);
        
        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        
        if ($referral_id != "") {
                $store_service = $this->container->get('store_manager_store.storeUpdate');
                $response = $store_service->updateUserAffiliation($register_id, $referral_id, $register_id, 1);
        }
        $affiliation_service = $this->container->get('affiliation_affiliation_manager.user');
        $affiliation_service->updateAffiliationStatusForEmailId($referral_id,$email,1);    
        //login the user
        $userinfo = $this->getUserInfo($register_id);
        $data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $userinfo);
        echo $this->encodeData($data);
        exit;
    }
    
    
    /**
     * set citizen profile
     * @param int $user_id
     * @return boolean
     */
    public function setDefaultCitizenProfile($user_id) {
        $user_id = $user_id;
        $region = '';
        $city = '';
        $address = '';
        $zip = '';
        $latitude = '';
        $longitude = '';

        //get citizen user object
        $citizenuser = new CitizenUser();
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //check user is already registerd
        $citizen_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId' => $user_id));
        if ($citizen_user_result) {
            return 5; //already exist
        }
        $citizenuser->setUserId($user_id);
        $citizenuser->setRoleId(22);
        $citizenuser->setRegion($region);
        $citizenuser->setCity($city);
        $citizenuser->setZip($zip);
        $citizenuser->setAddress($address);
        $citizenuser->setMapPlace('');
        $citizenuser->setLatitude($latitude);
        $citizenuser->setLongitude($longitude);
        $citizenuser->setProfileImg('');
        //get birth date date object
        $time = new \DateTime('now');
        $citizenuser->setCreatedAt($time);
        $citizenuser->setUpdatedAt($time);
        
        $em->persist($citizenuser);
        $em->flush();
        return 1;
    }
    
    /**
     * sending the mail.
     * @param user object
     * @return int
     */
    public function sendAction($user) {
        if($user){
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($user->getId(), true);
            //get locale
            $locale = !empty($receiver[$user->getId()]['current_language']) ? $receiver[$user->getId()]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);

            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            //preapare lang array
            $lang = $lang_array['REGISTRATION_WELCOME_MAIL_BODY'];

            $click_name = $lang_array['CLICK_HERE'];
            $login_text = $lang_array['LOGIN_TEXT'];
            $first_lastname = ucfirst($user->getFirstname())." ".ucfirst($user->getLastname());


            $email_template_service = $this->container->get('email_template.service'); //email template service.
            $link = "<a href='$angular_app_hostname'>$click_name</a>";
            $mail_sub = $lang_array['REGISTRATION_WELCOME_MAIL_SUBJECT'];
            $mail_body = sprintf($lang, $first_lastname);
            $mail_text = $lang_array['REGISTRATION_WELCOME_MAIL_BODY_CENTER'];
            $bodyData  = sprintf($mail_text, $link); 

            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'NEW_REGISTRATION');
        }
        return true;
    }

   /**
    * function for gettign the admin id
    * @param None
    */
   private function getAdminId() {
       $em = $this->container->get('doctrine')->getManager();
       $admin_id = $em
               ->getRepository('StoreManagerStoreBundle:Storeoffers')
               ->findByRole('ROLE_ADMIN');
       return $admin_id;
   }
   
    /**
     * Decode password
     * @param string $password
     * @return string
     */
    public function decodePassword($password){
            return base64_decode($password);
    }
    
    /* update user access token
     * @param Request $request
     * @return array;
     */
    public function postUpdatefacebookaccesstokensAction(Request $request) {
        //initilise the array
        $users_array = array();
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

        $required_parameter = array('user_id', 'facebook_id', 'facebook_accesstoken');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //validating params
        $requited_fields = array('user_id', 'facebook_id', 'facebook_accesstoken');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        //get user id
        $user_id        = $de_serialize['user_id'];
        $facebook_id    = $de_serialize['facebook_id'];
        $facebook_accesstoken = $de_serialize['facebook_accesstoken'];
        

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        $fbAllow = $this->container->getParameter('facebook_post_allow');
        
        if($fbAllow)
        {
            $facebookService = $this->container->get('facebook_auto_post.service');
            $fbToken = $facebookService->setAccessToken($facebook_accesstoken)
                                             ->getExtendedAccessToken();
            $extendedAccessToken = $fbToken['code']==101 ? $fbToken['access_token'] : $de_serialize['facebook_accesstoken'];

            if(empty($fbToken['expiry'])){
                $eAccessTokenInfo = $facebookService->setAccessToken($extendedAccessToken)
                                             ->getAccessTokenInfo();
                $token_expiry = isset($eAccessTokenInfo['expires_at']) ? $eAccessTokenInfo['expires_at'] : '';
                $isValid = isset($eAccessTokenInfo['is_valid']) ? $eAccessTokenInfo['is_valid'] : 0;
            }else{
                $token_expiry = $fbToken['expiry'];
                $isValid = 1;
            }
            $isPermitToPublish = $facebookService->setAccessToken($extendedAccessToken)
                    ->checkPermissions('publish_actions');
        }else{
            $extendedAccessToken = $facebook_accesstoken;
            $token_expiry = 0;
            $isValid = 0;
            $isPermitToPublish = false;
        }
        
        if($fbAllow and $isPermitToPublish==false){
            $facebookService->setAccessToken($extendedAccessToken)
                    ->deletePermissions();
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $Fb_res = $em
                ->getRepository('UserManagerSonataUserBundle:FacebookUser')
                ->findOneBy(array('userId' => $user_id));

        if(!$Fb_res){
            $Fb_res = new FacebookUser();
            $Fb_res->setUserId($user_id);
        } 
        
        $Fb_res->setFacebookId($facebook_id);
        $Fb_res->setFacebookAccessToken($extendedAccessToken);
        $Fb_res->setExpiryTime($token_expiry);
        $Fb_res->setSyncStatus($isValid);
        $Fb_res->setPublishActions($isPermitToPublish);
        $em->persist($Fb_res);
        $em->flush();
                
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());

        echo json_encode($resp_data);
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
    
    public function _log($sMessage){
        $monoLog = $this->container->get('monolog.logger.channel1');
        $monoLog->info($sMessage);
    }
    
    private function _toJSON($data){
        return json_encode($data);
    }

}
