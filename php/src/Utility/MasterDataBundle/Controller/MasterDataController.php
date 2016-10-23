<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Utility\MasterDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use PostFeeds\PostFeedsBundle\Utils\MessageFactory as Msg;

/**
 * Controller managing the master data
 */
class MasterDataController extends Controller
{
    CONST SELFRELATIONSHIP = 'SELFRELATIONSHIP';
    CONST FAMILYRELATIONSHIP = 'FAMILYRELATIONSHIP';
    CONST EDUCATION = 'EDUCATION';
    CONST LEGALSTATUS = 'LEGALSTATUS';
    CONST REGION = 'REGION';
    CONST COUNTRY = 'COUNTRY';
    CONST PRIVACY_SETTING = 'PRIVACY_SETTING';
    CONST MONTH = 'MONTH';
    CONST VISIBILITY = 'VISIBILITY';
    CONST LANGUAGE = 'LANGUAGE';
    CONST BUSINESS_CATEGORY = 'BUSINESS_CATEGORY';
    
    /**
     * Get master data
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function getMasterDataAction(Request $request) {
        $masterdata_service = $this->getMasterDataService();
        $masterdata_service->__createLog('Entering into class [Utility\MasterDataBundle\Controller] and function [getMasterDataAction]', array());
        $utilityService = $this->getUtilityService();
        $requiredParams = array('lang_code');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getMasterDataAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $type = (isset($data['type'])) ? trim($data['type']) : '';
        if($type == ''){
            $response = $this->getAllData($data);
        }else{
            $response  = $this->getTypeData($data);
        }
        Utility::createResponse($response);
    }
    
    /**
     * 
     * @param attay $data
     */
    public function getTypeData($data)
    {
        $masterdata_service = $this->getMasterDataService();
        $lang_code = $data['lang_code'];
        //check for valid language
        $is_valid_lang = $masterdata_service->checkValidCountryCode($lang_code);
        if(!$is_valid_lang){
          $lang_code = $masterdata_service->getDefaultLanguageCode();
        }

        $type = Utility::getUpperCaseString(Utility::getTrimmedString($data['type']));
        switch($type){
            case Utility::getUpperCaseString(self::SELFRELATIONSHIP): 
                $masterdata_self_relation = $masterdata_service->getSelfRelationShipType($lang_code);
                $masterdata_self_relation = array('selfRelationShipType' => $masterdata_self_relation);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_self_relation);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::FAMILYRELATIONSHIP): 
                $masterdata_family_relation = $masterdata_service->getFamilyRelationShipType($lang_code);
                $masterdata_family_relation = array('familyRelationShipType' => $masterdata_family_relation);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_family_relation);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::EDUCATION): 
                $masterdata_education = $masterdata_service->getEducation($lang_code);
                $masterdata_education = array('education' => $masterdata_education);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_education);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::LEGALSTATUS): 
                $masterdata_legal_status = $masterdata_service->getLegalStatus($lang_code);
                $masterdata_legal_status = array('legalStatus' => $masterdata_legal_status);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_legal_status);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::REGION): 
                $masterdata_region = $masterdata_service->getRegion($lang_code);
                $masterdata_region = array('region' => $masterdata_region);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_region);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::COUNTRY): 
                $masterdata_country = $masterdata_service->getCountry($lang_code);
                $masterdata_country = array('country' => $masterdata_country);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_country);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::PRIVACY_SETTING): 
                $masterdata_privacy_setting = $masterdata_service->getPrivacySetting($lang_code);
                $masterdata_privacy_setting = array('privacySetting' => $masterdata_privacy_setting);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_privacy_setting);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::MONTH): 
                $masterdata_months = $masterdata_service->getMonths($lang_code);
                $masterdata_months = array('month' => $masterdata_months);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_months);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::VISIBILITY): 
                $masterdata_visibility = $masterdata_service->getVisibilityList($lang_code);
                $masterdata_visibility = array('visibility' => $masterdata_visibility);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_visibility);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            case Utility::getUpperCaseString(self::LANGUAGE): 
                $masterdata_language = $masterdata_service->getLanguageList($lang_code);
                $masterdata_language = array('language' => $masterdata_language);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_language);
                return $resp_data;
            case Utility::getUpperCaseString(self::BUSINESS_CATEGORY): 
                $masterdata_category = $masterdata_service->getBusinessCategory($lang_code);
                $masterdata_category = array('businessCategory' => $masterdata_category);
                $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $masterdata_category);
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
            default:
                $resp_data = new Resp(Msg::getMessage(1136)->getCode(), Msg::getMessage(1136)->getMessage(), array()); //INVALID_TYPE
                $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getTypeData] with response: ' . Utility::encodeData($resp_data));
                return $resp_data;
        }
    }
    
    
    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility'); //StoreManager\StoreBundle\Utils\UtilityService
    }
    
    /**
     * 
     * @return type
     */
    protected function getMasterDataService() {
        return $this->container->get('master_data.masterdata'); //StoreManager\StoreBundle\Utils\UtilityService
    }

    /**
     * Create subscription log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.postfeeds_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
    
    /**
     * Get master data
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function checkcountrycodeAction(Request $request) {
        $utilityService = $this->getUtilityService();
        $requiredParams = array('lang_code');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            $this->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getMasterDataAction] with response: ' . (string) $resp_data);
            Utility::createResponse($resp_data);
        }
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $language_code = Utility::getUpperCaseString($data['lang_code']);
        $masterdata_service = $this->container->get('master_data.masterdata');
        $valid_language_code = $masterdata_service->checkValidCountryCode($language_code);
        echo "<pre>";
        print_r($valid_language_code);
        exit;
    }
    
    /**
     * Get all Data
     * @param array $data
     * @return array
     */
    public function getAllData($data)
    {
       $masterdata_service = $this->getMasterDataService();
       $lang_code = $data['lang_code']; 
       //check for valid language
       $is_valid_lang = $masterdata_service->checkValidCountryCode($lang_code);
       if(!$is_valid_lang){
          $lang_code = $masterdata_service->getDefaultLanguageCode();
       }
       $masterdata_self_relation = $masterdata_service->getSelfRelationShipType($lang_code);
       $masterdata_family_relation = $masterdata_service->getFamilyRelationShipType($lang_code);
       $masterdata_education = $masterdata_service->getEducation($lang_code);
       $masterdata_legal_status = $masterdata_service->getLegalStatus($lang_code);
       $masterdata_region = $masterdata_service->getRegion($lang_code);
       $masterdata_country = $masterdata_service->getCountry($lang_code);
       $masterdata_privacy_setting = $masterdata_service->getPrivacySetting($lang_code);
       $masterdata_months = $masterdata_service->getMonths($lang_code);
       $masterdata_visibility = $masterdata_service->getVisibilityList($lang_code);
       $masterdata_language = $masterdata_service->getLanguageList($lang_code);
       $masterdata_category = $masterdata_service->getBusinessCategory($lang_code);
       $data = array(
           'selfRelationShipType'=>$masterdata_self_relation,
           'familyRelationShipType'=>$masterdata_family_relation,
           'education'=>$masterdata_education,
           'legalStatus'=>$masterdata_legal_status,
           'region'=>$masterdata_region,
           'country'=>$masterdata_country,
           'privacySetting'=>$masterdata_privacy_setting,
           'month'=>$masterdata_months,
           'visibility'=>$masterdata_visibility,
           'language'=>$masterdata_language,
           'businessCategory' => $masterdata_category
       );
       $resp_data = new Resp(Msg::getMessage(101)->getCode(), Msg::getMessage(101)->getMessage(), $data);
       $masterdata_service->__createLog('Exiting from class [Utility\MasterDataBundle\Controller] and function [getAllData] with response: ' . Utility::encodeData($resp_data));
       return $resp_data;
    }

}