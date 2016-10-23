<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecurringPendingPayment
 */
class RecurringPendingPayment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var float
     */
    private $pendingamount;

    /**
     * @var integer
     */
    private $transactionId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $paid;

    /**
     * @var \DateTime
     */
    private $created_at;


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
     * Set shopId
     *
     * @param integer $shopId
     * @return RecurringPendingPayment
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
     * Set pendingamount
     *
     * @param float $pendingamount
     * @return RecurringPendingPayment
     */
    public function setPendingamount($pendingamount)
    {
        $this->pendingamount = $pendingamount;
    
        return $this;
    }

    /**
     * Get pendingamount
     *
     * @return float 
     */
    public function getPendingamount()
    {
        return $this->pendingamount;
    }

    /**
     * Set transactionId
     *
     * @param integer $transactionId
     * @return RecurringPendingPayment
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
     * Set type
     *
     * @param string $type
     * @return RecurringPendingPayment
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set paid
     *
     * @param integer $paid
     * @return RecurringPendingPayment
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    
        return $this;
    }

    /**
     * Get paid
     *
     * @return integer 
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return RecurringPendingPayment
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
}