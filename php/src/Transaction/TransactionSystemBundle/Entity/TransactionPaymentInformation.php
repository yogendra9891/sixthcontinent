<?php

namespace Transaction\TransactionSystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransactionPaymentInformation
 */
class TransactionPaymentInformation
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
    private $correlationId;

    /**
     * @var integer
     */
    private $build;

    /**
     * @var string
     */
    private $payKey;

    /**
     * @var string
     */
    private $paypalId;

    /**
     * @var string
     */
    private $paymentExecStatus;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $primaryUserEmail;

    /**
     * @var integer
     */
    private $primaryUserAmount;

    /**
     * @var string
     */
    private $secondryUserEmail;

    /**
     * @var integer
     */
    private $secondryUserAmount;

    /**
     * @var \DateTime
     */
    private $timeInitH;

    /**
     * @var \DateTime
     */
    private $timeUpdatedH;

    /**
     * @var integer
     */
    private $timeInit;

    /**
     * @var integer
     */
    private $timeUpdated;

    /**
     * @var string
     */
    private $paymentSerialize;


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
     * @return TransactionPaymentInformation
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
     * Set correlationId
     *
     * @param string $correlationId
     * @return TransactionPaymentInformation
     */
    public function setCorrelationId($correlationId)
    {
        $this->correlationId = $correlationId;
    
        return $this;
    }

    /**
     * Get correlationId
     *
     * @return string 
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    /**
     * Set build
     *
     * @param integer $build
     * @return TransactionPaymentInformation
     */
    public function setBuild($build)
    {
        $this->build = $build;
    
        return $this;
    }

    /**
     * Get build
     *
     * @return integer 
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * Set payKey
     *
     * @param string $payKey
     * @return TransactionPaymentInformation
     */
    public function setPayKey($payKey)
    {
        $this->payKey = $payKey;
    
        return $this;
    }

    /**
     * Get payKey
     *
     * @return string 
     */
    public function getPayKey()
    {
        return $this->payKey;
    }

    /**
     * Set paypalId
     *
     * @param string $paypalId
     * @return TransactionPaymentInformation
     */
    public function setPaypalId($paypalId)
    {
        $this->paypalId = $paypalId;
    
        return $this;
    }

    /**
     * Get paypalId
     *
     * @return string 
     */
    public function getPaypalId()
    {
        return $this->paypalId;
    }

    /**
     * Set paymentExecStatus
     *
     * @param string $paymentExecStatus
     * @return TransactionPaymentInformation
     */
    public function setPaymentExecStatus($paymentExecStatus)
    {
        $this->paymentExecStatus = $paymentExecStatus;
    
        return $this;
    }

    /**
     * Get paymentExecStatus
     *
     * @return string 
     */
    public function getPaymentExecStatus()
    {
        return $this->paymentExecStatus;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return TransactionPaymentInformation
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
     * Set primaryUserEmail
     *
     * @param string $primaryUserEmail
     * @return TransactionPaymentInformation
     */
    public function setPrimaryUserEmail($primaryUserEmail)
    {
        $this->primaryUserEmail = $primaryUserEmail;
    
        return $this;
    }

    /**
     * Get primaryUserEmail
     *
     * @return string 
     */
    public function getPrimaryUserEmail()
    {
        return $this->primaryUserEmail;
    }

    /**
     * Set primaryUserAmount
     *
     * @param integer $primaryUserAmount
     * @return TransactionPaymentInformation
     */
    public function setPrimaryUserAmount($primaryUserAmount)
    {
        $this->primaryUserAmount = $primaryUserAmount;
    
        return $this;
    }

    /**
     * Get primaryUserAmount
     *
     * @return integer 
     */
    public function getPrimaryUserAmount()
    {
        return $this->primaryUserAmount;
    }

    /**
     * Set secondryUserEmail
     *
     * @param string $secondryUserEmail
     * @return TransactionPaymentInformation
     */
    public function setSecondryUserEmail($secondryUserEmail)
    {
        $this->secondryUserEmail = $secondryUserEmail;
    
        return $this;
    }

    /**
     * Get secondryUserEmail
     *
     * @return string 
     */
    public function getSecondryUserEmail()
    {
        return $this->secondryUserEmail;
    }

    /**
     * Set secondryUserAmount
     *
     * @param integer $secondryUserAmount
     * @return TransactionPaymentInformation
     */
    public function setSecondryUserAmount($secondryUserAmount)
    {
        $this->secondryUserAmount = $secondryUserAmount;
    
        return $this;
    }

    /**
     * Get secondryUserAmount
     *
     * @return integer 
     */
    public function getSecondryUserAmount()
    {
        return $this->secondryUserAmount;
    }

    /**
     * Set timeInitH
     *
     * @param \DateTime $timeInitH
     * @return TransactionPaymentInformation
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
     * Set timeUpdatedH
     *
     * @param \DateTime $timeUpdatedH
     * @return TransactionPaymentInformation
     */
    public function setTimeUpdatedH($timeUpdatedH)
    {
        $this->timeUpdatedH = $timeUpdatedH;
    
        return $this;
    }

    /**
     * Get timeUpdatedH
     *
     * @return \DateTime 
     */
    public function getTimeUpdatedH()
    {
        return $this->timeUpdatedH;
    }

    /**
     * Set timeInit
     *
     * @param integer $timeInit
     * @return TransactionPaymentInformation
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
     * Set timeUpdated
     *
     * @param integer $timeUpdated
     * @return TransactionPaymentInformation
     */
    public function setTimeUpdated($timeUpdated)
    {
        $this->timeUpdated = $timeUpdated;
    
        return $this;
    }

    /**
     * Get timeUpdated
     *
     * @return integer 
     */
    public function getTimeUpdated()
    {
        return $this->timeUpdated;
    }

    /**
     * Set paymentSerialize
     *
     * @param string $paymentSerialize
     * @return TransactionPaymentInformation
     */
    public function setPaymentSerialize($paymentSerialize)
    {
        $this->paymentSerialize = $paymentSerialize;
    
        return $this;
    }

    /**
     * Get paymentSerialize
     *
     * @return string 
     */
    public function getPaymentSerialize()
    {
        return $this->paymentSerialize;
    }
}