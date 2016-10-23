<?php

namespace Payment\TransactionProcessBundle\Entity;

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
    private $transactionType;

    /**
     * @var integer
     */
    private $buyerId;

    /**
     * @var integer
     */
    private $citizenCreditLevel;

    /**
     * @var integer
     */
    private $citizenUserCredit;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $transactionDate;

    /**
     * @var integer
     */
    private $transactionAmount;

    /**
     * @var integer
     */
    private $totalCreditUsed;

    /**
     * @var integer
     */
    private $discountUsed;

    /**
     * @var integer
     */
    private $cashPaid;

    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $statusDate;

    /**
     * @var string
     */
    private $CreditDisbursalStatus;

    /**
     * @var \DateTime
     */
    private $DisbursalDate;

    /**
     * @var integer
     */
    private $ParentTransactionId;

    /**
     * @var string
     */
    private $remarks;


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
     * Set transactionType
     *
     * @param string $transactionType
     * @return Transaction
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
    
        return $this;
    }

    /**
     * Get transactionType
     *
     * @return string 
     */
    public function getTransactionType()
    {
        return $this->transactionType;
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
     * Set citizenCreditLevel
     *
     * @param integer $citizenCreditLevel
     * @return Transaction
     */
    public function setCitizenCreditLevel($citizenCreditLevel)
    {
        $this->citizenCreditLevel = $citizenCreditLevel;
    
        return $this;
    }

    /**
     * Get citizenCreditLevel
     *
     * @return integer 
     */
    public function getCitizenCreditLevel()
    {
        return $this->citizenCreditLevel;
    }

    /**
     * Set citizenUserCredit
     *
     * @param integer $citizenUserCredit
     * @return Transaction
     */
    public function setCitizenUserCredit($citizenUserCredit)
    {
        $this->citizenUserCredit = $citizenUserCredit;
    
        return $this;
    }

    /**
     * Get citizenUserCredit
     *
     * @return integer 
     */
    public function getCitizenUserCredit()
    {
        return $this->citizenUserCredit;
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
     * Set description
     *
     * @param string $description
     * @return Transaction
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set transactionDate
     *
     * @param \DateTime $transactionDate
     * @return Transaction
     */
    public function setTransactionDate($transactionDate)
    {
        $this->transactionDate = $transactionDate;
    
        return $this;
    }

    /**
     * Get transactionDate
     *
     * @return \DateTime 
     */
    public function getTransactionDate()
    {
        return $this->transactionDate;
    }

    /**
     * Set transactionAmount
     *
     * @param integer $transactionAmount
     * @return Transaction
     */
    public function setTransactionAmount($transactionAmount)
    {
        $this->transactionAmount = $transactionAmount;
    
        return $this;
    }

    /**
     * Get transactionAmount
     *
     * @return integer 
     */
    public function getTransactionAmount()
    {
        return $this->transactionAmount;
    }

    /**
     * Set totalCreditUsed
     *
     * @param integer $totalCreditUsed
     * @return Transaction
     */
    public function setTotalCreditUsed($totalCreditUsed)
    {
        $this->totalCreditUsed = $totalCreditUsed;
    
        return $this;
    }

    /**
     * Get totalCreditUsed
     *
     * @return integer 
     */
    public function getTotalCreditUsed()
    {
        return $this->totalCreditUsed;
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
     * Set cashPaid
     *
     * @param integer $cashPaid
     * @return Transaction
     */
    public function setCashPaid($cashPaid)
    {
        $this->cashPaid = $cashPaid;
    
        return $this;
    }

    /**
     * Get cashPaid
     *
     * @return integer 
     */
    public function getCashPaid()
    {
        return $this->cashPaid;
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
     * Set statusDate
     *
     * @param \DateTime $statusDate
     * @return Transaction
     */
    public function setStatusDate($statusDate)
    {
        $this->statusDate = $statusDate;
    
        return $this;
    }

    /**
     * Get statusDate
     *
     * @return \DateTime 
     */
    public function getStatusDate()
    {
        return $this->statusDate;
    }

    /**
     * Set CreditDisbursalStatus
     *
     * @param string $creditDisbursalStatus
     * @return Transaction
     */
    public function setCreditDisbursalStatus($creditDisbursalStatus)
    {
        $this->CreditDisbursalStatus = $creditDisbursalStatus;
    
        return $this;
    }

    /**
     * Get CreditDisbursalStatus
     *
     * @return string 
     */
    public function getCreditDisbursalStatus()
    {
        return $this->CreditDisbursalStatus;
    }

    /**
     * Set DisbursalDate
     *
     * @param \DateTime $disbursalDate
     * @return Transaction
     */
    public function setDisbursalDate($disbursalDate)
    {
        $this->DisbursalDate = $disbursalDate;
    
        return $this;
    }

    /**
     * Get DisbursalDate
     *
     * @return \DateTime 
     */
    public function getDisbursalDate()
    {
        return $this->DisbursalDate;
    }

    /**
     * Set ParentTransactionId
     *
     * @param integer $parentTransactionId
     * @return Transaction
     */
    public function setParentTransactionId($parentTransactionId)
    {
        $this->ParentTransactionId = $parentTransactionId;
    
        return $this;
    }

    /**
     * Get ParentTransactionId
     *
     * @return integer 
     */
    public function getParentTransactionId()
    {
        return $this->ParentTransactionId;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     * @return Transaction
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
    
        return $this;
    }

    /**
     * Get remarks
     *
     * @return string 
     */
    public function getRemarks()
    {
        return $this->remarks;
    }
}
