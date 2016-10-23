<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use JMS\Serializer\SerializerBuilder as JMSR;
use Symfony\Component\Form\FormTypeInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\UserBundle\Controller\SecurityController as BaseController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\OAuthServerBundle\Entity\TokenManager;
use UserManager\Sonata\UserBundle\Entity\UserToAccessToken;

/**
 * Class for REST API login and logout
 */
class RestSecurityController extends FOSRestController {

    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path_thumb = '/uploads/users/media/thumb/';
    protected $user_media_album_path = '/uploads/users/media/original/';
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->get('fos_user.user_manager');
    }

    /**
     * Check password
     * @param string $user
     * @param string $password
     * @return boolean
     */
    protected function checkUserPassword($user, $password) {
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder) {
            return false;
        }
        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    /**
     * Login user
     * @param array $user
     * @return void
     */
    protected function loginUser($user) { 
        $security = $this->get('security.context');
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $roles = $user->getRoles();
        $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
        $security->setToken($token);
    }

    /**
     * Call Login action
     * @param Request $request
     * @throws AccessDeniedException
     * @return multitype:boolean
     */
    public function postLoginsAction(Request $request) {
        //initilise the data array
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //end to get request object
        //@TODO: check the parameters in url
        //get user detail
        $username = $de_serialize['username'];
        $password = $de_serialize['password'];
        $password = $this->decodePassword($password); //password decryption
        //get user manager
        $um = $this->getUserManager();

        //get user detail
        $user = $um->findUserByUsername($username);
        if (!$user) {
            $res_data = array('code' => 100, 'message' => 'USERNAME_OR_PASSWORD_IS_WRONG', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        //check for login user type. Seller will not login in social.
        $seller_profile = $user->getSellerProfile();
        if($seller_profile == 1){
            $res_data = array('code' => 1090, 'message' => 'SELLER_CAN_NOT_LOGIN', 'data' => $data);
            $this->__createLog('Exiting from [RestSecurityController->logins] with response' . json_encode($res_data));
            $this->returnResponseWithHeader($res_data, 403);
        }
        
        //get verication status
        $verification_status = $user->getVerificationStatus();
        
        //check for trial period
        if($verification_status != 'VERIFIED'){
        $trial_period = $this->checkTrialPeriod($user);
        if(!$trial_period){
            try{
            //$user->setenabled(0);
            //$um->updateUser($user); //updatig user data
            }catch(\Exception $e){
              $response_data = array('code' => 1003, 'message' => 'ERROR_OCCURED', 'data' => $data);
              echo json_encode($response_data);
              exit();
            }
            $res_data = array('code' => 1045, 'message' => 'TRIAL_EXPIRED', 'data' => $data);
            $this->returnResponseWithHeader($res_data, 403);
        }
        }
        
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($username);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        


        if (!$user) {
            $res_data = array('code' => 100, 'message' => 'USERNAME_OR_PASSWORD_IS_WRONG', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        //@TODO: check the user instance
        //check for password
        if (!$this->checkUserPassword($user, $password)) {
            $res_data = array('code' => 100, 'message' => 'USERNAME_OR_PASSWORD_IS_WRONG', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
         
        //call login method
        $this->loginUser($user);
        
        $user_id = $user->getId();  
        $request = Request::createFromGlobals();
        $access_token=$request->query->get('access_token');

        if(!$access_token){
            $res_data=array('code'=>1037,'message'=>'INVALID_TOKEN','data'=>$data);
            echo json_encode($res_data);
            exit();
        }

        $this->user_toAccessTokenMapping($access_token,$user_id);
        //get user profile image
        $profile_image_id = $user->getProfileImg();
            $cover_image_id = $user->getCoverImg();
            $img_path       = '';            
            $img_thumb_path = '';
            $cover_img_path = '';
            $cover_img_thumb_path = '';
            if (!empty($profile_image_id)) {
               $dm = $this->get('doctrine.odm.mongodb.document_manager');
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
               $dm = $this->get('doctrine.odm.mongodb.document_manager');
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
             'broker_profile_active' =>$broker_profile_active,
             'current_language'=>$current_language,
             'verification_status' => $verification_status
 
         );

        //return the response
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $response_array);
        //return $res_data
        echo json_encode($res_data);
        exit();
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
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Check action
     * @throws \RuntimeException
     * @TODO Check action
     */
    public function checkAction() {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * Logout
     * @see FOS\UserBundle\Controller.SecurityController::logoutAction()
     * @param Requset class objet
     * @return array
     */
    public function postLogoutsAction(Request $request) {
        //initilise the data array
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object

        $access_token = $de_serialize['access_token'];
        $this->remove_UserToAccessTokenMap($access_token);

        //get entity manager object
        $dm = $this->getDoctrine()->getManager();

        $result = $dm
                ->getRepository('UserManagerSonataUserBundle:AccessToken')
                ->findOneByToken($access_token);

        if ($result) {
            $dm->remove($result);
            $dm->flush();
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
        $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
        echo json_encode($res_data);
        exit();
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
     * Decode password
     * @param string $password
     * @return string
     */
    public function decodePassword($password){
            return base64_decode($password);
    }
    /**
     * keep user to accesToken mapping
     * @param string $accessToken
     * @param integer $user_id
     * @return boolean
     */
     public function user_toAccessTokenMapping($access_token,$user_id){
        $data = array();
        $em = $this->getDoctrine()->getManager(); //get entity manager object 
        $ip_address_service = $this->container->get('ip_address.service');
        $ip_address = $ip_address_service->getCurrentIPAddress();
        //$map_obj=$em->getRepository('UserManagerSonataUserBundle:UserToAccessToken')->find($user_id);
        $map_obj=new UserToAccessToken();
        $map_obj->setAccessToken($access_token);
        $time=new \DateTime('now');  
        $map_obj->setCreatedAt($time);
        $map_obj->setUserId($user_id);
        $map_obj->setIPAddress($ip_address);
        try{
            $em->persist($map_obj);
            $em->flush();
        }catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit();
        }
        
    }
    public function remove_UserToAccessTokenMap($access_token){
        $em = $this->getDoctrine()->getManager(); //get entity manager object 
        $map_obj=$em->getRepository('UserManagerSonataUserBundle:UserToAccessToken')->findOneBy(array('accessToken'=>$access_token));
        try{
        $em->remove($map_obj);
        $em->flush();
        }catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit();
        }
    }

    
    /**
     * Check trial period for login user
     * @param object $user
     */
    public function checkTrialPeriod($user)
    {
         $ctime = time();
         $created_at = $user->getCreatedAt();
         $register_at = $created_at->format('Y-m-d H:i:s');
         $str_time = strtotime($register_at);
         
         $verify_time_limit=$this->container->getParameter('verify_time_limit'); //get verify time limit in hours
         $verify_time_limit_seconds = $verify_time_limit*3600;
         $expiry_data_diff =($ctime - $str_time);
         if($expiry_data_diff < $verify_time_limit_seconds){
             return true;
         }
         return false;
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponseWithHeader($data_array, $header) {
        $response = new Response(json_encode($data_array));

        $response->setStatusCode($header, 'FORBIDDEN');
        $response->headers->set('Content-Type', 'application/json');
        // prints the HTTP headers followed by the content
        $response->send();
        exit();
    }
    
     /**
     * Create social log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.social_profile_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }


}
