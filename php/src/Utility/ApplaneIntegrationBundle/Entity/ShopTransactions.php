<?php

namespace Utility\ApplaneIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopTransactions
 */
class ShopTransactions
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var float
     */
    private $totalTransactionAmount;

    /**
     * @var float
     */
    private $payableAmount;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var string
     */
    private $type;

    /**
     * @var float
     */
    private $vat;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $invoiceId;

    /**
     * @var float
     */
    private $totalPayableAmount;

    /**
     * @var string
     */
    private $transactionId;


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
     * Set date
     *
     * @param \DateTime $date
     * @return ShopTransactions
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
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopTransactions
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
     * Set userId
     *
     * @param integer $userId
     * @return ShopTransactions
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
     * Set totalTransactionAmount
     *
     * @param float $totalTransactionAmount
     * @return ShopTransactions
     */
    public function setTotalTransactionAmount($totalTransactionAmount)
    {
        $this->totalTransactionAmount = $totalTransactionAmount;
    
        return $this;
    }

    /**
     * Get totalTransactionAmount
     *
     * @return float 
     */
    public function getTotalTransactionAmount()
    {
        return $this->totalTransactionAmount;
    }

    /**
     * Set payableAmount
     *
     * @param float $payableAmount
     * @return ShopTransactions
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payableAmount = $payableAmount;
    
        return $this;
    }

    /**
     * Get payableAmount
     *
     * @return float 
     */
    public function getPayableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ShopTransactions
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
     * Set type
     *
     * @param string $type
     * @return ShopTransactions
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set vat
     *
     * @param float $vat
     * @return ShopTransactions
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ShopTransactions
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set invoiceId
     *
     * @param string $invoiceId
     * @return ShopTransactions
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    
        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return string 
     */
    public function getInvoiceId()
    {
        return $this->invoiceId;
    }

    /**
     * Set totalPayableAmount
     *
     * @param float $totalPayableAmount
     * @return ShopTransactions
     */
    public function setTotalPayableAmount($totalPayableAmount)
    {
        $this->totalPayableAmount = $totalPayableAmount;
    
        return $this;
    }

    /**
     * Get totalPayableAmount
     *
     * @return float 
     */
    public function getTotalPayableAmount()
    {
        return $this->totalPayableAmount;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return ShopTransactions
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
}