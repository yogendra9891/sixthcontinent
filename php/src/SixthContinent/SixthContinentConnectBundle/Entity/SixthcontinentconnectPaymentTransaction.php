<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SixthcontinentconnectPaymentTransaction
 */
class SixthcontinentconnectPaymentTransaction
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $sixthcontinentConnectId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $paymentVia;

    /**
     * @var string
     */
    private $paymentStatus;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $errorDescription;

    /**
     * @var string
     */
    private $transactionReference;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var integer
     */
    private $transactionValue;

    /**
     * @var integer
     */
    private $vatAmount;

    /**
     * @var string
     */
    private $contractId;

    /**
     * @var string
     */
    private $paypalId;

    /**
     * @var string
     */
    private $ciTransactionId;

    /**
     * @var integer
     */
    private $ciUsed;


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
     * Set sixthcontinentConnectId
     *
     * @param integer $sixthcontinentConnectId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setSixthcontinentConnectId($sixthcontinentConnectId)
    {
        $this->sixthcontinentConnectId = $sixthcontinentConnectId;
    
        return $this;
    }

    /**
     * Get sixthcontinentConnectId
     *
     * @return integer 
     */
    public function getSixthcontinentConnectId()
    {
        return $this->sixthcontinentConnectId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    
        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set appId
     *
     * @param string $appId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    
        return $this;
    }

    /**
     * Get appId
     *
     * @return string 
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set paymentVia
     *
     * @param string $paymentVia
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setPaymentVia($paymentVia)
    {
        $this->paymentVia = $paymentVia;
    
        return $this;
    }

    /**
     * Get paymentVia
     *
     * @return string 
     */
    public function getPaymentVia()
    {
        return $this->paymentVia;
    }

    /**
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    
        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return string 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    
        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string 
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorDescription
     *
     * @param string $errorDescription
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
    
        return $this;
    }

    /**
     * Get errorDescription
     *
     * @return string 
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * Set transactionReference
     *
     * @param string $transactionReference
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setTransactionReference($transactionReference)
    {
        $this->transactionReference = $transactionReference;
    
        return $this;
    }

    /**
     * Get transactionReference
     *
     * @return string 
     */
    public function getTransactionReference()
    {
        return $this->transactionReference;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set transactionValue
     *
     * @param integer $transactionValue
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setTransactionValue($transactionValue)
    {
        $this->transactionValue = $transactionValue;
    
        return $this;
    }

    /**
     * Get transactionValue
     *
     * @return integer 
     */
    public function getTransactionValue()
    {
        return $this->transactionValue;
    }

    /**
     * Set vatAmount
     *
     * @param integer $vatAmount
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setVatAmount($vatAmount)
    {
        $this->vatAmount = $vatAmount;
    
        return $this;
    }

    /**
     * Get vatAmount
     *
     * @return integer 
     */
    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    /**
     * Set contractId
     *
     * @param string $contractId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setContractId($contractId)
    {
        $this->contractId = $contractId;
    
        return $this;
    }

    /**
     * Get contractId
     *
     * @return string 
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * Set paypalId
     *
     * @param string $paypalId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setPaypalId($paypalId)
    {
        $this->paypalId = $paypalId;
    
        return $this;
    }

    /**
     * Get paypalId
     *
     * @return string 
     */
    public function getPaypalId()
    {
        return $this->paypalId;
    }

    /**
     * Set ciTransactionId
     *
     * @param string $ciTransactionId
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setCiTransactionId($ciTransactionId)
    {
        $this->ciTransactionId = $ciTransactionId;
    
        return $this;
    }

    /**
     * Get ciTransactionId
     *
     * @return string 
     */
    public function getCiTransactionId()
    {
        return $this->ciTransactionId;
    }

    /**
     * Set ciUsed
     *
     * @param integer $ciUsed
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setCiUsed($ciUsed)
    {
        $this->ciUsed = $ciUsed;
    
        return $this;
    }

    /**
     * Get ciUsed
     *
     * @return integer 
     */
    public function getCiUsed()
    {
        return $this->ciUsed;
    }
    /**
     * @var string
     */
    private $timeStamp;


    /**
     * Set timeStamp
     *
     * @param string $timeStamp
     * @return SixthcontinentconnectPaymentTransaction
     */
    public function setTimeStamp($timeStamp)
    {
        $this->timeStamp = $timeStamp;
    
        return $this;
    }

    /**
     * Get timeStamp
     *
     * @return string 
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }
}