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
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\BrokerUser;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use UserManager\Sonata\UserBundle\Entity\UserDeletedAssign;
use UserManager\Sonata\UserBundle\Entity\UserPosition;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Locale\Locale;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationBroker;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationCitizen;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationShop;
use StoreManager\StoreBundle\Entity\Storeoffers;
use Notification\NotificationBundle\Document\UserNotifications;
use StoreManager\StoreBundle\Entity\StoreJoinNotification;
use WalletManagement\WalletBundle\Entity\UserDiscountPosition;
use UserManager\Sonata\UserBundle\Entity\EmailVerificationToken;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

/**
 * Controller managing the registration
 */
class RestRegistrationV1Controller extends BaseController {

    protected $miss_param = '';
    const VAT_MODE = 'live';
    CONST UNDEFINED = "UNDEFINED";
    
    /**
     * Register user
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function registerAction(Request $request = null) { 
        $this->_log('[Entering in RestRegistrationV1Controller->registerAction(Request)]');
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $required_parameter = array('email', 'password', 'firstname', 'lastname', 'birthday', 'country');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, (object) $de_serialize);
        if ($chk_error) {

            $data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            $this->_log('Exiting with message YOU_HAVE_MISSED_A_PARAMETER_]');
            echo $this->encodeData($data);
            exit;
        }


        //end to get request object
        //get username
        $user_name = $de_serialize['email'];

        //get email
        $email = $de_serialize['email'];

        //get password
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

        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        //check if ccode is valid
        $countryLists = Locale::getCountries();
        if (!in_array($country, $countryLists)) {
            $data = array('code' => 1012, 'message' => 'INVALID_COUNTRY_CODE', 'data' => array());
            $this->_log('Exiting with message INVALID_COUNTRY_CODE]');
            echo $this->encodeData($data);
            exit;
        }

        //check for male and female
        $allow_gender = array('m', 'f','');
        if (!in_array($gender, $allow_gender)) {
            $data = array('code' => 1013, 'message' => 'INVALID_GENDER_TYPE', 'data' => array());
            $this->_log('Exiting with message INVALID_GENDER_TYPE]');
            echo $this->encodeData($data);
            exit;
        }

        //check for profile setting 
        $allow_setting = array('1', '2', '3');
        if (!in_array($type, $allow_setting)) {
            $data = array('code' => 1014, 'message' => 'INVALID_PROFILE_SETTING', 'data' => array());
            $this->_log('Exiting with message INVALID_PROFILE_SETTING]');
            echo $this->encodeData($data);
            exit;
        }

        //check for a refferal user id 
//        if ((in_array($type, array('3'))) && ($referral_id == '')) { //if user is registering as a shop, broker..
//            $data = array('code' => 143, 'message' => 'REFERRAL_ID_NEEDED', 'data' => array());
//            echo $this->encodeData($data);
//            exit;
//        }

        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();

        //check if referral id is for broker
        if ($type == 3) {
            if ($referral_id != "") {
                // checking V12 is broker Id or not
                $citizenuser = $em
                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->checkActiveCitizen($referral_id);
                if (!($citizenuser)) {
                    $res_data = array('code' => 1015, 'message' => 'CITIZEN_DOES_NOT_EXIST', 'data' => array());
                    $this->_log('Exiting with message CITIZEN_DOES_NOT_EXIT for TYPE 3 USER[Referral_id='. $this->_toJSON($referral_id).' not found]]');
                    echo json_encode($res_data);
                    exit;
                }
            }
        }

        //check if referral id is for broker
        if ($type == 2) {
            if ($referral_id != "") {
                // checking V12 is broker Id or not
                $brokeruser = $em
                        ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                        ->findOneBy(array('userId' => $referral_id, 'isActive' => 1));
                if (!($brokeruser)) {
                    $res_data = array('code' => 1016, 'message' => 'BROKER_DOES_NOT_EXIST', 'data' => array());
                    $this->_log('Exiting with message BROKER_DOES_NOT_EXIT for TYPE 2 USER[Referral_id='. $this->_toJSON($referral_id).' not found]]');
                    echo json_encode($res_data);
                    exit;
                }
            }
        }


        //check if referral id is for citizen
        if ($type == 1) {
            if ($referral_id != "") {
                // checking V12 is citizen Id or not
                $citizenuser = $em
                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->checkActiveCitizen($referral_id);
                if (!($citizenuser)) {
                    $res_data = array('code' => 1015, 'message' => 'CITIZEN_DOES_NOT_EXIST', 'data' => array());
                    $this->_log('Exiting with message CITIZEN_DOES_NOT_EXIT for  TYPE 1 USER[Referral_id='. $this->_toJSON($referral_id).' not found]]');
                    echo json_encode($res_data);
                    exit;
                }
            }
        }

      
        //get form object
        $form = $this->container->get('fos_user.registration.form');
        $formHandler = $this->container->get('fos_user.registration.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');
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
            $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            $this->_log('Exiting with message INVALID_DATE_FORMAT]');
            echo $this->encodeData($data);
            exit;
        }

        $user->setDateOfBirth($btime);

        $user->setGender($gender);

        $user->setCountry($country);

        $user->setProfileType($type);
        $user->setProfileImg('');
        $user->setCitizenProfile(0);
        $user->setBrokerProfile(0);
        $user->setStoreProfile(0);
        
        $user->setCurrentLanguage($current_language);
        /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $verification_accesstoken = $tokenGenerator->generateToken();
        
        $user->setVerificationToken($verification_accesstoken);
        $user->setVerificationStatus('UNVERIFIED');
//        if ($referral_id != "") {
//            $user->setAffiliationStatus(1);
//        }
        
        //set verification link creation time
        $time = new \DateTime('now');
        $user->setVerifylinkCreatedAt($time);
        
        //get email constraint object
        $emailConstraint = new EmailConstraint(); 
        //  $emailConstraint->message = 'Your customized error message';

        $errors = $this->container->get('validator')->validateValue(
                $email, $emailConstraint
        );

        // check email validation
        if (count($errors) > 0) {
            $res_data = array('code' => 1018, 'message' => 'EMAIL_IS_INVALID', 'data' => array());
            $this->_log('Exiting with message EMAIL_IS_INVALID]');
            echo json_encode($res_data);
            exit;
        }


        //handling exception
        try {
            $check_success = $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            $res_data = array('code' => 1019, 'message' => 'USER_EXIST', 'data' => array());
            $this->_log('Exiting with message USER_EXIST]');
            echo json_encode($res_data);
            exit;
        }

        $process = $formHandler->process($confirmationEnabled);
        $register_id = $user->getId();
        //update for verfication token
        $this->addEmailVerificationToken($register_id, $verification_accesstoken);
        //send email
        $this->sendAction($user, $verification_accesstoken);
        $register_id = $user->getId();
        
        $resp_data = array('user_id'=>$register_id, 'profile_type'=>$type);

        //for citizen profile
        if ($type == 1) {

            //insert the citizen data in citizen table
            $set_default_citizen = $this->setDefaultCitizenProfile($register_id);

            if ($set_default_citizen == 5) {
                $res_data = array('code' => 1020, 'message' => 'CITIZEN_REGISTERD_ALREADY', 'data' => array());
                $this->_log('Exiting with message CITIZEN_REGISTERD_ALREADY[USER_ID='. $this->_toJSON($register_id) . ']');
                echo json_encode($res_data);
                exit;
            }

            //insert the broker data in broker table
           /*
                $set_default_broker = $this->setDefaultBrokerProfile($register_id);

                if ($set_default_broker == 5) {
                    $res_data = array('code' => 92, 'message' => 'BROKER_REGISTERD_ALREADY', 'data' => array());
                    echo json_encode($res_data);
                    exit;
                }
           */
            //set the citizen profile as complete
            //get user object
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $register_id));
            if (!$user) {
                $this->_log('Exiting with message USER_DOES_NOT_EXIT[USER_ID='. $this->_toJSON($register_id) . ']');
                return array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
            }
            //below line should be uncommented.
            $user->setCitizenProfile(1);
            $this->container->get('fos_user.user_manager')->updateUser($user);

//            if ($referral_id != "") {
//
//                $em = $this->container->get('doctrine')->getManager();
//                //get citizen user object
//                $citizen_affiliation = new AffiliationCitizen();
//                $time = new \DateTime('now');
//                $citizen_affiliation->setFromId($referral_id);
//                $citizen_affiliation->setToId($register_id);
//                $citizen_affiliation->setCreatedAt($time);
//                $em->persist($citizen_affiliation);
//                $em->flush();
//            }
            //after user registration we have to also update the profile on shoppingplus server.
            //call shopping plus web service(CLIENTADD) from service. Register citizen to shopping plus server
//            $step = 'Citizen Registeration';
//            $cell = '';
//            $manager = 'N';
//            $password = $user->getPassword();
//            $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
            // BLOCK SHOPPING PLUS
            //$shoppingplus_obj->registerCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);
         
            $resps_data = array('user_id'=>$register_id, 'profile_type'=>$type);
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $resps_data);
            
            $appalne_data = $de_serialize;
            $appalne_data['register_id'] = $register_id;
            //get dispatcher object
            $this->_log('begin citizen register dispacher : '.$register_id ." : ".$user_name);
            $event = new FilterDataEvent($appalne_data);
            $this->_log('success fully created event object: '.$register_id." : ".$user_name);
            $dispatcher = $this->container->get('event_dispatcher');
            $this->_log('successfully get dispacher object: '.$register_id." : ".$user_name);
            $dispatcher->dispatch('citizen.register', $event);
            $this->_log('event is dispatched: '.$register_id." : ".$user_name);
            if ($referral_id != "") {
                $store_service = $this->container->get('store_manager_store.storeUpdate');
                $response = $store_service->updateUserAffiliation($register_id, $referral_id, $register_id, 1);
            }
            $affiliation_service = $this->container->get('affiliation_affiliation_manager.user');
            $affiliation_service->updateAffiliationStatusForEmailId($referral_id,$email,1);
            echo json_encode($res_data);
            exit;
        }

        //for broker profile
        if ($type == 2) {

            //set the citizen profile as complete
            //get user object
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $register_id));
            if (!$user) {
                return array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
            }
            //below line should be uncommented.
            $user->setCitizenProfile(1);
            $this->container->get('fos_user.user_manager')->updateUser($user);

            //insert the citizen data in citizen table
            $set_default_citizen = $this->setDefaultCitizenProfile($register_id);

            if ($set_default_citizen == 5) {
                $res_data = array('code' => 1020, 'message' => 'CITIZEN_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }

            //insert the broker data in broker table
            $set_default_broker = $this->setDefaultBrokerProfile($register_id);

            if ($set_default_broker == 5) {
                $res_data = array('code' => 1022, 'message' => 'BROKER_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            if ($referral_id != "") {
                //get citizen user object
                $broker_affiliation = new AffiliationBroker();
                $time = new \DateTime('now');
                $broker_affiliation->setFromId($referral_id);
                $broker_affiliation->setToId($register_id);
                $broker_affiliation->setCreatedAt($time);
                $em->persist($broker_affiliation);
                $em->flush();
            }
            
            //call shopping plus web service(CLIENTADD) from service. Register broker to shopping plus server
//            $step = 'Broker Registeration';
//            $cell = '';
//            $manager = 'N';
//            $password = $user->getPassword();
//            $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
            // BLOCK SHOPPING PLUS
            //$shoppingplus_obj->registerCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);

            $resps_data = array('user_id'=>$register_id, 'profile_type'=>$type);
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $resps_data);
            echo json_encode($res_data);
            exit;
        }

        //for store profile
        if ($type == 3) {

            //set the citizen profile as complete
            //get user object
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $register_id));
            if (!$user) {
                $res_data = array('code' => 1021, 'message' => 'USER_DOES_NOT_EXIST', 'data' => $data);
                echo json_encode($res_data);
                exit;
            }
            
            $user->setCitizenProfile(1);
            $this->container->get('fos_user.user_manager')->updateUser($user);

            //insert the citizen data in citizen table
            $set_default_citizen = $this->setDefaultCitizenProfile($register_id);

            if ($set_default_citizen == 5) {
                $res_data = array('code' => 1020, 'message' => 'CITIZEN_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }

            //insert the broker data in broker table
           // $set_default_broker = $this->setDefaultBrokerProfile($register_id);

//            if ($set_default_broker == 5) {
//                $res_data = array('code' => '134', 'message' => 'BROKER_REGISTERD_ALREADY', 'data' => array());
//                echo json_encode($res_data);
//                exit;
//            }
            //register a citizen on shopping plus when a user registered as a shop.
//            $step = 'Citizen Registeration';
//            $cell = '';
//            $manager = 'N';
//            $password = $user->getPassword();
//            $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
            // BLOCK SHOPPING PLUS
            //$shoppingplus_obj->registerCitizenShoppingPlus($register_id,$firstname,$lastname,$email,$cell,$password,$referral_id,$manager,$type,$step);
        }
        // calculate district,circle and position of user
        // $this->calculateUserDistrictCirclePosition($register_id);
        if ($type == '3') {
            $resps_data = array('user_id' => $register_id, 'profile_type' => $type, 'referral_id' => $referral_id);
        } else {
            $resps_data = array('user_id' => $register_id, 'profile_type' => $type);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $resps_data);
        echo json_encode($res_data);
        exit;
    }

    /**
     * Register for multi profiles
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function registermultiprofileAction(Request $request) {
        $response_data = array(); //initialise the array
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $call_type = $de_serialize['call_type'];

        //Make call type required.
        //check for call type
//        if ($de_serialize['call_type'] == "") {
//            $res_data = array('code' => '111', 'message' => 'CALL_TYPE_REQUIRED', 'data' => array());
//            echo json_encode($res_data);
//            exit;
//        }
        
        //check for user id
        if ($de_serialize['user_id'] == "") {
            $res_data = array('code' => 1023, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $type = $de_serialize['type'];
        $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        $allow_type = array(1, 2, 3);
        if (!in_array($type, $allow_type)) {
            $res_data = array('code' => 1024, 'message' => 'INVALID_PROFILE_TYPE', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $em = $this->container->get('doctrine')->getManager();
        //for citizen
        if ($type == 1) {
            $response = $this->setCitizenProfile($de_serialize);

            if ($response == 5) {
                $res_data = array('code' => 1020, 'message' => 'CITIZEN_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }

        //for broker
        if ($type == 2) {
            if ($referral_id != "") {
                // checking V12 is broker Id or not
                $brokeruser = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')
                        ->findOneBy(array('userId' => $referral_id, 'isActive' => 1));
                if (!($brokeruser)) {
                    $res_data = array('code' => 1016, 'message' => 'BROKER_DOES_NOT_EXIST', 'data' => array());
                    echo json_encode($res_data);
                    exit;
                }
              
            }

            $response = $this->setBrokerProfile($de_serialize);
            if ($response == 7) {
                $res_data = array('code' => 1016, 'message' => 'BROKER_DOES_NOT_EXIST', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            if ($response == 5) {
                $res_data = array('code' => 1022, 'message' => 'BROKER_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            if ($response == 6) {
                $res_data = array('code' => 1022, 'message' => 'BROKER_REGISTERD_ALREADY', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            if ($referral_id != "") {
                //make entry in affiliationbroker table.
                $broker_affiliation = new AffiliationBroker();
                $time = new \DateTime('now');
                $broker_affiliation->setFromId($referral_id);
                $broker_affiliation->setToId($de_serialize['user_id']);
                $broker_affiliation->setCreatedAt($time);
                $em->persist($broker_affiliation);
                $em->flush();
            }
            
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }

        //for store
        if ($type == 3) {
            
//            if ($referral_id == '') {
//                $data = array('code' => 143, 'message' => 'REFERRAL_ID_NEEDED', 'data' => array());
//                echo $this->encodeData($data);
//                exit;
//            }
            $store_id = $this->setStoreProfile($de_serialize);
            
            $user_email = "";
            $password = "";
            //get user object
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $de_serialize['user_id']));
            if ($user) {
                $user_email = $user->getEmail();
                $password = $user->getPassword();
            }
            
            
            //after store registration we have to also update the profile on shoppingplus server.
            //get curl object from service
           // $curl_obj = $this->container->get("store_manager_store.curl");

            $store_email =  $de_serialize['email'];
            

//            if ($referral_id != "") {
//
//                $em = $this->container->get('doctrine')->getManager();
//                //get citizen user object
//                $shop_affiliation = new AffiliationShop();
//                $time = new \DateTime('now');
//                $shop_affiliation->setFromId($referral_id);
//                $shop_affiliation->setToId($de_serialize['user_id']);
//                $shop_affiliation->setShopId($store_id);
//                $shop_affiliation->setCreatedAt($time);
//                $em->persist($shop_affiliation);
//                $em->flush();
//            }
             
          //create store on applane
          $appalne_data = $de_serialize;
          $appalne_data['store_id'] = $store_id;
          //get dispatcher object
          $event = new FilterDataEvent($appalne_data);
          $dispatcher = $this->container->get('event_dispatcher');
          $dispatcher->dispatch('shop.create', $event);
          //end of update
          /*
          $legal_status = $de_serialize['legal_status'];
          $business_name = $de_serialize['business_name'];
          $business_address = $de_serialize['business_address'];
          $zip = $de_serialize['zip'];
          $business_city = $de_serialize['business_city'];
          $provience = $de_serialize['province'];
          $phone = $de_serialize['phone'];
          $user_email = $user_email; // (fos_user_user email)
          $description = $de_serialize['description'];
          $vat_number = $de_serialize['vat_number']; //vat_number ( this should be unique)
          $user_password = $password; // (fos_user_user password)
          $referral_id = $referral_id;//fos_fos_user.id which is broker
          $virtual_status = "N";
          $importopdv_amount = 0;
          $step = 'Shop Registeration';
          $shop_status_shopping_plus = 'D';*/
          
//          $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
          // BLOCK SHOPPING PLUS
//          $shoppingplus_obj ->registerShopShopingplus($store_id,$business_name,$business_address,$zip,$business_city,
//                                                      $provience,$phone,$store_email,$description,$vat_number,$user_password,
//                                                      $referral_id,$virtual_status,$importopdv_amount,$type,$step,
//                                                      $shop_status_shopping_plus
//                                                    );
            if ($referral_id != "") {
//            $em = $this->container->get('doctrine')->getManager();
//            //get citizen user object
//            $broker_affiliation = new AffiliationShop();
//            $time = new \DateTime('now');
//            $broker_affiliation->setFromId($referral_id);
//            $broker_affiliation->setToId($store_owner_id);
//            $broker_affiliation->setShopId($store->getId());
//            $broker_affiliation->setCreatedAt($time);
//            $em->persist($broker_affiliation);
//            $em->flush();       
        $store_service = $this->container->get('store_manager_store.storeUpdate');
        //check if store is already affiliated
        $response = $store_service->updateStoreAffiliation($de_serialize['user_id'], $referral_id, $store_id, 1);
        }
            $response_data = array('store_id' => $store_id);
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $response_data);
            echo json_encode($res_data);
            exit;
           }
    }

    /**
     * set citizen profile
     * @param type $de_serialize
     * @return boolean
     */
    public function setCitizenProfile($de_serialize) {
        $user_id = $de_serialize['user_id'];
        $region = $de_serialize['region'];
        $city = $de_serialize['city'];
        $address = $de_serialize['address'];
        $zip = $de_serialize['zip'];
        $map_place = $de_serialize['map_place'];
        $latitude = $de_serialize['latitude'];
        $longitude = $de_serialize['longitude'];

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
        $citizenuser->setLatitude($latitude);
        $citizenuser->setLongitude($longitude);
        //get birth date date object
        $time = new \DateTime('now');
        $citizenuser->setCreatedAt($time);
        $citizenuser->setUpdatedAt($time);//set the updated time i.e. created_time(first time)
        $citizenuser->setProfileImg('');
        $citizenuser->setMapPlace($map_place);
        $em->persist($citizenuser);
        $em->flush();
        return 1;
    }

    /**
     * set citizen profile
     * @param type $de_serialize
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
        $citizenuser->setUpdatedAt($time);//set the updated date for a citizen profile.
        $em->persist($citizenuser);
        $em->flush();
        
        //Add citizen DP
        $this->insertCitizenDP($user_id);
        
        return 1;
    }

    /**
     * Insert citizen DP
     * @param type $user_id
     */
    public function insertCitizenDP($user_id)
    {
        $em = $this->container->get('doctrine')->getManager();
       //get entity object
        $user_discount_position = new UserDiscountPosition();
        $user_discount_position->setUserId($user_id);
        $user_discount_position->setTotalDp(0);
        $user_discount_position->setBalanceDp(0);
        $user_discount_position->setCitizenIncome(0);
        $time = new \DateTime('now');
        $user_discount_position->setCreatedAt($time);
        $em->persist($user_discount_position);
        $em->flush();
        return true;
    }
    /**
     * Set broker profile
     * @param type $de_serialize
     */
    public function setBrokerProfile($de_serialize) {
        $call_type = $de_serialize['call_type'];
        $user_id = $de_serialize['user_id'];
        $phone = $de_serialize['phone'];
        $vat_number = $de_serialize['vat_number'];
        $fiscal_code = $de_serialize['fiscal_code'];
        $iban = $de_serialize['iban'];
        $latitude = $de_serialize['latitude'];
        $longitude = $de_serialize['longitude'];
        $map_place = $de_serialize['map_place'];
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        
        if($call_type == 1){
            $brokeruser = $em
                    ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                    ->findOneBy(array('userId' => $user_id));
            if($brokeruser){
                $brokeruser->setUserId($user_id);
                $brokeruser->setRoleId(24);
                $brokeruser->setPhone($phone);
                $brokeruser->setVatNumber($vat_number);
                $brokeruser->setFiscalCode($fiscal_code);
                $brokeruser->setIban($iban);
                //get birth date date object
                $time = new \DateTime('now');
                $brokeruser->setCreatedAt($time);
                $brokeruser->setProfileImg('');
                $brokeruser->setLatitude($latitude);
                $brokeruser->setLongitude($longitude);
                //for ssn and idcard..
                $brokeruser->setSsn('');
                $brokeruser->setIdCard('');
                $brokeruser->setMapPlace($map_place);
                $em->persist($brokeruser);
                $em->flush();

                //set the broker profile as complete
                //get user object
                $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));

                // $user->setBrokerProfile(0); pass 1 beacuse in linked profile service we need to make active field.
                $user->setBrokerProfile(1);
                $this->container->get('fos_user.user_manager')->updateUser($user);
            }else{
                return 7;
            }            
            
        }else{
            //check user is already registerd
            $brokeruser_check = $em
                    ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                    ->findOneBy(array('userId' => $user_id));
            if ($brokeruser_check) {
                return 5; //already exist
            }

            //check broker profile is already complete
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));

            $broker_profile = $user->getBrokerProfile();
            if ($broker_profile == 1) {
                return 6;
            }
            //get broker object
            $brokeruser = new BrokerUser();
            $brokeruser->setUserId($user_id);
            $brokeruser->setRoleId(24);
            $brokeruser->setPhone($phone);
            $brokeruser->setVatNumber($vat_number);
            $brokeruser->setFiscalCode($fiscal_code);
            $brokeruser->setIban($iban);
            //get birth date date object
            $time = new \DateTime('now');
            $brokeruser->setCreatedAt($time);
            $brokeruser->setProfileImg('');
            $brokeruser->setLatitude($latitude);
            $brokeruser->setLongitude($longitude);
            //for ssn and idcard..
            $brokeruser->setSsn('');
            $brokeruser->setIdCard('');
            $brokeruser->setMapPlace($map_place);
            $em->persist($brokeruser);
            $em->flush();

            //set the broker profile as complete
            //get user object
            $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));

            // $user->setBrokerProfile(0); pass 1 beacuse in linked profile service we need to make active field.
            $user->setBrokerProfile(1);
            $this->container->get('fos_user.user_manager')->updateUser($user);
        }
        return 1;
    }

    /**
     * Set broker profile
     * @param type $user_id
     */
    public function setDefaultBrokerProfile($user_id) {
        $user_id = $user_id;
        $phone = '';
        $vat_number = '';
        $fiscal_code = '';
        $iban = '';
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //check user is already registerd
        $broker_user_result = $em
                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->findOneBy(array('userId' => $user_id));
        if ($broker_user_result) {
            return 5; //already exist
        }
        //get broker user object
        $brokeruser = new BrokerUser();
        $brokeruser->setUserId($user_id);
        $brokeruser->setRoleId(24);
        $brokeruser->setPhone($phone);
        $brokeruser->setVatNumber($vat_number);
        $brokeruser->setFiscalCode($fiscal_code);
        $brokeruser->setIban($iban);
        $brokeruser->setProfileImg('');
        $brokeruser->setLatitude('');
        $brokeruser->setLongitude('');
        $brokeruser->setMapPlace('');
        $brokeruser->setSsn('');
        $brokeruser->setIdCard('');
        //get birth date date object
        $time = new \DateTime('now');
        $brokeruser->setCreatedAt($time);

        $em->persist($brokeruser);
        $em->flush();
        return 1;
    }

    /**
     * Set store profile
     * @param type $de_serialize
     */
    public function setStoreProfile($de_serialize) {
        $data_validate = $this->container->get('export_management.validate_data'); //get validation service object
        //get data
        $user_id = $de_serialize['user_id'];
        $name = $de_serialize['name'];
        $email = $de_serialize['email'];
        $description = $de_serialize['description'];
        $phone = $de_serialize['phone'];
        $business_name = $de_serialize['business_name'];
        $legal_status = $de_serialize['legal_status'];
        $business_type = $de_serialize['business_type'];
        $business_country = $de_serialize['business_country'];
        $business_region = $de_serialize['business_region'];
        $business_city = $de_serialize['business_city'];
        $business_address = $de_serialize['business_address'];
        $zip = $de_serialize['zip'];
        $province = $de_serialize['province'];
        //getting the utility service
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $store_utility->checkVATFiscal($de_serialize); //check for vat number and fiscal code check
        $de_serialize['vat_number'] = (isset($de_serialize['vat_number'])) ? $de_serialize['vat_number'] : '';
        $de_serialize['fiscal_code'] = (isset($de_serialize['fiscal_code'])) ? $de_serialize['fiscal_code'] : '';
        //add vat number validation
        $vat_number = $store_utility->trimString($de_serialize['vat_number']);
        if ($vat_number != '') { //if vat number is not blank.
            $valid_vatnumber = $data_validate->checkVatNumber($vat_number); //call service
            $vat_mode = '';
            try {
               $vat_mode = strtolower($this->container->getParameter('vat_mode')); //get vat mode from parameter file               
            } catch (\Exception $ex) {

            }

            if((!$valid_vatnumber) && ($vat_mode == self::VAT_MODE)) {
                //if not valid vat number and vat mode is live
                $res_data = array('code' => 1025, 'message' => 'VAT_NUMBER_NOT_VALID', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }
        $iban = $de_serialize['iban'];
        $valid_iban = $data_validate->varfyIban($iban);//call service
        if((!$valid_iban) && ($vat_mode == self::VAT_MODE)){
            //if not valid iban number and vat mode is live
            $res_data = array('code' => 1026, 'message' => 'IBAN_NOT_VALID', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        $map_place = $de_serialize['map_place'];
        $latitude = $de_serialize['latitude'];
        $longitude = $de_serialize['longitude'];

        //new fields added on 15 Jan 2015
        $fiscal_code = $de_serialize['fiscal_code'];
        $sale_country = $de_serialize['sale_country'];
        $sale_region = $de_serialize['sale_region'];
        $sale_city = $de_serialize['sale_city'];
        $sale_province = $de_serialize['sale_province'];
        $sale_zip = $de_serialize['sale_zip'];
        $sale_email = $de_serialize['sale_email'];
        $sale_phone_number = $de_serialize['sale_phone_number'];
        $sale_catid = $de_serialize['sale_catid'];
        //make subcat id optional
        $sale_subcatid = isset($de_serialize['sale_subcatid']) ? $de_serialize['sale_subcatid'] : null;
        $sale_description = $de_serialize['sale_description'];
        $sale_address = $de_serialize['sale_address'];
        $sale_map = $de_serialize['sale_map'];
        $repres_fiscal_code = $de_serialize['repres_fiscal_code'];
        $repres_first_name = $de_serialize['repres_first_name'];
        $repres_last_name = $de_serialize['repres_last_name'];
        $repres_place_of_birth = $de_serialize['repres_place_of_birth'];
        $repres_dob = $de_serialize['repres_dob'];
        $repres_email = $de_serialize['repres_email'];
        $repres_phone_number = $de_serialize['repres_phone_number'];
        $repres_address = $de_serialize['repres_address'];
        $repres_province = $de_serialize['repres_province'];
        $repres_city = $de_serialize['repres_city'];
        $repres_zip = $de_serialize['repres_zip'];
        $shop_keyword = $de_serialize['shop_keyword'];

        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //check vat number should be unique
//        $store_vat = $em
//                ->getRepository('StoreManagerStoreBundle:Store')
//                ->findOneBy(array('vatNumber' => $vat_number, 'isActive' => 1));
//        $store_vate_number = count($store_vat);
//        if ($store_vate_number > 0) {
//            $res_data = array('code' => 1027, 'message' => 'VAT_NUMBER_ALREADY_EXIST', 'data' => array());
//            echo json_encode($res_data);
//            exit;
//        }
        
        try{
        $btime = new \DateTime($repres_dob);
        if(!$btime){
            $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
        }
       }catch(\Exception $e){
           $data = array('code' => 1017, 'message' => 'INVALID_DATE_FORMAT', 'data' => array());
            echo $this->encodeData($data);
            exit;
       }
       $referral_id = "";
        if (isset($de_serialize['referral_id'])) {
            $referral_id = $de_serialize['referral_id'];
        }
        
        $store = new Store();
        //$store->setUserId($user_id);
        $store->setEmail($email);
        $store->setName($name);
        $store->setDescription($description);
        $store->setPhone($phone);
        $store->setBusinessName($business_name);
        $store->setLegalStatus($legal_status);
        $store->setBusinessType($business_type);
        $store->setBusinessCountry($business_country);
        $store->setBusinessRegion($business_region);
        $store->setBusinessCity($business_city);
        $store->setBusinessAddress($business_address);
        $store->setZip($zip);
        $store->setProvince($province);
        $store->setVatNumber($vat_number);
        $store->setIban($iban);
        $store->setMapPlace($map_place);
        $store->setLatitude($latitude);
        $store->setLongitude($longitude);
        $store->setParentStoreId(0);
        $store->setIsActive(1);
        $store->setIsAllowed(1);
        $store->setStoreImage('');
        //get birth date date object
        $time = new \DateTime('now');
        $store->setCreatedAt($time);
        $store->setUpdatedAt($time);
        
        //added new field on 15 jan 2015
        $store->setFiscalCode($fiscal_code);
        $store->setSaleCountry($sale_country);
        $store->setSaleRegion($sale_region);
        $store->setSaleCity($sale_city);
        $store->setSaleProvince($sale_province);
        $store->setSaleZip($sale_zip);
        $store->setSaleAddress($sale_address);
        $store->setSaleEmail($sale_email);
        $store->setSalePhoneNumber($sale_phone_number);
        $store->setSaleCatid($sale_catid);
        $store->setSaleSubcatid($sale_subcatid);
        $store->setSaleDescription($sale_description);
        $store->setSaleMap($sale_map);
        $store->setRepresFiscalCode($repres_fiscal_code);
        $store->setRepresFirstName($repres_first_name);
        $store->setRepresLastName($repres_last_name);
        $store->setRepresPlaceOfBirth($repres_place_of_birth);
        $store->setRepresDob($btime);
        $store->setRepresEmail($repres_email);
        $store->setRepresPhoneNumber($repres_phone_number);
        $store->setRepresAddress($repres_address);
        $store->setRepresProvince($repres_province);
        $store->setRepresCity($repres_city);
        $store->setRepresZip($repres_zip);
        $store->setShopKeyword($shop_keyword);
        
         if ($referral_id != "") {
             $store->setAffiliationStatus(1);
        }
        $em->persist($store);
        $em->flush();

        //assign the user in UserToGroup Table
        //get usertogroup object
        $usertostore = new UserToStore();
        $usertostore->setStoreId($store->getId());
        $usertostore->setUserId($user_id);
        $usertostore->setChildStoreId(0); // set child store id as 0 for parent store
        $usertostore->setRole(15); //15 for owner
        $time = new \DateTime("now");
        $usertostore->setCreatedAt($time);

        //persist the group object
        $em->persist($usertostore);
        //save the group info
        $em->flush();

        //get ACL object from service
        $acl_obj = $this->container->get("store_manager_store.acl");

        $store_owner_acl_code = $acl_obj->getStoreOwnerAclCode();

        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $user_id));

        $aclManager = $this->container->get('problematic.acl_manager');
        $aclManager->setObjectPermission($store, $store_owner_acl_code, $user_obj);
        //$aclManager->addObjectPermission($group, MaskBuilder::MASK_OWNER);
        //set the broker profile as complete
        //get user object

        $user = $this->container->get('fos_user.user_manager')->findUserBy(array('id' => $user_id));

        $user->setStoreProfile(1);
        $this->container->get('fos_user.user_manager')->updateUser($user);
        //waiver management
        $waiver_service = $this->container->get('card_management.waiver');
        $waiver_service->checkRegistrationWaiverStatus($store->getId()); //for registration fee waiver
        //$waiver_service->checkSubscriptionWaiverStatus($store->getId()); //for subscription fee waiver        

        $resp_data = $store->getId();
        return $resp_data;
    }

    public function setMultiProfile($user_id, $email, $type) {
        //initilise the data array
        $data = array();
        $user_id = $user_id;
        $first_name = "";
        $last_name = "";
        $gender = "";
        $bdate = "";
        $phone = "";
        $ccode = "";
        $street = "";
        $profile_type = $type;


        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        $user_multiprofile = new UserMultiProfile();
        //check if user has already ctreated the profile for profile type
        //fire the query in User Repository

        $user_multiprofile->setUserId($user_id);
        $user_multiprofile->setEmail($email);

        $user_multiprofile->setFirstName($first_name);


        $user_multiprofile->setLastName($last_name);


        $user_multiprofile->setGender($gender);

        //get birth date date object
        $btime = new \DateTime('now');


        $user_multiprofile->setBirthDate($btime);


        $user_multiprofile->setPhone($phone);


        $user_multiprofile->setCountry($ccode);


        $user_multiprofile->setStreet($street);


        $user_multiprofile->setProfileType($profile_type);

        $user_multiprofile->setIsActive(1);
        $time = new \DateTime("now");

        $user_multiprofile->setCreatedAt($time);

        $user_multiprofile->setUpdatedAt($time);
        $user_multiprofile->setProfileSetting(1); //by deafault profile will be public
        $em->persist($user_multiprofile);
        $em->flush();

        return true;
    }

    /**
     * sending the mail.
     * @param user object
     * @return int
     */
    public function sendAction($user, $token) {
            
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($user->getId(), true);
        //get locale
        $locale = !empty($receiver[$user->getId()]['current_language']) ? $receiver[$user->getId()]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        $thumb_path = '';
        //get email
        $email = $user->getEmail();
        //$verification_token = $this->getVerificationToken()
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $verify_link=$this->container->getParameter('verify_link');
        $verify_link_first=$angular_app_hostname.$verify_link;
        //preapare lang array
        $lang = $lang_array['REGISTRATION_WELCOME_MAIL_BODY'];
        
        $click_name = $lang_array['CLICK_HERE'];
        $login_text = $lang_array['LOGIN_TEXT'];
        $first_lastname = ucfirst($user->getFirstname())." ".ucfirst($user->getLastname());
       
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $link = "<a href='$verify_link_first?email=$email&token=$token'>$click_name</a>";
        $mail_sub = $lang_array['REGISTRATION_WELCOME_MAIL_SUBJECT'];
        $mail_body = sprintf($lang, $first_lastname);
        $mail_text = $lang_array['REGISTRATION_WELCOME_MAIL_BODY_CENTER'];
        $bodyData  = sprintf($mail_text, $link); 

        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'NEW_REGISTRATION');
        
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
     * Tell the user to check his email provider
     */
    public function checkEmailAction() {
        $email = $this->container->get('session')->get('fos_user_send_confirmation_email/email');
        $this->container->get('session')->remove('fos_user_send_confirmation_email/email');
        $user = $this->container->get('fos_user.user_manager')->findUserByEmail($email);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:checkEmail.html.' . $this->getEngine(), array(
                    'user' => $user,
        ));
    }

    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction($token) {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);
        $user->setLastLogin(new \DateTime());

        $this->container->get('fos_user.user_manager')->updateUser($user);
        $response = new RedirectResponse($this->container->get('router')->generate('fos_user_registration_confirmed'));
        $this->authenticateUser($user, $response);

        return $response;
    }

    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:confirmed.html.' . $this->getEngine(), array(
                    'user' => $user,
        ));
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param \FOS\UserBundle\Model\UserInterface        $user
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function authenticateUser(UserInterface $user, Response $response) {
        try {
            $this->container->get('fos_user.security.login_manager')->loginUser(
                    $this->container->getParameter('fos_user.firewall_name'), $user, $response);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }

    /**
     * @param string $action
     * @param string $value
     */
    protected function setFlash($action, $value) {
        $this->container->get('session')->getFlashBag()->set($action, $value);
    }

    protected function getEngine() {
        return $this->container->getParameter('fos_user.template.engine');
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
     * @param int $last_user_id
     */
    public function userPositionAction($last_user_id) {
        $registration_number = $last_user_id;
        $district_number = '';
        if (isset($district_number)) {
            $district_number = ceil($registration_number / 1111);
        }
        //get district position
        $district_position = $registration_number % 1111;

        if ($district_position == 1) {
            $block = 1;
            //get position in block
            $remainder = $district_position;
            $pos = ($remainder - 1) + 1;
        }

        if ($district_position >= 2 && $district_position <= 11) {
            $block = 2;
            $remainder = $district_position;
            $pos = ($remainder - 2) + 1;
        }

        if ($district_position >= 12 && $district_position <= 111) {
            $block = 3;
            $remainder = $district_position;
            $pos = ($remainder - 12) + 1;
        }

        if (($district_position >= 112 && $district_position <= 1111) || $district_position == 0) {
            $block = 4;
            $remainder = $district_position;
            $pos = ($remainder - 112) + 1;
        }

        // $user_position= array();
        // $user_position[] = array($district_number, $block, $pos);
        return array('district' => $district_number, 'circle' => $block, 'position' => $pos);
    }

    /**
     * method to get user postion in district,circle and position (Daffodil on 27Aug)
     * @param type $user_id
     */
    public function calculateUserDistrictCirclePosition($user_id) {


        $last_user_id = $user_id;
        $em = $this->container->get('doctrine')->getManager();

        $registration_number = $em
                ->getRepository('UserManagerSonataUserBundle:UserPosition')
                ->getMaxCountValue(1, 0); //i is the limit size, 0 is the offest.
        $count = $registration_number + 1;

        $is_delete_assign = $em
                ->getRepository('UserManagerSonataUserBundle:UserDeletedAssign')
                ->getDeleteUserData(1, 0);

        $deleted_user_id = 0;
        if ($is_delete_assign) {
            $deleted_user_id = $is_delete_assign[0]->getUserId();
        }

        if ($deleted_user_id > 0) {
            // get district,block,Position of this userid and assign to new registered user
            $deleted_user_postion = $em
                    ->getRepository('UserManagerSonataUserBundle:UserPosition')
                    ->findOneBy(array('userId' => $deleted_user_id));
            $deleted_user_district = $deleted_user_postion->getDistrict();
            $deleted_user_circle = $deleted_user_postion->getCircle();
            $deleted_user_position = $deleted_user_postion->getPosition();
            $user_positions = new UserPosition();
            $user_positions->setUserId($last_user_id);
            $user_positions->setCount($count);
            // $user_positions->setCount(10);
            $user_positions->setDistrict($deleted_user_district);
            $user_positions->setCircle($deleted_user_circle);
            $user_positions->setPosition($deleted_user_position);
            $em->persist($user_positions);
            $em->flush();

            //user_id will be id of deleted user while assign_id is id of last_insert_id or last registerd user
            $delete_assign = $is_delete_assign[0];
            $delete_assign->setAssignId($last_user_id);
            $em->persist($delete_assign);
            $em->flush();
        } else {
            $user_positions = $this->userPositionAction($count);
            $district = $user_positions['district'];
            $circle = $user_positions['circle'];
            $position = $user_positions['position'];
            // save new user data into userPosition table
            $user_position = new UserPosition();
            $user_position->setUserId($last_user_id);
            // total number of users in userpostion or foes_user
            $user_position->setCount($count);
            // $user_position->setCount(10);
            $user_position->setDistrict($district);
            $user_position->setCircle($circle);
            $user_position->setPosition($position);
            $em->persist($user_position);
            $em->flush();
        }
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
    
       public function _log($sMessage){
        $monoLog = $this->container->get('monolog.logger.channel1');
        $monoLog->info($sMessage);
    }
    
    private function _toJSON($data){
        return json_encode($data);
    }
    
}
