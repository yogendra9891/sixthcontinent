<?php
namespace Affiliation\AffiliationManagerBundle\Services;
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
use Utility\UtilityBundle\Utils\Utility;
use Affiliation\AffiliationManagerBundle\Document\InvitationSend;
// service method  class
class AffiliationService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
  

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
    
   /**
    * Check email ids that are already registerd
    * @param array $emails
    */
   public function checkRegisteredEmails($emails)
   {
       $this->__createLog('Enter In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [checkRegisteredEmails] With emails:'.Utility::encodeData($emails));
       $data = array();
       $users = array();
       $em = $this->em;
       //get user object that exist in fos_user_user
       if(count($emails) > 0){
       $users = $em->getRepository('UserManagerSonataUserBundle:User')
                   ->getRegistredUserEmails($emails);
       }
      
       if(!$users){
           $this->__createLog('Exiting from class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [checkRegisteredEmails] With registerd emails:'.Utility::encodeData($data));
           return $data; //no registerd emails found
       }
       //get list of registerd emails
       foreach($users as $user){
           $data[] = $user['email']; //return registered emails
       }
      $this->__createLog('Exiting from class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [checkRegisteredEmails] With registerd emails:'.Utility::encodeData($data));
      return $data;
   }
   
    /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLog($monolog_req, $monolog_response = array()){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.affiliation_log');
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     *  function for adding the invitation list
     * @param type $from_id
     * @param type $email_ids
     * @param type $affiliation_type
     * @return boolean
     */
    public function addInvitationSend($from_id,$email_ids,$affiliation_type){
        $this->__createLog('Enter In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [addInvitationSend] With emails:'.Utility::encodeData($email_ids)."and from_id:".$from_id."and affiliation_type".$affiliation_type);
        $from_id = $from_id;
        $email_ids = $email_ids;
        $affiliation_type = $affiliation_type;
        $registered_emails = array();
        $registered_ids = array();
        //get the list of email ids who is already affiliated
        $already_affiliated_users = $this->getAlreadyAffiliatedUsers($from_id,$email_ids,$affiliation_type);
        $this->__createLog('In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [addInvitationSend] and already affiliated emails are :'.Utility::encodeData($already_affiliated_users));
        $user_affiliate_first_time = array_diff($email_ids, $already_affiliated_users);
        //get the lisy of all the already registered email users
        $registerd_emails_ids = $this->getRegisteredEmailsAndIds($user_affiliate_first_time);
        $this->__createLog('In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [addInvitationSend] and registered emails are :'.Utility::encodeData($registerd_emails_ids));
        foreach($registerd_emails_ids as $registered_info) {
            $registered_emails[] = $registered_info['email'];
            $registered_ids[] = $registered_info['id'];
        }
        //save already registered users 
        $this->saveInvitationSend($from_id,$registered_emails,$affiliation_type,2);
        //save non registered users 
        $save_affiliate_users = array_diff($user_affiliate_first_time, $registered_emails);
        $this->__createLog('In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [addInvitationSend] and user who are invited first time :'.Utility::encodeData($save_affiliate_users));
        $this->saveInvitationSend($from_id,$save_affiliate_users,$affiliation_type,0);
        return true;
    }
    
    
    /**
     *  function for getting the list of all the already affiliated users 
     * @param type $from_id
     * @param type $email_ids
     * @param type $affiliation_type
     * @return type
     */
    public function getAlreadyAffiliatedUsers($from_id,$email_ids,$affiliation_type) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->getAlreadyAffiliatedUsers($from_id,$email_ids,$affiliation_type);
        //calling the function for updating the affiliation count for the affiliated users 
        $already_affiliated_users = $this->updateAffiliationCount($affiliated_users);
        return $already_affiliated_users;
    }
    
    /**
     *  function for saving the invitation send list
     * @param type $from_id
     * @param type $email_ids
     * @param type $affiliation_type
     * @param type $status
     */
    public function saveInvitationSend($from_id,$email_ids,$affiliation_type,$status=0) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $time  = new \DateTime("now");
        foreach($email_ids as $email) {
            $invitation_send = new InvitationSend();
            $invitation_send->setFromId($from_id);
            $invitation_send->setEmail($email);
            $invitation_send->setStatus($status);
            $invitation_send->setAffiliationType($affiliation_type);
            $invitation_send->setCount(1);
            $invitation_send->setCreatedAt($time);
            $invitation_send->setUpdatedAt($time);
            $dm->persist($invitation_send);
            
        }
        $dm->flush();      
    }
    
    /**
     *  function for updating the affiliation count 
     * @param type $affiliated_users
     * @return type
     */
    public function updateAffiliationCount($affiliated_users) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $users_array = array();
        $time  = new \DateTime("now");
        //loop for the affiliated users
        foreach($affiliated_users as $affiliated_user) {
            $affiliation_count = $affiliated_user->getCount();
            $users_array[] = $affiliated_user->getEmail();
            $new_count = $affiliation_count + 1;
            $affiliated_user->setCount($new_count);
            $affiliated_user->setUpdatedAt($time);
            $dm->persist($affiliated_user);           
        }
        $dm->flush();
        return $users_array;
    }
    
    /**
    * Check email ids that are already registerd
    * @param array $emails
    */
   public function getRegisteredEmailsAndIds($emails)
   {
       $this->__createLog('Enter In class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [getRegisteredEmailsAndIds] With emails:'.Utility::encodeData($emails));
       $data = array();
       $users = array();
       $em = $this->em;
       //get user object that exist in fos_user_user
       if(count($emails) > 0){
       $users = $em->getRepository('UserManagerSonataUserBundle:User')
                   ->getRegistredUserEmails($emails);
       }

       if(!$users){
           $this->__createLog('Exiting from class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [getRegisteredEmailsAndIds] With registerd emails:'.Utility::encodeData($data));
           return $data; //no registerd emails found
       }
       //get list of registerd emails
       foreach($users as $user){
           $data[$user['email']]['email'] = $user['email']; //return registered emails
           $data[$user['email']]['id'] = $user['id']; //return registered emails
       }
      $this->__createLog('Exiting from class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [getRegisteredEmailsAndIds] With registerd emails:'.Utility::encodeData($data));
      return $data;
   }
   
   
   public function updateAffiliationStatusForEmailId($from_id,$email_id,$status) {
       $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $affiliated_users = $dm->getRepository('AffiliationAffiliationManagerBundle:InvitationSend')
                   ->findBy(array('email' => $email_id));
        
        $time  = new \DateTime("now");
        //loop for the affiliated users
        foreach($affiliated_users as $affiliated_user) {
            $affiliation_status = 2;
            $affiliation_from = $affiliated_user->getFromId();
            if($affiliation_from == $from_id) {
                $affiliation_status = 1;
            }
            $affiliated_user->setStatus($affiliation_status);
            $affiliated_user->setUpdatedAt($time);
            $dm->persist($affiliated_user);           
        }
        try{
            $dm->flush();
        } catch (\Exception $ex) {
            $this->__createLog('Exiting from class [Affiliation\AffiliationManagerBundle\Services\AffliationService] function [updateAffiliationStatusForEmailId] With exception:'.$ex->getMessage());
        }        
   }
   
   /**
    * function for saving the affiliated users to the Invitationsend collection
    * @param type $results
    * @param type $status
    */
   public function saveAffiliatedUsers($results,$status) {
       $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
       $time = new \DateTime('now');
       foreach($results as $result) {
           if($result['from_id'] != '' && $result['email'] != ''){
           $invite_mail = new InvitationSend();
           $invite_mail->setAffiliationType(1);
           $invite_mail->setCount(1);
           $invite_mail->setFromId($result['from_id']);
           $invite_mail->setEmail($result['email']);
           $invite_mail->setStatus(1);
           $invite_mail->setCreatedAt($time);
           $invite_mail->setUpdatedAt($time);
           $dm->persist($invite_mail);
           }           
       }
       
       try{
          $dm->flush(); 
       } catch (\Exception $ex) {
          echo "Exception Occured while saving :".$ex->getMessage();
       }
   }

}