<?php

namespace Payment\PaymentDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CitizenIncomeGainLog
 */
class CitizenIncomeGainLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $createdAt;


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
     * Set transactionId
     *
     * @param string $transactionId
     * @return CitizenIncomeGainLog
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
     * Set status
     *
     * @param integer $status
     * @return CitizenIncomeGainLog
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CitizenIncomeGainLog
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
     * @var \DateTime
     */
    private $updatedAt;


    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return CitizenIncomeGainLog
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    /**
     * @var integer
     */
    private $citizenAffiliateAmount;

    /**
     * @var integer
     */
    private $shopAffiliateAmount;

    /**
     * @var integer
     */
    private $friendsFollowerAmount;

    /**
     * @var integer
     */
    private $purchaserUserAmount;

    /**
     * @var integer
     */
    private $countryCitizenAmount;

    /**
     * @var integer
     */
    private $sixthcontinentAmount;

    /**
     * @var integer
     */
    private $totalAmount;


    /**
     * Set citizenAffiliateAmount
     *
     * @param integer $citizenAffiliateAmount
     * @return CitizenIncomeGainLog
     */
    public function setCitizenAffiliateAmount($citizenAffiliateAmount)
    {
        $this->citizenAffiliateAmount = $citizenAffiliateAmount;
    
        return $this;
    }

    /**
     * Get citizenAffiliateAmount
     *
     * @return integer 
     */
    public function getCitizenAffiliateAmount()
    {
        return $this->citizenAffiliateAmount;
    }

    /**
     * Set shopAffiliateAmount
     *
     * @param integer $shopAffiliateAmount
     * @return CitizenIncomeGainLog
     */
    public function setShopAffiliateAmount($shopAffiliateAmount)
    {
        $this->shopAffiliateAmount = $shopAffiliateAmount;
    
        return $this;
    }

    /**
     * Get shopAffiliateAmount
     *
     * @return integer 
     */
    public function getShopAffiliateAmount()
    {
        return $this->shopAffiliateAmount;
    }

    /**
     * Set friendsFollowerAmount
     *
     * @param integer $friendsFollowerAmount
     * @return CitizenIncomeGainLog
     */
    public function setFriendsFollowerAmount($friendsFollowerAmount)
    {
        $this->friendsFollowerAmount = $friendsFollowerAmount;
    
        return $this;
    }

    /**
     * Get friendsFollowerAmount
     *
     * @return integer 
     */
    public function getFriendsFollowerAmount()
    {
        return $this->friendsFollowerAmount;
    }

    /**
     * Set purchaserUserAmount
     *
     * @param integer $purchaserUserAmount
     * @return CitizenIncomeGainLog
     */
    public function setPurchaserUserAmount($purchaserUserAmount)
    {
        $this->purchaserUserAmount = $purchaserUserAmount;
    
        return $this;
    }

    /**
     * Get purchaserUserAmount
     *
     * @return integer 
     */
    public function getPurchaserUserAmount()
    {
        return $this->purchaserUserAmount;
    }

    /**
     * Set countryCitizenAmount
     *
     * @param integer $countryCitizenAmount
     * @return CitizenIncomeGainLog
     */
    public function setCountryCitizenAmount($countryCitizenAmount)
    {
        $this->countryCitizenAmount = $countryCitizenAmount;
    
        return $this;
    }

    /**
     * Get countryCitizenAmount
     *
     * @return integer 
     */
    public function getCountryCitizenAmount()
    {
        return $this->countryCitizenAmount;
    }

    /**
     * Set sixthcontinentAmount
     *
     * @param integer $sixthcontinentAmount
     * @return CitizenIncomeGainLog
     */
    public function setSixthcontinentAmount($sixthcontinentAmount)
    {
        $this->sixthcontinentAmount = $sixthcontinentAmount;
    
        return $this;
    }

    /**
     * Get sixthcontinentAmount
     *
     * @return integer 
     */
    public function getSixthcontinentAmount()
    {
        return $this->sixthcontinentAmount;
    }

    /**
     * Set totalAmount
     *
     * @param integer $totalAmount
     * @return CitizenIncomeGainLog
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    
        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return integer 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }
    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $shopId;


    /**
     * Set userId
     *
     * @param integer $userId
     * @return CitizenIncomeGainLog
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
     * @return CitizenIncomeGainLog
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
    private $couponAmount;

    /**
     * @var integer
     */
    private $discountPositionAmount;


    /**
     * Set couponAmount
     *
     * @param integer $couponAmount
     * @return CitizenIncomeGainLog
     */
    public function setCouponAmount($couponAmount)
    {
        $this->couponAmount = $couponAmount;
    
        return $this;
    }

    /**
     * Get couponAmount
     *
     * @return integer 
     */
    public function getCouponAmount()
    {
        return $this->couponAmount;
    }

    /**
     * Set discountPositionAmount
     *
     * @param integer $discountPositionAmount
     * @return CitizenIncomeGainLog
     */
    public function setDiscountPositionAmount($discountPositionAmount)
    {
        $this->discountPositionAmount = $discountPositionAmount;
    
        return $this;
    }

    /**
     * Get discountPositionAmount
     *
     * @return integer 
     */
    public function getDiscountPositionAmount()
    {
        return $this->discountPositionAmount;
    }
    /**
     * @var integer
     */
    private $distributedAmount;


    /**
     * Set distributedAmount
     *
     * @param integer $distributedAmount
     * @return CitizenIncomeGainLog
     */
    public function setDistributedAmount($distributedAmount)
    {
        $this->distributedAmount = $distributedAmount;
    
        return $this;
    }

    /**
     * Get distributedAmount
     *
     * @return integer 
     */
    public function getDistributedAmount()
    {
        return $this->distributedAmount;
    }
    /**
     * @var integer
     */
    private $citizenCount = 0;

    /**
     * @var integer
     */
    private $cronStatus = 0;


    /**
     * Set citizenCount
     *
     * @param integer $citizenCount
     * @return CitizenIncomeGainLog
     */
    public function setCitizenCount($citizenCount)
    {
        $this->citizenCount = $citizenCount;
    
        return $this;
    }

    /**
     * Get citizenCount
     *
     * @return integer 
     */
    public function getCitizenCount()
    {
        return $this->citizenCount;
    }

    /**
     * Set cronStatus
     *
     * @param integer $cronStatus
     * @return CitizenIncomeGainLog
     */
    public function setCronStatus($cronStatus)
    {
        $this->cronStatus = $cronStatus;
    
        return $this;
    }

    /**
     * Get cronStatus
     *
     * @return integer 
     */
    public function getCronStatus()
    {
        return $this->cronStatus;
    }
    /**
     * @var \DateTime
     */
    private $approvedAt;


    /**
     * Set approvedAt
     *
     * @param \DateTime $approvedAt
     * @return CitizenIncomeGainLog
     */
    public function setApprovedAt($approvedAt)
    {
        $this->approvedAt = $approvedAt;
    
        return $this;
    }

    /**
     * Get approvedAt
     *
     * @return \DateTime 
     */
    public function getApprovedAt()
    {
        return $this->approvedAt;
    }
    /**
     * @var integer
     */
    private $jobStatus = 0;


    /**
     * Set jobStatus
     *
     * @param integer $jobStatus
     * @return CitizenIncomeGainLog
     */
    public function setJobStatus($jobStatus)
    {
        $this->jobStatus = $jobStatus;
    
        return $this;
    }

    /**
     * Get jobStatus
     *
     * @return integer 
     */
    public function getJobStatus()
    {
        return $this->jobStatus;
    }
}