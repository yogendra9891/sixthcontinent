<?php
namespace Utility\SecurityBundle\EventListener;

use Utility\SecurityBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\Response;
use UserManager\Sonata\UserBundle\Entity\UserToAccessToken;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Doctrine\ORM\EntityManager;

class TokenListener {

    private $tokens;
    
    protected $seller_allowed_action = array('listsellersstores', 'searchsellerusers', 'getsellerprofiles', 'changesellerpasswords', 'sellerlogouts', 'appqueries', 'appupdates', 'batchqueries', 'storedetails', 'getinitilizedtransactions', 'canceltransaction', 'getinitbookingdetail', 'processTransaction', 'updateprocesstransaction' );

    public function __construct(EntityManager $entityManager) {
        $this->em = $entityManager;
    }

     /**
     * function to called before every request.
     * @param type $data_array
     */
    public function onKernelController(FilterControllerEvent $event) {
        $data = array();
        $controller = $event->getController();
       
        //list of actions which does not require accesstoken mapping validation 
        $excluded_actions = array('token', 'register', 'getaccesstoken','logins','suggestionmultiprofiles','getbusinesscategorylist','getkeywordlist','getrelationtype', 'sellerlogins');

        //get request parameters
        $request = $event->getRequest();
                
        //get base url to be called
        $base_uri = $request->getUri();
        
        $actionName='';
        $action_path=$request->getPathInfo();
        if(isset($action_path)){
            if(strpos($action_path,'/')!==false)
            {
                $params = explode('/', $action_path);
                $params =  array_reverse($params);
            }
            if(isset($params[0]))
            {
                $actionName = $params[0];
            }
        }
        

        //check for public services...public services will skip this validation
        if (strpos($base_uri, '/api/')) {
             if($actionName != '' && !in_array($actionName,$excluded_actions)){ 
                 
            $global_req = Request::createFromGlobals();
            //get access token 
            $access_token = $global_req->query->get('access_token');
            if (!$access_token) {
                $res_data = array('code' => 1037, 'message' => 'INVALID_TOKEN', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }

            
            //retrieve session id from get parameters
            $session_id_from_get_params=$global_req->query->get('session_id');
            
            //check if we are getting user_id in get parameters 
            if(!$session_id_from_get_params){
                //array need to be associative to use array_intersect_key function below
                $user_id_variable_name = array('session_id' => 'session_id', 'user_id' => 'user_id','sender_id'=>'sender_id','comment_author'=>'comment_author','owner_id'=>'owner_id'
                    ,'sender_userid' => 'sender_userid','receiver_userid' => 'receiver_userid'); 
                //add to the array in order of their priority

                $freq_obj = $request->get('reqObj');
//                $freq_obj = is_array($freq_obj) ? json_encode($freq_obj) : $freq_obj;
//                $field = $request->request->set('reqObj', $freq_obj);                          
                $device_request_type='';
                $fde_serialize = $this->decodeObjectAction($freq_obj);
                if(isset($freq_obj['device_request_type'])){
                    $device_request_type=$freq_obj['device_request_type'];
                }
                //this handling for with out image.
                if($device_request_type=='mobile'){  //for mobile if images are uploading.
                    $de_serialize= $freq_obj;
                }else{
                    if (isset($fde_serialize)) {
                        $de_serialize = $fde_serialize;
                    } else {
                        $de_serialize = $this->getAppData($request);
                    } 
                }
                //Code end for getting the request

                $object_info = (object) $de_serialize; //convert an array into object. 
                 //find if the reqest contains user id or not
                $req_intercept=array_intersect_key($user_id_variable_name,$de_serialize);
                if(sizeof($req_intercept)>0)
                {
                    foreach($user_id_variable_name as $name){ 
                        if(array_key_exists($name,$de_serialize)){
                            $session_id=$de_serialize[$name];
                            break;
                          }
                    }
                     //if user_id doesnot exists
                    if(!$session_id){
                        $res_data=array('code'=>1021,'message'=>'USER_DOES_NOT_EXIST','data'=>$data);
                        echo json_encode($res_data);
                        exit();
                    }    
                }else{
                    $res_data=array('code'=>1043,'message'=>'INVALID _GRANT','data'=>$data);
                    $this->returnResponseWithHeader($res_data, 403);
                }
            }else{
                $session_id=$session_id_from_get_params;
            }
            
            
            
//            $map_obj = $this->em->getRepository('UserManagerSonataUserBundle:UserToAccessToken')->findOneBy(array('accessToken' => $access_token));
//            //check if user exist for the given token else add current login user id from access token
//            if (count($map_obj) == 0) {
//                $res_data = array('code' => 1043, 'message' => 'INVALID _GRANT', 'data' => $data);
//                         // create a JSON-response with a 1043 status code
//                        $response = new Response(json_encode($res_data));
//
//                        $response->setStatusCode(403,'FORBIDDEN');
//                        $response->headers->set('Content-Type', 'application/json');
//                         // prints the HTTP headers followed by the content
//                        $response->send();
//                        exit();  
//            } else {
//                $user_id = $map_obj->getUserId();
//                $object_info->login_user_id = $user_id;
//                $container = NManagerNotificationBundle::getContainer();
//                
//                
//                $user_service = $container->get('user_object.service');
//                //block the service access once user trail get expired
//                $status = $user_service->checkUserAccountStatus($user_id);
//                if(!$status){
//                   $res_data = array('code' => 1045, 'message' => 'TRIAL_EXPIRED', 'data' => $data);
//                   $this->returnResponseWithHeader($res_data, 403);
//                }
//            }
                
                //find mapping in usertoaccesstoken table
                $map_obj=$this->em->getRepository('UserManagerSonataUserBundle:UserToAccessToken')->findOneBy(array('accessToken'=>$access_token,'userId'=>$session_id));
                //var_dump($map_obj);

                if(!$map_obj){
                    $res_data=array('code'=>1043,'message'=>'INVALID _GRANT','data'=>$data);
                    $this->returnResponseWithHeader($res_data, 403); 
                }
                
                $container = NManagerNotificationBundle::getContainer();               
                $user_service = $container->get('user_object.service');
                $um = $container->get('fos_user.user_manager');
                //get user detail
                $user = $um->findUserBy(array('id' => $session_id,'enabled' => 1));
                //check user exist
                if(count($user) == 0 ) {
                    $res_data=array('code'=>1043,'message'=>'INVALID _GRANT','data'=>$data);
                    $this->returnResponseWithHeader($res_data, 403);
                }
                //block the service access once user trail get expired
                // commented old code to checkUserAccountStatus and added new method getUserAccountStatus
                //$status = $user_service->checkUserAccountStatus($session_id);
                $status = $user_service->getUserAccountStatus($user);
                if(!$status){
                   $res_data = array('code' => 1045, 'message' => 'TRIAL_EXPIRED', 'data' => $data);
                   $this->returnResponseWithHeader($res_data, 403);
                } 
            // $object_info = json_encode($object_info);
            //$event->getRequest()->attributes->set('reqObj',$object_info);
            
                // commented old code to checkSellerStatus and added new method getSellerStatus
                //$seller_status = $user_service->checkSellerStatus($session_id);
                $seller_status = $user_service->getSellerStatus($user);
                $sellers_allowed_methods = $this->seller_allowed_action;
                if($seller_status) {
                    if(!in_array($actionName,$sellers_allowed_methods)) {
                      $res_data=array('code'=>1043,'message'=>'INVALID_GRANT','data'=>$data);
                      $this->returnResponseWithHeader($res_data, 403); 
                    }
                }
                    }
             //sfdu
        }
    }

    /**
     * Functionality decoding data
     * @param json $req_obj	
     * @return array
     */
    public function decodeDataAction($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        if(isset($dataer->reqObj)){
        $app_data = $dataer->reqObj;
        }else{
            return $content;
        }
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $encode_object = is_array($encode_object) ? json_encode($encode_object) : $encode_object;
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }
    
     /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponseWithHeader($data_array, $header) {
        $response = new Response(json_encode($data_array));

        $response->setStatusCode($header, 'FORBIDDEN');
        $response->headers->set('Content-Type', 'application/json');
        // prints the HTTP headers followed by the content
        $response->send();
        exit();
    }

}