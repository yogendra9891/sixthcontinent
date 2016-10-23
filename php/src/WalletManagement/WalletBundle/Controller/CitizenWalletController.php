<?php

namespace WalletManagement\WalletBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use StoreManager\StoreBundle\Controller\ShoppingplusController;

class CitizenWalletController extends Controller {
    
    /**
     * Get Citizen Wallet
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */

    public function postCitizenwalletsAction(Request $request){
        //initilise the array
        $data = array();
        $citizen_income = 0;
        $citizen_income_currency = 0;
        $total_credit_available_currency = 0;
        $shots_array = array();
        $gift_card_array = array();
        $momosy_card_array = array();
        $total_credit_available = 0;
        $shots_final_array = array();
        $gift_cards_final_array = array();
        $momosy_cards_final_array = array();
        $discount_position_array = array();
        $total_citizen_income = 0;
        
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
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        
        $user_id = $object_info->user_id;
        
        $total_credit_available_needed   = (isset($object_info->total_credit_available_needed)) ? $object_info->total_credit_available_needed : 1;
        $total_citizen_income_needed     = (isset($object_info->total_citizen_income_needed)) ? $object_info->total_citizen_income_needed : 1;
        $shots_needed           = (isset($object_info->shots_needed)) ? $object_info->shots_needed : 1;
        $purchase_card_needed   = (isset($object_info->purchase_card_needed)) ? $object_info->purchase_card_needed : 1;
        $momosy_card_needed     = (isset($object_info->momosy_card_needed)) ? $object_info->momosy_card_needed : 1;
        $discount_position_needed   = (isset($object_info->discount_position_needed)) ? $object_info->discount_position_needed : 1;
        
        $purchase_card_limit_start  = (isset($object_info->purchase_card_limit_start)) ? $object_info->purchase_card_limit_start : 0;
        $purchase_card_limit_size   = (isset($object_info->purchase_card_limit_size)) ? $object_info->purchase_card_limit_size : 20;
        
        $shots_card_limit_start     = (isset($object_info->shots_card_limit_start)) ? $object_info->shots_card_limit_start : 0;
        $shots_card_limit_size      = (isset($object_info->shots_card_limit_size)) ? $object_info->shots_card_limit_size : 20;
        
        $momosy_card_limit_start    = (isset($object_info->momosy_card_limit_start)) ? $object_info->momosy_card_limit_start : 0;
        $momosy_card_limit_size     = (isset($object_info->momosy_card_limit_size)) ? $object_info->momosy_card_limit_size : 20;
        
        //$citizen_expenses_needed     = (isset($object_info->citizen_expenses_needed)) ? $object_info->citizen_expenses_needed : 0;
        //$citizen_expenses_limit_start    = (isset($object_info->citizen_expenses_limit_start)) ? $object_info->citizen_expenses_limit_start : 0;
        //$citizen_expenses_limit_size     = (isset($object_info->citizen_expenses_limit_size)) ? $object_info->citizen_expenses_limit_size : 20;
        
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //get Citizen Income
//        $citizen_incomes = $em
//                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
//                        ->getCitizenIncome($user_id);
                    
        //get citizen income from the cardsaldo service.
        $citizen_incomes_response = $this->getCitizenincome($user_id);
        
        //get service object
        //$shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        //$citizen_incomes = $shoppingplus_obj->getCitizenIncomeFromCardsoldo($user_id);

        //$citizen_incomes = $this->getCitizenincome($user_id);
       
//        if($citizen_incomes){
//            $citizen_income = $citizen_incomes;
//            $citizen_income_currency = $this->convertCurrency($citizen_income);
//        }
//        
//        if($total_citizen_income_needed){
//            $total_citizen_income = $citizen_income_currency;
//        }
        
//       if($total_credit_available_needed){
//        //get total_credit_availabe
//        $citizen_balance = $em
//                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
//                        ->getCitizenBalance($user_id);
//         
//        if($citizen_balance){
//        $total_credit_available = $citizen_income+($citizen_balance['balanceMomosycard'] + $citizen_balance['balanceShots'] + $citizen_balance['balanceGiftcard']);
//        $total_credit_available_currency = $this->convertCurrency($total_credit_available);
//        }else{
//        //if no entry found in usershopcredit table.
//        $total_credit_available = $citizen_income;
//        $total_credit_available_currency = $this->convertCurrency($total_credit_available);
//        }
//       }
        
        $total_credit_available = $citizen_incomes_response['total_credit_ava'];
        $citizen_income = $citizen_incomes_response['total_income'];
        
        $total_credit_available_currency = $this->convertCurrency($total_credit_available);
        
        $total_citizen_income = $this->convertCurrency($citizen_income);
      
        //if shots needed
        if($shots_needed){
            
        //get total citizen shots
        $total_citizen_shots = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->citizenShotsCount($user_id);
        
        
        //get Shots
        $shots = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->getCitizenShots($user_id, $shots_card_limit_start, $shots_card_limit_size);
        
        if($shots){
            foreach($shots as $shot){
                //get shop id
                $shop_id = $shot['shopId'];
                $value = $shot['totalShots'];
                $value_currency = $this->convertCurrency($value);
                $balance = $shot['balanceShots'];
                $balance_currency = $this->convertCurrency($balance);
                //get shop object
                $shop_service           = $this->get('user_object.service');
                $shop_object            = $shop_service->getStoreObjectService($shop_id);
           
                $shots_array[] = array('shop_name' => $shop_object['businessName'], 'shop_info' =>$shop_object, 'value' => $value_currency, 'balance' => $balance_currency);
            }
        }
         $shots_final_array = array('shot' => $shots_array, 'total' => $total_citizen_shots);
        }
        
        //if gift card needed
        if($purchase_card_needed){
        //get total purchase cards
        $total_purchase_cards = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->citizenPurchaseCardsCount($user_id);
        
        //get gift card/purchase card
        $gift_cards = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->getCitizenGiftCards($user_id, $purchase_card_limit_start, $purchase_card_limit_size);
        
        if($gift_cards){
            foreach($gift_cards as $gift_card){
             
                //get shop id
                $shop_id = $gift_card['shopId'];
                $value = $gift_card['totalGiftCard'];
                $value_currency = $this->convertCurrency($value);
                $balance = $gift_card['balanceGiftCard'];
                $balance_currency = $this->convertCurrency($balance);
                //get shop object
                $shop_service           = $this->get('user_object.service');
                $shop_object            = $shop_service->getStoreObjectService($shop_id);
           
                $gift_card_array[] = array('shop_name' => $shop_object['businessName'], 'shop_info' =>$shop_object, 'value' => $value_currency, 'balance' => $balance_currency);
            }
        }
        
        $gift_cards_final_array = array('purchase_card' => $gift_card_array, 'total' => $total_purchase_cards);
        
        }
       
        //if momosy card needed
        if($momosy_card_needed){
         
        //get total purchase cards
        $total_momosy_cards = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->citizenMomosyCardsCount($user_id);
        
         //get momosy card
        $momosy_cards = $em
                        ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                        ->getCitizenMomosyCards($user_id, $momosy_card_limit_start, $momosy_card_limit_size);
        
        if($momosy_cards){
            foreach($momosy_cards as $momosy_card){
             
                //get shop id
                $shop_id = $momosy_card['shopId'];
                $value = $momosy_card['totalMomosyCard'];
                $value_currency = $this->convertCurrency($value);
                $balance = $momosy_card['balanceMomosyCard'];
                $balance_currency = $this->convertCurrency($balance);
                //get shop object
                $shop_service           = $this->get('user_object.service');
                $shop_object            = $shop_service->getStoreObjectService($shop_id);
           
                $momosy_card_array[] = array('shop_name' => $shop_object['businessName'], 'shop_info' =>$shop_object, 'value' => $value_currency, 'balance' => $balance_currency);
            } 

        }
        $momosy_cards_final_array = array('momosy_card' => $momosy_card_array, 'total' => $total_momosy_cards);
        }
        //if discount position needed
         if($discount_position_needed){
         //get discount postion
         $discount_positions = $em
                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                        ->getCitizenDiscountPositions($user_id);
         if($discount_positions){
             $total_discount_position = $discount_positions['totalDp'];
             $total_discount_position_currency = $this->convertCurrency($total_discount_position);
             $balance_discount_position = $discount_positions['balanceDp'];
             $balance_discount_position_currency = $this->convertCurrency($balance_discount_position);
             $discount_position_array = array('total_discount_position' => $total_discount_position_currency, 'balance_discount_position' => $balance_discount_position_currency);
         }
         }
       
         //get user expenses
//         $citizen_expenses = array();
//         if($citizen_expenses_needed){
//         $citizen_expenses = $this->getCitizenExpenses($user_id, $citizen_expenses_limit_start, $citizen_expenses_limit_size);
//         }
        
        //preapare response
        $resp_data = array('total_citizen_income' =>$total_citizen_income, 'total_credit_available' => $total_credit_available_currency,
            'discount_position'=> $discount_position_array ,'shots' => $shots_final_array,
            'purchase_cards' => $gift_cards_final_array, 'momosy_cards' => $momosy_cards_final_array);
        
        $datas = array('code' => 101, 'message' =>'SUCCESS', 'data' => $resp_data);
        
        echo json_encode($datas);
        exit;
    }
    
    
     /**
     * Get citizen income
     * @param int $citizen_id
     * @return array
     */
    public function getCitizenincome($citizen_id)
    {
            //get shopping plus class object
            $balance_ci = 0;
            $total_income = 0;
            $total_credit_available = 0;
            $shoppingplus = new ShoppingplusController();
            $param_array = array('idcard'=>$citizen_id);
            $params = json_encode($param_array);
            $request = new Request();
            //$params = '{"idcard":"12350"}';
            $request->attributes->set('reqObj',$params);
            $response = $shoppingplus->cardsoldsinternalAction($request);
            
            //check for response
           $decode_response = (object) $response;
          if ($decode_response->code == 100) {
            $stato = 0;
            $descrizione = 0;
            $saldoc = 0;
            $saldorc = 0;
            $saldorm = 0;
           } else {
            $stato = $decode_response->data['stato'];
            $descrizione = $decode_response->data['descrizione'];
            $saldoc = $decode_response->data['saldoc'];
            $saldorc = $decode_response->data['saldorc'];
            $saldorm = 0;
            if (isset($decode_response->data['saldorm'])) {
                $saldorm = $decode_response->data['saldorm'];
            }
         }
        //Totale Income :
        $total_income = (float) $saldorc + (float) $saldorm;

        //Total Credits Available:
        $total_credit_available = (float) $saldoc;
        
        $response = array('total_income'=>$total_income, 'total_credit_ava'=>$total_credit_available);
        return  $response;
    }

    
    /**
     * Crone service to update citizen income
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function croncitizenincomesAction(Request $request)
    {
        //increase memory size
        set_time_limit(0);
        ini_set('memory_limit','512M');
        
        $resp_data = array();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //get all users from user DP table
         //get Citizen Income
        $citizens= $em
                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                        ->findAll();
       if($citizens){
           foreach($citizens as $citizen){
            $citizen_id = $citizen->getUserId();
            
            //get shopping plus class object
            $shoppingplus = new ShoppingplusController();
            $param_array = array('idcard'=>$citizen_id);
            $params = json_encode($param_array);
            //$params = '{"idcard":"12350"}';
            $request->attributes->set('reqObj',$params);
            $response = $shoppingplus->cardsoldsinternalAction($request);
            
            //get response code
            $resp_code = $response['code'];
            if($resp_code == 101){
      
            $response_data = $response['data'];
            $balance_ci = $response_data['saldorc'];
            
            //update the user DP table
             $citizens= $em
                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                        ->UpdateCitizenIncome($citizen_id, $balance_ci);
           }
           }
        $data = array('code' => 101, 'message' =>'SUCCESS', 'data' => $resp_data);
        echo json_encode($data);
        exit;

       }
 
    }
    
    /**
     * Get citizen expenses
     * Citizen expenses will be by GiftCard or MomosyCardt
     * @param int $citizen_id
     * @return array
     */
    public function getCitizenExpenses($user_id, $limit_start, $limit_size)
    {
        $data = array();
        $gift_cards_purchased = array();
         //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $gift_cards_purchaseds = $em
                        ->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                        ->getGiftCardPurchased($user_id, $limit_start, $limit_size);
        if($gift_cards_purchaseds){
            
            foreach($gift_cards_purchaseds as $gift_cards_purchased){
                $puser_id = $gift_cards_purchased['userId'];
                $shop_id = $gift_cards_purchased['shopId'];
                $gift_card_amount = $this->convertCurrency($gift_cards_purchased['giftCardAmount']);
                $purchased_date = $gift_cards_purchased['date'];
                $date_format = date('Y/m/d', $purchased_date);
                $description = "Purchased gift card";
                $data[] = array('user_id' => $puser_id, 'shop_id'=> $shop_id, 
                    'purchased_amount' => $gift_card_amount, 'purchased_date'=>$date_format, 'type' =>'GIFT_CARD', 'description'=>$description);
            }
            
            //get total record count 
            $gift_cards_purchased_count = $em
                        ->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                        ->getGiftCardPurchasedCount($user_id);

            $resp_data = array('expenses' => $data, 'total'=>$gift_cards_purchased_count);
            return $resp_data;
            
        }
        
    }
    
    /**
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount)
    {
        $final_amount = (float)$amount/1000000;
        return $final_amount;
    }
    
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
     * get the citizen income getting from imported file.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postCitizenincomesAction(Request $request)
    {
        //initilise the array
        $data = array();
     
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
        $data = $result = array();
        $citizen_income_count = 0;
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $limit_start    = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
        $limit_size     = (isset($object_info->limit_size)) ? $object_info->limit_size : 20;
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $citizen_income_results = $em->getRepository('TransactionTransactionBundle:UserCitizenIncome')
                                    ->getCitizenTranaction($user_id, $limit_start, $limit_size);
        
       
        if (count($citizen_income_results)) {
            $citizen_income_count = $em->getRepository('TransactionTransactionBundle:UserCitizenIncome')
                                    ->getCitizenTranactionCount($user_id); 
        }
        foreach ($citizen_income_results as $citizen_income_result) {
            $user_id = $citizen_income_result['user_id'];
            $amount_c  = $this->convertCurrency($citizen_income_result['amount']);
            $date    = date('d/m/Y', $citizen_income_result['date']);
            $type = $citizen_income_result['type'];
            if($type == 1){
            $desc    = 'Gift Card Purchased';
            $amount_in = '';
            $amount_out = $amount_c;
            }else{
            $desc    = 'Citizen Income';
            $amount_in = $amount_c;
            $amount_out = '';
            }
            $result[] = array('user_id'=>$user_id, 'amount_in'=>$amount_in, 'amount_out'=>$amount_out, 'date'=>$date, 'description'=>$desc);
        }
        $resp_data = array('citizen_income'=>$result, 'total'=>$citizen_income_count);
        $data = array('code' => 101, 'message' =>'SUCCESS', 'data' => $resp_data);
        echo json_encode($data);
        exit;
    }
}