<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Favourite
 */
class Favourite
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
    private $storeId;

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
     * @return Favourite
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
     * Set storeId
     *
     * @param integer $storeId
     * @return Favourite
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    
        return $this;
    }

    /**
     * Get storeId
     *
     * @return integer 
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Favourite
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