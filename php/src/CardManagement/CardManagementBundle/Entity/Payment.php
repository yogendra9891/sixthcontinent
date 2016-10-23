<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payment
 */
class Payment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $contractId;

    /**
     * @var \DateTime
     */
    private $trasactionTime;

    /**
     * @var \DateTime
     */
    private $registrationTime;

    /**
     * @var string
     */
    private $description;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * @var string
     */
    private $mac;

    /**
     * @var string
     */
    private $trasactionCode;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var integer
     */
    private $status;


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
     * Set contractId
     *
     * @param integer $contractId
     * @return Payment
     */
    public function setContractId($contractId)
    {
        $this->contractId = $contractId;
    
        return $this;
    }

    /**
     * Get contractId
     *
     * @return integer 
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * Set trasactionTime
     *
     * @param \DateTime $trasactionTime
     * @return Payment
     */
    public function setTrasactionTime($trasactionTime)
    {
        $this->trasactionTime = $trasactionTime;
    
        return $this;
    }

    /**
     * Get trasactionTime
     *
     * @return \DateTime 
     */
    public function getTrasactionTime()
    {
        return $this->trasactionTime;
    }

    /**
     * Set registrationTime
     *
     * @param \DateTime $registrationTime
     * @return Payment
     */
    public function setRegistrationTime($registrationTime)
    {
        $this->registrationTime = $registrationTime;
    
        return $this;
    }

    /**
     * Get registrationTime
     *
     * @return \DateTime 
     */
    public function getRegistrationTime()
    {
        return $this->registrationTime;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Payment
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set amount
     *
     * @param float $amount
     * @return Payment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    
        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set currencyCode
     *
     * @param string $currencyCode
     * @return Payment
     */
    public function setCurrencyCode($currencyCode)
    {
        $this->currencyCode = $currencyCode;
    
        return $this;
    }

    /**
     * Get currencyCode
     *
     * @return string 
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return Payment
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
    
        return $this;
    }

    /**
     * Get mac
     *
     * @return string 
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * Set trasactionCode
     *
     * @param string $trasactionCode
     * @return Payment
     */
    public function setTrasactionCode($trasactionCode)
    {
        $this->trasactionCode = $trasactionCode;
    
        return $this;
    }

    /**
     * Get trasactionCode
     *
     * @return string 
     */
    public function getTrasactionCode()
    {
        return $this->trasactionCode;
    }

    /**
     * Set paymentType
     *
     * @param string $paymentType
     * @return Payment
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    
        return $this;
    }

    /**
     * Get paymentType
     *
     * @return string 
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Payment
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
}