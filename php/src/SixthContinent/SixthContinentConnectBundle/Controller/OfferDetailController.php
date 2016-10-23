<?php

namespace SixthContinent\SixthContinentConnectBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

class OfferDetailController extends Controller {

    CONST LIMIT_SIZE = 20;
    
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
     * purchase offer detail 
     * @param request object
     * @return json
     */
    public function postPurchaseOfferDetailAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferDetailAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id', 'offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferDetailAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $user_id = $data['user_id'];
        $offer_id = $data['offer_id'];
        $em = $this->_getEntityManager();
        //check offer
        $offer_record = $connect_offer_purchase_service->getOfferPurchaseRecord($offer_id);
        if (!$offer_record) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        
        $offer_detail = $connect_offer_purchase_service->getOfferDetail($user_id, $offer_record); //get offer detail
        if ($offer_detail['_id'] == '') {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        //check for ssn
        $ssn_flag = $connect_offer_purchase_service->checkCitizenSsn($user_id);
        $result_data = $connect_offer_purchase_service->getOfferObject($offer_record, $offer_detail);
        $result_data['is_ssn'] = $ssn_flag;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferDetailAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
     /**
     * purchase offer point of sale
     * @param request object
     * @return json
     */
    public function postPurchaseOfferPointOfSaleAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferPointOfSaleAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id', 'offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferPointOfSaleAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $user_id = $data['user_id'];
        $offer_id = $data['offer_id'];
        $limit_start = isset($data['limit_start']) ? $data['limit_start'] : 0;
        $limit_size  = isset($data['limit_size']) ? $data['limit_size'] : self::LIMIT_SIZE;
        $em = $this->_getEntityManager();
        $count = 0;
        //check offer
        $offer_record = $connect_offer_purchase_service->getOfferPurchaseRecord($offer_id);
        if (!$offer_record) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [postPurchaseOfferPointOfSaleAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = $connect_offer_purchase_service->getOfferPointofSale($offer_id, $limit_start, $limit_size);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [postPurchaseOfferPointOfSaleAction]');
        Utility::createResponse($resp_data);
    }
    /**
     * purchase offer detail 
     * @param request object
     * @return json
     */
    
    public function purchasetamoilofferdetailAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferdetailAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferdetailAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $offer_id = $data['offer_id'];
        $em = $this->_getEntityManager();
        //check offer
        $offer_record = $connect_offer_purchase_service->getOfferPurchaseRecord($offer_id);
        if (!$offer_record) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [purchasetamoilofferdetailAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = $connect_offer_purchase_service->getPublicOfferDetail($offer_record);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferdetailAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
    /**
     * purchase offer point of sale
     * @param request object
     * @return json
     */
    
    public function purchasetamoilofferpointofsaleAction(Request $request){
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_offer_purchase_service = $this->_getSixcontinentOfferService();
        $result_data = array();
        $connect_offer_purchase_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferpointofsaleAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferpointofsaleAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $offer_id = $data['offer_id'];
        $limit_start = isset($data['limit_start']) ? $data['limit_start'] : 0;
        $limit_size  = isset($data['limit_size']) ? $data['limit_size'] : self::LIMIT_SIZE;
        $em = $this->_getEntityManager();
        $count = 0;
        //check offer
        $offer_record = $connect_offer_purchase_service->getOfferPurchaseRecord($offer_id);
        if (!$offer_record) {
            $resp_data = new Resp(Msg::getMessage(1142)->getCode(), Msg::getMessage(1142)->getMessage(), $result_data); //OFFER_NOT_FOUND_ON_SIXTHCONTINENT
            $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferPurchaseController] and function [purchasetamoilofferpointofsaleAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $result_data = $connect_offer_purchase_service->getOfferPointofSale($offer_id, $limit_start, $limit_size);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_offer_purchase_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\OfferDetailController] and function [purchasetamoilofferpointofsaleAction]');
        Utility::createResponse($resp_data);
    }
}
