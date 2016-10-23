<?php

namespace Transaction\CommercialPromotionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
//Utilities
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;

class CommercialPromotionController extends Controller {

    private $_commercial_promotion_repo = null;

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

    public function setCommercialPromotionRepo() {
        $this->_commercial_promotion_repo = $this->get('doctrine')->getEntityManager()
                ->getRepository('CommercialPromotionBundle:CommercialPromotion');
    }

    /**
     * 
     * @param type $data ($object of promotion)
     */
    public function createComercialPromotionAction(Request $request) {
        $result_data = array();

        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array("promotion_type", "seller_id", "price", "promotion_type");
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }

        $promotion = $data;

        $this->setCommercialPromotionRepo();
        $promotion["sex_female"] = 1;

        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $return = $this->_commercial_promotion_repo
                ->createCommercialPromotion($promotion, $dm);

        /* Return Response Data */
        if (isset($return)) {
            $result_data['code'] = 100;
            $result_data['message'] = 'SUCCESS';
            $result_data['response'] = array("id" => $return->getId());
        } else {
            $result_data['code'] = 7417346;
            $result_data['message'] = 'FAILURE';
        }
        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"]);
        Utility::createResponseResult($resp_data);
    }

    /**
     * Get the offer active in store
     * @param Request $request 
     */
    public function getStoreOfferActiveAction(Request $request) {
        $result_data = array();

        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('user_id', 'seller_id', 'type');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }

        $this->setCommercialPromotionRepo();
        $commertial_promotion_id = null;
        $seller_id = $data["seller_id"];
        $type = $data["type"];

        $result_data = $this->_commercial_promotion_repo
                ->getComercialPromotion($commertial_promotion_id, $seller_id, 0, $limit = 100, $type);

        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"]);
        Utility::createResponseResult($resp_data);
    }

    /**
     * Delete the coomercial offer
     * @param Request $request
     */
    public function deleteCommercialPromotionAction(Request $request) {
        $result_data = array();

        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('user_id', 'seller_id', 'offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }

        $this->setCommercialPromotionRepo();

        $result_data = $this->_commercial_promotion_repo
                ->deleteCommercialPromotion($data);

        $resp_data = new Resp($result_data['code'], $result_data['message'], array());
        Utility::createResponse($resp_data);
    }
    
    /**
     * Get Detail of Single Commercial Promotion
     * @param Request $request
     */
    public function getCommercialPromotionDetailAction(Request $request) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('buyer_id', 'seller_id', 'offer_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        $this->setCommercialPromotionRepo();
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $resp_data = $this->_commercial_promotion_repo
                ->getCommercialPromotionDetail($data , $dm);
        
        $resp_data = new Resp($resp_data['code'], $resp_data['message'], $resp_data["result"]);
        Utility::createResponseResult($resp_data);
    }    

}
