<?php

namespace Transaction\TransactionSystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BookTransaction
 */
class BookTransaction
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $timeInitH;

    /**
     * @var \DateTime
     */
    private $timeUpdateStatusH;

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
    private $buyerId;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var integer
     */
    private $transactionId;

    /**
     * @var integer
     */
    private $withCredit;


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
     * @param integer $status
     * @return BookTransaction
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set timeInitH
     *
     * @param \DateTime $timeInitH
     * @return BookTransaction
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
     * @return BookTransaction
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
     * Set timeInit
     *
     * @param integer $timeInit
     * @return BookTransaction
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
     * @return BookTransaction
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
     * Set buyerId
     *
     * @param integer $buyerId
     * @return BookTransaction
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
     * @return BookTransaction
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
     * Set transactionId
     *
     * @param integer $transactionId
     * @return BookTransaction
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
     * Set withCredit
     *
     * @param integer $withCredit
     * @return BookTransaction
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
}