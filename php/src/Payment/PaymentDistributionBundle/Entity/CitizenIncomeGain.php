<?php

namespace Payment\PaymentDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CitizenIncomeGain
 */
class CitizenIncomeGain
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $income;

    /**
     * @var \DateTime
     */
    private $date;


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
     * Set userId
     *
     * @param integer $userId
     * @return CitizenIncomeGain
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
     * Set income
     *
     * @param integer $income
     * @return CitizenIncomeGain
     */
    public function setIncome($income)
    {
        $this->income = $income;
    
        return $this;
    }

    /**
     * Get income
     *
     * @return integer 
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return CitizenIncomeGain
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
     * @var integer
     */
    private $shopId;


    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return CitizenIncomeGain
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
     * @var integer
     */
    private $transactionId;


    /**
     * Set transactionId
     *
     * @param integer $transactionId
     * @return CitizenIncomeGain
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return integer 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
    /**
     * @var integer
     */
    private $purchaserId;


    /**
     * Set purchaserId
     *
     * @param integer $purchaserId
     * @return CitizenIncomeGain
     */
    public function setPurchaserId($purchaserId)
    {
        $this->purchaserId = $purchaserId;
    
        return $this;
    }

    /**
     * Get purchaserId
     *
     * @return integer 
     */
    public function getPurchaserId()
    {
        return $this->purchaserId;
    }
    /**
     * @var integer
     */
    private $status;


    /**
     * Set status
     *
     * @param integer $status
     * @return CitizenIncomeGain
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
     * @var string
     */
    private $type;


    /**
     * Set type
     *
     * @param string $type
     * @return CitizenIncomeGain
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
}