<?php

namespace StoreManager\StoreBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use StoreManager\StoreBundle\Entity\Store;
use Transaction\CommercialPromotionBundle\Entity\CommercialPromotion;
use Transaction\CommercialPromotionBundle\Repository\CommercialPromotionRepository;

class MapController extends Controller {

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

    public function getStoreMarkersAction(Request $request) {
        $result_data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);

        $markers_result = $this->_getEntityManager()
                ->getRepository("StoreManagerStoreBundle:Store")
                ->getStoreMarkers($data);
        $is_result_store =( isset($markers_result["results"]) && count($markers_result["results"]) > 0 ) ? true :false;

        $markers_result_offer["result"] = array();
        $data["offer_type"] = "voucher";
        
        

        //in the case of the map it avoid to show markers whene is selected the commercial promorion
        $seller_id = (isset($data["seller_id"]) && $data["seller_id"] > 0 )?$data["seller_id"]:null;
        $offer_id = (isset($data["offer_id"]) && $data["offer_id"] > 0 )?$data["offer_id"]:null;
        $query = (isset($data["query"]) && strlen($data["query"]) > 1 )?$data["query"]:null;
        $category_id = (isset($data["category_id"]) && $data["category_id"]!=null && count($data["category_id"]) > 0  )?$data["category_id"]:array();
        $is_result_offer = true;
        
        if ( $seller_id!= null || $query!=null || count($category_id) > 0) {
            $is_result_offer = false;
        } 
        if( $seller_id==null && $query == null && in_array(18 , $category_id)){
            $is_result_offer = true;
        }

        if($is_result_offer ) { // front end need any parameter
            $markers_result_offer = $this->_getEntityManager()
                    ->getRepository('CommercialPromotionBundle:CommercialPromotion')
                    ->getCommercialPromotionDetail($data, $dm);
            $is_result_offer = (isset($markers_result_offer["result"]["point_of_sales"]) && count($markers_result_offer["result"]["point_of_sales"]) > 0) ? true : false;
        }
        /*sixthcontinent connect shop*/
        $pos = strstr("buono carburante elettronico tamoil" , strtolower($query));
        if($seller_id =="50916" || $pos || $offer_id!=null){
            if($offer_id!=null){
            unset($data["promotionType"]);
            unset($data["offer_type"]);
            }else{
            $data["promotionType"] =  CommercialPromotionRepository::$VOUCHER_TYPE;    
            }
            $data["seller_id"] = "50916" ;
            $markers_result_offer = $this->_getEntityManager()
                    ->getRepository('CommercialPromotionBundle:CommercialPromotion')
                    ->getCommercialPromotionDetail($data, $dm);
            $is_result_offer =  true ;
            
        }



        $resp_date["result_offer"] = $markers_result;
        $resp_data = array("code" => $markers_result['code'], 'message' => $markers_result['message'],
            "result" => $markers_result["results"],
            "result_offer" => $markers_result_offer["result"],
            "is_result_offer" => $is_result_offer,
            "is_result_store" => $is_result_store,
        );


        echo json_encode($resp_data);
        exit();
    }

}
