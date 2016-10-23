<?php

namespace Transaction\CommercialPromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CodePaid
 */
class CodePaid
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var \DateTime
     */
    private $timeCreateH;

    /**
     * @var \DateTime
     */
    private $timeActivationH;

    /**
     * @var integer
     */
    private $timeCreate;

    /**
     * @var integer
     */
    private $timeActivation;

    /**
     * @var integer
     */
    private $offerId;

    /**
     * @var string
     */
    private $cardId;

    /**
     * @var string
     */
    private $providerId;

    /**
     * @var string
     */
    private $sellerId;

    /**
     * @var integer
     */
    private $walletCitizenID;

    /**
     * @var string
     */
    private $trsId;


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
     * Set amount
     *
     * @param integer $amount
     * @return CodePaid
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
     * Set timeCreateH
     *
     * @param \DateTime $timeCreateH
     * @return CodePaid
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
     * Set timeActivationH
     *
     * @param \DateTime $timeActivationH
     * @return CodePaid
     */
    public function setTimeActivationH($timeActivationH)
    {
        $this->timeActivationH = $timeActivationH;
    
        return $this;
    }

    /**
     * Get timeActivationH
     *
     * @return \DateTime 
     */
    public function getTimeActivationH()
    {
        return $this->timeActivationH;
    }

    /**
     * Set timeCreate
     *
     * @param integer $timeCreate
     * @return CodePaid
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
     * Set timeActivation
     *
     * @param integer $timeActivation
     * @return CodePaid
     */
    public function setTimeActivation($timeActivation)
    {
        $this->timeActivation = $timeActivation;
    
        return $this;
    }

    /**
     * Get timeActivation
     *
     * @return integer 
     */
    public function getTimeActivation()
    {
        return $this->timeActivation;
    }

    /**
     * Set offerId
     *
     * @param integer $offerId
     * @return CodePaid
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    
        return $this;
    }

    /**
     * Get offerId
     *
     * @return integer 
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * Set cardId
     *
     * @param string $cardId
     * @return CodePaid
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
    
        return $this;
    }

    /**
     * Get cardId
     *
     * @return string 
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * Set providerId
     *
     * @param string $providerId
     * @return CodePaid
     */
    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;
    
        return $this;
    }

    /**
     * Get providerId
     *
     * @return string 
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * Set sellerId
     *
     * @param string $sellerId
     * @return CodePaid
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
    
        return $this;
    }

    /**
     * Get sellerId
     *
     * @return string 
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Set walletCitizenID
     *
     * @param integer $walletCitizenID
     * @return CodePaid
     */
    public function setWalletCitizenID($walletCitizenID)
    {
        $this->walletCitizenID = $walletCitizenID;
    
        return $this;
    }

    /**
     * Get walletCitizenID
     *
     * @return integer 
     */
    public function getWalletCitizenID()
    {
        return $this->walletCitizenID;
    }

    /**
     * Set trsId
     *
     * @param string $trsId
     * @return CodePaid
     */
    public function setTrsId($trsId)
    {
        $this->trsId = $trsId;
    
        return $this;
    }

    /**
     * Get trsId
     *
     * @return string 
     */
    public function getTrsId()
    {
        return $this->trsId;
    }
    /**
     * @var string
     */
    private $activationCode;

    /**
     * @var string
     */
    private $activationHelper;


    /**
     * Set activationCode
     *
     * @param string $activationCode
     * @return CodePaid
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;
    
        return $this;
    }

    /**
     * Get activationCode
     *
     * @return string 
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    /**
     * Set activationHelper
     *
     * @param string $activationHelper
     * @return CodePaid
     */
    public function setActivationHelper($activationHelper)
    {
        $this->activationHelper = $activationHelper;
    
        return $this;
    }

    /**
     * Get activationHelper
     *
     * @return string 
     */
    public function getActivationHelper()
    {
        return $this->activationHelper;
    }
}