<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopSubscription
 */
class ShopSubscription
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $subscriberId;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $expiryDate;

    /**
     * @var \DateTime
     */
    private $purchasedDate;

    /**
     * @var integer
     */
    private $shopOwnerId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var integer
     */
    private $subscriptionAmount;

    /**
     * @var \Date
     */
    private $intervalDate;
    
    /**
     * @var integer
     */
    private $contractId = 0;
    
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
     * Set subscriberId
     *
     * @param integer $subscriberId
     * @return ShopSubscription
     */
    public function setSubscriberId($subscriberId)
    {
        $this->subscriberId = $subscriberId;
    
        return $this;
    }

    /**
     * Get subscriberId
     *
     * @return integer 
     */
    public function getSubscriberId()
    {
        return $this->subscriberId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopSubscription
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
     * Set description
     *
     * @param string $description
     * @return ShopSubscription
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return ShopSubscription
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    
        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return ShopSubscription
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    
        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set purchasedDate
     *
     * @param \DateTime $purchasedDate
     * @return ShopSubscription
     */
    public function setPurchasedDate($purchasedDate)
    {
        $this->purchasedDate = $purchasedDate;
    
        return $this;
    }

    /**
     * Get purchasedDate
     *
     * @return \DateTime 
     */
    public function getPurchasedDate()
    {
        return $this->purchasedDate;
    }

    /**
     * Set shopOwnerId
     *
     * @param integer $shopOwnerId
     * @return ShopSubscription
     */
    public function setShopOwnerId($shopOwnerId)
    {
        $this->shopOwnerId = $shopOwnerId;
    
        return $this;
    }

    /**
     * Get shopOwnerId
     *
     * @return integer 
     */
    public function getShopOwnerId()
    {
        return $this->shopOwnerId;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return ShopSubscription
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return ShopSubscription
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

    /**
     * Set subscriptionAmount
     *
     * @param integer $subscriptionAmount
     * @return ShopSubscription
     */
    public function setSubscriptionAmount($subscriptionAmount)
    {
        $this->subscriptionAmount = $subscriptionAmount;
    
        return $this;
    }

    /**
     * Get subscriptionAmount
     *
     * @return integer 
     */
    public function getSubscriptionAmount()
    {
        return $this->subscriptionAmount;
    }
    
    /**
     * Set intervalDate
     *
     * @param \Date $intervalDate
     * @return intervalDate
     */
    public function setIntervalDate($intervalDate)
    {
        $this->intervalDate = $intervalDate;
    
        return $this;
    }

    /**
     * Get intervalDate
     *
     * @return \DateTime 
     */
    public function getIntervalDate()
    {
        return $this->intervalDate;
    }
    
    /**
     * Set contractId
     *
     * @param integer $contractId
     * @return ContractId
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
}
