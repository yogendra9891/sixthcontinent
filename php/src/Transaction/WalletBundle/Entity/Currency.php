<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Currency
 */
class Currency
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $isBaseCurrency;

    /**
     * @var string
     */
    private $currencyLabel;

    /**
     * @var float
     */
    private $baseToCurrencyRatio;

    /**
     * @var \DateTime
     */
    private $timeUpdatedH;

    /**
     * @var integer
     */
    private $timeUpdated;


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
     * Set isBaseCurrency
     *
     * @param integer $isBaseCurrency
     * @return Currency
     */
    public function setIsBaseCurrency($isBaseCurrency)
    {
        $this->isBaseCurrency = $isBaseCurrency;
    
        return $this;
    }

    /**
     * Get isBaseCurrency
     *
     * @return integer 
     */
    public function getIsBaseCurrency()
    {
        return $this->isBaseCurrency;
    }

    /**
     * Set currencyLabel
     *
     * @param string $currencyLabel
     * @return Currency
     */
    public function setCurrencyLabel($currencyLabel)
    {
        $this->currencyLabel = $currencyLabel;
    
        return $this;
    }

    /**
     * Get currencyLabel
     *
     * @return string 
     */
    public function getCurrencyLabel()
    {
        return $this->currencyLabel;
    }

    /**
     * Set baseToCurrencyRatio
     *
     * @param float $baseToCurrencyRatio
     * @return Currency
     */
    public function setBaseToCurrencyRatio($baseToCurrencyRatio)
    {
        $this->baseToCurrencyRatio = $baseToCurrencyRatio;
    
        return $this;
    }

    /**
     * Get baseToCurrencyRatio
     *
     * @return float 
     */
    public function getBaseToCurrencyRatio()
    {
        return $this->baseToCurrencyRatio;
    }

    /**
     * Set timeUpdatedH
     *
     * @param \DateTime $timeUpdatedH
     * @return Currency
     */
    public function setTimeUpdatedH($timeUpdatedH)
    {
        $this->timeUpdatedH = $timeUpdatedH;
    
        return $this;
    }

    /**
     * Get timeUpdatedH
     *
     * @return \DateTime 
     */
    public function getTimeUpdatedH()
    {
        return $this->timeUpdatedH;
    }

    /**
     * Set timeUpdated
     *
     * @param integer $timeUpdated
     * @return Currency
     */
    public function setTimeUpdated($timeUpdated)
    {
        $this->timeUpdated = $timeUpdated;
    
        return $this;
    }

    /**
     * Get timeUpdated
     *
     * @return integer 
     */
    public function getTimeUpdated()
    {
        return $this->timeUpdated;
    }
}