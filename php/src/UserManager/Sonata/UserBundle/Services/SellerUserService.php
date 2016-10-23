<?php
namespace UserManager\Sonata\UserBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use UserManager\Sonata\UserBundle\Entity\SellerUser;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\EmailVerificationToken;
use UserManager\Sonata\UserBundle\Entity\UserToAccessToken;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Locale\Locale;

// service method class for seller user handling.
class SellerUserService {

    protected $em;
    protected $dm;
    protected $container;
    CONST user_media_path = '/uploads/users/media/original/';
    CONST user_media_path_thumb = '/uploads/users/media/thumb/';
    CONST user_media_album_path_thumb = '/uploads/users/media/thumb/';
    CONST user_media_album_path = '/uploads/users/media/original/';
    CONST RESET = 'reset';
    CONST UNVERIFIED = 'UNVERIFIED';
    CONST SELLER_PROFILE_TYPE = 6;
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    /**
     * Check a seller is exists for a shop
     * @param int $shop_id
     * @param int $user_id
     * @return boolean
     */
    public function checkSellerExists($shop_id, $user_id) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkSellerExists]', array());
        $em = $this->em;
        $seller_user = $em->getRepository('UserManagerSonataUserBundle:SellerUser')
                          ->findOneBy(array('shopId'=>$shop_id, 'sellerId'=>$user_id));
        if (!$seller_user) { //if seller does not exists
          $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkSellerExists]', array());
          return false;  
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkSellerExists]', array());
        return true;
    }
    
    
   /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    private function __createLog($monolog_req, $monolog_response = array()){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.seller_profile_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);  
        return true;
    }
    
    /**
     * mapp the user into [selleruser] table
     * @param int $shop_id
     * @param int $user_id
     * @param int $owner_id
     * @return int $seller_id
     */
    public function mapSellerUser($shop_id, $user_id, $owner_id) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [mapSellerUser]', array());
        $em = $this->em;
        $seller_id = 0;
        $time = new \DateTime('now');
        $seller_user = new SellerUser();
        $seller_user->setShopId($shop_id);
        $seller_user->setSellerId($user_id);
        $seller_user->setOwnerId($owner_id);
        $seller_user->setCreatedAt($time);
        $seller_user->setUpdatedAt($time);
        try {
            $em->persist($seller_user);
            $em->flush();
            $seller_id = $seller_user->getId();
            $this->__createLog('Seller user is mapped into table [SellerUser] with shopid: '.$shop_id. ' and user id: '.$user_id. ' and table id: '.$seller_id, array());
        } catch (\Exception $ex) {
           $this->__createLog('There is Some error in mapping(save) the seller user into table [SellerUser] for shop: '.$shop_id. ' and user id: '.$user_id, 'Error is: '.$ex->getMessage()); 
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [mapSellerUser]', array());
        return $seller_id;
    }
    
     /**
     * create seller in table [SellerUser]
     * @param int $shop_id
     * @param int $user_id
     * @param int $owner_id
     * @return int $seller_record_id
     */
    public function createSeller($user_name, $email, $password, $firstname, $lastname, $phone, $lang, $country_code) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [createSeller]', array());
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $verification_accesstoken = $tokenGenerator->generateToken();
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
        $user->setPhone($phone);
        $user->setCitizenProfile(0);
        $user->setBrokerProfile(0);
        $user->setStoreProfile(0);
        $user->setSellerProfile(1);
        $user->setVerificationStatus(self::UNVERIFIED);
        $user->setCountry($country_code);
        $user->setProfileImg('');
        $user->setProfileType(self::SELLER_PROFILE_TYPE);
        $user->setCurrentLanguage($lang);
        $user->setVerificationToken($verification_accesstoken);
        $time = new \DateTime('now');
        $user->setVerifylinkCreatedAt($time);
        //get email constraint object
        $emailConstraint = new EmailConstraint(); 

        $errors = $this->container->get('validator')->validateValue(
                $email, $emailConstraint
        );

        // check email validation
        if (count($errors) > 0) {
            $res_data = array('code' => 135, 'message' => 'EMAIL_IS_INVALID', 'data' => array());
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [createSeller] with message '.json_encode($res_data), array());
            return $res_data;
        }

        //handling exception
        try {
            $check_success = $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [createSeller] with exception '.$e->getMessage(), array());
            $res_data = array('code' => 136, 'message' => 'USER_EXIST', 'data' => array());
            return $res_data;
        }
        $register_id = $user->getId(); //registerd user id
        $res_data = array('code' => 101, 'data' => array('register_id' => $register_id, 'verification_token' => $verification_accesstoken));
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [createSeller] with message '.json_encode($res_data), array());
        return $res_data;
    }
    
    /**
     * Add email verification token
     * @param int $register_id
     * @param string $tokenGenerator
     * @return boolean
     */
    public function addEmailVerificationToken($register_id, $tokenGenerator)
    {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [addEmailVerificationToken] with userid: '.$register_id. "token: ".$tokenGenerator, array());
        $verify_time_limit = $this->container->getParameter('verify_time_limit');
        $expiry = time() + (3600*$verify_time_limit); //for 2 days
        $em = $this->container->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $emailVerificationToken = new EmailVerificationToken();
        $emailVerificationToken->setUserId($register_id);
        $emailVerificationToken->setVerificationToken($tokenGenerator);
        $emailVerificationToken->setCreatedAt($time);
        $emailVerificationToken->setUpdatedAt($time);
        $emailVerificationToken->setIsActive(1);
        $emailVerificationToken->setExpiryAt($expiry);
        try{
        $em->persist($emailVerificationToken);
        $em->flush();
        }catch(\Exception $e){
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [addEmailVerificationToken] with message '.$e->getMessage(), array());
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [addEmailVerificationToken]', array());
        return true;
    }
    
    /**
     * Check if user is store creator
     * @param type $user_id
     * @param type $shop_id
     * @return boolean
     */
    public function checkUserToStoreRelation($user_id, $shop_id) {
        $em = $this->em;
        $user_store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'userId' => $user_id, 'role' => 15));
        if (count($user_store_obj) == 0) {
            return false;
        }
        return true;
    }
    
    /**
     *  check if store given is active store
     * @param type $user_id
     * @param type $store_id
     * @return boolean
     */
    public function checkForActiveStore($shop_id) {
        $em = $this->em;
        //check if store is active store
        $store_obj = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => $shop_id, 'isActive' => 1));
        if (count($store_obj) == 0) {
            return false;
        }
        return true;
    }
    
    /**
     * Send register mail
     */
    public function sendRegisterMail($user, $store_owner_obj, $token, $password)
    {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [sendRegisterMail]', array());
        $shop_name = $store_owner_obj['name'];
        $postService = $this->container->get('post_detail.service');
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $receiver = $postService->getUserData($user->getId(), true);
        //get locale
        $locale = !empty($receiver[$user->getId()]['current_language']) ? $receiver[$user->getId()]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale); //get email
        $email = $user->getEmail();
        //preapare lang array
        $lang = $lang_array['SELLER_REGISTRATION_WELCOME_MAIL_BODY'];    
        $link = $this->getVerificationLink($lang_array, $email, $token);
        $mail_sub = $lang_array['SELLER_REGISTRATION_WELCOME_MAIL_SUBJECT'];
        $mail_body = sprintf($lang, $shop_name);
        $mail_text = $lang_array['SELLER_REGISTRATION_WELCOME_MAIL_BODY_CENTER'];
        $bodyData  = sprintf($mail_text, $shop_name, $password, $link); 
        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'NEW_REGISTRATION');
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [sendRegisterMail] with response: '.json_encode($emailResponse), array());
        return true;
    }
    
    /**
     * Send register mail
     */
    public function sendMapUserMail($user, $store_owner_obj)
    {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [sendMapUserMail]', array());
        $shop_name = $store_owner_obj['name'];
        $postService = $this->container->get('post_detail.service');
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $receiver = $postService->getUserData($user->getId(), true);
        //get locale
        $locale = !empty($receiver[$user->getId()]['current_language']) ? $receiver[$user->getId()]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale); //get email
        $email = $user->getEmail();
        //preapare lang array
        $lang = $lang_array['SELLER_MAP_REGISTRATION_WELCOME_MAIL_BODY'];    
        $link = '';
        $mail_sub = $lang_array['SELLER_MAP_REGISTRATION_WELCOME_MAIL_SUBJECT'];
        $mail_body = sprintf($lang, $shop_name);
        $mail_text = $lang_array['SELLER_MAP_REGISTRATION_WELCOME_MAIL_BODY_CENTER'];
        $bodyData  = sprintf($mail_text, $shop_name); 
        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'NEW_REGISTRATION');
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUser] and function [sendRegisterMail] with response: '.json_encode($emailResponse), array());
        return true;
    }
    
    /**
     * Get verification link
     * @param array $lang_array
     * @param string $email
     * @param string $token
     * @return string
     */
    public function getVerificationLink($lang_array, $email, $token)
    {
        $angular_app_hostname = $this->container->getParameter('angular_social_app_hostname');
        $verify_link = $this->container->getParameter('verify_link');
        $verify_link_first=$angular_app_hostname.$verify_link;
        $click_name = $lang_array['CLICK_HERE'];
        $link = "<a href='$verify_link_first?email=$email&token=$token'>$click_name</a>";
        return $link;
    }

    /**
     * chek and remove if a seller is exists corrospondindg a shop
     * @param int $shop_id
     * @param int $seller_id
     * @return boolean
     */
    public function removeSeller($shop_id, $seller_id) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeSeller] with shopid: '.$shop_id. ' and for sellerid: '.$seller_id, array());
        $em = $this->em;
        $seller_mapping = $em->getRepository('UserManagerSonataUserBundle:SellerUser')
                             ->findOneBy(array('shopId' => $shop_id, 'sellerId' => $seller_id));
        if ($seller_mapping) { //if seller is exists
            $em->remove($seller_mapping);
            try {
                $em->flush();
            } catch (\Exception $ex) {

            }
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeSeller] with shopid: '.$shop_id. ' and for sellerid: '.$seller_id, array());
            return true;
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeSeller] with shopid: '.$shop_id. ' and for sellerid: '.$seller_id, array());
        return false;
    }
    
    /**
     * remove the seller from all shops of a user
     * @param array $shop_ids
     * @param int $seller_id
     * @return boolean
     */
    public function removeShopSeller($shop_ids, $seller_id) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeShopSeller] with shopids: '.$this->convertToJson($shop_ids). ' and sellerid: '.$seller_id, array());
        $em = $this->em;
        $results = $em->getRepository('UserManagerSonataUserBundle:SellerUser')
                      ->findBy(array('sellerId'=> $seller_id, 'shopId'=>$shop_ids));
        if (count($results)) {
            foreach($results as $result) {
             $this->__createLog('Seller is removed for shop id:'. $result->getShopId(). ' and sellerid: '.$result->getSellerId(), array());
             $em->remove($result); 
            } 
            try {
                $em->flush();
            } catch (\Exception $ex) {

            }
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeShopSeller]', array());
        return true;
    }
    
    /**
     * check user is a seller for any shop.
     * @param int $seller_id
     * @return true
     */
    public function checkSeller($seller_id) {
        $this->__createLog('Entering into class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [checkSeller]', array());
        $em = $this->em;
        $result = $em->getRepository('UserManagerSonataUserBundle:SellerUser')
                     ->findOneBy(array('sellerId'=> $seller_id));
        if ($result) { //record is exists for a shop
            $this->__createLog('Exiting from  class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [checkSeller]', array());
            return true;
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [checkSeller]', array());
        return false;
    }
    
    /**
     * convert to json string
     * @param array $data
     */
    public function convertToJson($data) {
        return json_encode($data);
    }
    
     /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    
     /**
     * Check trial period for login user
     * @param object $user
     */
    public function checkTrialPeriod($user)
    {
        $serializer = $this->container->get('serializer');
        $user_json = $serializer->serialize($user, 'json'); //convert documnt object to json string
        $this->__createLog('Entering in [[UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkTrialPeriod] with data:' . $user_json);
        $ctime = time();
        $created_at = $user->getCreatedAt();
        $register_at = $created_at->format('Y-m-d H:i:s');
        $str_time = strtotime($register_at);
        $verify_time_limit=$this->container->getParameter('verify_time_limit'); //get verify time limit in hours
        $verify_time_limit_seconds = $verify_time_limit*3600;
        $expiry_data_diff =($ctime - $str_time);
        if($expiry_data_diff < $verify_time_limit_seconds){
            $this->__createLog('Exiting from [[UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkTrialPeriod] with trail period remail.');
            return true;
        }
        $this->__createLog('Exiting from [[UserManager\Sonata\UserBundle\Services\SellerUser] and function [checkTrialPeriod] with trail period expired.');
        return false;
    }
    
     /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($username) {
        //get user manager
        $um = $this->getUserManager();

        //get user detail
        $user = $um->findUserByUsername($username);
        $user_check_enable = $user->isEnabled();
        return $user_check_enable;
    }
    
    /**
     * Check password
     * @param string $user
     * @param string $password
     * @return boolean
     */
    public function checkUserPassword($user, $password) {
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder) {
            return false;
        }
        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }
    
     /**
     * keep user to accesToken mapping
     * @param string $access_tokensendMapUserMail
     * @param integer $user_id
     * @return boolean
     */
     public function userToAccessTokenMapping($access_token, $user_id)
     {  
        $ip_address_service = $this->container->get('ip_address.service');
        $ip_address = $ip_address_service->getCurrentIPAddress();
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUser] and function [userToAccessTokenMapping] with access_toke:'.$access_token." user_id: ".$user_id);
        $em = $this->em; //get entity manager object 
        $map_obj=new UserToAccessToken();
        $map_obj->setAccessToken($access_token);
        $time=new \DateTime('now');  
        $map_obj->setCreatedAt($time);
        $map_obj->setUserId($user_id);
        $map_obj->setIPAddress($ip_address);
        try{
            $em->persist($map_obj);
            $em->flush();
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [userToAccessTokenMapping] with success');
            return true;
        }catch (\Doctrine\DBAL\DBALException $e) {
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [userToAccessTokenMapping] with exception'.$e->getMessage());
           return false;
        }
        return true;
    }
    
    /**
     * Get login user profile
     * @param int $user_id
     */
    public function getLoginUserProfile($user_id)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUser] and function [getLoginUserProfile] with user_id:'.$user_id);
        //get user manager
        $um = $this->getUserManager();
        $user = $um->findUserBy(array('id' => $user_id)); //get user detail
        
         //get user profile image
        $profile_image_id = $user->getProfileImg();
            $cover_image_id = $user->getCoverImg();
            $img_path       = '';            
            $img_thumb_path = '';
            $cover_img_path = '';
            $cover_img_thumb_path = '';
            if (!empty($profile_image_id)) {
               $dm = $this->dm;
               $media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                             ->find($profile_image_id);
                if ($media_info) {
                    $album_id   = $media_info->getAlbumId();
                    $media_name = $media_info->getName();
                    if (!empty($album_id)) {
                      $img_path       =  $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
                      $img_thumb_path =  $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
                    } else {
                      $img_path       =  $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$media_name;
                      $img_thumb_path =  $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id .'/'.$media_name; 
                    }
                }
            }
            if (!empty($cover_image_id)) {
               $dm = $this->dm;
               $cover_media_info = $dm->getRepository('MediaMediaBundle:UserMedia')
                             ->find($cover_image_id);
                if ($cover_media_info) {
                    $cover_album_id   = $cover_media_info->getAlbumId();
                    $cover_media_name = $cover_media_info->getName();
                    if (!empty($cover_album_id)) {
                      $cover_img_path       =  $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                      $cover_img_thumb_path =  $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id . '/'.$cover_album_id.'/'.$cover_media_name;
                    } else {
                      $cover_img_path       =  $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$cover_media_name;
                      $cover_img_thumb_path =  $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id .'/'.$cover_media_name; 
                    }
                }
            }
       
         $image_array = new \stdClass;
         $image_array->profile_image = $img_path;
         $image_array->profile_image_thumb = $img_thumb_path;
         $image_array->cover_image = $cover_img_path;
         $image_array->cover_image_thumb = $cover_img_thumb_path;
          
         $id = $user->getId();
         $username = $user->getUsername();
         $email = $user->getEmail();
         $enabled = true;
         $created_at = $user->getCreatedAt();
         $updated_at = $user->getUpdatedAt();
         $date_of_birth = $user->getDateOfBirth();
         $firstname = $user->getFirstname();
         $lastname = $user->getLastname();
         $gender = $user->getGender();
         $country = $user->getCountry();
         $profile_img = $img_path;
         $profile_img_thumb = $img_thumb_path;
         $cover_img = $cover_img_path;
         $cover_img_thumb = $cover_img_thumb_path;
         $profile_type = $user->getProfileType();
         $citizen_profile = $user->getCitizenProfile();
         $broker_profile = $user->getBrokerProfile();
         $store_profile = $user->getStoreProfile();
         $broker_profile_active = $user->getBrokerProfileActive();
         $current_language = $user->getCurrentLanguage();
         $verification_status = $user->getVerificationStatus();
         $seller_profile = $user->getSellerProfile();
         $response_array = array(
             'id' => $id,
             'username' => $username,
             'email' => $email,
             'enabled' => $enabled,
             'created_at' => $created_at,
             'updated_at' => $updated_at,
             'date_of_birth' => $date_of_birth,
             'firstname' => $firstname,
             'lastname' => $lastname,
             'gender' => $gender,
             'country' => $country,
             'profile_img' => $profile_img,
             'profile_img_thumb' => $profile_img_thumb,
             'cover_img' => $cover_img,
             'cover_img_thumb' => $cover_img_thumb,
             'profile_type' => $profile_type,
             'citizen_profile' => $citizen_profile,
             'broker_profile' => $broker_profile,
             'store_profile' => $store_profile,
             'seller_profile' => $seller_profile,
             'broker_profile_active' =>$broker_profile_active,
             'current_language'=>$current_language,
             'verification_status' => $verification_status
         );
         $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [getLoginUserProfile] with profilr:'.json_encode($response_array));
         return $response_array;
    }
    
     /**
     * Remove access token mapping
     * @param string $access_token
     * @return boolean
     */
    public function removeUserToAccessTokenMap($access_token)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUser] and function [removeUserToAccessTokenMap] with access_token:'.$access_token);
        $em = $this->em; //get entity manager object 
        $map_obj=$em->getRepository('UserManagerSonataUserBundle:UserToAccessToken')->findOneBy(array('accessToken'=>$access_token));
        if($map_obj){
        try{
        $em->remove($map_obj);
        $em->flush();
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [removeUserToAccessTokenMap] with access_token:'.$access_token);
        return true;
        }catch (\Doctrine\DBAL\DBALException $e) {
          $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [removeUserToAccessTokenMap] with exception:'.$e->getMessage());
          return false;
        }
        }
        return false;
    }
    
    /**
     * Rempve access token
     * @param string $access_token
     * @return boolean
     */
    public function removeAccessToken($access_token)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [removeAccessToken] with access_token:'.$access_token);
        //get entity manager object
        $em = $this->em;
        $result = $em
                ->getRepository('UserManagerSonataUserBundle:AccessToken')
                ->findOneByToken($access_token);
        if ($result) {
            try{
            $em->remove($result);
            $em->flush();
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [removeAccessToken] with message: success');
            return true;
            }catch(\Exception $e){
                $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUser] and function [removeAccessToken] with message: '.$e->getMessage());
                return false;
            }
        }
        return false;
    }
    
    /**
     * Send email
     * @param \FOS\UserBundle\Model\UserInterface $user
     * @return boolean
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {  
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [sendResettingEmailMessage]');
        //get angular host url
        $angular_social_host_url =$this->container->getParameter('angular_social_app_hostname');
        $to_id = $user->getId();
        $email = $user->getEmail();
        $reset = self::RESET;
        $postService = $this->container->get('post_detail.service');
        $receiver    = $postService->getUserData($to_id);
        //get locale
        $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        //get language constant
        $click_name = $lang_array['CLICK_HERE'];
        
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        //get reset url
        $reset_url = $angular_social_host_url."$reset?token=".$user->getConfirmationToken();
        $reset_url_colon = ": ".$angular_social_host_url."$reset?token=".$user->getConfirmationToken();
        
        $link = "<a href='$reset_url'>$click_name</a>";
        $lang_footer = $lang_array['FORGET_SELLER_PASSWORD_MAIL_FOOTER_BODY'];
        $mail_sub    = $lang_array['FORGET_SELLER_PASSWORD_MAIL_SUBJECT'];
        $mail_body   = $lang_array['FORGET_SELLER_PASSWORD_MAIL_HEADER_BODY'];
        $bodyData    = sprintf($lang_footer, $link, $reset_url_colon); 
        try {
            $emailResponse = $email_template_service->sendMail(array($receiver), $bodyData, $mail_body, $mail_sub, $receiver['profile_image_thumb'], 'FORGOT_PASSWORD');
        } catch (\Exception $ex) {
            $this->__createLog('There is some error in sending the mail on email: '.$email, 'Error is: '.$ex->getMessage());
        }
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [sendResettingEmailMessage]');
        return true;
    }
        
     /**
     * Get base profile
     * @param type $user_id
     * @return array
     */
    public function getSellerBasicProfile($user_id)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getSellerBasicProfile] with user_id:'.$user_id);
        //get country code with country name
        $countryLists = Locale::getDisplayCountries('en');
        $em = $this->em;
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('id'=>$user_id));
        if(!$results){
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getSellerBasicProfile] with message: User not found');
            return false;
        }     
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
        $base_cover_img = $this->getUserCoverImage($base_cover_img_id, $user_id);
        $country = $results->getCountry();
        $state = $results->getState();
        $citizen_profile = $results->getCitizenProfile();
        $store_profile = $results->getStoreProfile();
        $seller_profile = $results->getSellerProfile();
        $current_language = $results->getCurrentLanguage();
    
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
            'country' =>$country_name,
            'country_name' => $cc_name,
            'country_code' =>$cc_code,
            'state' =>$state,
            'profile_img' => $base_profile_img,
            'profile_cover_img' => $base_cover_img,
            'citizen_profile' => $citizen_profile,
            'current_language'=>$current_language,
            'profile_image' => $base_profile_img['original'],
            'profile_image_thumb' => $base_profile_img['thumb'],
            'cover_image' => $base_cover_img['original'],
            'cover_image_thumb' => $base_cover_img['thumb'],
            );
          $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getSellerBasicProfile] with user_info:'.$this->convertToJson($user_base_info));
          return $user_base_info;
    }
    
     /**
     * 
     * @param type $user_id
     * @param type $profile_type
     * @return string
     */
    public function getUserProfileImage($image_id, $user_id)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getUserProfileImage] with user_id:'.$user_id. "Image_id:".$image_id);
        $image_id = $image_id;
        $dm = $this->dm;
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
        if($album_id == ""){
        $mediaPath   = $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id . '/'.$media_name;
        }else{
        $mediaPath   = $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
        }
        $profile_img = array('original' => $mediaPath, 'thumb' =>$thumbDir);
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getUserProfileImage] with image_info:'.$this->convertToJson($profile_img));
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
    * Get user cover image
    * @param int $image_id
    * @param int $user_id
    * @return string
    */
   public function getUserCoverImage($image_id, $user_id)
    {
       $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getUserCoverImage] with user_id:'.$user_id. "Image_id:".$image_id);
        $image_id = $image_id;
        $dm = $this->dm;
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
        $mediaPath   = $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id .'/'.$media_name;
        }else{
        $mediaPath   = $this->getS3BaseUri() . self::user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . self::user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
        }
        $profile_img = array('original' => $mediaPath, 'thumb' =>$thumbDir,
                       'x_cord'=>$x_cord,'y_cord'=>$y_cord,'media_id'=>$image_id);
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [getUserCoverImage] with image_info:'.$this->convertToJson($profile_img));
       return $profile_img;
    }
    
   /**
     * return the response for forbidden access
     * @param array $data_array
     * @param int $header
     */
    public function returnResponseWithHeader($data_array, $header) {
        $response = new Response(json_encode($data_array));

        $response->setStatusCode($header, 'FORBIDDEN');
        $response->headers->set('Content-Type', 'application/json');
        // prints the HTTP headers followed by the content
        $response->send();
        exit();
    }
    
    /**
     * Update seller profile
     * @param type $users_array
     */
    public function updateSellerProfile($users_array)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [UpdateSellerProfile] with user_array:'.$this->convertToJson($users_array));
        $user_id = $users_array['seller_id'];
        $first_name = $users_array['firstname'];
        $last_name = $users_array['lastname'];
        $phone = $users_array['phone'];
        $password = $users_array['password'];
        $userManager = $this->getUserManager();
        $user = $userManager->findUserBy(array('id' => $user_id));
        $user->setFirstname($first_name);
        $user->setLastname($last_name);
        $user->setPhone($phone);
        //If password change is also required, then need to uncomment below code.
        /*if(strlen(trim($password)) > 0){
            $user->setPlainPassword($password); //set password
        }*/
    	//handling exception
    	try {
            $userManager->updateUser($user);
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [UpdateSellerProfile] with message:success');
            return true;
        } catch (\Exception $e) {
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [UpdateSellerProfile] with exception:'.$e->getMessage());
            return false;
        }
    }
    
    /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function getUserProfile($user_id) {
        //get user manager
        $um = $this->getUserManager();
        //get user detail
        $user = $um->findUserBy(array('id' => $user_id));
        return $user;
    }
    
    /**
     * Change password
     * @param int $user_id
     * @param string $password
     * @return boolean
     */
    public function changePassword($user_id, $password)
    {
        $this->__createLog('Entering in [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [changePassword] with user_id:'.$user_id. " password:".$password);
        $userManager = $this->getUserManager();
        $user = $this->getUserProfile($user_id);
        $user->setPlainPassword($password);
        try{
            $userManager->updateUser($user);
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [changePassword] with messsag:success');
            return true;
        } catch (Exception $ex) {
          $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Services\SellerUserService] and function [changePassword] with exception:'.$ex->getMessage());
          return false;
        }
    }
}