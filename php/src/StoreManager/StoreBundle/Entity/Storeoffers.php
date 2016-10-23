<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Storeoffers
 */
class Storeoffers
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
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $discountPosition;

    /**
     * @var integer
     */
    private $shots;
    


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
     * @return Storeoffers
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
     * Set userId
     *
     * @param integer $userId
     * @return Storeoffers
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
     * Set discountPosition
     *
     * @param integer $discountPosition
     * @return Storeoffers
     */
    public function setDiscountPosition($discountPosition)
    {
        $this->discountPosition = $discountPosition;
    
        return $this;
    }

    /**
     * Get discountPosition
     *
     * @return integer 
     */
    public function getDiscountPosition()
    {
        return $this->discountPosition;
    }

    /**
     * Set shots
     *
     * @param integer $shots
     * @return Storeoffers
     */
    public function setShots($shots)
    {
        $this->shots = $shots;
    
        return $this;
    }

    /**
     * Get shots
     *
     * @return integer 
     */
    public function getShots()
    {
        return $this->shots;
    }
    /**
     * @var integer
     */
    private $affilationAmount;


    /**
     * Set affilationAmount
     *
     * @param integer $affilationAmount
     * @return Storeoffers
     */
    public function setAffilationAmount($affilationAmount)
    {
        $this->affilationAmount = $affilationAmount;
    
        return $this;
    }

    /**
     * Get affilationAmount
     *
     * @return integer 
     */
    public function getAffilationAmount()
    {
        return $this->affilationAmount;
    }
}