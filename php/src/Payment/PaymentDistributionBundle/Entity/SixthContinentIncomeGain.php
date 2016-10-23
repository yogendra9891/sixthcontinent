<?php

namespace Payment\PaymentDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SixthContinentIncomeGain
 */
class SixthContinentIncomeGain
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
    private $shopId;

    /**
     * @var integer
     */
    private $transactionId;

    /**
     * @var integer
     */
    private $amount;

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
     * @return SixthContinentIncomeGain
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
     * Set shopId
     *
     * @param integer $shopId
     * @return SixthContinentIncomeGain
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
     * Set transactionId
     *
     * @param integer $transactionId
     * @return SixthContinentIncomeGain
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
     * Set amount
     *
     * @param integer $amount
     * @return SixthContinentIncomeGain
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    
        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return SixthContinentIncomeGain
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
    private $Income;


    /**
     * Set Income
     *
     * @param integer $income
     * @return SixthContinentIncomeGain
     */
    public function setIncome($income)
    {
        $this->Income = $income;
    
        return $this;
    }

    /**
     * Get Income
     *
     * @return integer 
     */
    public function getIncome()
    {
        return $this->Income;
    }
    /**
     * @var integer
     */
    private $purchaserId;


    /**
     * Set purchaserId
     *
     * @param integer $purchaserId
     * @return SixthContinentIncomeGain
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
    private $isDistributed;


    /**
     * Set isDistributed
     *
     * @param integer $isDistributed
     * @return SixthContinentIncomeGain
     */
    public function setIsDistributed($isDistributed)
    {
        $this->isDistributed = $isDistributed;
    
        return $this;
    }

    /**
     * Get isDistributed
     *
     * @return integer 
     */
    public function getIsDistributed()
    {
        return $this->isDistributed;
    }
}