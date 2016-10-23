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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Controller\ResettingController as BaseController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RestResettingController extends BaseController
{
    const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

   
    /**
     * Forget password
     * 
     */
    public function forgetPasswordAction(Request $request=null)
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
    	
    	$username = $de_serialize['username'];
    	
    	if($username==""){
    		$res_data = array('code'=>'106','message'=>'INVALID_USERNAME','data'=>array());
    		$res_data_serialize = $this->encodeData($res_data);
    		echo $res_data_serialize;
    		exit;
    	}
    	//check if user is active or not
    	$user_check_enable = $this->checkActiveUserProfile($username);
    	
    	if($user_check_enable==false) {
    		$res_data =  array('code'=>100, 'message'=>'ACCOUNT_IS_NOT_ACTIVE','data'=>$data);
    		$res_data_serialize = $this->encodeData($res_data);
    		echo $res_data_serialize;
    		exit;
    	}
    	
    	/** @var $user UserInterface */
    	$user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
    	
    	if (null === $user) {
    		
    		$res_data = array('code'=>'106','message'=>'INVALID_USERNAME','data'=>array());
    		$res_data_serialize = $this->encodeData($res_data);
    		echo $res_data_serialize;
    		exit;
    		//return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array('invalid_username' => $username));
    	}
 
        //check for forget passowrd user type. Seller will not forget password in social.
        $seller_profile = $user->getSellerProfile();
        if ($seller_profile == 1) {
            $seller_service = $this->container->get('user.shop.seller'); //call seller service[UserManager\Sonata\UserBundle\Services\SellerUserService]
            $res_data = array('code' => 1087, 'message' => 'SELLER_CAN_NOT_FORGET_PASSWORD_IN_SOCIAL', 'data' => $data);
            $seller_service->returnResponseWithHeader($res_data, 403);
        }
        
    	if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
    		$res_data = array('code'=>'107','message'=>'THE_PASSWORD_FOR_THIS_USER_HAS_ALREADY_BEEN_REQUESTED_WITHIN_THE_LAST_24_HOURS','data'=>array());
    		$res_data_serialize = $this->encodeData($res_data);
    		echo $res_data_serialize;
    		exit;
    		//return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:passwordAlreadyRequested.html.'.$this->getEngine());
    	}
    	
    	if (null === $user->getConfirmationToken()) {
    		/** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
    		$tokenGenerator = $this->container->get('fos_user.util.token_generator');
    		$user->setConfirmationToken($tokenGenerator->generateToken());
    	}
    	
    	$this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
    	//$this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
    	$user->setPasswordRequestedAt(new \DateTime());
    	$this->container->get('fos_user.user_manager')->updateUser($user);
    	$this->sendResettingEmailMessage($user); // send mail to get token
    	$res_data = array('code'=>'105','message'=>'EMAIL_SENT','data'=>array());
    	$res_data_serialize = $this->encodeData($res_data);
    	echo $res_data_serialize;
    	exit;
    }
    
   
    /**
     * Send email
     * @param \FOS\UserBundle\Model\UserInterface $user
     * @return boolean
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {   
        //get angular host url
        $angular_host_url =$this->container->getParameter('angular_app_hostname');
        $to_id = $user->getId();

        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($to_id);
        //get locale
        $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        //get language constant
        $click_name = $lang_array['CLICK_HERE'];
        $forget_password_text = $lang_array['FORGET_PASSWORD'];
        $forget_password_support_msg = $lang_array['FORGET_PASSWORD_SUPPORT_MSG'];
        //$html = "Ciao " . $user->getUsername() . "<br /><br />";

        $email_template_service = $this->container->get('email_template.service'); //email template service.
        //get reset url
        $reset_url = $angular_host_url."reset?token=".$user->getConfirmationToken();
        $reset_url_colon = ": ".$angular_host_url."reset?token=".$user->getConfirmationToken();
        
        $link = "<a href='$reset_url'>$click_name</a>";
        $lang_footer = $lang_array['FORGET_PASSWORD_MAIL_FOOTER_BODY'];
        $mail_sub  = $lang_array['FORGET_PASSWORD_MAIL_SUBJECT'];
        $mail_body = $lang_array['FORGET_PASSWORD_MAIL_HEADER_BODY'];
        $bodyData      = sprintf($lang_footer, $link, $reset_url_colon); 

        $emailResponse = $email_template_service->sendMail(array($receiver), $bodyData, $mail_body, $mail_sub, $receiver['profile_image_thumb'], 'FORGOT_PASSWORD');
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
     * @param string $renderedTemplate
     * @param string $toEmail
     */
    protected function sendEmailMessage($renderedTemplate, $fromEmail, $toEmail)
    {
        // Render the email, use the first line as the subject, and the rest as the body
        $renderedLines = explode("\n", trim($renderedTemplate));
        $subject = $renderedLines[0];
        $body = implode("\n", array_slice($renderedLines, 1));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($sixthcontinent_admin_email)
            ->setTo($toEmail)
            ->setBody($body);

        $this->mailer->send($message);
    }
    

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.'.$this->getEngine(), array(
            'email' => $email,
        ));
    }

    /**
     * Reset user password
     */
    public function resetAction($token=null)
    {
    	//get request object
        $request = Request::createFromGlobals();
        
    	//initilise the data array
    	$data = array();
    	
    	//get request object
    	//$request = $this->getRequest();
    	
    	//$req_obj = $request->get('reqObj');
    	
    	//call the deseralizer
    	//$de_serialize = $this->decodeData($req_obj);
    	//get request object
       $freq_obj = $request->get('reqObj');
       $fde_serialize = $this->decodeData($freq_obj);

       if (isset($fde_serialize)) {
           $de_serialize = $fde_serialize;
       } else {
           $de_serialize = $this->getAppData($request);
       }
    	$token = $de_serialize['token'];
    	
    	$password = $de_serialize['password'];
    	
    	//check for password
    	if($password == "") {
    		$res_data = array('code'=>'113','message'=>'INVALID_PASSWORD','data'=>array());
    		$res_data_serialize = $this->encodeData($res_data);
    		echo $res_data_serialize;
    		exit;
    	}
    	
    	
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);
        
        //check for token
        if(!$user){
        	$res_data = array('code'=>'112','message'=>'INVALID_TOKEN','data'=>array());
        	$res_data_serialize = $this->encodeData($res_data);
        	echo $res_data_serialize;
        	exit;
        }
        
        //check if user is active or not
        $username = $user->getUsername();
        $user_check_enable = $this->checkActiveUserProfile($username);
        
        if($user_check_enable==false) {
        	$res_data =  array('code'=>100, 'message'=>'ACCOUNT_IS_NOT_ACTIVE','data'=>$data);
        	$res_data_serialize = $this->encodeData($res_data);
        	echo $res_data_serialize;
        	exit;
        }
        

        if (null === $user) {
        	
        	$res_data = array('code'=>'108','message'=>sprintf('The user with confirmation token does not exist for value %s', $token),'data'=>array());
        	$res_data_serialize = $this->encodeData($res_data);
        	echo $res_data_serialize;
        	exit;
        	
           // throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }
        
	//check for reset password user type. Seller will not reset password in social.
        $seller_profile = $user->getSellerProfile();
        if ($seller_profile == 1) {
            $seller_service = $this->container->get('user.shop.seller'); //call seller service[UserManager\Sonata\UserBundle\Services\SellerUserService]
            $res_data = array('code' => 1088, 'message' => 'SELLER_CAN_NOT_RESET_PASSWORD_IN_SOCIAL', 'data' => $data);
            $seller_service->returnResponseWithHeader($res_data, 403);
        }
        
        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $userManager = $this->container->get('fos_user.user_manager');
        //get user manager instance
       
        $user->setPlainPassword($password);
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);
        $user->setEnabled(true);
        $userManager->updateUser($user);
        $user_id = $user->getId();
        
        //reset password on shopping plus for citizen
        // BLOCK SHOPPING PLUS
        //$this->changeCitizenPasswordShoppingPlus($user_id);
        
        //reset password on shopping plus for shop
        // BLOCK SHOPPING PLUS
        //$this->changeShopPasswordShoppingPlus($user_id);
        
        $res_data = array('code'=>'101','message'=>'SUCCESS','data'=>array());
        $res_data_serialize = $this->encodeData($res_data);
    	echo $res_data_serialize;
    	exit;
    
    }
    
    /**
     * Update citizen password on shopping plus
     * @param int $user_id
     */
    public function changeCitizenPasswordShoppingPlus($user_id)
    {
        try{
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $user_id));

        $user_email = $user->getEmail();
        $user_password = $user->getPassword();
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
   
          
        $curl_obj = $this->container->get("store_manager_store.curl"); 
        $env = $this->container->getParameter('kernel.environment');
        if($env == 'dev'){ // test environment
          $url = $this->container->getParameter('shopping_plus_get_client_url_test');
          $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
          $shopping_plus_password =$this->container->getParameter('social_bees_password_test');

        } else {
          $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
          $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
          $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
        }
         
            $request_data = array('o'=>'CLIENTEUPDATE',
                'u'=>$shopping_plus_username,
                'p'=>$shopping_plus_password,
                'V01'=>$user_id,
                'V02'=>$curl_obj->convertSpaceToHtml($firstname),
                'V03'=>$curl_obj->convertSpaceToHtml($lastname),
                'V04'=>$user_email,
                'V05'=>'',
                'V06'=>$user_email,
                'V07'=>$user_password,
                'V08'=>'',
                'V09'=>'N'
                );
            $out_put_chp_citiz = $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);
            $decode_data = urldecode($out_put_chp_citiz);
            parse_str($decode_data, $final_output_chp_citiz);
                if(isset($final_output_chp_citiz)){
                   $sh_status = $final_output['stato'];
                   $sh_error_desc = $final_output['descrizione'];
                   $step = 'Change Citizen Password';
                   $type = '1';
                    if($sh_status != 0 ){
                        $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                        $shopping_plus_obj->ShoppingplusStatus($user_id,$type,$status = 0,$sh_status,$sh_error_desc,$step);
                     }
             }
        }catch (\Exception $e) {
//			$res_data = array('code'=>'96','message'=>'ERROR_OCCURED','data'=>array());
//			return $res_data;
		}
    }
    
    /**
     * Update shop password on shopping plus
     * @param int $user_id
     */
    public function changeShopPasswordShoppingPlus($user_id)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $user_id));
        $user_email = $user->getEmail();
        $user_password = $user->getPassword();
        
        $curl_obj = $this->container->get("store_manager_store.curl"); 
        $env = $this->container->getParameter('kernel.environment');
        if($env == 'dev'){ // test environment
          $url = $this->container->getParameter('shopping_plus_get_client_url_test');
          $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
          $shopping_plus_password =$this->container->getParameter('social_bees_password_test');

        } else {
          $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
          $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
          $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
        }
        
            // update password of the shop on shopping plus      
           $shops = $this->getStoresShoppinplus($user_id);
           foreach($shops as $shop_data)
           { 
                $store_id                = $shop_data['id'];
                $store_email             = $shop_data['email'];
                $store_legalStatus       = $shop_data['legalStatus'];
                $store_businessAddress   = $shop_data['businessAddress'];
                $store_zip               = $shop_data['zip'];
                $store_businessCity      = $shop_data['businessCity'];
                $store_province          = $shop_data['province'];
                $store_phone             = $shop_data['phone'];
                $store_description       = $shop_data['description'];
                $store_vatNumber         = $shop_data['vatNumber']; 
                // $store_password          = $user_password;
               $request_data = array('o'=>'PDVUPDATE',
                        'u'=>$shopping_plus_username,
                        'p'=>$shopping_plus_password,
                        'V01'=>$store_id,
                        'V02'=>$curl_obj->convertSpaceToHtml($store_legalStatus),      
                        'V03'=>$curl_obj->convertSpaceToHtml($store_businessAddress),
                        'V04'=>$curl_obj->convertSpaceToHtml($store_zip),
                        'V05'=>$curl_obj->convertSpaceToHtml($store_businessCity),
                        'V06'=>$curl_obj->convertSpaceToHtml($store_province),
                        'V07'=>$store_phone,
                      //  'V08'=>$store_email,
                        'V08'=>$store_email,
                        'V09'=>$curl_obj->convertSpaceToHtml($store_description),
                       // 'V10'=>$user_email,    // (fos_user_user email)
                        'V10'=>$store_vatNumber,    //vat_number ( this should be unique)
                        'V11'=>$user_password, // fos_user_user password
                        'V13'=>'N',
                        'V14'=>0
                    );
                    try{
                      $out_put_chp_shop =  $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);  
                      $decode_data = urldecode($out_put_chp_shop);
                      parse_str($decode_data, $final_output_chp_shop);
                        if(isset($final_output_chp_shop)){
                           $sh_status = $final_output_chp_shop['stato'];
                           $sh_error_desc = $final_output_chp_shop['descrizione'];
                           $step = 'Change shop Password';
                           $type = '3';
                            if($sh_status != 0 ){
                                $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                                $shopping_plus_obj->ShoppingplusStatus($user_id,$type,$status = 0,$sh_status,$sh_error_desc,$step);
                             }
                      }
                    } catch (\Exception $ex) {
                    }
           }  
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
     * Authenticate a user with Symfony Security
     *
     * @param \FOS\UserBundle\Model\UserInterface        $user
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function authenticateUser(UserInterface $user, Response $response)
    {
        try {
            $this->container->get('fos_user.security.login_manager')->loginUser(
                $this->container->getParameter('fos_user.firewall_name'),
                $user,
                $response);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }

    /**
     * Generate the redirection url when the resetting is completed.
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
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user)
    {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    /**
     * @param string $action
     * @param string $value
     */
    protected function setFlash($action, $value)
    {
        $this->container->get('session')->getFlashBag()->set($action, $value);
    }

    protected function getEngine()
    {
        return $this->container->getParameter('fos_user.template.engine');
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
     if($user){
    	$user_check_enable = $user->isEnabled();
    	return $user_check_enable;
      }
        return false;
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
    
}
