<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShoppingCard
 */
class ShoppingCard
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $shoppingCardId;

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
     * Set shoppingCardId
     *
     * @param string $shoppingCardId
     * @return ShoppingCard
     */
    public function setShoppingCardId($shoppingCardId)
    {
        $this->shoppingCardId = $shoppingCardId;
    
        return $this;
    }

    /**
     * Get shoppingCardId
     *
     * @return string 
     */
    public function getShoppingCardId()
    {
        return $this->shoppingCardId;
    }

    /**
     * Set initAmount
     *
     * @param integer $initAmount
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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
     * @return ShoppingCard
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