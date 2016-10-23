<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AmilonCard
 */
class AmilonCard
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $amilonCardId;

    /**
     * @var integer
     */
    private $initAmount;

    /**
     * @var string
     */
    private $productCode;

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
     * @var \DateTime
     */
    private $validityEndDateH;

    /**
     * @var \DateTime
     */
    private $validityStartDateH;

    /**
     * @var integer
     */
    private $validityStartDate;

    /**
     * @var integer
     */
    private $validityEndDate;

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
     * @var string
     */
    private $dedication;


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
     * Set amilonCardId
     *
     * @param string $amilonCardId
     * @return AmilonCard
     */
    public function setAmilonCardId($amilonCardId)
    {
        $this->amilonCardId = $amilonCardId;
    
        return $this;
    }

    /**
     * Get amilonCardId
     *
     * @return string 
     */
    public function getAmilonCardId()
    {
        return $this->amilonCardId;
    }

    /**
     * Set initAmount
     *
     * @param integer $initAmount
     * @return AmilonCard
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
     * Set productCode
     *
     * @param string $productCode
     * @return AmilonCard
     */
    public function setProductCode($productCode)
    {
        $this->productCode = $productCode;
    
        return $this;
    }

    /**
     * Get productCode
     *
     * @return string 
     */
    public function getProductCode()
    {
        return $this->productCode;
    }

    /**
     * Set availableAmount
     *
     * @param integer $availableAmount
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * Set validityEndDateH
     *
     * @param \DateTime $validityEndDateH
     * @return AmilonCard
     */
    public function setValidityEndDateH($validityEndDateH)
    {
        $this->validityEndDateH = $validityEndDateH;
    
        return $this;
    }

    /**
     * Get validityEndDateH
     *
     * @return \DateTime 
     */
    public function getValidityEndDateH()
    {
        return $this->validityEndDateH;
    }

    /**
     * Set validityStartDateH
     *
     * @param \DateTime $validityStartDateH
     * @return AmilonCard
     */
    public function setValidityStartDateH($validityStartDateH)
    {
        $this->validityStartDateH = $validityStartDateH;
    
        return $this;
    }

    /**
     * Get validityStartDateH
     *
     * @return \DateTime 
     */
    public function getValidityStartDateH()
    {
        return $this->validityStartDateH;
    }

    /**
     * Set validityStartDate
     *
     * @param integer $validityStartDate
     * @return AmilonCard
     */
    public function setValidityStartDate($validityStartDate)
    {
        $this->validityStartDate = $validityStartDate;
    
        return $this;
    }

    /**
     * Get validityStartDate
     *
     * @return integer 
     */
    public function getValidityStartDate()
    {
        return $this->validityStartDate;
    }

    /**
     * Set validityEndDate
     *
     * @param integer $validityEndDate
     * @return AmilonCard
     */
    public function setValidityEndDate($validityEndDate)
    {
        $this->validityEndDate = $validityEndDate;
    
        return $this;
    }

    /**
     * Get validityEndDate
     *
     * @return integer 
     */
    public function getValidityEndDate()
    {
        return $this->validityEndDate;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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
     * @return AmilonCard
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

    /**
     * Set dedication
     *
     * @param string $dedication
     * @return AmilonCard
     */
    public function setDedication($dedication)
    {
        $this->dedication = $dedication;
    
        return $this;
    }

    /**
     * Get dedication
     *
     * @return string 
     */
    public function getDedication()
    {
        return $this->dedication;
    }
    /**
     * @var string
     */
    private $link;


    /**
     * Set link
     *
     * @param string $link
     * @return AmilonCard
     */
    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }
    /**
     * @var integer
     */
    private $connectTrsId;


    /**
     * Set connectTrsId
     *
     * @param integer $connectTrsId
     * @return AmilonCard
     */
    public function setConnectTrsId($connectTrsId)
    {
        $this->connectTrsId = $connectTrsId;
    
        return $this;
    }

    /**
     * Get connectTrsId
     *
     * @return integer 
     */
    public function getConnectTrsId()
    {
        return $this->connectTrsId;
    }
}