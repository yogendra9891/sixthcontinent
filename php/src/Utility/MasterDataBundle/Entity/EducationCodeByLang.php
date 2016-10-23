<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EducationCodeByLang
 */
class EducationCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $educationCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $educationName;


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
     * Set educationCode
     *
     * @param string $educationCode
     * @return EducationCodeByLang
     */
    public function setEducationCode($educationCode)
    {
        $this->educationCode = $educationCode;
    
        return $this;
    }

    /**
     * Get educationCode
     *
     * @return string 
     */
    public function getEducationCode()
    {
        return $this->educationCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return EducationCodeByLang
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
     * Set educationName
     *
     * @param string $educationName
     * @return EducationCodeByLang
     */
    public function setEducationName($educationName)
    {
        $this->educationName = $educationName;
    
        return $this;
    }

    /**
     * Get educationName
     *
     * @return string 
     */
    public function getEducationName()
    {
        return $this->educationName;
    }
}