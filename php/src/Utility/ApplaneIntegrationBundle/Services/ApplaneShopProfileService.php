<?php

namespace Utility\ApplaneIntegrationBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;

// service method  class
class ApplaneShopProfileService {
    
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
     * Check user on applane from local db
     * @param int $filter
     */
    public function checkShopsProfileOnApplane($filter)
    {   
        $shops = $this->getAllLocalShops($filter);   
    }
    
    /**
     * Get All local users
     */
    public function getAllLocalShops($filter)
    {
        $em = $this->em;
        try{
            $limit = $this->container->getParameter('appalne_record_fatch_limit');
        } catch (\Exception $ex) {
            $limit = 500;
        }
        try{
            $offset = $this->container->getParameter('appalne_record_fatch_offset');
        } catch (\Exception $ex) {
            $offset = 0;
        }
        $is_local_shops = 1;
        $unchecked = false;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        do{
        $shops = $em->getRepository('StoreManagerStoreBundle:Store')
                    ->getRegistredShopsProfile($offset, $limit);
        $shops_count = count($shops);
        if($shops_count > 0){
            $shop_ids = array(); //initialise the array
            $shop_ids_array = array();
            foreach($shops as $shop){
                $shop_ids[] = (string)$shop['id'];
                $shop_ids_array[$shop['id']] = $shop;
            }
            $offset = $offset + $limit;
            $applane_shops = array();
            $response = $applane_service->getShopsInfoFromApplane($shop_ids);
            $applane_shops = $this->prepareApplaneShopIds($response);
            $map_shops = $this->mapShops($applane_shops, $shop_ids_array);
            if($map_shops == true){
                $unchecked = true;
            }
        }else{
           $is_local_shops = 0; 
        }
        }while($is_local_shops);
        $this->sendMail($unchecked); //send Mail
        exit('done');
    }
    
     /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLog($monolog_req, $monolog_response = array()){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.applane_shopsprofile_log');
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Get User Ids from applane
     * @param array $response
     * @return type
     */
    public function prepareApplaneShopIds($response)
    {
        $total_applane_shops = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
               $applane_shops['id'] = (isset($single_result->_id)) ? $single_result->_id : 0;
               $applane_shops['category_id'] = (isset($single_result->category_id->_id)) ? $single_result->category_id->_id : 0;
               $applane_shops['country'] = (isset($single_result->country->countryname)) ? $single_result->country->countryname : '';
               $applane_shops['shopowner_id'] = (isset($single_result->shopowner_id->_id)) ? $single_result->shopowner_id->_id : 0;
               $applane_shops['address_l1'] = (isset($single_result->address_l1)) ? $single_result->address_l1 : '';
               $applane_shops['mobile_no'] = (isset($single_result->mobile_no)) ? $single_result->mobile_no : 0;
               $applane_shops['name'] = (isset($single_result->name)) ? $single_result->name : '';
               $applane_shops['credit_card_added'] = (isset($single_result->credit_card_added)) ? (int)$single_result->credit_card_added : 0;
               $applane_shops['address_l2'] = (isset($single_result->address_l2)) ? $single_result->address_l2 : '';
               $applane_shops['is_shop_deleted'] = (isset($single_result->is_shop_deleted)) ? (int)$single_result->is_shop_deleted : 0;
               $applane_shops['region'] = (isset($single_result->region)) ? $single_result->region : '';
               $applane_shops['province'] = (isset($single_result->province)) ? $single_result->province : '';
               $applane_shops['zip'] = (isset($single_result->zip)) ? $single_result->zip : 0;
               $applane_shops['street_address'] = (isset($single_result->street_address)) ? $single_result->street_address : '';
               $applane_shops['email_address'] = (isset($single_result->email_address)) ? $single_result->email_address : '';
               $applane_shops['latitude'] = (isset($single_result->latitude)) ? $single_result->latitude : 0;
               $applane_shops['longitude'] = (isset($single_result->longitude)) ? $single_result->longitude : 0;
               $applane_shops['subcategory_id'] = (isset($single_result->subcategory_id->_id)) ? $single_result->subcategory_id->_id : '';
               $total_applane_shops[$single_result->_id] = $applane_shops;
            }
        }
        return $total_applane_shops;
    }
    
    /**
     * Check mapped users
     * @param array $applane_users
     * @param array $local_users
     * @return boolean
     */
    public function mapShops($applane_shops, $local_shops)
    {
       $unmatch_status = false;
       $not_exist_shops = array();
       $log_array = array();
       $unmatched_ids = array();
       //find matched keys
       $matched_keys = array_intersect_key($local_shops, $applane_shops);
       $unmatched_keys = array_diff_key($local_shops, $applane_shops);
       foreach($unmatched_keys as $unmatched_key){
           $unmatched_ids[] = $unmatched_key['id'];
       }
      // print_r($local_shops);
       $unmatched_ids = array_unique($unmatched_ids);
        foreach($matched_keys as $matched_key){
            $shop_id = $matched_key['id'];
            if($local_shops[$shop_id]['id'] == $applane_shops[$shop_id]['id']){
                //user exist. Check for internal value
                $local_shop_info = $local_shops[$shop_id];
                $applane_shop_info = $applane_shops[$shop_id];
                $log['id'] = $shop_id;
                if($applane_shop_info['country']=='Italy'){
                    $applane_shop_info['country'] = 'IT';
                }
                if($applane_shop_info['country']=='United States of America'){
                    $applane_shop_info['country'] = 'US';
                }
                
                if($local_shop_info['category_id'] != $applane_shop_info['category_id']){
                    $log['local_category_id'] = $local_shop_info['category_id'];
                    $log['appalne_category_id'] = $applane_shop_info['category_id'];
                    $log['category_id'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['shopowner_id'] != $applane_shop_info['shopowner_id']){
                    $log['local_shopowner_id'] = $local_shop_info['shopowner_id'];
                    $log['appalne_shopowner_id'] = $applane_shop_info['shopowner_id'];
                    $log['shopowner_id'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['address_l1'] != $applane_shop_info['address_l1']){
                    $log['local_address_l1'] = $local_shop_info['address_l1'];
                    $log['appalne_address_l1'] = $applane_shop_info['address_l1'];
                    $log['address_l1'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['mobile_no'] != $applane_shop_info['mobile_no']){
                    $log['local_mobile_no'] = $local_shop_info['mobile_no'];
                    $log['appalne_mobile_no'] = $applane_shop_info['mobile_no'];
                    $log['mobile_no'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['name'] != $applane_shop_info['name']){
                    $log['local_name'] = $local_shop_info['name'];
                    $log['appalne_name'] = $applane_shop_info['name'];
                    $log['name'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['credit_card_added'] != $applane_shop_info['credit_card_added']){
                    $log['local_credit_card_added'] = $local_shop_info['credit_card_added'];
                    $log['appalne_credit_card_added'] = $applane_shop_info['credit_card_added'];
                    $log['credit_card_added'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['address_l2'] != $applane_shop_info['address_l2']){
                    $log['local_address_l2'] = $local_shop_info['address_l2'];
                    $log['appalne_address_l2'] = $applane_shop_info['address_l2'];
                    $log['address_l2'] ='Note matched';
                    $unmatch_status = true;
                }
                
                if($local_shop_info['is_shop_deleted'] != $applane_shop_info['is_shop_deleted']){
                    $log['local_is_shop_deleted'] = $local_shop_info['is_shop_deleted'];
                    $log['appalne_is_shop_deleted'] = $applane_shop_info['is_shop_deleted'];
                    $log['is_shop_deleted'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['region'] != $applane_shop_info['region']){
                    $log['local_region'] = $local_shop_info['region'];
                    $log['appalne_region'] = $applane_shop_info['region'];
                    $log['region'] ='Note matched';
                    $unmatch_status = true;
                }
                
                if($local_shop_info['province'] != $applane_shop_info['province']){
                    $log['local_province'] = $local_shop_info['province'];
                    $log['appalne_province'] = $applane_shop_info['province'];
                    $log['province'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['zip'] != $applane_shop_info['zip']){
                    $log['local_zip'] = $local_shop_info['zip'];
                    $log['appalne_zip'] = $applane_shop_info['zip'];
                    $log['zip'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['street_address'] != $applane_shop_info['street_address']){
                    $log['local_street_address'] = $local_shop_info['street_address'];
                    $log['appalne_street_address'] = $applane_shop_info['street_address'];
                    $log['street_address'] ='Note matched';
                    $unmatch_status = true;
                }
                
                if($local_shop_info['email_address'] != $applane_shop_info['email_address']){
                    $log['local_email_address'] = $local_shop_info['email_address'];
                    $log['appalne_email_address'] = $applane_shop_info['email_address'];
                    $log['email_address'] ='Note matched';
                    $unmatch_status = true;
                }
                
               if($local_shop_info['latitude'] != $applane_shop_info['latitude']){
                    $log['local_latitude'] = $local_shop_info['latitude'];
                    $log['appalne_latitude'] = $applane_shop_info['latitude'];
                    $log['latitude'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_shop_info['longitude'] != $applane_shop_info['longitude']){
                    $log['local_longitude'] = $local_shop_info['longitude'];
                    $log['appalne_longitude'] = $applane_shop_info['longitude'];
                    $log['longitude'] ='Note matched';
                    $unmatch_status = true;
                }
                
               if($local_shop_info['subcategory_id'] != $applane_shop_info['subcategory_id']){
                    $log['local_subcategory_id'] = $local_shop_info['subcategory_id'];
                    $log['appalne_subcategory_id'] = $applane_shop_info['subcategory_id'];
                    $log['subcategory_id'] ='Note matched';
                    $unmatch_status = true;
                }
                $log_array[] = $log;
            }else{
                //user not exist
                $not_exist_shops[] = $shop_id;
            }
        }
        $this->__createLog('Shops_Missing_Info: '.json_encode($log_array));
        $this->__createLog('Not_Found_Shop_id on applane: '.json_encode($unmatched_ids));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_shops)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_shopsprofile_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for shop profile on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for shop  profile on sixthcontinent database and Applane database.';
        $mail_sub = ($map_shops == true) ? 'Applane Shop Profile Log [Unmatched found]' : 'Applane Shop Profile Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
}