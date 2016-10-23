<?php
namespace Transaction\WalletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

//Utilities
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;

class RestWalletCitizenController extends FOSRestController
{      
    protected $profile_image_path = '/uploads/users/media/thumb/'; 

    protected $shoppingcart_image_path = '/uploads/scard100/m_'; 
    protected $cart_image_path = '/uploads/scard50/m_'; 
    protected $coupon_image_path = '/uploads/coupon/m_'; 

   /**
     * Get Citizen Wallet Details
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postcitizenwalletincomeAction(Request $request) {
    
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('buyer_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->getDoctrine()->getManager();

        /* Get wallet Data */
        $walletData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getWalletData($de_serialize['buyer_id']);

         if($walletData) {
           
            $data = $walletData[0];
            /* Get today gain information */
            $postArr = array(
                'buyer_id' => $de_serialize['buyer_id'],
                'wallet_citizen_id' => $data->getId(),
                'currency' => $data->getcurrency()
            );
            
            /*Get Today Gain*/
           
            $walletService = $this->get('wallet_manager');        
            $de_serialize["record_id"] = date("d-m-Y" , time());
            $de_serialize["record_type_id"] = "6721";
            $ci_dayly_detail = $walletService->getRecordDetail($de_serialize);
            $todayGain = $this->filterCiFoType($ci_dayly_detail["response"]);
            $responseArray = array(
                    'buyer_id'                  => $data->getbuyerId(),
                    'currency'                  => (!empty($data->getcurrency())) ? $data->getcurrency() : '',
                    'currency_symbol'           => $walletService->getCurrencyCode($data->getcurrency()),
                    'citizen_income_gained'     => (!empty($data->getcitizenIncomeGained())) ? number_format($walletService->convertCurrency($data->getcitizenIncomeGained()), 2, '.', '') : '0.00',
                    'citizen_income_available'  => (!empty($data->getcitizenIncomeAvailable())) ? number_format($walletService->convertCurrency($data->getcitizenIncomeAvailable()), 2, '.', '') : '0.00',
                    'credit_position_gained'    => (!empty($data->getcreditPositionGained())) ? number_format($walletService->convertCurrency($data->getcreditPositionGained()), 2, '.', '') : '0.00',
                    'credit_postion_available'  => (!empty($data->getcreditPositionAvailable())) ? number_format($walletService->convertCurrency($data->getcreditPositionAvailable(), '.', ''), 2) : '0.00',
                    'cashBack' =>  number_format($todayGain["cashBack"]/100  , '2', '.', ''),
                    'citizenAffiliated' => number_format($todayGain["citizenAffiliated"]/100 , '2', '.', ''),
                    'shopAffiliated' => number_format($todayGain["shopAffiliated"]/100 , '2', '.', ''),
                    'totalProfPersFollower' =>   number_format($todayGain["totalProfPersFollower"]/100 , '2', '.', ''),
                    'totalAllNation' => number_format($todayGain["totalAllNation"]/100  , '2', '.', ''),
                    'today_gain' =>  number_format($todayGain["today_gain"]/100 , '2', '.', '')
                );
            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseArray)), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }

    public function posttestAction(Request $request) {

         echo 'test';
         die;
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