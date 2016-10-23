<?php

namespace Payment\PaymentProcessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentProcessCredit
 */
class PaymentProcessCredit
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $amountData;

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
    private $totalAmount;

    /**
     * @var integer
     */
    private $coupons;

    /**
     * @var integer
     */
    private $usedCoupons;

    /**
     * @var integer
     */
    private $premiumPosition;

    /**
     * @var integer
     */
    private $usedPremiumPosition;

    /**
     * @var integer
     */
    private $giftCard;

    /**
     * @var integer
     */
    private $usedGiftCard;

    /**
     * @var integer
     */
    private $momosyCard;

    /**
     * @var integer
     */
    private $usedMomosyCard;

    /**
     * @var integer
     */
    private $totalCitizenIncome;

    /**
     * @var integer
     */
    private $cpgAmount;

    /**
     * @var integer
     */
    private $totalUsed;

    /**
     * @var integer
     */
    private $balanceAmount;

    /**
     * @var string
     */
    private $giftCardPacketData;

    /**
     * @var integer
     */
    private $remainingGiftCards;

    /**
     * @var integer
     */
    private $usedRemainingGiftCards;

    /**
     * @var string
     */
    private $citizenStatus;

    /**
     * @var string
     */
    private $shopStatus;

    /**
     * @var integer
     */
    private $creditUse;

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
     * Set amountData
     *
     * @param string $amountData
     * @return PaymentProcessCredit
     */
    public function setAmountData($amountData)
    {
        $this->amountData = $amountData;
    
        return $this;
    }

    /**
     * Get amountData
     *
     * @return string 
     */
    public function getAmountData()
    {
        return $this->amountData;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return PaymentProcessCredit
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
     * @return PaymentProcessCredit
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
     * Set totalAmount
     *
     * @param integer $totalAmount
     * @return PaymentProcessCredit
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
     * Set coupons
     *
     * @param integer $coupons
     * @return PaymentProcessCredit
     */
    public function setCoupons($coupons)
    {
        $this->coupons = $coupons;
    
        return $this;
    }

    /**
     * Get coupons
     *
     * @return integer 
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * Set usedCoupons
     *
     * @param integer $usedCoupons
     * @return PaymentProcessCredit
     */
    public function setUsedCoupons($usedCoupons)
    {
        $this->usedCoupons = $usedCoupons;
    
        return $this;
    }

    /**
     * Get usedCoupons
     *
     * @return integer 
     */
    public function getUsedCoupons()
    {
        return $this->usedCoupons;
    }

    /**
     * Set premiumPosition
     *
     * @param integer $premiumPosition
     * @return PaymentProcessCredit
     */
    public function setPremiumPosition($premiumPosition)
    {
        $this->premiumPosition = $premiumPosition;
    
        return $this;
    }

    /**
     * Get premiumPosition
     *
     * @return integer 
     */
    public function getPremiumPosition()
    {
        return $this->premiumPosition;
    }

    /**
     * Set usedPremiumPosition
     *
     * @param integer $usedPremiumPosition
     * @return PaymentProcessCredit
     */
    public function setUsedPremiumPosition($usedPremiumPosition)
    {
        $this->usedPremiumPosition = $usedPremiumPosition;
    
        return $this;
    }

    /**
     * Get usedPremiumPosition
     *
     * @return integer 
     */
    public function getUsedPremiumPosition()
    {
        return $this->usedPremiumPosition;
    }

    /**
     * Set giftCard
     *
     * @param integer $giftCard
     * @return PaymentProcessCredit
     */
    public function setGiftCard($giftCard)
    {
        $this->giftCard = $giftCard;
    
        return $this;
    }

    /**
     * Get giftCard
     *
     * @return integer 
     */
    public function getGiftCard()
    {
        return $this->giftCard;
    }

    /**
     * Set usedGiftCard
     *
     * @param integer $usedGiftCard
     * @return PaymentProcessCredit
     */
    public function setUsedGiftCard($usedGiftCard)
    {
        $this->usedGiftCard = $usedGiftCard;
    
        return $this;
    }

    /**
     * Get usedGiftCard
     *
     * @return integer 
     */
    public function getUsedGiftCard()
    {
        return $this->usedGiftCard;
    }

    /**
     * Set momosyCard
     *
     * @param integer $momosyCard
     * @return PaymentProcessCredit
     */
    public function setMomosyCard($momosyCard)
    {
        $this->momosyCard = $momosyCard;
    
        return $this;
    }

    /**
     * Get momosyCard
     *
     * @return integer 
     */
    public function getMomosyCard()
    {
        return $this->momosyCard;
    }

    /**
     * Set usedMomosyCard
     *
     * @param integer $usedMomosyCard
     * @return PaymentProcessCredit
     */
    public function setUsedMomosyCard($usedMomosyCard)
    {
        $this->usedMomosyCard = $usedMomosyCard;
    
        return $this;
    }

    /**
     * Get usedMomosyCard
     *
     * @return integer 
     */
    public function getUsedMomosyCard()
    {
        return $this->usedMomosyCard;
    }

    /**
     * Set totalCitizenIncome
     *
     * @param integer $totalCitizenIncome
     * @return PaymentProcessCredit
     */
    public function setTotalCitizenIncome($totalCitizenIncome)
    {
        $this->totalCitizenIncome = $totalCitizenIncome;
    
        return $this;
    }

    /**
     * Get totalCitizenIncome
     *
     * @return integer 
     */
    public function getTotalCitizenIncome()
    {
        return $this->totalCitizenIncome;
    }

    /**
     * Set cpgAmount
     *
     * @param integer $cpgAmount
     * @return PaymentProcessCredit
     */
    public function setCpgAmount($cpgAmount)
    {
        $this->cpgAmount = $cpgAmount;
    
        return $this;
    }

    /**
     * Get cpgAmount
     *
     * @return integer 
     */
    public function getCpgAmount()
    {
        return $this->cpgAmount;
    }

    /**
     * Set totalUsed
     *
     * @param integer $totalUsed
     * @return PaymentProcessCredit
     */
    public function setTotalUsed($totalUsed)
    {
        $this->totalUsed = $totalUsed;
    
        return $this;
    }

    /**
     * Get totalUsed
     *
     * @return integer 
     */
    public function getTotalUsed()
    {
        return $this->totalUsed;
    }

    /**
     * Set balanceAmount
     *
     * @param integer $balanceAmount
     * @return PaymentProcessCredit
     */
    public function setBalanceAmount($balanceAmount)
    {
        $this->balanceAmount = $balanceAmount;
    
        return $this;
    }

    /**
     * Get balanceAmount
     *
     * @return integer 
     */
    public function getBalanceAmount()
    {
        return $this->balanceAmount;
    }

    /**
     * Set giftCardPacketData
     *
     * @param string $giftCardPacketData
     * @return PaymentProcessCredit
     */
    public function setGiftCardPacketData($giftCardPacketData)
    {
        $this->giftCardPacketData = $giftCardPacketData;
    
        return $this;
    }

    /**
     * Get giftCardPacketData
     *
     * @return string 
     */
    public function getGiftCardPacketData()
    {
        return $this->giftCardPacketData;
    }

    /**
     * Set remainingGiftCards
     *
     * @param integer $remainingGiftCards
     * @return PaymentProcessCredit
     */
    public function setRemainingGiftCards($remainingGiftCards)
    {
        $this->remainingGiftCards = $remainingGiftCards;
    
        return $this;
    }

    /**
     * Get remainingGiftCards
     *
     * @return integer 
     */
    public function getRemainingGiftCards()
    {
        return $this->remainingGiftCards;
    }

    /**
     * Set usedRemainingGiftCards
     *
     * @param integer $usedRemainingGiftCards
     * @return PaymentProcessCredit
     */
    public function setUsedRemainingGiftCards($usedRemainingGiftCards)
    {
        $this->usedRemainingGiftCards = $usedRemainingGiftCards;
    
        return $this;
    }

    /**
     * Get usedRemainingGiftCards
     *
     * @return integer 
     */
    public function getUsedRemainingGiftCards()
    {
        return $this->usedRemainingGiftCards;
    }

    /**
     * Set citizenStatus
     *
     * @param string $citizenStatus
     * @return PaymentProcessCredit
     */
    public function setCitizenStatus($citizenStatus)
    {
        $this->citizenStatus = $citizenStatus;
    
        return $this;
    }

    /**
     * Get citizenStatus
     *
     * @return string 
     */
    public function getCitizenStatus()
    {
        return $this->citizenStatus;
    }

    /**
     * Set shopStatus
     *
     * @param string $shopStatus
     * @return PaymentProcessCredit
     */
    public function setShopStatus($shopStatus)
    {
        $this->shopStatus = $shopStatus;
    
        return $this;
    }

    /**
     * Get shopStatus
     *
     * @return string 
     */
    public function getShopStatus()
    {
        return $this->shopStatus;
    }

    /**
     * Set creditUse
     *
     * @param integer $creditUse
     * @return PaymentProcessCredit
     */
    public function setCreditUse($creditUse)
    {
        $this->creditUse = $creditUse;
    
        return $this;
    }

    /**
     * Get creditUse
     *
     * @return integer 
     */
    public function getCreditUse()
    {
        return $this->creditUse;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PaymentProcessCredit
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
     * @var integer
     */
    private $credit_level;


    /**
     * Set credit_level
     *
     * @param integer $creditLevel
     * @return PaymentProcessCredit
     */
    public function setCreditLevel($creditLevel)
    {
        $this->credit_level = $creditLevel;
    
        return $this;
    }

    /**
     * Get credit_level
     *
     * @return integer 
     */
    public function getCreditLevel()
    {
        return $this->credit_level;
    }
    /**
     * @var \DateTime
     */
    private $updatedAt;


    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return PaymentProcessCredit
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
    private $isDistributed = 0;


    /**
     * Set isDistributed
     *
     * @param integer $isDistributed
     * @return PaymentProcessCredit
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

    /**
     * @var \DateTime
     */
    private $transactionApprovedAt;


    /**
     * Set transactionApprovedAt
     *
     * @param \DateTime $transactionApprovedAt
     * @return PaymentProcessCredit
     */
    public function setTransactionApprovedAt($transactionApprovedAt)
    {
        $this->transactionApprovedAt = $transactionApprovedAt;
    
        return $this;
    }

    /**
     * Get transactionApprovedAt
     *
     * @return \DateTime 
     */
    public function getTransactionApprovedAt()
    {
        return $this->transactionApprovedAt;
    }
    /**
     * @var integer
     */
    private $isCalculated = 0;


    /**
     * Set isCalculated
     *
     * @param integer $isCalculated
     * @return PaymentProcessCredit
     */
    public function setIsCalculated($isCalculated)
    {
        $this->isCalculated = $isCalculated;
    
        return $this;
    }

    /**
     * Get isCalculated
     *
     * @return integer 
     */
    public function getIsCalculated()
    {
        return $this->isCalculated;
    }
}