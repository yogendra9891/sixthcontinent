<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageCodeByLang
 */
class LanguageCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $languageNameCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $languageString;


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
     * Set languageNameCode
     *
     * @param string $languageNameCode
     * @return LanguageCodeByLang
     */
    public function setLanguageNameCode($languageNameCode)
    {
        $this->languageNameCode = $languageNameCode;
    
        return $this;
    }

    /**
     * Get languageNameCode
     *
     * @return string 
     */
    public function getLanguageNameCode()
    {
        return $this->languageNameCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return LanguageCodeByLang
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
     * Set languageString
     *
     * @param string $languageString
     * @return LanguageCodeByLang
     */
    public function setLanguageString($languageString)
    {
        $this->languageString = $languageString;
    
        return $this;
    }

    /**
     * Get languageString
     *
     * @return string 
     */
    public function getLanguageString()
    {
        return $this->languageString;
    }
}