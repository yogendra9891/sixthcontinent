<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FamilyRelationShipTypeCodeByLang
 */
class FamilyRelationShipTypeCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $relationshipCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $relationshipName;


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
     * Set relationshipCode
     *
     * @param string $relationshipCode
     * @return FamilyRelationShipTypeCodeByLang
     */
    public function setRelationshipCode($relationshipCode)
    {
        $this->relationshipCode = $relationshipCode;
    
        return $this;
    }

    /**
     * Get relationshipCode
     *
     * @return string 
     */
    public function getRelationshipCode()
    {
        return $this->relationshipCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return FamilyRelationShipTypeCodeByLang
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
     * Set relationshipName
     *
     * @param string $relationshipName
     * @return FamilyRelationShipTypeCodeByLang
     */
    public function setRelationshipName($relationshipName)
    {
        $this->relationshipName = $relationshipName;
    
        return $this;
    }

    /**
     * Get relationshipName
     *
     * @return string 
     */
    public function getRelationshipName()
    {
        return $this->relationshipName;
    }
}