<?php

namespace Payment\TransactionProcessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransactionDetails
 */
class TransactionDetails
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $transactionId;

    /**
     * @var string
     */
    private $creditCode;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var integer
     */
    private $dSellerId;

    /**
     * @var integer
     */
    private $dBuyerId;

    /**
     * @var integer
     */
    private $initialAmount;

    /**
     * @var integer
     */
    private $amountUsed;

    /**
     * @var integer
     */
    private $amountBalanced;


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
     * Set transactionId
     *
     * @param integer $transactionId
     * @return TransactionDetails
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return integer 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set creditCode
     *
     * @param string $creditCode
     * @return TransactionDetails
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
     * Set reason
     *
     * @param string $reason
     * @return TransactionDetails
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    
        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set dSellerId
     *
     * @param integer $dSellerId
     * @return TransactionDetails
     */
    public function setDSellerId($dSellerId)
    {
        $this->dSellerId = $dSellerId;
    
        return $this;
    }

    /**
     * Get dSellerId
     *
     * @return integer 
     */
    public function getDSellerId()
    {
        return $this->dSellerId;
    }

    /**
     * Set dBuyerId
     *
     * @param integer $dBuyerId
     * @return TransactionDetails
     */
    public function setDBuyerId($dBuyerId)
    {
        $this->dBuyerId = $dBuyerId;
    
        return $this;
    }

    /**
     * Get dBuyerId
     *
     * @return integer 
     */
    public function getDBuyerId()
    {
        return $this->dBuyerId;
    }

    /**
     * Set initialAmount
     *
     * @param integer $initialAmount
     * @return TransactionDetails
     */
    public function setInitialAmount($initialAmount)
    {
        $this->initialAmount = $initialAmount;
    
        return $this;
    }

    /**
     * Get initialAmount
     *
     * @return integer 
     */
    public function getInitialAmount()
    {
        return $this->initialAmount;
    }

    /**
     * Set amountUsed
     *
     * @param integer $amountUsed
     * @return TransactionDetails
     */
    public function setAmountUsed($amountUsed)
    {
        $this->amountUsed = $amountUsed;
    
        return $this;
    }

    /**
     * Get amountUsed
     *
     * @return integer 
     */
    public function getAmountUsed()
    {
        return $this->amountUsed;
    }

    /**
     * Set amountBalanced
     *
     * @param integer $amountBalanced
     * @return TransactionDetails
     */
    public function setAmountBalanced($amountBalanced)
    {
        $this->amountBalanced = $amountBalanced;
    
        return $this;
    }

    /**
     * Get amountBalanced
     *
     * @return integer 
     */
    public function getAmountBalanced()
    {
        return $this->amountBalanced;
    }
}
