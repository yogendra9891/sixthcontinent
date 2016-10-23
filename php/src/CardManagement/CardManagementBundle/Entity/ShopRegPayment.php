<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopRegPayment
 */
class ShopRegPayment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var \DateTime
     */
    private $created_at;

    /**
     * @var string
     */
    private $description;

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
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopRegPayment
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
     * Set amount
     *
     * @param float $amount
     * @return ShopRegPayment
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return ShopRegPayment
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ShopRegPayment
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
     * Set status
     *
     * @param integer $status
     * @return ShopRegPayment
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
    /**
     * @var float
     */
    private $regFee;

    /**
     * @var string
     */
    private $transactionType;


    /**
     * Set regFee
     *
     * @param float $regFee
     * @return ShopRegPayment
     */
    public function setRegFee($regFee)
    {
        $this->regFee = $regFee;
    
        return $this;
    }

    /**
     * Get regFee
     *
     * @return float 
     */
    public function getRegFee()
    {
        return $this->regFee;
    }

    /**
     * Set transactionType
     *
     * @param string $transactionType
     * @return ShopRegPayment
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
    
        return $this;
    }

    /**
     * Get transactionType
     *
     * @return string 
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }
    /**
     * @var float
     */
    private $vat;

    /**
     * @var float
     */
    private $pendingAmount;


    /**
     * Set vat
     *
     * @param float $vat
     * @return ShopRegPayment
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    
        return $this;
    }

    /**
     * Get vat
     *
     * @return float 
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set pendingAmount
     *
     * @param float $pendingAmount
     * @return ShopRegPayment
     */
    public function setPendingAmount($pendingAmount)
    {
        $this->pendingAmount = $pendingAmount;
    
        return $this;
    }

    /**
     * Get pendingAmount
     *
     * @return float 
     */
    public function getPendingAmount()
    {
        return $this->pendingAmount;
    }
    /**
     * @var integer
     */
    private $contractId;

    /**
     * @var integer
     */
    private $paymentId;


    /**
     * Set contractId
     *
     * @param integer $contractId
     * @return ShopRegPayment
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
     * Set paymentId
     *
     * @param integer $paymentId
     * @return ShopRegPayment
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    
        return $this;
    }

    /**
     * Get paymentId
     *
     * @return integer 
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }
    /**
     * @var string
     */
    private $method;


    /**
     * Set method
     *
     * @param string $method
     * @return ShopRegPayment
     */
    public function setMethod($method)
    {
        $this->method = $method;
    
        return $this;
    }

    /**
     * Get method
     *
     * @return string 
     */
    public function getMethod()
    {
        return $this->method;
    }
    /**
     * @var string
     */
    private $transactionCode;

    /**
     * @var integer
     */
    private $recurringPaymentId;


    /**
     * Set transactionCode
     *
     * @param string $transactionCode
     * @return ShopRegPayment
     */
    public function setTransactionCode($transactionCode)
    {
        $this->transactionCode = $transactionCode;
    
        return $this;
    }

    /**
     * Get transactionCode
     *
     * @return string 
     */
    public function getTransactionCode()
    {
        return $this->transactionCode;
    }

    /**
     * Set recurringPaymentId
     *
     * @param integer $recurringPaymentId
     * @return ShopRegPayment
     */
    public function setRecurringPaymentId($recurringPaymentId)
    {
        $this->recurringPaymentId = $recurringPaymentId;
    
        return $this;
    }

    /**
     * Get recurringPaymentId
     *
     * @return integer 
     */
    public function getRecurringPaymentId()
    {
        return $this->recurringPaymentId;
    }
    /**
     * @var string
     */
    private $transactionShopId;


    /**
     * Set transactionShopId
     *
     * @param string $transactionShopId
     * @return ShopRegPayment
     */
    public function setTransactionShopId($transactionShopId)
    {
        $this->transactionShopId = $transactionShopId;
    
        return $this;
    }

    /**
     * Get transactionShopId
     *
     * @return string 
     */
    public function getTransactionShopId()
    {
        return $this->transactionShopId;
    }
    /**
     * @var integer
     */
    private $pendingAmountVat = 0; 


    /**
     * Set pendingAmountVat
     *
     * @param integer $pendingAmountVat
     * @return ShopRegPayment
     */
    public function setPendingAmountVat($pendingAmountVat)
    {
        $this->pendingAmountVat = $pendingAmountVat;
    
        return $this;
    }

    /**
     * Get pendingAmountVat
     *
     * @return integer 
     */
    public function getPendingAmountVat()
    {
        return $this->pendingAmountVat;
    }
}