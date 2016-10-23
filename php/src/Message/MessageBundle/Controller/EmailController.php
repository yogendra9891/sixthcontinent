<?php

namespace Message\MessageBundle\Controller;

use Notification\NotificationBundle\Model\INotification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Message\MessageBundle\MessageMessageBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sonata\UserBundle\Admin\Model as ald;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use FOS\UserBundle\Entity\UserManager;
use Message\MessageBundle\Document\Email;

/**
 * Define the email class for sending the email
 * @author admin
 *
 */
class EmailController extends Controller
{
     protected $miss_param = '';
     
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
     * 
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request)
    {
         $content = $request->getContent();
         $dataer = (object)$this->decodeData($content);

         $app_data = $dataer->reqObj;
         $req_obj = $app_data; 
         return $req_obj;
    }
    /**
     * sending the mail.
     * @param Request object
     * @return int
     */
    public function postSendemailsAction(Request $request)
    {
    	try {
                //initilise the data array
                $data = array();
                //Code repeat start
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeData($freq_obj);

                if(isset($fde_serialize)){
                    $de_serialize = $fde_serialize;
                }else{
                    $de_serialize = $this->getAppData($request);
                }
                //Code repeat end
                
                //Code end for getting the request
                $object_info = (object) $de_serialize; //convert an array into object.

                $required_parameter = array('session_id','recipient');
                //checking for parameter missing.
                $chk_error = $this->checkParamsAction($required_parameter, $object_info);
                if ($chk_error) {
                        return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
                }
                $mail_sub = (isset($de_serialize['title'])? $de_serialize['title']:'');
                $mail_body = (isset($de_serialize['body'])? $de_serialize['body']:'');                
                $mail_to_id = $de_serialize['recipient'];
                $mail_from_id = $de_serialize['session_id'];
                
                $userManager = $this->container->get('fos_user.user_manager');
                $user_to = $userManager->findUserBy(array('id' => $mail_to_id));
                $user_from = $userManager->findUserBy(array('id' => $mail_from_id));
                     
                if($user_from=='')
                {            
                    $data[] = "SENDER_ID_IS_INVALID";
                }
                if($user_to=='')
                {            
                    $data[] = "RECIPIENT_ID_IS_INVALID";
                }
                if(!empty($data))
                {
                    return array('code'=>100, 'message'=>'FAILURE','data'=>$data); 
                }
                $sixthcontinent_admin_email = 
                array(
                    $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
                );
                $message = \Swift_Message::newInstance()
    		->setSubject($mail_sub)
    		->setFrom($sixthcontinent_admin_email)
    		->setTo(array($user_to->getEmail()))
    		->setBody($mail_body, 'text/html');
                
    		//getting the container defined in current bundle main file
    		$container = MessageMessageBundle::getContainer();
    		if ($container->get('mailer')->send($message)){
                    $dm_email = $container->get('doctrine.odm.mongodb.document_manager');        
                    $email = new Email();
                    $email->setSenderId($mail_from_id);
                    $email->setReceiverId($mail_to_id);
                    $email->setBody($mail_body);
                    $email->setSubject($mail_sub);
                    $email->setCreatedAt(time());
                    $dm_email->persist($email);
                    $dm_email->flush();
                    return array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
                }else{
                    return array('code'=>105, 'message'=>'FAILURE','data'=>$data); 
                }    			
    		
    	} catch (\Exception $e) {
    		return $e->getMessage();
    	}

    }
      /**
    * Functionality decoding data
    * @param json $object	
    * @return array
    */
      public function decodeData($req_obj)
    {
         //get serializer instance
         $serializer = new Serializer(array(), array(
                         'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                         'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
         ));
         $jsonContent = $serializer->decode($req_obj, 'json');
         return $jsonContent;
    }
}
