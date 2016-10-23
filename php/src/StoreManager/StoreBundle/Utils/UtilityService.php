<?php
namespace StoreManager\StoreBundle\Utils;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

// service method  class
class UtilityService {

    protected $em;
    protected $dm;
    protected $container;
    CONST UNDEFINED = "UNDEFINED";
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
    
    /**
     * convert the string to upper string with removing the space
     * @param sting $string
     * @return string
     */
    public function convertString($string) {
        return trim(strtoupper($string));
    }
    
    /**
     * checking the vat and fiscal code
     * @param array $de_serialize
     * $return boolean
     */
    public function checkVATFiscal($de_serialize) {
        $de_serialize['vat_number'] = (isset($de_serialize['vat_number'])) ? $de_serialize['vat_number'] : '';
        $de_serialize['fiscal_code'] = (isset($de_serialize['fiscal_code'])) ? $de_serialize['fiscal_code'] : '';
        $vat_number = (($this->convertString($de_serialize['vat_number']) == self::UNDEFINED) ? '' : $de_serialize['vat_number']);
        $fiscal_code = (($this->convertString($de_serialize['fiscal_code'])  == self::UNDEFINED) ? '' : $de_serialize['fiscal_code']);
        if ($vat_number == '' && $fiscal_code == '') {
            $res_data = array('code' => 1075, 'message' => 'VAT_NUMBER_OR_FISCAL_CODE_NEEDED', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        return true;
    }  
    
    /**
     * removing the space
     * @param sting $string
     * @return string
     */
    public function trimString($string) {
        return trim($string);
    }
    
    /**
     * 
     * @param type $freq_obj
     * @param type $required_parameter
     */
    public function checkRequest(Request $request, $required_parameter){
        $data = array();
        //get request object
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }

        $de_serialize = $this->getDeSerializeDataFromRequest($request);
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, (object) $de_serialize);
        if ($chk_error) {
            $data = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
            return $data;
        }
        return true;
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
            if(isset($converted_array[$param]) && is_array($converted_array[$param])) {
                $converted_array_size = count($converted_array[$param]);
            } else {
                $converted_array_size = (isset($converted_array[$param])) ? strlen($converted_array[$param]) : 0;
            }           
            //$converted_array_size = (isset($converted_array[$param])) ? $size : 0;
            if (array_key_exists($param, $converted_array) && ($converted_array_size >0)) {
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
     *  function for getting the deserialize data from the request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function getDeSerializeDataFromRequest(Request $request) {
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        // check needed for mobile device
        if(isset($freq_obj['device_request_type']) and $freq_obj['device_request_type']=='mobile'){
            $de_serialize = $freq_obj;
        }else{
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
        return $de_serialize;
    }
}    