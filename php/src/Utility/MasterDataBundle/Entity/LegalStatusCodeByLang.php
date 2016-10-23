<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LegalStatusCodeByLang
 */
class LegalStatusCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $legalstatusCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $legalstatusName;


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
     * Set legalstatusCode
     *
     * @param string $legalstatusCode
     * @return LegalStatusCodeByLang
     */
    public function setLegalstatusCode($legalstatusCode)
    {
        $this->legalstatusCode = $legalstatusCode;
    
        return $this;
    }

    /**
     * Get legalstatusCode
     *
     * @return string 
     */
    public function getLegalstatusCode()
    {
        return $this->legalstatusCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return LegalStatusCodeByLang
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
     * Set legalstatusName
     *
     * @param string $legalstatusName
     * @return LegalStatusCodeByLang
     */
    public function setLegalstatusName($legalstatusName)
    {
        $this->legalstatusName = $legalstatusName;
    
        return $this;
    }

    /**
     * Get legalstatusName
     *
     * @return string 
     */
    public function getLegalstatusName()
    {
        return $this->legalstatusName;
    }
}