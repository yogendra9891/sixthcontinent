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
class ApplaneUserProfileService {
    
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
    public function checkUsersProfileOnApplane($filter)
    {   
        $users = $this->getAllLocalUsers($filter);   
    }
    
    /**
     * Get All local users
     */
    public function getAllLocalUsers($filter)
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
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        do{
        $users = $em->getRepository('UserManagerSonataUserBundle:User')
                    ->getRegistredUsersProfile($offset, $limit);
       
        $users_count = count($users);
        if($users_count > 0){
            $user_ids = array();
            $user_ids_array = array();
            foreach($users as $user){
                $user_ids[] = (string)$user['id'];
                $user_ids_array[$user['id']] = $user;
            }
            $offset = $offset + $limit;
            $applane_users = array();
            $response = $applane_service->getUsersInfoFromApplane($user_ids);
            $applane_users = $this->prepareApplaneUserIds($response);
            $map_users = $this->mapUsers($applane_users, $user_ids_array);
            if($map_users == true){
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
        $handler = $this->container->get('monolog.logger.applane_usersprofile_log');
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Get User Ids from applane
     * @param array $response
     * @return type
     */
    public function prepareApplaneUserIds($response)
    {
        $applane_users = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
               $applane_users['id'] = (isset($single_result->_id)) ? $single_result->_id : 0;
               $applane_users['name'] = (isset($single_result->name)) ? $single_result->name : '';
               $applane_users['address_l1'] = (isset($single_result->address_l1)) ? $single_result->address_l1 : '';
               $applane_users['address_l2'] = (isset($single_result->address_l2)) ? $single_result->address_l2 : '';
               $applane_users['email_id'] = (isset($single_result->email_id)) ? $single_result->email_id : '';
               $applane_users['latitude'] = (isset($single_result->latitude)) ? $single_result->latitude : 0;
               $applane_users['longitude'] = (isset($single_result->longitude)) ? $single_result->longitude : 0;
               $applane_users['sex'] = (isset($single_result->sex)) ? $single_result->sex : '';
               $applane_users['city'] = (isset($single_result->city)) ? $single_result->city : '';
               $applane_users['country'] = (isset($single_result->country->countryname)) ? $single_result->country->countryname : '';
               $total_applane_users[$single_result->_id] = $applane_users;
            }
        }
        return $total_applane_users;
    }
    
    /**
     * Check mapped users
     * @param array $applane_users
     * @param array $local_users
     * @return boolean
     */
    public function mapUsers($applane_users, $local_users)
    {
       $unmatch_status = false;
       $not_exist_users = array();
       $log_array = array();
       $unmatched_ids = array();
       //find matched keys
       $matched_keys = array_intersect_key($local_users, $applane_users);
       $unmatched_keys = array_diff_key($local_users, $applane_users);
       foreach($unmatched_keys as $unmatched_key){
           $unmatched_ids[] = $unmatched_key['id'];
       }
       $unmatched_ids = array_unique($unmatched_ids);
        foreach($matched_keys as $matched_key){
            $user_id = $matched_key['id'];
            if($local_users[$user_id]['id'] == $applane_users[$user_id]['id']){
                //user exist. Check for internal value
                $local_user_info = $local_users[$user_id];
                $applane_user_info = $applane_users[$user_id];
                $log['id'] = $user_id;
                if($applane_user_info['country']=='Italy'){
                    $applane_user_info['country'] = 'IT';
                }
                if($applane_user_info['country']=='United States of America'){
                    $applane_user_info['country'] = 'US';
                }
                if(str_replace(' ', '', $local_user_info['name']) != str_replace(' ', '',$applane_user_info['name'])){
                    $log['local_name'] = $local_user_info['name'];
                    $log['appalne_name'] = $applane_user_info['name'];
                    $log['name'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['address_l1'] != $applane_user_info['address_l1']){
                    $log['local_address_l1'] = $local_user_info['address_l1'];
                    $log['appalne_address_l1'] = $applane_user_info['address_l1'];
                    $log['address_l1'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['address_l2'] != $applane_user_info['address_l2']){
                    $log['local_address_l2'] = $local_user_info['address_l2'];
                    $log['appalne_address_l2'] = $applane_user_info['address_l2'];
                    $log['address_l2'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['email_id'] != $applane_user_info['email_id']){
                    $log['local_email_id'] = $local_user_info['email_id'];
                    $log['appalne_email_id'] = $applane_user_info['email_id'];
                    $log['email_id'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['sex'] != $applane_user_info['sex']){
                     $log['local_sex'] = $local_user_info['sex'];
                    $log['appalne_sex'] = $applane_user_info['sex'];
                    $log['sex'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['latitude'] != $applane_user_info['latitude']){
                     $log['local_latitude'] = $local_user_info['latitude'];
                    $log['appalne_latitude'] = $applane_user_info['latitude'];
                    $log['latitude'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['longitude'] != $applane_user_info['longitude']){
                    $log['local_longitude'] = $local_user_info['longitude'];
                    $log['appalne_longitude'] = $applane_user_info['longitude'];
                    $log['longitude'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['city'] != $applane_user_info['city']){
                    $log['local_city'] = $local_user_info['city'];
                    $log['appalne_city'] = $applane_user_info['city'];
                    $log['city'] ='Note matched';
                    $unmatch_status = true;
                }
                if($local_user_info['country'] != $applane_user_info['country']){
                    $log['local_country'] = $local_user_info['country'];
                    $log['appalne_country'] = $applane_user_info['country'];
                    $log['country'] ='Note matched';
                    $unmatch_status = true;
                }
                $log_array[] = $log;
            }else{
                //user not exist
                $not_exist_users[] = $user_id;
            }
        }
        //$this->__createLog('Local_User_id: '.json_encode($local_users));
        //$this->__createLog('Applane_User_id: '.json_encode($applane_users));
        $this->__createLog('Users_Missing_Info: '.json_encode($log_array));
        $this->__createLog('Not_Matched_User_id: '.json_encode($unmatched_ids));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_users)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_usersprofile_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for user profile on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for user profile on sixthcontinent database and Applane database.';
        $mail_sub = ($map_users == true) ? 'Applane UserProfile Log [Unmatched found]' : 'Applane UserProfile Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
}