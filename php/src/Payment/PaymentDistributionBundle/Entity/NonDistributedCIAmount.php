<?php

namespace Payment\PaymentDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NonDistributedCIAmount
 */
class NonDistributedCIAmount
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $purchaserId;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var integer
     */
    private $transactionId;


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
     * Set purchaserId
     *
     * @param integer $purchaserId
     * @return NonDistributedCIAmount
     */
    public function setPurchaserId($purchaserId)
    {
        $this->purchaserId = $purchaserId;
    
        return $this;
    }

    /**
     * Get purchaserId
     *
     * @return integer 
     */
    public function getPurchaserId()
    {
        return $this->purchaserId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return NonDistributedCIAmount
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
     * Set amount
     *
     * @param integer $amount
     * @return NonDistributedCIAmount
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
     * Set transactionId
     *
     * @param integer $transactionId
     * @return NonDistributedCIAmount
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
}