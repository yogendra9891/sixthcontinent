<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\UserDeletedAssign;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;

/**
 * Controller managing the user profile
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class RestProfileController extends BaseController
{
    /**
     * Show the user
     */
    public function postShowsAction(Request $request=null)
    {
    	
    	//initilise the data array
    	$data = array();
    	
    	//get request object
    	//$request = $this->getRequest();
    	
    	 //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
    	
    	//get user id
    	$user_id = $de_serialize['user_id'];
    	//check for user id
    	if($user_id==""){
    		$res_data =  array('code'=>111, 'message'=>'USER_ID_REQUIRED','data'=>array());
    		return $res_data;
    	}
    	//get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
   
    	$user = $userManager->findUserBy(array('id' => $user_id));
    	if(!$user){
    		$res_data =  array('code'=>100, 'message'=>'ERROR_OCCURED','data'=>array());
    		return $res_data;
    	}
    	//get user data
    	$user_name = $user->getUsername();
    	$user_email = $user->getEmail();
    	$user_group = $user->getGroupNames();
    	
    	//check if user is active or not
    	$user_check_enable = $this->checkActiveUserProfile($user_name);
    	
    	if($user_check_enable==false) {
    		$res_data =  array('code'=>100, 'message'=>'ACCOUNT_IS_NOT_ACTIVE','data'=>$data);
    		return $res_data;
    	}
    	
    	
    	//create the user info array
    	$user_info = array('user_id'=>$user_id, 'user_name'=>$user_name,'user_email'=>$user_email, 'user_group'=>$user_group);
    	
    	$resp_data = array('code'=>'101','message'=>'SUCCESS','data'=>$user_info);
    	
    	echo json_encode($resp_data);
        exit();
      
   }

    /**
     * Edit the user
     */
    public function postEditsAction(Request $request=null)
    {
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
    	
    	//get user id
    	$user_id = $de_serialize['user_id'];
    	$user_name = $de_serialize['username'];
    	$user_email = $de_serialize['email'];
    	//get password
    	$password = $de_serialize['password'];
    	//check if user is active or not
    	
    	//get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
    	
    	$user = $userManager->findUserBy(array('id' => $user_id));
    	$vuser_name = $user->getUsername();
    	
    	
    	$user_check_enable = $this->checkActiveUserProfile($vuser_name);
    	 
    	if($user_check_enable==false) {
    		$res_data =  array('code'=>100, 'message'=>'ACCOUNT_IS_NOT_ACTIVE','data'=>$data);
    		return $res_data;
    	}
    	
    	//get email constraint object
    	$emailConstraint = new EmailConstraint();
    	//  $emailConstraint->message = 'Your customized error message';
    	
    	$errors = $this->container->get('validator')->validateValue(
    			$user_email,
    			$emailConstraint
    	);
    	
    	// check email validation
    	if (count($errors) > 0) {
    		$res_data = array('code'=>'100','message'=>'ERROR_OCCURED','data'=>array());
    		return $res_data;
    	}
    	
    	
    	
    	
    	$user = $userManager->findUserBy(array('id' => $user_id));
    	
    	//if user name is not empty
    	if($user_name != ""){
    	$user->setUsername($user_name);
    	}
    	
    	//if user email is not empty
    	if($user_email !=""){
    	$user->setEmail($user_email);
    	}
    	
    	//if user password is not empty
    	if($password !=""){
    		$user->setPlainPassword($password);
    	}
    	
    	$user->setEnabled(true);
    	
    	//handling exception
    	try{
    	$userManager->updateUser($user);
    	}catch (\Exception $e) {
			$res_data = array('code'=>'100','message'=>'ERROR_OCCURED','data'=>array());
			return $res_data;
		}
		
		$res_data = array('code'=>'101','message'=>'PROFILE_UPDATED_SUCCESSFULLY','data'=>array());
                echo json_encode($res_data);
                exit();
    }
    
    /**
     * Change password after user login
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postChangepasswordsAction(Request $request)
    {
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
    	
    	//get user id
        $required_parameter = array('user_id', 'password1', 'password2', 'old_password');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, (object) $de_serialize);
        if ($chk_error) {

            $data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            echo $this->encodeData($data);
            exit;
        }
    	$user_id = $de_serialize['user_id'];
        
        $old_password = $de_serialize['old_password'];
        //get user password
        $password1 = $de_serialize['password1'];
        $password2 = $de_serialize['password2'];

        //decrypt password
        $password1 = $this->decodePassword($password1);
        $password2 = $this->decodePassword($password2);
        $old_password = $this->decodePassword($old_password);
        
        if(trim($password1) == "") {
            $res_data =  array('code'=>139, 'message'=>'PASSWORD_CAN_NOT_BE_BLANK','data'=>$data);
            return $res_data;
        }
        
        if($password1 != $password2) {
            $res_data =  array('code'=>138, 'message'=>'PASSWORD_NOT_MATCHED','data'=>$data);
            return $res_data;
        }
        
        //check if user has same old password
        
        $user_object = $this->checkActiveUserProfileId($user_id);
        //get password
        $user_object_old_password = $user_object->getPassword();
        if($user_object_old_password != $this->convertMd5($old_password)){
            $res_data =  array('code'=>174, 'message'=>'OLD_PASSWORD_NOT_MATCHED','data'=>$data);
            echo json_encode($res_data);
            die;
        }
        
    	$user_check_enable =  $user_object->isEnabled();
    	if($user_check_enable==false) {
    		$res_data =  array('code'=>100, 'message'=>'ACCOUNT_IS_NOT_ACTIVE','data'=>$data);
    		return $res_data;
    	}
        
        //get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $user_id));
    	//if user password is not empty
    	if($password1 !=""){
    		$user->setPlainPassword($password1);
    	}
    	
    	$user->setEnabled(true);
    	
    	//handling exception
    	try{
    	$userManager->updateUser($user);
        $user_email = $user->getEmail();
        $user_password = $user->getPassword();
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
          
        $curl_obj = $this->container->get("store_manager_store.curl"); 
        $env = $this->container->getParameter('kernel.environment');
        // BLOCK SHOPPING PLUS
//        if($env == 'dev'){ // test environment
//          $url = $this->container->getParameter('shopping_plus_get_client_url_test');
//          $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
//          $shopping_plus_password =$this->container->getParameter('social_bees_password_test');
//
//        } else {
//          $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
//          $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
//          $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
//        }
         
//            $request_data = array('o'=>'CLIENTEUPDATE',
//                'u'=>$shopping_plus_username,
//                'p'=>$shopping_plus_password,
//                'V01'=>$user_id,
//                'V02'=>$curl_obj->convertSpaceToHtml($firstname),
//                'V03'=>$curl_obj->convertSpaceToHtml($lastname),
//                'V04'=>$user_email,
//                'V05'=>'',
//                'V06'=>$user_email,
//                'V07'=>$user_password,
//                'V08'=>'',
//                'V09'=>'N'
//                );
//            $out_put_chp_citiz = $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);
//            $decode_data = urldecode($out_put_chp_citiz);
//            parse_str($decode_data, $final_output_chp_citiz);
//                if(isset($final_output_chp_citiz)){
//                   $sh_status = $final_output['stato'];
//                   $sh_error_desc = $final_output['descrizione'];
//                   $step = 'Change Citizen Password';
//                   $type = '1';
//                    if($sh_status != 0 ){
//                        $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
//                        $shopping_plus_obj->ShoppingplusStatus($user_id,$type,$status = 0,$sh_status,$sh_error_desc,$step);
//                     }
//             }
    	    }catch (\Exception $e) {
//			$res_data = array('code'=>'96','message'=>'ERROR_OCCURED','data'=>array());
//			return $res_data;
		}
                
           // update password of the shop on shopping plus      
//           $shops = $this->getStoresShoppinplus($user_id);
//           foreach($shops as $shop_data)
//           { 
//                $store_id                = $shop_data['id'];
//                $store_email             = $shop_data['email'];
//                $store_legalStatus       = $shop_data['legalStatus'];
//                $store_businessAddress   = $shop_data['businessAddress'];
//                $store_zip               = $shop_data['zip'];
//                $store_businessCity      = $shop_data['businessCity'];
//                $store_province          = $shop_data['province'];
//                $store_phone             = $shop_data['phone'];
//                $store_description       = $shop_data['description'];
//                $store_vatNumber         = $shop_data['vatNumber']; 
//                // $store_password          = $user_password;
//               $request_data = array('o'=>'PDVUPDATE',
//                        'u'=>$shopping_plus_username,
//                        'p'=>$shopping_plus_password,
//                        'V01'=>$store_id,
//                        'V02'=>$curl_obj->convertSpaceToHtml($store_legalStatus),      
//                        'V03'=>$curl_obj->convertSpaceToHtml($store_businessAddress),
//                        'V04'=>$curl_obj->convertSpaceToHtml($store_zip),
//                        'V05'=>$curl_obj->convertSpaceToHtml($store_businessCity),
//                        'V06'=>$curl_obj->convertSpaceToHtml($store_province),
//                        'V07'=>$store_phone,
//                      //  'V08'=>$store_email,
//                        'V08'=>$store_email,
//                        'V09'=>$curl_obj->convertSpaceToHtml($store_description),
//                       // 'V10'=>$user_email,    // (fos_user_user email)
//                        'V10'=>$store_vatNumber,    //vat_number ( this should be unique)
//                        'V11'=>$user_password, // fos_user_user password
//                        'V13'=>'N',
//                        'V14'=>0
//                    );
//                    try{
//                      $out_put_chp_shop =  $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);  
//                      $decode_data = urldecode($out_put_chp_shop);
//                      parse_str($decode_data, $final_output_chp_shop);
//                        if(isset($final_output_chp_shop)){
//                           $sh_status = $final_output_chp_shop['stato'];
//                           $sh_error_desc = $final_output_chp_shop['descrizione'];
//                           $step = 'Change shop Password';
//                           $type = '3';
//                            if($sh_status != 0 ){
//                                $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
//                                $shopping_plus_obj->ShoppingplusStatus($user_id,$type,$status = 0,$sh_status,$sh_error_desc,$step);
//                             }
//                      }
//                    } catch (\Exception $ex) {
//                    }
//           }    
		$res_data = array('code'=>'101','message'=>'PROFILE_UPDATED_SUCCESSFULLY','data'=>array());
                echo json_encode($res_data);
                exit();

    }
    
    /**
     * Disable the user profile
     * @param Request $request
     */
    public function postDeleteprofilesAction(Request $request)
    {
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
    	
    	//get user id
    	$user_id = $de_serialize['user_id'];
    	
    	if($user_id==""){
    		$res_data =  array('code'=>111, 'message'=>'USER_ID_REQUIRED','data'=>array());
    		return $res_data;
    	}
    	
    	//get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
    	
    	
    	$user = $userManager->findUserBy(array('id' => $user_id));

    	//disable the profile
    	$user->setEnabled(false);
    	
    	//handling exception
    	try{
    		$userManager->updateUser($user);
    	}catch (\Exception $e) {
    		$res_data = array('code'=>'100','message'=>'ERROR_OCCURED','data'=>array());
    		echo json_encode($res_data);
                exit();
    	}
    	
        //call the function for entering the data in user delete assign table(this is for disctrict,circle,position)
        $this->userDeleteAssign($user_id);
    	$res_data = array('code'=>'101','message'=>'PROFILE_UPDATED_SUCCESSFULLY','data'=>array());
    	echo json_encode($res_data);
        exit();
    }

   
    /**
     * Generate the redirection url when editing is completed.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('fos_user_profile_show');
    }

    /**
     * @param string $action
     * @param string $value
     */
    protected function setFlash($action, $value)
    {
        $this->container->get('session')->getFlashBag()->set($action, $value);
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
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj)
    {
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
    public function checkActiveUserProfile($username){
    	//get user manager
    	$um = $this->container->get('fos_user.user_manager');
    
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
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfileId($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user;
    }
    
    /**
     * Save the deleted user id in userdeletedasign table
     * @param type $user_id
     * @return boolean
     */
    public function userDeleteAssign($user_id)
    {
       //get entity manager object
       $em = $this->container->get('doctrine')->getManager();
       $deleted_user = new UserDeletedAssign(); //get entity class object
       $deleted_user->setUserId($user_id);
       $deleted_user->setAssignId(0); //set the default value for assign id 0
       $em->persist($deleted_user);
       $em->flush();
    }
    
   /*
     * get shop of the citizen user
     * @param type $user_id
     * @return array
     */
    public function getStoresShoppinplus($user_id)
    {
        $em = $this->container->get('doctrine')->getManager();
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getStoresShoppinplus($user_id);
        return $stores;
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
     * Convert to string to md5
     * @param string $password
     */
    public function convertMd5($password)
    {
        return md5($password);
    }
    
    /**
     * Edit the user
     */
    public function postChangecurrentlanguagesAction(Request $request)
    {
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
    	
    	//get user id
        $required_parameter = array('user_id', 'current_language');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, (object) $de_serialize);
        if ($chk_error) {

            $data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            echo $this->encodeData($data);
            exit;
        }
    	$user_id = $de_serialize['user_id'];
        $current_lang = $de_serialize['current_language'];
                
        
        //get usermanager object
    	$userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $user_id));
    	$user->setCurrentLanguage($current_lang);    	
    	//handling exception
    	try{
            $userManager->updateUser($user);
        }catch (\Exception $e) {
                $res_data = array('code'=>'96','message'=>'ERROR_OCCURED','data'=>array());
                return $res_data;
        }
           
        $res_data = array(
            'code'=>'101',
            'message'=>'SUCCESS',
            'data'=>array(
                'user'=>array(
                    'id'=>$user->getId(),
                    'current_language'=>$current_lang
                )
            ));
        echo json_encode($res_data);
        exit();

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
     * check user verification expiry
     * 
     */

    public function checkUserVerificationExpiryAction(Request $request){
        
        //initilise the array
        $data = array();
        $verify_time_param=$this->container->getParameter('verify_time_limit');
        $em = $this->container->get('doctrine')->getManager(); //get entity manager object 
        
        //disable accounts after 48 hours of registration 
        $update_accounts=$em->getRepository('UserManagerSonataUserBundle:User')->disableexpiredaccounts($verify_time_param); 
    }
}
