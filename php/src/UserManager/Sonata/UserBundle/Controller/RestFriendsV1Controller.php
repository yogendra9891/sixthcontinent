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
use Notification\NotificationBundle\Document\UserNotifications;
use UserManager\Sonata\UserBundle\Entity\UserFollowers;
use UserManager\Sonata\UserBundle\Document\Group;

class RestFriendsV1Controller extends FOSRestController {

    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $request_type_val = 1;





    public function get_all_data($item2, $key) {
        echo "$key. $item2<br />\n";
        die;
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
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //get user id
        $user_id = $count_user_id = $de_serialize['user_id'];

        if ($de_serialize['friend_name'] == "") {
            $friend_name = "";
        } else {
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
            return array('code' => 1002, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 1003, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get entity manager object
        $dm = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $friendsIds = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriends($user_id, $friend_name, $offset, $limit);
        $user_service = $this->get('user_object.service');
        $results = array();
        if(!empty($friendsIds)){
            $results = $user_service->MultipleUserObjectService($friendsIds);
        }
        $user_info = array();
        foreach ($results as $result) {
            $users_array[] = array('user_id' => $result['id'], 'user_info' => $result, 'user_name' => $result['username'], 'user_email' => $result['email']);
        }

        //fire the query in User Repository
        $results_count = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriendsCount($user_id, $friend_name);

        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('users' => $users_array, 'count' => $results_count));

        echo json_encode($resp_data);
        exit();
    }



    /**
     * Check received friend request.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkReceivedFriendRequest($connectFrom, $connectTo, $reqTypeArr) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkReceivedFriendRequest($connectFrom, $connectTo, $reqTypeArr);

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
    public function checkReceivedFriendRequestStatus($connectFrom, $connectTo, $reqTypeArr) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkReceivedFriendRequestStatus($connectFrom, $connectTo, $reqTypeArr);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }



    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $msgtype, $msg) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId(0);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * send email for notification on activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body) {
        $userManager = $this->getUserManager();
        $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
        $to_user = $userManager->findUserBy(array('id' => (int) $to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($sixthcontinent_admin_email)
                ->setTo(array($to_user->getEmail()))
                ->setBody($mail_body, 'text/html');

        if ($this->container->get('mailer')->send($notification_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
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
        if (!$user) {
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
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }

    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }
    

}
