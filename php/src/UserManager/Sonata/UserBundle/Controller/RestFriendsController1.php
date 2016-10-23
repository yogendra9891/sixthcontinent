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
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use StoreManager\StoreBundle\Controller\ShoppingplusController;

class RestFriendsController extends FOSRestController {

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postSearchusersAction(Request $request) {
        //initilise the array
        $users_array = array();
        $data = array();

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
        
        //parameter check end
        
        //end to get request object
        //get user id
        $user_id = $de_serialize['user_id'];

        if ($user_id == "") {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }
        //check parameter friend name
        $friend_name = "";
        if(isset($de_serialize['friend_name'])){
        $friend_name = $de_serialize['friend_name'];
        }
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
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get entity manager object
        $dm = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $dm
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchByUsername($friend_name, $offset, $limit);
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($results as $result) {
            $user_id = $result->getId();
            $user_name = $result->getUsername();
            $user_email = $result->getEmail();
            $user_info = $user_service->UserObjectService($user_id);
            $users_array[] = array('user_id' => $user_id,'user_info'=>$user_info ,'user_name' => $user_name, 'user_email' => $user_email);
        }

        //fire the query in User Repository
        $results_count = $dm
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchByUsernameCount($friend_name);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('users' => $users_array, 'count' => $results_count));


        return $resp_data;
    }

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postSearchfriendsAction(Request $request) {
        //initilise the array
        $users_array = array();
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //parameter check end

        //get user id
        $user_id = $count_user_id = $de_serialize['user_id'];
        
        if($de_serialize['friend_name'] == ""){
         $friend_name = "";
        } else{
            $friend_name = $de_serialize['friend_name'];
        }
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
        
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get entity manager object
        $dm = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->searchFriendByUsername($user_id, $friend_name, $offset, $limit);

        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($results as $result) {
            $user_id = $result->getId();
            $user_name = $result->getUsername();
            $user_email = $result->getEmail();
            $user_info = $user_service->UserObjectService($user_id);
            $users_array[] = array('user_id' => $user_id,'user_info'=>$user_info ,'user_name' => $user_name, 'user_email' => $user_email);
        }

        //fire the query in User Repository
        $results_count = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->searchFriendByUsernameCount($count_user_id, $friend_name);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('users' => $users_array, 'count' => $results_count));


        return $resp_data;
    }

    /**
     * Send friend request.
     * @param Request $request
     * @return multitype:number string multitype: |multitype:string multitype:
     */
    public function postSendfriendrequestsAction(Request $request) {

        //initilise the array
        $users_array = array();
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object

        $connectFrom = $de_serialize['user_id'];

        if ($connectFrom == "") {
            $resp_data = array('code' => '111', 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $resp_data;
        }
        $connectTo = $de_serialize['friend_id'];


        if ($connectTo == "") {
            $resp_data = array('code' => '111', 'message' => 'FRIEND_ID_IS_REQUIRED', 'data' => array());
            return $resp_data;
        }

        $msg = $de_serialize['msg'];


        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($connectFrom);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //check if friend request is already sent to same user
        $is_friend_request_sent_alreay = $this->checkFriendRequest($connectFrom, $connectTo);

        if ($is_friend_request_sent_alreay) {
            $resp_data = array('code' => '109', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_SENT', 'data' => array());
            return $resp_data;
        }

        //check if friend request is already received
        $is_friend_request_received_alreay = $this->checkReceivedFriendRequest($connectFrom, $connectTo);

        if ($is_friend_request_received_alreay) {
            $resp_data = array('code' => '109', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_RECEIVED', 'data' => array());
            return $resp_data;
        }

        //get entity object
        $userConnection = new UserConnection();

        $userConnection->setConnectFrom($connectFrom);
        $userConnection->setConnectTo($connectTo);
        $userConnection->setMsg($msg);
        $userConnection->setStatus(0);


        $time = new \DateTime("now");
        $userConnection->setCreated($time);

        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($userConnection);
        $em->flush();

        $resp_data = array('code' => '101', 'message' => 'FRIEND_REQUEST_SENT', 'data' => array());
        return $resp_data;
    }

    /**
     * Check friend request if already sent to the same user from same user.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkFriendRequest($connectFrom, $connectTo) {
        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendRequest($connectFrom, $connectTo);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }

    /**
     * Check received friend request.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkReceivedFriendRequest($connectFrom, $connectTo) {
        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkReceivedFriendRequest($connectFrom, $connectTo);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }

    /**
     * Response friend request
     *
     */
    public function postResponsefriendrequestsAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
       //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id', 'action');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //parameter check end
        
        $user_id = $de_serialize['user_id'];
        $fid = $de_serialize['friend_id'];
        $action = $de_serialize['action']; //if 1 for accept, 0 for deny
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //check for request parameter
        $allowed_action = array(0, 1);
        if (!in_array($action, $allowed_action)) {
            $resp_data = array('code' => '110', 'message' => 'INVALID_ACTION_PARAMETER', 'data' => array());
            return $resp_data;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->responseFriendRequest($user_id, $fid, $action);

        if ($results) {

            if ($action == 1) {
                //get entity object
                $userConnection = new UserConnection();
                $msg = "request accepted";
                $userConnection->setConnectFrom($user_id);
                $userConnection->setConnectTo($fid);
                $userConnection->setMsg($msg);
                $userConnection->setStatus(1);


                $time = new \DateTime("now");
                $userConnection->setCreated($time);

                //get entity manager object
                $em = $this->getDoctrine()->getEntityManager();

                $em->persist($userConnection);
                $em->flush();
            }
            //friend request accepted or deny
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
            return $resp_data;
        }

        //error occured
        $resp_data = array('code' => '100', 'message' => 'ERROR_OCCURED', 'data' => array());
        return $resp_data;
    }

    /**
     * 
     */
    public function postViewprofilesAction(Request $request) {
        //initilise the data array
        $data = array();
        $is_friend = 0;
        $is_sent = 0;
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //parameter check end
        
        //get friend id
        $friend_id = $de_serialize['friend_id'];

        if ($friend_id == "") {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            return $res_data;
        }
        
        //check if friend is active or not
        $friend_user_check_enable = $this->checkActiveUserProfile($friend_id);

        if ($friend_user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get login user id
        $user_id = $de_serialize['user_id'];

        if ($user_id == "") {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            return $res_data;
        }
        //get usermanager object
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id' => $friend_id));

        //get user data

        $fuser_name = $user->getUsername();
        $fuser_email = $user->getEmail();
        $fuser_group = $user->getGroupNames();
        
        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();
         //fire the query in User Connection Repository
        $friend_check = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendShip($user_id, $friend_id);
        
        if($friend_check){
            $is_friend = $friend_check;
        }

        //check friend request
        //fire the query in User Connection Repository
        $friend_request_check = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendrequestsent($user_id, $friend_id);
        if($friend_request_check){
            $is_sent = $friend_request_check;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        $user_service = $this->get('user_object.service');
        $user_info_detail = array();
        $user_info_detail = $user_service->UserObjectService($friend_id);
        //create the user info array
        $user_info = array('user_id' => $friend_id,'user_info'=>$user_info_detail, 'user_email' => $fuser_email, 'user_name' => $fuser_name, 'user_group' => $fuser_group, 'is_friend'=>$is_friend, 'is_sent'=>$is_sent);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $user_info);

        return $resp_data;
        exit;
    }

    /**
     * Get pending friend request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postPendingfriendrequestsAction(Request $request) {
        //initilise the data array
        $users_array = array();

        //get request object
        //$request = $this->getRequest();

       //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        
        //parameter check end
        
        //get login user id
        $user_id = $de_serialize['user_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }

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
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        
        

        //get entity manager object
        $em = $this->getDoctrine()->getEntityManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->pendingFriendrequest($user_id, $offset, $limit);
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($results as $result) {
            $requset_id = $result->getId();

            $user_connect_from = $result->getConnectFrom();
            $user_info = $user_service->UserObjectService($user_connect_from);
            $users_array[] = array('request_id' => $requset_id, 'friend_id' => $user_connect_from,'user_info'=>$user_info);
        }
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $users_array);
        return $resp_data;
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

    /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if(!$user){
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user_check_enable;
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
     * 
     * @param type $trackid
     */
    public function trackemailAction($trackid)
    {
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
		->getRepository('NewsletterNewsletterBundle:Newslettertrack')
		->findOneByToken($trackid);
        
        $template_res->setOpenStatus(1);
        $em->flush();
        return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
    }
    
    /**
     * Get All connected profiles for that user id.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetconnectedprofilesAction(Request $request)
    {
        //initilise the data array
        $users_array = array();

       //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
         $user_id = $object_info->user_id;
        //get stores of the user
        //fire the query in User Connection Repository
        $em = $this->getDoctrine()->getEntityManager();

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getExternalProfileStores($user_id);
        foreach($stores as $store){
            //get store revenue
            $stores_revenue = $em
                ->getRepository('StoreManagerStoreBundle:Transactionshop')
                ->getShopsRevenue($store['id']);
             
            $stores_data[] = array('store'=>$store, 'revenue'=>$stores_revenue);
        }
        
       
        
        //get citizen info
        $citizen_profile = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->getExternalProfileCitizen($user_id);

         //get shopping plus class object
        $shoppingplus = new ShoppingplusController();
        $params = '{"idcard":"12350"}';
        $request->attributes->set('reqObj',$params);
        $response = $shoppingplus->cardsoldsAction($request);
        
        $decode_response = json_decode($response);
      
        $stato = $decode_response->data->stato;
        $descrizione = $decode_response->data->descrizione;
        $saldoc = $decode_response->data->saldoc;
        $saldorc = $decode_response->data->saldorc;
        $saldorm = $decode_response->data->saldorm;
        
        $citizen_data = array('citizen' =>$citizen_profile, 'stato' => $stato, 
            'description' => $descrizione, 'saldoc' =>$saldoc, 'saldorc' => $saldorc, 'saldorm' => $saldorm );
        
        $combine_data = array('store_profile' => $stores_data, 'citizen_profile'=>$citizen_data);
        
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $combine_data);
        return $resp_data;
        
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
}
