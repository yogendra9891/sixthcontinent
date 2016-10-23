<?php

namespace Transaction\TransactionSystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction
 */
class Transaction
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $sixcTransactionId;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var string
     */
    private $buyerCurrency;

    /**
     * @var string
     */
    private $sellerCurrency;

    /**
     * @var string
     */
    private $bOverSCurrencyRation;

    /**
     * @var integer
     */
    private $initPrice;

    /**
     * @var integer
     */
    private $finalPrice;

    /**
     * @var integer
     */
    private $withCredit;

    /**
     * @var integer
     */
    private $discountUsed;

    /**
     * @var integer
     */
    private $citizenIncomeUsed;

    /**
     * @var integer
     */
    private $couponUsed;

    /**
     * @var integer
     */
    private $creditPayment;

    /**
     * @var integer
     */
    private $shoppingCardUsed;

    /**
     * @var \DateTime
     */
    private $timeInitH;

    /**
     * @var \DateTime
     */
    private $timeUpdateStatusH;

    /**
     * @var \DateTime
     */
    private $timeCloseH;

    /**
     * @var integer
     */
    private $timeInit;

    /**
     * @var integer
     */
    private $timeUpdateStatus;

    /**
     * @var integer
     */
    private $timeClose;

    /**
     * @var integer
     */
    private $buyerId;

    /**
     * @var integer
     */
    private $transactionFee;

    /**
     * @var integer
     */
    private $sixcAmountPc;

    /**
     * @var integer
     */
    private $sixcAmountPcVat;

    /**
     * @var float
     */
    private $sellerPc;

    /**
     * @var integer
     */
    private $transactionTypeId;

    /**
     * @var integer
     */
    private $redistributionStatus;

    /**
     * @var integer
     */
    private $citizenAffCharge;

    /**
     * @var integer
     */
    private $shopAffCharge;

    /**
     * @var integer
     */
    private $friendsFollowerCharge;

    /**
     * @var integer
     */
    private $buyerCharge;

    /**
     * @var integer
     */
    private $sixcCharge;

    /**
     * @var integer
     */
    private $allCountryCharge;

    /**
     * @var string
     */
    private $transactionSerialize;

    /**
     * @var string
     */
    private $transactionGateWayReference;


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
     * Set status
     *
     * @param string $status
     * @return Transaction
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set sixcTransactionId
     *
     * @param string $sixcTransactionId
     * @return Transaction
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
     * Set sellerId
     *
     * @param integer $sellerId
     * @return Transaction
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
     * Set buyerCurrency
     *
     * @param string $buyerCurrency
     * @return Transaction
     */
    public function setBuyerCurrency($buyerCurrency)
    {
        $this->buyerCurrency = $buyerCurrency;
    
        return $this;
    }

    /**
     * Get buyerCurrency
     *
     * @return string 
     */
    public function getBuyerCurrency()
    {
        return $this->buyerCurrency;
    }

    /**
     * Set sellerCurrency
     *
     * @param string $sellerCurrency
     * @return Transaction
     */
    public function setSellerCurrency($sellerCurrency)
    {
        $this->sellerCurrency = $sellerCurrency;
    
        return $this;
    }

    /**
     * Get sellerCurrency
     *
     * @return string 
     */
    public function getSellerCurrency()
    {
        return $this->sellerCurrency;
    }

    /**
     * Set bOverSCurrencyRation
     *
     * @param string $bOverSCurrencyRation
     * @return Transaction
     */
    public function setBOverSCurrencyRation($bOverSCurrencyRation)
    {
        $this->bOverSCurrencyRation = $bOverSCurrencyRation;
    
        return $this;
    }

    /**
     * Get bOverSCurrencyRation
     *
     * @return string 
     */
    public function getBOverSCurrencyRation()
    {
        return $this->bOverSCurrencyRation;
    }

    /**
     * Set initPrice
     *
     * @param integer $initPrice
     * @return Transaction
     */
    public function setInitPrice($initPrice)
    {
        $this->initPrice = $initPrice;
    
        return $this;
    }

    /**
     * Get initPrice
     *
     * @return integer 
     */
    public function getInitPrice()
    {
        return $this->initPrice;
    }

    /**
     * Set finalPrice
     *
     * @param integer $finalPrice
     * @return Transaction
     */
    public function setFinalPrice($finalPrice)
    {
        $this->finalPrice = $finalPrice;
    
        return $this;
    }

    /**
     * Get finalPrice
     *
     * @return integer 
     */
    public function getFinalPrice()
    {
        return $this->finalPrice;
    }

    /**
     * Set withCredit
     *
     * @param integer $withCredit
     * @return Transaction
     */
    public function setWithCredit($withCredit)
    {
        $this->withCredit = $withCredit;
    
        return $this;
    }

    /**
     * Get withCredit
     *
     * @return integer 
     */
    public function getWithCredit()
    {
        return $this->withCredit;
    }

    /**
     * Set discountUsed
     *
     * @param integer $discountUsed
     * @return Transaction
     */
    public function setDiscountUsed($discountUsed)
    {
        $this->discountUsed = $discountUsed;
    
        return $this;
    }

    /**
     * Get discountUsed
     *
     * @return integer 
     */
    public function getDiscountUsed()
    {
        return $this->discountUsed;
    }

    /**
     * Set citizenIncomeUsed
     *
     * @param integer $citizenIncomeUsed
     * @return Transaction
     */
    public function setCitizenIncomeUsed($citizenIncomeUsed)
    {
        $this->citizenIncomeUsed = $citizenIncomeUsed;
    
        return $this;
    }

    /**
     * Get citizenIncomeUsed
     *
     * @return integer 
     */
    public function getCitizenIncomeUsed()
    {
        return $this->citizenIncomeUsed;
    }

    /**
     * Set couponUsed
     *
     * @param integer $couponUsed
     * @return Transaction
     */
    public function setCouponUsed($couponUsed)
    {
        $this->couponUsed = $couponUsed;
    
        return $this;
    }

    /**
     * Get couponUsed
     *
     * @return integer 
     */
    public function getCouponUsed()
    {
        return $this->couponUsed;
    }

    /**
     * Set creditPayment
     *
     * @param integer $creditPayment
     * @return Transaction
     */
    public function setCreditPayment($creditPayment)
    {
        $this->creditPayment = $creditPayment;
    
        return $this;
    }

    /**
     * Get creditPayment
     *
     * @return integer 
     */
    public function getCreditPayment()
    {
        return $this->creditPayment;
    }

    /**
     * Set shoppingCardUsed
     *
     * @param integer $shoppingCardUsed
     * @return Transaction
     */
    public function setShoppingCardUsed($shoppingCardUsed)
    {
        $this->shoppingCardUsed = $shoppingCardUsed;
    
        return $this;
    }

    /**
     * Get shoppingCardUsed
     *
     * @return integer 
     */
    public function getShoppingCardUsed()
    {
        return $this->shoppingCardUsed;
    }

    /**
     * Set timeInitH
     *
     * @param \DateTime $timeInitH
     * @return Transaction
     */
    public function setTimeInitH($timeInitH)
    {
        $this->timeInitH = $timeInitH;
    
        return $this;
    }

    /**
     * Get timeInitH
     *
     * @return \DateTime 
     */
    public function getTimeInitH()
    {
        return $this->timeInitH;
    }

    /**
     * Set timeUpdateStatusH
     *
     * @param \DateTime $timeUpdateStatusH
     * @return Transaction
     */
    public function setTimeUpdateStatusH($timeUpdateStatusH)
    {
        $this->timeUpdateStatusH = $timeUpdateStatusH;
    
        return $this;
    }

    /**
     * Get timeUpdateStatusH
     *
     * @return \DateTime 
     */
    public function getTimeUpdateStatusH()
    {
        return $this->timeUpdateStatusH;
    }

    /**
     * Set timeCloseH
     *
     * @param \DateTime $timeCloseH
     * @return Transaction
     */
    public function setTimeCloseH($timeCloseH)
    {
        $this->timeCloseH = $timeCloseH;
    
        return $this;
    }

    /**
     * Get timeCloseH
     *
     * @return \DateTime 
     */
    public function getTimeCloseH()
    {
        return $this->timeCloseH;
    }

    /**
     * Set timeInit
     *
     * @param integer $timeInit
     * @return Transaction
     */
    public function setTimeInit($timeInit)
    {
        $this->timeInit = $timeInit;
    
        return $this;
    }

    /**
     * Get timeInit
     *
     * @return integer 
     */
    public function getTimeInit()
    {
        return $this->timeInit;
    }

    /**
     * Set timeUpdateStatus
     *
     * @param integer $timeUpdateStatus
     * @return Transaction
     */
    public function setTimeUpdateStatus($timeUpdateStatus)
    {
        $this->timeUpdateStatus = $timeUpdateStatus;
    
        return $this;
    }

    /**
     * Get timeUpdateStatus
     *
     * @return integer 
     */
    public function getTimeUpdateStatus()
    {
        return $this->timeUpdateStatus;
    }

    /**
     * Set timeClose
     *
     * @param integer $timeClose
     * @return Transaction
     */
    public function setTimeClose($timeClose)
    {
        $this->timeClose = $timeClose;
    
        return $this;
    }

    /**
     * Get timeClose
     *
     * @return integer 
     */
    public function getTimeClose()
    {
        return $this->timeClose;
    }

    /**
     * Set buyerId
     *
     * @param integer $buyerId
     * @return Transaction
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
     * Set transactionFee
     *
     * @param integer $transactionFee
     * @return Transaction
     */
    public function setTransactionFee($transactionFee)
    {
        $this->transactionFee = $transactionFee;
    
        return $this;
    }

    /**
     * Get transactionFee
     *
     * @return integer 
     */
    public function getTransactionFee()
    {
        return $this->transactionFee;
    }

    /**
     * Set sixcAmountPc
     *
     * @param integer $sixcAmountPc
     * @return Transaction
     */
    public function setSixcAmountPc($sixcAmountPc)
    {
        $this->sixcAmountPc = $sixcAmountPc;
    
        return $this;
    }

    /**
     * Get sixcAmountPc
     *
     * @return integer 
     */
    public function getSixcAmountPc()
    {
        return $this->sixcAmountPc;
    }

    /**
     * Set sixcAmountPcVat
     *
     * @param integer $sixcAmountPcVat
     * @return Transaction
     */
    public function setSixcAmountPcVat($sixcAmountPcVat)
    {
        $this->sixcAmountPcVat = $sixcAmountPcVat;
    
        return $this;
    }

    /**
     * Get sixcAmountPcVat
     *
     * @return integer 
     */
    public function getSixcAmountPcVat()
    {
        return $this->sixcAmountPcVat;
    }

    /**
     * Set sellerPc
     *
     * @param float $sellerPc
     * @return Transaction
     */
    public function setSellerPc($sellerPc)
    {
        $this->sellerPc = $sellerPc;
    
        return $this;
    }

    /**
     * Get sellerPc
     *
     * @return float 
     */
    public function getSellerPc()
    {
        return $this->sellerPc;
    }

    /**
     * Set transactionTypeId
     *
     * @param integer $transactionTypeId
     * @return Transaction
     */
    public function setTransactionTypeId($transactionTypeId)
    {
        $this->transactionTypeId = $transactionTypeId;
    
        return $this;
    }

    /**
     * Get transactionTypeId
     *
     * @return integer 
     */
    public function getTransactionTypeId()
    {
        return $this->transactionTypeId;
    }

    /**
     * Set redistributionStatus
     *
     * @param integer $redistributionStatus
     * @return Transaction
     */
    public function setRedistributionStatus($redistributionStatus)
    {
        $this->redistributionStatus = $redistributionStatus;
    
        return $this;
    }

    /**
     * Get redistributionStatus
     *
     * @return integer 
     */
    public function getRedistributionStatus()
    {
        return $this->redistributionStatus;
    }

    /**
     * Set citizenAffCharge
     *
     * @param integer $citizenAffCharge
     * @return Transaction
     */
    public function setCitizenAffCharge($citizenAffCharge)
    {
        $this->citizenAffCharge = $citizenAffCharge;
    
        return $this;
    }

    /**
     * Get citizenAffCharge
     *
     * @return integer 
     */
    public function getCitizenAffCharge()
    {
        return $this->citizenAffCharge;
    }

    /**
     * Set shopAffCharge
     *
     * @param integer $shopAffCharge
     * @return Transaction
     */
    public function setShopAffCharge($shopAffCharge)
    {
        $this->shopAffCharge = $shopAffCharge;
    
        return $this;
    }

    /**
     * Get shopAffCharge
     *
     * @return integer 
     */
    public function getShopAffCharge()
    {
        return $this->shopAffCharge;
    }

    /**
     * Set friendsFollowerCharge
     *
     * @param integer $friendsFollowerCharge
     * @return Transaction
     */
    public function setFriendsFollowerCharge($friendsFollowerCharge)
    {
        $this->friendsFollowerCharge = $friendsFollowerCharge;
    
        return $this;
    }

    /**
     * Get friendsFollowerCharge
     *
     * @return integer 
     */
    public function getFriendsFollowerCharge()
    {
        return $this->friendsFollowerCharge;
    }

    /**
     * Set buyerCharge
     *
     * @param integer $buyerCharge
     * @return Transaction
     */
    public function setBuyerCharge($buyerCharge)
    {
        $this->buyerCharge = $buyerCharge;
    
        return $this;
    }

    /**
     * Get buyerCharge
     *
     * @return integer 
     */
    public function getBuyerCharge()
    {
        return $this->buyerCharge;
    }

    /**
     * Set sixcCharge
     *
     * @param integer $sixcCharge
     * @return Transaction
     */
    public function setSixcCharge($sixcCharge)
    {
        $this->sixcCharge = $sixcCharge;
    
        return $this;
    }

    /**
     * Get sixcCharge
     *
     * @return integer 
     */
    public function getSixcCharge()
    {
        return $this->sixcCharge;
    }

    /**
     * Set allCountryCharge
     *
     * @param integer $allCountryCharge
     * @return Transaction
     */
    public function setAllCountryCharge($allCountryCharge)
    {
        $this->allCountryCharge = $allCountryCharge;
    
        return $this;
    }

    /**
     * Get allCountryCharge
     *
     * @return integer 
     */
    public function getAllCountryCharge()
    {
        return $this->allCountryCharge;
    }

    /**
     * Set transactionSerialize
     *
     * @param string $transactionSerialize
     * @return Transaction
     */
    public function setTransactionSerialize($transactionSerialize)
    {
        $this->transactionSerialize = $transactionSerialize;
    
        return $this;
    }

    /**
     * Get transactionSerialize
     *
     * @return string 
     */
    public function getTransactionSerialize()
    {
        return $this->transactionSerialize;
    }

    /**
     * Set transactionGateWayReference
     *
     * @param string $transactionGateWayReference
     * @return Transaction
     */
    public function setTransactionGateWayReference($transactionGateWayReference)
    {
        $this->transactionGateWayReference = $transactionGateWayReference;
    
        return $this;
    }

    /**
     * Get transactionGateWayReference
     *
     * @return string 
     */
    public function getTransactionGateWayReference()
    {
        return $this->transactionGateWayReference;
    }
}
