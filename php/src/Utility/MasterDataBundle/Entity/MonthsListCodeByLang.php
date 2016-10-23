<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MonthsListCodeByLang
 */
class MonthsListCodeByLang
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $monthCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $monthName;


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
     * Set monthCode
     *
     * @param string $monthCode
     * @return MonthsListCodeByLang
     */
    public function setMonthCode($monthCode)
    {
        $this->monthCode = $monthCode;
    
        return $this;
    }

    /**
     * Get monthCode
     *
     * @return string 
     */
    public function getMonthCode()
    {
        return $this->monthCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return MonthsListCodeByLang
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
     * Set monthName
     *
     * @param string $monthName
     * @return MonthsListCodeByLang
     */
    public function setMonthName($monthName)
    {
        $this->monthName = $monthName;
    
        return $this;
    }

    /**
     * Get monthName
     *
     * @return string 
     */
    public function getMonthName()
    {
        return $this->monthName;
    }
}