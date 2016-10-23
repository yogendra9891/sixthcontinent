<?php

namespace StoreManager\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Transaction\TransactionBundle\Entity\TotalEconomyShifted;

class ShoppingplusController extends Controller {

    protected $miss_param = '';

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
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && !empty($converted_array[$param])) {
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

    /*
     * testing service for shoppingplus
     */

    public function postShoppingplusclientesAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $required_parameter = array('idcard');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //get idcard
        $idCard = $de_serialize['idcard'];

        if ($idCard == "") {
            $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        //get curl object from service
        $curl_obj = $this->container->get("store_manager_store.curl");

        $env = $this->container->getParameter('kernel.environment');
        if ($env == 'dev') { // test environment
            // echo "dev envir";
            $url = $this->container->getParameter('shopping_plus_get_client_url_test');
            $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
            $shopping_plus_password = $this->container->getParameter('social_bees_password_test');
        } else {
            $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
            $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
            $shopping_plus_password = $this->container->getParameter('social_bees_password_prod');
        }

        $fields = array('o' => 'CLIENTEGET',
            'u' => $shopping_plus_username,
            'p' => $shopping_plus_password,
            'v01' => $idCard
        );

        $output = $curl_obj->shoppingplusClientRemoteServer($fields, $url);
        $decode_data = urldecode($output);
        parse_str($decode_data, $final_output);
        $status = $final_output['stato'];
        if ($status == '0') {
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $final_output);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
    }

    /*
     * Return user’s credit available
     */
    /* public function postCardsoldsAction(Request $request) {
      //get request object
      $freq_obj = $request->get('reqObj');
      $fde_serialize = $this->decodeData($freq_obj);

      if (isset($fde_serialize)) {
      $de_serialize = $fde_serialize;
      } else {
      $de_serialize = $this->getAppData($request);
      }
      $required_parameter = array('idcard');
      $data = array();
      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
      if ($chk_error) {
      return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
      }
      //get idcard
      $idCard = $de_serialize['idcard'];
      if($idCard == ""){
      $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
      echo json_encode($res_data);
      exit;
      }
      //get curl object from service
      $curl_obj = $this->container->get("store_manager_store.curl");

      //get remote url from parameters.yml
      $url = $this->container->getParameter('shopping_plus_get_client_url');
      $social_bees_username = $this->container->getParameter('social_bees_username');
      $social_bees_password =$this->container->getParameter('social_bees_password');
      /*  $fields = array('o'=>'CLIENTEGET',
      'u'=>'S932A001',
      'p'=>'49150',
      'v01'=>12530
      ); */
    /* $fields = array('o'=>'CARDSALDO',
      'u'=> $social_bees_username,
      'p'=> $social_bees_password,
      'v01'=>$idCard
      );

      $output =  $curl_obj->shoppingplusClientRemoteServer($fields,$url);
      $decode_data = urldecode($output);
      parse_str($decode_data, $e);
      //  echo'<pre>'; print_r($e); echo'</pre>';
      $status = $e['stato'];
      if($status == '0'){
      $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $e);
      return $res_data;
      } else {
      $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
      return $res_data;
      }
      }
     */

    /*
     * Return user’s total money movement
     */
    /*  public function postEconomsAction(Request $request) {
      //get request object
      $freq_obj = $request->get('reqObj');
      $fde_serialize = $this->decodeData($freq_obj);

      if (isset($fde_serialize)) {
      $de_serialize = $fde_serialize;
      } else {
      $de_serialize = $this->getAppData($request);
      }
      /* $required_parameter = array('idcard');
      $data = array();
      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
      if ($chk_error) {
      return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
      }
     */

    //get idcard
    /* $idCard = $de_serialize['idcard'];
      if($idCard == ""){
      $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
      echo json_encode($res_data);
      exit;
      }
      //get curl object from service
      $curl_obj = $this->container->get("store_manager_store.curl");

      //get remote url from parameters.yml
      $url = $this->container->getParameter('shopping_plus_get_client_url');
      $social_bees_username = $this->container->getParameter('social_bees_username');
      $social_bees_password =$this->container->getParameter('social_bees_password');
      /*  $fields = array('o'=>'CLIENTEGET',
      'u'=>'S932A001',
      'p'=>'49150',
      'v01'=>12530
      ); */
    /* $fields = array('o'=>'ECONOMS',
      'u'=> $social_bees_username,
      'p'=> $social_bees_password,
      'v01'=>$idCard
      );

      $output =  $curl_obj->shoppingplusClientRemoteServer($fields,$url);
      $decode_data = urldecode($output);
      parse_str($decode_data, $e);
      //  echo'<pre>'; print_r($e); echo'</pre>';
      $status = $e['stato'];
      if($status == '0'){
      $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $e);
      return $res_data;
      } else {
      $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
      return $res_data;
      }
      }
     */

    /**
     * calculate the citizen income.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json
     */
    public function cardsoldsAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $required_parameter = array('idcard');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //get idcard
        $idCard = $de_serialize['idcard'];
        if ($idCard == "") {
            $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $call_type = '';
        try {
            $call_type = $this->container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {

        }
        if ($call_type == 'APPLANE') { //from applane
             try {
                //get the applane service for total citizen income.
                $applane_service      = $this->container->get('appalne_integration.callapplaneservice');
                $citizen_income_data = $applane_service->getCitizenIncome($idCard);
                //prepare the data
                $data = array(
                    'stato'=>0,
                    'descrizione'=>'',
                    'saldoc'=>'0',
                    'saldorc'=>0,
                    'saldorm'=>'0',
                    'shopping_plus_user'=>1,
                    'tot_income'=>$citizen_income_data['citizen_income']
                );
                $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);            
            } catch (\Exception $ex) {
                $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
            }
        } else { //from our local database.
            $this->getUserCIDetailsAction($request);
        } 
        echo json_encode($res_data);
        exit;
    }

    public function cardsoldsinternalAction(Request $request) {
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $required_parameter = array('idcard');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //get idcard
        $idCard = $de_serialize['idcard'];
        if ($idCard == "") {
            $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $container = NManagerNotificationBundle::getContainer();
        //check if variable is defined in parameter file 
        try {
            $CI_calls = $container->getParameter('CI_calls'); //get server
        } catch (\Exception $e) {
            $CI_calls = 'SP';
        }
        //get curl object from service
        //check from where we have to call shopping plus service
        if ($CI_calls == 'SP') {
            $curl_obj = $container->get("store_manager_store.curl");

            $env = $container->getParameter('kernel.environment');
            if ($env == 'dev') { // test environment
                // echo "dev envir";
                $url = $container->getParameter('shopping_plus_get_client_url_test');
                $shopping_plus_username = $container->getParameter('social_bees_username_test');
                $shopping_plus_password = $container->getParameter('social_bees_password_test');
            } else {
                $url = $container->getParameter('shopping_plus_get_client_url_prod');
                $shopping_plus_username = $container->getParameter('social_bees_username_prod');
                $shopping_plus_password = $container->getParameter('social_bees_password_prod');
            }

            $fields = array('o' => 'CARDSALDO',
                'u' => $shopping_plus_username,
                'p' => $shopping_plus_password,
                'v01' => $idCard
            );

            $output = $curl_obj->shoppingplusClientRemoteServer($fields, $url);
            $decode_data = urldecode($output);
            parse_str($decode_data, $final_output);

            if (isset($final_output['stato'])) {
                $status = $final_output['stato'];
            } else {
                $status = 'no record';
            }
            if ($status == '0') {
                $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $final_output);
                return $res_data;
            } else {
                $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
                return $res_data;
            }
        } else {
            $result = $this->getuserincomedetailsAction($request);
            return $result;
        }
    }


    
    public function economsAction(Request $request) {
        $container = NManagerNotificationBundle::getContainer();
        $em = $container->get('doctrine')->getManager();
        $time = new \DateTime();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //get idcard
        $idCard = $de_serialize['idcard'];
        if ($idCard == "") {
            $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        //check if variable is defined in parameter file 
        try {
            $CI_calls = $container->getParameter('CI_calls'); //get server
        } catch (\Exception $e) {
            $CI_calls = 'SP';
        }
        
        $response = $call_type = '';
        
        try {
          $call_type = $this->container->getParameter('tx_system_call');
        } catch (\Exception $ex) {

        }
        
        //get curl object from service
        //check from where we have to call shopping plus service
         if($call_type == "APPLANE"){
             
            $api = 'query';
            $queryParam = 'query';
            $data = '{"$collection":"sixc_income","$fields":{"income":1}}';
            
            //call applane service 
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            $response = $applane_service->callApplaneService($data, $api, $queryParam);
            $response = json_decode($response);
            if( isset($response->status) && $response->status == 'ok' ){
               $total_economy = '';
               foreach($response->response->result as $results){
                 $total_economy = $results->income ;
               }
                $data = array();
                $data['stato'] = 0;
                $data['descrizione'] = '';
                $data['economstot'] = (string)$total_economy;
                //$data['economstot'] = "394992550000";
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
               echo json_encode($res_data);
               exit; 
            }else {
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
                echo json_encode($res_data);
                exit;  
            }
        } else {
            $economy_shifted = $em->getRepository('TransactionTransactionBundle:TotalEconomyShifted')
                    ->findAll();
            $data = array();
            if (count($economy_shifted) > 0) {
                $economy_data = $economy_shifted[0];
                $data['stato'] = 0;
                $data['descrizione'] = '';
                $data['economstot'] = $economy_data->getEconomyShifted();
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit;
            } else {
                $res_data = array('code' => 100, 'message' => 'FAILED', 'data' => $data);
                echo json_encode($res_data);
                exit;
            }
        }
    }
    
    
    /**
     *  function for getting the user CI from the local DB
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getUserCIDetailsAction(Request $request) {
        $response = $this->getuserincomedetailsAction($request);
        echo json_encode($response);
        exit;
    }

    /**
     *  function for getting the user income details from UserDiscountPosition table
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getuserincomedetailsAction(Request $request) {

        $container = NManagerNotificationBundle::getContainer();
        //get request object
        $em = $container->get('doctrine')->getManager();
        $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $required_parameter = array('idcard');
        $data = array();
        //checking for parameter missing.

        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);

        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        //get idcard
        $idCard = $de_serialize['idcard'];
        if ($idCard == "") {
            $res_data = array('code' => 111, 'message' => 'ID_CARD_NECESSARY', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $user_ci_info = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                ->findBy(array('userId' => $idCard));

        if (count($user_ci_info) > 0) {
            $user_ci_info = $user_ci_info[0];
            $data['stato'] = 0;
            $data['descrizione'] = '';
            $data['saldoc'] = $user_ci_info->getCitizenIncome();
            $data['saldorc'] = $user_ci_info->getTotalCitizenIncome() - $user_ci_info->getSaldorm();
            $data['saldorm'] = $user_ci_info->getSaldorm();
            $data['shopping_plus_user'] = 1;
            $data['tot_income'] = ($user_ci_info->getTotalCitizenIncome()) / 1000000;
            $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
        } else {
            $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
        }

        return $res_data;
    }
    
    /**
     *  function for saving the total economy shifted 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function saveTotalEconomyShiftedAction(Request $request) {
        $container = NManagerNotificationBundle::getContainer();
        $em = $container->get('doctrine')->getManager();
        $time = new \DateTime();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $required_parameter = array('idcard');
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        $data = array();
        //check for missing parameters
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        //get idcard from the request
        $idCard = $de_serialize['idcard'];
        //call shopping plus service
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $total_economy = $shoppingplus_obj->economs($idCard);
        // $output =  $curl_obj->shoppingplusClientRemoteServer($fields,$url);
        $decode_data = urldecode($total_economy);
        parse_str($decode_data, $final_total_economy);
        //check if array is set
        if (isset($final_total_economy)) {
            $status = $final_total_economy['stato'];
            if ($status == '0') {
                $economy_shifted = $em->getRepository('TransactionTransactionBundle:TotalEconomyShifted')
                        ->findAll();
                //check if record exist in the DB
                if (count($economy_shifted) > 0) {
                    $economy_obj = $economy_shifted[0];
                } else {
                    $economy_obj = new TotalEconomyShifted();
                }
                $economy_obj->setEconomyShifted($final_total_economy['economstot']);
                $economy_obj->setUpdatedAt($time);
                $em->persist($economy_obj);
                $em->flush();
                $res_data = array('code' => '101', 'message' => 'SUCESS', 'data' => array());
                echo json_encode($res_data);
                exit;
            } else {
                $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        } else {
            $res_data = array('code' => '100', 'message' => 'FAILURE', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
    }
    
    /**
     *  function for getting the total econimy shifted from the local DB
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getTotalEconomyShiftedAction(Request $request) {
        $container = NManagerNotificationBundle::getContainer();
        $em = $container->get('doctrine')->getManager();
        $time = new \DateTime();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $required_parameter = array('idcard');
        $chk_error = $this->checkParamsAction($required_parameter, $de_serialize);
        $data = array();
        //check for missing parameters
        if ($chk_error) {
            $res_data = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $economy_shifted = $em->getRepository('TransactionTransactionBundle:TotalEconomyShifted')
                ->findAll();
        
        $data = array();
        if(count($economy_shifted) > 0) {
            $economy_data = $economy_shifted[0];
            $data['stato'] = 0;
            $data['descrizione'] = '';
            $data['economstot'] = $economy_data->getEconomyShifted();
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        } else {
            $res_data = array('code' => 100, 'message' => 'FAILED', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
    }

}
