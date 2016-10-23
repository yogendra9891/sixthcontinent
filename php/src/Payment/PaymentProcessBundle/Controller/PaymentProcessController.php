<?php

namespace Payment\PaymentProcessBundle\Controller;

use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Payment\PaymentProcessBundle\Services\TransactionManagerService;
use Payment\PaymentProcessBundle\Entity\PaymentProcessCredit;
use Payment\PaymentProcessBundle\Entity\ShopGiftCards;
use Payment\PaymentDistributionBundle\Controller\PaymentDistributionController;
use Payment\PaymentDistributionBundle\Entity\CitizenIncomeGainLog;
use Payment\PaymentDistributionBundle\Entity\CitizenIncomeGain;
use Payment\PaymentDistributionBundle\Entity\PyamentDistributedAmount;

class PaymentProcessController extends Controller {

    protected $is_card_use  = array(2,1); //1 for use , 0 for not use
    protected $credit_level = array(0, 1); //0 for below maximum, 1 for above minimum.
    protected $response = array(1,2); //1 for accept , 2 for DENY
    protected $base_six = 1000000;
    protected $miss_param = '';
    
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
        $check_transaction = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                       ->findOneBy(array('id' => $tid, 'shopStatus' => 'PENDING'));
        if(!$check_transaction){
            $data = array('code' => 170, 'message' => 'NO_TRANSACTION_FOUND', 'data' => $data);
            $this->returnResponse($data);
        }
        $object_info->credit_level = $check_transaction->getCreditLevel(); //set the transacion credit level.
        
        $tm = $this->container->get('payment_payment_process.transaction_manager');
        
        $em = $this->container->get('doctrine')->getManager();
        $payment_credit = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                       ->findOneBy(array('id' => $tid));
        $credit_use = 2; 
        if($payment_credit){
            $credit_use  = $payment_credit->getCreditUse();
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
        $tm = $this->container->get('payment_payment_process.transaction_manager');
        $tm->userCards($user_id, $shop_id, $amount, $credit_level);

        //call service
        //check if coupon available
        $coupon = $tm->coupons;
        $premimum = $tm->premimum;
        $gift_card = $tm->gift_card;
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
            $response_gift_card = $this->container->get('giftcard.credit')->applyCredit($tm);
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
        $gift_card   = $this->convertAmountToInt($tm->gift_card);
        $momosy_card = $this->convertAmountToInt($tm->momosy_cards);
        $total_citizen_income = $this->convertAmountToInt($tm->total_citizen_income);
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
           // 'gift_card' => $gift_card,
            'used_gift_card' => $used_gift_card,
            'gift_card_packets' => $tm->gift_card_packets,
            'gift_card_setting' => $tm->gift_card_setting,
            'momosy_card' => $momosy_card,
            'used_momosy_card' => $used_momosy_card,
            'total_citizen_income' => $total_citizen_income,
            'cpg_amount' => $amount,
            'total_used'=>$total_used,
            'balance_amount' => $balance_amount,
            'used_remaining_gift_cards' => $used_remaining_gift_cards,
            'remaining_gift_cards' => $remaining_gift_cards
        );
        
        //save cards amount into payment process credit.
        $transaction_id = $this->EditPaymentProcessCreditObject($transaction_data);
        if ($transaction_id) {
            //save gift cards..
            $this->purchaseGiftCard($user_id, $shop_id, $tm->gift_card_packets);
            $transaction_data['transaction_id'] = $transaction_id; //append the transaction id
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
     * Use Cards For transaction
     * @param type $object_info
     */
    public function notUseCreditsForTransactions($object_info)
    {
        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $amount = $total_amount = $this->convertToEuroNumber($object_info->amount);
        $tid = $object_info->transaction_id;
        $credit_level = $object_info->credit_level;
        //call service method
        $tm = $this->container->get('payment_payment_process.transaction_manager');
        $tm->userCards($user_id, $shop_id, $amount, $credit_level);

        //call service
        //check if coupon available
        $coupon = $tm->coupons;
        $premimum = $tm->premimum;
        $gift_card = $tm->gift_card;
        $momosy_card = $tm->momosy_cards;
        $remaining_gift_cards = $tm->remaining_gift_cards;

        $coupons = $this->convertAmountToInt($tm->coupons);
        $premimum_position = $this->convertAmountToInt($tm->premimum);
        $gift_card   = $this->convertAmountToInt($tm->gift_card);
        $momosy_card = $this->convertAmountToInt($tm->momosy_cards);
        $total_citizen_income = $this->convertAmountToInt($tm->total_citizen_income);
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
        $shop_id = $this->convertAmountToInt($shop_id);
        $balance_amount = $total_amount;
        $transaction_data = array(
            'tid' => $tid,
            'user_id'=>$user_id,
            'shop_id'=>$shop_id,
            'amount'=>$total_amount,
            'coupons' => 0,
            'used_coupons' => 0,
            'premium_position' => 0,
            'used_premium_position' => 0,
            'used_gift_card' => 0,
            'gift_card_packets' => array(),
            'gift_card_setting' => 1,
            'momosy_card' => 0,
            'used_momosy_card' => 0,
            'total_citizen_income' => $total_citizen_income,
            'cpg_amount' => $amount,
            'total_used'=>0,
            'balance_amount' => $balance_amount,
            'used_remaining_gift_cards' => 0,
            'remaining_gift_cards' => 0
        );
        //save cards amount into payment process credit.
        $transaction_id = $this->EditPaymentProcessCreditObject($transaction_data);
        if ($transaction_id) {
            //save gift cards..
            $this->purchaseGiftCard($user_id, $shop_id, $tm->gift_card_packets);
            $transaction_data['transaction_id'] = $transaction_id; //append the transaction id
            //get transaction object
            //call service method
            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
            $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>$transaction_data_response);
        } else {
            $data = array('code'=>100, 'message'=>'ERROR_OCCURED', 'data'=>array());
        }
        
        $this->returnResponse($data);
    }
    /**
     * Approve credit
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postApprovecreditsAction(Request $request)
    {
        //get request object
        $de_serialize = $this->getAppData($request);
 
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id', 'transaction_id', 'response');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }

        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $transaction_id = $object_info->transaction_id;
        $response = $object_info->response;
        
         //check if type is in specified array
        if (!in_array($response, $this->response)) {
            return array('code' => 173, 'message' => 'RESPONSE_TYPE_NOT_FOUND', 'data' => $data);
        }
        //check if user has already used the transaction id
        $em = $this->getDoctrine()->getManager();
        $payment_credit = $em
                        ->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                        ->findOneBy(array('id' => $transaction_id, 'userId' => $user_id, 'shopId' => $shop_id, 'shopStatus' => 'PENDING'));
        
       
        //if used then return the error response
        if(!$payment_credit){
            
            //get transaction object
            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
            $data = array('code' => 168, 'message' => 'NO_CREDIT_FOUND', 'data' => $transaction_data_response);
            $this->returnResponse($data);
        }
            
        //get the payment credit object
        $transaction_obj = array(
            'id' => $payment_credit->getId(),
            'user_id' => $payment_credit->getUserId(),
            'shop_id' => $payment_credit->getShopId(),
            'coupons' => $payment_credit->getCoupons(),
            'amount' =>$payment_credit->getTotalAmount(),
            'used_coupons' => $payment_credit->getUsedCoupons(),
            'premium_position' => $payment_credit->getPremiumPosition(),
            'used_premium_position' => $payment_credit->getUsedPremiumPosition(),
            'gift_card' => $payment_credit->getGiftCard(),
            'used_gift_card' => $payment_credit->getUsedGiftCard(),
            'momosy_card' => $payment_credit->getMomosyCard(),
            'used_momosy_card' => $payment_credit->getUsedMomosyCard(),
            'total_citizen_income' => $payment_credit->getTotalCitizenIncome(),
            'cpg_amount' => $payment_credit->getCpgAmount(),
            'total_used'=>$payment_credit->getTotalUsed(),
            'balance_amount' => $payment_credit->getBalanceAmount(),
            'gift_card_packet_data' => $payment_credit->getGiftCardPacketData(),
            'remaining_gift_cards' =>$payment_credit->getRemainingGiftCards(),
            'used_remaining_gift_cards' => $payment_credit->getUsedRemainingGiftCards(),
            'shop_status' => $response
        );
       
        //Decrease the credit available
        $this->updateCreditAvailable($transaction_obj); 
    }
    
    /**
     * Update the credit available
     * @param array $transaction_obj
     */
    public function updateCreditAvailable($transaction_obj)
    {
       
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $time = new \DateTime('now');
        $id = $transaction_obj['id'];
        $amount = $transaction_obj['amount'];
        $user_id = $transaction_obj['user_id'];
        $shop_id = $transaction_obj['shop_id'];
        $used_coupons = $transaction_obj['used_coupons'];
        $used_premium = $transaction_obj['used_premium_position'];
        $used_gift_cards = $transaction_obj['used_gift_card'];
        $used_momosy_cards = $transaction_obj['used_momosy_card'];
        $gift_card_packet_data =  $transaction_obj['gift_card_packet_data']; //serialize array
        $remaining_gift_cards  = $transaction_obj['remaining_gift_cards'];
        $used_remaining_gift_cards = $transaction_obj['used_remaining_gift_cards'];
        $shop_status = $transaction_obj['shop_status'];
        
        //shop status handling
        switch($shop_status){
            case 1: 
                $shop_status_string = "APPROVED";
                break;
            case 2: 
                $shop_status_string = "REJECT";
                break;
            default:
                $shop_status_string = "PENDING";
                break;
        }
        
        $tm = $this->container->get('payment_payment_process.transaction_manager');
        
        //update the is_used as 1
        $paymentObj = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                       ->findOneBy(array('id' => $id));
        if(!$paymentObj){
            $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($data);
        }
        //get credit
        $credit_use  = $paymentObj->getCreditUse();
        if($credit_use == 1 && $shop_status_string == "APPROVED"){
            //call service method
            $tm->updateUserCards($user_id, $shop_id, $used_coupons, $used_premium, $used_gift_cards, $used_momosy_cards, $gift_card_packet_data, $remaining_gift_cards, $used_remaining_gift_cards);
            
        }       
            
       
        $paymentObj->setShopStatus($shop_status_string);
        $paymentObj->setUpdatedAt($time);
        $paymentObj->setTransactionApprovedAt($time);
        $em->persist($paymentObj);
        $em->flush(); 
        
         if($shop_status_string == "APPROVED") {            
            /** save distributed log in db **/
            $this->saveObjectInCitizenLog($user_id,$shop_id,$amount,$id,$used_coupons,$used_premium);
            $msg_code = 'TXN_SHOP_APPROVE';
            $msg = 'Transaction confirmed by shop.';
        }else{
           $msg_code = 'TXN_SHOP_CANCEL';
           $msg = 'Transaction cancelled by shop.';
        }
        
        
        //call service method
        $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
        $transaction_data_response = $transaction_obj->getTransactionObject($id);
            
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $transaction_data_response);
        
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
        $device_array = $push_object_service->getReceiverDeviceInfo($user_id);
        $from_id = $shop_owners_id;
        $to_id = array($user_id);
        $ref_type = 'TXN';
        $ref_id = $id;
        $notification_role = 5;
        $client_type = "CITIZEN";
        $push_object_service->sendNotificationByRole($from_id, $to_id, $device_array, $msg_code, $msg, $ref_type, $ref_id, $notification_role, $client_type);
        
        /** Code for email notification **/
        
        /** get object of email template service **/
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        /** get locale **/
        $locale = $this->container->getParameter('locale');
        $language_const_array = $this->container->getParameter($locale);

        $user_service = $this->get('user_object.service');
        $store_object_info = $user_service->getStoreObjectService($shop_id);
        $shop_name = '';
        if($store_object_info) {
                $shop_name = $store_object_info['name'];
        }
        $sender = $postService->getUserData($from_id);
        
        if(is_array($sender)) {
            $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
        } else {
            $sender_name = '';
        }
       
        $sender_profile_img = '';
        $shop_owner_img = '';
        if(isset($sender['profile_image_thumb'])) {
            $sender_profile_img = $sender['profile_image_thumb'];
        }
        
        $receivers = $postService->getUserData($to_id, true);
        //get locale
        $locale = !empty($receivers[$to_id]['current_language']) ? $receivers[$to_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        if(isset($receivers[1]['profile_image_thumb'])) {
            $shop_owner_img = $receivers[1]['profile_image_thumb'];
        } 
        
        if($shop_status_string == "APPROVED") { 
            $mail_text = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS_MAIL_TEXT'],$shop_name);
            $href = '';
            #$bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href); //making the link html from service
            $bodyData = $mail_text;
            $subject = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS'] );
            $mail_body = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS_BODY'] , $shop_name );
        }else {
            $mail_text = sprintf($lang_array['TRANSACTION_CANCEL_STATUS_MAIL_TEXT'],$shop_name);
            $href = '';
            #$bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href); //making the link html from service
            $bodyData = $mail_text;
            $subject = sprintf($lang_array['TRANSACTION_CANCEL_STATUS'] );
            $mail_body = sprintf($lang_array['TRANSACTION_CANCEL_STATUS_BODY'], $shop_name );
        }
      
        $email_response_citizen = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $shop_owner_img, 'TRANSACTION');
        
        $senders = $postService->getUserData($from_id,true);
        //get locale
        $locale = !empty($senders[$from_id]['current_language']) ? $senders[$from_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        if($shop_status_string == "APPROVED") { 
            $mail_text = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS_MAIL_TEXT'],$shop_name);
            $href = '';
            #$bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href); //making the link html from service
            $bodyData = $mail_text;
            $subject = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS'] );
            $mail_body = sprintf($lang_array['TRANSACTION_SUCCESS_STATUS_BODY'] , $shop_name );
        }else {
            $mail_text = sprintf($lang_array['TRANSACTION_CANCEL_STATUS_MAIL_TEXT'],$shop_name);
            $href = '';
            #$bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href); //making the link html from service
            $bodyData = $mail_text;
            $subject = sprintf($lang_array['TRANSACTION_CANCEL_STATUS'] );
            $mail_body = sprintf($lang_array['TRANSACTION_CANCEL_STATUS_BODY'], $shop_name );
        }
        
        $email_response_shop = $email_template_service->sendMail($senders, $bodyData, $mail_body, $subject, $sender_profile_img, 'TRANSACTION');
        
        $this->returnResponse($data);
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
     * Set momosy card balance amount
     * @param object $tm
     */
    public function setMomosyBalanceAmount($tm) {
        $balance_amount = $tm->balance_amount;
        $amount = $tm->amount;
        $paid_by_momosy_card = $balance_amount + $amount;
        $tm->balance_amount = $paid_by_momosy_card;
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
     * save card amount into payment process credit(temporary)
     * @param array $data
     * @return int $transaction_id
     */
    private function savePaymentProcessCreditObject($data) {
        //get doctrine object
        $em   = $this->get('doctrine')->getManager();
        $time = new \DateTime('now');
        $serialize_data = serialize($data);
        $gift_cards_packets = serialize($data['gift_card_packets']);
        $payment_credit = new PaymentProcessCredit(); //entity object
        $payment_credit->setUserId($data['user_id']);
        $payment_credit->setShopId($data['shop_id']);
        $payment_credit->setTotalAmount($data['amount']);
        $payment_credit->setCoupons($data['coupons']);
        $payment_credit->setUsedCoupons($data['used_coupons']);
        $payment_credit->setPremiumPosition($data['premium_position']);
        $payment_credit->setUsedPremiumPosition($data['used_premium_position']);
        $payment_credit->setGiftCard(0);
        $payment_credit->setUsedGiftCard($data['used_gift_card']);
        $payment_credit->setMomosyCard($data['momosy_card']);
        $payment_credit->setUsedMomosyCard($data['used_momosy_card']);
        $payment_credit->setTotalCitizenIncome($data['total_citizen_income']);
        $payment_credit->setCpgAmount($data['cpg_amount']); //coupons + premium position + gift card (1/2 of total amount)
        $payment_credit->setTotalUsed($data['total_used']); //coupons + premium position + gift card + momosy card
        $payment_credit->setBalanceAmount($data['balance_amount']);
        $payment_credit->setAmountData($serialize_data); //serialize data.
        $payment_credit->setCitizenStatus($data['citizen_status']);
        $payment_credit->setShopStatus($data['shop_status']);
        $payment_credit->setCreditUse($data['credit_use']);
        $payment_credit->setCreditLevel($data['credit_level']); // for below maximum and above minimum 
        $payment_credit->setGiftCardPacketData($gift_cards_packets);
        $payment_credit->setUsedRemainingGiftCards($data['used_remaining_gift_cards']);
        $payment_credit->setRemainingGiftCards($data['remaining_gift_cards']);
        $payment_credit->setCreatedAt($time);
        $payment_credit->setUpdatedAt($time);
        $payment_credit->setTransactionApprovedAt($time);
        try {
            $em->persist($payment_credit);
            $em->flush();
            //get transaction id
            $transaction_id = $payment_credit->getId();
            
            //get transaction object
            //call service method
            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
            return $transaction_data_response = ($transaction_data_response ? $transaction_data_response : null);
        } catch (\Exception $ex) {
            return null;
        }
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
        $serialize_data = serialize($data);
        $id = $data['tid'];
        $gift_cards_packets = serialize($data['gift_card_packets']);
        $payment_credit = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                       ->findOneBy(array('id' => $id));
        $payment_credit->setUserId($data['user_id']);
        $payment_credit->setShopId($data['shop_id']);
        $payment_credit->setTotalAmount($data['amount']);
        $payment_credit->setCoupons($data['coupons']);
        $payment_credit->setUsedCoupons($data['used_coupons']);
        $payment_credit->setPremiumPosition($data['premium_position']);
        $payment_credit->setUsedPremiumPosition($data['used_premium_position']);
        $payment_credit->setGiftCard(0);
        $payment_credit->setUsedGiftCard($data['used_gift_card']);
        $payment_credit->setMomosyCard($data['momosy_card']);
        $payment_credit->setUsedMomosyCard($data['used_momosy_card']);
        $payment_credit->setTotalCitizenIncome($data['total_citizen_income']);
        $payment_credit->setCpgAmount($data['cpg_amount']); //coupons + premium position + gift card (1/2 of total amount)
        $payment_credit->setTotalUsed($data['total_used']); //coupons + premium position + gift card + momosy card
        $payment_credit->setBalanceAmount($data['balance_amount']);
        $payment_credit->setAmountData($serialize_data); //serialize data.
        $payment_credit->setGiftCardPacketData($gift_cards_packets);
        $payment_credit->setUsedRemainingGiftCards($data['used_remaining_gift_cards']);
        $payment_credit->setRemainingGiftCards($data['remaining_gift_cards']);
        $payment_credit->setUpdatedAt($time);
        $payment_credit->setIsCalculated(1);
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
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array,JSON_NUMERIC_CHECK);
        exit;
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
     * save the data for purchased gift card.
     * @param int $user_id
     * @param int $shop_id
     * @param array $gc_amount_packets
     */
    private function purchaseGiftCard($user_id, $shop_id, $gc_amount_packets) {
        //get entity manger object.
        $em = $this->container->get('doctrine')->getManager();
        $date = new \DateTime('now');
        
        if (count($gc_amount_packets)) {
            foreach ($gc_amount_packets as $packet_amount) {
                $shop_gift_card = new ShopGiftCards();
                $shop_gift_card->setUserId($user_id);
                $shop_gift_card->setShopId($shop_id);
                $shop_gift_card->setGiftCardAmount($packet_amount);
                $shop_gift_card->setDate($date);
                $shop_gift_card->setIsUsed(0);
                $em->persist($shop_gift_card);
            }
            try {
                $em->flush();
                return true;     
            } catch (\Exception $ex) {
            }
        }
        return true;
    }
    
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
        
        $transaction_data = array(
            'user_id'=>$this->castToInt($user_id),
            'shop_id'=>$this->castToInt($shop_id),
            'amount'=>0,
            'coupons' => 0,
            'used_coupons' => 0,
            'premium_position' => 0,
            'used_premium_position' => 0,
            'used_gift_card' => 0,
            'gift_card_packets' => 0,
            'gift_card_setting' => 0,
            'momosy_card' => 0,
            'used_momosy_card' => 0,
            'total_citizen_income' =>0,
            'cpg_amount' => 0,
            'total_used'=>0,
            'balance_amount' => 0,
            'used_remaining_gift_cards' =>0,
            'remaining_gift_cards' => 0,
            'credit_use' => $type,
            'credit_level' => $credit_level,
            'citizen_status' =>'PENDING',
            'shop_status' => 'PENDING'
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
        
        /** comment the code becuase not need this email notification
        
        // Code for email notification 
        
        // get object of email template service 
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        // get locale //
        $locale = $this->container->getParameter('locale');
        $language_const_array = $this->container->getParameter($locale);
        
        $user_service = $this->get('user_object.service');
        $store_object_info = $user_service->getStoreObjectService($shop_id);
        $shop_name = '';
        if($store_object_info) {
            $shop_name = $store_object_info['name'];
        }
        $sender = $postService->getUserData($from_id);
        $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
        $mail_text = sprintf($language_const_array['TRANSACTION_INITIATE_MAIL_TEXT'], ucwords($sender_name),$shop_name);
        $href = '';
        #$bodyData = $mail_text . "<br><br>" . $email_template_service->getLinkForMail($href); //making the link html from service
        $bodyData = $mail_text; //making the link html from service
        $subject = sprintf($language_const_array['TRANSACTION_INITIATE'], ucwords($sender_name) );
       
        $mail_body = sprintf($language_const_array['TRANSACTION_INITIATE_BODY'], ucwords($sender_name) , $shop_name );
        $receivers = $postService->getUserData($shop_owners_id, true);
        
        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'TRANSACTION');
        */
     
        //$notification_role = 1 for web notification
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $transaction_obj);
        $this->returnResponse($data);
    }
    
     /**
     * Finding the transaction history for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGettransactionobjectsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        
        $transaction_arr = array('APPROVED','REJECT','PENDING');
        $transaction_arr_val = array('APPROVED'=>'APPROVED','REJECT'=>'REJECT','PENDING'=>'PENDING');
        
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
        $data        = array();     
        
        /** check transaction id **/
        if(isset($object_info->transaction_id) && $object_info->transaction_id !='') {
            $transaction_id   = $object_info->transaction_id;
        }else{
            $transaction_id   = '';
        }
        
        /** check shop status **/
        if(isset($object_info->shop_status) && $object_info->shop_status !='') {
            $shop_status   = trim(strtoupper($object_info->shop_status));
            if(!in_array($shop_status,$transaction_arr)) {
                $res_data = array('code' => 172, 'message' => 'SHOP_STATUS_NOT_VALID', 'data' => $data);
                return $res_data;
            }else {
                $shop_status = $shop_status;
            }
        }else{
            $shop_status   = '';
        }
        
        /** check shop status **/
        if(isset($object_info->citizen_status) && $object_info->citizen_status !='') {
            $citizen_status   = trim(strtoupper($object_info->citizen_status));
            if(!in_array($citizen_status,$transaction_arr)) {
                $res_data = array('code' => 173, 'message' => 'CITIZEN_STATUS_NOT_VALID', 'data' => $data);
                return $res_data;
            }else {
                $citizen_status = $citizen_status;
            }
        }else{
            $citizen_status   = '';
        }
       
        
        /** find user object service. **/
        $user_service = $this->get('user_object.service');
         
        /** check limit start **/
        if(isset($object_info->limit_start) && $object_info->limit_start !='') {
            $limit_start   = $object_info->limit_start;
        }else{
            $limit_start   = 0;
        }
        
         /** check limit size **/
        if(isset($object_info->limit_size) && $object_info->limit_size !='') {
            $limit_size   = $object_info->limit_size;
        }else {
            $limit_size   = 20;
        }
       
        
        /** check for store object **/
        if(isset($object_info->shop_id) && $object_info->shop_id !='') {
            $shop_id   = $object_info->shop_id;
            $store = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id); 
            /** if store not found **/
            if (!$store) {
                $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
                return $res_data;
            }
        }else{
            $shop_id = '';
        }
        
        /** check for user exist or not**/
         if(isset($object_info->user_id) && $object_info->user_id !='') {
            $user_id   = $object_info->user_id;
            /** get user manager **/
            $um = $this->container->get('fos_user.user_manager');
            /** get user detail **/
            $user = $um->findUserBy(array('id' => $user_id));

            if (!$user) {
                return array('code' => 100, 'message' => 'USER_DOES_NOT_EXISTS', 'data' => $data);
            }
         }else {
             $user_id = '';
         }
        
         /** check for minimum parameter required **/
        if($transaction_id=='' && $user_id=='' && $shop_id=='') {
            return array('code' => 171, 'message' => 'MINIMUM_PARAMETER_REQUIRED', 'data' => $data);
        }
        
        /** get transaction records **/
        $transaction_res = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->getTransactionObj($transaction_id,$user_id,$shop_id,$citizen_status,$shop_status,$limit_start,$limit_size);
        $count = 0;
        $count = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->getTransactionObjCount($transaction_id,$user_id,$shop_id,$citizen_status,$shop_status);
        
        if(count($transaction_res)>0) {
            
            /** getting shop ids **/
            $store_ids = array_map(function($transaction_record) {
                return "{$transaction_record->getShopId()}";
            }, $transaction_res);            
            $store_ids = array_unique($store_ids);
            
            /** get store object. **/
            $stores_object_array = $user_service->getMultiStoreObjectService($store_ids);
            
            /** getting users ids **/
            $user_ids = array_map(function($transaction_record) {
                return "{$transaction_record->getUserId()}";
            }, $transaction_res);
            $user_ids = array_unique($user_ids);
            
            /** get user object. **/
            $users_object_array = $user_service->MultipleUserObjectService($user_ids);        
            
            foreach($transaction_res as $transaction_record) {
                $shop_id = $transaction_record->getShopId();
                $user_id = $transaction_record->getUserId();
                
                //get the service for transaction object..
                $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
                $transaction_data_response = $transaction_obj->getTransactionObject($transaction_record);
                $record_arr = $transaction_data_response;
                $data[] = $record_arr;
            }
        }
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('transactions' => $data, 'size' => $count));
        $this->returnResponse($data);
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
    public function saveObjectInCitizenLog($user_id,$store_id,$total_amount,$transaction_id,$coupon_amount,$discount_position_amount) {
        
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
        $amount = $total_amount - ($coupon_amount + $discount_position_amount);
        
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
                
                 /** update user CI in userdiscountposition table **/
//                $sixthcontinent_update = $em_transaction
//                    ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
//                    ->updateUserCitizenIncome($transaction_id);
                
                /** function for saving the non distributed amount for further use **/            
//                $set_status = $em_transaction
//                     ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
//                     ->SetStatusForUserGotCI($transaction_id); 
                
                /** commit the transactional **/
                $em_transaction->getConnection()->commit();
                $em_transaction->close();                
                /** insert/update **/               
                $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0,1);
                
            }
            catch (\Exception $e) {       
               $connection->rollback();
               $em_transaction->close();
                /** insert/update **/
               $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0,0);
              
            }
        }
        
        return true;
    }
    
     /**
     * 
     * @param type $transaction_id
     * @param type $status
     * @return boolean
     */
    public function updateCitizenIncomeGainLogLog($transaction_id,$status,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$count,$cron_status,$job_status) {
        
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
            $citizen_income_log->setCouponAmount($coupon_amount);
            $citizen_income_log->setDiscountPositionAmount($discount_position_amount);
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
    
     /**
     * save the amount to be distributed different category
     * @param type $transaction_id
     * @param type $country_citizen_distribute_amount
     * @param type $friend_follower_distribute_amount
     * @param type $citizen_affiliator_amount
     * @param type $store_affiliation_amount
     * @param type $purchaser_distribute_amount
     * @param type $sixthcontinent_amount
     * @return boolean
     */
    public function updatePaymentDistributedAmount($em_transaction,$transaction_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount) {
             
        //echo  $transaction_id."and".$country_citizen_distribute_amount."and".$friend_follower_distribute_amount."and".$citizen_affiliator_amount."and".$store_affiliation_amount."and".$purchaser_distribute_amount."and".$sixthcontinent_amount;exit;
        $em = $em_transaction;
        $time = new \DateTime("now");
        
        $payment_distributed_log = new PyamentDistributedAmount();
        /** set PyamentDistributedAmount  fields **/        
        $payment_distributed_log->setTransactionId($transaction_id);
        $payment_distributed_log->setCitizenAffiliateAmount($citizen_affiliator_amount);
        $payment_distributed_log->setShopAffiliateAmount($store_affiliation_amount);
        $payment_distributed_log->setFriendsFollowerAmount($friend_follower_distribute_amount);
        $payment_distributed_log->setPurchaserUserAmount($purchaser_distribute_amount);
        $payment_distributed_log->setCountryCitizenAmount($country_citizen_distribute_amount);
        $payment_distributed_log->setSixthcontinentAmount($sixthcontinent_amount);
        $payment_distributed_log->setCreatedAt($time);
        /** persist the PyamentDistributedAmount object **/
        $em->persist($payment_distributed_log);
        /** save the PyamentDistributedAmount info **/
        $em->flush();
    
        return true;
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int
     */
    public function postSearchtransactionobjectsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        
        $transaction_arr = array('APPROVED','REJECT','PENDING');
        $transaction_arr_val = array('APPROVED'=>'APPROVED','REJECT'=>'REJECT','PENDING'=>'PENDING');
        
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
        $data        = array();     
       
        $required_parameter = array();

        //we have commented this beacase we have required parameters.
        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
//            $this->returnResponse($data);
//        }
        
        /** check shop status **/
        if(isset($object_info->search_key) && $object_info->search_key !='') {
            $search_key   = $object_info->search_key;
        }else{
            $search_key   = '';
        }
        
        /** check shop status **/
        if(isset($object_info->shop_status) && $object_info->shop_status !='') {
               $shop_status =  trim(strtoupper($object_info->shop_status));
            if(!in_array($shop_status,$transaction_arr)) {
                $res_data = array('code' => 172, 'message' => 'SHOP_STATUS_NOT_VALID', 'data' => $data);
                return $res_data;
            }else {
                $shop_status = $shop_status;
            }
        }else{
            $shop_status   = '';
        }
        
        /** check shop status **/
        if(isset($object_info->citizen_status) && $object_info->citizen_status !='') {
            $citizen_status   = trim(strtoupper($object_info->citizen_status));
            if(!in_array($citizen_status,$transaction_arr)) {
                $res_data = array('code' => 173, 'message' => 'CITIZEN_STATUS_NOT_VALID', 'data' => $data);
                return $res_data;
            }else {
                $citizen_status = $citizen_status;
            }
        }else{
            $citizen_status   = '';
        }
       
        
        /** find user object service. **/
        $user_service = $this->get('user_object.service');
         
        /** check limit start **/
        if(isset($object_info->limit_start) && $object_info->limit_start !='') {
            $limit_start   = $object_info->limit_start;
        }else{
            $limit_start   = 0;
        }
        
         /** check limit size **/
        if(isset($object_info->limit_size) && $object_info->limit_size !='') {
            $limit_size   = $object_info->limit_size;
        }else {
            $limit_size   = 20;
        }
       
        
        /** check for store object **/
        if(isset($object_info->shop_id) && $object_info->shop_id != '') {
            $shop_id   = $object_info->shop_id;
            $store = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id); 
            /** if store not found **/
            if (!$store) {
                $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
                return $res_data;
            }
        } else {
            $shop_id = '';
        }
        
        /** get transaction object according search key **/
        $transaction_res = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->searchTransactionObj($search_key,$shop_id,$shop_status,$citizen_status,$limit_start,$limit_size);
        $count = 0;
       
        $count = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->searchTransactionObjCount($search_key,$shop_id,$shop_status,$citizen_status,$limit_start,$limit_size);
       
        
        if(count($transaction_res)>0) {
            //prepare the transaction object.            
            foreach($transaction_res as $transaction_record) {
                $shop_id = $transaction_record->getShopId();
                $user_id = $transaction_record->getUserId();
               
                //get the service for transaction object..
                $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
                $transaction_data_response = $transaction_obj->getTransactionObject($transaction_record);
                $record_arr = $transaction_data_response;
                $data[] = $record_arr;
            }
        }
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('transactions' => $data, 'size' => $count));
        $this->returnResponse($data);
    }
    
    /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToEuro($amount) {
        if($amount>0) {
            $amount_euro =  $amount/$this->base_six;
            
        }else {
            $amount_euro = $amount;
        }
        return $amount_euro;
    }
    
    /**
     * Finding the transaction history for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGettransactionbyidsAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        
        
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
        $data        = array();     
        
        $required_parameter = array('transaction_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        
        $transaction_id   = $object_info->transaction_id;
        
        
        /** find user object service. **/
        $user_service = $this->get('user_object.service');         
        
        
        /** get transaction records **/
        $transaction_record = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->find($transaction_id);
        
        if(count($transaction_record)>0) {
                  
            $shop_id = $transaction_record->getShopId();
            $user_id = $transaction_record->getUserId();
            $user_obj = array();
            $shop_obj = array();
            $user_obj = $user_service->UserObjectService($user_id);
            $shop_obj = $user_service->getStoreObjectService($shop_id);
            /** get balance gift card value **/
            $user_credit_rec = $em
                    ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                    ->findOneBy(array('userId'=>$user_id,'shopId'=> $shop_id)); 

            if(count($user_credit_rec) > 0) {
                $balance_gift_card = $user_credit_rec->getBalanceGiftCard();
            }else {
                $balance_gift_card = 0;
            }

            $used_coupons   = $this->convertAmountToInt($transaction_record->getUsedCoupons());
            $used_premium_position = $this->convertAmountToInt($transaction_record->getUsedPremiumPosition());
            $used_gift_card   = $this->convertAmountToInt($transaction_record->getUsedGiftCard());
            $used_momosy_card = $this->convertAmountToInt($transaction_record->getUsedMomosyCard());
            $used_remaining_gift_cards = $transaction_record->getUsedRemainingGiftCards();
            $remaining_gift_cards = $transaction_record->getRemainingGiftCards();
            $total_used = ($used_coupons + $used_premium_position + $used_gift_card + $used_momosy_card +$used_remaining_gift_cards - $remaining_gift_cards); //all used cards amount

            $balance_amount = 0;
            $total_amount = $this->convertAmountToInt($transaction_record->getTotalAmount()); 
            $balance_amount = $total_amount - $total_used;

            $gift_card_arr = unserialize($transaction_record->getGiftCardPacketData());
            $gift_card_packet = array();
            if(count($gift_card_arr)>0) {
//                $gift_card_packet = array_map(function($gift_card_arr_rec) {
//                    return "{$this->converToEuro($gift_card_arr_rec)}";
//                }, $gift_card_arr);
                foreach($gift_card_arr as $gift_card_arr_rec) {
                    $gift_card_packet[] = "{$this->converToEuro($gift_card_arr_rec)}";
                }
            }

            $transaction_id = $transaction_record->getId();
            //call service method
            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
                    
            $data= $transaction_data_response;
            
        }
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' =>$data);
        $this->returnResponse($data);
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
     * Convert to number
     * @param int $number
     * @return type
     */
    public function convertToEuroNumber($number)
    {
        return ($number*1000000);
    }
    
    
}
