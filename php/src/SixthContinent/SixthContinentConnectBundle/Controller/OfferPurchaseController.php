<?php

namespace SixthContinent\SixthContinentConnectBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Transaction\TransactionSystemBundle\Repository\TransactionRepository;

class OfferPurchaseController extends Controller {

    CONST LIMIT_SIZE = 20;
    CONST STATUS_OK = 'ok';
    CONST STATUS_KO = 'ko';
    public function indexAction($name) {
        return $this->render('SixthContinentConnectBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * utility service
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }

    private function _getEntityManager() {
        return $this->getDoctrine()->getManager();
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    protected function _getSixcontinentBusinessAppService() {
        return $this->container->get('sixth_continent_connect.connect_business_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectBusinessAccountService
    }
    
    protected function _getSixcontinentOfferService() {
        return $this->container->get('sixth_continent_connect.purchasing_offer_transaction'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectBusinessAccountService
    }
    
    /**
     * purchaase offer from tamoil
     * @param request object
     * @return json
     */
    public function postPurchaseOfferAction(Request $request) {
        
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id', 'offer_id', 'return_url', 'cancel_url');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with request: ' . Utility::encodeData($data));
        //extract parameters
        $user_id = $data['user_id'];
        $offer_id = $data['offer_id'];
        $return_url = $data['return_url'];
        $cancel_url = $data['cancel_url'];
        $ssn = isset($data['ssn']) ? $data['ssn'] : '';
        
        $app_name = ApplaneConstentInterface::TAMOIL_OFFER_NAME;
        $applane_status = ApplaneConstentInterface::INITIATED;
        $pending_status  = ApplaneConstentInterface::PENDING;

        $em = $this->_getEntityManager();
        //check offer
        $offer_record = $connect_offer_purchase_service->getOfferPurchaseRecord($offer_id);

        if (!$offer_record) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        //CHECK FOR COUPON AVAILABILITY and COMMERCIAL offer
        $coupon_count = $number_offert = $connect_offer_purchase_service->checkAvailableCoupn($user_id, $offer_id);
        if($number_offert == 0){
            $commoffert_count = $number_offert = $connect_offer_purchase_service->checkAvailableCommercialPromotion($user_id, $offer_id);   
        }
        if ($number_offert == 0) {
            $resp_data = new Resp(Msg::getMessage(1152)->getCode(), Msg::getMessage(1152)->getMessage(), $result_data); //COUPON_IS_NOT_AVAILABLE
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] for userid: '.$user_id.' and offerid: '.$offer_id.' and with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data); 
        }

        
        $shop_id = $offer_record->getSellerId();
        $search["buyer_id"] =  $user_id;
        $search["offer_id"] =  $offer_id;
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $repo_commercial_offer = $em->getRepository("CommercialPromotionBundle:CommercialPromotion");
        $offer_detail = $repo_commercial_offer->getCommercialPromotionDetail($search , $dm); //get offer detail
        
        if($offer_detail["result"]["promotion_type"]["promotionType"]=="genericvoucher"){
            $requiredParams = array('user_id', 'offer_id', 'return_url', 'cancel_url' ,'card_id');
            if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
                $resp_data = new Resp($result['code'], $result['message'], array());
                $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
          
             //It's a multiple array
            $single_cards = $offer_detail["result"]["extra_information"]["single_cards"];
            foreach ($single_cards as $value) {
             // Selecting the proper card
                if($value["card_id"] == $data["card_id"] ){
                    $offer_detail['result']["price_for_me"] = $value["price_for_me"];
                    
                    break;
                }

            }
        }
//        $offer_detail = $connect_offer_purchase_service->getOfferDetail($user_id, $offer_record); //get offer detail

        if (isset($offer_detail[0]['result']['id']) ) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $connect_offer_purchase_service->__createLog('Transaction break up is: '.Utility::encodeData($offer_detail));
        $offer_detail['application_id'] = $offer_id;
        $offer_detail['shop_id'] = $shop_id;
        if ($ssn != '') { //update ssn
            $updated_ssn = $connect_offer_purchase_service->updateSsn($user_id, $ssn);
            if ($updated_ssn == '') {
                $resp_data = new Resp(Msg::getMessage(1150)->getCode(), Msg::getMessage(1150)->getMessage(), $result_data); //SSN_NOT_UPDATED
                $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
        }
        $ssn_flag = $connect_offer_purchase_service->checkCitizenSsn($user_id);
        if (!$ssn_flag) {
            $resp_data = new Resp(Msg::getMessage(1151)->getCode(), Msg::getMessage(1151)->getMessage(), $result_data); //UPDATE_FIRST_YOUR_SSN
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data. ' and userid: '.$user_id);
            Utility::createResponse($resp_data); 
        }
        $offer_data_array = $this->preperareConnectArray($offer_detail);
        $offer_data_array["user_id"] =  $offer_data_array['buyer_id'] = $user_id;
        $offer_data_array["application_id"] = $offer_id;
        //card preference we are storing  also  the card tha has been choosen to buy
        $offer_data_array["card_preference"]  = isset($data["card_id"])?$data["card_id"]:"";
        
        $TransactionData = $em->getRepository('TransactionSystemBundle:Transaction')
                                               ->createTransactionRecord($offer_data_array);
        //initiate the transaction on sixthcontinent
        $connect_transaction_id = $connect_app_service->initiateConnectTransation($offer_data_array);
        
        
        if ($connect_transaction_id == 0) {
            $resp_data = new Resp(Msg::getMessage(1143)->getCode(), Msg::getMessage(1143)->getMessage(), $result_data); //TRANSACTION_NOT_INITIATED_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data. 'and userid: '.$user_id);
            Utility::createResponse($resp_data);
        }
        
        //intiate the transaction on transaction system
        /*
        $ci_transaction_system_data = $connect_offer_purchase_service->registerTransactionOnApplane($user_id, $offer_detail['ci_used'], $offer_detail['checkout_with_vat'], $app_name, $connect_transaction_id, $offer_detail['total_value'], $offer_detail['cash_amount'], $applane_status);
        $ci_transaction_system_id = $ci_transaction_system_data['_id'];
        $ci_transaction_id = $ci_transaction_system_data['id'];
         * 
         */
        $ci_transaction_system_id = $ci_transaction_id  = $TransactionData['id'];
        if ($ci_transaction_system_id == 0) {
            $resp_data = new Resp(Msg::getMessage(1123)->getCode(), Msg::getMessage(1123)->getMessage(), $result_data); //CITIZEN_INCOME_IS_NOT_RESERVED_ON_TRANSACTION_SYSTEM
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\CredentialVerificationController] and function [postPurchaseOfferAction]  for userid: '.$user_id.' with response: '. (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        //prepare the cartisi gateway url.
        $payment_url = $connect_offer_purchase_service->createOfferPurchaseUrl($offer_data_array['payble_value'], $user_id, $connect_transaction_id, $return_url, $cancel_url);
        
        //update transaction in sixthcontinent with transaction system id and status, transaction id of TR system
        $connect_offer_purchase_service->updateOfferTransaction($connect_transaction_id, $ci_transaction_system_id, $pending_status, $payment_url, $ci_transaction_id);
        $result_data = array('url'=>$payment_url['url'], 'id'=>$connect_transaction_id);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] for userid: '.$user_id .' with response: '. (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
    public function preperareConnectArray($param) {


        $data['shop_id'] = $param['shop_id'];
        $data['currency'] = "EUR";
        $data['transaction_value'] = $param['result']["price_for_me"]["init_amount"];
        $data['discount'] = $param['result']["price_for_me"]["discount_value"];
        $data['payble_value'] = $param['result']["price_for_me"]["cashpayment"];
        $data['checkout_value'] = $param['result']["price_for_me"]["cashpayment"];
        $data['description'] = $param['result']["promotion_type"]["promotionLabel"];
        $data['total_available_ci'] = $param['result']["price_for_me"]["available_amount"];
        
        $data['used_ci'] = $param['result']["price_for_me"]["sixthcontinent_contribution"];
        $data['transaction_type'] = "PAY_ONCE_OFFER";
        $data['citizen_aff_charge'] = isset($param["result"]["extra_information"]["citizen_aff_charge"])?number_format($param["result"]["extra_information"]["citizen_aff_charge"], 3, '.', ''):'0.001';
        $data['shop_aff_charge'] = isset($param["result"]["extra_information"]["shop_aff_charge"])?number_format($param["result"]["extra_information"]["shop_aff_charge"], 3, '.', ''):'0';
        $data['friends_follower_charge'] = isset($param["result"]["extra_information"]["friends_follower_charge"])?number_format($param["result"]["extra_information"]["friends_follower_charge"], 3, '.', ''):'0';
        $data['buyer_charge'] = isset($param["result"]["extra_information"]["buyer_charge"])?number_format($param["result"]["extra_information"]["buyer_charge"], 3, '.', ''):'0.015';
        $data['sixc_charge'] = isset($param["result"]["extra_information"]["sixc_charge"])?number_format($param["result"]["extra_information"]["sixc_charge"], 3, '.', ''):'0.001';
        $data['all_country_charge'] = isset($param["result"]["all_country_charge"])?number_format($param["result"]["extra_information"]["all_country_charge"], 3, '.', ''):'0.001';
        $data['sixc_amount_pc'] = '0';
        $data['sixc_amount_pc_vat'] = '0';
        return $data ;
    }
    
    public function ciTransactionAction() {
       $export_connect_service = $this->container->get('sixth_continent_connect.connect_export_transaction_app');
       $export_connect_service->exportCiTransaction();
       exit;
    }

    /**
     * purchaase offer from tamoil response
     * @param request object
     * @return json
     */
    public function postResponsePurchaseOfferAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] ');
        $utilityService = $this->getUtilityService();
        $requiredParams = array('user_id', 'transaction_id', 'status');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] with request: ' . Utility::encodeData($data));
        //extract parameters
        $user_id = $data['user_id'];
        $transaction_id   = $data['transaction_id'];
        $status = Utility::getUpperCaseString($data['status']);
        $cancel_status = Utility::getUpperCaseString(ApplaneConstentInterface::CANCELED);
        $pending_status  = Utility::getUpperCaseString(ApplaneConstentInterface::PENDING);
        $completed_status  = Utility::getUpperCaseString(ApplaneConstentInterface::COMPLETED);
        $applane_approved_status = ApplaneConstentInterface::APPROVED;
        $applane_rejected_status = ApplaneConstentInterface::REJECTED;
        
        $em = $this->_getEntityManager();
        $offer = array();
        $status_array = array($pending_status, $cancel_status);
        if (!in_array($status, $status_array)) {
            $resp_data = new Resp(Msg::getMessage(1144)->getCode(), Msg::getMessage(1144)->getMessage(), $result_data);//TRANSACTION_STATUS_NOT_ACCEPTED
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $transaction = $connect_offer_purchase_service->getTransactionObject($transaction_id);
        if (!$transaction) {
            $resp_data = new Resp(Msg::getMessage(1145)->getCode(), Msg::getMessage(1145)->getMessage(), $result_data);//TRANSACTION_DOES_NOT_EXISTS
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] userid: '.$transaction_id.' and with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);  
        }
        $transaction_owner_id = $transaction->getUserId();
        $transaction_status   = Utility::getUpperCaseString($transaction->getStatus());
        $ci_transaction_system_id = $transaction->getCiTransactionSystemId();
        if ($user_id != $transaction_owner_id) {
            $resp_data = new Resp(Msg::getMessage(1146)->getCode(), Msg::getMessage(1146)->getMessage(), $result_data);//TRANSACTION_DOES_NOT_BELOGS_TO_YOU
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] with userid:'. $user_id. ' and response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        if ($status == $cancel_status) { //cancel status check
            if ($transaction_status == $pending_status) { //pending status
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
                $connect_offer_purchase_service->updateTransactionSystemStatus($transaction, $applane_rejected_status);
                $connect_offer_purchase_service->updateTransactionStatus($transaction, $cancel_status);
            } else if ($transaction_status == $cancel_status) { //cancel status
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
            } else if ($transaction_status == $completed_status) { //already completed
                $resp_data = new Resp(Msg::getMessage(1147)->getCode(), Msg::getMessage(1147)->getMessage(), $result_data);//TRANSACTION_ALREADY_COMPLETED_CAN_NOT_MARK_CANCEL
            }
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] transactionid: '.$transaction_id. ' and userid: '.$user_id.' and with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        } 
        if ($status == $pending_status) { //success case
            if ($transaction_status == $pending_status) {
                $resp_data = new Resp(Msg::getMessage(1149)->getCode(), Msg::getMessage(1149)->getMessage(), $result_data);//TRANSACTION_IS_IN_PENDING_MODE_CAN_NOT_MARK_COMPLETE
            } else if ($transaction_status == $cancel_status) {
                $resp_data = new Resp(Msg::getMessage(1148)->getCode(), Msg::getMessage(1148)->getMessage(), $result_data);//TRANSACTION_ALREADY_CANCELED_CAN_NOT_MARK_COMPLETE
                Utility::createResponse($resp_data);
            } else if ($transaction_status == $completed_status) {
                //update on transaction system.
//                $connect_offer_purchase_service->updateTransactionSystemStatus($ci_transaction_system_id, $applane_approved_status);
//                $offer = $connect_offer_purchase_service->getOfferPurchaseData($transaction);
//                $couponService = $this->container->get('tamoil_offer.coupon');
//                $couponService->createCoupon($user_id, $offer); 
                $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
                $transaction_record = $connect_offer_purchase_service->getTransactionObject($transaction_id);
                $result_data["offer_id"] =  $transaction_record->getApplicationId();
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
            }
        }
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postResponsePurchaseOfferAction] transactionid: '.$transaction_id.' and userid:'.$user_id.' and with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * post back of cartisi 
     * $param mixed $_POST
     */
    public function offerpurchasebackAction() {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $applane_approved_status = ApplaneConstentInterface::APPROVED;
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [offerpurchasepostbackAction]', array());
        $utilityService = $this->getUtilityService();
        $post_data = $_POST;
        $transaction_id = $_GET['txn_id'];
        $completed_status = Utility::getUpperCaseString(ApplaneConstentInterface::COMPLETED);
        $canceled_status  = Utility::getUpperCaseString(ApplaneConstentInterface::CANCELED);
        $connect_offer_purchase_service->__createLog('Transaction id  is coming for cartisi gateway: '.$transaction_id);
        $em = $this->_getEntityManager();
        $connect_offer_purchase_service->__createLog('Post data is coming for cartisi gateway: '.json_encode($post_data));
        if ($transaction_id != '') {
            $transaction_record = $connect_offer_purchase_service->getTransactionObject($transaction_id);
            if (!$transaction_record) {
               $connect_offer_purchase_service->__createLog('Transaction not found for id: '.$transaction_id); 
            }
            if (isset($_POST['esito']) && Utility::getLowerCaseString($_POST['esito']) == Utility::getLowerCaseString(self::STATUS_OK)) { //successful transaction case
                $connect_offer_purchase_service->updateTransactionStatus($transaction_record, $completed_status);
                $user_id = $transaction_record->getUserId();
                $ci_transaction_system_id = $transaction_record->getCiTransactionSystemId();
                ///$connect_offer_purchase_service->updateTransactionSystemStatus($ci_transaction_system_id, $applane_approved_status);//Applane transaction approved. not used
                $this->updateTransactionAndStartRedistribution($ci_transaction_system_id);
                $offer = $connect_offer_purchase_service->getOfferPurchaseData($transaction_record);
                
                $search["offer_id"] = $transaction_record->getApplicationId();
                $search["buyer_id"] =  $transaction_record->getUserId();
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $repo_commercial_offer = $em->getRepository("CommercialPromotionBundle:CommercialPromotion");
                $offer_detail = $repo_commercial_offer->getCommercialPromotionDetail($search , $dm);
                
                if($offer_detail["result"]["promotion_type"]["promotionType"]=="genericvoucher"){
                     $Transaction = $em->getRepository('TransactionSystemBundle:Transaction')
                        ->findOneBy(array( 'id'=>$transaction_record->getCiTransactionSystemId()));
                    $amilon_card_add  = $this->container->get('amilon_offer.card');
                    $amilon_card_add->saveAmilonOffer($transaction_record , $Transaction , $offer , $offer_detail);
                }else{
                    $couponService = $this->container->get('tamoil_offer.coupon');
                    $couponService->createCoupon($user_id, $offer);
                }
                                
            } else if (isset($_POST['esito']) && Utility::getLowerCaseString($_POST['esito']) == Utility::getLowerCaseString(self::STATUS_KO)) { //transaction failed or unsuccessful.
                $connect_offer_purchase_service->updateTransactionStatus($transaction_record, $canceled_status);
                $connect_offer_purchase_service->updateTransactionSystemStatus($transaction_record, $canceled_status);
            }
        }
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [offerpurchasepostbackAction]', array());
        exit('DONE');
    }
    
    public function downloadFileAction($type, $id, Request $request){
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $connect_offer_purchase_service->__createLog('Entering in class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [downloadFileAction]', array());
        $dType = strtolower($type);
        $allowedDownloadTypes = array('coupon');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $notFoundHtml = "<html><body><div style='max-width:600px;margin:0 auto;'><h1>Sorry! We couldn't find it.</h1><br><p>You have requested a page or file which does not exist. <br> <a href='$angular_app_hostname'>Click here</a> to visit SixthContinent.</p></div></body></html>";
        if(!in_array($dType, $allowedDownloadTypes)){
            $connect_offer_purchase_service->__createLog('[OfferPurchaseController:downloadFileAction] File does not exists.', array());
            return new Response($notFoundHtml, Response::HTTP_NOT_FOUND, array('Content-Type'=>'text/html'));
        }
        $userId = $request->query->get('session_id');
        $file = '';
        switch($dType){
            case 'coupon':
                $transactionId = $id;
                $file = $this->_getCouponFile($userId, $transactionId);
                break;
        }
        
        if(!empty($file)){
            $filename = basename($file);
            try{
                $response = new StreamedResponse(
                        function () use ($file) {
                                echo file_get_contents($file);
                        });

                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Cache-Control', '');
                $response->headers->set('Content-Length', strlen(file_get_contents($file)));
                $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s'));
                $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);
                $response->headers->set('Content-Disposition', $contentDisposition);
                $response->prepare($request);
                $connect_offer_purchase_service->__createLog('[OfferPurchaseController:downloadFileAction] Downloading file - '.$file, array());
                return $response;
            }catch(\Exception $e){
                $connect_offer_purchase_service->__createLog('[OfferPurchaseController:downloadFileAction] '. $e->getMessage(), array());
            }
        }
        $connect_offer_purchase_service->__createLog('[OfferPurchaseController:downloadFileAction] File does not exists.', array());
        return new Response($notFoundHtml, Response::HTTP_NOT_FOUND, array('Content-Type'=>'text/html'));
    }
    
    public function genericvoucherViewAction( $id, Request $request){
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $connect_offer_purchase_service->__createLog('Entering in class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [downloadFileAction]', array());
        
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        

        $userId = $request->query->get('session_id');
        $transactionId = $id ;
        $activation_status = $this->isCardActive($transactionId);
        $genericvoucher = null;
        if($activation_status == 1 ){
            $genericvoucher = $this->_getCouponHtmlPage($userId, $transactionId);
        }
  
        $notFoundHtml = "<html><body><div style='max-width:600px;margin:0 auto;'><h1>It will be active on $activation_status <br> Sara' attiva il $activation_status  </h1><br><p>You have requested a page or file which does not exist. <br> <a href='$angular_app_hostname'>Click here</a> to visit SixthContinent.</p></div></body></html>";
        if($genericvoucher!= "" && $genericvoucher != null && $activation_status == 1 ){
            $html_page = file_get_contents($genericvoucher);
            return new Response($html_page, Response::HTTP_NOT_FOUND, array('Content-Type'=>'text/html'));
            exit();
        }
        $connect_offer_purchase_service->__createLog('[OfferPurchaseController:downloadFileAction] File does not exists.', array());
        return new Response($notFoundHtml, Response::HTTP_NOT_FOUND, array('Content-Type'=>'text/html'));
    }
    
    private function _getCouponFile($userId, $transactionId){
        $em = $this->_getEntityManager();
        $codesC = $em->getRepository('SixthContinentConnectBundle:CodesConsumption')
                    ->findOneBy(array('userId'=>$userId, 'transactionId'=>$transactionId));
        $file = '';
        if($codesC){
            $sourcePath = dirname(dirname(dirname(dirname(__DIR__)))).'/web/uploads/attachments/coupons/';
            $fileName = $codesC->getCoupon();
            $file = !empty($fileName) ? $sourcePath.$fileName : '';
        }
        return $file;
    }
    private function _getCouponHtmlPage($userId, $transactionId){
        $em = $this->_getEntityManager();

        $amilon_card_add  = $this->container->get('amilon_offer.card');
        $wallets = $em->getRepository("WalletBundle:WalletCitizen")
                ->getWalletData($userId);
        $wallet = $wallets[0];
        
        $amilon = $em->getRepository("WalletBundle:AmilonCard")
                ->findOneBy(array( 'connectTrsId'=>$transactionId ,"walletCitizenId"=>$wallet->getId() ));
        //Checking if The card needs a code already paid
        if( is_numeric($amilon->getAmilonCardId()) ){
            if($amilon->getProductCode() != null){
            }else{
            $amilon = $amilon_card_add->getCardPaid( $amilon , $userId );   
            }
            echo "Codice Voucer: ". $amilon->getProductCode();
            exit();
        
        }else{
        $amilon_return = $amilon_card_add->getAmilonCard( $amilon , $userId );    
        }
        
        return $amilon_return->getLink();
    }
    
    public function purchaseOfferWalletAction(Request $request){
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            return Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $userId = $data['user_id'];
        $limitStart = isset($data['limit_start']) ? (int)$data['limit_start'] : 0;
        $limitSize = (isset($data['limit_size']) and $data['limit_size']>0) ? (int)$data['limit_size'] : 0;
        $completed_status  = Utility::getUpperCaseString(ApplaneConstentInterface::COMPLETED);
        $em = $this->_getEntityManager();
        $coupons = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')->getPurchasedCouponDetails($userId, $completed_status, $limitStart, $limitSize);
            
        if(empty($coupons)){
            $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array('records'=>array(), 'count'=>0));
            return Utility::createResponse($resp_data);
        }
        
        $response = array();
        $offerPurchaseService = $this->container->get('sixth_continent_connect.purchasing_offer_transaction');
        $symfonyBaseUrl = $this->container->getParameter('symfony_base_url');
        
        foreach($coupons as $coupon){
            if($coupon["promotion_type"]!="genericvoucher"){
                $expiry_date = $coupon['expiry_date'];
                $couponDownloadLink = $symfonyBaseUrl.'api/downloadfile/coupon/';
            }else{
                $expiry_date = $coupon['validity_end_date_h'];
                $couponDownloadLink = $symfonyBaseUrl.'api/genericvoucherview/';
                
            }
            if(($coupon["coupon"]!= null && $coupon["coupon"]!="") || $coupon["promotion_type"]=="genericvoucher" ){
            $response[] = array(
                "name"=>$coupon['cpt_description'],
                "coupon_value"=> $offerPurchaseService->changeRoundAmountCurrency($coupon['transaction_value']),
                "used_ci"=> $offerPurchaseService->changeRoundAmountCurrency($coupon['used_ci']),
                "discount"=> $offerPurchaseService->changeRoundAmountCurrency($coupon['discount']),
                "cash_amount"=> $offerPurchaseService->changeRoundAmountCurrency($coupon['payble_value']),
                "coupon_link"=>$couponDownloadLink.$coupon['transaction_id'],
                "purchase_date"=>$coupon['purchase_date'],
                "expiry_date"=>$expiry_date,
                'transaction_id'=> $coupon['transaction_id']
                );
            }
        }
        $responseCount = $em->getRepository('SixthContinentConnectBundle:Sixthcontinentconnecttransaction')->getPurchasedCouponDetailsCount($userId, $completed_status);
        
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), array('records'=>$response, 'count'=>$responseCount));
        return Utility::createResponse($resp_data);
    }
    
    /**
     * 
     * @param type $id_transaction
     * @return boolean
     */
    public function updateTransactionAndStartRedistribution($id_transaction){
        
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $connect_offer_purchase_service->__createLog('[OfferPurchaseController:updateTransactionAndStartRedistribution] START ', array());
        $redistribution_ci  = $this->container->get('redistribution_ci');
        $em = $this->_getEntityManager();
        $TransData = $em->getRepository("TransactionSystemBundle:Transaction")
                     ->updateTransactionRecord(array('transaction_id' => $id_transaction, 'status' => 'COMPLETED'));
             
        $id_transaction = $TransData["id"];
        $sellerId = $TransData["seller_id"];
        $time_close = $TransData["time_close"];
        $transactionGatewayReference  = TransactionRepository::$TRNS_GATEWAY_REFERENCE_OFFER;
        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
        $connect_offer_purchase_service->__createLog('[OfferPurchaseController:updateTransactionAndStartRedistribution] FINISH ', array());
        return true;
    }
    /**
     * 
     * @param \SixthContinent\SixthContinentConnectBundle\Controller\type $id
     * @return boolean|\SixthContinent\SixthContinentConnectBundle\Controller\booleanFind
     * @param type $id
     * @return booleanFind if the transaction has been activated or not
     */
    public function isCardActive($id) {
        $six_repo = $this->_getEntityManager()->getRepository("SixthContinentConnectBundle:Sixthcontinentconnecttransaction");
        $sixth_connect = $six_repo->findOneBy(array("id"=>$id));
        if(!isset($sixth_connect)){
            return false; 
        }
        $tdate = $sixth_connect->getDate();
        $activationdDate = strtotime($tdate->format('Y-m-d') . ' +4 Weekday');

        $now = time();
        if ($now > $activationdDate) {
            return "1";
        } else {
            return date("d-m-Y", $activationdDate);
        }
    }
}
