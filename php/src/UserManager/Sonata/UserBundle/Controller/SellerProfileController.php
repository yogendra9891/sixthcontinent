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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use FOS\UserBundle\Model\UserInterface;
use UserManager\Sonata\UserBundle\Utils\MessageFactory as Msg;

/**
 * Controller managing the seller profile
 */
class SellerProfileController extends BaseController {

    const SESSION_EMAIL = 'fos_user_send_resetting_email/email';

    /**
     * forget seller password
     * @param \Symfony\Component\HttpFoundation\Request $request
     * return json response
     */
    public function forgetSellerPasswordAction(Request $request) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword]');
        $required_parameter = array('username');
        $data = array();
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service[UserManager\Sonata\UserBundle\Services\SellerUserService]
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) { //if any parameter missed
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request); //return associate array
        $user_name = $de_serialize['username'];
        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($user_name);
        if (null === $user) { //if user does not exists
            $resp_data = new Resp(Msg::getMessage(1030)->getCode(), Msg::getMessage(1030)->getMessage(), $data); //INVALID_USERNAME
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        $user_check_enable = $user->isEnabled();
        if (!$user_check_enable) { //check user is enabled
            $resp_data = new Resp(Msg::getMessage(1079)->getCode(), Msg::getMessage(1079)->getMessage(), $data);//SELLER_ACCOUNT_IS_NOT_ACTIVE
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        //check user is seller user
        //check forget password for only seller and shop type
        $shop_profile = $user->getStoreProfile();
        $seller_profile = $user->getSellerProfile();
        if ($shop_profile == 0 && $seller_profile == 0) { //only shop profile and seller user
            $res_data = array('code' => Msg::getMessage(1082)->getCode(), 'message' => Msg::getMessage(1082)->getMessage(), 'data' => $data);//ONLY_SELLER_SHOP_CAN_FORGET_PASSWORD
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . Utility::encodeData($res_data));
            $seller_service->returnResponseWithHeader($res_data, 403);
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            $resp_data = new Resp(Msg::getMessage(1031)->getCode(), Msg::getMessage(1031)->getMessage(), $data);//THE_PASSWORD_FOR_THIS_USER_HAS_ALREADY_BEEN_REQUESTED_WITHIN_THE_LAST_24_HOURS
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        //$this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);
        $user_id = $user->getId();
        $seller_service->sendResettingEmailMessage($user); // send mail to get token
        $this->__createLog('Forget Password is requested by userid: ' .$user_id);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [forgetSellerPassword] with response: ' . (string)$resp_data);
        Utility::createResponse($resp_data, 1);
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.seller_profile_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
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
     * reset password
     * @param \Symfony\Component\HttpFoundation\Request $request
     * return json
     */
    public function resetSellerPasswordAction(Request $request) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction]');
        $required_parameter = array('token', 'password');
        $data = array();
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service[UserManager\Sonata\UserBundle\Services\SellerUserService]
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) { //if any parameter missed
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request); //return associate array
        $token        = $de_serialize['token'];
        $password     = Utility::decodePassword($de_serialize['password']); //decode password

        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);
        //check for token
        if (!$user) {
            $resp_data = new Resp(Msg::getMessage(1037)->getCode(), Msg::getMessage(1037)->getMessage(), $data);//INVALID_TOKEN
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        
        $user_check_enable = $user->isEnabled();
        if (!$user_check_enable) { //check user is enabled
            $resp_data = new Resp(Msg::getMessage(1079)->getCode(), Msg::getMessage(1079)->getMessage(), $data); //SELLER_ACCOUNT_IS_NOT_ACTIVE
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        //check user is seller user
        //check reset password for only seller and shop type
        $shop_profile   = $user->getStoreProfile();
        $seller_profile = $user->getSellerProfile();
        if ($shop_profile == 0 && $seller_profile == 0) { //only shop profile and seller user
            $res_data = array('code' => Msg::getMessage(1084)->getCode(), 'message' => Msg::getMessage(1084)->getMessage(), 'data' => $data); //ONLY_SELLER_SHOP_CAN_RESET_PASSWORD
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with response: ' . Utility::encodeData($res_data));
            $seller_service->returnResponseWithHeader($res_data, 403);
        }
        
        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            $resp_data = new Resp(Msg::getMessage(1085)->getCode(), Msg::getMessage(1085)->getMessage(), $data); //TOKEN_EXPIRED
            $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with expired token: '.$token. ' and response: '.(string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }

        $userManager = $this->container->get('fos_user.user_manager');
        //get user manager instance
        try {
            $user->setPlainPassword($password);
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);
            $userManager->updateUser($user);
            $user_id = $user->getId();
            $this->__createLog('Password is changed for userid: '.$user_id);
        } catch (\Exception $ex) {
            $this->__createLog('There is some error for changing the password with token: '.$token, 'Error is: '.$ex->getMessage());
        }

        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->__createLog('Exiting from [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [resetSellerPasswordAction] with response: '. (string)$resp_data);
        Utility::createResponse($resp_data, 1);
    }

    /**
     * Get seller profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetsellerprofilesAction(Request $request) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles]');
        $required_parameter = array('user_id', 'seller_id');
        $data = array();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) { //if any parameter missed
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request); //return associate array
        $user_id = $de_serialize['seller_id'];
        $result = $seller_service->getSellerBasicProfile($user_id);
        if (!$result) {
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), $data); //USER_DOES_NOT_EXIST
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles] with response: ' .(string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result);
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles] with response: ' .  (string)$resp_data);
        Utility::createResponse($resp_data);
    }
    
     /**
     * Change seller password
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postChangesellerpasswordsAction(Request $request) 
    {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Changesellerpasswords]');
        $data = array();
        $required_parameter = array('user_id', 'password1', 'password2', 'old_password');
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) { //if any parameter missed
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Changesellerpasswords] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request); //return associate array
        $user_id = $de_serialize['user_id'];
        $old_password = $de_serialize['old_password'];
        $password1 = $de_serialize['password1'];
        $password2 = $de_serialize['password2'];
        //decrypt password
        $password1 = Utility::decodePassword($password1);
        $password2 = Utility::decodePassword($password2);
        $old_password = Utility::decodePassword($old_password);
        if(Utility::getTrimmedString($password1) == "") {
            $resp_data = new Resp(Msg::getMessage(139)->getCode(), Msg::getMessage(139)->getMessage(), $data); //PASSWORD_CAN_NOT_BE_BLANK
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Changesellerpasswords] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        if(!Utility::matchString($password1, $password2)){
            $resp_data = new Resp(Msg::getMessage(138)->getCode(), Msg::getMessage(138)->getMessage(), $data); //PASSWORD_NOT_MATCHED
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Changesellerpasswords] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $user_object = $seller_service->getUserProfile($user_id);
        //get password
        $user_object_old_password = $user_object->getPassword();
        if($user_object_old_password != Utility::convertMd5($old_password)){
            $resp_data = new Resp(Msg::getMessage(174)->getCode(), Msg::getMessage(174)->getMessage(), $data); //OLD_PASSWORD_NOT_MATCHED
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Changesellerpasswords] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $response = $seller_service->changePassword($user_id, $password1);
        if(!$response){
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles] with response: ' .  (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data);
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerProfileController] and function [Getsellerprofiles] with response: ' .  (string)$resp_data);
        Utility::createResponse($resp_data);
    }
}
