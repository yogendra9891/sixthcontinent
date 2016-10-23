<?php

namespace Transaction\WalletBundle\Controller;

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

class HistoryWalletController extends Controller {


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

    public function showHistoryWalletCitizenAction(Request $request) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('buyer_id', 'numberofdays', 'startday');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        $wallet_citizen = $this->container->get("wallet_manager");
        $result_data = $wallet_citizen->giveHistoryWalletCitizenList($data);
        
        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"] , $result_data["dataInfo"]);
        Utility::createResponseDataInfo($resp_data);
    }
    
    public function historyWalletCitizenDetailAction(Request $request) {
        $result_data = array();
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('buyer_id', 'record_id', 'record_type_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        
        $wallet_citizen = $this->container->get("wallet_manager");
        $result_data = $wallet_citizen->getRecordDetail($data);
        
        $resp_data = new Resp($result_data['code'], $result_data['message'], $result_data["response"]);
        Utility::createResponseResult($resp_data);
    }
    public function testCiDetailAction() {
        $wallet_citizen = $this->container->get("wallet_manager");
        $limit = 500 ;
        $skip = 0 ;
        $start_date ="04-11-2015";
        $end_date ="04-11-2015";
        $result_data = $wallet_citizen->getUserWithFullWallet($limit, $skip , $start_date , $end_date );
        print_r($result_data);
        exit();

    }
    

}
