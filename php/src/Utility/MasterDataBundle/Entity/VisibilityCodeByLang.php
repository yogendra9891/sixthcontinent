<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VisibilityCodeByLang
 */
class VisibilityCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $visiblityCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $visibilityName;


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
     * Set visiblityCode
     *
     * @param string $visiblityCode
     * @return VisibilityCodeByLang
     */
    public function setVisiblityCode($visiblityCode)
    {
        $this->visiblityCode = $visiblityCode;
    
        return $this;
    }

    /**
     * Get visiblityCode
     *
     * @return string 
     */
    public function getVisiblityCode()
    {
        return $this->visiblityCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return VisibilityCodeByLang
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
     * Set visibilityName
     *
     * @param string $visibilityName
     * @return VisibilityCodeByLang
     */
    public function setVisibilityName($visibilityName)
    {
        $this->visibilityName = $visibilityName;
    
        return $this;
    }

    /**
     * Get visibilityName
     *
     * @return string 
     */
    public function getVisibilityName()
    {
        return $this->visibilityName;
    }
}