<?php

namespace StoreManager\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\ShopFollowers;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class FollowShopController extends Controller
{
    
    /**
     * Follow the user
     * @param Request $request
     * @return array;
     */
    public function postFollowshopsAction(Request $request)
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

        $required_parameter = array('user_id', 'shop_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        $shopId = $object_info->shop_id;
        
        
        //get entity object
        $shopfollow = new ShopFollowers();
       
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user has already connected
        $user_con = $em
                ->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->findOneBy(array('userId' => $userId, 'shopId'=> $shopId));
         
        //do return success if already following
        if($user_con){
           $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
        $shopfollow->setUserId($userId);
        $shopfollow->setShopId($shopId);
        $time = new \DateTime('now');
        $shopfollow->setCreatedAt($time);
        $em->persist($shopfollow);
        $em->flush();
        
        $last_insert_id = $shopfollow->getId();
        $applane_id = $shopId."_".$userId;
        $de_serialize['id'] = $applane_id;
       // $de_serialize['id'] = $last_insert_id;
        //update to applane
        //$app_data = $this->prepareApplaneData($de_serialize);
        //get dispatcher object
        $event = new FilterDataEvent($de_serialize);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.follow', $event);
        //end of update
            
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
    }
    
    /**
     * Follow the user
     * @param Request $request
     * @return array;
     */
    public function postUnfollowshopsAction(Request $request)
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

        $required_parameter = array('user_id', 'shop_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        $shopId = $object_info->shop_id;
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user has already connected
        $user_con = $em
                ->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->findOneBy(array('userId' => $userId, 'shopId'=> $shopId));
        
        // return success if user already unfollowed 
        if(!$user_con){
           $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
           echo json_encode($res_data);
           exit;
        }
        
        $follow_atore_app_id = $user_con->getId();
       
        //remove the connection
        $em->remove($user_con);
        $em->flush();
        
        //$de_serialize['id'] = $follow_atore_app_id;
        $de_serialize['id'] = $shopId."_".$userId;
        //get dispatcher object
        $event = new FilterDataEvent($de_serialize);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.unfollow', $event);
        //end of update
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
    }
    
    
    /**
     * Get Shop List followed by user
     * @param Request $request
     * @return array;
     */
    public function postUserfollowedshopsAction(Request $request)
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

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $userId = $object_info->user_id;
        $offset = isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0 ;
        $limit = isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20 ;
        

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        $bucket_path = $this->getS3BaseUri();

        $store_type = 1;
        $filter_type = 1;
        $citizen_income = 0;
        $language_code = 'it';
        $friendsIds = array();
        //check if user has already connected
        $user_shops = $em
                ->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->getfollowedshops($userId, $offset, $limit, $store_type, $bucket_path, $filter_type, $citizen_income, $language_code, $friendsIds);
        
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stores'=>$user_shops));
        echo json_encode($res_data);
        exit;
    }
    
    /**
     * Get Shop List followed by user
     * @param Request $request
     * @return array;
     */
    public function postUserfollowingshopsAction(Request $request)
    {
         //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $data = array();
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('shop_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
 
        $storeId = $object_info->shop_id;
        $offset = isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0 ;
        $limit = isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20 ;
        

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user already marked shop favourite
        $usersfollowing = $em
                ->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->findBy(array('shopId' => $storeId), array('createdAt' => 'DESC'), $limit, $offset);
        
        
        $userFolllowingCount = $em
                ->getRepository('StoreManagerStoreBundle:ShopFollowers')
                ->findBy(array('shopId' => $storeId));
        
        $userCount = count($userFolllowingCount);
        
        //getting the user ids.
        $user_ids = array_map(function($user_followed) {
            return "{$user_followed->getUserId()}";
        }, $usersfollowing);

        $user_service = $this->get('user_object.service');
        $dp_user_objects = $user_service->MultipleUserObjectService($user_ids);
        
        if( count($dp_user_objects) ){
           $userInfo = array();
            foreach($user_ids as $user_id){
                if(array_key_exists($user_id, $dp_user_objects )){
                    $userInfo[] = $dp_user_objects[$user_id];
                }
            }
            $data['user_info'] = $userInfo;
            $data['total'] = $userCount;
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
              
        } else {
              
            $res_data = array('code' => '102', 'message' => 'NO_USER_FOLLOWING', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
    }
    
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
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
