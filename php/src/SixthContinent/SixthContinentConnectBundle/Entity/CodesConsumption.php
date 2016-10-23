<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CodesConsumption
 */
class CodesConsumption
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $typeId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $offerId;

    /**
     * @var integer
     */
    private $codeId;


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
     * Set userId
     *
     * @param integer $userId
     * @return CodesConsumption
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set typeId
     *
     * @param string $typeId
     * @return CodesConsumption
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    
        return $this;
    }

    /**
     * Get typeId
     *
     * @return string 
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return CodesConsumption
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
     * Set offerId
     *
     * @param string $offerId
     * @return CodesConsumption
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    
        return $this;
    }

    /**
     * Get offerId
     *
     * @return string 
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * Set codeId
     *
     * @param integer $codeId
     * @return CodesConsumption
     */
    public function setCodeId($codeId)
    {
        $this->codeId = $codeId;
    
        return $this;
    }

    /**
     * Get codeId
     *
     * @return integer 
     */
    public function getCodeId()
    {
        return $this->codeId;
    }
    /**
     * @var integer
     */
    private $transactionId;

    /**
     * @var integer
     */
    private $couponId;

    /**
     * @var string
     */
    private $coupon;


    /**
     * Set transactionId
     *
     * @param integer $transactionId
     * @return CodesConsumption
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
     * Set couponId
     *
     * @param integer $couponId
     * @return CodesConsumption
     */
    public function setCouponId($couponId)
    {
        $this->couponId = $couponId;
    
        return $this;
    }

    /**
     * Get couponId
     *
     * @return integer 
     */
    public function getCouponId()
    {
        return $this->couponId;
    }

    /**
     * Set coupon
     *
     * @param string $coupon
     * @return CodesConsumption
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
    
        return $this;
    }

    /**
     * Get coupon
     *
     * @return string 
     */
    public function getCoupon()
    {
        return $this->coupon;
    }
    /**
     * @var \DateTime
     */
    private $date;


    /**
     * Set date
     *
     * @param \DateTime $date
     * @return CodesConsumption
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }
}