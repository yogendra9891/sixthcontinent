<?php
namespace UserManager\Sonata\UserBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use UserManager\Sonata\UserBundle\Entity\EmailVerificationToken;
use Symfony\Component\HttpFoundation\Response;
use Utility\UtilityBundle\Utils\Utility;

class VerificationController extends Controller {
    
    CONST SELLER = 'SELLER';
    CONST SOCIAL = 'SOCIAL';
    
    /**
     * Resend verification mail
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function resendverificationmailAction(Request $request)
    {
      
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('email');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                $resp = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
                $this->returnResponse($resp);
        }

        //get application type
       $application_type = (isset($de_serialize['app_type'])) ? $de_serialize['app_type'] : self::SOCIAL;
       $tokenGenerator = $this->container->get('fos_user.util.token_generator');
       $verification_accesstoken = $tokenGenerator->generateToken();
       $email = $object_info->email;

       if (Utility::matchString($application_type, self::SELLER)) {
            $resp = $this->checkSellerShopEmailExist($email);
        } else {
            $resp = $this->checkEmailExist($email); //check email exist
        }
        
        if(!$resp){
           $resp = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
           $this->returnResponse($resp);
       }
       //check if user has already verified
       $email_verification = $resp->getVerificationStatus();
       if($email_verification == 'VERIFIED'){
           $resp = array('code' => 1046, 'message' => 'EMAIL_ALREADY_VERIFIED', 'data' => $data);
           $this->returnResponse($resp);
       }

      
       $register_id = $resp->getId(); //get user id
       $this->addEmailVerificationToken($register_id, $verification_accesstoken);
       //send mail
       $this->sendVerificationEmail($resp, $verification_accesstoken, $application_type);

       
      $email_success = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
      $this->returnResponse($email_success);
    }
    
    /**
     * Check if email exist
     * @param string $email
     * @return boolean
     */
    public function checkEmailExist($email)
    {
        $data = array();
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('email' => $email));
        if($user){
            if($user->getSellerProfile() == 1){
                 $resp = array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
                 $this->returnResponse($resp);
            }
            return $user;
        }
             $resp = array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
             $this->returnResponse($resp);
    }
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj)
    {
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
            if (array_key_exists($param, $converted_array) && !empty($converted_array[$param])) {
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
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array,JSON_NUMERIC_CHECK);
        exit;
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
     * Send Email
     * @param object array $user
     * @return boolean
     */
    public function sendVerificationEmail($user, $token, $application_type)
    {
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($user->getId(), true);
        //get locale
        $locale = !empty($receiver[$user->getId()]['current_language']) ? $receiver[$user->getId()]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        $thumb_path = '';
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        if(Utility::matchString($application_type, self::SELLER)){
        $angular_app_hostname = $this->container->getParameter('angular_social_app_hostname');
        }
        $verify_link=$this->container->getParameter('verify_link');
        $verify_link_first=$angular_app_hostname.$verify_link;
        //preapare lang array

        $lang = $lang_array['RESEND_MAIL_BODY'];
        $email = $user->getEmail();

        $click_name = $lang_array['CLICK_HERE'];
        $login_text = $lang_array['LOGIN_TEXT'];
        $first_lastname = ucfirst($user->getFirstname())." ".ucfirst($user->getLastname());
       
        $email_template_service = $this->container->get('email_template.service'); //email template service.

        $link = "<a href='$verify_link_first?email=$email&token=$token'>$click_name</a>";
        $mail_sub = $lang_array['RESEND_MAIL_SUBJECT'];
        $mail_body = sprintf($lang, $first_lastname);
        $mail_text = $lang_array['RESEND_MAIL_BODY_CENTER'];
        $bodyData  = sprintf($mail_text, $link); 

        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'NEW_REGISTRATION');
        if($emailResponse)
        return true;
        else
        return false;
    }
    
    
    /**
     *Verify Registered account
     * @param string useremail
     * @param string verificationToken
     * @return string
     */
    public function accountverificationAction(Request $request){
       
        //initialize the data array 
        $data=array();
        
        //get request object
        $freq_obj=$request->get('reqObj');
        $fde_serialize=$this->decodeData($freq_obj);
        
        if(isset($fde_serialize)){
            $de_serialize = $fde_serialize;
        }else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_email', 'verify_token');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
                $this->returnResponse($resp);
        }
             
        //extract parameters.
        $user_email_encoded = $de_serialize['user_email'];
        $user_email=$user_email_encoded;
       // $user_email=$this->decodeEmail($user_email_encoded);
        $verify_token   = $de_serialize['verify_token'];
        	
        //get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
    	$user = $userManager->findUserBy(array('username' => $user_email));
        
    	if(!$user){
            $res_data =  array('code'=>1035, 'message'=>'ERROR_OCCURED', 'data'=>array());
            $this->returnResponse($res_data);
    	}
        
         //check if user is active or not
    	$user_check_enable = $user->isEnabled();
        
    	if($user_check_enable==false) {
    		$res_data =  array('code'=>1003, 'message'=>'ACCOUNT_IS_NOT_ACTIVE', 'data'=>$data);
    		 $this->returnResponse($res_data);
    	}
        
        //get Verification status
        $verification_status = $user->getVerificationStatus();
        if($verification_status == 'VERIFIED'){
            $res_data = array('code' => 1048, 'message' => 'ACCOUNT_HAS_ALREADY_VERIFIED', 'data' => array());
            $this->returnResponse($res_data);
        }
        
        //get user data
       $user_id=$user->getId();
       //check for Verification 
       $email_verification_object = $this->getEmailVerificationObject($user_id, $verify_token);
       
       //check for expiry
       $verification_expiry = $email_verification_object->getExpiryAt();
       //get current time
       $ctime = time();
       
       if ($verification_expiry < $ctime) {
            $res_data = array('code' => 1047, 'message' => 'VERIFICATION_LINK_EXPIRED', 'data' => array());
            $this->returnResponseWithHeader($res_data, 403);
        }
        
        //activate the user
        $user->setVerificationStatus('VERIFIED');
        try {
                $userManager->updateUser($user); //updatig user data
                $time = new \DateTime("now");
                
                //update for verification token
                $email_verification_object->setIsActive(2);
                $email_verification_object->setUpdatedAt($time);
                $em = $this->container->get('doctrine')->getManager();
                $em->persist($email_verification_object);
                $em->flush();
                //end
                
                $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                $this->returnResponse($response_data);
            } catch (\Doctrine\DBAL\DBALException $e) {
                $response_data = array('code' => 1035, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($response_data);
            }

       $this->returnResponse($response_data);
    }
    
    /**
     * Get Email veroification Object
     */
    public function getEmailVerificationObject($user_id, $verify_token)
    {
          $data = array();
          $em = $this->container->get('doctrine')->getManager();
          $result = $em
                        ->getRepository('UserManagerSonataUserBundle:EmailVerificationToken')
                        ->findOneBy(array('verificationToken' => $verify_token, 'isActive' => 1, 'userId'=> $user_id));
          if(!$result){
               $resp_data = array('code'=> 1049,'message'=>'TOKEN_NOT_EXIST','data'=>$data);
               $this->returnResponse($resp_data);
               exit();
          }
         return $result;  
    }
    
    
    /**
     * Add email verification token
     * @param int $register_id
     * @param string $tokenGenerator
     * @return boolean
     */
    public function addEmailVerificationToken($register_id, $tokenGenerator)
    {
        $expiry = time() + (3600*24*2); //for 2 days
        $em = $this->container->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $emailVerificationToken = new EmailVerificationToken();
        $emailVerificationToken->setUserId($register_id);
        $emailVerificationToken->setVerificationToken($tokenGenerator);
        $emailVerificationToken->setCreatedAt($time);
        $emailVerificationToken->setUpdatedAt($time);
        $emailVerificationToken->setIsActive(1);
        $emailVerificationToken->setExpiryAt($expiry);
        $em->persist($emailVerificationToken);
        $em->flush();
        return true;
    }
    
    /**
     * Check seller or Shop exists
     * @param string $email
     * @return string
     */
    private function checkSellerShopEmailExist($email) {
        $data = array();
        //get user manager
        $um = $this->container->get('fos_user.user_manager');
        //get user detail
        $user = $um->findUserBy(array('email' => $email));
        if ($user) {
            if ($user->getSellerProfile() == 1 || $user->getStoreProfile() == 1) {
                return $user;
            }
        }
        $resp = array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
        $this->returnResponse($resp);
    }

}