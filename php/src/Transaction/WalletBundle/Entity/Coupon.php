<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Coupon
 */
class Coupon
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $couponId;

    /**
     * @var integer
     */
    private $initAmount;

    /**
     * @var integer
     */
    private $availableAmount;

    /**
     * @var \DateTime
     */
    private $timeCreatedH;

    /**
     * @var \DateTime
     */
    private $timeUpdatedH;

    /**
     * @var integer
     */
    private $timeCreated;

    /**
     * @var integer
     */
    private $timeUpdated;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var integer
     */
    private $walletCitizenId;

    /**
     * @var integer
     */
    private $commercialPromotionId;

    /**
     * @var integer
     */
    private $maxUsageInitPrice;


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
     * Set couponId
     *
     * @param string $couponId
     * @return Coupon
     */
    public function setCouponId($couponId)
    {
        $this->couponId = $couponId;
    
        return $this;
    }

    /**
     * Get couponId
     *
     * @return string 
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * Set initAmount
     *
     * @param integer $initAmount
     * @return Coupon
     */
    public function setInitAmount($initAmount)
    {
        $this->initAmount = $initAmount;
    
        return $this;
    }

    /**
     * Get initAmount
     *
     * @return integer 
     */
    public function getInitAmount()
    {
        return $this->initAmount;
    }

    /**
     * Set availableAmount
     *
     * @param integer $availableAmount
     * @return Coupon
     */
    public function setAvailableAmount($availableAmount)
    {
        $this->availableAmount = $availableAmount;
    
        return $this;
    }

    /**
     * Get availableAmount
     *
     * @return integer 
     */
    public function getAvailableAmount()
    {
        return $this->availableAmount;
    }

    /**
     * Set timeCreatedH
     *
     * @param \DateTime $timeCreatedH
     * @return Coupon
     */
    public function setTimeCreatedH($timeCreatedH)
    {
        $this->timeCreatedH = $timeCreatedH;
    
        return $this;
    }

    /**
     * Get timeCreatedH
     *
     * @return \DateTime 
     */
    public function getTimeCreatedH()
    {
        return $this->timeCreatedH;
    }

    /**
     * Set timeUpdatedH
     *
     * @param \DateTime $timeUpdatedH
     * @return Coupon
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
     * Set timeCreated
     *
     * @param integer $timeCreated
     * @return Coupon
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    
        return $this;
    }

    /**
     * Get timeCreated
     *
     * @return integer 
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }

    /**
     * Set timeUpdated
     *
     * @param integer $timeUpdated
     * @return Coupon
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

    /**
     * Set currency
     *
     * @param string $currency
     * @return Coupon
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    
        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set sellerId
     *
     * @param integer $sellerId
     * @return Coupon
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
    
        return $this;
    }

    /**
     * Get sellerId
     *
     * @return integer 
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Set walletCitizenId
     *
     * @param integer $walletCitizenId
     * @return Coupon
     */
    public function setWalletCitizenId($walletCitizenId)
    {
        $this->walletCitizenId = $walletCitizenId;
    
        return $this;
    }

    /**
     * Get walletCitizenId
     *
     * @return integer 
     */
    public function getWalletCitizenId()
    {
        return $this->walletCitizenId;
    }

    /**
     * Set commercialPromotionId
     *
     * @param integer $commercialPromotionId
     * @return Coupon
     */
    public function setCommercialPromotionId($commercialPromotionId)
    {
        $this->commercialPromotionId = $commercialPromotionId;
    
        return $this;
    }

    /**
     * Get commercialPromotionId
     *
     * @return integer 
     */
    public function getCommercialPromotionId()
    {
        return $this->commercialPromotionId;
    }

    /**
     * Set maxUsageInitPrice
     *
     * @param integer $maxUsageInitPrice
     * @return Coupon
     */
    public function setMaxUsageInitPrice($maxUsageInitPrice)
    {
        $this->maxUsageInitPrice = $maxUsageInitPrice;
    
        return $this;
    }

    /**
     * Get maxUsageInitPrice
     *
     * @return integer 
     */
    public function getMaxUsageInitPrice()
    {
        return $this->maxUsageInitPrice;
    }
}