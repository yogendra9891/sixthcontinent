<?php

namespace Transaction\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletCitizen
 */
class WalletCitizen
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
    private $pendingPayment;

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
    private $citizenIncomeAvailable;

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
    private $buyerId;

    /**
     * @var integer
     */
    private $creditPositionAvailable;

    /**
     * @var integer
     */
    private $creditPositionGained;

    /**
     * @var integer
     */
    private $transactionPreference;


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
     * @return WalletCitizen
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
     * Set pendingPayment
     *
     * @param integer $pendingPayment
     * @return WalletCitizen
     */
    public function setPendingPayment($pendingPayment)
    {
        $this->pendingPayment = $pendingPayment;
    
        return $this;
    }

    /**
     * Get pendingPayment
     *
     * @return integer 
     */
    public function getPendingPayment()
    {
        return $this->pendingPayment;
    }

    /**
     * Set shoppingCardAvailable
     *
     * @param integer $shoppingCardAvailable
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * Set citizenIncomeAvailable
     *
     * @param integer $citizenIncomeAvailable
     * @return WalletCitizen
     */
    public function setCitizenIncomeAvailable($citizenIncomeAvailable)
    {
        $this->citizenIncomeAvailable = $citizenIncomeAvailable;
    
        return $this;
    }

    /**
     * Get citizenIncomeAvailable
     *
     * @return integer 
     */
    public function getCitizenIncomeAvailable()
    {
        return $this->citizenIncomeAvailable;
    }

    /**
     * Set citizenIncomeGained
     *
     * @param integer $citizenIncomeGained
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * @return WalletCitizen
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
     * Set buyerId
     *
     * @param integer $buyerId
     * @return WalletCitizen
     */
    public function setBuyerId($buyerId)
    {
        $this->buyerId = $buyerId;
    
        return $this;
    }

    /**
     * Get buyerId
     *
     * @return integer 
     */
    public function getBuyerId()
    {
        return $this->buyerId;
    }

    /**
     * Set creditPositionAvailable
     *
     * @param integer $creditPositionAvailable
     * @return WalletCitizen
     */
    public function setCreditPositionAvailable($creditPositionAvailable)
    {
        $this->creditPositionAvailable = $creditPositionAvailable;
    
        return $this;
    }

    /**
     * Get creditPositionAvailable
     *
     * @return integer 
     */
    public function getCreditPositionAvailable()
    {
        return $this->creditPositionAvailable;
    }

    /**
     * Set creditPositionGained
     *
     * @param integer $creditPositionGained
     * @return WalletCitizen
     */
    public function setCreditPositionGained($creditPositionGained)
    {
        $this->creditPositionGained = $creditPositionGained;
    
        return $this;
    }

    /**
     * Get creditPositionGained
     *
     * @return integer 
     */
    public function getCreditPositionGained()
    {
        return $this->creditPositionGained;
    }

    /**
     * Set transactionPreference
     *
     * @param integer $transactionPreference
     * @return WalletCitizen
     */
    public function setTransactionPreference($transactionPreference)
    {
        $this->transactionPreference = $transactionPreference;
    
        return $this;
    }

    /**
     * Get transactionPreference
     *
     * @return integer 
     */
    public function getTransactionPreference()
    {
        return $this->transactionPreference;
    }
}