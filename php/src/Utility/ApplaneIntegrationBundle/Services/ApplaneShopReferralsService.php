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
class ApplaneShopReferralsService {
    
    protected $em;
    protected $dm;
    protected $container;
    CONST store_media_path = '/uploads/documents/stores/gallery/';
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
    public function checkShopReferralsOnApplane($filter)
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
                    ->getRegistredShops($offset, $limit);
        $shops_count = count($shops);
        if($shops_count > 0){
            $shop_ids = array(); //initialise the array
            $shop_media_ids = array();
            foreach($shops as $shop){
                $shop_ids[] = (string)$shop['id'];
            }
            //get shop images
            $referrals_final_array = array();
            $referrals_final_array = $this->getShopReferrals($shop_ids);

            $offset = $offset + $limit;
            $applane_shops = array();
            $response = $applane_service->getShopsInfoFromApplane($shop_ids);
            $applane_shops = $this->prepareApplaneShopIds($response);
            
            $map_shops = $this->mapShops($applane_shops, $referrals_final_array);
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
        $handler = $this->container->get('monolog.logger.applane_shop_referrals_log');
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Get shops info from applane
     * @param array $response
     * @return type
     */
    public function prepareApplaneShopIds($response)
    {
        $total_applane_shops = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
               $applane_shops['id'] = (isset($single_result->_id)) ? $single_result->_id : 0;
               $applane_shops['referrals'] = (isset($single_result->referred_by_id->_id)) ? $single_result->referred_by_id->_id : '';
               $total_applane_shops[$single_result->_id] = $applane_shops['referrals'];
            }
        }
        
        return $total_applane_shops;
    }
    
    /**
     * Check mapped shops
     * @param array $applane_users
     * @param array $local_users
     * @return boolean
     */
    public function mapShops($applane_shops, $local_shops)
    {
        $unmatch_status = false;
        $unmatched_shops =array();
        $matched_shops = array_intersect_key($local_shops, $applane_shops);
        $unmatched_shops = array_diff_key($local_shops, $applane_shops);
        $data = array();
        foreach($matched_shops as $key => $value){
            $shop_id = $key;
          if($local_shops[$shop_id] != $applane_shops[$shop_id]){
            $unmatched_shop_referrals['shop_id'] = $shop_id;
            $unmatched_shop_referrals['unmatched_referrals'] = $local_shops[$shop_id];
            $data[] = $unmatched_shop_referrals;
            $unmatch_status = true;
          }
        }

        $this->__createLog('Local_Shop_Referrals: '.json_encode($local_shops));
        $this->__createLog('Applane_Shop_Referrals: '.json_encode($applane_shops));
        $this->__createLog('Not_Matched_Shop_Referrals: '.json_encode($data));
       // $this->__createLog('Not_Matched_Shop_ids: '.json_encode($unmatched_shops));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_shops)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_shop_referrals_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for shop referrals on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for shop referrals on sixthcontinent database and Applane database.';
        $mail_sub = ($map_shops == true) ? 'Applane Shop referrals Log [Unmatched found]' : 'Applane Shop referrals Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
    
    /**
     * Get shop images
     * @param array $shop_ids
     * @return array
     */
    public function getShopReferrals($shop_ids) {
        $em = $this->em;
        $shop_affiliates = array();
        $shop_affiliates_array = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')                   
                              ->getShopAffiliates($shop_ids);
        
        if($shop_affiliates_array){
            foreach($shop_affiliates_array as $shop_affiliates_single){
                $shop_id = $shop_affiliates_single['shopId'];
                $shop_affiliates[$shop_id] =  $shop_affiliates_single['fromId'];
            }
        }
       return $shop_affiliates;
    }

    /**
    * Function to retrieve s3 server base
    */
   public function getS3BaseUri() {
       //finding the base path of aws and bucket name
       $aws_base_path = $this->container->getParameter('aws_base_path');
       $aws_bucket    = $this->container->getParameter('aws_bucket');
       $full_path     = $aws_base_path.'/'.$aws_bucket;
       return $full_path;
   }
}