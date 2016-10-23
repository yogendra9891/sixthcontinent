<?php

namespace StoreManager\StoreBundle\Controller;

use StoreManager\StoreBundle\Entity\Store;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

class StoresController extends Controller
{

    /**
     * Get  Details
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getallshopsAction(Request $request) {
      
        // $freq_obj = $request->get('reqObj');

        // $fde_serialize = $this->decodeData($freq_obj);

        // if (isset($fde_serialize)) {
        //     $de_serialize = $fde_serialize;
        // } else {
        //     $de_serialize = $this->getAppData($request);
        // }


        // /* check required parameters*/
        // $object_info = (object) $de_serialize;
        // $required_parameter = array('shop_type');
        
        // /* checking for parameter missing */
        // $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        // if ($chk_error) {
        //      $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
        //      echo json_encode($resp);
        //      exit();
        // }

        // $em = $this->getDoctrine()->getManager();

        // $limit_start  = 0;  
        // $limit_size = 500;

        // /* Get All shops Data */
      
        // $ShopData = $em
        //                 ->getRepository('StoreManagerStoreBundle:Store')
        //                 ->getAllStoresResult($limit_start,$limit_size);
       
        // if($ShopData) {

        //     foreach ($ShopData as $store_detail) {

        //           $shopdetails[] =  array(
                               
        //             '_id' => $store_detail->getid(),
        //             'countryname' => $store_detail->getbusinessCountry(),
        //             'address_l1' => $store_detail->getprovince(),
        //             'name' => $store_detail->getbusinessName(),
        //             'mobile_no' => $store_detail->getphone(),
        //             'address_l2' => $store_detail->getbusinessAddress(),
        //             'region' => $store_detail->getbusinessRegion(),
        //             'province' => $store_detail->getprovince(),
        //             'zip' => $store_detail->getzip(),
        //             'email_address' => $store_detail->getemail(),
        //             'latitude' => $store_detail->getlatitude(),
        //             'longitude' => $store_detail->getlongitude(),
        //             'average_anonymous_rating' => $store_detail->getAvgRating()
        //            );

        //      }                          

        //     echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $shopdetails)), JSON_UNESCAPED_UNICODE);
        
        // } 
        // else {
            
        //     echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        // }


        set_time_limit(0);
        ini_set('memory_limit','512M');
        $bucket_path = $this->getS3BaseUri();
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //parmeter check start
        $object_info = (object) $de_serialize;

        $required_parameter = array('user_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $user_id = (int) $de_serialize['user_id'];
        $language_code = isset($de_serialize['lang_code'])?$de_serialize['lang_code']:'it';
        // get documen manager object
        $em = $this->getDoctrine()->getManager();
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        //$citizen_income = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);
        $citizen_income = 0;
        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getAllMapStoreOptimizeRest($user_id,$citizen_income,$bucket_path,$language_code);
        $count = count($stores);
        $dataResponse = $stores;
        $res_data = array('code' => 101, 'message' => 'SUCCESS','data' => array('stores' => $dataResponse), 'count' => $count);

        echo json_encode($res_data);
        exit();

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
     * Encode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $json = $serializer->serialize($req_obj, 'json');
        return  $json;
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

    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }


}
