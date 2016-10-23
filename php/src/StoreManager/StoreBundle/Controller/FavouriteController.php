<?php

namespace StoreManager\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\Favourite;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class FavouriteController extends Controller
{
    
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
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
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
     * Check for enabled user
     * @param string $userId
     * @return boolean
     */
    public function checkActiveUserProfile($user_id){
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $user_id));
        
        if($user){
            $user_check_enable = $user->isEnabled();
        } else {
            $user_check_enable = 0;
        }
        
        return $user_check_enable;
    }
    
    /**
     * Check for enabled Store
     * @param string $storeId
     * @return boolean
     */
    public function checkActiveStore($store_id){
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check if Store is active
        $store_results = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id ));
        if($store_results){
            $store_check_enable = $store_results->getIsActive();
        } else {
            $store_check_enable = 0;
        }
        
        return $store_check_enable;
    }
    
    /**
     * Check if user already made Store favourite
     * @param string $userId
     * @return boolean
     */
    public function checkFavouriteStore($store_id, $user_id){
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check if user already marked shop favourite
        $favourite_results = $em
                ->getRepository('StoreManagerStoreBundle:Favourite')
                ->findOneBy(array('userId' => $user_id, 'storeId' => $store_id ));

        return $favourite_results;
    }
    
    /**
     * Make store favourite to user 
     * @param json $request
     * @return array
     */
    public function postFavouritestoresAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $store_id = $object_info->store_id;
                
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

     
        if($this->checkFavouriteStore($store_id, $user_id ) ){
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        
        $favourite = new Favourite();
        
        $date = new \DateTime("now");
        
        $favourite->setUserId($user_id);
        $favourite->setStoreId($store_id);
        $favourite->setCreatedAt($date);
        $em->persist($favourite);
        try{
            $em->flush(); 
            $last_insert_id = $favourite->getId();
            $applane_id = $store_id."_".$user_id;
            $de_serialize['id'] = $applane_id;
            //get dispatcher object
            $event = new FilterDataEvent($de_serialize);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.favourite', $event);
            //end of update
            
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }catch( Exception $e ){
            
            $res_data = array('code' => '100', 'message' => 'SOME_ERROR_OCCOURED', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
       
    }
    
    
    /**
     * Make store favourite to user 
     * @param json $request
     * @return array
     */
    public function postUnfavouritestoresAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $store_id = $object_info->store_id;
                
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $fav_store = $this->checkFavouriteStore($store_id, $user_id );
        $fav_atore_app_id = $fav_store->getId();
         
        if( $fav_store ){
           
            $em->remove($fav_store);
            try{
                $em->flush(); 
               
                $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
                
                $de_serialize['id'] = $store_id."_".$user_id;
                 //get dispatcher object
                 $event = new FilterDataEvent($de_serialize);
                 $dispatcher = $this->container->get('event_dispatcher');
                 $dispatcher->dispatch('shop.unfavourite', $event);
                 //end of update
            
                echo json_encode($res_data);
                exit();
            }catch( Exception $e ){

                $res_data = array('code' => '100', 'message' => 'SOME_ERROR_OCCOURED', 'data' => array());
                echo json_encode($res_data);
                exit();
            }
  
        } else {
              
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
  
    }
    
    
    /**
     * Make store favourite to user 
     * @param json $request
     * @return array
     */
    public function postMyfavouritestoresAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        
        //check if user profile is active
        if(!$this->checkActiveUserProfile($user_id) ){
            $res_data = array('code' => '102', 'message' => 'USER_PROFILE_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user already marked shop favourite
        $MyFavouriteStores = $em
                ->getRepository('StoreManagerStoreBundle:Favourite')
                ->findBy(array('userId' => $user_id), array('createdAt' => 'DESC'));
        
        //getting the store ids.
        $store_ids = array_map(function($fav_store) {
            return "{$fav_store->getStoreId()}";
        }, $MyFavouriteStores);

        $user_service = $this->get('user_object.service');
        $dp_shop_objects = $user_service->getMultiStoreObjectService($store_ids);
        
        if( count($dp_shop_objects) ){
           
            foreach($store_ids as $store_id){
                if(array_key_exists($store_id, $dp_shop_objects ) && $dp_shop_objects[$store_id]['isActive']){
                    $data[] = $dp_shop_objects[$store_id];
                }
            }
            
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
              
        } else {
              
            $res_data = array('code' => '102', 'message' => 'NO_RELEVANT_STORE_FOUND', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
  
    }
    
    /**
     * users who liked store 
     * @param json $request
     * @return array
     */
    public function postStorelikedusersAction(Request $request) {
        //Code start for getting the request
        $data = array();
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('store_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $store_id = $object_info->store_id;
        
        //check if Store is active
        if(!$this->checkActiveStore($store_id) ){
            $res_data = array('code' => '102', 'message' => 'USER_PROFILE_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //check if user already marked shop favourite
        $usersLiked = $em
                ->getRepository('StoreManagerStoreBundle:Favourite')
                ->findBy(array('storeId' => $store_id), array('createdAt' => 'DESC'));
        
        //getting the store ids.
        $user_ids = array_map(function($user_liked) {
            return "{$user_liked->getUserId()}";
        }, $usersLiked);

        $user_service = $this->get('user_object.service');
        $dp_user_objects = $user_service->MultipleUserObjectService($user_ids);
        
        if( count($dp_user_objects) ){
           
            foreach($user_ids as $user_id){
                if(array_key_exists($user_id, $dp_user_objects )){
                    $data[] = $dp_user_objects[$user_id];
                }
            }
            
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
              
        } else {
              
            $res_data = array('code' => '102', 'message' => 'NO_RELEVANT_STORE_FOUND', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
  
    } 

}
