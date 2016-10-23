<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CountryCodeByLang
 */
class CountryCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $countryString;


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
     * Set countryCode
     *
     * @param string $countryCode
     * @return CountryCodeByLang
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    
        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return CountryCodeByLang
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
     * Set countryString
     *
     * @param string $countryString
     * @return CountryCodeByLang
     */
    public function setCountryString($countryString)
    {
        $this->countryString = $countryString;
    
        return $this;
    }

    /**
     * Get countryString
     *
     * @return string 
     */
    public function getCountryString()
    {
        return $this->countryString;
    }
}