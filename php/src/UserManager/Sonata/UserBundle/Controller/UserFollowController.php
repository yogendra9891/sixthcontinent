<?php

namespace UserManager\Sonata\UserBundle\Controller;

use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use UserManager\Sonata\UserBundle\Entity\UserFollowers;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class UserFollowController extends FOSRestController {
    
    /**
     * Follow the user
     * @param Request $request
     * @return array;
     */
    public function postFollowusersAction(Request $request)
    {
         //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('sender_id', 'to_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $senderId = $object_info->sender_id;
        $toId = $object_info->to_id;
        
        
        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user has already connected
        $user_con = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->findOneBy(array('senderId' => $senderId, 'toId'=> $toId));
         
        if($user_con){
           $res_data = array('code' => '151', 'message' => 'ALREADY_FOLLOWED', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
        $userfollow->setSenderId($senderId);
        $userfollow->setToId($toId);
        $time = new \DateTime('now');
        $userfollow->setCreatedAt($time);
        $em->persist($userfollow);
        $em->flush();
        
        //update to applane
        $appalne_data = $de_serialize;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.follow', $event);
        //end of update
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
    }
    
    /**
     * Follow the user
     * @param Request $request
     * @return array;
     */
    public function postUnfollowusersAction(Request $request)
    {
         //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        $friendId = $object_info->friend_id;
        
        
        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user has already connected
        $user_con = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->findOneBy(array('senderId' => $userId, 'toId'=> $friendId));
         
        if(!$user_con){
           $res_data = array('code' => '151', 'message' => 'ALREADY_UNFOLLOWED', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
       
        //remove the connection
        $em->remove($user_con);
        $em->flush();
        
        //update to applane
        $appalne_data = $de_serialize;
        //get dispatcher object
        $event      = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.unfollow', $event);
        //end of update
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
    }
    
    
    /**
     * Get the followers
     * @param Request $request
     * @return array;
     */
    public function postGetfollowersAction(Request $request)
    {
         //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
        $offset = $de_serialize['limit_start'];
        $limit = $de_serialize['limit_size'];

        //set dafault limit
        if ($limit == "") {
            $limit = 20;
        }

        //set default offset
        if ($offset == "") {
            $offset = 0;
        }
        }else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        
        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
         //check if user has already connected
        $user_cons_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowersCount($userId);
        
        //check if user has already connected
        $user_cons = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowers($userId, $offset, $limit);
         
        if(!$user_cons){
           $res_data = array('code' => '100', 'message' => 'NO_RESULT', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
       
        //get each user id
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach($user_cons as $user_con){
            $user_id = $user_con['id'];
            $user_info = $user_service->UserObjectService($user_id);
            $users_array[] = array('user_id' => $user_id,'user_info'=>$user_info);
        }
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('followers'=>$users_array,'size'=>$user_cons_count));
        echo json_encode($res_data);
        exit;
    }
    
    
    /**
     * Get the followers
     * @param Request $request
     * @return array;
     */
    public function postGetfollowingsAction(Request $request)
    {
         //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
        $offset = $de_serialize['limit_start'];
        $limit = $de_serialize['limit_size'];

        //set dafault limit
        if ($limit == "") {
            $limit = 20;
        }

        //set default offset
        if ($offset == "") {
            $offset = 0;
        }
        }else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        
        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
         //check if user has already connected
        $user_cons_count = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowingsCount($userId);
        
        //check if user has already connected
        $user_cons = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->getFollowings($userId, $offset, $limit);
         
        if(!$user_cons){
           $res_data = array('code' => '100', 'message' => 'NO_RESULT', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
       
        //get each user id
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach($user_cons as $user_con){
            $user_id = $user_con['id'];
            $user_info = $user_service->UserObjectService($user_id);
            $users_array[] = array('user_id' => $user_id,'user_info'=>$user_info);
        }
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('followers'=>$users_array,'size'=>$user_cons_count));
        echo json_encode($res_data);
        exit;
    }
    
    /**
     * Check if user has already followed
     * @param Request $request
     * @return array;
     */
    public function postCheckfollowAction(Request $request)
    {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('sender_id', 'to_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $senderId = $object_info->sender_id;
        $toId = $object_info->to_id;
        
        
        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user has already connected
        $user_con = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->findOneBy(array('senderId' => $senderId, 'toId'=> $toId));
         
        if($user_con){
           $res_data = array('code' => '151', 'message' => 'ALREADY_FOLLOWED', 'data' => array('is_follow'=>'1'));
           echo json_encode($res_data);
           exit;
        }
         $res_data = array('code' => '152', 'message' => 'NO_FOLLOWED', 'data' => array('is_follow'=>'0'));
           echo json_encode($res_data);
           exit;
    }
    
      /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }
    
    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
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
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->encode($req_obj, 'json');
        return $jsonContent;
    }
    
}