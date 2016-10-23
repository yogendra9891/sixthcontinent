<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RegionCodeByLang
 */
class RegionCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $regionCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $regionName;


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
     * Set regionCode
     *
     * @param string $regionCode
     * @return RegionCodeByLang
     */
    public function setRegionCode($regionCode)
    {
        $this->regionCode = $regionCode;
    
        return $this;
    }

    /**
     * Get regionCode
     *
     * @return string 
     */
    public function getRegionCode()
    {
        return $this->regionCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return RegionCodeByLang
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
     * Set regionName
     *
     * @param string $regionName
     * @return RegionCodeByLang
     */
    public function setRegionName($regionName)
    {
        $this->regionName = $regionName;
    
        return $this;
    }

    /**
     * Get regionName
     *
     * @return string 
     */
    public function getRegionName()
    {
        return $this->regionName;
    }
}