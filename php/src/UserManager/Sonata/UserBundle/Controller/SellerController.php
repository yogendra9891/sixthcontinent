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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Controller\RegistrationController as BaseController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Locale\Locale;
use UserManager\Sonata\UserBundle\Entity\EmailVerificationToken;
use UserManager\Sonata\UserBundle\Entity\ArchiveUser;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use UserManager\Sonata\UserBundle\Utils\MessageFactory as Msg;

/**
 * Controller managing the seller registration
 */
class SellerController extends BaseController {

    protected $miss_param = '';

    CONST UNDEFINED = "UNDEFINED";
    CONST VERIFIED = "VERIFIED";
    /**
     * Register seller
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function postRegistersellersAction(Request $request) {
        $this->__createLog('[Entering in SellerController->Registersellers(Request)]');
        $data = array();
        $required_parameter = array('email', 'firstname', 'lastname', 'phone', 'user_id', 'shop_id');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Registersellers] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }

        //register user
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $user_name = $de_serialize['email'];
        $email = $de_serialize['email'];
        $password = (isset($de_serialize['password'])) ? $de_serialize['password'] : '';     
        $firstname = $de_serialize['firstname'];
        $lastname = $de_serialize['lastname'];
        $phone = $de_serialize['phone'];
        $owner_id = $de_serialize['user_id'];
        $shop_id = $de_serialize['shop_id'];
        $seller_id = (isset($de_serialize['seller_id'])) ? $de_serialize['seller_id'] : 0;

        //check if user_id is the owner of the shop
        $store_update = $this->container->get('store_manager_store.storeUpdate');
        $store_owner_obj = $store_update->checkStoreOwner($shop_id, $owner_id);
        if (!$store_owner_obj) {
            $resp_data = new Resp(Msg::getMessage(1077)->getCode(), Msg::getMessage(1077)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from [SellerController->Registersellers] with response:Inavlid store owner id.');
            Utility::createResponse($resp_data);
        }
        $owner_object = $this->getUserProfile($owner_id);
        $owner_language = $owner_object->getCurrentLanguage();
        $owner_country = $owner_object->getCountry();
        //get shop owner object
        $user_service = $this->container->get('user_object.service');
        $store_detail = $user_service->getStoreObjectService($shop_id);
        //if seller id is not 0, Only map the seller with shop
        if ($seller_id != 0) {
            if(strlen(trim($password)) != 0){
            $de_serialize['password'] = Utility::decodePassword($password);
            }
            $seller_resp = $seller_service->checkSellerExists($shop_id, $seller_id);
            if ($seller_resp) {
                //seller exist
                $resp_data = new Resp(Msg::getMessage(1078)->getCode(), Msg::getMessage(1078)->getMessage(), $data); //SELLER_ALREADY_MAPPED_WITH_THIS_SHOP
                $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            $user_object = $this->getUserProfile($seller_id);
            //map seller with shop
            $resp_seller = $seller_service->mapSellerUser($shop_id, $seller_id, $owner_id); //for mapping seller user
            if (!$resp_seller) {
                $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
                $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
                Utility::createResponse($resp_data);
            }
            $resp_seller = $seller_service->updateSellerProfile($de_serialize); //for mapping seller user
            //send mail
            $seller_service->sendMapUserMail($user_object, $store_detail);
            //$resp_success = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
            $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //end to check map seller
        //new seeler user. Check for password
        if(strlen(trim($password)) == 0){
            $resp_data = new Resp(Msg::getMessage(1001)->getCode(), Msg::getMessage(1001)->getMessage() . strtoupper('password'), $data);
            $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $password = $this->decodePassword($password); //decode password
        $seller_resp = $seller_service->createSeller($user_name, $email, $password, $firstname, $lastname, $phone, $owner_language, $owner_country); //register seller
        if ($seller_resp['code'] !== 101) {
            $resp_data = new Resp($seller_resp['code'], $seller_resp['message'], $seller_resp['data']);
            $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //case success
        $register_id = $seller_resp['data']['register_id']; //get user regsiter id
        $verification_accesstoken = $seller_resp['data']['verification_token']; //get verification access token
        $resp_seller = $seller_service->mapSellerUser($shop_id, $register_id, $owner_id); //for mapping seller user
        if (!$resp_seller) {
           $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
           $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
           Utility::createResponse($resp_data);
        }
        $seller_service->addEmailVerificationToken($register_id, $verification_accesstoken); //update for verfication token
        //send mail
        $user_object = $this->getUserProfile($register_id);
        $seller_service->sendRegisterMail($user_object, $store_detail, $verification_accesstoken, $password);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->__createLog('Exiting from [SellerController->Registersellers] with response' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }

    /**
     *  function for getting the list of search sellers based on the email address
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Array json object of all the search sellers users
     */
    public function postSearchsellerusersAction(Request $request) {
        $this->__createLog('[Entering in SellerController->postSearchsellerusersAction(Request)]');
        $data = array();
        $results_count = 0;
        $required_parameter = array('user_id');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for vat number and fiscal code check
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('[Exiting the service with'.(string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $user_id = $de_serialize['user_id'];
        $search_text = isset($de_serialize['search_text']) ? $de_serialize['search_text'] : '';
        $limit_start = isset($de_serialize['limit_start']) ? (int) $de_serialize['limit_start'] : 20;
        $limit_size = isset($de_serialize['limit_size']) ? (int) $de_serialize['limit_size'] : 20;
        $shop_id = isset($de_serialize['shop_id']) ? (int) $de_serialize['shop_id'] : false;
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        
        if ($shop_id && $de_serialize['user_id']) {
            //check if the user to store realtionship exist
            $store_utility_service = $this->container->get('user.shop.seller');
            $user_to_store = $store_utility_service->checkUserToStoreRelation($user_id, $shop_id);
            if (!$user_to_store) {
                $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), $data);
                $this->__createLog('[Exiting the service with 1054 => ACCESS_VOILATION with data : '.  Utility::encodeData($de_serialize).']');
                Utility::createResponse($resp_data);
            }

            $store_obj = $store_utility_service->checkForActiveStore($shop_id);
            if (!$store_obj) {
                $resp_data = new Resp(Msg::getMessage(413)->getCode(), Msg::getMessage(413)->getMessage(), $data);
                $this->__createLog('[Exiting the service with 413 => INVALID_STORE with data : '.  Utility::encodeData($de_serialize).']');
                Utility::createResponse($resp_data);
            }
        }

        //fire the query in seller user Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:SellerUser')
                ->searchSellerByEmailId($user_id, $shop_id, $search_text, $limit_start, $limit_size);
        $results_count = $em
                ->getRepository('UserManagerSonataUserBundle:SellerUser')
                ->searchSellerByEmailIdCount($user_id, $shop_id, $search_text);

        $data = array('sellers' => $results, 'count' => $results_count);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data);
        $this->__createLog('Exiting from [SellerController->postSearchsellerusersAction] with response'.(string)$resp_data);
        Utility::createResponse($resp_data,1);
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
     *  function for getting the list of all the stores for a seller
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Array json object of all the search sellers users
     */
    public function postListsellersstoresAction(Request $request) {
        $this->__createLog('[Entering in SellerController->postListsellersstoresAction(Request)]');
        $data = array();
        $results_count = 0;
        $required_parameter = array('user_id');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for vat number and fiscal code check
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('[Exiting the service with'.(string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $user_id = $de_serialize['user_id'];
        $limit_start = isset($de_serialize['limit_start']) ? (int) $de_serialize['limit_start'] : 0;
        $limit_size = isset($de_serialize['limit_size']) ? (int) $de_serialize['limit_size'] : 20;
        $language_code = isset($de_serialize['language_code']) ? $de_serialize['language_code'] : $this->container->getParameter('locale');
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();

        //fire the query in seller user Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:SellerUser')
                ->searchSellerStores($user_id, $language_code, $limit_start, $limit_size);
        $results_count = $em
                ->getRepository('UserManagerSonataUserBundle:SellerUser')
                ->searchSellerStoresCount($user_id, $language_code);

        $data = array('stores' => $results, 'count' => $results_count);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data);
        $this->__createLog('Exiting from [SellerController->postListsellersstoresAction] with response'.(string)$resp_data);
        Utility::createResponse($resp_data,1);
    }

    /**
     * Function for encoding the given data
     * @param type $data
     * @param  $is_numeric_check 
     */
    public function encodeData($data, $is_numeric_check = 0) {
        if ($is_numeric_check == 1) {
            $data = json_encode($data, JSON_NUMERIC_CHECK);
        } else {
            $data = json_encode($data);
        }
        echo $data;
        exit;
    }

    /**
     * check seller action
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function checkSellerAction(Request $request) {
        $seller_service = $this->container->get('user.shop.seller');
        //$e = $seller_service->checkSellerExists(123, 1);
        $e = $seller_service->mapSellerUser(12, 13, 1);
        echo $e;
        exit;
    }

    /**
     * delete the seller 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postDeletesellersAction(Request $request) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [postDeletesellersAction]');
        $required_parameter = array('user_id', 'seller_id');
        $data = array();
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) { //if any parameter missed
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [postDeletesellersAction] with response: ' . (string)$resp_data);
            echo $this->encodeData($response);
            exit;
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request); //return associate array
        //extract variables
        $shop_id = (isset($de_serialize['shop_id']) ? $de_serialize['shop_id'] : '');
        $user_id = $de_serialize['user_id'];
        $seller_id = $de_serialize['seller_id'];
        $user = $seller_service->getUserProfile($user_id);
        if ($user === null) { //if user does not exists.
            $resp_data = new Resp(Msg::getMessage(1021)->getCode(), Msg::getMessage(1021)->getMessage(), $data);//USER_DOES_NOT_EXIST
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        $sellerid_user = $seller_service->getUserProfile($seller_id);
        if ($sellerid_user === null) { //if seller user does not exists.
            $resp_data = new Resp(Msg::getMessage(1093)->getCode(), Msg::getMessage(1093)->getMessage(), $data);//SELLER_USER_DOES_NOT_EXISTS
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        
        if ($sellerid_user->getSellerProfile() == 0) { //if user is not seller profile
            $resp_data = new Resp(Msg::getMessage(1092)->getCode(), Msg::getMessage(1092)->getMessage(), $data);//USER_IS_NOT_SELLER_PROFILE
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);            
        }
        if ($shop_id > 0 && $shop_id != '') { //if shopid is sent in request
            $this->removeSellerWithShop($user_id, $shop_id, $seller_id);
        } else { //if shop id is not sent
            $this->removeSeller($seller_id, $user_id); //remove seller from all shops of a user
        }
        //check seller is exists for other shops
        $seller_user = $seller_service->checkSeller($seller_id);
        if (!$seller_user) { //if a seller is not exists for other shops
           // $user_response = $this->removeFosUser($seller_id); //remove the main user from [fos_user_user] table
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->__createLog('Exiting from  class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [postDeletesellersAction] with data: ' . (string)$resp_data);
        Utility::createResponse($resp_data, 1);
    }

    /**
     * remove a seller from all shop of a user
     * @param int $seller_id
     * @param int $user_id
     * @return boolean
     */
    public function removeSeller($seller_id, $user_id) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSeller]');
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        //if shop id does not sent and user wants to remove seller from his all shops
        $shop_ids = $em->getRepository('StoreManagerStoreBundle:Store')
                       ->getUserShops($user_id);
        if (count($shop_ids)) {
            $seller_service->removeShopSeller($shop_ids, $seller_id);
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSeller]');
        return true;
    }

    /**
     * remove the main user from fos_user_user table
     * @param int $seller_id
     */
    public function removeFosUser($seller_id) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeFosUser]');
        $user_data = array();
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $seller_id));
        if ($user) {
            $user_data['user_id'] = $seller_id;
            $user_data['user_name'] = $user->getUserName();
            $user_data['email'] = $user->getEmail();
            $user_data['first_name'] = $user->getFirstname();
            $user_data['last_name'] = $user->getLastName();
            $user_data['created_at'] = $user->getCreatedAt();
            $user_data['dob'] = $user->getDateOfBirth();
            $user_data['gender'] = $user->getGender();
            $user_data['country'] = $user->getCountry();
            $user_data['language'] = $user->getCurrentLanguage();
            $user_data['phone'] = $user->getPhone();
            try {
                $em->remove($user);
                $em->flush();
                $this->saveArchiveUser($user_data);
                $this->__createLog('Fos user is removed from table [fos_user_user] with id: ' . $seller_id);
            } catch (\Exception $ex) {
                
            }
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeFosUser]');
        return true;
    }

    /**
     * remove the seller when user passed shop id
     * @param int $user_id
     * @param int $shop_id
     * @param int $seller_id
     */
    public function removeSellerWithShop($user_id, $shop_id, $seller_id) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop]', array());
        $response = array();
        $resp_data = array();
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        //check the shop owner
        $user_to_store = $seller_service->checkUserToStoreRelation($user_id, $shop_id);
        if (!$user_to_store) {
            $data = array('shop_id' => $shop_id, 'seller_id' => $seller_id);
            $resp_data = new Resp(Msg::getMessage(1054)->getCode(), Msg::getMessage(1054)->getMessage(), $data);//ACCESS_VOILATION
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        //check for shop activate state
        $store_obj = $seller_service->checkForActiveStore($shop_id);
        if (!$store_obj) {
            $data = array('shop_id' => $shop_id);
            $resp_data = new Resp(Msg::getMessage(413)->getCode(), Msg::getMessage(413)->getMessage(), $data);//INVALID_STORE
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        //remove the seller for current shop.
        $seller_mapping = $seller_service->removeSeller($shop_id, $seller_id);
        if (!$seller_mapping) {
            $data = array('shop_id' => $shop_id, 'seller_id' => $seller_id);
            $resp_data = new Resp(Msg::getMessage(1076)->getCode(), Msg::getMessage(1076)->getMessage(), $data);//INVALID_SELLER_FOR_SHOP
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data, 1);
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [removeSellerWithShop]', array());
        return true;
    }
    
    /**
     * Seller Login
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postSellerloginsAction(Request $request)
    {
        $this->__createLog('Entering in [SellerController->Sellerlogins]', array());
        $data = array();
        $required_parameter = array('username', 'password');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Sellerlogins] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $username = $de_serialize['username'];
        $password = $de_serialize['password'];
        $password = $this->decodePassword($password); //password decryption
        $um = $this->getUserManager();
        //get user detail
        $user = $um->findUserByUsername($username);
        if (!$user) {
            $resp_data = new Resp(Msg::getMessage(1080)->getCode(), Msg::getMessage(1080)->getMessage(), $data); //SELLER_USERNAME_OR_PASSWORD_IS_WRONG
            $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        //check login for only seller and shop type
        $shop_profile = $user->getStoreProfile();
        $seller_profile = $user->getSellerProfile();
        if($shop_profile == 0 && $seller_profile == 0){
            $res_data = array('code' => Msg::getMessage(1081)->getCode(), 'message' => Msg::getMessage(1081)->getMessage(), 'data' => $data); //ONLY_SELLER_SHOP_CAN_LOGIN
            $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . Utility::encodeData($res_data));
            $seller_service->returnResponseWithHeader($res_data, 403);
        }
        $verification_status = $user->getVerificationStatus(); //get verication status
        if(strtolower($verification_status) != strtolower(self::VERIFIED)){
        $trial_period = $seller_service->checkTrialPeriod($user);
         if(!$trial_period){
            $res_data = array('code' => Msg::getMessage(1045)->getCode(), 'message' => Msg::getMessage(1045)->getMessage(), 'data' => $data); //TRIAL_EXPIRED
            $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . Utility::encodeData($res_data));
            $seller_service->returnResponseWithHeader($res_data, 403);
        }
        }
        //check if user is active or not
        $user_check_enable = $seller_service->checkActiveUserProfile($username);
        if ($user_check_enable == false) {
            $resp_data = new Resp(Msg::getMessage(1079)->getCode(), Msg::getMessage(1079)->getMessage(), $data); //SELLER_ACCOUNT_IS_NOT_ACTIVE
            $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);     
        }
        //check for password
        if (!$seller_service->checkUserPassword($user, $password)) {
            $resp_data = new Resp(Msg::getMessage(1080)->getCode(), Msg::getMessage(1080)->getMessage(), $data); //SELLER_USERNAME_OR_PASSWORD_IS_WRONG
            $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);     
        }
        $user_id = $user->getId();  
        $request = Request::createFromGlobals();
        $access_token=$request->query->get('access_token');
        //Map access token with login user
        $seller_service->userToAccessTokenMapping($access_token, $user_id);
        //get login user profile
        $profile = $seller_service->getLoginUserProfile($user_id);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $profile); //SUCCESS
        $this->__createLog('Exiting from [SellerController->Sellerlogins] with response' . (string)$resp_data);
        Utility::createResponse($resp_data);
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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
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
     * save acrhive user on deletion from fos_user table
     * @param array $user_data
     * @return boolean
     */
    public function saveArchiveUser($user_data) {
        $this->__createLog('Entering in class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [saveArchiveUser]', array());
        $time = new \DateTime('now');
        //get doctrine manager object
        $em = $this->container->get('doctrine')->getManager();
        $ac_user = new ArchiveUser();
        try {
            $ac_user->setUserId($user_data['user_id']);
            $ac_user->setUsername($user_data['user_name']);
            $ac_user->setEmail($user_data['email']);
            $ac_user->setRegisteredAt($user_data['created_at']);
            $ac_user->setFirstName($user_data['first_name']);
            $ac_user->setLastName($user_data['last_name']);
            $ac_user->setCountry($user_data['country']);
            $ac_user->setCurrentLanguage($user_data['language']);
            $ac_user->setGender($user_data['gender']);
            $ac_user->setPhone($user_data['phone']);
            $ac_user->setDateOfBirth($user_data['dob']);
            $ac_user->setCreatedAt($time);
            $em->persist($ac_user);
            $em->flush();
        } catch (\Exception $ex) {
            $this->__createLog('There is some error for saving the data for username: '.$user_data['user_name'], 'Error is: '.$ex->getMessage());
        }
        $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [saveArchiveUser] with username: '.$user_data['user_name'], array());
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
     * Seller logout
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postSellerlogoutsAction(Request $request)
    {
        $this->__createLog('Entering in [SellerController->Sellerlogout]', array());
        $data = array();
        $required_parameter = array('user_id', 'access_token');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $seller_service = $this->container->get('user.shop.seller'); //call seller service
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Sellerlogouts] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $access_token = $de_serialize['access_token'];
        $remove_user_token = $seller_service->removeUserToAccessTokenMap($access_token);
        if(!$remove_user_token){
            $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
            $this->__createLog('Exiting from [SellerController->Sellerlogouts] with response' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $remove_token = $seller_service->removeAccessToken($access_token);
        if(!$remove_token){
           $resp_data = new Resp(Msg::getMessage(1035)->getCode(), Msg::getMessage(1035)->getMessage(), $data); //ERROR_OCCURED
           $this->__createLog('Exiting from [SellerController->Sellerlogouts] with response' . (string)$resp_data);
           Utility::createResponse($resp_data);
        }
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data); //SUCCESS
        $this->__createLog('Exiting from [SellerController->Sellerlogouts] with response' . (string)$resp_data);
        Utility::createResponse($resp_data);
    }
}
