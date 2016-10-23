<?php

namespace Utility\MasterDataBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\PostFeeds;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Document\TaggingFeeds;
use PostFeeds\PostFeedsBundle\Document\ShopTagFeeds;
use PostFeeds\PostFeedsBundle\Document\ClubTagFeeds;
use PostFeeds\PostFeedsBundle\Document\UserTagFeeds;
use PostFeeds\PostFeedsBundle\Document\CommentFeeds;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class MasterDataService {

    protected $em;
    protected $dm;
    protected $container;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }

    /**
     * Get Self RelationShip Type
     * @param type string
     * @param type array
     */
    public function getSelfRelationShipType($lang_code) {
     $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getSelfRelationShipType] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:SelfRelationShipType')
                ->getSelfRelationList($lang_code, 0);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:SelfRelationShipType')
                ->getSelfRelationList($lang_code, 0);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
     }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getSelfRelationShipType] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get Family RelationShip Type
     * @param type string
     * @param type array
     */
    public function getFamilyRelationShipType($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getFamilyRelationShipType] with languagecode :'.$lang_code, array());
      try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:FamilyRelationShipType')
                ->getFamilyRelationList($lang_code, 0);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:FamilyRelationShipType')
                ->getFamilyRelationList($lang_code, 0);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getFamilyRelationShipType] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    
    /**
     * Get Education list
     * @param type string
     * @param type array
     */
    public function getEducation($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getEducation] with languagecode :'.$lang_code, array());
      try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:Education')
                ->getEducationList($lang_code, 0);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:Education')
                ->getEducationList($lang_code, 0);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getEducation] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get LegalStatus
     * @param type string
     * @param type array
     */
    public function getLegalStatus($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getLegalStatus] with languagecode :'.$lang_code, array());
      try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:LegalStatus')
                ->getLegalStatusList($lang_code, 0);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:LegalStatus')
                ->getLegalStatusList($lang_code, 0);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getLegalStatus] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get Region
     * @param type string
     * @param type array
     */
    public function getRegion($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getRegion] with languagecode :'.$lang_code, array());
      try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:Region')
                ->getRegionList($lang_code, 0);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:Region')
                ->getRegionList($lang_code, 0);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getRegion] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get Region
     * @param type string
     * @param type array
     */
    public function getCountry($lang_code) {
     $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getCountry] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:CountryCode')
                ->getCountryCodeList($lang_code);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:CountryCode')
                ->getCountryCodeList($lang_code);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getCountry] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get default language code
     * @return string
     */
    public function getDefaultLanguageCode()
    {
      try{
        $lang_code = $this->container->getParameter('default_language_code');
      }catch(\Exception $e){
        $lang_code  = 'IT';
      } 
      return $lang_code;
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
     *  function for checking the country or language code
     * @param type $country_code
     * @return boolean
     */
    public function checkValidCountryCode($country_code) {
        try {
            $language_code = Utility::getUpperCaseString(Utility::getTrimmedString($country_code));
            $em = $this->em;
            $result = $em
                    ->getRepository('MasterDataBundle:CountryCode')
                    ->getRecordByCountryCode($language_code);
            if (count($result) > 0) {
                return true;
            }
            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    
    /**
     *  function for checking the country or language code and return the valid language code
     * @param type $country_code
     * @return boolean
     */
    public function checkValidLanguageCode($country_code) {
        try {
            $language_code = Utility::getUpperCaseString(Utility::getTrimmedString($country_code));
            $valid_language_code = $this->container->getParameter('default_language_code');
            $em = $this->em;
            $result = $em
                    ->getRepository('MasterDataBundle:CountryCode')
                    ->getRecordByCountryCode($language_code);
            if (count($result) > 0) {
                $country_data = $result[0];
                $valid_language_code = $country_data->getLanguageCode();
            }
            return $valid_language_code;
        } catch (\Exception $ex) {
            return $this->container->getParameter('default_language_code');
        }
    }
    
    /**
     * Get Privacy Setting
     * @param type string
     * @param type array
     */
    public function getPrivacySetting($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getPrivacySetting] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:PrivacySetting')
                ->getPrivacySettingList($lang_code);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:PrivacySetting')
                ->getPrivacySettingList($lang_code);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getPrivacySetting] with exception :'.$e->getMessage(), array());
         return array();
     }
    }

    /**
     *  function for getting the business category master data
     * @param type $lang_code
     */
    public function getBusinessCategory($lang_code) {
        try {
            $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getBusinessCategory] with languagecode :' . $lang_code, array());
            $em = $this->em;
            $searchData = array();
            $searchData = $em
                    ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                    ->getBusinessCategory($lang_code);
            if (count($searchData)) {
                $data = $this->prepareData($searchData,'category_name');
                return $data;
            }
            //get for default language setting
            $lang_code = $this->getDefaultLanguageCode();
            $searchData = $em
                    ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                    ->getBusinessCategory($lang_code);
            
            if (count($searchData)) {
                $data = $this->prepareData($searchData,'category_name');
                return $data;
            }
            
            return $searchData;
           
        } catch (\Exception $ex) {
             $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getBusinessCategory] with exception :'.$ex->getMessage(), array());
             return array();
        }
        
    }

    
    protected $final_cat = array();

    public function prepareData($searchData, $master_key) {
        try {
            $prepared_data = array();
            foreach ($searchData as $data) {
                if (isset($data['parent']) && $data['parent'] == 0) {
                    $this->final_cat[$data['id']] = array('id' => $data['id'], $master_key => $data[$master_key], 'image' => $data['image'], 'image_thumb' => $data['image_thumb']);
                    $final_data = $this->parseRequireData($searchData, $data, $master_key);
                }
            }

            return array_values($final_data);
        } catch (\Exception $ex) {
            return array();
        }
    }

    public function parseRequireData($searchData, $data, $master_key) {
        try {
            foreach ($searchData as $newdata) {
                if ($newdata['parent'] == $data['id']) {
                    unset($newdata['parent']);
                    $this->final_cat[$data['id']]['subcategory'][] = $newdata;
                }
            }

            return $this->final_cat;
        } catch (\Exception $ex) {
            return array();
        }
    }

    /**
     * Get Month List
     * @param type string
     * @param type array
     */
    public function getMonths($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getMonths] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:MonthsList')
                ->getMonthList($lang_code);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:MonthsList')
                ->getMonthList($lang_code);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getPrivacySetting] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get Visibility List
     * @param type string
     * @param type array
     */
    public function getVisibilityList($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getVisibilityList] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:VisibilityCode')
                ->getVisibilityList($lang_code);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:VisibilityCode')
                ->getVisibilityList($lang_code);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getVisibilityList] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
    
    /**
     * Get Visibility List
     * @param type string
     * @param type array
     */
    public function getLanguageList($lang_code) {
      $this->__createLog('Entering in class [Utility\MasterDataBundle\Services] and function [getLanguageList] with languagecode :'.$lang_code, array());
     try{
      $em = $this->em;
      $searchData = array();
      $searchData = $em
                ->getRepository('MasterDataBundle:LanguageCode')
                ->getLanguageList($lang_code);
      if(count($searchData)){
          return $searchData;
      }
      //get for default language setting
      $lang_code = $this->getDefaultLanguageCode();
      $searchData = $em
                ->getRepository('MasterDataBundle:LanguageCode')
                ->getLanguageList($lang_code);
       if(count($searchData)){
          return $searchData;
      }
      return $searchData;
       }catch(\Exception $e){
         $this->__createLog('Exiting from class [Utility\MasterDataBundle\Services] and function [getLanguageList] with exception :'.$e->getMessage(), array());
         return array();
     }
    }
}