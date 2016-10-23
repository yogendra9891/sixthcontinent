<?php

namespace Utility\RequestHandlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Utility\RequestHandlerBundle\Document\MonologRecords;
use Utility\RequestHandlerBundle\Document\MonologRecordsContextt;
use Utility\RequestHandlerBundle\Document\MonologRecordsExtra;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger; 

/**
 * class for handling the request failed from symfony
 */
class MonologHandlerController extends Controller
{
    protected $miss_param = '';
    
    public function indexAction($name)
    {
        return $this->render('UtilityRequestHandlerBundle:Default:index.html.twig', array('name' => $name));
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
     * get the request records
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getmonologrecordsAction(Request $request) {
  
      
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
        $order_by=(isset($object_info->order_by)?$object_info->order_by: 'desc'); 
        $count = 0;
        //getting doctrine manager object.
        
        $valid_order_by=array('asc','desc');
        //checking for valid order by
        if(!in_array($order_by,$valid_order_by)){
            $order_by='desc';
        }

        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
      
        $records= $dm->getRepository('UtilityRequestHandlerBundle:MonologRecords')
                    ->findRequestRecords($limit_start, $limit_size,$order_by);
        $count = $dm->getRepository('UtilityRequestHandlerBundle:MonologRecords')
                    ->findRequestRecordsCount();
        
       
        //prepare the response data
        foreach ($records as $record) {
            $res_data[] = array(
                'id'=> $record->getId(),
                'message'=>$record->getMessage(),
                'context'=>$record->getContext(),
                'level'=>$record->getLevel(),
                'level_name'=>$record->getLevelName(),
                'channel'=>$record->getChannel(),
                'datetime'=>$record->getDatetime(),
                'extra'=>$record->getExtra() 
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
}

