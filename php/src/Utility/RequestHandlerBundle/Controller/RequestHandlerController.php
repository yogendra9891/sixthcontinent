<?php

namespace Utility\RequestHandlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Utility\RequestHandlerBundle\Document\RequestRecords;

/**
 * class for handling the request failed from symfony
 */
class RequestHandlerController extends Controller
{
    protected $miss_param = '';
    
    public function indexAction($name)
    {
        return $this->render('UtilityRequestHandlerBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * save the failed request.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function saverequestAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.


        $required_parameter = array();
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data =  array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => array());
            $this->returnResponse($data);
        }
        
        //extract parameters
        $response_code  = (isset($object_info->response_code) ? $object_info->response_code : '');
        $page_name      = (isset($object_info->page_name) ? $object_info->page_name : '');
        $action_name    = (isset($object_info->action_name) ? $object_info->action_name : '');
        $request_object  = (isset($object_info->request_object) ? $object_info->request_object : '');
        $response_object = (isset($object_info->response_object) ? $object_info->response_object : '');
        $request_content_type  = (isset($object_info->request_content_type) ? $object_info->request_content_type : '');
        $response_content_type = (isset($object_info->response_content_type) ? $object_info->response_content_type : '');
        $header_str = (isset($object_info->header_str) ? $object_info->header_str : '');
        $time = new \DateTime('now');
        
        //getting doctrine manager object.
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        //create the document object
        $request_records = new RequestRecords();
        $request_records->setResponseCode($response_code);
        $request_records->setPageName($page_name);
        $request_records->setActionName($action_name);
        $request_records->setRequestObject($request_object);
        $request_records->setResponseObject($response_object);
        $request_records->setRequestContentType($request_content_type);
        $request_records->setResponseContentType($response_content_type);
        $request_records->setHeaderStr($header_str);
        $request_records->setCreatedAt($time);
        
        try {
            $dm->persist($request_records); //persist the data..
            $dm->flush();
            $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>array());
        } catch (\Exception $ex) {
             $data = array('code'=>1029, 'message'=>'FAILURE', 'data'=>array());
        }
        $this->returnResponse($data);          
    }
    
    /**
     * get the request records
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getrequestrecordsAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        //initialize the array.
        $required_parameter = $data = $res_data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data =  array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => array());
            $this->returnResponse($data);
        }
        
        $limit_start = (isset($object_info->limit_start)? $object_info->limit_start : '');
        $limit_size  = (isset($object_info->limit_size) ? $object_info->limit_size : '');
        $count = 0;
        //getting doctrine manager object.
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $records = $dm->getRepository('UtilityRequestHandlerBundle:RequestRecords')
                    ->findRequestRecords($limit_start, $limit_size);
        $count = $dm->getRepository('UtilityRequestHandlerBundle:RequestRecords')
                    ->findRequestRecordsCount();
        //prepare the response data
        foreach ($records as $record) {
            $res_data[] = array(
                'id'=> $record->getId(),
                'response_code'=>$record->getResponseCode(),
                'page_name'=>$record->getPageName(),
                'action_name'=>$record->getActionName(),
                'request_object'=>$record->getRequestObject(),
                'response_object'=>$record->getResponseObject(),
                'request_content_type'=>$record->getRequestContentType(),
                'response_content_type'=>$record->getResponseContentType(),
                'header_str'=>$record->getHeaderStr(),
                'created_at'=>$record->getCreatedAt()
            );
        }
        $response = array('records'=>$res_data, 'count'=>$count);
        $data =  array('code' => 101, 'message' =>'SUCCESS', 'data' =>$response);
        $this->returnResponse($data);
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
     * Function to retrieve current applications base URI(hostname/project/web)
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
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
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        $check_error = 0;
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
     * return the response.
     * @param type $data_array
     */
    public function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }
}