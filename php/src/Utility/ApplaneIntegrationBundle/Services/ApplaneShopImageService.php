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
class ApplaneShopImageService {
    
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
    public function checkShopsImageOnApplane($filter)
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
            $shop_media_ids = array();
            foreach($shops as $shop){
                $shop_ids[] = (string)$shop['id'];
                $shop_media_ids[] = $shop['storeImage'];
            }
            //get shop images
            $image_final_array = array();
            $image_array = $this->getShopImages($shop_media_ids);

            foreach($shops as $shop){
                $single_id = $shop['id'];
                $image_final_array[$single_id] = (isset($image_array[$single_id])) ? $image_array[$single_id]: array('id'=>$single_id,'image'=>'');
            }
            $offset = $offset + $limit;
            $applane_shops = array();
            $response = $applane_service->getShopsInfoFromApplane($shop_ids);
            $applane_shops = $this->prepareApplaneShopIds($response);
            
            $map_shops = $this->mapShops($applane_shops, $image_final_array);
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
        $handler = $this->container->get('monolog.logger.applane_shopsimage_log');
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
               $applane_shops['image'] = (isset($single_result->shop_thumbnail_img)) ? $single_result->shop_thumbnail_img : '';
               $total_applane_shops[$single_result->_id] = $applane_shops;
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
        $unmatched_users =array();
        $matched_users = array_intersect_key($local_shops, $applane_shops);
        $unmatched_users = array_diff_key($local_shops, $applane_shops);
        $data = array();
        foreach($matched_users as $key => $value){
            $shop_id = $key;
          if($local_shops[$shop_id]['image'] != $applane_shops[$shop_id]['image']){
            $unmatched_shop_image['shop_id'] = $shop_id;
            $unmatched_shop_image['unmatched_image'] = $local_shops[$shop_id]['image'];
            $data[] = $unmatched_shop_image;
            $unmatch_status = true;
          }
        }
        $users_images = array();
//        foreach($local_users as $key => $value){
//            $users_images[$key][] = array('id' => $value['id'], 'thumb_image' => $value['profile_image_thumb']) ;
//        }
        $this->__createLog('Local_Shop_Image: '.json_encode($local_shops));
        $this->__createLog('Applane_Shop_Image: '.json_encode($applane_shops));
        $this->__createLog('Not_Matched_Shop_Images: '.json_encode($data));
        $this->__createLog('Not_Matched_Shop_ids: '.json_encode($unmatched_users));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_shops)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_shopsimage_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for shop profile image on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for shop  profile image on sixthcontinent database and Applane database.';
        $mail_sub = ($map_shops == true) ? 'Applane Shop Profile Image Log [Unmatched found]' : 'Applane Shop Profile Image Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
    
    /**
     * Get shop images
     * @param array $shop_ids
     * @return array
     */
    public function getShopImages($shop_media_ids) {
        $em = $this->em;
        $shop_image_array = array();
        $store_profile_images = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->findBy(array('id' => $shop_media_ids));
        if ($store_profile_images) {
            foreach ($store_profile_images as $store_profile_image) {
                $album_id = $store_profile_image->getAlbumId();
                $image_name = $store_profile_image->getImageName();
                $current_store_id = $store_profile_image->getStoreId();
                if (!empty($album_id)) {
                    $store_profile_image_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/original/' . $album_id . '/' . $image_name;
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/thumb/' . $album_id . '/' . $image_name;
                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/thumb/' . $album_id . '/coverphoto/' . $image_name;
                } else {
                    $store_profile_image_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/original/' . $image_name;
                    $store_profile_image_thumb_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/thumb/' . $image_name;
                    $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . self::store_media_path . $current_store_id . '/thumb/coverphoto/' . $image_name;
                }
                
                $shop_image['id'] = $current_store_id;
                $shop_image['image'] = $store_profile_image_thumb_path;
                $shop_image_array[$current_store_id] = $shop_image;
            }
        }
         return $shop_image_array;
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