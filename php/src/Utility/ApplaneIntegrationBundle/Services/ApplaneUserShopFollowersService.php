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
class ApplaneUserShopFollowersService {
    
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
    public function checkShopFollowersOnApplane($filter)
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
        $is_local_users = 1;
        $unchecked = false;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        do{
        $followers_final_array = array();
        $shops = $em->getRepository('StoreManagerStoreBundle:Store')
                    ->getRegistredShops($offset, $limit);
        $shops_count = count($shops);
        if($shops_count > 0){
            $shop_ids = array(); //initialise the array
            foreach($shops as $shop){
                $shop_ids[] = (string)$shop['id'];
            }
            $offset = $offset + $limit;
            $applane_shops = array();
            $shop_user_id = $this->getShopUserFollowers($shop_ids);
            $shop_followers = $this->getShopFollowers($shop_ids);
            foreach($shops as $shop){
                $single_id = $shop['id'];
                $followers_final_array[$single_id] = (isset($shop_followers[$single_id])) ? $shop_followers[$single_id]: array();
            }
            $response = $applane_service->getShopUsersFollowersInfoFromApplane($shop_user_id);
            $applane_shops = $this->prepareApplaneShopIds($response);
            $map_shops = $this->mapShops($applane_shops, $followers_final_array);
            if($map_shops == true){
                $unchecked = true;
            }
        }else{
           $is_local_users = 0; 
        }
        }while($is_local_users);
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
        $handler = $this->container->get('monolog.logger.applane_shops_user_followers_log');
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
        $applane_shops = array();
        $final_data = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
               //$applane_shops[] = $single_result->_id;
               $applane_shops['citizen_id'] = (isset($single_result->citizen_id->_id)) ? $single_result->citizen_id->_id : 0;
               $applane_shops['shop_id'] = (isset($single_result->shop_id->_id)) ? $single_result->shop_id->_id : 0;
               $final_data[$applane_shops['shop_id']][] = $applane_shops['citizen_id'];
            }
        }
        
        return $final_data;
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
        $matched_shops = array_intersect_key($local_shops, $applane_shops);
        $unmatched_shops = array_diff_key($local_shops, $applane_shops);
        $unmatched_shops = array();
        $data = array();
        foreach($matched_shops as $key => $value){
            
            $shop_id = $key;
            $unmatched_followers['shop_id'] = $shop_id;
            $unmatched_followers['unmatched_followers'] = array_diff($local_shops[$shop_id], $applane_shops[$shop_id]);
            $data[] = $unmatched_followers;
            if(count($unmatched_followers['unmatched_followers']) > 0){
            $unmatch_status = true;
            }
        }
        
        $this->__createLog('Local_Shop_Followers: '.json_encode($local_shops));
        $this->__createLog('Applane_Shop_Followers: '.json_encode($applane_shops));
        $this->__createLog('Not_Matched_Shop_Followers: '.json_encode($data));
        //$this->__createLog('Not_Matched_opsh_ids: '.json_encode($unmatched_users));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_shops)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_shops_user_followers_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for shop followers on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for shop followers on sixthcontinent database and Applane database.';
        $mail_sub = ($map_shops == true) ? 'Applane Shop followers Log [Unmatched found]' : 'Applane Shop followers Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
    
    
    /**
     * Get Shop Followers
     * @param array $shop_ids
     */
    public function getShopUserFollowers($shop_ids)
    {
        $em = $this->em;
        $shop_id_user_id = array();
        $shop_followers_array = $em->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->getShopFollowers($shop_ids);
       
        if($shop_followers_array){
            foreach($shop_followers_array as $shop_followers_single){
                $shop_id_user_id[] = $shop_followers_single['shopId']."_".$shop_followers_single['userId'];
            }
        }
       return $shop_id_user_id;
    }
    
    /**
     * Get Shop Followers
     * @param array $shop_ids
     */
    public function getShopFollowers($shop_ids)
    {
        $em = $this->em;
        $shop_followers_array = array();
        $shop_followers = array();
        $shop_followers_array = $em->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->getShopFollowers($shop_ids);
       
        if($shop_followers_array){
            foreach($shop_followers_array as $shop_followers_single){
                $shop_id = $shop_followers_single['shopId'];
                $shop_followers[$shop_id][] =  $shop_followers_single['userId'];
            }
        }
       return $shop_followers;
    }
}