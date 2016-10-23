<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopFollowers
 */
class ShopFollowers
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
     * @var integer
     */
    private $shopId;

    /**
     * @var \DateTime
     */
    private $createdAt;


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
     * @return ShopFollowers
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
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopFollowers
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ShopFollowers
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}