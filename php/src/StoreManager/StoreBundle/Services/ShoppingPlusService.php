<?php

namespace StoreManager\StoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use WalletManagement\WalletBundle\Entity\UserDiscountPosition;
use StoreManager\StoreBundle\Controller\ShoppingplusController;

// service method  class
class ShoppingPlusService {

    protected $em;
    protected $dm;
    protected $container;

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
        //$this->request   = $request;
    }

    /**
     * 
     * @return type
     */
    public function getShoppingPlusParameters() {
        //get remote url url
        $env = $this->container->getParameter('kernel.environment');
        if ($env == 'dev') { // test environment
            // echo "dev envir";
            $url = $this->container->getParameter('shopping_plus_get_client_url_test');
            $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
            $shopping_plus_password = $this->container->getParameter('social_bees_password_test');
        } else {
            $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
            $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
            $shopping_plus_password = $this->container->getParameter('social_bees_password_prod');
        }
        $res_data = array(
            'url' => $url,
            'shopping_plus_username' => $shopping_plus_username,
            'shopping_plus_password' => $shopping_plus_password
        );
        return $res_data;
    }

    /**
     * 
     * @param type $data
     * @return boolean
     */
    public function changeStoreStatusOnShoppingPlus($profile_id, $status) {

        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $data_object = array('o' => 'PDVSTATO',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'v01' => $profile_id,
            'v02' => $status
        );
        try {
            $shop_deactive_output = $curl_obj->shoppingplusCitizenRemoteServer($data_object, $url);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @return boolean
     */
    public function movreg($accumulo, $user_id, $amount, $importo, $shop_id, $type) {

        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $request_data = array('o' => 'MOVREG',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $accumulo,
            'V02' => $user_id,
            'V03' => $amount,
            'V04' => $importo,
            'V05' => $shop_id,
            'V06' => $type,
        );
        try {
            $out = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
        } catch (\Exception $ex) {
            
        }

        return true;
    }

    /**
     * open DP on shop
     * @param type $data
     * @return boolean
     */
    public function pdvpsupdate($shop_id, $process, $discount) {

        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $request_data = array('o' => 'PDVPSUPDATE',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $shop_id,
            'V02' => $process, //ADD
            'V03' => $discount,
        );
        try {
            $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
        } catch (\Exception $ex) {
            
        }

        return true;
    }

    /**
     * 
     * @param type $data
     * @return boolean
     */
    public function pushNotification($user_id, $message, $label_of_button, $redirection_link, $message_title) {

        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $request_data = array('o' => 'PUSHADD',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $user_id,
            'V02' => $curl_obj->convertSpaceToHtml($message), //ADD
            'V03' => $curl_obj->convertSpaceToHtml($label_of_button),
            'V04' => $curl_obj->convertSpaceToHtml($redirection_link), //ADD
            'V05' => $curl_obj->convertSpaceToHtml($message_title),
        );

        try {
            $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
        } catch (\Exception $ex) {
            
        }

        return true;
    }

    /*
     * Register citizen on shopping plus
     * @param type $data
     * @return boolean
     */

    public function registerCitizenShoppingPlus($register_id, $firstname, $lastname, $email, $cell, $password, $referral_id, $manager, $type, $step) {
        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $request_data = array('o' => 'CLIENTEADD',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $register_id,
            'V02' => $curl_obj->convertSpaceToHtml($firstname),
            'V03' => $curl_obj->convertSpaceToHtml($lastname),
            'V04' => $email,
            'V05' => $cell, // I do not get this value from above so I left it blank
            'V06' => $email,
            'V07' => $password,
            'V08' => $referral_id,
            'V09' => $curl_obj->convertSpaceToHtml($manager)
        );
        try {
            $out_put = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
            $decode_data = urldecode($out_put);
            parse_str($decode_data, $final_output);
            if (isset($final_output)) {
                $sh_status = $final_output['stato'];
                $sh_error_desc = $final_output['descrizione'];
                if ($sh_status != 0) {
                    $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                    $out_put_shop = $shopping_plus_obj->ShoppingplusStatus($register_id, $type, $status = 0, $sh_status, $sh_error_desc, $step);
                }
            }
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /*
     * Register citizen on shopping plus
     * @param type $data
     * @return boolean
     */

    public function updateCitizenShoppingPlus($register_id, $firstname, $lastname, $email, $cell, $password, $referral_id, $manager, $type, $step) {
        $curl_obj = $this->container->get("store_manager_store.curl");

        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $request_data = array('o' => 'CLIENTEUPDATE',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $register_id,
            'V02' => $curl_obj->convertSpaceToHtml($firstname),
            'V03' => $curl_obj->convertSpaceToHtml($lastname),
            'V04' => $email,
            'V05' => $cell, // I do not get this value from above so I left it blank
            'V06' => $email,
            'V07' => $password,
            'V08' => $referral_id,
            'V09' => $curl_obj->convertSpaceToHtml($manager)
        );
        try {
            $out_put = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
            $decode_data = urldecode($out_put);
            parse_str($decode_data, $final_output);
            if (isset($final_output)) {
                $sh_status = $final_output['stato'];
                $sh_error_desc = $final_output['descrizione'];
                if ($sh_status != 0) {
                    $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                    $out_put_shop = $shopping_plus_obj->ShoppingplusStatus($register_id, $type, $status = 0, $sh_status, $sh_error_desc, $step);
                }
            }
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /*
     * Register Store on shopping plus 
     * @param type $data
     * @return boolean
     */

    public function registerShopShopingplus($store_id, $business_name, $business_address, $zip, $business_city, $provience, $phone, $user_email, $description, $vat_number, $password, $referral_id, $virtual_status, $importopdv_amount, $type, $step, $shop_status_shopping_plus) {

        $curl_obj = $this->container->get("store_manager_store.curl");
        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];

        //V12 = fos_user_user which should be registered on shopping plus server as broker(only user is broker).
        //It is given by frontend(Angular). We have to check this broker in
        // our table if find then ok otherwise disaply error. this value may be same as time of register store

        $request_data = array('o' => 'PDVADD',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $store_id,
            'V02' => $curl_obj->convertSpaceToHtml($business_name), //legal_status
            'V03' => $curl_obj->convertSpaceToHtml($business_address),
            'V04' => $curl_obj->convertSpaceToHtml($zip),
            'V05' => $curl_obj->convertSpaceToHtml($business_city),
            'V06' => $curl_obj->convertSpaceToHtml($provience),
            'V07' => $curl_obj->convertSpaceToHtml($phone),
            // 'V08'=>$store_email, //store email
            'V08' => $user_email,
            'V09' => $curl_obj->convertSpaceToHtml($description),
            // 'V10'=>$user_email,   // (fos_user_user email)
            'V10' => $curl_obj->convertSpaceToHtml($vat_number), //vat_number ( this should be unique)
            'V11' => $password, // fos_user_user password
            'V12' => $referral_id, //fos_fos_user.id which is broker
            'V13' => $virtual_status,
            'V14' => $importopdv_amount
        );


        // we use try catch in case, any how shop is not registered on shopping plus
        // then it throw exception but not stop the registration flow.
        try {
            $out_put_sh = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
            // save those shop which are registered on sixthcontinent but not on shoppingplus server
            // save these data in mongodb
            $decode_data = urldecode($out_put_sh);
            parse_str($decode_data, $final_output_sh);
            if (isset($final_output_sh)) {
                $sh_status = $final_output_sh['stato'];
                $sh_error_desc = $final_output_sh['descrizione'];
                $step = 'Shop Registeration';
                if ($sh_status != 0) {
                    $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                    $shopping_plus_obj->ShoppingplusStatus($store_id, $type, $status = 0, $sh_status, $sh_error_desc, $step);
                }
            }
            $shop_active_fields = array('o' => 'PDVSTATO',
                'u' => $shopping_plus_username,
                'p' => $shopping_plus_password,
                'v01' => $store_id,
                'v02' => $shop_status_shopping_plus
            );
            $curl_obj->shoppingplusCitizenRemoteServer($shop_active_fields, $url);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /*
     * Function to update Store
     */

    public function updateShopShopingplus($store_id, $business_name, $business_address, $zip, $business_city, $provience, $phone, $user_email, $description, $vat_number, $password, $referral_id, $virtual_status, $importopdv_amount, $type, $step) {

        $curl_obj = $this->container->get("store_manager_store.curl");
        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];

        $request_data = array('o' => 'PDVUPDATE',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'V01' => $store_id,
            'V02' => $curl_obj->convertSpaceToHtml($business_name), //legal_status
            'V03' => $curl_obj->convertSpaceToHtml($business_address),
            'V04' => $curl_obj->convertSpaceToHtml($zip),
            'V05' => $curl_obj->convertSpaceToHtml($business_city),
            'V06' => $curl_obj->convertSpaceToHtml($provience),
            'V07' => $phone,
            // 'V08'=>$store_email, //store email
            'V08' => $user_email,
            'V09' => $curl_obj->convertSpaceToHtml($description),
            // 'V10'=>$user_email,   // (fos_user_user email)
            'V10' => $vat_number, //vat_number ( this should be unique)
            'V11' => $password, // fos_user_user password
            'V12' => $referral_id, //fos_fos_user.id which is broker
            'V13' => $virtual_status,
            'V14' => $importopdv_amount
        );
        try {
            $out_put_sh = $curl_obj->shoppingplusCitizenRemoteServer($request_data, $url);
            // save these data in mongodb
            $decode_data = urldecode($out_put_sh);
            parse_str($decode_data, $final_output_sh);
            if (isset($final_output_sh)) {
                $sh_status = $final_output_sh['stato'];
                $sh_error_desc = $final_output_sh['descrizione'];
                if ($sh_status != 0) {
                    $shopping_plus_obj = $this->container->get('store_manager_store.shoppingplusStatus');
                    $shopping_plus_obj->ShoppingplusStatus($store_id, $type, $status = 0, $sh_status, $sh_error_desc, $step);
                }
            }
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /* function for calcluation of total economics of shop
     * @return array
     * @param type $data
     */
    /* function for calcluation of total economics of shop
     * @return array
     * @param type $data
     */

    public function economs($id) {
        $curl_obj = $this->container->get("store_manager_store.curl");
        $shopping_parameters_data = $this->getShoppingPlusParameters();
        $url = $shopping_parameters_data['url'];
        $shopping_plus_username = $shopping_parameters_data['shopping_plus_username'];
        $shopping_plus_password = $shopping_parameters_data['shopping_plus_password'];
        $fields = array('o' => 'ECONOMS',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'v01' => $id
        );
        $economics = $curl_obj->shoppingplusClientRemoteServer($fields, $url);
        return $economics;
    }

    public function getCitizenIncomeFromCardsoldo($citizen_id) {
        $date = date('Y-m-d');
        $user_ci = 0;
        $em = $this->container->get('doctrine')->getManager();
        $user_dp = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                ->findOneBy(array('userId' => $citizen_id, 'updatedAt' => new \DateTime($date)));
        if (count($user_dp) > 0) {
            $user_ci = $user_dp->getCitizenIncome();
        } else {
            $dm = $this->container->get('doctrine')->getManager();
            $param_array = array('idcard' => $citizen_id);
            $params = json_encode($param_array);
            $request = new Request();
            //$params = '{"idcard":"12350"}';
            $request->attributes->set('reqObj', $params);
            $shopping_plus_controller = new ShoppingplusController();
            $response = $shopping_plus_controller->cardsoldsinternalAction($request);
            //get response code
            $resp_code = $response['code'];
            if ($resp_code == 101) {
                $response_data = $response['data'];
                $user_ci = $response_data['saldoc'];
                $user_dp = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                        ->findOneBy(array('userId' => $citizen_id));
                if (count($user_dp) > 0) {
                    $user_dp->setCitizenIncome($user_ci);
                    $user_dp->setUpdatedAt(new \DateTime($date));
                } else {
                    $user_dp = new UserDiscountPosition();
                    $user_dp->setTotalDp(0);
                    $user_dp->setBalanceDp(0);
                    $user_dp->setCitizenIncome($user_ci);
                    $user_dp->setUserId($citizen_id);
                    $user_dp->setCreatedAt(new \DateTime($date));
                    $user_dp->setUpdatedAt(new \DateTime($date));
                }
                $dm->persist($user_dp);
                $dm->flush();
            }
        }
        return $user_ci;
    }

}
