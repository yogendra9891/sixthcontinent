<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PrivacySettingCodeByLang
 */
class PrivacySettingCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $privacySettingCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $privacySettingName;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set privacySettingCode
     *
     * @param string $privacySettingCode
     * @return PrivacySettingCodeByLang
     */
    public function setPrivacySettingCode($privacySettingCode)
    {
        $this->privacySettingCode = $privacySettingCode;
    
        return $this;
    }

    /**
     * Get privacySettingCode
     *
     * @return string 
     */
    public function getPrivacySettingCode()
    {
        return $this->privacySettingCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return PrivacySettingCodeByLang
     */
    public function setLangCode($langCode)
    {
        $this->langCode = $langCode;
    
        return $this;
    }

    /**
     * Get langCode
     *
     * @return string 
     */
    public function getLangCode()
    {
        return $this->langCode;
    }

    /**
     * Set privacySettingName
     *
     * @param string $privacySettingName
     * @return PrivacySettingCodeByLang
     */
    public function setPrivacySettingName($privacySettingName)
    {
        $this->privacySettingName = $privacySettingName;
    
        return $this;
    }

    /**
     * Get privacySettingName
     *
     * @return string 
     */
    public function getPrivacySettingName()
    {
        return $this->privacySettingName;
    }
}