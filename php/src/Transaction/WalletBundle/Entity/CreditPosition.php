<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CreditPosition
 */
class CreditPosition
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $premiumId;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var \DateTime
     */
    private $timeCreatedH;

    /**
     * @var integer
     */
    private $timeCreated;

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
     * Set premiumId
     *
     * @param string $premiumId
     * @return CreditPosition
     */
    public function setPremiumId($premiumId)
    {
        $this->premiumId = $premiumId;
    
        return $this;
    }

    /**
     * Get premiumId
     *
     * @return string 
     */
    public function getPremiumId()
    {
        return $this->premiumId;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return CreditPosition
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
     * Set timeCreatedH
     *
     * @param \DateTime $timeCreatedH
     * @return CreditPosition
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
     * Set timeCreated
     *
     * @param integer $timeCreated
     * @return CreditPosition
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
     * Set currency
     *
     * @param string $currency
     * @return CreditPosition
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
     * @return CreditPosition
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
     * @return CreditPosition
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
     * @return CreditPosition
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
     * Set sixcTransactionId
     *
     * @param string $sixcTransactionId
     * @return CreditPosition
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