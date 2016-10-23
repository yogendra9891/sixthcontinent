<?php

namespace Utility\RatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentsMedia;
use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * change the privacy setting
 */
class PrivacyController extends Controller
{
    protected $miss_param = '';
    protected $privacy_type_item = array('dashboard_post');
    protected $privacy_setting_numbers = array(1,2,3);
    
    public function indexAction($name)
    {
        return $this->render('UtilityRatingBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * set the chnage privcy setting
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postChangeprivacysAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'type', 'type_id', 'privacy_setting');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($data);
            exit;
        }
        
        //extract parameters.
        $item_type = $object_info->type;
        $item_id   = $object_info->type_id;
        $privacy_setting = $object_info->privacy_setting;
        $user_id   = $object_info->user_id;
        
        //check for rating type
        if (!in_array($item_type, $this->privacy_type_item)) {
            return array('code' => 176, 'message' => 'PRIVACY_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }
        
        //check for privacy setting..
        if (!in_array($privacy_setting, $this->privacy_setting_numbers)) {
            return array('code' => 177, 'message' => 'PRIVACY_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }
        switch ($item_type) {
            case 'dashboard_post':
                $this->changeDashboardPostPrivacy($item_type, $item_id, $privacy_setting, $user_id);
                break;
            
        }
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
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
     * change the dashboard post privacy setting.
     * @param string $item_type
     * @param string $item_id
     * @param int $privacy_setting
     * @param int $user_id
     */
    private function changeDashboardPostPrivacy($item_type, $item_id, $privacy_setting, $user_id)
    {
        //(previously)1 for public, 2 for friend, 3 for private(only me)
        //(currently) 1 for personal post , 2 for professional post , 3 for public
        $data = array();
        //check for privacy setting value for personal friend(personal and public) post
        $allow_personal_friend_privacy_setting = array(); 
        //check for privacy setting value for professional friend(professional and public) post
        $allow_professional_friend_privacy_setting = array();
        //check for privacy setting value for self post
        $allow_self_privacy_setting = array('1','2','3');
        $allow_other_user_wall_privacy_setting = array('3');
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        
        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                       ->find($item_id);
        if (!$post_res) {
            $response_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $to_id = $post_res->getToId();
        $post_owner_id = $post_res->getUserId();
        
        if ($user_id != $post_owner_id) {
            $response_data = array('code' => 500, 'message' => 'PERMISSION_DENIED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        // if user post on his own wall or Dashboard
        if ($user_id == $to_id){
          $allow_privacy_setting = $allow_self_privacy_setting; 
        } else { //if user post on other user wall
          $allow_privacy_setting = $this->checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id);
        }
        
        if (!in_array($privacy_setting, $allow_privacy_setting)) {
            $response_data = array('code' => 153, 'message' => 'INVALID_PRIVACY_SETTING', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $post_res->setprivacySetting($privacy_setting);
        try {
            $dm->persist($post_res);
            $dm->flush();            
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $data = array('privacy_setting'=>(int)$privacy_setting);
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }
    
    /**
     * check friendship(personal, professional) for a user.
     * @param array $allow_personal_friend_privacy_setting
     * @param array $allow_professional_friend_privacy_setting
     * @param array $allow_other_user_wall_privacy_setting
     * @param int $user_id
     * @param int $to_id
     * @return array $allow_privacy_setting
     */
    public function checkFriendshipType($allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting, $allow_other_user_wall_privacy_setting, $user_id, $to_id){
        
        // checking type of friend ship (personal or professional type of friend)
        // personal firend can submit two types of post (personal as well as public post)
        // professional firend can submit two types of post (professional as well as public post)
        //get entity manager object
           $em = $this->getDoctrine()->getManager();
            $friends_results = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                   ->checkPersonalProfessionalFriendship($user_id, $to_id);
            foreach ($friends_results as $friends_result) {
                $status = $friends_result['status'];
                switch ($status) {
                    case 1 :
                        $allow_personal_friend_privacy_setting = array(1, 3); //personal and public
                        break;
                    case 2 :
                        $allow_professional_friend_privacy_setting = array(2, 3); //professional and public
                        break;
                }
            }
            $allow_privacy_setting = array_unique(array_merge($allow_other_user_wall_privacy_setting, $allow_personal_friend_privacy_setting, $allow_professional_friend_privacy_setting));
            return $allow_privacy_setting;
        
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    public function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }
    
    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }
    
    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
}
