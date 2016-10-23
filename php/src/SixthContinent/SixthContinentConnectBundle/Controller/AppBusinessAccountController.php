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

class AppBusinessAccountController extends Controller {

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
    /**
     * find the business account profile
     * @param request object
     * @return json
     */
    public function postAppConnectBusinessAccountAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_business_app_service = $this->_getSixcontinentBusinessAppService();
        $result_data = array();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectBusinessAccountAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id', 'app_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectBusinessAccountAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request
        //extract parameters
        $user_id = $data['user_id'];
        $app_id  = $data['app_id'];
        $is_owner = 0;
        $em = $this->_getEntityManager();
        $app_data = $connect_business_app_service->getApplicationProfile($app_id);//checking the application is exists
        if (!$app_data) {
            $resp_data = new Resp(Msg::getMessage(1118)->getCode(), Msg::getMessage(1118)->getMessage(), $result_data); //INVALID_APP
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectBusinessAccountAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $app_name  = $app_data->getApplicationName();
        $app_owner_id = $app_data->getUserId();
        if (Utility::getIntergerValue($app_owner_id) == Utility::getIntergerValue($user_id)) {
            $is_owner = 1;
        }
        $business_account_info = $em->getRepository('SixthContinentConnectBundle:Application')
                                    ->getApplicationBusinessInformation($app_id);
        $result_data = $connect_business_app_service->getBusinessAccountInfo($business_account_info);
        
        //finding the total transations count and total amount against the app.
        $app_transaction_revenue = $connect_business_app_service->getAppTransactionRevenue($app_id);
        //find the count of completed transaction on a app.
        $app_transaction_count = $connect_business_app_service->getAppTransactionCount($app_id);
        $result_data['total_transaction'] = $app_transaction_count;
        $result_data['total_transaction_amount'] = $connect_app_service->changeRoundAmountCurrency($app_transaction_revenue);
        $result_data['is_owner'] = $is_owner;
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectBusinessAccountAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
    
    public function ciTransactionAction() {
       $export_connect_service = $this->container->get('sixth_continent_connect.connect_export_transaction_app');
       $export_connect_service->exportCiTransaction();
       exit;
    }
    
    /**
     * search application account.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAppConnectSearchAction(Request $request) {
        $connect_app_service = $this->_getSixcontinentAppService();
        $connect_business_app_service = $this->_getSixcontinentBusinessAppService();
        $result_data = array();
        $connect_app_service->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectSearchAction]', array());
        $utilityService = $this->getUtilityService();

        $requiredParams = array('user_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectSearchAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request); //getting the data from request 
        $user_id = $data['user_id'];
        $search_string = isset($data['search_string']) ? $data['search_string'] : '';
        $limit_start   = isset($data['limit_start']) ? $data['limit_start'] : 0;
        $limit_size    = isset($data['limit_size']) ? $data['limit_size'] : self::LIMIT_SIZE;
        $result_data = $connect_business_app_service->searchApplications($user_id, $search_string, $limit_start, $limit_size);
        $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $result_data);//SUCCESS
        $connect_app_service->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Controller\AppBusinessAccountController] and function [postAppConnectSearchAction] with response: ' . (string) $resp_data);
        Utility::createResponse($resp_data);
    }
}
