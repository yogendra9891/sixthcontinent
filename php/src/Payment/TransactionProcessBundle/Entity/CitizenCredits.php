<?php

namespace Payment\TransactionProcessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CitizenCredits
 */
class CitizenCredits
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $creditCode;

    /**
     * @var integer
     */
    private $buyerId;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $creditType;

    /**
     * @var integer
     */
    private $totalAmount;

    /**
     * @var integer
     */
    private $usedAmount;

    /**
     * @var integer
     */
    private $balanceAmount;

    /**
     * @var \DateTime
     */
    private $expiryDate;

    /**
     * @var \DateTime
     */
    private $creationDate;

    /**
     * @var integer
     */
    private $campaignId;

    /**
     * @var integer
     */
    private $isUsedAnyWhere;


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
     * Set creditCode
     *
     * @param string $creditCode
     * @return CitizenCredits
     */
    public function setCreditCode($creditCode)
    {
        $this->creditCode = $creditCode;
    
        return $this;
    }

    /**
     * Get creditCode
     *
     * @return string 
     */
    public function getCreditCode()
    {
        return $this->creditCode;
    }

    /**
     * Set buyerId
     *
     * @param integer $buyerId
     * @return CitizenCredits
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
     * Set sellerId
     *
     * @param integer $sellerId
     * @return CitizenCredits
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
     * Set status
     *
     * @param string $status
     * @return CitizenCredits
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
     * Set creditType
     *
     * @param string $creditType
     * @return CitizenCredits
     */
    public function setCreditType($creditType)
    {
        $this->creditType = $creditType;
    
        return $this;
    }

    /**
     * Get creditType
     *
     * @return string 
     */
    public function getCreditType()
    {
        return $this->creditType;
    }

    /**
     * Set totalAmount
     *
     * @param integer $totalAmount
     * @return CitizenCredits
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
     * Set usedAmount
     *
     * @param integer $usedAmount
     * @return CitizenCredits
     */
    public function setUsedAmount($usedAmount)
    {
        $this->usedAmount = $usedAmount;
    
        return $this;
    }

    /**
     * Get usedAmount
     *
     * @return integer 
     */
    public function getUsedAmount()
    {
        return $this->usedAmount;
    }

    /**
     * Set balanceAmount
     *
     * @param integer $balanceAmount
     * @return CitizenCredits
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
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return CitizenCredits
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;
    
        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return CitizenCredits
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    
        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime 
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set campaignId
     *
     * @param integer $campaignId
     * @return CitizenCredits
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    
        return $this;
    }

    /**
     * Get campaignId
     *
     * @return integer 
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * Set isUsedAnyWhere
     *
     * @param integer $isUsedAnyWhere
     * @return CitizenCredits
     */
    public function setIsUsedAnyWhere($isUsedAnyWhere)
    {
        $this->isUsedAnyWhere = $isUsedAnyWhere;
    
        return $this;
    }

    /**
     * Get isUsedAnyWhere
     *
     * @return integer 
     */
    public function getIsUsedAnyWhere()
    {
        return $this->isUsedAnyWhere;
    }
}
