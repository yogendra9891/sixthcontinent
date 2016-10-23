<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SelfRelationShipTypeCodeByLang
 */
class SelfRelationShipTypeCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $relationShipCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $relationShipName;


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
     * Set relationShipCode
     *
     * @param string $relationShipCode
     * @return SelfRelationShipTypeCodeByLang
     */
    public function setRelationShipCode($relationShipCode)
    {
        $this->relationShipCode = $relationShipCode;
    
        return $this;
    }

    /**
     * Get relationShipCode
     *
     * @return string 
     */
    public function getRelationShipCode()
    {
        return $this->relationShipCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return SelfRelationShipTypeCodeByLang
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
     * Set relationShipName
     *
     * @param string $relationShipName
     * @return SelfRelationShipTypeCodeByLang
     */
    public function setRelationShipName($relationShipName)
    {
        $this->relationShipName = $relationShipName;
    
        return $this;
    }

    /**
     * Get relationShipName
     *
     * @return string 
     */
    public function getRelationShipName()
    {
        return $this->relationShipName;
    }
}