<?php
namespace Payment\TransactionProcessBundle\Controller;

use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Payment\TransactionProcessBundle\Services\TransactionManagerService;
use Payment\TransactionProcessBundle\Entity\Transaction;
use Payment\PaymentDistributionBundle\Controller\PaymentDistributionController;
use Payment\PaymentDistributionBundle\Entity\CitizenIncomeGainLog;
use Payment\PaymentDistributionBundle\Entity\CitizenIncomeGain;

class TransactionProcessController extends Controller {

    protected $is_card_use = array(1, 2); //1 for use , 0 for not use
    protected $credit_level = array(1, 2); //1 for below maximum, 2 for above minimum.
    protected $response = array(1, 2); //1 for accept , 2 for DENY
    protected $base_six = 1000000;
    protected $miss_param = '';

     /**
     * Initiate transaction
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postInitiatetransactionsAction(Request $request)
    {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id', 'credit_use',);
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        //extract parameters.
        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $type    = $object_info->credit_use; 
        $credit_level = (isset($object_info->credit_level) ? $object_info->credit_level : 1);
        //check if type is in specified array
        if (!in_array($type, $this->is_card_use)) {
            return array('code' => 172, 'message' => 'CARD_USE_VALUE_SUPPPORTED', 'data' => $data);
        }
        
        //check if credit level type is in specified array
        if (!in_array($credit_level, $this->credit_level)) {
            return array('code' => 175, 'message' => 'CREDIT_LEVEL_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }
        
        $time = new \DateTime('now');
        $transaction_data = array(
            'transaction_type'=>'citizen_manual',
            'citizen_id'=>$this->castToInt($user_id),
            'citizen_credit_level'=>$credit_level,
            'citizen_user_credit' => $type,
            'shop_id' => $shop_id,
            'description' => 'initiate transaction by citizen manually',
            'transaction_date' => $time,
            'transaction_amount' => 0,
            'total_credit_used' => 0,
            'discount_used' => 0,
            'cash_paid' => 0,
            'status' => 'NEW',
            'status_date' =>$time,
            'credit_disbursal_status' => 'PENDING',
            'disbursal_date'=>$time,
            'parent_transaction_id' => 0,
            'remarks' =>'initiate transaction by citizen manually',
        );
        
        //save cards amount into payment process credit.
        $transaction_obj = $this->savePaymentProcessCreditObject($transaction_data);

        //send notification type Notification_Acl = 5 to shop owner
        //get shop owner
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        $push_object_service = $this->container->get('push_notification.service');
        $shop_owners = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->getShopOwnerById($shop_id);
        
        $shop_owners_id = $shop_owners['userId'];  
        //get user devices
        $device_array = $push_object_service->getReceiverDeviceInfo($shop_owners_id);
        $from_id = $user_id;
        $to_id = array($shop_owners_id);
        $msg_code = 'TXN_CUST_PENDING';
        $msg = 'New Transaction by customer.';
        $ref_type = 'TXN';
        $ref_id = $transaction_obj['transaction_id'];
        $notification_role = 5;
        $client_type = "SHOP";
        $push_object_service->sendNotificationByRole($from_id, $to_id, $device_array, $msg_code, $msg, $ref_type, $ref_id, $notification_role, $client_type);
     
        //$notification_role = 1 for web notification
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $transaction_obj);
        $this->returnResponse($data);
    }
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
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
     * Convert number to int
     * @param type $number
     * @return type
     */
    public function castToInt($number)
    {
        return (int)$number;
    }
    
    
    
    /**
     * save card amount into payment process credit(temporary)
     * @param array $data
     * @return int $transaction_id
     */
    private function savePaymentProcessCreditObject($data) {
        //get doctrine object
        $em   = $this->get('doctrine')->getManager();
        $time = new \DateTime('now');
        
        $payment_credit = new Transaction(); //entity object
        
        $payment_credit->setTransactionType($data['transaction_type']);
        $payment_credit->setBuyerId($data['citizen_id']);
        $payment_credit->setCitizenCreditLevel($data['citizen_credit_level']);
        $payment_credit->setCitizenUserCredit($data['citizen_user_credit']);
        $payment_credit->setSellerId($data['shop_id']);
        $payment_credit->setDescription($data['description']);
        $payment_credit->setTransactionDate($data['transaction_date']);
        $payment_credit->setTransactionAmount($data['transaction_amount']);
        $payment_credit->setTotalCreditUsed($data['total_credit_used']);
        $payment_credit->setDiscountUsed($data['discount_used']);
        $payment_credit->setCashPaid($data['cash_paid']);
        $payment_credit->setStatus($data['status']);
        $payment_credit->setStatusDate($data['status_date']);
        $payment_credit->setCreditDisbursalStatus($data['credit_disbursal_status']);
        $payment_credit->setDisbursalDate($data['disbursal_date']);
        $payment_credit->setParentTransactionId($data['parent_transaction_id']);
        $payment_credit->setRemarks($data['remarks']);
       
       try {
            $em->persist($payment_credit);
            $em->flush();
            //get transaction id
            $transaction_id = $payment_credit->getId();
            //get transaction object
            //call service method
            $transaction_obj = $this->container->get('payment_transaction_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
            return $transaction_data_response = ($transaction_data_response ? $transaction_data_response : null);
        } catch (\Exception $ex) {
            return null;
        }
    }
    
    
    /**
     * Get user credit
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function postPaymentcreditsAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'amount', 'shop_id', 'transaction_id');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            echo json_encode($data);
            exit;
        }

        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $amount = $total_amount = $this->convertToEuroNumber($object_info->amount);
        $tid = $object_info->transaction_id;
        
        //check if transaction exist
        $em = $this->getDoctrine()->getManager();
        $check_transaction = $em->getRepository('PaymentTransactionProcessBundle:Transaction')
                                       ->findOneBy(array('id' => $tid));
        

        if(!$check_transaction){
            $data = array('code' => 170, 'message' => 'NO_TRANSACTION_FOUND', 'data' => $data);
            $this->returnResponse($data);
        } 
        
        $check_transaction_status = $check_transaction->getStatus();
        if($check_transaction_status != "NEW" && $check_transaction_status != "PENDING"){
            $data = array('code' => 1044, 'message' => 'TRANSACTION_OPERATION_HAS_ALREADY_PERFORMED', 'data' => $data);
            $this->returnResponse($data);
        }
        
        $object_info->credit_level = $check_transaction->getCitizenCreditLevel(); //set the transacion credit level.
        
        $tm = $this->container->get('payment_transaction_process.transaction_manager');
        
        $em = $this->container->get('doctrine')->getManager();
        $payment_credit = $em->getRepository('PaymentTransactionProcessBundle:Transaction')
                                       ->findOneBy(array('id' => $tid));
        $credit_use = 2;  //Want to use no credit
        if($payment_credit){
            $credit_use  = $payment_credit->getCitizenUserCredit();
        }
   
        if($credit_use == 1){
            //Use credit for transactions
            $this->useCreditsForTransactions($object_info);
        }
        else {
            //Not use credit
            $this->notUseCreditsForTransactions($object_info);
        }
    }
    
    
    /**
     * Use Cards For transaction
     * @param type $object_info
     */
    public function useCreditsForTransactions($object_info)
    {
        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $amount = $total_amount = $this->convertToEuroNumber($object_info->amount);
        $tid = $object_info->transaction_id;
        $credit_level = $object_info->credit_level;
        //call service method
        $tm = $this->container->get('payment_transaction_process.transaction_manager');
        $tm->userCards($user_id, $shop_id, $amount, $credit_level);
 
        //call service
        //check if coupon available
        $coupon = $tm->coupons;
        $premimum = $tm->premimum;
        $gift_card = $tm->total_citizen_income_available;
        $momosy_card = $tm->momosy_cards;
        $remaining_gift_cards = $tm->remaining_gift_cards;
        //if coupon is available
        if ($coupon > 0) {
            //call coupon service
            $response_coupon = $this->container->get('coupon.credit')->applyCredit($tm);
            //get balance amount
            $balance_amount = $this->getBalanceAmount($tm);
        } else {
            $this->setBalanceAmount($tm);
        }

        //if premium is available
        if ($premimum > 0 && $tm->balance_amount > 0) {
            //call coupon service
            $response_coupon = $this->container->get('premiumposition.credit')->applyCredit($tm);
            //get balance amount
            $balance_amount = $this->getBalanceAmount($tm);
        } else {
            $this->setBalanceAmount($tm);
        }

        //if gift card is available
        if (($gift_card > 0 && $tm->balance_amount > 0) ||  ($remaining_gift_cards > 0)) {
            //call Gift Card service
            $response_gift_card = $this->container->get('transaction.giftcard.credit')->applyCredit($tm);
            $balance_amount = $this->getBalanceAmount($tm);
        } else {
            $this->setBalanceAmount($tm);
        }

        //if momosy card is available
        if ($momosy_card > 0 ) {
            $this->setMomosyBalanceAmount($tm);
            if($tm->balance_amount > 0){
                //call Momosy Card service
                $response_momosy_card = $this->container->get('momosycard.credit')->applyCredit($tm);
                $balance_amount = $this->getBalanceAmount($tm);
            }
        } else {
            $this->setBalanceAmount($tm);
        }

        $coupons = $this->convertAmountToInt($tm->coupons);
        $premimum_position = $this->convertAmountToInt($tm->premimum);
        $gift_card   = $this->convertAmountToInt($tm->total_citizen_income_available); //total citizen income after block

        $momosy_card = $this->convertAmountToInt($tm->momosy_cards);
        $total_citizen_income = $this->convertAmountToInt($tm->total_citizen_income);
        $total_citizen_income_available = $this->convertAmountToInt($tm->total_citizen_income_available); //total citizen income after block
        $amount = $this->convertAmountToInt($tm->amount); //50% amount of total amount
        $balance_amount = $this->convertAmountToInt($tm->balance_amount);
        $used_coupons   = $this->convertAmountToInt($tm->used_coupons);
        $used_premium_position = $this->convertAmountToInt($tm->used_premium_positions);
        $used_gift_card   = $this->convertAmountToInt($tm->used_gift_cards);
        $used_momosy_card = $this->convertAmountToInt($tm->used_momosy_cards);
        $used_remaining_gift_cards = $tm->used_remaining_gift_cards;
        $remaining_gift_cards = $tm->remaining_gift_cards;
        $total_used = ($used_coupons + $used_premium_position + $used_gift_card + $used_momosy_card +$used_remaining_gift_cards - $remaining_gift_cards); //all used cards amount

        $total_amount = $this->convertAmountToInt($total_amount); 
        $shop_id = $shop_id;
        $balance_amount = $total_amount - $total_used;
        
        $user_service = $this->get('user_object.service');
        $stores_object = $user_service->getStoreObjectService($shop_id);
        $users_object = $user_service->userObjectService($user_id); 
        //$block_citizen_income = $tm->remaining_gift_cards;
        $transaction_data = array(
            'tid' => $tid,
            'user_id'=>$user_id,
            'shop_id'=>$shop_id,
            'user_info'=>$users_object,
            'store_info' =>$stores_object,
            'amount'=>$total_amount,
            'coupons' => $coupons,
            'used_coupons' => $used_coupons,
            'premium_position' => $premimum_position,
            'used_premium_position' => $used_premium_position,
            'gift_card' => $total_citizen_income_available,
            'used_gift_card' => $used_gift_card,
            'gift_card_packets' => $tm->gift_card_packets,
            'gift_card_setting' => $tm->gift_card_setting,
            'momosy_card' => $momosy_card,
            'used_momosy_card' => $used_momosy_card,
            'total_citizen_income' => $total_citizen_income_available,
            'cpg_amount' => $amount,
            'total_used'=>$total_used,
            'balance_amount' => $balance_amount,
            'used_remaining_gift_cards' => $used_remaining_gift_cards,
            'remaining_gift_cards' => $remaining_gift_cards
        );
        
        
       
        //save cards amount into Transaction.
        $transaction_id = $this->EditPaymentProcessCreditObject($transaction_data);
  
        if ($transaction_id) {
            //save gift cards..
            //$this->purchaseGiftCard($user_id, $shop_id, $tm->gift_card_packets);
            $transaction_data['transaction_id'] = $transaction_id; //append the transaction id
            
            //Save in transaction table for used GC
            $this->updateGiftCardInTransaction($transaction_data);
            
            
            //call service method
            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($tid);
            //$transaction_data_response = $this->getTransactionObject($tid);
            $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>$transaction_data_response);
        } else {
            $data = array('code'=>100, 'message'=>'ERROR_OCCURED', 'data'=>array());
        }
        
        $this->returnResponse($data);
    }
    
    /**
     * Update citizen credit
     * @param array $transaction_data
     */
    public function updateGiftCardInTransaction($transaction_data){
       
        $tid = $transaction_data['tid'];
        $gift_card_packets = $transaction_data['gift_card_packets'];
        $em   = $this->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $buyer_id = $transaction_data['user_id'];
        //insert Gift card for the transaction
       
        foreach($gift_card_packets as $gift_card_packet)
        {
        $payment_credit = new Transaction(); //entity object   
        $payment_credit->setTransactionType('CITIZEN_PAY_CITIZEN_CREDIT_AGAINST_CARD50');
        $payment_credit->setBuyerId($buyer_id);
        $payment_credit->setCitizenCreditLevel(0);
        $payment_credit->setCitizenUserCredit(0);
        $payment_credit->setSellerId('SC');
        $payment_credit->setDescription('CITIZEN_<CITIZEN_NAME>_PAY_FOR_UPTO50_FROM_SC');
        $payment_credit->setTransactionDate($time);
        $payment_credit->setTransactionAmount($gift_card_packet);
        $payment_credit->setTotalCreditUsed($gift_card_packet);
        $payment_credit->setDiscountUsed(0);
        $payment_credit->setCashPaid(0);
        $payment_credit->setStatus('PENDING');
        $payment_credit->setStatusDate($time);
        $payment_credit->setCreditDisbursalStatus('');
        $payment_credit->setDisbursalDate($time);
        $payment_credit->setParentTransactionId($tid);
        $payment_credit->setRemarks('NA');
        $em->persist($payment_credit);
        }
       
        $em->flush();
        die('d');
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array,JSON_NUMERIC_CHECK);
        exit;
    }


    /**
     * temporary method
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postCalculateblockcreditsAction(Request $request) {
        $user_id = 2;
        $tm = $this->container->get('payment_transaction_process.transaction_manager');
       // $tm->calculateBlockedCredit($user_id);
       // $tm->reduceBlockedCredit();
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
       $s =  $applane_service->getCitizenIncome('551d03d348619e4e34b7f060');
        exit('he is getting me..');
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
     * Convert to number
     * @param int $number
     * @return type
     */
    public function convertToEuroNumber($number)
    {
        return ($number*1000000);
    }
    
     /**
     * Set balance amount
     * @param object $tm
     */
    public function setBalanceAmount(TransactionManagerService $tm) {
        $coupon = $tm->coupons;
        $premimum = $tm->premimum;
        $gift_card = $tm->gift_card;
        $momosy_card = $tm->momosy_cards;
        if ($coupon == 0) {
            $tm->balance_amount = $tm->amount;
        }
        if ($coupon == 0 && $premimum == 0) {
            $tm->balance_amount = $tm->amount;
        }
        if ($coupon == 0 && $premimum == 0 && $momosy_card = 0) {
            $tm->balance_amount = $tm->amount;
        }
    }
    
    /**
     * Get balance amount
     * @param int $amount
     * @return int
     */
    public function getBalanceAmount(TransactionManagerService $tm) {
        return $tm->balance_amount;
    }
    
     /**
     * convert amount into int
     * @param type $amount
     * @return int
     */
    private function convertAmountToInt($amount) {
        return (int)$amount;
    }
    

    
     /**
     * save card amount into payment process credit(temporary)
     * @param array $data
     * @return int $transaction_id
     */
    private function EditPaymentProcessCreditObject($data) {
        //get doctrine object
        $em   = $this->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $id = $data['tid'];
        $payment_credit = $em->getRepository('PaymentTransactionProcessBundle:Transaction')
                                       ->findOneBy(array('id' => $id));

        if(!$payment_credit){
             return null;
        }

        $total_credit_used = $data['used_gift_card'];
        $discount_used = $data['used_coupons'] + $data['used_premium_position'] + $data['used_remaining_gift_cards'];
        $cash_paid = $data['balance_amount'];
        $transaction_amount = $data['amount'];
        $status = 'PENDING';
        $payment_credit->setTransactionAmount($transaction_amount);
        $payment_credit->setTotalCreditUsed($total_credit_used);
        $payment_credit->setDiscountUsed($discount_used);
        $payment_credit->setCashPaid($cash_paid);
        $payment_credit->setStatus($status);
        $payment_credit->setStatusDate($time);
        try {
            $em->persist($payment_credit);
            $em->flush();
            $transaction_id = $payment_credit->getId();
            return $transaction_id = ($transaction_id ? $transaction_id : null);
        } catch (\Exception $ex) {
   
            return null;
        }
        
   }
     /**
     * payment distribution
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function paymentapprovaldistributionAction() {    
        $user_id = 23599;
        $store_id = 1495;
        $total_amount = 135000000;
        $transaction_id = 1636537;
        $discount_amount = 20000000;
        $this->saveObjectInCitizenLog($user_id,$store_id,$total_amount,$transaction_id,$discount_amount);
    }
    
    /**
     * save distributed data in db
     * @param type $user_id
     * @param type $store_id
     * @param type $total_amount
     * @param type $transaction_id
     * @param type $coupon_amount
     * @param type $discount_position_amount
     * @return boolean
     */
    public function saveObjectInCitizenLog($user_id,$store_id,$total_amount,$transaction_id,$discount_amount) {
        
        /** payment distribution related variables **/
        $citizen_country_per       = $this->container->getParameter('country_distribute_per');
        $citizen_affiliation_per   = $this->container->getParameter('citizen_affiliate_distribute_per');
        $friends_follower_affiliation_per = $this->container->getParameter('friends_follower_distribute_per');
        $sixthcontinent_per = $this->container->getParameter('sixthcontinent_distribute_per');
        $store_affiliation_per = $this->container->getParameter('store_affiliate_distribute_per');
        $purchaser_distribute_per = $this->container->getParameter('purchaser_distribute_per');
        $status = 0;
        /** get entity manager object **/
        $em = $this->getDoctrine()->getManager();
        
        /** amount after deducting coupon and discount position **/
        $amount = $total_amount - $discount_amount;
        
        /** amount taht need to distribute to the store affiliator **/
        $store_affiliation_amount = ($amount*$store_affiliation_per)/100;
        
        /** amount to assign to citizen affiliator if user has **/
        $citizen_affiliator_amount = $amount*$citizen_affiliation_per/100;
        
        /** friend follower amount **/
        $friend_follower_amount = $amount*$friends_follower_affiliation_per/100;
        
        /** same country amount **/
        $same_country_amount = $amount*$citizen_country_per/100;
        
        /** amount to assign to purchased user **/
        $purchaser_distribute_amount = $amount*$purchaser_distribute_per/100;
        
        /** total amount that ned to be distributed **/
        $amount_to_distribute = $amount * ($citizen_country_per + $citizen_affiliation_per + $friends_follower_affiliation_per + $sixthcontinent_per + $store_affiliation_per + $purchaser_distribute_per)/100; 
        
        /** amount to assign to sixthcontinent **/
        $sixthcontinent_amount = $amount*$sixthcontinent_per/100;
        
        /** get$amount entity manager object **/
        $em = $this->getDoctrine()->getManager();
        
        /** get count for user affiliator **/
        $user_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getUserAffiliator($user_id);
        
        /** check if user affiliator is present **/
        if ($user_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $citizen_affiliator_amount;
            $citizen_affiliator_amount = 0;
        }
        
        /** get count for shop affiliator **/
        $shop_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getShopAffiliator($store_id);
        /** check if shop affiliator is present **/
        if ($shop_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $store_affiliation_amount;
            $store_affiliation_amount = 0;
        }
        
        /** get count for friends/follower **/
        $friends_follower_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getFriendFollowerAffiliator($user_id);
        
        /** check if shop affiliator is present **/
        if ($friends_follower_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $friend_follower_amount;
            $friend_follower_amount = 0;
        }
        
        /** get user of same country **/ 
        $user_country_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getSameCountryUser($user_id);
        
        /** set amount to be distribute amoung friend/follower and same country citizen **/
        $friend_follower_distribute_amount = 0;
        $country_citizen_distribute_amount = (integer)($same_country_amount/$user_country_user); 
        if($friends_follower_affiliation_user !=0) {
            $friend_follower_distribute_amount = (integer)($friend_follower_amount/$friends_follower_affiliation_user);
        }
        
        /** save distribution log in citizen income log table **/
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        
         /** check for log entry **/   
        $citizen_income_log = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id));
        //if log is not present
        if(count($citizen_income_log) == 0) {      
            
            /** get entity manager object **/
            $em_transaction = $this->getDoctrine()->getManager();
            $connection = $em_transaction->getConnection();
            $connection->beginTransaction();
            
            try {
                
                /** insert distribute amount in citizenincomegain table **/ 
                $distribute_citizen_income = $em_transaction
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->distributeCitizenIncomeGain($transaction_id,$user_id,$store_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount);
                
                /** distribute amount to sixthcontient **/
                $sixthcontinent_update = $em_transaction
                        ->getRepository('PaymentPaymentDistributionBundle:SixthContinentIncomeGain')
                        ->assignSixthcontinentCitizen($store_id,$user_id,$sixthcontinent_amount,$transaction_id,0);

                /** function for saving the non distributed amount for further use **/
                $non_distribute_amount = $em_transaction
                     ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                     ->saveNonDistributedAmount($store_id,$user_id,$amount_to_distribute,$transaction_id);
                
                 /** insert/update **/               
                $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$discount_amount,$total_amount,$distribute_citizen_income,0,1);
                /** commit the transactional **/
                $em_transaction->getConnection()->commit();
                $em_transaction->close();                
               
                
            } catch (Exception $ex) {

                $connection->rollback();
               $em_transaction->close();
                /** insert/update **/
               $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$discount_amount,$total_amount,$distribute_citizen_income,0,0);
              
            }
        }
        exit;
        
    }
    
    /**
     *  make entry in citizen income gain table if not present otherwise update it
     * @param type $transaction_id
     * @param type $status
     * @return boolean
     */
    public function updateCitizenIncomeGainLogLog($transaction_id,$status,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount,$user_id,$store_id,$discount_amount,$total_amount,$count,$cron_status,$job_status) {
        
        /** get entity manager object **/
        
        $this->container->get('doctrine')->resetEntityManager();
        /** reset the EM and all aias **/
        $this->container->set('doctrine.orm.entity_manager', null);
        $this->container->set('doctrine.orm.default_entity_manager', null);
        /** get a fresh EM **/
        $this->entityManager = $this->container->get('doctrine')->getEntityManager();        
        
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        
        $citizen_income_log_check = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id));
        if(count($citizen_income_log_check) > 0) {
            $citizen_income_log_check->setStatus($status);
            $citizen_income_log_check->setJobStatus($job_status);
            $citizen_income_log_check->setUpdatedAt($time);
            /** persist the store object **/
            $em->persist($citizen_income_log_check);
            /** save the store info **/
            $em->flush();
        }else {
            $citizen_income_log = new CitizenIncomeGainLog();
            /** set CitizenIncomeGainLog  fields **/        
            $citizen_income_log->setTransactionId($transaction_id);
            $citizen_income_log->setCitizenAffiliateAmount($citizen_affiliator_amount);
            $citizen_income_log->setShopAffiliateAmount($store_affiliation_amount);
            $citizen_income_log->setFriendsFollowerAmount($friend_follower_distribute_amount);
            $citizen_income_log->setPurchaserUserAmount($purchaser_distribute_amount);
            $citizen_income_log->setCountryCitizenAmount($country_citizen_distribute_amount);
            $citizen_income_log->setSixthcontinentAmount($sixthcontinent_amount);
            $citizen_income_log->setTotalAmount($total_amount);
            $citizen_income_log->setDistributedAmount($amount);
            $citizen_income_log->setDiscountAmount($discount_amount);
            $citizen_income_log->setUserId($user_id);
            $citizen_income_log->setShopId($store_id);
            $citizen_income_log->setCitizenCount($count);
            $citizen_income_log->setCronStatus($cron_status);
            $citizen_income_log->setStatus($status);
            $citizen_income_log->setCreatedAt($time);
            $citizen_income_log->setUpdatedAt($time);
            $citizen_income_log->setApprovedAt($time);
            $citizen_income_log->setJobStatus($job_status);
            /** persist the store object **/
            $em->persist($citizen_income_log);
            /** save the store info **/
            $em->flush();
        } 
        
        return true;
    }

}
