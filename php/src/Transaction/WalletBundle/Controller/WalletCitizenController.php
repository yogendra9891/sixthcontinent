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

class WalletCitizenController extends Controller
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
    public function getcitizenwalletincomeAction(Request $request) {
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
    /**
     * 
     * @param type $ci_dayly_detail
     */
    public function filterCiFoType($ci_dayly_detail) {
        $shopAffiliated = 0 ;
        foreach ($ci_dayly_detail as $key => $value) {
            if($ci_dayly_detail[$key]["type"] =="CASHBACK"){
                $cash_back = $ci_dayly_detail[$key]["value"] ;
            }
            if($ci_dayly_detail[$key]["type"] =="CITIZENAFFILIATED"){
                $citizenAffiliated = $ci_dayly_detail[$key]["value"] ;
            }
            if($ci_dayly_detail[$key]["type"] =="SHOPAFFILIATED"){
                $shopAffiliated += $ci_dayly_detail[$key]["value"] ;
            }
            if($ci_dayly_detail[$key]["type"] =="CONNECTIONS"){
                $totalProfPersFollower = $ci_dayly_detail[$key]["value"] ;
            }
            if($ci_dayly_detail[$key]["type"] =="ALLNATION"){
                 $totalAllNation = $ci_dayly_detail[$key]["value"] ;
            }     
        }
        $todayGain["cashBack"] = $cash_back ;
        $todayGain["citizenAffiliated"] = $citizenAffiliated;
        $todayGain["shopAffiliated"] = $shopAffiliated ;
        $todayGain["totalProfPersFollower"] = $totalProfPersFollower;
        $todayGain["totalAllNation"] = $totalAllNation;
        $today_gain =  $cash_back + $citizenAffiliated + $shopAffiliated + $totalProfPersFollower + $totalAllNation;
        $todayGain["today_gain"] = $today_gain ;
         return $todayGain;
    }

    /**
     * Get Citizen Wallet Details
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function getavailablecitizenincomeAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('citizen_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->getDoctrine()->getManager();

        /* Get citizen available income */
        $walletData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getavailablecitizenincome($de_serialize['citizen_id']);

        if($walletData) {
            $data = $walletData[0];

            $responseArray = array(
                    'currency'                  => (!empty($data['currency'])) ? $data['currency'] : '',
                    'currency_symbol'           => $walletService->getCurrencyCode($data['currency']),
                    'total_citizen_income'      => (!empty($data['citizenIncomeGained'])) ? number_format($walletService->convertCurrency($data['citizenIncomeGained']), 2, '.', '') : '0.00'
                );

            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseArray)), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
    /**
     * GEt top 50 citizen as per the
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function gettopcitizenperincomeAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('limit');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        } 

        $em = $this->getDoctrine()->getManager();

        /* Get top 50 citizens */
        $citizenIncomeData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getTopCitizenPerIncome($de_serialize['limit']);

        if(!empty($citizenIncomeData)) {
            foreach($citizenIncomeData as $key => $val) {
                /* Get citizen detail */
                $user = $em
                            ->getRepository('UserManagerSonataUserBundle:User')
                            ->findBy(array('id' => $val->getbuyerId()));
                
                $userData = $user[0];
                $profile_pic = (!empty($userData->getprofileImageName())) ? $this->getS3BaseUri() . $this->profile_image_path . $userData->getId(). '/' . $userData->getprofileImageName() : '';  
                
                $responseData[] = array(
                            'user_info' => array(
                                    'id'            => $userData->getId(),
                                    'email'         => $userData->getemail(),
                                    'firstname'     => $userData->getfirstname(),
                                    'lastname'      => $userData->getlastname(),
                                    'profile_image' => $profile_pic
                                ),
                            'position_number'   => intval($key+1),
                            'currency'          => $val->getcurrency(),
                            'currency_symbol'   => $walletService->getCurrencyCode($val->getcurrency()),
                            'tot_amount'        => number_format($val->getcitizenIncomeGained()/100, 2, '.', '')
                    );
            }
            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData)), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }



    
     /**
     * GET List of all ShppoingCards and Cards List 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */

   public function getcitizenshoppingcardsdetailsAction(Request $request) {
   
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        if(isset($de_serialize['search_shop'])){

           $search_name = $de_serialize['search_shop'];
        } 
        else{

           $search_name = "";
        }
        if(isset($de_serialize['from_date'])){

           $from_date = $de_serialize['from_date'];
        } 
        else{

           $from_date = "";
        }

        if(isset($de_serialize['to_date'])){

           $to_date = $de_serialize['to_date'];
        } 
        else{

           $to_date = "";
        }
 
        $walletService = $this->get('wallet_manager');

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

        
        /* Get wallet Data */
        $em = $this->getDoctrine()->getManager();
        $wdata = 0;

        $walletData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getWalletData($de_serialize['buyer_id']);
       
       if($walletData) {
         
          $wdata = $walletData[0];
        }
        
        $data['wallet_citizen_id'] = $wdata->getId();
        $data['buyer_id'] = $wdata->getbuyerId();
        $data['offset'] = $de_serialize['limit_start'];
        $data['limit'] = $de_serialize['limit_size'];
        $data['search_name'] = $search_name;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        /* Get Shopping card Data */

        $shoppingcardData = $em
                        ->getRepository('WalletBundle:ShoppingCard')
                        ->getShoppingCards($data);

        $my_wallet_upto100_count['my_wallet_upto100_count']['result'] = array();

        $scData['my_wallet_upto100'] = array();

        $my_wallet_upto50_count['my_wallet_upto50_count']['result'] = array();

        $cResponse['my_wallet_upto50'] = array();

        $has_next['dataInfo'] =  array();
        
        // Count Shopping Card upto 100% 

        $countShoppingCardUpto100 = $em
                        ->getRepository('WalletBundle:ShoppingCard')
                        ->countShoppingCardUpto100($data);

       if(!empty($countShoppingCardUpto100)){

            $countShoppingCardUpto100 = $countShoppingCardUpto100[0]['recordcount'];
        }
        else
        {
           $countShoppingCardUpto100 = 0;  
        }
       
        if($shoppingcardData) {
     
         $my_wallet_upto100_count['my_wallet_upto100_count']['result'][] = array('count' => $countShoppingCardUpto100);
 
         foreach($shoppingcardData as $sdata) {

            $credit_amount = ($sdata['credit']/100);
            
            $url = $this->getS3BaseUri().$this->shoppingcart_image_path.$credit_amount.'.png'; 
    
            if(date('m-d-Y',strtotime($sdata['endtime'])) == "01-01-1970"){
             
              $sdata['endtime'] = '';
            }
            else{
                $sdata['endtime'] =  date('m-d-Y',strtotime($sdata['endtime']));
            }

            $shoppingcardsResponse[] =  array(
                       '_id'         => $sdata['scid'],
                       'credit'      => number_format((float)$credit_amount,2,'.',''),
                       'balance'     => number_format((float)($sdata['balance']/100),2,'.',''),
                       'date'        => date('m-d-y',strtotime($sdata['starttime'])),
                       'card_image'  => $url,
                       'shop_id' => array(
                                        '_id' => $sdata['shopid'],
                                        'countryname' => $sdata['business_country'],
                                        'address_l1' => $sdata['province'],
                                        'name' => $sdata['business_name'],
                                        'mobile_no' => $sdata['phone'],
                                        'address_l2' => $sdata['business_address'],
                                        'region' => $sdata['business_region'],
                                        'province' => $sdata['province'],
                                        'zip' => $sdata['zip'],
                                        'email_address' => $sdata['email'],
                                        'latitude' => $sdata['latitude'],
                                        'longitude' => $sdata['longitude'],
                                        'average_anonymous_rating' => $sdata['avg_rate']
                                        ),
                       'from_date'=> date('m-d-Y',strtotime($sdata['starttime'])),
                       'to_date'=> $sdata['endtime'],
                       'card_no'=> $sdata['shopping_card_id']
                     );
               }


               // Calculate Paginataion 

                $offset = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : '');

                $limit_size = (isset($de_serialize['limit_size']) ?$de_serialize['limit_size'] : '');

                if ($offset == 0) {

                    $check_status = $limit_size;
                } else {

                    $check_status = ($offset * $limit_size);
                }

                if ($check_status > $countShoppingCardUpto100) {

                    $has_next['dataInfo'] = array('hasNext' => false);
                } else {

                    $has_next['dataInfo'] = array('hasNext' => true);
                }  

            //$has_next['dataInfo'] = array("hasNext"=>true); 
            $result['result'] =  $shoppingcardsResponse;
            $scData['my_wallet_upto100'] =  $result + $has_next;

         }
            
      
         /* Get Cards Data */


         // Count Shopping Card upto 100% 

        $countShoppingCardUpto50 = $em
                        ->getRepository('WalletBundle:Card')
                        ->countShoppingCardUpto50($data);

       if(!empty($countShoppingCardUpto50)){

            $countShoppingCardUpto50 = $countShoppingCardUpto50[0]['recordcount'];
        }
        else
        {
           $countShoppingCardUpto50 = 0;  
        }

         $cardData = $em
                        ->getRepository('WalletBundle:Card')
                        ->getCards($data);

         if($cardData) {

          $my_wallet_upto50_count['my_wallet_upto50_count']['result'][] = array('count' => $countShoppingCardUpto50);  

          foreach ($cardData as $cart_data) {

           $credit_amount =  ($cart_data['credit']/100);
           $url = $this->getS3BaseUri().$this->cart_image_path.$credit_amount.'.png'; 
      
    
            $cardsResponse[] = array(
               '_id'             => $cart_data['cardid'],
               'credit'          => number_format((float)$credit_amount,2,'.',''),
               'balance'         => number_format((float)($cart_data['balance']/100),2,'.',''),
               'from_date'       => date('m-d-Y',strtotime($cart_data['starttime'])),
               'card_image'      => $url,
               'shop_id'         => array(
                                    '_id' => $cart_data['shopid'],
                                    'countryname' => $cart_data['business_country'],
                                    'address_l1' => $cart_data['province'],
                                    'name' => $cart_data['business_name'],
                                    'mobile_no' => $cart_data['phone'],
                                    'address_l2' => $cart_data['business_address'],
                                    'region' => $cart_data['business_region'],
                                    'province' => $cart_data['province'],
                                    'zip' => $cart_data['zip'],
                                    'email_address' => $cart_data['email'],
                                    'latitude' => $cart_data['latitude'],
                                    'longitude' => $cart_data['longitude'],
                                    'average_anonymous_rating' => $cart_data['avg_rate']
                                 ),
               'card_no'=> $cart_data['cardno']
              );

            }

                // Calculate Paginataion 

                $offset = (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : '');

                $limit_size = (isset($de_serialize['limit_size']) ?$de_serialize['limit_size'] : '');

                if ($offset == 0) {

                    $check_status = $limit_size;
                } else {

                    $check_status = ($offset * $limit_size);
                }

                if ($check_status > $countShoppingCardUpto50) {

                    $has_next['dataInfo'] = array('hasNext' => false);
                } else {

                    $has_next['dataInfo'] = array('hasNext' => true);
                } 
             //$has_next['dataInfo'] = array("hasNext"=>false); 
             $result['result'] =  $cardsResponse;
             $cResponse['my_wallet_upto50'] =  $result + $has_next;
          }

          if($cardData == "" && $shoppingcardData == "") {
             echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
           } 
           else
           {
              echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => $scData + $my_wallet_upto100_count + $cResponse + $my_wallet_upto50_count ), JSON_UNESCAPED_UNICODE);
           }
        
           exit();
    }

     /**
     * GET List of all Coupons List 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */

    public function getcitizencouponsAction(Request $request) {
   
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        if(isset($de_serialize['search_shop'])){

           $search_name = $de_serialize['search_shop'];
        } 
        else{

           $search_name = "";
        }
        if(isset($de_serialize['from_date'])){

           $from_date = $de_serialize['from_date'];
        } 
        else{

           $from_date = "";
        }

        if(isset($de_serialize['to_date'])){

           $to_date = $de_serialize['to_date'];
        } 
        else{

           $to_date = "";
        }

        $walletService = $this->get('wallet_manager');

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
        $wdata = 0;
        $walletData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getWalletData($de_serialize['buyer_id']);
       
       if($walletData) {
         
          $wdata = $walletData[0];
        }
        
        $data['wallet_citizen_id'] = $wdata->getId();
        $data['buyer_id'] = $wdata->getbuyerId();
        $data['offset'] = $de_serialize['limit_start'];
        $data['limit'] = $de_serialize['limit_size'];
        $data['search_name'] = $search_name;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        /* Get Coupons Data */

        $couponData = $em
                        ->getRepository('WalletBundle:Coupon')
                        ->getCoupons($data);

        $my_wallet_coupons_count['my_wallet_coupons_count']['result'] = array();
        $responseArray = array();

        if($couponData != "") {

        $my_wallet_coupons_count['my_wallet_coupons_count']['result'][] = array('count' => count($couponData));  
   
         foreach ($couponData as $cpnData) {
        
           $data = $cpnData;

            $credit_amount =  ($data['credit']/100);
            $url = $this->getS3BaseUri().$this->coupon_image_path.$credit_amount.'.png'; 
      
            $responseArray[] = array(
               '_id'         => $data['cpnid'],
               'credit'      => number_format((float)($credit_amount),2,'.',''),
               'balance'     => number_format((float)($data['balance']/100),2,'.',''),
               'discount'    => number_format((float)($data['max_usage_init_price']),2,'.',''),
               'from_date'=> date('m-d-Y',strtotime($data['starttime'])),
               'to_date'=> date('m-d-Y',strtotime($data['endtime'])),
               'card_image'  => $url,
               'shop_id'     => array(
                                '_id' => $data['shopid'],
                                'countryname' => $data['business_country'],
                                'address_l1' => $data['province'],
                                'name' => $data['business_name'],
                                'mobile_no' => $data['phone'],
                                'address_l2' => $data['business_address'],
                                'region' => $data['business_region'],
                                'province' => $data['province'],
                                'zip' => $data['zip'],
                                'email_address' => $data['email'],
                                'latitude' => $data['latitude'],
                                'longitude' => $data['longitude'],
                                'average_anonymous_rating' => $data['avg_rate']
                               ),
                 'card_no'=> $data['couponno']
                );
            }

            $has_next['dataInfo'] = array("hasNext"=>false); 
            $result['result'] =  $responseArray;
            $couponResponse['my_wallet_coupons'] =  $result + $has_next;

            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => $couponResponse + $my_wallet_coupons_count), JSON_UNESCAPED_UNICODE);
        } else {
           echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    public function getcitizenwallethistoryAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('citizen_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->getDoctrine()->getManager();
        $TransactionHistory = $em->getRepository('WalletBundle:WalletCitizen')
                                                ->getcitizenwallethistory(array('buyer_id' => $de_serialize['citizen_id'], 'limit' => $de_serialize['limit'], 'offset' => $de_serialize['skip']));
       
        $resultResponse = array();
        $store_detail = array();
        $store_cat_detail = array();
        $TransactionHistoryCount['historyCount'] = 0;
        $TransactionHistoryCount['hasNext'] = false;
            
        if(!empty($TransactionHistory)) {
            $TransactionHistoryCount = $em->getRepository('WalletBundle:WalletCitizen')
                                                            ->getcountofwallethistory(array('buyer_id' => $de_serialize['citizen_id'], 'limit' => $de_serialize['limit'], 'offset' => $de_serialize['skip']));
            
            foreach($TransactionHistory as $val) {
                /* Get store detail */
                $store_detail = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getcitizenhistoryshopdetail(array('store_id' => $val->getsellerId()));
                
                /* Get business cat detail */
                if(!empty($store_detail)) {
                    $store_cat_detail = $em
                                                    ->getRepository('WalletBundle:WalletCitizen')
                                                    ->getcitizenhistoryshopcatdetail(array('cat_id' => $store_detail[0]->getsaleCatid()));
                }
                
                /* Create response result */
                $time = $val->gettimeInitH();
                $resultResponse[] = array(
                    '_id' => array(
                        'record_from' => 'Transaction',
                        'group_column' => $val->getId(),
                        'date' => array(
                                'date' => intval($time->format('d')),
                                'month' => intval($time->format('n')),
                                'year' => intval($time->format('Y'))
                            )
                    ),
                    'shop_id' => array(
                        '_id' => (!empty($store_detail)) ? $store_detail[0]->getId() : '',
                        'category_id' => array(
                            '_id' => (!empty($store_cat_detail)) ? $store_cat_detail[0]->getId() : '',
                            'name' => (!empty($store_cat_detail)) ? $store_cat_detail[0]->getcategoryName() : ''
                        ),
                        'name' => (!empty($store_detail)) ? $store_detail[0]->getbusinessName() : ''
                    ),
                    'record_from' => 'Transaction',
                    'group_column' => $val->getId(),
                    'citizen_transaction_id' => array(
                        '_id' => $val->getsixCTransactionId(),
                        'id' =>  $val->getsixCTransactionId()
                    ),
                    'credit' => number_format($walletService->convertCurrency($val->getinitPrice()), 2, '.', ''),
                    'debit' => '0.00',
                    'transaction_value' => number_format($walletService->convertCurrency($val->getinitPrice()), 2, '.', ''),
                    'scct_id' => NULL,
                    'app_name' => NULL,
                    'date' => $time->format('Y-m-d H:i:s')
                );
            }
        }
            
        /* Create return response */
        $returnArr = array(
            'response' => array(
                'citizenWalletHistoryRecord' => array(
                    'result' => (!empty($resultResponse)) ? $resultResponse : array(),
                    'dataInfo' => array(
                        'hasNext' => $TransactionHistoryCount['hasNext']
                    )
                ),
                'citizenWalletHistoryCount' => array(
                    'result' => array(
                            array(
                                '_id' => NULL,
                                'count' => $TransactionHistoryCount['historyCount']
                            )
                        ),
                    'dataInfo' => array(
                        'hasNext' => $TransactionHistoryCount['hasNext']
                    )
                )
            ),
            'status' => 'OK',
            'code' => 200,
            'serverTime' => '',
            'serviceLogId' => ''
        );
            
        echo json_encode($returnArr);
        exit();
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    public function getcitizenwallethistorydetailAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('record_from', 'group_column');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->getDoctrine()->getManager();
        
        if($de_serialize['record_from'] == 'Transaction') {
            $this->getcitizenTransactionDetail($de_serialize['group_column']);
        } elseif($de_serialize['record_from'] == 'CI') {
            $this->getCitizenCITransactionHistory($de_serialize['group_column']);
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
    public function getcitizenTransactionDetail($TransactionId) {
        $TransactionData = array();
        $serializeData = array();
        $store_detail = array();
        $store_cat_detail = array();
        $resultArr = array();
        
        $em = $this->getDoctrine()->getManager();
        /* get transaction data */
        $TransactionData = $em->getRepository('TransactionSystemBundle:Transaction')
                                            ->findby(array('id' => $TransactionId));

        /* Create  array of transaction uses credits */
        if(!empty($TransactionData)) {
            $time = $TransactionData[0]->gettimeInitH();
            $serializeData = unserialize($TransactionData[0]->gettransactionSerialize());

            /* Get store detail */
            $store_detail = $em
                    ->getRepository('WalletBundle:WalletCitizen')
                    ->getcitizenhistoryshopdetail(array('store_id' => $TransactionData[0]->getsellerId()));

            /* Get business cat detail */
            if(!empty($store_detail)) {
                $store_cat_detail = $em
                                                ->getRepository('WalletBundle:WalletCitizen')
                                                ->getcitizenhistoryshopcatdetail(array('cat_id' => $store_detail[0]->getsaleCatid()));
            }
            
            /* Create array of shop */
            $shopArr = array(
                                     '_id' => $TransactionData[0]->getsellerId(),
                                     'category_id' => array(
                                            '_id' => (!empty($store_cat_detail)) ? $store_cat_detail[0]->getId() : '',
                                            'name' => (!empty($store_cat_detail)) ? $store_cat_detail[0]->getcategoryName() : ''
                                      ),
                                      'name' => (!empty($store_detail)) ? $store_detail[0]->getbusinessName() : '',
                                      'is_shop_deleted' => false
                                );
            
            $TxnId = (!empty($TransactionData)) ? $TransactionData[0]->getsixcTransactionId() : '';
            $timeFormat = $time->format('Y-m-d H:i:s');
            
            /* Create result array of credit usage */
             /* Coupon usage response */
             if(!empty($serializeData['coupon_data']) && $serializeData['coupon_usage']) {
                 $couponData = $em->getRepository('WalletBundle:Coupon')
                                           ->findby(array('id' => $serializeData['coupon_data']['id']));
                 
                 $resultArr[] = array(
                            '_id' => $TxnId,
                            'shop_id' => $shopArr,
                            'id' => $TxnId,
                            'date' => $timeFormat,
                            'discount_details' => array(
                                'type' => array(
                                    '_id' => (!empty($couponData)) ? $couponData[0]->getcouponId() : '',
                                    'name' => 'Coupon usage'
                                ),
                                'amount' => (!empty($couponData)) ? number_format($couponData[0]->getinitAmount()/100, 2, '.', '') : '',
                                'card_id' => array(
                                    '_id' => (!empty($couponData)) ? $couponData[0]->getcouponId() : '',
                                    'card_no' => (!empty($couponData)) ? $couponData[0]->getcouponId() : ''
                                ),
                                'used_amount' => ($serializeData['coupon_usage']['amount_used'] > 0) ? number_format($serializeData['coupon_usage']['amount_used']/100, 2, '.', '') : '0.00'
                            )
                     );
             }
             
             /* Premium position usage response */
             if(!empty($serializeData['new_credit_position_data']) && $serializeData['credit_position_usage']) {
                 $creditPositionData = $em->getRepository('WalletBundle:CreditPosition')
                                                        ->findby(array('id' => $serializeData['new_credit_position_data']['id']));
                 
                 $resultArr[] = array(
                            '_id' => $TxnId,
                            'shop_id' => $shopArr,
                            'id' => $TxnId,
                            'date' => $timeFormat,
                            'discount_details' => array(
                                'type' => array(
                                    '_id' => (!empty($creditPositionData)) ? $creditPositionData[0]->getpremiumId() : '',
                                    'name' => 'Premium position usage'
                                ),
                                'amount' => (!empty($creditPositionData)) ? number_format($creditPositionData[0]->getamount()/100, 2, '.', '') : '',
                                'card_id' => array(
                                    '_id' => (!empty($creditPositionData)) ? $creditPositionData[0]->getpremiumId() : '',
                                    'card_no' => (!empty($creditPositionData)) ? $creditPositionData[0]->getpremiumId() : ''
                                ),
                                'used_amount' => ($serializeData['new_credit_position_data']['amount_used'] > 0) ? number_format($serializeData['new_credit_position_data']['amount_used']/100, 2, '.', '') : '0.00'
                            )
                     );
             }
             
             if(!empty($serializeData['card_data']) && !empty($serializeData['card_usage'])) {
                /* Get used card detail */
                $cardData = $em->getRepository('WalletBundle:Card')
                                           ->findby(array('id' => $serializeData['card_data']['id']));
                $resultArr[] = array(
                        '_id' => $TxnId,
                        'shop_id' => $shopArr,
                        'id' => $TxnId,
                        'date' => $timeFormat,
                        'discount_details' => array(
                            'type' => array(
                                '_id' => (!empty($cardData)) ? $cardData[0]->getcardId() : '',
                                'name' => 'Shopping Card Upto 50%'
                            ),
                            'amount' => (!empty($cardData)) ? number_format($cardData[0]->getinitAmount()/100, 2, '.', '') : '',
                            'card_id' => array(
                                '_id' => (!empty($cardData)) ? $cardData[0]->getcardId() : '',
                                'card_no' => (!empty($cardData)) ? $cardData[0]->getcardId() : ''
                            ),
                            'used_amount' => ($serializeData['card_usage']['amount_used'] > 0) ? number_format($serializeData['card_usage']['amount_used']/100, 2, '.', '') : '0.00'
                        )
                 );
            }
             
             /* Shopping card usage response */
             if(!empty($serializeData['shopping_card_data']) && !empty($serializeData['shopping_card_usage'])) {
                 foreach($serializeData['shopping_card_usage'] as $val) {
                     $scData = $em->getRepository('WalletBundle:ShoppingCard')
                                           ->findby(array('id' => $val['id']));
                     
                     $resultArr[] = array(
                            '_id' => $TxnId,
                            'shop_id' => $shopArr,
                            'id' => $TxnId,
                            'date' => $timeFormat,
                            'discount_details' => array(
                                'type' => array(
                                    '_id' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : '',
                                    'name' => 'Shopping Card Upto 100%'
                                ),
                                'amount' => (!empty($scData)) ? number_format($scData[0]->getinitAmount()/100, 2, '.', '') : '',
                                'card_id' => array(
                                    '_id' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : '',
                                    'card_no' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : ''
                                ),
                                'used_amount' => ($val['used_data']['amount_used'] > 0) ? number_format($val['used_data']['amount_used']/100, 2, '.', '') : '0.00'
                            )
                     );
                 }
             }
             
             /* citizen income usage response */
             if(!empty($serializeData['new_card_usage'])) {
                 /* Get used new card detail */
                $newCardData = $em->getRepository('WalletBundle:Card')
                                           ->findby(array('id' => $serializeData['new_card_usage']['id']));
                 $resultArr[] = array(
                        '_id' => $TxnId,
                        'shop_id' => $shopArr,
                        'id' => $TxnId,
                        'date' => $timeFormat,
                        'discount_details' => array(
                            'type' => array(
                                '_id' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : '',
                                'name' => 'Shopping Card Upto 50%'
                            ),
                            'amount' => (!empty($newCardData)) ? number_format($newCardData[0]->getinitAmount()/100, 2, '.', '') : '',
                            'card_id' => array(
                                '_id' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : '',
                                'card_no' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : ''
                            ),
                            'used_amount' => ($serializeData['new_card_usage']['amount_used'] > 0) ? number_format($serializeData['new_card_usage']['amount_used']/100, 2, '.', '') : '0.00'
                        )
                 );
             }
             
             /* Cash payment return response */
             $data1Arr = array(
                            '_id' => $TxnId,
                            'shop_id' => $shopArr,
                            'date' => $timeFormat,
                            'payble_value' => (!empty($TransactionData)) ? number_format($TransactionData[0]->getfinalprice()/100, 2, '.', '') : '0.00'
                     );
        }
        
        $returnArr = array(
            'response' => array(
                'data' => array(
                    'result' => $resultArr,
                    'dataInfo' => array(
                        'hasNext' => false
                    )
                ),
                'data1' => array(
                    'result' => $data1Arr,
                    'dataInfo' => array(
                        'hasNext' => false
                    )
                ),
                'status' => 'ok',
                'code' => 200,
                'serverTime' => '',
                'query' => array(
                    'data' => array(
                        '$collection' => 'Transaction',
                        '$fields' => array(
                            'date' => 1,
                            'discount_details.amount' => 1,
                            'discount_details.amount_used' => 1,
                            'discount_details.balance' => 1,
                            'discount_details.type' => 1,
                            'discount_details.card_id' => 1,
                            'shop_id' => (!empty($TransactionData)) ? $TransactionData[0]->getsellerId() : '',
                            'id' => (!empty($TransactionData)) ? $TransactionData[0]->getId() : '',
                        ),
                        '$filter' => array(
                            '_id' => '',
                            'discount_details.type.id' => array(
                                '$in' => array()
                            )
                        ),
                        '$unwind' => array('discount_details')
                    ),
                    'data1' => array(
                        '$collection' => 'Transa•••••••••ction',
                        '$fields' => array(
                           'date' => 1,
                           'payble_value' => (!empty($TransactionData)) ? $TransactionData[0]->getfinalPrice() : '0.00',
                           'shop_id' => (!empty($TransactionData)) ? $TransactionData[0]->getsellerId() : ''
                        ),
                        '$filters' => array(
                            '_id' => ''
                        )
                    )
                ),
                'serviceLogId' => ''
            )
        );
        echo json_encode($returnArr);
    }
    
    public function getCitizenCITransactionHistory($TransactionId) {
        echo '{"response":{"result":[{"_id":"551fb92a994df33026af6eba","reason_id":{"_id":"551fb92a994df33026af6eba","name":"Country Citizen"},"credit":0.030688619033291814},{"_id":"551fb92a994df33026af6eb6","reason_id":{"_id":"551fb92a994df33026af6eb6","name":"Friend"},"credit":0.11122390520394017}],"dataInfo":{"hasNext":false}},"status":"ok","code":200,"serverTime":21,"serviceLogId":""}';
    }
    
    /*
     * Change citizen transaction preference
     * @param requestObj
     */
    public function managecitizenprefrenceAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('citizen_id', 'setting');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->getDoctrine()->getManager();
        $updateArr = array(
            'buyer_id' => $de_serialize['citizen_id'],
            'setting' => $de_serialize['setting']
        );
        $updateRes = $em->getRepository('WalletBundle:WalletCitizen')
                                     ->updateWalletSettings($updateArr);
        if($updateRes) {
            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'data' => array('message' => 'SETTINGS_UPDATED_SUCCESSFULLY')));
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
    /*
     * Get tachimeter status based on the user transactions
     * @param $request
     */
    public function tachimeterstatusAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('citizen_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp_data = new Resp($result_data['code'] = 1029, $result_data['message'] = 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             Utility::createResponseResult($resp_data);
        }

        $em = $this->getDoctrine()->getManager();

        $result_data = $em->getRepository('WalletBundle:WalletCitizen')
                                      ->citizenTacimeter(array('buyer_id' => $de_serialize['citizen_id']));
        
        /* Return Response */
        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"]);
        Utility::createResponseResult($resp_data);
    }
    
    public function noribredistributionAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $required_parameter = array('citizen_id', 'single_share');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp_data = new Resp($result_data['code'] = 1029, $result_data['message'] = 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             Utility::createResponseResult($resp_data);
        }

        $em = $this->getDoctrine()->getManager();
        
        $result_data = $em->getRepository('WalletBundle:WalletCitizen')
                                      ->noribdistribution($de_serialize);
        
        /* Return Response */
        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"]);
        Utility::createResponseResult($resp_data);
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