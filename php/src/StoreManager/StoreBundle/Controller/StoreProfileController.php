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
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Document\Affiliation;
#use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationShop;
use Affiliation\AffiliationManagerBundle\Entity\AffiliationCitizen;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use StoreManager\PostBundle\Document\ItemRating;
use StoreManager\StoreBundle\Controller\RestStoreController as StoreController;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use StoreManager\StoreBundle\Utils\MessageFactory as Msg;
use UserManager\Sonata\UserBundle\Entity\UserConnection;

class StoreProfileController extends StoreController {

    private $affiliation_type = array('SHOP', 'CITIZEN');
    private $affiliation_status = array(1, 2);

    /**
     *  function for managin the affiliation process for the citizen and shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postUpdateAffiliationsAction(Request $request) {
        $this->__createLog('[Entering in StoreProfileController->postUpdateAffiliationsAction(Request)]');
        $data = array();
        $required_parameter = array('session_id', 'type', 'to_id', 'affiliation_status');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Controller\StoreProfileController] and function [postUpdateAffiliationsAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $from_id = '';
        $user_id = $de_serialize['session_id'];
        $to_id = $de_serialize['to_id'];
        $affiliation_type = $de_serialize['type'];
        $affiliation_status = $de_serialize['affiliation_status'];
        //check if from is set if set then assign it in the $from_id
        if (isset($de_serialize['from_id'])) {
            $from_id = $de_serialize['from_id'];
        }
        $valid_affiliation_status = $this->affiliation_status;
        //check for valid affiliation type
        if (!in_array($affiliation_status, $valid_affiliation_status)) {
            $resp_data = new Resp(Msg::getMessage(1110)->getCode(), Msg::getMessage(1110)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Controller\StoreProfileController] and function [postUpdateAffiliationsAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        //check if the from id is not passed and affiliation status is 1
        if ($from_id == '' && $affiliation_status == 1) {
            $resp_data = new Resp(Msg::getMessage(1106)->getCode(), Msg::getMessage(1106)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Controller\StoreProfileController] and function [postUpdateAffiliationsAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }

        //convert the affiliation type to uppercase and trimmed string
        $affiliation_type_const = Utility::getUpperCaseString(Utility::getTrimmedString($affiliation_type));
        $valid_affiliation_type = $this->affiliation_type;

        //check for valid affiliation type
        if (!in_array($affiliation_type_const, $valid_affiliation_type)) {
            $resp_data = new Resp(Msg::getMessage(1107)->getCode(), Msg::getMessage(1107)->getMessage(), $data); //INAVALID_SHOP_OR_OWNER_ID
            $this->__createLog('Exiting from class [StoreManager\StoreBundle\Controller\StoreProfileController] and function [postUpdateAffiliationsAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }  
        switch ($affiliation_type_const) {
            case 'SHOP':
                 $store_service = $this->container->get('store_manager_store.storeUpdate');
                 //check if store is already affiliated
                 $response = $store_service->updateStoreAffiliation($user_id, $from_id, $to_id, $affiliation_status);
                break;
            case 'CITIZEN':
                $store_service = $this->container->get('store_manager_store.storeUpdate');
                $response = $store_service->updateUserAffiliation($user_id, $from_id, $to_id, $affiliation_status);
                break;
        }
        
        Utility::createResponse($response);
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    private function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.store_profile');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
}
