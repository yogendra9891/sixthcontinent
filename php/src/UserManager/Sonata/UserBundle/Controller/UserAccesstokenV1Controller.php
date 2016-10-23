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
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\AccessToken;
use UserManager\Sonata\UserBundle\Entity\Client;

class UserAccesstokenV1Controller extends FOSRestController {
    const CONFIG_ACCESS_LIFETIME        = 'access_token_lifetime';  // The lifetime of access token in seconds.
    
    /**
  * Array of persistent variables stored.
  */
 protected $conf = array();
 
    /**
     * Facebook login
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getaccesstokenAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $data = array();
        $required_parameter = array('client_id', 'client_secret', 'grant_type', 'username', 'password');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {

             $resp = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
             echo json_encode($resp);
             exit();
        }
                
        //check if client id and client secret exist
        $rand_client_id = $object_info->client_id;
        $client_secret = $object_info->client_secret;
        
        $client_id_array = explode('_',$rand_client_id);

        $client_id = $client_id_array[1];
        
         //get entity manager object
       $em = $this->getDoctrine()->getManager();
       
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:Client')
                ->findOneBy(array('secret' => $client_secret, 'randomId' => $client_id));
        
        if(!$results){
            $data = array('error' => 'invalid_request', 'error_description' => 'Invalid grant_type parameter or parameter missing');
            $resp_data = array('code' => 1040, 'message' => 'INVALID_REQUEST', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
        
        //check if user exist in db
        $userManager = $this->getUserManager();
      
        //check user name
        $from_user   = $userManager->findUserBy(array('username' => $object_info->username));
       
        if(!$from_user){
            $data = array('error' => 'invalid_request', 'error_description' => 'Invalid grant_type parameter or parameter missing');
            $resp_data = array('code' => 1040, 'message' => 'INVALID_REQUEST', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
        
         //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($object_info->username);
        
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
         $password = $this->decodePassword($object_info->password);
        //@TODO: check the user instance
        //check for password
        if (!$this->checkUserPassword($from_user, $password)) {
            $res_data = array('code' => 100, 'message' => 'USERNAME_OR_PASSWORD_IS_WRONG', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        $access_token = $this->generateAccessToken();
        
        //set expiry for 1 years.
        $expiry = time() + (3600*24*365);
        $scope = 'user';
       
       $accessObj = new AccessToken();
       $accessObj->setToken($access_token);
       $accessObj->setExpiresAt($expiry);
       $accessObj->setScope($scope);
       $em->persist($accessObj);
       $em->flush();
       $new_acesstoken = $accessObj->getToken();
       $token_type = 'bearer';
       $scope = 'user';
       $refresh_token = '';
       $data = array('access_token'=>$new_acesstoken, 'expires_in'=>'', 'token_type'=>$token_type, 'scope'=>$scope, 'refresh_token'=>$refresh_token);
       $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
       echo json_encode($resp_data);
       exit();
       }
       
       
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    
    
    /**
  * Returns a persistent variable.
  *
  * @param $name
  *   The name of the variable to return.
  * @param $default
  *   The default value to use if this variable has never been set.
  *
  * @return
  *   The value of the variable.
  */
 public function getVariable($name, $default = NULL) {
   $name = strtolower($name);

   return isset($this->conf[$name]) ? $this->conf[$name] : $default;
 }
 
 
    public function generateAccessToken() {
        if (@file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100);
        } elseif (function_exists('openssl_random_pseudo_bytes')) { // Get 100 bytes of pseudo-random data
            $bytes = openssl_random_pseudo_bytes(100, $strong);
            if (true === $strong && false !== $bytes) {
                $randomData = $bytes;
            }
        }
        // Last resort: mt_rand
        if (empty($randomData)) { // Get 108 bytes of (pseudo-random, insecure) data
            $randomData = mt_rand() . mt_rand() . mt_rand() . uniqid(mt_rand(), true) . microtime(true) . uniqid(mt_rand(), true);
        }
        return rtrim(strtr(base64_encode(hash('sha256', $randomData)), '+/', '-_'), '=');
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
    protected function checkUserPassword($user, $password) {
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder) {
            return false;
        }
        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }
    
     /**
     * Decode password
     * @param string $password
     * @return string
     */
    public function decodePassword($password){
            return base64_decode($password);
    }
}