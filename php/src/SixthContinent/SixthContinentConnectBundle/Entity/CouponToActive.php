<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CouponToActive
 */
class CouponToActive
{
   
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $offerId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var \DateTime
     */
    private $importedAt;

    /**
     * @var string
     */
    private $importedTimestamp;

    /**
     * @var \DateTime
     */
    private $expiredDate;

    /**
     * @var string
     */
    private $expiredDateTimestamp;

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var \DateTime
     */
    private $isActivatedAt;

    /**
     * @var string
     */
    private $isActivatedTimestamp;

    /**
     * @var integer
     */
    private $is_active;

    /**
     * @var integer
     */
    private $isDeleted;


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
     * Set offerId
     *
     * @param integer $offerId
     * @return CouponToActive
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
     * Set userId
     *
     * @param integer $userId
     * @return CouponToActive
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
     * Set importedAt
     *
     * @param \DateTime $importedAt
     * @return CouponToActive
     */
    public function setImportedAt($importedAt)
    {
        $this->importedAt = $importedAt;
    
        return $this;
    }

    /**
     * Get importedAt
     *
     * @return \DateTime 
     */
    public function getImportedAt()
    {
        return $this->importedAt;
    }

    /**
     * Set importedTimestamp
     *
     * @param string $importedTimestamp
     * @return CouponToActive
     */
    public function setImportedTimestamp($importedTimestamp)
    {
        $this->importedTimestamp = $importedTimestamp;
    
        return $this;
    }

    /**
     * Get importedTimestamp
     *
     * @return string 
     */
    public function getImportedTimestamp()
    {
        return $this->importedTimestamp;
    }

    /**
     * Set expiredDate
     *
     * @param \DateTime $expiredDate
     * @return CouponToActive
     */
    public function setExpiredDate($expiredDate)
    {
        $this->expiredDate = $expiredDate;
    
        return $this;
    }

    /**
     * Get expiredDate
     *
     * @return \DateTime 
     */
    public function getExpiredDate()
    {
        return $this->expiredDate;
    }

    /**
     * Set expiredDateTimestamp
     *
     * @param string $expiredDateTimestamp
     * @return CouponToActive
     */
    public function setExpiredDateTimestamp($expiredDateTimestamp)
    {
        $this->expiredDateTimestamp = $expiredDateTimestamp;
    
        return $this;
    }

    /**
     * Get expiredDateTimestamp
     *
     * @return string 
     */
    public function getExpiredDateTimestamp()
    {
        return $this->expiredDateTimestamp;
    }

    /**
     * Set orderNumber
     *
     * @param string $orderNumber
     * @return CouponToActive
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    
        return $this;
    }

    /**
     * Get orderNumber
     *
     * @return string 
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * Set isActivatedAt
     *
     * @param \DateTime $isActivatedAt
     * @return CouponToActive
     */
    public function setIsActivatedAt($isActivatedAt)
    {
        $this->isActivatedAt = $isActivatedAt;
    
        return $this;
    }

    /**
     * Get isActivatedAt
     *
     * @return \DateTime 
     */
    public function getIsActivatedAt()
    {
        return $this->isActivatedAt;
    }

    /**
     * Set isActivatedTimestamp
     *
     * @param string $isActivatedTimestamp
     * @return CouponToActive
     */
    public function setIsActivatedTimestamp($isActivatedTimestamp)
    {
        $this->isActivatedTimestamp = $isActivatedTimestamp;
    
        return $this;
    }

    /**
     * Get isActivatedTimestamp
     *
     * @return string 
     */
    public function getIsActivatedTimestamp()
    {
        return $this->isActivatedTimestamp;
    }

    /**
     * Set is_active
     *
     * @param integer $isActive
     * @return CouponToActive
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
    
        return $this;
    }

    /**
     * Get is_active
     *
     * @return integer 
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set isDeleted
     *
     * @param integer $isDeleted
     * @return CouponToActive
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    
        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return integer 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
    
    /**
     * @var string
     */
    private $orderNumberFromImport;


    /**
     * Set orderNumberFromImport
     *
     * @param string $orderNumberFromImport
     * @return CouponToActive
     */
    public function setOrderNumberFromImport($orderNumberFromImport)
    {
        $this->orderNumberFromImport = $orderNumberFromImport;
    
        return $this;
    }

    /**
     * Get orderNumberFromImport
     *
     * @return string 
     */
    public function getOrderNumberFromImport()
    {
        return $this->orderNumberFromImport;
    }
}