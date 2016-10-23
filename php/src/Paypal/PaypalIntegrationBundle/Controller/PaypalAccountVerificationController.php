<?php

namespace Paypal\PaypalIntegrationBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Paypal\PaypalIntegrationBundle\Entity\ShopPaypalInformation;
use Utility\UtilityBundle\Utils\Utility;

require_once(__DIR__ . '/../Resources/lib/adaptiveaccounts-sdk-php-master/sdk/PPBootStrap.php');
require_once(__DIR__ . '/../Resources/lib/adaptiveaccounts-sdk-php-master/sdk/Configuration.php');

use GetVerifiedStatusRequest;
use AccountIdentifierType;
use AdaptiveAccountsService;
use Configuration;

class PaypalAccountVerificationController extends Controller {
    
    const PAYPAL_UNVERIFIED = 'UNVERIFIED';
    const PAYPAL_VERIFIED = 'VERIFIED';
    const PAYPAL_BUSINESS = 'BUSINESS';
    const PAYPAL_PREMIER = 'PREMIER';
    
    protected $valid_paypal_accounts = array(self::PAYPAL_BUSINESS,self::PAYPAL_PREMIER);
    /**
     *  function for verifie the paypal account for the store 
     * @param Request $request
     * @return array;
     */
    public function postVerifiepaypalsAction(Request $request) {

        //initilise the array
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

        $required_parameter = array('session_id', 'shop_id', 'email_address', 'first_name', 'last_name');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        //get information from the object
        $user_id = $object_info->session_id;
        $shop_id = $object_info->shop_id;
        $email_id = $object_info->email_address;
        $first_name = $object_info->first_name;
        $account_id = isset($object_info->account_id) ? $object_info->account_id : '';
        $last_name = $object_info->last_name;
        $match_criteria = "NAME";
        $mobile_nub = isset($object_info->mobile_number) ? $object_info->mobile_number : '';
        $is_default = 0;
        //get doctrine object
        $em = $this->getDoctrine()->getManager();

        //get the previous paypal account if exist
        $paypal_info = $em
                ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findBy(array("shopId" => $shop_id, "status" => self::PAYPAL_VERIFIED, 'isDeleted' => 0, 'emailId' => $email_id, 'firstName' => $first_name, 'lastName' => $last_name));


        //check if user already added a paypal account
        if (count($paypal_info) > 0) {
            $res_obj = json_encode(array('code' => 1053, 'message' => 'PAYPAL_ALREADY_EXIST', 'data' => $data));
            echo $res_obj;
            exit;
        }

        //check user login is the store owner or creater
        $user_store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'userId' => $user_id, 'role' => 15));
        if (count($user_store_obj) > 0) {
            //get object of the GetVerifiedStatusRequest class
            $getVerifiedStatus = new GetVerifiedStatusRequest();
            //get object of the AccountIdentifierType class
            $accountIdentifier = new AccountIdentifierType();
            // adding require data in the $accountIdentifier object
            $accountIdentifier->emailAddress = $email_id;
            $getVerifiedStatus->accountIdentifier = $accountIdentifier;
            $getVerifiedStatus->firstName = $first_name;
            $getVerifiedStatus->lastName = $last_name;
            $getVerifiedStatus->matchCriteria = $match_criteria;

            //get parameters from the parameter.yml file
            $mode = $this->container->getParameter('paypal_mode');
            if ($mode == 'sandbox') {
                $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
                $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
                $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
                $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
                $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
            } else {
                $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
                $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
                $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
                $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
                $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
            }

            // ## Creating service wrapper object
            // Creating service wrapper object to make API call
            // Configuration::getAcctAndConfig() returns array that contains credential and config parameters
            $service = new AdaptiveAccountsService(Configuration::getAcctAndConfig($paypal_acct_username, $paypal_acct_password, $paypal_acct_signature, $paypal_acct_appid, $paypal_acct_email_address));
            try {
                // ## Making API call
                // invoke the appropriate method corresponding to API in service
                // wrapper object
                $response = $service->GetVerifiedStatus($getVerifiedStatus);
            } catch (Exception $ex) {
                require_once 'Common/Error.php';
                exit;
            }
            // ## Accessing response parameters
            // You can access the response parameters as shown below
            $ack = strtoupper($response->responseEnvelope->ack);
            $account_type = '';
            $account_id = '';
            $account_to_display = '';
            if ($ack != "SUCCESS") {
                //store infomation in the shoppaypalinformation table with error 
                $correlation_id = $response->responseEnvelope->correlationId;
                $build_id = $response->responseEnvelope->build;
                $error_code_array = $response->error;
                $error_code_obj = $error_code_array[0];
                $error_code = $error_code_obj->errorId;
                $error_discription = $error_code_obj->message;
                $info_saved = $this->storePaypalInformation($shop_id, $account_to_display, $email_id, $mobile_nub, $first_name, $last_name, self::PAYPAL_UNVERIFIED, $correlation_id, $account_type, $account_id, $build_id, $error_code, $error_discription, $is_default);
                $res_obj = json_encode(array('code' => $error_code, 'message' => $error_discription, 'data' => $data));
                echo $res_obj;
                exit;
            } else {
                //store infomation in the shoppaypalinformation table with error 
                //get the previous paypal account if exist
                $paypal_check = $em
                        ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                        ->findBy(array("shopId" => $shop_id, "status" => self::PAYPAL_VERIFIED, 'isDeleted' => 0));
                //check if first paypal is added
                if (count($paypal_check) == 0) {
                    $is_default = 1;
                }
                $id = '';
                $correlation_id = $response->responseEnvelope->correlationId;
                $build_id = $response->responseEnvelope->build;
                $user_info_obj = $response->userInfo;
                $account_type = $user_info_obj->accountType;
                $account_id = $user_info_obj->accountId;
                $account_to_display = $this->getAccountIdTodisplay($user_info_obj->accountId);
                $account_status = $response->accountStatus;
                $error_code = '';
                $error_discription = '';
                //check account type is verified or not in live enviormrnt only               
                if ($mode == 'live') {
                    if(strtoupper(trim($account_status)) == self::PAYPAL_UNVERIFIED) {
                        $info_saved = $this->storePaypalInformation($shop_id, $account_to_display, $email_id, $mobile_nub, $first_name, $last_name, self::PAYPAL_UNVERIFIED, $correlation_id, $account_type, $account_id, $build_id, $error_code, $error_discription, 0);
                        $res_obj = json_encode(array('code' => 550001, 'message' => 'User is not allowed to perform this action.', 'data' => $data));
                        echo $res_obj;
                        exit;
                    }
                }
//                $valid_paypal_accounts = $this->valid_paypal_accounts;
//                //check if user is adding premium or business account
//                if(!in_array(Utility::getUpperCaseString($account_type), $valid_paypal_accounts)) {
//                    $res_obj = json_encode(array('code' => 1137, 'message' => 'INVALID_PAYPAL_TYPE', 'data' => $data));
//                    echo $res_obj;
//                    exit;
//                }
                $info_saved = $this->storePaypalInformation($shop_id, $account_to_display, $email_id, $mobile_nub, $first_name, $last_name, self::PAYPAL_VERIFIED, $correlation_id, $account_type, $account_id, $build_id, $error_code, $error_discription, $is_default);
                $data['shop_id'] = $shop_id;
                //check if retuen the proper info saved
                if ($info_saved) {
                    $id = $info_saved->getId();
                }
                $data['id'] = $id;
                $res_obj = json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => $data));
                //save paypal status in the store tabel and set to 1
                $paypal_status = 1;
                $this->updateStorePaypalStatus($shop_id, $paypal_status);
                echo $res_obj;
                exit;
            }
        } else {
            $res_obj = json_encode(array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data));
            echo $res_obj;
            exit;
        }
    }

    /**
     *  function for storing the information in the shoppaypalinformation table
     * @param type $shop_id
     * @param type $email_to_display
     * @param type $email_id
     * @param type $mobile_nub
     * @param type $first_name
     * @param type $last_name
     * @param type $status
     * @param type $correlation_id
     * @param type $account_type
     * @param type $account_id
     * @param type $build_id
     * @param type $error_code
     * @param type $error_discription
     */
    public function storePaypalInformation($shop_id, $account_to_display, $email_id, $mobile_nub, $first_name, $last_name, $status, $correlation_id, $account_type, $account_id, $build_id, $error_code, $error_discription, $is_default) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime();
        $paypal_info = new ShopPaypalInformation();
        $paypal_info->setShopId($shop_id);
        $paypal_info->setAccountIdToDisplay($account_to_display);
        $paypal_info->setEmailId($email_id);
        $paypal_info->setMobileNumber($mobile_nub);
        $paypal_info->setFirstName($first_name);
        $paypal_info->setLastName($last_name);
        $paypal_info->setStatus($status);
        $paypal_info->setCorrelationId($correlation_id);
        $paypal_info->setAccountType($account_type);
        $paypal_info->setAccountId($account_id);
        $paypal_info->setBuildId($build_id);
        $paypal_info->setErrorCode($error_code);
        $paypal_info->setErrorDiscription($error_discription);
        $paypal_info->setCreatedAt($time);
        $paypal_info->setIsDefault($is_default);
        $em->persist($paypal_info);
        $em->flush();

        $info = false;
        //check if saved correctly
        if ($paypal_info) {
            $info = $paypal_info;
        }

        return $info;
    }

    /**
     *  function for deleting the paypal account for the store 
     * @param Request $request
     * @return array;
     */
    public function postDeletepaypalsAction(Request $request) {
        //initilise the array
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

        $required_parameter = array('session_id', 'shop_id', 'id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //get information from the object
        $user_id = $object_info->session_id;
        $shop_id = $object_info->shop_id;
        $paypal_id = $object_info->id;

        //get the previous paypal account if exist
        $paypal_info = $em
                ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => self::PAYPAL_VERIFIED, 'isDeleted' => 0, 'id' => $paypal_id));
        //check if account really exist
        if (count($paypal_info) > 0) {
            //check login user is the store owner or creater
            $user_store_obj = $em
                    ->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId' => $shop_id, 'userId' => $user_id, 'role' => 15));
            //check if user is the owner of the store
            if (count($user_store_obj) > 0) {
                $is_default = $paypal_info->getIsDefault();
                //check if the paypal_id from DB and send paypal id is same 
                if ($paypal_info->getId() == $paypal_id) {
                    $paypal_info->setIsDeleted(1);
                    $paypal_info->setStatus(self::PAYPAL_UNVERIFIED);
                    $paypal_info->setIsDefault(0);
                    $em->persist($paypal_info);
                    $em->flush();
                    $res_obj = json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => $data));

                    $current_paypal_accounts = $em
                            ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                            ->findOneBy(array("shopId" => $shop_id, "status" => self::PAYPAL_VERIFIED, 'isDeleted' => 0));
                    //check if shop has deleted all the paypal accounts
                    if (count($current_paypal_accounts) == 0) {
                        //update store status
                        $this->updateStorePaypalStatus($shop_id, 0);
                    } else {
                        if ($is_default == 1) {
                            $paypal_id_for_default = $current_paypal_accounts->getId();
                            $this->setPaypalAccountStatus($shop_id, self::PAYPAL_VERIFIED, self::PAYPAL_VERIFIED, 0, 1, 0, 0, $paypal_id_for_default);
                        }
                    }
                    echo $res_obj;
                    exit;
                } else {
                    $res_obj = json_encode(array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data));
                    echo $res_obj;
                    exit;
                }
            } else {
                $res_obj = json_encode(array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data));
                echo $res_obj;
                exit;
            }
        } else {
            $res_obj = json_encode(array('code' => 1029, 'message' => 'FAILURE', 'data' => $data));
            echo $res_obj;
            exit;
        }
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
     *  function for updating the paypal status for the shop
     * @param type $shop_id
     * @param type $paypal_status
     */
    public function updateStorePaypalStatus($shop_id, $paypal_status) {
        //get doctring object
        $em = $this->getDoctrine()->getManager();

        //get the previous paypal account if exist
        $store_info = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->find(array("id" => $shop_id));
        //set isPaypalAdded to 1 because paypal added
        if (count($store_info) > 0) {
            $store_info->setIsPaypalAdded($paypal_status);
            $em->persist($store_info);
            $em->flush();
        }
    }

    /**
     *  function for listing the paypal account for the store 
     * @param Request $request
     * @return array;
     */
    public function postListpaypalaccountsAction(Request $request) {
        //initilise the array
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

        $required_parameter = array('session_id', 'shop_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $limit = (int) (isset($object_info->limit_size) ? $object_info->limit_size : 20);
        $offset = (int) (isset($object_info->limit_start) ? $object_info->limit_start : 0);
        $shop_id = $object_info->shop_id;
        $user_id = $object_info->session_id;
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        //check user login is the store owner or creater
        $user_store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'userId' => $user_id, 'role' => 15));
        if (count($user_store_obj) > 0) {
            $paypal_infos = $em
                    ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                    ->findBy(array("shopId" => $shop_id, "status" => self::PAYPAL_VERIFIED, 'isDeleted' => 0), null, $limit, $offset);

            //find count of the paypal accounts
            $paypal_count = $em
                    ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                    ->countPaypals($shop_id);
            //loop for making the required object
            $paypal_data = array();
            foreach ($paypal_infos as $paypal_info) {
                $paypal_obj = array(
                    'id' => $paypal_info->getId(),
                    'account_id' => $paypal_info->getAccountIdToDisplay(),
                    'registered_date' => $paypal_info->getCreatedAt(),
                    'first_name' => $paypal_info->getFirstName(),
                    'last_name' => $paypal_info->getLastName(),
                    'default_status' => $paypal_info->getIsDefault()
                );
                $paypal_data[] = $paypal_obj;
            }
            $data['paypal_accounts'] = $paypal_data;
            $data['paypal_count'] = $paypal_count;
            $res_obj = json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => $data));
            echo $res_obj;
            exit;
        } else {
            $res_obj = json_encode(array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data));
            echo $res_obj;
            exit;
        }
    }

    /**
     *  function for setting a paypal account as default paypal account
     * @param Request $request
     * @return array;
     */
    public function postSetdefaultpaypalsAction(Request $request) {
        //initilise the array
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

        $required_parameter = array('session_id', 'shop_id', "id");
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $user_id = $object_info->session_id;
        $shop_id = $object_info->shop_id;
        $paypal_id = $object_info->id;
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        //check user login is the store owner or creater
        $user_store_obj = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id, 'userId' => $user_id, 'role' => 15));
        if (count($user_store_obj) > 0) {
            //set previous paypal id to non default
            $status = $this->setPaypalAccountStatus($shop_id, self::PAYPAL_VERIFIED, self::PAYPAL_VERIFIED, 1, 0, 0, 0);
            if ($status) {
                //set new paypal is to default paypal account
                $this->setPaypalAccountStatus($shop_id, self::PAYPAL_VERIFIED, self::PAYPAL_VERIFIED, 0, 1, 0, 0, $paypal_id);
                $res_obj = json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => $data));
                echo $res_obj;
                exit;
            } else {
                $res_obj = json_encode(array('code' => 1029, 'message' => 'FAILURE', 'data' => $data));
                echo $res_obj;
                exit;
            }
        } else {
            $res_obj = json_encode(array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data));
            echo $res_obj;
            exit;
        }
    }

    /**
     *  function for changing the paypal account status
     * @param type $shop_id
     * @param type $current_status
     * @param type $new_status
     * @param type $current_is_default
     * @param type $new_is_default
     * @param type $current_is_deleted
     * @param type $new_is_deleted
     * @param type $id
     * @return boolean
     */
    public function setPaypalAccountStatus($shop_id, $current_status, $new_status, $current_is_default, $new_is_default, $current_is_deleted, $new_is_deleted, $id = 0) {

        //check if is is passed
        if ($id == 0) {
            $condition = array("shopId" => $shop_id, "status" => $current_status, 'isDeleted' => $current_is_deleted, 'isDefault' => $current_is_default);
        } else {
            $condition = array("shopId" => $shop_id, "status" => $current_status, 'isDeleted' => $current_is_deleted, "id" => $id);
        }
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        //get the paypal account that is previousally set default
        $paypal_info = $em
                ->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy($condition);
        if (count($paypal_info) > 0) {
            $paypal_info->setStatus($new_status);
            $paypal_info->setIsDefault($new_is_default);
            $paypal_info->setIsDeleted($new_is_deleted);
            try {
                $em->persist($paypal_info);
                $em->flush();
                return true;
            } catch (Exception $ex) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     *  function for securing the account id of the paypal account
     * @param type $account_id
     * @return type
     */
    public function getAccountIdTodisplay($account_id) {
        $len = strlen($account_id);
        return substr($account_id, 0, 3) . str_repeat('*', $len - 6) . substr($account_id, $len - 3, 3);
    }

}
