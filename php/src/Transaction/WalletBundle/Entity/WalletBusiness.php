<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletBusiness
 */
class WalletBusiness
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var integer
     */
    private $pendingPaymentAmount;

    /**
     * @var integer
     */
    private $shoppingCardAvailable;

    /**
     * @var integer
     */
    private $cardAvailable;

    /**
     * @var integer
     */
    private $couponAvailable;

    /**
     * @var integer
     */
    private $citizenIncomeAvailble;

    /**
     * @var integer
     */
    private $citizenIncomeGained;

    /**
     * @var \DateTime
     */
    private $timeLastUpdateH;

    /**
     * @var integer
     */
    private $timeLastUpdate;

    /**
     * @var \DateTime
     */
    private $timeCreateH;

    /**
     * @var integer
     */
    private $timeCreate;

    /**
     * @var integer
     */
    private $writingStatus;

    /**
     * @var integer
     */
    private $totalRevenue;

    /**
     * @var integer
     */
    private $premiumPosition;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var integer
     */
    private $creditPosition;

    /**
     * @var integer
     */
    private $pendingPaymentCounter;


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
     * Set currency
     *
     * @param string $currency
     * @return WalletBusiness
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
     * Set pendingPaymentAmount
     *
     * @param integer $pendingPaymentAmount
     * @return WalletBusiness
     */
    public function setPendingPaymentAmount($pendingPaymentAmount)
    {
        $this->pendingPaymentAmount = $pendingPaymentAmount;
    
        return $this;
    }

    /**
     * Get pendingPaymentAmount
     *
     * @return integer 
     */
    public function getPendingPaymentAmount()
    {
        return $this->pendingPaymentAmount;
    }

    /**
     * Set shoppingCardAvailable
     *
     * @param integer $shoppingCardAvailable
     * @return WalletBusiness
     */
    public function setShoppingCardAvailable($shoppingCardAvailable)
    {
        $this->shoppingCardAvailable = $shoppingCardAvailable;
    
        return $this;
    }

    /**
     * Get shoppingCardAvailable
     *
     * @return integer 
     */
    public function getShoppingCardAvailable()
    {
        return $this->shoppingCardAvailable;
    }

    /**
     * Set cardAvailable
     *
     * @param integer $cardAvailable
     * @return WalletBusiness
     */
    public function setCardAvailable($cardAvailable)
    {
        $this->cardAvailable = $cardAvailable;
    
        return $this;
    }

    /**
     * Get cardAvailable
     *
     * @return integer 
     */
    public function getCardAvailable()
    {
        return $this->cardAvailable;
    }

    /**
     * Set couponAvailable
     *
     * @param integer $couponAvailable
     * @return WalletBusiness
     */
    public function setCouponAvailable($couponAvailable)
    {
        $this->couponAvailable = $couponAvailable;
    
        return $this;
    }

    /**
     * Get couponAvailable
     *
     * @return integer 
     */
    public function getCouponAvailable()
    {
        return $this->couponAvailable;
    }

    /**
     * Set citizenIncomeAvailble
     *
     * @param integer $citizenIncomeAvailble
     * @return WalletBusiness
     */
    public function setCitizenIncomeAvailble($citizenIncomeAvailble)
    {
        $this->citizenIncomeAvailble = $citizenIncomeAvailble;
    
        return $this;
    }

    /**
     * Get citizenIncomeAvailble
     *
     * @return integer 
     */
    public function getCitizenIncomeAvailble()
    {
        return $this->citizenIncomeAvailble;
    }

    /**
     * Set citizenIncomeGained
     *
     * @param integer $citizenIncomeGained
     * @return WalletBusiness
     */
    public function setCitizenIncomeGained($citizenIncomeGained)
    {
        $this->citizenIncomeGained = $citizenIncomeGained;
    
        return $this;
    }

    /**
     * Get citizenIncomeGained
     *
     * @return integer 
     */
    public function getCitizenIncomeGained()
    {
        return $this->citizenIncomeGained;
    }

    /**
     * Set timeLastUpdateH
     *
     * @param \DateTime $timeLastUpdateH
     * @return WalletBusiness
     */
    public function setTimeLastUpdateH($timeLastUpdateH)
    {
        $this->timeLastUpdateH = $timeLastUpdateH;
    
        return $this;
    }

    /**
     * Get timeLastUpdateH
     *
     * @return \DateTime 
     */
    public function getTimeLastUpdateH()
    {
        return $this->timeLastUpdateH;
    }

    /**
     * Set timeLastUpdate
     *
     * @param integer $timeLastUpdate
     * @return WalletBusiness
     */
    public function setTimeLastUpdate($timeLastUpdate)
    {
        $this->timeLastUpdate = $timeLastUpdate;
    
        return $this;
    }

    /**
     * Get timeLastUpdate
     *
     * @return integer 
     */
    public function getTimeLastUpdate()
    {
        return $this->timeLastUpdate;
    }

    /**
     * Set timeCreateH
     *
     * @param \DateTime $timeCreateH
     * @return WalletBusiness
     */
    public function setTimeCreateH($timeCreateH)
    {
        $this->timeCreateH = $timeCreateH;
    
        return $this;
    }

    /**
     * Get timeCreateH
     *
     * @return \DateTime 
     */
    public function getTimeCreateH()
    {
        return $this->timeCreateH;
    }

    /**
     * Set timeCreate
     *
     * @param integer $timeCreate
     * @return WalletBusiness
     */
    public function setTimeCreate($timeCreate)
    {
        $this->timeCreate = $timeCreate;
    
        return $this;
    }

    /**
     * Get timeCreate
     *
     * @return integer 
     */
    public function getTimeCreate()
    {
        return $this->timeCreate;
    }

    /**
     * Set writingStatus
     *
     * @param integer $writingStatus
     * @return WalletBusiness
     */
    public function setWritingStatus($writingStatus)
    {
        $this->writingStatus = $writingStatus;
    
        return $this;
    }

    /**
     * Get writingStatus
     *
     * @return integer 
     */
    public function getWritingStatus()
    {
        return $this->writingStatus;
    }

    /**
     * Set totalRevenue
     *
     * @param integer $totalRevenue
     * @return WalletBusiness
     */
    public function setTotalRevenue($totalRevenue)
    {
        $this->totalRevenue = $totalRevenue;
    
        return $this;
    }

    /**
     * Get totalRevenue
     *
     * @return integer 
     */
    public function getTotalRevenue()
    {
        return $this->totalRevenue;
    }

    /**
     * Set premiumPosition
     *
     * @param integer $premiumPosition
     * @return WalletBusiness
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
     * Set sellerId
     *
     * @param integer $sellerId
     * @return WalletBusiness
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
     * Set creditPosition
     *
     * @param integer $creditPosition
     * @return WalletBusiness
     */
    public function setCreditPosition($creditPosition)
    {
        $this->creditPosition = $creditPosition;
    
        return $this;
    }

    /**
     * Get creditPosition
     *
     * @return integer 
     */
    public function getCreditPosition()
    {
        return $this->creditPosition;
    }

    /**
     * Set pendingPaymentCounter
     *
     * @param integer $pendingPaymentCounter
     * @return WalletBusiness
     */
    public function setPendingPaymentCounter($pendingPaymentCounter)
    {
        $this->pendingPaymentCounter = $pendingPaymentCounter;
    
        return $this;
    }

    /**
     * Get pendingPaymentCounter
     *
     * @return integer 
     */
    public function getPendingPaymentCounter()
    {
        return $this->pendingPaymentCounter;
    }
}