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

class AppTransactionController extends Controller {


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
    protected function _getSixcontinentTransactionAppService() {//transaction app service
        return $this->container->get('sixth_continent_connect.connect_transaction_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectAppTransactionService
    }
    /**
     * find the business account profile
     * @param request object
     * @return json
     */
    public function postAppConnectTransactionHistoryAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_business_app_service = $this->_getSixcontinentBusinessAppService();
        $connect_transaction_app_service = $this->_getSixcontinentTransactionAppService();
        $result_data = array();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\AppTransactionController] and function [postAppConnectTransactionHistoryAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id', 'app_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppTransactionController] and function [postAppConnectTransactionHistoryAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $user_id = $data['user_id'];
        $app_id  = $data['app_id'];
        $limit_start = isset($data['limit_start']) ? $data['limit_start'] : 0;
        $limit_size  = isset($data['limit_size']) ? $data['limit_size'] : 20;
        $em = $this->_getEntityManager();
        $transaction_count = 0;
        $is_owner = 0;
        $transaction_result_data = array('transaction_records'=>array());
        $app_data = $connect_business_app_service->getApplicationProfile($app_id);//checking the application is exists
        if (!$app_data) {
            $resp_data = new Resp(Msg::getMessage(1118)->getCode(), Msg::getMessage(1118)->getMessage(), $result_data);//INVALID_APP
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppTransactionController] and function [postAppConnectTransactionHistoryAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $app_name     = $app_data->getApplicationName();
        $app_owner_id = $app_data->getUserId();
        if (Utility::getIntergerValue($app_owner_id) == Utility::getIntergerValue($user_id)) {
            $is_owner = 1;
        }
        //getting the transaction of an application
        $transaction_data = $connect_transaction_app_service->getApplicationTransaction($app_id, $limit_start, $limit_size);
        $transaction_ids  = $transaction_data['transaction_ids'];
        $transactions     = $transaction_data['transactions'];
        $user_ids = $transaction_data['user_ids'];
        if (count($transaction_ids)) {
            $paypal_transaction_records = $connect_transaction_app_service->getApplicationPaypalRecordsTransaction($transaction_ids);
            $transaction_count = $connect_transaction_app_service->getApplicationTransactionCount($app_id);
            //prepare the object
            $transaction_result_data = $connect_transaction_app_service->prepareTransactionObject($transactions, $paypal_transaction_records, $user_ids, $app_data);
        }
        
        $result_data = array('transaction_records'=>$transaction_result_data['transaction_records'], 'is_owner'=>$is_owner, 'count'=>$transaction_count);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppTransactionController] and function [postAppConnectTransactionHistoryAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
}
