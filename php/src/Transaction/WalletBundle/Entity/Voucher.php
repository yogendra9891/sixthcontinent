<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Voucher
 */
class Voucher
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $voucherId;

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
    private $maxUsageInitPrice;

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
     * @var string
     */
    private $sixcTransactionId;


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
     * Set voucherId
     *
     * @param string $voucherId
     * @return Voucher
     */
    public function setVoucherId($voucherId)
    {
        $this->voucherId = $voucherId;
    
        return $this;
    }

    /**
     * Get voucherId
     *
     * @return string 
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * Set initAmount
     *
     * @param integer $initAmount
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * Set maxUsageInitPrice
     *
     * @param integer $maxUsageInitPrice
     * @return Voucher
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

    /**
     * Set sellerId
     *
     * @param integer $sellerId
     * @return Voucher
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
     * @return Voucher
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
     * @return Voucher
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
     * Set sixcTransactionId
     *
     * @param string $sixcTransactionId
     * @return Voucher
     */
    public function setSixcTransactionId($sixcTransactionId)
    {
        $this->sixcTransactionId = $sixcTransactionId;
    
        return $this;
    }

    /**
     * Get sixcTransactionId
     *
     * @return string 
     */
    public function getSixcTransactionId()
    {
        return $this->sixcTransactionId;
    }
}