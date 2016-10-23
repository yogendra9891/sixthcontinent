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
class ApplaneUserFriendService {
    
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
    public function checkUsersFriendOnApplane($filter)
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
        $unchecked = false;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        do{
        $users = $em->getRepository('UserManagerSonataUserBundle:User')
                    ->getRegistredUsers($offset, $limit);
       
        $users_count = count($users);
        if($users_count > 0){
            $user_ids = array();
            $user_ids_array = array();
            foreach($users as $user){
                $user_ids[] = (string)$user['id'];
            }
            $offset = $offset + $limit;
            //get friends
            $friends = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->getRegistredUsersFriends($user_ids);
            $friend_array = $this->preapareFriendsArray($friends);
            $applane_users = array();
            $response = $applane_service->getUsersInfoFromApplane($user_ids);
            $applane_users = $this->prepareApplaneUserIds($response);
            $map_users = $this->mapUsers($applane_users, $friend_array);
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
        $handler = $this->container->get('monolog.logger.applane_usersfriend_log');
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
        $total_applane_users = array();
        if(count($response->response->result) > 0){
            foreach($response->response->result as $single_result){
                $friend_id = array();
               $applane_users['id'] = (isset($single_result->_id)) ? $single_result->_id : 0;
               $my_friends = (isset($single_result->my_friends)) ? $single_result->my_friends : array();
               foreach($my_friends as $my_friend){
                   $friend_id[] = (isset($my_friend->_id)) ? $my_friend->_id : 0;
               }
               $total_applane_users[$applane_users['id']] = $friend_id;
            }
        }
        return $total_applane_users;
    }
    
    /**
     * Check mapped users
     * @param array $applane_users
     * @param array $friend_array
     * @return boolean
     */
    public function mapUsers($applane_users, $friend_array)
    {
        $unmatch_status = false;
        $matched_users = array_intersect_key($friend_array, $applane_users);
        $unmatched_users = array_diff_key($friend_array, $applane_users);
        $unmatched_friends = array();
        $data = array();
        foreach($matched_users as $key => $value){
            
            $user_id = $key;
            $unmatched_friends['user_id'] = $user_id;
            $unmatched_friends['unmatched_friends'] = array_diff($friend_array[$user_id], $applane_users[$user_id]);
            $data[] = $unmatched_friends;
            if(count($unmatched_friends['unmatched_friends']) > 0){
            $unmatch_status = true;
            }
        }
        $this->__createLog('Local_User_Friends: '.json_encode($friend_array));
        $this->__createLog('Applane_User_Friends: '.json_encode($applane_users));
        $this->__createLog('Not_Matched_User_Friends: '.json_encode($data));
        $this->__createLog('Not_Matched_User_ids: '.json_encode($unmatched_users));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_users)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_usersfriend_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for user friends on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for user friends on sixthcontinent database and Applane database.';
        $mail_sub = ($map_users == true) ? 'Applane UserFriends Log [Unmatched found]' : 'Applane UserFriends Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
    
    /**
     * Prepare friend array
     * @param type $friends
     * @return boolean
     */
    public function preapareFriendsArray($friends)
    {
        $friend_array = array();
        if(count($friends) == 0){
            return $friend_array;
        }
        foreach($friends as $friend){
            $connect_from = $friend['connectFrom'];
            $connect_to = $friend['connectTo'];
            $friend_array[$connect_from][] = $connect_to;
            $friend_array[$connect_to][] = $connect_from;
        }
        return $friend_array;
    }
}