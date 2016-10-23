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
class ApplaneUserReferralsService {
    
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
    public function checkUserReferralsOnApplane($filter)
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
            //get referrals
            $referrals = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                    ->findBy(array('toId'=>$user_ids));
            $referral_array = $this->preapareReferralArray($referrals);
            $applane_users = array();
            $response = $applane_service->getUsersInfoFromApplane($user_ids);
            $applane_users = $this->prepareApplaneUserIds($response);
            $map_users = $this->mapUsers($applane_users, $referral_array);
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
        $handler = $this->container->get('monolog.logger.applane_usersreferral_log');
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
               $applane_users['id'] = (isset($single_result->_id)) ? $single_result->_id : 0;
               $my_referral = (isset($single_result->refferred_by->_id)) ? $single_result->refferred_by->_id : '';
               $total_applane_users[$applane_users['id']] = $my_referral;
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
        $unmatched_shops =array();
        $matched_shops = array_intersect_key($local_users, $applane_users);
        $unmatched_shops = array_diff_key($local_users, $applane_users);
        $data = array();
        foreach($matched_shops as $key => $value){
            $user_id = $key;
          if($local_users[$user_id] != $applane_users[$user_id]){
            $unmatched_user_referrals['user_id'] = $user_id;
            $unmatched_user_referrals['unmatched_referrals'] = $local_users[$user_id];
            $data[] = $unmatched_user_referrals;
            $unmatch_status = true;
          }
        }

        $this->__createLog('Local_User_Referrals: '.json_encode($local_users));
        $this->__createLog('Applane_User_Referrals: '.json_encode($applane_users));
        $this->__createLog('Not_Matched_User_Referrals: '.json_encode($data));
       // $this->__createLog('Not_Matched_Shop_ids: '.json_encode($unmatched_shops));
        return $unmatch_status;
    }
    
    /**
     * Send Mail
     */
    public function sendMail($map_users)
    {
        $date = new \DateTime();
        $log_date_format = $date->format('Y-m-d');
        $path_to_log_directory = __DIR__ . "/../../../../app/logs/applane_usersreferral_log-".$log_date_format.".log";
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $log_owner_email = $this->container->getParameter('log_owner_email');
        $receivers = $log_owner_email;
        $bodyData = 'Log File for user referral on sixthcontinent database and Applane database.';
        $mail_body = 'Log File for user referral on sixthcontinent database and Applane database.';
        $mail_sub = ($map_users == true) ? 'Applane UserReferrlas Log [Unmatched found]' : 'Applane UserFriends Log [No unmatched found]';
        $file = $path_to_log_directory;
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $mail_sub, '', 'Log', $file, null, 1);
        return true;
    }
    
    /**
     * Prepare referrals array
     * @param type $referrals
     * @return boolean
     */
    public function preapareReferralArray($referrals)
    {
        $citizen_referrals = array();
        if(count($referrals) == 0){
            return $citizen_referrals;
        }
        foreach($referrals as $referral){
            $to_id = $referral->getToId();
            $citizen_referrals[$to_id] =  $referral->getFromId();
        }
        return $citizen_referrals;
    }
}