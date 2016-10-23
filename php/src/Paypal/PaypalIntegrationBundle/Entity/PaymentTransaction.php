<?php

namespace Paypal\PaypalIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentTransaction
 */
class PaymentTransaction
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $itemId;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var integer
     */
    private $citizenId;

    /**
     * @var integer
     */
    private $shopId;

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
     * @var float
     */
    private $transactionValue;

    /**
     * @var float
     */
    private $vatAmount;

    /**
     * @var string
     */
    private $contractId;

    /**
     * @var integer
     */
    private $paypalId;


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
     * Set itemId
     *
     * @param string $itemId
     * @return PaymentTransaction
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    
        return $this;
    }

    /**
     * Get itemId
     *
     * @return string 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return PaymentTransaction
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
     * Set citizenId
     *
     * @param integer $citizenId
     * @return PaymentTransaction
     */
    public function setCitizenId($citizenId)
    {
        $this->citizenId = $citizenId;
    
        return $this;
    }

    /**
     * Get citizenId
     *
     * @return integer 
     */
    public function getCitizenId()
    {
        return $this->citizenId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return PaymentTransaction
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    
        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer 
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set paymentVia
     *
     * @param string $paymentVia
     * @return PaymentTransaction
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
     * @return PaymentTransaction
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
     * @return PaymentTransaction
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
     * @return PaymentTransaction
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
     * @return PaymentTransaction
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
     * @return PaymentTransaction
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
     * @param float $transactionValue
     * @return PaymentTransaction
     */
    public function setTransactionValue($transactionValue)
    {
        $this->transactionValue = $transactionValue;
    
        return $this;
    }

    /**
     * Get transactionValue
     *
     * @return float 
     */
    public function getTransactionValue()
    {
        return $this->transactionValue;
    }

    /**
     * Set vatAmount
     *
     * @param float $vatAmount
     * @return PaymentTransaction
     */
    public function setVatAmount($vatAmount)
    {
        $this->vatAmount = $vatAmount;
    
        return $this;
    }

    /**
     * Get vatAmount
     *
     * @return float 
     */
    public function getVatAmount()
    {
        return $this->vatAmount;
    }

    /**
     * Set contractId
     *
     * @param string $contractId
     * @return PaymentTransaction
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
     * @param integer $paypalId
     * @return PaymentTransaction
     */
    public function setPaypalId($paypalId)
    {
        $this->paypalId = $paypalId;
    
        return $this;
    }

    /**
     * Get paypalId
     *
     * @return integer 
     */
    public function getPaypalId()
    {
        return $this->paypalId;
    }
    /**
     * @var string
     */
    private $transationId = '';


    /**
     * Set transationId
     *
     * @param string $transationId
     * @return PaymentTransaction
     */
    public function setTransationId($transationId)
    {
        $this->transationId = $transationId;
    
        return $this;
    }

    /**
     * Get transationId
     *
     * @return string 
     */
    public function getTransationId()
    {
        return $this->transationId;
    }
    /**
     * @var float
     */
    private $ciUsed = 0;


    /**
     * Set ciUsed
     *
     * @param float $ciUsed
     * @return PaymentTransaction
     */
    public function setCiUsed($ciUsed)
    {
        $this->ciUsed = $ciUsed;
    
        return $this;
    }

    /**
     * Get ciUsed
     *
     * @return float 
     */
    public function getCiUsed()
    {
        return $this->ciUsed;
    }
    /**
     * @var string
     */
    private $orderId = '';

    /**
     * @var string
     */
    private $ProductName = '';


    /**
     * Set orderId
     *
     * @param string $orderId
     * @return PaymentTransaction
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    
        return $this;
    }

    /**
     * Get orderId
     *
     * @return string 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set ProductName
     *
     * @param string $productName
     * @return PaymentTransaction
     */
    public function setProductName($productName)
    {
        $this->ProductName = $productName;
    
        return $this;
    }

    /**
     * Get ProductName
     *
     * @return string 
     */
    public function getProductName()
    {
        return $this->ProductName;
    }
}